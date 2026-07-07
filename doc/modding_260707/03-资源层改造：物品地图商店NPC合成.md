# 03 - 资源层改造：物品地图商店 NPC 合成

资源层是最适合新手开始的地方。它决定“游戏里有什么”，例如地图上刷什么、商店卖什么、NPC 长什么样、合成公式是什么。

默认资源主要在：

```text
gamedata/cache/
```

RuleSet 资源覆盖主要在：

```text
gamedata/ruleset/<规则集ID>/cache/
```

如果房间启用了 RuleSet，`config()` 会优先读取 RuleSet 的 `cache` 目录。改默认资源却在 RuleSet 房间测试，很可能看不到变化。

## 道具的五个核心字段

大多数道具都可以用这几个字段描述：

| 字段 | 含义 |
| --- | --- |
| `itm` | 道具名 |
| `itmk` | 道具类型 |
| `itme` | 效果值，武器攻击、食物回复量、装备防御等 |
| `itms` | 耐久/数量，`∞` 表示无限耐久 |
| `itmsk` | 特殊属性字符串，每个字符代表一个属性 |
| `itmpara` | 扩展参数，通常是 JSON 字符串 |

例子：

```php
'矿泉水', 'HS', 40, 1, '', ''
```

这通常表示一个回复 SP 的消耗品。

```php
'测试长刀', 'WK', 120, 30, 'r', '{"lore":"测试用武器"}'
```

这表示斩系武器，攻击 120，耐久 30，带 `r` 连击属性，并有一段扩展说明。

## `itmk` 道具类型速查

完整定义看 `gamedata/cache/resources_1.php` 的 `$iteminfo`。常见类型：

| 前缀/类型 | 大意 |
| --- | --- |
| `WP` | 殴系武器 |
| `WK` | 斩系武器 |
| `WG` | 射系武器 |
| `WC` | 投系武器 |
| `WD` | 爆系武器 |
| `WF` | 灵系武器 |
| `WJ` | 重枪/机体相关武器 |
| `DB` | 身体防具 |
| `DH` | 头部防具 |
| `DA` | 手部防具 |
| `DF` | 足部防具 |
| `DN` | 颈部防具 |
| `A` | 饰品 |
| `HH` | 回复 HP |
| `HS` | 回复 SP |
| `HB` | 同时回复 HP/SP |
| `HT` | 增加生命上限或相关效果 |
| `HM` | 增加体力上限或相关效果 |
| `P` | 毒物或带毒回复品 |
| `C` | 治疗异常状态 |
| `T` | 陷阱 |
| `GB` / `GA` | 弹药 |
| `R` / `ER` | 雷达 |
| `EW` | 天气相关 |
| `EE` | 电子设备 |
| `V` | 技能书 |
| `M` | 强化素材 |
| `Y` / `Z` | 特殊道具，需要对应逻辑处理 |
| `X` | 多数情况下是合成素材或特殊内部用途 |

武器类型可以组合，例如 `WGK` 这类混合类型要看战斗逻辑和已有道具如何使用。

## `itmsk` 特殊属性

完整定义看 `resources_1.php` 的 `$itemspkinfo`。它不是单词列表，而是“一串字符”。例如：

```php
'rnf'
```

可能同时表示连击、贯穿、灼焰等属性。解析函数会把字符串拆成字符并查 `$itemspkinfo`。

常见属性例子：

| 字符 | 常见含义 |
| --- | --- |
| `r` | 连击 |
| `f` | 灼焰 |
| `n` | 贯穿 |
| `V` | 诅咒，很多场合不能丢弃或收入背包 |
| `v` | 灵魂绑定 |
| `^` | 背包属性 |
| `🧰` | 工具属性 |

注意：`itmsk` 是按字符拆的，使用 emoji 或多字节字符时，已有解析函数依赖项目自己的兼容处理。照着现有属性写最稳。

## `itmpara` 扩展参数

`itmpara` 可以为空，也可以是 JSON。常见用途：

```php
'{"QuestID":"Q1","QuestAction":"summon"}'
```

```php
'{"AddDamageRaw":50,"DecreaseDamagePercentage":20}'
```

代码里 `get_itmpara()` 会尝试 JSON 解码。如果不是合法 JSON，非空字符串可能原样返回。新增逻辑时不要假设它一定是数组。

## 地图物品：`mapitem_1.php`

文件：

```text
gamedata/cache/mapitem_1.php
```

