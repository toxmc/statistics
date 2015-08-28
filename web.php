<?php
/**
 * 启动 php web.php
 * 统计页面显示
 * @author xmc
 */
date_default_timezone_set("asia/shanghai");
include __DIR__.'/Bootstrap/WebServer.php';
include __DIR__.'/Bootstrap/Autoload.php';

use Bootstrap\WebServer;

define('BASEDIR',__DIR__);
spl_autoload_register('autoload');

$web = new WebServer();

$web->run("0.0.0.0", 6666);
