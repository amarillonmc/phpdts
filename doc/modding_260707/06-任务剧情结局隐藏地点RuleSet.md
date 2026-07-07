# 06 - 任务剧情结局隐藏地点 RuleSet

本章讲更接近“做一套玩法内容”的系统：任务、剧情、结局、隐藏地点、RuleSet，以及战斗时有特殊机制的 NPC。

## 任务系统的组成

主要文件：

```text
gamedata/cache/questcfg_1.php
gamedata/cache/questitem_1.php
gamedata/cache/addnpc_quest_1.php
include/game/quest.func.php
include/game/item.quest.php
templates/default/quest.htm
templates/default/slidingpanel.htm
```

RuleSet 任务资源有一个容易踩的点：任务相关扩展文件通过 `get_ruleset_plain_resource_file()` 读取，通常放在规则集根目录，不是 `cache` 目录。

例如：

```text
gamedata/ruleset/YELLOWKNIFE/questcfg_1.php
gamedata/ruleset/YELLOWKNIFE/questitem_1.php
gamedata/ruleset/YELLOWKNIFE/addnpc_quest_1.php
```

## `questcfg_1.php`

这里有两层：

- `$questcfg_global`：全局任务参数。
- `$questcfg`：单个任务配置。

全局参数例子：

| 字段 | 含义 |
| --- | --- |
| `max_active` | 最多同时进行多少任务 |
| `assign_obbs` | 分配概率 |
| `offer_threshold` | 触发任务邀请的门槛 |
| `assign_cooldown` | 接任务冷却 |
| `reject_cooldown_steps` | 拒绝后的冷却步数 |

单个任务常见字段：

| 字段 | 含义 |
| --- | --- |
| `title` | 任务标题 |
| `tier` | 任务等级 |
| `repeatable` | 是否可重复 |
| `assign` | 分配条件 |
| `items` | 起始/奖励/保护类道具 |
| `reward` | 金钱、经验等奖励 |
| `npc` | 召唤 NPC 类型和子编号 |
| `steps` | 任务步骤文案或阶段 |

不同任务还会有自己的专用字段，例如目标地点、HP 阈值、需要糖果数量、坚持回合数等。这些字段不是通用魔法，必须有逻辑读取它。

## `questitem_1.php`

任务道具通常使用 `itmk = YQ`，并在 `itmpara` 写任务参数。

例子：

```php
'itmpara' => '{"IsQuestItem":1,"QuestID":"Q1","QuestAction":"summon"}'
```

`item.quest.php` 会根据 `QuestID` 和 `QuestAction` 决定使用道具时发生什么。

新增任务道具时至少要确认：

- `QuestID` 对应 `questcfg_1.php`。
- `QuestAction` 在 `item.quest.php` 里有处理。
- 使用后是否消耗、是否生成 NPC、是否完成任务。

## `addnpc_quest_1.php`

任务 NPC 是动态 NPC 的一类。任务逻辑会调用 `addnpc()`，并给 NPC 写入一些 `clbpara` 标记，例如：

- `quest_id`
- `linked_player_id`
- `quest_purpose`

这样战斗或死亡时才能知道这个 NPC 属于谁的任务。

## 新增任务的现实路线

最简单路线：复用已有任务模式。

例如已有任务支持“给玩家一个道具，使用后召唤 NPC，击败后完成”。你可以：

1. 在 `questcfg_1.php` 复制一个类似任务。
2. 在 `questitem_1.php` 复制一个类似道具。
3. 在 `addnpc_quest_1.php` 复制一个类似 NPC。
4. 确保 `QuestAction` 使用已有分支。

中等路线：新增一个 `QuestAction`。

需要改 `item.quest.php`，让新任务道具有新使用效果。

高级路线：新增任务事件钩子。

需要改 `quest.func.php` 里的战斗、搜索、移动、死亡等 hook，例如：

```text
quest_tick()
quest_combat_prepare_events()
quest_attack_result_events()
quest_handle_npc_death()
```

