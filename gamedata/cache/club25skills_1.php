<?php
if(!defined('IN_GAME')) exit('Access Denied');

include_once GAME_ROOT.'./include/game/club25.func.php';
$club25cfg = club25_config();
$club_skillslist[25] = Array('s_hp','s_ad','f_heal','c25_karma','c25_modify','c25_hide','c25_feed','c25_field','c25_trip','c25_line','c25_net');

$cskills['c25_karma'] = Array(
	'name'=>'暗业', 'tags'=>Array('passive'),
	'desc'=>"普通武器最终伤害只保留{$club25cfg['weapon_percent']}%，经验与熟练照常获得。受到的陷阱、食用毒物、幻影与诡雷直接伤害只保留{$club25cfg['hazard_percent']}%；不减免战斗毒属性或行动中毒伤害。<br>自己的普通机关首次实际伤害敌人或新增中毒时，获得一次正常命中经验与{$club25cfg['reward_wd']}点爆熟。同一机关不重复计奖，自己与队友不计。<br><span class='grey'>你只是看起来很好欺负。只是看起来。</span>",
);
$cskills['c25_modify'] = Array(
	'name'=>'改装', 'tags'=>Array('active'), 'input'=>'改装',
	'desc'=>"将武器改成一枚普通雷并立即埋设。有限耐久每次消耗{$club25cfg['modify_durability']}点，效果随原武器效果与种类变化；爆系转换效率最高，射系较低，不继承原属性。无限耐久武器消耗整件。<br>在副面板「可怜猫」选择当前武器、背包或刚发现的武器。<br><span class='grey'>不能咬人的东西，稍微改一改就能咬人了。</span>",
);
$cskills['c25_hide'] = Array(
	'name'=>'捉迷藏', 'tags'=>Array('active'), 'input'=>'藏起来',
	'desc'=>"消耗{$club25cfg['hide_sp']}体力，把自己的幻影藏进补给。第一次实际食用时，先受到制作时基础攻击×{$club25cfg['hide_att_rate']}＋爆熟×{$club25cfg['hide_wd_rate']}的机关伤害，并尝试造成足部受伤；存活后正常食用。整堆只触发一次，随后幻影消失。无需毒药。<br><span class='grey'>你咬补给一口，补给里的我咬你一口。很公平吧？</span>",
);
$cskills['c25_feed'] = Array(
	'name'=>'投喂', 'tags'=>Array('active'), 'input'=>'投喂',
	'desc'=>"消耗{$club25cfg['feed_rage']}怒气，取出一堆{$club25cfg['feed_effect']}效、{$club25cfg['feed_count']}份的普通命体补给。可以自己吃，也可以藏入幻影或装上诡雷。生成与自行食用不返还怒气。<br><span class='grey'>先给你一点甜头。至于里面有没有我，那可说不准。</span>",
);
$cskills['c25_field'] = Array(
	'name'=>'布场', 'tags'=>Array('active'), 'input'=>'打开副面板',
	'desc'=>'通过副面板查看自己留在全图地面的机关，投放背包或刚发现的物品，一次最多埋设3枚普通雷，也可在当地撤回自有布置。被拿走的物品不再追踪携带者。<br><span class="grey">这里是我的，那边也是我的。你脚下那个……也是哦。</span>',
);
$cskills['c25_trip'] = Array(
	'name'=>'诡雷', 'tags'=>Array('active'), 'input'=>'装机关',
	'desc'=>"消耗{$club25cfg['trip_sp']}体力，为物品装上一次拾取机关：选择造成半份幻影伤害，或尝试使拾取者中毒。只在成功拿入背包、装备或合并时触发，仅仅发现不会触发。陷阱探测装备可看出细线。可与捉迷藏共存，各自触发一次。<br><span class='grey'>你都伸手拿了，就是愿意陪我玩吧？</span>",
);
$cskills['c25_line'] = Array(
	'name'=>'复线', 'tags'=>Array('passive'),
	'desc'=>"主动埋设普通雷时，按当时爆熟×{$club25cfg['trap_wd_rate']}追加固定威力，并产生{$club25cfg['copy_count']}枚半效副雷；回收重布不重复增殖或叠加威力，副雷不再复制。自己的普通雷有{$club25cfg['trap_floor']}%的统一接触率下限，每次行动仍最多一枚。特殊比例与致死雷不参与。<br>主动布置后获得一次「猫步」：下次成功移动不因该移动遭遇敌人或踩雷。次数不累积，不免除已有异常、天气、禁区，也不能阻止别人主动找到你。<br><span class='grey'>我已经走远了，你不用再送啦。</span>",
);
$cskills['c25_net'] = Array(
	'name'=>'收网', 'tags'=>Array('battle'),
	'desc'=>'放弃本次普通攻击，使用与当前敌人同地点的一份普通雷攻击对方；面对NPC时也可诱其食用地面毒物或捉迷藏补给。可以借用他人的布置，照实消耗现场物品，敌人仍可反击。有主机关的伤害与击杀归原制作者，无主机关归你。特殊雷不能定点引爆。',
	'bdesc'=>'选择战斗面板中的当地机关收网；消耗现场物品，仍承担敌方反击。',
);
