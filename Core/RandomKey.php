<?php
/**
 * 随机密钥
 * @author xmc
 */
namespace Core;

class RandomKey
{

	static function getChineseCharacter()
	{
		$unidec = rand(19968, 24869);
		$unichr = '&#' . $unidec . ';';
		$zhcnchr = mb_convert_encoding($unichr, "UTF-8", "HTML-ENTITIES");
		return $zhcnchr;
	}

	/**
	 * 随机生成一个字符串
	 * @param $length
	 * @param $number
	 * @param $not_o0
	 * @return unknown_type
	 */
	static function string($length = 8, $number = true, $not_o0 = false)
	{
		$strings = 'ABCDEFGHIJKLOMNOPQRSTUVWXYZ'; // 字符池
		$numbers = '0123456789'; // 数字池
		if ($not_o0) {
			$strings = str_replace('O', '', $strings);
			$numbers = str_replace('0', '', $numbers);
		}
		$pattern = $strings . $number;
		$max = strlen($pattern) - 1;
		$key = '';
		for ($i = 0; $i < $length; $i ++) {
			$key .= $pattern{mt_rand(0, $max)}; // 生成php随机数
		}
		return $key;
	}

	/**
	 * 按ID计算散列
	 * @param $uid
	 * @param $base
	 * @return unknown_type
	 */
	static function idhash($uid, $base = 1000)
	{
		return intval($uid / $base);
	}

	/**
	 * 按UNIX时间戳产生随机数
	 * @param $rand_length
	 * @return string
	 */
	static function randtime($rand_length = 6)
	{
		list ($usec, $sec) = explode(" ", microtime());
		$min = intval('1' . str_repeat('0', $rand_length - 1));
		$max = intval(str_repeat('9', $rand_length));
		return substr($sec, - 5) . ((int) $usec * 100) . rand($min, $max);
	}

	/**
	 * 产生一个随机MD5字符的一部分
	 * @param $length
	 * @param $seed
	 * @return unknown_type
	 */
	static function randmd5($length = 8, $seed = null)
	{
		if (empty($seed))
			$seed = self::string(16);
		return substr(md5($seed . rand(111111, 999999)), 0, $length);
	}
}
