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
use EasyWeChat\Core\AccessToken;
use EasyWeChat\Js\Js;
//use think\Session;
/**
 * 前台公共控制器
 * @package app\cms\admin
 */
class Common extends Home
{
    public function __construct()
    {
        session_start();
        parent::__construct();
       // dump($_SESSION['wechat_user']['original']['openid']);
       // exit;
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        if($this->request->action() != 'addcore'){
            $_SESSION['target_url'] = $http_type . $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        }
        $userCfg = DB::table('ien_admin_user')->where('openid', $_SESSION['wechat_user']['original']['openid'])->find();
//        dump($_SESSION['wechat_user']['original']['openid']);
//        dump($userCfg);
//        exit;
        if(empty($userCfg) && $this->request->action() != 'paysuccess' && $this->request->action() != 'notify_url'){
            $_SESSION['register_page'] = empty($_SERVER['HTTP_REFERER']) ? $_SERVER['REQUEST_URI'] : $_SERVER['HTTP_REFERER'];
            $this->redirect('oauth/oauth');
            $userCfg = DB::table('ien_admin_user')->where('openid', $_SESSION['wechat_user']['original']['openid'])->find();
        }
        if(!empty($userCfg) && !$_SESSION['is_save_last_login_time']){
            DB::table('ien_admin_user')->where('id', $userCfg['id'])->update(array('last_login_time' => time(), 'last_login_ip' => get_client_ip(1)));
            $_SESSION['is_save_last_login_time'] = true;
            $user_log = array(
                'uid' => $userCfg['id'],
                'openid' => $userCfg['openid'],
                'login_ip' => get_client_ip(1),
                'create_time' => time()
            );
            DB::table('ien_user_log')->insert($user_log);
        }
        $date = date('Y-m-d', time());
        $exit = DB::table('ien_day_log')->where('addtime', $date)->find();
        // 获取网址参数 
        $temp = explode('&', $_SERVER["QUERY_STRING"]); #id=5
        $params = array();
        foreach($temp as $val){
            $temp1 = explode('=', $val);
            $params[$temp1[0]] = $temp1[1];
        }

        $action = request()->action();
        if(empty($exit)){
            $data['addtime'] = $date;
            $data['click'] = 0;
            $data['pv'] = 0;
            DB::table('ien_day_log')->insert($data);
        }
        if($action == 'index' || array_key_exists('t', $params)){
            DB::table('ien_day_log')->where('addtime', $date)->setInc('click');
        }
        $zid = input('id');
        $bid = input('bid');
        $comefrom = empty(input('comefrom')) ? 0 : input('comefrom');
        $subid = empty(input('subid')) ? 0 : input('subid');
        if($this->request->action() == 'desc' && !empty($bid) && !empty($comefrom)){
            $_SESSION['comefrom'] = $comefrom;
            $_SESSION['subid'] = $subid;
        }
        if(request()->controller() != 'Document' && $action != 'indexidx' && $action != 'doajaxidx' && $action != 'savestyle'){
            unset($_SESSION['comefrom']);
            unset($_SESSION['subid']);
        }
        DB::table('ien_day_log')->where('addtime', $date)->setInc('pv');
        if($_SESSION['wechat_user']['original']['openid']){
            $_SESSION['target_url'] = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
            // 添加点击数
            // PV统计、UV统计
            if($userCfg['tgid'] > 0){
                DB::table('ien_agent')->where('id',$userCfg['tgid'])->setInc('pv');
            }
            if((!empty($zid) || !empty($bid)) && ($action == 'desc' || $action == 'detail')){
                $recommend = get_recommend(empty($_SESSION['comefrom']) ? 0 : $_SESSION['comefrom']);
                $bookid = !empty($zid) ? DB::table('ien_chapter')->where('id',$zid)->value('bid') : $bid;
                $data = [
                    'uid'           => empty($userCfg['id']) ? 0 : $userCfg['id'],
                    'openid'        => empty($_SESSION['wechat_user']['original']['openid']) ? '' : $_SESSION['wechat_user']['original']['openid'],
                    'bookid'        => $bookid ? $bookid : 0,
                    'pagetype'      => !empty($zid) ? 1 : 0,
                    'zid'           => !empty($zid) ? $zid : 0,
                    'tgid'          => $userCfg['tgid'] ? $userCfg['tgid'] : 0,
                    'isnewuser'     => $userCfg['tgid'] > 0 && $userCfg['tgid'] == $params['t'] ? 1 : 0,
                    'comefrom'      => empty($_SESSION['comefrom']) ? 0 : $_SESSION['comefrom'],
                    'subid'         => empty($_SESSION['subid']) ? 0 : $_SESSION['subid'],
                    'createtime'    => time(),
                ];
                Db::table('ien_agent_pv')->insert($data);
            }
            
            if (!empty($params['t'])) {
                DB::table('ien_agent')->where('id',$params['t'])->setInc('click');
                $agentuvCfg = DB::table('ien_agent_uv')->where(array('openid' => $_SESSION['wechat_user']['original']['openid'], 'tgid' => $params['t']))->count();
                if ($agentuvCfg == 0) {
                    $data = [
                        'uid'           => $userCfg['id'],
                        'openid'        => $_SESSION['wechat_user']['original']['openid'],
                        'tgid'          => $params['t'],
                        'isnewuser'     => $userCfg['tgid'] == $params['t'] ? 1 : 0,
                        'createtime'    => time(),
                    ];
                    Db::table('ien_agent_uv')->insert($data);
                }
                $exist = DB::table('ien_comefrom_log')->where(['uid' => $userCfg['id'], 'tgid' => $params['t']])->find();
                if(!$exist){
                    $comefrom_log = array(
                        'uid' => $userCfg['id'],
                        'tgid' => $params['t'],
                        'is_new' => 0,
                        'create_time' => time()
                    );
                    Db::table('ien_comefrom_log')->insert($comefrom_log);
                }
            }
        }
    }
    /**
     * 初始化方法
     * @author 拼搏 <378184@qq.com>
     */
    protected function _initialize()
    {
        parent::_initialize();
        $config = module_config('wechat');
        $jssdk = new Js(new AccessToken($config['app_id'], $config['secret']));
        // if(empty($user) && !$_SESSION['wechat_user']['original']['openid']){
        //     $user = DB::table('ien_admin_user')->where('openid', $_SESSION['wechat_user']['original']['openid'])->find();
        // }
        // $this->assign('user', $user);
        $share = [
            'title' => config('web_site_title'),
            'desc' => config('web_site_description'),
            'img' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . get_thumb(config('web_site_logo'))
        ];
        $this->assign('signature', $jssdk->signature());
        $this->assign('share', $share);
        $this->assign('action', $this->request->action());
        $this->assign('controller', $this->request->controller());

      
    }

