# PHPDTS 大房间四路线结局全面重构与附属系统实装 HANDOFF（终版）

**文档版本**：v2.0 Final（取代 v1.0 初版，2026-11-12 三轮问答全部拍板后重写）
**基线**：`nouveau` @ `4e294f9`（pull 后已核对）
**适用范围**：大房间（`groomid=0`）。历史规则集（`YELLOWKNIFE`/`ACDTS_2011`/`ACDTS_298SP4`/`ACBRA_2009` 等）通过 `$ending_routes_v2` 开关保持原样。
**决策来源（冲突取新）**：`doc/design/zhenjiejin_jiaotou_spec.md` → `doc/20260915-031129-four-ending-routes-design-review.md` → `doc/20261106-165702-talents-races-severe-status-design.md` → 本会话 Nemo 三轮批复（详见附录 A）。

## 铁则

1. **最小完整改动**（`AGENTS.md`）：复用现役机制，不新建框架/服务层/新表；配置能表达就改配置。
2. **双档铁律**：玩家与 NPC 同 `Player` 对象（`type=0`）；教头/动物/五柱技能一律做成 `npc_*` 技能，玩家档=同机制、缩范围、加成本。
3. **公共事件铁律**：七日送行、善恶德、动物团、湾城 BOSS 全部全局单实例；不按玩家数复刷；状态 per-player。
4. **表设定安全**：玩家可见文案禁里设定（BASSLINE 来历、五柱身份、红蓝同一人、冰炎动力装甲细节、幻境钻探进程等）；红暮蓝凝按「孪生姐妹」分写。
5. **做加法不做减法**：旧解离（`『C.H.A.O.S』`/英灵殿/武神）以「#18 替天行道」头衔彩蛋通道保留；`董子语录`/`圣光弹` 是英灵殿内掉落，入口封死后自然不可得，无需另删配方。

---

## 0. 全局开关与编号分配

### 0.1 大房间新结局开关
- `gamedata/cache/gamecfg_1.php` 新增 `$ending_routes_v2 = 1;`（config 读取：`config('gamecfg', 1)`）。
- **开启时**（大房间默认）：§1~§9 全部生效。
- **关闭时**（历史规则集规则集配置写入 `'ending_routes_v2' => 0`；`club_skills == 0` 的规则集亦不生效）：一切保持现役行为，零回归。

### 0.2 NPC type 分配（已核实现役占用：`addnpc_1.php` 0–25、89/90/92/93；`npc_1.php` 0–33、88–93。**34–44 全空闲**）

| type | 用途 | 位置 |
| --- | --- | --- |
| `34` | 新红杀八大教头 sub 0~7（铁城/灵翼/破石/银锤/电反/三步/白凰/绯刹） | `gamedata/cache/addnpc_1.php` |
| `35` | 善德·恶德投影 sub 0~1（伪·解离 BOSS） | 同上 |
| `36` | 动物团七人 hub NPC sub 0~6（大排档/七日送行保护对象） | 同上 |
| `37` | 动物团七人 BOSS 版 sub 0~6（湾城投影） | 同上 |
| `38` | 七日送行事件怪 sub 0~4（鹅群/猎人团/袭击者/恶霸/高隐猎手/雾影） | `gamedata/addnpc_quest_1.php`（QUEST 资源层） |
| `39` | 旧红杀八大教头 sub 0~7（裂堡/弑翼/黑岩/烬锤/断序/瞬刃/魇凰/血翎） | `gamedata/cache/addnpc_1.php` |
| `40` | 真·解离阶段一 Extra sub 0~2（林无月/传承翼人·神奈备命/钥刃装甲·残留的思念） | 同上 |
| `41` | 终局两柱 sub 0~1（DIAMOND/OMICRON） | 同上 |
| `42` | 蒸汽羊 sub 0~1（电反随从） | 同上 |

- 真红暮/真蓝凝沿用现役 `type 19`（`$typeinfo` 注释即「真红暮·蓝凝用」）。
- Dark Force 沿用现役配置（`YELLOWKNIFE` Extra Boss 定位，**不**挂 `npc_retro_barrier`）。
- 幻境自主存在体（种火花/电子狐/百命猫/走地羊/书中虫/魔法蜂/笼中鸟）已在 `npc_1.php type 89` subs，第 16 棵天赋树直接引用，无需新 NPC。
- `$typeinfo` 追加对应中文名（34~42）。

### 0.3 编号预算
- **头衔**：`titles_1.php` 现占 0–124、6001/6002、7771–7776、9201–9205、9301（轮椅专用，**勿碰**）。天赋树头衔用 **9310–9325**（16 棵）。
- **成就**：现占 0–24、100、101、210、503–629。新增用 **630–657**：630 伪·解禁、631 真·解禁、632 伪·解离、633 真·解离、634–641 首杀新八教头×8、642–648 首杀动物团×7、649 首杀善德·恶德（一次）、650–657 首杀旧八教头×8。以上每项奖励 1 天赋点（见 §8.1）。
- **死法**：`$dinfo` 现占 ≤60。新增 `61` 头部破坏致死、`62` 湾城投影消散（超时）。
- **地图**：湾城投影 `pgroup = 3`，`pls 120~129` 十区；`$hplsinfo[3]` 注册（`$hplsinfo` 按 `players.pgroup` 分组，改图必须同时写 `pls` 与 `pgroup`）。

### 0.4 结局子路线标识
- `$gamevars['ending_subroute']`，取值 `'jiejin_v'`（伪·解禁）/ `'jiejin_Zv'`（真·解禁）/ `'jieli_phantom'`（伪·解离）/ `'jieli_true'`（真·解离）。
- **写入时机**：`item.ending.php` 在调用 `gameover()` 之前按消耗道具的 `$itmsk`/名称写入（结局物品会在 `end.php` 渲染前被消耗，不能事后读物品）。
- `templates/default/ending_story.htm` 按 `$winmode + $gamevars['ending_subroute']` 分派四段新文本；`subroute` 为空时回落现役旧文本（历史规则集与 `#18` 彩蛋线零影响）。

---

## 1. 伪·解禁路线

### 1.1 现状
- 普通红暮（`npc_1.php` type 1 sub 0）现掉 `挑战者之印`（`npc_1.php:28-31` 区域）。
- 朱鹮三执行官已存在于 `addnpc_1.php` **type 7 sub 3/4/5**（HOROU `club=98` `npc_overrainbow/npc_7colour`；ORIHIME `npc_wrelease/npc_flying`；SAYA `npc_mecstasy/npc_perfectspy`），各掉 `社员专用的ID卡`。
- `include/game/item.ending.php:13-18`：硬编码 `$itm == '游戏解除钥匙'` → `gameover('end3')`。**伪·解禁钥匙同样必须叫 `游戏解除钥匙`**，用 `itmsk='v'` 与真·解禁 `itmsk='Zv'` 区分。

### 1.2 改法
1. `item.npc.php`（`include/game/item.npc.php`）新增 `挑战者之印` 分支：`addnpc(7, 3, 1); addnpc(7, 4, 1); addnpc(7, 5, 1);`（一次性召唤三人组，广播）。
2. `itemmix.func.php`/`mixitem_1.php` 新增 hidden 配方：`社员专用的ID卡×3 → 游戏解除钥匙（itmsk='v'）`。
3. `item.ending.php`：`游戏解除钥匙` 分支内，先按 `$itmsk` 写 `$gamevars['ending_subroute']`（`'v'`→`jiejin_v`；含 `'Zv'`→`jiejin_Zv`），再 `gameover('end3')`。
4. 结局文本：伪·解禁**沿用现役 `winmode==3` 胜者故事**（内容即三执行官路线：ID 卡变钥匙、红暮与三执行官现身、种火问答），仅微调 1 句钥匙名衔接；不另写新故事（见 §9.1）。

