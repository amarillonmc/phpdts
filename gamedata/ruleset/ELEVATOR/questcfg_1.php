<?php
if (!defined('IN_GAME')) exit('Access Denied');
$questcfg_global=array('floor_board'=>true,'max_active'=>12);
$questcfg=array(
    'lost'=>array('pool'=>1,'title'=>'失物招领','kind'=>'submit','need'=>2,'match'=>array('names'=>'学生卡|报纸|头版'),'desc'=>'把学生卡或报纸交给巡夜保安。'),
    'snack'=>array('pool'=>1,'title'=>'忙里偷闲','kind'=>'use','need'=>2,'match'=>array('names'=>'猫粮|饮料'),'desc'=>'实际使用猫粮或饮料，让这一层恢复日常。'),
    'purify'=>array('pool'=>2,'title'=>'留下完整的回忆','kind'=>'purify','need'=>1,'npc'=>'烧焦的回忆体','item'=>'purify','hp_threshold'=>20,'conflict_group'=>'ruins','desc'=>'将回忆体削到20%生命以下，再使用净化匣；杀死目标会失败。'),
    'recycle'=>array('pool'=>2,'title'=>'把废墟带走','kind'=>'submit','need'=>2,'match'=>array('names'=>'电子|零件|电路|螺栓|数据芯'),'conflict_group'=>'ruins','desc'=>'提交电子垃圾或零件；回忆体将失去修复的来源。'),
    'comfort'=>array('pool'=>3,'title'=>'请把这句话听完','kind'=>'comfort','need'=>1,'npc'=>'无月的残影','item'=>'comfort','desc'=>'将夹页花束交给残影，安抚未说完的记忆。'),
    'books'=>array('pool'=>3,'title'=>'M.D. 的借阅单','kind'=>'submit','need'=>1,'match'=>array('names'=>'M.D.|旧书|教科书|笔记'),'desc'=>'寻找并提交与记忆有关的书籍。'),
    'escort'=>array('pool'=>4,'title'=>'带拾荒者回到灯下','kind'=>'escort','need'=>2,'npc'=>'等待归途的拾荒者','conflict_group'=>'waste','desc'=>'与拾荒者会合，保护其存活至电梯再停靠两次。'),
    'soldiers'=>array('pool'=>4,'title'=>'缴获旧军旗','kind'=>'kill','need'=>2,'kill_count'=>array('type'=>array(96,97),'pool'=>4),'conflict_group'=>'waste','desc'=>'击败旧红杀残兵；存活的拾荒者会因枪声逃离。'),
    'feed'=>array('pool'=>5,'title'=>'猫的补给线','kind'=>'use','need'=>2,'match'=>array('names'=>'猫粮|饮料'),'desc'=>'使用猫粮或饮料；给猫群喂食也算一次。'),
    'cipher'=>array('pool'=>5,'title'=>'广播室最后一句','kind'=>'puzzle','need'=>1,'word'=>'life game','desc'=>'将 LIFE GAME 译为种火语。手册与字典提供字母对应。'),
    'survey'=>array('pool'=>6,'title'=>'完整的白色地图','kind'=>'explore','need'=>2,'conflict_group'=>'white','desc'=>'接取后走访本层不同地点；不要打碎这里的回声。'),
    'break'=>array('pool'=>6,'title'=>'打碎白色回声','kind'=>'kill','need'=>2,'kill_count'=>array('type'=>array(96,97),'pool'=>6),'conflict_group'=>'white','desc'=>'击破回声体，使白色地图失去完整性。'),
    'cheer'=>array('pool'=>7,'title'=>'为零位观众歌唱','kind'=>'cheer','need'=>3,'npc'=>'种火V直播间的舞台','desc'=>'完成三轮应援交锋，或在战斗中击破舞台的回声。'),
    'collect'=>array('pool'=>7,'title'=>'还在亮着的应援牌','kind'=>'collect','need'=>3,'npc'=>'捧着糖果的残响','desc'=>'寻找残响收集三份灯纸；每次对话后需探索一次才能再次领取。'),
    'errors'=>array('pool'=>8,'title'=>'清除钻探错误','kind'=>'kill','need'=>2,'kill_count'=>array('type'=>array(96,97),'pool'=>8),'conflict_group'=>'mine','desc'=>'击败数据聚合体、错误NPC或半成品，清理通道。'),
    'prospector'=>array('pool'=>8,'title'=>'把勘探员带回去','kind'=>'escort','need'=>2,'npc'=>'迷途的勘探员','conflict_group'=>'mine','desc'=>'会合后保护勘探员，直到电梯再停靠两次。'),
    'ore'=>array('pool'=>8,'title'=>'最后一袋样本','kind'=>'submit','need'=>2,'match'=>array('names'=>'矿|零件|碎片|拓片'),'desc'=>'交付矿石、零件或钻探碎片。')
);
foreach ($questcfg as &$elquest) $elquest['floor_board']=true;
unset($elquest);
