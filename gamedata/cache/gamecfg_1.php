<?php
/*Game Config*/

//禁区间隔时间,单位 小时
$areahour = 40;
//每次间隔增加的禁区数量
$areaadd = 4;
//聊天记录里的禁区提示时间，单位秒
$areawarntime = 60;
//玩家激活结束时的增加禁区的回数，相当于已经进行的小时数/间隔时间，〉0
$arealimit = 2;
//是否自动逃避禁区 0=只有重视躲避自动躲避，1=所有玩家自动躲避，适合新手较多，不了解禁区机制
$areaesc = 1;
//是否开启死斗模式 0=关闭,1=开启。连斗后下次禁区，进入死斗状态，死斗后玩家只会遇到玩家，死斗后所有区域都将一次性宣布为禁区。（尚未完成）
$isduel = 0;

//开启快捷键？0为不开启，1为开启
$hotkeyon = 0;
//开启NPC台词功能？0为不开启，1为开启
$npcchaton = 1;
//有台词的NPC
$npccanchat = Array(1,5,6,7,9,10,12,13,15,21,22);
//反挂机系统间隔时间，单位分钟
$antiAFKertime = 20;
//尸体保护时间，单位秒
$corpseprotect = 10;
//是否启动冷却时间，0为不启动，1为启动；
$coldtimeon = 1;
//是否显示冷却时间倒计时，0为不显示，1为显示；
$showcoldtimer = 1;
//移动的冷却时间，单位微秒
$movecoldtime=900;
//探索的冷却时间，单位微秒
$searchcoldtime=900;
//使用物品的冷却时间，单位微秒
$itemusecoldtime=600;

//胜率榜最小参赛次数
$winratemingames = 50;
//是否启动赌注系统？0为不启动，1为启动；
$gamblingon = 1;

//本局游戏人数限制
$validlimit = 300;
//连斗时的人数限制
$combolimit = 50;
//连斗最小死亡人数限制a
$deathlimit = 160;
//连斗激活系数分母b
$deathdeno = 20;
//连斗激活系数分子c。如果设参与人数为d，则实际连斗判定死亡数是a+ceil(d/b)*c
$deathnume = 20;
 
// 等级提升基本经验值 
$baseexp = 9;
// 初始耐力最大值 
$splimit = 400;
// 初始生命最大值 
$hplimit = 400;
// 怒气最大值 
$mrage = 500;
//携带金钱上限
$moneylimit = 65500;

// 恢复量的设定
//体力恢复时间(秒):*秒1点恢复
$sleep_time = 3;
//生命恢复时间(秒):*秒1点恢复
$heal_time = 6;
//包扎伤口需要的体力
$inf_sp = 50;
//治疗特殊状态需要的体力
$inf_sp_2 = 200;
//创建队伍需要的体力
$team_sp = 200;
//加入队伍需要的体力
$teamj_sp = 100;
//队伍最大人数
$teamlimit = 10;

//随机事件几率(百分比)
$event_obbs = 5;
//强制踩陷阱最小几率(百分比)
$trap_min_obbs = 1;
//强制踩陷阱最大几率(百分比)
$trap_max_obbs = 40;
//道具发现基础几率(百分比);
$item_obbs = 60;
//敌人发现基础几率(百分比)
$enemy_obbs = 70;
//尸体发现几率（百分比）
$corpse_obbs = 50;
//基础先攻率
$active_obbs = 50;
//基础反击率
$counter_obbs = 50;

//受伤状态的设定
//h-头部受伤，b-身体受伤,a-手腕受伤，f-足部受伤，p-中毒，u-烧伤，i-冻结，e-麻痹
//各种受伤状态对移动消耗体力的影响
$inf_move_sp = Array('f'=> 10, 'i'=> 20,'e'=> 5);
//各种受伤状态对探索消耗体力的影响
$inf_search_sp = Array('a'=> 10, 'i'=> 20,'e'=> 5);
//各种受伤状态移动时消耗的生命力，百分比
$inf_move_hp = Array('p'=> 0.0625, 'u'=> 0.0625);
//各种受伤状态探索时消耗的生命力，百分比
$inf_search_hp = Array('p'=> 0.03125, 'u'=> 0.03125);
//hack基础成功率
$hack_obbs = 40;
//电子设备充电上限，包括电脑和雷达
$elec_cap = 5;

//成就总数
$achievement_count=70;

?>
