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
for (const file of ['include/common.js','include/game20130526.js']) vm.runInContext(fs.readFileSync(path.join(root,file),'utf8'),context);
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
