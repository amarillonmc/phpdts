# PHPDTS 升级 PHP 8.6 与保留 7.4 兼容性审计

2026-11-22 UTC，`nouveau`，提交 `96433f2e55930aef79be7ed8b84a8939225c3905`。本记录合并第一次抽样和第二次全仓检查。业务代码、配置、模板和数据库均未改动。

## 维护结论

建议以 **PHP 8.6.x 为推荐部署目标，保留 PHP 7.4 源码兼容**。官方于 2026-11-19 发布 8.6.0，常规维护至 2028 年底，安全维护至 2030 年底。仓库 Dockerfile 仍用 8.1 FPM，8.1 和 7.4 的官方维护分别已于 2025-12-31 和 2022-11-28 结束。后台物品参数编辑器已经使用 `json_last_error_msg()`（`include/admin/resourcemng.php:316`），同一错误输入 `[1,]` 在 7.4 只提示语法错误，8.6 可定位到第 1 行第 4 列。本次没有测站点吞吐、响应时间或 BOT 回合耗时。[PHP 发布说明](https://www.php.net/releases/8.6/en.php) · [支持周期](https://www.php.net/supported-versions.php) · [停止支持的分支](https://www.php.net/eol.php)

升级影响已超出第一次抽样发现的骰子和数据库错误处理。普通装备改造、元素合成、查看尸体、旧账号进场等都有具体失败条件。主要工作是把既有规则写明确，再验证一次指令完成后的物品、账号和房间状态。下列修复都可以用 7.4 语法完成，没有发现必须改表、重写模板引擎、迁移数据库驱动或分两条源码分支的前提。

## 检查范围与结果

| 检查 | PHP 7.4.33 | PHP 8.6.0 |
| --- | --- | --- |
| 全部 651 个跟踪的 `.php`，另加 `config.inc.php.sample`、`include/game/item.func.old`，逐文件开短标签 lint | 653/653 通过。旧 JSON 库仍有弃用提示 | 652/653 通过。旧 JSON 库语法失败，另 8 个文件通过但有编译期弃用提示 |
| 272 个模板输入，经项目原编译器生成 PHP，再 lint | 270/272 | 270/272，同样两份失败。272 个编译产物逐文件相同 |
| 48 份现行 `*_test.php` 中的 45 个独立主入口，含一份 UI 页面生成器 | 38 个正常退出，7 个失败 | 33 个正常退出，12 个失败 |
| 公共规则与注册表的 11 个 RuleSet，使用内存房间查询桩 | 1,380 次配置选择、60 次资源读取 | 路径和解析结果逐项参与的摘要相同，两个版本均无诊断 |
| 4 个 Node 界面测试 | 4/4 通过 | 4/4 通过。其中账号奖励用例实际调用各自的 PHP 生成页面，另外三项使用模拟 DOM |

清单取自 `git ls-files` 的 2,888 个跟踪文件。PHP 检查包括根目录、后台、BOT、玩法、公用与模式配置、测试、备份及历史调试资料。额外用 PHP 词法分析器检查 653 个输入中的直接内建函数调用，再按官方迁移指南检索数值运算、宽松比较、空集合、文件句柄、数据库和弃用接口。文本资源的保护头、注释及历史模块均按实际加载方式判读。浏览器脚本、Python 远程宿主、命令行启动脚本和构建工具按与 PHP 的交界处核对。静态扫描不能枚举动态变量、运行中的数组内容或每一种装备组合。

共 284 个跟踪的 `.htm`。272 份模板包括三套主题顶层 250 份、主题内样例及备份 21 份、ANOTHER 独立模板 1 份。其余 10 份是目录占位页、2 份是直接读取的公告。另将历史 `.htm2` 在临时目录改后缀交给原编译器，两端都通过，产物相同。失败的是 `templates/default/fireseedpage.htm` 的动态槽表达式和 `templates/default/report.htm:3` 的内嵌 PHP 标签被再次展开。未找到直接调用这两份模板的位置。

资源检查对 56 类带版本号的 cache 配置遍历仓内版本，并用一个不存在的版本号检查回退路径。每个模式再调用现有合成、同步合成、覆盖合成读取函数，以及地图和商店的 `openfile()`。运行在 Windows x64 CLI，使用本机 7.4.33 与核过官方 SHA-256 的 8.6.0 便携包。两次审计中，8.4.23 仅作为中间对照，首轮 244 份源码 lint 和指南所列三项测试均已实跑，没有对 8.4 重做完整矩阵。

## 玩家与 BOT 能遇到的差异

以下为两版实测。除注明来自现成回归的情况外，使用仓库原函数或原代码块、内存玩家和必要的外围桩，随机分支用固定种子或可控掷骰。表中明确标注的构造输入用于验证边界，线上出现频率未知。许多变化来自 8.0 的数值及比较规则，在 8.6 上仍会触发。[PHP 8.0 不兼容变更](https://www.php.net/manual/en/migration80.incompatible.php)

| 场景与位置 | 7.4.33 → 8.6.0 的实际结果 | 建议改动 |
| --- | --- | --- |
| **D3 重掷装备**。`include/game/item.dice.php:37`。公共商店有 D3，地图的德古拉斗篷耐久为 `∞`（`shopitem_1.php:225`、`mapitem_1.php:184`，均在 `gamedata/cache/`） | 有限耐久两端完成。穿无限耐久护甲时，7.4 发出非数值警告后完成，8.6 在六个部位耐久相加时抛 `TypeError` | 总量和重分配分别保留无限耐久槽。D1000 在同文件第 444 行有同形求和，需补独立用例 |
| **天然呆四面的奖赏改武器**。`include/game/item.weapon_mod.php:161、179`。四面包合成配方和地图的 Vampire Killer 都是正式资源 | 构造社团 1，抽中增加耐久档。有限耐久 20 两端都变 21。无限耐久时，7.4 报警、将 `∞` 变成 1 并消费奖赏，8.6 在加算时抛 `TypeError`。发新闻调用在异常点之前 | 对所有社团先判无限耐久，照原规则处理命名和奖赏消费。联调时核对指令失败、重试后的新闻和库存 |
| **元素大师随机合成**。`command.php:825`、`include/game/elementmix.func.php:551、555`、`elementmix.calc.php:480、494、502` | 10 级、51 份昼闪闪、未命中固定配方，普通运势两端均产效果 31、耐久 26 的 WP。大成功时 7.4 带警告产效果 51、耐久 ∞ 的 WP，8.6 在生成日志的“效果＋耐久”处中断。防具的 `max(效果, ∞)` 也会改变结果；技能书的效耐函数还可能在 `∞ / 等级` 处中断 | 普通装备、需要数值上限的药物／技能书、日志显示分别处理。先保留各类别原有上限，再明确无限结果的显示文字，覆盖大成功、大失败与普通运势 |
| **查看尸体／轮椅追击**。`include/game/battle.func.php:34、87`、`depot.func.php:6、16` | 仓库配置已在另一作用域载入时，`findcorpse()` 内的 `include_once` 不再执行，局部拿不到全局的可搜仓库类型。7.4 报警后继续，8.6 把空值传入 `in_array()`，原生 `TypeError`。轮椅回归已设置合法全局配置，仍能复现 | 在使用处显式引用现有全局配置。覆盖首次查看和同一请求内再次查看 |
| **旧账号成就迁移**。`user_profile.php:90`、`valid.php:80`、`gamedata/cache/achievement_1.php:197` | 用空 `achrev`、停用的旧成就 #5、旧阶段 999、进度 3 输入两个原迁移代码块。7.4 报警后写出阶段 0、进度 3，8.6 在 `count(null)` 中断，两条路径均未写入新账本 | 两个入口一致处理停用编号，保留原进度并验证二次访问。实际有多少旧账号命中尚未知 |
| **生存名单的胜率**。`alive.php:33` | 同一行一胜、有效场数为零，7.4 报警并显示 `INF%`，8.6 抛 `DivisionByZeroError`。建号两值初始为零，独自逃脱成就有单独加胜场的代码，未检索真实账号 | 沿用 `user_profile.php:37` 对总场数的判断。用零场和正常场数各验一行 |
| **轮椅移动到 0 号地图**。`include/game/wheelchair_action.func.php:60`、`search.func.php:39、90`。LAIKA 的 `ruleset.func.php:367` 也有同类比较 | 测试解除禁区、当前位置为 1。整数 0 在 7.4 被 `== 'main'` 拒绝，8.6 允许。表单字符串 `'0'` 两端都允许。轮椅移动明确传入整数 | 对菜单标记 `main` 做严格比较，并保留地图编号、禁区及范围校验 |
| **BOT 给无限耐久防具使用针线包**。`bot/revbot.func.php:309–314`、`include/game/item.weapon_mod.php:120` | 有限耐久 20、防御 520 时两端均用掉一包并变成 526。穿德古拉斗篷时 7.4 跳过，8.6 用掉针线包并变成 526。另构造无限耐久且带 Z 的防具，道具拒绝使用，8.6 的 BOT 紧接着再次尝试，探针在两次后停止 | 明确无限耐久是否属于 BOT 使用条件。使用被拒绝且库存未变时退出本次循环 |
| **合成工具、无限弹叠与陷阱**。`include/game/itemmain.func.php:1265、1270`、`item.ammo.php:79、92`、`itemmain.func.php:137` | 构造带工具属性的无限耐久 WP 素材，7.4 合成后静默清空，8.6 保留。构造 1/12 弹匣及无限 GB 弹叠，7.4 报警、残弹仍为 1，8.6 填至 12，普通两发两端均装至 3。正式商店的效果 25 陷阱，同种子两端均造成 16 点伤害，仅 8.6 报 12.5 隐式转整数的弃用提示 | 分别按“工具可复用”“无限弹叠不消耗”处理。陷阱随机上限显式取整以保留现有伤害分布。两种构造的无限素材未确认有预制掉落 |
| **BOT 无安全地图**。`bot/revbot.func.php:106、211` | 构造逃避阶段、现金为零且安全地图为空。7.4 报警后返回空值，8.6 在 `array_rand([])` 抛 `ValueError`。未证实普通开局可自然到达此状态 | 候选为空时结束这次移动选择。由已有状态决定后续行动，不把 0 号地图当作默认退路 |

复现两个随机分支时，可以控制掷骰而不修改正式资源。四面奖赏用社团 1、效果 150、无多重／进化属性的 Vampire Killer、一次奖赏，固定首个 `rand(0,99)` 在 70–89。元素合成使用 10 级角色、元素 4 库存 100、投入 51，调用 `element_mix([4],[51],[0],100,55)` 并使其不命中固定配方。选种时先模拟提示语的 `array_rand(range(1,15))`，再取 `rand(1,100)`，以 1–5 作为大成功、51–95 作为普通对照，重设该种子后调用原函数。两组都需记录返回的物品和调用后保留的输入。

## 数据库、安装与文件失败路径

- **MySQLi 的静默查询约定。** `include/db_mysqli.class.php:44` 在查询返回后才检查 `SILENT`。自 8.1 起 MySQLi 默认会先抛异常。`include/activation.func.php:104` 创建奖励邮件后，若登记兑换失败，本应按返回值撤回邮件；安装器的探测查询也使用同一约定。此项根据源码与官方行为确认，尚未在真实 MySQL 上故意令第二步失败。建议驱动对 `SILENT` 恢复返回 `false`，保留普通查询的错误反馈，实测兑换、撤回、重试和解锁。未连接对象的析构另有双端实测，`db_mysqli.class.php:301、318` 在 7.4 警告后返回，8.6 对空句柄抛 `TypeError`，会干扰原始连接错误的报告。[MySQLi 官方错误模式](https://www.php.net/manual/en/mysqli-driver.report-mode.php)
- **PDO 部署的投稿配方审核。** `include/admin/vnmixlist.php:6` 调 `include/vnworld/vnmix.func.php:56`。项目两个驱动默认都取关联数组，调用者额外传了 `MYSQLI_ASSOC`。用项目函数、项目 PDO 的取行逻辑和含一行的 SQLite 内存结果集实测，仅启用 PDO 时，7.4 报警后列表为空，8.6 报常量未定义。同时启用 MySQLi 时，PDO 把该数值当作 `FETCH_LAZY`，7.4 留下错误的行对象，8.6 在移除字段时抛错。此处省略第二个参数即可使用各驱动的正确默认值，待用真实 PDO MySQL 和 MySQLi 各审一条配方。
- **资源不可读和安装日志不可写。** `include/global.func.php:269–270` 的 `readover()` 在文件打开失败后继续加锁。7.4 警告后走项目的读取失败提示，8.6 在 `flock(false)` 中断。`install.php:1178、1282` 的 `loginit()` 在临时日志目录不存在时，7.4 仍调用成功回执，8.6 在 `fwrite(false)` 中断。建议先检查打开结果，并向安装者报出失败。`writeover()` 的第 285–295 行也有相似调用顺序，写失败时文件状态尚未实测。
- **部署配置。** `Dockerfile:1、3` 使用 PHP 8.1 FPM 和 MySQLi。`config.inc.php.sample:68` 仍选两版均无原生扩展的 `mysql`，公共入口和安装器还按 PHP 4.3 检查下限，Windows BOT 样例亦带旧 PHP 路径。发布支持声明时同步样本、版本检查、镜像和 BOT 启动说明。以实际部署选用 MySQLi 或 PDO MySQL，分别核对 Web 和 CLI 的 `short_open_tag` 及所需扩展。

## 回归失败与弃用提示

48 份现行 PHP 测试中，`account_rewards_test.php`、`fcg_database_test.php` 需要真实 MySQL，本次跳过。`elevator_extensions_test.php` 已由 ELEVATOR 主测试加载。其余 45 个主入口在两份独立的同提交快照执行。另跑 8 组参数变体，不计入 45 的分母。

**仅 8.6 失败的五个入口，涉及四处代码。**

- `wheelchair_battle_test.php` 触发上文的尸体仓库配置错误。这一处即使没有把告警转成异常，也会抛出类型错误。
- `ai93_test.php` 在 `include/system.func.php:944` 首次累加同名 NPC 数量时读了未初始化的键。首次从零计数即可。
- `club25_items_test.php` 在 `include/game/itembag.func.php:192、194` 遍历背包时多读一格，空包会读取键 0。遍历实际条目，并验证空包和满包的堆叠。
- `club24_test.php` 和 `ending_battle_test.php` 都在 `include/game/revattr.func.php:189` 向缺失的 `logsave` 追加日志。先初始化需要写入的一方，再验证日志实际保存。以上三种缺键在 8.6 产生 Warning，严格测试处理器将其转成异常。线上是否中断取决于处理器和数据，不能按五起线上宕机计数。

**两版都失败的七个入口。** ANOTHER 的 `native_smoke_test.php` 临时环境缺 `club24.func.php`。FLASH 的 `flash_route_test.php` 停在商店操作参数。LAIKA 的 `laika_ruleset_test.php` 停在对话容器源码断言。`club25_command_test.php` 的六个指令场景均缺 `race_ai_resolve_active` 桩。`fcg_test.php:255` 停在自适应曲线 W 的断言。`club25_combat_test.php` 以及引用它的 `mianhuping_test.php` 在 7.4 先遇到测试数据缺 `nicksrev`，8.6 先遇到战斗日志缺 `logsave`。这些入口退出后的断言没有得到覆盖。补跑的职业 24 掷骰和职业 25 资源模板参数在两端均通过。

Q6、笼中鸟、FLASH 主规则（298 项断言）、结局战斗的 `ending_combat_test.php`（108 项）和 ELEVATOR（含扩展，共 4,487 项）两端均通过。需区分名称相近的 `ending_battle_test.php` 和 FLASH 路线测试。正常退出也可能伴随诊断，`ending_routes_isolation_test.php` 两端各有 21 条常量重复定义，`wheelchair_test.php` 两端各有 3 条引用 Notice，`talent_race_test.php:47` 的测试桩仅 8.6 报 4 条未显式传 CSV 转义参数的弃用提示。

**源码弃用项及历史文件。** 8.6 的 8 份有编译期弃用提示的文件中，`include/admin/configmng.php`、`gamecfgmng.php`、`systemmng.php` 使用旧式字符串变量插值；`include/game/elementmix.calc.php:214` 的必填参数在可选参数之后；`include/gameencrypt.php:14` 和 `install.php:1373` 的 `(double)` 从 8.5 起弃用，可写为 `(float)`。另外两份是旧 `include/db_mysql.class.php` 和旧 `include/game/combat.func.php`。宜先处理实际加载的位置，修改形参前核对调用者的实参顺序。[PHP 8.5 弃用项](https://www.php.net/manual/en/migration85.deprecated.php)

唯一 lint 失败的 `include/JSON.php:156` 使用花括号下标，其加载条件在 `include/global.func.php:313`，仅供 PHP 5.2 以前调用。独立调试页 `error.php:2–3` 在 7.4 警告后退出，8.6 因未定义常量中断；历史入口 `login_old.php:4` 所需的 `set_magic_quotes_runtime()` 在两端都不存在。未找到它们作为当前登录或游戏入口的引用。旧驱动专用分支中的 `mysql_*` 文本命中也没有按现代驱动的故障计数。

## 修复顺序、复验与边界

1. 优先处理可中断正常指令、影响资产或挡住旧账号的路径。先测尸体、无限耐久装备改造与元素合成、旧成就迁移和兑换失败撤回。每项同时覆盖有限／无限或新／旧数据，核对重新读取后的库存、账本和新闻。
2. 补移动和 BOT 的分支、背包遍历、战斗日志、后台审核及 I/O 错误反馈。恢复现有失败测试的基线后再跑完整矩阵，否则首个异常会遮住后续问题。没有必要为此统一重构战斗、数据库或所有物品处理函数。
3. 真实验收仍缺 FPM／IIS FastCGI 的请求、实际 MySQLi／PDO MySQL、全新安装和已有资料升级、浏览器 AJAX 与模板刷新，以及多请求／BOT 并发下的保存与发奖。尚未验证主机扩展配置、未跟踪的私有 RuleSet、每一种装备组合与对局状态，也没有生产性能数据。
4. 第一次抽样给出的 1–3 人日只适用于当时已知的几处修复。扩大范围后，工作包括多个独立玩法、账号入口、数据库失败分支以及恢复回归基线。可先按熟悉项目的维护者 **1–2 周**安排第一轮修复和测试站联调，前提是已有可用的测试站和代表性旧数据。完整发布排期需在失败用例走完、真实落库重试通过后再收敛。保留 7.4 的持续成本是共用语法并维护两端回归。

复验时先确认 CLI 版本，用 `git -c core.quotepath=false ls-files -- '*.php' 'config.inc.php.sample' 'include/game/item.func.old'` 清点文件，对每个路径运行 `php -n -d short_open_tag=1 -d error_reporting=-1 -l <路径>`。行为回归在临时快照中启用相应扩展，多数脚本先定义 `IN_GAME`，例如 `php -d short_open_tag=1 -r "define('IN_GAME', true); require 'gamedata/tests/wheelchair_battle_test.php';"`。按测试说明运行参数变体，单列数据库用例。历史 `doc/etc/debug_archive/` 脚本只做静态检查。复核新增的 8.6 规则时参考[不兼容变更](https://www.php.net/manual/en/migration86.incompatible.php)和[弃用项](https://www.php.net/manual/en/migration86.deprecated.php)。