它看起来像 PHP 文件，但主体其实是逗号分隔文本。第一行以 `<?` 开头只是为了防止直接访问时泄漏内容，解析时会跳过第一行。

字段顺序：

```text
iarea, imap, inum, iname, ikind, ieff, ista, iskind, itmpara
```

| 字段 | 含义 |
| --- | --- |
| `iarea` | 第几次禁区/阶段刷入，`0` 是开局，`99` 常表示每轮禁区都可能参与 |
| `imap` | 地点编号，`99` 表示随机标准地图 |
| `inum` | 数量 |
| `iname` | 道具名 |
| `ikind` | 道具类型，也就是 `itmk` |
| `ieff` | 效果值 |
| `ista` | 耐久/数量 |
| `iskind` | 特殊属性，也就是 `itmsk` |
| `itmpara` | 扩展参数，常为空或 JSON |

普通道具例子：

```text
0,99,3,测试面包,HH,80,1,,
```

陷阱一般使用 `TO*` 类类型，会进 `maptrap` 表而不是普通地图物品表：

```text
0,99,2,测试地雷,TO,120,1,,
```

注意事项：

- 这里不是完整 CSV 解析器，尽量不要在道具名里写英文逗号。
- 如果最后没有 `itmpara`，也建议保留尾部逗号。
- 改完后旧局不会自动重刷地图物品，通常要新开一局。
- 隐藏地图编号也可以作为 `imap`，但普通移动不会进入隐藏地图，入口要另做。

## 商店：`shopitem_1.php`

文件：

```text
gamedata/cache/shopitem_1.php
```

字段顺序：

```text
kind, num, price, area, item, itmk, itme, itms, itmsk, itmpara
```

| 字段 | 含义 |
| --- | --- |
| `kind` | 商店分类编号，`0` 行通常是分类标题 |
| `num` | 库存 |
| `price` | 价格 |
| `area` | 出现阶段，商店按禁区进度逐步解锁 |
| `item` | 商品名 |
| `itmk` | 道具类型 |
| `itme` | 效果值 |
| `itms` | 耐久/数量 |
| `itmsk` | 特殊属性 |
| `itmpara` | 扩展参数 |

商品例子：

```text
1,5,80,0,测试面包,HH,80,1,,
```

商店实际显示时会检查：

- 商品分类是否存在。
- `area` 是否小于等于当前解锁阶段。
- `num > 0`。
- `price > 0`。

分类标题和商店入口还与 `resources_1.php` 里的 `$gshoplist`、`$shops` 有关。

## 合成：`mixitem_1.php`

文件：

```text
gamedata/cache/mixitem_1.php
```

这是正常 PHP 数组。基本格式：

```php
$mixinfo = array(
    array(
        'class' => 'food',
        'stuff' => array('矿泉水', '面包'),
        'result' => array('简易套餐', 'HB', 80, 1, ''),
    ),
);
```

`stuff` 是素材名列表。普通合成主要按道具名匹配，顺序不重要。

`result` 字段位置：

| 位置 | 含义 |
| --- | --- |
| `0` | 结果道具名 |
| `1` | 结果 `itmk` |
| `2` | 结果 `itme` |
| `3` | 结果 `itms` |
| `4` | 可选，结果 `itmsk` |
| `5` | 可选，结果 `itmpara` |

`class` 常用于分类和提示，不一定改变逻辑。`hidden` 通常表示隐藏合成，不应直接展示完整提示。

合成还有两种特殊表：

- `synitem_1.php`：同调合成，按星数和调整素材判断。
- `overlay_1.php`：超量合成，按同星素材数量判断。

如果只是加普通配方，优先改 `mixitem_1.php`。

## NPC：`npc_1.php`

文件：

```text
gamedata/cache/npc_1.php
```

主要有两块：

- `$npcinit`：NPC 默认基础字段。
- `$npcinfo`：按类型编号定义 NPC。

一个简化例子：

```php
$npcinfo[90] = array(
    'mode' => 8,
    'num' => 1,
    'name' => '测试 NPC',
    'gd' => 'm',
    'pls' => 99,
    'mhp' => 500,
    'hp' => 500,
    'wep' => '测试刀',
    'wepk' => 'WK',
    'wepe' => 120,
    'weps' => 30,
    'club' => 1,
    'sub' => array(
        0 => array('name' => '测试 NPC A'),
    ),
);
```

常见字段：

