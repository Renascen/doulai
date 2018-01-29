<?php
// +----------------------------------------------------------------------
// | 浩森PHP框架 [ IeasynetPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2017~2018 北京浩森宇特互联科技有限公司 [ http://www.ieasynet.com ]
// +----------------------------------------------------------------------
// | 官方网站：http://ieasynet.com
// +----------------------------------------------------------------------
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | 作者: 拼搏 <378184@qq.com>
// +----------------------------------------------------------------------

namespace app\cms\home;

use app\index\controller\Home;
use think\Db;
use util\Tree;
use EasyWeChat\Foundation\Application;
use Doctrine\Common\Cache\RedisCache;
//use think\Session;
/**
 * 前台公共控制器
 * @package app\cms\admin
 *http://book.ieasynet.com/index.php/cms/document/detail/id/7.html?t=5555
 */
class oauth extends Home
{
    //放弃不用。方法调用方式send函数无法跳转。出现死循环
    public function checklogin()
    {
        // 未登录
        if (empty($_SESSION['wechat_user'])) {
          //$_SESSION['target_url'] = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

            $this->oauth();
          //return $oauth->redirect();
          // 这里不一定是return，如果你的框架action不是返回内容的话你就得使用
          // $oauth->redirect()->send();
        }
        else{
        // 已经登录过 
            //header('location:'. $_SESSION['target_url']);
            return $user=$_SESSION['wechat_user'];
        }
    }

    public function oauth()
    {
        session_start();

          //$_SESSION['target_url'] = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $config = [
          // ...
          'oauth' => [
                  'scopes'   => ['snsapi_base'],
            	  // 'scopes'   => ['snsapi_userinfo'],
                  'callback' => 'http://'.module_config('agent.agent_rooturl').'/index.php/cms/oauth/oauth_callback',
                
          ],
          // ..
        ];
        $cacheDriver = new RedisCache();
        // 创建 redis 实例
        $redis = new \Redis();
        $redis->connect('localhost', 6379);
        $cacheDriver->setRedis($redis); 
                


        $config2 = module_config('wechat');
        $config2['cache']=$cacheDriver;
        $config = array_merge($config, $config2);
        $app = new Application($config);
        $oauth = $app->oauth;
        //dump($oauth);
        
        //$this->checklogin();

