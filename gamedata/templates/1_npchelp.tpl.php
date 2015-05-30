<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<p><span class="lime">BOSS NPC简介</span>：</p>
此类NPC对玩家<span class="yellow">均有较大威胁</span>，在<span class="yellow">没有足够高的防御</span>的情况下，请<span class="yellow">尽可能避免和它们的接触</span>，也<span class="yellow">不要贸然攻击它们</span>，否则很可能导致自己被击杀。<br>
当然，如果成功击杀了这些NPC，它们也会掉落<span class="lime">大量有用的道具或金钱</span>。<br>
<br>
<?php if(is_array($ty1)) { foreach($ty1 as $vkind => $kind) { include template('npcinfohelp'); } } ?>
<br>
<p><span class="lime">真职人 NPC简介</span>：</p>
此类NPC在开局对<span class="yellow">无防具的玩家有较大威胁</span>，这类NPC的特点是<span class="yellow">防御非常高</span>，且<span class="yellow">武器均带有“电击+冻气+带毒+火焰+音波”属性</span>，很容易导致玩家中异常状态。<br>如果防具很差，或没有属性防御也没钱买药剂，请不要贸然攻击它们，否则很可能导致自己中大量异常状态后难以恢复，苦不堪言。<br>
当然，击杀这些NPC后可以获取<span class="lime">极为优秀的防具</span>，在游戏中后期<span class="lime">击杀它们并拾取它们的防具保护自己</span>往往是玩家取胜的关键。<br>
<br>
<?php if(is_array($ty2)) { foreach($ty2 as $vkind => $kind) { include template('npcinfohelp'); } } ?>
<br>
<p><span class="lime">全息幻象NPC、小兵NPC 简介</span>：</p>
此类NPC对玩家基本无威胁，是玩家<span class="yellow">最主要的熟练度、经验来源，也是最主要的击杀对象</span>。每个小兵NPC掉落金钱220元，是玩家<span class="lime">主要金钱来源</span>。<br>全息幻象掉落<span class="lime">更多的金钱</span>以及<span class="lime">各系优秀的武器或强化道具</span>，往往是<span class="yellow">拉开玩家差距、建立优势乃至取得最终胜利的关键</span>。<br>
<br>
<?php if(is_array($ty3)) { foreach($ty3 as $vkind => $kind) { include template('npcinfohelp'); } } ?>
<br>
<p><span class="lime">特殊NPC 简介</span>：</p>
此类NPC对玩家无威胁，但当玩家击杀它们后，它们会变身为<span class="yellow">“第二形态”</span>，此时<span class="yellow">攻击力会变得极强</span>。<br>可别不小心击杀了它们后被第二形态秒杀哦～ 不过，当自己处于劣势时，偷偷击杀这类NPC，并期望对手撞上它们并被它们秒杀，也是不错的翻盘思路哦～<br>
<br>
<?php if(is_array($ty4)) { foreach($ty4 as $vkind => $kind) { include template('npcinfohelp'); } } ?>
<br>
<p><span class="lime">妖精幻象 簡介</span>：</p>
靈魂抽取定義：<span class="yellow">戰鬥中使雙方的武器和飾品上的屬性都無效化，而且靈系武器的傷害下降到只剩1%。</span><br>
精神抽取定義：<span class="yellow">戰鬥中使雙方身上的四件防具，擁有的效果全部無效化。</span><br>
技能抽取定義：<span class="yellow">戰鬥中使雙方的熟練度開根號後才計算物理傷害。</span><br>
此類NPC對玩家沒有危險性，<span class="yellow">不會迴避禁區。</span><br>
<br>
<?php if(is_array($ty5)) { foreach($ty5 as $vkind => $kind) { include template('npcinfohelp'); } } ?>
<br>
<p><span class="lime">抹殺使徒 簡介</span>：</p>
抹殺使徒出場的定義：當<span class="yellow">AC专业职人</span>被殺死的時候，就會亂入戰場，初始位置是全圖隨機。<br>
AC专业职人：全圖只有一位，身上的數據、屬性、武器、頭像都和<span class="yellow">AC专业喷子</span>相同，所以玩家可以秒殺小兵的時候很難察覺到。<br>
由於<span class="yellow">AC专业职人</span>是全圖隨機，所以要是刷在開局的熱門地點可能很快就會讓AI出場，但如果刷在SCP就可能到死鬥的時候都沒出場。<br>
此NPC對玩家危險性很高，但是會自動避開非目標範圍，<span class="yellow">AI每一次移動位置的時候都會在聊天框顯示訊息。</span><br>
<span class="yellow">即使死鬥後，AI依然會存在，但AI本身不算是玩家，所以如果只剩下玩家一人和AI時依然算獲勝。</span><br>
AI會根據玩家目前的<span class="yellow">rp和APM</span>判定是不是目標，只要有一個條件缺乏就不會主動追人。<br>
<span class="yellow">每攻擊一次AI，AI都有機率全屬性上升，會越來越強。</span>和AI對決時請速戰速決。<br>
<br>
<?php if(is_array($ty6)) { foreach($ty6 as $vkind => $kind) { include template('npcinfohelp'); } } ?>
<p><span class="lime">天神NPC 简介</span>：</p>
天神称号授予对ACFUN大逃杀的创造和发展有里程碑式贡献的人物。目前只有冴月麟和四面两人。<br>
虽然理论上还应该有初七等人，但是由于GM的偷懒并没有加入。该等级的NPC对玩家基本就是<span class="yellow">一击即死</span>。<br>
但是因为没有<span class="yellow">伤害制御和强袭姿态</span>，威胁实际上不如武神。 <br>
<br>
<?php if(is_array($ty7)) { foreach($ty7 as $vkind => $kind) { include template('npcinfohelp'); } } ?>
<br>
<p><span class="lime">武神NPC 简介</span>：</p>
武神称号授予参与过ACFUN大逃杀开发工作的人员。武神装备有接近10000总防御的防具，拥有很高的基础攻防，并各有特色。<br>
武神统一拥有<span class="yellow">“伤害制御”</span>属性，<span class="lime">有90%的几率把受到的伤害压缩到2000点</span>。<br>
由於大多数武神拥有<span class="yellow">“强袭姿态”</span>，先攻率远高于其他NPC，是进军英灵殿玩家的最大威胁。<br>
<br>
<?php if(is_array($ty8)) { foreach($ty8 as $vkind => $kind) { include template('npcinfohelp'); } } ?>
<br>
<br>
