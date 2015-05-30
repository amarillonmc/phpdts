<?php if(!defined('IN_GAME')) exit('Access Denied'); if($command=='check') { ?>
<form method="post" name="pcmng" onsubmit="admin.php">
<input type="hidden" name="mode" value="pcmng">
<input type="hidden" id="command" name="command" value="list">
<input type="hidden" name="start" value="<?php echo $start?>">
<input type="hidden" name="checkmode" value="<?php echo $checkmode?>">	
<input type="hidden" name="checkinfo" value="<?php echo $checkinfo?>">
<input type="hidden" name="name" value="<?php echo $pc['name']?>">
<input type="hidden" name="pid" value="<?php echo $pc['pid']?>">

<input type="hidden" name="itm0" value="<?php echo $pc['itm0']?>">
<input type="hidden" name="itme0" value="<?php echo $pc['itme0']?>">
<input type="hidden" name="itmk0" value="<?php echo $pc['itmk0']?>">
<input type="hidden" name="itms0" value="<?php echo $pc['itms0']?>">
<input type="hidden" name="itmsk0" value="<?php echo $pc['itmsk0']?>">
<table class="admin">
<tr>		
<th>属性名</th>
<th>属性值</th>
<th>装备属性</th>
<th>装备数值</th>
<th>包裹属性</th>
<th>包裹数值</th>
</tr>
<tr>		
<td>姓名</td>
<td><?php echo $pc['name']?></td>
<td>武器</td>
<td><input size="20" type="text" name="wep" value="<?php echo $pc['wep']?>" maxlength="30"></td>
<td>包裹1</td>
<td><input size="20" type="text" name="itm1" value="<?php echo $pc['itm1']?>" maxlength="30"></td>
</tr>
<tr>		
<td>性别</td>
<td><input size="20" type="text" name="gd" value="<?php echo $pc['gd']?>" maxlength="20"></td>
<td>类型</td>
<td><input size="20" type="text" name="wepk" value="<?php echo $pc['wepk']?>" maxlength="20"></td>
<td>类型</td>
<td><input size="20" type="text" name="itmk1" value="<?php echo $pc['itmk1']?>" maxlength="20"></td>
</tr>
<tr>
<td>学号</td>
<td><input size="20" type="text" name="sNo" value="<?php echo $pc['sNo']?>" maxlength="20"></td>
<td>效果</td>
<td><input size="20" type="text" name="wepe" value="<?php echo $pc['wepe']?>" maxlength="20"></td>
<td>效果</td>
<td><input size="20" type="text" name="itme1" value="<?php echo $pc['itme1']?>" maxlength="20"></td>
</tr>
<tr>
<td>头像</td>
<td><input size="20" type="text" name="icon" value="<?php echo $pc['icon']?>" maxlength="20"></td>
<td>耐久</td>
<td><input size="20" type="text" name="weps" value="<?php echo $pc['weps']?>" maxlength="20"></td>
<td>耐久</td>
<td><input size="20" type="text" name="itms1" value="<?php echo $pc['itms1']?>" maxlength="20"></td>
</tr>
<tr>
<td>社团</td>
<td><input size="20" type="text" name="club" value="<?php echo $pc['club']?>" maxlength="20"></td>
<td>子类型</td>
<td><input size="20" type="text" name="wepsk" value="<?php echo $pc['wepsk']?>" maxlength="20"></td>
<td>子类型</td>
<td><input size="20" type="text" name="itmsk1" value="<?php echo $pc['itmsk1']?>" maxlength="20"></td>
</tr>
<tr>
<td>生命</td>
<td><input size="20" type="text" name="hp" value="<?php echo $pc['hp']?>" maxlength="20"></td>
<td>防具(体)</td>
<td><input size="20" type="text" name="arb" value="<?php echo $pc['arb']?>" maxlength="30"></td>
<td>包裹2</td>
<td><input size="20" type="text" name="itm2" value="<?php echo $pc['itm2']?>" maxlength="30"></td>
</tr>
<tr>
<td>最大生命</td>
<td><input size="20" type="text" name="mhp" value="<?php echo $pc['mhp']?>" maxlength="20"></td>
<td>类型</td>
<td><input size="20" type="text" name="arbk" value="<?php echo $pc['arbk']?>" maxlength="20"></td>
<td>类型</td>
<td><input size="20" type="text" name="itmk2" value="<?php echo $pc['itmk2']?>" maxlength="20"></td>
</tr>
<tr>
<td>体力</td>
<td><input size="20" type="text" name="sp" value="<?php echo $pc['sp']?>" maxlength="20"></td>
<td>效果</td>
<td><input size="20" type="text" name="arbe" value="<?php echo $pc['arbe']?>" maxlength="20"></td>
<td>效果</td>
<td><input size="20" type="text" name="itme2" value="<?php echo $pc['itme2']?>" maxlength="20"></td>
</tr>
<tr>
<td>最大体力</td>
<td><input size="20" type="text" name="msp" value="<?php echo $pc['msp']?>" maxlength="20"></td>
<td>耐久</td>
<td><input size="20" type="text" name="arbs" value="<?php echo $pc['arbs']?>" maxlength="20"></td>
<td>耐久</td>
<td><input size="20" type="text" name="itms2" value="<?php echo $pc['itms2']?>" maxlength="20"></td>
</tr>
<tr>
<td>基础攻击</td>
<td><input size="20" type="text" name="att" value="<?php echo $pc['att']?>" maxlength="20"></td>
<td>子类型</td>
<td><input size="20" type="text" name="arbsk" value="<?php echo $pc['arbsk']?>" maxlength="20"></td>
<td>子类型</td>
<td><input size="20" type="text" name="itmsk2" value="<?php echo $pc['itmsk2']?>" maxlength="20"></td>
</tr>
<tr>
<td>基础防御</td>
<td><input size="20" type="text" name="def" value="<?php echo $pc['def']?>" maxlength="20"></td>
<td>防具(头)</td>
<td><input size="20" type="text" name="arh" value="<?php echo $pc['arh']?>" maxlength="30"></td>
<td>包裹3</td>
<td><input size="20" type="text" name="itm3" value="<?php echo $pc['itm3']?>" maxlength="30"></td>
</tr>
<tr>
<td>位置</td>
<td><input size="20" type="text" name="pls" value="<?php echo $pc['pls']?>" maxlength="20"></td>
<td>类型</td>
<td><input size="20" type="text" name="arhk" value="<?php echo $pc['arhk']?>" maxlength="20"></td>
<td>类型</td>
<td><input size="20" type="text" name="itmk3" value="<?php echo $pc['itmk3']?>" maxlength="20"></td>
</tr>
<tr>
<td>等级</td>
<td><input size="20" type="text" name="lvl" value="<?php echo $pc['lvl']?>" maxlength="20"></td>
<td>效果</td>
<td><input size="20" type="text" name="arhe" value="<?php echo $pc['arhe']?>" maxlength="20"></td>
<td>效果</td>
<td><input size="20" type="text" name="itme3" value="<?php echo $pc['itme3']?>" maxlength="20"></td>
</tr>
<tr>
<td>经验</td>
<td><input size="20" type="text" name="exp" value="<?php echo $pc['exp']?>" maxlength="20"></td>
<td>耐久</td>
<td><input size="20" type="text" name="arhs" value="<?php echo $pc['arhs']?>" maxlength="20"></td>
<td>耐久</td>
<td><input size="20" type="text" name="itms3" value="<?php echo $pc['itms3']?>" maxlength="20"></td>
</tr>
<tr>
<td>钱</td>
<td><input size="20" type="text" name="money" value="<?php echo $pc['money']?>" maxlength="20"></td>
<td>子类型</td>
<td><input size="20" type="text" name="arhsk" value="<?php echo $pc['arhsk']?>" maxlength="20"></td>
<td>子类型</td>
<td><input size="20" type="text" name="itmsk3" value="<?php echo $pc['itmsk3']?>" maxlength="20"></td>
</tr>
<tr>
<td>对手</td>
<td><input size="20" type="text" name="bid" value="<?php echo $pc['bid']?>" maxlength="20"></td>
<td>防具(腕)</td>
<td><input size="20" type="text" name="ara" value="<?php echo $pc['ara']?>" maxlength="30"></td>
<td>包裹4</td>
<td><input size="20" type="text" name="itm4" value="<?php echo $pc['itm4']?>" maxlength="30"></td>
</tr>
<tr>
<td>受伤</td>
<td><input size="20" type="text" name="inf" value="<?php echo $pc['inf']?>" maxlength="20"></td>
<td>类型</td>
<td><input size="20" type="text" name="arak" value="<?php echo $pc['arak']?>" maxlength="20"></td>
<td>类型</td>
<td><input size="20" type="text" name="itmk4" value="<?php echo $pc['itmk4']?>" maxlength="20"></td>
</tr>
<tr>
<td>怒气</td>
<td><input size="20" type="text" name="rage" value="<?php echo $pc['rage']?>" maxlength="20"></td>
<td>效果</td>
<td><input size="20" type="text" name="arae" value="<?php echo $pc['arae']?>" maxlength="20"></td>
<td>效果</td>
<td><input size="20" type="text" name="itme4" value="<?php echo $pc['itme4']?>" maxlength="20"></td>
</tr>
<tr>
<td>基础姿态</td>
<td><input size="20" type="text" name="pose" value="<?php echo $pc['pose']?>" maxlength="20"></td>
<td>耐久</td>
<td><input size="20" type="text" name="aras" value="<?php echo $pc['aras']?>" maxlength="20"></td>
<td>耐久</td>
<td><input size="20" type="text" name="itms4" value="<?php echo $pc['itms4']?>" maxlength="20"></td>
</tr>
<tr>
<td>应战策略</td>
<td><input size="20" type="text" name="tactic" value="<?php echo $pc['tactic']?>" maxlength="20"></td>
<td>子类型</td>
<td><input size="20" type="text" name="arask" value="<?php echo $pc['arask']?>" maxlength="20"></td>
<td>子类型</td>
<td><input size="20" type="text" name="itmsk4" value="<?php echo $pc['itmsk4']?>" maxlength="20"></td>
</tr>
<tr>
<td>杀人数</td>
<td><input size="20" type="text" name="killnum" value="<?php echo $pc['killnum']?>" maxlength="20"></td>
<td>防具(足)</td>
<td><input size="20" type="text" name="arf" value="<?php echo $pc['arf']?>" maxlength="30"></td>
<td>包裹5</td>
<td><input size="20" type="text" name="itm5" value="<?php echo $pc['itm5']?>" maxlength="30"></td>
</tr>
<tr>
<td>殴熟</td>
<td><input size="20" type="text" name="wp" value="<?php echo $pc['wp']?>" maxlength="20"></td>
<td>类型</td>
<td><input size="20" type="text" name="arfk" value="<?php echo $pc['arfk']?>" maxlength="20"></td>
<td>类型</td>
<td><input size="20" type="text" name="itmk5" value="<?php echo $pc['itmk5']?>" maxlength="20"></td>
</tr>
<tr>
<td>斩熟</td>
<td><input size="20" type="text" name="wk" value="<?php echo $pc['wk']?>" maxlength="20"></td>
<td>效果</td>
<td><input size="20" type="text" name="arfe" value="<?php echo $pc['arfe']?>" maxlength="20"></td>
<td>效果</td>
<td><input size="20" type="text" name="itme5" value="<?php echo $pc['itme5']?>" maxlength="20"></td>
</tr>
<tr>
<td>枪熟</td>
<td><input size="20" type="text" name="wg" value="<?php echo $pc['wg']?>" maxlength="20"></td>
<td>耐久</td>
<td><input size="20" type="text" name="arfs" value="<?php echo $pc['arfs']?>" maxlength="20"></td>
<td>耐久</td>
<td><input size="20" type="text" name="itms5" value="<?php echo $pc['itms5']?>" maxlength="20"></td>
</tr>
<tr>
<td>投熟</td>
<td><input size="20" type="text" name="wc" value="<?php echo $pc['wc']?>" maxlength="20"></td>
<td>子类型</td>
<td><input size="20" type="text" name="arfsk" value="<?php echo $pc['arfsk']?>" maxlength="20"></td>
<td>子类型</td>
<td><input size="20" type="text" name="itmsk5" value="<?php echo $pc['itmsk5']?>" maxlength="20"></td>
</tr>
<tr>
<td>爆熟</td>
<td><input size="20" type="text" name="wd" value="<?php echo $pc['wd']?>" maxlength="20"></td>
<td>饰品</td>
<td><input size="20" type="text" name="art" value="<?php echo $pc['art']?>" maxlength="30"></td>
<td>包裹6</td>
<td><input size="20" type="text" name="itm6" value="<?php echo $pc['itm6']?>" maxlength="30"></td>
</tr>
<tr>
<td>灵熟</td>
<td><input size="20" type="text" name="wf" value="<?php echo $pc['wf']?>" maxlength="20"></td>
<td>类型</td>
<td><input size="20" type="text" name="artk" value="<?php echo $pc['artk']?>" maxlength="20"></td>
<td>类型</td>
<td><input size="20" type="text" name="itmk6" value="<?php echo $pc['itmk6']?>" maxlength="20"></td>
</tr>
<tr>
<td>队伍名称</td>
<td><input size="20" type="text" name="teamID" value="<?php echo $pc['teamID']?>" maxlength="20"></td>
<td>效果</td>
<td><input size="20" type="text" name="arte" value="<?php echo $pc['arte']?>" maxlength="20"></td>
<td>效果</td>
<td><input size="20" type="text" name="itme6" value="<?php echo $pc['itme6']?>" maxlength="20"></td>
</tr>
<tr>
<td>成就</td>
<td><input size="20" type="text" name="achievement" value="<?php echo $pc['achievement']?>" maxlength="500"></td>
<td>耐久</td>
<td><input size="20" type="text" name="arts" value="<?php echo $pc['arts']?>" maxlength="20"></td>
<td>耐久</td>
<td><input size="20" type="text" name="itms6" value="<?php echo $pc['itms6']?>" maxlength="20"></td>
</tr>
<tr>
<td>歌魂</td>
<td><input size="20" type="text" name="ss" value="<?php echo $pc['ss']?>" maxlength="20"></td>
<td>子类型</td>
<td><input size="20" type="text" name="artsk" value="<?php echo $pc['artsk']?>" maxlength="20"></td>
<td>子类型</td>
<td><input size="20" type="text" name="itmsk6" value="<?php echo $pc['itmsk6']?>" maxlength="20"></td>
</tr>
<tr>
<td>最大歌魂</td>
<td><input size="20" type="text" name="mss" value="<?php echo $pc['mss']?>" maxlength="20"></td>
<td></td>
<td></td>
<td></td>
<td></td>
</tr>
</table>
<input type="submit" value="修改玩家数值" onclick="$('command').value = 'submitedit'">
</form> 
<form method="post" name="pcmng" onsubmit="admin.php">
<input type="hidden" name="mode" value="pcmng">
<input type="hidden" name="command" value="list">
<input type="submit" value="返回玩家管理">
</form>
<?php } else { ?>
<form method="post" name="pcmng" onsubmit="admin.php">
<input type="hidden" name="mode" value="pcmng">
<input type="hidden" id="command" name="command" value="find">
<input type="hidden" name="start" value="<?php echo $start?>">
<input type="hidden" name="pagemode" value="">
<table class="admin">
<tr>
<th colspan=2>查找玩家</th>
</tr>
<tr>
<td>条件：
<select name="checkmode">
<option value="name" 
<?php if($checkmode == 'name') { ?>
selected
<?php } ?>
>玩家名
<option value="teamID" 
<?php if($checkmode == 'teamID') { ?>
selected
<?php } ?>
>队伍名称
<option value="club" 
<?php if($checkmode == 'club') { ?>
selected
<?php } ?>
>社团
</select>类似
</td>
<td>
<input size="30" type="text" name="checkinfo" id="checkinfo" value="<?php echo $checkinfo?>" maxlength="30"><input type="submit" value="查找玩家" onclick="javascript:document.pcmng.pagemode.value='ref'">
</td>
</tr>
</table>
<br>
<input type="submit" value="上一页" onclick="javascript:document.pcmng.pagemode.value='up';">
<span class="yellow"><?php echo $resultinfo?></span>
<input type="submit" value="下一页" onclick="javascript:document.pcmng.pagemode.value='down';">
<table class="admin">
<tr>
<th>选</th>
<th>姓名</th>
<th>性别</th>
<th>学号</th>
<th>等级</th>
<th>位置</th>
<th>队伍</th>
<th>状态</th>
<th>体力</th>
<th>生命</th>
<th>社团</th>
<th>金钱</th>
<th>熟练</th>
<th>武器</th>
<th>操作</th>
</tr>
<?php if(isset($pcdata)) { if(is_array($pcdata)) { foreach($pcdata as $n => $pc) { ?>
<tr>
<td><input type="checkbox" id="pc_<?php echo $n?>" name="pc_<?php echo $n?>" value="<?php echo $pc['pid']?>"></td>
<td><span 
<?php if($pc['hp']<=0 || $pc['state']>=10) { ?>
class="red"
<?php } ?>
><?php echo $pc['name']?></span></td>
<td><?php echo $sexinfo[$pc['gd']]?></td>
<td><?php echo $pc['sNo']?></td>
<td><?php echo $pc['lvl']?></td>
<td><?php echo $plsinfo[$pc['pls']]?></td>
<td><?php echo $pc['teamID']?></td>
<td><span 
<?php if($pc['state']>=10) { ?>
class="red"
<?php } ?>
><?php echo $stateinfo[$pc['state']]?></span></td>
<td><?php echo $pc['sp']?>/<?php echo $pc['msp']?></td>
<td><?php echo $pc['hp']?>/<?php echo $pc['mhp']?></td>
<td><?php echo $clubinfo[$pc['club']]?></td>
<td><?php echo $pc['money']?></td>
<td><?php echo $pc['wp']?>/<?php echo $pc['wk']?>/<?php echo $pc['wg']?>/<?php echo $pc['wc']?>/<?php echo $pc['wd']?>/<?php echo $pc['wf']?></td>
<td><?php echo $pc['wep']?>/<?php echo $pc['wepe']?>/<?php echo $pc['weps']?></td>
<td><input type="submit" value="查看/修改详细资料" onclick="$('command').value='edit_<?php echo $n?>_<?php echo $pc['pid']?>'"></td>
</tr>
<?php } } ?>
<tr>
<td colspan="2">
<input type="checkbox" name="pc_all" onchange="for(i=0; i<=<?php echo $n?>;i++){if(! $('pc_'+i).disabled){if(this.checked==true){$('pc_'+i).checked=true}else{$('pc_'+i).checked=false}}}">全选
</td>
<td colspan="13" style="text-align:center">
<input type="submit" value="复活所选玩家" onclick="$('command').value='live'">
<input type="submit" value="杀死所选玩家" onclick="$('command').value='kill'">
<input type="submit" value="清除所选玩家" onclick="$('command').value='del'">
</td>
</tr>
<?php } ?>
</table>

</form>
<?php } ?>