### 1.3 Xharmer 指挥中心遭遇（挂 `event.func.php`，**不是** `item.platform.php`）
1. **挂点**：`include/game/event.func.php:69` 现成的空置分支 `} elseif($pls == 5) { //指挥中心`——在此分支内写遭遇逻辑。
2. **触发**：每人每局一次（`clbpara['xharmer_event']=1`），在指挥中心探索时 25% 概率弹对话。
3. **对话**：`gamedata/cache/dialogue_1.php` 新增 `'xharmer_meet'`（文案见 §9.2）；选项走引擎原生 `$dialogue_branch` + `$clbpara['noskip_dialogue'] = 1`，在 `command.php:647` 的 `dialogue_choice` 分支结算：
   - 接受 → 发 `便携式化身平台`（`itmk='💝'` 前缀平台物），`clbpara['xharmer_platform'] = array('char' => $randver1 % 3)`（0=HOROU/1=ORIHIME/2=SAYA）；
   - 拒绝 → 无。
4. **技能携带（platformSkills 白名单）**：`item.platform.php` 平台物品默认禁投射 `clbpara`（`platform_forbidden_fields()`），朱鹮三人专属技能在 `clbpara['skill']`。新增**白名单**：变身时仅挂载/卸载 `npc_overrainbow/npc_7colour/npc_wrelease/npc_flying/npc_mecstasy/npc_perfectspy` 六个技能（按化身角色二选），到期或手动解除时精准卸载，不覆盖玩家任务/路线 `clbpara`。
5. **稳定度**：不显示精确剩余次数（Xharmer 出品不稳定设备）；按 `randver2` 映射 40~60 次有效行动；界面提供**「解除投射」按钮**（`command.php` 新指令分支，调 `restorePlayerOriginalData`）。
6. **强制解除时机**：进入湾城投影扒光流程前，强制解除一切平台投射。

---

## 2. 真·解禁路线

### 2.1 新八教头（type 34 sub 0~7）
- `item.npc.php` 新增 `红杀教头挑战状` 分支：`for($i=0;$i<=7;$i++) addnpc(34,$i,1);` + 广播 `jiaotou_challenge`。
- 机制沿用 `zhenjiejin_jiaotou_spec.md` 全案（铁城追击锁技/灵翼换装爆头/破石弱点解析/银锤埋雷进化/电反蒸汽羊干扰/三步落影即死/白凰 AA 符盾/绯刹隐身淬毒），面板如下：

| 席位 | 代号 | HP/攻/防 | 要点（修正后） |
| --- | --- | --- | --- |
| 铁城 sub0 | 车 | 60,000/6,500/5,000 | 追击锁定；反击挂 `'l'` 锁缚（逃跑率-30%） |
| 灵翼 sub1 | 象 | 25,000/8,000/2,000 | 武器库换装 + 射程压制 + 爆头处决 |
| 破石 sub2 | 马 | 28,000/7,500/2,500 | 破绽-8%/层满5层穿防；非目标攻击减半层 |
| 银锤 sub3 | 兵 | 20,000/4,500/2,200 | 受击埋雷；HP≤50% 进化 32,000 |
| 电反 sub4 | 王 | 30,000/5,000/3,000 | 蒸汽羊 type42×2 干扰（命中-20%+特效清空+电子道具失败） |
| 三步 sub5 | 后 | 12,000/9,000/1,500 | `aievent.func.php` 静流式瞬移偷袭；落影满 3 层即死 |
| 白凰 sub6 | 炮 | 22,000/6,000/2,000 | AA 符盾**跨交战保留剩余层数**（4 击破盾后冷却再补符，勿脱战重置）；弹幕概率挂黑暗/迷惑诅咒；噩梦技能来源 |
| 绯刹 sub7 | 士 | 15,000/10,000/1,200 | hide≥70 现形 120 秒；攻击附内出血概率 |

- **铁城系别修正**：铁城=拳系 `WP`/`WN`（不是斩系）；追击须在 `revcombat.func.php:177` 外层 `$chase_obbs` 门控放行（现 `revcombat.func.php:237-239` 仅在 `$chase_obbs||$dfight_obbs` 时调用 `check_can_chase`）。
- **恶伤挂载**（与 §7 系统联动）：铁城/绯刹概率内出血；裂堡→内脏破裂/上肢破坏；灵翼/黑岩处决→头部破坏（免疫种族免即死仍吃高额伤害）；银锤/烬锤/真蓝凝/瞬刃→下肢破坏或断腿诅咒；白凰→黑暗/迷惑诅咒+噩梦来源；魇凰→复仇诅咒+虚弱/野兽诅咒；DIAMOND→运灭诅咒+自身背德诅咒；三步落影标记附麻烦诅咒（RP 飙升）。

### 2.2 终战（无月之影，type 19）
- 击杀判定处 `SELECT COUNT(*) FROM players WHERE type=34 AND hp>0` 为 0 → 生成真红暮（type19 sub0）与真蓝凝（type19 sub1）+ 广播 `jiaotou_final`。
- **真红暮**：HP 100,000；单次受伤封顶 10,000（10 段截断）；受实伤且存活 → 随机撤离至当前安全地点 + 广播 `hongmu_retreat`（追击用现役个人特判）。
- **真蓝凝**：沿用近千万面板；单次受伤封顶 50% MHP（2 段截断）；站桩不撤离，受击在无月之影布光球雷（现役机制）。
- **掉落**：红暮 `冰炎钥匙·炎`、蓝凝 `冰炎钥匙·冰`；`mixitem_1.php` 新 hidden 配方：`冰炎钥匙·炎 + 冰炎钥匙·冰 → 游戏解除钥匙（itmsk='Zv'）`。
- **轮椅分流**：使用合成钥匙时 `wheelchair_penalty_count($data) > 0` → 文案嘲讽（§9.3）→ 按伪·解禁结算（发 `解除钥匙` `itmsk='v'`）。天赋树技能（§8）**不计入**分流检测（技能 ID 不在 `wc_*` 列表内，天然豁免，注释写明勿改）。

---

## 3. 七日送行 QUEST 与大排档（双层架构）

### 3.1 架构（Nemo 定：静态 QUEST 资源 + 专门公共逻辑）
- **静态资源层**（`gamedata/questcfg_1.php` / `questitem_1.php` / `addnpc_quest_1.php`，经 `get_ruleset_plain_resource_file()` 加载）：七日送行用**独立前缀 `QD1`~`QD7`**（现役私人任务 `Q1`~`Q7` 受 `max_active=1` 限制，公共事件不复用其前缀）。
- **公共状态机层**：`include/game/quest_sendo.func.php`（新文件），挂 `aievent.func.php`（180 秒周期调度）+ `command.php`（大排档面板指令）。进度存 `$gamevars`（全局单实例）+ 玩家 `clbpara['sendo']`（个人 flag）。

### 3.2 流转
1. 商店（`shopitem_1.php` 新分类 19）+ 随机掉落 `【动物团的七封请柬】`（`itmk='ticket'`）。
2. 使用请柬 → `clbpara['sendo']` 同时激活 QD1~QD7。
3. 表侧 `pls 34` 改为**「代码洪流」**（`$plsinfo[34]`），移除 `event.func.php:615-617` 的 `Untainted Glory` 停留门控/弹回；原英灵殿整体迁入隐藏地图组（见 §0.4 与 §3.4 彩蛋）。只在pls 34新增命令按钮「进入动物大排档」→ 动物团成员给出文案任务提示+交接面板（交任务/领工具/重置任务）。
4. 全部完成 → 发 `【湾城通票】`（使用后执行解离线路判定，扒光逻辑并传送至湾城投影地图组）。

### 3.3 七大公共事件（180 秒周期，全局单实例）

