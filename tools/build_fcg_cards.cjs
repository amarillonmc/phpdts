// Rebuild the authored card data and art job list from the approved consultation document.
// Usage: node tools/build_fcg_cards.cjs <Vol.1 document> <reference-art-directory> [Vol.2 document]
const fs = require('fs');
const path = require('path');
const source = fs.readFileSync(process.argv[2], 'utf8') + (process.argv[4] ? '\n'+fs.readFileSync(process.argv[4], 'utf8') : '');
const refRoot = process.argv[3];
const root = path.resolve(__dirname, '..');
const clean = s => s.replace(/<br\s*\/?\s*>/gi, '\n').replace(/[`*]/g, '').trim();
const variant = (s, up, mechanics = false) => {
  s = mechanics ? s.replace(/<br\s*\/?\s*>/gi, '\n').replace(/`|\*\*/g, '').trim() : clean(s);
  const m = s.match(/Base:\s*([\s\S]*?)\s*Upg:\s*([\s\S]*)/);
  return m ? m[up ? 2 : 1].trim() : s;
};
const color = {'⚪':'W','🔴':'R','🟡':'Y','🟢':'G','🔵':'B'};
const cards = [], jobs = [];
for (const line of source.split(/\r?\n/)) {
  if (!/^\| \*\*#\d+/.test(line)) continue;
  const c = line.split('|').slice(1,-1).map(x=>x.trim());
  const pair = Number(c[0].match(/#(\d+)/)[1]);
  const names = clean(c[3]).split('➔').map(x=>x.trim());
  const flavors = clean(c[6]).split(/\n?•\s*/).filter(Boolean).map(x=>x.replace(/〔[^〕]*〕/g,'').trim());
  const prompts = c[7].match(/\*\*Base\*\*:\s*([\s\S]*?)<br>\*\*Upg\*\*:\s*([\s\S]*)/);
  if (!prompts) throw new Error('No art prompt for pair '+pair);
  const ref = c[7].split('**Base**:')[0];
  const refs = [...ref.matchAll(/`([^`]+)`/g)].map(x=>path.resolve(refRoot,x[1]));
  for (const file of refs) if (!fs.existsSync(file)) throw new Error('Missing reference '+file);
  for(let up=0;up<2;up++) {
    const id = (pair-1)*2+up, costs=clean(c[4]).match(/(\d+)E/g);
    const stats=variant(c[4],up).match(/(\d+)\/(\d+)\/(\d+)/);
    const card={id,pair,name:names[up],color:color[c[1]],type:c[2].includes('Monster')?'monster':'spell',rare:c[2].includes('Rare'),upgraded:!!up,cost:Number(costs[Math.min(up,costs.length-1)].replace('E','')),atk:stats?+stats[1]:0,def:stats?+stats[2]:0,hp:stats?+stats[3]:0,ops:variant(c[5],up,true),flavor:flavors[up]||flavors[0],unlimited:pair<=12,art:'img/fcg/cards/'+String(id).padStart(3,'0')+'.png'};
    card.series=pair<=75?'vol1':'vol2';
    card.ops=card.ops.split(/\n【|\n💘/)[0].trim();
    cards.push(card);
    jobs.push({id,name:card.name,path:card.art,refs,prompt:clean(prompts[up+1])+' anime digital illustration, cel-shaded with atmospheric lighting, clean composition, card game illustration art, NO text, NO numbers, NO card border, NO UI frame, NO watermark.'});
  }
}
const expected=process.argv[4]?230:150;
if(cards.length!==expected || new Set(cards.map(c=>c.id)).size!==expected) throw new Error('Expected all '+expected+' distinct cards');
// The document explicitly permits cross-color adjustments to obtain 15 pairs per color.
// Preserve every name, effect and original art prompt; record these choices in the change note.
const counts=()=>Object.fromEntries(Object.keys(color).map(k=>[color[k],cards.filter(c=>c.color===color[k]).length]));
console.log('Authored color counts',counts());
const targets={40:'R',36:'R',54:'G',31:'R',56:'G',68:'G',70:'R',71:'G',75:'R'};
for(const c of cards) if(targets[c.pair]) c.color=targets[c.pair];
fs.mkdirSync(path.join(root,'gamedata/ruleset/FCG'),{recursive:true});
fs.mkdirSync(path.join(root,'img/fcg/cards'),{recursive:true});
fs.mkdirSync(path.join(root,'doc/fcg'),{recursive:true});
fs.writeFileSync(path.join(root,'gamedata/ruleset/FCG/cards.php'),"<?php\nif(!defined('IN_GAME')) exit('Access Denied');\nreturn json_decode(<<<'FCG_CARDS'\n"+JSON.stringify(cards,null,2)+"\nFCG_CARDS\n, true);\n");
for(const series of ['vol1','vol2']) {
  const list=jobs.filter(j=>series==='vol1'?j.id<150:j.id>=150);
  if(list.length) fs.writeFileSync(path.join(root,'doc/fcg/'+(series==='vol1'?'art-jobs':'vol2-art-jobs')+'.json'),JSON.stringify(list,null,2)+'\n');
}
const portraits = source.split(/\r?\n/).filter(line=>/^\| \d{2} \|/.test(line)).map(line=>{
  const c=line.split('|').slice(1,-1).map(x=>x.trim()), id=Number(c[0]);
  const refs=[...c[3].matchAll(/`([^`]+)`/g)].map(x=>path.resolve(refRoot,x[1]));
  return {id,name:clean(c[1]),path:'img/fcg/fireseeds/'+String(id).padStart(2,'0')+'.png',refs,
    prompt:clean(c[4]).replace('"WARNING" paper slips','warning-symbol paper slips')+' Single full-body small chibi Fireseed portrait, square composition, dark virtual background. First reference is the small Fireseed body and art style; other references supply only color, mood and signature motifs, not adult faces or body proportions. No readable text, letters, numbers, border, UI or watermark.'};
});
if(portraits.length!==(process.argv[4]?16:12)) throw new Error('Unexpected Fireseed portrait count');
fs.mkdirSync(path.join(root,'img/fcg/fireseeds'),{recursive:true});
for(const series of ['vol1','vol2']) {
  const list=portraits.filter(j=>series==='vol1'?j.id<=12:j.id>12);
  if(list.length) fs.writeFileSync(path.join(root,'doc/fcg/'+(series==='vol1'?'portrait-jobs':'vol2-portrait-jobs')+'.json'),JSON.stringify(list,null,2)+'\n');
}
console.log('Cards',cards.length,'Rare',cards.filter(c=>c.rare).length,'Colors',counts());
