<?php

/**********************************
 * 服务提供者
 *
 * @author xmc
 *********************************/

namespace Bootstrap;

use \Config\Config;

class Provider extends Worker
{

    public static $instance;
    /**
     * 用于接收广播的udp socket
     *
     * @var resource
     */
    protected $broadcastSocket = null;

    public function __construct()
    {
    }

    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 处理请求统计
     *
     * @param \swoole\server $serv
     * @param int            $fd
     * @param int            $from_id
     * @param array          $req_data
     */
    public function message($serv, $fd, $from_id, $req_data)
    {
        $module = $req_data['module'];
        $interface = $req_data['interface'];
        $cmd = $req_data['cmd'];
        $start_time = isset($req_data['start_time']) ? $req_data['start_time'] : '';
        $end_time = isset($req_data['end_time']) ? $req_data['end_time'] : '';
        $date = isset($req_data['date']) ? $req_data['date'] : '';
        $code = isset($req_data['code']) ? $req_data['code'] : '';
        $msg = isset($req_data['msg']) ? $req_data['msg'] : '';
        $offset = isset($req_data['offset']) ? $req_data['offset'] : '';
        $count = isset($req_data['count']) ? $req_data['count'] : 10;
        switch ($cmd) {
            case 'get_statistic':
                $buffer = json_encode(array(
                        'modules'   => $this->getModules($module),
                        'statistic' => $this->getStatistic($date, $module, $interface)
                    )) . "\n";
                $serv->send($fd, $buffer);
                break;
            case 'get_log':
                $buffer = json_encode($this->getStasticLog($module, $interface, $start_time, $end_time, $code, $msg, $offset, $count)) . "\n";
                $serv->send($fd, $buffer);
                break;
            default:
                $serv->send($fd, 'pack err');
        }
    }

    /**
     * 获取模块
     *
     * @param string $current_module
     *
     * @return array
     */
    public function getModules($current_module = '')
    {
        $st_dir = Config::$dataPath . $this->statisticDir;
        $modules_name_array = array();
        foreach (glob($st_dir . "/*", GLOB_ONLYDIR) as $module_file) {
            $tmp = explode("/", $module_file);
            $module = end($tmp);
            $modules_name_array[$module] = array();
            if ($current_module == $module) {
                $st_dir = $st_dir . $current_module . '/';
                $all_interface = array();
                foreach (glob($st_dir . "*") as $file) {
                    if (is_dir($file)) {
                        continue;
                    }
                    list ($interface, $date) = explode(".", basename($file));
                    $all_interface[$interface] = $interface;
                }
                $modules_name_array[$module] = $all_interface;
            }
        }
        return $modules_name_array;
    }

    /**
     * 获得统计数据
     *
     * @param string $module
     * @param string $interface
     * @param int    $date
     *
     * @return bool/string
     */
    protected function getStatistic($date, $module, $interface)
    {
        if (empty($module) || empty($interface)) {
            return '';
        }
        // log文件
        $log_file = Config::$dataPath . $this->statisticDir . "{$module}/{$interface}.{$date}";

        $handle = @fopen($log_file, 'r');
        if (!$handle) {
            return '';
        }

        // 预处理统计数据，每5分钟一行
        // [time=>[ip=>['suc_count'=>xx, 'suc_cost_time'=>xx, 'fail_count'=>xx, 'fail_cost_time'=>xx, 'code_map'=>[code=>count, ..], ..], ..]
        $statistics_data = array();
        while (!feof($handle)) {
            $line = fgets($handle, 4096);
            if ($line) {
                $explode = explode("\t", $line);
                if (count($explode) < 7) {
                    continue;
                }
                list ($ip, $time, $suc_count, $suc_cost_time, $fail_count, $fail_cost_time, $code_map) = $explode;
                $time = ceil($time / 300) * 300;
                if (!isset($statistics_data[$time])) {
                    $statistics_data[$time] = array();
                }
                if (!isset($statistics_data[$time][$ip])) {
                    $statistics_data[$time][$ip] = array(
                        'suc_count'      => 0,
                        'suc_cost_time'  => 0,
                        'fail_count'     => 0,
                        'fail_cost_time' => 0,
                        'code_map'       => array()
                    );
                }
                $statistics_data[$time][$ip]['suc_count'] += $suc_count;
                $statistics_data[$time][$ip]['suc_cost_time'] += round($suc_cost_time, 5);
                $statistics_data[$time][$ip]['fail_count'] += $fail_count;
                $statistics_data[$time][$ip]['fail_cost_time'] += round($fail_cost_time, 5);
                $code_map = json_decode(trim($code_map), true);
                if ($code_map && is_array($code_map)) {
                    foreach ($code_map as $code => $count) {
                        if (!isset($statistics_data[$time][$ip]['code_map'][$code])) {
                            $statistics_data[$time][$ip]['code_map'][$code] = 0;
                        }
                        $statistics_data[$time][$ip]['code_map'][$code] += $count;
                    }
                }
            } // end if
        } // end while

        fclose($handle);
        ksort($statistics_data);

        // 整理数据
        $statistics_str = '';
        foreach ($statistics_data as $time => $items) {
            foreach ($items as $ip => $item) {
                $statistics_str .= "$ip\t$time\t{$item['suc_count']}\t{$item['suc_cost_time']}\t{$item['fail_count']}\t{$item['fail_cost_time']}\t"
                    . json_encode($item['code_map']) . "\n";
            }
        }
        return $statistics_str;
    }

