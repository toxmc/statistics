<?php

/**
 * 文件缓存类，提供类似memcache的接口
 * 警告：此类仅用于测试，不作为生产环境的代码，请使用Key-Value缓存系列！
 * @author xmc
 * @subpackage cache
 */

namespace Core\Cache;

class FileCache
{

	protected $config;

	/**
	 * 初始化
	 * @param unknown $config
	 */
	function __construct($config = array())
	{
		if (! isset($config['cache_dir'])) {
			$config['cache_dir'] = BASEDIR.'/data/cache/filecache';
		}
		if (! is_dir($config['cache_dir'])) {
			mkdir($config['cache_dir'], 0777, true);
		}
		$this->config = $config;
	}

	/**
	 * 获取文件名
	 * @param unknown $key
	 * @return string
	 */
	protected function getFileName($key)
	{
		$file = $this->config['cache_dir'] . '/' . trim(str_replace('_', '/', $key), '/');
		$dir = dirname($file);
		if (! is_dir($dir)) {
			mkdir($dir, 0755, true);
		}
		return $file;
	}

	/**
	 * 设置缓存
	 * @param unknown $key
	 * @param unknown $value
	 * @param number $timeout
	 * @return number
	 */
	function set($key, $value, $timeout = 0)
	{
		$file = $this->getFileName($key);
		$data["value"] = $value;
		$data["timeout"] = $timeout;
		$data["mktime"] = time();
		$res = file_put_contents($file, serialize($data));
		return $res;
	}

	/**
	 * 获取缓存数据
	 * @param unknown $key
	 * @return boolean|mixed
	 */
	function get($key)
	{
		$file = $this->getFileName($key);
		if (! is_file($file))
			return false;
		$data = unserialize(file_get_contents($file));
		if (empty($data) or ! isset($data['timeout']) or ! isset($data["value"])) {
			return false;
		}
		// 已过期
		if ($data["timeout"] != 0 and ($data["mktime"] + $data["timeout"]) < time()) {
			$this->delete($key);
			return false;
		}
		return $data['value'];
	}

	/**
	 * 删除
	 * @param unknown $key
	 * @return boolean
	 */
	function delete($key)
	{
		$file = $this->getFileName($key);
		return unlink($file);
	}
}