(() => {
  'use strict';
  const boot = JSON.parse(document.getElementById('fcg-bootstrap').textContent);
  const app = document.getElementById('fcg-app'), content = document.getElementById('fcg-content');
  const dialog = document.getElementById('fcg-dialog'), dialogContent = document.getElementById('fcg-dialog-content');
  const colors = {W:['白','#d8d9e0'],R:['红','#f08e95'],Y:['黄','#edcf7c'],G:['绿','#82d2ae'],B:['蓝','#89b6ef']};
  const esc = value => String(value).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  let state=boot.state, tab=boot.shop?'shop':(state.match&&!state.match.result?'battle':state.run?'draft':'lobby'), shopCategory='fcg_pack';
  let draft=state.deck.slice(), busy=false, dirty=false, clockOffset=state.now*1000-Date.now(), tutorialStep=0;
  let filter={cost:'all',color:'all',rarity:'all',series:'all',keyword:'all',search:'',owned:false,inDeck:false}, inspectId=null, rewardView=null, stickerOpen=false;
  let sampleHand=[], referenceSearch='', lastRun='', shownRunOver='';
  let uiMode='classic', tblExpanded=false, pileLogSide=null, logOpen=false, tblViewport=null;
  let prevMatchView=null;
  try {if(localStorage.getItem('fcg-ui')==='table') uiMode='table';} catch(e) {}
  const keywordEntries={};
  for(const c of boot.cards) {
    c.keywords=[...new Set(c.glossary.flatMap(g=>g.keywords))];
    for(const g of c.glossary) for(const key of g.keywords) {
      if(!keywordEntries[key]) keywordEntries[key]=[];
      if(!keywordEntries[key].some(entry=>entry.label===g.label&&entry.text===g.text)) keywordEntries[key].push({...g,card:c.id});
    }
  }
  const keywords=Object.keys(keywordEntries).sort((a,b)=>a.localeCompare(b,'zh-CN'));
  const available=id=>(state.available||state.collection)[id]||0;
  const num=value=>'<strong class="fcg-num">'+esc(value)+'</strong>';
  const socialPanel=document.createElement('div'); socialPanel.id='fcg-social'; content.before(socialPanel);
  const runPanel=document.createElement('div'); runPanel.id='fcg-run'; content.before(runPanel);
  const settingsPanel=document.createElement('section'); settingsPanel.id='fcg-settings'; settingsPanel.className='fcg-settings'; settingsPanel.hidden=true;
  settingsPanel.setAttribute('role','dialog'); settingsPanel.setAttribute('aria-label','牌桌设置');
  settingsPanel.innerHTML='<div class="fcg-tbl-panel-head"><h2>牌桌设置</h2><button type="button" data-action="settings-close" aria-label="关闭设置">×</button></div><h3>对战界面</h3><div class="fcg-settings-options"><button type="button" data-action="set-ui" data-ui="classic">经典牌桌</button><button type="button" data-action="set-ui" data-ui="table">新牌桌</button></div><p>选择保存在本浏览器；新牌桌仍在打磨，对局中可随时切回经典。</p>';
  app.append(settingsPanel);
  const pilePanel=document.createElement('section'); pilePanel.id='fcg-pile-log'; pilePanel.className='fcg-tbl-panel'; pilePanel.hidden=true; pilePanel.setAttribute('aria-label','本回合出牌记录'); app.append(pilePanel);
  const logPanel=document.createElement('section'); logPanel.id='fcg-log-panel'; logPanel.className='fcg-tbl-panel'; logPanel.hidden=true; logPanel.setAttribute('aria-label','牌桌记事'); app.append(logPanel);
  const tableFx=document.createElement('div');tableFx.className='fcg-tbl-fx';tableFx.setAttribute('aria-hidden','true');app.append(tableFx);
  const tableFxTimers=new Set();
  let activeRequest=Promise.resolve(), mutationPending=false, lastBattle='';
  const counts = list => list.reduce((out,id)=>{out[id]=(out[id]||0)+1;return out;},{});
  const notice = text => { document.getElementById('fcg-notice').textContent=text; };
  function modal(html) { dialogContent.innerHTML=html; if(!dialog.open) dialog.showModal(); dialog.scrollTop=0; }
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
    if(state.run && payload.run===undefined) payload.run=state.run.id;
    const controller=new AbortController(), timeout=setTimeout(()=>controller.abort(),15000);
    try {
      const response=await fetch('fcg.php',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload),signal:controller.signal});
      const result=await response.json();
      if(!response.ok || result.error) throw new Error(result.error||'暂时无法连接牌桌。');
      const previousMatch=state.match&&state.match.id;
      state=result.state; clockOffset=state.now*1000-Date.now();
      if(state.match&&state.match.id!==previousMatch) {stickerOpen=false;tblExpanded=false;pileLogSide=null;logOpen=false;inspectId=null;}
      if(!dirty) draft=state.deck.slice();
      if(action!=='status') notice('');
      if(['start','mirror','accept','draft_battle'].includes(action)||(state.match&&state.match.live&&!state.match.result&&state.match.id!==previousMatch)) tab='battle';
      if(['draft_enter','draft_pick','draft_node','draft_abandon'].includes(action)) tab='draft';
      if(action==='draft_reward') tab='draft';
      if(action==='deck') {dirty=false;draft=state.deck.slice();notice('卡组已保存，下局使用这副牌。');}
      if(action==='invite') notice('邀请已发出，请等待对方回应。');
      // Do not replace a focused search field/deck editor during background polling.
      if(action!=='status' || tab==='lobby' || (tab==='draft' && JSON.stringify(state.run)!==lastRun) || (tab==='battle' && JSON.stringify(state.match)!==lastBattle)) render(); else {renderWallet();renderSocial();renderRun();}
      fitTable();
      if(action==='buy' || action==='embers' || action==='draft_reward') reward(state.last_reward);
      return true;
    } catch(error) {
      notice(error.name==='AbortError' || error.message==='Failed to fetch'?'连接中断。请刷新状态后重试，已保存的收藏不会丢失。':error.message);
      if(action!=='status') setTimeout(()=>send('status').then(()=>{if(tab!=='deck') render();}),250);
      return false;
    } finally {clearTimeout(timeout);busy=false;}
  }
  function renderWallet() {
    document.getElementById('fcg-balance').textContent=state.credits.toLocaleString();
    document.getElementById('fcg-collection-count').textContent=Object.keys(state.collection).length+' / '+boot.cards.length+' 张图鉴';
  }
  function cardStats(c) {
    return '⚔ <b class="fcg-card-atk">'+esc(c.atk)+'</b> 🛡 <b class="fcg-card-def">'+esc(c.def)+'</b> ♥ <b class="fcg-card-hp">'+esc(c.hp)+'</b>';
  }
  function card(id, mode, slot=0) {
    const c=boot.cards[id], owned=available(id), used=counts(draft)[id]||0, temp=state.temp_cards[id]||0;
    let actions='', cost=c.cost;
    if(mode==='hand') {
      const s=state.match.sides[0]; if(s.free[slot]) cost=0;
      const burn=s.monster&&!s.monster.frozen&&s.monster.abilities.includes('aura_burn_monster_heal');
      const allowed=s.energy[c.color]>=cost && !(c.type==='monster'&&s.monster&&!c.escort&&!burn) && !state.match.result;
      const attrs=' data-card="'+id+'" data-turn="'+state.match.turn+'" data-slot="'+slot+'"';
      actions='<div class="fcg-card-actions"><button data-action="play"'+attrs+' '+(!allowed?'disabled':'')+'>出牌</button><button data-action="discard"'+attrs+' title="弃牌：每回合回血减 1">弃牌</button></div>';
    } else if(mode==='deck') {
      const allowed=used<owned && (c.unlimited||used<7) && draft.length<100;
      actions='<div class="fcg-card-count">可用 '+owned+' · 已放 '+used+(temp?' · ✨借牌 '+temp:'')+'</div><div class="fcg-card-actions"><button data-action="remove" data-card="'+id+'" '+(!used?'disabled':'')+'>−</button><button data-action="add" data-card="'+id+'" '+(!allowed?'disabled':'')+'>＋</button></div>';
    } else if(mode.startsWith('draft-')) {
      const label={'draft-pick':'选入远征卡组','draft-reinforce':'选择这张补强','draft-upgrade':'查看升级','draft-remove':'移除这张卡'}[mode];
      actions='<div class="fcg-card-count">远征幻影</div><div class="fcg-card-actions"><button data-action="'+(mode==='draft-upgrade'?'draft-upgrade-preview':mode)+'" data-card="'+id+'" data-slot="'+slot+'" data-run="'+esc(state.run.id)+'" data-layer="'+state.run.layer+'" data-pick="'+state.run.picks.length+'"'+(mode==='draft-remove'?' data-removed="'+state.run.node.removed+'"':'')+' '+(!state.in_room?'disabled':'')+'>'+label+'</button></div>';
    }
    return '<article data-inspect="'+id+'" class="fcg-card '+(c.upgraded?'upgraded ':'')+(c.rare?'rare ':'')+(mode==='deck'&&!owned?'unowned':'')+'" style="--tone:'+colors[c.color][1]+'">'+
      '<img src="'+esc(c.art)+'" alt="'+esc(c.name)+'" data-action="detail" data-card="'+id+'" data-back="'+(['draft-upgrade','draft-remove'].includes(mode)?mode+'-back':'')+'" tabindex="0" loading="lazy"><span class="fcg-cost" title="'+colors[c.color][0]+'色能量">'+num(cost)+'</span>'+
      '<div class="fcg-card-body"><h3>'+esc(c.name)+'</h3><p>'+colors[c.color][0]+' · '+(c.upgraded?'已升级':'未升级')+(c.rare?' · 稀有':'')+'</p>'+
      (c.type==='monster'?'<p>'+cardStats(c)+'</p>':'')+'<p class="fcg-card-rules">'+c.short_rules+'</p></div>'+actions+'<i class="fcg-rarity" aria-hidden="true"></i></article>';
  }
  function glossary(c) {const numbers=s=>esc(s).replace(/\d+(?:\.\d+)?/g,n=>num(n));return '<div class="fcg-glossary"><h3>词条详解</h3>'+c.glossary.map(g=>'<p><b>'+numbers(g.label)+'</b><br>'+numbers(g.text)+'</p>').join('')+'</div>';}
  function inspect(id) {
    const node=document.getElementById('fcg-inspector'); if(!node||!boot.cards[id])return;
    inspectId=id;const c=boot.cards[id];
    node.innerHTML='<img src="'+esc(c.art)+'" alt="'+esc(c.name)+'"><h3>'+esc(c.name)+'</h3><p><span class="fcg-cost fcg-cost-inline" style="--tone:'+colors[c.color][1]+'" title="'+colors[c.color][0]+'色能量" aria-label="费用 '+c.cost+'，'+colors[c.color][0]+'色能量">'+num(c.cost)+'</span>'+(c.type==='monster'?' · '+cardStats(c):'')+'</p>'+glossary(c);
    node.hidden=false;
  }
  function detail(id, heading='', back='') {
    const c=boot.cards[id];
    modal((heading?'<h2>'+esc(heading)+'</h2>':'')+'<div class="fcg-detail"><img src="'+esc(c.art)+'" alt="'+esc(c.name)+'"><div><p class="fcg-eyebrow">'+(c.series==='vol2'?'Vol.2 红杀演武':'Vol.1 种火初啼')+' · '+colors[c.color][0]+' / '+(c.upgraded?'已升级':'未升级')+(c.rare?' / 稀有':'')+'</p><h2>'+esc(c.name)+'</h2><p><span class="fcg-cost fcg-cost-inline" style="--tone:'+colors[c.color][1]+'" title="'+colors[c.color][0]+'色能量" aria-label="费用 '+c.cost+'，'+colors[c.color][0]+'色能量">'+num(c.cost)+'</span> · '+(c.type==='monster'?cardStats(c):'法术')+'</p><p class="fcg-flavor">'+esc(c.flavor)+'</p><p class="fcg-muted">永久持有 '+(state.collection[id]||0)+' 张'+(state.temp_cards[id]?' · ✨本房借牌 '+state.temp_cards[id]:'')+'</p>'+glossary(c)+'</div></div>'+(back?'<button data-action="'+back+'">'+({'reward-back':'返回开包一览','sample-back':'返回范例手牌','keyword-reference':'返回关键字速查表','draft-upgrade-back':'返回升级选卡','draft-remove-back':'返回移除选卡'}[back])+'</button>':''));
  }
  function reward(r) {
    if(!r) return;
    rewardView=r;
    if(r.cards) modal((r.draft?'<h2>远征收获已入册</h2><p>'+r.wins+' 胜 · 永久卡片 '+r.cards.length+' 张 · 余烬印花 +'+r.embers+' · 入场花费 '+num(r.total_cost)+' 切糕</p><p>卡组内的同一卡号只抽一次，能量卡除外。稀有与升级保底优先取自你的卡组；卡组不足时从全池非能量卡中补足。满 7 张的卡另转为 1 枚印花。</p>':'<h2>开包收获一览</h2><p>共 '+r.cards.length+' 包 · 花费 '+num(r.total_cost)+' 切糕</p>')+'<div class="fcg-reward-grid">'+r.cards.map(x=>'<div class="'+(x.new?'fcg-new':'')+'"><b>已拥有 '+(state.collection[x.card]||0)+' 张'+(x.overflow?' · 🔥+1 印花':x.new?' · ✨NEW':'')+'</b>'+card(x.card,'preview')+'</div>').join('')+'</div>');
    else if(r.card!==undefined) detail(r.card,'已拥有 '+(state.collection[r.card]||0)+' 张'+(r.overflow?' · 溢出卡片已化作 1 枚余烬印花':''));
    else modal('<h2>获得头衔「'+esc(boot.titles[r.title]||r.title)+'」</h2><p>在个人资料中装备，下次进入常规游戏时生效。</p><a href="user_profile.php">打开个人资料</a>');
  }
  function draftEntrance() {
    const r=state.run, tiers=boot.draft_tiers, entry=tiers.entry, disabled=!state.in_room||state.credits<entry||(state.match&&!state.match.result);
    const rewards=[1,2,3,4,5].map(wins=>{
      const t=tiers[wins], guaranteed=[t.rare?t.rare+' 张稀有':'',t.upgraded?t.upgraded+' 张升级':''].filter(Boolean).join('、');
      return wins+' 胜 '+(t.deck+t.upgraded+t.rare)+' 张'+(guaranteed?'（含 '+guaranteed+' 保底）':'');
    }).join('；');
    return '<section class="fcg-expedition-entry"><div><p class="fcg-eyebrow">DRAFT ROGUELITE · 单人</p><h2>🔥 种火远征</h2><p>和四位虚拟牌手轮抽 24 张记忆，带上 8 张能量基底，直接挑战五层种火。每胜生命上限 +5（最高 100），并恢复全部生命；胜利奖励三选一：移除两张牌、强化一张牌，或从对手牌组选择一张牌。</p><p class="fcg-muted">过程中的卡片都是幻影。结束后永久入册：'+rewards+'。每胜 1 枚印花，零胜 0 张卡、保底 '+tiers[0].embers+' 枚印花。稀有与升级保底优先从最终卡组抽取，缺少时从全池非能量卡中补足。</p></div><div>'+(r?'<button data-action="draft-resume">'+(r.stage==='over'?'领取远征奖励':'继续远征')+'</button>':'<button data-action="draft-enter" '+(disabled?'disabled':'')+'>🔥 种火远征（轮抽）· '+entry+' 切糕</button><small>'+(state.credits<entry?'切糕不足':!state.in_room?'请先加入 FCG 房间':state.match&&!state.match.result?'请先完成当前牌局':'点击支付入场费，开始轮抽')+'</small>')+'</div></section>';
  }
  function renderRun() {
    const r=state.run;
    if(!r) {runPanel.innerHTML='';return;}
    const hp=state.match&&state.match.draft&&!state.match.result?Math.max(0,state.match.sides[0].hp):r.hp;
    const phase={draft:'轮抽中',node:r.node&&r.node.type==='victory'?'胜利奖励':'远征途中',battle:'迎战种火',over:'远征结束'}[r.stage];
    runPanel.innerHTML='<div class="fcg-run-bar"><strong>🔥 种火远征 · '+phase+'</strong><span>♥ '+num(hp)+' / '+r.mhp+'</span><span>第 '+r.layer+' / 5 层 · '+r.wins+' 胜</span><button data-action="draft-resume">'+(r.stage==='over'?'领取奖励':'继续远征')+'</button>'+(r.stage!=='over'?'<button class="fcg-muted" data-action="draft-abandon">放弃远征</button>':'')+'</div>';
    if(r.stage==='over'&&shownRunOver!==r.id) {shownRunOver=r.id;draftEnd();}
  }
  function draftEnd() {
    const r=state.run;if(!r||r.stage!=='over')return;
    const t=boot.draft_tiers[r.wins], n=t.deck+t.upgraded+t.rare;
    const guaranteed=[t.rare?t.rare+' 张稀有':'',t.upgraded?t.upgraded+' 张升级':''].filter(Boolean).join('、');
    modal('<h2>'+({win:'五层火光，尽收眼底。',loss:'把这一路的记忆带回去。',draw:'两团火苗，同时熄灭。',abandoned:'暂且收起远征的行囊。'}[r.ending]||'远征结束')+'</h2><p>本次 '+r.wins+' 胜 · '+n+' 张永久卡片 · '+t.embers+' 枚余烬印花'+(guaranteed?'（卡片含 '+guaranteed+' 保底）':'')+'</p><p>先抽稀有与升级保底，再抽其余卡组奖励；卡组内同号只抽一次。保底缺少时从全池非能量卡中补足，其余幻影卡片随行程消散。</p><button data-action="draft-reward" data-run="'+esc(r.id)+'">领取远征奖励</button>');
  }
  function canDraftUpgrade(id) {
    const c=boot.cards[id], next=boot.cards[id+1];
    return c&&!c.unlimited&&!c.upgraded&&next&&next.upgraded&&next.pair===c.pair;
  }
  function draftChoose(kind) {
    const r=state.run;if(!r||r.stage!=='node'||r.node.type!=='victory'||(r.node.removed>0&&kind!=='remove'))return;
    if(kind==='pick') {
      modal('<h2>选择对手的一张记忆</h2><p>从对手本局的牌组中选择一张幻影记忆，完成本次胜利奖励。</p><div class="fcg-draft-pack">'+r.node.opts.map((id,slot)=>card(id,'draft-reinforce',slot)).join('')+'</div><button data-action="draft-node-back">返回胜利奖励</button>');return;
    }
    modal('<h2>'+(kind==='upgrade'?'选择要强化的记忆':'选择要移除的记忆 · 已移除 '+r.node.removed+'/2')+'</h2><p>只影响这次远征的幻影卡组。'+(kind==='upgrade'?'强化一张后完成本次奖励。':'还需移除 '+(2-r.node.removed)+' 张；移除第一张后不能改选其他奖励。')+'能量基底不能强化或移除。</p><div class="fcg-card-grid">'+r.deck.map((id,slot)=>({id,slot})).filter(x=>!boot.cards[x.id].unlimited&&(kind!=='upgrade'||canDraftUpgrade(x.id))).map(x=>card(x.id,'draft-'+kind,x.slot)).join('')+'</div><button data-action="draft-node-back">返回胜利奖励</button>');
  }
  function upgradePreview(id, slot) {
    const r=state.run;
    if(!r||r.stage!=='node'||r.node.type!=='victory'||r.node.removed>0||r.deck[slot]!==id||!canDraftUpgrade(id)) {notice('这张记忆现在无法升级，请重新选择。');return;}
    const c=boot.cards[id], next=boot.cards[id+1];
    const diff=(label,key)=>'<p class="'+(c[key]!==next[key]?'fcg-diff':'fcg-same')+'">'+label+'：'+num(c[key])+' → '+num(next[key])+'</p>';
    modal('<h2>升级预览：'+esc(c.name)+'</h2><p>确认后完成本次胜利奖励；返回选卡不会改变卡组。</p><div class="fcg-upgrade-preview"><div><h3>当前记忆</h3>'+card(id,'preview')+'</div><span class="fcg-upgrade-arrow" aria-hidden="true">→</span><div><h3>升级之后</h3>'+card(id+1,'preview')+'</div></div><div class="fcg-upgrade-diff">'+diff('费用','cost')+(c.type==='monster'?diff('攻击','atk')+diff('防御','def')+diff('生命','hp'):'')+'</div><div class="fcg-dialog-actions"><button data-action="draft-upgrade" data-card="'+id+'" data-slot="'+slot+'" data-run="'+esc(r.id)+'" data-layer="'+r.layer+'" '+(!state.in_room?'disabled':'')+'>确认升级</button><button data-action="draft-upgrade-back">返回选卡</button></div>');
    dialogContent.querySelectorAll('[data-action="detail"]').forEach(el=>{el.dataset.back='draft-upgrade-back';});
  }
  function expedition() {
    const r=state.run;
    if(!r) return draftEntrance()+(state.last_reward&&state.last_reward.draft?'<div class="fcg-note">上次远征：'+state.last_reward.wins+' 胜，奖励已领取。<button data-action="draft-last-reward">查看收获</button></div>':'');
    let html=!state.in_room?'<div class="fcg-note">已离开 FCG 房间。行程已保存，回到 FCG 房间后可继续；也可以主动放弃并结算。</div>':'';
    const disabled=!state.in_room?'disabled':'', list=r.stage==='draft'?r.picks:r.deck;
    html+='<div class="fcg-expedition-layout"><div>';
    if(r.stage==='draft') {
      html+='<div class="fcg-section-head"><h2>第 '+(r.circle+1)+' 圈 · 第 '+(r.pick+1)+' / 8 包</h2><span>已选 '+r.picks.length+' / 24 张</span></div><progress class="fcg-draft-progress" max="24" value="'+r.picks.length+'">'+r.picks.length+' / 24</progress><p>本圈'+(r.circle%2?'逆向':'顺向')+'传牌 · 四位虚拟牌手已取走 '+(5-r.pack.length)+' 张，本包剩余 '+r.pack.length+' 张，选 1 张。</p><div class="fcg-draft-pack">'+r.pack.map((id,slot)=>card(id,'draft-pick',slot)).join('')+'</div><p class="fcg-muted">选满 24 张后，按选牌颜色配发 8 张能量基底。选牌会自动保存。</p>';
    } else if(r.stage==='node'&&r.node.type==='victory') {
      const removed=r.node.removed, removable=r.deck.filter(id=>!boot.cards[id].unlimited).length;
      html+='<h2>第 '+r.layer+' 层通过 · 胜利奖励三选一</h2><p>生命已完全恢复。'+(r.wins===5?'这是最后一次胜利奖励，完成后结算远征。':'选择一项奖励后进入下一层。')+'只影响远征幻影卡组。</p><div class="fcg-draft-nodes"><button data-action="draft-choose-remove" '+(disabled||removable<2-removed?'disabled':'')+'><strong>移除两张牌</strong><span>'+(removed?'已移除 '+removed+'/2 · 请继续选择':'选择两张非能量卡移除')+'</span></button><button data-action="draft-choose-upgrade" '+(disabled||removed>0||!r.deck.some(canDraftUpgrade)?'disabled':'')+'><strong>强化一张牌</strong><span>查看升级前后的变化，再确认强化</span></button>'+(r.node.opts.length?'<button data-action="draft-choose-pick" '+(disabled||removed>0?'disabled':'')+'><strong>选择对手 1 张牌</strong><span>从三张候选记忆中选择一张补强</span></button>':'')+'</div>';
    } else if(r.stage==='battle') {
      const o=boot.opponents[r.opponent], active=state.match&&state.match.draft&&!state.match.result;
      html+='<h2>第 '+r.layer+' 层 · 火光的另一端</h2><article class="fcg-opponent fcg-draft-foe"><img src="'+esc(o.portrait)+'" alt="'+esc(o.name)+'"><div><h3>'+esc(o.name)+'</h3><p>♥ '+(o.hp+5*(r.layer-1))+' · 你的生命 '+r.hp+' / '+r.mhp+'</p></div>'+(active?'<button data-tab="battle">回到远征牌桌</button>':'<button data-action="draft-battle" data-layer="'+r.layer+'" '+disabled+'>进入第 '+r.layer+' 层战斗</button>')+'</article><p class="fcg-muted">胜利后生命回满，并三选一领取奖励；失败或认输会结束本次远征。</p>';
    } else html+='<h2>远征结束 · '+r.wins+' 胜</h2><p>行程已保存，领取奖励后可以开始新的远征。</p><button data-action="draft-reward" data-run="'+esc(r.id)+'">领取远征奖励</button>';
    const byColor=Object.fromEntries(Object.keys(colors).map(c=>[c,list.filter(id=>boot.cards[id].color===c).length]));
    html+='</div><aside class="fcg-draft-list"><h3>'+(r.stage==='draft'?'已选记忆':'远征卡组')+' · '+list.length+' 张</h3><p>'+Object.keys(colors).filter(c=>byColor[c]).map(c=>'<span style="color:'+colors[c][1]+'">'+colors[c][0]+' '+byColor[c]+'</span>').join(' · ')+'</p><p class="fcg-muted">幻影卡片，结算前不计入永久卡册</p>'+Object.entries(counts(list)).map(([id,n])=>'<button data-action="detail" data-inspect="'+id+'" data-card="'+id+'"><span style="color:'+colors[boot.cards[id].color][1]+'">'+esc(boot.cards[id].name)+'</span><b>×'+n+'</b></button>').join('')+'</aside></div>';
    return html;
  }
  function lobby() {
    const playing=!!state.run || state.match&&!state.match.result;
    let html=(!state.in_room?'<div class="fcg-note">卡册和商店随时开放。开始对战前，请在 <a href="index.php">大厅房间列表</a> 的「创建异色房间」中选择 FCG，或加入已有 FCG 房间。</div>':'');
    html+=draftEntrance();
    if(state.match&&!state.match.result) html+='<div class="fcg-note">牌局仍在进行中。<button data-tab="battle">回到牌桌</button></div>';
    if(state.in_room) html+='<h2>同房间的牌手</h2><div class="fcg-online">'+(state.online.length?state.online.map(p=>'<span>'+esc(p.name)+' <button data-action="invite" data-uid="'+p.uid+'" '+(playing||p.busy?'disabled':'')+'>'+(p.busy?'对局中':'发起切磋')+'</button></span>').join(''):'等待其他牌手落座。')+'</div>';
    html+='<div class="fcg-section-head"><h2>陪练的小家伙们</h2><span class="fcg-muted">赢过 '+Object.keys(state.wins).length+' / '+boot.opponents.length+' 位</span></div><p class="fcg-muted">对战免费。普通挑战获胜可获得对手本局牌组中的一张卡；认真模式赠送角色代表卡或精选升级／稀有卡，升级代表卡更易获得，不掉落基础能量卡。失败与平局不扣除卡片。</p>';
    for(const [lo,hi,title] of [[0,5,'初次相逢 · Lv.1–2'],[5,11,'外面的回声'],[11,17,'更深处的记忆'],[17,21,'演武场的锋芒 · Vol.2'],[21,22,'推演不息']]) {
      html+='<div class="fcg-section-head"><h3>'+title+'</h3></div><div class="fcg-opponents">';
      for(const o of boot.opponents.slice(lo,hi)) {
        const disabled=!state.in_room||playing?'disabled':'', hardWins=(state.hard_wins||{})[o.id]||0;
        html+='<article class="fcg-opponent'+(o.adaptive?' fcg-adaptive':'')+'">'+(state.wins[o.id]?'<span class="fcg-won">已胜 '+state.wins[o.id]+' 次</span>':'')+'<img src="'+esc(o.portrait)+'" alt="'+esc(o.name)+'"><div><h3>'+esc(o.name)+'</h3><span class="fcg-muted">Lv.'+o.level+' · '+o.hp+' 生命</span></div>';
        if(o.adaptive) html+='<p class="fcg-opponent-note">从五套单色构筑中随机选用一套。每色打满 12 局有效对局后，学习双方的用牌表现、调整构筑。开局可查看颜色、世代和上轮换牌。</p>';
        html+='<button data-action="start" data-opponent="'+o.id+'" '+disabled+'>'+(o.adaptive?'与炉火一同推演':'坐下来打一局')+'</button>';
        if(o.has_hard) html+='<button class="fcg-hard" data-action="start" data-hard="1" data-opponent="'+o.id+'" '+disabled+'>🔥 认真模式 (HARD)<small>Lv.'+o.hard_level+' · '+o.hard_hp+' 生命 · 已胜 '+hardWins+' 次</small></button>';
        html+='</article>';
      }
      html+='</div>';
    }
    html+='<h2>游荡的牌手残影</h2><p>切磋胜场 '+num(state.pvp_wins)+' · 每日前 3 位不同对手各赠 2 枚余烬；获胜可借用对手一张记忆至本房间结束或次日。永久卡册不受影响。</p><div class="fcg-opponents">'+state.mirrors.map(p=>'<article class="fcg-opponent fcg-mirror"><div><h3>✦'+esc(p.name)+'的残影</h3><p>'+esc(boot.titles[p.nick]||p.nick||'')+' · 构筑 '+p.count+' 张</p></div><button data-action="mirror" data-uid="'+p.uid+'" '+(!state.in_room||playing?'disabled':'')+'>挑战残影</button></article>').join('')+'</div><p class="fcg-footer-note">击败全部 17 位初代牌手，或拥有至少 150 张图鉴，可获得「具现的暗影」。'+(state.titles.includes(9106)?'你已获得这一头衔。':'')+'</p>'; return html;
  }
  function renderSocial() {
    const p=state.invite;
    socialPanel.innerHTML=(p?'<div class="fcg-invite">'+esc(p.name)+' 邀请你来一局牌桌对决 <button data-action="accept" data-uid="'+p.from+'" '+(state.run?'disabled':'')+'>接受</button> <button data-action="decline" data-uid="'+p.from+'">婉拒</button></div>':'')+(state.reports||[]).filter(r=>r.at>state.now-60).map(r=>'<p class="fcg-report">'+esc(r.text)+'</p>').join('');
  }
  function side(s, name, enemy, sticker) {
    let monster='<span class="fcg-muted">没有驻场帮手</span>';
    if(s.monster) {const m=s.monster,c=boot.cards[m.id];monster='<div class="fcg-monster" data-inspect="'+m.id+'"><img src="'+esc(c.art)+'" alt="'+esc(c.name)+'" data-action="detail" data-card="'+m.id+'"><div><b>'+esc(c.name)+'</b><br>'+cardStats(m)+(m.frozen?'<br>❄️冻结 '+num(m.frozen):'')+(m.stealth?' · 🌫️潜伏':'')+'</div></div>';}
    const age=sticker?(Date.now()+clockOffset)/1000-sticker.at:99;
    const bubble=sticker&&age<2.5&&boot.stickers[sticker.id]?'<img class="fcg-sticker-bubble" data-expires="'+(sticker.at+2.5)+'" style="animation-delay:-'+Math.max(0,age)+'s" src="img/fcg/stickers/'+String(sticker.id).padStart(2,'0')+'.png" alt="'+esc(boot.stickers[sticker.id])+'">':'';
    return '<div class="fcg-side"><div><h3>'+esc(name)+bubble+'</h3><div class="fcg-stats"><span>♥ '+num(Math.max(0,s.hp))+' / '+(s.mhp||200)+'</span><span>攻击 '+num(s.atk)+(s.bonus?'＋'+num(s.bonus):'')+'</span><span>防御 '+num(s.def)+'</span><span>回血 '+num(s.regen)+'</span><span>符盾 '+num(s.barrier||0)+'</span><span>破绽 '+num(s.vuln||0)+'</span><span>牌库 '+num(s.deck_count)+'</span>'+(enemy?'<span>手牌 '+num(s.hand_count)+'</span>':'')+'</div><div class="fcg-hp"><i style="width:'+Math.max(0,Math.min(100,s.hp/(s.mhp||200)*100))+'%"></i></div><div class="fcg-energy">'+Object.keys(colors).map(c=>'<span style="--tone:'+colors[c][1]+'" title="'+colors[c][0]+'能量 / 每回合产能">'+colors[c][0]+' '+num(s.energy[c])+' <small>＋'+num(s.gen[c])+'</small></span>').join('')+'</div></div><div>'+monster+'</div></div>';
  }
  function renderSettings() {
    settingsPanel.querySelectorAll('[data-ui]').forEach(b=>b.setAttribute('aria-pressed',String(b.dataset.ui===uiMode)));
    app.querySelectorAll('[data-action="settings"]').forEach(b=>b.setAttribute('aria-expanded',String(!settingsPanel.hidden)));
  }
  function setUiMode(mode) {
    uiMode=mode==='table'?'table':'classic'; inspectId=null;
    try {localStorage.setItem('fcg-ui',uiMode);} catch(e) {}
    render();
  }
  function fitTable() {
    const stage=document.getElementById('fcg-tbl-stage');
    if(stage) stage.style.height=window.innerWidth>700?Math.max(320,window.innerHeight-content.getBoundingClientRect().top-12)+'px':'';
  }
  function layoutHand() {
    const hand=content.querySelector('.fcg-tbl-hand');if(!hand)return;
    const cards=[...hand.querySelectorAll('.fcg-tbl-handcard')], n=cards.length;
    if(window.innerWidth<=700) {cards.forEach(c=>{c.style.width='';c.style.marginLeft='';c.style.flex='';});return;}
    if(!n)return;
    const GAP=6, CLEAR=26, MIN_STRIP=24;
    const port=content.querySelector('.fcg-tbl-self-info .fcg-tbl-portrait'), rect=hand.getBoundingClientRect();
    const right=rect.right-2, capLeft=(port?port.getBoundingClientRect().right:rect.left)+CLEAR;
    const avail=Math.max(0,right-capLeft);
    let width=Math.round(Math.max(110,Math.min(144,window.innerWidth*.11))), step=width+GAP;
    if(n>1&&n*width+(n-1)*GAP>avail) {
      // 放不下时向左重叠，给头像右侧的防御角标留出位置。
      step=(avail-width)/(n-1);
      if(step<MIN_STRIP) {width=Math.max(1,avail-(n-1)*MIN_STRIP);step=MIN_STRIP;}
    }
    cards.forEach((c,i)=>{c.style.width=width+'px';c.style.flex='0 0 auto';c.style.marginLeft=i?(step-width)+'px':'0';});
  }
  function renderPileLog() {
    const scroll=pilePanel.dataset.side===String(pileLogSide)?pilePanel.scrollTop:0;
    pilePanel.hidden=pileLogSide===null;
    app.querySelectorAll('[data-action="pile-log"]').forEach(b=>b.setAttribute('aria-expanded',String(Number(b.dataset.side)===pileLogSide)));
    if(pilePanel.hidden) {pilePanel.innerHTML='';return;}
    const list=state.match.sides[pileLogSide].played_cards||[];
    pilePanel.innerHTML='<div class="fcg-tbl-panel-head"><h2>'+(pileLogSide===0?'你的':'对手的')+'出牌堆 · '+list.length+' 张</h2><button type="button" data-action="pile-close" aria-label="关闭出牌记录">×</button></div><p class="fcg-muted">第 '+state.match.turn+' 回合 · 按入堆顺序（含本次结算阵亡的帮手）</p><div class="fcg-tbl-pile-grid">'+(list.length?list.map((id,i)=>pileCard(id,i,pileLogSide,true)).join(''):'<p class="fcg-muted">这一回合还没有卡片入堆。</p>')+'</div>';
    pilePanel.dataset.side=String(pileLogSide);pilePanel.scrollTop=scroll;
  }
  function renderLogPanel() {
    logPanel.hidden=!logOpen;
    app.querySelectorAll('[data-action="log"]').forEach(b=>b.setAttribute('aria-expanded',String(logOpen)));
    logPanel.innerHTML=logOpen?'<div class="fcg-tbl-panel-head"><h2>📜 牌桌记事</h2><button type="button" data-action="log-close" aria-label="关闭牌桌记事">×</button></div><div class="fcg-log" id="fcg-log">'+state.match.log.map(line=>'<p>'+esc(line)+'</p>').join('')+'</div>':'';
    const log=logPanel.querySelector('.fcg-log'); if(log) log.scrollTop=log.scrollHeight;
  }
  function cardTblHand(id, slot) {
    const c=boot.cards[id], s=state.match.sides[0], cost=s.free[slot]?0:c.cost;
    const burn=s.monster&&!s.monster.frozen&&s.monster.abilities.includes('aura_burn_monster_heal');
    const allowed=s.energy[c.color]>=cost && !(c.type==='monster'&&s.monster&&!c.escort&&!burn) && !state.match.result;
    const hot=allowed && ((c.combo_min!=null && (s.played_this_turn||0)+1>=c.combo_min) || ((c.res_colors||[]).length && s.last_color && (c.res_colors.includes('ANY')||c.res_colors.includes(s.last_color))));
    const attrs=' data-card="'+id+'" data-slot="'+slot+'" data-turn="'+state.match.turn+'"';
    return '<article class="fcg-card fcg-tbl-handcard '+(c.upgraded?'upgraded ':'')+(c.rare?'rare ':'')+(hot?'fcg-hot ':'')+(allowed?'':'disabled')+'" data-inspect="'+id+'" style="--tone:'+colors[c.color][1]+'" '+(allowed?'data-action="play"'+attrs+' role="button" tabindex="0" aria-label="打出「'+esc(c.name)+'」"':'aria-label="暂时无法打出「'+esc(c.name)+'」，仍可查看详情或弃牌"')+'>'+
      '<img src="'+esc(c.art)+'" alt="'+esc(c.name)+'" loading="lazy"><span class="fcg-cost" title="'+colors[c.color][0]+'色能量">'+num(cost)+'</span>'+
      '<button type="button" class="fcg-tbl-lens" data-action="detail" data-card="'+id+'" aria-label="查看「'+esc(c.name)+'」详情">🔍</button><button type="button" class="fcg-tbl-trash" data-action="discard"'+attrs+' title="弃牌：每回合回血减 1" aria-label="弃掉「'+esc(c.name)+'」">🗑️</button>'+
      '<div class="fcg-card-body"><h3>'+esc(c.name)+'</h3><p>'+colors[c.color][0]+' · '+(c.upgraded?'已升级':'未升级')+(c.rare?' · 稀有':'')+'</p>'+(c.type==='monster'?'<p>'+cardStats(c)+'</p>':'')+'<p class="fcg-card-rules">'+c.short_rules+'</p></div><i class="fcg-rarity" aria-hidden="true"></i></article>';
  }
  function pileCard(id, i, side, inLog=false) {
    const c=boot.cards[id];
    return '<article class="fcg-tbl-pile-card" data-inspect="'+id+'" style="--tone:'+colors[c.color][1]+';--stack-index:'+i+';'+(!inLog?'z-index:'+(i+1):'')+'"><img src="'+esc(c.art)+'" alt="'+esc(c.name)+'" loading="lazy"><button type="button" class="fcg-tbl-lens" data-action="detail" data-card="'+id+'" aria-label="查看「'+esc(c.name)+'」详情">🔍</button><b>'+esc(c.name)+'</b><p class="fcg-tbl-mini-rules">'+c.short_rules+'</p></article>';
  }
  function tablePile(s, side) {
    const all=s.played_cards||[], list=all.slice(-12);
    return '<section class="fcg-tbl-pile fcg-tbl-pile-'+(side===0?'self':'foe')+'" data-side="'+side+'" aria-label="'+(side===0?'你的':'对手的')+'出牌堆" style="--stack-count:'+Math.max(1,list.length-1)+'"><div class="fcg-tbl-pile-label"><span>'+(side===0?'你的出牌':'对手出牌')+' · 本回合 ×'+all.length+'</span><button type="button" class="fcg-tbl-notebook" data-action="pile-log" data-side="'+side+'" aria-controls="fcg-pile-log" aria-expanded="false" aria-label="查看'+(side===0?'你的':'对手的')+'本回合出牌">📓</button></div>'+(list.length?list.map((id,i)=>pileCard(id,i,side)).join(''):'<span class="fcg-tbl-pile-empty">等待落牌</span>')+'</section>';
  }
  function statBadge(icon, label, value, title, extra='', tone='', fill=null, preview=null, cls='', key='') {
    const percent=n=>Math.max(0,Math.min(100,n));
    const sign=cls==='fcg-tbl-status'&&value!==0?(value<0||['vuln','frozen','stealth'].includes(key)?'fcg-tbl-neg':'fcg-tbl-pos'):'';
    return '<span class="fcg-tbl-badge '+cls+'"'+(key?' data-stat="'+key+'"':'')+' style="--tone:'+(tone||'#b6a7cc')+'" title="'+esc(title)+'" aria-label="'+esc(label+' '+value+'；'+title)+'">'+(fill!==null?'<span class="fcg-tbl-liquid" aria-hidden="true"><i class="fcg-tbl-liquid-preview" style="height:'+percent(preview??fill)+'%"></i><i class="fcg-tbl-liquid-fill" style="height:'+percent(fill)+'%"></i></span>':'')+(icon?'<span class="fcg-tbl-badge-icon" aria-hidden="true">'+icon+'</span>':'')+'<b'+(sign?' class="'+sign+'"':'')+'>'+esc(value)+'</b>'+extra+'</span>';
  }
  function tablePortrait(m, side) {
    const s=m.sides[side], sticker=(m.stickers||{})[side];
    const name=m.names?m.names[side]:side===0?'你的牌桌':boot.opponents[m.opponent].name;
    const portrait=(m.portraits||[])[side]||(side===1&&!m.pvp?boot.opponents[m.opponent]?.portrait:null);
    const age=sticker?(Date.now()+clockOffset)/1000-sticker.at:99;
    const bubble=sticker&&age<2.5&&boot.stickers[sticker.id]?'<img class="fcg-sticker-bubble" data-expires="'+(sticker.at+2.5)+'" style="animation-delay:-'+Math.max(0,age)+'s" src="img/fcg/stickers/'+String(sticker.id).padStart(2,'0')+'.png" alt="'+esc(boot.stickers[sticker.id])+'">':'';
    return '<div class="fcg-tbl-portrait fcg-tbl-portrait-'+(side===0?'self':'foe')+'" data-side="'+side+'"><div class="fcg-tbl-avatar '+(portrait&&portrait.startsWith('img/fcg/fireseeds/')?'fcg-tbl-fireseed':'')+'">'+(portrait?'<img src="'+esc(portrait)+'" alt="'+esc(name)+'的头像">':'<span aria-hidden="true">✦</span>')+'<span class="fcg-tbl-avatar-stat fcg-tbl-atk" title="攻击 '+s.atk+'，本回合加成 '+s.bonus+'" aria-label="攻击 '+(s.atk+(s.bonus||0))+'"><i aria-hidden="true">⚔</i><b>'+(s.atk+(s.bonus||0))+'</b></span><span class="fcg-tbl-avatar-stat fcg-tbl-def" title="每次攻击抵挡 '+s.def+' 伤害" aria-label="防御 '+s.def+'"><i aria-hidden="true">🛡</i><b>'+s.def+'</b></span></div><h3 class="fcg-tbl-name" title="'+esc(name)+'">'+esc(name)+'</h3>'+bubble+'</div>';
  }
  function tableStats(m, side) {
    const s=m.sides[side];
    const health='<div class="fcg-tbl-health">'+statBadge('♥','生命',Math.max(0,s.hp),'生命上限 '+(s.mhp||200),'<small>/ '+(s.mhp||200)+'</small>','#c65b4e',s.hp,null,'fcg-tbl-hp','hp')+'</div>';
    const stats='<div class="fcg-tbl-stats">'+
      statBadge(s.regen<0?'☠️':'💚',s.regen<0?'中毒':'回血',s.regen,s.regen<0?'每回合因中毒损失 '+(-s.regen)+' 生命':'每回合恢复 '+s.regen+' 生命','','',null,null,'fcg-tbl-status','regen')+
      statBadge('🧿','符盾',s.barrier||0,'抵挡伤害时消耗的符盾','','',null,null,'fcg-tbl-status','barrier')+statBadge('🔬','破绽',s.vuln||0,'下一次受到攻击时的额外伤害','','',null,null,'fcg-tbl-status','vuln')+'</div>';
    const energy='<div class="fcg-tbl-energy">'+
      Object.keys(colors).map(c=>statBadge('',colors[c][0]+'能量',s.energy[c],colors[c][0]+'色能量 '+s.energy[c]+'；每回合产能 '+s.gen[c],'<i class="fcg-tbl-gen"><span aria-hidden="true">▲</span><strong>'+esc(s.gen[c])+'</strong></i>',colors[c][1],s.energy[c]/15*100,(s.energy[c]+s.gen[c])/15*100,'','energy-'+c)).join('')+'</div>';
    return '<div class="fcg-tbl-info-row">'+(side===0?health+stats+energy:tablePortrait(m,side)+health+stats+energy)+'</div>'+(side===1?'<div class="fcg-tbl-foe-counts">'+tableCount(s.deck_count,1)+tableCount(s.hand_count,1,true)+'</div>':'');
  }
  function tableCount(value, side, hand=false) {
    const label=hand?'手牌':'牌库', title=(side===0?'你的':'对手的')+label+'剩余 '+value+' 张';
    return '<div class="fcg-tbl-count-badge'+(side===0?' fcg-tbl-deck':hand?' fcg-tbl-handcount':'')+'"'+(side===1&&!hand?' data-foe-deck':'')+' title="'+esc(title)+'" aria-label="'+esc(title)+'">'+(hand?'<i class="fcg-cardback" aria-hidden="true"></i>':'<span aria-hidden="true">✦</span>')+'<b>'+esc(value)+'</b><small>'+label+'</small></div>';
  }
  function tableMonster(s) {
    if(!s.monster) return '<div class="fcg-tbl-monster-empty"><span aria-hidden="true">♧</span><small>没有驻场帮手</small></div>';
    const m=s.monster,c=boot.cards[m.id];
    let status='';
    if(m.frozen)status+=statBadge('❄️','冻结',-Math.abs(m.frozen),'冻结剩余 '+m.frozen+' 回合','','',null,null,'fcg-tbl-status','frozen');
    if(m.stealth)status+=statBadge('🌫️','潜伏','潜','潜伏中的帮手','','',null,null,'fcg-tbl-status','stealth');
    if(m.regen<0)status+=statBadge('☠️','中毒',m.regen,'每回合因中毒损失 '+(-m.regen)+' 生命','','',null,null,'fcg-tbl-status','regen');
    if(m.barrier)status+=statBadge('🧿','符盾',m.barrier,'抵挡伤害时消耗的符盾','','',null,null,'fcg-tbl-status','barrier');
    if(m.vuln)status+=statBadge('🔬','破绽',m.vuln,'下一次受到攻击时的额外伤害','','',null,null,'fcg-tbl-status','vuln');
    return (status?'<div class="fcg-tbl-mon-status" aria-label="帮手状态">'+status+'</div>':'')+'<article class="fcg-tbl-monster-card" data-inspect="'+m.id+'" style="--tone:'+colors[c.color][1]+'"><img src="'+esc(c.art)+'" alt="'+esc(c.name)+'"><button type="button" class="fcg-tbl-lens" data-action="detail" data-card="'+m.id+'" aria-label="查看「'+esc(c.name)+'」详情">🔍</button><span class="fcg-tbl-mon-hp" title="帮手生命">♥ '+m.hp+'</span><span class="fcg-tbl-mon-atk" title="帮手攻击" aria-label="帮手攻击 '+m.atk+'"><i aria-hidden="true">⚔</i><b>'+m.atk+'</b></span><span class="fcg-tbl-mon-def" title="帮手防御" aria-label="帮手防御 '+m.def+'"><i aria-hidden="true">🛡</i><b>'+m.def+'</b></span><div><h3>'+esc(c.name)+'</h3><p class="fcg-tbl-mini-rules">'+c.short_rules+'</p></div></article>';
  }
  function tableRects() {
    const rect=selector=>content.querySelector(selector)?.getBoundingClientRect();
    return [0,1].map(side=>({
      hand:side===0?[...content.querySelectorAll('.fcg-tbl-handcard')].map(el=>({id:Number(el.dataset.inspect),rect:el.getBoundingClientRect()})):[],
      deck:rect(side===0?'.fcg-tbl-deck':'[data-foe-deck]'),
      portrait:rect('.fcg-tbl-portrait[data-side="'+side+'"]'),
      info:rect(side===0?'.fcg-tbl-self-info':'.fcg-tbl-foe-info'),
      monster:rect('.fcg-tbl-monster-zone[data-side="'+side+'"] .fcg-tbl-monster-card'),
      pile:rect('.fcg-tbl-pile[data-side="'+side+'"] .fcg-tbl-pile-card:last-child')
    }));
  }
  function clearTableFx() {
    tableFxTimers.forEach(clearTimeout);tableFxTimers.clear();tableFx.replaceChildren();
  }
  function tableEffect(node, duration) {
    tableFx.append(node);
    const timer=setTimeout(()=>{node.remove();tableFxTimers.delete(timer);},duration);
    tableFxTimers.add(timer);
  }
  function tableMotionReduced() {
    return !!state.ui?.reduced_motion||window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  }
  function flyCard(id, from, to, played=false) {
    if(!from||!to||tableMotionReduced())return;
    const c=boot.cards[id], flip=played&&c.cost>=8, duration=flip?450:260;
    const node=document.createElement('img');node.src=c.art;node.className='fcg-tbl-card-flight'+(flip?' fcg-tbl-card-flip':'');
    node.style.cssText='left:'+from.left+'px;top:'+from.top+'px;width:'+Math.min(128,from.width)+'px;border-color:'+colors[c.color][1];
    tableEffect(node,duration+30);
    const dx=to.left+to.width/2-from.left-Math.min(128,from.width)/2,dy=to.top-from.top;
    const end='translate('+dx+'px,'+dy+'px)';
    node.animate(flip?[
      {transform:'perspective(600px) rotateY(90deg) scale(.7)',opacity:.6},
      {transform:end+' perspective(600px) rotateY(0deg) scale(1.04)',opacity:1,offset:.8},
      {transform:end+' scale(1)',opacity:0}
    ]:[{transform:'translate(0,0) scale(.85)',opacity:.85},{transform:end+' scale(1)',opacity:0}],{duration,easing:'ease-out'});
  }
  function floatStat(el, delta, hp=false) {
    if(!el||!delta)return;
    const rect=el.getBoundingClientRect(), node=document.createElement('span'), reduced=tableMotionReduced();
    node.className='fcg-tbl-float '+(delta>0?'fcg-tbl-gain':'fcg-tbl-loss');node.textContent=(delta>0?'+':'')+delta;
    node.style.cssText='left:'+(rect.left+rect.width/2)+'px;top:'+rect.top+'px';
    tableEffect(node,1000);
    if(!reduced)node.animate([{transform:'translate(-50%,0)',opacity:1},{transform:'translate(-50%,-40px)',opacity:0}],{duration:1000,easing:'ease-out'});
    if(hp&&!reduced) {
      el.classList.add(delta<0?'fcg-tbl-hp-hit':'fcg-tbl-hp-heal');
      if(delta<0)el.animate([{transform:'translateX(0)'},{transform:'translateX(-5px)'},{transform:'translateX(5px)'},{transform:'translateX(-3px)'},{transform:'translateX(0)'}],{duration:450});
    }
  }
  function tableProjectile(from, target, heal) {
    if(!from||!target||tableMotionReduced())return;
    const to=target.getBoundingClientRect(),dx=to.left+to.width/2-from.left-from.width/2,dy=to.top+to.height/2-from.top-from.height/2;
    const node=document.createElement('i');node.className='fcg-tbl-projectile '+(heal?'fcg-tbl-gain':'fcg-tbl-loss');
    node.style.cssText='left:'+(from.left+from.width/2)+'px;top:'+(from.top+from.height/2)+'px;--angle:'+Math.atan2(dy,dx)+'rad';
    tableEffect(node,420);node.animate([{transform:'translate(0,0)',opacity:0},{opacity:1,offset:.15},{transform:'translate('+dx+'px,'+dy+'px)',opacity:0}],{duration:400,easing:'ease-in'});
  }
  function animateTable(oldRects) {
    const m=state.match, before=prevMatchView;
    if(uiMode!=='table'||tab!=='battle'||!m||m.result) {prevMatchView=null;clearTableFx();return;}
    // 仅保存上次已呈现的状态；轮询、打开面板和切换布局不会重播旧变化。
    prevMatchView={id:m.id,turn:m.turn,sides:m.sides.map(s=>({hp:s.hp,energy:{...s.energy},hand:(s.hand||[]).slice(),hand_count:s.hand_count,deck_count:s.deck_count,monster:s.monster?.id,played_cards:(s.played_cards||[]).slice()}))};
    if(!before||before.id!==m.id) {clearTableFx();return;}
    const after=prevMatchView, nowRects=tableRects(), additions=[];
    for(let i=0;i<2;i++) {
      const a=before.sides[i], b=after.sides[i], old=oldRects[i], now=nowRects[i];
      const left=counts(b.hand), removed=[];
      a.hand.forEach((id,slot)=>{if(left[id])left[id]--;else removed.push({id,rect:old.hand[slot]?.rect});});
      const prefix=a.played_cards.every((id,n)=>b.played_cards[n]===id);
      // 服务端在换回合时清空旧堆；清空本身不产生移动，新堆中可能含结算阵亡的帮手。
      const pile=before.turn===after.turn&&prefix?b.played_cards.slice(a.played_cards.length):before.turn!==after.turn?b.played_cards:[];
      additions[i]=pile;
      pile.forEach(id=>{
        if(boot.cards[id].type==='monster')return;
        const at=removed.findIndex(c=>c.id===id), source=at>=0?removed.splice(at,1)[0].rect:old.portrait;
        flyCard(id,source,now.pile,true);
      });
      if(b.monster!==undefined&&b.monster!==a.monster) {
        const source=removed.find(c=>c.id===b.monster)?.rect||old.portrait;
        flyCard(b.monster,source,now.monster,true);
      }
      if(a.monster!==undefined&&b.monster!==a.monster&&pile.includes(a.monster))flyCard(a.monster,old.monster,now.pile);
      if(b.deck_count<a.deck_count) {
        if(i===0) {
          const retained=counts(a.hand), drawn=[];
          b.hand.forEach((id,slot)=>{if(retained[id])retained[id]--;else drawn.push({id,slot});});
          drawn.slice(-(a.deck_count-b.deck_count)).forEach(c=>flyCard(c.id,old.deck,now.hand[c.slot]?.rect));
        } else if(b.hand_count>a.hand_count) {
          // 敌方手牌保密，只移动牌背，不猜测卡号。
          if(!tableMotionReduced()&&old.deck&&now.portrait) {
            const node=document.createElement('span');node.className='fcg-tbl-hidden-flight';node.textContent='✦';
            node.style.cssText='left:'+old.deck.left+'px;top:'+old.deck.top+'px';tableEffect(node,290);
            node.animate([{transform:'translate(0,0)',opacity:1},{transform:'translate('+(now.portrait.left-old.deck.left)+'px,'+(now.portrait.top-old.deck.top)+'px)',opacity:0}],{duration:260});
          }
        }
      }
      Object.keys(colors).forEach(c=>floatStat(content.querySelector('[data-side="'+i+'"] [data-stat="energy-'+c+'"]'),b.energy[c]-a.energy[c]));
    }
    for(let i=0;i<2;i++) {
      const delta=after.sides[i].hp-before.sides[i].hp, target=content.querySelector('[data-side="'+i+'"] [data-stat="hp"]');
      if(!delta)continue;
      floatStat(target,delta,true);
      const source=delta>0?i:1-i, now=nowRects[source], old=oldRects[source];
      tableProjectile(additions[source].length?now.pile:(now.monster||old.monster||(delta>0?now.portrait:now.info)),target,delta>0);
    }
  }
  function battleTable() {
    const m=state.match;
    if(!m) return battleClassic();
    const self=m.sides[0], foe=m.sides[1];
    const challenge=(m.hard?'<p class="fcg-challenge-label">🔥 认真模式 (HARD)</p>':'')+(m.ai_color?'<div class="fcg-tbl-ai"><strong>'+colors[m.ai_color][0]+'色推演 · 第 '+esc(m.ai_gen)+' 世代</strong><p>'+esc(m.ai_change)+'</p></div>':'');
    const hud='<div class="fcg-tbl-hud">'+challenge+'<div class="fcg-tbl-tools">'+(m.last_card!==undefined?'<button class="fcg-last-card fcg-tbl-last" data-inspect="'+m.last_card+'" data-action="detail" data-card="'+m.last_card+'"><img src="'+esc(boot.cards[m.last_card].art)+'" alt=""><span>上一张<br><b>'+esc(boot.cards[m.last_card].name)+'</b></span></button>':'')+'<button type="button" data-action="log" aria-controls="fcg-log-panel" aria-expanded="false">📜 牌桌记事</button></div><p class="fcg-tbl-hint">双方同时行动 · 出牌续 1 秒'+(m.live?'（每回合最多续 4 秒）':'')+'</p></div>';
    const inspector='<aside id="fcg-inspector" class="fcg-tbl-inspector" hidden></aside>';
    if(m.result) return battleResult(m)+'<div class="fcg-tbl-result-tools">'+hud+'</div>'+inspector;
    return '<div class="fcg-tbl-stage" id="fcg-tbl-stage"><div class="fcg-tbl-row1">'+hud+'<div class="fcg-tbl-foe"><div class="fcg-tbl-foe-info" data-side="1">'+tableStats(m,1)+'</div><div class="fcg-tbl-monster-zone" data-side="1">'+tableMonster(foe)+'</div></div></div>'+
      '<div class="fcg-tbl-row2">'+tablePile(self,0)+tablePile(foe,1)+'</div><div class="fcg-tbl-turn"><span class="fcg-tbl-hourglass" aria-hidden="true">⏳</span><strong>第 '+m.turn+' 回合</strong><span id="fcg-seconds">—</span><div class="fcg-timer"><i id="fcg-timer-bar"></i></div></div>'+
      '<div class="fcg-tbl-row3"><div class="fcg-tbl-monster-zone" data-side="0">'+tableMonster(self)+'</div><div class="fcg-tbl-self-info" data-side="0">'+tableStats(m,0)+
      '<div class="fcg-tbl-controls"><button data-action="destroy" '+(!self.monster?'disabled':'')+'>解散帮手</button><button data-action="surrender">认输</button><div class="fcg-sticker-control"><button data-action="sticker-menu" aria-expanded="false">😊 表情</button><div id="fcg-sticker-menu" hidden>'+Object.keys(boot.stickers).map(id=>'<button data-action="sticker" data-sticker="'+id+'" title="'+esc(boot.stickers[id])+'"><img src="img/fcg/stickers/'+String(id).padStart(2,'0')+'.png" alt="'+esc(boot.stickers[id])+'"></button>').join('')+'</div></div></div>'+tablePortrait(m,0)+'</div>'+
      '<div class="fcg-tbl-hand" aria-label="你的手牌">'+(self.hand.length?self.hand.map((id,i)=>cardTblHand(id,i)).join(''):'<p class="fcg-tbl-empty-hand">等待下一张记忆</p>')+'</div>'+tableCount(self.deck_count,0)+'</div></div>'+inspector;
  }
  function battleResult(m) {
    let html='<div class="fcg-result"><h2>'+({win:m.draft?'本层通过，生命已完全恢复。':m.pvp?'切磋获胜。':'小种火把一张记忆交给了你。',loss:'这次，是对手赢了。',draw:'两团火苗，同时熄灭。'}[m.result])+'</h2>';
    if(m.reward&&m.reward.pvp) html+='<p>余烬印花 +'+m.reward.embers+(m.reward.embers===0?'（今日奖励次数已用完，或已赢过这位对手）':'')+'</p>'+(m.reward.temp_card!==null?'<p>✨本房间幻影记忆「'+esc(boot.cards[m.reward.temp_card].name)+'」</p><button data-action="detail" data-card="'+m.reward.temp_card+'">查看借牌</button>':'');
    else if(m.reward) html+='<p>获得「'+esc(boot.cards[m.reward.card].name)+'」'+(m.reward.overflow?'，转为余烬印花 ×1':'')+'</p><button data-action="detail" data-card="'+m.reward.card+'">查看战利品</button> ';
    return html+(m.draft?'<button data-action="draft-resume">'+(state.run&&state.run.stage==='over'?'远征结算':'继续远征，查看胜利奖励')+'</button>':'<button data-tab="lobby">再找一位牌手</button>')+'</div>';
  }
  function battleClassic() {
    const m=state.match;
    if(!m) return '<div class="fcg-note">还没有开始牌局。<button data-tab="lobby">挑选牌手</button></div>';
    let html='';
    if(m.result) html=battleResult(m);
    const stickers=m.stickers||{};
    if(m.hard) html+='<p class="fcg-challenge-label">🔥 认真模式 (HARD)</p>';
    if(m.ai_color) html+='<div class="fcg-note"><strong>'+colors[m.ai_color][0]+'色推演 · 第 '+esc(m.ai_gen)+' 世代</strong><p>'+esc(m.ai_change)+'</p></div>';
    html+='<div class="fcg-match-status"><strong>第 '+num(m.turn)+' 回合</strong><div class="fcg-timer"><i id="fcg-timer-bar"></i></div><span id="fcg-seconds">—</span></div><div class="fcg-duel">'+side(m.sides[0],m.names?m.names[0]:'你的牌桌',false,stickers[0])+side(m.sides[1],m.names?m.names[1]:boot.opponents[m.opponent].name,true,stickers[1])+'</div>';
    if(!m.result) html+='<div class="fcg-hand">'+m.sides[0].hand.map((id,i)=>card(id,'hand',i)).join('')+'</div><div class="fcg-stats"><button data-action="destroy" '+(!m.sides[0].monster?'disabled':'')+'>解散帮手</button><button data-action="surrender">认输</button><div class="fcg-sticker-control"><button data-action="sticker-menu" aria-expanded="false">😊 表情</button><div id="fcg-sticker-menu" hidden>'+Object.keys(boot.stickers).map(id=>'<button data-action="sticker" data-sticker="'+id+'" title="'+esc(boot.stickers[id])+'"><img src="img/fcg/stickers/'+String(id).padStart(2,'0')+'.png" alt="'+esc(boot.stickers[id])+'"></button>').join('')+'</div></div><span>双方同时行动 · 出牌续 1 秒'+(m.live?'（每回合最多续 4 秒）':'')+'</span></div>';
    if(m.last_card!==undefined)html+='<button class="fcg-last-card" data-inspect="'+m.last_card+'" data-action="detail" data-card="'+m.last_card+'">上一张：'+esc(boot.cards[m.last_card].name)+'</button>';
    return '<div class="fcg-battle-layout"><div>'+html+'<h3 style="margin-top:18px">牌桌记事</h3><div class="fcg-log" id="fcg-log">'+m.log.map(line=>'<p>'+esc(line)+'</p>').join('')+'</div></div><aside id="fcg-inspector" class="fcg-inspector"><p>划过卡图，检视记忆与词条。</p></aside></div>';
  }
  function renderDeckGrid() {
    const used=counts(draft);
    const query=filter.search.trim().toLocaleLowerCase();
    const list=boot.cards.filter(c=>(filter.series==='all'||c.series===filter.series)&&(filter.cost==='all'||(filter.cost==='8+'?c.cost>=8:c.cost===Number(filter.cost)))&&(filter.color==='all'||c.color===filter.color)&&(!filter.owned||available(c.id))&&(!filter.inDeck||used[c.id])&&(filter.keyword==='all'||c.keywords.includes(filter.keyword))&&(!query||[c.name,c.rules,...c.keywords,...c.glossary.map(g=>g.label)].join(' ').toLocaleLowerCase().includes(query))&&(filter.rarity==='all'||(filter.rarity==='rare'?c.rare:filter.rarity==='upgraded'?c.upgraded:!c.upgraded)));
    document.getElementById('fcg-deck-grid').innerHTML=list.length?list.map(c=>card(c.id,'deck')).join(''):'<p class="fcg-empty">没有符合筛选条件的卡片。</p>';
    document.getElementById('fcg-filter-count').textContent='显示 '+list.length+' / '+boot.cards.length+' 种卡片';
    const sorted=Object.keys(used).sort((a,b)=>boot.cards[a].cost-boot.cards[b].cost||Number(a)-Number(b));
    document.getElementById('fcg-deck-list').innerHTML='<h3>当前构筑 · '+draft.length+' 张</h3><p class="fcg-muted">25–100 张，同卡通常最多 7 张。普通与升级卡分别计算。</p>'+manaCurve()+'<button data-action="sample-hand" '+(draft.length<3?'disabled':'')+'>抽一手范例手牌</button><p class="fcg-muted">从当前构筑抽取起手 3 张，包含未保存改动。</p>'+sorted.map(id=>{const c=boot.cards[id];return '<div class="fcg-deck-row"><span class="fcg-deck-cost" style="--tone:'+colors[c.color][1]+'" title="'+colors[c.color][0]+'色能量 '+c.cost+'">'+colors[c.color][0]+' '+num(c.cost)+'</span><button class="fcg-deck-name" data-action="detail" data-card="'+id+'">'+esc(c.name)+' ×'+used[id]+'</button><button data-action="remove" data-card="'+id+'" aria-label="移除一张'+esc(c.name)+'">−</button></div>';}).join('')+'<button data-action="save-deck">'+(dirty?'保存改动':'保存卡组')+'</button><button data-action="clear-deck">清空构筑</button>'+(dirty?'<button data-action="restore-deck">还原已保存的卡组</button>':'');
  }
  function manaCurve() {
    const buckets=Array.from({length:9},()=>({total:0,colors:{}}));
    for(const id of draft) {const c=boot.cards[id],bucket=buckets[Math.min(8,c.cost)];bucket.total++;bucket.colors[c.color]=(bucket.colors[c.color]||0)+1;}
    const max=Math.max(1,...buckets.map(b=>b.total));
    return '<div class="fcg-curve"><h4>费用曲线</h4><p class="fcg-muted">按投入张数 · 卡面费用 · 8+ 合并高费</p><div class="fcg-curve-bars" role="img" aria-label="'+buckets.map((b,i)=>(i===8?'8费及以上':i+'费')+' '+b.total+'张').join('，')+'">'+buckets.map((b,i)=>'<div class="fcg-curve-column" title="'+(i===8?'8费及以上':i+'费')+'：'+b.total+' 张'+Object.keys(colors).filter(c=>b.colors[c]).map(c=>' · '+colors[c][0]+' '+b.colors[c]+' 张').join('')+'"><span>'+b.total+'</span><div class="fcg-curve-track">'+Object.keys(colors).filter(c=>b.colors[c]).map(c=>'<i style="height:'+(b.colors[c]/max*100)+'%;background:'+colors[c][1]+'"></i>').join('')+'</div><span>'+i+(i===8?'+':'')+'</span></div>').join('')+'</div><div class="fcg-curve-legend">'+Object.keys(colors).map(c=>'<span style="--tone:'+colors[c][1]+'">'+colors[c][0]+'</span>').join('')+'</div></div>';
  }
  function drawSample() {
    if(draft.length<3) return;
    const pool=draft.slice(); sampleHand=[];
    for(let i=0;i<3;i++) sampleHand.push(pool.splice(Math.floor(Math.random()*pool.length),1)[0]);
    showSample();
  }
  function showSample() {
    modal('<h2>范例起手 · 3 张</h2><p>按当前构筑的投入张数随机抽取；未保存的改动也会使用。仅供试抽，不消耗卡片，也不影响牌局。</p><div class="fcg-sample-hand">'+sampleHand.map(id=>card(id,'preview')).join('')+'</div><div class="fcg-dialog-actions"><button data-action="sample-hand">再抽一手</button><button class="fcg-dialog-close">返回组卡</button></div>');
  }
  function deck() {
    return '<h2>每一张，都是独立的记忆。</h2><p class="fcg-muted">点击卡图阅读完整规则。✨幻影借牌仅限本房间与当天，次日或换房自动消散。</p>'+(state.temp_expired?'<p class="fcg-note">之前的幻影记忆已消散，构筑已移除过期卡；不足 25 张时已还原入门卡组。</p>':'')+'<div class="fcg-filters"><input id="fcg-search" aria-label="搜索卡名、关键字或规则" placeholder="搜索卡名、关键字或规则" value="'+esc(filter.search)+'"><select id="fcg-series" aria-label="所属系列">'+[['all','全部系列'],['vol1','Vol.1 种火初啼'],['vol2','Vol.2 红杀演武']].map(([v,n])=>'<option value="'+v+'" '+(filter.series===v?'selected':'')+'>'+n+'</option>').join('')+'</select><select id="fcg-cost" aria-label="费用">'+[['all','全部费用'],...Array.from({length:8},(_,n)=>[String(n),n+' 费']),['8+','8 费及以上']].map(([v,n])=>'<option value="'+v+'" '+(filter.cost===v?'selected':'')+'>'+n+'</option>').join('')+'</select><select id="fcg-color" aria-label="颜色"><option value="all">全部颜色</option>'+Object.keys(colors).map(c=>'<option value="'+c+'" '+(filter.color===c?'selected':'')+'>'+colors[c][0]+'</option>').join('')+'</select><select id="fcg-rarity" aria-label="版本与稀有度">'+[['all','全部版本'],['base','未升级'],['upgraded','已升级'],['rare','稀有']].map(([v,n])=>'<option value="'+v+'" '+(filter.rarity===v?'selected':'')+'>'+n+'</option>').join('')+'</select><select id="fcg-keyword" aria-label="卡片关键字"><option value="all">全部关键字</option>'+keywords.map(key=>'<option value="'+esc(key)+'" '+(filter.keyword===key?'selected':'')+'>'+esc(key)+'</option>').join('')+'</select><label><input type="checkbox" id="fcg-owned" '+(filter.owned?'checked':'')+'> 只看可用卡</label><label><input type="checkbox" id="fcg-in-deck" '+(filter.inDeck?'checked':'')+'> 只显示卡组中的卡</label><button data-action="reset-filters">重置筛选</button><span id="fcg-filter-count" class="fcg-muted" role="status"></span></div><div class="fcg-deck-layout"><div class="fcg-card-grid" id="fcg-deck-grid"></div><aside class="fcg-deck-list" id="fcg-deck-list"></aside></div>';
  }
  function hosts(ruby,snow) {
    return '<div class="fcg-hosts"><div class="fcg-host"><img src="img/fcg/hosts/ruby.png" alt="埃尔文·露比"><div><strong>埃尔文·露比</strong><p>'+esc(ruby)+'</p></div></div><div class="fcg-host"><img src="img/fcg/hosts/snow.png" alt="保琳·斯诺"><div><strong>保琳·斯诺</strong><p>'+esc(snow)+'</p></div></div></div>';
  }
  function poolFor(item) {
    const p=JSON.parse(item.reward_payload);
    return boot.cards.filter(c=>(!p.series||p.series==='all'||c.series===p.series)&&(p.upgraded===undefined||c.upgraded===p.upgraded)&&(p.rare===undefined||c.rare===p.rare)&&(!p.rare_only||c.rare)&&(!p.color||p.color==='all'||c.color===p.color));
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
      const remaining=Math.min(+item.daily_limit?Math.max(0,+item.daily_limit-(c.day===today?c.daily:0)):Infinity,+item.total_limit?Math.max(0,+item.total_limit-c.total):Infinity);
      const options=item.reward_type==='fcg_pack'?(+item.price_credits2?[1,5,10]:(+item.daily_limit?['remaining']:[1])):[1];
      html+='<article class="fcg-product"><h3>'+esc(item.name)+'</h3><p>'+esc(item.description)+'</p><small>'+(+item.daily_limit?'今日剩余 '+Math.max(0,+item.daily_limit-(c.day===today?c.daily:0))+' 次':'')+(+item.total_limit?' · 每账号限 '+item.total_limit+' 次':'')+'</small><div class="fcg-price">'+(+item.price_credits2?Number(item.price_credits2).toLocaleString()+' 切糕 / 包':'免费')+'</div>'+(item.reward_type==='fcg_pack'?'<small>每包 1 张 · 范围内各卡等概率</small><button data-action="pool" data-item="'+item.item_id+'">查看卡池 · '+poolFor(item).length+' 张</button>':'')+options.filter(n=>n===1||n==='remaining'||item.reward_type==='fcg_pack'&&+item.total_limit!==1&&+item.daily_limit!==1).map(n=>{
        const amount=n==='remaining'?remaining:n,price=Math.round(+item.price_credits2*amount*(n===10?0.8:n===5?0.9:1));
        return '<button data-action="buy" data-count="'+n+'" data-item="'+item.item_id+'" '+(owned||limited||unsupported||amount>remaining||state.credits<price?'disabled':'')+'>'+(owned?'已经拥有':limited?'已达限购':unsupported?'暂未开放':n==='remaining'?'一键领取今日剩余（'+amount+' 包）':item.reward_type!=='fcg_pack'?'购买并领取':n===1?'单抽 · '+price+' 切糕':n+' 连 · '+price+' 切糕（'+(n===5?'9':'8')+'折）')+'</button>';
      }).join('')+'</article>';
    }
    html+='</div><div class="fcg-note"><h3>余烬回收桶 · '+state.embers+' 枚印花</h3><p>非能量卡持有超过 7 张时，溢出卡自动换为 1 枚印花。能量卡不回收；印花与卡片都不兑换切糕。</p><button data-action="embers" data-kind="upgraded" '+(state.embers<20?'disabled':'')+'>20 枚 · 深潜重构包</button> <button data-action="embers" data-kind="rare" '+(state.embers<50?'disabled':'')+'>50 枚 · 核心残响包</button></div><p><a href="user_profile.php">前往个人资料装备头衔</a></p>';return html;
  }
  function render() {
    const oldRects=tableRects();
    const oldHand=content.querySelector('.fcg-tbl-hand'), handScroll=oldHand?oldHand.scrollLeft:0;
    const inTblBattle=uiMode==='table'&&tab==='battle'&&!!state.match;
    app.classList.toggle('fcg-reduced-motion',tableMotionReduced());
    // default 页头没有 viewport；进入触屏牌桌时补上，离开后恢复原页面。
    if(inTblBattle&&!document.querySelector('meta[name="viewport"]')) {
      tblViewport=document.createElement('meta');tblViewport.name='viewport';tblViewport.content='width=device-width, initial-scale=1';document.head.append(tblViewport);
    } else if(!inTblBattle&&tblViewport) {tblViewport.remove();tblViewport=null;}
    const collapsed=inTblBattle&&!state.match.result&&!tblExpanded;
    const changedTable=app.classList.contains('fcg-tbl-battle')!==inTblBattle || app.classList.contains('fcg-tbl-collapsed')!==collapsed;
    app.classList.toggle('fcg-tbl-battle',inTblBattle); document.body.classList.toggle('fcg-tbl-battle',inTblBattle);
    app.classList.toggle('fcg-tbl-collapsed',collapsed); document.body.classList.toggle('fcg-tbl-collapsed',collapsed);
    const bookmark=document.getElementById('fcg-bookmark');
    bookmark.hidden=!inTblBattle; bookmark.firstElementChild.setAttribute('aria-expanded',String(!collapsed));
    bookmark.firstElementChild.textContent=collapsed?'▾ 种火牌桌':'▴ 收起导航';
    if(!inTblBattle) {pileLogSide=null;logOpen=false;}
    // 清掉浮窗中的日志，避免切回经典时出现两个 fcg-log。
    if(!logOpen) {logPanel.innerHTML='';logPanel.hidden=true;}
    app.classList.toggle('fcg-playing',tab==='battle'&&!!state.match);
    if(tab==='battle') lastBattle=JSON.stringify(state.match);
    lastRun=JSON.stringify(state.run);
    renderWallet(); renderSocial(); renderRun(); app.querySelectorAll('.fcg-nav [data-tab]').forEach(b=>b.classList.toggle('active',b.dataset.tab===tab));
    content.innerHTML=({lobby,battle:()=>uiMode==='table'?battleTable():battleClassic(),deck,shop,draft:expedition}[tab]||lobby)();
    const hand=content.querySelector('.fcg-tbl-hand');if(hand)hand.scrollLeft=handScroll;
    if(tab==='deck') renderDeckGrid();
    if(tab==='battle'&&inspectId!==null&&!inTblBattle)inspect(inspectId);
    renderSettings(); renderPileLog(); renderLogPanel();
    const menu=document.getElementById('fcg-sticker-menu');
    if(menu) {menu.hidden=!stickerOpen;menu.previousElementSibling.setAttribute('aria-expanded',String(stickerOpen));}
    const log=document.getElementById('fcg-log'); if(log) log.scrollTop=log.scrollHeight;
    if(inTblBattle&&changedTable) window.scrollTo(0,0);
    fitTable();
    layoutHand();
    animateTable(oldRects);
    tick();
  }
  function tick() {
    document.querySelectorAll('.fcg-sticker-bubble').forEach(el=>{if(+el.dataset.expires<(Date.now()+clockOffset)/1000)el.remove();});
    const seconds=document.getElementById('fcg-seconds'),bar=document.getElementById('fcg-timer-bar');
    if(!seconds||!state.match) return;
    const remain=Math.max(0,state.match.deadline-(Date.now()+clockOffset)/1000);
    seconds.textContent=state.match.result?'已结束':remain.toFixed(1)+' 秒';
    if(bar) bar.style.width=(state.match.result?0:Math.min(100,remain/(state.match.turn===1?10:5)*100))+'%';
  }
  const lessons=[
    ['为什么打牌','欢迎来到梦境与代码的夹缝——好啦其实也没那么玄乎！如你所见，下面那群不会好好说话的小家伙（小种火），最近迷上了把在幻境里见到的东西压成扁扁的卡片。','准确来说，那群小豆丁只是顺着本能在收集外面的情报而已。但光攒着数据没法消化，所以它们就照着《记忆之链》那种老掉牙的套路，把记忆碎片做成了能互相对撞的牌局——顺便还塞了你一副最基础的陪练卡组。'],
    ['五秒，同时行动','打起来很简单！每回合大家同时掏牌，谁先把对面的体力削光谁就赢！只要手里的五色火苗（能量）够用，一回合你想甩多少张牌出去都行哦！','前提是你手速够快。第一回合给你 10 秒热身，之后每回合只有 5 秒——当然，每打出一张牌会给你续 1 秒。记住先打发电卡把每回合自动涨的能量提上去，不然抓一手大牌也只能干瞪眼。'],
    ['两次攻击，各算各的','还有还有，你可以自己提刀上去砍（玩家攻击），也可以叫一个帮手驻场（怪物攻击）！如果帮手把对面的帮手打飞了，多出来的力气还会顺便糊在对面脸上！','但注意了——你自己的攻击和帮手的攻击是分开算防御的！如果对面堆了 10 点防御，你 7 攻、帮手 9 攻，两边破不了防就是 0 伤害，别算错了！另外手牌满了（6 张）或者牌库抽干了还硬抽，是会直接叠中毒（负回血）的。'],
    ['新的记忆，从哪里来','打赢那些小家伙的话，它们一高兴就会从自己的牌堆里随便抖落一张卡送你！要是你看到有些长得很像熟人的新种火……嘘，别问，问就是它们自己捏着玩的！','嫌刷得慢、或者想弄点已经升级好的深层卡片，就带着你的切糕来我们店里买补充包。行了，去跟新手村那五只小家伙练手吧！']
  ];
  function tutorial(step=0) {
    tutorialStep=step; const l=lessons[step];
    modal('<p class="fcg-tutorial-progress">课间 '+(step+1)+' / 4</p><div class="fcg-lesson-tabs"><button data-action="lesson-resume" aria-pressed="true">入门课堂</button><button data-action="keyword-reference" aria-pressed="false">关键字速查表</button></div><h2>'+l[0]+'</h2>'+hosts(l[2],l[1])+'<div class="fcg-dialog-actions"><button data-action="lesson-prev" '+(step===0?'disabled':'')+'>上一幕</button><button data-action="lesson-next">'+(step===3?'去认识小家伙们':'下一幕')+'</button></div>');
  }
  function keywordReference() {
    modal('<div class="fcg-lesson-tabs"><button data-action="lesson-resume" aria-pressed="false">入门课堂</button><button data-action="keyword-reference" aria-pressed="true">关键字速查表</button></div><h2>关键字速查表</h2><p>露比的笔记：以下释义来自实际卡片，数字与触发条件以对应卡片为准。同一关键字的其他写法可展开查看。</p><input id="fcg-keyword-search" aria-label="搜索关键字或释义" placeholder="搜索关键字或释义" value="'+esc(referenceSearch)+'"><div id="fcg-keyword-table"></div>');
    renderKeywordTable();
  }
  function renderKeywordTable() {
    const query=referenceSearch.trim().toLocaleLowerCase();
    const list=keywords.filter(key=>!query||key.includes(query)||keywordEntries[key].some(g=>(g.label+' '+g.text).toLocaleLowerCase().includes(query))).sort((a,b)=>Number(b===query)-Number(a===query));
    const entry=g=>'<div class="fcg-keyword-example"><b>'+esc(g.label)+'</b><p>'+esc(g.text)+'</p><button data-action="detail" data-card="'+g.card+'">卡例：'+esc(boot.cards[g.card].name)+'</button></div>';
    document.getElementById('fcg-keyword-table').innerHTML=list.length?'<table class="fcg-keyword-table"><thead><tr><th scope="col">关键字</th><th scope="col">释义与卡例</th></tr></thead><tbody>'+list.map(key=>{const entries=keywordEntries[key].filter(g=>!query||key.includes(query)||(g.label+' '+g.text).toLocaleLowerCase().includes(query));return '<tr><th scope="row">'+esc(key)+'</th><td>'+entry(entries[0])+(entries.length>1?'<details><summary>其他 '+(entries.length-1)+' 种写法</summary>'+entries.slice(1).map(entry).join('')+'</details>':'')+'</td></tr>';}).join('')+'</tbody></table>':'<p class="fcg-empty">没有找到对应的关键字或释义。</p>';
  }
  app.addEventListener('click',async event=>{
    const el=event.target.closest('[data-action],[data-tab],[data-shop],.fcg-dialog-close');
    if(!el || el.disabled) return;
    if(el.classList.contains('fcg-dialog-close')) {dialog.close();return;}
    if(el.dataset.tab) {tab=el.dataset.tab;render();return;}
    if(el.dataset.shop) {shopCategory=el.dataset.shop;render();return;}
    const a=el.dataset.action,id=Number(el.dataset.card),slot=Number(el.dataset.slot);
    if(a==='settings') {settingsPanel.hidden=!settingsPanel.hidden;renderSettings();return;}
    if(a==='set-ui') {setUiMode(el.dataset.ui);return;}
    if(a==='settings-close') {settingsPanel.hidden=true;renderSettings();return;}
    if(a==='bookmark') {tblExpanded=!tblExpanded;render();return;}
    if(a==='pile-log') {pileLogSide=pileLogSide===Number(el.dataset.side)?null:Number(el.dataset.side);renderPileLog();return;}
    if(a==='pile-close') {pileLogSide=null;renderPileLog();return;}
    if(a==='log') {logOpen=!logOpen;renderLogPanel();return;}
    if(a==='log-close') {logOpen=false;renderLogPanel();return;}
    if(a==='detail') {detail(id,'',el.dataset.back||(el.closest('.fcg-reward-grid')?'reward-back':el.closest('.fcg-sample-hand')?'sample-back':el.closest('.fcg-keyword-table')?'keyword-reference':''));return;}
    if(a==='reward-back') {reward(rewardView);return;}
    if(a==='draft-enter') {await send('draft_enter');return;}
    if(a==='draft-resume') {tab=state.run&&state.run.stage==='battle'&&state.match&&state.match.draft&&!state.match.result?'battle':'draft';render();if(state.run&&state.run.stage==='over')draftEnd();return;}
    if(a==='draft-pick') {await send('draft_pick',{slot,card:id,pick:Number(el.dataset.pick),run:el.dataset.run});return;}
    if(a==='draft-node-back') {dialog.close();return;}
    if(a==='draft-choose-pick') {draftChoose('pick');return;}
    if(a==='draft-upgrade-preview') {upgradePreview(id,slot);return;}
    if(['draft-choose-upgrade','draft-choose-remove','draft-upgrade-back','draft-remove-back'].includes(a)) {draftChoose(a.includes('upgrade')?'upgrade':'remove');return;}
    if(['draft-upgrade','draft-remove','draft-reinforce'].includes(a)) {
      dialog.close();const ok=await send('draft_node',{choice:a==='draft-reinforce'?'pick':a.slice(6),slot,card:id,run:el.dataset.run,layer:Number(el.dataset.layer),...(a==='draft-remove'?{removed:Number(el.dataset.removed)}:{})});
      if(ok&&a==='draft-remove'&&state.run&&state.run.stage==='node'&&state.run.node.type==='victory') draftChoose('remove');return;
    }
    if(a==='draft-battle') {await send('draft_battle',{layer:Number(el.dataset.layer)});return;}
    if(a==='draft-reward') {dialog.close();await send('draft_reward',{run:el.dataset.run});return;}
    if(a==='draft-last-reward') {reward(state.last_reward);return;}
    if(a==='draft-abandon') {modal('<h2>在这里结束远征？</h2><p>按当前胜场结算最终奖励，其余幻影记忆消散。已支付的 '+(state.run.entry_cost??500)+' 切糕入场费不退还。</p><button data-action="draft-abandon-confirm" data-run="'+esc(state.run.id)+'">放弃并结算</button>');return;}
    if(a==='draft-abandon-confirm') {dialog.close();await send('draft_abandon',{run:el.dataset.run});return;}
    if(a==='sample-hand') {drawSample();return;}
    if(a==='sample-back') {showSample();return;}
    if(['invite','accept','decline','mirror'].includes(a)){await send(a,{uid:Number(el.dataset.uid)});return;}
    if(a==='sticker-menu'){const menu=document.getElementById('fcg-sticker-menu');stickerOpen=menu.hidden;menu.hidden=!stickerOpen;el.setAttribute('aria-expanded',String(stickerOpen));return;}
    if(a==='sticker'){stickerOpen=false;document.getElementById('fcg-sticker-menu').hidden=true;await send(a,{sticker:Number(el.dataset.sticker)});return;}
    if(a==='tutorial') {tutorial();return;}
    if(a==='lesson-resume') {tutorial(tutorialStep);return;}
    if(a==='keyword-reference') {keywordReference();return;}
    if(a==='lesson-prev') {tutorial(Math.max(0,tutorialStep-1));return;}
    if(a==='lesson-next') {if(tutorialStep<3)tutorial(tutorialStep+1);else{dialog.close();await send('tutorial');}return;}
    if(a==='pool') {
      const item=boot.catalog.find(i=>+i.item_id===Number(el.dataset.item)); const pool=poolFor(item);
      modal('<h2>'+esc(item.name)+'</h2><p>每包随机 1 张，以下 '+pool.length+' 张卡等概率（各约 '+(100/pool.length).toFixed(2)+'%）。</p><div class="fcg-card-grid">'+pool.map(c=>card(c.id,'preview')).join('')+'</div>');return;
    }
    if(a==='add') {
      const c=boot.cards[id],n=counts(draft)[id]||0;
      if(draft.length<100&&n<available(id)&&(c.unlimited||n<7)){draft.push(id);dirty=true;renderDeckGrid();}return;
    }
    if(a==='remove') {const at=draft.indexOf(id);if(at>=0){draft.splice(at,1);dirty=true;renderDeckGrid();}return;}
    if(a==='clear-deck') {draft=[];dirty=true;renderDeckGrid();return;}
    if(a==='restore-deck') {draft=state.deck.slice();dirty=false;notice('已还原上次保存的卡组。');renderDeckGrid();return;}
    if(a==='reset-filters') {filter={cost:'all',color:'all',rarity:'all',series:'all',keyword:'all',search:'',owned:false,inDeck:false};render();return;}
    if(a==='save-deck') {await send('deck',{deck:draft});return;}
    if(a==='start') {await send('start',{opponent:Number(el.dataset.opponent),hard:el.dataset.hard==='1'});return;}
    if(a==='play'||a==='discard') {await send(a,{slot,card:id,turn:Number(el.dataset.turn)});return;}
    if(a==='destroy') {await send(a);return;}
    if(a==='surrender') {modal('<h2>收起这一局的牌？</h2><p>'+(state.match&&state.match.draft?'认输会结束本次远征，按此前的胜场结算最终奖励。':'认输不会失去收藏，本局不会获得胜利卡片。')+'</p><button data-action="surrender-confirm">确认认输</button>');return;}
    if(a==='surrender-confirm') {dialog.close();await send('surrender');return;}
    if(a==='buy') {const item=boot.catalog.find(i=>+i.item_id===Number(el.dataset.item));await send('buy',{item:+item.item_id,price:+item.price_credits2,count:el.dataset.count==='remaining'?'remaining':Number(el.dataset.count)});return;}
    if(a==='embers') await send(a,{kind:el.dataset.kind});
  });
  app.addEventListener('input',event=>{
    if(event.target.id==='fcg-search'){filter.search=event.target.value;renderDeckGrid();}
    if(event.target.id==='fcg-keyword-search'){referenceSearch=event.target.value;renderKeywordTable();}
  });
  app.addEventListener('change',event=>{
    const id=event.target.id;
    if(id==='fcg-color')filter.color=event.target.value;
    else if(id==='fcg-cost')filter.cost=event.target.value;
    else if(id==='fcg-series')filter.series=event.target.value;
    else if(id==='fcg-rarity')filter.rarity=event.target.value;
    else if(id==='fcg-keyword')filter.keyword=event.target.value;
    else if(id==='fcg-in-deck')filter.inDeck=event.target.checked;
    else if(id==='fcg-owned')filter.owned=event.target.checked;else return;
    renderDeckGrid();
  });
  render();
  app.addEventListener('mouseover',event=>{const el=event.target.closest('[data-inspect]');if(el)inspect(Number(el.dataset.inspect));});
  app.addEventListener('focusin',event=>{const el=event.target.closest('[data-inspect]');if(el)inspect(Number(el.dataset.inspect));});
  app.addEventListener('mouseout',event=>{
    if(!app.classList.contains('fcg-tbl-battle'))return;
    const from=event.target.closest('[data-inspect]'), to=event.relatedTarget instanceof Element?event.relatedTarget.closest('[data-inspect]'):null;
    if(from&&from!==to) {const inspector=document.getElementById('fcg-inspector');if(inspector)inspector.hidden=true;inspectId=null;}
  });
  app.addEventListener('keydown',event=>{
    if(event.key==='Enter'&&event.target.matches('img[data-action="detail"]'))event.target.click();
    if((event.key==='Enter'||event.key===' ')&&event.target.matches('.fcg-tbl-handcard[data-action="play"]')) {event.preventDefault();event.target.click();}
  });
  document.addEventListener('click',event=>{
    if(!settingsPanel.hidden&&!event.target.closest('#fcg-settings,[data-action="settings"]')) {settingsPanel.hidden=true;renderSettings();}
    if(pileLogSide!==null&&!event.target.closest('#fcg-pile-log,[data-action="pile-log"]')) {pileLogSide=null;renderPileLog();}
    if(logOpen&&!event.target.closest('#fcg-log-panel,[data-action="log"]')) {logOpen=false;renderLogPanel();}
  },true);
  document.addEventListener('keydown',event=>{
    if(event.key==='Escape') {
      settingsPanel.hidden=true;pileLogSide=null;logOpen=false;renderSettings();renderPileLog();renderLogPanel();
      const inspector=document.querySelector('.fcg-tbl-inspector');if(inspector)inspector.hidden=true;
    }
  });
  let tableResize=0;
  window.addEventListener('resize',()=>{if(!tableResize)tableResize=requestAnimationFrame(()=>{tableResize=0;fitTable();layoutHand();});});
  if(!state.tutorial) tutorial();
  setInterval(tick,100);
  setInterval(()=>{if(!document.hidden && !busy && (state.in_room||state.match&&!state.match.result)) send('status');},1000);
  document.addEventListener('visibilitychange',()=>{if(!document.hidden)send('status');});
  window.addEventListener('beforeunload',event=>{if(dirty){event.preventDefault();event.returnValue='';}});
})();
