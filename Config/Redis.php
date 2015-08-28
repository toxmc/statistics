<?php
/**
 * redis 配置文件
 * @author xmc
 */

namespace Config;
class Redis
{
    public static function getConfig() {
    	$config = array(
    		'host' => '127.0.0.1',
    		'port' => '6379',
    	);
    	return $config;
    }
}

