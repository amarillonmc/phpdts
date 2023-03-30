# 常磐大逃杀（PHP Battle Royale）

A PHP based game emulating the settings and gameplay of the original Battle Royale series, with a lot of original spin and an original story with tons of references to pop culture.

## 依赖

- PHP 7.4.30/5.6
- PDO (PHP Data Objects) extension
- composer（可选）

## 安装

```bash
git clone https://github.com/amarillonmc/phpdts.git
cd phpdts
composer install    #可选
```

之后打开 `http://domain/path_to_dts/install.php` 根据提示完成后续安装。

## 守护进程

```bash
bash ./bot/bot_enable.sh
```

可以使用 nohup，screen 等程序防止进程被结束。


## 开发注意

1. 使用`composer install`安装依赖
2. `./yii serve`命令在windows下会报错，[原因点我](https://github.com/yiisoft/yii-console/issues/175)，临时解决方案

    找到`vendor\yiisoft\yii-console\src\Command\Serve.php`文件，修改第138行中的`'PHP_CLI_SERVER_WORKERS=' . $workers .`部分，变成以下代码

    ```php
    passthru('"' . PHP_BINARY . '"' . " -S $address -t \"$documentRoot\" $router");
    ```
3. 修改`config/configuration.php`之后一定要运行`composer du`来重新生成`.merge-plan.php`
4. 目前只加入了`src`和`config`文件夹，demo文件见`C:\git\phpdts\src\Controller\HomeController.php`