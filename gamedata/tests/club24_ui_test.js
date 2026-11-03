// node gamedata/tests/club24_ui_test.js — 执行真实JS，XHR只记录请求，不连接站点。
const fs = require('fs');
const path = require('path');
const vm = require('vm');
const assert = require('assert');
const root = path.resolve(__dirname, '../..');
const mode = {name:'mode',type:'hidden',value:'itemfind'};
const command = {name:'command',type:'hidden',value:'back'};
const form = {elements:[mode,command]};
const sent = [];
let confirmed = false, prompt = '';
const context = vm.createContext({
    document:{forms:{gamecmd:form},getElementById:id=>({mode,command}[id])},
    window:{confirm:text=>{prompt=text; return confirmed;}},
    XMLHttpRequest:function(){
        this.open=()=>{}; this.setRequestHeader=()=>{};
        this.send=body=>sent.push(new URLSearchParams(body));
    },
});
for(const file of ['include/common.js','include/game20130526.js']) vm.runInContext(fs.readFileSync(path.join(root,file),'utf8'),context);
assert.strictEqual(context.useClub24Card(6),false);
assert.strictEqual(sent.length,0);
assert.strictEqual(mode.value,'itemfind');
assert(prompt.includes('——你确定要正式入伙吗？') && prompt.includes('\n\n「想好了吗？') && prompt.endsWith('——瑞克·拉玛尔'));
confirmed=true;
for(const slot of [0,6]) {
    context.lastRun=0;
    assert.strictEqual(context.useClub24Card(slot),true);
    const body=sent[sent.length-1];
    assert.strictEqual(body.get('mode'),'command');
    assert.strictEqual(body.get('command'),'itm'+slot);
    assert.strictEqual(body.get('c24_confirm'),'1');
}
// 尸体页的command是radio且没有同名id；技能使用请求必须覆盖旧指令。
form.elements=[{name:'mode',type:'hidden',value:'corpse'},{name:'command',type:'radio',value:'wep',checked:true},{name:'c24_reforge_target',type:'text',value:'0E'}];
context.lastRun=0;
context.postCmd('gamecmd','command.php',{data:{mode:'revskpts',command:'actskill_c24_reforge'}});
const body=sent[sent.length-1];
assert.deepStrictEqual(body.getAll('command'),['actskill_c24_reforge']);
assert.strictEqual(body.get('mode'),'revskpts');
assert.strictEqual(body.get('c24_reforge_target'),'0E');
console.log('PASS: 两段确认；取消不提交；背包/发现页入社；尸体页技能参数覆盖。');