    /**
     * 获取导航
     * @author 拼搏 <378184@qq.com>
     */
    private function getNav()
    {
        $list_nav = Db::name('cms_nav')->where('status', 1)->column('id,tag');

        foreach ($list_nav as $id => $tag) {
            $data_list = Db::view('cms_menu', true)
                ->view('cms_column', ['name' => 'column_name'], 'cms_menu.column=cms_column.id', 'left')
                ->view('cms_page', ['title' => 'page_title'], 'cms_menu.page=cms_page.id', 'left')
                ->where('cms_menu.nid', $id)
                ->where('cms_menu.status', 1)
                ->order('cms_menu.sort,cms_menu.pid,cms_menu.id')
                ->select();

            foreach ($data_list as &$item) {
                if ($item['type'] == 0) { // 栏目链接
                    $item['title'] = $item['column_name'];
                    $item['url'] = url('cms/column/index', ['id' => $item['column']]);
                } elseif ($item['type'] == 1) { // 单页链接
                    $item['title'] = $item['page_title'];
                    $item['url'] = url('cms/page/detail', ['id' => $item['page']]);
                } else {
                    if ($item['url'] != '#' && substr($item['url'], 0, 4) != 'http') {
                        $item['url'] = url($item['url']);
                    }
                }
            }

            $this->assign($tag, Tree::toLayer($data_list));
        }
    }

    /**
     * 获取滚动图片
     * @author 拼搏 <378184@qq.com>
     */
    private function getSlider()
    {
        return Db::name('cms_slider')->where('status', 1)->select();
    }

