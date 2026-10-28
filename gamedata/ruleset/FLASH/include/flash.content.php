<?php
if (!defined('IN_GAME')) exit('Access Denied');

function flash_cfg($key) { return $GLOBALS['flash_config'][$key]; }
function flash_html($s) { return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8'); }
function flash_say($s) { $GLOBALS['log'].=flash_html($s).'<br>'; }
function flash_story(&$p,$title,$text) {
    $p['clbpara']['flash']['scene']=array('title'=>$title,'text'=>$text);
    $GLOBALS['log'].='<span class="yellow">'.flash_html($title).'</span><br>'; flash_say($text);
}
function flash_revival_text($n) {
    $lines=array(
        1=>'你死了。……然后，你在无月之影睁开眼睛。报告册里多了一页：地点、时间，还有没来得及喊出的那一声。乔乔把笔递给你，手还在抖。窗外，一条街道消失了。',
        2=>'又一次。无月之影外多了一片空白，像被撕掉一角的照片。乔乔沿着地图折出一条新路：「这次走这里的说。」',
        3=>'这次醒来，消失的是两片街区。你在报告册上划掉旧路线，笔尖却戳穿了纸。代价变贵了。',
        4=>'又是两片。乔乔蹲在边缘看了很久：「那边像被橡皮擦过了的说……手不要伸出去的说。」',
        5=>'三片。远方的楼房向天空倾倒，却没有落地的声音。你第一次认真数起剩下的街道。',
        6=>'又是三片。乔乔给你看她标记的路线，纸上旧折痕比新墨水还密。「乔乔一直很会记路的说！」',
        7=>'四片。从此，每次醒来都会少四片。它不再加速，只是耐心地、一口一口吞掉剩下的世界。',
        8=>'乔乔数到这里，耳朵垂了下来。「乔乔以前也数到过这里的说。再往后……就不敢数了的说。」',
        9=>'四片。禁区边缘站着一个和你一模一样的人。你眨了眨眼，那个身影仍站在那里。不要过去。',
        10=>'四片。「下次……可能就没有下次了。」你不知道是谁说的。乔乔只把你的手握得更紧。',
        11=>'最后四片。还差一点，剩下的路几乎连成细线。乔乔替你抚平报告册：「最后一次，也要一起走的说。」',
        12=>'你死了。……你没能再睁开眼睛。幻境和你一起，爆发四散。',
    );
    return $lines[min(12,max(1,$n))];
}
function flash_report_text($key) {
    $texts=array(
        'first'=>'复活会把你送回无月之影。武器、防具、属性只能带回一类；证据和这本册子会留下。搬运工会自动记下找回委托，帮你追回失去的东西。地图不会变回完整的样子。',
        'chain'=>'青鬼倒下之前，赤鬼已经拔出了刀。先拦住赤鬼，便能让这条路续下去。目击的事已经记下；即使这一回反杀了她，也不必再死一次证明。',
        'drift'=>'相同的路标不保证通往相同的地方。听见第二声钟响以后，千万别跟着自己的脚步走。绕开那条反光的路。',
        'wrong'=>'倒下的人没有拔刀。杀死一个带路者，不会使出口更近。她留下的委托仍能完成，只是再也不会有人来接你的话。',
        'kind'=>'热茶是真的，门后的危险也是真的。好意并不认识你的每一次死亡。先看门缝里的影子，再决定是否跟进去。',
    );
    return $texts[$key]??'';
}
function flash_jobs() {
    return array(
        'patrol'=>array('title'=>'把路清出来','kind'=>'minion','need'=>3,'lvl'=>1,'rev'=>0,'text'=>'搬运工的包裹被拦在路上。击退三名杂兵。'),
        'holo'=>array('title'=>'不会熄灭的放映机','kind'=>'holo','need'=>1,'lvl'=>5,'rev'=>1,'text'=>'打碎一具全息幻象，带回投影结束的声音。'),
        'collect'=>array('title'=>'两个人的晚餐','kind'=>'collect','need'=>2,'lvl'=>1,'rev'=>0,'text'=>'交出两份命体补给；红烁会为你重新装好行囊。'),
        'explore'=>array('title'=>'没有收件人的信','kind'=>'explore','need'=>3,'lvl'=>1,'rev'=>0,'text'=>'探索三个批次，寻找信封上的地址。'),
        'escort'=>array('title'=>'陪她走一段','kind'=>'move','need'=>3,'lvl'=>3,'rev'=>1,'text'=>'带着仍在身边的乔乔移动三次。'),
        'feed'=>array('title'=>'大人的下午茶','kind'=>'feed','need'=>2,'lvl'=>5,'rev'=>1,'text'=>'乔乔说不饿，却一直看着你的背包。喂食两次。'),
        'track'=>array('title'=>'循着蓝色的脚印','kind'=>'track','need'=>2,'lvl'=>8,'rev'=>2,'text'=>'成功追踪两次躲藏的身影。粼粼水留下了练习的足迹。'),
        'disarm'=>array('title'=>'给后来者留条路','kind'=>'disarm','need'=>0,'lvl'=>1,'rev'=>1,'text'=>'清除场上全部阔剑地雷。探雷车清除与踩掉都计入进展；地雷不会随回溯补回，全部清除后自动送达补给报酬。'),
    );
}
function flash_boss_specs() {
    return array(
        1=>array('source'=>array('add',4,0),'name'=>'逆响的黑影','rule'=>'recoil','hp'=>12000,'att'=>1800,'def'=>1600,'skill'=>1000,'wepe'=>1200),
        2=>array('source'=>array('add',2,1),'name'=>'镜中的白昼','rule'=>'weak','hp'=>6500),
        3=>array('source'=>array('add',5,0),'name'=>'雨幕的另一端','rule'=>'weak','hp'=>9000),
        4=>array('source'=>array('add',7,0),'name'=>'电击使 御坂 美琴','rule'=>'hard'),
        5=>array('source'=>array('add',9,0),'name'=>'感觉像蓝凝的东西','rule'=>'hard'),
    );
}
function flash_friends() {
    return array('porter'=>array(90,0),'nurse'=>array(93,17),'cook'=>array(93,16),
        'blue'=>array(93,0),'red'=>array(93,1),'tracker'=>array(93,5),'eci'=>array(93,20),'guide'=>array(93,19));
}
function flash_ending_copy($p) {
    $s=$p['clbpara']['flash'];
    if ($s['result']==='lost') return array('FL3 · 爆发四散',($s['reason']==='timeout'?'最后一秒没有声音。你手里的纸先碎了，接着是门、街道和天空。':'你死了。……你没能再睁开眼睛。').'幻境和你一起，爆发四散。');
    $text='门在身后合拢。便利店卷起闸门，早点铺飘着蒸汽，骑单车的学生按铃从你身边掠过。没有人看你。口袋里的报告册翻到最后一页：「新的一天开始了。」';
    if ($s['result']==='true') {
        $text.='今天似乎是漫展日。人流迎面涌来，一个装扮成猫娘的身影与你擦肩而过。她比乔乔高挑，兜帽压得很低，手中道具兵器缠着藤蔓。她是谁？这个念头只闪了一瞬。';
        $text.=$s['deaths']>=flash_cfg('boundary_revives')?'街角的售货机少了一个角，对面的招牌缺了半行字。人群还在笑。一个平凡的瞬间，周围开始崩坏了。':'你没有再想下去。然后你醒了。无月之影。你什么都不记得，只觉得这里很熟悉。「醒了的话，就是同伴了的说！」眼前的少女眼睛红红的，却硬要笑。';
    } elseif (empty($s['jojo_missing'])) $text.='你回头，少女停在门缝的光里。她挥着手，嘴唇无声地动了一下。你猜她说的是明天见。';
    return array($s['result']==='true'?'FL2 · 擦肩而过':'FL1 · 新的一天',$text);
}
function flash_ending_extras($p) {
    $s=$p['clbpara']['flash']; $out=array();
    if (flash_report_count($p)<=1) $out['零报告']='你赢了。身后传来孤零零的掌声，一下、两下、三下，像在奖励一张白卷。';
    if (count($s['friends_killed'])>=count(flash_friends())) $out['屠夫']='人群从你身边流过，每个人的脸都光滑平整。没有一张脸愿意看你。';
    if ($s['fed']>=12) $out['饲主']='一个身影停了一下，手里捏着一包和乔乔最爱的一模一样的零食。然后她没入人流。';
    if ($s['death_places'] && max($s['death_places'])>=3) $out['缠身者']='你仍会在那个路口停下。那里什么都没有，你却总觉得下一辆拐弯的车会带走什么人。';
    if ($s['finished']-$GLOBALS['gamevars']['flash']['start']>=3000) $out['守时']='崩坏落在你的腕表上。秒针一格一格地脱落，像在倒数已经用完的时间。';
    if ($s['gate_returns']>=flash_cfg('gate_returns')) $out['折返癖']='这一次，你终于没有回头。门缝里，有人轻轻叹了口气。';
    if ($s['jojo_missing']) $out['她不在']='没有扑过来拽衣角的人，也没有一声「的说」落在耳边。你站在街头很久。她真的不在了。';
    return $out;
}