| 事件 | 公共世界 NPC（type 38 subs / 36 subs） | 温柔完成（flag+1） | 暴力捷径（无 flag） |
| --- | --- | --- | --- |
| QD1 南飞 (Blasty) | 鹅群 38sub0 + 猎人团波次/首领 | 保护波次，全鹅存活下击杀首领 | 直接强杀首领 |
| QD2 守护之牙 (Dante) | 36sub1 驻守点 + 袭击者波次 38sub1 | 驻守满倒计时、个人击破≥2、Dante 存活 | 提前杀穿波次 |
| QD3 不杀之武 (Akane) | 恶霸 38sub2 | **HP 压至 ≤15%** 或捕绳触发时留 1 血寸止（不逃窜），使用捕绳后生擒 | 打死 |
| QD4 雪原目击 (Holly) | 高隐猎手 38sub3（hide+60） | 领「侦探眼镜」定位并击破 | 拿雪兔少女当诱饵 |
| QD5 九尾的灯 (Sandy) | 雾影 38sub4×3 | 领「引路灯」（脆弱腕部装），按**随机指定的 3 个表侧安全检查点+1 个终点**顺序护送且灯未碎 | 未装备灯·弃灯/碎灯硬杀 |
| QD6 吱吱电台 (Albert) | 地面/全图刷新掉落「备用电池」 | 交 3 电池（也可以是其他的“电池”） + 自愿再交 1 件自有装备（展示确认界面） | 只交电池 |
| QD7 鹿跃的终局 (Kiko) | 36sub6 于**下回禁区预告点** | 封锁前赶到+探索触发告别对话（dialogue文案） | 未赶上；若无新禁区预告则刷随机安全点开 180 秒最终告别窗口兜底 |

- 重置：面板「重置任务」可重置未温柔完成项，下周期重做；NPC 全局单实例不因玩家重置复刷。
- 文本（任务说明/温柔/暴力完成提示）→ `gamedata/questcfg_1.php`，全文见 §9.4。

### 3.4 旧解离彩蛋通道（#18 替天行道）
- `titles_1.php:461-463` 已实装：`#18 替天行道` 入场佩戴 `Untainted Glory`（artk='A'、artsk='HZ'），且佩戴时合成不清背包。
- **封口**：`$ending_routes_v2==1` 时移除 `mixitem_1.php:382-383`（校服+水+面包→Untainted Glory）与 `elementmix_1.php:238`（6 元素→Untainted Glory）两条旁路（`mixitem_1.php:385` 武神之魂配方与 386/387 董子语录/圣光弹配方保留——后两素材是英灵殿内掉落，入口封闭后天然不可得）。
- **通道**：只有佩戴 `Untainted Glory` 的玩家在「代码洪流」可进入隐藏英灵殿（`pgroup=4` 新组）→ 击破武神拿 `武神之魂` → 合 `『C.H.A.O.S』` → `item.ending.php` 现有 `end7` 旧结局 + 旧成就。
- `elementmix_1.php:257-258` 分解捷径：同步适配新线——元素大师可分解朱鹮三人尸体（伪·解禁）与冰炎钥匙（真·解禁）出结局物/元素，旧武神尸体分解随彩蛋通道保留。

---

## 4. 湾城投影（`pgroup=3`，`pls 120~129`）

### 4.1 地图与规则
- 十区：中央旧城区（落地点）/新金融核心区/西部山区/港口工业区/玄武数据公园/「下层」地下商业街/天青大桥/岚江口生态再造区/寂山书院/东洲岛；`$hplsinfo[3]` 注册；无禁区（`system.func.php` 禁区推进豁免）；开局零地雷；各区刷高阶物资。
- 入场时**同时写 `pls` 与 `pgroup=3`**（只改 `pls` 不生效，`$hplsinfo` 按 `pgroup` 分组）。
- **扒光**：清武器/五防具/背包（`wep,arb,arh,ara,arf,art,itm0~6`）；**保留**金钱、等级、裸属性、六系熟练、已学技能；**金钱不清是设计**（黑市消费）。扒光前强制解除平台投射。
- **安全箱**：仅定义区域可用，湾城投影内不可用（无需代码，只要新图不挂安全箱点）。
- 落地福袋：中央旧城区发 `旧红杀应急补给包`（按最高熟练度定制的成长武器+适配弹药+基础防具+强效急救剂）。
- **黑市**：中央旧城区与「下层」地下商业街两点；`command.php` 商店地点检查加入 `pls 120/126`，货架切换湾城专属（顶级各系武器/顶级补给·状态药剂/顶级强化用道具（熟练书·强化药·物品强化道具））。
- **75 分钟倒计时**：`clbpara['dissociation_deadline'] = $now + 4500`，前端 JS 倒计时；每次行动校验，超时 → `death('dissociation_timeout')`（$dinfo 62，文案 §9.5）；豁免表侧 antiAFK 挂机死与自然全禁 `end1`。

### 4.2 多人边界
- 各玩家持票独立入场、独立 75 分钟倒计时；公共 BOSS 全局单实例、先到先打。
- 若该线最终 BOSS 已全灭（投影已坍缩），后来者用票 → 提示不吞票、不扒光。
- OMICRON BOSS：单人被偷上限 3 件；BOSS 死亡自动返还至原主背包/脚下。
- 伪/真湾城独立检定互不干扰。

---

## 5. 伪·解离（难度：伪解禁 < 伪解离 ≈ 真解禁）

### 5.1 入场
- `【湾城通票】` + 温柔 4~6（或持轮椅技能者 7/7 也分流至此，文案 §9.3）。
- 扒光入场（§4.1）；不锁视界。

### 5.2 善德·恶德与动物团
- 善德（`club=13` 铁拳无敌，空手）、恶德（`club=2` 见敌必斩，斩系）坐镇寂山书院（type 35 sub0/1，各 50,000 HP 量级）。
- 七动物每局 7 抽 4（创建批次时固定，刷新不重抽），BOSS 版（type 37 subs）散布其余子图，面板 15,000~25,000 HP；hub 版（type 36）与 BOSS 版为**不同 role 实体**（杀 BOSS 版不影响大排档任务 NPC）。
- **技能继承**：善恶德开局持「未刷出 3 只 + 存活 4 只」动物技能；击破一只即全局移除对应技能；善恶德版数值较动物本尊缓（如狐火 -5%/层而非 -8%）。
- 七恶化机制存玩家 `clbpara['animal_debuff']`，每次交战递增：Blasty 荣誉之拳（HP 地板递增）/Holly 战意（耗尽无法搜索）/Dante 熬战（移动攻击耗体+有效 SP 上限递减）/Akane 关节锁（腕腿颈三锁瘫痪，保留挣脱）/Sandy 狐火（命中-8、先制+8，百分点钳位）/Albert 啮噬（限次损坏装备）/Kiko 天运（充能型幸运逃脱，禁止永久 100% 免死）。数值默认档进 `gamedata/cache/dissociation_1.php`。
- **主动逃跑恢复**（Nemo 加）：与善恶德交战主动逃脱成功 → 回 25% HP/SP + 动物恶化层数回退 1 层；该行为本身耗时（行动成本），非白嫖。

### 5.3 结算
- 击破善德→`善之印`、恶德→`恶之印`；合成为 `【解离凭证·幻境】`；`item.ending.php` 新分支 → `$gamevars['ending_subroute']='jieli_phantom'` → `gameover('end7')`。
- 全灭善恶德即可触发结局结束游戏（无后来者问题）；动物为可选削弱目标，不强制全灭。

---

## 6. 真·解离（难度高墙：真解离 <<<<< 其余）

### 6.1 入场与模式ＥＸ
- `【湾城通票】` + 温柔 7/7 且**无**轮椅技能；扒光入场；**强制 `horizon=2`（模式ＥＸ）并锁定**：`clbpara['ex_lock']=1`；`command.php:767` hor 指令拒绝、`profile.htm` 下拉禁用、平台禁投影 horizon。
- `$horizoninfo` 追加 `2 => '模式ＥＸ'`；模式EX中改界面颜色（类似于灵子视界改法，这次改成默认配色下泛红色），EX 内战斗**双向伤害 ×1.5**（`revattr.func.php` 伤害结算钩子）；遭遇门控=同视界才遭遇（死斗期解离图内互见豁免）；与同视界玩家同点休息获恢复增益；HP/SP 消耗反转做 config 开关（首轮关闭）。
- 75 分钟倒计时同 §4.1；阶段一速杀可加时（`dissociation_1.php` 方向开关：默认速杀奖励）。