## 剧情对话

配置：

```text
gamedata/cache/dialogue_1.php
templates/default/dialogue.htm
```

触发方式通常是给玩家 `clbpara` 写：

```php
$clbpara['dialogue'] = 'dialogue_id';
```

如果不允许跳过：

```php
$clbpara['noskip_dialogue'] = 1;
```

配置数组：

| 变量 | 用途 |
| --- | --- |
| `$dialogues` | 对话正文，按 id 和页码组织 |
| `$dialogue_icon` | 立绘/头像 |
| `$dialogue_log` | 对话结束或选择后的日志 |
| `$dialogue_branch` | 选项 |
| `$dialogue_ending` | 对话结束标记或结局相关信息 |

玩家选择分支后，`command.php` 会把选择记录到：

```php
$clbpara['dialogue_choice'][$dialogue_id] = $choice;
```

注意：选择本身主要是记录和日志，不会自动执行复杂分支效果。要让选择改变属性、发道具、开隐藏地点或触发结局，需要写逻辑读取这个选择。

## 开场和结局故事

模板：

```text
templates/default/opening.htm
templates/default/opening_story.htm
templates/default/ending.htm
templates/default/ending_story.htm
templates/default/ruleset_opening_story.htm
templates/default/ruleset_ending_story.htm
```

普通结局故事多在模板里按 `winmode`、玩家状态或胜者判断。RuleSet 结局故事主要看：

```text
gamedata/ruleset/story_config.php
```

里面有两套并存的结构：

- `$ruleset_stories`：较简单的标题/正文/按钮式故事。
- `$ruleset_story_pages`：分页面故事板，供 opening/ending story 模板使用。

新增 RuleSet 剧情时要确认你改的是模板实际读取的那一套。

## 结局系统

核心函数：

```text
include/system.func.php 的 gameover()
```

结局显示名：

```text
resources_1.php 的 $gwin
```

常见默认结局：

| 编号 | 含义 |
| --- | --- |
| `1` | 全灭 |
| `2` | 最后幸存 |
| `3` | 解除禁区 |
| `4` | 无玩家 |
| `5` | 核爆 |
| `6` | 管理员停止 |
| `7` | 离脱/特殊结局 |

一些结局由特殊道具触发：

```text
include/game/item.ending.php
include/game/item.tool.php
```

例如“游戏解除钥匙”会触发类似 `end3` 的流程。

新增结局通常要做：

1. 在 `$gwin` 添加结局名。
2. 找到触发点，调用 `gameover($time, 'endN', $winner)` 或等价逻辑。
3. 在新闻、模板或故事配置里添加展示。
4. 如果有成就，补 `achievement_1.php` 和触发逻辑。
5. 如果 RuleSet 有专属结局故事，补 `story_config.php` 的对应 key。

只在 `$gwin` 加名字不会产生新结局。

## 隐藏地点

隐藏地点配置在：

```text
resources_1.php 的 $hplsinfo
players.pgroup
players.pls
```

标准地点是 `$plsinfo`，隐藏地点是 `$hplsinfo`。隐藏地点通常按 `pgroup` 分组。

当前移动规则大意：

- 玩家在标准地点时，只显示标准地图移动。
- 玩家已经在某个隐藏地点且有 `pgroup` 时，只能在同组隐藏地点间移动。
- 普通移动不会自动进入隐藏地点。

所以“新增隐藏地点”至少包括两件事：

1. 在 `$hplsinfo` 添加隐藏地点编号和分组。
2. 做一个入口逻辑，把玩家的 `pls` 设置到隐藏地点，并设置合适的 `pgroup`。

入口可以来自：

- 特殊道具。
- NPC 事件。
- 剧情选择。
- 社团技能。
- 任务完成。
- RuleSet 专用逻辑。

如果只加 `$hplsinfo`，玩家一般进不去。

