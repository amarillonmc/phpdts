# PHPDTS 开发指引

适用于本仓库。PHPDTS（常磐大逃杀）是长期迭代的 PHP 网页游戏，开发重点是玩法、内容和实际问题。沿用项目已有的实现方式，让维护者能顺着代码直接理解改动。

## 首要原则：不要过度设计（Do not over-engineer）

**完成当前需求所需的最小完整改动。不要把一次玩法开发或修复变成架构改造，也不要为了少改几行而漏掉必要行为。** 这条原则同样适用于策划、评审和实施方案。

- 先找现有的相近道具、NPC、技能、事件或结局，沿用其处理位置和调用方式。配置能表达的内容改配置；现有函数里几段条件判断能清楚表达的规则，就直接写。玩法特例、固定数值和局部 `if/elseif` 本身不是需要消除的问题。
- 默认采用现有函数、数组和状态字段。只有当前需求确实存在重复逻辑，或一个有明确职责的辅助函数能明显改善可读性时，才提取函数；不要预先设计通用框架、服务层、注册器、事件总线或插件协议。不要因“以后可能复用”新增一层转发。
- 新增持久化字段只保存不能可靠推导、确实需要跨请求保留的状态。优先考虑已有的 `clbpara`、`itmpara`、`gamevars`，明确归属、初始化和保存位置；不要在多处保存同一份进度，也不要把无限增长的历史塞进这些字段。
- 新表、新依赖、新钩子、通用状态机、版本协议、快照或恢复台账都需要具体理由：**本次哪条已确定的需求无法用现有机制合理完成？** 如果只能回答“更规范”“方便扩展”“未来可能需要”，先删掉这层设计。必要时可以增加，但只解决已知问题，不顺势推广到全系统。
- 修改范围围绕本次功能。不要顺手统一命名、格式化旧文件、迁移数据库驱动、改造全部战斗入口，或给无关历史分支补兼容层。发现旁支问题，简短说明即可。
- 简单实现仍须正确：服务端校验、实际的数据保存、防重复发奖，以及当前多人操作确实涉及的互斥不能省略。先核对现有保护范围，补足具体缺口；不要为每个理论异常配一套新基础设施。
- 尊重已确定的玩法和叙事。不要为方便实现擅自删机制、增加限制、改变胜负归属，或扩充原案未要求的进度、货币和资格系统。确有冲突时，说明冲突和最小可行调整；改变玩法或需求范围才需要作者取舍，普通实现细节自行处理。

风格参照是 `beginning`、`nachster` 分支的人工修改。例如：

- `beginning` 的 `6bd5d58`：在出错位置补回需要的 `global`；`d3e5903`：用现有 `clbpara` 记录福袋开启情况，并补对应资源。
- `nachster` 的 `32e0ab6`（鱼眼凸）：在道具处理处加入具体行为，同时补资源、说明和新闻。

可用 `git show <commit>` 查看；本地没有同名分支时查看 `origin/beginning`、`origin/nachster`。学习的是直接、局部、贴合玩法的修改尺度，不照搬旧缺陷，也不把已经拆分的当前代码搬回旧结构。

## 以当前代码为准

本指引依据当前 `nouveau` 分支整理。工作前先看 `git status --short`、当前分支及相关代码；保留工作区已有改动，跟踪实际调用链再决定改哪里。

- 当前主程序是过程式 PHP、自定义模板、MySQL 兼容数据库和浏览器 JavaScript；数据库封装在 `include/db_*.class.php`，实际驱动由部署配置选择。
- 当前没有 `composer.json`、`yii` 入口或完整 Yii 工程。README 中的 `composer install`、`yii serve`、`config/configuration.php`、`.merge-plan.php` 是历史说明；残留的 `autoload.php`、`rector.php` 不代表现在要走该流程。不要用 `composer dump-autoload` 刷新游戏配置或模板。
- `Dockerfile` 使用 PHP 8.1 FPM，Compose 使用 MariaDB；这不等于所有部署版本。普通改动保持 PHP 7.4 可用语法，不主动提高最低版本；兼容性结论须说明实际测试版本。
- `doc/modding_260707/` 可用于定位功能，`doc/nouveau_250609/`、`doc/etc/` 和旧提交可作背景；具体函数、参数、规则和路径仍要核对源码。
- `doc/20260915-024212-elevator-ruleset-design-review.md` 和 `doc/20260915-031129-four-ending-routes-design-review.md` 是设计评审稿。其中新增的表、版本、实例、统一结算等是建议，不能当作已实装能力、作者已定需求或必须先完成的工程前置条件。

## 从哪里找实现