### 6.2 阶段一：历史 Extra 四连（type 40 + 现役 Dark Force）
刷点：湾城投影指定区，击破上一体刷下一体：
1. **林无月**——按 2009 `acg_xilin` 面板填充（`ACBRA_2009/cache/npc_1.php`：攻击 200/技能 180/黑风衣 DB/金冠 DH/白金球鞋 DF/炼金手套 DA/奇怪挂件 AD，`WG 【Neo Axe Remote】`）（原创角色，为一穿着骑士盔甲，薰衣草色长发紫色瞳的女性形象）。
2. **传承翼人·神奈备命**——按 Nemo 提供 2010-04 原版（`mhp 303303/def 303303/lvl 1303/skill 100`，`WK 翼人的羽毛`，`DB 巫女服`，其他四槽“翼人的羽毛”）。（捏他《AIR》同名角色）
3. **钥刃装甲·残留的思念**——按 `ACDTS_2011` 现役「残留的思念」数据（捏他 KHII 隐藏 BOSS，可复用ruleset内对应npc资源）。
4. **Dark Force**——现役配置（幼体→真身 3000 万），**不挂** `npc_retro_barrier`（YELLOWKNIFE Extra 定位，可复用资源）。

**`npc_retro_barrier`（时光锚定，前 3 体挂载）**——专属技能，按 2009~2011 真实数据库还原玩家攻击面（依据 `origin/master:install/bra.sql`）：
- 生效数值截断 `smallint unsigned` 上限 `65535`：`att/def/mhp/msp/六系熟练(wp~wf)/武器防具效果(wepe,arbe~arte)`；
- 装备属性串 `wepsk/arbsk~artsk` 只取前 5 个属性字符（当年 `char(5)`）；
- 交战中临时挂起 TORONTO 翻新后的全部 `clubskills`（旧社团原生基础特性保留）；
- **不**做「单次扣血封顶 20% MHP」之类粗暴封顶（Nemo 明确否掉）；破局手段=属性克制/贯穿/毒雷/特殊属性，湾城黑市与阶段一掉落保证有老派磨血手段。

### 6.3 阶段二：旧红杀八大教头（type 39，全城分散 8 点，一次刷出+广播）
面板 35,000~80,000 HP，必掉多系毕业装（破学习），机制按 `zhenjiejin_jiaotou_spec.md` §24：
- 裂堡 Rook（液压处刑 3 层即死/骨甲免普通状态/武器防具双破坏）
- 弑翼 Bishop（弹种轮盘多段/恒先制/40% 锁头）
- 黑岩 Knight（死兆居合/难愈灼伤/饮血残血增伤）
- 烬锤 Pawn（白磷高热雷/灼伤引信×2/全图埋雷）
- 断序 King（序列学习：同手段 2 次-50%、3 次无效）
- 瞬刃 Queen（残影落空层/钢丝缠绕一线天处决）
- 魇凰 PAO（魇层满 10 夺刀自伤/精神屏障抹消）
- 血翎 SHI（毒层满即死/残血俯冲猎杀）
- 恶伤/诅咒挂载见 §2.1 表。

### 6.4 阶段三：DIAMOND + OMICRON（type 41）
- 八教头全灭后刷出：DIAMOND 于寂山书院、OMICRON 于东洲岛；各 120,000~150,000 HP。
- DIAMOND（命运演算）：受击记录攻击者属性快照，相似度过高→伤害无效；攻击吻合记录者→追加真伤递增；反制=多人轮换/多武器；附运灭诅咒、自带背德诅咒。
- OMICRON（跨维度窃盗）：命中偷装备当场装备（仓库 6 格上限，击杀爆出全归还）；预取骰截取攻击转为反击加成；维度换位（战后概率与标记玩家互换 `pls`）；终局开关：倒计时归零时若存活，死法附嘲讽文案。
- **掉落**：`命运之钥` + `维度之钥` → `【解离凭证·真实】` → `$gamevars['ending_subroute']='jieli_true'` → `gameover('end7')`。

---

## 7. 恶性受伤与诅咒（`clbpara['severe_inf']`，不动 `players.inf`）

### 7.1 七种恶性受伤（已拍板）

| 状态 | 效果 | 显示 | 解除 |
| --- | --- | --- | --- |
| 内出血 | 每次移动/探索扣当前血 3%~6% MHP + 扣 1%~3% MHP 上限（上限累计最多 -50%；保底留 1 HP 不摔死）；治愈后**恢复被扣上限** | 隐藏不提示 | 黑衣(club 8)「自愈」直接清除；其他社团原地静养满时清除 |
| 内脏破裂 | 行动 SP ×2；补给回复 ×0.35 | 隐藏不提示 | 同内出血 |
| 下肢破坏 | SP<80%MSP 无法移动；移动固定耗 80%MSP | 显示 | 仅黑衣静养解除；特定足部装备（新属性标记/义肢战靴）期间忽略 |
| 上肢破坏 | 禁用消耗品与主动道具；**允许换装**（保证伤后能穿义肢防具） | 显示 | 仅黑衣静养解除；特定身体防具期间忽略 |
| 头部破坏 | 无种族免疫→专属致死判定（不改 DB `mhp`，避免复活后 0/0 坏档）；**复活技能可免疫并输出专用文案**（春哥/百命猫等常规复活救得回来，破头威慑来自判定本身） | 显示 | 致死/复活结算即终 |
| 狂热 | 攻防 ×1.25、怒气/熟练成长 ×1.5；禁 `HH/HS/HB` 回复补给（专用镇静品除外） | 显示 | 休息/睡眠动作或镇静道具 |
| 噩梦 | 禁主动 `hor` 切视界与平台视界投射（不拦真解离强制 EX） | 显示 | 专用清醒道具或完成任意 1 个普通 QUEST |

免疫名单：妖精、ＡＩ免头部破坏；鱼人免上肢/下肢破坏；种族见 §8.4。

### 7.2 九种诅咒（已拍板）

| 诅咒 | 效果 | 说明 |
| --- | --- | --- |
| 黑暗 | 持有者遇敌进入大雾逻辑（不改全局天气），含无法辨识目标/可能误击队友 | 仅影响持有者 |
| 迷惑 | 持有者命中 ×0.67 | 仅持有者，不削对手 |
| 虚弱 | 持有者终伤 ×0.67 | 仅持有者 |
| 复仇 | 持有者无负面；真死时令凶手攻/防/全熟练随机降 1%~10%（不是降到 10%） | 反噬护身符 |
| 背德 | 持有者无负面；受击给攻击者叠层，其踩负面事件/地雷时惩罚按层放大后清空 | 反噬护身符 |
| 断腿 | 持有者无负面；真死时令击杀玩家获下肢破坏（种族免疫照常） | 反噬护身符 |
| 野兽 | 持有者常驻狂热；攻击/反击概率传染狂热给对手；须先除咒才能退狂热 | |
| 麻烦 | RP 按有效行动自然上升（刷新/无效指令不计）；除咒后停止，已增 RP 保留 | |
| 运灭 | 补给效果随机下降；战斗相关数值计算随机减益（一次行动抽一次幅度，避免连乘） | |

### 7.3 除咒与治愈渠道
- **通用除咒**：使用技能点治疗、在地点静养或治疗 ≥180 秒（黑衣组织 60 秒）。
- **普通医疗药剂（解毒草/全恢复药水）不能清恶伤与诅咒**。

### 7.4 BOSS 挂载表
见 §2.1 表（铁城/绯刹=内出血；裂堡=内脏破裂/上肢破坏；灵翼/黑岩=头部破坏；银锤/烬锤/真蓝凝/瞬刃=下肢破坏或断腿；白凰=黑暗/迷惑/噩梦来源；魇凰=复仇+虚弱/野兽；DIAMOND=运灭+背德；三步=麻烦）。

---

## 8. 账户天赋树、种族与入场

### 8.1 天赋点池（已拍板）
- **获取**：成就 630~657（四路线通关 ×4 + 首杀新八教头 ×8 + 首杀动物团 ×7 + 首杀善恶德 ×1 + 首杀旧八教头 ×8）每项永久奖励 1 点；`user.php` 可用 `credits2`（切糕）购买点数与洗点（阶梯价格进 `gamecfg_1.php` 配置）。
- **上限**：玩家同时可点满**半数树**——账户分配点数上限 = 全部树节点总点数的 50%（默认 80/160，可按实配节点成本重算），保证双树群不能全满。
- **洗点**：`user.php` 消耗切糕一键重置分配；**该账号在当前房间有存活角色时禁止洗点**（防局中改构筑）。树头衔、中段/终点技能、种族解锁为**永久收集**，洗点不清。
- **隔离**：仅 `club_skills != 0` 且 `$ending_routes_v2==1` 的大房间/标准房生效；2009/2011/298SP4/FCG/ANOTHER/ELEVATOR/FLASH/YELLOWKNIFE 等不注入。

