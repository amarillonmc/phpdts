# FCG Phase 3 — Vol.3 卡池（常磐万象 & 电波狂想）、场地区（Field Zone）三合一机制与战术积木 HANDOFF

更新：2026-10-29。适用：常磐版 PHPDTS — FCG（Fireseed Collection Game）第三阶段（Vol.3）扩充与代码/美术交接。
配套前序与同批文档：
- 第一阶段顶层设计：[`FCG-Fireseed-Collection-Game-HANDOFF.md`](./FCG-Fireseed-Collection-Game-HANDOFF.md)
- 第一阶段 75 对（150 张）卡表：[`FCG-CardPool-And-ArtPrompts.md`](./FCG-CardPool-And-ArtPrompts.md)
- 第二阶段顶层设计（Vol.2 & PvP）：[`FCG-Phase2-Vol2-And-PvP-HANDOFF.md`](./FCG-Phase2-Vol2-And-PvP-HANDOFF.md)
- 第二阶段 40 对（80 张）卡表：[`FCG-Vol2-CardPool-And-ArtPrompts.md`](./FCG-Vol2-CardPool-And-ArtPrompts.md)
- **第三阶段 Vol.3 40 对（80 张）完整卡表与生图提示词专册**：
  - 知识库归档：[`FCG-Vol3-CardPool-And-ArtPrompts.md`](C:/Users/friedrice/AppData/Local/hermes/skills/creative/dts-creative-consultation/knowledge/05-游戏系统与模式设定/FCG-Vol3-CardPool-And-ArtPrompts.md)
  - 开发仓副本：`doc/design/fcg_vol3_cardpool_and_artprompts.md`

---

## 1. 先读这里：第三阶段（Vol.3）作者已拍板的核心决议

基于作者（Nemo / 冴月麟）与开发者（NazoBlaster / 低维生物）对 FCG 第三补充包的联合规划，在正式进入 `Vol.4`（多色/调色与《无尽天梭》/幻境暗面主题）之前，`Vol.3` 承担 **「常磐版 × 电波版经典梗文化全明星延伸」** 与 **「补齐单色深度及实时互动战术积木」** 的双重任务，确认以下 **6 项核心决议**：

1. **新增牌桌「场地区（Field Zone）」—— 场地 / 倒数 / 盖放陷阱三合一单槽位**：
   - 复用新牌桌 `.fcg-tbl-row2` 出牌堆左右两侧原有的 `24%` 空白区域，为双方各设 **1 个场地区槽位**（`$side['field']`，**每人场上最多同时存在 1 张**，避免记忆负担与界面拥挤）。
   - 该槽位可容纳三类卡牌：
     - **【🏛️场地（Domain）】**：明牌永续驻留，提供环境光环或每回合末收益（如 *精灵中心、英灵殿、蹲站房、常磐森林、数字生命服务器*）。
     - **【🚨倒数 N（Countdown）】**：明牌驻留并显示倒计时徽章 `⏳N`，每回合末 `-1`，归零时自动引爆大招并退场入堆（如 *核爆倒计时、菜包拖八禁、稀神老仙毒奶*）。
     - **【🪤伏击（Trap / Secret）】**：将 DTS 中的经典陷阱（阔剑地雷、伪装炸药箱、高压EMP、毒秋刀鱼、武器反噬）做成**暗牌盖放卡**。盖放时对手只能看到神秘牌背 `✦`（服务端对敌方脱敏卡号与出牌记录），当对手操作满足条件时自动翻牌结算反制！
   - **单槽顶替与拆场规则**：己方场地区已有卡时打出新场地区卡，直接覆盖替换旧卡（旧卡入出牌堆，不卡手）；配套提供 `#127 金属探雷器 ➔ 拆弹专家的清场旋风` 专门拆除敌方场地区（含暗牌伏击）并白嫖收益。
2. **新增「倾泻减费」积木 —— 【⚡涌动（Surge）】**：
   - 响应实时制下主动倾泻手牌的快感：带有 `surge_hand` 的高费王牌在手牌中时，你每打出 1 张其他牌（`#134` 为每打出 1 张法术），该手牌费用动态 **`-1`**（最低至 0）；带有 `surge_match` 的 `#128 废品战士` 则按本局累计已打出或已弃掉的卡牌总数全局减费。
3. **盘活「弃牌」按钮 —— 【🔥孤注（Gamble）】与【♻️重构（Refactor）】**：
   - **【🔥孤注】**：打出时自动弃掉你手牌中当前实际费用最低的 1 张其他牌（若已空手则直接免费满足！），换取超模收益；且被【🔥孤注】弃掉的牌若带有【♻️重构】，同样直接触发其重构效果！
   - **【♻️重构】**：当你主动点击手牌的「弃牌（🗑️）」按钮、或被【🔥孤注】弃掉带有 `refactor(...)` 的卡牌时，**免除「弃牌扣 1 点回血」的惩罚，并立即免费执行其重构效果**（把闲置的弃牌按钮变成 0 费战术扳机）。
4. **单色高纯度底座 —— 【🌟极意 N（Mastery）】**：
   - 在 `Vol.4` 引入多色调色前，先确立纯单色构筑的终极回报：当己方对应颜色的产能 `gen[Color] >= N`（本包统一为 `4`）时，额外解锁强力效果（如 *便当盒盾压、海虎百万匹磁场、计划通行偷武神之魂、雏见泽L5反转、四面DeathNote无视潜伏抹杀*）。