| 字段 | 含义 |
| --- | --- |
| `mode` | 生成模式。当前常见 NPC 使用 `8` 走新式初始化流程 |
| `num` | 生成数量 |
| `sub` | 子形态数组，生成多个时会轮换合并 |
| `pls` | 所在地点，`99` 常表示随机标准地图 |
| `gd` | 性别，`r` 表示随机 |
| `club` | 社团编号 |
| `clubskill` | 额外技能列表 |
| `clubskillpara` | 技能参数 |
| `clbpara` | 扩展状态，可写 JSON/数组 |
| `horizon` | 视界，影响能否被搜索遭遇 |
| `pgroup` | 隐藏地点组，配合隐藏地图使用 |

如果 `pls` 是数组，会从数组里随机一个位置。如果 `pls=99`，会在标准地图中随机，并避开禁区/深层地图。

给 NPC 特殊机制有两种路线：

- 只套已有社团技能：改 `club`、`clubskill`、`clubskillpara`。
- 真正新增机制：需要写战斗或事件逻辑，见后续章节。

## 动态 NPC：`addnpc_1.php`

文件：

```text
gamedata/cache/addnpc_1.php
```

这是给剧情、任务、事件动态召唤 NPC 用的配置。逻辑函数是 `addnpc($type, $sub, $num, ...)`。

适合场景：

- 使用某个道具召唤 NPC。
- 任务开始时生成目标。
- 剧情推进后刷出敌人。

任务专用动态 NPC 还可能来自：

```text
gamedata/cache/addnpc_quest_1.php
```

或 RuleSet 根目录下的：

```text
gamedata/ruleset/<规则集ID>/addnpc_quest_1.php
```

注意这个任务文件不在 `cache` 下，容易放错。

## NPC 进化：`evonpc_1.php`

文件：

```text
gamedata/cache/evonpc_1.php
```

它不是新生成 NPC，而是按类型和名字找已有 NPC，再覆盖字段。适合做“某个 NPC 进入二阶段”“某个剧情后强化现有 NPC”。

这属于比较高级的资源/逻辑混合功能。改之前先搜索已有 `evonpc` 调用点。

## 套装：`setitems_1.php`

文件：

```text
gamedata/cache/setitems_1.php
```

套装分两层：

```php
$set_items = array(
    'wep' => array(
        '节操炸弹' => 'jc',
    ),
);

$set_items_info = array(
    'jc' => array(
        'name' => '有节操！',
        'active' => array(1, 6),
    ),
);
```

`$set_items` 说明“哪件装备属于哪个套装”。`$set_items_info` 说明套装名、激活件数范围和奖励描述/逻辑。装备刷新时会调用 `reload_set_items()`。

如果只是让道具在图鉴里显示属于某套装，改配置即可；如果要新套装提供全新属性结算，可能要补逻辑。

## 钓鱼：`fishing.php`

文件：

```text
gamedata/cache/fishing.php
include/game/fishing.func.php
```

资源文件定义：

- `$fishing_items`：能钓到什么。
- `$fishing_places`：地点对应鱼池。
- `$fishing_time_bonus`：时间段加成。
- `$fishing_weather_bonus`：天气加成。

逻辑里只有部分地点允许钓鱼，地点列表在 `fishing.func.php` 的 `$fishing_places_list`。如果你新增钓鱼地点，只改资源池还不够，还要让逻辑认为那里能钓。

## 安全箱、医院、商店地点

地点类功能在 `resources_1.php` 中有数组控制，例如：

```php
$depots = Array(5, 28);
$shops = Array(...);
$hospitals = Array(...);
```

这些数组决定哪些地点显示安全箱、商店或医院入口。只把地点名加到 `$plsinfo` 不会自动拥有这些功能。

## 天气、状态、地图名

也在 `resources_1.php`：

- `$plsinfo`：标准地点。
- `$hplsinfo`：隐藏地点。
- `$wthinfo`：天气名。
- `$stateinfo`：死亡/状态原因名。
- `$gwin`：结局名。

这些通常只是显示文本或基础列表。新增编号后，仍要确认逻辑有没有处理该编号。

## 资源层改造的底线

可以安全改：

- 现有数组里新增类似条目。
- 现有 CSV 风格文件里新增类似行。
- 复用现有 `itmk`、`itmsk`、`itmpara` 模式。

要小心：

- 自创 `itmk`：分发逻辑可能完全不认识。
- 自创 `itmsk`：属性说明会显示，但战斗/物品逻辑不会自动生效。
- 自创 `itmpara` 字段：没有代码读取就没有效果。
- 改 NPC `type` 或房间表结构：会影响旧存档和已有房间。