### 8.2 十五棵天赋树（+可选第十六棵）
- **左树群·幻境动物团（7 棵）**：Blasty / Dante / Akane / Holly / Sandy / Albert / Kiko。
- **右树群·主时空帧红杀八教头（8 棵）**：铁城 / 灵翼 / 破石 / 银锤 / 电反 / 三步 / 白凰 / 绯刹。
- **可选第十六棵·电掣树**：幻境自主存在体（种火花/电子狐/百命猫/走地羊/书中虫/魔法蜂/笼中鸟，即 `npc_1.php type 89` subs 的玩家版技能与白值）——Nemo「如果想加上」，默认实装（独立头衔，不进即零影响）。
- **每棵树结构**（自根到顶）：
  1. **根节点＝头衔**（titles 9310~9325，`get_title_valid` 无入场数值）：点亮即**永久收集**；**只有挂该头衔入场才能享受该树的专属被动技能与入场福利**。
  2. **过渡小节点（白值/入场福利）**：白值与部分永续福利（开局探雷率、合成/静养耗时微缩等）根据这颗树的主人来，**全树共享适用**（受 50% 点数预算约束，与挂哪棵树头衔无关）；不做裸攻防面板堆叠。
  3. **中段/终点节点＝玩家版技能**：对应动物团成员/教头（或电掣个体）的玩家版技能，点数成本高，**点出即永久保留**（挂在头衔下生效）。
  4. **种族解锁节点**（高段）：见 §8.3。
- 玩家版技能严格双档：同机制、缩范围、加成本（例：玩家铁城=概率触发+耗 SP；白凰符盾=耗 SP 手动贴 1~2 层+CD；黑岩处决=高怒气斩杀；百命猫=每局 1 次残血锁血；走地羊=冲刺减移动 SP；兽人树追击向=受击后维持追猎，受平衡测试）。

### 8.3 种族（`players.race` 与 `$raceinfo` 0~5 已存在，直接写入）
- 种族解锁节点分布：**妖精→Blasty 树**、**鱼人→Akane 树**、**兽人→白凰树**、**龙→破石树**（学者/商业）、**ＡＩ→银锤树**。人类默认可用。
- 入场（`valid.php` 头衔选择界面旁）新增**种族下拉**：可选=已解锁种族；入场写 `players.race`。
- **天赋树技能不触发轮椅分流**（技能 ID 独立，`wheelchair_penalty_count` 只数 `wc_*`；注释防回归）。

### 8.4 六种族规格（按 `20261106-165702` 作者定案执行；妖精加 Nemo 修正；ＡＩ加 Nemo 分体规则）

| 种族 | 机制 |
| --- | --- |
| `0 人类` | 基准。无额外加成（作者未定则不加）。 |
| `1 兽人` | 除灵系外物理攻击高免疫（具体比例进 config 待平衡），额外属性弱、尤其惧火；**手/足装备槽可互换**（可双手装或双足装，仍占原两槽）；树技能走追击方向。 |
| `2 妖精` | 玻璃大炮：灵系（WF）伤害加强；入场随机绑定 1 种属性（火/冻/电/音/毒之一），该属性**输出与承伤均 ×2**；**身体装备槽废除**——`arb` 锁定一件无法卸下/替换的低属性种族装备（默认 50 效/无限耐久白板），**弱点属性直接写进该装备名字**（如「妖精羽翼·炎」），本局弱点即由名字判定；免头部破坏。湾城扒光时该锁定装备**保留**（种族机制非玩家装备）。 |
| `3 龙` | **系统变现**：指令面板新增「变现」动作，可直接将物品出售给系统换钱（玩家间无交易；出售价格/可售范围=任务绑定物禁售，其余进 config）；探索发现**特殊物品概率↑、常规武器/防具/补给概率↓**；树技能走隐匿方向。 |
| `4 鱼人` | 免上肢/下肢破坏；**游走**：连续移动时后续移动耗体递减（最低至常规 40%），搜索/交战即重置；**滑脱**：脱离追击/束缚成功率提升（具体值进 config）。 |
| `5 ＡＩ` | 免头部破坏；**一控多（分体）**： |

**ＡＩ 分体规则（Nemo 终案）**：
1. 开局即携带1个分体，局内消耗20技能点，可在技能面板部署新分体；**同时只有 1 个机体处于激活（可操作）状态**。
2. 行模型：主机体 `name=$cuser`（原行）；分体 `name=$cuser.'#AI'.K`（`type=0`，`clbpara['ai_master']=$cuser`）；背包/金钱/地点/血量完全独立；`game.php:19`/`command.php:21` 的 `fetch_playerdata_by_name($cuser)` 单行绑定不变——激活态由 `clbpara['ai_active']` 标记，主行查询始终返回主机体行、激活分体行通过主行指针定位（**禁止插两个同名 `type=0` 行**）。
3. 「切换机体」按钮（非战斗可用，耗冷却）：事务内互换激活标记；待机机体留原地，被摸时按自身装备正常反击。
4. **死亡**：分体被击破 → 自动切回主机体（若存活）；**主机体被击破 → 该账号直接判死，全部分体同时击破**。
5. **结局**：分体可以触发结局（`gameover` 以当前激活机体名结算，账户成就/胜场/天赋点只计一次）。
6. **湾城投影**：任一分体使用入场道具 → **主机体+全部分体一起传送进湾城地图组**（各按扒光规则处理）；`ex_lock`/倒计时为账号级（全行写入）。

### 8.5 存储
- `{$gtablepre}users` 新增 `talentdata` JSON 列，照 `include/paperdoll.func.php` 的懒加载列保障模式（`paperdoll_ensure_storage` 先例）实现 `talent_ensure_storage()`；字段：`earned_points`、`bought_points`、`alloc`（tree_id→node 列表）、`unlocked_titles`、`unlocked_skills`、`unlocked_races`。
- `user.php` 新 `mode=talent` 面板（点树/购买/洗点），模板 `templates/default/user.htm` 加区块（nouveau 缺失自动回落 default，不必复制）。

---

## 9. 结局演出与文案

### 9.1 分派规则（`templates/default/ending_story.htm`）
- 沿用 `op_sht_endpage` + `op_sht_0..N` 翻页结构与现役 CSS class（`ltcrimson`=红暮、`clan`=蓝凝、`yellow/evergreen/lightpink` 等）。
- `winmode==3`：`subroute=='jiejin_v'` → 现役「锁定解除」故事（微调钥匙名）；`'jiejin_Zv'` → §9.6 ②；空 → 现役文本。
- `winmode==7`：`'jieli_phantom'` → §9.6 ③；`'jieli_true'` → §9.6 ④；空 → 现役「幻境解离」（#18 彩蛋线）。
- 每段均带 `state==6` 胜者页与 `state!=6` 旁观者页（沿用「其他玩家已完成…」收尾句）。

### 9.2 Xharmer 对话（`dialogue_1.php`，键 `xharmer_meet`）
```
0: 你在指挥中心的机柜间摸索时，头顶的通风管道忽然传来一阵金属刮擦声。
1: 一只生着淡金弯角、尾巴末端像心形箭头的身影轻巧落地。少女拍了拍深紫短衫上的灰，怀里抱着个银灰色金属平台。
2: 「……呼。这地方真是越来越不好钻了。」她抬头打量了你几秒，眼睛唰地亮起来：
3: 「喂喂，那边的。你看起来像是会用它的人——」她把平台往你面前一递，上面贴着张歪歪扭扭的手写标签：
4: 「『朱鹮拟态平台·测试型』。踩上去能借到朱鹮执行官的力量。我打金属还行，人形机体的平衡老调不好，正缺个敢踩的小白鼠。」
5: 她晃了晃尾巴，又补了一句：「放心，免费样品。出了事……咳，出故障你按边上那个钮就能下来。大概。」
```
- 选项：`$dialogue_branch['xharmer_meet']` = 接受（拿平台）/ 婉拒。口吻=重度家里蹲的锻冶匠+不靠谱推销，不出门走江湖的倒爷腔。