5. **可控随机与满手管理 —— 【🎲奇迹（Miracle）】、【🐹囤积（Hoard）】与【💉SQL注入 (`1=1`)】**：
   - **【🎲奇迹】**：采用「保底收益 + 50%概率大成功，且当牌手处于逆境（`hp < enemy_hp`）或在场有 `#147 老千`（`aura_cheat_miracle`）时 **100% 必出大成功**」的可控设计。
   - **【🐹囤积】**：按当前手牌张数获得护甲/回血/能量收益（含满手安全抽牌不扣回血的 `draw_safe`）。
   - **【💉SQL注入（`#150 半角引号的血案`）】**：打出后本回合获得 `$side['sql_inject'] = 1`，使本回合你打出的所有【🔗连击】、【🎨余韵】、【🌟极意】、【🎲奇迹】、【🐹囤积】无视条件强制视为已满足（`1=1`）！
6. **Vol.3 卡池（Pairs `#116`–`#155`，40对/80张）与 4 位新拟态种火（Opponents `#22`–`#25`）**：
   - 五色严格各 8 对（16 张），全卡池扩展至 **155 对 / 310 张**（每色 31 对 / 62 张）。
   - 新增 4 位拟态种火牌手（均配齐普通难度与 32 张跨卷 HARD 认真构筑）：**`✦屏息的潜火`**、**`✦孤注的炽火`**、**`✦登峰的罡火`**、**`✦弄巧的游火`**。

---

## 2. 服务端引擎实现规格（`include/fcg/engine.php`）

### 2.1 `fcg_side()` 新增轻量状态位

在 `include/fcg/engine.php:45` 的 `fcg_side()` 返回数组中新增 4 个字段：
```php
'field' => null,          // 场地区单槽：null 或 array('id'=>int, 'kind'=>'domain'|'countdown'|'trap', 'op'=>string, 'turns'=>int, 'owner_side'=>int)
'surge' => array(),       // 手牌槽位减费表：$slot => int（与 'free' 完全同构，随 fcg_remove_hand 移位）
'discarded_match' => 0,   // 本局累计已打出或弃掉的卡牌总数（供 #128 废品战士 surge_match 计算）
'sql_inject' => 0,        // 本回合 SQL 注入标记：1 = 连击/余韵/极意/奇迹/囤积全部视为条件满足
```

### 2.2 手牌移除与实时费用计算辅助函数（`fcg_remove_hand` & `fcg_card_cost`）

1. **`fcg_remove_hand(&$s, $slot)`（`engine.php:329`）同步维护 `surge` 索引**：
   ```php
   function fcg_remove_hand(&$s, $slot)
   {
       array_splice($s['hand'], $slot, 1);
       foreach (array('free', 'surge') as $key) {
           $next = array();
           foreach ($s[$key] ?? array() as $i => $value) {
               if ($i !== $slot) $next[$i > $slot ? $i - 1 : $i] = $value;
           }
           $s[$key] = $next;
       }
   }
   ```
2. **新增统一实际费用计算函数 `fcg_card_cost($side, $slot)`**：
   ```php
   function fcg_card_cost($side, $slot)
   {
       if (!isset($side['hand'][$slot])) return 99;
       if (!empty($side['free'][$slot])) return 0;
       $card = fcg_cards()[$side['hand'][$slot]];
       $discount = (int)($side['surge'][$slot] ?? 0);
       if (in_array('surge_match', fcg_tokens($card['ops']), true)) {
           $discount += (int)($side['discarded_match'] ?? 0);
       }
       return max(0, $card['cost'] - $discount);
   }
   ```
   - 将 `engine.php`（`fcg_play`、`fcg_ai`）与 `adaptive.php`（`fcg_evo_end_turn`）中原本判断 `!empty($s['free'][$slot]) ? 0 : $c['cost']` 的地方统一替换为 `fcg_card_cost($s, $slot)`。
3. **手牌涌动递增辅助函数 `fcg_trigger_hand_surge(&$side, $played_card)`**：
   - 每当一方成功打出一张牌（在 `fcg_remove_hand` 之后），遍历该方当前手牌 `$side['hand']`：
     - 若手牌 `$c` 的 `ops` 含 `surge_hand`：
       - 若 `$c['pair'] === 134`（歌神音趴，仅法术触发）：当 `$played_card['type'] === 'spell'` 时，`$side['surge'][$i] = ($side['surge'][$i] ?? 0) + 1`；
       - 其余 `surge_hand` 卡（如 `#131` 练习生）：打出的任意牌均使 `$side['surge'][$i] = ($side['surge'][$i] ?? 0) + 1`。

### 2.3 弃牌与【♻️重构 `refactor`】/【🔥孤注 `gamble`】执行逻辑

