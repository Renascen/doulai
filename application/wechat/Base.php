<?php

namespace app\wechat;

use EasyWeChat\Foundation\Application;

use Doctrine\Common\Cache\RedisCache;

use think\Db;

trait Base
{


    protected $options = [
        /**
         * Debug 模式，bool 值：true/false
         *
         * 当值为 false 时，所有的日志都不会记录
         */
        'debug' => true,
        /**
         * 账号基本信息，请从微信公众平台/开放平台获取
         */
        'app_id' => 'your-app-id',         // AppID
        'secret' => 'your-app-secret',     // AppSecret
        'token' => 'your-token',          // Token
        'aes_key' => '',                    // EncodingAESKey，安全模式下请一定要填写！！！
        'wechat_name' => '',
        'wechat_id' => '',
        'wechat_number' => '',
        'wechat_type' => 1,
		/**
		* 缓存
		*/
		 //'cache'   => $cacheDriver,
        /**
         * 日志配置
         *
         * level: 日志级别, 可选为：
         *         debug/info/notice/warning/error/critical/alert/emergency
         * permission：日志文件权限(可选)，默认为null（若为null值,monolog会取0644）
         * file：日志文件位置(绝对路径!!!)，要求可写权限
         */
        'log' => [
            'level' => 'debug',
            'permission' => 0777,
            'file' => './runtime/log/wechat/easywechat.log',
        ],

        /**
         * Guzzle 全局设置
         *
         * 更多请参考： http://docs.guzzlephp.org/en/latest/request-options.html
         */
        'guzzle' => [
            'timeout' => 3.0, // 超时时间（秒）
            //'verify' => false, // 关掉 SSL 认证（强烈不建议！！！）
        ],
    ];



    protected $app;

    public function _initialize()
    {
        parent::_initialize();
		
		$cacheDriver = new RedisCache();
		// 创建 redis 实例
		$redis = new \Redis();
		$redis->connect('localhost', 6379);
		$cacheDriver->setRedis($redis);	

        $config = module_config('wechat');
		$config['cache']=$cacheDriver;
		
	     preg_match_all("/([1-9]\d*)/", $_SERVER['HTTP_HOST'], $agenturl);
    
    if(!empty($agenturl['0']['0']))
    {
        $uconfig=DB::table('ien_wechat_uconfig')->where('uid',$agenturl['0']['0'])->where('isopen','on')->find();
        
    }

    if(!empty($uconfig))
    {
        $config = [
        /**
         * Debug 模式，bool 值：true/false
         *
         * 当值为 false 时，所有的日志都不会记录
         */
        'debug' => true,
        /**
         * 账号基本信息，请从微信公众平台/开放平台获取
         */
        'app_id' => $uconfig['appid'],         // AppID
        'secret' => $uconfig['appsecret'],     // AppSecret
        'token' => $uconfig['token'],          // Token
        'aes_key' => $uconfig['encodingaeskey'],                    // EncodingAESKey，安全模式下请一定要填写！！！
        'wechat_name' => $uconfig['name'],
        'wechat_id' => $uconfig['gid'],
        'wechat_number' =>  $uconfig['wxh'],
        'wechat_type' => 1,
        /**
        * 缓存
        */
         //'cache'   => $cacheDriver,
        /**
         * 日志配置
         *
         * level: 日志级别, 可选为：
         *         debug/info/notice/warning/error/critical/alert/emergency
         * permission：日志文件权限(可选)，默认为null（若为null值,monolog会取0644）
         * file：日志文件位置(绝对路径!!!)，要求可写权限
         */
        'log' => [
            'level' => 'debug',
            'permission' => 0777,
            'file' => './runtime/log/wechat/easywechat.log',
        ],

        /**
         * Guzzle 全局设置
         *
         * 更多请参考： http://docs.guzzlephp.org/en/latest/request-options.html
         */
        'guzzle' => [
            'timeout' => 3.0, // 超时时间（秒）
            //'verify' => false, // 关掉 SSL 认证（强烈不建议！！！）
        ],
    ];
    }
		
		
        $this->options = array_merge($this->options, $config);

        $this->app = new Application($this->options);
		
    }

}