        $oauth->redirect()->send();


    }
    public function oauth_callback()
    {
        session_start();
        $cacheDriver = new RedisCache();

        // 创建 redis 实例
        $redis = new \Redis();
        $redis->connect('localhost', 6379);
        $cacheDriver->setRedis($redis); 

        $config = module_config('wechat');
        $config2['cache']=$cacheDriver;

        $app = new Application($config);
        $oauth = $app->oauth;
        // 获取 OAuth 授权结果用户信息
        $user = $oauth->user();

//         $a=$user->toArray();

//         dump($a);
//         die();
        $dbuser = DB::table('ien_admin_user')->where('openid',$user['original']['openid'])->find();
//        $_SESSION['wechat_user']['original']['openid'] = $user['original']['openid'];
        $urla = array();
        if (!$dbuser) {

            if (isset($_SESSION['target_url']) && strpos($_SESSION['target_url'],"?") !== false) {
                $url=explode("?",$_SESSION['target_url']);
                if(strpos($url[1], '&') !== false){
                    $urla = explode('&', $url[1]);
                    $urla = explode('=', $urla[0]);
                }else{
                    $urla=explode("=", $url[1]);
                }
            }
            $bid = 0;
            if (!empty($urla[1])) {

                $sxid = DB::table('ien_agent')->where('id',$urla[1])->find();
                if (empty($sxid)) {
                    $sxid['uid']=0;
                }
                if(!empty($sxid['zid'])){
                    $bid = DB::table('ien_chapter')->where('id', $sxid['zid'])->value('bid');
                }
            } else {
                $sxid['uid']=0;
            }
            if(isset($urla[0]) && $urla[0] == 'from') {
                $urla[1] = 0;
            }
            $data = [
                'username' => $user['nickname'] ? $user['nickname'] : "读者", 
        		// 'username' => $user['nickname'], 
                'nickname' => $user['nickname']."读者", 
        		// 'nickname' => $user['nickname'],
                'password' => '$2y$10$wwJ7bP4SLfGWZ3.DTQ0RdeglgBLAW5iY4mA6LvoDuQrvcV6qsKdou',
                'email'    =>  $user['email']." ", 
                'avatar'    => $user['avatar']."http://".module_config('agent.agent_rooturl')."/images/homeuser.png",
        		// 'avatar'    => $user['avatar'],
                'create_time'    => time(),
                'last_login_time'    => time(),
                'openid'    => $user['original']['openid'],
                'role'      =>   '3',
                'tgid'      => !empty($urla[1]) && is_numeric($urla[1]) ? $urla[1] : 0,
                'bid'      => $bid,
                'status'    => '1', 
                'sxid'      =>$sxid['uid'],
                'signup_ip' => get_client_ip(1),
                'did' => 0,
                'isvip' => 0,
                'vipstime' => '',
                'vipetime' => '',
                'fcbl'  => '',
                'xingming'  => '',
                'fangshi' => '',
                'zhanghao' => '',
                'sex'  => 0,
                'guanzhu'  => 0,
                'ewm'  => '',
                'isguanzhu'  => 0,
                'isout'  => 0,
                'gzopenid'   => '',
                'tuijian'  => 0,
                'ticket' => 0
                //'sex'       =>$user['original']['sex'],
            ];

            $uid = Db::table('ien_admin_user')->insertGetId($data);
            $comefrom_log = array(
                'uid' => $uid,
                'tgid' => isset($urla[1]) && is_numeric($urla[1]) ? $urla[1] : 0,
                'comefrom' => !empty($urla[1]) ? '' : $_SESSION['register_page'],
                'is_new' => 1,
                'create_time' => time()
            );
            Db::table('ien_comefrom_log')->insert($comefrom_log);
        } else {

            if(empty($dbuser['signup_ip'])){
                DB::table('ien_admin_user')->where('id', $dbuser['id'])->update(['signup_ip' => get_client_ip(1)]);
            }
            if(module_config('agent.agent_fltime')!=0 || module_config('agent.agent_fltime') !=""){
                $timecha=ceil((time()-$dbuser['create_time'])/86400); 
                if($timecha>module_config('agent.agent_fltime')){
                    $nokou=explode(',',module_config('agent.agent_nokou'));
                    if(!in_array($dbuser['sxid'],$nokou)){
                        DB::table('ien_admin_user')->where('openid', $dbuser['openid'])->update(['isout'=>'1']);
                    }
                }
            }
        }

        //判断跳转
        //     dump($user);
        // die;
            
        $_SESSION['wechat_user'] = $user->toArray();
//        dump($_SESSION);
//        dump($_SESSION['wechat_user']);
//        exit;
        DB::table('ien_admin_user')->where('openid', $_SESSION['wechat_user']['original']['openid'])->update(['last_login_time' => time(), 'last_login_ip' => get_client_ip(1)]);
        $_SESSION['is_save_last_login_time'] = true;
        $user_log = array(
            'uid' => empty($dbuser) ? $uid : $dbuser['id'],
            'openid' => $_SESSION['wechat_user']['original']['openid'],
            'login_ip' => get_client_ip(1),
            'create_time' => time()
        );
       DB::table('ien_user_log')->insert($user_log);



        if(empty($_SESSION['target_url'])){


            header('location:' . url('/cms/index/index'));
            die;
        }

        header('location:'. $_SESSION['target_url']);

        //dump($_SESSION['wechat_user']);
        //$_SESSION['wechat_user'] = $user->toArray();
       // $targetUrl = empty($_SESSION['target_url']) ? '/' : $_SESSION['target_url'];
        //header('location:'. $targetUrl); // 跳转到 user/profile

    }




}