新增统一弃牌处理函数 `fcg_discard_slot(&$m, $side, $slot, $by_gamble = false)`：
```php
function fcg_discard_slot(&$m, $side, $slot, $by_gamble = false)
{
    $a = &$m['sides'][$side];
    if (!isset($a['hand'][$slot])) return;
    $id = $a['hand'][$slot];
    $card = fcg_cards()[$id];
    fcg_evo_observe_hand($m, $side);
    fcg_remove_hand($a, $slot);
    $a['discarded_match'] = ($a['discarded_match'] ?? 0) + 1;
    $a['played_cards'][] = $id;
    // 检查是否带有 refactor(...) 词条
    foreach (fcg_tokens($card['ops']) as $token) {
        if (preg_match('/^refactor\((.*)\)$/', $token, $p)) {
            $sub = $card; $sub['ops'] = $p[1];
            fcg_effect($m, $side, $sub);
            fcg_log($m, fcg_side_name($m, $side).'弃掉「'.$card['name'].'」，触发了【重构】！');
            fcg_finish($m);
            return;
        }
    }
    // 普通弃牌：仅在主动点击弃牌按钮（!$by_gamble）时扣 1 点 regen；被【孤注】弃掉不扣 regen
    if (!$by_gamble) {
        $a['regen']--;
        fcg_log($m, fcg_side_name($m, $side).'弃掉了一张牌，回合回血降低了 1 点。');
    }
}
```
- 在 `account.php:249`（单人 `discard`）和 `social.php:233`（PvP `discard`）以及 `engine.php:528`（AI 满手弃牌）中，统一改为调用 `fcg_discard_slot($m, $side, $slot, false)`！
- **【🔥孤注 `gamble(sub_ops)`】解析**：
  - 在 `fcg_effect()` 中匹配 `preg_match('/^gamble\((.*)\)$/', $token, $p)`：
  - 若当前 `$a['hand']` 非空：自动找出当前手牌中 `fcg_card_cost($a, $i)` 最低的槽位 `$min_slot`（若并列取最右侧），调用 `fcg_discard_slot($m, $side, $min_slot, true)`；
  - 无论原本是否有手牌（空手同样触发），接着执行 `$sub['ops'] = $p[1]; fcg_effect($m, $side, $sub, $mirroring);`！

### 2.4 场地区（`$side['field']`）三大子机制与盖放保密协议

#### A. 放置场地区卡（`field_domain` / `field_countdown` / `trap_set`）
在 `fcg_effect()` 中处理以下三种指令（新卡直接覆盖原 `$a['field']`；若原 `$a['field']` 存在，将原 `$a['field']['id']` 推入 `$a['played_cards'][]`）：
1. **`field_domain(name)`**：
   - 设置 `$a['field'] = array('id' => $card['id'], 'kind' => 'domain', 'op' => $name, 'turns' => 0)`。
   - 若 `$name === 'peep_room_1' || $name === 'peep_room_2'`（`#122 蹲站房`）：在场期间，每当**敌方**打出一张非能量卡时（在 `fcg_play` 中检测 `$b['field']['op']`），自动使蹲站房所属方手牌中当前实际费用最高的 1 张牌获得 `$b['surge'][$max_slot] += 1`！
   - 若 `$name === 'valhalla_2' || $name === 'valhalla_3'`（`#117 英灵殿`）：置入场地时立即使牌手获得 `+2/+2`（升级版 `+3/+3`）攻防；且当己方帮手在 `fcg_monster_death()` 中阵亡（非重生）且己方场地为 `valhalla_*` 时，牌手永久获得 `+2`（或 `+3`）攻击与 `+2`（或 `+3`）白能量！
2. **`field_countdown(turns, name)`**：
   - 设置 `$a['field'] = array('id' => $card['id'], 'kind' => 'countdown', 'op' => $name, 'turns' => (int)$turns)`。
   - 特殊驻留效果：当 `$a['field']['op']` 为 `stall_eight_zones_1` 或 `stall_eight_zones_2`（`#125 菜包拖八禁`）时，在 `fcg_packet()` 结算直接攻击伤害（`!$monster_target`）时，若任一方场地区为 `stall_eight_zones_*`，本次直接攻击伤害减半 `(int)ceil($dealt / 2)`！
3. **`trap_set(trigger, name)`**：
   - 设置 `$a['field'] = array('id' => $card['id'], 'kind' => 'trap', 'trigger' => $trigger, 'op' => $name, 'turns' => 0)`。
   - **盖放保密规则（CRITICAL）**：
     - 在 `fcg_play()` 中，若打出的卡牌 `ops` 以 `trap_set(` 开头：
       - **不将 `$card['id']` 推入 `$a['played_cards']`**，也**不更新 `$m['last_card']` 与 `$m['last_spell']`**；
       - 牌桌记事只写：`fcg_log($m, fcg_side_name($m, $side).'在场地区盖放了一张伏击卡。');`。
     - 在 `account.php` 的 `fcg_public_state()` 与 `social.php` 的 `fcg_social_public()` 中，对敌方（`$i === 1`）的 `field` 做脱敏：
       ```php
       if ($i === 1 && !empty($s['field']) && $s['field']['kind'] === 'trap') {
           $s['field'] = array('id' => 0, 'kind' => 'trap', 'hidden' => true);
       }
       ```
       这样对手在前端只能看到一张神秘盖卡（`hidden: true`），哪怕打开 F12 网络面板也绝对偷看不到盖的是哪张陷阱！

