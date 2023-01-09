<?php

	if(!defined('IN_GAME')) {
		exit('Access Denied');
	}

	//global haomafan  wo hao miss mingmingspace
	global $elements_info;
	global $no_type_to_e_list,$no_itmk_to_e_list,$no_itmsk_to_e_list,$no_emix_circulation;
	global $corpse_to_e_lvl_r,$itmk_to_e_r,$itmsk_to_e,$itmsk_to_e_r;

	//过滤：
	//不可以被拆解的NPC类型
	$no_type_to_e_list = Array();
	//不可以被拆解的道具类别
	$no_itmk_to_e_list = Array('N','WN','Y','Z','p','fy','ygo');
	//不可以被拆解的道具属性
	$no_itmsk_to_e_list = Array('v','^');
	//使用元素合成的道具自带灵魂绑定属性（启用：1）
	$no_emix_circulation = 0;

	//系数：
	//元素合成的效果初始上限值（合成出的道具/装备效果不会超过这个值）
	$max_emix_itme_start = 45;
	//每1级能够提升的上限值 （前32级时的计算公式：提升值*等级*(100-等级)%）（32级后开始数值膨胀：（提升值+等级）*等级*(1+等级/（255-等级）)）
	$max_emix_itme_up = 29;
	//拆解的对象等级与获得元素的数量关系（默认：等级*5）
	$corpse_to_e_lvl_r = 5; 
	//拆解特定类别道具时获得元素数量的修正系数（默认：1倍）（基础的元素数量：效果+耐久/2 *系数 *浮动）
	//给自己一个参考，拆掉一个小兵能获得的元素（等级10*5+武器100/2+装备55/2*4=210），好像勉强还能和钱对标，但是到真职人开始可能就要崩盘了
	//真职人能提供多少元素？（等级30*5+武器88+888/2+装备888+888/2*4=4190 好像也还能接受！）
	$itmk_to_e_r = Array
	(
		//拆解复合武器获得的元素数量x2.5
		'WGK' => 2.5,
		'WCF' => 2.5,
		'WCP' => 2.5,
		'WKF' => 2.5,
		'WKP' => 2.5,
		'WFK' => 2.5,
		'WDG' => 2.5,
		'WDF' => 2.5,
		'WJ' => 1.25,
		//拆解补给品获得的元素数量x0.1
		'HH' => 0.1,	
		'HS' => 0.1,
		'HB' => 0.2,
		'PH' => 0.12,
		'PS' => 0.12,
		'PB' => 0.25,	
		//拆解弹药……这也要拆！？
		'GBh' => 5,
		'GBr' => 0.003,
		'GBi' => 0.005,
		'GBe' => 0.005,
		'GB' => 0.004,	
	);
	//拆解特定属性时获得元素数量（种类）的基础值
	$itmsk_to_e = Array
	(
		'x' => 999997,//奇迹
		'e' => Array(0=>77),//电击 固定给77个亮晶晶
		'u' => Array(1=>101),//火焰 固定给101个暖洋洋
		'i' => Array(2=>121),//冻气 固定给121个冷冰冰
		'p' => Array(3=>22),//带毒 固定给22个郁萌萌
		'w' => Array(4=>141),//音波 固定给141个昼闪闪
		'S' => Array(5=>21),//消音 固定会给21个夜静静
	);
	//指定某些道具类别能拆出特定种类的元素、数量
	$itmk_to_e_list = Array();

	/********拆解元素部分********/

	//把尸体打散成元素
	function split_corpse_to_elements(&$edata)
	{
		if($edata)
		{
			global $elements_info,$no_type_to_e_list;
			global $log,$rp;
			//过滤不能分解的尸体
			if(in_array($edata['type'],$no_type_to_e_list))
			{
				$log.="无法从这具尸体上提炼元素！<br>";
				return;
			}
			//成功从尸体中提炼元素
			$log.="{$edata['name']}化作点点荧光四散开来……<br>";
			//计算能获得的元素种类与数量
			$get_elements = 13;
			//增加对应的元素
			foreach($elements_info as $e_key=>$e_info)
			{
				global ${'element'.$e_key};
				${'element'.$e_key} += $get_elements;
				$log.="从{$edata['name']}身上提取到了{$get_elements}份{$e_info}！<br>";
			}
			//销毁尸体
			destory_corpse($edata);
			//炼人油败人品
			$ep_dice = rand(0,100);
			if($ep_dice>70)
			{
				$rp += $ep_dice;
				$log.="……但是这一切真的值得吗？<br>";
			}
			$log.="<br>";
		}
		return;
	}

	//把道具打散成元素 改下传入的参数其实也可以拆装备
	function split_item_to_elements($iid=NULL)
	{
		if(isset($iid))
		{
			global $elements_info,$no_itmk_to_e_list,$no_itmsk_to_e_list,$itmk_to_e_list;
			global $log,$rp;
			global ${'itm'.$iid},${'itmk'.$iid},${'itme'.$iid},${'itms'.$iid},${'itmsk'.$iid};
			if(!${'itms'.$iid})
			{
				$log.="道具来源非法。<br>";
				return;
			}
			//把道具属性打散成数组
			if(${'itmsk'.$iid})  $tmp_itmsk_arr = get_itmsk_array(${'itmsk'.$iid});
			//过滤不能分解的道具属性、类型
			if(in_array(${'itmk'.$iid},$no_itmk_to_e_list) || array_intersect($tmp_itmsk_arr,$no_itmsk_to_e_list))
			{
				$log.="不能分解此类道具。<br><br>";
				include_once GAME_ROOT.'./include/game/itemmain.func.php';
				itemfind();
				return;
			}
			//分解道具获得元素
			$log.=${'itm'.$iid}."化作点点荧光四散开来……<br>";
			//计算能获得的元素种类与数量
			$get_elements = 13;
			$e_key = array_rand($elements_info);
			//增加对应的元素
			global ${'element'.$e_key};
			${'element'.$e_key} += $get_elements;
			$log.="提取到了{$get_elements}份{$elements_info[$e_key]}！<br>";
			//销毁道具
			${'itm'.$iid} = ${'itmk'.$iid} = ${'itmsk'.$iid} = '';
			${'itme'.$iid} = ${'itms'.$iid} = 0;
			//捡垃圾涨功德
			$ep_dice = rand(0,100);
			if($ep_dice>70)
			{
				$rp = max(0,$rp-$ep_dice);
				//$log.="忽闻遥远天外飞来一句赞叹：“感谢你对幻境环卫事业作出的贡献！”<br>";
			}
			$log.="<br>";
		}
		$mode='command';
		return;
	}

	/********元素合成部分********/

	function element_mix($emlist,$eitmk=NULL,$eitme_r=NULL)
	{
		global $log,$elements_info;
		//输入了合法的元素参数 打印log
		$log.="从口袋中抓出了";
		//好看修正
		$log_prefix = 0;
		foreach($emlist as $log_e_key=>$log_e_num)
		{
			if($log_prefix>0) $log.="、";
			$log.="{$log_e_num}份{$elements_info[$log_e_key]}";
			$log_prefix++;
		}
		$log.="。<br>……<br>开始了合成。<br><br>";
		return;
	}

	/********一些可复用函数 也许可以挪到其他地方********/

	//销毁尸体
	function destory_corpse(&$edata)
	{
		if($edata && $edata['hp']<=0)
		{
			//$edata['state'] = 16;
			$edata['money'] = 0;
			$edata['weps'] = 0;$edata['arbs'] = 0;$edata['arhs'] = 0;$edata['aras'] = 0;$edata['arfs'] = 0;$edata['arts'] = 0;
			$edata['itms0'] = 0;$edata['itms1'] = 0;$edata['itms2'] = 0;$edata['itms3'] = 0;$edata['itms4'] = 0;$edata['itms5'] = 0;$edata['itms6'] = 0;
			player_save($edata);
		}
		return;
	}

	//数组化itmsk 可能是四面的遗产
	function get_itmsk_array($sk_value)
	{
		$ret = Array();
		$i = 0;
		while ($i < strlen($sk_value))
		{
			$sub = substr($sk_value,$i,1); 
			$i++;
			if(!empty($sub)) array_push($ret,$sub);
		}
		return $ret;		
	}

?>