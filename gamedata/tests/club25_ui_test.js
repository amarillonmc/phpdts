// node gamedata/tests/club25_ui_test.js — 执行真实请求代码，不连接站点。
const fs = require('fs');
const path = require('path');
const vm = require('vm');
const assert = require('assert');
const root = path.resolve(__dirname, '../..');
const sent = [];
const form = {elements: []};
const elements = {gamecmd: form, 'c25-target': {value:'0'}, 'c25-count': {value:'3'}, 'c25-kind': {value:'poison'}};
const context = vm.createContext({
    document: {forms: {gamecmd: form}, getElementById: id => elements[id]},
    window: {},
    XMLHttpRequest: function() {
        this.open = () => {}; this.setRequestHeader = () => {};
        this.send = body => sent.push(new URLSearchParams(body));
    }
});
for (const file of ['include/common.js','include/game20130526.js','include/json.js']) vm.runInContext(fs.readFileSync(path.join(root,file),'utf8'),context);
for (const currentMode of ['command','itemfind','corpse','revcombat']) {
    const mode = {name:'mode',type:'hidden',value:currentMode};
    form.elements = [mode,{name:'command',type:'radio',value:'wep',checked:true}];
    context.lastRun = 0;
    context.club25Cmd('trip_deploy');
    const body = sent[sent.length-1];
    assert.strictEqual(body.get('mode'),'club25');
    assert.deepStrictEqual(body.getAll('command'),['trip_deploy']);
    assert.strictEqual(body.has('c25_return_mode'),false);
    assert.strictEqual(body.get('c25_target'),'0');
    assert.strictEqual(body.get('c25_count'),'3');
    assert.strictEqual(body.get('c25_kind'),'poison');
    assert.strictEqual(mode.value,currentMode);
}
context.lastRun = 0;
context.club25Cmd('withdraw','t123');
assert.strictEqual(sent[sent.length-1].get('c25_target'),'t123');
context.lastRun = 0;
context.club25Net('i456');
assert.strictEqual(sent[sent.length-1].get('mode'),'revcombat');
assert.strictEqual(sent[sent.length-1].get('command'),'bskill_c25_net');
assert.strictEqual(sent[sent.length-1].get('c25_net_item'),'i456');
console.log('PASS: 可怜猫主指令、发现页、尸体页、战斗页参数覆盖；撤回与收网目标；原表单保持不变。');

// 执行侧栏脚本和模板中的点击处理，覆盖技能按钮冒泡到“点击外部关闭”的情况。
const panelTemplate = fs.readFileSync(path.join(root,'templates/default/slidingpanel.htm'),'utf8');
const panelScript = panelTemplate.slice(panelTemplate.lastIndexOf('<script>') + 8, panelTemplate.lastIndexOf('</script>'));
function panelElement() {
    const classes = new Set();
    return {style: {}, classList: {
        add: name => classes.add(name), remove: name => classes.delete(name), contains: name => classes.has(name)
    }};
}
for (const id of ['sliding-panel','side-panel-button','c25-tab-button','misc-tab','c25-tab','c25-board']) elements[id] = panelElement();
const panel = elements['sliding-panel'];
panel.contains = target => target === elements['c25-board'];
let documentClick;
context.document.addEventListener = (name, handler) => { if (name === 'click') documentClick = handler; };
context.document.querySelectorAll = selector => selector === '.tab-pane'
    ? [elements['misc-tab'],elements['c25-tab']] : [elements['c25-tab-button']];
context.window.addEventListener = () => {};
vm.runInContext(panelScript,context);
context.initPanel();
const closeHandler = panelTemplate.match(/class="close-button" onclick="([^"]+)"/)[1];
for (const skill of ['modify','hide','field','trip']) {
    const template = fs.readFileSync(path.join(root,'templates/default/skill_c25_'+skill+'.htm'),'utf8');
    const input = template.match(/<input[^>]+value="打开可怜猫"[^>]*>/)[0];
    const target = {closest: selector => input.includes(selector.slice(1,-1)) ? target : null};
    context.lastRun = 0;
    vm.runInContext(input.match(/onclick="([^"]+)"/)[1],context);
    documentClick({target});
    assert.strictEqual(context.isPanelOpen(),true,skill+' 技能入口不能立即被外部点击关闭');
    assert.strictEqual(elements['c25-tab'].classList.contains('active'),true);
    assert.strictEqual(elements['side-panel-button'].style.display,'none');
    assert.strictEqual(sent[sent.length-1].get('command'),'board');

    context.responseBody = JSON.stringify({innerHTML: {'c25-board':'刷新后的布场'}, display: {'c25-tab-button':''}});
    vm.runInContext('showData(responseBody);',context);
    documentClick({target:elements['c25-board']});
    assert.strictEqual(elements['c25-board'].innerHTML,'刷新后的布场');
    assert.strictEqual(context.isPanelOpen(),true,'刷新与面板内点击保持打开');
    vm.runInContext(closeHandler,context);
    assert.strictEqual(context.isPanelOpen(),false,'X 按钮移除打开状态');
    assert.strictEqual(elements['side-panel-button'].style.display,'block');
    vm.runInContext('showData(responseBody);',context);
    assert.strictEqual(context.isPanelOpen(),false,'稍后到达的指令响应不能重新打开面板');
}
context.lastRun = 0;
context.club25Open();
documentClick({target:{closest:()=>null}});
assert.strictEqual(context.isPanelOpen(),false,'普通外部点击仍可关闭');
console.log('PASS: 四个技能入口、面板内点击、X 关闭、重新打开、外部点击与 AJAX 刷新状态。');