#### B. 盖放陷阱（`trap`）的 5 个自动触发时点（`fcg_check_trap`）
编写辅助函数 `fcg_check_trap(&$m, $defender_side, $trigger, $context = 0)`：当 `$d = &$m['sides'][$defender_side]` 的 `$d['field']` 满足 `kind === 'trap' && $d['field']['trigger'] === $trigger` 时，立即取出陷阱卡 `$trap_id = $d['field']['id']`，清空 `$d['field'] = null`，将 `$trap_id` 推入 `$d['played_cards'][]` 且更新 `$m['last_card'] = $trap_id`，记录日志 `fcg_log($m, fcg_side_name($m, $defender_side).'翻开了伏击卡「'.fcg_cards()[$trap_id]['name'].'」！')`，并按 `op` 结算：
1. **`on_enemy_summon`（`#118 绊发铁丝网 ➔ 连环定向阔剑阵`）**：
   - **触发点**：`fcg_summon()` 末尾（敌方刚召唤或协战帮手后）。
   - **效果（`mine_snare_10_3` / `mine_snare_15_5`）**：对敌方在场帮手造成 `10`（升级版 `15`）点伤害（若溢出则伤及敌方牌手），并削减敌方牌手 `3`（升级版 `5`）点防御，调用 `fcg_monster_death()`。
2. **`on_enemy_combo2`（`#120 伪装的补给箱 ➔ 惊喜吧！是高能炸药！`）**：
   - **触发点**：`fcg_play()` 中，当出牌方 `$a['played_this_turn'] >= 2` 且打出的不是基础无限能量卡时。
   - **效果（`booby_12_3` / `booby_16_5`）**：对敌方牌手造成 `12`（升级版 `16`）点无视防御直伤，并削减敌方及其帮手 `3`（升级版 `5`）点防御（`sunder`）。
3. **`on_enemy_big_card`（`#121 高压电击陷阱 ➔ EMP 电磁脉冲雷场`）**：
   - **触发点**：`fcg_play()` 中，当出牌方打出的卡牌原始费用 `$card['cost'] >= 4` 时。
   - **效果（`emp_trap_3_2_4` / `emp_trap_5_3_6`）**：夺取敌方最多颜色的能量 `3`（升级版 `5`）点，冻结敌方帮手 `2`（升级版 `3`）回合，并使敌方本回合 `disarm += 4`（升级版 `6`）。
4. **`on_enemy_heal_or_energy`（`#124 掺了料的秋刀鱼 ➔ 下毒党的致命满汉全席`）**：
   - **触发点**：`fcg_play()` 中，当敌方打出非 `unlimited` 卡且该卡 `ops` 包含 `gain_hp`、`add_regen`、`add_energy` 或 `add_gen` 时。
   - **效果（`poison_feast_3_10` / `poison_feast_5_14`）**：使敌方 `regen -= 3`（升级版 `5`），并立即对敌方牌手造成 `10`（升级版 `14`）点无视防御毒爆伤害！
5. **`on_enemy_heavy_hit`（`#126 未注册的非法神兵 ➔ 权限[哔]的武器反噬`）**：
   - **触发点**：`fcg_packet()` 中，当 `!$monster_target && $dealt >= 10` 时。
   - **效果（`mirror_backfire_1` / `mirror_backfire_2`）**：将本次攻击伤害 `$dealt` 强制降为 `1`，并将原伤害（升级版再额外 `+6`）直接反打给攻击方牌手 `fcg_hurt($attacker, $orig_dealt + ($upg ? 6 : 0))`！

#### C. 回合末场地区结算（`fcg_turn()`）
在 `fcg_turn()` 回合末（帮手结算之后）遍历双方 `$s['field']`：
- **若 `kind === 'domain'`**：
  - `heal_both_4_clean_freeze` / `heal_both_6_def_1_clean_freeze`（`#116 精灵中心`）：牌手恢复 `4`（或 `6`）HP（升级版牌手 `def += 1`）；若己方有帮手，帮手恢复 `4`（或 `6`）HP（不超过 `maxhp`）且清除冻结 `frozen = 0`。
  - `valhalla_2` / `valhalla_3`（`#117 英灵殿`）：每回合末己方牌手获得 `+2`（或 `+3`）临时攻击加成或恢复 `3`（或 `5`）HP，若有帮手则帮手 `+1/+1/0`（升级版 `+2/+1/0`）。
  - `peep_room_1` / `peep_room_2`（`#122 蹲站房`）：每回合末获得 `+1`（升级版 `+2`）黄能量，升级版额外抽 `1` 张牌。
  - `viridian_1` / `viridian_2`（`#123 常磐森林`）：己方 `regen += 1`，敌方 `regen -= 1`（升级版 `-2`）；若己方 `gen['G'] >= 4` 且有驻场帮手，帮手额外获得 `+2/+1/+2`（升级版 `+3/+2/+3`）。
  - `digital_life_1` / `digital_life_2`（`#135 数字生命服务器`）：全色能量 `+1`，并对敌方牌手造成 `4`（升级版 `6`）点无视防御直伤。
- **若 `kind === 'countdown'`**：
  - 先递减 `$s['field']['turns']--`。
  - 若 `$s['field']['turns'] <= 0`：取出 `$done = $s['field']`，清空 `$s['field'] = null`，将 `$done['id']` 推入 `$s['played_cards'][]`，并执行倒数引爆效果：
    - `nuke_24_8` / `nuke_32_12`（`#119 核爆倒计时`）：对敌方牌手造成 `24`（升级版 `32`）点无视防御伤害，若有敌方帮手则对其造成 `24`（或 `32`）点伤害；对己方牌手造成 `8`（或 `12`）点波及伤害。
    - `stall_eight_zones_1` / `stall_eight_zones_2`（`#125 菜包拖八禁`）：对敌方牌手造成 `array_sum($s['gen']) + array_sum($enemy['gen']) + ($upg ? 12 : 6)` 点无视防御伤害，且己方抽 `2` 张牌。
    - `jinx_18_3` / `jinx_25_5`（`#149 稀神老仙毒奶应验`）：敌方牌手攻击力减半 `(int)floor($enemy['atk'] / 2)`，受到 `18`（升级版 `25`）点无视防御伤害，且敌方 `regen -= 3`（升级版 `-5`）！

