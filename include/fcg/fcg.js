(() => {
  'use strict';
  const boot = JSON.parse(document.getElementById('fcg-bootstrap').textContent);
  const app = document.getElementById('fcg-app'), content = document.getElementById('fcg-content');
  const dialog = document.getElementById('fcg-dialog'), dialogContent = document.getElementById('fcg-dialog-content');
  const colors = {W:['白','#d8d9e0'],R:['红','#f08e95'],Y:['黄','#edcf7c'],G:['绿','#82d2ae'],B:['蓝','#89b6ef']};
  const esc = value => String(value).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  let state=boot.state, tab=boot.shop?'shop':(state.match&&!state.match.result?'battle':'lobby'), shopCategory='fcg_pack';
  let draft=state.deck.slice(), busy=false, dirty=false, clockOffset=state.now*1000-Date.now(), tutorialStep=0;
  let filter={color:'all',rarity:'all',search:'',owned:false};
  let activeRequest=Promise.resolve(), mutationPending=false, lastBattle='';
  const counts = list => list.reduce((out,id)=>{out[id]=(out[id]||0)+1;return out;},{});
  const notice = text => { document.getElementById('fcg-notice').textContent=text; };
  function modal(html) { dialogContent.innerHTML=html; if(!dialog.open) dialog.showModal(); }
  const token = () => Array.from(crypto.getRandomValues(new Uint8Array(16)),x=>x.toString(16).padStart(2,'0')).join('');
  function send(action, values={}) {
    if((action==='status' && (busy||mutationPending)) || (action!=='status' && mutationPending)) return Promise.resolve(false);
    if(action!=='status') mutationPending=true;
    const next=activeRequest.then(()=>performSend(action,values)).finally(()=>{if(action!=='status')mutationPending=false;});
    activeRequest=next.catch(()=>{});
    return next;
  }
  async function performSend(action, values) {
    busy=true;
    const payload={action,csrf:boot.csrf,rev:state.rev,token:token(),...values};
    if(state.match) payload.match=state.match.id;
    const controller=new AbortController(), timeout=setTimeout(()=>controller.abort(),15000);
    try {
      const response=await fetch('fcg.php',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload),signal:controller.signal});
      const result=await response.json();
      if(!response.ok || result.error) throw new Error(result.error||'暂时无法连接牌桌。');
      state=result.state; clockOffset=state.now*1000-Date.now();
      if(!dirty) draft=state.deck.slice();
      if(action!=='status') notice('');
      if(action==='start') tab='battle';
      if(action==='deck') {dirty=false;draft=state.deck.slice();notice('卡组已保存，下局使用这副牌。');}
      // Do not replace a focused search field/deck editor during background polling.
      if(action!=='status' || (tab==='battle' && JSON.stringify(state.match)!==lastBattle)) render(); else renderWallet();
      if(action==='buy' || action==='embers') reward(state.last_reward);
      return true;
    } catch(error) {
      notice(error.name==='AbortError' || error.message==='Failed to fetch'?'连接中断。请刷新状态后重试，已保存的收藏不会丢失。':error.message);
      if(action!=='status') setTimeout(()=>send('status').then(()=>{if(tab!=='deck') render();}),250);
      return false;
    } finally {clearTimeout(timeout);busy=false;}
  }
  function renderWallet() {
    document.getElementById('fcg-balance').textContent=state.credits.toLocaleString();
    document.getElementById('fcg-collection-count').textContent=Object.keys(state.collection).length+' / 150 张图鉴';
  }
  function card(id, mode, slot=0) {
    const c=boot.cards[id], owned=state.collection[id]||0, used=counts(draft)[id]||0;
    let actions='', cost=c.cost;
    if(mode==='hand') {
      const s=state.match.sides[0]; if(s.free[slot]) cost=0;
      const allowed=s.energy[c.color]>=cost && !(c.type==='monster'&&s.monster) && !state.match.result;
      actions='<div class="fcg-card-actions"><button data-action="play" data-slot="'+slot+'" '+(!allowed?'disabled':'')+'>出牌</button><button data-action="discard" data-slot="'+slot+'" title="弃牌：每回合回血减 1">弃牌</button></div>';
    } else if(mode==='deck') {
      const allowed=used<owned && (c.unlimited||used<7) && draft.length<100;
      actions='<div class="fcg-card-count">持有 '+owned+' · 已放 '+used+'</div><div class="fcg-card-actions"><button data-action="remove" data-card="'+id+'" '+(!used?'disabled':'')+'>−</button><button data-action="add" data-card="'+id+'" '+(!allowed?'disabled':'')+'>＋</button></div>';
    }
    return '<article class="fcg-card '+(c.upgraded?'upgraded ':'')+(c.rare?'rare ':'')+(mode==='deck'&&!owned?'unowned':'')+'" style="--tone:'+colors[c.color][1]+'">'+
      '<img src="'+esc(c.art)+'" alt="'+esc(c.name)+'" data-action="detail" data-card="'+id+'" loading="lazy"><span class="fcg-cost" title="'+colors[c.color][0]+'色能量">'+cost+'</span>'+
      '<div class="fcg-card-body"><h3>'+esc(c.name)+'</h3><p>'+colors[c.color][0]+' · '+(c.upgraded?'已升级':'未升级')+(c.rare?' · 稀有':'')+'</p>'+
      (c.type==='monster'?'<p>⚔ '+c.atk+'　▣ '+c.def+'　♥ '+c.hp+'</p>':'')+'<p class="fcg-card-rules" title="'+esc(c.rules)+'">'+esc(c.rules)+'</p></div>'+actions+'<i class="fcg-rarity" aria-hidden="true"></i></article>';
  }
  function detail(id, heading='') {
    const c=boot.cards[id];
    modal((heading?'<h2>'+esc(heading)+'</h2>':'')+'<div class="fcg-detail"><img src="'+esc(c.art)+'" alt="'+esc(c.name)+'"><div><p class="fcg-eyebrow">'+colors[c.color][0]+' / '+(c.upgraded?'已升级':'未升级')+(c.rare?' / 稀有':'')+'</p><h2>'+esc(c.name)+'</h2><p>费用 '+c.cost+' · '+(c.type==='monster'?'驻场单位　攻 '+c.atk+'／防 '+c.def+'／生命 '+c.hp:'法术')+'</p><p>'+esc(c.rules)+'</p><p class="fcg-flavor">'+esc(c.flavor)+'</p><p class="fcg-muted">持有 '+(state.collection[id]||0)+' 张'+(c.unlimited?' · 能量牌不受同卡 7 张限制':' · 每副卡组最多 7 张')+'</p></div></div>');
  }
  function reward(r) {
    if(!r) return;
    if(r.card!==undefined) detail(r.card,r.overflow?'溢出卡片已化作 1 枚余烬印花':'新的记忆，已收入卡册');
    else modal('<h2>获得头衔「'+esc(boot.titles[r.title]||r.title)+'」</h2><p>在个人资料中装备，下次进入常规游戏时生效。</p><a href="user_profile.php">打开个人资料</a>');
  }
  function lobby() {
    const playing=state.match&&!state.match.result;
    let html=(!state.in_room?'<div class="fcg-note">卡册和商店随时开放。开始对战前，请在 <a href="index.php">大厅房间列表</a> 的「创建异色房间」中选择 FCG，或加入已有 FCG 房间。</div>':'');
    if(playing) html+='<div class="fcg-note">牌局仍在进行中。<button data-tab="battle">回到牌桌</button></div>';
    html+='<div class="fcg-section-head"><h2>陪练的小家伙们</h2><span class="fcg-muted">赢过 '+Object.keys(state.wins).length+' / 17 位</span></div><p class="fcg-muted">对战免费。胜利会随机获得对手本局牌组中的一张卡；失败与平局不扣除卡片。</p>';
    for(const [lo,hi,title] of [[0,5,'初次相逢 · Lv.1–2'],[5,11,'外面的回声'],[11,17,'更深处的记忆']]) {
      html+='<div class="fcg-section-head"><h3>'+title+'</h3></div><div class="fcg-opponents">';
      for(const o of boot.opponents.slice(lo,hi)) html+='<article class="fcg-opponent">'+(state.wins[o.id]?'<span class="fcg-won">已胜 '+state.wins[o.id]+' 次</span>':'')+'<img src="'+esc(o.portrait)+'" alt="'+esc(o.name)+'"><div><h3>'+esc(o.name)+'</h3><span class="fcg-muted">Lv.'+o.level+' · '+o.hp+' 生命</span></div><button data-action="start" data-opponent="'+o.id+'" '+(!state.in_room||playing?'disabled':'')+'>坐下来打一局</button></article>';
      html+='</div>';
    }
    html+='<p class="fcg-footer-note">击败全部 17 位牌手，或收齐 150 张图鉴，可获得终极头衔「具现的暗影」。'+(state.titles.includes(9106)?'你已获得这一头衔。':'')+'</p>'; return html;
  }
  function side(s, name, enemy) {
    let monster='<span class="fcg-muted">没有驻场帮手</span>';
    if(s.monster) {const m=s.monster,c=boot.cards[m.id];monster='<div class="fcg-monster"><img src="'+esc(c.art)+'" alt="'+esc(c.name)+'" data-action="detail" data-card="'+m.id+'"><div><b>'+esc(c.name)+'</b><br>⚔ '+m.atk+' ▣ '+m.def+' ♥ '+m.hp+(m.frozen?'<br>冻结 '+m.frozen+' 回合':'')+'</div></div>';}
    return '<div class="fcg-side"><div><h3>'+esc(name)+'</h3><div class="fcg-stats"><span>♥ '+Math.max(0,s.hp)+' / 200</span><span>攻击 '+s.atk+(s.bonus?'＋'+s.bonus:'')+'</span><span>防御 '+s.def+'</span><span>每回合回血 '+s.regen+'</span><span>牌库 '+s.deck_count+'</span>'+(enemy?'<span>手牌 '+s.hand_count+'</span>':'')+'</div><div class="fcg-hp"><i style="width:'+Math.max(0,Math.min(100,s.hp/2))+'%"></i></div><div class="fcg-energy">'+Object.keys(colors).map(c=>'<span style="--tone:'+colors[c][1]+'" title="'+colors[c][0]+'能量 / 每回合产能">'+colors[c][0]+' '+s.energy[c]+' <small>＋'+s.gen[c]+'</small></span>').join('')+'</div></div><div>'+monster+'</div></div>';
  }
  function battle() {
    const m=state.match;
    if(!m) return '<div class="fcg-note">还没有开始牌局。<button data-tab="lobby">挑选牌手</button></div>';
    let html='';
    if(m.result) html='<div class="fcg-result"><h2>'+({win:'小种火把一张记忆交给了你。',loss:'这次，是小家伙赢了。',draw:'两团火苗，同时熄灭。'}[m.result])+'</h2>'+(m.reward?'<p>获得「'+esc(boot.cards[m.reward.card].name)+'」'+(m.reward.overflow?'，转为余烬印花 ×1':'')+'</p><button data-action="detail" data-card="'+m.reward.card+'">查看战利品</button> ':'')+'<button data-tab="lobby">再找一位牌手</button></div>';
    html+='<div class="fcg-match-status"><strong>第 '+m.turn+' 回合</strong><div class="fcg-timer"><i id="fcg-timer-bar"></i></div><span id="fcg-seconds">—</span></div><div class="fcg-duel">'+side(m.sides[0],'你的牌桌',false)+side(m.sides[1],boot.opponents[m.opponent].name,true)+'</div>';
    if(!m.result) html+='<div class="fcg-hand">'+m.sides[0].hand.map((id,i)=>card(id,'hand',i)).join('')+'</div><div class="fcg-stats"><button data-action="destroy" '+(!m.sides[0].monster?'disabled':'')+'>解散帮手</button><button data-action="surrender">认输</button><span>双方同时行动 · 出牌续 1 秒 · 满手或抽空牌库会增加中毒</span></div>';
    return html+'<h3 style="margin-top:18px">牌桌记事</h3><div class="fcg-log" id="fcg-log">'+m.log.map(line=>'<p>'+esc(line)+'</p>').join('')+'</div>';
  }
  function renderDeckGrid() {
    const list=boot.cards.filter(c=>(filter.color==='all'||c.color===filter.color)&&(!filter.owned||state.collection[c.id])&&(!filter.search||c.name.includes(filter.search)||c.rules.includes(filter.search))&&(filter.rarity==='all'||(filter.rarity==='rare'?c.rare:filter.rarity==='upgraded'?c.upgraded:!c.upgraded)));
    document.getElementById('fcg-deck-grid').innerHTML=list.map(c=>card(c.id,'deck')).join('');
    const used=counts(draft);
    document.getElementById('fcg-deck-list').innerHTML='<h3>当前构筑 · '+draft.length+' 张</h3><p class="fcg-muted">25–100 张，同卡通常最多 7 张。普通与升级卡分别计算。</p>'+Object.keys(used).map(id=>'<div class="fcg-deck-row"><span>'+esc(boot.cards[id].name)+' ×'+used[id]+'</span><button data-action="remove" data-card="'+id+'" aria-label="移除一张'+esc(boot.cards[id].name)+'">−</button></div>').join('')+'<button data-action="save-deck">'+(dirty?'保存改动':'保存卡组')+'</button><button data-action="clear-deck">清空构筑</button>'+(dirty?'<button data-action="restore-deck">还原已保存的卡组</button>':'');
  }
  function deck() {
    return '<h2>每一张，都是独立的记忆。</h2><p class="fcg-muted">点击卡图阅读完整规则和卡面叙述。卡组改动保存后，从下一局开始使用。</p><div class="fcg-filters"><input id="fcg-search" aria-label="搜索卡名或规则" placeholder="搜索卡名或规则" value="'+esc(filter.search)+'"><select id="fcg-color" aria-label="颜色"><option value="all">全部颜色</option>'+Object.keys(colors).map(c=>'<option value="'+c+'" '+(filter.color===c?'selected':'')+'>'+colors[c][0]+'</option>').join('')+'</select><select id="fcg-rarity" aria-label="版本与稀有度">'+[['all','全部版本'],['base','未升级'],['upgraded','已升级'],['rare','稀有']].map(([v,n])=>'<option value="'+v+'" '+(filter.rarity===v?'selected':'')+'>'+n+'</option>').join('')+'</select><label><input type="checkbox" id="fcg-owned" '+(filter.owned?'checked':'')+'> 只看已拥有</label></div><div class="fcg-deck-layout"><div class="fcg-card-grid" id="fcg-deck-grid"></div><aside class="fcg-deck-list" id="fcg-deck-list"></aside></div>';
  }
  function hosts(ruby,snow) {
    return '<div class="fcg-hosts"><div class="fcg-host"><img src="img/fcg/hosts/ruby.png" alt="埃尔文·露比"><div><strong>埃尔文·露比</strong><p>'+esc(ruby)+'</p></div></div><div class="fcg-host"><img src="img/fcg/hosts/snow.png" alt="保琳·斯诺"><div><strong>保琳·斯诺</strong><p>'+esc(snow)+'</p></div></div></div>';
  }
  function poolFor(item) {
    const p=JSON.parse(item.reward_payload);
    return boot.cards.filter(c=>(p.upgraded===undefined||c.upgraded===p.upgraded)&&(p.rare===undefined||c.rare===p.rare)&&(!p.rare_only||c.rare)&&(!p.color||p.color==='all'||c.color===p.color));
  }
  function shop() {
    let html=hosts(shopCategory==='fcg_pack'?'卡包里只有一张。先看范围再买，别把切糕袋子直接倒过来。':'纪念品记在你的名下。买过的头衔不会再收一次钱。',shopCategory==='fcg_pack'?'今天的小涂鸦也都装好了！多出来的记忆还能贴进余烬册呢。':'名字会亮起来耶！这个闪闪发光的，真的只是好看吗？');
    html+='<div class="fcg-shop-tabs"><button data-shop="fcg_pack">种火卡包铺</button><button data-shop="title">头衔与奇物专柜</button></div><div class="fcg-products">';
    const today=new Date(state.now*1000).toISOString().slice(0,10);
    for(const item of boot.catalog.filter(i=>shopCategory==='fcg_pack'?i.category==='fcg_pack':i.category!=='fcg_pack')) {
      const p=JSON.parse(item.reward_payload), c=state.purchases[item.item_id]||{daily:0,total:0,day:today};
      const owned=item.reward_type==='title'&&state.titles.includes(p.title);
      const limited=(+item.daily_limit>0&&c.day===today&&c.daily>=+item.daily_limit)||(+item.total_limit>0&&c.total>=+item.total_limit);
      const unsupported=!['title','fcg_pack'].includes(item.reward_type);
      html+='<article class="fcg-product"><h3>'+esc(item.name)+'</h3><p>'+esc(item.description)+'</p><small>'+(+item.daily_limit?'今日剩余 '+Math.max(0,+item.daily_limit-(c.day===today?c.daily:0))+' 次':'')+(+item.total_limit?' · 每账号限 '+item.total_limit+' 次':'')+'</small><div class="fcg-price">'+(+item.price_credits2?Number(item.price_credits2).toLocaleString()+' 切糕':'免费')+'</div>'+(item.reward_type==='fcg_pack'?'<small>每包 1 张 · 范围内各卡等概率</small><button data-action="pool" data-item="'+item.item_id+'">查看卡池 · '+poolFor(item).length+' 张</button>':'')+'<button data-action="buy" data-item="'+item.item_id+'" '+(owned||limited||unsupported||state.credits<+item.price_credits2?'disabled':'')+'>'+(owned?'已经拥有':limited?'已达限购':unsupported?'暂未开放':+item.price_credits2?'购买并领取':'领取试手包')+'</button></article>';
    }
    html+='</div><div class="fcg-note"><h3>余烬回收桶 · '+state.embers+' 枚印花</h3><p>非能量卡持有超过 7 张时，溢出卡自动换为 1 枚印花。能量卡不回收；印花与卡片都不兑换切糕。</p><button data-action="embers" data-kind="upgraded" '+(state.embers<20?'disabled':'')+'>20 枚 · 深潜重构包</button> <button data-action="embers" data-kind="rare" '+(state.embers<50?'disabled':'')+'>50 枚 · 核心残响包</button></div><p><a href="user_profile.php">前往个人资料装备头衔</a></p>';return html;
  }
  function render() {
    app.classList.toggle('fcg-playing',tab==='battle'&&!!state.match);
    if(tab==='battle') lastBattle=JSON.stringify(state.match);
    renderWallet(); app.querySelectorAll('.fcg-nav [data-tab]').forEach(b=>b.classList.toggle('active',b.dataset.tab===tab));
    content.innerHTML=({lobby,battle,deck,shop}[tab]||lobby)();
    if(tab==='deck') renderDeckGrid();
    const log=document.getElementById('fcg-log'); if(log) log.scrollTop=log.scrollHeight;
    tick();
  }
  function tick() {
    const seconds=document.getElementById('fcg-seconds'),bar=document.getElementById('fcg-timer-bar');
    if(!seconds||!state.match) return;
    const remain=Math.max(0,state.match.deadline-(Date.now()+clockOffset)/1000);
    seconds.textContent=state.match.result?'已结束':remain.toFixed(1)+' 秒';
    if(bar) bar.style.width=(state.match.result?0:Math.min(100,remain/(state.match.turn===1?10:5)*100))+'%';
  }
  const lessons=[
    ['为什么打牌','欢迎来到梦境与代码的夹缝——好啦其实也没那么玄乎！如你所见，下面那群不会好好说话的小家伙（小种火），最近迷上了把在幻境里见到的东西压成扁扁的卡片。','准确来说，那群小豆丁只是顺着本能在收集外面的情报而已。但光攒着数据没法消化，所以它们就照着某游戏那种老掉牙的套路，把记忆碎片做成了能互相对撞的牌局——顺便还塞了你一副最基础的陪练卡组。'],
    ['五秒，同时行动','打起来很简单！每回合大家同时掏牌，谁先把对面的体力削光谁就赢！只要手里的五色火苗（能量）够用，一回合你想甩多少张牌出去都行哦！','前提是你手速够快。第一回合给你 10 秒热身，之后每回合只有 5 秒——当然，每打出一张牌会给你续 1 秒。记住先打发电卡把每回合自动涨的能量提上去，不然抓一手大牌也只能干瞪眼。'],
    ['两次攻击，各算各的','还有还有，你可以自己提刀上去砍（玩家攻击），也可以叫一个帮手驻场（怪物攻击）！如果帮手把对面的帮手打飞了，多出来的力气还会顺便糊在对面脸上！','但注意了——你自己的攻击和帮手的攻击是分开算防御的！如果对面堆了 10 点防御，你 7 攻、帮手 9 攻，两边破不了防就是 0 伤害，别算错了！另外手牌满了（6 张）或者牌库抽干了还硬抽，是会直接叠中毒（负回血）的。'],
    ['新的记忆，从哪里来','打赢那些小家伙的话，它们一高兴就会从自己的牌堆里随便抖落一张卡送你！要是你看到有些长得很像熟人的新种火……嘘，别问，问就是它们自己捏着玩的！','嫌刷得慢、或者想弄点已经升级好的深层卡片，就带着你的切糕来我们店里买补充包。行了，去跟新手村那五只小家伙练手吧！']
  ];
  function tutorial(step=0) {
    tutorialStep=step; const l=lessons[step];
    modal('<p class="fcg-tutorial-progress">课间 '+(step+1)+' / 4</p><h2>'+l[0]+'</h2>'+hosts(l[2],l[1])+'<div class="fcg-dialog-actions"><button data-action="lesson-prev" '+(step===0?'disabled':'')+'>上一幕</button><button data-action="lesson-next">'+(step===3?'去认识小家伙们':'下一幕')+'</button></div>');
  }
  app.addEventListener('click',async event=>{
    const el=event.target.closest('[data-action],[data-tab],[data-shop],.fcg-dialog-close');
    if(!el || el.disabled) return;
    if(el.classList.contains('fcg-dialog-close')) {dialog.close();return;}
    if(el.dataset.tab) {tab=el.dataset.tab;render();return;}
    if(el.dataset.shop) {shopCategory=el.dataset.shop;render();return;}
    const a=el.dataset.action,id=Number(el.dataset.card),slot=Number(el.dataset.slot);
    if(a==='detail') {detail(id);return;}
    if(a==='tutorial') {tutorial();return;}
    if(a==='lesson-prev') {tutorial(Math.max(0,tutorialStep-1));return;}
    if(a==='lesson-next') {if(tutorialStep<3)tutorial(tutorialStep+1);else{dialog.close();await send('tutorial');}return;}
    if(a==='pool') {
      const item=boot.catalog.find(i=>+i.item_id===Number(el.dataset.item)); const pool=poolFor(item);
      modal('<h2>'+esc(item.name)+'</h2><p>每包随机 1 张，以下 '+pool.length+' 张卡等概率（各约 '+(100/pool.length).toFixed(2)+'%）。</p><div class="fcg-card-grid">'+pool.map(c=>card(c.id,'preview')).join('')+'</div>');return;
    }
    if(a==='add') {
      const c=boot.cards[id],n=counts(draft)[id]||0;
      if(draft.length<100&&n<(state.collection[id]||0)&&(c.unlimited||n<7)){draft.push(id);dirty=true;renderDeckGrid();}return;
    }
    if(a==='remove') {const at=draft.indexOf(id);if(at>=0){draft.splice(at,1);dirty=true;renderDeckGrid();}return;}
    if(a==='clear-deck') {draft=[];dirty=true;renderDeckGrid();return;}
    if(a==='restore-deck') {draft=state.deck.slice();dirty=false;notice('已还原上次保存的卡组。');renderDeckGrid();return;}
    if(a==='save-deck') {await send('deck',{deck:draft});return;}
    if(a==='start') {await send('start',{opponent:Number(el.dataset.opponent)});return;}
    if(a==='play'||a==='discard') {await send(a,{slot});return;}
    if(a==='destroy') {await send(a);return;}
    if(a==='surrender') {modal('<h2>收起这一局的牌？</h2><p>认输不会失去收藏，本局不会获得胜利卡片。</p><button data-action="surrender-confirm">确认认输</button>');return;}
    if(a==='surrender-confirm') {dialog.close();await send('surrender');return;}
    if(a==='buy') {const item=boot.catalog.find(i=>+i.item_id===Number(el.dataset.item));await send('buy',{item:+item.item_id,price:+item.price_credits2});return;}
    if(a==='embers') await send(a,{kind:el.dataset.kind});
  });
  app.addEventListener('input',event=>{
    if(event.target.id==='fcg-search'){filter.search=event.target.value;renderDeckGrid();}
  });
  app.addEventListener('change',event=>{
    const id=event.target.id;
    if(id==='fcg-color')filter.color=event.target.value;
    else if(id==='fcg-rarity')filter.rarity=event.target.value;
    else if(id==='fcg-owned')filter.owned=event.target.checked;else return;
    renderDeckGrid();
  });
  render();
  if(!state.tutorial) tutorial();
  setInterval(tick,100);
  setInterval(()=>{if(!document.hidden && !busy && state.match && !state.match.result) send('status');},1000);
  document.addEventListener('visibilitychange',()=>{if(!document.hidden)send('status');});
  window.addEventListener('beforeunload',event=>{if(dirty){event.preventDefault();event.returnValue='';}});
})();
