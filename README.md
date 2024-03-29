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
2. 可以使用`./yii serve`来启动开发版服务器
3. 修改`config/configuration.php`之后一定要运行`composer du`来重新生成`.merge-plan.php`
4. 目前只加入了`src`和`config`文件夹，demo文件见`C:\git\phpdts\src\Controller\HomeController.php`
5. 可以使用[Yii Dev Panel](https://yiisoft.github.io/yii-dev-panel)来调试
6. 数据库配置请修改 `config\common\params.php`，默认使用`Yiisoft\Db\Mysql\ConnectionPDO`
7. 目前只用了`yii serve -t .`， `-t`参数是必要的，用来指定docroot，不然访问不到静态文件，使用php原生自带的Routing file功能做路由，nginx和`.htaccess`在做了在做了