### 2.5 其余 Vol.3 新增原子指令在 `fcg_effect()` / `fcg_summon()` 中的实现

1. **【🌟极意 `mastery(Color, N, sub_ops)` 与 `mastery_mon(Color, N, A, D, H)`】**：
   - 判定函数 `fcg_has_mastery($side, $color, $n)`：`return !empty($side['sql_inject']) || ($side['gen'][$color] ?? 1) >= $n;`。
   - 法术中的 `mastery(C, N, sub_ops)`：若满足极意，递归执行 `sub_ops`（注：`fcg_tokens()` 拆分 `;` 时需注意括号嵌套——**重要实现细节**：`fcg_tokens($ops)` 应改为按**括号深度为 0 时的分号 `;`** 切分 token，这样 `gamble(a; b)`、`refactor(a; b)`、`mastery(R, 4, a; b)` 内部的多条子指令就能天然支持，无需写复杂的正则特判！）。
   - 括号感知的 `fcg_tokens($ops)` 标准实现（替换 `engine.php:182`，仅 10 行）：
     ```php
     function fcg_tokens($ops)
     {
         $out = array(); $buf = ''; $depth = 0; $len = strlen($ops);
         for ($i = 0; $i < $len; $i++) {
             $ch = $ops[$i];
             if ($ch === '(') $depth++;
             elseif ($ch === ')' && $depth > 0) $depth--;
             if ($ch === ';' && $depth === 0) {
                 if (trim($buf) !== '') $out[] = trim($buf);
                 $buf = '';
             } else $buf .= $ch;
         }
         if (trim($buf) !== '') $out[] = trim($buf);
         return $out;
     }
     ```
