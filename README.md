# statistics
一个运用php与swoole实现的统计监控系统

## 界面截图
![Swoole statistics screenshot one](https://raw.githubusercontent.com/smalleyes/statistics/master/doc/1.png)

![Swoole statistics screenshot two](https://raw.githubusercontent.com/smalleyes/statistics/master/doc/2.png)

![Swoole statistics screenshot three](https://raw.githubusercontent.com/smalleyes/statistics/master/doc/3.png)

![Swoole statistics screenshot four](https://raw.githubusercontent.com/smalleyes/statistics/master/doc/4.png)

## 说明
* statistics是一个以swoole作为服务器容器的统计监控系统。
* statisitcs使用PHP开发，无需安装Mysql等数据库，无需安装php-fpm等软件。
* statistics包含了客户端和服务端，客户端是一个类库，通过函数调用的方式以UDP协议上报数据给服务端。
* statistics服务端接收上报数据然后汇总展示。
* statistics以曲线图、饼图和表格的方式展示请求量、耗时、成功率、错误日志等。
* workerman版本实现statistics [https://github.com/walkor/workerman-statistics](https://github.com/walkor/workerman-statistics)

## 依赖

* PHP 5.3+
* Swoole 1.7.18
* Linux, OS X and basic Windows support (Thanks to cygwin)

## 安装 Swoole扩展

1. Install swoole extension from pecl
    
    ```
    pecl install swoole
    ```

2. Install swoole extension from source

    ```
    sudo apt-get install php5-dev
    git clone https://github.com/swoole/swoole-src.git
    cd swoole-src
    phpize
    ./configure
    make && make install
    ```

## 安装

### 1. 下载 Swoole statistics

linux shell Clone the git repo: 
```
git clone https://github.com/smalleyes/statistics.git
```
linux wget the zip file:
```
wget https://github.com/smalleyes/statistics/archive/master.zip
unzip master.zip
```
### 2. 安全

    管理员用户名密码默认都为admin。
    如果不需要登录验证，在applications/Statistics/Config/Config.php里面设置管理员密码留空。
    请自行做好安全相关的限制.

## 运行

* 配置NGINX虚拟主机
* 配置文件位于doc/statistics.conf
* 复制文件statistics.conf到nginx，虚拟主机配置文件目录下（默认为nginx/conf.d目录下）
* 重启nginx或重新加载nginx配置文件（nginx -s reload）
* 配置hoshs文件，绑定ip域名对应关系
* 使用swoole需要启动服务，php web.php与php worker.php再打开浏览器访问绑定的域名。
* 配置信息都在Config目录下。
* 开启守护进程模式，请修改配置Config/Server.php的daemonize选项为TRUE。

## 客户端使用方法 

```php

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
    
```
