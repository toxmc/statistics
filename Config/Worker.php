<?php

/**
 * config配置文件
 * @author xmc
 */
namespace Config;

class Worker
{
	/**
	 * 获取Worker类初始化配置
	 * @return multitype:number string
	 */
	public static function getInitData()
	{
		$config = array(
			'max_log_buffer_size' => 1024000,	//最大日志buffer，大于这个值就写磁盘
			'write_period_length' => 50000,	//多长时间写一次数据到磁盘(单位:毫秒)
			'clear_period_length' => 100000,	//多长时间清理一次老的磁盘数据(单位:毫秒)
			'expired_time'			 => 31536000,	//数据多长时间过期,过期删除统计数据(单位:毫秒)
			'logBufferKey'			 => 'logBuffer',	//日志的redis buffer key
			'statisticDir'			 => 'statistic/statistic/',	//存放统计数据的目录
			'logDir'					 => 'statistic/log/',	//存放统计日志的目录
			'statisticDataKey'	 => 'statisticData', //redis统计数据 key
		);
		return $config;
	}
	
	static public $masterPidPath = '/pid/master.pid'; //worker master pid path
	static public $webPidPath = '/pid/web.pid'; //web master pid path
}