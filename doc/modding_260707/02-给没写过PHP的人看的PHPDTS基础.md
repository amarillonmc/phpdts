# 02 - 给没写过 PHP 的人看的 PHPDTS 基础

本章不是完整 PHP 教程，只讲你看 PHPDTS 源码时最常撞见的语法和项目习惯。

## PHP 文件从 `<?php` 开始

PHP 文件通常长这样：

```php
<?php

if (!defined('IN_GAME')) {
    exit('Access Denied');
}

function example_func($value)
{
    return $value + 1;
}
```

`<?php` 之前不要有空格、空行或 BOM。否则 PHP 可能已经向浏览器输出内容，后面再设置 Cookie 或跳转时就会出错。

## 变量都带 `$`

```php
$name = '示例玩家';
$money = 100;
$alive = true;
```

PHPDTS 里大量变量是全局变量，例如：

```php
global $db, $tablepre, $pdata, $log, $now;
```

看到 `global` 就表示函数内部要使用外面的同名变量。这个项目历史比较久，很多函数不是通过参数传完整上下文，而是直接读写全局变量。

## 数组使用 `array(...)`

项目里为了兼容旧 PHP 风格，资源配置大多使用传统数组：

```php
$config = array(
    'name' => '测试道具',
    'kind' => 'HH',
    'effect' => 100,
);
```

`=>` 左边是键，右边是值。读取时写：

```php
$name = $config['name'];
```

多层数组写：

```php
$npcinfo[90]['sub'][0]['name']
```

意思是：`$npcinfo` 里编号 90 的 NPC 类型，下面第 0 个子形态，取它的名字。

## 字符串拼接用 `.`

```php
$log .= '获得了道具 ' . $itm0 . '<br>';
```

`.=` 表示追加到原字符串后面。PHPDTS 里 `$log` 是游戏内提示文本，经常这样累加 HTML。

## `extract($data, EXTR_REFS)` 是什么

很多游戏函数会这样写：

```php
function itemuse($itmn, &$data = NULL)
{
    global $pdata;

    if (!isset($data)) {
        $data = &$pdata;
    }

    extract($data, EXTR_REFS);
}
```

`$data` 通常是一整份玩家数据，例如里面有 `hp`、`sp`、`itm1`、`wep`。`extract` 会把数组键变成变量：

```php
$data['hp']  -> $hp
$data['itm1'] -> $itm1
```

`EXTR_REFS` 表示引用。也就是说改 `$hp` 可能会同步改回 `$data['hp']`。这很方便，也很危险：变量名写错时不一定马上报错。

## 动态变量：`$$name`

物品栏有 `itm1` 到 `itm6`，装备有 `wep`、`arb` 等。代码经常用动态变量：

```php
$slot = 'itm' . $itmn;
${$slot} = '矿泉水';
```

如果 `$itmn = 3`，那么 `$slot = 'itm3'`，`${$slot}` 就等于 `$itm3`。

看到这种写法时，先把字符串拼出来，再想它到底访问哪个变量。

## 包含文件：`include_once` 和 `require`

```php
include_once GAME_ROOT . './include/game/item.weapon.php';
require config('resources', $gamecfg);
```

`include_once` 常用于按需载入某个功能文件。`require` 常用于必须存在的配置。`config('resources', $gamecfg)` 会返回实际资源路径；如果当前房间启用了 RuleSet，可能返回规则集目录下的资源，而不是默认资源。

## 模板不是普通 HTML

模板文件在 `templates/default/*.htm` 或 `templates/nouveau/*.htm`。它们会被 `include/template.func.php` 编译成 PHP。

常见语法：

```html
<span>{player_name}</span>

<!--{if $gamestate == 20}-->
<p>游戏进行中</p>
<!--{/if}-->

<!--{loop $items $item}-->
<li>{item['name']}</li>
<!--{/loop}-->

<!--{template header}-->
```

不要直接去改 `gamedata/templates/*.tpl.php`，那里是编译结果。

## 数据库查询风格

项目里大量代码这样查数据库：

```php
$result = $db->query("SELECT * FROM {$tablepre}players WHERE type = 0");

while ($row = $db->fetch_array($result)) {
    // 使用 $row
}
```

`$tablepre` 是当前房间表前缀。不要把表名写死成 `acbra3_players`，否则多房间或不同配置会坏。

对用户输入不要直接拼 SQL。项目已有 `gstrfilter()` 会过滤请求数据，但新增功能时仍应尽量把数字转成 `(int)`，字符串确认来源，必要时使用数据库类提供的转义方式。

## JSON 参数：`clbpara` 和 `itmpara`

两个最常见的扩展参数：

| 字段 | 常见用途 |
| --- | --- |
| `clbpara` | 玩家/NPC 身上的扩展状态，如技能参数、任务状态、剧情标记 |
| `itmpara` | 单个道具的扩展参数，如任务道具参数、特殊伤害参数、平台参数 |

读取时常见：

```php
$clbpara = get_clbpara($clbpara);
$itmpara = get_itmpara($itmpara);
```

资源里写 JSON 时用双引号：

```php
'{"QuestID":"Q1","QuestAction":"summon"}'
```

不要写成：

```php
'{QuestID:Q1}'
```

这不是合法 JSON。

## `$log` 是给玩家看的

在游戏逻辑里想提示玩家：

```php
$log .= '你使用了测试道具。<br>';
```

`$log` 可以包含 HTML，但不要把未过滤的玩家输入直接拼进去。需要显示名字、物品名时，优先参考同类代码怎么处理。

## 新手读代码的办法

遇到一个功能，不要从全项目乱翻。按这个顺序：

1. 先在模板里找按钮文字或命令值。
2. 去 `command.php` 找对应 `mode` / `command`。
3. 看它 `include_once` 了哪个文件、调用了哪个函数。
4. 找这个函数用到了哪些资源文件。
5. 找一个已有资源或已有物品照着改。

例如“商店购买”：

```text
templates/default/shop.htm
  -> command.php 的 mode=shop
  -> include/game/itemmain.func.php 或相关购买函数
  -> gamedata/cache/shopitem_1.php
```

例如“任务道具使用”：

```text
道具 itmk = YQ
  -> include/game/item.main.php 分发
  -> include/game/item.quest.php
  -> gamedata/cache/questcfg_1.php
  -> gamedata/cache/questitem_1.php
```

## 什么时候只改资源，什么时候必须写代码

只改资源通常够用：

- 新增一个普通恢复道具
- 新增地图掉落
- 新增商店商品
- 新增普通 NPC 或给 NPC 套已有技能
- 新增普通合成公式
- 改剧情文本或结局故事文本

必须写逻辑：

- 新道具有全新的点击效果
- 新技能有新的主动按钮或战斗结算
- 新 NPC 有独有战斗机制
- 新任务有新的完成条件
- 新成就需要监听一个以前没人记录的行为
- 新 RuleSet 要覆盖通用函数，而不是只换资源

