<?php

/*************************************
 * 自动加载类
 *
 * @param string $class
 *
 * @author xmc
 *************************************/
function autoload($class)
{
    $filename = BASEDIR . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($filename)) {
        include $filename;
    } else {
        echo '文件' . $filename . '不存在' . PHP_EOL;
    }
}