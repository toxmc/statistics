<?php

/**
 * examples
 * @author xmc
 */

class User {
	public static function getInfo()
	{
		$res = array();
		$res = array('name'=>'xmc','password'=>'123456');
		return $res;
	}

	public static function addInfo()
	{
		$res = array();
		$res = array('name'=>'xmc','password'=>'123456');
		return $res;
	}

	public static function getErrCode()
	{
		$errcode = 10001;
		return $errcode;
	}

	public static function getErrMsg()
	{
		$errmsg = '添加用户失败';
		return $errmsg;
	}
}

include 'StatisticClient.php';

// 统计开始
StatisticClient::tick("User", 'addInfo');
// 统计的产生，接口调用是否成功、错误码、错误日志
$success = true; $code = 0; $msg = '';
// 假如有个User::getInfo方法要监控
$user_info = User::addInfo();
if(!$user_info){
	// 标记失败
	$success = false;
	// 获取错误码，假如getErrCode()获得
	$code = User::getErrCode();
	// 获取错误日志，假如getErrMsg()获得
	$msg = User::getErrMsg();
}
// 上报结果
$res = StatisticClient::report('User', 'addInfo', $success, $code, $msg);

echo "done over...\n";
var_dump($user_info,$res);