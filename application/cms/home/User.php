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

use think\Db;

use util\Tree;

use EasyWeChat\Foundation\Application;

use Doctrine\Common\Cache\RedisCache;



/**
 * 前台首页控制器
 * @package app\cms\admin
 */



class User extends Common



{



    /**
     * 首页
     * @author 拼搏 <378184@qq.com>
     * @return mixed
     */



    public function index()



    {



    	// 微信网页授权接口

		 /*登陆验证方法*/

		// session_start();

        $_SESSION['target_url'] = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

        if(empty($_SESSION['wechat_user'])){

            $this->redirect('oauth/oauth');

            //$this-> checklogin();

        }

		//如果上线ID有人,并且设置了关注.那么拉取关注openid去公众号里面查用户信息

		$user=DB::table('ien_admin_user')->where('openid',$_SESSION['wechat_user']['original']['openid'])->find();

		if($user['sxid']!=0 && $user['gzopenid']!="")

		{

			//查上线代理对接信息

			$userdl=DB::table('ien_wechat_uconfig')->where('uid',$user['sxid'])->where('isopen',"on")->find();

			if(!empty($userdl))

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

				        'app_id' => $userdl['appid'],         // AppID

				        'secret' => $userdl['appsecret'],     // AppSecret

				        'token' => $userdl['token'],          // Token

				        'aes_key' => $userdl['encodingaeskey'],                    // EncodingAESKey，安全模式下请一定要填写！！！

				        'wechat_name' => $userdl['name'],

				        'wechat_id' => $userdl['gid'],

				        'wechat_number' =>  $userdl['wxh'],

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



				    $cacheDriver = new RedisCache();

			        // 创建 redis 实例

			        $redis = new \Redis();

			        $redis->connect('localhost', 6379);

			        $cacheDriver->setRedis($redis); 



			        //$config2 = module_config('wechat');

			        $config2['cache']=$cacheDriver;

			        $config = array_merge($config, $config2);

			        $app = new Application($config);

			        $userService = $app->user;

			        $userinfo = $userService->get($user['gzopenid']);

			        if(!empty($userinfo))

			        {

			        	$data=['nickname'=>$userinfo['nickname'],'name'=>$userinfo['nickname'],'sex'=>$userinfo['sex'],'avatar'=>$userinfo['headimgurl'],];



			        	DB::table('ien_admin_user')->where('openid', $_SESSION['wechat_user']['original']['openid'])->update($data);

			        }





			}





		}	



		$user=DB::table('ien_admin_user')->where('openid',$_SESSION['wechat_user']['original']['openid'])->find();
        DB::table('ien_read_log')->where('uid', $_SESSION['wechat_user']['original']['openid'])->count();
        $history = Db::view('read_log','id,bid,zid,update_time')
            ->view('chapter',['title'=>'ptitle','idx'=>'idx'],'chapter.id=read_log.zid','LEFT')
            ->view('book',['title'=>'btitle', 'image','images','zuozhe'],'book.id=read_log.bid','LEFT')
            ->where("read_log.uid" , $_SESSION['wechat_user']['original']['openid'])
            ->group('read_log.bid')
            ->limit(0,6)
            ->order('read_log.update_time desc')
            ->select();

        $info = array();
        foreach($history as $val){
            $val['images'] = $val['images']!=0?get_file_path($val['images']):"/public/static/cms/img/default_erect.jpg";
            if(array_key_exists($val['bid'], $info)){
                continue;
            }
            $info[$val['bid']] = $val;

        }

           if (is_numeric($user['avatar'])){
            $user['avatar'] = get_file_path($user['avatar']);
        }
        $this->assign('history', $info);
		$this->assign('user', $user);