    /**
     * 获取在线客服
     * @author 拼搏 <378184@qq.com>
     */
    private function getSupport()
    {
        return Db::name('cms_support')->where('status', 1)->order('sort')->select();
    }

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
        $config = [
          // ...
          'oauth' => [
                  'scopes'   => ['snsapi_userinfo'],
                  'callback' => '/index.php/cms/oauth/oauth_callback',
                
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
         if(empty($_SESSION['wechat_user'])){
            $oauth->redirect()->send();
           // return $this->success('正在跳转',url('Common/oauth_callback', $oauth));
            //$this->redirect('Common/oauth_callback', $oauth);
        }else
            return true;

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
       

        $dbuser=DB::table('ien_admin_user')->where('openid',$user['original']['openid'])->find();
        if(!$dbuser)
        {
            if(strpos($_SESSION['target_url'],"?"))
            {
                $url=explode("?",$_SESSION['target_url']);
                $urla=explode("=", $url[1]);
                //echo $urla['1'];
            }
            
            $data = [
                         'username' => $user['nickname'], 
                         'nickname' => $user['nickname'], 
                         'password' => '$2y$10$wwJ7bP4SLfGWZ3.DTQ0RdeglgBLAW5iY4mA6LvoDuQrvcV6qsKdou',
                         'email'    =>  $user['email']." ", 
                         'avatar'    => $user['avatar'],
                         'create_time'    => time(),
                         'last_login_time'    => time(),
                         'openid'    => $user['original']['openid'],
                         'role'      =>   '3',
                         'tgid'      => $urla['1'],
                         'status'    => '1', 
                         'gzopenid'  => $user['original']['openid'], 
                        ];
            Db::table('ien_admin_user')->insert($data);
        }

        //判断跳转
        
        $_SESSION['wechat_user'] = $user->toArray();
        header('location:'. "www.baidu.com");

    }
    public function ihttp_request($url, $data = '', $type = "get", $res = "json"){
        //1.初始化curl
        $curl = curl_init();
        //2.设置curl的参数
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SAFE_UPLOAD, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT,60);
        if ($type == "post"){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        //3.采集
        $output = curl_exec($curl);
        //4.关闭
        curl_close($curl);

        if ($res == 'json') {
            return json_decode($output,true);
        }
        return $output;
    }

    public function getReadHistory($uid, $bid, $all = false)
    {
        $map = array(
            'uid' => $uid,
            'bid' => $bid
        );
        if($all){
            $info = DB::table('ien_read_log')->where($map)->select();
        }else{
            $info = DB::table('ien_read_log')->where($map)->order('update_time desc')->find();
        }
        return $info;
    }
        
    /**
     * 获取最后一条阅读记录
     * @param  int  $uid 用户openid
     * @param  boolean $bid 为false不指定小说，否则为小说id
     * @return array  返回找到的记录 
     */
    public function getLastRead($uid, $bid = false)
    {
        $map = array('uid' => $uid);
        if($bid){
            $map['bid'] = $bid;
        }
        return DB::table('ien_read_log')->where($map)->order('create_time desc')->find();
    }

    /**
     * 广告点击日志
     * @param  int $tag   广告位置标记
     * @param  int $subid 广告在表中的自增id
     */
    public function count_click($tag = 0, $subid = 0){
        if($_SESSION['wechat_user']['original']['openid']){
            $user = DB::table('ien_admin_user')->where('openid', $_SESSION['wechat_user']['original']['openid'])->find();
        }
        $recommend = get_recommend($tag);
        $data = array(
            'tag' => $tag,
            'subid' => $subid,
            'in_table' => $recommend['tablename'],
            'uid' => empty($user) ? 0 : $user['id'],
            'ip' => get_client_ip(1),
            'create_time' => time()
        );
        return DB::table('ien_recommend_click_log')->insert($data);
    }
}


/**
 * `tag` int unsigned not null default 0 COMMENT '位置标记',
  `subid` int unsigned not null default 0 COMMENT '广告id',
  `in_table` varchar(100) not null default '' comment '所在数据表',
  `create_time` int(11) unsigned NOT NULL default 0 COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL default 0 COMMENT '更新时间'
 */