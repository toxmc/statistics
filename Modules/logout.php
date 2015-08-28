<?php

/**
 * 退出
 * @author xmc
 */
namespace Statistics\Modules;

function logout($module, $interface, $date, $start_time, $offset, $count)
{
	$response = \Core\Response::getInstance()->response();
	$session = \Core\Session::getInstance($response);
	$session->delete();
	include ST_ROOT . '/Views/login.tpl.php';
}
