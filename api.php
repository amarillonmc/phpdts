<?php
  include_once GAME_ROOT.'./include/game/itemplace.func.php';
  include_once GAME_ROOT.'./include/game/depot.func.php';
  include_once GAME_ROOT.'./include/game/elementmix.func.php';
  /** 获取对尸体的行动 */
  function getCorpseAction() {
    $list = array();
    if ($club == 20) {
      $list[] = array(
        "key" => "element_split",
        "title" => "提炼元素"
      );
      if (!check_skill_unlock('c20_zombie',$pdata)) {
        $list[] = array(
          "key" => "c20_zombie",
          "title" => "转化灵俑"
        );
      }
    } else {
      $list[] = array(
        "key" => "destory",
        "title" => "销毁尸体"
      );
    }
    if ($cstick_flag) {
      $list[] = array(
        "key" => "cstick",
        "title" => "抡起尸体！"
      );
    }
    if ($loot_depot_flag) {
      $list[] = array(
        "key" => "loot_depot",
        "title" => "转移安全箱权限"
      );
    }
    $list[] = array(
      "key" => "back",
      "title" => "返回"
    );
    return $list;
  }
  /** 获取敌方物品信息 */
  function getCorpseItems($tdata) {
    $list = array();
    if ($tdata['weps'] && $tdata['wepe']) {
      $list[] = array(
        "key" => "wep",
        "type" => parse_info_desc($tdata['wepk'], 'k'),
        "name" => $tdata['wep'],
        "props" => $tdata['wepsk'] ? parse_info_desc($tdata['wepsk'], 'sk', 'wepsk', 1) : '',
        "quality" => $tdata['wepe'],
        "durability" => $tdata['weps'],
      );
    }
    //$w_arbs && $w_arbe
    if ($tdata['arbs'] && $tdata['arbe']) {
      $list[] = array(
        "key" => "arb",
        "type" => parse_info_desc($tdata['arbk'], 'k'),
        "name" => $tdata['arb'],
        "props" => $tdata['arbsk'] ? parse_info_desc($tdata['arbsk'], 'sk', 'arbsk', 1) : '',
        "quality" => $tdata['arbe'],
        "durability" => $tdata['arbs'],
      );
    }
    //$w_arhs
    if ($tdata['arhs']) {
      $list[] = array(
        "key" => "arh",
        "type" => parse_info_desc($tdata['arhk'], 'k'),
        "name" => $tdata['arh'],
        "props" => $tdata['arhsk'] ? parse_info_desc($tdata['arhsk'], 'sk', 'arhsk', 1) : '',
        "quality" => $tdata['arhe'],
        "durability" => $tdata['arhs'],
      );
    }
    //w_aras
    if ($tdata['aras']) {
      $list[] = array(
        "key" => "ara",
        "type" => parse_info_desc($tdata['arak'], 'k'),
        "name" => $tdata['ara'],
        "props" => $tdata['arask'] ? parse_info_desc($tdata['arask'], 'sk', 'arask', 1) : '',
        "quality" => $tdata['arae'],
        "durability" => $tdata['aras'],
      );
    }
    // w_arfs
    if ($tdata['arfs']) {
      $list[] = array(
        "key" => "arf",
        "type" => parse_info_desc($tdata['arfk'], 'k'),
        "name" => $tdata['arf'],
        "props" => $tdata['arfsk'] ? parse_info_desc($tdata['arfsk'], 'sk', 'arfsk', 1) : '',
        "quality" => $tdata['arfe'],
        "durability" => $tdata['arfs'],
      );
    }
    // w_arts
    if ($tdata['arts']) {
      $list[] = array(
        "key" => "art",
        "type" => parse_info_desc($tdata['artk'], 'k'),
        "name" => $tdata['art'],
        "props" => $tdata['artsk'] ? parse_info_desc($tdata['artsk'], 'sk', 'artsk', 1) : '',
        "quality" => $tdata['arte'],
        "durability" => $tdata['arts'],
      );
    }
    //w_itms0
    if ($tdata['itms0']) {
      $list[] = array(
        "key" => "itm0",
        "type" => parse_info_desc($tdata['itmk0'], 'k'),
        "name" => $tdata['itm0'],
        "props" => $tdata['itmsk0'] ? parse_info_desc($tdata['itmsk0'], 'sk', 'itmsk0', 1) : '',
        "quality" => $tdata['itme0'],
        "durability" => $tdata['itms0'],
      );
    }
    //w_itms1
    if ($tdata['itms1']) {
      $list[] = array(
        "key" => "itm1",
        "type" => parse_info_desc($tdata['itmk1'], 'k'),
        "name" => $tdata['itm1'],
        "props" => $tdata['itmsk1'] ? parse_info_desc($tdata['itmsk1'], 'sk', 'itmsk1', 1) : '',
        "quality" => $tdata['itme1'],
        "durability" => $tdata['itms1'],
      );
    }
    //w_itms2
    if ($tdata['itms2']) {
      $list[] = array(
        "key" => "itm2",
        "type" => parse_info_desc($tdata['itmk2'], 'k'),
        "name" => $tdata['itm2'],
        "props" => $tdata['itmsk2'] ? parse_info_desc($tdata['itmsk2'], 'sk', 'itmsk2', 1) : '',
        "quality" => $tdata['itme2'],
        "durability" => $tdata['itms2'],
      );
    }
    //w_itms3
    if ($tdata['itms3']) {
      $list[] = array(
        "key" => "itm3",
        "type" => parse_info_desc($tdata['itmk3'], 'k'),
        "name" => $tdata['itm3'],
        "props" => $tdata['itmsk3'] ? parse_info_desc($tdata['itmsk3'], 'sk', 'itmsk3', 1) : '',
        "quality" => $tdata['itme3'],
        "durability" => $tdata['itms3'],
      );
    }
    //w_itms4
    if ($tdata['itms4']) {
      $list[] = array(
        "key" => "itm4",
        "type" => parse_info_desc($tdata['itmk4'], 'k'),
        "name" => $tdata['itm4'],
        "props" => $tdata['itmsk4'] ? parse_info_desc($tdata['itmsk4'], 'sk', 'itmsk4', 1) : '',
        "quality" => $tdata['itme4'],
        "durability" => $tdata['itms4'],
      );
    }
    //w_itms5
    if ($tdata['itms5']) {
      $list[] = array(
        "key" => "itm5",
        "type" => parse_info_desc($tdata['itmk5'], 'k'),
        "name" => $tdata['itm5'],
        "props" => $tdata['itmsk5'] ? parse_info_desc($tdata['itmsk5'], 'sk', 'itmsk5', 1) : '',
        "quality" => $tdata['itme5'],
        "durability" => $tdata['itms5'],
      );
    }
    //w_itms6
    if ($tdata['itms6']) {
      $list[] = array(
        "key" => "itm6",
        "type" => parse_info_desc($tdata['itmk6'], 'k'),
        "name" => $tdata['itm6'],
        "props" => $tdata['itmsk6'] ? parse_info_desc($tdata['itmsk6'], 'sk', 'itmsk6', 1) : '',
        "quality" => $tdata['itme6'],
        "durability" => $tdata['itms6'],
      );
    }
    if ($tdata['money']) {
      $list[] = array(
        "key" => "money",
        "name" => $tdata['money'].'元',
      );
    }
    return $list;
  }
  /** 获取战斗中可使用的技能 */
  function getBattleSkills($battle_skills) {
    global $cskills;
    return array_map(function($item) use ($cskills) {
      return array(
        "unlock" => !$item[0],
        "key" => 'bskill_'.$item[1],
        "title" => $cskills[$item[1]]['name'],
        "desc" => $item[2],
      );
    }, $battle_skills);
  }
  /** 获取技能页面 */
  function getSkillPage() {
    global $pdata, $cskills, $itemspkinfo, $clubinfo;
    $uidata = $pdata;
    //创建一个空数组
    $array = array();
    
    //遍历$cskills数组中的每个元素，每个元素是一个$cskill数组
    foreach ($uidata['clbpara']['skill'] as $skid) {
      $cskill = $cskills[$skid];
      //获取当前$cskill数组中的各个值
      $name = $cskill['name'];
      $cdesc = parse_skilldesc($skid, $uidata);
      $num_input = $cskill['num_input'];
      $input = $cskill['input'];
      $unlock_flag = check_skill_unlock($skid, $uidata);
      $unlock_desc = parse_skilllockdesc($skid, $unlock_flag);

      //创建一个新的数组，包含当前$cskill数组中的各个值
      $new_array = array(
        'id' => $skid,
        'name' => $name,
        'desc' => $cdesc,
        'num' => $num_input,
        'action' => $input,
        'unlockFlag' => $unlock_flag,
        'unlockDesc' => $unlock_desc,
        'specialData' => null,
      );
      // 百战
      if ($skid === 'c1_veteran') {
        $para = get_clbpara($uidata['clbpara']);
        $nchoice = $para['skillpara']['c1_veteran']['choice'];
        foreach ($cskills['c1_veteran']['choice'] as $item) {
          $list[] = array(
            'id' => $item,
            'title' => $itemspkinfo[$item],
          );
        }
        $new_array['specialData'] = array(
          'now' => $itemspkinfo[$nchoice],
          'list' => $list,
        );
      }
      // 附魔
      if ($skid === 'c3_enchant') {
        $exdmgarr = get_skillvars('c3_enchant','exdmgarr');
        $result = array();
        foreach (get_skillvars('c3_enchant','exdmgdesc') as $ex) {
          $ex_r = get_skillpara('c3_enchant', $ex, $uidata['clbpara']);
          array_push($result, array("title" => $ex, "value" => $ex_r));
        }
        $new_array['specialData'] = array(
          'list' => $result,
        );
      }
      // 专注
      if ($skid === 'c5_focus') {
        $para = get_clbpara($uidata['clbpara']);
        $new_array['specialData'] = array(
          'now' => $para['skillpara']['c5_focus']['choice'],
        );
      }
      // 灵感
      if ($skid === 'c10_inspire') {
        $para = get_clbpara($uidata['clbpara']);
        $nchoice = $para['skillpara']['c10_inspire']['choice'];
        foreach ($cskills[$skid]['choice'] as $key) {
          $list[] = array(
            'id' => $key,
            'title' => $clubinfo[$key],
          );
        }
        $new_array['specialData'] = array(
          'now' => $clubinfo[$nchoice],
          'list' => $list,
        );
      }
      //将新的数组添加到空数组中，以$skid为键
      $array[] = $new_array;
    }

    //返回最终的数组
    return $array;
  }
  /** 获取敌方技能页面 */
  function getEnemySkillPage($tdata) {
    global $cskills;
    $array = array();
    foreach ($tdata['clbpara']['skill'] as $skid) {
      $cskill = $cskills[$skid];
      $name = $cskill['name'];
      $cdesc = parse_skilldesc($skid, $uidata);
      $num_input = $cskill['num_input'];
      $input = $cskill['input'];
      $unlock_flag = check_skill_unlock($skid, $uidata);
      $unlock_desc = parse_skilllockdesc($skid, $unlock_flag);
      $new_array = array(
        'id' => $skid,
        'name' => $name,
        'desc' => $cdesc,
      );
      $array[] = $new_array;
    }
    return $array;
  }
  /** 获取佣兵信息 */
  function getMercInfo() {
    global $pdata, $plsinfo, $pls, $arealist, $areanum, $hack;
    $uidata = $pdata;
    $clbpara = get_clbpara($uidata['clbpara']);
    $merc_ids = get_skillpara('c11_merc', 'id', $clbpara);
    $merc_num = !empty($merc_ids) ? count($merc_ids) : 0;
    $merc_info = array();
    if ($merc_num > 0) {
      foreach ($merc_ids as $mkey => $mid) {
        $nowmerc = fetch_playerdata_by_pid($mid);
        $salary = get_skillpara('c11_merc', 'paid', $clbpara)[$mkey];
        $merc_info[] = array(
          // 佣兵ID
          'id' => $mkey,
          // 佣兵姓名
          'name' => $nowmerc['name'],
          // 佣兵头像
          'avatar' => 'img/n_'.$nowmerc['icon'].'.gif',
          // 佣兵是否死亡
          'isDead' => $nowmerc['hp'] == 0,
          // 佣兵友善度
          'friendShip' => init_friedship_states($pdata, 'c11_merc', $mkey),
          // 佣兵状态
          'status' => init_single_hp_states($nowmerc),
          // 佣兵工资
          'salary' => $salary,
          // 下次支付工资回合数
          'nextPay' => get_skillvars('c11_merc','mst') - get_skillpara('c11_merc','mms',$clbpara)[$mkey],
          // 佣兵位置
          'position' => $plsinfo[$nowmerc['pls']],
          // 佣兵是否可协战
          'canAssist' => $nowmerc['pls'] == $pls,
          // 佣兵出击消耗金钱
          'attackCost' => $salary * get_skillvars('c11_merc','atkp'),
          // 佣兵是否可追击
          'canChase' => !empty($nowmerc['clbpara']['mercchase']),
          // 佣兵移动消耗金钱
          'moveCost' => $salary * get_skillvars('c11_merc','movep'),
          // 解雇描述
          'fireDesc' => get_skillpara('c11_merc', 'leave', $clbpara)[$mkey] ? '离开战场' : '留在原地',
        );
      }
    }
    $options = array();
    foreach ($arealist as $pl) {
      if (array_search($pl, $arealist) > $areanum || $hack) {
        $options[] = $pl;
      }
    }
    sort($options);
    return array(
      'moveList' => $options,
      'mercList' => $merc_info,
    );
  }
  /** 获取元素口袋信息 */
  function getElement() {
    global $pdata, $elements_info;
    $elements = array('element0', 'element1', 'element2', 'element3', 'element4', 'element5');
    $result = array();
    foreach ($elements as $ekey => $enum) {
      $result[] = array(
        "title" => $elements_info[$ekey],
        "num" => $pdata[$enum],
        "mainType" => array(
          emix_init_elements_tags($ekey, 'dom', 0, $pdata),
          emix_init_elements_tags($ekey, 'dom', 1, $pdata),
        ),
        "subType" => array(
          emix_init_elements_tags($ekey, 'sub', 0, $pdata),
          emix_init_elements_tags($ekey, 'sub', 1, $pdata),
          emix_init_elements_tags($ekey, 'sub', 2, $pdata),
          emix_init_elements_tags($ekey, 'sub', 3, $pdata),
        ),
      );
    }
    return array(
      "elements" => $result,
      "hint" => emix_init_elements_info($pdata),
      "max" => emix_calc_maxenum(),
    );
  }
  echo (json_encode(array(
    /** 玩家状态 */
    "playerState" => array(
      /** 玩家信息 */
      "playerInfo" => array(
        /** 称号 */
        "nick" => titles_get_desc($nick),
        /** 姓名 */
        "name" => $name,
        /** 性别 */
        "sex" => $sexinfo[$gd],
        /** 编号 */
        "id" => $sNo,
        /** 头像 */
        "avatar" => $iconImg,
      ),
      /** 等级 */
      "level" => array(
        /** 当前等级 */
        "nowLevel" => $lvl,
        /** 经验 */
        "exp" => $exp,
        /** 升级经验 */
        "upgradeExp" => $upexp,
      ),
      /** 生命 */
      "hp" => array(
        /** 当前生命 */
        "nowHp" => $hp,
        /** 最大生命 */
        "maxHp" => $mhp,
      ),
      /** 体力 */
      "mp" => array(
        /** 当前体力 */
        "nowMp" => $sp,
        /** 最大体力 */
        "maxMp" => $msp,
      ),
      /** 怒气 */
      "rage" => $rage,
      /** 歌魂 */
      "songSoul" => array(
        "nowSongSoul" => $ss,
        "maxSongSoul" => $mss,
      ),
      /** 内定称号 */
      "gift" => array(
        /** 当前称号 */
        "nowGiftId" => $club,
        /** 可选称号 */
        "giftList" => $clubavl,
        /** 称号类型 */
        "type" => $clubinfo,
      ),
      /** 应战策略 */
      "tactic" => array(
        /** 当前姿态id */
        "nowTacticId" => $tactic,
        /** 当前姿态 */
        "nowTactic" => "<span tooltip=\"{$tactips[$tactic]}\">".$tacinfo[$tactic]."</span>",
        /** 可选id */
        "idList" => $atac,
        /** 姿态类型 */
        "type" => $tacinfo,
        /** 姿态tips */
        "tips" => $tactips,
      ),
      /** 基础姿态 */
      "pose" => array(
        /** 当前姿态id */
        "nowPoseId" => $pose,
        /** 当前姿态 */
        "nowPose" => "<span tooltip=\"{$posetips[$pose]}\">".$poseinfo[$pose]."</span>",
        /** 可选id */
        "idList" => $apose,
        /** 姿态类型 */
        "type" => $poseinfo,
        /** 姿态tips */
        "tips" => $posetips,
      ),
      /** 攻击力 */
      "attack" => $atkinfo,
      /** 防御力 */
      "defense" => $definfo,
      /** 团队 */
      "team" => $teamID ? $teamID : '',
      /** 熟练度 */
      "proficiency" => array(
        /** 殴熟 */
        "melee" => $wp,
        /** 斩熟 */
        "slash" => $wk,
        /** 射熟 */
        "shoot" => $wg,
        /** 投熟 */
        "throw" => $wc,
        /** 爆熟 */
        "blast" => $wd,
        /** 灵熟 */
        "spirit" => $wf,
      ),
      /** 击杀数 */
      "killNum" => $killnum,
      /** 负面状态 */
      "debuff" => $inf ? $inf : ['无'],
      "debuffList" => $infinfo,
      /** 装备 */
      "equipment" => array(
        /** 武器 */
        "weapon" => array(
          "type" => $wep ? $wepk_words : '空手',
          "name" => $wep_words,
          "props" => $wepsk_words != '--' ? $wepsk_words : '',
          "quality" => $wepe,
          "durability" => $weps,
        ),
        /** 装甲 */
        "armor" => array(
          "type" => $arb ? $arbk_words : '装甲',
          "name" => $arb,
          "props" => $arbsk_words != '--' ? $arbsk_words : '',
          "quality" => $arbe,
          "durability" => $arbs,
        ),
        /** 头盔 */
        "helmet" => array(
          "type" => $arh ? $arhk_words : '头盔',
          "name" => $arh,
          "props" => $arhsk_words != '--' ? $arhsk_words : '',
          "quality" => $arhe,
          "durability" => $arhs,
        ),
        /** 护臂 */
        "arm" => array(
          "type" => $ara ? $arak_words : '护臂',
          "name" => $ara,
          "props" => $arask_words != '--' ? $arask_words : '',
          "quality" => $arae,
          "durability" => $aras,
        ),
        /** 靴子 */
        "boot" => array(
          "type" => $arf ? $arfk_words : '靴子',
          "name" => $arf,
          "props" => $arfsk_words != '--' ? $arfsk_words : '',
          "quality" => $arfe,
          "durability" => $arfs,
        ),
        /** 饰品 */
        "accessory" => array(
          "type" => $art ? $artk_words : '饰品',
          "name" => $art,
          "props" => $artsk_words != '--' ? $artsk_words : '',
          "quality" => $arte,
          "durability" => $arts,
        )
      ),
      /** 背包 */
      "bag" => array(
        "item1" => $itm1 ? array(
          "typeID" => $itmk1,
          "type" => $itmk1_words,
          "name" => $itm1_words,
          "propsID" => $itmsk1,
          "props" => $itmsk1_words != '--' ? $itmsk1_words : '',
          "quality" => $itme1,
          "durability" => $itms1,
        ) : null,
        "item2" => $itm2 ? array(
          "typeID" => $itmk2,
          "type" => $itmk2_words,
          "name" => $itm2_words,
          "propsID" => $itmsk2,
          "props" => $itmsk2_words != '--' ? $itmsk2_words : '',
          "quality" => $itme2,
          "durability" => $itms2,
        ) : null,
        "item3" => $itm3 ? array(
          "typeID" => $itmk3,
          "type" => $itmk3_words,
          "name" => $itm3_words,
          "propsID" => $itmsk3,
          "props" => $itmsk3_words != '--' ? $itmsk3_words : '',
          "quality" => $itme3,
          "durability" => $itms3,
        ) : null,
        "item4" => $itm4 ? array(
          "typeID" => $itmk4,
          "type" => $itmk4_words,
          "name" => $itm4_words,
          "propsID" => $itmsk4,
          "props" => $itmsk4_words != '--' ? $itmsk4_words : '',
          "quality" => $itme4,
          "durability" => $itms4,
        ) : null,
        "item5" => $itm5 ? array(
          "typeID" => $itmk5,
          "type" => $itmk5_words,
          "name" => $itm5_words,
          "propsID" => $itmsk5,
          "props" => $itmsk5_words != '--' ? $itmsk5_words : '',
          "quality" => $itme5,
          "durability" => $itms5,
        ) : null,
        "item6" => $itm6 ? array(
          "typeID" => $itmk6,
          "type" => $itmk6_words,
          "name" => $itm6_words,
          "propsID" => $itmsk6,
          "props" => $itmsk6_words != '--' ? $itmsk6_words : '',
          "quality" => $itme6,
          "durability" => $itms6,
        ) : null,
      ),
      /** 道具背包 */
      "itemBag" => array(
        /** 背包内物品 */
        "item" => $itembag ? json_decode($itembag,true) : null,
        /** 背包内物品数量 */
        "num" => $itmnum,
        /** 背包内物品上限 */
        "limit" => $itmnumlimit,
        /** 是否装备中 */
        "isEquip" => strpos($arbsk,'^') !== false && $arbs && $arbe,
      ),
      /** 金钱 */
      "money" => $money,
      /** 地区信息 */
      "area" => array(
        /** 当前所在地区id */
        "nowArea" => $pls,
        /** 当前所在地区剩余人数 */
        "aliveNum" => $alivenum,
        /** 当前天气 */
        "weather" => $weather,
        /** 禁区进度列表 */
        "areaList" => $arealist,
        /** 当前禁区数量 */
        "areaNum" => $areanum,
        /** 每次禁区增加数 */
        "areaAdd" => $areaadd,
      ),
      /** 攻击方式 */
      "attackType" => array(
        /** 方式1 */
        "type1" => array(
          "id" => substr($wepk,1,1),
          "name" => $attinfo[substr($wepk,1,1)],
        ),
        /** 方式2 */
        "type2" => array(
          "id" => substr($wepk,2,1) ? substr($wepk,2,1) : null,
          "name" => substr($wepk,2,1) ? $attinfo[substr($wepk,2,1)] : null,
        ),
      ),
      /** 视野 */
      "semo" => $clbpara['smeo'],
      /** 合成 */
      "craftTips" => $itemcmd == 'itemmix' ? init_itemmix_tips() : '',
      /** 合成弹窗 */
      "craftDialog" => $itemindex ? init_itemmix_tips($itemindex) : '',
      /** 聊天日志 */
      // "message" => $chatdata['msg'],
      /** 休息状态 */
      "rest" => $restinfo[$state],
      /** 技能 */
      "skill" => getSkillPage(),
      /** 技能点 */
      "skillPoint" => $skillpoint,
      /** 商店 */
      "shop" => $itemdata,
      /** 行动是否可行 */
      "canAction" => array(
        /** 商店 */
        "shop" => in_array($pls,$shops) || !check_skill_unlock('c11_ebuy',$pdata),
        /** 静养 */
        "rest3" => in_array($pls,$hospitals),
        /** 安全箱 */
        "depot" => in_array($pls,$depots),
        /** 歌唱 */
        "song" => strpos($artk,'ss') !== false,
        /** 带电 */
        "electric" => $club == 7,
        /** 淬毒 */
        "poison" => $club == 8,
        /** 佣兵 */
        "mercenary" => !empty(get_skillpara('c11_merc','id',$clbpara)),
        /** 合成 */
        "crafting" => $club != 20,
        /** 元素口袋 */
        "element" => $club == 20,
      ),
      /** 安全箱物品 */
      "depotItems" => depot_getlist($name,$type),
      /** 报应点数 */
      "rp" => $club == 19 ? $rp : null,
      /** 佣兵信息 */
      "mercenary" => getMercInfo(),
      /** 雷达信息 */
      "radar" => $radarscreen,
      /** 元素口袋 */
      "element" => $club == 20 ? getElement() : null,
    ),
    /** 搜寻状态 */
    "searchState" => array(
      /** 发现物品 */
      "findItem" => $itm0 ? array(
        "type" => $itmk0_words,
        "name" => $itm0,
        "props" => $itmsk0_words != '--' ? $itmsk0_words : '',
        "quality" => $itme0,
        "durability" => $itms0,
        "canMerge" => preg_match('/^H|^P/',$itmk0) && $itms0 !== $nosta,
      ) : null,
      /** 发现敌人 */
      "findEnemy" => $tdata['nameinfo'] ? array(
        /** 敌方等级 */
        "level" => $tdata['lvl'],
        /** 敌方姓名 */
        "name" => $tdata['name'],
        /** 敌方类型 */
        "type" => $tdata['typeinfo'],
        /** 敌方称号 */
        "title" => "{$sexinfo[$tdata['gd']]}{$tdata['sNo']}号",
        /** 敌方头像 */
        "avatar" => $tdata['iconImgB'] ? $tdata['iconImgB'] : $tdata['iconImg'],
        /** 是否有大头像 */
        "hasBigAvatar" => $tdata['iconImgB'] ? true : false,
        /** 敌方怒气 */
        "rage" => $tdata['ragestate'],
        /** 敌方体力 */
        "mp" => $tdata['spstate'],
        /** 敌方生命 */
        "hp" => $tdata['hpstate'],
        /** 敌方攻击 */
        "attack" => $tdata['wepestate'],
        /** 敌方武器种类 */
        "weaponType" => $tdata['wepk_words'],
        /** 敌方武器 */
        "weapon" => $tdata['wep_words'],
        /** 敌方应战策略 */
        "pose" => $tdata['tacinfo'],
        /** 敌方基础姿态 */
        "tactic" => $tdata['poseinfo'],
        /** 敌方受伤部位 */
        "hurt" => $tdata['infdata'],
        /** 报应点数 */
        "rp" => $club == 19 ? $tdata['rp'] : null,
        /** 战斗状态 */
        "battleState" => $battle_title,
        /** 发现尸体后的操作列表 */
        "actionList" => $battle_title === "发现尸体" ? getCorpseAction() : null,
        /** 敌方道具 */
        "items" => $battle_title === "发现尸体" ? getCorpseItems($tdata) : null,
        /** 敌方技能 */
        "skill" => $tdata['clbpara']['skill'] ? getEnemySkillPage($tdata) : null,
        /** 战斗技能 */
        "battleSkills" => $battle_skills ? getBattleSkills($battle_skills) : null,
      ) : null,
      /** 发现队友 */
      "findTeam" => $battle_title === '发现队友' ? array(
        /** 队友等级 */
        "level" => $w_lvl,
        /** 队友姓名 */
        "name" => $w_name,
        /** 队友类型 */
        "type" => $typeinfo[$w_type],
        /** 队友称号 */
        "title" => "{$sexinfo[$w_gd]}{$w_sNo}号",
        /** 队友头像 */
        "avatar" => $w_iconImg,
        /** 队友怒气 */
        "rage" => $w_ragestate,
        /** 队友体力 */
        "mp" => $w_spstate,
        /** 队友生命 */
        "hp" => $w_hpstate,
        /** 队友攻击 */
        "attack" => $w_wepestate,
        /** 队友武器种类 */
        "weaponType" => $w_wepk_words,
        /** 队友武器 */
        "weapon" => $w_wep_words,
        /** 队友应战策略 */
        "pose" => $poseinfo[$w_pose],
        /** 队友基础姿态 */
        "tactic" => $tacinfo[$w_tactic],
        /** 队友受伤部位 */
        "hurt" => $w_infdata ? $w_infdata : '无',
      ) : null,
    ),
    /** 行动日志 */
    "actionLog" => $log,
  )));
?>