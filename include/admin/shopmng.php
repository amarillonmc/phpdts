<?php
if(!defined('IN_ADMIN')) exit('Access Denied');
require_once GAME_ROOT.'./include/fcg/account.php';
require_once GAME_ROOT.'./include/vnworld/vncreation.func.php';
vncreation_start_session();
fcg_ensure_storage();
$shop_message='';
try {
	if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['shop_save'])) {
		foreach(array('item_id','token','name','description','reward_payload') as $field) if(isset($_POST[$field]) && !is_scalar($_POST[$field])) throw new InvalidArgumentException('商品表单格式无效。');
		$id=vncreation_int($_POST['item_id'] ?? '',0,2147483647);
		vncreation_check_token($_POST['token'] ?? '','shop:'.$id);
		$category=$_POST['category'] ?? ''; $type=$_POST['reward_type'] ?? '';
		if(!in_array($category,array('fcg_pack','title','special'),true) || !in_array($type,array('fcg_pack','title','custom'),true)) throw new InvalidArgumentException('商品类别或发货类型无效。');
		$name=trim($_POST['name'] ?? ''); $description=trim($_POST['description'] ?? '');
		if($name==='' || mb_strlen($name,'UTF-8')>60 || mb_strlen($description,'UTF-8')>2000) throw new InvalidArgumentException('名称限 1～60 字，说明限 2000 字。');
		$price=vncreation_int($_POST['price_credits2'] ?? '',0,8388607);
		$daily=vncreation_int($_POST['daily_limit'] ?? '',0,1000000);
		$total=vncreation_int($_POST['total_limit'] ?? '',0,1000000);
		$sort=vncreation_int($_POST['sort_order'] ?? '',-1000000,1000000);
		if($type==='fcg_pack') {
			$payload=array('count'=>1,'color'=>$_POST['pack_color'] ?? 'all','series'=>$_POST['pack_series'] ?? 'all');
			foreach(array('upgraded','rare') as $filter) {
				$value=$_POST['pack_'.$filter] ?? 'all';
				if(!in_array($value,array('all','yes','no'),true)) throw new InvalidArgumentException('卡池筛选无效。');
				if($value!=='all') $payload[$filter]=$value==='yes';
			}
			fcg_pack_pool($payload);
		} elseif($type==='title') {
			$title=vncreation_int($_POST['title_id'] ?? '',0,2147483647);
			if(!isset($titles_list[$title])) throw new InvalidArgumentException('请选择有效头衔。');
			$payload=array('title'=>$title);
		} else {
			$payload=json_decode($_POST['reward_payload'] ?? '',true);
			if(!is_array($payload)) throw new InvalidArgumentException('扩展发货内容须为合法 JSON 对象。');
		}
		$enabled=isset($_POST['enabled']) ? 1 : 0;
		$values=array('category'=>$category,'name'=>$name,'description'=>$description,'price_credits2'=>$price,'reward_type'=>$type,'reward_payload'=>fcg_json($payload),'daily_limit'=>$daily,'total_limit'=>$total,'sort_order'=>$sort,'enabled'=>$enabled);
		$escaped=array(); foreach($values as $key=>$value) $escaped[$key]=is_int($value) ? (string)$value : fcg_sql($value);
		if($id) {
			$set=array(); foreach($escaped as $key=>$value) $set[]=$key.'='.$value;
			$db->query("UPDATE {$gtablepre}shop_items SET ".implode(',',$set).' WHERE item_id='.$id);
		} else {
			$db->query("INSERT INTO {$gtablepre}shop_items (".implode(',',array_keys($values)).') VALUES ('.implode(',',$escaped).')');
			$id=$db->insert_id();
		}
		adminlog('shopmng',$id,'price='.$price.';daily='.$daily.';total='.$total.';enabled='.$enabled,hash('sha256',fcg_json($values)));
		unset($_SESSION['vncreation_forms'][$_POST['token']]);
		$shop_message='商品已保存，新的购买请求立即使用新配置。';
	}
} catch(Exception $e) { $shop_message=$e->getMessage(); }
$shop_items=fcg_catalog(true);
$shop_items[0]=array('item_id'=>0,'category'=>'fcg_pack','name'=>'','description'=>'','price_credits2'=>10,'reward_type'=>'fcg_pack','reward_payload'=>'{"upgraded":false,"count":1}','daily_limit'=>0,'total_limit'=>0,'sort_order'=>100,'enabled'=>1);
$shop_categories=array('fcg_pack'=>'种火卡包铺','title'=>'头衔','special'=>'其他奇物');
$shop_types=array('fcg_pack'=>'单卡补充包','title'=>'头衔','custom'=>'扩展商品（尚未接发货逻辑）');
$shop_colors=array('all'=>'全部颜色','W'=>'白','R'=>'红','Y'=>'黄','G'=>'绿','B'=>'蓝');
$shop_series=array('all'=>'全部系列','vol1'=>'Vol.1 · 种火初啼','vol2'=>'Vol.2 · 红杀演武','vol3'=>'Vol.3 · 常磐万象 & 电波狂想');
$shop_filters=array('all'=>'不限','yes'=>'是','no'=>'否');
$shop_group_names=$shop_categories+array('new'=>'新增商品');
$shop_groups=array_fill_keys(array_keys($shop_group_names),array());
foreach($shop_items as $shop_item) {
	$shop_item['token']=vncreation_form_token('shop:'.$shop_item['item_id']);
	$payload=json_decode($shop_item['reward_payload'],true) ?: array();
	$shop_item['title_id']=$payload['title'] ?? 0;
	$shop_item['pack_color']=$payload['color'] ?? 'all';
	$shop_item['pack_series']=$payload['series'] ?? 'all';
	if(!empty($payload['rare_only'])) $payload['rare']=true;
	foreach(array('upgraded','rare') as $filter) $shop_item['pack_'.$filter]=isset($payload[$filter]) ? ($payload[$filter]?'yes':'no') : 'all';
	$shop_groups[$shop_item['item_id']?$shop_item['category']:'new'][]=$shop_item;
}
session_write_close();
include template('admin_shopmng');