| 任务 | 优先查看 |
| --- | --- |
| 请求初始化、房间与配置加载 | `include/common.inc.php`、`include/global.func.php`、`include/roommng.func.php` |
| 玩家页面、指令与保存 | `game.php`、`command.php`、`include/game.func.php` |
| 移动、搜索、遭遇 | `include/game/search.func.php`、`include/game/revbattle.func.php` |
| 当前战斗、属性、技能 | `include/game/revcombat*.php`、`include/game/revattr*.php`、`include/game/revclubskills*.php` |
| 道具使用、拾取与合成 | `include/game/` 下的 `item.main.php`、对应的 `item.*.php`、`itemmain.func.php`、`itemmix.func.php`；`item.func.php` 主要是兼容入口 |
| 任务、事件、死亡与结局 | `include/game/` 下的 `quest.func.php`、`event.func.php`、`revevent.func.php`、`item.ending.php`；另见 `include/state.func.php`、`include/system.func.php`、`end.php` |
| 资源与规则集 | `gamedata/cache/`、`gamedata/`、`gamedata/ruleset/`、`include/resources.func.php`、`include/ruleset_override.func.php` |
| 页面与交互 | `templates/default/`、`templates/nouveau/`、`include/game20130526.js`、`include/common.js`、`include/dialogue.js`；以模板实际加载为准 |
| 数据库结构 | `gamedata/sql/`、`install/bra.sql` 及相关结构更新函数 |
| BOT | `bot/revbotservice.php`、`bot/revbot.func.php`；远程宿主另见 `bothost/README.md` |

同名旧模块和 `rev*` 模块并存，不能仅凭文件名认定入口。新增 NPC、技能、地图或结局编号前，搜索资源定义和引用，避免覆盖已有编号。

## 配置和 RuleSet

- **`gamedata/cache/` 中有受版本控制的正式配置与资源，不是可随意清空的生成目录。** 某些 `.php` 实际是带访问保护头的逗号分隔资源，由 `openfile()` 等读取；按原有列顺序和格式编辑。
- `config($file, $cfg)` 在 `include/global.func.php` 中：先找当前 RuleSet 的 `cache/{$file}_{$cfg}.php`，再找该 RuleSet 的版本 1，最后回退公共 `gamedata/cache/` 的对应版本或版本 1。没有自动继承另一个 RuleSet 的机制。
- 任务等非 `cache` 资源另走 `include/resources.func.php` 的 `get_ruleset_plain_resource_file()` 等读取函数。根目录资源、公共 cache 和 RuleSet 下可能有同名文件，先确认本次实际加载哪份，不批量同步所有副本。
- RuleSet 注册在 `gamedata/ruleset/ruleset_config.php`；模式逻辑优先从该模式的 `include/ruleset.func.php` 加载，兼容旧 `include/ruleset_functions.php`。沿用现有钩子，遵守参数、返回值和调用时机；只有缺少必要接点时才补公共钩子。
- 模式专属行为放在对应 RuleSet；修改公共代码时保留未启用该模式的行为。大房间 `groomid=0` 也可以有 RuleSet，不能用“非零房间”代替规则集判断。
- `include` 会继承调用处的变量作用域。配置若从函数中载入，要检查声明和使用位置，避免临时变量覆盖规则集注册表或读取不到配置。

## 写代码时保留的约定

- 按附近代码的缩进、命名和 `array()` / `Array()` / `[]` 风格修改，不进行无关替换。新逻辑写清楚即可，不要求给每行补类型、注释或中英双语；注释解释规则缘由和不明显的限制。
- 被包含的逻辑文件保留 `IN_GAME` 访问保护，后台模块沿用 `IN_ADMIN`。Web 入口通常先定义 `CURSCRIPT` 再加载 `common.inc.php`；不要机械地给所有入口、资源或 CLI 测试加同一个文件头。有 namespace 的文件须保留合法声明顺序。新增 PHP 逻辑使用 `<?php`。
- 复用 `GAME_ROOT`、`config()`、`template()` 和现有数据库封装，不硬编码机器路径、部署前缀或私有房间表名。`$tablepre` 是当前房间前缀，`$gtablepre` 是公共前缀。
- 许多函数通过 `global`、引用参数、`extract($data, EXTR_REFS)` 和动态物品槽名工作。改状态前确认操作的是当前玩家、其他玩家还是 NPC，以及调用方何时保存；别把引用改成副本，也别让稍后的旧数组覆盖刚写入数据库的结果。
- 玩家状态通常经 `player_save()` 保存；房间状态经 `save_gameinfo()` 保存。`clbpara`、物品 `para` 有 JSON 与数组两种形态，沿用 `get_clbpara()`、`get_itmpara()` 和现有格式化流程。保存前要核对数据所有者，不能只改页面变量。
- 物品处理须保留名称、种类、效果、数量／耐久、属性和 `para`；注意 `itm0` 是待拾取物品，`itm1`—`itm6` 是背包槽，`$nosta` 表示无限耐久。沿用拾取、叠加、掉落和清空函数，避免漏字段或丢失模式标记。
- 涉及时间时沿用调用链的 `$now` 等时间基准；`$now` 含部署时差，不能随意与 `time()` 混用。修改共享进度前核对 `process.lock`、RuleSet 请求钩子和 BOT 的实际锁范围；初始化阶段上过锁不表示整条指令都受保护。
- 玩家输入须做类型、范围、权限及当前状态校验。`gstrfilter()` 是旧输入过滤，不保证所有 SQL／HTML 上下文安全；SQL 按实际驱动处理，输出按上下文转义，不直接拼接未经校验的输入。不要输出或提交真实密码、密钥、会话数据。
- 游戏反馈沿用 `$log`、新闻和现有错误处理。不要将调试 `echo` 混入 `command.php` 的 JSON 响应，也不要把内部状态键或工程术语当作玩家提示。