    /**
     * 获取指定日志
     */
    protected function getStasticLog($module, $interface, $start_time = '', $end_time = '', $code = '', $msg = '', $offset = '', $count = 100)
    {
        // log文件
        $log_file = Config::$dataPath . $this->logDir . (empty($start_time) ? date('Y-m-d') : date('Y-m-d', $start_time));
        if (!is_readable($log_file)) {
            return array(
                'offset' => 0,
                'data'   => ''
            );
        }
        // 读文件
        $h = fopen($log_file, 'r');

        // 如果有时间，则进行二分查找，加速查询
        if ($start_time && $offset == 0 && ($file_size = filesize($log_file)) > 1024000) {
            $offset = $this->binarySearch(0, $file_size, $start_time - 1, $h);
            $offset = $offset < 100000 ? 0 : $offset - 100000;
        }

        // 正则表达式
        $pattern = "/^([\d: \-]+)\t\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\t";

        if ($module && $module != 'AllData') {
            $pattern .= $module . "::";
        } else {
            $pattern .= ".*::";
        }

        if ($interface && $module != 'AllData') {
            $pattern .= $interface . "\t";
        } else {
            $pattern .= ".*\t";
        }

        if ($code !== '') {
            $pattern .= "code:$code\t";
        } else {
            $pattern .= "code:\d+\t";
        }

        if ($msg) {
            $pattern .= "msg:$msg";
        }

        $pattern .= '/';

        // 指定偏移位置
        if ($offset > 0) {
            fseek($h, (int)$offset - 1);
        }

        // 查找符合条件的数据
        $now_count = 0;
        $log_buffer = '';

        while (1) {
            if (feof($h)) {
                break;
            }
            // 读1行
            $line = fgets($h);
            if (preg_match($pattern, $line, $match)) {
                // 判断时间是否符合要求
                $time = strtotime($match[1]);
                if ($start_time) {
                    if ($time < $start_time) {
                        continue;
                    }
                }
                if ($end_time) {
                    if ($time > $end_time) {
                        break;
                    }
                }
                // 收集符合条件的log
                $log_buffer .= $line;
                if (++$now_count >= $count) {
                    break;
                }
            }
        }
        // 记录偏移位置
        $offset = ftell($h);
        return array(
            'offset' => $offset,
            'data'   => $log_buffer
        );
    }

    /**
     * 日志二分查找法
     *
     * @param int $start_point
     * @param int $end_point
     * @param int $time
     * @param fd  $fd
     *
     * @return int
     */
    protected function binarySearch($start_point, $end_point, $time, $fd)
    {
        if ($end_point - $start_point < 65535) {
            return $start_point;
        }

        // 计算中点
        $mid_point = (int)(($end_point + $start_point) / 2);

        // 定位文件指针在中点
        fseek($fd, $mid_point - 1);

        // 读第一行
        $line = fgets($fd);
        if (feof($fd) || false === $line) {
            return $start_point;
        }

        // 第一行可能数据不全，再读一行
        $line = fgets($fd);
        if (feof($fd) || false === $line || trim($line) == '') {
            return $start_point;
        }

        // 判断是否越界
        $current_point = ftell($fd);
        if ($current_point >= $end_point) {
            return $start_point;
        }

        // 获得时间
        $tmp = explode("\t", $line);
        $tmp_time = strtotime($tmp[0]);

        // 判断时间，返回指针位置
        if ($tmp_time > $time) {
            return $this->binarySearch($start_point, $current_point, $time, $fd);
        } elseif ($tmp_time < $time) {
            return $this->binarySearch($current_point, $end_point, $time, $fd);
        } else {
            return $current_point;
        }
    }
} 