### 9.3 轮椅分流嘲讽（`$log`）
```
【中枢协议警报】
「扶手还没松，就急着摸世界的底牌？先走这边吧。」
检测到你本局依赖了轮椅辅助技能，中枢通道已强制重定向：你将转入【伪·解禁 / 伪·解离】路线。
```

### 9.4 七日送行文本（`questcfg_1.php`）
- QD1 南飞（Blasty）：「加拿大鹅群正在迁徙，偷猎者的枪口已经瞄准了天空。保护它们平安通过。」/ 温柔：「随着最后一名猎人倒下，完整的鹅群发出高亢的鸣叫，向南方的天际飞去。」/ 暴力：「首领倒在血泊中，但混乱交火里，鹅群已经折了大半。」
- QD2 守护之牙（Dante）：「暴徒正成批涌向大排档。与獒犬少年并肩守到最后一刻。」/ 温柔：「倒计时归零，最后一名暴徒溃逃。他收起臂盾，低沉点头：『有你在，门没有被跨过。』」/ 暴力：「你杀穿了所有波次，可硝烟散尽时，大排档的招牌也碎了半边。」
- QD3 不杀之武（Akane）：「柔术是不杀之术。用捕绳让暴走的恶霸停下，而不是杀死他。」/ 温柔：「捕绳绞紧关节，恶霸脱力倒地，眼中的狂暴逐渐退去。无须见血，胜负已定。」/ 暴力：「你没能收住力道。武力赢了，不杀的誓言碎了。」
- QD4 雪原目击（Holly）：「戴上侦探眼镜，在风雪里揪出藏起来的枪口。」/ 温柔：「光谱滤镜锁定了伪装网下的身影！精准反制，猎手彻底失去先机。」/ 暴力：「你让雪兔少女暴露在开阔地引蛇出洞。目标达成了，她还在发抖。」
- QD5 九尾的灯（Sandy）：「提着引路灯穿过雾影封锁的山道，灯火一灭，迷雾就会吞掉一切。」/ 温柔：「终点处灯油恰好燃尽。一路行来，烛火未灭，迷雾自散。」/ 暴力：「灯碎了。你凭蛮力在雾里砍出一条血路，两手沾满残渣。」
- QD6 吱吱电台（Albert）：「带回备用电池，并为电波那头的听众留下一件真正的信物。」/ 温柔：「仓鼠少年把电池装进广播机，再把你的装备挂上信号塔：『吱！通了！所有人都能听到希望了！』」/ 暴力：「你丢下三枚冷冰冰的电池。电台启动了，播的只有盲音。」
- QD7 鹿跃的终局（Kiko）：「警报已经拉响，迷糊的狍子少女还在禁区里打转。带她离开。」/ 温柔：「在警报化为红光的最后一秒，你拉着她冲进安全区。善德与恶德的虚影在大门后浮现，深深鞠躬。」/ 暴力：「禁区轰然封锁。她凭极强的运气毫发无伤地弹了出来，可告别的仪式已被硝烟冲散。」

### 9.5 死法文案
- 头部破坏（$dinfo 61）：「世界在一瞬间倒转过来。你看见自己的身体倒下，看见自己的视野滚落在地。最后的念头停在半空：啊……原来是我。」
- 投影消散（$dinfo 62）：「75 分钟的时空锚定归零。湾城的街道在噪波中剥落，你伸出指尖，已化作飞散的代码。黑暗里有人叹息：『看来……你没能走到最后。』」

### 9.6 四结局胜者故事（全文，供直接替换）

**② 真·解禁「冰炎合璧」（winmode==3，subroute jiejin_Zv）**——`op_sht_endpage=6`，6 页：
```
[op_sht_0]
无月之影的风停了。
你左手的「冰炎钥匙·炎」与右手的「冰炎钥匙·冰」同时泛起光。两枚钥匙离手而起，在半空咬合、旋转——冰与炎的纹路首尾相连，化作你见过的那把「游戏解除钥匙」。
它轻飘飘落回你掌心，温凉参半，像握住了一场刚下完的雨。
[op_sht_1]
远处，红暮从断墙后走了出来。
她没出手，只是拍着身上的灰，随手把佩剑插回鞘里。
<span class="ltcrimson">「八位教头都拦不住你，连我都被你撵得满城跑。」</span>她朝你抛来一个似笑非笑的眼神，<span class="ltcrimson">「干得不错。真不错。」</span>
[op_sht_2]
蓝凝不知何时收起了满场的光球。
<span class="clan">「喂喂，别说得好像只有你一个人在挨打！」</span>她从雷场里踱出来，裙摆上还噼啪跳着细小的电光，却把法杖往怀里一抱，一副没打够的样子。
<span class="clan">「不过——能逼我们俩都出全力，算你有两下子。」</span>
[op_sht_3]
红暮抬手一引，漫天光球如萤火般熄去。
<span class="ltcrimson">「冰炎合璧。当年设下这道锁的人，恐怕没想过真有人能把两把钥匙凑齐。」</span>
她抬头望向缓缓裂开的天穹，侧身让出你面前的路：
<span class="ltcrimson">「去吧。这局，是你赢了。」</span>
你举起钥匙，喊出那句——「精神锁定解除！」
[op_sht_4]
世界崩解成代码与星光。纯白吞没一切之前，你听见身后两个声音——
<span class="ltcrimson">「记住，出了这扇门，我们就是『现实』里的人了。」</span>
<span class="clan">「下次见面，可不会这么客气了哦！」</span>
你笑了笑，把这句约战收进怀里。
[op_sht_5]
<span class="evergreen b">■ GAME CLEAR - END #3 ■</span>
「冰炎合璧」真·锁定解除，胜利！
```
旁观者页：「无月之影的枪声与蓝光雷场戛然而止……宣告解除的号令响彻天地。你松了口气——终于，有人替所有人把锁打开了。」

**③ 伪·解离「沙盘一梦」（winmode==7，subroute jieli_phantom）**——`op_sht_endpage=6`，6 页：
```
[op_sht_0]
善之印与恶之印在掌心合一。
寂山书院的落叶静止了一瞬，然后，风重新吹了起来。
[op_sht_1]
善德站在书院门前，缓缓收了拳架。她回头看了一眼身后四只略带疲惫的动物少年少女，露出温和的笑：
<span class="yellow">「能在时限里走到这里，还看破了孩子们的阵型……我们输得心服口服。」</span>
[op_sht_2]
恶德抱着手臂哼了一声。
<span class="linen">「别得意。这不过是个沙盘罢了。」</span>她用脚尖点了点地面，<span class="linen">「这湾城、这书院，连同我们俩，都只是映在幻境水面上的影子。真正的答案，比这深得多。」</span>
[op_sht_3]
<span class="yellow">「不过，作为回报，告诉你一件事吧——」</span>善德走到你面前，微微欠身，
<span class="yellow">「红暮那家伙，比你想的更看重你。这满城的风雨，有一半是她想看看你的器量。」</span>
[op_sht_4]
二人合掌。湾城十区如轻纱褪去，四只动物在逐渐透明的街道上朝你挥手。
手中的凭证滚烫，像一颗刚刚安静下来的心脏。
[op_sht_5]
<span class="evergreen b">■ GAME CLEAR - END #7 ■</span>
「沙盘一梦」幻境解离，胜利！
```
旁观者页：「湾城投影的方向亮起一片柔光。有人把整场演习，走完了。你望着那道光，忽然有点羡慕。」

