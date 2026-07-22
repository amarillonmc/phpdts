<?php

if (!defined('IN_GAME')) {
	exit('Access Denied');
}

// 暴走笼中鸟：规则集剧情 / Caged Bird Rampage: ruleset story
global $ruleset_stories, $ruleset_story_pages;

$ruleset_stories['RAIDCAGEDBIRD'] = Array(
	'opening' => Array(
		'title' => 'RAID：暴走笼中鸟',
		'content' => '
			<div class="story-content">
				<h3>这次不是通常的大逃杀</h3>
				<p>开场广播被一阵刺耳的数据噪声切断。屏幕重新亮起时，出现在上面的不是执行官，而是芙蓉。</p>
				<p>“听好。笼中鸟已经脱离韩如雪的控制——她进来的时候，执行官就被抹掉了。生存、解禁、解离，那些旧路线全都不要再想。”</p>
				<p><span class="yellow">搜打撤：</span>打破地图上的容器，收纳宝藏并在仍有命的时候撤离。</p>
				<p><span class="yellow">平息笼中鸟：</span>找到我指定的材料，在隐藏地点【代码源流】转化为封印数据，再部署传送进来的三件装置部件。</p>
				<p class="story-note">“她会追逐报应点数高的人。看见她停下来充能，就立刻离开那张地图。”</p>
			</div>
		',
		'buttons' => Array(
			Array('text' => '记住两条路线', 'action' => 'close'),
		),
	),
	'ending' => Array(
		'title' => 'RAID记录封存',
		'content' => '
			<div class="story-content">
				<h3>异常事件已经结束</h3>
				<p>芙蓉关闭了最后一页遥测记录。被带离会场的宝藏、数据与伤亡名单一并写入本轮档案。</p>
			</div>
		',
		'buttons' => Array(
			Array('text' => '返回大厅', 'action' => 'redirect', 'url' => 'index.php'),
		),
	),
);

$ruleset_story_pages['RAIDCAGEDBIRD'] = Array(
	'opening' => Array(
		'广播没有响起。执行官的位置只剩下一片无法读取的空白。<br><br>几秒后，芙蓉抢占了画面：<span class="red">“这是异常。笼中鸟已经暴走，而且她刚刚抹掉了这里原本的控制者。”</span>',
		'“她会吸收幻境里的报应点数，并优先追击积累得最多的人。第一个人走出足够远以后，她就会进入会场。别想着正面击倒她。”<br><br>芙蓉把两条路线写在屏幕两侧：<span class="yellow">搜打撤</span>，或者<span class="yellow">平息笼中鸟</span>。',
		'<span class="evergreen b">■ ROUTE A：搜打撤 / PvP ■</span><br>击破容器，收纳宝藏与其他物品换取积分，并在任何时候主动撤离。死者若留下尸体，已收纳的积分可能被后来者全部夺走。',
		'<span class="evergreen b">■ ROUTE B：平息笼中鸟 / PvE ■</span><br>收集本轮指定材料，用商店的【源流折跃信标】进入【代码源流】并转化数据。进度充满后，寻找三件封印部件并在指定地点分别部署。',
		'“任务面板会一直显示收纳物、材料、进度和装置坐标。还有——如果她开始充能，不要犹豫，马上换地图。”<br><br><span class="red b">■ RAID：暴走笼中鸟 ■</span>',
	),
	'death' => Array(
		'你的生命信号从RAID面板上熄灭。<br><br>芙蓉没有停下引导；容器、封印数据和正在追逐其他人的笼中鸟仍在会场里运转。',
		'<span class="grey">“如果尸体还在，后来者或许能把你收纳的成果带出去。”</span>',
	),
	'end1' => Array(
		'连斗结束后，会场中已经找不到任何活着、也尚未撤离的玩家。<br><br>封印流程无人完成，笼中鸟继续在空荡的地图间回收最后一点报应。',
		'<span class="red b">■ NO SURVIVORS ■</span><br><br><span class="grey">芙蓉切断了通信。无人幸存。</span>',
	),
	'end8_winner' => Array(
		'第三件装置部件在指定地点展开。遍布地图的封印坐标同时响应，白色数据链从【代码源流】贯穿会场，把仍在暴走的外壳重新锁回笼中。',
		'笼中鸟最后一次抬起头。席卷地图的报应洪流没有落下；外壳在交错的封印线里缩成一个暗点，随后彻底沉寂。<br><br><span class="evergreen b">■ RAID COMPLETE：平息笼中鸟 ■</span>',
		'“成功了。”芙蓉的声音终于松弛下来，“当前仍在场的你们完成了封印。奖励和本轮积分会按记录结算。”',
	),
	'end8_other' => Array(
		'最后一件封印部件已经部署。你从场外记录中看见，数据链合拢成新的牢笼，将那具失控外壳重新封入黑暗。',
		'<span class="evergreen b">■ RAID COMPLETE：平息笼中鸟 ■</span><br><br>胜利属于完成部署时仍在场、仍然活着的执行者与其合资格队友；这次异常终于没有继续扩散。',
	),
	// 当前通用剧情路由不会为winmode 8区分winner/other，提供完整的中性封印页。
	// The generic story router does not split winner/other for winmode 8, so expose a complete neutral ending.
	'end8' => Array(
		'第三件装置部件展开，遍布地图的封印坐标同时响应。白色数据链从【代码源流】贯穿会场，把仍在暴走的外壳重新锁回笼中。',
		'<span class="evergreen b">■ RAID COMPLETE：平息笼中鸟 ■</span><br><br>胜者名单以最后部署瞬间仍存活、尚未撤离且通过同IP去重的队伍快照为准。',
	),
);

?>