        return $this->fetch(); // 渲染模板



    }

	//送书币

	public function free(){

		

		

		return $this->fetch('free');

		

		}





   //书签

   public function bookmark(){

	   

	   /*登陆验证方法*/

		// session_start();

        $_SESSION['target_url'] = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

        if(empty($_SESSION['wechat_user'])){

            $this->redirect('oauth/oauth');

            //$this-> checklogin();

        }

		

		$openid=$_SESSION['wechat_user']['original']['openid'];

		$bookmark=DB::view('ien_bookmarks')

		->view('ien_chapter','bid,idx,title','ien_chapter.id=ien_bookmarks.zid')

		->where('ien_bookmarks.uid',$openid)

		->select();

		

		$this->assign('bookmark', $bookmark);

		return $this->fetch('bookmark');

		

	   

	   

	   }

	 public function delmark($id=null)

	 {

		 /*登陆验证方法*/

		// session_start();

        $_SESSION['target_url'] = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

        if(empty($_SESSION['wechat_user'])){

            $this->redirect('oauth/oauth');

            //$this-> checklogin();

        }

		

		$openid=$_SESSION['wechat_user']['original']['openid'];

		$bookmark=DB::table('ien_bookmarks')->where('uid',$openid)->select();

		

		if(DB::table('ien_bookmarks')->where('id',$id)->delete())

		return true;

		else

		return false;

		

		 

		 

		 }

		 //自动阅读历史跳转

   	public function readold($openid=null)

	{

		// $gzopenid=$openid;

		 /*登陆验证方法*/

		// session_start();

        $_SESSION['target_url'] = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

        if(empty($_SESSION['wechat_user'])){

            $this->redirect('oauth/oauth');

            //$this-> checklogin();

        }

		$openid=$_SESSION['wechat_user']['original']['openid'];

		$old=DB::table('ien_read_log')->where('uid',$openid)->order('update_time desc')->find();


		

		// $guanzhu=DB::table('ien_admin_user')->where('openid',$openid)->update(['isguanzhu'=>1,'gzopenid'=>$gzopenid]);

		$guanzhu=DB::table('ien_admin_user')->where('openid',$openid)->update(['isguanzhu'=>1]);

		if(empty($old))

		{

			 $this->redirect('index/index');

			}

		else{
			$max_idx = DB::table('ien_chapter')->where('bid', $old['bid'])->value('max(idx)');
			$cur_idx = DB::table('ien_chapter')->where('id', $old['zid'])->value('idx');
			if($cur_idx < $max_idx){
				$old['zid']++;
			}
			$url = '/index.php/cms/document/detail/id/' . $old['zid'] . '.html?isindexkeep=true'; 
			$this->redirect($url);

			}

		

		

		

		

		

		

	}


    /**
     *
     *
     *
     * @param int $start
     * @param int $limit
     * @return array|mixed
     */
	public function booksheet_temp($start = 0, $limit = 10)
	{

		$_SESSION['target_url'] = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

        if(empty($_SESSION['wechat_user'])){
            $this->redirect('oauth/oauth');
        }
        $openid=$_SESSION['wechat_user']['original']['openid'];

        if ($this->request->isAjax()) {
            $booksheet = DB::view('ien_book_collect')
                ->view('ien_book', 'cover,image,zuozhe,title,id as bid', 'ien_book.id=ien_book_collect.bid')
                ->where('ien_book_collect.uid', $openid)
                ->limit($start, $limit)
                ->order('ien_book_collect.createtime desc')
                ->select();

            if ($booksheet) {
                $max_idx = DB::query("select max(idx) idx,bid from ien_chapter where bid in (select bid from ien_book_collect where uid='$openid') group by bid");
                $bookmark = DB::query("select b.idx idx,a.bid,b.id as zid from ien_read_log a left join ien_chapter b on a.zid=b.id where a.uid='$openid'");
                foreach ($booksheet as $k => $v) {
                    foreach ($max_idx as $key => $value) {
                        if ($v['bid'] == $value['bid']) {
                            $booksheet[$k]['max_idx'] = $value['idx'];
                        }
                    }
                    foreach ($bookmark as $mk => $mv) {
                        if ($v['bid'] == $mv['bid']) {
                            $booksheet[$k]['mark_id'] = $mv['idx'];
                            $booksheet[$k]['zid'] = $mv['zid'];
                        }
                    }
                }

                foreach ($booksheet as $k => $v) {
                    $booksheet[$k]['image'] = $v['image'] != 0 ? get_file_path($v['image']) : "/public/static/cms/img/default_erect.jpg";
                    if (isset($v['mark_id'])) {
                        $booksheet[$k]['rate'] = get_ratio($v['mark_id'], $v['max_idx']);
                    } else {
                        $booksheet[$k]['rate'] = 0;

                    }

                    if ($booksheet[$k]['rate'] == 0) {
                        $booksheet[$k]['zid'] = Db::table('ien_chapter')->where('bid', $v['bid'])->order('idx asc')->value(id);
                    }
                }
            }
            return json($booksheet);

        }
       
        return $this->fetch('booksheet');
	}

	public function booksheet($active = 'booksheet')
	{

		$num = DB::table('ien_book_collect')->where('uid', $_SESSION['wechat_user']['original']['openid'])->count();
		/**********我的书架小说推荐************/
		$tj_book_myB = [];
		$btj_status = Db::table('ien_admin_user')->where('openid',$_SESSION['wechat_user']['original']['openid'])->value('btj_status');
		if ($btj_status == 1) {
			$list_num_myB = Db::table('ien_tj_admin')->field('list_num')->where('tj',6)->where('status',1)->find();
        	$tj_book_myB = Db::table('ien_book book')->field('book.id,book.title,book.zuozhe,book.image,book.desc,book.model,book.cover,book.tstype,book.recommend')
            // ->join('ien_book_pv pvt', 'book.id=pvt.bid', 'left')
            ->join('ien_tj_book tjbook','book.id=tjbook.bid','left')
            ->where('book.status=1')
            ->where("FIND_IN_SET( '6', book.tj)")
            ->where("tjbook.tjid=6")
            ->limit($list_num_myB['list_num'])
            ->order('rand()')
            ->select();

                if ($list_num_myB['list_num'] == 0) {
                    $tj_book_myB = [];
                }
            // echo(Db::getLastsql());exit();
            $zhubian_name = Db::table('ien_tj_admin')->field('name')->where('tj',6)->find();
            if (count($tj_book_myB)>0) {
                 $tj_book_myB[0]['tjname'] = $zhubian_name['name'];
     		}
		}
		
     	$this->assign('tj_book_myB', $tj_book_myB);
        /**********小说推荐end************/
        /**********阅读历史小说推荐************/
        $tj_book_reH = [];
        $rtj_status = Db::table('ien_admin_user')->where('openid',$_SESSION['wechat_user']['original']['openid'])->value('rtj_status');
        if ($rtj_status == 1) {
        	$list_num_reH = Db::table('ien_tj_admin')->field('list_num')->where('tj',7)->where('status',1)->find();
        	$tj_book_reH = Db::table('ien_book book')->field('book.id,book.title,book.zuozhe,book.image,book.desc,book.model,book.cover,book.tstype,book.recommend')
            // ->join('ien_book_pv pvt', 'book.id=pvt.bid', 'left')
            ->join('ien_tj_book tjbook','book.id=tjbook.bid','left')
            ->where('book.status=1')
            ->where("FIND_IN_SET( '7', book.tj)")
            ->where("tjbook.tjid=7")
            ->limit($list_num_reH['list_num'])
            ->order('rand()')
            ->select();

                if ($list_num_reH['list_num'] == 0) {
                    $tj_book_reH = [];
                }
            $zhubian_name = Db::table('ien_tj_admin')->field('name')->where('tj',7)->find();
            if (count($tj_book_reH)>0) {
                 $tj_book_reH[0]['tjname'] = $zhubian_name['name'];
     		}
        }
       
     	$this->assign('tj_book_reH', $tj_book_reH);
        /**********小说推荐end***********/
     	$user = DB::table('ien_admin_user')->where('openid', $_SESSION['wechat_user']['original']['openid'])->find();

		$this->assign('num', $num);
		$this->assign('user', $user);
		$this->assign('active', $active);
		return $this->fetch('booksheet');	
	}

	public function delbooksheet($ids = array())
	{
		if(empty($ids)){
			return array('status' => 0, 'message' => '没有被选中的小说');
		}
		if(empty($_SESSION['wechat_user']['original']['openid'])){
			return array('status' => 0, 'message' => '没有登录');
		}
		$openid=$_SESSION['wechat_user']['original']['openid'];
		$user = DB::table('ien_admin_user')->where('openid', $openid)->find();
		if(empty($user)){
			return array('status' => 0, 'message' => '用户不存在');
		}
		if(DB::table('ien_book_collect')->whereIn('id', implode(',', $ids))->delete())
			return array('status' => 1, 'message' => '删除成功');
		else{
			return array('status' => 0, 'message' => '删除失败');
		}
	}

    public function readhistory_temp($start = 0, $limit = 5)
    {
      // 微信网页授权接口
		/*登陆验证方法*/
		// session_start();
        $_SESSION['target_url'] = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

        if(empty($_SESSION['wechat_user'])){

            $this->redirect('oauth/oauth');

            //$this-> checklogin();

        }
        $user = DB::table('ien_admin_user')->where('openid', $_SESSION['wechat_user']['original']['openid'])->find();
        DB::table('ien_read_log')->where('uid', $_SESSION['wechat_user']['original']['openid'])->count();
        $history = Db::view('read_log','id,bid,zid,update_time')
        ->view('chapter',['title'=>'ctitle','idx'=>'idx'],'chapter.id=read_log.zid','LEFT')
        ->view('book',['title'=>'btitle', 'image','cover','zuozhe' => 'author'],'book.id=read_log.bid','LEFT')
        ->where("read_log.uid" , $_SESSION['wechat_user']['original']['openid'])
        ->group('read_log.bid')
        ->limit($start, $limit)
        ->order('read_log.update_time desc')
        ->select();
        $info = array();
        foreach($history as $val){
        	if(array_key_exists($val['bid'], $info)){
        		continue;
        	}
        	$info[$val['bid']] = $val;
        }
        $this->assign('history', $info);
        $this->assign('user', $user);
        /**********小说推荐************/
        $tj_book = Db::table('ien_book book')->field('book.id,book.title,book.zuozhe,book.image,book.desc,book.model,book.cover,book.tstype,book.recommend')
            // ->join('ien_book_pv pvt', 'book.id=pvt.bid', 'left')
            ->join('ien_tj_book tjbook','book.id=tjbook.bid','left')
            ->where('book.status=1')
            ->where("FIND_IN_SET( '7', book.tj)")
            ->where("tjbook.tjid=7")
            // ->order('pvt.pv desc')
            ->select();
            $zhubian_name = Db::table('ien_tj_admin')->field('name')->where('tj',7)->find();
            if (count($tj_book)>0) {
                 $tj_book[0]['tjname'] = $zhubian_name['name'];
     		}
     	$this->assign('tj_book', $tj_book);
        /**********小说推荐************/
        return array('html' => $this->fetch('readhistory'), 'length' => count($history)); // 渲染模板
    }



   
    public function payhistory()
    {
        if ($this->request->isAjax()){
            $start = $_GET['start'];
            $limit = $_GET['limit'];
            if(empty($_SESSION['wechat_user'])){
                $this->redirect('oauth/oauth');
                //$this-> checklogin();
            }
            $user = DB::table('ien_admin_user')->where('openid', $_SESSION['wechat_user']['original']['openid'])->find();
            $payhistory = DB::table('ien_pay_log')
                ->where(['uid' => $user['openid'], 'status' => 1])
                ->limit($start,$limit)
                ->order('addtime desc')
                ->select();
            if ($payhistory) {
                foreach($payhistory as &$value) {
                    $value['paytime'] = date('m-d H:i',$value['paytime']);
                }
                unset($value);
            }
            return json($payhistory);

        }
    	return $this->fetch();
    }


	public function savestyle($style)
    {
        if(empty($_SESSION['wechat_user']) || empty($style)){
        	return false;
        }
        $user = DB::table('ien_admin_user')->where('openid', $_SESSION['wechat_user']['original']['openid'])->find();
        if(!empty($user)){
        	$style_val = $style['bg'] . ',' . $style['font'];
        	DB::table('ien_admin_user')->where('id', $user['id'])->update(['style' => $style_val]);
        	return true;
        }
        return false;
    }


    public function user_set()
    {
    	if(empty($_SESSION['wechat_user'])){
            $this->redirect('oauth/oauth');
        }
        $btj = Db::table('ien_tj_admin')->field('tj,name,status')->where('tj','6')->find();
        $this->assign('btj', $btj);
        $rtj = Db::table('ien_tj_admin')->field('tj,name,status')->where('tj','7')->find();
        $this->assign('rtj', $rtj);


        $btj_status = Db::table('ien_admin_user')->where('openid',$_SESSION['wechat_user']['original']['openid'])->value('btj_status');
        $rtj_status = Db::table('ien_admin_user')->where('openid',$_SESSION['wechat_user']['original']['openid'])->value('rtj_status');

        if ($_SESSION['wechat_user']['original']['openid']) {
        	$user = DB::table('ien_admin_user')->where('openid', $_SESSION['wechat_user']['original']['openid'])->find();
    		$this->assign('user', $user);
        }
       
        $this->assign('btj_status', $btj_status);
        $this->assign('rtj_status', $rtj_status);
    	return $this->fetch();
    }
    /**********ajax开关设置************/
    public function user_set_ajax()
    {
    	$data = input('param.');
    	if ($data['tj'] == 6) {
    		$upd['btj_status'] = $data['status'];
    	}elseif ($data['tj'] == 7) {
    		$upd['rtj_status'] = $data['status'];
    	}

    	$res = Db::table('ien_admin_user')->where('openid',$_SESSION['wechat_user']['original']['openid'])->update($upd);
    	if ($res) {
    		return 1;
    	}else{
    		return 0;
    	}


    }
}