**④ 真·解离「真实解离」（winmode==7，subroute jieli_true）**——`op_sht_endpage=7`，7 页：
```
[op_sht_0]
命运之钥与维度之钥发出共鸣。
东洲岛的高塔上，演算矩阵的亿万全息画面同时定格。
[op_sht_1]
DIAMOND 看着崩解的光幕，忽然笑了。那笑很轻，像一道早就写好的概率，终于兑现。
<span class="yellow">「……三厘四（0.34%）。」</span>他轻声说，<span class="yellow">「我算到了所有通往胜利的路径，唯独没有算到，你真的会凭一双凡人的手，走到这个结果之前。」</span>
[op_sht_2]
OMICRON 从裂开的维度缝隙中退回半步，披风上的黑羽四散飘落。
她盯着自己空空的掌心——那里本该攥着从你身上偷来的东西。
<span class="purple">「偷不到的东西……这还是第一次。」</span>她的声音低得像在自言自语，<span class="purple">「你的『现在』，我还给你。」</span>
[op_sht_3]
倒计时的钟声停了。
一直压在湾城上空的那道无形高墙，裂开了。
你看见那些从历史深处走来的影子——巫女的羽毛、装甲的残响、旧红杀的亡命徒们——一个接一个转身，消融在光里。
[op_sht_4]
黑岩拄刀而立，把刀插回地面，朝你点了点头。
然后，整个投影开始剥落。
[op_sht_5]
你站在崩解的世界中心，握着【解离凭证·真实】。
不是演习，不是沙盘——你亲手把「真实」，从幻境的最深处剥离了出来。
纯白与星光在尽头交汇，像在为一个走到终点的人，行注目礼。
[op_sht_6]
<span class="evergreen b">■ GAME CLEAR - END #7 ■</span>
「真实解离」胜利。传说，由凡人亲手写下。
```
旁观者页：「湾城的天，碎了。有谁把真实的深渊撕开了一道口子。你怔怔望着那道光，直到它把整个幻境照成纯白。」

---

## 10. CG 生图 Prompt 与角色参考卡（供代码 LLM 直接生图）

**通用规则**：每段 Prompt 末尾必须追加 `NO text, NO letters, NO nameplates, NO signature, NO UI frames, no words anywhere in the image.`；参考图用本地绝对路径 `knowledge/98-人设图/` 下文件；场景写清构图与光线，角色外貌以参考卡为准。

### 10.1 角色参考卡（vision 已核对立绘）

| 角色 | 参考卡 | 参考图 |
| --- | --- | --- |
| 红暮 | 鲜红长直发（粉红高光）红瞳；深灰黑无袖翻领上衣+红粉细领带、红粉长外套滑落露肩、深蓝超短裤、黑灰过膝袜 | `98-人设图/紅暮3f.png` |
| 蓝凝 | 冰蓝长直发、蓝青渐变瞳；浅蓝白和风交领滑肩+蓝圆斑、百褶裙；持魔法书 | `98-人设图/蓝凝.png` |
| Xharmer | 橘红短发高双马尾、金黄竖瞳；淡金弯角+心形箭头尾、尖耳、金色圆环耳饰、浅金蝙蝠翼；深紫露脐上衣+黄短马甲+低腰短裤、紫长手套 | `98-人设图/Xharmer.png` |
| DIAMOND | 黑偏深蓝中短发、浅金黄瞳；深蓝灰衬衫卷袖、多条皮带、黑长外套披肩（白斑纹+蓝绿渐变）、金色手环项链 | `98-人设图/DIAMOND.png` |
| OMICRON | 浅金棕长直发（发梢青蓝紫渐变）；左紫瞳+**右眼银色尖刺蓝紫宝石眼罩**；黑色亮面深V上衣、黑网格袜、深蓝黑长披风酒红内衬+黑羽（**无机械臂**） | `98-人设图/OMICRON.png` |
| 善德 | 浅樱粉高双马尾粉蝴蝶结、灰紫瞳；白长外套粉蓝紫滚边+袖交叉系带、深蓝紫翻领+白领带、粉兔挂件 | `98-人设图/善德/1.png` |
| 恶德 | 银白及肩中短发（淡紫内层）、紫罗兰-青蓝渐变瞳；深紫兜帽、白露腹短上衣青系带、开衩短裙、机械耳状角 | `98-人设图/恶德/1.png` |
| 动物团 | Blasty（黑长直挑白染、深棕运动裤+皮外套+合金拳套）/Holly（白长发长耳、白色连帽运动服）/Albert（金棕短发、工装连体裤+护目镜）/Kiko（头顶杂物、碎花背心裙）/Sandy（白橙开衫、三尾、折扇）/Akane（深青羽织）/Dante（战术夹克、犬耳尾） | `98-人设图/{Blasty,Holly,Albert,Kiko,Sandy,Akane,Dante}.png` |
| 新八教头 | 白凰（~160cm 白发狐耳少女、符咒法杖）/绯刹（深红长发、小恶魔尾、双匕首）/灵翼（~185cm 金发青年、光环饰、十字长狙）/三步（~155cm 棕发哥特少女、提灯）/铁城（~190cm 黑发角饰巨汉、背负巨剑）/银锤（金发、巨大金属行李箱）/破石（银绿发眼镜、复合弓）/电反（黑长直女仆装、两只蒸汽羊） | `98-人设图/红杀教头/`（每人一张+徽标） |

### 10.2 CG Prompt（每个结局 2 张）

- **真·解禁 A**（红蓝并肩）：`Scene: ruined moonlit battlefield. Two young women stand side by side before the viewer: CRIMSON (long crimson hair, red eyes, dark sleeveless top with crimson-pink tie, crimson-pink long coat slipping off shoulders) sheathing a sword; AZURE (long ice-blue hair, blue-cyan gradient eyes, pale blue-white kimono top, pleated skirt) hugging a magic staff, tiny electric sparks on her skirt. Burning light orbs fade into the dark sky like fireflies. Composition: low angle, both facing viewer, confident small smiles. Style: anime game CG illustration.`
- **真·解禁 B**（冰炎钥匙）：`Scene: close-up of two glowing keys floating and merging in mid-air — one wreathed in red flame, one sheathed in blue ice — their patterns interlocking into a single key. Background: collapsing digital sky of code fragments. Foreground: the player's outstretched hands from behind. Style: anime game CG illustration, dramatic rim light.`
- **伪·解离 A**（沙盘褪去）：`Scene: a quiet academy courtyard at dusk. Two women — GRACE (light sakura-pink twin tails with pink ribbons, grey-purple eyes, white long coat with pink-blue-purple trim, pink rabbit charm bag) bowing slightly with a gentle smile, and DISGRACE (silver-white bob hair, violet-cyan gradient eyes, deep purple hood, white cropped top, mechanical ear-like horns) standing with arms crossed — watch four animal-eared teenagers (goose girl, rabbit girl, hamster boy, roe deer girl) waving goodbye as the whole city around them fades into translucent light and falling leaves. Style: anime game CG illustration, warm melancholy lighting.`
- **伪·解离 B**（凭证发光）：`Scene: two seals, one glowing warm amber, one glowing cold indigo, merging in the player's palm into a single softly shining certificate. Background: the ten districts of a night city dissolving like thin gauze. Style: anime game CG illustration.`
- **真·解离 A**（矩阵定格）：`Scene: atop a high tower on an island at night. DIAMOND (short black-blue hair, pale golden eyes, dark blue-grey shirt with rolled sleeves, black long coat draped on shoulders with white mottled pattern) smiling faintly as a colossal holographic matrix of countless frozen screens collapses into light fragments around her. Composition: her figure small against the giant broken matrix. Style: anime game CG illustration, cold dramatic lighting.`
- **真·解离 B**（维度归还）：`Scene: a dark dimensional rift tearing open in the air. OMICRON (long light golden-brown hair fading to cyan-blue-purple at the tips, purple left eye, silver spiked blue-purple gem eyepatch over right eye, black glossy outfit, dark blue-black long cape with black feathers) steps back as black feathers scatter, reaching out her empty palm toward the viewer as if returning something. Style: anime game CG illustration, dramatic purple rim light.`

---

## 11. 实装清单（按文件，现状→改法）

