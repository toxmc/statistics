<?php
/**
 * @author walkor
 * @author xmc
 */

use Core\Session;

//打包
function encode($string)
{
	$packData = pack('N', strlen($string)).$string;
	return $packData;
}

/**
 * 批量请求
 * @param array $request_buffer_array ['ip:port'=>req_buf, 'ip:port'=>req_buf, ...]
 * @return multitype:unknown string
 */
function multiRequest($request_buffer_array)
{
	\Statistics\Lib\Cache::$lastSuccessIpArray = array();
	$client_array = $sock_to_ip = $ip_list = array();
	foreach ($request_buffer_array as $address => $buffer) {
		list ($ip, $port) = explode(':', $address);
		$ip_list[$ip] = $ip;
		$client = new swoole_client(SWOOLE_TCP | SWOOLE_KEEP, SWOOLE_SOCK_SYNC);
		$client->connect($ip, $port);
		if (! $client) {
			continue;
		}
		$client_array[$address] = $client;
		$client_array[$address]->send(encode($buffer));
		$sock_to_address[(int) $client->sock] = $address;
	}
	$read = $client_array;
	$write = $except = $read_buffer = array();
	$time_start = microtime(true);
	$timeout = 0.99;
	// 轮询处理数据
	while (count($read) > 0) {
		foreach ($read as $client) {
			$address = $sock_to_address[(int) $client->sock];
			$buf = $client->recv();
			if (! $buf) {
				unset($client_array[$address]);
				continue;
			}
			if (! isset($read_buffer[$address])) {
				$read_buffer[$address] = $buf;
			} else {
				$read_buffer[$address] .= $buf;
			}
			// 数据接收完毕
			if (($len = strlen($read_buffer[$address])) && $read_buffer[$address][$len - 1] === "\n") {
				unset($client_array[$address]);
			}
		}
		// 超时了
		if (microtime(true) - $time_start > $timeout) {
			break;
		}
		$read = $client_array;
	}
	
	foreach ($read_buffer as $address => $buf) {
		list ($ip, $port) = explode(':', $address);
		\Statistics\Lib\Cache::$lastSuccessIpArray[$ip] = $ip;
	}
	
	\Statistics\Lib\Cache::$lastFailedIpArray = array_diff($ip_list, \Statistics\Lib\Cache::$lastSuccessIpArray);
	
	ksort($read_buffer);
	
	return $read_buffer;
}

/**
 * 检查是否登录
 */
function check_auth()
{
	// 如果配置中管理员用户名密码为空则说明不用验证
	if (Config\Config::$adminName == '' && Config\Config::$adminPassword == '') {
		return true;
	}
	// 进入验证流程
	$response = \Core\Response::getInstance()->response();
	$session = \Core\Session::getInstance($response);
	$session->start();
	if (! isset($_SESSION['admin'])) {
		if (! isset($_POST['admin_name']) || ! isset($_POST['admin_password'])) {
			include ST_ROOT . '/Views/login.tpl.php';
			return _exit();
		} else {
			$admin_name = $_POST['admin_name'];
			$admin_password = $_POST['admin_password'];
			if ($admin_name != Config\Config::$adminName || $admin_password != Config\Config::$adminPassword) {
				$msg = "用户名或者密码不正确";
				include ST_ROOT . '/Views/login.tpl.php';
				return _exit();
			}
			$_SESSION['admin'] = $admin_name;
			$_GET['fn'] = 'main';
		}
	}
	$session->save();
	return true;
}

/**
 * 退出
 * @param string $str        	
 */
function _exit($str = '')
{
	echo($str);
	return false;
}