2. **其他专有算子一览**：
   - `clear_enemy_field(energy_gain, def_gain)`（`#127 清场旋风`）：若 `$b['field'] !== null`，将 `$b['field']['id']` 推入 `$b['played_cards'][]`，清空 `$b['field'] = null`（不触发陷阱！），并使己方获得 `+energy_gain` 黄能量与 `+def_gain` 防御。
   - `summon_self_or_heal(hp)`（`#133 我是高达` 重构子指令）：若己方当前无驻场帮手，直接将 `$card` 作为帮手召唤上场（不触发协战）；若已有帮手，则为牌手恢复 `hp` 点生命并获得 `+4` 防御。
   - `upload_on_death(tier)`（`#135 数字生命` 帮手词条）：在 `fcg_monster_death()` 中，若死亡帮手带此词条，自动在所属方场地区置入 `$a['field'] = array('id'=>$monster['id'], 'kind'=>'domain', 'op'=>'digital_life_'.(int)$tier, 'turns'=>0)`。
   - `surge_boost_hand(n)`（`#136 埃克法轻工` / `#145 魔数`）：使当前己方手牌中所有槽位 `$a['surge'][$slot] = ($a['surge'][$slot] ?? 0) + $n`。
   - `armor_press`（`#138 便当盒` 帮手词条）：在 `fcg_turn()` 回合末帮手未冻结时，对敌方牌手造成等于该帮手当前 `def` 值的无视防御压杀伤害。
   - `desperate_burst(hp_limit, dmg)`（`#139 海虎爆破拳`）：若己方 `hp <= hp_limit`，额外造成 `dmg` 点穿透伤害。
   - `steal_valkyrie_soul(tier)`（`#141 计划通行偷魂`）：若敌方有驻场帮手，夺取其一半的攻防加到己方牌手身上，若己方也有帮手则将敌方帮手除 `escort/ephemeral` 外的 `abilities` 复制并入己方帮手；若敌方无帮手，额外夺取敌方 `4`（升级版 `6`）点能量并造成 `10`（升级版 `14`）点穿透伤害。
   - `gain_monster_stats(a, d, h)`（`#142 雏见泽L5`）：若己方有帮手，使其获得 `+a/+d/+h`（及 `+h maxhp`）；若无帮手，使牌手获得 `+a` 攻、`+d` 防、`+h` 生命。
   - `mastery_deathnote(req, dmg)`（`#144 四面DeathNote` 登场词条）：登场时若 `fcg_has_mastery($a, 'B', $req)`，若敌方有驻场帮手则**无视 `stealth` 潜伏直接将其击破**（`$b['monster']['hp'] = 0; fcg_monster_death($b, $a);`），并对敌方牌手造成 `dmg` 点无视防御伤害！
   - `miracle_box(tier)`（`#146 小叶子妙妙箱`）：判定大成功条件 `$jackpot = !empty($a['sql_inject']) || fcg_aura($a, 'aura_cheat_miracle') > 0 || $a['hp'] < $b['hp'] || random_int(1, 100) <= 50;`。
     - 保底：全色能量 `+1`（升级版 `+2`）、恢复 `10`（升级版 `14`）生命；
     - 大成功（叶子钦定）：额外全色能量 `+1`、对敌方造成 `14`（升级版 `20`）点穿透直伤、抽 `2` 张牌！
     - 若在场帮手有 `aura_cheat_miracle($n)`，每次触发奇迹卡额外抽 `1` 张牌并获得 `+$n` 黄能量。
   - `miracle_gem(base_stat, lucky_stat)`（`#148 赌玉狂魔`）：同样判定 `$jackpot`；保底牌手攻防各 `+base_stat`，大成功时改为攻防各 `+lucky_stat` 且若己方有驻场帮手则赋予其 `rebirth_once`（【🔥重生】）与 `+4/+4`！
   - `on_summon_jinx(enemy_atk_buff, dmg, poison)`（`#149 稀神老仙` 登场词条）：登场时使敌方牌手 `atk += enemy_atk_buff`，同时在己方场地区置入 `$a['field'] = array('id'=>$card['id'], 'kind'=>'countdown', 'op'=>($card['upgraded']?'jinx_25_5':'jinx_18_3'), 'turns'=>1)`。
   - `sql_inject`（`#150 半角引号`）：设置 `$a['sql_inject'] = 1`（回合末在 `fcg_turn()` 重置为 `0`），同时使 `combo` 与 `resonance` 判定在 `!empty($a['sql_inject'])` 时直接返回 `true`！
   - `hoard_slides(per_card_def, per_card_hp)`（`#151 首席雕花官` 登场词条）：计算手牌数 `$n = !empty($a['sql_inject']) ? 6 : count($a['hand'])`；牌手获得 `+$n * $per_card_def` 防御、恢复 `$n * $per_card_hp` 生命；若 `$n >= 4`，额外获得白产能 `+1`（升级版 `+2`）。
   - `draw_safe(n)` 与 `hoard_feast(per_card_hp, per_card_e)`（`#152 仓鼠挑战者`）：`draw_safe` 抽 `n` 张牌但手牌满 6 张时不扣 `regen`；随后按 `$cnt = !empty($a['sql_inject']) ? 6 : count($a['hand'])` 恢复 `$cnt * $per_card_hp` 生命并获得 `$cnt * $per_card_e` 绿能量。
   - `programmer_husband(tier)`（`#153 程序员老公`）：将敌方牌手 `atk` 与 `def` 中较高的一项强制降为 `1`；若敌方有驻场帮手，将其 `atk` 强制降为 `1`；升级版（`tier == 2`）额外将敌方存量最多的一种颜色能量强制降为 `1`！
   - `copy_enemy_last_card(discount)`（`#154 AI沈阳美食家`）：若 `$m['last_card'] !== null` 且手牌未满 6 张，将该卡号复制 1 张加入己方手牌末尾，并赋予 `$a['surge'][count($a['hand'])-1] = $discount`。
   - `rewind_last_played(discount)`（`#155 欧内的手好汉`）：从己方 `$a['played_cards']`（或 `$m['last_spell']`）中找出上一张非本卡的卡牌复制回手牌末尾，并赋予 `$a['surge'][count($a['hand'])-1] = $discount`。

---

## 3. 前端牌桌「场地区（Field Zone）」UI 渲染与同义同形规范（`fcg.js` & `fcg.css`）

严格遵循 NazoBlaster 拍板的 **「同义同形 UI 铁则」**：

