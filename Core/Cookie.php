<?php
/**
 * Cookie
 * @author xmc
 */

namespace Core;

class Cookie
{
	public static $path = '/';
	public static $domain = null;
	public static $secure = false;
	public static $httponly = false;
	private $response = false;
	private static $instance;

	public function __construct()
	{
	}
	
	public static function getInstance()
	{
		if (!self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	public function setResponse($response)
	{
		$this->response = $response;
	}
	
	public function get($key, $default = null)
	{
		if (! isset($_COOKIE[$key])) {
			return $default;
		} else {
			return $_COOKIE[$key];
		}
	}

	public function set($key, $value, $expire = 0)
	{
		if ($expire != 0) {
			$expire = time() + $expire;
		}
		$this->response->cookie($key, $value, $expire, self::$path, self::$domain, self::$secure, self::$httponly);
	}

	public function delete($key)
	{
		unset($_COOKIE[$key]);
		$this->set($key, null);
	}
}
