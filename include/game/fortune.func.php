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
    global $att, $def, $money, $rage;

    $fnumber = $fortune;

    //If $nick is 69, we output the fortune number.
    if($nick == 69){
        $log .= "该命运的命运编号为：<br><span class=\"red\">$fnumber</span>！<br>";
    }
    //Start Generating Fortune Cookie contents.
    $log .= "你的脑海中听到了一个莫名的声音……<br>";

    //Start Generating Random Fortune contents.
    if($fnumber == 0){
        $log .= "<span class=\"red\">「我觉得你可能是个肮脏的黑客……？」</span><br>";
    }elseif($fnumber == 1){
        $log .= "<span class=\"lime\">「道生一，一生二，二生三，三生万物——但在这里，一通常只意味着保底伤害。」</span><br>";
    }elseif($fnumber == 2){
        $log .= "<span class=\"lime\">「如果这一局到最后没有任何人幸存，那么奖池里所有的切糕都会被冴冴一个人吃掉哦。」</span><br>";
    }elseif($fnumber == 3){
        $log .= "<span class=\"lime\">「在一个连地图坐标都只有一个整数变量的网页游戏里，就不要妄想什么扇形AOE和直线冲锋了。」</span><br>";
    }elseif($fnumber == 4){
        $log .= "<span class=\"lime\">「『摇滚乐才配得上炙热的战场。要是觉得吵的话，只能说明你的热血还不够沸腾。』——红暮」</span><br>";
    }elseif($fnumber == 5){
        $log .= "<span class=\"lime\">「把电风扇绑在直升机的螺旋桨上，停电的时候直升机能不能给整个小区扇风？」</span><br>";
    }elseif($fnumber == 6){
        $log .= "<span class=\"lime\">「连锁2，发动增殖的G！请问灰流丽通过吗？」</span><br>";
    }elseif($fnumber == 7){
        $log .= "<span class=\"lime\">「叮叮叮！幸运数字７！虽然没有老虎机给你吐硬币，但你的心情莫名变好了一点。」</span><br>";
        $hp = min($mhp, $hp + 7);
        $sp = min($msp, $sp + 7);
    }elseif($fnumber == 8){
        $log .= "<span class=\"lime\">「被坑杀之后硬生生拖了八禁？虽然行为极其恶劣，但GM翻了半天规则书发现这居然是合规的！」</span><br>";
    }elseif($fnumber == 9){
        $log .= "<span class=\"lime\">「バカ⑨！连青蛙都能冰冻起来的智慧，稍微分给了你一点点！」</span><br>";
        $wp += 9;
    }elseif($fnumber == 10){
        $log .= "<span class=\"lime\">「时空特使总部的积分兑换机上，至今挂着一条令人遐想的选项：『和GA-01抱一起睡一晚——10点』。」</span><br>";
    }elseif($fnumber == 11){
        $log .= "<span class=\"lime\">「在这个代码库里，一行 extract(\$data, EXTR_REFS) 就能让百来个变量凭空出现，至于它们从哪来的，只有天知道。」</span><br>";
    }elseif($fnumber == 12){
        $log .= "<span class=\"lime\">「谜语：六簇在幻境里摇曳的小小火苗，仔细看去，里面倒映着的却是五个孩子再也回不去的童年。」</span><br>";
    }elseif($fnumber == 13){
        $log .= "<span class=\"lime\">「要是你在岳城迷了路，误入13/13号巷子，记得向那只会说人话的猫老大问个好，顺便别乱碰那盏不用电池的灯笼。」</span><br>";
    }elseif($fnumber == 14){
        $log .= "<span class=\"lime\">「『踏雪』论坛的站长只是个十四岁的小姑娘，但如果你试图黑进她的服务器，她会让你明白什么叫降维打击。」</span><br>";
    }elseif($fnumber == 15){
        $log .= "<span class=\"lime\">「我以前也是个像你一样的挑战者，直到我的膝盖中了一发贴脸的核子武器。」</span><br>";
    }elseif($fnumber == 16){
        $log .= "<span class=\"lime\">「半夜三点起来给冰箱里的鸡蛋挨个编号，结果数到第七个的时候发现它正用敬畏的眼神看着我。」</span><br>";
    }elseif($fnumber == 17){
        $log .= "<span class=\"lime\">「捂脸姬摸了摸自己的口袋——等等，我的『武神之魂』怎么又被计划通行偷走了？！」</span><br>";
    }elseif($fnumber == 18){
        $log .= "<span class=\"lime\">「『干我们这行的，哪怕天塌下来，端着红茶的手也不能抖一下。』——芙蓉」</span><br>";
    }elseif($fnumber == 19){
        $log .= "<span class=\"lime\">「为什么背包槽不用数组而是要用 \$itm1 到 \$itm6？别问，问就是自2006年传承至今的古法编程！」</span><br>";
    }elseif($fnumber == 20){
        $log .= "<span class=\"lime\">「自然２０！哪怕你是对着一块路边的石头进行游说检定，石头也会感动得为你让路！」</span><br>";
    }elseif($fnumber == 21){
        $log .= "<span class=\"lime\">「千万别和动物团里的那只狍子少女Kiko玩猜硬币，因为连硬币落地的方向都会主动讨好她。」</span><br>";
    }elseif($fnumber == 22){
        $log .= "<span class=\"lime\">「谢邀，人在常磐，刚下灰机，因为圈内熟人太多而且容易被SANMA追杀，先匿了。」</span><br>";
    }elseif($fnumber == 23){
        $log .= "<span class=\"lime\">「是『八丁念佛团子刺』，不是『八念丁佛团子』！当年的OCR识别到底对杂贺孙市的爱刀做了什么啊！」</span><br>";
    }elseif($fnumber == 24){
        $log .= "<span class=\"lime\">「胶冻在熬夜写攻略，OGAS在用三进制数负数，菌菌子画完了立绘还得给全员画Q版表情包——巫师们今天也在绝赞加班中。」</span><br>";
    }elseif($fnumber == 25){
        $log .= "<span class=\"lime\">「你说得对，但是《常磐大逃杀》是一款由PHP驱动的古早网页文字生存游戏……」</span><br>";
    }elseif($fnumber == 26){
        $log .= "<span class=\"lime\">「随机数大神记仇得很，要是你敢在摇完骰子赚够属性之后偷偷换掉瑞克·拉玛尔的歌单，小心祂扣光你的力量！」</span><br>";
    }elseif($fnumber == 27){
        $log .= "<span class=\"lime\">「谜语：有人在黑衣死神推门而入的刹那，把自己敲碎撒进了银白盒子的海底——于是，提着灯火的孩子们在幻境里醒来了。」</span><br>";
    }elseif($fnumber == 28){
        $log .= "<span class=\"lime\">「你在地上捡到了一本写着『四面亲手制作』的黑色笔记本，翻开第一页写着：谁敢在上面写四面的名字，死后就变成青蛙。」</span><br>";
    }elseif($fnumber == 29){
        $log .= "<span class=\"lime\">「经过严密的拓扑学证明，炸鸡块蘸草莓酱的味道其实和在游泳池里听交响乐是一模一样的。」</span><br>";
    }elseif($fnumber == 30){
        $log .= "<span class=\"lime\">「『我只是一个平平无奇、最不出名的高中生侦探罢了……诶，幻境里那个顶着狐狸耳朵的白神？那肯定不是我。』」</span><br>";
    }elseif($fnumber == 31){
        $log .= "<span class=\"lime\">「勇敢的程序员试图重构这座历史悠久的屎山，并在引发了一场壮观的屎崩之后，成功建造出了『屎山2.0』！」</span><br>";
    }elseif($fnumber == 32){
        $log .= "<span class=\"lime\">「你强化了手中的灵魂宝石，只听啪嚓一声，它华丽地碎成了悲叹之种！凯丽在远处为你鼓掌。」</span><br>";
    }elseif($fnumber == 33){
        $log .= "<span class=\"lime\">「把击败的敌人尸体塞进背包当成钝器抡起来砸人，这在游戏工程学上被优雅地称为『素材复用』。」</span><br>";
    }elseif($fnumber == 34){
        $log .= "<span class=\"lime\">「蛋糕是个谎言，但幻境里的毒蛋糕是真的会要你命的。」</span><br>";
    }elseif($fnumber == 35){
        $log .= "<span class=\"lime\">「『知道了。』——当时空特使的司令只回你这三个字的时候，别去思考她的逻辑，照做就对了。」</span><br>";
    }elseif($fnumber == 36){
        $log .= "<span class=\"lime\">「一个半角单引号引发的血案：\\' OR 1=1 --，数据库先生表示它不想回忆起那一天。」</span><br>";
    }elseif($fnumber == 37){
        $log .= "<span class=\"lime\">「『これが私のありったけの魂よ。食らいなさい！飛鯨！』——伴随着尖锐的鲸鸣，近五米长的巨剑从低轨道呼啸而下！」</span><br>";
    }elseif($fnumber == 38){
        $log .= "<span class=\"lime\">「昨晚岳城夜空中出现的五米发光巨物与鲸鸣声？《走进科学》专家表示，这只是罕见的海风声光折射现象，请市民切勿传谣。」</span><br>";
    }elseif($fnumber == 39){
        $log .= "<span class=\"lime\">「初音大魔王在2011年的愚人节开着宇宙巡航机来过这里，并留下了一地强力射系武器。」</span><br>";
    }elseif($fnumber == 40){
        $log .= "<span class=\"lime\">「警告：请勿在星期八的下午与路边的消防栓进行眼神交流，它们会顺着网线偷喝你的洗发水。」</span><br>";
    }elseif($fnumber == 41){
        $log .= "<span class=\"lime\">「不管外面的局势多么混乱，邮递员加西亚总会准时把包裹送到你手上——只要你别纠结她是怎么念自己名字的。」</span><br>";
    }elseif($fnumber == 42){
        $log .= "<span class=\"lime\">「第42号特使从电路纹披风下掏出一张印着奇异字符的黄纸，啪地贴在了你的脑门上，你感觉自己多了一点剑术与投掷心得。」</span><br>";
        $wk += 4;
        $wc += 2;
    }elseif($fnumber == 43){
        $log .= "<span class=\"lime\">「在湾城收电子破烂的大学生亮亮看了看你手里的移动PC，表示这玩意儿哪怕算上情怀也只能抵五块钱。」</span><br>";
    }elseif($fnumber == 44){
        $log .= "<span class=\"lime\">「『小乌丸』的『乌』比『鸟』少了一横，于是平氏传家宝刀在幻境里永远变成了『☆平家宝刀小鸟☆』。」</span><br>";
    }elseif($fnumber == 45){
        $log .= "<span class=\"lime\">「只要把所有的BUG都改名叫BIG，再开三场总结反思会，项目进度就一定能蒸蒸日上！」</span><br>";
    }elseif($fnumber == 46){
        $log .= "<span class=\"lime\">「谜语：一个打不开、砸不碎、也无法被解析的银白色方盒，有人借它唤醒潜能，有人借它复现逝者，却永远没人能读出它真正的名字。」</span><br>";
    }elseif($fnumber == 47){
        $log .= "<span class=\"lime\">「光头西装男向你露出了礼貌的微笑——好消息是他没有带消音手枪，坏消息是他手里拎着一只会自动拐弯的公文包。」</span><br>";
    }elseif($fnumber == 48){
        $log .= "<span class=\"lime\">「打开任何一个PHP文件，映入眼帘的永远是整整齐齐三排 global，少写一个变量就会在某个百年不遇的分支里打出0点伤害。」</span><br>";
    }elseif($fnumber == 49){
        $log .= "<span class=\"lime\">「你哼哧哼哧拧了半天棱镜八面体，终于把八个面拧成了同一种颜色——然后它啪的一声变成了一颗彩色糖果。」</span><br>";
    }elseif($fnumber == 50){
        $log .= "<span class=\"lime\">「今天是不是疯狂星期四？不管是不是，随机数大神决定先V你50块钱再去买炸鸡！」</span><br>";
        $money += 50;
    }elseif($fnumber == 51){
        $log .= "<span class=\"lime\">「欢迎来到『東埔寨Protoject』！如有与某幻想乡作品雷同，纯属你眼花了。」</span><br>";
    }elseif($fnumber == 52){
        $log .= "<span class=\"lime\">「林天星在战前进行了长达三分钟的严密战术推演，最终得出了无懈可击的结论：『直接超频，然后拎起两米长的纯金大剑砸死对面。』」</span><br>";
    }elseif($fnumber == 53){
        $log .= "<span class=\"lime\">「要来一个早苗阿姨亲手烤的彩虹面包吗？吃完之后跑向洗手间的速度会突破音速哦。」</span><br>";
    }elseif($fnumber == 54){
        $log .= "<span class=\"lime\">「你好，我是一只假装成系统提示的马铃薯，如果你能看到这句话，说明你已经三天没有给仙人掌削皮了。」</span><br>";
    }elseif($fnumber == 55){
        $log .= "<span class=\"lime\">「集齐『我太酷啦』『我太帅啦』『我太棒啦』『我太强啦』四个按钮，就能召唤出mtkkk发明的破则战术——『我头四』！」</span><br>";
    }elseif($fnumber == 56){
        $log .= "<span class=\"lime\">「谜语：湾城郊区的孤儿院顶楼有两个常年上锁的房间，隔着门板传来的嗡鸣不是风声，而是二十年前幸存下来的余烬。」</span><br>";
    }elseif($fnumber == 57){
        $log .= "<span class=\"lime\">「当你凝视深渊的时候，深渊正在检查你的 \$clbpara 到底是JSON字符串还是PHP数组。」</span><br>";
    }elseif($fnumber == 58){
        $log .= "<span class=\"lime\">「金龙通讯社表面上是一家正经的媒体兼猎头公司，至于为什么员工们出门采访都带着重火力和防弹衣，建议你不要多问。」</span><br>";
    }elseif($fnumber == 59){
        $log .= "<span class=\"lime\">「前有绝景，因此试试跳跃——然后你就摔断了双腿。」</span><br>";
    }elseif($fnumber == 60){
        $log .= "<span class=\"lime\">「哪怕在幻境里捡到了金坷垃，一袋也顶不了两袋撒，更不可能让你的武器熟练度亩产一千八。」</span><br>";
    }elseif($fnumber == 61){
        $log .= "<span class=\"lime\">「为什么蓝凝小姐的歌单里明明写着『梦幻般的人声』，放出来却全都是纯音乐伴奏？因为唱出来的版本被版权结界封印啦。」</span><br>";
    }elseif($fnumber == 62){
        $log .= "<span class=\"lime\">「昨天的我借了明天的我五十块钱，今天的我决定直接申请破产清算，让他们两个自己去打官司。」</span><br>";
    }elseif($fnumber == 63){
        $log .= "<span class=\"lime\">「编译器弹出了八百个Warning？没事，野指针先生表示只要不是Fatal Error，代码就还能跑！」</span><br>";
    }elseif($fnumber == 64){
        $log .= "<span class=\"lime\">「你的脑海里突然装满了整整64个一组的圆石，史蒂夫看了都想找你借两块搭路。」</span><br>";
    }elseif($fnumber == 65){
        $log .= "<span class=\"lime\">「谜语：十三道影子倒在同一场意外里——断裂的横梁、卡壳的枪膛、失灵的安全锁，当所有的巧合同时发生，那还叫巧合吗？」</span><br>";
    }elseif($fnumber == 66){
        $log .= "<span class=\"lime\">「白刀子进，白刀子出！你在全图广播里发了一条『我觉得我还可以抢救一下……』，然后像没事人一样拍拍屁股站了起来。」</span><br>";
    }elseif($fnumber == 67){
        $log .= "<span class=\"lime\">「霜火协奏曲曾在这里留下了史上第一神触的传说，而你刚刚差点被路边的杂兵绊倒。」</span><br>";
    }elseif($fnumber == 68){
        $log .= "<span class=\"lime\">「温馨提示：当你觉得自己杀得兴起、APM与RP双双飙升的时候，某位握着黄金秋刀鱼的守卫者已经在提刀赶来的路上了。」</span><br>";
    }elseif($fnumber == 69){
        $log .= "<span class=\"lime\">「在另一个幸运函数里，编号６９给了你决心；而在这里，编号６９真的给你塞了一口救命的奶！」</span><br>";
        $hp = min($mhp, $hp + 69);
    }elseif($fnumber == 70){
        $log .= "<span class=\"lime\">「『给武器和招式起名字是一门艺术，像那种毫无美感的命名——就地丢弃，请。』——芙蓉」</span><br>";
    }elseif($fnumber == 71){
        $log .= "<span class=\"lime\">「枪毙的某神留下的大师球至今还在常磐森林里滚动，但千万别拿它去砸真职人。」</span><br>";
    }elseif($fnumber == 72){
        $log .= "<span class=\"lime\">「有人往滚筒洗衣机里塞了一本《高等数学》，转了四十分钟后甩出来一堆活蹦乱跳的拉格朗日中值定理。」</span><br>";
    }elseif($fnumber == 73){
        $log .= "<span class=\"lime\">「修水管的时候发现前任维护者『小林』留下的一张纸条：『这根漏雪的管子千万别动，动了整个房间都会塌。』」</span><br>";
    }elseif($fnumber == 74){
        $log .= "<span class=\"lime\">「看到地上的尸体先别急着摸装备，万一有人趁着尸体无敌时间突然诈尸凸你一下呢？」</span><br>";
    }elseif($fnumber == 75){
        $log .= "<span class=\"lime\">「谜语：第三把交椅并非无人入座，而是曾坐在上面的人，亲手将自己的名字与过往一同从所有的纸页上抹去了。」</span><br>";
    }elseif($fnumber == 76){
        $log .= "<span class=\"lime\">「『喵哈哈！本名侦探已经看穿一切了，ZERO少年！』——虽然她的推理过程一塌糊涂，但偏偏每次都能蒙对方向。」</span><br>";
    }elseif($fnumber == 77){
        $log .= "<span class=\"lime\">「７７－ＪＡＣＫＰＯＴ！为了阿婆主的点击量，你的钱包突然鼓了一点，连报应都消退了几分！」</span><br>";
        $money += 77;
        $rp = max(0, $rp - 7);
    }elseif($fnumber == 78){
        $log .= "<span class=\"lime\">「初代高达的编号是RX-78-2，但这并不妨碍幻境里有个叫『我是高达』的家伙拿着枪到处乱射。」</span><br>";
    }elseif($fnumber == 79){
        $log .= "<span class=\"lime\">「在 include/game/ 目录下静静躺着一个四千多行的 item.func.old，它就像一块赛博琥珀，封印着拆分文件前的混沌纪元。」</span><br>";
    }elseif($fnumber == 80){
        $log .= "<span class=\"lime\">「鸡汤来咯！这在幻境里喝上一碗加了鹤顶红的鸡汤，多是一件美事啊！」</span><br>";
    }elseif($fnumber == 81){
        $log .= "<span class=\"lime\">「湾城里有个穿连帽衫的白发青年，好像永远不需要睡觉，不管是修电脑、撬锁还是打架都恰好『稍微懂一点』。」</span><br>";
    }elseif($fnumber == 82){
        $log .= "<span class=\"lime\">「企鹅之所以不会飞，是因为它们把所有的飞行技能点都点在了穿燕尾服的社交礼仪检定上。」</span><br>";
    }elseif($fnumber == 83){
        $log .= "<span class=\"lime\">「千万不要随便对路边的AC专业职人痛下杀手，除非你真的很想品尝秋刀鱼罐头糊脸的滋味。」</span><br>";
    }elseif($fnumber == 84){
        $log .= "<span class=\"lime\">「笑点解析：这条命运提示本身没有任何实质性作用，但你读完之后浪费了两秒钟的宝贵搜索时间，令人忍俊不禁。」</span><br>";
    }elseif($fnumber == 85){
        $log .= "<span class=\"lime\">「为什么时空特使的白色长大衣设计得那么帅，却只有亚玛丽欧一个人老老实实穿全套？因为其他人嫌发光缝线太显眼了！」</span><br>";
    }elseif($fnumber == 86){
        $log .= "<span class=\"lime\">「昨晚我在秋名山输给了一辆AE86，他用惯性漂移过弯，车门上还印着『金龙通讯社专送』。」</span><br>";
    }elseif($fnumber == 87){
        $log .= "<span class=\"lime\">「谜语：盒子不会自己长出哪怕一片树叶；你往里面投下怎样的渴望与恐惧，它便在海底为你拓印出怎样的幻景。」</span><br>";
    }elseif($fnumber == 88){
        $log .= "<span class=\"lime\">「★棱镜八面体模样的彩色糖果★在你的脑海中闪过，你的歌魂跟着轻轻跳动了一下！」</span><br>";
        $ss = min($mss, $ss + 8);
    }elseif($fnumber == 89){
        $log .= "<span class=\"lime\">「为什么D6骰出来的菜刀上会带着防弹和防爆炸属性？因为源码注释里理直气壮地写着：『Thus, this check is omitted - On PURPOSE!!!』」</span><br>";
    }elseif($fnumber == 90){
        $log .= "<span class=\"lime\">「告诉老默，我想吃秋刀鱼了——等等，来的怎么是拿着元素枪刃Glider的SANMA_TK？！」</span><br>";
    }elseif($fnumber == 91){
        $log .= "<span class=\"lime\">「『演剧部的排练明明已经够忙了，为什么姐姐还要塞给我一堆奇怪的课外任务啊！』——丁香」</span><br>";
    }elseif($fnumber == 92){
        $log .= "<span class=\"lime\">「这年头连魅魔都偏科了——魅惑不了人类，偏偏能魅惑各种金属，只好躲在家里靠定制COSPLAY金属道具糊口。」</span><br>";
    }elseif($fnumber == 93){
        $log .= "<span class=\"lime\">「五名错位职能女仆正在幻境里游荡，千万别问她们为什么倒过来的名字分别是冰、火、雷、泥和咆哮。」</span><br>";
    }elseif($fnumber == 94){
        $log .= "<span class=\"lime\">「大自然的搬运工从不生产代码，他们只是把雪从一座山头搬到另一座山头，直到引发雪崩。」</span><br>";
    }elseif($fnumber == 95){
        $log .= "<span class=\"lime\">「『虚拟幻境？那不就是一个比拼反应速度和帧数判定的高速动作PVP游戏吗？』——某位提供动力装甲的学弟如是说。」</span><br>";
    }elseif($fnumber == 96){
        $log .= "<span class=\"lime\">「如果一只蟑螂出现在你的厨房里，那说明暗处已经有一百只蟑螂正在准备进行同调召唤了。」</span><br>";
    }elseif($fnumber == 97){
        $log .= "<span class=\"lime\">「谜语：在某条未被沙漏截断的平行轨迹里，她没有化作幻境的种火，而是研究生毕业后和恋人开了一家软件公司，每天靠打牌度日。」</span><br>";
    }elseif($fnumber == 98){
        $log .= "<span class=\"lime\">「2024年7月19日，某位开发者在给物品加上 \$itmpara 时留下了一行悲壮的注释：『I'm mad enough to add \$itmpara, with me luck.』——甚至连 wish 都打成了 with。」</span><br>";
    }elseif($fnumber == 99){
        $log .= "<span class=\"lime\">「天才就是99%的汗水，加上1%抽中『随机数之神的庇佑』直接开启L5状态！」</span><br>";
        $rage = min(100, $rage + 9);
    }elseif($fnumber == 100){
        $log .= "<span class=\"lime\">「１００点！在CoC里这叫大失败，但在考试里这叫满分！系统决定按满分奖励你一口体力！」</span><br>";
        $sp = min($msp, $sp + 100);
    }elseif($fnumber == 101){
        $log .= "<span class=\"lime\">「为什么事件系统里的 charge1 上限偏偏是101而不是100？大概是因为多出来的那1点叫做骄傲吧。」</span><br>";
    }elseif($fnumber == 102){
        $log .= "<span class=\"lime\">「戴着魔女帽的银紫发少女轻轻晃了晃法杖，哪怕走两步路都会气喘吁吁，聚光灯却总是追着她一个人转。」</span><br>";
    }elseif($fnumber == 103){
        $log .= "<span class=\"lime\">「一道强光闪过——你背包中的物品并没有消失，虚惊一场，看来今天你还不需要付出『无毁荣光的必要代价』。」</span><br>";
    }elseif($fnumber == 104){
        $log .= "<span class=\"lime\">「你听见虚空中传来一串十六进制的报时声：『当前锚定时空帧——＃１０４Ｂ６，祝您旅途愉快。』」</span><br>";
    }elseif($fnumber == 105){
        $log .= "<span class=\"lime\">「把头伸进人生重来炮之前请先检查自己的最大生命值是否大于200，否则大炮会在你伸头前直接糊你一脸。」</span><br>";
    }elseif($fnumber == 106){
        $log .= "<span class=\"lime\">「永远不要低估一只加拿大鹅的战斗力，哪怕她变成了人形少女，那一双铁拳也照样能把装甲车砸出坑来。」</span><br>";
    }elseif($fnumber == 107){
        $log .= "<span class=\"lime\">「谜语：若你在异乡的时空不幸殒命，只需搭上那辆由娃娃脸司机驾驶的巴士便可归来——只是下车之后，你还会记得自己坐过这趟车吗？」</span><br>";
    }elseif($fnumber == 108){
        $log .= "<span class=\"lime\">「除夕夜的钟声敲响了一百零八下，你的烦恼消散了一点，六系武艺各精进了1点！」</span><br>";
        $wp += 1; $wk += 1; $wg += 1; $wc += 1; $wd += 1; $wf += 1;
    }elseif($fnumber == 109){
        $log .= "<span class=\"lime\">「左手Visual Studio 2010，右手GoldWave，腰挂MeGUI，头顶AppLocale——北京推倒你这身装备真的是来打大逃杀而不是来压制字幕的吗？」</span><br>";
    }elseif($fnumber == 110){
        $log .= "<span class=\"lime\">「当你试图在星期四的下午理解星期三为什么不叫星期五的时候，星期二已经偷偷把星期一吃掉了。」</span><br>";
    }elseif($fnumber == 111){
        $log .= "<span class=\"lime\">「三个１排成一排，像不像三根正在给你上香的脆皮火腿肠？」</span><br>";
    }elseif($fnumber == 112){
        $log .= "<span class=\"lime\">「自从莫名其妙觉醒了能看见别人头顶数据的能力之后，ZERO每天最大的愿望就是找到这个破功能的关闭按钮。」</span><br>";
    }elseif($fnumber == 113){
        $log .= "<span class=\"lime\">「为了照顾二十年前留下来的短标签，每次在本地跑PHP语法检查的时候都得老老实实挂上 -d short_open_tag=1。」</span><br>";
    }elseif($fnumber == 114){
        $log .= "<span class=\"lime\">「系统检测到您的登录IP为 114.514.191.81——好吧，这其实是写在代码里的假IP彩蛋。」</span><br>";
    }elseif($fnumber == 115){
        $log .= "<span class=\"lime\">「幻境的最后防线『一一五』今天也在努力卖萌，但如果你真把她当成软柿子捏，断罪女神会教你做人。」</span><br>";
    }elseif($fnumber == 116){
        $log .= "<span class=\"lime\">「谜语：铜绿色的交叠圆环包裹着蔚蓝的核心，当少女在漫天风雪中接过那枚沙漏徽章时，所有的指针都指向了最大的刻度。」</span><br>";
    }elseif($fnumber == 117){
        $log .= "<span class=\"lime\">「当你在星莲船第四面看到那个熟悉的身影时，请记住她不仅是BOSS，还是写了这堆代码的程序员投影。」</span><br>";
    }elseif($fnumber == 118){
        $log .= "<span class=\"lime\">「大脑：我已经完全掌握全套游戏王同调与超量合成表了！手：不，你只会合出一堆『★我是狗屎★』。」</span><br>";
    }elseif($fnumber == 119){
        $log .= "<span class=\"lime\">「往木棍上装一颗☢核子核心☢，恭喜你获得了『☢木棍』！现在你一棍子下去整个地图的吃瓜群众都要跟着陪葬了。」</span><br>";
    }elseif($fnumber == 120){
        $log .= "<span class=\"lime\">「某只小兔子声称自己平时码代码就是听着《Schnuffel》写的，并且顺手扣掉了你120点报应——虽然这次没有真扣。」</span><br>";
    }elseif($fnumber == 121){
        $log .= "<span class=\"lime\">「１２１，立正，稍息！恭喜你通过了幻境军训队列检定，但并没有任何学分奖励。」</span><br>";
    }elseif($fnumber == 122){
        $log .= "<span class=\"lime\">「屏幕里的『FOREST』活力四射、元气满满；屏幕外的配音少女林铃子却缩在角落里，连和陌生人对视都会紧张。」</span><br>";
    }elseif($fnumber == 123){
        $log .= "<span class=\"lime\">「１、２、３，木头人！不许动！……好吧，在回合制网页游戏里你本来也是点一下才动一下。」</span><br>";
    }elseif($fnumber == 124){
        $log .= "<span class=\"lime\">「如果说林仙凤是时空特使天马行空的灵魂，那么秘书林彩光就是那个不得不替她把所有离谱想法写成可执行文件的苦命大脑。」</span><br>";
    }elseif($fnumber == 125){
        $log .= "<span class=\"lime\">「谜语：『那绝不是什么迫击炮，那是我们教头内功大成、爆气炸碎了整栋屋子！』——至于提出质疑的人，都已经化作灰烬了。」</span><br>";
    }elseif($fnumber == 126){
        $log .= "<span class=\"lime\">「看到这张写着八百字小作文的效果卡了吗？我敢打赌你连第一句『此卡名的①②效果1回合各能使用1次』都没读完。」</span><br>";
    }elseif($fnumber == 127){
        $log .= "<span class=\"lime\">「在 fortuneCookie1 里，127是最后一条正常命运；但在 randomFortune 里，我们的狂欢才刚刚过半！」</span><br>";
    }elseif($fnumber == 128){
        $log .= "<span class=\"lime\">「１２８！这是第一梦之数值的极值，也是核武器打出7.77倍天谴伤害的黄金刻度！你的生命上限受到了轻微的鼓舞！」</span><br>";
        $mhp += 12;
        $hp += 12;
    }elseif($fnumber == 129){
        $log .= "<span class=\"lime\">「看见那个八百八十八万血、自带双抹消的便当盒了吗？相信我，那真的只是个吉祥物，千万别手贱去摸它。」</span><br>";
    }elseif($fnumber == 130){
        $log .= "<span class=\"lime\">「俺寻思这颗骰子能骰出大成功！WAAAGH！」</span><br>";
    }elseif($fnumber == 131){
        $log .= "<span class=\"lime\">「听说前代红杀将军飞龙退隐之后，在某个不起眼的街角开了一家卖中草药的铺子，抓药的时候手稳得像握刀一样。」</span><br>";
    }elseif($fnumber == 132){
        $log .= "<span class=\"lime\">「搬运⑨课的先辈们用弹幕糊满了整个屏幕，然后深藏功与名。」</span><br>";
    }elseif($fnumber == 133){
        $log .= "<span class=\"lime\">「感谢777当年提供的桃箱服与备案，常磐的火种才得以在互联网的寒冬里继续燃烧。」</span><br>";
    }elseif($fnumber == 134){
        $log .= "<span class=\"lime\">「谜语：相同的面容，相同的名字，相同的底色；一人在港区的车间里点亮微光，一人在沙漏的阴影下拨弄棋盘。」</span><br>";
    }elseif($fnumber == 135){
        $log .= "<span class=\"lime\">「在数据库里，你和路边的杂兵NPC其实住在同一张表里，区别仅仅在于你的 type 是0，而它们不是。」</span><br>";
    }elseif($fnumber == 136){
        $log .= "<span class=\"lime\">「今天夜里要是看到一只猫在路灯下解二元一次方程，请不要打扰它，它明天还要考会计证。」</span><br>";
    }elseif($fnumber == 137){
        $log .= "<span class=\"lime\">「精细结构常数的倒数大约是137，宇宙的物理规律在此刻向你投来了赞许的一瞥。」</span><br>";
    }elseif($fnumber == 138){
        $log .= "<span class=\"lime\">「藤甲兵刀枪不入，唯独怕火——所以看到对面掏出火焰武器的时候，建议立刻脱掉身上的藤甲跑路。」</span><br>";
    }elseif($fnumber == 139){
        $log .= "<span class=\"lime\">「自从红暮把那座大排档据点送给善德和恶德之后，七只动物少女每天都在认真研究怎么把中华料理做出新花样。」</span><br>";
    }elseif($fnumber == 140){
        $log .= "<span class=\"lime\">「FFF团资深团员已经举起了火把与汽油，幻境里禁止一切未经审批的成双成对行为！」</span><br>";
    }elseif($fnumber == 141){
        $log .= "<span class=\"lime\">「高并发锁机制的终极奥义：在服务器硬盘上摸一个名叫 process.lock 的空文件。」</span><br>";
    }elseif($fnumber == 142){
        $log .= "<span class=\"lime\">「『只要能飞上蓝天，不管是战斗机还是模因技术，本质上都是追求极限的速度啊！』——狂飙」</span><br>";
    }elseif($fnumber == 143){
        $log .= "<span class=\"lime\">「ДΨЙЙЖ ОЖТЙΡ, ΛΑХ ΔЖИ ДΨΑТ ПΔ ЛЖВΛΨ? ——耳边传来了一阵清脆如鸟鸣的口哨声，好像在向你打招呼。」</span><br>";
    }elseif($fnumber == 144){
        $log .= "<span class=\"lime\">「这是十二的平方，也是一打鸡蛋乘以一打鸡蛋——恭喜你在脑海里获得了144颗虚拟煎蛋！」</span><br>";
    }elseif($fnumber == 145){
        $log .= "<span class=\"lime\">「从鱼篓子里掏出三叉戟、高压水枪甚至潜水艇都是很合理的日常现象，请不要用鱼类学的常识来质疑幻境。」</span><br>";
    }elseif($fnumber == 146){
        $log .= "<span class=\"lime\">「你还记得你到现在为止吃过多少片面包吗？——至少在幻境里，这个数字是可以按恢复体力的次数算出来的！」</span><br>";
    }elseif($fnumber == 147){
        $log .= "<span class=\"lime\">「斯诺克满分杆是147分，但你手里拿的不是台球杆，而是一根沾着铁钉的棍棒。」</span><br>";
    }elseif($fnumber == 148){
        $log .= "<span class=\"lime\">「谜语：在没有十六进制编号的底片世界里，野心家们早已在互相倾轧中化作尘土，只剩无人看管的银白方盒仍在寂静中日夜空转。」</span><br>";
    }elseif($fnumber == 149){
        $log .= "<span class=\"lime\">「在这个世界里，无限耐久不是 -1 也不是 INF，而是字符串 '∞'——千万别在代码里拿它去做加减法！」</span><br>";
    }elseif($fnumber == 150){
        $log .= "<span class=\"lime\">「初代宝可梦一共有150种……等一下，常磐森林树冠上是不是还藏着第151只？」</span><br>";
    }elseif($fnumber == 151){
        $log .= "<span class=\"lime\">「刚才那只粉红色的小精灵从你眼前晃了过去，顺手把你的好运气揉成了一个毛线球。」</span><br>";
    }elseif($fnumber == 152){
        $log .= "<span class=\"lime\">「在金龙通讯社的技术总监薇娜眼里，世间万物都可以被拆解成优雅的算法——除了某些人完全不讲逻辑的直觉。」</span><br>";
    }elseif($fnumber == 153){
        $log .= "<span class=\"lime\">「电子狐会复制你的家当，走地羊会敲碎你的防具，魔法蜂专扎你的痛处，而那只笼中鸟……你的报应越深，它就越喜欢你。」</span><br>";
    }elseif($fnumber == 154){
        $log .= "<span class=\"lime\">「用决斗带来笑容！哪怕背包里只剩下一块发霉的面包，只要把它当成决斗盘绑在胳膊上就能抽卡！」</span><br>";
    }elseif($fnumber == 155){
        $log .= "<span class=\"lime\">「如果所有的拖鞋都决定在礼拜三集体向南迁徙，那么袜子们就不得不自己学会系鞋带了。」</span><br>";
    }elseif($fnumber == 156){
        $log .= "<span class=\"lime\">「商店里摆满了埃克法轻工的特供神装，而你摸了摸兜里仅剩的几十块钱，默默买了一瓶矿泉水。」</span><br>";
    }elseif($fnumber == 157){
        $log .= "<span class=\"lime\">「谜语：至柔易碎的琉璃与至刚狂暴的纯金，谁能想到这两具截然相反的躯壳，最初竟是拓印自同一张图纸的正反两面？」</span><br>";
    }elseif($fnumber == 158){
        $log .= "<span class=\"lime\">「只要塞够香油钱，不管是博丽神社还是守矢神社的神明都会对你露出营业式的微笑。」</span><br>";
    }elseif($fnumber == 159){
        $log .= "<span class=\"lime\">「在这个文件最开始被写下的时候，第一行注释写着：『This came to me in a dream.』——没错，你正在经历开发者的梦话。」</span><br>";
    }elseif($fnumber == 160){
        $log .= "<span class=\"lime\">「葬送的芙莉莲提醒您：路边无人看管的箱子有99%是陷阱，但万一里面真的装着珍贵的技能书呢？」</span><br>";
    }elseif($fnumber == 161){
        $log .= "<span class=\"lime\">「在遥远的#16161时空帧里，有一艘无尽天梭正在航行——不过那已经是另一个故事的片场了。」</span><br>";
    }elseif($fnumber == 162){
        $log .= "<span class=\"lime\">「你试图用意念把面前的空气拧成麻花，空气礼貌地拒绝了你的请求，并还了你一个喷嚏。」</span><br>";
    }elseif($fnumber == 163){
        $log .= "<span class=\"lime\">「大爷星人、肥猫大爷、肉用大爷、挂腿上的大爷……逆向朝阳前辈这到底是来打大逃杀的，还是来开猫咪咖啡厅的？」</span><br>";
    }elseif($fnumber == 164){
        $log .= "<span class=\"lime\">「『无论是虚拟实境还是外面的世界，魔导的回路本质上都是一样的哦——只要你能理解它的韵律。』——蓝凝」</span><br>";
    }elseif($fnumber == 165){
        $log .= "<span class=\"lime\">「左脚踩右脚虽然不能让你直接螺旋升天，但在某些老版本游戏的物理引擎里真的可以！」</span><br>";
    }elseif($fnumber == 166){
        $log .= "<span class=\"lime\">「云玩家满意地点了点头：虽然我从来没进过英灵殿，但只要我看过别人屠殿的战报，四舍五入我也算通关了！」</span><br>";
    }elseif($fnumber == 167){
        $log .= "<span class=\"lime\">「谜语：侦探提供悬案的骨架，魔女织就故事的血肉；而在虚拟的舞台上，侦探却披上了魔女缝制的白狐外衣。」</span><br>";
    }elseif($fnumber == 168){
        $log .= "<span class=\"lime\">「飞雪大魔王向你传授了沙包护体神功：只要这次挨揍掉的血比上一次少，那就等于完全没掉血！」</span><br>";
    }elseif($fnumber == 169){
        $log .= "<span class=\"lime\">「冰冻青蛙在公告牌上歪歪扭扭地写下：『咸鱼的目标可不是杀死玩家哦~』」</span><br>";
    }elseif($fnumber == 170){
        $log .= "<span class=\"lime\">「老电波服谚语：包里揣着镜湖楼主的头，过马路再也不怕被泥头车撞去异世界了！」</span><br>";
    }elseif($fnumber == 171){
        $log .= "<span class=\"lime\">「常磐森林里掉落的『露琪亚之羽』，到底是宝可梦洛奇亚掉的羽毛，还是尸魂界死神代理掉的羽毛呢？」</span><br>";
    }elseif($fnumber == 172){
        $log .= "<span class=\"lime\">「富竹次郎咔嚓一声按下了相机快门：『哎呀，今晚的风祭森林好像有点不太对劲呢……』」</span><br>";
    }elseif($fnumber == 173){
        $log .= "<span class=\"lime\">「无论你手里拿的是H173注射器，还是面前站着SCP-173，现在最正确的选择都是——千万别眨眼，也别挠脖子！」</span><br>";
    }elseif($fnumber == 174){
        $log .= "<span class=\"lime\">「这里是东方地雷殿，每走一步都有惊喜在脚下等着你哦！」</span><br>";
    }elseif($fnumber == 175){
        $log .= "<span class=\"lime\">「欧内的手，好汉！脑海中突然回荡起一段倒放般的抽象语音，让你不禁开始思考所说的道理。」</span><br>";
    }elseif($fnumber == 176){
        $log .= "<span class=\"lime\">「在红暮的私人收藏室里，静静摆放着前代菁英『翼虎』留下的那面盾牌。」</span><br>";
    }elseif($fnumber == 177){
        $log .= "<span class=\"lime\">「恭喜你晋升为SCP研究设施的荣誉D级人员！你的工作内容包括但不限于给SCP-682刷牙。」</span><br>";
    }elseif($fnumber == 178){
        $log .= "<span class=\"lime\">「你凝视着wdhwg001留下的键盘，上面仿佛还残留着当年敲代码与修图时的余温。」</span><br>";
    }elseif($fnumber == 179){
        $log .= "<span class=\"lime\">「千万不要在代码里随手写 time()，因为幻境的时间基准 \$now 带着部署时差，混用就会让人原地穿越时空！」</span><br>";
    }elseif($fnumber == 180){
        $log .= "<span class=\"lime\">「你来我往，胜负之数瞬间完成了180度的大翻转……或者又转了180度变回了原样！」</span><br>";
    }elseif($fnumber == 181){
        $log .= "<span class=\"lime\">「ΩВХΡ ПΔ ДАТЩ ВХ ЩДΨ ЦХЖО. ——火苗跳动着拼出了一串奇异的符号，像是在雪地里寻找失落之物。」</span><br>";
    }elseif($fnumber == 182){
        $log .= "<span class=\"lime\">「要是觉得幻境太难活下去，不妨试试在心里默念三遍『我是沙包别打我』，虽然对NPC完全无效就是了。」</span><br>";
    }elseif($fnumber == 183){
        $log .= "<span class=\"lime\">「左手《妥拉》右手《死海文书》，嘴里念着固有结界，随手甩出三张东方符卡——这就是武神水月的缝合美学。」</span><br>";
    }elseif($fnumber == 184){
        $log .= "<span class=\"lime\">「鲷鱼烧一定要从头开始吃，还是从尾巴开始吃？这是一个关乎雪之镇和平的严肃哲学问题。」</span><br>";
    }elseif($fnumber == 185){
        $log .= "<span class=\"lime\">「当你的大脑决定摆烂的时候，你的小脑其实已经在替你盘算晚饭吃什么味的泡面了。」</span><br>";
    }elseif($fnumber == 186){
        $log .= "<span class=\"lime\">「弹幕最重要的就是火力だぜ☆！不管是魔炮还是核弹，只要口径够大就不需要瞄准！」</span><br>";
    }elseif($fnumber == 187){
        $log .= "<span class=\"lime\">「清理大师摸了摸牌桌上的透明麻将牌：哪怕面对地狱般的赌局，只要不按套路出牌就能活下去。」</span><br>";
    }elseif($fnumber == 188){
        $log .= "<span class=\"lime\">「据不可靠统计，在233服的第5821局里，歌神mtkkk在雏菊之丘连续开了两万六千次演唱会。」</span><br>";
    }elseif($fnumber == 189){
        $log .= "<span class=\"lime\">「真正的决斗者哪怕生命值只剩下最后1点，也能在神抽的瞬间把卡组印出花来！」</span><br>";
    }elseif($fnumber == 190){
        $log .= "<span class=\"lime\">「你的命运返回了一个 NaN（Not a Number），系统差点给天然呆程序员自动发了一封报错站内信。」</span><br>";
    }elseif($fnumber == 191){
        $log .= "<span class=\"lime\">「冷知识：在填料之前，这个函数把 elseif(\$fnumber > 1024) 写在了 elseif(\$fnumber > 4096) 的前面，导致后面的四句话从来没有被执行过。」</span><br>";
    }elseif($fnumber == 192){
        $log .= "<span class=\"lime\">「192.168.1.1——别看了，这不是什么神秘宇宙坐标，这只是你家路由器的后台地址。」</span><br>";
    }elseif($fnumber == 193){
        $log .= "<span class=\"lime\">「金龙通讯社社员守则第一条：看到老板红暮和密传芙蓉同时出现时，请自觉保持安全距离并假装在认真写稿。」</span><br>";
    }elseif($fnumber == 194){
        $log .= "<span class=\"lime\">「如果你觉得周围安静得可怕，那大概率是因为大家都躲在暗处磨刀，等着禁区缩小的那一刻。」</span><br>";
    }elseif($fnumber == 195){
        $log .= "<span class=\"lime\">「费马在页边写下了一个绝妙的必胜合成公式，可惜背包格子太小，这里写不下。」</span><br>";
    }elseif($fnumber == 196){
        $log .= "<span class=\"lime\">「低维生物向你扔出了一张二向箔，好在幻境本来就是个二维网页游戏，你没有受到任何降维打击！」</span><br>";
    }elseif($fnumber == 197){
        $log .= "<span class=\"lime\">「如果把柠檬汁滴在隐形墨水上，能不能写出只有柠檬精才能看懂的加密日记？」</span><br>";
    }elseif($fnumber == 198){
        $log .= "<span class=\"lime\">「非常非常二打开了虚数几何空间，试图把第七件道具塞进只有六个格子的背包里。」</span><br>";
    }elseif($fnumber == 199){
        $log .= "<span class=\"lime\">「只要还在199以内，四舍五入就还不算满200——商家促销和善良之刃的怒气判定都是这么算的。」</span><br>";
    }elseif($fnumber == 200){
        $log .= "<span class=\"lime\">「２００！恭喜你的状态码返回了 200 OK，至少在这一秒钟里，你的网络连接和精神状态都十分正常。」</span><br>";
    }elseif($fnumber == 201){
        $log .= "<span class=\"lime\">「《银月哨兵团》里的熵魔法传人Howling看了一眼玛雅历法，表示今天宜摸鱼，忌踩雷。」</span><br>";
    }elseif($fnumber == 202){
        $log .= "<span class=\"lime\">「左右对称的２０２！哪怕你把显示器倒过来摆，这个数字看起来也一模一样。」</span><br>";
    }elseif($fnumber == 203){
        $log .= "<span class=\"lime\">「银白愿天使Annabelle一边发射着『雷米尔之冲击』，一边从兜里掉出了一包游戏王卡包——这跨界是不是稍微有点太远了？」</span><br>";
    }elseif($fnumber == 204){
        $log .= "<span class=\"lime\">「到底是『索非亚小傻鸟』、『索菲亚小傻鸟』还是『麻烦妖精Sophia』？算了，今晚A岛美食版的菜单是红烧小鸟！」</span><br>";
    }elseif($fnumber == 205){
        $log .= "<span class=\"lime\">「『当黑夜降临湾城，当骰子的点数背叛了你的期待——别怕，大英雄瑞克·拉玛尔来助你一臂之力了！』」</span><br>";
    }elseif($fnumber == 206){
        $log .= "<span class=\"lime\">「正常成年人一共有206块骨头，但如果你在幻境里挨了一记重锤，可能会瞬间变成207块或者更多。」</span><br>";
    }elseif($fnumber == 207){
        $log .= "<span class=\"lime\">「↑↑↓↓←→←→BA！天降软妹与几十吨重的宇宙战机正在向圣Gradius学园紧急迫降中，请地面人员注意闪避！」</span><br>";
    }elseif($fnumber == 208){
        $log .= "<span class=\"lime\">「谜语：长着火苗样子的未必是火，长着翅膀的也未必能飞出鸟笼；当七只小兽在幻境的角落里各自巡游，它们守着的究竟是谁留下的旧痕？」</span><br>";
    }elseif($fnumber == 209){
        $log .= "<span class=\"lime\">「曾有人建议把这几百行 if...elseif 改成 switch 或者数组查表，但为了保持祖传代码的原汁原味，这个建议被无情地驳回了。」</span><br>";
    }elseif($fnumber == 210){
        $log .= "<span class=\"lime\">「『生气了吗？』『没有哦。』——pop子和pipi美在你的脑海里跳了一段意义不明的体操。」</span><br>";
    }elseif($fnumber == 211){
        $log .= "<span class=\"lime\">「２１１！在D100的隐藏判定里，要是你的第二梦之数值恰好是211，你就是天选之子！」</span><br>";
    }elseif($fnumber == 212){
        $log .= "<span class=\"lime\">「迎面走来的挑战者突然开始练习时长两年半的唱、跳、Rap与投系篮球，你不禁发出了『你干嘛～哎哟』的感叹。」</span><br>";
    }elseif($fnumber == 213){
        $log .= "<span class=\"lime\">「如果一个问题引发了另一个问题，那么只要把提出问题的人和问题一起塞进合成炉里，大概就能合出『★问题解决者★』了吧。」</span><br>";
    }elseif($fnumber == 214){
        $log .= "<span class=\"lime\">「去死去死团员证正在你的口袋里隐隐发烫，提醒你今天离一切散发着恋爱酸臭味的地方远一点。」</span><br>";
    }elseif($fnumber == 215){
        $log .= "<span class=\"lime\">「†升天†！互联网天使提醒您：高强度网上冲浪可能会导致最大生命值减半，但埋雷的威力会变得超大哦！」</span><br>";
    }elseif($fnumber == 216){
        $log .= "<span class=\"lime\">「这是六的三次方！六面骰子连投三次都出六的概率只有216分之一，但你刚刚一发就中了！」</span><br>";
    }elseif($fnumber == 217){
        $log .= "<span class=\"lime\">「章鱼猫向你发起了『拉取请球（Pull Request）』，并试图在你的脑海里强行『建立分叉（Fork）』！」</span><br>";
    }elseif($fnumber == 218){
        $log .= "<span class=\"lime\">「金龙通讯社的执行官原型机——HOROU、ORIHIME、SAYA三只朱鹮正在各自的辖区巡逻，千万别把她们当成普通的鸟类。」</span><br>";
    }elseif($fnumber == 219){
        $log .= "<span class=\"lime\">「从2006年的 BRA 简体版 V3.0.2 到如今的常磐大逃杀，这套PHP代码甚至比屏幕前的一部分玩家年纪还要大。」</span><br>";
    }elseif($fnumber == 220){
        $log .= "<span class=\"lime\">「钓鱼吧老哥熟练地甩出了鱼竿，结果钓上来了一条除了头之外什么都可以吃的神奇生物。」</span><br>";
    }elseif($fnumber == 221){
        $log .= "<span class=\"lime\">「真职人221递给了你一杯温热的『荼荼丸的茶』，你抿了一口，感觉浑身舒畅！」</span><br>";
        $hp = min($mhp, $hp + 22);
        $sp = min($msp, $sp + 21);
    }elseif($fnumber == 222){
        $log .= "<span class=\"lime\">「２２２！三只小黄鸭排成一排游过了清水池，除了嘎嘎叫之外什么也没留下。」</span><br>";
    }elseif($fnumber == 223){
        $log .= "<span class=\"lime\">「汽车人，变形，出发！……咔嚓一声，你的闪避姿态好像稍微扭到了腰。」</span><br>";
    }elseif($fnumber == 224){
        $log .= "<span class=\"lime\">「ТΨПΨПΗΨТ ПΑЫЙΨ, ТΨПΨПΗΨТ ИЦ. ——这是残留在幻境深处的低语，只有懂得种火之语的人才能解读。」</span><br>";
    }elseif($fnumber == 225){
        $log .= "<span class=\"lime\">「十五的平方是225！十五的月亮十六圆，但幻境里的月亮有时候会因为异变而永远停在午夜。」</span><br>";
    }elseif($fnumber == 226){
        $log .= "<span class=\"lime\">「改了半天网页模板却发现页面毫无变化？先去看看是不是忘了清 gamedata/templates/ 里的编译缓存吧。」</span><br>";
    }elseif($fnumber == 227){
        $log .= "<span class=\"lime\">「用 22 除以 7 约等于圆周率，所以今天非常适合吃一张圆形的披萨或者面糊饼。」</span><br>";
    }elseif($fnumber == 228){
        $log .= "<span class=\"lime\">「泰酷辣！你突然有一种想要一拳砸碎『😂我太酷啦！😂』按钮并化作漫天烟花的冲动，好在理智及时按住了你的手。」</span><br>";
    }elseif($fnumber == 229){
        $log .= "<span class=\"lime\">「只要我不看判定点，我就永远不会撞到弹幕上！——某位抱头蹲防的东方玩家留下的遗言。」</span><br>";
    }elseif($fnumber == 230){
        $log .= "<span class=\"lime\">「奥利给，干了兄弟们！虽然眼前的补给品看起来颜色十分可疑，但只要闭上眼睛咽下去就是好补给！」</span><br>";
    }elseif($fnumber == 231){
        $log .= "<span class=\"lime\">「看，这个男人叫小帅，他刚刚在常磐台中学捡到了一个可疑的箱子，殊不知危险正在悄悄逼近……」</span><br>";
    }elseif($fnumber == 232){
        $log .= "<span class=\"lime\">「如果把两片抹了黄油的吐司背靠背绑在猫背上扔出去，就能制造出一台悬浮在半空中的永动机！」</span><br>";
    }elseif($fnumber == 233){
        $log .= "<span class=\"lime\">「２３３３３３！来自上古AcFun的欢笑声环绕着你，连空气中的恶意都消散了不少！」</span><br>";
        $rp = max(0, $rp - 23);
    }elseif($fnumber == 234){
        $log .= "<span class=\"lime\">「All your base are belong to us! 你献祭了一朵黑莲花，但这里没有三点任意颜色的法力，只有满地的电子元件。」</span><br>";
    }elseif($fnumber == 235){
        $log .= "<span class=\"lime\">「铀-235正在你的脑海中进行安全的思维裂变，请勿将其装填进任何没有安全栓的土制武器中。」</span><br>";
    }elseif($fnumber == 236){
        $log .= "<span class=\"lime\">「↓↘→＋拳！波动拳虽然帅，但在没有搓招指令的网页里，你还是得老老实实点击『攻击』按钮。」</span><br>";
    }elseif($fnumber == 237){
        $log .= "<span class=\"lime\">「在某个闪灵般的旅馆里，237号房间千万不要随便推门进去；在幻境里，则是无月之影千万不要随便走进去。」</span><br>";
    }elseif($fnumber == 238){
        $log .= "<span class=\"lime\">「面对那个拎着纯金大剑动不动就要核弹同归于尽的疯子，连操纵概率的DIAMOND都不得不感慨：『赢得越多，算力亏得越惨。』」</span><br>";
    }elseif($fnumber == 239){
        $log .= "<span class=\"lime\">「你感觉背后有一道锐利的视线正在扫视你的数据源——别紧张，那只是幻境自检程序路过而已。」</span><br>";
    }elseif($fnumber == 240){
        $log .= "<span class=\"lime\">「一天有24小时，一小时有60分钟，但当你残血踩中陷阱的时候，人生走马灯只需要0.24秒就能播完。」</span><br>";
    }elseif($fnumber == 241){
        $log .= "<span class=\"lime\">「买一送二叫241，但如果你在幻境里看到『买一送二』的陷阱礼盒，那通常意味着你会连续挨炸三次。」</span><br>";
    }elseif($fnumber == 242){
        $log .= "<span class=\"lime\">「如果哥谭市的小丑遇到了沈阳大街的狠活，蝙蝠侠大概也只能默默掏出手机给他们刷个火箭了。」</span><br>";
    }elseif($fnumber == 243){
        $log .= "<span class=\"lime\">「贝尔·格里尔斯告诉你：去掉头之后，这个不明物体富含蛋白质，鸡肉味，嘎嘣脆，热量是牛肉的四倍！」</span><br>";
    }elseif($fnumber == 244){
        $log .= "<span class=\"lime\">「醒醒吧武士，我们还有一座幻境要烧——前提是你的显卡和浏览器没有先一步崩溃。」</span><br>";
    }elseif($fnumber == 245){
        $log .= "<span class=\"lime\">「生而为人，我很抱歉……算了，满地都是补给和武器，还是先把对面锤爆再继续网抑云吧！」</span><br>";
    }elseif($fnumber == 246){
        $log .= "<span class=\"lime\">「豪有根！升龙拳！就算对手飞在天上，只要判定框够大就一定能打下来！」</span><br>";
    }elseif($fnumber == 247){
        $log .= "<span class=\"lime\">「『咕，杀了我吧！』——面对败北女骑士的经典台词，对方不慌不忙地掏出了一锅热腾腾的咖喱饭。」</span><br>";
    }elseif($fnumber == 248){
        $log .= "<span class=\"lime\">「臭名昭著的B.I.G提醒您：有些家伙哪怕死掉了，留下的怨念也会化作更加难缠的怪物追着你咬！」</span><br>";
    }elseif($fnumber == 249){
        $log .= "<span class=\"lime\">「✦复燃的烽火在远处静静闪烁，每一次读档与重开，都是一段崭新的轮回。」</span><br>";
    }elseif($fnumber == 250){
        $log .= "<span class=\"lime\">「２５０！虽然听起来稍微有点傻里傻气，但莽夫的快乐往往就是这么朴实无华！」</span><br>";
        $att += 2;
        $def += 5;
    }elseif($fnumber == 251){
        $log .= "<span class=\"lime\">「伐木挑战者今天也在常磐森林里辛勤砍树，试图用木棍合成出传说中的终极神器。」</span><br>";
    }elseif($fnumber == 252){
        $log .= "<span class=\"lime\">「宝可梦努力值的单项上限是252，看来你的某项特训已经练到头啦！」</span><br>";
    }elseif($fnumber == 253){
        $log .= "<span class=\"lime\">「老婆让程序员去买五个苹果，如果看到红薯就买一个——最后程序员拎着一个苹果回来了。」</span><br>";
    }elseif($fnumber == 254){
        $log .= "<span class=\"lime\">「距离无符号八位整数的悬崖边缘只差最后一步了，千万别再往前迈哪怕半个像素！」</span><br>";
    }elseif($fnumber == 255){
        $log .= "<span class=\"lime\">「２５５！幻境的地图编号上限是255，因为数据库的 pls 字段是 tinyint unsigned——谁要是敢走到第256号地图，就会直接溢出回老家！」</span><br>";
    }elseif($fnumber == 256){
        $log .= "<span class=\"lime\">「２５６！这是第二梦之数值的极值，２的８次方在此刻完美闭合！你的体力上限得到了滋养！」</span><br>";
        $msp += 16;
        $sp += 16;
    }elseif($fnumber == 257){
        $log .= "<span class=\"lime\">「DOMO，挑战者=SAN，挑战挑战者者DESU！凡是名字里带『挑战者』的家伙，通通吃我一记空手道啦！」</span><br>";
    }elseif($fnumber == 258){
        $log .= "<span class=\"lime\">「给岁月以文明，而不是给文明以岁月——但首先，请给你的背包留出一个空位。」</span><br>";
    }elseif($fnumber == 259){
        $log .= "<span class=\"lime\">「冻鳗婆罗门推了推鼻梁上的反光眼镜：『现在的年轻人居然连2009年ACBRA时代的古早梗都不知道了吗？』」</span><br>";
    }elseif($fnumber == 260){
        $log .= "<span class=\"lime\">「只要初动率达到100%，我就绝对不会卡手！……然后你起手摸到了五张手坑。」</span><br>";
    }elseif($fnumber == 261){
        $log .= "<span class=\"lime\">「随机数眷顾者拍了拍你的肩膀：『相信我，下一次掷骰子一定能出满值，如果没出，就把这句话再读一遍。』」</span><br>";
    }elseif($fnumber == 262){
        $log .= "<span class=\"lime\">「S.G.G.K——超·伟大的守门员！只要守在禁区门口，连飞来的导弹都能用脸接住！」</span><br>";
    }elseif($fnumber == 263){
        $log .= "<span class=\"lime\">「所有的这些时刻，终将流失在时光中，一如眼泪消失在雨中……是时候去搜下一个地点了。」</span><br>";
    }elseif($fnumber == 264){
        $log .= "<span class=\"lime\">「『…………』脑海中的声音沉默了整整三秒钟，似乎刚刚忘词了。」</span><br>";
    }elseif($fnumber == 265){
        $log .= "<span class=\"lime\">「远古传说：当年士兵丁曾单枪匹马出征265g，为大逃杀玩家立下赫赫战功。」</span><br>";
    }elseif($fnumber == 266){
        $log .= "<span class=\"lime\">「鼠鼠我啊，今天在幻境里捡到了一块奶酪，还没来得及啃一口就被路过的触手大佬顺手扬了捏。」</span><br>";
    }elseif($fnumber == 267){
        $log .= "<span class=\"lime\">「世上最可怕的四件事：地震、雷鸣、火灾、还有拎着高爆武器到处找人的老爹。」</span><br>";
    }elseif($fnumber == 268){
        $log .= "<span class=\"lime\">「谜语：凌晨三点十四分的微弱信号仍在湾城图书馆的旧终端里闪烁，D盘的归档大门紧闭，想要钥匙的话，就去问问『M.』吧。」</span><br>";
    }elseif($fnumber == 269){
        $log .= "<span class=\"lime\">「你的钻头，可是能够突破天际的钻头啊！哪怕前面是英灵殿的大门也照样钻开给你看！」</span><br>";
    }elseif($fnumber == 270){
        $log .= "<span class=\"lime\">「秋之镇的落叶里藏着烤红薯的香气，如果玩累了的话，坐下来歇一歇也没关系哦。」</span><br>";
    }elseif($fnumber == 271){
        $log .= "<span class=\"lime\">「稀神老仙，法力无边！他在看了一眼你的面板之后断言：『这波你绝对死定了，飞龙骑脸怎么输？』——恭喜你，这把稳了！」</span><br>";
    }elseif($fnumber == 272){
        $log .= "<span class=\"lime\">「和田町研究所里传来了咚咚咔咔的敲鼓声，达人难度的谱面正在向你的手速发起挑战！」</span><br>";
    }elseif($fnumber == 273){
        $log .= "<span class=\"lime\">「零下273.15摄氏度是绝对零度，但某些冷笑话的降温效果显然已经突破了这个物理学下限。」</span><br>";
    }elseif($fnumber == 274){
        $log .= "<span class=\"lime\">「I am the bone of my code……欢迎来到冴月麟的『Unlimited Code Works』！」</span><br>";
    }elseif($fnumber == 275){
        $log .= "<span class=\"lime\">「不管是满汉全席还是发光的料理，进了肚子里统统都只是加HP和SP的数字罢了！」</span><br>";
    }elseif($fnumber == 276){
        $log .= "<span class=\"lime\">「如果一只鸽子咕咕叫了三次却依然没有起飞，那它大概率不是鸽子，而是正在写更新日志的开发者。」</span><br>";
    }elseif($fnumber == 277){
        $log .= "<span class=\"lime\">「在月球表面打过仗的老兵告诉你：在低重力环境下扔手雷，小心手雷绕月球一圈从背后炸到自己。」</span><br>";
    }elseif($fnumber == 278){
        $log .= "<span class=\"lime\">「只要心中有海，哪怕在花菱商厦的喷水池里也能钓上深海大鱼！」</span><br>";
    }elseif($fnumber == 279){
        $log .= "<span class=\"lime\">「在下雨天撑起一把阳伞，说不定会从伞骨里掉出一只喜欢吓人的唐伞妖怪哦。」</span><br>";
    }elseif($fnumber == 280){
        $log .= "<span class=\"lime\">「远古时代的A站首页曾挂着通往这里的神秘链接，于是一代又一代的挑战者便顺着网线掉进了兔子洞。」</span><br>";
    }elseif($fnumber == 281){
        $log .= "<span class=\"lime\">「即使过去了二十年，当你翻开底层源码时，依然能看到Azazil等远古先驱留下的代码印记。」</span><br>";
    }elseif($fnumber == 282){
        $log .= "<span class=\"lime\">「银色盒子的触手从虚空中探出了一角，轻轻戳了戳你的脑门，又缩了回去。」</span><br>";
    }elseif($fnumber == 283){
        $log .= "<span class=\"lime\">「『今天中午吃什么？』『随便。』——于是幻境餐厅为您端上了一盘新鲜出炉的『★随便★』。」</span><br>";
    }elseif($fnumber == 284){
        $log .= "<span class=\"lime\">「古明地恋今天也在你的身后对你微笑——等等，你回头看了看，身后明明什么人都没有啊？」</span><br>";
    }elseif($fnumber == 285){
        $log .= "<span class=\"lime\">「你以为角色死亡就是结束了吗？只要把意识上传成550W里的数字生命，下一局你依然是一条好汉！」</span><br>";
    }elseif($fnumber == 286){
        $log .= "<span class=\"lime\">「从古老的80286处理器到今天的量子计算机，随机数永远是人类最爱又最恨的发明。」</span><br>";
    }elseif($fnumber == 287){
        $log .= "<span class=\"lime\">「欢迎光临黄昏酒场，本店今日特供『吞食天地』套餐，吃完包您忘却一切大逃杀的烦恼。」</span><br>";
    }elseif($fnumber == 288){
        $log .= "<span class=\"lime\">「『这瓜保熟吗？』『我开水果摊的，能卖给你生瓜蛋子？』——小心，这颗西瓜可能是爆系武器！」</span><br>";
    }elseif($fnumber == 289){
        $log .= "<span class=\"lime\">「十七的平方是289！永远年轻，永远十七岁——哪怕后面写着『十七岁零两百个月』。」</span><br>";
    }elseif($fnumber == 290){
        $log .= "<span class=\"lime\">「地底的核融合炉正在轰鸣，小心别把地狱鸦的羽毛和放射性同位素混在一起炖汤。」</span><br>";
    }elseif($fnumber == 291){
        $log .= "<span class=\"lime\">「内陆帝国在你耳边低语：这枚骰子有着自己的灵魂，而且它现在正在用看猴子的眼神看着你。」</span><br>";
    }elseif($fnumber == 292){
        $log .= "<span class=\"lime\">「除错模式的最高境界：不是把BUG修好，而是把触发BUG的操作直接写进游戏说明书里当成隐藏特性！」</span><br>";
    }elseif($fnumber == 293){
        $log .= "<span class=\"lime\">「红魔馆的门番今天也在门口站着睡大觉，哪怕你从她面前大摇大摆地走过去，她也只会回你一个呼噜。」</span><br>";
    }elseif($fnumber == 294){
        $log .= "<span class=\"lime\">「你在SCP-294自动售货机的键盘上输入了『一杯必胜的运气』，机器嗡嗡响了两声，吐出了一杯白开水。」</span><br>";
    }elseif($fnumber == 295){
        $log .= "<span class=\"lime\">「码符『终极BUG·拉电闸』！只要拔掉服务器的电源线，世界上就没有任何人能击败你了！」</span><br>";
    }elseif($fnumber == 296){
        $log .= "<span class=\"lime\">「地狱三头犬的三个脑袋正在为晚饭吃狗粮还是吃挑战者争论不休，最终它们决定石头剪刀布——可惜它们只有爪子。」</span><br>";
    }elseif($fnumber == 297){
        $log .= "<span class=\"lime\">「毛玉滚过去，毛玉滚过来，毛玉把你的鞋带系成了一个死结。」</span><br>";
    }elseif($fnumber == 298){
        $log .= "<span class=\"lime\">「这是来自298SP4时代的远古回响，那时候的天空还是纯文本的颜色。」</span><br>";
    }elseif($fnumber == 299){
        $log .= "<span class=\"lime\">「距离三百大关只差临门一脚！这种感觉就像经验条卡在99.9%偏偏刷不出怪一样难受。」</span><br>";
    }elseif($fnumber == 300){
        $log .= "<span class=\"lime\">「ＴＨＩＳ　ＩＳ　ＳＰＡＲＴＡ！三百勇士的战吼在你的胸腔中回荡，你的攻防略微提升了！」</span><br>";
        $att += 3;
        $def += 3;
    }elseif($fnumber == 301){
        $log .= "<span class=\"lime\">「３０１　Ｍｏｖｅｄ　Ｐｅｒｍａｎｅｎｔｌｙ！锁定解除，但你的逃跑路线已经被永久重定向到了禁区中心！」</span><br>";
    }elseif($fnumber == 302){
        $log .= "<span class=\"lime\">「３０２　Ｆｏｕｎｄ！永恒世界的住人今天也在花菱商厦里临时跳转，试图刷出一张通往核平结局的门票。」</span><br>";
    }elseif($fnumber == 303){
        $log .= "<span class=\"lime\">「篝火的引导在黑暗中亮起——别高兴得太早，在幻境里看到火光，有一半概率是篝火，另一半概率是别人刚引爆的炸药。」</span><br>";
    }elseif($fnumber == 304){
        $log .= "<span class=\"lime\">「『那种话最讨厌了！』——网页返回了 304 Not Modified，表示不管你点多少次刷新，地上那具尸体包里的钱都不会自己变多。」</span><br>";
    }elseif($fnumber == 305){
        $log .= "<span class=\"lime\">「不止是运气好而已！能在满地阔剑地雷的森林里毫发无伤地走完十步，你怕不是偷偷借走了Kiko的幸运值。」</span><br>";
    }elseif($fnumber == 306){
        $log .= "<span class=\"lime\">「核弹粉的终极浪漫：只要我手里的☢核子武器☢足够大，方圆十里之内就没有任何人能对我造成伤害——包括我自己！」</span><br>";
    }elseif($fnumber == 307){
        $log .= "<span class=\"lime\">「奇迹的篝火微微跳动，把一段本该报错崩溃的死循环，硬生生编译成了一曲温柔的晚安曲。」</span><br>";
    }elseif($fnumber == 308){
        $log .= "<span class=\"lime\">「清水池之王从水底探出头来，看了看你手里拿的塑料水枪，默默潜回水底并顺手给你记了一笔报应。」</span><br>";
    }elseif($fnumber == 309){
        $log .= "<span class=\"lime\">「不动的大图书馆今天也没有挪动半步，毕竟在没有抽风机的地下室里，光靠魔导书给服务器散热就已经够呛了。」</span><br>";
    }elseif($fnumber == 310){
        $log .= "<span class=\"lime\">「脚本小子满怀信心地按下了F12打开控制台，然后绝望地发现所有的伤害结算都在服务器端的PHP里。」</span><br>";
    }elseif($fnumber == 311){
        $log .= "<span class=\"lime\">「Run With Wolves！与狼共舞听起来很帅，但在幻境里与走地羊共舞只会让你的防具耐久掉得像瀑布一样快。」</span><br>";
    }elseif($fnumber == 312){
        $log .= "<span class=\"lime\">「谈笑风生之间，你的对手已经换上了全套神装，而你还在对着合成表研究怎么把面粉和水揉成面团。」</span><br>";
    }elseif($fnumber == 313){
        $log .= "<span class=\"lime\">「幻境的百万富翁看着账户里躺着的六位数存款，悲哀地意识到商店里最贵的切糕还是不收游戏币。」</span><br>";
    }elseif($fnumber == 314){
        $log .= "<span class=\"lime\">「３：１４　ＡＭ……湾城图书馆旧终端里的AKANE信号在此刻准时闪烁了一下，随即隐没在圆周率的无尽小数之中。」</span><br>";
    }elseif($fnumber == 315){
        $log .= "<span class=\"lime\">「ＦＩＲＳＴ　ＢＬＯＯＤ！在315消费者权益日这一天，你决定向商店老板投诉这把号称永不磨损的武器为什么砍了三刀就断了。」</span><br>";
    }elseif($fnumber == 316){
        $log .= "<span class=\"lime\">「幻境无双！当你一刀一个杂兵觉得自己天下无敌的时候，不妨抬头看看英灵殿门口那排金光闪闪的名字。」</span><br>";
    }elseif($fnumber == 317){
        $log .= "<span class=\"lime\">「系统提示：成就『聚集的祈愿』已被标记为 ABANDONED，就像你去年年初立下的减肥计划一样再也没有后续了。」</span><br>";
    }elseif($fnumber == 318){
        $log .= "<span class=\"lime\">「风祭之旅的导游提醒您：在森林里遇到穿白大褂的研究员或者拿柴刀的少女时，请保持礼貌微笑并迅速转身离开。」</span><br>";
    }elseif($fnumber == 319){
        $log .= "<span class=\"lime\">「谜语：白凰展翅于棋盘的炮位，却从不亲自跨越楚河；唯有借一枚看不见的火雷为架，方能轰碎千里之外的坚城。」</span><br>";
    }elseif($fnumber == 320){
        $log .= "<span class=\"lime\">「如果把三百二十只尖叫鸡同时塞进电梯轿厢里，电梯会不会因为承受不住高浓度噪音而直接升入太空？」</span><br>";
    }elseif($fnumber == 321){
        $log .= "<span class=\"lime\">「３、２、１——发射！倒计时结束之后什么都没有发生，看来点错火的是隔壁房间的爆系挑战者。」</span><br>";
    }elseif($fnumber == 322){
        $log .= "<span class=\"lime\">「３２２！DOTA圈流传千古的假赛圣数！系统怀疑你正在偷偷打假赛，但因为找不到证据只好作罢。」</span><br>";
    }elseif($fnumber == 323){
        $log .= "<span class=\"lime\">「红杀暗杀教头『绯刹·SHI』摇了摇身后纯属装饰的小恶魔尾巴：『只要在九宫格里潜伏得够深，就没有双匕首抹不掉的咽喉。』」</span><br>";
    }elseif($fnumber == 324){
        $log .= "<span class=\"lime\">「『哼，我还可以变身两次！』——话还没说完，路过的挑战者就趁着变身动画的前摇把BOSS揍扁了。」</span><br>";
    }elseif($fnumber == 325){
        $log .= "<span class=\"lime\">「常磐的训练师扔出了精灵球！球晃了三下之后啪地弹开，里面跳出来的居然是扛着火箭筒的皮卡丘！」</span><br>";
    }elseif($fnumber == 326){
        $log .= "<span class=\"lime\">「为什么 gamedata/cache/ 目录下的 .php 文件打开全是用逗号分隔的表格？因为当年写代码的人觉得手搓CSV解析器比建数据库表省事多了！」</span><br>";
    }elseif($fnumber == 327){
        $log .= "<span class=\"lime\">「你在路边捡到了一本《海浒》，翻开第一页写着：『话说天下大势，合久必分，今天先吃我一记十万匹海虎爆破拳！』」</span><br>";
    }elseif($fnumber == 328){
        $log .= "<span class=\"lime\">「３２８！手游氪金礼包最爱的数字！好在常磐大逃杀没有首充双倍，否则你现在已经倾家荡产了。」</span><br>";
    }elseif($fnumber == 329){
        $log .= "<span class=\"lime\">「红杀火器教头『灵翼·Bishop』扶了扶头顶的金属光环，将一枚特种弹推入十字长柄狙击枪：『斜线之上的猎物，从无生还之理。』」</span><br>";
    }elseif($fnumber == 330){
        $log .= "<span class=\"lime\">「每天下午三点三十分，饮茶先啦！做人最紧要就是开心，打打杀杀什么的等喝完这杯冻柠茶再说！」</span><br>";
    }elseif($fnumber == 331){
        $log .= "<span class=\"lime\">「不给糖就解禁！万圣节的南瓜灯在常磐森林里一字排开，里面装的不是蜡烛而是微型高爆地雷。」</span><br>";
    }elseif($fnumber == 332){
        $log .= "<span class=\"lime\">「谜语：古董提灯的火苗连一缕微风都未曾惊动，提灯的哥特少女却已踏出三步，将纵横斜向的死路尽数踏遍。」</span><br>";
    }elseif($fnumber == 333){
        $log .= "<span class=\"lime\">「３３３！三三得九，九九归一！黄金三连号的共鸣让你浑身舒泰，生命与体力各恢复了33点！」</span><br>";
        $hp = min($mhp, $hp + 33);
        $sp = min($msp, $sp + 33);
    }elseif($fnumber == 334){
        $log .= "<span class=\"lime\">「３３－４！为什么呢？明明已经是九局下半两出局了，为什么还会提起这串让阪神虎球迷血压飙升的数字呢？」</span><br>";
    }elseif($fnumber == 335){
        $log .= "<span class=\"lime\">「红杀拳脚教头『铁城·Rook』身高一米九、背后背着一把门板大的巨剑——但他一辈子都没拔过剑，因为戴上钨钢指虎一拳下去比砍人痛多了。」</span><br>";
    }elseif($fnumber == 336){
        $log .= "<span class=\"lime\">「你在蹲站观察房的书架上抽出一本《肘术肥战》，封面上画着两个满身横肉的壮汉正在展开领域『伏魔御厨子·自助烤肉店』。」</span><br>";
    }elseif($fnumber == 337){
        $log .= "<span class=\"lime\">「鼓掌的节奏是３－３－７！虽然不知道为什么要为你鼓掌，但虚空中的观众席确实响起了整齐划一的掌声。」</span><br>";
    }elseif($fnumber == 338){
        $log .= "<span class=\"lime\">「真男人，结构抗！哪怕护盾和装甲已经被打得连渣都不剩，只要最后一层结构值还没归零，飞船就绝对不会爆！」</span><br>";
    }elseif($fnumber == 339){
        $log .= "<span class=\"lime\">「冻鳗婆罗门轻蔑地看了一眼你的背包：『连八十年代OVA的冷门武器合成公式都背不下来，也敢自称二次元？』」</span><br>";
    }elseif($fnumber == 340){
        $log .= "<span class=\"lime\">「如果把路由器放进微波炉里加热三十秒，发出来的Wi-Fi信号会不会自带孜然烤肉的香味？」</span><br>";
    }elseif($fnumber == 341){
        $log .= "<span class=\"lime\">「红杀爆炸物教头『银锤·Pawn』微笑着放下一只发条玩偶：『过河的卒子从不回头，因为它走过的每一步都会炸成绚烂的舞台。』」</span><br>";
    }elseif($fnumber == 342){
        $log .= "<span class=\"lime\">「为了忘却的纪念——有些名字留在了旧版本的更新日志里，有些名字则永远留在了常磐的墓碑上。」</span><br>";
    }elseif($fnumber == 343){
        $log .= "<span class=\"lime\">「这是七的三次方！343 Guilty Spark 闪着蓝光从你头顶飘过，嘴里嘟囔着什么『启动光环就能消灭一切虫族』。」</span><br>";
    }elseif($fnumber == 344){
        $log .= "<span class=\"lime\">「红杀冷兵器教头『破石·Knight』推了推圆框眼镜，用考古放大镜仔细端详着你的防具：『第344号纹理裂隙……很好，一刀就能劈开。』」</span><br>";
    }elseif($fnumber == 345){
        $log .= "<span class=\"lime\">「３、４、５！最标准的勾股定理直角三角形！毕达哥拉斯对你的走位几何学表示高度赞赏。」</span><br>";
    }elseif($fnumber == 346){
        $log .= "<span class=\"lime\">「欢迎来到346 Production！灰姑娘的魔法在午夜十二点就会失效，但在幻境里，午夜十二点通常是禁区连环爆炸的时间。」</span><br>";
    }elseif($fnumber == 347){
        $log .= "<span class=\"lime\">「红杀信息技术教头『电反·King』提着女仆裙角微微欠身，身旁两只发条蒸汽羊咩咩叫着接管了方圆五里的全部通讯中继。」</span><br>";
    }elseif($fnumber == 348){
        $log .= "<span class=\"lime\">「你在书架角落翻出了一本《水星的魔法少女》，里面的主角正开着十几米高的浮游炮机甲把对手轰成番茄酱，并坚称这是正统魔法。」</span><br>";
    }elseif($fnumber == 349){
        $log .= "<span class=\"lime\">「谜语：在BASSLINE那片死寂的废土上，前代的信息之王早已踏中地雷化作尘土，只留下一瓶写着『因果断』的苦药与两个流亡的女儿。」</span><br>";
    }elseif($fnumber == 350){
        $log .= "<span class=\"lime\">「尼斯湖水怪从清水池里探出脑袋，礼貌地问你能不能借它三块五毛钱（Tree Fiddy）。」</span><br>";
    }elseif($fnumber == 351){
        $log .= "<span class=\"lime\">「０ｘ５ｆ３７５９ｄｆ！雷神之锤源码里的快速平方根倒数魔数在你脑中一闪而过，连注释里的『what the fuck?』都转化为了你的爆系与灵系灵感！」</span><br>";
        $wd += 5;
        $wf += 3;
    }elseif($fnumber == 352){
        $log .= "<span class=\"lime\">「中文房间里的西尔勒正在疯狂翻阅字典，试图搞清楚门外塞进来的纸条上写的『V我50』到底是什么意思。」</span><br>";
    }elseif($fnumber == 353){
        $log .= "<span class=\"lime\">「图灵测试考官向对面的BOT提问：『请问草莓里有几个r？』BOT沉思了两秒，反手给了考官一记暴风60连击。」</span><br>";
    }elseif($fnumber == 354){
        $log .= "<span class=\"lime\">「停机问题告诉我们：没有任何一段程序能提前判断你这一局到底是会满装吃鸡，还是会在三秒钟后踩雷暴毙。」</span><br>";
    }elseif($fnumber == 355){
        $log .= "<span class=\"lime\">「『Say hello to my little friend!』——你最棒的朋友不是什么小猫咪，而是一把装满弹药的重机枪！」</span><br>";
    }elseif($fnumber == 356){
        $log .= "<span class=\"lime\">「在没有IDE自动补全的年代，手写PHP最刺激的事情莫过于把 \$itmsk1 打成了 \$itmk1，然后眼睁睁看着玩家的物品属性变成了物品类别。」</span><br>";
    }elseif($fnumber == 357){
        $log .= "<span class=\"lime\">「.357马格南左轮手枪的弹巢咔哒一声转到了底，午时已到——虽然看了看表现在明明是凌晨两点。」</span><br>";
    }elseif($fnumber == 358){
        $log .= "<span class=\"lime\">「G.D.S金龙通讯社的扫地大妈默默扫过走廊，顺手用竹扫帚拍飞了一枚迎面射来的穿甲弹，深藏功与名。」</span><br>";
    }elseif($fnumber == 359){
        $log .= "<span class=\"lime\">「距离转完一整个圆周只差最后１度！强迫症患者看着这个359度的扇形图，急得差点拿头撞显示器。」</span><br>";
    }elseif($fnumber == 360){
        $log .= "<span class=\"lime\">「３６０度托马斯回旋加速无死角原地起跳——然后你稳稳地落回了刚才起跳的同一个格子里。」</span><br>";
    }elseif($fnumber == 361){
        $log .= "<span class=\"lime\">「十九乘十九等于361，围棋盘上一共有361个交叉点，但柯洁来了也算不出你下一步搜索会撞见什么怪物。」</span><br>";
    }elseif($fnumber == 362){
        $log .= "<span class=\"lime\">「G.D.S的项目经理在凌晨三点给你发来了夺命连环催：『甘特图呢？里程碑呢？为什么这局大逃杀还没有按排期准时结束？！』」</span><br>";
    }elseif($fnumber == 363){
        $log .= "<span class=\"lime\">「你翻开了那本名为《游气王》的奇书，里面的决斗者们一旦抽不到想要的卡，就会直接撸起袖子用物理拳脚决一胜负。」</span><br>";
    }elseif($fnumber == 364){
        $log .= "<span class=\"lime\">「究极逃杀生命体站在常磐之巅迎着朝阳展开双翼——然后因为飞得太高直接触发了高空禁区判定！」</span><br>";
    }elseif($fnumber == 365){
        $log .= "<span class=\"lime\">「一年３６５天，幻境『年中无休（笑）』地运转着，哪怕是除夕夜也有人在常磐台中学熬夜搓合成。」</span><br>";
    }elseif($fnumber == 366){
        $log .= "<span class=\"lime\">「四年一度的闰年多出来的那一天被塞进了你的脑海里，你感觉今天的时间好像比别人稍微宽裕了一丢丢。」</span><br>";
    }elseif($fnumber == 367){
        $log .= "<span class=\"lime\">「G.D.S的摸鱼网管打了个哈欠，在面对顶级黑客入侵时使出了绝学——直接伸手拔掉了主交换机的网线。」</span><br>";
    }elseif($fnumber == 368){
        $log .= "<span class=\"lime\">「谜语：踏雪的研究员『七星』在屏幕前敲下严谨的论文，而在虚拟的战场上，一米七二的猫耳战士正挥舞着藤蔓缠绕的北斗重兵。」</span><br>";
    }elseif($fnumber == 369){
        $log .= "<span class=\"lime\">「三六九，往外走！老话说明天是个出门搜刮的好日子，前提是你别一出门就撞上巡逻的真职人。」</span><br>";
    }elseif($fnumber == 370){
        $log .= "<span class=\"lime\">「如果你把耳朵贴在冰冷的机箱侧面仔细倾听，就能听见CPU里面的几十亿个晶体管正在合唱《大悲咒》。」</span><br>";
    }elseif($fnumber == 371){
        $log .= "<span class=\"lime\">「G.D.S的女秘书推了推眼镜，面对新入职员工的震惊脸淡定地回答：『怎么，没人告诉过你我们金龙通讯社的总裁是女生吗？』」</span><br>";
    }elseif($fnumber == 372){
        $log .= "<span class=\"lime\">「你在书架最底层发现了一本《東埔寨地灵殿》，翻开一看全是各种防不胜防的地雷阵设防指南。」</span><br>";
    }elseif($fnumber == 373){
        $log .= "<span class=\"lime\">「『领导让我当法人！』——年轻的背锅侠一脸悲壮地签下了自己的名字，随即抡起一口纯铁大黑锅向敌人迎头砸去！」</span><br>";
    }elseif($fnumber == 374){
        $log .= "<span class=\"lime\">「歪嘴龙王邪魅一笑，三年之期已到，恭迎龙王回归常磐——然后龙王刚出诊所大门就被路人一闷棍敲晕了。」</span><br>";
    }elseif($fnumber == 375){
        $log .= "<span class=\"lime\">「一瓶标准装的药水正好是375毫升，多喝一滴怕撑，少喝一滴怕亏。」</span><br>";
    }elseif($fnumber == 376){
        $log .= "<span class=\"lime\">「然后，睡一会儿吧……只要在睡眠状态下缓慢恢复生命，天塌下来也可以当被子盖。」</span><br>";
    }elseif($fnumber == 377){
        $log .= "<span class=\"lime\">「斐波那契数列的第14项是377！黄金螺旋的奥秘在你的指尖流转，可惜并不能让你扔出去的石头自动追踪。」</span><br>";
    }elseif($fnumber == 378){
        $log .= "<span class=\"lime\">「你在地摊上捡到了一瓶『二零醚』，说明书上严肃地写着：『剧毒化学品，分子式 H2O，饮用后可缓解口渴。』」</span><br>";
    }elseif($fnumber == 379){
        $log .= "<span class=\"lime\">「ＤＵＲＯ！ＭＯＮＳＴＡ　ＫＡＤＯ！狂战士之魂彻底觉醒，哪怕牌组里只剩最后一张怪兽卡，你也决定把羽蛾鞭尸到天亮！」</span><br>";
    }elseif($fnumber == 380){
        $log .= "<span class=\"lime\">「空客A380双层大客机从常磐上空轰鸣掠过，机长广播提醒各位乘客：跳伞前请先确认脚下不是无月之影。」</span><br>";
    }elseif($fnumber == 381){
        $log .= "<span class=\"lime\">「『听我唱歌吧——！』热气巴萨拉抱着吉他在炮火连天的战场中央强行开唱，连飞来的导弹都被歌声感动得偏离了航线！」</span><br>";
    }elseif($fnumber == 382){
        $log .= "<span class=\"lime\">「扫黑除恶专项行动正在幻境全面展开，凡是名字里带黑恶势力头衔的NPC，今天见一个抓一个！」</span><br>";
    }elseif($fnumber == 383){
        $log .= "<span class=\"lime\">「谜语：三十三岁的死鱼眼大叔与十四岁的毒舌幽灵共用着同一具躯壳，当粉色的霓虹兔耳在胡茬大叔头顶亮起时，请千万捂好你的眼睛。」</span><br>";
    }elseif($fnumber == 384){
        $log .= "<span class=\"lime\">「月球到地球的平均距离是38.4万公里，但从满血状态到黑白遗照的距离，往往只需要一次ミス（Miss）。」</span><br>";
    }elseif($fnumber == 385){
        $log .= "<span class=\"lime\">「你在自动售货机里买了一罐『正氧烷』，喝了一口才反应过来：这不就是花五块钱买了一罐纯水吗？！」</span><br>";
    }elseif($fnumber == 386){
        $log .= "<span class=\"lime\">「妮梦雷莉娅的寝姬楼甜美地塌陷着，少女在塞满点心的巨大床铺上翻了个身，把你的噩梦当成曲奇饼干一口吃掉了。」</span><br>";
    }elseif($fnumber == 387){
        $log .= "<span class=\"lime\">「『你见过凌晨四点的洛杉矶吗？』——没见过，但我见过凌晨四点还在常磐大逃杀里连开八局的修仙玩家。」</span><br>";
    }elseif($fnumber == 388){
        $log .= "<span class=\"lime\">「双八发财，三八开泰！虽然没有真的掉钱下来，但你总觉得下一个箱子里一定藏着好东西。」</span><br>";
    }elseif($fnumber == 389){
        $log .= "<span class=\"lime\">「《心灵阿尔法》的封面上印着一个光头心灵控制专家，据说只要看久了封面，你就会不由自主地想造尤里复制中心。」</span><br>";
    }elseif($fnumber == 390){
        $log .= "<span class=\"lime\">「为什么同样是写PHP，别人的项目叫『优雅的现代微服务架构』，我们的项目叫『会动的2006年古董博物馆』？」</span><br>";
    }elseif($fnumber == 391){
        $log .= "<span class=\"lime\">「『不站队、不赊账、不致命、不卖人、不接烂活。』——霓虹魅影·默晚在接单前，总会先用死鱼眼把这五条规矩念上一遍。」</span><br>";
    }elseif($fnumber == 392){
        $log .= "<span class=\"lime\">「八八舰队计划的图纸在风中哗哗作响，可惜幻境里最大的水面只有一口小小的清水池，连驱逐舰都放不下。」</span><br>";
    }elseif($fnumber == 393){
        $log .= "<span class=\"lime\">「９９３……不对，是３９３！不管是做算术题还是拆炸弹，看错第一位数字都会引发极其惨烈的后果。」</span><br>";
    }elseif($fnumber == 394){
        $log .= "<span class=\"lime\">「『Turn to page 394.』——斯内普教授冷冷地合上课本，示意你今天这堂课的内容是如何应对幻境里的狼人与吸血鬼。」</span><br>";
    }elseif($fnumber == 395){
        $log .= "<span class=\"lime\">「你小心翼翼地调配着『脱碳甲醛』与『零醇』，满心以为能合出什么剧毒炸弹，结果只得到了一杯温开水。」</span><br>";
    }elseif($fnumber == 396){
        $log .= "<span class=\"lime\">「俄罗斯方块掉下来的长条永远在你最不需要它的时候出现，而当你留好四行竖井苦苦等待时，它却去休年假了。」</span><br>";
    }elseif($fnumber == 397){
        $log .= "<span class=\"lime\">「谜语：玄武湖面的垂柳倒映着悠闲的游船，却无人知晓万顷碧波之下，正轰鸣着冷却半座湾城算力的深水机房。」</span><br>";
    }elseif($fnumber == 398){
        $log .= "<span class=\"lime\">「全场特价398，件件398！只要398，八八八万血的便当盒手办抱回家——当然是不可能的。」</span><br>";
    }elseif($fnumber == 399){
        $log .= "<span class=\"lime\">「距离四百大关只差最后一步！就像只差一点熟练度就能升阶一样，多摸一下哪怕是空气也能涨经验！」</span><br>";
    }elseif($fnumber == 400){
        $log .= "<span class=\"lime\">「４００　Ｂａｄ　Ｒｅｑｕｅｓｔ！服务器看了一眼你发过去的离谱指令，决定假装没听懂并驳回你的请求。」</span><br>";
    }elseif($fnumber == 401){
        $log .= "<span class=\"lime\">「４０１　Ｕｎａｕｔｈｏｒｉｚｅｄ！你想推开英灵殿的后门，门卫礼貌地提示您尚未获得管理员权限。」</span><br>";
    }elseif($fnumber == 402){
        $log .= "<span class=\"lime\">「『只要是活着的东西，就算是神也杀给你看！』——你眯起眼睛努力寻找死之线，结果因为近视度数太高只看到了屏幕上的灰尘。」</span><br>";
    }elseif($fnumber == 403){
        $log .= "<span class=\"lime\">「４０３　Ｆｏｒｂｉｄｄｅｎ！前方属于绝对禁区，连网页服务器都拒绝向你透露里面的半个字节。」</span><br>";
    }elseif($fnumber == 404){
        $log .= "<span class=\"lime\">「４０４　Ｎｏｔ　Ｆｏｕｎｄ！跑路大师发动了隐身术，顺便帮你把一部分报应点数也弄丢了！」</span><br>";
        $rp = max(0, $rp - 40);
    }elseif($fnumber == 405){
        $log .= "<span class=\"lime\">「疾走！疾走！只要我的移动速度够快，禁区爆炸的冲击波就永远追不上我的尾气！」</span><br>";
    }elseif($fnumber == 406){
        $log .= "<span class=\"lime\">「『解离？那不过是把全部的注意力集中到极致之后，顺手拨动一下空气里的水分子罢了，很简单吧？』——蓝凝」</span><br>";
    }elseif($fnumber == 407){
        $log .= "<span class=\"lime\">「红石工程师在常磐森林里铺了整整三公里的红石中继器，最后成功做出了一台能够自动给自己鼓掌的活塞机器。」</span><br>";
    }elseif($fnumber == 408){
        $log .= "<span class=\"lime\">「４０８　Ｒｅｑｕｅｓｔ　Ｔｉｍｅｏｕｔ！因为你思考人生的时间太长，对面的NPC已经打着哈欠去搜下一个地点了。」</span><br>";
    }elseif($fnumber == 409){
        $log .= "<span class=\"lime\">「侵略如火，不动如山！只要我蹲在角落里一动不动，路过的大佬就会以为我只是一棵长得很像玩家的 декоративная 盆栽。」</span><br>";
    }elseif($fnumber == 410){
        $log .= "<span class=\"lime\">「４１０　Ｇｏｎｅ！猛风吹过大地，刚才还摆在地上的补给品已经永远地从这个世界上消失了。」</span><br>";
    }elseif($fnumber == 411){
        $log .= "<span class=\"lime\">「谜语：连接大陆与东洲岛的天青大桥终日车流不息，而在钢铁斜拉索的腹腔深处，整座城市的『大脑』正静静俯瞰着众生。」</span><br>";
    }elseif($fnumber == 412){
        $log .= "<span class=\"lime\">「反演开始！当所有的伤害变成治疗、所有的治疗变成毒药时，喝下一瓶鹤顶红岂不是等于原地满血复活？」</span><br>";
    }elseif($fnumber == 413){
        $log .= "<span class=\"lime\">「『键来——！』键盘侠深吸一口气，十指在机械键盘上敲出残影，试图用一秒五喷的弹幕直接击穿对手的护甲！」</span><br>";
    }elseif($fnumber == 414){
        $log .= "<span class=\"lime\">「４１４　ＵＲＩ　Ｔｏｏ　Ｌｏｎｇ！你给自制武器起的名字实在太长了，连数据库的 varchar 字段都差点被你撑爆！」</span><br>";
    }elseif($fnumber == 415){
        $log .= "<span class=\"lime\">「你在实验室里发现了一桶标着『氢化脱磺硫酸』的不明液体，尝了一口，发现还是熟悉的白开水味。」</span><br>";
    }elseif($fnumber == 416){
        $log .= "<span class=\"lime\">「『只要有我416在，你就什么都不需要担心了。』——少女拍了拍手中的突击步枪，眼神里写满了绝对的自信。」</span><br>";
    }elseif($fnumber == 417){
        $log .= "<span class=\"lime\">「系统提示：技能编号417『姨妈』虽然写在配置文件的注释里，但因为从来没有实装过，所以你也别指望它能触发什么效果。」</span><br>";
    }elseif($fnumber == 418){
        $log .= "<span class=\"lime\">「４１８　Ｉ＇ｍ　ａ　ｔｅａｐｏｔ！服务器理直气壮地拒绝了你泡咖啡的请求，因为它坚称自己是一只茶壶，而且正在赌博。」</span><br>";
    }elseif($fnumber == 419){
        $log .= "<span class=\"lime\">「修仙党看了一眼窗外渐渐发白的天空：『才国内凌晨四点而已，对海外党来说这不正是最精神的下午茶时间吗？』」</span><br>";
    }elseif($fnumber == 420){
        $log .= "<span class=\"lime\">「肉鸽挑战者推开了未知层的大门，发现门后还是熟悉的常磐森林。」</span><br>";
    }elseif($fnumber == 421){
        $log .= "<span class=\"lime\">「为什么 command.php 每次处理完指令都要小心翼翼地检查输出缓冲区？因为只要有一个文件不小心多打了一个空格，前端的JSON解析就会当场暴毙！」</span><br>";
    }elseif($fnumber == 422){
        $log .= "<span class=\"lime\">「４２２　Ｕｎｐｒｏｃｅｓｓａｂｌｅ　Ｅｎｔｉｔｙ！你的操作虽然符合语法，但实在太过抽象，连幻境规则都不知道该怎么结算了。」</span><br>";
    }elseif($fnumber == 423){
        $log .= "<span class=\"lime\">「Dr.Clef推了推帽檐，手里的霰弹枪咔嚓上膛：『不管是现实扭曲者还是幻境BOSS，没有什么是一发十二号口径解决不了的。』」</span><br>";
    }elseif($fnumber == 424){
        $log .= "<span class=\"lime\">「谜语：当手持黄纸的电子道士撞上双魂同调的地下黑客，两条底层权限在空气中陷入了死循环——然后他们双双蓝屏了。」</span><br>";
    }elseif($fnumber == 425){
        $log .= "<span class=\"lime\">「如果一只螃蟹学会了前后走而不是横着走，其他的螃蟹会不会怀疑它偷偷去报了芭蕾舞培训班？」</span><br>";
    }elseif($fnumber == 426){
        $log .= "<span class=\"lime\">「你把『氢化脱苄苯甲醇』和『氢氧酸』倒进同一个烧杯里摇晃了半天，恭喜你，你获得了一整杯纯净水！」</span><br>";
    }elseif($fnumber == 427){
        $log .= "<span class=\"lime\">「This is the story of a man named Stanley. 斯坦利走到了两扇门前，旁白建议他走左边那扇门，于是他毫不犹豫地跳进了垃圾桶。」</span><br>";
    }elseif($fnumber == 428){
        $log .= "<span class=\"lime\">「『为什么要演奏春日影？！』——伴随着一声痛彻心扉的质问，752号重力场轰然降临，在场所有人的身体瞬间沉重了十倍！」</span><br>";
    }elseif($fnumber == 429){
        $log .= "<span class=\"lime\">「４２９　Ｔｏｏ　Ｍａｎｙ　Ｒｅｑｕｅｓｔｓ！你的手速实在太快了，服务器怀疑你是连点器，并建议你先去喝口水歇一歇。」</span><br>";
    }elseif($fnumber == 430){
        $log .= "<span class=\"lime\">「卡尔·萨根说我们都是星尘，但幻境里的杂兵表示它们只是数据库里的一行临时记录。」</span><br>";
    }elseif($fnumber == 431){
        $log .= "<span class=\"lime\">「谜语：新金融核心区的地下有两层世界——上层是衣香鬓影的繁华商廊，下层则是废弃地铁与管廊交织的法外暗网。」</span><br>";
    }elseif($fnumber == 432){
        $log .= "<span class=\"lime\">「有人声称432赫兹的音乐蕴含着宇宙的治愈频率，但当对面拿着音响砸向你脑袋的时候，多少赫兹都一样痛。」</span><br>";
    }elseif($fnumber == 433){
        $log .= "<span class=\"lime\">「蛋服挑战者看了看自己一身毫无花哨技能的白板属性，自信满满地拎起铁棍冲向了满身神装的BOSS。」</span><br>";
    }elseif($fnumber == 434){
        $log .= "<span class=\"lime\">「宇宙C位敌法师正在野区疯狂刷钱，等他刷出狂战分身蝴蝶心……咦，基地怎么已经爆了？」</span><br>";
    }elseif($fnumber == 435){
        $log .= "<span class=\"lime\">「创世神大酒神拍了拍你的肩膀：『先出个提高属性的道具压压惊，这把听我的，包赢！』」</span><br>";
    }elseif($fnumber == 436){
        $log .= "<span class=\"lime\">「你在冰箱里发现了一盒标着『氧乙烯』的透明冰块，舔了一口，冰凉清爽，毕竟那本来就是普通冰块。」</span><br>";
    }elseif($fnumber == 437){
        $log .= "<span class=\"lime\">「『这波可以打，对面没大！』——然后你就眼睁睁看着队友在一秒钟之内被对面吃得连骨头都不剩。」</span><br>";
    }elseif($fnumber == 438){
        $log .= "<span class=\"lime\">「挽歌在风中轻轻奏响，鸽子们排成整齐的方阵从天空飞过，并在你的头顶投下了一枚精准制导的鸟粪。」</span><br>";
    }elseif($fnumber == 439){
        $log .= "<span class=\"lime\">「『把那个英雄给我Ban了！谁放出来谁背锅！』——著名选手龙弟弟在语音里发出了震耳欲聋的咆哮。」</span><br>";
    }elseif($fnumber == 440){
        $log .= "<span class=\"lime\">「父爱如山！末日使者向你张开了温暖的怀抱，在接下来的十五秒里，你将无法使用任何技能与道具，只能安详地罚站！」</span><br>";
    }elseif($fnumber == 441){
        $log .= "<span class=\"lime\">「药不能停！钱四爷仰头灌下一整瓶不明药剂，至于喝完之后是属性暴涨还是当场中毒，就全看造化了。」</span><br>";
    }elseif($fnumber == 442){
        $log .= "<span class=\"lime\">「艺术就是派大星！EternaLEnVy的操作永远处于『人类看不懂的艺术』与『令人窒息的暴毙』两态叠加之中！」</span><br>";
    }elseif($fnumber == 443){
        $log .= "<span class=\"lime\">「４４３端口已开启！虽然上了HTTPS加密，但并不能阻止路过的电子狐偷偷看光你背包里的每一件底裤。」</span><br>";
    }elseif($fnumber == 444){
        $log .= "<span class=\"lime\">「『你吼那么大声干什么嘛！』——老队长的怒吼响彻全场，连对面的键盘都被震掉了两个键帽！」</span><br>";
    }elseif($fnumber == 445){
        $log .= "<span class=\"lime\">「千万别在公网服务器上随便暴露445端口，否则勒索病毒会比禁区广播更早一步锁死你的硬盘。」</span><br>";
    }elseif($fnumber == 446){
        $log .= "<span class=\"lime\">「谜语：寂山书院的古柏掩映着朗朗书声，可若将时空拨回那片崩坏的废土，这里却是旧红杀锤炼死士的修罗场。」</span><br>";
    }elseif($fnumber == 447){
        $log .= "<span class=\"lime\">「你在化学试剂柜的最后一格找到了『脱氰零醛肟』，不用怀疑，这依然是换了个马甲的H2O。」</span><br>";
    }elseif($fnumber == 448){
        $log .= "<span class=\"lime\">「如果把所有的全局变量都装进一个叫 \$GLOBALS 的大麻袋里，那么写PHP和圣诞老人送礼物其实没有任何区别。」</span><br>";
    }elseif($fnumber == 449){
        $log .= "<span class=\"lime\">「只差１点就到450的斩杀线了！对手顶着最后1点血皮向你做了个鬼脸，然后一溜烟跑得无影无踪。」</span><br>";
    }elseif($fnumber == 450){
        $log .= "<span class=\"lime\">「４５０！斧王举起了淘汰之刃，只要生命值低于斩杀线，神仙也救不回来！你的攻击力与怒气跟着沸腾了起来！」</span><br>";
        $att += 4;
        $rage = min(100, $rage + 5);
    }elseif($fnumber == 451){
        $log .= "<span class=\"lime\">「４５１　Ｕｎａｖａｉｌａｂｌｅ　Ｆｏｒ　Ｌｅｇａｌ　Ｒｅａｓｏｎｓ！《华氏451度》的消防员提醒您，还有个叫『黑球』的废案技能也因为不明原因被封印了。」</span><br>";
    }elseif($fnumber == 452){
        $log .= "<span class=\"lime\">「距离地球1400光年的开普勒-452b星球上，不知道有没有外星人也在熬夜玩文字大逃杀游戏。」</span><br>";
    }elseif($fnumber == 453){
        $log .= "<span class=\"lime\">「『Never gonna give you up, never gonna let you down~』——收音机里突然传来了熟悉的歌声，恭喜你被跨越时空的瑞克摇了！」</span><br>";
    }elseif($fnumber == 454){
        $log .= "<span class=\"lime\">「为什么每次刚合成出一把满意的武器，下一步移动就会立刻撞见专门克制这把武器属性的真职人？这就是墨菲定律！」</span><br>";
    }elseif($fnumber == 455){
        $log .= "<span class=\"lime\">「六分钟无敌时间已到！天宇技师看着计时器过了第六分钟，突然忘记了自己接下来该怎么补刀。」</span><br>";
    }elseif($fnumber == 456){
        $log .= "<span class=\"lime\">「４、５、６！骰子顺子大吉！你的背包虽然没有自动整理功能，但你的心情瞬间变得井井有条。」</span><br>";
    }elseif($fnumber == 457){
        $log .= "<span class=\"lime\">「陷阱挑战者眯着眼睛看地面：『字太多了我不看！只要我闭着眼睛踩过去，陷阱的说明书就对我无效！』」</span><br>";
    }elseif($fnumber == 458){
        $log .= "<span class=\"lime\">「谜语：白衣的军长能用荧光的妖精看穿一切能力的纯度与烈度，却唯独看不清自己被命运诅咒的病弱明日。」</span><br>";
    }elseif($fnumber == 459){
        $log .= "<span class=\"lime\">「当一只母鸡开始思考先有鸡还是先有蛋的时候，路过的厨师已经把葱姜蒜切好下锅了。」</span><br>";
    }elseif($fnumber == 460){
        $log .= "<span class=\"lime\">「在幻境里唱一首《爸爸野猪》（Baba Yetu），说不定能让周围的野怪感受到来自《文明4》的虔诚教化。」</span><br>";
    }elseif($fnumber == 461){
        $log .= "<span class=\"lime\">「为什么有些道具的属性代码长得像一串乱码？因为当年设计合成表的时候，每个字母都代表一种毁天灭地的词条！」</span><br>";
    }elseif($fnumber == 462){
        $log .= "<span class=\"lime\">「红杀特工F看了一眼周围的枪林弹雨，无奈地叹了口气：『我明明是个异界来的幻想种，为什么每天都要打卡上班写外勤报告啊？』」</span><br>";
    }elseif($fnumber == 463){
        $log .= "<span class=\"lime\">「书卷使卡玛合上厚重的典籍：『在这个世界里，知识就是力量——尤其是当你直接拿精装硬壳书砸人脑袋的时候。』」</span><br>";
    }elseif($fnumber == 464){
        $log .= "<span class=\"lime\">「黑熊精向你发起了暴风60连击！啪啪啪啪啪啪——打完60下之后一共掉了60点血，你的防具甚至觉得有点舒服！」</span><br>";
    }elseif($fnumber == 465){
        $log .= "<span class=\"lime\">「如果把早上的闹钟声音设置成禁区倒计时警报，保证你每天都能在零点一秒之内从床上弹射起步。」</span><br>";
    }elseif($fnumber == 466){
        $log .= "<span class=\"lime\">「你在常磐森林里唱起了《小苹果》，树上的乌鸦听完之后感动得纷纷把攒了半个月的树枝扔到了你头上。」</span><br>";
    }elseif($fnumber == 467){
        $log .= "<span class=\"lime\">「谜语：表界里她是温和有礼、在网络上连载耽美武侠小说的才女；里界里她却是红杀密传的掌门，连毒药与暗器都浸着茶香。」</span><br>";
    }elseif($fnumber == 468){
        $log .= "<span class=\"lime\">「『巫女巫女灵梦，醋溜便当～』夜雀的歌声在林间回荡，听得你肚子咕咕直叫，恨不得立刻去摸个便当盒来吃。」</span><br>";
    }elseif($fnumber == 469){
        $log .= "<span class=\"lime\">「当你在数据库里看到某个字段名叫 clbpara 的时候，千万别以为它只是社团参数，那里面简直是个无所不包的四次元垃圾桶！」</span><br>";
    }elseif($fnumber == 470){
        $log .= "<span class=\"lime\">「如果把方便面调料包直接撒在键盘上，敲出来的代码会不会自带一股浓郁的红烧牛肉味？」</span><br>";
    }elseif($fnumber == 471){
        $log .= "<span class=\"lime\">「动物团的雪兔少女Holly竖起长长的白耳朵：『嘘——三百米外有人正在拆方便面包装袋，而且是老坛酸菜味的。』」</span><br>";
    }elseif($fnumber == 472){
        $log .= "<span class=\"lime\">「十一二岁的仓鼠少年Albert从多口袋工装裤里掏出了一把比他脸还大的扳手：『只要还没彻底炸成灰，我就能给你修好！』」</span><br>";
    }elseif($fnumber == 473){
        $log .= "<span class=\"lime\">「谜语：失踪的父亲并未远去，只是换上了『军需官』的代号在异乡的时空帧里打铁——顺便替某位电子道士改良了符纸的背胶。」</span><br>";
    }elseif($fnumber == 474){
        $log .= "<span class=\"lime\">「每次看到有人在公屏发『和平发育别打我』，就意味着他的背包里至少已经藏了三颗高爆手雷和一把磨好的快刀。」</span><br>";
    }elseif($fnumber == 475){
        $log .= "<span class=\"lime\">「４７５！距离五百大关只剩二十五步，相当于一场百米赛跑已经跑完了前三段！」</span><br>";
    }elseif($fnumber == 476){
        $log .= "<span class=\"lime\">「公元476年西罗马帝国灭亡，但幻境里的精罗今天也在对着一块碎砖头流泪。」</span><br>";
    }elseif($fnumber == 477){
        $log .= "<span class=\"lime\">「４７７．７厘米！单千铃的轨道空降巨剑『飞鲸』精准地砸穿了地板，连小数点后面的零头都算得一清二楚！」</span><br>";
    }elseif($fnumber == 478){
        $log .= "<span class=\"lime\">「为什么模板引擎要把 .htm 编译成 .tpl.php？因为这是来自Discuz!远古时代的智慧结晶，乱改一行正则就会全站白屏！」</span><br>";
    }elseif($fnumber == 479){
        $log .= "<span class=\"lime\">「数体教挑战者推了推眼镜开始了长达五分钟的口胡：『根据哥德尔不完备定理，刚才那一下攻击在数学上是不存在的！』」</span><br>";
    }elseif($fnumber == 480){
        $log .= "<span class=\"lime\">「你的视野分辨率突然降到了 640×480，一股浓郁的九十年代CRT显示器怀旧风扑面而来。」</span><br>";
    }elseif($fnumber == 481){
        $log .= "<span class=\"lime\">「『Clear Morning！』清爽的晨歌驱散了夜间的迷雾，虽然对夜视仪电池并没有什么实质性的帮助。」</span><br>";
    }elseif($fnumber == 482){
        $log .= "<span class=\"lime\">「梦想天生！无数光球在天空中交织成华丽的弹幕网，这时候除了抱头蹲下祈祷ミス之外也没有别的办法了。」</span><br>";
    }elseif($fnumber == 483){
        $log .= "<span class=\"lime\">「谜语：在绝大多数时空里都因贪腐而被处决的远房舅舅，却被黑衣的统帅特意从某个好结局里拽了出来，继续在名利场里蝇营狗苟。」</span><br>";
    }elseif($fnumber == 484){
        $log .= "<span class=\"lime\">「『太强了，我们来削弱虚子吧！』——无论哪个版本、哪张地图出现了平衡性问题，最终的解决方案永远是先把虚子削一刀。」</span><br>";
    }elseif($fnumber == 485){
        $log .= "<span class=\"lime\">「RS-485工业总线向你的大脑发送了一串差分信号，抗干扰能力极强，就是波特率稍微有点感人。」</span><br>";
    }elseif($fnumber == 486){
        $log .= "<span class=\"lime\">「４８６！伴随着一声凄厉的『呃啊啊啊——』，死亡回归的钟声敲响了，你的怒气在轮回的记忆中增加了8点！」</span><br>";
        $rage = min(100, $rage + 8);
    }elseif($fnumber == 487){
        $log .= "<span class=\"lime\">「如果在电梯关门前的最后150秒里没能赶回轿厢，你就会留在钻探层的深渊里和种火们大眼瞪小眼了哦。」</span><br>";
    }elseif($fnumber == 488){
        $log .= "<span class=\"lime\">「『More One Night~』哪怕已经是终末的绝境，只要两个人还能分吃一块压缩饼干，旅途就还没有结束。」</span><br>";
    }elseif($fnumber == 489){
        $log .= "<span class=\"lime\">「当你在深夜独自面对满屏的PHP报错时，请记住：不是你在写BUG，而是BUG在借你的手完成它的艺术创作。」</span><br>";
    }elseif($fnumber == 490){
        $log .= "<span class=\"lime\">「如果把吸尘器的管子接在吹风机的出风口上，它们两个会不会因为意见不合而在客厅里打起来？」</span><br>";
    }elseif($fnumber == 491){
        $log .= "<span class=\"lime\">「FCG火焰卡牌大赛实况转播中！主持人保琳·斯诺正在一本正经地胡说八道，旁边的埃尔文·露比已经把吐槽的麦克风捏变形了！」</span><br>";
    }elseif($fnumber == 492){
        $log .= "<span class=\"lime\">「饥肠辘辘的凸眼鱼从牌桌底下钻了出来，一口吞掉了刚倒下的墓地单位，并打了个响亮的饱嗝。」</span><br>";
    }elseif($fnumber == 493){
        $log .= "<span class=\"lime\">「宇宙之神阿尔宙斯的全国图鉴编号是493，但就算创世神亲自来到常磐，也得遵守每回合只能移动一次的基本法。」</span><br>";
    }elseif($fnumber == 494){
        $log .= "<span class=\"lime\">「止步十三的钻头在关键时刻卡住了！明明只要再往前钻一毫米就能出奇迹，命运却偏偏让它停在了13这个数字上。」</span><br>";
    }elseif($fnumber == 495){
        $log .= "<span class=\"lime\">「４９５！黑格尔的卡普雷卡尔常数！不管你拿什么四位数去做重排相减，最后都会掉进6174的旋涡；正如芙兰朵露的495年波纹一样！」</span><br>";
    }elseif($fnumber == 496){
        $log .= "<span class=\"lime\">「４９６！这是继6和28之后的第三个完全数！数学的纯粹之美洗涤了你的心灵。」</span><br>";
    }elseif($fnumber == 497){
        $log .= "<span class=\"lime\">「谜语：『将我的全部给予隔壁大叔！』——当濒死的少女用这句胡闹般的遗言卡住许愿协议的漏洞时，两个灵魂便永远焊在了一起。」</span><br>";
    }elseif($fnumber == 498){
        $log .= "<span class=\"lime\">「只差两块钱就能凑够五百整了！你在身上翻箱倒柜找了半天，最后只摸出了一粒洗得发白的纽扣。」</span><br>";
    }elseif($fnumber == 499){
        $log .= "<span class=\"lime\">「４９９！商家最喜欢的定价策略！看起来明明是四百多，付完钱才发现口袋已经空了五百块！」</span><br>";
    }elseif($fnumber == 500){
        $log .= "<span class=\"lime\">「５００　Ｉｎｔｅｒｎａｌ　Ｓｅｒｖｅｒ　Ｅｒｒｏｒ！中场休息时间到，服务器决定先给你塞50点体力压压惊！」</span><br>";
        $sp = min($msp, $sp + 50);
    }elseif($fnumber == 501){
        $log .= "<span class=\"lime\">「５０１　Ｎｏｔ　Ｉｍｐｌｅｍｅｎｔｅｄ！你想使用的这项超能力目前还停留在策划案的标题阶段，敬请期待下个世纪的更新。」</span><br>";
    }elseif($fnumber == 502){
        $log .= "<span class=\"lime\">「５０２　Ｂａｄ　Ｇａｔｅｗａｙ！不管是Nginx的502还是502胶水，一旦粘在手上就很难甩掉了。」</span><br>";
    }elseif($fnumber == 503){
        $log .= "<span class=\"lime\">「５０３　Ｓｅｒｖｉｃｅ　Ｕｎａｖａｉｌａｂｌｅ！命运女神现在正在休产假，您的转运申请已被自动转接至语音信箱。」</span><br>";
    }elseif($fnumber == 504){
        $log .= "<span class=\"lime\">「５０４　Ｇａｔｅｗａｙ　Ｔｉｍｅ－ｏｕｔ！pop子一拳砸碎了沙漏，决定直接跳过等待时间进入下一回合！」</span><br>";
    }elseif($fnumber == 505){
        $log .= "<span class=\"lime\">「ＳＯＳ！摩尔斯电码的三短三长三短在夜空中闪烁，结果引来的不是救援队，而是闻着味儿赶来的抢人头大佬。」</span><br>";
    }elseif($fnumber == 506){
        $log .= "<span class=\"lime\">「谜语：上行的电梯通往挤满玩家荒诞杂念的阁楼，下行的电梯直抵深埋种火的钻探深渊——请问，你要按亮哪一颗按钮？」</span><br>";
    }elseif($fnumber == 507){
        $log .= "<span class=\"lime\">「断罪之镰高高举起！在绝对的法则面前，任何花里胡哨的锁血技巧都不过是待宰的羔羊。」</span><br>";
    }elseif($fnumber == 508){
        $log .= "<span class=\"lime\">「５０８　Ｌｏｏｐ　Ｄｅｔｅｃｔｅｄ！因为你在两个相邻地图之间来回横跳了八百次，系统怀疑你掉进了莫比乌斯环。」</span><br>";
    }elseif($fnumber == 509){
        $log .= "<span class=\"lime\">「『空想神话』的旋律在幻境上空飘荡，那些被遗忘在旧版本里的故事，化作了点点星光。」</span><br>";
    }elseif($fnumber == 510){
        $log .= "<span class=\"lime\">「如果把五百一十只鸽子放进同一个广场，它们会在三分钟内成立一个鸽子工会并要求每天加餐两顿玉米粒。」</span><br>";
    }elseif($fnumber == 511){
        $log .= "<span class=\"lime\">「嘴强王者正在公屏上进行激烈的文字格斗，虽然他的面板攻击力只有可怜的个位数，但气势上已经屠穿整个服务器了！」</span><br>";
    }elseif($fnumber == 512){
        $log .= "<span class=\"lime\">「５１２！这是第三梦之数值的中轴线——跨过这条线，小兔子的歌单就会被芙蓉当场抓获！」</span><br>";
    }elseif($fnumber == 513){
        $log .= "<span class=\"lime\">「跨过512的第一步！小兔子 nervously 抱紧了自己的小板凳，总觉得背后有一道来自红杀密传的温柔视线。」</span><br>";
    }elseif($fnumber == 514){
        $log .= "<span class=\"lime\">「５１４！只要不和前面的114连在一起念，这就只是一个平平无奇、毫无恶臭气息的自然数罢了！」</span><br>";
    }elseif($fnumber == 515){
        $log .= "<span class=\"lime\">「✦覆唱的篝火在牌桌对面轻轻跳跃，将你上一回合打出的指令原封不动地复读了一遍。」</span><br>";
    }elseif($fnumber == 516){
        $log .= "<span class=\"lime\">「为什么 include/ 目录下既有 item.func.php 又有 itemmain.func.php 还有 item.main.php？这就是二十年迭代留下的地质沉积层！」</span><br>";
    }elseif($fnumber == 517){
        $log .= "<span class=\"lime\">「系统提示：517号技能『鱼弹』已被历史的车轮无情碾过，现在只剩下一条咸鱼在风中凌乱。」</span><br>";
    }elseif($fnumber == 518){
        $log .= "<span class=\"lime\">「５１８，我要发！虽然幻境里的ATM机早就被人砸烂了，但美好的发财愿望还是值得鼓励的。」</span><br>";
    }elseif($fnumber == 519){
        $log .= "<span class=\"lime\">「谜语：在岳城那条连门牌号都写成13/13的古怪弄堂里，异色瞳的黑猫蹲在墙头，冷眼数着来来往往的仿生过客。」</span><br>";
    }elseif($fnumber == 520){
        $log .= "<span class=\"lime\">「５２０！虽然在大逃杀里谈恋爱大概率会被FFF团连人带房一起扬了，但随机数大神还是温柔地给你奶了52点生命！」</span><br>";
        $hp = min($mhp, $hp + 52);
    }elseif($fnumber == 521){
        $log .= "<span class=\"lime\">「５２１！你在幻境里向路过的NPC深情表白，NPC感动得热泪盈眶，并反手给了你一记暴击作为回礼。」</span><br>";
    }elseif($fnumber == 522){
        $log .= "<span class=\"lime\">「✦爱恋的埋火深深藏在灰烬之下，表面上看去冷若冰霜，稍微碰一下却会被烫得缩回手去。」</span><br>";
    }elseif($fnumber == 523){
        $log .= "<span class=\"lime\">「如果把键盘上的Ctrl、C和V三个键抠下来藏好，全世界有一半的程序员明天就得改行去卖烤冷面。」</span><br>";
    }elseif($fnumber == 524){
        $log .= "<span class=\"lime\">「老电波服传统艺能：开局先去诊所翻垃圾桶，翻不到好东西就大喊一声『暗改爆率』然后光速重开！」</span><br>";
    }elseif($fnumber == 525){
        $log .= "<span class=\"lime\">「５２５，我爱我！在残酷的生存游戏里，学会好好疼爱自己、按时吃药包扎，比什么花哨的操作都管用。」</span><br>";
    }elseif($fnumber == 526){
        $log .= "<span class=\"lime\">「✦怜悯的永火静静注视着重伤的旅人，它很想为你照亮前路，却又怕火光引来暗处的猎食者。」</span><br>";
    }elseif($fnumber == 527){
        $log .= "<span class=\"lime\">「你在路边看到一块写着『前方无陷阱』的木牌，根据大逃杀反心理学定律，这块木牌底下肯定埋着至少三颗地雷。」</span><br>";
    }elseif($fnumber == 528){
        $log .= "<span class=\"lime\">「谜语：十二岁的少女在录音棚里用最元气的声音喊出森林精灵的台词，走出隔音间后，却连抬头看一眼陌生人的勇气都没有。」</span><br>";
    }elseif($fnumber == 529){
        $log .= "<span class=\"lime\">「二十三的平方是529！这个数字看起来平平无奇，但只要你盯着它看够十秒钟，你就会发现自己老了十秒钟。」</span><br>";
    }elseif($fnumber == 530){
        $log .= "<span class=\"lime\">「降维打击已启动！三维的烦恼一旦被拍扁到二维的网页上，就只剩下HTML标签和CSS样式表了。」</span><br>";
    }elseif($fnumber == 531){
        $log .= "<span class=\"lime\">「磁场颠佬狂笑着轰出了九十九万九千九百九十九匹力量！什么常识、什么逻辑，通通没有我这记霸拳重要口牙！」</span><br>";
    }elseif($fnumber == 532){
        $log .= "<span class=\"lime\">「✦执念的残火死死咬住最后一根柴薪，哪怕整座森林都已经化作焦土，它也绝不肯熄灭。」</span><br>";
    }elseif($fnumber == 533){
        $log .= "<span class=\"lime\">「如果你在半夜两点半听到厨房里传来切菜的声音，别害怕，那只是蟑螂们在自己做宵夜。」</span><br>";
    }elseif($fnumber == 534){
        $log .= "<span class=\"lime\">「为什么每次合成武器的时候都要先去洗个手？因为玄学证明，洗手液的品牌会直接影响超量合成的成功率！」</span><br>";
    }elseif($fnumber == 535){
        $log .= "<span class=\"lime\">「✦希望的焰火在夜空中炸开一团温暖的金光，哪怕只是转瞬即逝的火花，也足够让人看清脚下的路了。」</span><br>";
    }elseif($fnumber == 536){
        $log .= "<span class=\"lime\">「谜语：十三岁的小魔女在直播间里出口成章，随手逆向出来的NPC外壳代码虽然狂冒八百个警告，跑起来却比谁都欢快。」</span><br>";
    }elseif($fnumber == 537){
        $log .= "<span class=\"lime\">「在幻境里遇到打不过的敌人怎么办？当然是熟练地打开聊天框，试图用感人肺腑的嘴炮说服对方放你一马！」</span><br>";
    }elseif($fnumber == 538){
        $log .= "<span class=\"lime\">「✦羞怯的萤火躲在草叶背面偷偷看你，你刚想伸手去接，它就害羞地熄灭了亮光。」</span><br>";
    }elseif($fnumber == 539){
        $log .= "<span class=\"lime\">「当一个PHP函数需要同时修改三十个变量的时候，聪明的人会传对象，而大逃杀选择写满三行 global！」</span><br>";
    }elseif($fnumber == 540){
        $log .= "<span class=\"lime\">「５４０度回旋踢！你在空中转了一圈半，落地的时候成功忘记了自己原本要踢哪个方向。」</span><br>";
    }elseif($fnumber == 541){
        $log .= "<span class=\"lime\">「六〇年的東埔寨裁判吹响了哨子，宣布刚才那次致命伤害属于假摔，并向攻击方出示了一张黄牌！」</span><br>";
    }elseif($fnumber == 542){
        $log .= "<span class=\"lime\">「山里灵活的狗在常磐森林里展现出惊人的走位，左扭右闪之间，把追杀它的挑战者绕晕在了树桩旁。」</span><br>";
    }elseif($fnumber == 543){
        $log .= "<span class=\"lime\">「看到红薯就买一个！程序员老公严格执行了布尔条件判断，拎着唯一的一个苹果骄傲地回家交差了。」</span><br>";
    }elseif($fnumber == 544){
        $log .= "<span class=\"lime\">「✦守望的炉火在空无一人的老屋里静静燃烧，等着一个再也不会推门回来的主人。」</span><br>";
    }elseif($fnumber == 545){
        $log .= "<span class=\"lime\">「谜语：老派的调查员嚼着口香糖，年轻的干将雷厉风行，沉稳的队长握紧配枪——只要ES队的徽章还在，岳城的黑夜就不会彻底失控。」</span><br>";
    }elseif($fnumber == 546){
        $log .= "<span class=\"lime\">「如果把闹钟调慢一个小时，是不是就等于向宇宙银行免息贷款了六十分钟的睡眠时间？」</span><br>";
    }elseif($fnumber == 547){
        $log .= "<span class=\"lime\">「✦缄默的暗火从不发出半点声响，当你察觉到鞋底传来的焦味时，它已经把你的退路烧成了灰。」</span><br>";
    }elseif($fnumber == 548){
        $log .= "<span class=\"lime\">「你在地上捡到了一张写满合成公式的小抄，仔细一看，上面写的全是怎么把神装逆向拆解成废铁的亏本秘籍。」</span><br>";
    }elseif($fnumber == 549){
        $log .= "<span class=\"lime\">「距离五百五十只差一步！就像考试考了59.9分一样，多出来的那一点点遗憾最让人睡不着觉。」</span><br>";
    }elseif($fnumber == 550){
        $log .= "<span class=\"lime\">「５５０Ｗ智能量子计算机正在为您编译命运……编译完成，建议您本局采取『苟到最后应有尽有』战术！」</span><br>";
    }elseif($fnumber == 551){
        $log .= "<span class=\"lime\">「功德仙人慈悲为怀，决定将手里刚抓到的剧毒水母就地放生到矿泉水瓶里，功德＋１，报应＋１００！」</span><br>";
    }elseif($fnumber == 552){
        $log .= "<span class=\"lime\">「✦漫游的流火从不在同一个区域停留超过两回合，用它的话说，停下来就会被禁区追上。」</span><br>";
    }elseif($fnumber == 553){
        $log .= "<span class=\"lime\">「５５３号技能『Ｖ５０』发动成功！虽然今天不一定是星期四，但你的口袋里真的多出了50块钱炸鸡基金！」</span><br>";
        $money += 50;
    }elseif($fnumber == 554){
        $log .= "<span class=\"lime\">「为什么每次打开旧代码都能看到一堆被注释掉的 echo 调试语句？因为删掉怕以后还要用，留着又像祖宗牌位一样不敢动！」</span><br>";
    }elseif($fnumber == 555){
        $log .= "<span class=\"lime\">「５５５！ＳＴＡＮＤＩＮＧ　ＢＹ……ＣＯＭＰＬＥＴＥ！假面骑士Faiz的变身音效在脑海中炸响，可惜你手里拿的是翻盖诺基亚。」</span><br>";
    }elseif($fnumber == 556){
        $log .= "<span class=\"lime\">「5.56毫米北约标准弹药摆在你面前，但你看了一眼手里的弹弓，觉得还是鹅卵石比较顺手。」</span><br>";
    }elseif($fnumber == 557){
        $log .= "<span class=\"lime\">「✦憧憬的蜃火在远方映出一座金碧辉煌的商店，等你气喘吁吁地跑过去，才发现那只是墓地里的一块残碑。」</span><br>";
    }elseif($fnumber == 558){
        $log .= "<span class=\"lime\">「谜语：温柔的姐姐调配着救命的药剂，毒舌的妹妹把控着致死的剂量；在流亡的动物少女们眼中，那座大排档便是整个世界最温暖的港湾。」</span><br>";
    }elseif($fnumber == 559){
        $log .= "<span class=\"lime\">「如果把一万只猴子放在键盘前面乱敲一万年，它们大概还是敲不出《哈姆雷特》，但一定能敲出一套能跑的PHP大逃杀。」</span><br>";
    }elseif($fnumber == 560){
        $log .= "<span class=\"lime\">「你在背包里塞了六块一模一样的磨刀石，试图召唤出传说中的磨刀石之神，结果只得到了沉甸甸的负重。」</span><br>";
    }elseif($fnumber == 561){
        $log .= "<span class=\"lime\">「赌玉挑战者一刀切开了花高价买来的原石——恭喜，里面既没有帝王绿也没有神器，只有一颗即将引爆的定时炸弹，小心断腿！」</span><br>";
    }elseif($fnumber == 562){
        $log .= "<span class=\"lime\">「过劳死警告！连续工作二十四个小时之后，连你的影子都向劳动仲裁委员会递交了辞职信。」</span><br>";
    }elseif($fnumber == 563){
        $log .= "<span class=\"lime\">「✦倾听的灯火安静地悬在半空，无论你吐槽代码有多烂、运气有多差，它都会耐心地闪动火苗回应你。」</span><br>";
    }elseif($fnumber == 564){
        $log .= "<span class=\"lime\">「为什么有些NPC死掉之后还会留在原地诈尸？因为刷新状态的数据库语句偶尔也会想偷个懒！」</span><br>";
    }elseif($fnumber == 565){
        $log .= "<span class=\"lime\">「如果你对着镜子里的自己连续猜拳赢了十把，请立刻离开洗手间，因为镜子里的那个家伙正在让着你。」</span><br>";
    }elseif($fnumber == 566){
        $log .= "<span class=\"lime\">「５６６！顺溜溜的数字滑过了你的脑海，就像脚底踩了香蕉皮一样丝滑。」</span><br>";
    }elseif($fnumber == 567){
        $log .= "<span class=\"lime\">「５、６、７！大步流星往前走！哪怕前面是英灵殿的守门人，气势上也绝对不能输！」</span><br>";
    }elseif($fnumber == 568){
        $log .= "<span class=\"lime\">「锐评大师推了推眼镜，对你的出装、走位和发际线进行了长达三千字的全方位毒舌点评。」</span><br>";
    }elseif($fnumber == 569){
        $log .= "<span class=\"lime\">「谜语：来自异界幻境的魅魔生着藏不住的恶魔角与尾巴，却因为只懂魅惑金属不懂魅惑人类，只好躲在湾城的出租屋里打铁糊口。」</span><br>";
    }elseif($fnumber == 570){
        $log .= "<span class=\"lime\">「✦戏谑的燐火绕着你的脚尖转了个圈，把你刚丢在地上的垃圾伪装成了金光闪闪的稀有道具。」</span><br>";
    }elseif($fnumber == 571){
        $log .= "<span class=\"lime\">「G.D.S的保安队长举起防暴盾牌：『衣冠不整者与携带未登记核武器者，一律不得进入金龙通讯社大厦！』」</span><br>";
    }elseif($fnumber == 572){
        $log .= "<span class=\"lime\">「当你在代码里看到 \$wepk、\$wepe、\$weps、\$wepsk 排成一排的时候，千万别眼花看错最后一个字母。」</span><br>";
    }elseif($fnumber == 573){
        $log .= "<span class=\"lime\">「５７３！来自圣Gradius学园的古老秘籍生效了，你的生命上限与生命值增加了30点！」</span><br>";
        $mhp += 30;
        $hp += 30;
    }elseif($fnumber == 574){
        $log .= "<span class=\"lime\">「刚拿完573的秘籍福利，574就来提醒你：科乐美秘籍虽然好用，但在多人联机里乱按小心被当成卡键！」</span><br>";
    }elseif($fnumber == 575){
        $log .= "<span class=\"lime\">「五、七、五！你在战场中央突然诗兴大发，吟出了一首标准格式的俳句：『常磐秋风起，出门踩中阔剑雷，下一局再见。』」</span><br>";
    }elseif($fnumber == 576){
        $log .= "<span class=\"lime\">「二十四的平方是576！相当于二十四小时里的每一小时都被切成了二十四份，给打工人的日程表提供了全新的压榨思路。」</span><br>";
    }elseif($fnumber == 577){
        $log .= "<span class=\"lime\">「毒奶色拍着胸脯向你保证：『对面只剩最后十点血了，这波你闭着眼睛A上去都能赢！』——你突然感到一阵彻骨的寒意。」</span><br>";
    }elseif($fnumber == 578){
        $log .= "<span class=\"lime\">「✦早慧的薪火年纪轻轻就看透了牌局的胜负，一边叹着老气横秋的气，一边打出了绝杀的一击。」</span><br>";
    }elseif($fnumber == 579){
        $log .= "<span class=\"lime\">「醉拳算什么本事，睡拳才叫真功夫！只要我一边睡觉一边反击，敌人就摸不清我的出拳套路！」</span><br>";
    }elseif($fnumber == 580){
        $log .= "<span class=\"lime\">「如果把五百八十颗红豆和五百八十颗绿豆混在一起，强迫症NPC就会在原地蹲上一整局把它们一颗颗分好。」</span><br>";
    }elseif($fnumber == 581){
        $log .= "<span class=\"lime\">「谜语：在岚江入海口的红树林湿地深处，基因编辑的飞鸟掠过水面，而水下潜伏的究竟是自然的新生，还是失控的造物？」</span><br>";
    }elseif($fnumber == 582){
        $log .= "<span class=\"lime\">「为什么游戏的房间表前缀叫 \$tablepre，公共表前缀叫 \$gtablepre？少写一个g，你就会在房间里把全服玩家的账号表给清空了！」</span><br>";
    }elseif($fnumber == 583){
        $log .= "<span class=\"lime\">「✦倦怠的灰火懒洋洋地趴在火盆里，表示除非有人往它嘴里塞一块上好的松木，否则它今天绝不多烧一秒钟。」</span><br>";
    }elseif($fnumber == 584){
        $log .= "<span class=\"lime\">「『油库里していってね！！！』两只馒头状的不明生物在路边冲你大喊大叫，建议直接拿去做成红豆馅饼。」</span><br>";
    }elseif($fnumber == 585){
        $log .= "<span class=\"lime\">「人类的本质是复读机！人类的本质是复读机！哪怕是在生死攸关的大逃杀里，公屏上也永远能排起整齐的队形！」</span><br>";
    }elseif($fnumber == 586){
        $log .= "<span class=\"lime\">「奔腾586处理器发出了年迈的嗡鸣声，提醒你这套网页游戏在当年用拨号上网也能流畅游玩。」</span><br>";
    }elseif($fnumber == 587){
        $log .= "<span class=\"lime\">「威武霸气（587）！你在原地摆出了一个极具压迫感的JOJO立，路过的野狗看了都夹着尾巴绕道走。」</span><br>";
    }elseif($fnumber == 588){
        $log .= "<span class=\"lime\">「✦渴求的鬼火幽幽地飘在半空，盯着你背包里的夜宵流下了并不存在的口水。」</span><br>";
    }elseif($fnumber == 589){
        $log .= "<span class=\"lime\">「谜语：一个是只相信硬实力与重剑的执事风尖兵，一个是把概率与命运玩弄于股掌的王牌——当不可阻挡之剑撞上不可命中之盾，谁会先一步算力枯竭？」</span><br>";
    }elseif($fnumber == 590){
        $log .= "<span class=\"lime\">「如果今天的天空变成了抹茶冰淇淋的颜色，那么云朵一定全都是棉花糖做的！」</span><br>";
    }elseif($fnumber == 591){
        $log .= "<span class=\"lime\">「为什么每次只差最后一件材料就能合成神器的时候，整张地图都会突然变成寸草不生的荒漠？」</span><br>";
    }elseif($fnumber == 592){
        $log .= "<span class=\"lime\">「✦傲慢的炫火在半空中炸开一朵硕大的孔雀尾羽，生怕方圆十里的狙击手看不到它的位置。」</span><br>";
    }elseif($fnumber == 593){
        $log .= "<span class=\"lime\">「八意制药倾情出品！这瓶秘药喝下去要么让你原地飞升成仙，要么让你下半辈子只能靠光合作用维持生命。」</span><br>";
    }elseif($fnumber == 594){
        $log .= "<span class=\"lime\">「你打开了 include/global.func.php，发现 config() 函数正在像翻抽屉一样，一层一层找当前规则集有没有覆盖配置文件。」</span><br>";
    }elseif($fnumber == 595){
        $log .= "<span class=\"lime\">「如果把两台复读机面对面放在一起，它们会在五分钟之内发明出一门人类无法理解的赛博方言。」</span><br>";
    }elseif($fnumber == 596){
        $log .= "<span class=\"lime\">「✦孤绝的冥火独自守在钻探层的最深处，幽蓝的火光映照着从未有人踏足的冰冷岩壁。」</span><br>";
    }elseif($fnumber == 597){
        $log .= "<span class=\"lime\">「喵呜一声，两只艾露猫推着木板车把你从战场上迅速抬走——恭喜你达成了光荣的『猫车』成就！」</span><br>";
    }elseif($fnumber == 598){
        $log .= "<span class=\"lime\">「『决斗开始！我的回合，直接用拳头攻击决斗者本人！』——这就是力拔山兮气盖世的『勇☆仪☆王』规则！」</span><br>";
    }elseif($fnumber == 599){
        $log .= "<span class=\"lime\">「距离六百大关只差临门一脚！你深吸一口气，决定把这一分钱掰成两半花。」</span><br>";
    }elseif($fnumber == 600){
        $log .= "<span class=\"lime\">「６００！整数大关的钟声稳稳敲响，大地母亲赐予了你6点坚实的防御力！」</span><br>";
        $def += 6;
    }elseif($fnumber == 601){
        $log .= "<span class=\"lime\">「✦攻坚的烈火化作一柄赤红的攻城锤，不管前面是防弹衣还是能量力场，通通烧穿给你看！」</span><br>";
    }elseif($fnumber == 602){
        $log .= "<span class=\"lime\">「6.02×10的23次方是阿伏伽德罗常数，你刚才喝下去的那口水里，足足有好几摩尔的水分子在为你加油！」</span><br>";
    }elseif($fnumber == 603){
        $log .= "<span class=\"lime\">「谜语：在伪造的解离尽头，流亡的姐妹与四只随机现身的动物伙伴并肩而立；你拖延的每一秒钟，都会化作她们守护彼此的锋芒。」</span><br>";
    }elseif($fnumber == 604){
        $log .= "<span class=\"lime\">「为什么模板文件里的注释要写成 <!--{if ...}--> 这种奇怪的样子？因为这样就算不编译直接用浏览器打开，也不会满屏乱码！」</span><br>";
    }elseif($fnumber == 605){
        $log .= "<span class=\"lime\">「✦瞬息的惊火啪地闪了一下就消失了，快得让你怀疑是不是自己的显示器刚才跳帧了。」</span><br>";
    }elseif($fnumber == 606){
        $log .= "<span class=\"lime\">「六零六，顺溜溜！你的走位像抹了润滑油一样，在枪林弹雨中擦弹而过。」</span><br>";
    }elseif($fnumber == 607){
        $log .= "<span class=\"lime\">「如果把所有的合成表倒过来背，能不能把一把终极神器手搓还原成十几个新手礼包？」</span><br>";
    }elseif($fnumber == 608){
        $log .= "<span class=\"lime\">「✦喧嚣的爆火在牌桌上噼里啪啦地乱蹦，就算炸不到对手，也要把全场的背景音乐震得破音！」</span><br>";
    }elseif($fnumber == 609){
        $log .= "<span class=\"lime\">「你在地图角落里发现了一台吃角子老虎机，投进去一块钱硬币，它居然吐出来了一张欠条。」</span><br>";
    }elseif($fnumber == 610){
        $log .= "<span class=\"lime\">「斐波那契数列的第15项是610！大自然的花瓣和松果都喜欢这个数字，但幻境的地雷并不关心。」</span><br>";
    }elseif($fnumber == 611){
        $log .= "<span class=\"lime\">「谜语：在真正的解离之路上，十数年前的旧影与跨帧而来的私兵层层阻截；唯有斩断一切虚假的因果，方能窥见沙漏外侧的天空。」</span><br>";
    }elseif($fnumber == 612){
        $log .= "<span class=\"lime\">「在遥远的B-612小行星上，小王子正在认真地给他的玫瑰花浇水，顺便拔掉了三棵试图长成禁区的猴面包树苗。」</span><br>";
    }elseif($fnumber == 613){
        $log .= "<span class=\"lime\">「✦统御的枢火稳居牌阵中央，周围的小火苗们排成整齐的队列，像极了正在做早操的小学生。」</span><br>";
    }elseif($fnumber == 614){
        $log .= "<span class=\"lime\">「为什么 gstrfilter() 函数看起来那么有年代感？因为它从二十年前就开始独自一人扛起整个游戏的防注入重任了！」</span><br>";
    }elseif($fnumber == 615){
        $log .= "<span class=\"lime\">「如果把一个装满水的气球扔进游泳池里，气球里的水会不会觉得自己终于回家了，只是还隔着一层秋裤？」</span><br>";
    }elseif($fnumber == 616){
        $log .= "<span class=\"lime\">「漫威电影宇宙的主宇宙编号是Earth-616，而你现在所在的常磐幻境，大概是个每天都要重启好几十次的平行口袋宇宙。」</span><br>";
    }elseif($fnumber == 617){
        $log .= "<span class=\"lime\">「英国皇家空军第617中队擅长炸水坝，而幻境里的爆系玩家擅长把除了水坝之外的所有建筑炸上天。」</span><br>";
    }elseif($fnumber == 618){
        $log .= "<span class=\"lime\">「６１８年中大促狂欢节！虽然商店老板坚决不肯打折，但系统偷偷给你返现了61块钱红包！」</span><br>";
        $money += 61;
    }elseif($fnumber == 619){
        $log .= "<span class=\"lime\">「『BOOYAKA BOOYAKA 619！』神秘人雷尔在绳圈上漂亮地一甩，一记旋风踢踹在了空气上！」</span><br>";
    }elseif($fnumber == 620){
        $log .= "<span class=\"lime\">「你在诊所的药柜里翻找了半天，发现所有的感冒药背面都印着一行小字：『本品不能治疗被核弹炸出来的内伤。』」</span><br>";
    }elseif($fnumber == 621){
        $log .= "<span class=\"lime\">「谜语：四米七七的巨剑原本没有名字，只因坠落时的蜂鸣如深海孤鲸的长歌，那戴着红项圈的少女便将它唤作『飞鲸』。」</span><br>";
    }elseif($fnumber == 622){
        $log .= "<span class=\"lime\">「当你发现自己的背包已经塞满六个格子、偏偏地上又刷出了一件极品装备时，人类历史上最艰难的断舍离就开始了。」</span><br>";
    }elseif($fnumber == 623){
        $log .= "<span class=\"lime\">「如果把周日晚上十一点到周一早上八点之间的时间从日历上直接删掉，打工人是不是就永远不用经历周一综合症了？」</span><br>";
    }elseif($fnumber == 624){
        $log .= "<span class=\"lime\">「你在电话亭里按下了 6-2-4-4-2（MAGIC），马桶并没有把你冲进魔法部，只是发出了一声普通的水响。」</span><br>";
    }elseif($fnumber == 625){
        $log .= "<span class=\"lime\">「二十五的平方是625！五的四次方也是625！如此工整的数字，简直让人想在常磐森林里拉起横幅庆祝一番。」</span><br>";
    }elseif($fnumber == 626){
        $log .= "<span class=\"lime\">「星际宝贝史迪奇的实验体编号是626！那只蓝色的外星小怪物从你身边跑过，顺手咬坏了路边的路牌。」</span><br>";
    }elseif($fnumber == 627){
        $log .= "<span class=\"lime\">「为什么游戏里回血叫HP、回体叫SP、歌魂叫SS，唯独怒气不叫RP而叫rage？因为RP早就被用来表示『报应点数（人品）』啦！」</span><br>";
    }elseif($fnumber == 628){
        $log .= "<span class=\"lime\">「６．２８是圆周率的两倍（τ）！信奉τ派的数学家表示，用6.28算圆周比3.14优雅一万倍！」</span><br>";
    }elseif($fnumber == 629){
        $log .= "<span class=\"lime\">「你在地上捡到了一张过期的彩票，刮开涂层的瞬间，上面缓缓浮现出四个大字：『下次一定』。」</span><br>";
    }elseif($fnumber == 630){
        $log .= "<span class=\"lime\">「谜语：红杀的前代将军早已卸下戎装，在城外僻静的街角守着一方百草药柜；可若有不速之客敢踏过门槛，老人的捣药杵依然重逾千钧。」</span><br>";
    }elseif($fnumber == 631){
        $log .= "<span class=\"lime\">「当一只咸鱼决定翻身的时候，它惊讶地发现自己背面其实也已经煎熟了。」</span><br>";
    }elseif($fnumber == 632){
        $log .= "<span class=\"lime\">「为什么有时候明明打死了敌人，系统却提示对方还剩1点血逃跑了？因为某些保命技能比蟑螂还要顽强！」</span><br>";
    }elseif($fnumber == 633){
        $log .= "<span class=\"lime\">「英国皇家空军633中队的轰炸机呼啸而过，投下了一箱装满红茶和司康饼的紧急空投。」</span><br>";
    }elseif($fnumber == 634){
        $log .= "<span class=\"lime\">「东京晴空塔的高度正好是634米（武藏），站在塔顶往下看，整个幻境的地图就像一张横竖分明的棋盘。」</span><br>";
    }elseif($fnumber == 635){
        $log .= "<span class=\"lime\">「你试图用胶带把两把单手剑绑在一起合成双头剑，结果因为缠得太紧，连剑柄都握不住了。」</span><br>";
    }elseif($fnumber == 636){
        $log .= "<span class=\"lime\">「谜语：在港口工业区废弃的集装箱与生锈行车之间，一层看不见的认知迷雾轻轻笼罩，将时空特使二部的灯火藏在凡人的视线之外。」</span><br>";
    }elseif($fnumber == 637){
        $log .= "<span class=\"lime\">「为什么每次遇到野怪都是它先手打我，遇到真职人更是它连打我三下？因为你的先制率低得连乌龟都看不下去了！」</span><br>";
    }elseif($fnumber == 638){
        $log .= "<span class=\"lime\">「如果把大脑的缓存清理干净，是不是就能彻底忘掉刚才被陷阱连炸三次的屈辱记忆？」</span><br>";
    }elseif($fnumber == 639){
        $log .= "<span class=\"lime\">「你在路边捡到了一颗看起来很像仙豆的绿豆，嚼碎咽下去之后发现它真的只是一颗普通的绿豆。」</span><br>";
    }elseif($fnumber == 640){
        $log .= "<span class=\"lime\">「『640KB内存对于任何人来说都足够了！』——比尔·盖茨到底有没有说过这句话已经无从考证，但640点血在大逃杀里肯定不够看！」</span><br>";
    }elseif($fnumber == 641){
        $log .= "<span class=\"lime\">「当你把鼠标悬停在『攻击』按钮上犹豫了三秒钟，对面的挑战者已经趁机喝完三瓶药并换好克制你的武器了。」</span><br>";
    }elseif($fnumber == 642){
        $log .= "<span class=\"lime\">「为什么 item.dice.php 里的注释全是用英文写的，而且充满了各种感叹号？因为写那段代码的时候作者正处于极度亢奋的深夜灵感期！」</span><br>";
    }elseif($fnumber == 643){
        $log .= "<span class=\"lime\">「棒球场上的 6-4-3 双杀配合行云流水！游击手传二垒再传一垒，把试图偷垒的挑战者直接淘汰出局！」</span><br>";
    }elseif($fnumber == 644){
        $log .= "<span class=\"lime\">「chmod 644！文件权限已设置为所有者可读写、其他人只读——哪怕是管理员也不能随便替你乱改这份命运！」</span><br>";
    }elseif($fnumber == 645){
        $log .= "<span class=\"lime\">「谜语：五柱之末的黑岩握紧染血的冷刃，在背叛师门的那一夜，他便将旧红杀的荣光一并卖给了黑衣的统帅。」</span><br>";
    }elseif($fnumber == 646){
        $log .= "<span class=\"lime\">「左右对称的６４６！如果你盯着中间的4看太久，两边的6就会像括号一样把你的视线夹住。」</span><br>";
    }elseif($fnumber == 647){
        $log .= "<span class=\"lime\">「你在旧书摊上买了一本《如何在大逃杀中活过前三分钟》，翻开第一页写着：『首先，不要站在原地看书。』」</span><br>";
    }elseif($fnumber == 648){
        $log .= "<span class=\"lime\">「６４８！一单648下去，金光一闪，卡池里跳出来的依然是你已经满命的保底蓝天白云！」</span><br>";
    }elseif($fnumber == 649){
        $log .= "<span class=\"lime\">「第五世代宝可梦图鉴的终点是649号盖诺赛克特，背着巨型卡带炮的机械虫表示它很喜欢幻境里的重型火器。」</span><br>";
    }elseif($fnumber == 650){
        $log .= "<span class=\"lime\">「MOS 6502八位处理器向你发来贺电！没有当年红白机里的这颗小芯片，就没有今天的电子游戏时代！」</span><br>";
    }elseif($fnumber == 651){
        $log .= "<span class=\"lime\">「如果把一个空的矿泉水瓶装满空气拧紧盖子，带到十米深的水底打开，算不算在水下成功制造了一座小型气泡温泉？」</span><br>";
    }elseif($fnumber == 652){
        $log .= "<span class=\"lime\">「为什么每次快要通关的时候服务器就会刚好开始日常维护？这就是传说中的『宿命之拔网线』！」</span><br>";
    }elseif($fnumber == 653){
        $log .= "<span class=\"lime\">「谜语：十七岁的高中生侦探总是谦称自己只是运气好，可那低至三点的幸运刻度早已出卖了他全凭智商硬撑的真相。」</span><br>";
    }elseif($fnumber == 654){
        $log .= "<span class=\"lime\">「６、５、４！倒着数的顺子！就像你在发现前面蹲着真职人之后，以迅雷不及掩耳之势倒着退回了上一格地图！」</span><br>";
    }elseif($fnumber == 655){
        $log .= "<span class=\"lime\">「再往后多一位数字就是65535了！十六位无符号整数的墙壁在远处若隐若现，好在这次你只骰到了655。」</span><br>";
    }elseif($fnumber == 656){
        $log .= "<span class=\"lime\">「你在自动贩卖机底下摸出了一枚沾满灰尘的游戏币，擦干净一看，上面印着『常磐电玩城专用，概不退换』。」</span><br>";
    }elseif($fnumber == 657){
        $log .= "<span class=\"lime\">「当一个玩家开始认真阅读状态栏里的每一条buff说明时，通常意味着他已经中了至少五种奇奇怪怪的异常状态。」</span><br>";
    }elseif($fnumber == 658){
        $log .= "<span class=\"lime\">「如果把拖把倒过来举着走在森林里，远处的狙击手会不会以为你顶着一头极其茂密的爆炸头？」</span><br>";
    }elseif($fnumber == 659){
        $log .= "<span class=\"lime\">「距离恶魔的数字只差七步！空气中渐渐飘来了一丝硫磺与烤红薯混合的奇妙气味。」</span><br>";
    }elseif($fnumber == 660){
        $log .= "<span class=\"lime\">「６６０！六六大顺乘以十！虽然没有直接给你发钱，但你脚下的步伐明显轻快了不少。」</span><br>";
    }elseif($fnumber == 661){
        $log .= "<span class=\"lime\">「谜语：明明只是把儿时玩伴的笑语写进了银白方盒的深处，可当硝烟散尽，醒来的却只有六簇不知自己姓名的火苗。」</span><br>";
    }elseif($fnumber == 662){
        $log .= "<span class=\"lime\">「为什么 bot/revbotservice.php 里的BOT走起路来有时候比真人还像真人？因为它们从不犹豫，看到什么捡什么！」</span><br>";
    }elseif($fnumber == 663){
        $log .= "<span class=\"lime\">「你在常磐台中学的黑板上看到一行粉笔字：『距离高考还有663天，请各位挑战者抓紧时间复习合成表。』」</span><br>";
    }elseif($fnumber == 664){
        $log .= "<span class=\"lime\">「如果把柠檬和苦瓜一起放进榨汁机，榨出来的果汁能不能让喝下它的人瞬间看破红尘？」</span><br>";
    }elseif($fnumber == 665){
        $log .= "<span class=\"lime\">「The Neighbor of the Beast！住在666号房隔壁的665号邻居表示，每天晚上被隔壁的重金属摇滚吵得睡不着觉真的很烦。」</span><br>";
    }elseif($fnumber == 666){
        $log .= "<span class=\"lime\">「６６６！老铁这波操作溜得飞起，四尊亚空间大神决定顺手赏你全系熟练度＋６！」</span><br>";
        $wp += 6;
        $wk += 6;
        $wg += 6;
        $wc += 6;
        $wd += 6;
        $wf += 6;
    }elseif($fnumber == 667){
        $log .= "<span class=\"lime\">「６６７！冷知识：小叶子的妙妙箱自爆阈值正好是667——看到这个数字的瞬间，你的双手条件反射般地抖了一下！」</span><br>";
    }elseif($fnumber == 668){
        $log .= "<span class=\"lime\">「住在666另一边的668号邻居探出头来：『六六八，一路发！别管隔壁那个玩核弹的疯子了，来喝杯茶吧！』」</span><br>";
    }elseif($fnumber == 669){
        $log .= "<span class=\"lime\">「如果把一张写着『正面朝上』的纸片贴在硬币的反面，硬币落地的时候会不会陷入深深的自我怀疑？」</span><br>";
    }elseif($fnumber == 670){
        $log .= "<span class=\"lime\">「谜语：演剧部的部长总是随身拖着塞满戏服的行李箱，无论是古装侠客还是未来特工，换上一身行头便是另一段人生。」</span><br>";
    }elseif($fnumber == 671){
        $log .= "<span class=\"lime\">「你在废墟里翻出了一台老式收音机，拧了半天旋钮，里面传出了天气预报：『今日常磐地区局部有流星雨及轨道轰炸，出门请带伞。』」</span><br>";
    }elseif($fnumber == 672){
        $log .= "<span class=\"lime\">「为什么有些道具的名字前后都要加上闪亮的★五角星★？因为不加星星的话，大家就会把它当成路边的普通垃圾扔掉！」</span><br>";
    }elseif($fnumber == 673){
        $log .= "<span class=\"lime\">「６７３！一个质数静静地站在路中央，拒绝被除了1和它自己之外的任何武器整除。」</span><br>";
    }elseif($fnumber == 674){
        $log .= "<span class=\"lime\">「当你试图在游戏里寻找和平共处的可能性时，游戏胜利条件正指着『最后幸存者：1人』对你露出慈祥的微笑。」</span><br>";
    }elseif($fnumber == 675){
        $log .= "<span class=\"lime\">「三分之二以上的千面骰刻度已经被你甩在身后了！坚持住，离1024的终极顶点越来越近了！」</span><br>";
    }elseif($fnumber == 676){
        $log .= "<span class=\"lime\">「二十六的平方是676！英文字母表一共有26个字母，两个字母的组合正好有676种——而你偏偏选了最容易拼错的那一种。」</span><br>";
    }elseif($fnumber == 677){
        $log .= "<span class=\"lime\">「谜语：在没有编号的#00000荒漠之中，娃娃脸的司机握稳方向盘，将迷失在最强幻影里的归人一一接上返程的巴士。」</span><br>";
    }elseif($fnumber == 678){
        $log .= "<span class=\"lime\">「６、７、８！步步高升大顺子！虽然不能让你直接升一级，但至少听起来特别吉利！」</span><br>";
    }elseif($fnumber == 679){
        $log .= "<span class=\"lime\">「为什么每次残血想找个安全的地方睡觉回血时，系统都会精准地把下一个禁区刷在你脑门上？」</span><br>";
    }elseif($fnumber == 680){
        $log .= "<span class=\"lime\">「摩托罗拉68000处理器向你致敬！当年的街机厅里，无数格斗家的青春都在这颗芯片上燃烧。」</span><br>";
    }elseif($fnumber == 681){
        $log .= "<span class=\"lime\">「如果把泡面桶盖子用字典压住整整三分钟，泡出来的面条会不会比用拖鞋压住的更有文化气息？」</span><br>";
    }elseif($fnumber == 682){
        $log .= "<span class=\"lime\">「SCP-682不灭孽蜥看了一眼常磐幻境里的八百八十八万血便当盒，默默转身钻回了盐酸池里：『打扰了，这地方比基金会还危险。』」</span><br>";
    }elseif($fnumber == 683){
        $log .= "<span class=\"lime\">「你在地上捡到了一本没有封面的武功秘籍，翻开一看，每一页都印着同一个字：『跑！』」</span><br>";
    }elseif($fnumber == 684){
        $log .= "<span class=\"lime\">「为什么 player_save() 和 save_gameinfo() 的函数名一个把save放后面、一个把save放前面？别问，这就是自由奔放的命名艺术！」</span><br>";
    }elseif($fnumber == 685){
        $log .= "<span class=\"lime\">「谜语：大排档的油烟气掩盖了S液体的微光，那双戴着重型合金拳套的鹅少女站在门前，连砖墙与铁门都挡不住她护短的一拳。」</span><br>";
    }elseif($fnumber == 686){
        $log .= "<span class=\"lime\">「６８６！双管猎枪装填完毕！虽然只有两发子弹，但只要贴脸轰出去，众生一律平等！」</span><br>";
    }elseif($fnumber == 687){
        $log .= "<span class=\"lime\">「如果把一个沙漏横着平放在桌子上，时间是不是就会停在原地不知所措？」</span><br>";
    }elseif($fnumber == 688){
        $log .= "<span class=\"lime\">「６８８！溜爸爸……不对，是六八八！祝您今天搜刮物资一路顺风，箱箱出金！」</span><br>";
    }elseif($fnumber == 689){
        $log .= "<span class=\"lime\">「６、８、９！看到这三个数字连在一起，你的脑海里突然响起了某种古老而滑稽的街头音效。」</span><br>";
    }elseif($fnumber == 690){
        $log .= "<span class=\"lime\">「当你在深夜的房间里独自点击『移动』按钮时，有没有想过屏幕里的角色也在疑惑你为什么总喜欢去墓地转悠？」</span><br>";
    }elseif($fnumber == 691){
        $log .= "<span class=\"lime\">「谜语：『我比红暮可强得多了哈哈哈！』——张扬的少女魔导士高昂着下巴走过街角，却从不在任何人面前与那位姐姐并肩同框。」</span><br>";
    }elseif($fnumber == 692){
        $log .= "<span class=\"lime\">「为什么有些规则集要用规则集自己的 cache 目录，有些却直接改公共文件？因为重构的脚步永远追不上策划开脑洞的速度！」</span><br>";
    }elseif($fnumber == 693){
        $log .= "<span class=\"lime\">「你在路边的树洞里发现了一颗松鼠藏起来的松果，本着大逃杀雁过拔毛的原则，你把松果也塞进了背包。」</span><br>";
    }elseif($fnumber == 694){
        $log .= "<span class=\"lime\">「如果把墨镜戴在夜视仪外面，看到的画面会不会变成黑白默片？」</span><br>";
    }elseif($fnumber == 695){
        $log .= "<span class=\"lime\">「距离七百大关只剩五步之遥！五根手指头数完，新的百位篇章就要开启了！」</span><br>";
    }elseif($fnumber == 696){
        $log .= "<span class=\"lime\">「６９６！不管正着看还是倒着看都充满了一种微妙的哲学平衡感。」</span><br>";
    }elseif($fnumber == 697){
        $log .= "<span class=\"lime\">「你在墙壁上看到了一行前人留下的刻痕：『千万不要相信任何名字里带礼盒的道具！』」</span><br>";
    }elseif($fnumber == 698){
        $log .= "<span class=\"lime\">「谜语：金龙通讯社的顶层办公室里，热爱摇滚乐的年轻总裁望向窗外的天青大桥；她身上没有任何超凡的回路，却执掌着整片江湖的锋芒。」</span><br>";
    }elseif($fnumber == 699){
        $log .= "<span class=\"lime\">「６９９！促销甩卖价的终极形态！再加一块钱就能凑整七百了！」</span><br>";
    }elseif($fnumber == 700){
        $log .= "<span class=\"lime\">「７００！幸运七百大关轰然开启！你的精神为之一振，生命与体力各恢复了20点！」</span><br>";
        $hp = min($mhp, $hp + 20);
        $sp = min($msp, $sp + 20);
    }elseif($fnumber == 701){
        $log .= "<span class=\"lime\">「求闻持聪明法发动！阿加莎克里斯Q合上笔记，整个幻境里所有发生过的死亡与合成记录都分毫不差地印在了脑海里。」</span><br>";
    }elseif($fnumber == 702){
        $log .= "<span class=\"lime\">「如果把两面镜子面对面摆在禁区边缘，禁区爆炸的火光会不会在两面镜子之间弹射一万年？」</span><br>";
    }elseif($fnumber == 703){
        $log .= "<span class=\"lime\">「『月光蝶である！！』背后展开绚丽的纳米光翼，所过之处一切科技文明尽数化作回归自然的尘埃！」</span><br>";
    }elseif($fnumber == 704){
        $log .= "<span class=\"lime\">「为什么游戏里的天气系统一变天，大家的属性和视野就会跟着乱跳？因为常磐的气象局是由随机数大神亲自兼任台长的！」</span><br>";
    }elseif($fnumber == 705){
        $log .= "<span class=\"lime\">「你在旧仓库里翻出了一箱标着『保质期至世界末日』的军用压缩饼干，咬了一口，差点把门牙崩掉半颗。」</span><br>";
    }elseif($fnumber == 706){
        $log .= "<span class=\"lime\">「谜语：西山深处的李家宅邸俯瞰着整座河口湾，盘山公路上只有获准通行的无人车静静滑过，替古老的豪门守着半城风雨。」</span><br>";
    }elseif($fnumber == 707){
        $log .= "<span class=\"lime\">「甩锅大法好！只要在踩中陷阱的瞬间大喊一声『都是网卡害的』，心理上的挫败感就会成功转移给无辜的运营商！」</span><br>";
    }elseif($fnumber == 708){
        $log .= "<span class=\"lime\">「『差不多得了！』——扎不多先生摆了摆手，表示做人也好打牌也好，见好就收才是活到最后的硬道理。」</span><br>";
    }elseif($fnumber == 709){
        $log .= "<span class=\"lime\">「当你在代码里看到 extract(\$pdata) 的时候，请保持虔诚的敬畏之心，因为接下来出现的每一个变量都可能直接决定你的生死。」</span><br>";
    }elseif($fnumber == 710){
        $log .= "<span class=\"lime\">「如果把OIL这个单词倒过来拿在手里看，正好就是710！可惜幻境里没有石油大亨给你投资。」</span><br>";
    }elseif($fnumber == 711){
        $log .= "<span class=\"lime\">「欢迎光临7-Eleven便利店！虽然这里没有关东煮和冰镇可乐，但货架底下说不定藏着前人留下的磨刀石。」</span><br>";
    }elseif($fnumber == 712){
        $log .= "<span class=\"lime\">「谜语：他将十三位逝者的基因凝于一脉，在郊区的孤儿院顶楼锁住往昔的机房；八十岁的老人守着年幼的孙女，如同守着电掣最后的孤星。」</span><br>";
    }elseif($fnumber == 713){
        $log .= "<span class=\"lime\">「你在路边看到一只正在思考猫生的橘猫，它看了一眼你手里的武器，露出了一副『就这？』的嫌弃表情。」</span><br>";
    }elseif($fnumber == 714){
        $log .= "<span class=\"lime\">「贝比·鲁斯生涯打出了714支全垒打，而你拎着球棒挥了一下午，只打中了路边的两只蚊子。」</span><br>";
    }elseif($fnumber == 715){
        $log .= "<span class=\"lime\">「为什么有些武器的耐久度打着打着会突然变成1点而不是直接碎掉？因为留得青山在，还能拿去合成炉里当材料骗吃骗喝！」</span><br>";
    }elseif($fnumber == 716){
        $log .= "<span class=\"lime\">「占卜师水晶球里浮现出一团浓雾：『卦象显示，你接下来五步之内必定会遇到点什么——至于遇到的是补给还是地雷，水晶球信号不好没看清。』」</span><br>";
    }elseif($fnumber == 717){
        $log .= "<span class=\"lime\">「波音717客机平稳地滑过天际，而地面上的你正在为了省几点体力纠结要不要脱掉沉重的防具。」</span><br>";
    }elseif($fnumber == 718){
        $log .= "<span class=\"lime\">「如果把所有的错误提示都改成『恭喜你触发了隐藏彩蛋』，玩家们的游戏幸福感会不会瞬间提升三百个百分点？」</span><br>";
    }elseif($fnumber == 719){
        $log .= "<span class=\"lime\">「谜语：在某个未被剪断的明日里，『林氏软件』的招牌下没有生死逃杀，只有一对恋人守着满屋子的游戏卡带，争论今晚谁洗碗。」</span><br>";
    }elseif($fnumber == 720){
        $log .= "<span class=\"lime\">「『这碗鸡汤我先干为敬！』——伴随着720度空中转体两周，穿山甲特工含泪饮下了自己亲手熬制的浓汤！」</span><br>";
    }elseif($fnumber == 721){
        $log .= "<span class=\"lime\">「０７２１！请不要对这串神秘的四位数字进行任何不必要的联想，这真的只是单纯排在720后面的自然数而已！」</span><br>";
    }elseif($fnumber == 722){
        $log .= "<span class=\"lime\">「雪崩之时，没有一片雪花觉得自己有责任——因为它们全都在忙着往你的衣领里钻！」</span><br>";
    }elseif($fnumber == 723){
        $log .= "<span class=\"lime\">「为什么每次背包满了想丢掉一件垃圾的时候，手一抖就会精准地把刚合出来的神器给丢进深渊？」</span><br>";
    }elseif($fnumber == 724){
        $log .= "<span class=\"lime\">「野指针发起了『越界』攻击！它直接跳出了数组边界，在内存的荒野里一路狂奔，顺便把隔壁桌的数据踩得稀巴烂！」</span><br>";
    }elseif($fnumber == 725){
        $log .= "<span class=\"lime\">「７２５！距离八百只差七十五，相当于一天二十四小时已经熬过了下午六点！」</span><br>";
    }elseif($fnumber == 726){
        $log .= "<span class=\"lime\">「如果把太阳镜反过来戴，能不能让太阳以为今天其实是黑夜，从而提前下班休息？」</span><br>";
    }elseif($fnumber == 727){
        $log .= "<span class=\"lime\">「７２７！ＷＹＳＩ（When You See It）！音游玩家的DNA在此刻猛烈颤动了一下，手指不由自主地开始敲击桌面！」</span><br>";
    }elseif($fnumber == 728){
        $log .= "<span class=\"lime\">「谜语：在钻探层最深处的岩壁下，大种火张开温暖的羽翼；它不问你是为何而来的旅人，只将一盒小小的希望递到你手中。」</span><br>";
    }elseif($fnumber == 729){
        $log .= "<span class=\"lime\">「九的三次方是729！二十七的平方也是729！三的六次方还是729！三三不尽的数学洪流在你的脑海里刷屏了！」</span><br>";
    }elseif($fnumber == 730){
        $log .= "<span class=\"lime\">「一年有365天，两年就有730天——如果你一局大逃杀能打上整整730天，连服务器硬盘都会为你感动得坏道。」</span><br>";
    }elseif($fnumber == 731){
        $log .= "<span class=\"lime\">「你在旧诊所的墙角发现了一瓶没有标签的药水，摇一摇还会发出可乐冒泡般的声音，建议谨慎饮用。」</span><br>";
    }elseif($fnumber == 732){
        $log .= "<span class=\"lime\">「为什么有些函数的名字叫 rev*，比如 revcombat 和 revattr？因为那是大改版重制（Revolution/Revamp）时留下的光荣印记！」</span><br>";
    }elseif($fnumber == 733){
        $log .= "<span class=\"lime\">「如果把闹钟塞进枕头底下，梦里的自己会不会把闹钟铃声当成背景BGM继续蹦迪？」</span><br>";
    }elseif($fnumber == 734){
        $log .= "<span class=\"lime\">「谜语：天青大桥的下层步道开满了全息投影的繁花，年轻的情侣在观景台喝着咖啡，却不知头顶的磁浮列车正载着跨帧的特使呼啸而过。」</span><br>";
    }elseif($fnumber == 735){
        $log .= "<span class=\"lime\">「『Bomb has been planted.』——7355608！拆弹专家熟练地输入了这串刻进DNA的密码，成功保住了半张地图！」</span><br>";
    }elseif($fnumber == 736){
        $log .= "<span class=\"lime\">「你在草丛里摸索了半天，没有摸到伏地魔，倒是从草根底下抠出了一枚生锈的铁钉。」</span><br>";
    }elseif($fnumber == 737){
        $log .= "<span class=\"lime\">「波音737是民航史上最畅销的窄体客机，但在常磐大逃杀里，最畅销的交通工具永远是你自己的两条腿。」</span><br>";
    }elseif($fnumber == 738){
        $log .= "<span class=\"lime\">「为什么每次有人在群里喊『开房了速来』，等你选好头像点进去的时候，房间里已经有人手搓出半套神装了？」</span><br>";
    }elseif($fnumber == 739){
        $log .= "<span class=\"lime\">「如果把一本字典放进微波炉里转三分钟，取出来的时候会不会变成一本热腾腾的畅销书？」</span><br>";
    }elseif($fnumber == 740){
        $log .= "<span class=\"lime\">「７４０！虽然听起来有点像骂人的谐音，但随机数大神发誓这真的只是字面意思上的七百四十！」</span><br>";
    }elseif($fnumber == 741){
        $log .= "<span class=\"lime\">「谜语：一只只会把自己的名字倒过来念的笨拙女仆，却在虚拟的战场上挥舞着冰火雷泥与咆哮，替不知名的主人看守着庭院。」</span><br>";
    }elseif($fnumber == 742){
        $log .= "<span class=\"lime\">「常青街742号住着辛普森一家，而常磐森林742号树桩底下只住着一窝脾气暴躁的马蜂。」</span><br>";
    }elseif($fnumber == 743){
        $log .= "<span class=\"lime\">「你在背包里发现了一张写着『再来一瓶』的汽水瓶盖，可惜幻境里的自动售货机根本不认账。」</span><br>";
    }elseif($fnumber == 744){
        $log .= "<span class=\"lime\">「一个月如果有31天，那就正好是744个小时！打工人算完这个数字之后，默默流下了辛酸的泪水。」</span><br>";
    }elseif($fnumber == 745){
        $log .= "<span class=\"lime\">「为什么游戏里的金钱单位叫『元』，但大家买高级货的时候却只认『切糕』？因为硬通货的含金量是不会贬值的！」</span><br>";
    }elseif($fnumber == 746){
        $log .= "<span class=\"lime\">「如果把风扇对着镜子吹，镜子里的风扇会不会也给你吹来一股双倍的凉风？」</span><br>";
    }elseif($fnumber == 747){
        $log .= "<span class=\"lime\">「波音747空中女皇掠过云端！四台发动机喷出的尾流把常磐森林里的落叶吹得漫天飞舞。」</span><br>";
    }elseif($fnumber == 748){
        $log .= "<span class=\"lime\">「你在墓碑后面发现了一个写着748的诡异木箱，理智告诉你千万别开，但贪婪的手已经自动伸了过去。」</span><br>";
    }elseif($fnumber == 749){
        $log .= "<span class=\"lime\">「谜语：当黑衣的统帅因算不出万全的胜率而在湾城按兵不动时，棋盘两侧的棋子们早已在暗巷与幻境中交手了千百回。」</span><br>";
    }elseif($fnumber == 750){
        $log .= "<span class=\"lime\">「７５０毫升的红酒瓶拿在手里，既能倒进高脚杯里装优雅，也能在关键时刻直接当成钝器抡过去！」</span><br>";
    }elseif($fnumber == 751){
        $log .= "<span class=\"lime\">「为什么有些PHP文件末尾写了 ?>，有些却故意不写？因为少写一个闭合标签，就能拯救无数个被末尾空行搞崩的JSON响应！」</span><br>";
    }elseif($fnumber == 752){
        $log .= "<span class=\"lime\">「７５２号重力场警告！空气中突然弥漫着一股沉重的乐队怨念，仿佛下一秒就要有人冲出来质问你为什么要演奏那首曲子！」</span><br>";
    }elseif($fnumber == 753){
        $log .= "<span class=\"lime\">「公元前753年罗马建城，母狼乳养了罗慕路斯与雷穆斯；而在幻境里，野狼只会追着你的小腿肚猛咬。」</span><br>";
    }elseif($fnumber == 754){
        $log .= "<span class=\"lime\">「IEEE 754浮点数标准微笑着看着你：『你以为 0.1 + 0.2 等于 0.3 吗？不，它等于 0.30000000000000004！』」</span><br>";
    }elseif($fnumber == 755){
        $log .= "<span class=\"lime\">「chmod 755！你的执行权限已开启，现在你可以堂堂正正地在常磐大地上奔跑了！」</span><br>";
    }elseif($fnumber == 756){
        $log .= "<span class=\"lime\">「谜语：从一楼摁下电梯的按钮，只需一枚切糕的船票；可若想从集体潜意识的阁楼全身而退，却要在倒计时归零前跑赢自己的贪念。」</span><br>";
    }elseif($fnumber == 757){
        $log .= "<span class=\"lime\">「你在路边捡到了一根看起来像法杖的干树枝，挥舞了两下之后啪的一声断成了两截短树枝。」</span><br>";
    }elseif($fnumber == 758){
        $log .= "<span class=\"lime\">「为什么每次想找特定NPC做任务的时候跑遍全图都找不到，不想见它的时候却能在一格地图里连续撞见三次？」</span><br>";
    }elseif($fnumber == 759){
        $log .= "<span class=\"lime\">「如果把所有的问号都拉直变成感叹号，这个世界上是不是就没有疑惑、只剩下震惊了？！」</span><br>";
    }elseif($fnumber == 760){
        $log .= "<span class=\"lime\">「显卡风扇发出了7600转的咆哮——等等，玩个纯文字网页大逃杀为什么显卡风扇会转这么快？快看看是不是开了两百个标签页！」</span><br>";
    }elseif($fnumber == 761){
        $log .= "<span class=\"lime\">「你在水池边看到一条正在仰泳的咸鱼，它甚至还悠闲地用鱼鳍给你比了个耶。」</span><br>";
    }elseif($fnumber == 762){
        $log .= "<span class=\"lime\">「7.62毫米全威力步枪弹！不管是AK还是莫辛纳甘，口径即正义的真理永远不会过时！」</span><br>";
    }elseif($fnumber == 763){
        $log .= "<span class=\"lime\">「谜语：在金龙通讯社的技术总监眼中，世间万物皆可收敛为最优的算法；唯独那位最高司令掷出的随机数，永远在她的回归曲线之外。」</span><br>";
    }elseif($fnumber == 764){
        $log .= "<span class=\"lime\">「距离765偶像事务所只差一步！你已经能听见后台传来的打call声和荧光棒挥舞的破空声了！」</span><br>";
    }elseif($fnumber == 765){
        $log .= "<span class=\"lime\">「７６５！舞台的聚光灯为你点亮，偶像们的歌声让你的歌魂与最大歌魂提升了10点！」</span><br>";
        $mss += 10;
        $ss += 10;
    }elseif($fnumber == 766){
        $log .= "<span class=\"lime\">「刚从765的舞台上走下来，你的耳朵里还嗡嗡回响着安可的欢呼声，连踩草地的脚步都带上了节拍。」</span><br>";
    }elseif($fnumber == 767){
        $log .= "<span class=\"lime\">「波音767宽体客机在云层上方留下一道长长的白线，像极了有人用粉笔把天空划成了两半。」</span><br>";
    }elseif($fnumber == 768){
        $log .= "<span class=\"lime\">「７６８！在1024×768的经典显示器年代，这正是整个屏幕纵向像素的尽头！」</span><br>";
    }elseif($fnumber == 769){
        $log .= "<span class=\"lime\">「为什么游戏里的尸体明明已经死透了，还能在被人摸包的时候保留完整的自我介绍？因为死人也是有尊严的！」</span><br>";
    }elseif($fnumber == 770){
        $log .= "<span class=\"lime\">「如果把七百七十只荧光棒同时掰亮扔进清水池，清水池之王会不会以为水底开了一家赛博迪厅？」</span><br>";
    }elseif($fnumber == 771){
        $log .= "<span class=\"lime\">「谜语：两只发条蒸汽羊跟在黑长直女仆的身后咩咩轻叫，它们不吃青草，只咀嚼空气中流淌的加密电波。」</span><br>";
    }elseif($fnumber == 772){
        $log .= "<span class=\"lime\">「ＥＡ－７７２！身高一米七二的猫娘战士奥西戈夫扛起藤蔓缠绕的北斗七星重兵，一锤砸碎了面前的障碍物！」</span><br>";
    }elseif($fnumber == 773){
        $log .= "<span class=\"lime\">「你在旧书堆里翻出了一本《PHP从入门到放弃》，发现里面的书签永远夹在了第三页的『环境配置』上。」</span><br>";
    }elseif($fnumber == 774){
        $log .= "<span class=\"lime\">「７７４（Nanashi）无名氏在公告板上留下了一行匿名留言：『楼下那个拿核弹的家伙是个大笨蛋。』」</span><br>";
    }elseif($fnumber == 775){
        $log .= "<span class=\"lime\">「chmod 775！群组读写执行权限全开，要不是为了留最后一点体面，差点就变成777裸奔权限了！」</span><br>";
    }elseif($fnumber == 776){
        $log .= "<span class=\"lime\">「距离777头奖只差最后１点！老虎机的第三个滚轮慢悠悠地转了半圈，最后极其无情地停在了柠檬图案上！」</span><br>";
    }elseif($fnumber == 777){
        $log .= "<span class=\"lime\">「７７７！ＧＥ７７７　ＹＯＵ　ＡＲＥ　ＢＬＥＳＳＥＤ！头奖钟声狂响，你的生命与体力完全恢复，兜里还多出了777块钱！」</span><br>";
        $hp = $mhp;
        $sp = $msp;
        $money += 777;
    }elseif($fnumber == 778){
        $log .= "<span class=\"lime\">「７７８！刚拿完头奖出来的778号挑战者表示，虽然错过了百万大奖，但至少不用担心因为运气太好而被全图追杀。」</span><br>";
    }elseif($fnumber == 779){
        $log .= "<span class=\"lime\">「如果把今天没用完的运气存进冰箱冷冻室里，保质期能不能延长到下个周末？」</span><br>";
    }elseif($fnumber == 780){
        $log .= "<span class=\"lime\">「７８０！七百八十度的转体动作只有体操冠军和被炸飞的布娃娃物理引擎才能做出来。」</span><br>";
    }elseif($fnumber == 781){
        $log .= "<span class=\"lime\">「谜语：背负巨剑的铁城从不拔剑，手握放大镜的破石专寻死穴；在红杀的棋盘上，最凶险的杀招往往藏在最不合常理的兵刃里。」</span><br>";
    }elseif($fnumber == 782){
        $log .= "<span class=\"lime\">「为什么每次想合一个普通的补给品都会合出『★我是狗屎★』？因为合成公式的判定顺序是从上往下挨个匹配的，错一步就掉进兜底深渊！」</span><br>";
    }elseif($fnumber == 783){
        $log .= "<span class=\"lime\">「你在草地上看到一朵长着人脸的向日葵，它冲你咧嘴一笑：『在这个世界里，不是杀，就是被杀哦！』」</span><br>";
    }elseif($fnumber == 784){
        $log .= "<span class=\"lime\">「二十八的平方是784！手写数字识别数据集MNIST里的每一张图片，正好都是28×28＝784个像素点！」</span><br>";
    }elseif($fnumber == 785){
        $log .= "<span class=\"lime\">「如果把望远镜反过来拿着看远处的BOSS，BOSS看起来就会变得只有蚂蚁那么大，心理压力瞬间小多了！」</span><br>";
    }elseif($fnumber == 786){
        $log .= "<span class=\"lime\">「你在收音机里调到了786千赫的中波电台，里面只有沙沙的电流声和偶尔传来的敲键盘声。」</span><br>";
    }elseif($fnumber == 787){
        $log .= "<span class=\"lime\">「波音787梦想客机从头顶滑过，而你依然在常磐的泥地上做着满装吃鸡的白日梦。」</span><br>";
    }elseif($fnumber == 788){
        $log .= "<span class=\"lime\">「７８８！离八百只差十二，就像一打鸡蛋里刚好被偷吃了十二颗，只剩下一个空纸盒。」</span><br>";
    }elseif($fnumber == 789){
        $log .= "<span class=\"lime\">「７、８、９！最大号的三连顺子！为什么6为什么害怕7？因为 7 ate 9（7把9吃掉了）！」</span><br>";
    }elseif($fnumber == 790){
        $log .= "<span class=\"lime\">「谜语：在BASSLINE那场没有胜者的漫长内战之后，活下来的不止有废土上的流亡者，还有跨越时空帧潜入湾城的旧日亡魂。」</span><br>";
    }elseif($fnumber == 791){
        $log .= "<span class=\"lime\">「当你在游戏里看到某件武器的攻击力写着 9999999 的时候，别怀疑，那大概率是给GM或者不讲武德的真职人准备的玩具。」</span><br>";
    }elseif($fnumber == 792){
        $log .= "<span class=\"lime\">「如果把一个闹钟扔进黑洞里，它在掉进奇点之前会不会坚持把早八的起床铃声响完？」</span><br>";
    }elseif($fnumber == 793){
        $log .= "<span class=\"lime\">「公元793年维京人洗劫了林迪斯法恩修道院，而2026年的你正在洗劫常磐台中学的储物柜。」</span><br>";
    }elseif($fnumber == 794){
        $log .= "<span class=\"lime\">「『鸣くよ（794）ウグイス平安京！』——日本历史背诵口诀在你的脑海里突然蹦了出来，虽然对合成表没有任何帮助。」</span><br>";
    }elseif($fnumber == 795){
        $log .= "<span class=\"lime\">「为什么有些道具吃下去不仅不回血，反而会扣掉半管体力？因为不看说明书就乱吃路边野果是要交学费的！」</span><br>";
    }elseif($fnumber == 796){
        $log .= "<span class=\"lime\">「你在路边捡到了一张写着『免死金牌』的硬纸板，翻过来一看，背面用圆珠笔写着：『有效期截至三秒钟前。』」</span><br>";
    }elseif($fnumber == 797){
        $log .= "<span class=\"lime\">「左右对称的７９７！像不像两把镰刀中间夹着一个正在瑟瑟发抖的气球？」</span><br>";
    }elseif($fnumber == 798){
        $log .= "<span class=\"lime\">「欢迎来到798艺术区！这里陈列着由历代挑战者折断的武器与踩爆的地雷碎片拼接而成的后现代主义雕塑。」</span><br>";
    }elseif($fnumber == 799){
        $log .= "<span class=\"lime\">「７９９！八百标兵已经在起跑线后方整装待发，只等你迈出最后这一步！」</span><br>";
    }elseif($fnumber == 800){
        $log .= "<span class=\"lime\">「８００！八百标兵奔北坡，炮兵并排北边跑！绕口令的气势让你的殴系与斩系熟练度各提升了8点！」</span><br>";
        $wp += 8;
        $wk += 8;
    }elseif($fnumber == 801){
        $log .= "<span class=\"lime\">「８０１！腐女们看到这个数字露出了神秘的微笑，而芙蓉老师默默合上了正在连载的耽美武侠小说草稿。」</span><br>";
    }elseif($fnumber == 802){
        $log .= "<span class=\"lime\">「IEEE 802.11无线局域网协议正在为你传输命运的电波，虽然偶尔会因为隔壁微波炉加热便当而丢包。」</span><br>";
    }elseif($fnumber == 803){
        $log .= "<span class=\"lime\">「为什么卡片技能编号最高只排到了806？因为写技能表的巫师在写到第806个技能的时候终于决定去吃晚饭了！」</span><br>";
    }elseif($fnumber == 804){
        $log .= "<span class=\"lime\">「谜语：在伪造的解禁结局里，你以为自己推开了自由的大门，却不知门外依旧是银白方盒折射出的另一层镜影。」</span><br>";
    }elseif($fnumber == 805){
        $log .= "<span class=\"lime\">「如果把八百零五只小黄鸭排成一列放进浴缸，浴缸里的水会不会直接漫到客厅里开游泳派对？」</span><br>";
    }elseif($fnumber == 806){
        $log .= "<span class=\"lime\">「８０６！卡片技能编号表的终点站到了！列车即将驶入纯粹的随机数荒野，请各位乘客系好安全带！」</span><br>";
    }elseif($fnumber == 807){
        $log .= "<span class=\"lime\">「你在地上捡到了一本《五年逃杀三年模拟》，翻开第一题就是：请简述在只剩1点血且中了毒的情况下如何反杀真职人。」</span><br>";
    }elseif($fnumber == 808){
        $log .= "<span class=\"lime\">「Roland TR-808鼓机敲出了经典的低音律动，整个常磐森林的树木都跟着动次打次的节奏摇晃起来！」</span><br>";
    }elseif($fnumber == 809){
        $log .= "<span class=\"lime\">「为什么每次刚买完防弹衣，下一个遇到的敌人手里拿的就一定是无视防弹的斩系大刀？因为针对你是写进宇宙常数里的！」</span><br>";
    }elseif($fnumber == 810){
        $log .= "<span class=\"lime\">「８１０！看到这串数字的瞬间，一股来自下北泽红茶的困意涌上心头，好在喝进嘴里的只是普通矿泉水。」</span><br>";
    }elseif($fnumber == 811){
        $log .= "<span class=\"lime\">「你在废墟墙上看到一张泛黄的海报，上面印着：『想体验真正刺激的卡牌对决吗？来钻探层找小种火打牌吧！』」</span><br>";
    }elseif($fnumber == 812){
        $log .= "<span class=\"lime\">「谜语：当真正的解禁钟声敲响，挡在面前的不再是敷衍的幻影，而是执掌八方的教头与红蓝交织的最高锋芒。」</span><br>";
    }elseif($fnumber == 813){
        $log .= "<span class=\"lime\">「如果把键盘上的退格键拆掉，人类打字的时候是不是就会变得更加谨言慎行？」</span><br>";
    }elseif($fnumber == 814){
        $log .= "<span class=\"lime\">「你在清水池边发现了一只正在思考人生的青蛙，为了防止它突然变成冰冻青蛙砸你一脸，你决定悄悄绕道走。」</span><br>";
    }elseif($fnumber == 815){
        $log .= "<span class=\"lime\">「大洋航空815航班从高空掠过，希望岛上的幸存者们不需要面对每半小时缩小一次的禁区。」</span><br>";
    }elseif($fnumber == 816){
        $log .= "<span class=\"lime\">「为什么 gamedata/system.php 和 gameinfo.php 动不动就会出现在 git status 里？因为老架构把运行时状态直接写进PHP源码文件里了！」</span><br>";
    }elseif($fnumber == 817){
        $log .= "<span class=\"lime\">「你在草丛里捡到了一副黑框眼镜，戴上去之后感觉自己的长相变得稍微有点膜法气息。」</span><br>";
    }elseif($fnumber == 818){
        $log .= "<span class=\"lime\">「８１８！八卦论坛开贴专用开头：『818那个在雏菊之丘连唱了两万六千首歌还不掉线的歌神！』」</span><br>";
    }elseif($fnumber == 819){
        $log .= "<span class=\"lime\">「如果把所有的合成材料按照拼音首字母排序，排在最前面的永远是那堆让人看了就头疼的化学药剂。」</span><br>";
    }elseif($fnumber == 820){
        $log .= "<span class=\"lime\">「８２０！八百二十度的烤箱不仅能烤熟火鸡，还能把试图躲进烤箱里避难的挑战者烤成焦炭。」</span><br>";
    }elseif($fnumber == 821){
        $log .= "<span class=\"lime\">「谜语：每当『模因轰炸』的航迹云划破天际，那位痴迷速度与战机的旧友便在云端大笑——对他而言，思想本就是最快的超音速导弹。」</span><br>";
    }elseif($fnumber == 822){
        $log .= "<span class=\"lime\">「你在商店门口捡到了一张优惠券：『满100000元立减5元』，看了一眼自己干瘪的钱包，你流下了贫穷的泪水。」</span><br>";
    }elseif($fnumber == 823){
        $log .= "<span class=\"lime\">「为什么有些NPC的台词看起来像是一百年前的老梗？因为它们确实是在十几年前的版本里就已经住在那儿了！」</span><br>";
    }elseif($fnumber == 824){
        $log .= "<span class=\"lime\">「８２４！不管科比·布莱恩特的球衣是8号还是24号，当你拎起篮球当成投系武器扔出去的时候，命中率都取决于你的熟练度。」</span><br>";
    }elseif($fnumber == 825){
        $log .= "<span class=\"lime\">「如果把一个汉堡包上下两层面包对调过来吃，味道会不会产生颠覆性的分子料理变化？」</span><br>";
    }elseif($fnumber == 826){
        $log .= "<span class=\"lime\">「你在路边看到一台自动擦鞋机，把脚伸进去之后，它不仅擦亮了你的鞋，还顺手给你系了个蝴蝶结。」</span><br>";
    }elseif($fnumber == 827){
        $log .= "<span class=\"lime\">「为什么有时候点一下按钮会连发两次请求？因为前端没锁住你的麒麟臂，而后端的 process.lock 正在拼命替你挡刀！」</span><br>";
    }elseif($fnumber == 828){
        $log .= "<span class=\"lime\">「８２８！八二八，发又发！虽然口袋里的钞票数量没有变化，但你感觉自己的商业头脑膨胀了一倍。」</span><br>";
    }elseif($fnumber == 829){
        $log .= "<span class=\"lime\">「谜语：在金龙通讯社的招工启事背后，从扫地的大妈到拔网线的网管，每个人都在用最朴素的职场技能打着最凶险的江湖架。」</span><br>";
    }elseif($fnumber == 830){
        $log .= "<span class=\"lime\">「你在森林里遇到了一只迷路的信鸽，它腿上的竹筒里塞着一张纸条：『鸽子汤味道不错，下次别放信鸽了。』」</span><br>";
    }elseif($fnumber == 831){
        $log .= "<span class=\"lime\">「８３１！八月三十一日是暑假的最后一天，也是初音未来的生日！哪怕暑假作业一个字没写，先听首歌再说吧！」</span><br>";
    }elseif($fnumber == 832){
        $log .= "<span class=\"lime\">「如果把一兆字节拆成八百三十二份，多出来的零头正好够塞下一行简短的注释。」</span><br>";
    }elseif($fnumber == 833){
        $log .= "<span class=\"lime\">「你在诊所病床底下摸出了一双穿洞的拖鞋，上面用马克笔写着：『跑路专用，穿戴后心理移动速度＋10%』。」</span><br>";
    }elseif($fnumber == 834){
        $log .= "<span class=\"lime\">「为什么游戏里的防具分身体、头部、手部、足部，却偏偏没有裤子槽位？难道大家在常磐都是光着腿穿战靴奔跑的吗？！」</span><br>";
    }elseif($fnumber == 835){
        $log .= "<span class=\"lime\">「８３５！距离九百大关还有六十五步，相当于退休年龄又往后延迟了几年。」</span><br>";
    }elseif($fnumber == 836){
        $log .= "<span class=\"lime\">「如果把所有的标点符号都换成波浪号～那么哪怕是最凶残的死亡通告～读起来也会变得像是在撒娇呢～」</span><br>";
    }elseif($fnumber == 837){
        $log .= "<span class=\"lime\">「你在路边捡到了一块写着『砖家认证』的板砖，拍人脑袋的时候不仅有物理伤害，还附带精神上的权威压制。」</span><br>";
    }elseif($fnumber == 838){
        $log .= "<span class=\"lime\">「谜语：当你在第一层的回廊里与守门的影子对坐，它不索取你的性命，只问你可曾听懂那三道关于火、关于人、关于盒子的谜题。」</span><br>";
    }elseif($fnumber == 839){
        $log .= "<span class=\"lime\">「为什么有些合成公式需要三件材料，有些却需要五件？因为合成炉的胃口跟程序员当天的晚饭分量成正比！」</span><br>";
    }elseif($fnumber == 840){
        $log .= "<span class=\"lime\">「８４０！八百四十毫米口径的古斯塔夫巨炮要是拉进常磐森林，一炮下去连地图编号都要少掉三个！」</span><br>";
    }elseif($fnumber == 841){
        $log .= "<span class=\"lime\">「二十九的平方是841！离三十的平方900只差一步之遥，就像差一分上岸的考生一样充满了故事感。」</span><br>";
    }elseif($fnumber == 842){
        $log .= "<span class=\"lime\">「你在旧书摊上翻到了一本《论持久战》，可惜在大逃杀里拖得越久，禁区留给你的落脚地就越少。」</span><br>";
    }elseif($fnumber == 843){
        $log .= "<span class=\"lime\">「如果把左手放进冰水里，右手放进热水里，那么你的大脑就会得出结论：你现在正处于温暖的春天！」</span><br>";
    }elseif($fnumber == 844){
        $log .= "<span class=\"lime\">「８４４！两个4排在8后面，像不像一架钢琴上刚刚弹响的低音和弦？」</span><br>";
    }elseif($fnumber == 845){
        $log .= "<span class=\"lime\">「你在树枝上看到一只正在倒挂金钩的毛毛虫，它用行动向你展示了什么叫真正的腰腹核心力量。」</span><br>";
    }elseif($fnumber == 846){
        $log .= "<span class=\"lime\">「为什么每次残血去商店买药的时候，手都会不自觉地滑向旁边那排死贵死贵的摸奖卡包？因为赌狗至死方休！」</span><br>";
    }elseif($fnumber == 847){
        $log .= "<span class=\"lime\">「谜语：『赢得越多，算力亏得越惨！』——当操纵概率的王牌第无数次算出一百种必胜路线，却发现每条路的尽头都站着那个拎纯金大剑的莽夫时，她终于陷入了沉思。」</span><br>";
    }elseif($fnumber == 848){
        $log .= "<span class=\"lime\">「８４８！八四八，发死发！听起来像是个让人纠结到底要不要去摸空投箱的薛定谔吉利数。」</span><br>";
    }elseif($fnumber == 849){
        $log .= "<span class=\"lime\">「如果把所有的NPC都召集起来开一场批斗大会，得票最高的罪名一定是『为什么我搜了十次都没遇到你』和『为什么我刚残血你就跳出来了』！」</span><br>";
    }elseif($fnumber == 850){
        $log .= "<span class=\"lime\">「８５０！你已经走过了八百五十个命运刻度，连幸运饼干里的纸条都没你能写！」</span><br>";
    }elseif($fnumber == 851){
        $log .= "<span class=\"lime\">「你在废墟里捡到了一只断了弦的尤克里里，虽然弹不出曲子，但拿来拍蚊子依然相当顺手。」</span><br>";
    }elseif($fnumber == 852){
        $log .= "<span class=\"lime\">「中国香港的国际区号是+852！阿Sir提醒您：在幻境里持械斗殴是家常便饭，但乱扔垃圾会增加报应点数哦。」</span><br>";
    }elseif($fnumber == 853){
        $log .= "<span class=\"lime\">「中国澳门的国际区号是+853！性感荷官在线发牌，可惜发给你的全都是写着『ミス』的空气牌。」</span><br>";
    }elseif($fnumber == 854){
        $log .= "<span class=\"lime\">「为什么游戏的日志变量叫 \$log，而且不管发生什么事都是往 \$log 后面一顿无脑 .= 拼接？因为字符串拼接就是PHP最快乐的魔法！」</span><br>";
    }elseif($fnumber == 855){
        $log .= "<span class=\"lime\">「如果把闹钟调成每隔五分钟响一次，连响十二次之后，你就会获得整整一个小时的碎片化浅睡眠体验。」</span><br>";
    }elseif($fnumber == 856){
        $log .= "<span class=\"lime\">「谜语：那只头顶总顶着杂物的狍子少女从不看路，可当流弹与落石齐齐擦着她的发梢飞过时，连死神都不得不怀疑自己是不是眼花了。」</span><br>";
    }elseif($fnumber == 857){
        $log .= "<span class=\"lime\">「８５７！８５７！脑海里突然响起了震耳欲聋的夜店蹦迪电音，你的双脚不由自主地在草地上踩起了鬼步舞！」</span><br>";
    }elseif($fnumber == 858){
        $log .= "<span class=\"lime\">「你在地上看到了一行蚂蚁排成的字：『别踩我们，去踩前面那个埋了雷的土包。』」</span><br>";
    }elseif($fnumber == 859){
        $log .= "<span class=\"lime\">「为什么有些玩家的称号看起来长到能绕屏幕一圈？因为成就系统就是用来给大家展示收集癖的勋章墙！」</span><br>";
    }elseif($fnumber == 860){
        $log .= "<span class=\"lime\">「８６０！离八百八十八的发财大关越来越近了，你甚至已经能闻到铜钱的清香了。」</span><br>";
    }elseif($fnumber == 861){
        $log .= "<span class=\"lime\">「如果把一杯浓茶和一杯美式咖啡兑在一起喝下去，今晚的你就能睁着眼睛看清服务器每一帧的刷新过程。」</span><br>";
    }elseif($fnumber == 862){
        $log .= "<span class=\"lime\">「你在常磐森林的树干上发现了一个奇怪的按钮，上面写着『千万别按』——你忍住了没按，并为自己的理智鼓了鼓掌。」</span><br>";
    }elseif($fnumber == 863){
        $log .= "<span class=\"lime\">「８６３计划向您的科研精神致敬！虽然你在合成炉里搞出来的科研成果大多是不明黑色物质。」</span><br>";
    }elseif($fnumber == 864){
        $log .= "<span class=\"lime\">「一天正好有86400秒！去掉后面两个零就是864，每一秒钟都有人在常磐的某个角落里翻箱倒柜。」</span><br>";
    }elseif($fnumber == 865){
        $log .= "<span class=\"lime\">「谜语：在湾城理事会的圆桌旁，科技新贵与百年豪门微笑着共商大计；而在桌面之下，无形的暗流早已顺着光纤漫过了整座东洲岛。」</span><br>";
    }elseif($fnumber == 866){
        $log .= "<span class=\"lime\">「８６６！六六大顺加上八百里加急，祝你下一次移动直接摸到梦寐以求的合成素材！」</span><br>";
    }elseif($fnumber == 867){
        $log .= "<span class=\"lime\">「『867-5309 / Jenny！』八十年代的金曲旋律在电话线里回荡，千万别随便拨打写在公厕墙上的神秘电话号码。」</span><br>";
    }elseif($fnumber == 868){
        $log .= "<span class=\"lime\">「左右对称的８６８！像不像两个胖乎乎的雪人中间蹲着一只正在吃松果的小松鼠？」</span><br>";
    }elseif($fnumber == 869){
        $log .= "<span class=\"lime\">「为什么每次你觉得某个冷门地点绝对没人去、打算躲进去回血的时候，一推门就会看到三个满装大佬正在里面大眼瞪小眼？」</span><br>";
    }elseif($fnumber == 870){
        $log .= "<span class=\"lime\">「如果把雨伞倒过来接雨水，接满一伞水之后能不能在里面养两条热带鱼？」</span><br>";
    }elseif($fnumber == 871){
        $log .= "<span class=\"lime\">「你在旧仓库的角落里发现了一张写着『欠条：欠挑战者一个奇迹』的纸片，落款是二十年前的某位巫师。」</span><br>";
    }elseif($fnumber == 872){
        $log .= "<span class=\"lime\">「为什么 fortune.func.php 的最底下写着一行注释『//Yes, this file would probably be very large. //Whatever.』？因为作者早就预料到今天会有人把1024条全填满了！」</span><br>";
    }elseif($fnumber == 873){
        $log .= "<span class=\"lime\">「８７３！不管前面有多少风浪，只要手里还有一口面包，就还没有到放弃治疗的时候！」</span><br>";
    }elseif($fnumber == 874){
        $log .= "<span class=\"lime\">「８７４！来自MOP远古猫扑时代的灵魂表情包！大耳锤子人拿着铁锤向你的屁股发起了无情的874打击！」</span><br>";
    }elseif($fnumber == 875){
        $log .= "<span class=\"lime\">「八分之七是0.875！你的1024大冒险已经正式完成了87.5%，胜利的曙光就在前方！」</span><br>";
    }elseif($fnumber == 876){
        $log .= "<span class=\"lime\">「８、７、６！倒计时三秒钟！你是选择原地摆个帅气的姿势，还是立刻抱头鼠窜？」</span><br>";
    }elseif($fnumber == 877){
        $log .= "<span class=\"lime\">「谜语：在#16161的遥远幻界里，白凰曾见识过真正的灵脉与符阵；而当她踏入这方数据织就的棋盘，手中的符纸依然能唤起隔山的惊雷。」</span><br>";
    }elseif($fnumber == 878){
        $log .= "<span class=\"lime\">「如果把所有的周二都改名叫『小周五』，大家上班的心情会不会稍微愉快那么零点五个百分点？」</span><br>";
    }elseif($fnumber == 879){
        $log .= "<span class=\"lime\">「你在路边捡到了一颗包装精美的巧克力，剥开锡纸一看，里面包着的居然是一颗涂成棕色的鹅卵石。」</span><br>";
    }elseif($fnumber == 880){
        $log .= "<span class=\"lime\">「８８０！双八临门！虽然还没有到888的巅峰，但空气中已经充满了暴富的前兆！」</span><br>";
    }elseif($fnumber == 881){
        $log .= "<span class=\"lime\">「为什么游戏里的社团技能可以随时花钱洗点或者升级？因为只要思想不滑坡，钞能力就能让你文武双全！」</span><br>";
    }elseif($fnumber == 882){
        $log .= "<span class=\"lime\">「如果把两块磁铁的同极死死绑在一起扔出去，它们会不会因为互相嫌弃而越飞越快？」</span><br>";
    }elseif($fnumber == 883){
        $log .= "<span class=\"lime\">「哈雷戴维森Iron 883摩托车的引擎声在耳边轰鸣，可惜常磐森林里的树太密，骑车容易撞树桩。」</span><br>";
    }elseif($fnumber == 884){
        $log .= "<span class=\"lime\">「８８４８！珠穆朗玛峰的高度是8848.86米，而你的钛合金手机哪怕掉进陷阱里也依然坚不可摧！」</span><br>";
    }elseif($fnumber == 885){
        $log .= "<span class=\"lime\">「你在商店货架背后发现了一本账本，上面详细记录了本局游戏各位玩家因为手滑买错东西而白送给店长的冤枉钱。」</span><br>";
    }elseif($fnumber == 886){
        $log .= "<span class=\"lime\">「８８６！远古QQ聊天室的告别专用语！在对面的残血玩家冲你挥手说出886之前，赶紧先把他留下来！」</span><br>";
    }elseif($fnumber == 887){
        $log .= "<span class=\"lime\">「距离888发财号只差最后１点！你的手指离金币堆只有零点零一公分，却摸到了一手灰。」</span><br>";
    }elseif($fnumber == 888){
        $log .= "<span class=\"lime\">「８８８！便当盒看着自己8888888点的血条，满意地拍了拍肚皮，并顺手给你塞了88块钱红包！」</span><br>";
        $money += 88;
    }elseif($fnumber == 889){
        $log .= "<span class=\"lime\">「８８９！刚路过便当盒的领地，你决定保持绝对的礼貌与谦卑，千万不要对那个发光的饭盒产生任何非分之想。」</span><br>";
    }elseif($fnumber == 890){
        $log .= "<span class=\"lime\">「如果把所有的闹钟都扔进洗衣机里洗一遍，洗出来的时间会不会变得干干净净？」</span><br>";
    }elseif($fnumber == 891){
        $log .= "<span class=\"lime\">「谜语：在集体潜意识堆积而成的阁楼里，玩家们十几年来用过的烂梗、踩过的坑与未竟的执念，全都化作了活蹦乱跳的荒诞怪谈。」</span><br>";
    }elseif($fnumber == 892){
        $log .= "<span class=\"lime\">「为什么每次你想安安静静做个合成党的时候，出生点周围的三个地图就全是被人搜空的荒地？」</span><br>";
    }elseif($fnumber == 893){
        $log .= "<span class=\"lime\">「８９３（Yakuza）！穿着花衬衫戴着墨镜的极道大哥从街角走过，看了一眼你手里的核弹，礼貌地给你让出了半条马路。」</span><br>";
    }elseif($fnumber == 894){
        $log .= "<span class=\"lime\">「你在废墟里翻出了一张旧报纸，头版头条写着：《震惊！某挑战者竟靠在诊所睡觉熬死全场所有对手！》」</span><br>";
    }elseif($fnumber == 895){
        $log .= "<span class=\"lime\">「如果把键盘反过来拍一拍，掉出来的饼干碎屑加起来说不定能够合成半块压缩饼干。」</span><br>";
    }elseif($fnumber == 896){
        $log .= "<span class=\"lime\">「为什么 \$nosta 变量在代码里代表无限耐久，而且它的值是字符串 '∞'？因为八字躺平的时候，就是永恒的开始！」</span><br>";
    }elseif($fnumber == 897){
        $log .= "<span class=\"lime\">「你在路边看到一只正在练习铁头功的啄木鸟，它咚咚咚地把一棵大树啄出了摩尔斯电码的节奏。」</span><br>";
    }elseif($fnumber == 898){
        $log .= "<span class=\"lime\">「左右对称的８９８！九百大关前的最后一道凯旋门已经为你敞开！」</span><br>";
    }elseif($fnumber == 899){
        $log .= "<span class=\"lime\">「８９９！三十的平方近在咫尺！你甚至能听见整数强迫症患者如释重负的欢呼声！」</span><br>";
    }elseif($fnumber == 900){
        $log .= "<span class=\"lime\">「９００！三十的平方完美达成！一股厚重的力量涌入四肢百骸，你的爆系与灵系熟练度各提升了9点！」</span><br>";
        $wd += 9;
        $wf += 9;
    }elseif($fnumber == 901){
        $log .= "<span class=\"lime\">「谜语：那位最高司令总是笑嘻嘻地用『知道了』打发一切汇报，可当她那看似胡闹的指令逐一应验时，连最精密的智脑都不得不俯首称臣。」</span><br>";
    }elseif($fnumber == 902){
        $log .= "<span class=\"lime\">「你在草地上捡到了一枚四叶草，仔细一数发现它其实长了五片叶子——多出来的那一片大概是辐射变异长出来的。」</span><br>";
    }elseif($fnumber == 903){
        $log .= "<span class=\"lime\">「为什么每次更新完模板之后都要提醒大家清缓存？因为总有人改了半天HTML发现没生效，最后气得差点把显示器砸了！」</span><br>";
    }elseif($fnumber == 904){
        $log .= "<span class=\"lime\">「如果把一本推理小说的最后一页撕下来贴在第一页前面，侦探是不是在案发前就能直接把凶手抓起来？」</span><br>";
    }elseif($fnumber == 905){
        $log .= "<span class=\"lime\">「你在自动售货机旁边的草丛里摸索，没有摸到硬币，倒是摸到了一只正在午睡的电子狐的尾巴尖。」</span><br>";
    }elseif($fnumber == 906){
        $log .= "<span class=\"lime\">「９０６！九百零六只绵羊从你的脑海里挨个跳了过去，非但没有把你哄睡着，反而让你数得精神百倍。」</span><br>";
    }elseif($fnumber == 907){
        $log .= "<span class=\"lime\">「为什么有些NPC的掉率写着万分之一，却总有欧皇能在第一回合就把它摸出来？因为欧皇跟我们玩的根本不是同一个游戏！」</span><br>";
    }elseif($fnumber == 908){
        $log .= "<span class=\"lime\">「如果把所有的退路都用胶水粘起来，那就不叫走投无路，而叫破釜沉舟！」</span><br>";
    }elseif($fnumber == 909){
        $log .= "<span class=\"lime\">「Roland TR-909鼓机接过了808的接力棒，科技舞曲的节拍在幻境的天空中回荡！」</span><br>";
    }elseif($fnumber == 910){
        $log .= "<span class=\"lime\">「谜语：在湾城郊外的孤儿院里，孩子们围着慈祥的老院长嬉笑奔跑；没人知道顶楼那两扇紧锁的门后，曾封存着怎样惊心动魄的雷霆。」</span><br>";
    }elseif($fnumber == 911){
        $log .= "<span class=\"lime\">「保时捷911的水平对置引擎轰鸣着冲过天青大桥，可惜在回合制文字游戏里，超跑的速度也只能算一格移动。」</span><br>";
    }elseif($fnumber == 912){
        $log .= "<span class=\"lime\">「你在地上捡到了一张写着『特等奖』的刮刮乐，仔细一看，兑奖日期是公元前221年。」</span><br>";
    }elseif($fnumber == 913){
        $log .= "<span class=\"lime\">「９１３！ＳＴＡＮＤＩＮＧ　ＢＹ！假面骑士Kaixa的专属数字！小心吃瘪之后化作一地金黄色的沙子哦！」</span><br>";
    }elseif($fnumber == 914){
        $log .= "<span class=\"lime\">「为什么 include 会直接继承调用处的变量作用域？因为PHP觉得大家既然在一个文件里见面了，那就是一家人，没必要分你的我的！」</span><br>";
    }elseif($fnumber == 915){
        $log .= "<span class=\"lime\">「如果把两杯50度的温水倒在一起，为什么不能变成一杯100度的开水用来泡面呢？物理学真是一门小气的学科。」</span><br>";
    }elseif($fnumber == 916){
        $log .= "<span class=\"lime\">「你在路边的石头底下翻出了一只寄居蟹，它背上背着的不是贝壳，而是一个空掉的红牛饮料罐。」</span><br>";
    }elseif($fnumber == 917){
        $log .= "<span class=\"lime\">「９１７，就要吃！干饭人干饭魂，哪怕外面枪林弹雨，先把背包里的便当热一热吃完再说！」</span><br>";
    }elseif($fnumber == 918){
        $log .= "<span class=\"lime\">「保时捷918 Spyder混动超跑向你闪了闪远光灯，提醒你在夜里搜刮的时候记得打开手电筒。」</span><br>";
    }elseif($fnumber == 919){
        $log .= "<span class=\"lime\">「谜语：当你在幻境里看见那七只熟悉的动物身影，请别急着把它们当成温顺的萌宠——在另一个时空的废土上，她们曾从尸山血海中杀出重围。」</span><br>";
    }elseif($fnumber == 920){
        $log .= "<span class=\"lime\">「９２０，就爱你！虽然这是个充满大逃杀恶意的地方，但偶尔收到一句毫无理由的夸奖还是挺让人开心的。」</span><br>";
    }elseif($fnumber == 921){
        $log .= "<span class=\"lime\">「如果把日历翻到九月二十一日，你的脑海里是不是就会自动播放起『Do you remember, the 21st night of September?』」</span><br>";
    }elseif($fnumber == 922){
        $log .= "<span class=\"lime\">「你在废弃教室的抽屉里翻出了一张零分试卷，上面的选择题完美避开了所有正确答案，某种意义上也是个运气奇才。」</span><br>";
    }elseif($fnumber == 923){
        $log .= "<span class=\"lime\">「为什么有些道具的类别叫 PB、有些叫 Y、有些叫 X？因为这套单字母与双字母缩写体系，是从远古BR时代一路传下来的摩斯密码！」</span><br>";
    }elseif($fnumber == 924){
        $log .= "<span class=\"lime\">「如果把一只猫和一台扫地机器人放在同一个房间里，三分钟后你就会获得一名威风凛凛的猫咪装甲骑兵。」</span><br>";
    }elseif($fnumber == 925){
        $log .= "<span class=\"lime\">「９２５纯银饰品在阳光下闪闪发光，如果你遇到狼人NPC的话，说不定能拿它当暗器砸过去。」</span><br>";
    }elseif($fnumber == 926){
        $log .= "<span class=\"lime\">「你在水池边看到了一只正在洗脸的小浣熊，它洗着洗着就把手里的棉花糖洗没了，正坐在原地怀疑人生。」</span><br>";
    }elseif($fnumber == 927){
        $log .= "<span class=\"lime\">「谜语：她用『共感』倾听众生的心声，以至于设计出的能力移植手术连排异反应都取决于『心的认可』——可她自己的幸运刻度，却永远停在最低的１上。」</span><br>";
    }elseif($fnumber == 928){
        $log .= "<span class=\"lime\">「为什么每次你攒了一背包的顶级合成材料，还没来得及点开合成界面，就会一脚踩进别人埋好的连环雷阵里？」</span><br>";
    }elseif($fnumber == 929){
        $log .= "<span class=\"lime\">「如果把耳机左右声道反过来戴，是不是就能听见从背后偷袭你的敌人在面前走路的声音？」</span><br>";
    }elseif($fnumber == 930){
        $log .= "<span class=\"lime\">「９３０！九百三十个刻度已经走过，距离千点大关只剩下最后的七十步冲刺！」</span><br>";
    }elseif($fnumber == 931){
        $log .= "<span class=\"lime\">「你在地上捡到了一枚生锈的指南针，不管你怎么转，它的指针永远坚定不移地指向离你最近的食堂。」</span><br>";
    }elseif($fnumber == 932){
        $log .= "<span class=\"lime\">「为什么数据库驱动文件既有 db_mysqli.class.php 又有 db_pdo.class.php？因为不管时代怎么变，只要包一层同名的 query() 方法，老代码就能永远活下去！」</span><br>";
    }elseif($fnumber == 933){
        $log .= "<span class=\"lime\">「如果把一张钞票对折八次，它虽然不会变得更有钱，但会变得像小砖头一样厚实。」</span><br>";
    }elseif($fnumber == 934){
        $log .= "<span class=\"lime\">「你在九又四分之三站台前面往墙上猛冲过去——咚的一声，墙没穿过去，你的脑门上倒是多了一个大包。」</span><br>";
    }elseif($fnumber == 935){
        $log .= "<span class=\"lime\">「９３５研究小组留下的神秘元素115正在发光，好在这里没有成群结队的僵尸，只有满地跑的挑战者。」</span><br>";
    }elseif($fnumber == 936){
        $log .= "<span class=\"lime\">「谜语：每一粒真正的种火都是一段没来得及说完的话；当你在深渊里俯身倾听，火苗里传出的究竟是孩童的嬉闹，还是造物者的叹息？」</span><br>";
    }elseif($fnumber == 937){
        $log .= "<span class=\"lime\">「你在路边看到一棵歪脖子树，树干上挂着一块牌子：『此处风景优美，适合思考下一局怎么开局。』」</span><br>";
    }elseif($fnumber == 938){
        $log .= "<span class=\"lime\">「为什么有些技能的名字听起来霸气侧漏，实际效果却是让你逃跑的速度变快一点点？因为三十六计走为上计！」</span><br>";
    }elseif($fnumber == 939){
        $log .= "<span class=\"lime\">「如果把所有的感冒病毒都抓起来关进回收站，然后点击『清空回收站』，人类是不是就再也不会流鼻涕了？」</span><br>";
    }elseif($fnumber == 940){
        $log .= "<span class=\"lime\">「９４０！谐音『就是你』！命运的手指在人群中转了三圈，最后精准地指在了你的鼻尖上！」</span><br>";
    }elseif($fnumber == 941){
        $log .= "<span class=\"lime\">「你在旧书摊上买了一本《防骗指南》，付款之后老板一溜烟跑没影了，你翻开书发现里面全是白纸。」</span><br>";
    }elseif($fnumber == 942){
        $log .= "<span class=\"lime\">「1942年的双翼机在弹幕中穿梭，CAPCOM经典飞行射击的魂在你的手指尖熊熊燃烧！」</span><br>";
    }elseif($fnumber == 943){
        $log .= "<span class=\"lime\">「为什么每次看到别人切糕多得花不完，再看看自己个位数的切糕余额，都会产生一种处于不同通货膨胀宇宙的错觉？」</span><br>";
    }elseif($fnumber == 944){
        $log .= "<span class=\"lime\">「如果把枕头晒过太阳之后的味道装进罐头里卖，一定能成为畅销全球的助眠神器。」</span><br>";
    }elseif($fnumber == 945){
        $log .= "<span class=\"lime\">「谜语：在BASSLINE与#104B6的交界处，有人为了逃离废土而跨越深渊，也有人为了追索旧债而踏雪而来。」</span><br>";
    }elseif($fnumber == 946){
        $log .= "<span class=\"lime\">「你在草丛里发现了一只正在装死的负鼠，演技逼真到连系统差点都给它刷出了尸体摸包界面。」</span><br>";
    }elseif($fnumber == 947){
        $log .= "<span class=\"lime\">「为什么有些武器合出来之后名字会变成一串问号？因为编码格式一旦没对齐，神器也会变成天书！」</span><br>";
    }elseif($fnumber == 948){
        $log .= "<span class=\"lime\">「如果把今天要做的事情推到明天，明天再推到后天，只要推的速度超过光速，DDL就永远追不上你！」</span><br>";
    }elseif($fnumber == 949){
        $log .= "<span class=\"lime\">「９４９！左右对称的九四九，像不像两只气球中间夹着一把小旗子？」</span><br>";
    }elseif($fnumber == 950){
        $log .= "<span class=\"lime\">「９５０！距离1024的终点只剩下最后74步了！你的呼吸依然平稳，代码的行数却在飞速暴涨！」</span><br>";
    }elseif($fnumber == 951){
        $log .= "<span class=\"lime\">「你在路边捡到了一张写着『好运来』的红纸剪纸，虽然贴在防弹衣上并不能增加防御力，但看起来非常喜庆。」</span><br>";
    }elseif($fnumber == 952){
        $log .= "<span class=\"lime\">「９５２７！华安，从今天起你的终身代号就是9527了！多谢华夫人赏赐的唐伯虎真迹与含笑半步癫！」</span><br>";
    }elseif($fnumber == 953){
        $log .= "<span class=\"lime\">「为什么在游戏里喝毒药死亡和被核弹炸死，最后结算出来的墓志铭都一样简短？因为死人是不需要写长篇小说的！」</span><br>";
    }elseif($fnumber == 954){
        $log .= "<span class=\"lime\">「谜语：当你在虚拟的牌桌上面对那十六簇性格各异的小火苗，别忘了它们打出的每一张牌，都是幻境自我演化的呼吸。」</span><br>";
    }elseif($fnumber == 955){
        $log .= "<span class=\"lime\">「９５５！朝九晚五，每周工作五天！这是无数程序员梦寐以求的理想乡作息时间！」</span><br>";
    }elseif($fnumber == 956){
        $log .= "<span class=\"lime\">「如果把冰箱门打开对着房间吹，房间不仅不会变凉快，月底的电费账单还会让你心头一凉。」</span><br>";
    }elseif($fnumber == 957){
        $log .= "<span class=\"lime\">「你在废墟里翻出了一台老式传真机，它吐出了一张纸，上面印着：『请问您看到我的墨盒了吗？』」</span><br>";
    }elseif($fnumber == 958){
        $log .= "<span class=\"lime\">「为什么有些玩家的RP值能叠到几千甚至上万？因为他们在常磐犯下的罪孽已经连功德箱都装不下了！」</span><br>";
    }elseif($fnumber == 959){
        $log .= "<span class=\"lime\">「保时捷959拉力赛车在泥泞的林间小道上狂飙而过，溅了路边的野怪一身泥点子。」</span><br>";
    }elseif($fnumber == 960){
        $log .= "<span class=\"lime\">「三十一的平方是961，而你偏偏停在了960！就差最后那1点，强迫症的DNA又开始蠢蠢欲动了！」</span><br>";
    }elseif($fnumber == 961){
        $log .= "<span class=\"lime\">「９６１！三十一的平方终于合拢！刚才在960悬着的那口气，现在终于可以舒舒服服地吐出来了！」</span><br>";
    }elseif($fnumber == 962){
        $log .= "<span class=\"lime\">「你在树下捡到了一颗掉下来的苹果，虽然没有砸出万有引力定律，但吃下去实打实地恢复了5点体力——才怪，你没吃。」</span><br>";
    }elseif($fnumber == 963){
        $log .= "<span class=\"lime\">「谜语：黑衣的少女与白衣的军长拥有着相同的姓名与相反的底色；正数与负数的时空在湾城隔空对峙，谁也不曾轻易落下第一颗棋子。」</span><br>";
    }elseif($fnumber == 964){
        $log .= "<span class=\"lime\">「为什么每次残血逃命的时候都会连续三次搜到同一个刚才被你打跑的敌人？这就是常磐著名的『转角遇到爱』定律！」</span><br>";
    }elseif($fnumber == 965){
        $log .= "<span class=\"lime\">「如果把所有的蚊子都改造成只会吸脂肪不会吸血的益虫，人类一定会心甘情愿地在夏天敞开蚊帐睡觉。」</span><br>";
    }elseif($fnumber == 966){
        $log .= "<span class=\"lime\">「９６６！虽然比996少了三个小时，但打工人表示还是955听起来更顺耳一点。」</span><br>";
    }elseif($fnumber == 967){
        $log .= "<span class=\"lime\">「你在路边看到一块写着『此处禁止随地大小变』的告示牌，变身系魔法少女看了之后陷入了长久的沉默。」</span><br>";
    }elseif($fnumber == 968){
        $log .= "<span class=\"lime\">「为什么游戏里的商店从来不会被人打劫？因为商店老板根本没有实体血条，你连攻击按钮都点不出来！」</span><br>";
    }elseif($fnumber == 969){
        $log .= "<span class=\"lime\">「９６９！九六九，走一走！顺着这条小路一直走下去，说不定就能走到没有禁区的桃花源。」</span><br>";
    }elseif($fnumber == 970){
        $log .= "<span class=\"lime\">「GTX 970显卡看着自己3.5G+0.5G的显存配置，露出了欣慰而历史悠久的微笑。」</span><br>";
    }elseif($fnumber == 971){
        $log .= "<span class=\"lime\">「你在地上捡到了一枚写着『第971号实验品』的金属铭牌，背面刻着一行小字：『如拾获此牌，请勿喂食凸眼鱼。』」</span><br>";
    }elseif($fnumber == 972){
        $log .= "<span class=\"lime\">「谜语：那位自称只懂一点点皮毛的白发青年，总能在危机降临的关头恰好掏出最需要的工具；至于那份许可从何而来，只有空白的档案袋知晓。」</span><br>";
    }elseif($fnumber == 973){
        $log .= "<span class=\"lime\">「９７３计划重点基础研究项目启动！今天的课题是：为什么幸运骰子总是在我最需要大点数的时候骰出1？」</span><br>";
    }elseif($fnumber == 974){
        $log .= "<span class=\"lime\">「如果把耳机线塞进口袋里晃三下就能自动打成死结，为什么不能把鞋带塞进口袋里晃三下让它自动系好呢？」</span><br>";
    }elseif($fnumber == 975){
        $log .= "<span class=\"lime\">「９７５！距离一千大关只剩下最后的二十五步！千面骰的棱角已经在天空中闪闪发光了！」</span><br>";
    }elseif($fnumber == 976){
        $log .= "<span class=\"lime\">「你在草丛里发现了一只正在做俯卧撑的螳螂，它看了你一眼，用两把大刀比划了一个挑衅的手势。」</span><br>";
    }elseif($fnumber == 977){
        $log .= "<span class=\"lime\">「为什么每次看到代码里写着 //TODO 的地方，git blame 一查提交时间都是十年前？因为明天的事情永远可以留给下个十年的维护者！」</span><br>";
    }elseif($fnumber == 978){
        $log .= "<span class=\"lime\">「国际标准书号ISBN的前缀永远是978！恭喜你，你的这段逃杀经历已经具备了正式出版成书的号码资格！」</span><br>";
    }elseif($fnumber == 979){
        $log .= "<span class=\"lime\">「如果把闹钟放在离床五米远的桌子上，第二天早上你就会学会在不睁开眼睛的情况下精准完成五米跨栏并按掉闹钟。」</span><br>";
    }elseif($fnumber == 980){
        $log .= "<span class=\"lime\">「９８０！当年的一代神卡GTX 980向你发来贺电，虽然这套PHP网页游戏用计算器都能跑满帧！」</span><br>";
    }elseif($fnumber == 981){
        $log .= "<span class=\"lime\">「谜语：九九八十一难的尽头是灵山，而在常磐的尽头，四条通往解禁与解离的道路正等待着最后一位幸存者的抉择。」</span><br>";
    }elseif($fnumber == 982){
        $log .= "<span class=\"lime\">「你在路边看到一台坏掉的电子秤，站上去之后它显示你的体重是 -32768 公斤，恭喜你成功溢出了有符号短整型！」</span><br>";
    }elseif($fnumber == 983){
        $log .= "<span class=\"lime\">「为什么游戏里有些道具叫『奇怪的药水』，有些叫『可疑的药水』？因为起名废策划在词穷的时候翻遍了近义词词典！」</span><br>";
    }elseif($fnumber == 984){
        $log .= "<span class=\"lime\">「１９８４的老大哥正在通过监控摄像头看着你——看到你连续三次合成失败之后，老大哥也忍不住叹了口气。」</span><br>";
    }elseif($fnumber == 985){
        $log .= "<span class=\"lime\">「９８５工程重点高校录取通知书从天而降！可惜在常磐台中学里，学历并不能替你挡住飞来的电磁炮。」</span><br>";
    }elseif($fnumber == 986){
        $log .= "<span class=\"lime\">「如果把一块冰块放进保温杯里，保温杯到底是在努力帮它保冷，还是在纠结自己为什么不叫保冷杯？」</span><br>";
    }elseif($fnumber == 987){
        $log .= "<span class=\"lime\">「斐波那契数列的第16项是987！这也是1000以内最大的一个斐波那契数，黄金分割的旋律在此刻达到了高潮！」</span><br>";
    }elseif($fnumber == 988){
        $log .= "<span class=\"lime\">「９８８！久发发！愿你的暴击率像这个数字一样长长久久、一路高歌！」</span><br>";
    }elseif($fnumber == 989){
        $log .= "<span class=\"lime\">「你在旧诊所的墙壁上看到一行用红药水写的字：『不要相信任何血量超过一百万的饭盒！』」</span><br>";
    }elseif($fnumber == 990){
        $log .= "<span class=\"lime\">「谜语：『盒子不会自己长东西。』——当你在幻境里拾起每一柄似曾相识的兵刃，那上面残留的，都是某个人曾经深爱或畏惧过的记忆。」</span><br>";
    }elseif($fnumber == 991){
        $log .= "<span class=\"lime\">「９９１！距离999只差八步，距离1024只差三十三步，连空气里的随机数因子都开始排起长队了！」</span><br>";
    }elseif($fnumber == 992){
        $log .= "<span class=\"lime\">「为什么每次你在游戏里捡到好东西想截图发群里炫耀的时候，下一秒就会立刻弹出一行红色的死亡通告？这就是『截图必死定律』！」</span><br>";
    }elseif($fnumber == 993){
        $log .= "<span class=\"lime\">「１０００减７等于多少？——９９３！金木研掰响了手指骨节，白发的残影在你的脑海中一闪而过！」</span><br>";
    }elseif($fnumber == 994){
        $log .= "<span class=\"lime\">「如果把所有的袜子都买成完全相同的款式和颜色，洗衣机吞掉一只袜子的时候是不是就再也不会被发现了？」</span><br>";
    }elseif($fnumber == 995){
        $log .= "<span class=\"lime\">「９９５！救救我！来自残血萌新的呼救声在公屏上回荡，然后各路大佬纷纷赶来——并顺手收走了他的人头。」</span><br>";
    }elseif($fnumber == 996){
        $log .= "<span class=\"lime\">「９９６福报降临！高强度的加班消耗了你9点体力，但老板含泪给你结了96块钱加班费！」</span><br>";
        $sp = max(1, $sp - 9);
        $money += 96;
    }elseif($fnumber == 997){
        $log .= "<span class=\"lime\">「９９７！这是1000以内最大的一个质数！它孤高地站在百位数的尽头，谁也别想把它拆成两个整数的乘积！」</span><br>";
    }elseif($fnumber == 998){
        $log .= "<span class=\"lime\">「只要９９８！电视购物主持人声嘶力竭地咆哮着：不要9999，不要1998，只要998，八星八钻的常磐纪念币带回家！」</span><br>";
    }elseif($fnumber == 999){
        $log .= "<span class=\"lime\">「９９９！银河铁道999号列车呼啸而过，车窗里坐着当年在圣诞夜消了999行俄罗斯方块的笔者。」</span><br>";
    }elseif($fnumber == 1000){
        $log .= "<span class=\"lime\">「１０００！千面之骰在天空中一分为三，命运的齿轮开始以三倍速疯狂旋转，你的生命与体力上限各提升了10点！」</span><br>";
        $mhp += 10;
        $hp += 10;
        $msp += 10;
        $sp += 10;
    }elseif($fnumber == 1001){
        $log .= "<span class=\"lime\">「一千零一夜的故事拉开了序幕，山鲁佐德向国王讲起了第一个故事：『从前有一群挑战者，被困在一个用PHP写成的幻境里……』」</span><br>";
    }elseif($fnumber == 1002){
        $log .= "<span class=\"lime\">「跨过千点大关后的第二步！普通千面骰已经无法抵达这片领域，唯有叠加了梦之数值的天选者才能窥见此处的风景！」</span><br>";
    }elseif($fnumber == 1003){
        $log .= "<span class=\"lime\">「御坂妹妹10032号推了推护目镜：『御坂对这片突然刷出电磁炮的常磐台中学表示高度警惕，御坂如此汇报。』」</span><br>";
    }elseif($fnumber == 1004){
        $log .= "<span class=\"lime\">「１０－４！无线电通讯用语『收到（Roger that）』！总部已收到您的幸运信号，祝您一路顺风！」</span><br>";
    }elseif($fnumber == 1005){
        $log .= "<span class=\"lime\">「如果把一千零五颗幸运星折好塞进许愿瓶里，许愿瓶会不会因为塞得太满而直接裂开？」</span><br>";
    }elseif($fnumber == 1006){
        $log .= "<span class=\"lime\">「为什么 randomFortune 函数在填满1024条之后看起来像一座摩天大楼？因为每一层楼都住着一个不吐不快的梗！」</span><br>";
    }elseif($fnumber == 1007){
        $log .= "<span class=\"lime\">「你在超越千点的虚空中捡到了一片羽毛，上面写着：『能走到这里的骰子，至少也是个带加值的神骰了。』」</span><br>";
    }elseif($fnumber == 1008){
        $log .= "<span class=\"lime\">「１００８６！移动客服小姐姐温柔的声音在脑海中响起：『您好，您本月的幸运流量已超出套餐上限，将继续为您免费加速。』」</span><br>";
    }elseif($fnumber == 1009){
        $log .= "<span class=\"lime\">「１００９！这是超过一千之后的第一个质数，像一位守在千门之外的哨兵，向你行了一个标准的军礼。」</span><br>";
    }elseif($fnumber == 1010){
        $log .= "<span class=\"lime\">「二进制的 1010 等于十进制的 10，而十进制的 1010 正好是一双手十指大动、十全十美的双倍快乐！」</span><br>";
    }elseif($fnumber == 1011){
        $log .= "<span class=\"lime\">「谜语：当第一千零一十一道波纹在湖底的冷却机房里荡开，玄武公园的游人依旧在喂着锦鲤，浑然不觉脚下的世界刚刚完成了一次重演。」</span><br>";
    }elseif($fnumber == 1012){
        $log .= "<span class=\"lime\">「你在虚空回廊的墙壁上看到了前人留下的涂鸦：『再往前走十二步，就是2的10次方了！』」</span><br>";
    }elseif($fnumber == 1013){
        $log .= "<span class=\"lime\">「如果把一千零一十三行 if...elseif 全部打印在A4纸上，连起来的长度足够从常磐台中学直接铺到清水池！」</span><br>";
    }elseif($fnumber == 1014){
        $log .= "<span class=\"lime\">「距离1024的王座只剩最后十步！倒计时十、九、八……连空气中的电子都开始随着二进制的节拍跃动！」</span><br>";
    }elseif($fnumber == 1015){
        $log .= "<span class=\"lime\">「你在第1015级台阶上捡到了一枚发光的键帽，上面印着已经被磨平的 Ctrl 键字母。」</span><br>";
    }elseif($fnumber == 1016){
        $log .= "<span class=\"lime\">「为什么程序员数数总是从0开始，到1024结束？因为在这个由0和1构筑的世界里，2的幂次才是真正的整百整千！」</span><br>";
    }elseif($fnumber == 1017){
        $log .= "<span class=\"lime\">「谜语：无论是表传的侦探、里传的特使、怪传的流亡者，还是断章里的少年少女——所有的故事，终将在银白方盒折射出的火光里交汇。」</span><br>";
    }elseif($fnumber == 1018){
        $log .= "<span class=\"lime\">「１０１８！距离终极的1024只剩六步之遥，你的六系熟练度在隐隐共鸣，等待着登顶的那一刻！」</span><br>";
    }elseif($fnumber == 1019){
        $log .= "<span class=\"lime\">「如果把这1024条命运全部背下来，你大概就能成为常磐大逃杀历史上第一位『命运学博士』了！」</span><br>";
    }elseif($fnumber == 1020){
        $log .= "<span class=\"lime\">「１０２０！最后四步！你仿佛已经看到了坐在1024王座上的随机数大神正冲你举起茶杯！」</span><br>";
    }elseif($fnumber == 1021){
        $log .= "<span class=\"lime\">「１０２１！这是1024之前最后一个质数！它像最后一道守护结界，替终极的二进制圣殿把守着大门！」</span><br>";
    }elseif($fnumber == 1022){
        $log .= "<span class=\"lime\">「１０２２！源码文件末尾那句『//Yes, this file would probably be very large.』在此刻得到了最完美的预言成真！」</span><br>";
    }elseif($fnumber == 1023){
        $log .= "<span class=\"lime\">「１０２３！二进制 1111111111！十个1整齐地排成一排，只差最后一点星火，就能进位成辉煌的1024！」</span><br>";
    }elseif($fnumber == 1024){
        $log .= "<span class=\"lime\">「１０２４！这是第三梦之数值的终极顶点，也是程序员的节日！代码的祝福令你全系熟练度＋１０！」</span><br>";
        $wp += 10;
        $wk += 10;
        $wg += 10;
        $wc += 10;
        $wd += 10;
        $wf += 10;
    }elseif($fnumber > 4096){
        $log .= "<span class=\"lime\">「当然，现在暂且还用不到，所以这些东西先放在这里。」</span><br>";
    }elseif($fnumber > 3086){
        $log .= "<span class=\"lime\">「变量fnumber不一定非要是梦2记系统的值，也可以在调用时算一下。」</span><br>";
    }elseif($fnumber > 3072){
        $log .= "<span class=\"lime\">「根据骰出来的结果不同，甚至会影响玩家各种数值。」</span><br>";
    }elseif($fnumber > 2048){
        $log .= "<span class=\"lime\">「说白了，这东西就是我梦见的神骰。」</span><br>";
    }elseif($fnumber > 1024){
        $log .= "<span class=\"lime\">「总之各位神和巫师们可以有事没事就会来这里加点东西。」</span><br>";
    }else{
        $log .= "<span class=\"red\">「老实坦白交代，你看代码了吧……？」</span><br>";
    }
}

//Yes, this file would probably be very large.
//Whatever.
?>