### 3.1 新牌桌（Table UI）布局：填补 `.fcg-tbl-row2` 左右 24% 空白区
- **现状**：`include/fcg/fcg.css:117` 中 `.fcg-tbl-row2` 为 2 列网格（左：己方出牌堆，右：对手出牌堆），靠 `padding: 0 24%` 留白。
- **改法**：
  1. 将 `.fcg-tbl-row2` 改为 **4 列网格**：
     ```css
     .fcg-tbl-row2 {
       position: relative;
       z-index: 3;
       grid-template-columns: 140px minmax(0, 1fr) minmax(0, 1fr) 140px;
       column-gap: clamp(24px, 4vw, 64px);
       padding: 0 4%;
       align-items: center;
     }
     ```
     - **第 1 列（最左）**：己方场地区 `.fcg-tbl-field-zone[data-side="0"]`（与下方第 3 行最左侧的己方帮手区 `.fcg-tbl-monster-zone[data-side="0"]` 垂直对齐！）。
     - **第 2 列（中左）**：己方出牌堆 `tablePile(self, 0)`。
     - **第 3 列（中右）**：对手出牌堆 `tablePile(foe, 1)`（中间沙漏 `.fcg-tbl-turn` 依然绝对居中于第 2、3 列之间）。
     - **第 4 列（最右）**：对手场地区 `.fcg-tbl-field-zone[data-side="1"]`（与上方第 1 行最右侧的对手帮手区 `.fcg-tbl-monster-zone[data-side="1"]` 垂直对齐！）。
  2. **场地区组件 `tableField(s, side)`**：
     - **空槽态**：与 `.fcg-tbl-monster-empty` 同风格，显示 `<div class="fcg-tbl-field-empty"><span aria-hidden="true">🏛️</span><small>没有场地／伏击</small></div>`。
     - **明牌态（`domain` 或 `countdown`）**：复用帮手卡框 `.fcg-tbl-monster-card` 的尺寸与圆角，展示卡图、放大镜按钮 `🔍`、卡名与短规则；若为 `countdown`，在卡面上方状态栏 `.fcg-tbl-mon-status` 渲染一枚醒目的状态徽章 `statBadge('⏳', '倒数', s.field.turns, '倒数 '+s.field.turns+' 回合后触发', '', '#edcf7c', null, null, 'fcg-tbl-status', 'countdown')`；若为 `domain`，渲染 `statBadge('🏛️', '场地', '常驻', '永续场地效果生效中', '', colors[c.color][1], null, null, 'fcg-tbl-status', 'domain')`。
     - **暗牌伏击态（`trap`）**：
       - **己方视角（`side === 0`）**：显示己方盖放的真实卡图（半透明暗调）+ 上方状态徽章 `🪤 伏击`，方便自己随时确认盖的是哪张牌；
       - **对手视角（`side === 1 && s.field.hidden`）**：渲染神秘卡背卡片 `<article class="fcg-tbl-monster-card fcg-tbl-trap-back"><span>✦</span><div><h3>盖伏卡</h3><p class="fcg-tbl-mini-rules">【🪤伏击：满足条件时自动翻开触发】</p></div></article>`！
  3. **飞行与翻牌动画接入（`tableRects` & `animateTable`）**：
     - `tableRects()` 新增 `field: rect('.fcg-tbl-field-zone[data-side="'+side+'"] .fcg-tbl-monster-card')`。
     - 当一方打出场地区卡时，调用 `flyCard(id, source, now.field, true)` 飞入左右场地槽；当敌方伏击卡触发进入 `played_cards` 时，从 `old.field` 带着 `.fcg-tbl-card-flip` 3D 翻转特效飞入 `now.pile`！
  4. **经典视图（`battleClassic`）兼容**：在 `side()` 函数的 `monster` 旁追加一行简洁的场地区显示（明牌显示卡名+倒数，敌方暗牌显示 `🪤 盖伏卡`）。
  5. **手牌减费高亮**：当手牌因 `free` 或 `surge` 导致实际费用 `cost < c.cost` 时，手牌费用圆形徽章 `.fcg-cost` 内的 `.fcg-num` 显示减费后的实际数值，并附加绿色高亮类 `.fcg-cost-discounted{color:#7fd98a}`。

---

## 4. Vol.3 新增 4 位拟态种火牌手与 HARD 认真模式 32 张构筑表

### 4.1 普通难度配置（`include/fcg/engine.php:fcg_opponents()` Opponents `#22`–`#25`）
> **编号顺延说明**：保持 `#0~#16`（Vol.1）、`#17~#20`（Vol.2）、`#21 ✦推演的炉火` 编号完全不变，将 Vol.3 的 4 位新种火追加为 **`id = 22..25`**（立绘文件 `img/fcg/fireseeds/18.png`～`21.png`），并在大厅新增分组标题 **`「常磐与电波的回响 · Vol.3」`**。

1. **`#22 ✦屏息的潜火`**（Lv.6｜115 HP｜HARD: Lv.8 · 140 HP｜专属代表卡对：`#118, #120, #121, #122, #124, #125, #126`）
2. **`#23 ✦孤注的炽火`**（Lv.6｜115 HP｜HARD: Lv.8 · 140 HP｜专属代表卡对：`#128, #129, #130, #131, #132, #133, #134, #135`）
3. **`#24 ✦登峰的罡火`**（Lv.7｜120 HP｜HARD: Lv.9 · 145 HP｜专属代表卡对：`#116, #117, #136, #138, #139, #140, #142, #144, #145`）
4. **`#25 ✦弄巧的游火`**（Lv.7｜120 HP｜HARD: Lv.9 · 145 HP｜专属代表卡对：`#119, #123, #127, #137, #141, #143, #146, #147, #148, #149, #150, #151, #152, #153, #154, #155`）

### 4.2 4 位新种火 HARD 认真模式 32 张竞技构筑（写入 `gamedata/ruleset/FCG/npc_decks.php`）

1. **`#22 ✦屏息的潜火` — HARD【黄蓝（Y/B）蹲站窥屏 · 八禁伏击控制流】（32 张）**：
   - **能量基底（10张）**：`#9 全频段监听塔`(Y0)×3, `#11 神算妙计`(Y0)×2, `#17 深层视界回路`(B0)×3, `#19 超常顿悟`(B0)×2
   - **帮手与场地区（12张）**：`#243 十九万秒的永恒蹲站房`(Y3)×2, `#249 「菜包拖八禁」的永恒死局`(B5)×2, `#241 EMP 电磁脉冲雷场`(Y2)×3, `#251 「权限[哔]」的武器反噬`(B3)×3, `#167 九宫电磁王座·King`(Y6)×2
   - **法术与反制（10张）**：`#253 拆弹专家的清场旋风`(Y2)×2, `#267 两万六千次歌的奇迹覆唱`(B10, 蹲站+法术减费)×2, `#305 「那就只买一个苹果！」`(B3)×2, `#307 令人忍俊不禁的笑点解析`(B3)×2, `#187 凌晨三点的夺命连环催`(Y1)×2
