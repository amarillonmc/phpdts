<?php
if(!defined('IN_GAME')) exit('Access Denied');

/**
 * itmpara 字段的 tooltip 配置文件
 *
 * 本文件定义了 itmpara 字段中各个键值对应的 tooltip 显示内容
 *
 * 格式说明：
 * $itmpara_tooltip 数组的键为 itmpara 中的键名
 * 值为一个数组，包含以下元素：
 * - 'title'：显示的标题，如果为空则不显示标题
 * - 'format'：显示的格式，使用 {value} 作为占位符，会被实际值替换
 * - 'suffix'：值的后缀，如 '%'、'点' 等
 * - 'color'：显示的颜色，可选，默认为空 - 注意：由于目前对tooltip的设计，span元素实际上无法在tooltip中进行解析，因此设置了这个元素也只会显示解析前的span元素，除非完全重写逻辑，否则该元素可以认为是废弃的。
 * - 'condition'：条件函数，接收 $item_type 和 $value 两个参数，返回 true 表示显示，false 表示不显示
 *
 * 示例：
 * 'AddDamageRaw' => [
 *     'title' => '最终伤害增加',
 *     'format' => '{value}',
 *     'suffix' => '',
 *     'color' => 'red',
 *     'condition' => function($item_type, $value) {
 *         return $item_type == 'W' || $item_type == 'D';
 *     }
 * ]
 */

