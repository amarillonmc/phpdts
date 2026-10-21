<?php
if(!defined('IN_GAME')) exit('Access Denied');

// AN:OTHER 的世界与数值。库存是整局固定投放，不随账号或角色数复制。
function another_config()
{
    static $config = NULL;
    if($config !== NULL) return $config;
    $maps = array(
        1 => array('name'=>'旧商业街集市', 'indoor'=>false, 'cover'=>1.0, 'description'=>'卷帘门停在半开的高度。摊位上的武器还带着封条，电子价签却已经亮起。这里是各条街道交汇的地方。', 'x'=>440, 'y'=>290),
        2 => array('name'=>'大排档', 'indoor'=>true, 'cover'=>1.0, 'description'=>'锅里没有火，外卖保温箱却仍温热。通往地下训练场的门被撬开，地上散落着护具和试纸。', 'x'=>440, 'y'=>445),
        3 => array('name'=>'金龙通讯社', 'indoor'=>true, 'cover'=>1.0, 'description'=>'所有报纸都停在同一天。柜台后有能用的通信设备，呼叫记录里没有任何署名。', 'x'=>585, 'y'=>230),
        4 => array('name'=>'玄武数据公园', 'indoor'=>false, 'cover'=>0.85, 'description'=>'树冠挡住了远处大厦的倒影。维护通道连接旧城和山区，灌木下偶尔露出金属绊线。', 'x'=>325, 'y'=>195),
        5 => array('name'=>'林氏软件生态大厦', 'indoor'=>true, 'cover'=>1.0, 'description'=>'大厅的接待屏反复播放欢迎语。安保柜里有新换上的物资；楼上的信号灯没有回应。', 'x'=>650, 'y'=>300),
        6 => array('name'=>'DTS线下体验馆', 'indoor'=>true, 'cover'=>1.0, 'description'=>'展台仍挂着体验装备的说明牌。箱子里的武器却有真实的重量，不能再把这里当作演示场。', 'x'=>805, 'y'=>300),
        7 => array('name'=>'地下通勤商场', 'indoor'=>true, 'cover'=>1.0, 'description'=>'购物广播只有杂音。饮料、缝补工具和弹药被混放在关门的店铺前，几条员工通道仍可通行。', 'x'=>580, 'y'=>385),
        8 => array('name'=>'「下层」灰市', 'indoor'=>true, 'cover'=>0.85, 'description'=>'报废线路旁的摊位无人看守。警报器、药剂和拆除工具并排摆着，瓶身标签比价签更值得细看。', 'x'=>580, 'y'=>495),
        9 => array('name'=>'自动化集装箱码头', 'indoor'=>false, 'cover'=>1.15, 'description'=>'吊机停在半空，整片堆场无遮无挡。货箱里不缺弹药，但开箱声和枪声都很容易传远。', 'x'=>790, 'y'=>560),
        10 => array('name'=>'港区废弃车间', 'indoor'=>true, 'cover'=>1.0, 'description'=>'旧工作台上留下了枪管、磨刀材料和成套维修工具。后门通向灰市与港边。', 'x'=>690, 'y'=>535),
        11 => array('name'=>'岚江口生态再造区', 'indoor'=>false, 'cover'=>0.85, 'description'=>'芦苇和临时栈道交错。供巡护人员使用的储物箱还在，陌生的脚步很容易藏进水声里。', 'x'=>900, 'y'=>425),
        12 => array('name'=>'天青大桥花园步道', 'indoor'=>true, 'cover'=>1.15, 'description'=>'封闭步道挡住了风雨，却挡不住迎面的人。桥的另一端是东洲岛；撤退时必须记住这里的禁区顺序。', 'x'=>1020, 'y'=>340),
        13 => array('name'=>'东洲岛滨水区', 'indoor'=>false, 'cover'=>1.0, 'description'=>'滨水储物间里还放着救生用品。返回城区只有大桥这一条路，远离人群并不等于永远安全。', 'x'=>1170, 'y'=>340),
        14 => array('name'=>'西部盘山路', 'indoor'=>false, 'cover'=>0.85, 'description'=>'护栏外看得到整座复制城市。路边散落的工具足以防身，山路同时通向两处旧建筑。', 'x'=>260, 'y'=>320),
        15 => array('name'=>'冈山李府别墅', 'indoor'=>true, 'cover'=>1.0, 'description'=>'别墅的安保灯仍亮着。房间没有主人，门边的护甲内衬和短佩刀却像是刚被人放下。', 'x'=>140, 'y'=>375),
        16 => array('name'=>'冈山第二车间', 'indoor'=>true, 'cover'=>1.0, 'description'=>'地上的旧划痕与墙边崭新的装备箱格格不入。这是山区的车间，与港区那一处并非同地。', 'x'=>165, 'y'=>235),
        17 => array('name'=>'寂山书院', 'indoor'=>true, 'cover'=>0.85, 'description'=>'书院藏在更深的山里。茶水、绷带和轻兵器留在廊下，从这里回城需要经过李府或后山。', 'x'=>50, 'y'=>285),
        18 => array('name'=>'后山废弃工地', 'indoor'=>false, 'cover'=>0.85, 'description'=>'围挡后的机械已经停转。配重、切刀和警报器仍有用，穿过工地可以回到公园。', 'x'=>190, 'y'=>115),
    );
    $edges = array(array(1,2),array(1,3),array(1,4),array(1,7),array(1,14),array(2,8),array(2,9),array(3,5),array(3,7),array(4,5),array(4,14),array(4,18),array(5,6),array(5,7),array(6,11),array(6,12),array(7,8),array(8,10),array(9,10),array(9,11),array(9,12),array(10,11),array(11,12),array(12,13),array(14,15),array(14,16),array(15,17),array(16,18),array(17,18));
    foreach($maps as $id=>&$map) {
        $map['neighbors'] = array();
        $map['drops'] = array(
            array('item'=>'heal', 'stock'=>2), array('item'=>'stamina', 'stock'=>2),
            array('item'=>'ammo', 'stock'=>2), array('item'=>'slash', 'stock'=>1),
            array('item'=>'throw', 'stock'=>1), array('item'=>'armor', 'stock'=>1),
            array('item'=>'scrap', 'stock'=>2),
        );
    }
    unset($map);
    foreach($edges as $edge) {
        $maps[$edge[0]]['neighbors'][] = $edge[1];
        $maps[$edge[1]]['neighbors'][] = $edge[0];
    }
    $special = array(
        1=>array(array('gun_mid','配弹手枪',4),array('slash_mid','短柄砍刀',4),array('throw_mid','带属性投刀组',4),array('heal','小型急救包',4),array('stamina','瓶装水',4)),
        2=>array(array('meal','招牌盒饭',5),array('strong_heal','热食保温包',3),array('slash_mid','战术短刀',2),array('poison_test','查毒试纸',4),array('food_antidote','食品解毒剂',2)),
        3=>array(array('radio','对讲机',4),array('binoculars','便携望远镜',3),array('gun','安保手枪',2),array('satellite','卫星电话',2)),
        4=>array(array('slash','园艺刀',2),array('throw','配重投石',3),array('detector','简易探测仪',3),array('trap_alarm','报警绊线',3)),
        5=>array(array('gun_mid','安保制式枪',2),array('gun_part','封装枪械组件',3),array('armor_mid','轻型安保护衣',2),array('satellite','卫星电话',2)),
        6=>array(array('gun_mid','赛事配发手枪',2),array('slash_mid','短剑',2),array('throw_mid','投刀套装',2),array('armor_mid','普通护甲',2),array('strong_heal','应急药包',3)),
        7=>array(array('meal','便携食品包',4),array('repair','针线与维修包',3),array('armor_patch','护甲补片',3),array('whetstone','磨刀石',3),array('poison_test','查毒工具',3)),
        8=>array(array('throw_mid','飞刀',2),array('poison','有毒试剂',3),array('food_antidote','食品解毒剂',3),array('disarm_tool','拆除工具',3),array('trap_combo','复合绊索',2),array('satellite','卫星电话',2)),
        9=>array(array('ammo','封装弹药',8),array('gun_mid','货运护卫枪',2),array('throw','配重投钉',3),array('armor','工装护衣',2),array('gun_part','枪械组件',3)),
        10=>array(array('gun_part','备用枪管组件',4),array('repair','整备工具包',4),array('whetstone','磨刀石',4),array('armor_patch','护甲补片',3),array('radio','维修用对讲机',2)),
        11=>array(array('slash','砍藤刀',2),array('throw_mid','投矛',2),array('stamina','巡护饮水',4),array('trap_alarm','报警陷阱',3),array('disarm_tool','拆除工具',2)),
        12=>array(array('stamina','步道咖啡',3),array('meal','便携口粮',2),array('throw_mid','钢制投镖',2),array('binoculars','望远镜',2),array('radio','对讲机',2)),
        13=>array(array('slash','救生刀',2),array('gun','防水手枪',2),array('armor_mid','防水护衣',2),array('meal','鱼干与饮水',3),array('strong_heal','岸站急救包',2)),
        14=>array(array('throw','配重投石',3),array('slash','砍枝刀',2),array('throw_mid','投矛',2),array('whetstone','磨刀石',3),array('heal','绷带',3)),
        15=>array(array('gun_mid','安保手枪',2),array('slash_mid','短佩刀',2),array('armor_mid','护甲内衬',2),array('gem','宝石块',3),array('strong_heal','急救包',2)),
        16=>array(array('slash_mid','车间短剑',2),array('throw_mid','投刀',2),array('gun_part','枪械部件',3),array('whetstone','磨刀石',3),array('armor_patch','护甲补片',3)),
        17=>array(array('slash_mid','短剑',2),array('throw_mid','飞镖',3),array('whetstone','磨刀石',3),array('repair','缝补包',2),array('stamina','茶水',4),array('meal','干粮',2)),
        18=>array(array('slash','施工切刀',2),array('throw','投掷砖块',4),array('armor','安全护具',2),array('heal','绷带',3),array('trap_alarm','施工报警器',3)),
    );
    foreach($special as $id=>$drops) foreach($drops as $drop) $maps[$id]['drops'][] = array('item'=>$drop[0], 'name'=>$drop[1], 'stock'=>$drop[2]);

    $npcbase = array(
        'pass'=>'', 'gd'=>'n', 'icon'=>0, 'club'=>0, 'msp'=>200, 'sp'=>200, 'lvl'=>1,
        'money'=>0, 'inf'=>'', 'rage'=>0, 'pose'=>0, 'tactic'=>0, 'killnum'=>0, 'state'=>0,
        'teamID'=>'', 'teamPass'=>'', 'bid'=>0, 'horizon'=>0, 'skills'=>0, 'rp'=>0,
        'wp'=>0, 'wk'=>0, 'wc'=>0, 'wg'=>0, 'wd'=>0, 'wf'=>0, 'wepsk'=>'',
        'arb'=>'', 'arbk'=>'', 'arbe'=>0, 'arbs'=>0, 'arbsk'=>'',
        'arh'=>'', 'arhk'=>'', 'arhe'=>0, 'arhs'=>0, 'arhsk'=>'',
        'ara'=>'', 'arak'=>'', 'arae'=>0, 'aras'=>0, 'arask'=>'',
        'arf'=>'', 'arfk'=>'', 'arfe'=>0, 'arfs'=>0, 'arfsk'=>'',
        'art'=>'', 'artk'=>'', 'arte'=>0, 'arts'=>0, 'artsk'=>'',
    );
    for($i=0;$i<=6;$i++) foreach(array('itm'=>'','itmk'=>'','itme'=>0,'itms'=>0,'itmsk'=>'','itmpara'=>array()) as $field=>$value) $npcbase[$field.$i]=$value;
    $roles = array(
        'grunt'=>array(105,'游荡巡检体',180,80,80,'WK',30,'巡检切刀','杂音里只剩反复执行的巡逻口令。外壳破旧，行动迟缓。'),
        'elite'=>array(106,'隔离护卫',420,110,110,'WG',100,'护卫短枪','一台仍在执行隔离命令的旧式护卫。它没有回答身份询问。'),
        'hunter'=>array(107,'投刃猎手',600,120,120,'WC',100,'回收投刃','发射槽内装着追踪投刃。它的投掷攻击必中，单靠回避不足以保证安全。'),
        'poisoner'=>array(108,'毒雾技师',600,100,120,'WK',80,'封装切割器','背罐的毒雾会在首次反击时独立释放，普通攻击落空也不能阻止施毒。先准备解毒药。'),
        'engineer'=>array(109,'设伏工兵',600,110,120,'WG',90,'工兵自卫枪','地面有醒目的感应绊线。特殊陷阱触发后不能回避；可以提前侦测、拆除或绕行。'),
        'mirror'=>array(110,'优胜者镜像',600,120,120,'WG',180,'镜像制式枪','一段来自过往优胜者的行动记录。它使用专用装备，保留的天赋会改变战斗方式。'),
    );
    $npc_templates=array();
    foreach($roles as $role=>$spec) {
        $npc=array_merge($npcbase,array('type'=>$spec[0],'name'=>$spec[1],'mhp'=>$spec[2],'hp'=>$spec[2],'att'=>$spec[3],'def'=>$spec[4],'wepk'=>$spec[5],'wepe'=>$spec[6],'wep'=>$spec[7],'weps'=>$spec[5]==='WC'?99:1));
        $npc['weppara']=array('another'=>array('id'=>'npc_'.$role,'wear'=>0,'ammo'=>999,'tier'=>1,'effects'=>array()));
        $npc['clbpara']=array('another'=>array('npc_role'=>$role,'display_name'=>$spec[1],'description'=>$spec[8],'talents'=>array(),'portrait'=>'gamedata/ruleset/ANOTHER/img/'.$role.'.svg'));
        $npc_templates[$role]=$npc;
    }
    $npc_spawns=array();
    foreach(range(2,18) as $map) $npc_spawns[]=array('template'=>'grunt','pls'=>$map,'count'=>2);
    foreach(array(3,5,6,7,9,10,15,16) as $map) $npc_spawns[]=array('template'=>'elite','pls'=>$map,'count'=>1);
    $npc_spawns[]=array('template'=>'hunter','pls'=>12,'count'=>1);
    $npc_spawns[]=array('template'=>'poisoner','pls'=>11,'count'=>1);
    $npc_spawns[]=array('template'=>'engineer','pls'=>8,'count'=>1);
    $reinforcements=array(900=>array(),1800=>array());
    foreach(array(3,5,6,7,8,9,10,11) as $map) $reinforcements[900][]=array('template'=>'grunt','pls'=>$map,'count'=>1);
    foreach(array(2,5,7,8,9,10) as $map) $reinforcements[1800][]=array('template'=>'elite','pls'=>$map,'count'=>1);

    $config=array(
        'name'=>'AN:OTHER', 'maps'=>$maps, 'edges'=>$edges, 'start_map'=>1,
        'zones'=>array(600=>array(13,17,18),1200=>array(14,15,16),1800=>array(4,11,12),2400=>array(3,6,9),3000=>array(5,10),3300=>array(7,8),3600=>array(2),3900=>array(1)),
        'zone_warning'=>120, 'entry_close'=>600, 'deadline'=>3900,
        'npc_templates'=>$npc_templates, 'npc_spawns'=>$npc_spawns, 'npc_reinforcements'=>$reinforcements,
        'mirrors'=>array('spawn_at'=>1200,'count'=>3,'armor'=>'armor_mid','loadouts'=>array(
            'slash'=>array('item'=>'slash_mid','name'=>'镜像制式刃'),
            'gun'=>array('item'=>'gun_mid','name'=>'镜像制式枪'),
            'throw'=>array('item'=>'throw_mid','name'=>'镜像制式投刃'),
        )),
        'wild_traps'=>array(array('pls'=>4,'item'=>'trap_alarm'),array('pls'=>8,'item'=>'trap_hazard'),array('pls'=>11,'item'=>'trap_slow'),array('pls'=>14,'item'=>'trap_alarm'),array('pls'=>18,'item'=>'trap_combo')),
        'discovery'=>array('player'=>55,'npc'=>70,'item'=>70,'corpse'=>65,'trap'=>40,'balloon'=>60,'noise_lifetime'=>60,'trail_lifetime'=>90),
        'action'=>array('move_sp'=>15,'search_sp'=>10,'attack_sp'=>5,'cooldown_ms'=>900,'rest_hp_fraction_per_second'=>1/600,'rest_sp_fraction_per_second'=>1/300),
        'weather_schedule'=>array(0=>0,600=>3,1200=>2,1800=>4,2400=>0,3000=>8,3300=>2,3600=>0),
        'weather_effects'=>array(0=>array('name'=>'晴天','discovery'=>1.0),2=>array('name'=>'多云','discovery'=>1.0),3=>array('name'=>'小雨','discovery'=>0.9),4=>array('name'=>'暴雨','discovery'=>0.8),8=>array('name'=>'起雾','discovery'=>0.75)),
        'combat'=>array('growth_per_level'=>0.025,'shot_hit'=>90,'slash_hit'=>95,'slash_variance'=>array(0.95,1.05),'wear_per_hit'=>2,'earth_actions'=>3,'earth_cooldown'=>6,'flurry_chance'=>20,'poison_interval'=>5,'poison_duration'=>20,'poison_percent'=>5,'talent_priority'=>array('sure_hit_before_evade'=>true,'sky_before_earth'=>true)),
        'economy'=>array('start_money'=>300,'start_rep'=>0,'fortune_money'=>20,'rewards'=>array('grunt'=>array('money'=>40,'rep'=>5),'elite'=>array('money'=>120,'rep'=>25),'hunter'=>array('money'=>200,'rep'=>80),'poisoner'=>array('money'=>200,'rep'=>80),'engineer'=>array('money'=>200,'rep'=>80),'mirror'=>array('money'=>180,'rep'=>60),'player'=>array('money'=>120,'rep'=>30))),
        'story'=>array(
            'intro'=>array(
                '载入普通对局的画面突然停住。下一次眨眼，你已经站在一条陌生商业街上。街道尽头的湾城天际线一段段重复，像是没有拼好的倒影。',
                '红暮的声音断断续续地从尚未关闭的通讯窗口里传来：“这不是预定地图。外面的操作进不去……听着，如果结算规则还在，达成幸存，可能就能回到正常出口。”',
                '你还没来得及追问，信号就消失了。附近的自动售货终端却亮着，摊位上有钱币、医疗品和真实武器。更远处，某种金属步伐正在重复。',
                '一台没有署名的设备向你显示了 REP 余额。援助从何而来无从确认，至少这些东西能够使用。现在，先决定怎样活下去。'
            ),
            'survival'=>array('最后的敌对信号熄灭了。旧商业街的景物开始失去颜色，一道熟悉的结算窗口挤进画面。','“能收到吗？……出口有响应了。”这一次，红暮的声音没有被杂音截断。','你和队友穿过逐渐恢复的出口。身后仍有无人应答的终端亮着，记录下一次不知何时才会发生的请求。'),
            'escape'=>array('卫星电话发出了本不该属于它的确认音。整座湾城的复制景物停顿一瞬，随后出现了异常终止的提示。','你抓住那道短暂开启的出口。队伍的信号一起从地图中撤出，街道上的旧巡逻口令还在继续。','再次接通通讯时，外面的人也没有解释这条出口从何而来。至少，这一次你们回来了。'),
            'timeout'=>array('最后一块安全区域也从地图上消失了。街灯逐排熄灭，出口没有等到任何幸存信号。','这份异常对局记录以无人优胜封存。'),
            'death'=>array('画面失去响应。你留下的装备仍在那条街上，其他人的对局还没有结束。'),
        ),
    );
    return $config;
}