2. **`#23 ✦孤注的炽火` — HARD【白红（W/R）废品同调 · 倾泻涌动快攻流】（32 张）**：
   - **能量基底（10张）**：`#1 器物共鸣桩`(W0)×3, `#3 满载行囊`(W0)×2, `#5 杀伐熔炉`(R0)×3, `#7 雷霆怒火`(R0)×2
   - **涌动王牌帮手（8张）**：`#255 聚集祈愿的同调王牌`(W10, 全局涌动0费)×3, `#261 两年半的铁山靠宗师`(R9, 手牌涌动0费)×3, `#233 英灵殿的黄金王座`(W4, 场地)×2
   - **孤注与倾泻引擎（14张）**：`#257 献祭一切的「无毁荣光」`(W4)×2, `#259 抡起尸体当大剑`(R3)×3, `#271 飞鱼导弹特供军火库`(W3, 全手牌减费2)×3, `#309 倒放时间的说的道理`(R3)×2, `#173 三步之内的死线`(R2)×2, `#165 压轴烟花·安可曲`(R1)×2
3. **`#24 ✦登峰的罡火` — HARD【纯红特化（R + 极意4）一百万匹海虎爆破流】（32 张）**：
   - **能量基底（10张）**：`#5 杀伐熔炉`(R0)×4, `#7 雷霆怒火`(R0)×3, `#4 战意火花`(R1)×3
   - **驻场与场地（6张）**：`#261 两年半的铁山靠宗师`(R9)×2, `#237 毁灭全图的核爆倒计时`(R4)×2, `#239 「惊喜吧！是高能炸药！」`(R2)×2
   - **极意爆发法术（16张）**：`#279 第一神触的千锤百炼`(R3, 红极意再加产能与双倍攻)×4, `#277 一百万匹·海虎爆破拳`(R6, 红极意34+46穿伤)×3, `#295 赌玉狂魔的 +15 神装`(R3)×3, `#203 神抽回应的决斗盘`(R4, 锁血过牌配合海虎残血)×3, `#309 倒放时间的说的道理`(R3, 回收海虎爆破拳再轰一拳！)×3
4. **`#25 ✦弄巧的游火` — HARD【黄绿（Y/G）SQL注入 · 老仙毒奶老千流】（32 张）**：
   - **能量基底（10张）**：`#9 全频段监听塔`(Y0)×3, `#11 神算妙计`(Y0)×2, `#13 不息温床`(G0)×3, `#15 战地急救`(G0)×2
   - **老仙与老千帮手（8张）**：`#297 言出法随的毒奶宗师`(G5, 登场挂25伤毒奶倒数)×3, `#293 随机数大神的叛军首领`(Y4, 奇迹100%大成功)×3, `#285 挂满全身的猫大爷军团`(G4, 协战极意)×2
   - **注入与奇迹法术（14张）**：`#299 ' OR 1=1 -- 终极注入`(Y2, 全条件恒真)×3, `#291 「小叶子认为你做得对」`(Y4, 钦定大成功)×3, `#281 「计划通行」偷取武神之魂`(Y5, 注入后直接偷魂)×2, `#263 删库跑路的终极重构`(Y3)×2, `#283 雏见泽 L5 狂暴觉醒`(G3)×2, `#303 囤积挑战者的冬眠粮仓`(G3)×2

---

## 5. 商城卡包、构建脚本与自进化/轮抽系统同步扩展

1. **`tools/build_fcg_cards.cjs` 扩展支持第 3 卷文档参数**：
   - 支持命令：`node tools/build_fcg_cards.cjs <Vol.1 doc> <ref-dir> <Vol.2 doc> <Vol.3 doc>`。
   - `card.series = pair <= 75 ? 'vol1' : (pair <= 115 ? 'vol2' : 'vol3')`。
   - 导出 `doc/fcg/vol3-art-jobs.json`（IDs `230`–`309`，共 80 张卡图任务）与 `doc/fcg/vol3-portrait-jobs.json`（IDs `18`–`21`，共 4 张新种火立绘任务）。
2. **卡包商城与系列过滤（`account.php`、`fcg_shop.php`、`social.php`、`shopmng.php`）**：
   - `fcg_pack_pool()` 的 `series` 白名单扩充为 `array('all', 'vol1', 'vol2', 'vol3')`。
   - `gamedata/fcg_shop.php` 新增 3 款 Vol.3 补充包种子商品（并在 `social.php:fcg_ensure_social_storage()` 中像 Vol.2 一样做幂等自动入库）：
     - `43`: **万象表象包（Vol.3）** — 10 切糕（`series: 'vol3', upgraded: false`，40 张等概率）；
     - `44`: **万象深潜包（Vol.3）** — 60 切糕（`series: 'vol3', upgraded: true`，40 张等概率）；
     - `45`: **狂想残响包（Vol.3 稀有）** — 150 切糕（`series: 'vol3', rare: true`，24 张等概率）。
3. **轮抽远征（`draft.php`）与自进化 NPC（`adaptive.php`）兼容**：
   - `fcg_draft_prepare_battle()` 第 5 层对手池从 `range(17, 20)` 扩充为 `range(17, 25)`（包含 `#21 推演的炉火` 与 Vol.3 四位新种火）。
   - `fcg_evo_effect_value()` 补充 Vol.3 新词条静态估值权重，使 `#21 ✦推演的炉火` 能自动学习并吸纳 Vol.3 的单色极意卡、场地卡与涌动卡进入五色世代进化！

---
*未经 Nemo 明确允许，不得在任何故事／设定／公开文案中使用里设定内容。*
