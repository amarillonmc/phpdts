<?php if(!defined('IN_GAME')) exit('Access Denied'); if($command=='check') { ?>
<form method="post" name="npcmng" onsubmit="admin.php">
<input type="hidden" name="mode" value="npcmng">
<input type="hidden" id="command" name="command" value="list">
<input type="hidden" name="start" value="<?php echo $start?>">
<input type="hidden" name="checkmode" value="<?php echo $checkmode?>">	
<input type="hidden" name="checkinfo" value="<?php echo $checkinfo?>">
<input type="hidden" name="name" value="<?php echo $npc['name']?>">
<input type="hidden" name="pid" value="<?php echo $npc['pid']?>">

<input type="hidden" name="itm0" value="<?php echo $npc['itm0']?>">
<input type="hidden" name="itme0" value="<?php echo $npc['itme0']?>">
<input type="hidden" name="itmk0" value="<?php echo $npc['itmk0']?>">
<input type="hidden" name="itms0" value="<?php echo $npc['itms0']?>">
<input type="hidden" name="itmsk0" value="<?php echo $npc['itmsk0']?>">
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
<td><?php echo $npc['name']?></td>
<td>武器</td>
<td><input size="20" type="text" name="wep" value="<?php echo $npc['wep']?>" maxlength="30"></td>
<td>包裹1</td>
<td><input size="20" type="text" name="itm1" value="<?php echo $npc['itm1']?>" maxlength="30"></td>
</tr>
<tr>		
<td>性别</td>
<td><input size="20" type="text" name="gd" value="<?php echo $npc['gd']?>" maxlength="20"></td>
<td>类型</td>
<td><input size="20" type="text" name="wepk" value="<?php echo $npc['wepk']?>" maxlength="20"></td>
<td>类型</td>
<td><input size="20" type="text" name="itmk1" value="<?php echo $npc['itmk1']?>" maxlength="20"></td>
</tr>
<tr>
<td>学号</td>
<td><input size="20" type="text" name="sNo" value="<?php echo $npc['sNo']?>" maxlength="20"></td>
<td>效果</td>
<td><input size="20" type="text" name="wepe" value="<?php echo $npc['wepe']?>" maxlength="20"></td>
<td>效果</td>
<td><input size="20" type="text" name="itme1" value="<?php echo $npc['itme1']?>" maxlength="20"></td>
</tr>
<tr>
<td>头像</td>
<td><input size="20" type="text" name="icon" value="<?php echo $npc['icon']?>" maxlength="20"></td>
<td>耐久</td>
<td><input size="20" type="text" name="weps" value="<?php echo $npc['weps']?>" maxlength="20"></td>
<td>耐久</td>
<td><input size="20" type="text" name="itms1" value="<?php echo $npc['itms1']?>" maxlength="20"></td>
</tr>
<tr>
<td>社团</td>
<td><input size="20" type="text" name="club" value="<?php echo $npc['club']?>" maxlength="20"></td>
<td>子类型</td>
<td><input size="20" type="text" name="wepsk" value="<?php echo $npc['wepsk']?>" maxlength="20"></td>
<td>子类型</td>
<td><input size="20" type="text" name="itmsk1" value="<?php echo $npc['itmsk1']?>" maxlength="20"></td>
</tr>
<tr>
<td>生命</td>
<td><input size="20" type="text" name="hp" value="<?php echo $npc['hp']?>" maxlength="20"></td>
<td>防具(体)</td>
<td><input size="20" type="text" name="arb" value="<?php echo $npc['arb']?>" maxlength="30"></td>
<td>包裹2</td>
<td><input size="20" type="text" name="itm2" value="<?php echo $npc['itm2']?>" maxlength="30"></td>
</tr>
<tr>
<td>最大生命</td>
<td><input size="20" type="text" name="mhp" value="<?php echo $npc['mhp']?>" maxlength="20"></td>
<td>类型</td>
<td><input size="20" type="text" name="arbk" value="<?php echo $npc['arbk']?>" maxlength="20"></td>
<td>类型</td>
<td><input size="20" type="text" name="itmk2" value="<?php echo $npc['itmk2']?>" maxlength="20"></td>
</tr>
<tr>
<td>体力</td>
<td><input size="20" type="text" name="sp" value="<?php echo $npc['sp']?>" maxlength="20"></td>
<td>效果</td>
<td><input size="20" type="text" name="arbe" value="<?php echo $npc['arbe']?>" maxlength="20"></td>
<td>效果</td>
<td><input size="20" type="text" name="itme2" value="<?php echo $npc['itme2']?>" maxlength="20"></td>
</tr>
<tr>
<td>最大体力</td>
<td><input size="20" type="text" name="msp" value="<?php echo $npc['msp']?>" maxlength="20"></td>
<td>耐久</td>
<td><input size="20" type="text" name="arbs" value="<?php echo $npc['arbs']?>" maxlength="20"></td>
<td>耐久</td>
<td><input size="20" type="text" name="itms2" value="<?php echo $npc['itms2']?>" maxlength="20"></td>
</tr>
<tr>
<td>基础攻击</td>
<td><input size="20" type="text" name="att" value="<?php echo $npc['att']?>" maxlength="20"></td>
<td>子类型</td>
<td><input size="20" type="text" name="arbsk" value="<?php echo $npc['arbsk']?>" maxlength="20"></td>
<td>子类型</td>
<td><input size="20" type="text" name="itmsk2" value="<?php echo $npc['itmsk2']?>" maxlength="20"></td>
</tr>
<tr>
<td>基础防御</td>
<td><input size="20" type="text" name="def" value="<?php echo $npc['def']?>" maxlength="20"></td>
<td>防具(头)</td>
<td><input size="20" type="text" name="arh" value="<?php echo $npc['arh']?>" maxlength="30"></td>
<td>包裹3</td>
<td><input size="20" type="text" name="itm3" value="<?php echo $npc['itm3']?>" maxlength="30"></td>
</tr>
<tr>
<td>位置</td>
<td><input size="20" type="text" name="pls" value="<?php echo $npc['pls']?>" maxlength="20"></td>
<td>类型</td>
<td><input size="20" type="text" name="arhk" value="<?php echo $npc['arhk']?>" maxlength="20"></td>
<td>类型</td>
<td><input size="20" type="text" name="itmk3" value="<?php echo $npc['itmk3']?>" maxlength="20"></td>
</tr>
<tr>
<td>等级</td>
<td><input size="20" type="text" name="lvl" value="<?php echo $npc['lvl']?>" maxlength="20"></td>
<td>效果</td>
<td><input size="20" type="text" name="arhe" value="<?php echo $npc['arhe']?>" maxlength="20"></td>
<td>效果</td>
<td><input size="20" type="text" name="itme3" value="<?php echo $npc['itme3']?>" maxlength="20"></td>
</tr>
<tr>
<td>经验</td>
<td><input size="20" type="text" name="exp" value="<?php echo $npc['exp']?>" maxlength="20"></td>
<td>耐久</td>
<td><input size="20" type="text" name="arhs" value="<?php echo $npc['arhs']?>" maxlength="20"></td>
<td>耐久</td>
<td><input size="20" type="text" name="itms3" value="<?php echo $npc['itms3']?>" maxlength="20"></td>
</tr>
<tr>
<td>钱</td>
<td><input size="20" type="text" name="money" value="<?php echo $npc['money']?>" maxlength="20"></td>
<td>子类型</td>
<td><input size="20" type="text" name="arhsk" value="<?php echo $npc['arhsk']?>" maxlength="20"></td>
<td>子类型</td>
<td><input size="20" type="text" name="itmsk3" value="<?php echo $npc['itmsk3']?>" maxlength="20"></td>
</tr>
<tr>
<td>对手</td>
<td><input size="20" type="text" name="bid" value="<?php echo $npc['bid']?>" maxlength="20"></td>
<td>防具(腕)</td>
<td><input size="20" type="text" name="ara" value="<?php echo $npc['ara']?>" maxlength="30"></td>
<td>包裹4</td>
<td><input size="20" type="text" name="itm4" value="<?php echo $npc['itm4']?>" maxlength="30"></td>
</tr>
<tr>
<td>受伤</td>
<td><input size="20" type="text" name="inf" value="<?php echo $npc['inf']?>" maxlength="20"></td>
<td>类型</td>
<td><input size="20" type="text" name="arak" value="<?php echo $npc['arak']?>" maxlength="20"></td>
<td>类型</td>
<td><input size="20" type="text" name="itmk4" value="<?php echo $npc['itmk4']?>" maxlength="20"></td>
</tr>
<tr>
<td>怒气</td>
<td><input size="20" type="text" name="rage" value="<?php echo $npc['rage']?>" maxlength="20"></td>
<td>效果</td>
<td><input size="20" type="text" name="arae" value="<?php echo $npc['arae']?>" maxlength="20"></td>
<td>效果</td>
<td><input size="20" type="text" name="itme4" value="<?php echo $npc['itme4']?>" maxlength="20"></td>
</tr>
<tr>
<td>基础姿态</td>
<td><input size="20" type="text" name="pose" value="<?php echo $npc['pose']?>" maxlength="20"></td>
<td>耐久</td>
<td><input size="20" type="text" name="aras" value="<?php echo $npc['aras']?>" maxlength="20"></td>
<td>耐久</td>
<td><input size="20" type="text" name="itms4" value="<?php echo $npc['itms4']?>" maxlength="20"></td>
</tr>
<tr>
<td>应战策略</td>
<td><input size="20" type="text" name="tactic" value="<?php echo $npc['tactic']?>" maxlength="20"></td>
<td>子类型</td>
<td><input size="20" type="text" name="arask" value="<?php echo $npc['arask']?>" maxlength="20"></td>
<td>子类型</td>
<td><input size="20" type="text" name="itmsk4" value="<?php echo $npc['itmsk4']?>" maxlength="20"></td>
</tr>
<tr>
<td>杀人数</td>
<td><input size="20" type="text" name="killnum" value="<?php echo $npc['killnum']?>" maxlength="20"></td>
<td>防具(足)</td>
<td><input size="20" type="text" name="arf" value="<?php echo $npc['arf']?>" maxlength="30"></td>
<td>包裹5</td>
<td><input size="20" type="text" name="itm5" value="<?php echo $npc['itm5']?>" maxlength="30"></td>
</tr>
<tr>
<td>殴熟</td>
<td><input size="20" type="text" name="wp" value="<?php echo $npc['wp']?>" maxlength="20"></td>
<td>类型</td>
<td><input size="20" type="text" name="arfk" value="<?php echo $npc['arfk']?>" maxlength="20"></td>
<td>类型</td>
<td><input size="20" type="text" name="itmk5" value="<?php echo $npc['itmk5']?>" maxlength="20"></td>
</tr>
<tr>
<td>斩熟</td>
<td><input size="20" type="text" name="wk" value="<?php echo $npc['wk']?>" maxlength="20"></td>
<td>效果</td>
<td><input size="20" type="text" name="arfe" value="<?php echo $npc['arfe']?>" maxlength="20"></td>
<td>效果</td>
<td><input size="20" type="text" name="itme5" value="<?php echo $npc['itme5']?>" maxlength="20"></td>
</tr>
<tr>
<td>枪熟</td>
<td><input size="20" type="text" name="wg" value="<?php echo $npc['wg']?>" maxlength="20"></td>
<td>耐久</td>
<td><input size="20" type="text" name="arfs" value="<?php echo $npc['arfs']?>" maxlength="20"></td>
<td>耐久</td>
<td><input size="20" type="text" name="itms5" value="<?php echo $npc['itms5']?>" maxlength="20"></td>
</tr>
<tr>
<td>投熟</td>
<td><input size="20" type="text" name="wc" value="<?php echo $npc['wc']?>" maxlength="20"></td>
<td>子类型</td>
<td><input size="20" type="text" name="arfsk" value="<?php echo $npc['arfsk']?>" maxlength="20"></td>
<td>子类型</td>
<td><input size="20" type="text" name="itmsk5" value="<?php echo $npc['itmsk5']?>" maxlength="20"></td>
</tr>
<tr>
<td>爆熟</td>
<td><input size="20" type="text" name="wd" value="<?php echo $npc['wd']?>" maxlength="20"></td>
<td>饰品</td>
<td><input size="20" type="text" name="art" value="<?php echo $npc['art']?>" maxlength="30"></td>
<td>包裹6</td>
<td><input size="20" type="text" name="itm6" value="<?php echo $npc['itm6']?>" maxlength="30"></td>
</tr>
<tr>
<td>灵熟</td>
<td><input size="20" type="text" name="wf" value="<?php echo $npc['wf']?>" maxlength="20"></td>
<td>类型</td>
<td><input size="20" type="text" name="artk" value="<?php echo $npc['artk']?>" maxlength="20"></td>
<td>类型</td>
<td><input size="20" type="text" name="itmk6" value="<?php echo $npc['itmk6']?>" maxlength="20"></td>
</tr>
<tr>
<td>队伍名称</td>
<td><input size="20" type="text" name="teamID" value="<?php echo $npc['teamID']?>" maxlength="20"></td>
<td>效果</td>
<td><input size="20" type="text" name="arte" value="<?php echo $npc['arte']?>" maxlength="20"></td>
<td>效果</td>
<td><input size="20" type="text" name="itme6" value="<?php echo $npc['itme6']?>" maxlength="20"></td>
</tr>
<tr>
<td>队伍密码</td>
<td><input size="20" type="text" name="teamPass" value="<?php echo $npc['teamPass']?>" maxlength="20"></td>
<td>耐久</td>
<td><input size="20" type="text" name="arts" value="<?php echo $npc['arts']?>" maxlength="20"></td>
<td>耐久</td>
<td><input size="20" type="text" name="itms6" value="<?php echo $npc['itms6']?>" maxlength="20"></td>
</tr>
<tr>
<td></td>
<td></td>
<td>子类型</td>
<td><input size="20" type="text" name="artsk" value="<?php echo $npc['artsk']?>" maxlength="20"></td>
<td>子类型</td>
<td><input size="20" type="text" name="itmsk6" value="<?php echo $npc['itmsk6']?>" maxlength="20"></td>
</tr>
</table>
<input type="submit" value="修改NPC数值" onclick="$('command').value = 'submitedit'">
</form> 
<form method="post" name="npcmng" onsubmit="admin.php">
<input type="hidden" name="mode" value="npcmng">
<input type="hidden" name="command" value="list">
<input type="submit" value="返回NPC管理">
</form>
<?php } else { ?>
<form method="post" name="npcmng" onsubmit="admin.php">
<input type="hidden" name="mode" value="npcmng">
<input type="hidden" id="command" name="command" value="find">
<input type="hidden" name="start" value="<?php echo $start?>">
<input type="hidden" name="pagemode" value="">
<table class="admin">
<tr>
<th colspan=2>查找NPC</th>
</tr>
<tr>
<td>条件：
<select name="checkmode">
<option value="name" 
<?php if($checkmode == 'name') { ?>
selected
<?php } ?>
>NPC名
<option value="teamID" 
<?php if($checkmode == 'teamID') { ?>
selected
<?php } ?>
>队伍名称
<option value="pls" 
<?php if($checkmode == 'pls') { ?>
selected
<?php } ?>
>地点
</select>类似
</td>
<td>
<input size="30" type="text" name="checkinfo" id="checkinfo" value="<?php echo $checkinfo?>" maxlength="30"><input type="submit" value="查找NPC" onclick="javascript:document.npcmng.pagemode.value='ref'">
</td>
</tr>
</table>
<br>
<input type="submit" value="上一页" onclick="javascript:document.npcmng.pagemode.value='up';">
<span class="yellow"><?php echo $resultinfo?></span>
<input type="submit" value="下一页" onclick="javascript:document.npcmng.pagemode.value='down';">
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
<?php if(isset($npcdata)) { if(is_array($npcdata)) { foreach($npcdata as $n => $npc) { ?>
<tr>
<td><input type="checkbox" id="npc_<?php echo $n?>" name="npc_<?php echo $n?>" value="<?php echo $npc['pid']?>"></td>
<td><span 
<?php if($npc['hp']<=0 || $npc['state']>=10) { ?>
class="red"
<?php } ?>
><?php echo $npc['name']?></span></td>
<td><?php echo $sexinfo[$npc['gd']]?></td>
<td><?php echo $npc['sNo']?></td>
<td><?php echo $npc['lvl']?></td>
<td><?php echo $plsinfo[$npc['pls']]?></td>
<td><?php echo $npc['teamID']?></td>
<td><span 
<?php if($npc['state']>=10) { ?>
class="red"
<?php } ?>
><?php echo $stateinfo[$npc['state']]?></span></td>
<td><?php echo $npc['sp']?>/<?php echo $npc['msp']?></td>
<td><?php echo $npc['hp']?>/<?php echo $npc['mhp']?></td>
<td><?php echo $clubinfo[$npc['club']]?></td>
<td><?php echo $npc['money']?></td>
<td><?php echo $npc['wp']?>/<?php echo $npc['wk']?>/<?php echo $npc['wg']?>/<?php echo $npc['wc']?>/<?php echo $npc['wd']?>/<?php echo $npc['wf']?></td>
<td><?php echo $npc['wep']?>/<?php echo $npc['wepe']?>/<?php echo $npc['weps']?></td>
<td><input type="submit" value="查看/修改详细资料" onclick="$('command').value='edit_<?php echo $n?>_<?php echo $npc['pid']?>'"></td>
</tr>
<?php } } ?>
<tr>
<td colspan="2">
<input type="checkbox" name="npc_all" onchange="for(i=0; i<=<?php echo $n?>;i++){if(! $('npc_'+i).disabled){if(this.checked==true){$('npc_'+i).checked=true}else{$('npc_'+i).checked=false}}}">全选
</td>
<td colspan="13" style="text-align:center">
<input type="submit" value="复活所选NPC" onclick="$('command').value='live'">
<input type="submit" value="杀死所选NPC" onclick="$('command').value='kill'">
<input type="submit" value="清除所选NPC" onclick="$('command').value='del'">
</td>
</tr>
<?php } ?>
</table>

</form>
<?php } ?>