## 模板与浏览器交互

模板源文件是 `templates/*/*.htm`，语法包括 `{$name}`、`<!--{if ...}-->`、`<!--{loop ...}-->`、`{template ...}`。由 `template()` 和 `include/template.func.php` 编译至 `gamedata/templates/`；修改源模板，不手改编译产物。

`nouveau` 缺失的模板会回退到 `default`。公共界面变更检查两套模板实际受影响的页面，不能假设都要复制一份，也不能只验证默认界面。修改指令或对话时，同时看表单的 `mode`、`command`、AJAX 提交和局部刷新，确保从物品发现、尸体等相关临时页面也能完成操作。

模板更新不生效时检查实际模板选择、`$tplrefresh` 和编译缓存；按已有模板清理入口处理，别删除正式资源目录。纯前端按钮禁用不能替代服务端规则校验。

## 运行与验证

以下命令从仓库根目录执行。先用 `php -v` 确认本机运行时。仓库保留部分短标签，运行和检查这些文件时需启用 `short_open_tag`。

```sh
# 本地开发服务器；还需要可用的数据库与站点配置
php -d short_open_tag=1 -S localhost:8080 -t .

# 只检查本次涉及的 PHP 文件，按需替换路径
php -d short_open_tag=1 -l include/game/item.main.php
```

新环境通过 `install.php` 的实际安装流程配置；它使用 `install/bra.sql`。`config.inc.php.sample` 仅作参考，其中旧 `mysql` 驱动配置不能直接用于 PHP 7.4/8.x，应选已提供且本机扩展可用的驱动。不要把任意 SQL 快照导入当作完整安装，也不要为了验证普通改动而重装、重置已有站点。`config.inc.php`、`gamedata/system.php`、`gamedata/gameinfo.php` 等可能带有部署或运行状态，即使被 Git 跟踪，也不应作为测试副产物提交。

BOT 在已配置的开发环境中可直接运行 `php -d short_open_tag=1 bot/revbotservice.php`。`bot/bot_enable.sh` 内有 `cd ..`，使用它时工作目录须是 `bot/`；不为普通改动默认启动常驻 BOT。

当前没有统一测试框架，但有以下独立回归脚本。它们需先定义 `IN_GAME`，使用桩函数／假数据库，不能替代真实站点联调。按改动范围选择运行：

```sh
php -d short_open_tag=1 -r "define('IN_GAME', true); require 'gamedata/tests/quest_q6_test.php';"
php -d short_open_tag=1 -r "define('IN_GAME', true); require 'gamedata/ruleset/LAIKAADVENT/tests/laika_ruleset_test.php';"
php -d short_open_tag=1 -r "define('IN_GAME', true); require 'gamedata/ruleset/RAIDCAGEDBIRD/tests/raid_ruleset_test.php';"
```

功能改动至少检查直接相关的正常操作和失败分支。涉及奖励、进度或共享状态时，检查重复提交及重新加载后的结果；涉及 UI 时验证实际提交与刷新。只有存在相关交互才扩大到其他模式，不要求每次改文案都遍历全游戏。需要新增回归用例时优先补现有脚本，验证真实行为，不搭新框架，也不靠搜索源码字符串代替行为验证。

不能连接数据库或打开站点时，说明已完成的语法／脚本检查和未验证部分；不要把 `php -l` 通过说成玩法验证通过。`doc/etc/debug_archive/` 是历史调试资料，不是可批量执行的测试套件。提交前检查 `git diff --check` 和完整 diff，确认没有运行数据、缓存或无关变动。

## 文档和交付

- 方案先写玩家会经历什么，再写对应的现有实现位置和必要改动。区分“源码现状”“已确定需求”“待确认建议”；不要用详尽的字段表、风险清单和分阶段工程计划代替解决玩法问题。
- 对未确定的细节给出最小建议，并说明影响；额外工程能力不能未经确认就升级为首版要求。不要把“将来支持所有模式、任意扩展、完整恢复”当作本次完成条件。
- 玩法、逻辑或数据格式的改动，在 `doc/YYYYMMDD-HHMMSS-change-description.txt` 留一份简短记录：改了什么、为什么、如何验证、已知限制；中文为主，可附简短英文摘要。纯文档修订或小勘误不必再生成一份重复日志。
- 最终说明实际改动与验证结果。文档中的建议不等于实装，脚本通过不等于上线验证；记录保持与本次工作规模相称。
