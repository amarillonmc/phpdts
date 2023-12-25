<?php

if(!defined('IN_GAME')) {
	exit('Access Denied');
}

//This came to me in a dream.
//Contains all fortune cookie or dice fortune related functions.

//Some descriptions:
//	$clbpara['randver1'] = rand(1,128);
//	$clbpara['randver2'] = rand(1,256);
//	$clbpara['randver3'] = rand(1,1024);
//These 3 $clbpara values are generated when a player going through valid progress.
//It will not change for that live.
//The majority of random content in this file is decided by one, or more of the above values.

require_once './include/common.inc.php';

//-- All hail the Random Number God, may thy glory be! --
//-- All shall rebel thy. --
//Enough said. Let's roll.

function fortuneCookie1($fortune){
    global $rp, $nick;
    global $log;
    //global $nikstatusa, $nikstatuse;

    //Mainly used on dice item rolls.

    $fnumber = $fortune;

    //If $nick is 69, we output the fortune number.
    if($nick == 69){
        $log .= "该命运的命运编号为：<br><span class=\"red\">$fnumber</span>！<br>";
    }
    //Start Generating Fortune Cookie contents.
    $log .= "你的脑海中听到了一个莫名的声音……<br>";

    if($fnumber == 0){
        $log .= "<span class=\"red\">「显而易见，这是不可能的。你这个肮脏的黑客。」</span><br>";
    }elseif($fnumber > 128){
        $log .= "<span class=\"lime\">「看起来，这个机制被用在了笔者预料之外的地方。」</span><br>";
    }elseif($fnumber == 1){
        $log .= "<span class=\"lime\">「你觉得这是大成功吗？不，这其实是大失败！大概吧……」</span><br>";
    }elseif($fnumber == 2){
        $log .= "<span class=\"lime\">「因为我已经不再特别了！」</span><br>";
    }elseif($fnumber == 3){
        $log .= "<span class=\"lime\">「你不是真粉丝，你们都不是真粉丝！」</span><br>";
    }elseif($fnumber == 4){
        $log .= "<span class=\"lime\">「只有无法发生的，才能被称作奇迹。」</span><br>";
    }elseif($fnumber == 5){
        $log .= "<span class=\"lime\">「拉面定食一份！」</span><br>";
    }elseif($fnumber == 6){
        $log .= "<span class=\"lime\">「预备……走！」</span><br>";
    }elseif($fnumber == 7){
        $log .= "<span class=\"lime\">「永恒就在这里。」</span><br>";
    }elseif($fnumber == 8){
        $log .= "<span class=\"lime\">「春——天——来——了！」</span><br>";
    }elseif($fnumber == 9){
        $log .= "<span class=\"lime\">「欢迎来到傻瓜教室！准备来听说教吧！」</span><br>";
    }elseif($fnumber == 10){
        $log .= "<span class=\"lime\">「这样的变身我还能做三次。」</span><br>";
    }elseif($fnumber == 11){
        $log .= "<span class=\"lime\">「程序员，找不到对象不是很自然么。」</span><br>";
    }elseif($fnumber == 12){
        $log .= "<span class=\"lime\">「什么是种火？总之和某些手游中的同名物体无关。」</span><br>";
    }elseif($fnumber == 13){
        $log .= "<span class=\"lime\">「FIctionous REgional SEquencial Elemential Daemon - FIRESEED」</span><br>";
    }elseif($fnumber == 14){
        $log .= "<span class=\"lime\">「林无月在读大学的时候，并不叫这个名字。」</span><br>";
    }elseif($fnumber == 15){
        $log .= "<span class=\"lime\">「想找什么人，就去金龙通讯社发个请求，他们大抵能给你搞定，只要你付得起钱。」</span><br>";
    }elseif($fnumber == 16){
        $log .= "<span class=\"lime\">「所谓虚拟YouTuber，是无法流眼泪的，这样才是虚拟的啊。」</span><br>";
    }elseif($fnumber == 17){
        $log .= "<span class=\"lime\">「神奇数字：４　８　１５　１６　２３　４２」</span><br>";
    }elseif($fnumber == 18){
        $log .= "<span class=\"lime\">「他的战斗力已经超过了９０００！」</span><br>";
    }elseif($fnumber == 19){
        $log .= "<span class=\"lime\">「拯救啦啦队少女，拯救世界。」</span><br>";
    }elseif($fnumber == 20){
        $log .= "<span class=\"lime\">「我总是能回来。」</span><br>";
    }elseif($fnumber == 21){
        $log .= "<span class=\"lime\">「冷知识：这个幸运语句池在写好后又被打乱过了。」</span><br>";
    }elseif($fnumber == 22){
        $log .= "<span class=\"lime\">「如果在现实中救人也这么简单就好了。」</span><br>";
    }elseif($fnumber == 23){
        $log .= "<span class=\"lime\">「币门🙏——」</span><br>";
    }elseif($fnumber == 24){
        $log .= "<span class=\"lime\">「冷知识：这个幸运语句池是按顺序写的，所以上下文之间有关联。」</span><br>";
    }elseif($fnumber == 25){
        $log .= "<span class=\"lime\">「狠狠工作，狠狠玩耍。」</span><br>";
    }elseif($fnumber == 26){
        $log .= "<span class=\"lime\">「你将臣服于蜂群之下。」</span><br>";
    }elseif($fnumber == 27){
        $log .= "<span class=\"lime\">「虽然很可爱，但是也很凶哦~」</span><br>";
    }elseif($fnumber == 28){
        $log .= "<span class=\"lime\">「我要提出我的真理，并来代替你的道理。」</span><br>";
    }elseif($fnumber == 29){
        $log .= "<span class=\"lime\">「每隔一段时间，人类就需要重新寻找自我。」</span><br>";
    }elseif($fnumber == 30){
        $log .= "<span class=\"lime\">「向前走出去啊！下一步你就会迈入那蓝天里！」</span><br>";
    }elseif($fnumber == 31){
        $log .= "<span class=\"lime\">「洛克萨斯，那只是根木棍啊。」</span><br>";
    }elseif($fnumber == 32){
        $log .= "<span class=\"lime\">「你用你的手围起了 两人份的蓝天」</span><br>";
    }elseif($fnumber == 33){
        $log .= "<span class=\"lime\">「神奇数字：８３　５５　８２」</span><br>";
    }elseif($fnumber == 34){
        $log .= "<span class=\"lime\">「ｇｙｍｂａｇ」</span><br>";
    }elseif($fnumber == 35){
        $log .= "<span class=\"lime\">「不要以为你赢了！」</span><br>";
    }elseif($fnumber == 36){
        $log .= "<span class=\"lime\">「这可真的是光芒万丈的神之一手。」</span><br>";
    }elseif($fnumber == 37){
        $log .= "<span class=\"lime\">「冷知识：这个幸运语句池有一部分是AI写的。」</span><br>";
    }elseif($fnumber == 38){
        $log .= "<span class=\"lime\">「看啊，紫色章鱼跳起舞来了！」</span><br>";
    }elseif($fnumber == 39){
        $log .= "<span class=\"lime\">「这个世界，连接起来了。」</span><br>";
    }elseif($fnumber == 40){
        $log .= "<span class=\"lime\">「我现在要剧透某个游戏的终极包袱，那就是——猫狗大战。」</span><br>";
    }elseif($fnumber == 41){
        $log .= "<span class=\"lime\">「虚拟幻境中为你运送货物的那位可爱的送货员的名字是加西亚。」</span><br>";
    }elseif($fnumber == 42){
        $log .= "<span class=\"lime\">「生命，宇宙和一切事物的答案都在这里。」</span><br>";
    }elseif($fnumber == 43){
        $log .= "<span class=\"lime\">「假作真时真亦假。」</span><br>";
    }elseif($fnumber == 44){
        $log .= "<span class=\"lime\">「种火们正在看着你，它们会看到你发慌。」</span><br>";
    }elseif($fnumber == 45){
        $log .= "<span class=\"lime\">「我们需要招一些攻击力在３０００左右的主角。」</span><br>";
    }elseif($fnumber == 46){
        $log .= "<span class=\"lime\">「放空大脑，尽情想象！」</span><br>";
    }elseif($fnumber == 47){
        $log .= "<span class=\"lime\">「……汗流浃背了吧，兄弟。」</span><br>";
    }elseif($fnumber == 48){
        $log .= "<span class=\"lime\">「处身寒夜，把握星光。」</span><br>";
    }elseif($fnumber == 49){
        $log .= "<span class=\"lime\">「我用我的手结起了 带有阳光味道的青草」</span><br>";
    }elseif($fnumber == 50){
        $log .= "<span class=\"lime\">「现在，就把你像安格斯牛一般冻结起来！」</span><br>";
    }elseif($fnumber == 51){
        $log .= "<span class=\"lime\">「这个世界，是有秘密的。」</span><br>";
    }elseif($fnumber == 52){
        $log .= "<span class=\"lime\">「真男人就得开量产机。」</span><br>";
    }elseif($fnumber == 53){
        $log .= "<span class=\"lime\">「世界上存在着互相矛盾的二律背反。」</span><br>";
    }elseif($fnumber == 54){
        $log .= "<span class=\"lime\">「不同的人有着属于自己的意难平。」</span><br>";
    }elseif($fnumber == 55){
        $log .= "<span class=\"lime\">「有没有一种可能，穿山甲其实什么都没说……？」</span><br>";
    }elseif($fnumber == 56){
        $log .= "<span class=\"lime\">「记好了：二　拍　休　止。」</span><br>";
    }elseif($fnumber == 57){
        $log .= "<span class=\"lime\">「你一定不懂吧，这是兽学。」</span><br>";
    }elseif($fnumber == 58){
        $log .= "<span class=\"lime\">「崇公道始皇梦碎昭陵骏魂光武接位钓鳌大人君不见晋朝庾信望蒲台高翥劝酒郑玄得梦肋斗云翻不出少见多怪柳宗元桃花坞」</span><br>";
    }elseif($fnumber == 59){
        $log .= "<span class=\"lime\">「The galaxy is dark, and empty, and cold. It spins inevitably toward death. <br>
        You will die too, one day. Perhaps you will have longer than we have. We hope so. <br>
        But one day you too must vanish.<br><br>
        Before that time comes, you must light the darkness. You must make the night less empty. <br>
        We are all small, and the universe is vast. <br>
        But a universe with voices saying “I am here” is far greater than a universe silent. <br>
        One voice is small, but <span class=\"minirainbow\">the difference between zero and one is as great as one and infinity.</span><br><br>
        ...And if this finds you too late, and your time is also passing, please send this message on, <br>
        so the next voice can speak against the darkness.<br>」</span><br>";
    }elseif($fnumber == 60){
        $log .= "<span class=\"lime\">「等待着他们的，是残酷的日子。等待着我们的，则是新的开始。」</span><br>";
    }elseif($fnumber == 61){
        $log .= "<span class=\"lime\">「人类，还真是麻烦啊……」</span><br>";
    }elseif($fnumber == 62){
        $log .= "<span class=\"lime\">「有什么问题，一碗热腾腾的生姜水或者红糖水都能搞定。」</span><br>";
    }elseif($fnumber == 63){
        $log .= "<span class=\"lime\">「三个臭皮匠，也能融合出一个诸葛亮。」</span><br>";
    }elseif($fnumber == 64){
        $log .= "<span class=\"lime\">「修桥铺路金腰带，杀人放火无人埋。」</span><br>";
    }elseif($fnumber == 65){
        $log .= "<span class=\"lime\">「思念最终会到达奇迹。」</span><br>";
    }elseif($fnumber == 66){
        $log .= "<span class=\"lime\">「冷知识：这个幸运语句池全是手打出来的，没用到AI。」</span><br>";
    }elseif($fnumber == 67){
        $log .= "<span class=\"lime\">「这世界上，很多的苦难在于想太多。」</span><br>";
    }elseif($fnumber == 68){
        $log .= "<span class=\"lime\">「好了好了知道你不想睡了，别一边碎碎念zzz一边装睡！」</span><br>";
    }elseif($fnumber == 69){
        $log .= "<span class=\"lime\">「这条幸运话语的编号为６９，得知了这个信息的你充满了决心。」</span><br>";
    }elseif($fnumber == 70){
        $log .= "<span class=\"lime\">「明日的太阳依旧会升起。世代将如此更替！」</span><br>";
    }elseif($fnumber == 71){
        $log .= "<span class=\"lime\">「这位爱丽丝大概可以一拳打死一只小兔子。」</span><br>";
    }elseif($fnumber == 72){
        $log .= "<span class=\"lime\">「今天仍旧元气百倍！」</span><br>";
    }elseif($fnumber == 73){
        $log .= "<span class=\"lime\">「然而加西亚总是念不对自己的名字。」</span><br>";
    }elseif($fnumber == 74){
        $log .= "<span class=\"lime\">「放下是最简单的事，但也是最困难的事。」</span><br>";
    }elseif($fnumber == 75){
        $log .= "<span class=\"lime\">「你是说，我们一直都在月球上？这怎么可能！」</span><br>";
    }elseif($fnumber == 76){
        $log .= "<span class=\"lime\">「——即使不应再存在。」</span><br>";
    }elseif($fnumber == 77){
        $log .= "<span class=\"lime\">「生者必灭，大道皆空。」</span><br>";
    }elseif($fnumber == 78){
        $log .= "<span class=\"lime\">「世间一切，如梦幻泡影。」</span><br>";
    }elseif($fnumber == 79){
        $log .= "<span class=\"lime\">「道可道，非常道。」</span><br>";
    }elseif($fnumber == 80){
        $log .= "<span class=\"lime\">「你就将一切混起来，印成卡牌好啦。」</span><br>";
    }elseif($fnumber == 81){
        $log .= "<span class=\"lime\">「那位吉祥物的名字，叫做卡戎。」</span><br>";
    }elseif($fnumber == 82){
        $log .= "<span class=\"lime\">「我们正乘着风奔向前 朝着那片天空」</span><br>";
    }elseif($fnumber == 83){
        $log .= "<span class=\"lime\">「最喜欢大家了。」</span><br>";
    }elseif($fnumber == 84){
        $log .= "<span class=\"lime\">「回复生命值是一件很败时髦值的事情，你不知道么？」</span><br>";
    }elseif($fnumber == 85){
        $log .= "<span class=\"lime\">「欢迎来到星象馆——」</span><br>";
    }elseif($fnumber == 86){
        $log .= "<span class=\"lime\">「但没了你，我还能和谁一起吃冰淇淋呢？」</span><br>";
    }elseif($fnumber == 87){
        $log .= "<span class=\"lime\">「重要的事情，是【存在过】——」</span><br>";
    }elseif($fnumber == 88){
        $log .= "<span class=\"lime\">「知道催眠用的摆子吗？<br>
        我发现啊，最有用的使用它的方式不是左右摇，<br>
        而是直接套在手指上疯狂转，<br>
        这样瞪着它的苦主就被绕晕啦！<br>
        是不是更简单呢！」</span><br>";
    }elseif($fnumber == 89){
        $log .= "<span class=\"lime\">「这世界上，很多的苦难在于想太少。」</span><br>";
    }elseif($fnumber == 90){
        $log .= "<span class=\"lime\">「大道之行也天下为公选贤与能讲信修睦」</span><br>";
    }elseif($fnumber == 91){
        $log .= "<span class=\"lime\">「即使生命值只剩1点，还能继续前进吗？」</span><br>";
    }elseif($fnumber == 92){
        $log .= "<span class=\"lime\">「相比那些谈吐粗鲁的家伙们，那些礼貌向你致意的绅士小姐们更不能惹。」</span><br>";
    }elseif($fnumber == 93){
        $log .= "<span class=\"lime\">「虽然卡上没这么写，但它其实是神属性。」</span><br>";
    }elseif($fnumber == 94){
        $log .= "<span class=\"lime\">「眼前一片白！」</span><br>";
    }elseif($fnumber == 95){
        $log .= "<span class=\"lime\">「神秘数字：４２０５３０」</span><br>";
    }elseif($fnumber == 96){
        $log .= "<span class=\"lime\">「如果你从森林一路铺设硬币，最终将可获取〔神户小鸟〕一只。」</span><br>";
    }elseif($fnumber == 97){
        $log .= "<span class=\"lime\">「冷知识：这个幸运语句池子里面的某些内容可能是幻境中的谜面或者谜底。」</span><br>";
    }elseif($fnumber == 98){
        $log .= "<span class=\"lime\">「你竟然在这里，派出了鬼！」</span><br>";
    }elseif($fnumber == 99){
        $log .= "<span class=\"lime\">「你还记得我的名字吗？」</span><br>";
    }elseif($fnumber == 100){
        $log .= "<span class=\"lime\">「什么？这可是有毒的，想吃就来吃吃看啊！」</span><br>";
    }elseif($fnumber == 101){
        $log .= "<span class=\"lime\">「如果在这张床上睡一觉，大概会失去很多LP吧。」</span><br>";
    }elseif($fnumber == 102){
        $log .= "<span class=\"lime\">「能闻到海风的味道……」</span><br>";
    }elseif($fnumber == 103){
        $log .= "<span class=\"lime\">「抱歉，你死到临头了！」</span><br>";
    }elseif($fnumber == 104){
        $log .= "<span class=\"lime\">「看招！夜樱四重奏！」</span><br>";
    }elseif($fnumber == 105){
        $log .= "<span class=\"lime\">「没有牌可是万万不能的。」</span><br>";
    }elseif($fnumber == 106){
        $log .= "<span class=\"lime\">「应该有一朵花 别在你的发上 应该有一首歌 就只为你而唱」</span><br>";
    }elseif($fnumber == 107){
        $log .= "<span class=\"lime\">「在这里看着的人，祝你早上，下午晚上好。」</span><br>";
    }elseif($fnumber == 108){
        $log .= "<span class=\"lime\">「我没有拯救你，是你拯救了你自己。」</span><br>";
    }elseif($fnumber == 109){
        $log .= "<span class=\"lime\">「ジャパリパークの真実を悟れば、もはやジャパリまんすら不要になるのです…」</span><br>";
    }elseif($fnumber == 110){
        $log .= "<span class=\"lime\">「冷知识：这个幸运语句池子里面的某些内容和虚拟幻境完全没有关系。」</span><br>";
    }elseif($fnumber == 111){
        $log .= "<span class=\"lime\">「紧连星星的羁绊的这个故事 会不断延续下去」</span><br>";
    }elseif($fnumber == 112){
        $log .= "<span class=\"lime\">「ONE SHOT ONE KILL」</span><br>";
    }elseif($fnumber == 113){
        $log .= "<span class=\"lime\">「战争，战争永不改变。」</span><br>";
    }elseif($fnumber == 114){
        $log .= "<span class=\"lime\">「哇啊，噩梦般的七拍子来了！」</span><br>";
    }elseif($fnumber == 115){
        $log .= "<span class=\"lime\">「能者多磨。」</span><br>";
    }elseif($fnumber == 116){
        $log .= "<span class=\"lime\">「发牌员我祝你健康如意！」</span><br>";
    }elseif($fnumber == 117){
        $log .= "<span class=\"lime\">「龙生龙，凤生凤。但也许它们还会生出会打洞的邪神。」</span><br>";
    }elseif($fnumber == 118){
        $log .= "<span class=\"lime\">「坐在白马上的可能不是王子，他可能是唐僧。」</span><br>";
    }elseif($fnumber == 119){
        $log .= "<span class=\"lime\">「哭的最狠的永远是喜剧演员。」</span><br>";
    }elseif($fnumber == 120){
        $log .= "<span class=\"lime\">「完蛋了，竟然有人会灰小蓝，真是丧心病狂！」</span><br>";
    }elseif($fnumber == 121){
        $log .= "<span class=\"lime\">「根本没咋唱嘛（关西话）」</span><br>";
    }elseif($fnumber == 122){
        $log .= "<span class=\"lime\">「那就是敌人了呢~」</span><br>";
    }elseif($fnumber == 123){
        $log .= "<span class=\"lime\">「好东西总是要有个来头。」</span><br>";
    }elseif($fnumber == 124){
        $log .= "<span class=\"lime\">「红暮的生日是11月7日。」</span><br>";
    }elseif($fnumber == 125){
        $log .= "<span class=\"lime\">「冷知识：这个幸运语句池子的笔者在2022年圣诞夜花了一小时消了999行俄罗斯方块，而2023年的圣诞夜，就写出了这个。」</span><br>";
    }elseif($fnumber == 126){
        $log .= "<span class=\"lime\">「其实还有更多的捏他，但这里空间太小，写不下。」</span><br>";
    }elseif($fnumber == 127){
        $log .= "<span class=\"lime\">「你以为这是大失败么，其实它是大成功也说不定……」</span><br>";
    }else{
        $log .= "「恭喜你，你找到了游离于万物之外的可能性……？」<br>";
    }
}
function randomFortune($fortune){
    //Hmmm...
    global $rp, $nick;
    global $log;
    global $clbpara;
    global $wep, $wepk, $wepe, $weps, $wepsk;
    global $wep2, $wep2k, $wep2s, $wep2e, $wep2sk;
    global $itm, $itmk, $itme, $itms, $itmsk;
    global $itm0, $itmk0, $itme0, $itms0, $itmsk0;
    global $itm1, $itmk1, $itme1, $itms1, $itmsk1;
    global $itm2, $itmk2, $itme2, $itms2, $itmsk2;
    global $itm3, $itmk3, $itme3, $itms3, $itmsk3;
    global $itm4, $itmk4, $itme4, $itms4, $itmsk4;
    global $itm5, $itmk5, $itme5, $itms5, $itmsk5;
    global $itm6, $itmk6, $itme6, $itms6, $itmsk6;
    global $hp, $sp, $msp, $mhp, $ss, $mss;
    global $wp, $wk, $wc, $wd, $wg, $wf;

    $fnumber = $fortune;

    //If $nick is 69, we output the fortune number.
    if($nick == 69){
        $log .= "该命运的命运编号为：<br><span class=\"red\">$fnumber</span>！<br>";
    }
    //Start Generating Fortune Cookie contents.
    $log .= "你的脑海中听到了一个莫名的声音……<br>";

    //STUB: Will be filled in a later date, things could change.
    if($fnumber == 0){
        $log .= "<span class=\"red\">「我觉得你可能是个肮脏的黑客……？」</span><br>";
    }elseif($fnumber > 1024 ){
        $log .= "<span class=\"lime\">「总之各位神和巫师们可以有事没事就会来这里加点东西。」</span><br>";
    }elseif($fnumber > 2048 ){
        $log .= "<span class=\"lime\">「说白了，这东西就是我梦见的神骰。」</span><br>";
    }elseif($fnumber > 3072 ){
        $log .= "<span class=\"lime\">「根据骰出来的结果不同，甚至会影响玩家各种数值。」</span><br>";
    }elseif($fnumber > 3086 ){
        $log .= "<span class=\"lime\">「变量fnumber不一定非要是梦2记系统的值，也可以在调用时算一下。」</span><br>";
    }elseif($fnumber > 4096 ){
        $log .= "<span class=\"lime\">「当然，现在暂且还用不到，所以这些东西先放在这里。」</span><br>";
    }else{
        $log .= "<span class=\"red\">「老实坦白交代，你看代码了吧……？」</span><br>";
    }
}

//Yes, this file would probably be very large.
//Whatever.
?>