| 文件 | 改法 |
| --- | --- |
| `gamedata/cache/gamecfg_1.php` | `$ending_routes_v2=1`；天赋点购买/洗点价格、兽人减伤、龙售价、鱼人数值等旋钮 |
| `gamedata/cache/resources_1.php` | `$typeinfo` 补 34~42；`$horizoninfo` 补 `2=>'模式ＥＸ'`；`$hplsinfo[3]` 注册十区；`$plsinfo[34]` 改「代码洪流」；`$dinfo` 补 61/62 |
| `gamedata/cache/addnpc_1.php` | type 34/35/36/37/39/40/41/42 全部 NPC 面板+掉落+台词 |
| `gamedata/questcfg_1.php` `questitem_1.php` `addnpc_quest_1.php` | QD1~QD7 静态资源（前缀 QD，公共事件不占 `max_active=1`） |
| `gamedata/cache/titles_1.php` | 9310~9325 树头衔（`get_title_valid` 挂 `clbpara['talent_tree']=<id>`） |
| `gamedata/cache/achievement_1.php` | 630~657 成就（各奖 1 天赋点） |
| `gamedata/cache/dialogue_1.php` | `xharmer_meet` + `$dialogue_branch` 以及其他对话相关文案 |
| `gamedata/cache/mixitem_1.php` | 冰炎钥匙→游戏解除钥匙(Zv)；ID卡×3→游戏解除钥匙(v)；封口校服/水/面包配方（v2 开关） |
| `gamedata/cache/elementmix_1.php` | 封口 6 元素→Untainted Glory（v2 开关）；分解捷径适配朱鹮尸体/冰炎钥匙 |
| `gamedata/cache/shopitem_1.php` | 新分类 19：`【动物团的七封请柬】`；除厄御守/净灵水投放 |
| `include/game/item.npc.php` | 挑战者之印（type7×3）、红杀教头挑战状（type34×8） |
| `include/game/event.func.php` | `$pls==5` 分支写 Xharmer 遭遇；615-617 英灵殿门控迁隐藏组 |
| `include/game/item.ending.php` | 游戏解除钥匙按 itmsk 写 subroute；凭证·幻境/真实→end7+subroute；『C.H.A.O.S』保留 |
| `include/game/item.platform.php` | platformSkills 白名单（六技能按化身挂卸）；「解除投射」按钮；湾城扒光前强制解除 |
| `include/game/quest_sendo.func.php`（新） | QD1~QD7 公共状态机（180 秒调度、全局单实例、温柔 flag、重置、通票发放） |
| `include/game/aievent.func.php` | 挂 quest_sendo 调度；三步落影偷袭 |
| `include/game/command.php` | 大排档面板、切换机体、解除投射、变现（龙）、黑市点、hor 锁（EX） |
| `include/game/revattr.func.php` / `revattr_extra.func.php` / `revcombat_extra.func.php` | 教头机制 attr_extra_34_*、EX 双向 ×1.5、npc_retro_barrier、恶伤/诅咒结算、头部破坏判定 |
| `include/game/revcombat.func.php` / `revcombat.calc.php` | 铁城追击 `$chase_obbs` 门控放行（:177/:237-239/:150） |
| `include/game/search.func.php` | 湾城组移动/黑市/倒计时校验；EX 遭遇门控 |
| `include/state.func.php` | `dissociation_timeout`/破头致死结算；复活技能免疫破头文案 |
| `include/system.func.php` | 禁区豁免湾城组；`gameover` 前固化 subroute |
| `include/game/wheelchair.func.php` | 无需改（天赋技能不触发；写注释防回归） |
| `user.php` + `templates/default/user.htm` | talent 面板；`talent_ensure_storage()`（paperdoll 懒加载模式） |
| `valid.php` + `templates/default/valid.htm` | 种族下拉；树头衔入场应用 `clbpara['talent_tree']` |
| `include/game/dissociation.func.php`（新） | 湾城入场扒光/福袋/黑市/75 分钟/公共 BOSS 边界/逃跑恢复/偷窃返还 |
| `templates/default/ending_story.htm` | §9.6 四结局页（胜者+旁观者） |
| `include/news.func.php` | 广播：jiaotou_challenge/jiaotou_final/hongmu_retreat/dissociation_enter 等 |

### 回归与验证
- 新增/扩展回归：QUEST 层 `quest_q6` 同款测试（QD* 状态机）、平台白名单挂卸、`npc_retro_barrier` 截断（65535/char(5)/挂起 clubskills）、天赋点预算与洗点锁、AI 分体切换/死亡/湾城全传送。
- 全部规则集（2009/2011/298SP4/FCG/ANOTHER/ELEVATOR/FLASH/YELLOWKNIFE）跑现役回归确认 `ending_routes_v2=0` 零行为变化。
- 实装侧完成 `php -d short_open_tag=1 -l` + `git diff --check`；浏览器多人实测：七日送行并发、湾城后来者入场、分体切换。

### 文档记录
- 实装侧落 `doc/YYYYMMDD-HHMMSS-*.txt`（改动/验证/已知限制，中文为主）。

---

## 12. 参数收口（非阻塞，视情况进 `dissociation_1.php`/`npc_1.php`/`addnpc_1.php`/`gamecfg_1.php` 配置，测试调参）

- 兽人物伤减伤比例与火弱点倍率；鱼人游走/滑脱数值；龙售价与可售范围；妖精锁定装备名与数值（默认「妖精羽翼·X」，50 效）。
- 各 BOSS 最终面板与掉落表（梯度已定：动物 15k~25k、善恶德各 50k、旧八 35k~80k、两柱 120k~150k、新八 12k~60k；DF/真蓝凝沿用现役）。
- 玩家档技能数值（SP 成本/回合数）以测试为准。
- 七日送行各事件周期参数（触发间隔/窗口/重刷/灯碎率/眼镜充能 3/QD6 电池总数）；鹅群只数/波次强度。
- 阶段加时倍率与方向（默认速杀奖励）；EX 双向增伤倍率（默认 1.5）；消耗反转开关（默认关）。
- 天赋树各节点具体白值、单树点数分布（结构定：根头衔/过渡白值/中段技能/终点技能/种族节点）；AI「分体部署」部署成本。
- 八教头信物（车之令牌等）：本轮=通常物品，无特殊用途，收藏系统留待后续。

---

## 附录 A：Nemo 三轮批复拍板记录（摘要）

1. 旧解离：董子语录/圣光弹=英灵殿内掉落，入口封死即不可得；元素大师合成 Untainted Glory 配方于大房间**移除**；#18 替天行道头衔=进英灵殿唯一彩蛋通道。
2. 真解离阶段一：**保留林无月**（09 年 acg_xilin 面板）；神奈备命用 2010-04 原版数据；大师的思念=残留的思念（KHII 捏他）；`npc_retro_barrier` 按旧版攻击面还原（65535/char(5)/挂起 clubskills），**不做 20% MHP 封顶**；Dark Force 不挂 barrier（YELLOWKNIFE Extra 定位）。
3. 平台：platformSkills 白名单；不显示精确倒数+手动解除投射按钮；湾城扒光前强制解除。
4. 安全箱仅定义区域、湾城内不可用；**携带金钱不清是设计**（黑市）；主动逃跑回血减层=耗时行为非白嫖。
5. 全灭 BOSS 者直接触发结局结束游戏，无后来者问题。
6. 难度：伪解禁 < 伪解离 ≈ 真解禁 <<<<< 真解离。
7. 破头：复活技能可免疫并输出专用文案。
8. 反噬三诅咒=挂自己坑对方的护身符被动；除咒=技能点治疗/静养或治疗 180 秒（黑衣 60 秒）。
9. 恶伤/诅咒 BOSS 挂载表照准。
10. 天赋：结局+首杀教头/动物团成员=成就各奖 1 点；切糕可购点/洗点；**15 棵树=7 动物+8 教头**（可选第 16 棵电掣）；每树根=永久头衔，挂头衔入场享该树被动+入场福利；白值/永续福利全树共享；中段/终点=永久玩家版技能；**同时只能点满半数树**；种族选择加入入场；天赋技能不触发轮椅分流。
11. 种族：除妖精外全部按 `20261106` 作者定案（兽人=物伤减免+火弱+手足槽互换+追击向；龙=系统变现+搜物倾向+隐匿向；鱼人=游走+滑脱；ＡＩ=免破头+分体）；妖精=废除身体装备槽（无法卸下的低属性种族装备，名字判定本局弱点）；ＡＩ 分体=20 点技能、单激活、分体死回主机、主机死全灭、分体可触发结局、湾城入场全机体传送。
12. 结局文案：四处里设定转表设定，扩写至现役 end 文案长度并附 CG 提示词。