隐藏地图还有一个源码注释提到的问题：隐藏地图目前不生成声音/音效相关信息。这一点已记录在异常观察文件。

## RuleSet 是什么

RuleSet 是一套房间级规则/资源覆盖。配置入口：

```text
gamedata/ruleset/ruleset_config.php
include/ruleset_override.func.php
```

资源目录形态：

```text
gamedata/ruleset/<规则集ID>/
  cache/
    resources_1.php
    gamecfg_1.php
    combatcfg_1.php
    clubskills_1.php
    npc_1.php
    ...
  img/
  include/
```

大多数通过 `config()` 读取的资源会优先走：

```text
gamedata/ruleset/<规则集ID>/cache/<资源名>_1.php
```

如果规则集没有该文件，会回退默认 `gamedata/cache/`。

## RuleSet 能覆盖什么

较稳的覆盖：

- 地点、道具、社团名、天气名。
- gamecfg 参数。
- combatcfg 参数。
- NPC、商店、合成、称号、成就、剧情资源。
- 模板里读取的 RuleSet story。

不一定通用的覆盖：

- 自定义 PHP 函数覆盖。
- 新的核心流程 hook。
- 新的资源文件加载规则。

当前 `ruleset_override.func.php` 对自定义函数加载并不是完全通用的模块系统，明显只对 `ACDTS_298SP4_AR` 有专门加载路径。也就是说，新增 RuleSet 目录和配置后，资源覆盖比较可靠；想让它自动加载一套新 PHP 函数，需要检查或扩展 loader。

## 创建一个资源型 RuleSet 的步骤

1. 在 `gamedata/ruleset/ruleset_config.php` 添加规则集配置。
2. 新建目录 `gamedata/ruleset/<ID>/cache/`。
3. 复制需要覆盖的资源文件，例如 `resources_1.php`、`gamecfg_1.php`。
4. 只改必要字段，缺省文件让它回退默认资源。
5. 如果有剧情，补 `gamedata/ruleset/story_config.php`。
6. 新建房间并选择该 RuleSet 测试。

不要一开始就复制全部 `cache`。复制越多，后续和默认资源同步越困难。

## 特殊机制 NPC 怎么做

先判断特殊机制属于哪个时机。

| 机制 | 常见修改点 |
| --- | --- |
| 只是不一样的装备/属性/技能 | `npc_1.php` |
| 战斗前显示特殊提示 | `revbattle.func.php` 或战斗准备 hook |
| 攻击命中后触发 | `revcombat_extra.func.php`、`revattr.func.php` |
| 死亡后掉落/召唤/完成任务 | `state.func.php`、`quest.func.php`、NPC death hook |
| 被特定玩家任务绑定 | `quest.func.php` + `addnpc_quest_1.php` |
| 使用道具召唤 | `item.npc.php` 或 `item.quest.php` |
| RuleSet 专属随机化 | 规则集 override function |

推荐路线：

1. 先用 `npc_1.php` 做出普通 NPC。
2. 给它一个独特 `type`、`name` 或 `clbpara` 标记。
3. 在对应触发点判断这个标记。
4. 只在需要的函数里写最小逻辑。

不要用 NPC 名字做过于脆弱的唯一判断。如果未来会多语言或改名，最好用 `type`、`sub` 或 `clbpara` 标记。

## 哪些内容配置能做，哪些不能

| 想做的事 | 配置够不够 |
| --- | --- |
| 改一段剧情台词 | 够 |
| 加一条线性剧情对话 | 基本够 |
| 选择 A 给道具，选择 B 进隐藏地图 | 不够，要写逻辑 |
| 新增普通结局名 | 不够，还要触发和展示 |
| 新增隐藏地点 | 不够，还要入口 |
| 新增资源型 RuleSet | 基本够 |
| 新增 RuleSet 专用 PHP 规则 | 不一定，要扩展 loader |
| 新增任务但复用已有 QuestAction | 可能够 |
| 新增任务完成条件 | 不够，要写 hook |

