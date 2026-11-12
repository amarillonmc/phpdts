// 执行原有表单序列化和 AJAX 提交流程，验证临时页面没有隐藏 mode/id 时也能提交。
const fs = require('fs');
const path = require('path');
const vm = require('vm');
const assert = require('assert');
const root = path.resolve(__dirname, '../..');
const sent = [];
const context = {
    navigator: {userAgent: 'test'},
    window: {},
    document: {forms: {}, getElementById: () => null},
    console, Date, setTimeout, clearTimeout, setInterval, clearInterval,
    zXmlHttp: {createRequest: () => ({open() {}, setRequestHeader() {}, send(body) {sent.push(body);}})},
    showNotice(message) {throw new Error(message);}
};
context.window = context;
vm.createContext(context);
vm.runInContext(fs.readFileSync(path.join(root, 'include/common.js'), 'utf8'), context);
vm.runInContext(fs.readFileSync(path.join(root, 'include/game20130526.js'), 'utf8'), context);
context.zXmlHttp.createRequest = () => ({open() {}, setRequestHeader() {}, send(body) {sent.push(body);}});
context.showNotice = message => {throw new Error(message);};
for (const oldMode of ['command', 'itemmain', 'corpse', 'revcombat', 'depot']) {
    context.document.forms.gamecmd = {elements: [
        {name: 'mode', value: oldMode, type: 'hidden'},
        {name: 'command', value: 'old', type: 'radio', checked: true},
        {name: 'command', value: 'ignored', type: 'radio', checked: false},
        {name: 'wc_target', value: '大师球', type: 'text'}
    ]};
    context.lastRun = 0;
    context.wheelchairCmd('wc_route_find');
    const body = new URLSearchParams(sent.pop());
    assert.deepStrictEqual(body.getAll('mode'), ['command']);
    assert.deepStrictEqual(body.getAll('command'), ['wc_route_find']);
    assert.strictEqual(body.get('wc_target'), '大师球');
    console.log('PASS: ' + oldMode + ' 页面提交唯一辅助指令并保留目标输入');
}
context.document.forms.gamecmd = {elements: [{name: 'wc_hunt_kind', value: 'minion', type: 'select-one'}]};
context.lastRun = 0;
context.wheelchairCmd('wc_hunt_set');
const body = new URLSearchParams(sent.pop());
assert.strictEqual(body.get('mode'), 'command');
assert.strictEqual(body.get('command'), 'wc_hunt_set');
assert.strictEqual(body.get('wc_hunt_kind'), 'minion');
console.log('PASS: 没有原有 mode/command 输入的临时页面');
context.lastRun = 0;
context.wheelchairCmd('wc_hunt_act');
assert.strictEqual(new URLSearchParams(sent.pop()).get('mode'), 'revcombat');
console.log('PASS: 追猎战斗进入现有 revcombat 入口');
const elements = {cmd: {innerHTML: ''}, log: {innerHTML: ''}};
context.document.getElementById = id => elements[id] || null;
context.responseBody = JSON.stringify({innerHTML: {cmd: '<div class="wheelchair-panel">战力 520</div><input name="mode" value="command">', log: '【整备】补给完成'}});
vm.runInContext('String.prototype.parseJSON = function () { return JSON.parse(String(this)); }; showData(responseBody);', context);
assert.ok(elements.cmd.innerHTML.includes('战力 520'));
assert.strictEqual(elements.log.innerHTML, '【整备】补给完成');
console.log('PASS: 现有 AJAX 刷新实际更新战力面板、指令区和日志');

for (const theme of ['default', 'nouveau']) {
    delete context.CURSCRIPT;
    context.document.body = {style: {}};
    const page = fs.readFileSync(path.join(root, 'templates', theme, 'game.htm'), 'utf8');
    const boot = Array.from(page.matchAll(/<script[^>]*>([\s\S]*?)<\/script>/g)).map(match => match[1]).find(script => script.includes('CURSCRIPT'));
    vm.runInContext(boot || '', context);
    context.responseBody = JSON.stringify({innerHTML: {cmd: '七封送行', pls: '动物大排档'}, locationId: 34});
    assert.strictEqual(vm.runInContext('showData(responseBody);', context), true);
    assert.strictEqual(elements.cmd.innerHTML, '七封送行');
    assert.strictEqual(context.document.body.style.backgroundImage, "url('img/location/34.jpg')");
    console.log('PASS: ' + theme + ' 页面初始化后可完整处理地点与指令 AJAX 响应');
}
