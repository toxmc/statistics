<?php

/**
 * redis缓存类，提供类似memcache的接口
 *
 * @author     xmc
 * @subpackage cache
 */

namespace Core\Cache;

class RedisCache
{
    protected $redis;
    protected $config;

    /**
     * 初始化
     *
     * @param array $config
     */
    function __construct($config = array())
    {
        $this->config = $config;
    }

    /**
     * 获取redis实例
     *
     * @return \Redis
     */
    protected function getRedis()
    {
        if (empty($this->redis) || !$this->redis->info()) {
            $this->redis = new \Redis();
            if (empty($this->config)) {
                $this->config = \Config\Redis::getSessionConfig();
            }
            $res = $this->redis->connect($this->config['host'], $this->config['port']);
            if (isset($this->config['password'])) {
                $this->redis->auth($this->config['password']);
            }
            $this->redis->select($this->config['database']);
            if (empty($res)) {
                echo "connect Redis failed!\n";
            }
        }
        return $this->redis;
    }

    /**
     * 设置缓存
     *
     * @param string $key
     * @param mixed $value
     * @param int  $timeout
     *
     * @return number
     */
    function set($key, $value, $timeout = 0)
    {
        $data["value"] = $value;
        $data["timeout"] = $timeout;
        $data["mktime"] = time();
        $res = $this->getRedis()->set($key, json_encode($data));
        return $res;
    }

    /**
     * 获取缓存数据
     *
     * @param string $key
     *
     * @return boolean|mixed
     */
    function get($key)
    {
        $result = $this->getRedis()->get($key);
        $data = json_decode($result, true);

        if (empty($data) or !isset($data['timeout']) or !isset($data["value"])) {
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
     *
     * @param string $key
     *
     * @return boolean
     */
    function delete($key)
    {
        $result = $this->getRedis()->del($key);
        return $result;
    }
}