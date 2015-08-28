<?php
/**
 * Response 响应
 * @author xmc
 */

namespace Core;

class Response
{
	private $response;
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
	
	public function response()
	{
		return $this->response;
	}
	
	public function setResponse($response)
	{
		if (!self::$instance) {
			self::$instance = new self();
		}
		$this->response = $response;
		return true;
	}
}