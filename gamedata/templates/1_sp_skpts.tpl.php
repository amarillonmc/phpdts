<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
你目前有<span class="lime"><?php echo $skillpoint?></span>点技能点供自由分配，升级时可以获取新的技能点。<br>
想升级哪一项技能？ <br>
<TABLE border="0" cellSpacing=0 cellPadding=0 height=100% width=100%>
<tr>
<TD class=b1 width="40"><span>名称</span></TD>
<TD class=b1><span>描述</span></TD>
<TD class=b1 width="40"><span>消耗技能点</span></TD>
<TD class=b1 width="40"><span>操作</span></TD>
</tr>

<input type="hidden" name="mode" value="sp_skpts">
<input type="hidden" id="command" name="command" value="menu">
<?php if($club != 17) { ?>
<tr>
<td class=b1><span>生命</span></td>
<td class=b3><span>
<?php if($club == 13) { ?>
生命上限<span class="yellow">+6</span>
<?php } else { ?>
生命上限<span class="yellow">+3</span>
<?php } ?>
</span></td>
<td class=b3><span class="lime">1</span></td>
<td class=b3>
<?php if($skillpoint >= 1) { ?>
<input type="button" onclick="$('command').value='clubbasic1';postCmd('gamecmd','command.php');this.disabled=true;" value="升级">
<?php } else { ?>
<input type="button" disabled="true" value="升级">
<?php } ?>
</td>
</tr>

<tr>
<td class=b1><span>攻防</span></td>
<td class=b3><span>
<?php if($club == 14) { ?>
基础攻击<span class="yellow">+9</span><br>基础防御<span class="yellow">+12</span>
<?php } else { ?>
基础攻击<span class="yellow">+4</span><br>基础防御<span class="yellow">+6</span>
<?php } ?>
</span></td>
<td class=b3><span class="lime">1</span></td>
<td class=b3>
<?php if($skillpoint >= 1) { ?>
<input type="button" onclick="$('command').value='clubbasic2';postCmd('gamecmd','command.php');this.disabled=true;" value="升级">
<?php } else { ?>
<input type="button" disabled="true" value="升级">
<?php } ?>
</td>
</tr>
<?php } ?>
<tr>
<td class=b1><span>治疗</span></td>
<td class=b3><span>
包扎全身所有伤口；<br>
解除全部异常状态；
</span></td>
<td class=b3><span class="lime">1</span></td>
<td class=b3>
<?php if($skillpoint >= 1) { ?>
<input type="button" onclick="$('command').value='clubbasic3';postCmd('gamecmd','command.php');this.disabled=true;" value="治疗">
<?php } else { ?>
<input type="button" disabled="true" value="治疗">
<?php } ?>
</td>
</tr>
<?php if(is_array($p12)) { foreach($p12 as $key => $value) { if($skarr['learn'.$key] == 1) { ?>
<tr>
<td class=b1><span>格挡</span></td>
<td class=b3><span>
<?php if($skarr['sk'.$key]['nextlv'] == -1) { ?>
当你装备殴系武器时，你的防御值将额外增加武器面板数值*<span class="yellow"><?php echo $skarr['sk'.$key]['curdef']?>%</span>，但最高不超过2000点(<span class="yellow">已到达最高级</span>)
<?php } else { ?>
(<span class="yellow">从<?php echo $skarr['sk'.$key]['lv']?>级到<?php echo $skarr['sk'.$key]['nextlv']?>级</span>)<br>
当你装备殴系武器时，你的防御值将额外增加武器面板数值*<span class="yellow"><?php echo $skarr['sk'.$key]['newdef']?>%</span>(当前为<?php echo $skarr['sk'.$key]['curdef']?>%)，但最高不超过2000点
<?php } ?>
</span></td>
<td class=b3>
<?php if($skarr['sk'.$key]['nextlv'] != -1) { ?>
<span class="lime"><?php echo $skarr['sk'.$key]['cost']?></span>
<?php } else { ?>
<?php echo $nosta?>
<?php } ?>
</td>
<td class=b3>
<?php if(($skillpoint >= $skarr['sk'.$key]['cost'] && $skarr['sk'.$key]['nextlv'] != -1)) { ?>
<input type="button" onclick="$('command').value='clubskill1';postCmd('gamecmd','command.php');this.disabled=true;" value="升级">
<?php } else { ?>
<input type="button" disabled="true" value="升级">
<?php } ?>
</td>
</tr>
<?php } if($skarr['learn'.$key] == 2) { ?>
<tr>
<td class=b1><span>暴击</span></td>
<td class=b3><span>
<?php if($skarr['sk'.$key]['nextlv'] == -1) { ?>
当你使用殴系武器时，你的攻击力将获得<span class="yellow"><?php echo $skarr['sk'.$key]['curatt']?>%</span>的加成，你将有<span class="yellow"><?php echo $skarr['sk'.$key]['curpro']?>%</span>的概率在计算伤害时令对方防御值减少<span class="yellow"><?php echo $skarr['sk'.$key]['curdec']?>%</span>(<span class="yellow">已到达最高级</span>)
<?php } else { ?>
(<span class="yellow">从<?php echo $skarr['sk'.$key]['lv']?>级到<?php echo $skarr['sk'.$key]['nextlv']?>级</span>)<br>
当你使用殴系武器时，你的攻击力将获得<span class="yellow"><?php echo $skarr['sk'.$key]['newatt']?>%</span>的加成(当前为<?php echo $skarr['sk'.$key]['curatt']?>%)，你将有<span class="yellow"><?php echo $skarr['sk'.$key]['newpro']?>%</span>的概率(当前为<?php echo $skarr['sk'.$key]['curpro']?>%)在计算伤害时令对方防御值减少<span class="yellow"><?php echo $skarr['sk'.$key]['newdec']?>%</span>(当前为<?php echo $skarr['sk'.$key]['curdec']?>%)
<?php } ?>
</span></td>
<td class=b3>
<?php if($skarr['sk'.$key]['nextlv'] != -1) { ?>
<span class="lime"><?php echo $skarr['sk'.$key]['cost']?></span>
<?php } else { ?>
<?php echo $nosta?>
<?php } ?>
</td>
<td class=b3>
<?php if(($skillpoint >= $skarr['sk'.$key]['cost'] && $skarr['sk'.$key]['nextlv'] != -1)) { ?>
<input type="button" onclick="$('command').value='clubskill2';postCmd('gamecmd','command.php');this.disabled=true;" value="升级">
<?php } else { ?>
<input type="button" disabled="true" value="升级">
<?php } ?>
</td>
</tr>
<?php } if($skarr['learn'.$key] == 3) { ?>
<tr>
<td class=b1><span>精准</span></td>
<td class=b3><span>
<?php if($skarr['sk'.$key]['nextlv'] == -1) { ?>
当你使用斩系武器时，你的命中率和连击命中系数都将获得<span class="yellow"><?php echo $skarr['sk'.$key]['curacc']?>%</span>的加成(<span class="yellow">已到达最高级</span>)
<?php } else { ?>
(<span class="yellow">从<?php echo $skarr['sk'.$key]['lv']?>级到<?php echo $skarr['sk'.$key]['nextlv']?>级</span>)<br>
当你使用斩系武器时，你的命中率和连击命中系数都将获得<span class="yellow"><?php echo $skarr['sk'.$key]['newacc']?>%</span>的加成(当前为<?php echo $skarr['sk'.$key]['curacc']?>%)
<?php } ?>
</span></td>
<td class=b3>
<?php if($skarr['sk'.$key]['nextlv'] != -1) { ?>
<span class="lime"><?php echo $skarr['sk'.$key]['cost']?></span>
<?php } else { ?>
<?php echo $nosta?>
<?php } ?>
</td>
<td class=b3>
<?php if(($skillpoint >= $skarr['sk'.$key]['cost'] && $skarr['sk'.$key]['nextlv'] != -1)) { ?>
<input type="button" onclick="$('command').value='clubskill3';postCmd('gamecmd','command.php');this.disabled=true;" value="升级">
<?php } else { ?>
<input type="button" disabled="true" value="升级">
<?php } ?>
</td>
</tr>
<?php } if($skarr['learn'.$key] == 4) { ?>
<tr>
<td class=b1><span>保养</span></td>
<td class=b3><span>
<?php if($skarr['sk'.$key]['nextlv'] == -1) { ?>
当你使用斩系武器时，你的攻击/耐久损耗率将降低<span class="yellow"><?php echo $skarr['sk'.$key]['curpro']?>%</span>(<span class="yellow">已到达最高级</span>)
<?php } else { ?>
(<span class="yellow">从<?php echo $skarr['sk'.$key]['lv']?>级到<?php echo $skarr['sk'.$key]['nextlv']?>级</span>)<br>
当你使用斩系武器时，你的攻击/耐久损耗率将降低<span class="yellow"><?php echo $skarr['sk'.$key]['newpro']?>%</span>(当前为<?php echo $skarr['sk'.$key]['curpro']?>%)
<?php } ?>
</span></td>
<td class=b3>
<?php if($skarr['sk'.$key]['nextlv'] != -1) { ?>
<span class="lime"><?php echo $skarr['sk'.$key]['cost']?></span>
<?php } else { ?>
<?php echo $nosta?>
<?php } ?>
</td>
<td class=b3>
<?php if(($skillpoint >= $skarr['sk'.$key]['cost'] && $skarr['sk'.$key]['nextlv'] != -1)) { ?>
<input type="button" onclick="$('command').value='clubskill4';postCmd('gamecmd','command.php');this.disabled=true;" value="升级">
<?php } else { ?>
<input type="button" disabled="true" value="升级">
<?php } ?>
</td>
</tr>
<?php } if($skarr['learn'.$key] == 7) { ?>
<tr>
<td class=b1><span>臂力</span></td>
<td class=b3><span>
<?php if($skarr['sk'.$key]['nextlv'] == -1) { ?>
当你使用投系武器时，你的反击率将提升<span class="yellow"><?php echo $skarr['sk'.$key]['curpro']?>%</span>(<span class="yellow">已到达最高级</span>)
<?php } else { ?>
(<span class="yellow">从<?php echo $skarr['sk'.$key]['lv']?>级到<?php echo $skarr['sk'.$key]['nextlv']?>级</span>)<br>
当你使用投系武器时，你的反击率将提升<span class="yellow"><?php echo $skarr['sk'.$key]['newpro']?>%</span>(当前为<?php echo $skarr['sk'.$key]['curpro']?>%)
<?php } ?>
</span></td>
<td class=b3>
<?php if($skarr['sk'.$key]['nextlv'] != -1) { ?>
<span class="lime"><?php echo $skarr['sk'.$key]['cost']?></span>
<?php } else { ?>
<?php echo $nosta?>
<?php } ?>
</td>
<td class=b3>
<?php if(($skillpoint >= $skarr['sk'.$key]['cost'] && $skarr['sk'.$key]['nextlv'] != -1)) { ?>
<input type="button" onclick="$('command').value='clubskill7';postCmd('gamecmd','command.php');this.disabled=true;" value="升级">
<?php } else { ?>
<input type="button" disabled="true" value="升级">
<?php } ?>
</td>
</tr>
<?php } if($skarr['learn'.$key] == 8) { ?>
<tr>
<td class=b1><span>潜能</span></td>
<td class=b3><span>
<?php if($skarr['sk'.$key]['nextlv'] == -1) { ?>
当你使用投系武器时，你的攻击力将获得<span class="yellow"><?php echo $skarr['sk'.$key]['curatt']?>%</span>的加成，武器浮动将增加<span class="yellow"><?php echo $skarr['sk'.$key]['curfluc']?>%</span>(<span class="yellow">已到达最高级</span>)
<?php } else { ?>
(<span class="yellow">从<?php echo $skarr['sk'.$key]['lv']?>级到<?php echo $skarr['sk'.$key]['nextlv']?>级</span>)<br>
当你使用投系武器时，你的攻击力将获得<span class="yellow"><?php echo $skarr['sk'.$key]['newatt']?>%</span>的加成(当前为<span class="yellow"><?php echo $skarr['sk'.$key]['curatt']?>%</span>)，武器浮动将增加<span class="yellow"><?php echo $skarr['sk'.$key]['newfluc']?>%</span>(当前为<span class="yellow"><?php echo $skarr['sk'.$key]['curfluc']?>%</span>)
<?php } ?>
</span></td>
<td class=b3>
<?php if($skarr['sk'.$key]['nextlv'] != -1) { ?>
<span class="lime"><?php echo $skarr['sk'.$key]['cost']?></span>
<?php } else { ?>
<?php echo $nosta?>
<?php } ?>
</td>
<td class=b3>
<?php if(($skillpoint >= $skarr['sk'.$key]['cost'] && $skarr['sk'.$key]['nextlv'] != -1)) { ?>
<input type="button" onclick="$('command').value='clubskill8';postCmd('gamecmd','command.php');this.disabled=true;" value="升级">
<?php } else { ?>
<input type="button" disabled="true" value="升级">
<?php } ?>
</td>
</tr>
<?php } if($skarr['learn'.$key] == 5) { ?>
<tr>
<td class=b1><span>静息</span></td>
<td class=b3><span>
<?php if($skarr['sk'.$key]['nextlv'] == -1) { ?>
当你使用射系武器时，你的命中率和连击命中系数都将获得<span class="yellow"><?php echo $skarr['sk'.$key]['curacc']?>%</span>的加成(<span class="yellow">已到达最高级</span>)
<?php } else { ?>
(<span class="yellow">从<?php echo $skarr['sk'.$key]['lv']?>级到<?php echo $skarr['sk'.$key]['nextlv']?>级</span>)<br>
当你使用射系武器时，你的命中率和连击命中系数都将获得<span class="yellow"><?php echo $skarr['sk'.$key]['newacc']?>%</span>的加成(当前为<?php echo $skarr['sk'.$key]['curacc']?>%)
<?php } ?>
</span></td>
<td class=b3>
<?php if($skarr['sk'.$key]['nextlv'] != -1) { ?>
<span class="lime"><?php echo $skarr['sk'.$key]['cost']?></span>
<?php } else { ?>
<?php echo $nosta?>
<?php } ?>
</td>
<td class=b3>
<?php if(($skillpoint >= $skarr['sk'.$key]['cost'] && $skarr['sk'.$key]['nextlv'] != -1)) { ?>
<input type="button" onclick="$('command').value='clubskill5';postCmd('gamecmd','command.php');this.disabled=true;" value="升级">
<?php } else { ?>
<input type="button" disabled="true" value="升级">
<?php } ?>
</td>
</tr>
<?php } if($skarr['learn'.$key] == 6) { ?>
<tr>
<td class=b1><span>重击</span></td>
<td class=b3><span>
<?php if($skarr['sk'.$key]['nextlv'] == -1) { ?>
当你使用射系武器时，你将有额外<span class="yellow"><?php echo $skarr['sk'.$key]['curpro']?>%</span>的概率损耗敌人防具的耐久，并且将有<span class="yellow"><?php echo $skarr['sk'.$key]['cureff']?>倍</span>的损耗效果。你的攻击将有<span class="yellow"><?php echo $skarr['sk'.$key]['curpro2']?>%</span>的概率造成<span class="yellow"><?php echo $skarr['sk'.$key]['curdmg']?>%</span>额外的伤害。(<span class="yellow">已到达最高级</span>)
<?php } else { ?>
(<span class="yellow">从<?php echo $skarr['sk'.$key]['lv']?>级到<?php echo $skarr['sk'.$key]['nextlv']?>级</span>)<br>
当你使用射系武器时，你将有额外<span class="yellow"><?php echo $skarr['sk'.$key]['newpro']?>%</span>的概率损耗敌人防具的耐久(当前为<span class="yellow"><?php echo $skarr['sk'.$key]['curpro']?>%</span>)，并且将有额外<span class="yellow"><?php echo $skarr['sk'.$key]['neweff']?>倍</span>的损耗效果(当前为<span class="yellow"><?php echo $skarr['sk'.$key]['cureff']?>倍</span>)。你的攻击将有<span class="yellow"><?php echo $skarr['sk'.$key]['newpro2']?>%</span>的概率(当前为<span class="yellow"><?php echo $skarr['sk'.$key]['curpro2']?>%</span>)造成<span class="yellow"><?php echo $skarr['sk'.$key]['newdmg']?>%</span>额外的伤害(当前为<span class="yellow"><?php echo $skarr['sk'.$key]['curdmg']?>%</span>)。
<?php } ?>
</span></td>
<td class=b3>
<?php if($skarr['sk'.$key]['nextlv'] != -1) { ?>
<span class="lime"><?php echo $skarr['sk'.$key]['cost']?></span>
<?php } else { ?>
<?php echo $nosta?>
<?php } ?>
</td>
<td class=b3>
<?php if(($skillpoint >= $skarr['sk'.$key]['cost'] && $skarr['sk'.$key]['nextlv'] != -1)) { ?>
<input type="button" onclick="$('command').value='clubskill6';postCmd('gamecmd','command.php');this.disabled=true;" value="升级">
<?php } else { ?>
<input type="button" disabled="true" value="升级">
<?php } ?>
</td>
</tr>
<?php } if($skarr['learn'.$key] == 9) { ?>
<tr>
<td class=b1><span>隐蔽</span></td>
<td class=b3><span>
<?php if($skarr['sk'.$key]['nextlv'] == -1) { ?>
你的隐蔽率将提升<span class="yellow"><?php echo $skarr['sk'.$key]['curhid']?>%</span>，你的先攻率将提升<span class="yellow"><?php echo $skarr['sk'.$key]['curact']?>%</span>(<span class="yellow">已到达最高级</span>)
<?php } else { ?>
(<span class="yellow">从<?php echo $skarr['sk'.$key]['lv']?>级到<?php echo $skarr['sk'.$key]['nextlv']?>级</span>)<br>
你的隐蔽率将提升<span class="yellow"><?php echo $skarr['sk'.$key]['newhid']?>%</span>(当前为<span class="yellow"><?php echo $skarr['sk'.$key]['curhid']?>%</span>)，你的先攻率将提升<span class="yellow"><?php echo $skarr['sk'.$key]['newact']?>%</span>(当前为<span class="yellow"><?php echo $skarr['sk'.$key]['curact']?>%</span>)
<?php } ?>
</span></td>
<td class=b3>
<?php if($skarr['sk'.$key]['nextlv'] != -1) { ?>
<span class="lime"><?php echo $skarr['sk'.$key]['cost']?></span>
<?php } else { ?>
<?php echo $nosta?>
<?php } ?>
</td>
<td class=b3>
<?php if(($skillpoint >= $skarr['sk'.$key]['cost'] && $skarr['sk'.$key]['nextlv'] != -1)) { ?>
<input type="button" onclick="$('command').value='clubskill9';postCmd('gamecmd','command.php');this.disabled=true;" value="升级">
<?php } else { ?>
<input type="button" disabled="true" value="升级">
<?php } ?>
</td>
</tr>
<?php } if($skarr['learn'.$key] == 10) { ?>
<tr>
<td class=b1><span>冷静</span></td>
<td class=b3><span>
<?php if($skarr['sk'.$key]['nextlv'] == -1) { ?>
你的陷阱回避率将增加<span class="yellow"><?php echo $skarr['sk'.$key]['curmis']?>%</span>，拆除并再利用陷阱的几率将增加<span class="yellow"><?php echo $skarr['sk'.$key]['curpic']?>%</span>(<span class="yellow">已到达最高级</span>)
<?php } else { ?>
(<span class="yellow">从<?php echo $skarr['sk'.$key]['lv']?>级到<?php echo $skarr['sk'.$key]['nextlv']?>级</span>)<br>
你的陷阱回避率将增加<span class="yellow"><?php echo $skarr['sk'.$key]['newmis']?>%</span>(当前为<span class="yellow"><?php echo $skarr['sk'.$key]['curmis']?>%</span>)，拆除并再利用陷阱的几率将增加<span class="yellow"><?php echo $skarr['sk'.$key]['newpic']?>%</span>(当前为<span class="yellow"><?php echo $skarr['sk'.$key]['curpic']?>%</span>)
<?php } ?>
</span></td>
<td class=b3>
<?php if($skarr['sk'.$key]['nextlv'] != -1) { ?>
<span class="lime"><?php echo $skarr['sk'.$key]['cost']?></span>
<?php } else { ?>
<?php echo $nosta?>
<?php } ?>
</td>
<td class=b3>
<?php if(($skillpoint >= $skarr['sk'.$key]['cost'] && $skarr['sk'.$key]['nextlv'] != -1)) { ?>
<input type="button" onclick="$('command').value='clubskill10';postCmd('gamecmd','command.php');this.disabled=true;" value="升级">
<?php } else { ?>
<input type="button" disabled="true" value="升级">
<?php } ?>
</td>
</tr>
<?php } if($skarr['learn'.$key] == 11) { ?>
<tr>
<td class=b1><span>敏捷</span></td>
<td class=b3><span>
<?php if($skarr['sk'.$key]['nextlv'] == -1) { ?>
你的隐蔽率将提升<span class="yellow"><?php echo $skarr['sk'.$key]['curhid']?>%</span>，你的先攻率将提升<span class="yellow"><?php echo $skarr['sk'.$key]['curact']?>%</span>，你的反击率将提升<span class="yellow"><?php echo $skarr['sk'.$key]['curcnt']?>%</span>(<span class="yellow">已到达最高级</span>)
<?php } else { ?>
(<span class="yellow">从<?php echo $skarr['sk'.$key]['lv']?>级到<?php echo $skarr['sk'.$key]['nextlv']?>级</span>)<br>
你的隐蔽率将提升<span class="yellow"><?php echo $skarr['sk'.$key]['newhid']?>%</span>(当前为<span class="yellow"><?php echo $skarr['sk'.$key]['curhid']?>%</span>)，你的先攻率将提升<span class="yellow"><?php echo $skarr['sk'.$key]['newact']?>%</span>(当前为<span class="yellow"><?php echo $skarr['sk'.$key]['curact']?>%</span>)，你的反击率将提升<span class="yellow"><?php echo $skarr['sk'.$key]['newcnt']?>%</span>(当前为<span class="yellow"><?php echo $skarr['sk'.$key]['curcnt']?>%</span>)
<?php } ?>
</span></td>
<td class=b3>
<?php if($skarr['sk'.$key]['nextlv'] != -1) { ?>
<span class="lime"><?php echo $skarr['sk'.$key]['cost']?></span>
<?php } else { ?>
<?php echo $nosta?>
<?php } ?>
</td>
<td class=b3>
<?php if(($skillpoint >= $skarr['sk'.$key]['cost'] && $skarr['sk'.$key]['nextlv'] != -1)) { ?>
<input type="button" onclick="$('command').value='clubskill11';postCmd('gamecmd','command.php');this.disabled=true;" value="升级">
<?php } else { ?>
<input type="button" disabled="true" value="升级">
<?php } ?>
</td>
</tr>
<?php } if($skarr['learn'.$key] == 12) { ?>
<tr>
<td class=b1><span>闪避</span></td>
<td class=b3><span>
<?php if($skarr['sk'.$key]['nextlv'] == -1) { ?>
战斗中，敌人的命中率将降低<span class="yellow"><?php echo $skarr['sk'.$key]['curmis']?>%</span>(<span class="yellow">已到达最高级</span>)
<?php } else { ?>
(<span class="yellow">从<?php echo $skarr['sk'.$key]['lv']?>级到<?php echo $skarr['sk'.$key]['nextlv']?>级</span>)<br>
战斗中，敌人的命中率将降低<span class="yellow"><?php echo $skarr['sk'.$key]['newmis']?>%</span>(当前为<span class="yellow"><?php echo $skarr['sk'.$key]['curmis']?>%</span>)
<?php } ?>
</span></td>
<td class=b3>
<?php if($skarr['sk'.$key]['nextlv'] != -1) { ?>
<span class="lime"><?php echo $skarr['sk'.$key]['cost']?></span>
<?php } else { ?>
<?php echo $nosta?>
<?php } ?>
</td>
<td class=b3>
<?php if(($skillpoint >= $skarr['sk'.$key]['cost'] && $skarr['sk'.$key]['nextlv'] != -1)) { ?>
<input type="button" onclick="$('command').value='clubskill12';postCmd('gamecmd','command.php');this.disabled=true;" value="升级">
<?php } else { ?>
<input type="button" disabled="true" value="升级">
<?php } ?>
</td>
</tr>
<?php } if($skarr['learn'.$key] == 13) { ?>
<tr>
<td class=b1><span>灵力</span></td>
<td class=b3><span>
<?php if($skarr['sk'.$key]['nextlv'] == -1) { ?>
你使用灵力兵器时，主动攻击的体力消耗将减少<span class="yellow"><?php echo $skarr['sk'.$key]['curles']?>%</span>，敌人对你的反击率将降低<span class="yellow"><?php echo $skarr['sk'.$key]['curcnt']?>%</span>(<span class="yellow">已到达最高级</span>)
<?php } else { ?>
(<span class="yellow">从<?php echo $skarr['sk'.$key]['lv']?>级到<?php echo $skarr['sk'.$key]['nextlv']?>级</span>)<br>
你使用灵力兵器时，主动攻击的体力消耗将减少<span class="yellow"><?php echo $skarr['sk'.$key]['newles']?>%</span>(当前为<span class="yellow"><?php echo $skarr['sk'.$key]['curles']?>%</span>)，敌人对你的反击率将降低<span class="yellow"><?php echo $skarr['sk'.$key]['newcnt']?>%</span>(当前为<span class="yellow"><?php echo $skarr['sk'.$key]['curcnt']?>%</span>)
<?php } ?>
</span></td>
<td class=b3>
<?php if($skarr['sk'.$key]['nextlv'] != -1) { ?>
<span class="lime"><?php echo $skarr['sk'.$key]['cost']?></span>
<?php } else { ?>
<?php echo $nosta?>
<?php } ?>
</td>
<td class=b3>
<?php if(($skillpoint >= $skarr['sk'.$key]['cost'] && $skarr['sk'.$key]['nextlv'] != -1)) { ?>
<input type="button" onclick="$('command').value='clubskill13';postCmd('gamecmd','command.php');this.disabled=true;" value="升级">
<?php } else { ?>
<input type="button" disabled="true" value="升级">
<?php } ?>
</td>
</tr>
<?php } if($skarr['learn'.$key] == 14) { ?>
<tr>
<td class=b1><span>晶莹</span></td>
<td class=b3><span>
<?php if($skarr['sk'.$key]['nextlv'] == -1) { ?>
你施加的最终伤害将下降<span class="yellow"><?php echo $skarr['sk'.$key]['wdmgdown']?>%</span>，你受到的最终伤害将下降<span class="yellow"><?php echo $skarr['sk'.$key]['dmgdown']?>%</span>，你的『业』增长率是原始状态的<span class="yellow"><?php echo $skarr['sk'.$key]['rpdec']?>%</span>(<span class="yellow">已到达最高级</span>)
<?php } else { ?>
(<span class="yellow">从<?php echo $skarr['sk'.$key]['lv']?>级到<?php echo $skarr['sk'.$key]['nextlv']?>级</span>)<br>
你施加的最终伤害将下降<span class="yellow"><?php echo $skarr['sk'.$key]['newwdmgdown']?>%</span>(当前为<span class="yellow"><?php echo $skarr['sk'.$key]['wdmgdown']?>%</span>)；你受到的最终伤害将下降<span class="yellow"><?php echo $skarr['sk'.$key]['newdmgdown']?>%</span>(当前为<span class="yellow"><?php echo $skarr['sk'.$key]['dmgdown']?>%</span>)；
你的『业』增长率将下降为原始状态的<span class="yellow"><?php echo $skarr['sk'.$key]['newrpdec']?>%</span>(当前为<span class="yellow"><?php echo $skarr['sk'.$key]['rpdec']?>%</span>)。
<?php } ?>
</span></td>
<td class=b3>
<?php if($skarr['sk'.$key]['nextlv'] != -1) { ?>
<span class="lime"><?php echo $skarr['sk'.$key]['cost']?></span>
<?php } else { ?>
<?php echo $nosta?>
<?php } ?>
</td>
<td class=b3>
<?php if(($skillpoint >= $skarr['sk'.$key]['cost'] && $skarr['sk'.$key]['nextlv'] != -1)) { ?>
<input type="button" onclick="$('command').value='clubskill14';postCmd('gamecmd','command.php');this.disabled=true;" value="升级">
<?php } else { ?>
<input type="button" disabled="true" value="升级">
<?php } ?>
</td>
</tr>
<?php } if($skarr['learn'.$key] == 15) { ?>
<tr>
<td class=b1><span>剔透</span></td>
<td class=b3><span>
<?php if($skarr['sk'.$key]['nextlv'] == -1) { ?>
你有一定概率在主动攻击的时候造成额外伤害（反击时无效），这一额外伤害值等于对方『业』与你『业』的差值，且不会受到任何因素的削弱，但这一伤害的发动概率会随着你的『业』的增加而下降。这一概率最高为<span class="yellow"><?php echo $skarr['sk'.$key]['rpdmgr']?>%</span>。(<span class="yellow">已到达最高级</span>)
<?php } else { ?>
(<span class="yellow">从<?php echo $skarr['sk'.$key]['lv']?>级到<?php echo $skarr['sk'.$key]['nextlv']?>级</span>)<br>
你有一定概率在主动攻击的时候造成额外伤害（反击时无效），这一额外伤害值等于对方『业』与你『业』的差值，且不会受到任何因素的削弱，但这一伤害的发动概率会随着你的『业』的增加而下降。这一概率的最高值将上升为<span class="yellow"><?php echo $skarr['sk'.$key]['newrpdmgr']?>%</span>。(当前为<span class="yellow"><?php echo $skarr['sk'.$key]['rpdmgr']?>%</span>)；
<?php } ?>
</span></td>
<td class=b3>
<?php if($skarr['sk'.$key]['nextlv'] != -1) { ?>
<span class="lime"><?php echo $skarr['sk'.$key]['cost']?></span>
<?php } else { ?>
<?php echo $nosta?>
<?php } ?>
</td>
<td class=b3>
<?php if(($skillpoint >= $skarr['sk'.$key]['cost'] && $skarr['sk'.$key]['nextlv'] != -1)) { ?>
<input type="button" onclick="$('command').value='clubskill15';postCmd('gamecmd','command.php');this.disabled=true;" value="升级">
<?php } else { ?>
<input type="button" disabled="true" value="升级">
<?php } ?>
</td>
</tr>
<?php } } } ?>
</table>
<br>
<?php if($club == 18 && $skarr['learn']>0) { ?>
你还能学习<span class="yellow"><?php echo $skarr['learn']?></span>个新技能。请查看帮助以获取各个技能更详细的说明。<br>
想要研发什么技能？ <br>

<TABLE border="0" cellSpacing=0 cellPadding=0 height=100% width=100%>
<tr>
<TD class=b1 width="80"><span>来源</span></TD>
<TD class=b1 width="40"><span>名称</span></TD>
<TD class=b1 width="40"><span>消耗</span></TD>
<TD class=b1 width="40"><span>操作</span></TD>
<TD class=b1 width="40"><span>名称</span></TD>
<TD class=b1 width="40"><span>消耗</span></TD>
<TD class=b1 width="40"><span>操作</span></TD>
</tr>

<tr>
<td class=b3><span>铁拳无敌</span></td>
<td class=b1><span>格挡</span></td>

<td class=b3>
<?php if($skarr['rs1'] != 1) { ?>
<span class="lime"><?php echo $skarr['rs1cost']?></span>
<?php } else { ?>
<?php echo $nosta?>
<?php } ?>
</td>
<td class=b3>
<?php if(($skillpoint >= $skarr['rs1cost'] && $skarr['rs1'] != 1)) { ?>
<input type="button" onclick="$('command').value='learnrs1';postCmd('gamecmd','command.php');this.disabled=true;" value="研发">
<?php } else { ?>
<input type="button" disabled="true" value="研发">
<?php } ?>
</td>

<td class=b1><span>暴击</span></td>
<td class=b3>
<?php if($skarr['rs2'] != 1) { ?>
<span class="lime"><?php echo $skarr['rs2cost']?></span>
<?php } else { ?>
<?php echo $nosta?>
<?php } ?>
</td>
<td class=b3>
<?php if(($skillpoint >= $skarr['rs2cost'] && $skarr['rs2'] != 1)) { ?>
<input type="button" onclick="$('command').value='learnrs2';postCmd('gamecmd','command.php');this.disabled=true;" value="研发">
<?php } else { ?>
<input type="button" disabled="true" value="研发">
<?php } ?>
</td>

</tr>

<tr>
<td class=b3><span>见敌必斩</span></td>
<td class=b1><span>精准</span></td>

<td class=b3>
<?php if($skarr['rs3'] != 1) { ?>
<span class="lime"><?php echo $skarr['rs3cost']?></span>
<?php } else { ?>
<?php echo $nosta?>
<?php } ?>
</td>
<td class=b3>
<?php if(($skillpoint >= $skarr['rs3cost'] && $skarr['rs3'] != 1)) { ?>
<input type="button" onclick="$('command').value='learnrs3';postCmd('gamecmd','command.php');this.disabled=true;" value="研发">
<?php } else { ?>
<input type="button" disabled="true" value="研发">
<?php } ?>
</td>

<td class=b1><span>保养</span></td>
<td class=b3>
<?php if($skarr['rs4'] != 1) { ?>
<span class="lime"><?php echo $skarr['rs4cost']?></span>
<?php } else { ?>
<?php echo $nosta?>
<?php } ?>
</td>
<td class=b3>
<?php if(($skillpoint >= $skarr['rs4cost'] && $skarr['rs4'] != 1)) { ?>
<input type="button" onclick="$('command').value='learnrs4';postCmd('gamecmd','command.php');this.disabled=true;" value="研发">
<?php } else { ?>
<input type="button" disabled="true" value="研发">
<?php } ?>
</td>

</tr>

<tr>
<td class=b3><span>狙击鹰眼</span></td>
<td class=b1><span>静息</span></td>

<td class=b3>
<?php if($skarr['rs5'] != 1) { ?>
<span class="lime"><?php echo $skarr['rs5cost']?></span>
<?php } else { ?>
<?php echo $nosta?>
<?php } ?>
</td>
<td class=b3>
<?php if(($skillpoint >= $skarr['rs5cost'] && $skarr['rs5'] != 1)) { ?>
<input type="button" onclick="$('command').value='learnrs5';postCmd('gamecmd','command.php');this.disabled=true;" value="研发">
<?php } else { ?>
<input type="button" disabled="true" value="研发">
<?php } ?>
</td>

<td class=b1><span>重击</span></td>
<td class=b3>
<?php if($skarr['rs6'] != 1) { ?>
<span class="lime"><?php echo $skarr['rs6cost']?></span>
<?php } else { ?>
<?php echo $nosta?>
<?php } ?>
</td>
<td class=b3>
<?php if(($skillpoint >= $skarr['rs6cost'] && $skarr['rs6'] != 1)) { ?>
<input type="button" onclick="$('command').value='learnrs6';postCmd('gamecmd','command.php');this.disabled=true;" value="研发">
<?php } else { ?>
<input type="button" disabled="true" value="研发">
<?php } ?>
</td>

</tr>

<tr>
<td class=b3><span>灌篮高手</span></td>
<td class=b1><span>臂力</span></td>

<td class=b3>
<?php if($skarr['rs7'] != 1) { ?>
<span class="lime"><?php echo $skarr['rs7cost']?></span>
<?php } else { ?>
<?php echo $nosta?>
<?php } ?>
</td>
<td class=b3>
<?php if(($skillpoint >= $skarr['rs7cost'] && $skarr['rs7'] != 1)) { ?>
<input type="button" onclick="$('command').value='learnrs7';postCmd('gamecmd','command.php');this.disabled=true;" value="研发">
<?php } else { ?>
<input type="button" disabled="true" value="研发">
<?php } ?>
</td>

<td class=b1><span>潜能</span></td>
<td class=b3>
<?php if($skarr['rs8'] != 1) { ?>
<span class="lime"><?php echo $skarr['rs8cost']?></span>
<?php } else { ?>
<?php echo $nosta?>
<?php } ?>
</td>
<td class=b3>
<?php if(($skillpoint >= $skarr['rs8cost'] && $skarr['rs8'] != 1)) { ?>
<input type="button" onclick="$('command').value='learnrs8';postCmd('gamecmd','command.php');this.disabled=true;" value="研发">
<?php } else { ?>
<input type="button" disabled="true" value="研发">
<?php } ?>
</td>

</tr>

<tr>
<td class=b3><span>拆弹专家</span></td>
<td class=b1><span>隐蔽</span></td>

<td class=b3>
<?php if($skarr['rs9'] != 1) { ?>
<span class="lime"><?php echo $skarr['rs9cost']?></span>
<?php } else { ?>
<?php echo $nosta?>
<?php } ?>
</td>
<td class=b3>
<?php if(($skillpoint >= $skarr['rs9cost'] && $skarr['rs9'] != 1)) { ?>
<input type="button" onclick="$('command').value='learnrs9';postCmd('gamecmd','command.php');this.disabled=true;" value="研发">
<?php } else { ?>
<input type="button" disabled="true" value="研发">
<?php } ?>
</td>

<td class=b1><span>冷静</span></td>
<td class=b3>
<?php if($skarr['rs10'] != 1) { ?>
<span class="lime"><?php echo $skarr['rs10cost']?></span>
<?php } else { ?>
<?php echo $nosta?>
<?php } ?>
</td>
<td class=b3>
<?php if(($skillpoint >= $skarr['rs10cost'] && $skarr['rs10'] != 1)) { ?>
<input type="button" onclick="$('command').value='learnrs10';postCmd('gamecmd','command.php');this.disabled=true;" value="研发">
<?php } else { ?>
<input type="button" disabled="true" value="研发">
<?php } ?>
</td>

</tr>

<tr>
<td class=b3><span>宛如疾风</span></td>
<td class=b1><span>敏捷</span></td>
<td class=b3>
<?php if($skarr['rs11'] != 1) { ?>
<span class="lime"><?php echo $skarr['rs11cost']?></span>
<?php } else { ?>
<?php echo $nosta?>
<?php } ?>
</td>
<td class=b3>
<?php if(($skillpoint >= $skarr['rs11cost'] && $skarr['rs11'] != 1)) { ?>
<input type="button" onclick="$('command').value='learnrs11';postCmd('gamecmd','command.php');this.disabled=true;" value="研发">
<?php } else { ?>
<input type="button" disabled="true" value="研发">
<?php } ?>
</td>

<td class=b1><span>闪避</span></td>

<td class=b3>
<?php if($skarr['rs12'] != 1) { ?>
<span class="lime"><?php echo $skarr['rs12cost']?></span>
<?php } else { ?>
<?php echo $nosta?>
<?php } ?>
</td>
<td class=b3>
<?php if(($skillpoint >= $skarr['rs12cost'] && $skarr['rs12'] != 1)) { ?>
<input type="button" onclick="$('command').value='learnrs12';postCmd('gamecmd','command.php');this.disabled=true;" value="研发">
<?php } else { ?>
<input type="button" disabled="true" value="研发">
<?php } ?>
</td>

</tr>

<tr>
<td class=b3><span>超能力者</span></td>
<td class=b1><span>灵力</span></td>

<td class=b3>
<?php if($skarr['rs13'] != 1) { ?>
<span class="lime"><?php echo $skarr['rs13cost']?></span>
<?php } else { ?>
<?php echo $nosta?>
<?php } ?>
</td>
<td class=b3>
<?php if(($skillpoint >= $skarr['rs13cost'] && $skarr['rs13'] != 1)) { ?>
<input type="button" onclick="$('command').value='learnrs13';postCmd('gamecmd','command.php');this.disabled=true;" value="研发">
<?php } else { ?>
<input type="button" disabled="true" value="研发">
<?php } ?>
</td>

</tr>
</table>
<br>
<?php } ?>
<input type="button" class="cmdbutton" onclick="postCmd('gamecmd','command.php');this.disabled=true;" value="返回">

