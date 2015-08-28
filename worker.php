<?php
/**
 * 启动 php worker.php
 * 收集并且处理统计相关数据
 * @author xmc
 */
date_default_timezone_set("asia/shanghai");
include __DIR__.'/Bootstrap/Worker.php';
include __DIR__.'/Bootstrap/Autoload.php';

use Bootstrap\Worker;

define('BASEDIR',__DIR__);
spl_autoload_register('autoload');

$worker = new Worker();

$worker->run("0.0.0.0", 55656);