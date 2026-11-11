// node gamedata/tests/account_rewards_ui_test.js
// 执行 PHP 渲染后的表单事件、真实序列化与 AJAX 回包处理，不以源码字符串代替行为。
const fs = require('fs');
const path = require('path');
const vm = require('vm');
const assert = require('assert');
const {execFileSync} = require('child_process');
const root = path.resolve(__dirname,'../..');
const pages = JSON.parse(execFileSync('php',['-d','short_open_tag=1',path.join(__dirname,'account_rewards_ui_test.php')],{encoding:'utf8'}));
const decode = value => value.replace(/&quot;/g,'"').replace(/&#0?39;/g,"'").replace(/&lt;/g,'<').replace(/&gt;/g,'>').replace(/&amp;/g,'&');
function attributes(tag) {
    const out = {};
    for(const match of tag.matchAll(/([\w-]+)\s*=\s*(?:"([^"]*)"|'([^']*)')/g)) out[match[1]] = decode(match[2] ?? match[3]);
    return out;
}
for(const theme of Object.keys(pages)) {
    const elements = {}, forms = {}, requests = [];
    const context = vm.createContext({document:{forms,getElementById:id=>elements[id] || null},window:{},navigator:{userAgent:'test'},console,setTimeout,clearTimeout});
    for(const file of ['include/common.js','include/game20130526.js','include/json.js']) vm.runInContext(fs.readFileSync(path.join(root,file),'utf8'),context);
    context.showNotice = message => { throw new Error(message); };
    context.zXmlHttp.createRequest = () => {
        const xhr = {open(method,url){this.url=url;},setRequestHeader(){},send(body){requests.push({xhr:this,body:new URLSearchParams(body)});}};
        return xhr;
    };
    function run(handler,element={}) { context.element=element; context.event={stopPropagation(){}}; context.lastRun=0; return vm.runInContext('(function(){'+handler+'}).call(element)',context); }
    function mount(html,name) {
        const match = [...html.matchAll(/<form\b([^>]*)>([\s\S]*?)<\/form>/g)].find(m=>attributes(m[1]).name===name);
        assert(match,'rendered form '+name);
        const form = {elements:[],...attributes(match[1])}; forms[name]=form;
        for(const m of match[2].matchAll(/<input\b([^>]*)>/g)) {
            const el = {checked:false,disabled:/\sdisabled\b/.test(m[1]),value:'',...attributes(m[1])}; form.elements.push(el); if(el.id) elements[el.id]=el;
            el.click=()=>{el.checked=!el.checked;if(el.onchange)run(el.onchange,el);};
        }
        for(const m of match[2].matchAll(/<select\b([^>]*)>([\s\S]*?)<\/select>/g)) {
            const options=[...m[2].matchAll(/<option\b([^>]*)>/g)].map(o=>attributes(o[1]));
            form.elements.push({type:'select-one',selectedIndex:0,options,...attributes(m[1])});
        }
        return form;
    }
    const mailForm=mount(pages[theme].messages,'message_cmd');
    const checkbox=elements.message_sl7;
    checkbox.click();
    const claimButton=[...pages[theme].messages.matchAll(/<input\b([^>]*)>/g)].map(m=>attributes(m[1])).find(a=>a.value==='查收附件');
    run(claimButton.onclick);
    assert.strictEqual(requests.at(-1).body.get('sl7'),'1');
    assert.strictEqual(requests.at(-1).body.get('reward_csrf'),'mail-csrf');
    checkbox.click();
    const link=[...pages[theme].messages.matchAll(/<a\b([^>]*)>/g)].map(m=>attributes(m[1])).find(a=>a.onclick);
    run(link.onclick);
    assert.deepStrictEqual(requests.at(-1).body.getAll('sl7'),['1']);
    assert.strictEqual(requests.at(-1).body.get('mode'),'check');
    elements.info={}; elements.messages={};
    context.shm=()=>{}; context.jQuery=()=>({find:()=>({each(){}})});
    context.showData(JSON.stringify({innerHTML:{info:'领取成功',messages:pages[theme].claimed}}));
    assert.strictEqual(elements.info.innerHTML,'领取成功');
    assert(elements.messages.innerHTML.includes('附件已收'));
    assert(!elements.messages.innerHTML.includes('<测试赐福>'),'boon names escaped in HTML');
    assert(![...pages[theme].deleted.matchAll(/<a\b([^>]*)>/g)].map(m=>attributes(m[1])).some(a=>a.onclick),'trash cannot submit an inbox claim');
    const redeemForm=mount(pages[theme].user,'activation_form');
    elements.activation_code.value='新手福利';
    run(redeemForm.onsubmit,redeemForm);
    assert.strictEqual(requests.at(-1).body.get('mode'),'redeem');
    assert.strictEqual(requests.at(-1).body.get('activation_code'),'新手福利');
    assert.strictEqual(requests.at(-1).body.get('activation_request'),'a'.repeat(32));
    elements.activation_info={};
    context.showData(JSON.stringify({innerHTML:{activation_info:'奖励已发送'},value:{activation_request:'c'.repeat(32),activation_code:''}}));
    assert.strictEqual(elements.activation_request.value,'c'.repeat(32));
    assert.strictEqual(elements.activation_code.value,'');
    const adminFields=[...pages[theme].admin.matchAll(/<input\b([^>]*)>/g)].map(m=>attributes(m[1]));
    assert.strictEqual(adminFields.find(a=>a.name==='stitle').value,'标题 " <标签>');
    const generateForm=mount(pages[theme].admin,'activation_generate_form');
    assert.strictEqual(elements.activation_custom.maxlength,'32');
    elements.activation_custom.value='新手福利'; run(elements.activation_custom.oninput,elements.activation_custom);
    const submitted=()=>Object.fromEntries(generateForm.elements.filter(e=>e.name && !e.disabled).map(e=>[e.name,e.type==='select-one' ? e.options[e.selectedIndex].value : e.value]));
    assert.deepStrictEqual(['bound_username','seed','count'].filter(name=>name in submitted()),[]);
    assert.strictEqual(submitted().custom_code,'新手福利');
    assert.strictEqual(submitted().reward_csrf,'admin-csrf');
    for(const once of [0,1]) for(const limited of [0,1]) {
        for(const [name,value] of [['per_account_once',once],['limited',limited]]) {
            const field=generateForm.elements.find(e=>e.name===name);
            field.selectedIndex=field.options.findIndex(o=>o.value===String(value));
        }
        assert.strictEqual(submitted().per_account_once,String(once));
        assert.strictEqual(submitted().limited,String(limited));
    }
    elements.activation_custom.value=''; run(elements.activation_custom.oninput,elements.activation_custom);
    assert.strictEqual(submitted().seed,'b'.repeat(64));
    assert.strictEqual(submitted().count,'1');
    mount(pages[theme].admin_custom,'activation_generate_form');
    assert.strictEqual(elements.activation_custom.value,'新手福利');
    assert(['activation_bound','activation_seed','activation_count'].every(id=>elements[id].disabled));
    mount(pages[theme].admin_invalid,'activation_generate_form');
    assert.strictEqual(elements.activation_custom.value,'" onfocus="alert(1)');
    assert.strictEqual(elements.activation_custom.onfocus,undefined);
    const adminForm=mount(pages[theme].admin_users,'urpage');
    assert.strictEqual(adminForm.elements.find(a=>a.name==='reward_csrf').value,'admin-csrf');
    assert(adminForm.elements.some(a=>a.name==='boon_att'));
    console.log('PASS: '+theme+' 模板编译、邮件领取和刷新、中文码兑换、自定义码表单与四种次数组合、后台赐福表单');
}