$itmpara_tooltip = [
    // 伤害相关
    'AddDamageRaw' => [
        'title' => '最终伤害增加',
        'format' => '{value}',
        'suffix' => '',
        //'color' => 'red',
        'condition' => function($item_type, $value) {
            return true;
        }
    ],
    'AddDamagePercentage' => [
        'title' => '最终伤害增加',
        'format' => '{value}',
        'suffix' => '%',
        //'color' => 'red',
        'condition' => function($item_type, $value) {
			return true;
        }
    ],
    'DecreaseDamageRaw' => [
        'title' => '最终伤害减少',
        'format' => '{value}',
        'suffix' => '',
        //'color' => 'blue',
        'condition' => function($item_type, $value) {
            return true;
        }
    ],
    'DecreaseDamagePercentage' => [
        'title' => '最终伤害减少',
        'format' => '{value}',
        'suffix' => '%',
        //'color' => 'blue',
        'condition' => function($item_type, $value) {
            return true;
        }
    ],

    // 战斗中属性增加
    'AddPlayerMhpInCombat' => [
        'title' => '战斗中生命上限增加',
        'format' => '{value}',
        'suffix' => '',
        //'color' => 'yellow',
        'condition' => function($item_type, $value) {
            return true;
        }
    ],
    'AddPlayerMspInCombat' => [
        'title' => '战斗中体力上限增加',
        'format' => '{value}',
        'suffix' => '',
        //'color' => 'yellow',
        'condition' => function($item_type, $value) {
            return true;
        }
    ],
    'AddPlayerMssInCombat' => [
        'title' => '战斗中灵力上限增加',
        'format' => '{value}',
        'suffix' => '',
        //'color' => 'yellow',
        'condition' => function($item_type, $value) {
            return true;
        }
    ],
    'AddPlayerAttInCombat' => [
        'title' => '战斗中攻击力增加',
        'format' => '{value}',
        'suffix' => '',
        //'color' => 'yellow',
        'condition' => function($item_type, $value) {
            return true;
        }
    ],
    'AddPlayerDefInCombat' => [
        'title' => '战斗中防御力增加',
        'format' => '{value}',
        'suffix' => '',
        //'color' => 'yellow',
        'condition' => function($item_type, $value) {
            return true;
        }
    ],
    'AddPlayerWpInCombat' => [
        'title' => '战斗中殴系熟练度增加',
        'format' => '{value}',
        'suffix' => '',
        //'color' => 'yellow',
        'condition' => function($item_type, $value) {
            return true;
        }
    ],
    'AddPlayerWkInCombat' => [
        'title' => '战斗中斩系熟练度增加',
        'format' => '{value}',
        'suffix' => '',
        //'color' => 'yellow',
        'condition' => function($item_type, $value) {
            return true;
        }
    ],
    'AddPlayerWgInCombat' => [
        'title' => '战斗中射系熟练度增加',
        'format' => '{value}',
        'suffix' => '',
        //'color' => 'yellow',
        'condition' => function($item_type, $value) {
            return true;
        }
    ],
    'AddPlayerWcInCombat' => [
        'title' => '战斗中投系熟练度增加',
        'format' => '{value}',
        'suffix' => '',
        //'color' => 'yellow',
        'condition' => function($item_type, $value) {
            return true;
        }
    ],
    'AddPlayerWdInCombat' => [
        'title' => '战斗中爆系熟练度增加',
        'format' => '{value}',
        'suffix' => '',
        //'color' => 'yellow',
        'condition' => function($item_type, $value) {
            return true;
        }
    ],
    'AddPlayerWfInCombat' => [
        'title' => '战斗中灵系熟练度增加',
        'format' => '{value}',
        'suffix' => '',
        //'color' => 'yellow',
        'condition' => function($item_type, $value) {
            return true;
        }
    ],
    'AddPlayerMoneyInCombat' => [
        'title' => '战斗中金钱增加',
        'format' => '{value}',
        'suffix' => '',
        //'color' => 'yellow',
        'condition' => function($item_type, $value) {
            return true;
        }
    ],
    'AddPlayerRageInCombat' => [
        'title' => '战斗中怒气增加',
        'format' => '{value}',
        'suffix' => '',
        //'color' => 'yellow',
        'condition' => function($item_type, $value) {
            return true;
        }
    ],

    // 搜索/移动中属性增加
    'AddPlayerMhpInSearchMove' => [
        'title' => '搜索/移动中生命上限增加',
        'format' => '{value}',
        'suffix' => '',
        //'color' => 'green',
        'condition' => function($item_type, $value) {
            return true;
        }
    ],
    'AddPlayerMspInSearchMove' => [
        'title' => '搜索/移动中体力上限增加',
        'format' => '{value}',
        'suffix' => '',
       // 'color' => 'green',
        'condition' => function($item_type, $value) {
            return true;
        }
    ],
    'AddPlayerMssInSearchMove' => [
        'title' => '搜索/移动中歌魂上限增加',
        'format' => '{value}',
        'suffix' => '',
        //'color' => 'green',
        'condition' => function($item_type, $value) {
            return true;
        }
    ],
    'AddPlayerAttInSearchMove' => [
        'title' => '搜索/移动中攻击力增加',
        'format' => '{value}',
        'suffix' => '',
        //'color' => 'green',
        'condition' => function($item_type, $value) {
            return true;
        }
    ],
    'AddPlayerDefInSearchMove' => [
        'title' => '搜索/移动中防御力增加',
        'format' => '{value}',
        'suffix' => '',
        //'color' => 'green',
        'condition' => function($item_type, $value) {
            return true;
        }
    ],
    'AddPlayerWpInSearchMove' => [
        'title' => '搜索/移动中殴系熟练度增加',
        'format' => '{value}',
        'suffix' => '',
        //'color' => 'green',
        'condition' => function($item_type, $value) {
            return true;
        }
    ],
    'AddPlayerWkInSearchMove' => [
        'title' => '搜索/移动中斩系熟练度增加',
        'format' => '{value}',
        'suffix' => '',
        //'color' => 'green',
        'condition' => function($item_type, $value) {
            return true;
        }
    ],
    'AddPlayerWgInSearchMove' => [
        'title' => '搜索/移动中射系熟练度增加',
        'format' => '{value}',
        'suffix' => '',
        //'color' => 'green',
        'condition' => function($item_type, $value) {
            return true;
        }
    ],
    'AddPlayerWcInSearchMove' => [
        'title' => '搜索/移动中投系熟练度增加',
        'format' => '{value}',
        'suffix' => '',
        //'color' => 'green',
        'condition' => function($item_type, $value) {
            return true;
        }
    ],
    'AddPlayerWdInSearchMove' => [
        'title' => '搜索/移动中爆系熟练度增加',
        'format' => '{value}',
        'suffix' => '',
       // 'color' => 'green',
        'condition' => function($item_type, $value) {
            return true;
        }
    ],
    'AddPlayerWfInSearchMove' => [
        'title' => '搜索/移动中灵系熟练度增加',
        'format' => '{value}',
        'suffix' => '',
       // 'color' => 'green',
        'condition' => function($item_type, $value) {
            return true;
        }
    ],
    'AddPlayerMoneyInSearchMove' => [
        'title' => '搜索/移动中金钱增加',
        'format' => '{value}',
        'suffix' => '',
       // 'color' => 'green',
        'condition' => function($item_type, $value) {
            return true;
        }
    ],
    'AddPlayerRageInSearchMove' => [
        'title' => '搜索/移动中怒气增加',
        'format' => '{value}',
        'suffix' => '',
      //  'color' => 'green',
        'condition' => function($item_type, $value) {
            return true;
        }
    ],

    // 平台物品相关
    'IsPlatformItem' => [
        'title' => '平台物品',
        'format' => '此物品可以变身为特定角色',
        'suffix' => '',
      //  'color' => 'purple',
        'condition' => function($item_type, $value) {
            return $value == 1;
        }
    ],
    'PlatformIsTimed' => [
        'title' => '限时变身',
        'format' => '变身效果会随着时间消失',
        'suffix' => '',
        //'color' => 'purple',
        'condition' => function($item_type, $value) {
            return $value == 1;
        }
    ],
    'PlatformChargeBaseValue' => [
        'title' => '变身持续时间',
        'format' => '{value}',
        'suffix' => '回合',
        //'color' => 'purple',
        'condition' => function($item_type, $value) {
            return true;
        }
    ],

    // 任务物品相关
    'IsQuestItem' => [
        'title' => '任务物品',
        'format' => '此物品与任务相关',
        'suffix' => '',
        //'color' => 'orange',
        'condition' => function($item_type, $value) {
            return $value == 1;
        }
    ],

    // 核武器相关
    'isNuclearWeapon' => [
        'title' => '群体攻击武器',
        'format' => '此武器会对战斗区域内的所有人造成伤害',
        'suffix' => '',
        'condition' => function($item_type, $value) {
            return $value == 1;
        }
    ],

    // lore 特殊处理，直接显示内容
    'lore' => [
        'title' => '',
        'format' => '{value}',
        'suffix' => '',
        'color' => 'lore',
        'condition' => function($item_type, $value) {
            return true;
        }
    ],
];
