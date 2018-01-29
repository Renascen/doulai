<?php
/**
 * User: pinbo
 * Date: 2017/4/8
 * Time: 上午9:50
 */
//后台访问控制器
namespace app\agent\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;
use EasyWeChat\Foundation\Application;
use Doctrine\Common\Cache\RedisCache;

class User extends Admin{
	public function index(){
		$map = $this->getMap();
		$user=DB::table('ien_admin_user')->where('id',UID)->find();
		if($user['role']==1)
		{
			//管理员查看所有,有问题.不能所有用户
			$datalist=DB::table('ien_admin_user')
			->where($map)
			->whereNotIn('role',1)
			->whereNotIn('role',3)
			->order('create_time desc')
			->paginate();

		}
		else{
			$map['did']=UID;
			$datalist=DB::table('ien_admin_user')
			->where($map)
			->order('create_time desc')
			->paginate();
		}
	
		//SELECT ien_admin_user.id,ien_admin_user.username,ien_admin_user.nickname,ien_admin_user.create_time,ien_admin_user.last_login_time,ien_admin_user.xingming,ien_admin_user.fcbl,a.czje FROM `ien_admin_user` LEFT JOIN (SELECT did,sum(money) as czje FROM `ien_pay_log` group by did) as a ON `a`.`did`=`ien_admin_user`.`id` ORDER BY create_time desc
		
        $btn_optionurl = [
            'title' => '代理登陆设置信息',
            'icon'  => 'fa fa-fw fa-key',
            'class' => 'btn btn-xs btn-default ',
            'href'  => url('optionurl', ['id' => '__id__']),
        ];
        $btn_dingdan = [
            'title' => '代理全部订单',
            'icon'  => 'fa fa-fw fa-shopping-cart',
            'class' => 'btn btn-xs btn-default ',
            'href'  => url('dingdan', ['id' => '__id__']),
        ];



		return ZBuilder::make('table')
			->hideCheckbox()
            ->addColumns([ // 批量添加数据列
              	['id', 'ID'],
				['username', '用户名'],
				['nickname','昵称'],
                ['role','用户组',['2'=>'代理商','4'=>'渠道商']]
				//['czje','充值金额'],
            ])
            ->addColumn('czje','推广充值金额','callback',function($data){
					$czje=DB::table('ien_pay_log')->where('did',$data['id'])->where('status','1')->sum('money');
					return $czje;
				},'__data__')
				
             ->addColumns([
           		['create_time','注册时间','datetime'],
				['last_login_time','最后登录时间','datetime'],
				['xingming','姓名'],
				['fcbl','分成比例'],
				['right_button', '操作', 'btn']
			])
			->addTopButton('add')
			->addRightButton('edit')
            ->addRightButton('custom', $btn_optionurl) 
            ->addRightButton('custom', $btn_dingdan) 
            ->setRowList($datalist) // 设置表格数据
            ->fetch(); // 渲染模板
		}
		
	public function add(){
		// 添加代理商
        if ($this->request->isPost()) {
            $data = $this->request->post();

		if ($data['username'] == '') {
            $this->error('用户名不能为空');
            return false;
        }

        if ($data['xingming'] == '') {
            $this->error('姓名不能为空');
            return false;
        }
        if ($data['did'] == '') {
            $this->error('出错了!联系管理员');
            return false;
        }
        if ($data['fcbl'] == '') {
            $this->error('分成比例不能为空');
            return false;
        }

        if ($data['fcbl'] != 0.1 && $data['fcbl'] != 0.2 && $data['fcbl'] != 0.3 && $data['fcbl'] != 0.4 && $data['fcbl'] != 0.5 && $data['fcbl'] != 0.6 && $data['fcbl'] != 0.7 && $data['fcbl'] != 0.8 && $data['fcbl'] != 0.9 ) {
            $this->error('分成比例写错啦!例:0.6,0.7,0.8,0.9');
            return false;
        }
        if ($data['fangshi'] == '') {
            $this->error('提现方式不能为空');
            return false;
        }
        if ($data['zhanghao'] == '') {
            $this->error('提现账号不能为空');
            return false;
        }
        $data['nickname']=$data['xingming'];
            if (false === DB::table('ien_admin_user')->insert($data)) {
                $this->error('创建失败');
            }
            $this->success('创建成功');
        }

        $user=DB::table('ien_admin_user')->where('id',UID)->find();
		if($user['role']==1)
		{
			//管理员可以添加其他级别	
			$list_jb = ['4' => '渠道商', '2' => '代理商'];
		}
		else if($user['role']==4){
			$list_jb = ['2' => '代理商'];

		}
          // 显示添加页面
        return ZBuilder::make('form')
        	->setPageTips('新建账号密码为"123456",请用户登录后自行修改!<br/>设置成功之后,在代理商管理中,点击代理登陆信息,复制发送给用户!')
            ->addFormItems([
                ['hidden', 'did', UID],
                ['hidden', 'status', 1],
                ['text', 'username', '用户名', '必填'],
                ['hidden', 'password', '$2y$10$cRk7.QQQr310e5jmBCbKrOEPuUC6WCpHKyYTsnA8NpxGAnhWjp8gq'],
                ['text', 'guanzhu', '关注章节', '选填,20章开始付费阅读'],
                ['text', 'ewm', '二维码链接', '选填'],
                ['text', 'fcbl', '分成比例', '必填,请填写格式:0.6'],
                ['text', 'xingming', '姓名', '必填,提现姓名'],
                ['text', 'fangshi', '提现方式', '必填,提现方式'],
                ['text', 'zhanghao', '提现账号', '必填,提现账号'],
				['hidden', 'create_time', $this->request->time()],
				['hidden', 'update_time', $this->request->time()],

            ])
            ->addSelect('role', '选择级别', '请选择代理级别', $list_jb, '2')
			->isAjax(true)
			->layout(['username' => 6, 'guanzhu' => 6, 'ewm' => 6, 'xingming' => 3, 'fangshi' => 3, 'zhanghao' => 3,'role' => 3, 'fcbl' => 6])
            ->fetch();

	}
	
		public function edit($id = null){
		if ($id === 0) $this->error('参数错误');
		// 添加代理商
        if ($this->request->isPost()) {
            $data = $this->request->post();

        if ($data['xingming'] == '') {
            $this->error('姓名不能为空');
            return false;
        }

        if ($data['fcbl'] == '') {
            $this->error('分成比例不能为空');
            return false;
        }

        if ($data['fcbl'] != 0.1 && $data['fcbl'] != 0.2 && $data['fcbl'] != 0.3 && $data['fcbl'] != 0.4 && $data['fcbl'] != 0.5 && $data['fcbl'] != 0.6 && $data['fcbl'] != 0.7 && $data['fcbl'] != 0.8 && $data['fcbl'] != 0.9 ) {
            $this->error('分成比例写错啦!例:0.6,0.7,0.8,0.9');
            return false;
        }
        if ($data['fangshi'] == '') {
            $this->error('提现方式不能为空');
            return false;
        }
        if ($data['zhanghao'] == '') {
            $this->error('提现账号不能为空');
            return false;
        }
        $data['nickname']=$data['xingming'];
            if (false === DB::table('ien_admin_user')->where('id',$data['id'])->update($data)) {
                $this->error('更新失败');
            }
            $this->success('更新成功');
        }

        $user=DB::table('ien_admin_user')->where('id',$id)->find();
          // 显示修改页面
        return ZBuilder::make('form')
        	->setPageTips('如果修改密码,请联系管理员!')
            ->addFormItems([
            	['hidden', 'id'],
                ['hidden', 'did'],
                ['hidden', 'status'],
                ['static', 'username', '用户名', '必填'],
                ['text', 'guanzhu', '关注章节', '选填,20章开始付费阅读'],
                ['text', 'ewm', '二维码链接', '选填'],
                ['text', 'fcbl', '分成比例', '必填,请填写格式:0.6'],
                ['text', 'xingming', '姓名', '必填,提现姓名'],
                ['text', 'fangshi', '提现方式', '必填,提现方式'],
                ['text', 'zhanghao', '提现账号', '必填,提现账号'],
				['hidden', 'update_time', $this->request->time()],

            ])
			->isAjax(true)
			->layout(['username' => 6, 'guanzhu' => 6, 'ewm' => 6, 'xingming' => 4, 'fangshi' => 4, 'zhanghao' => 4, 'fcbl' => 6])
			->setFormData($user)
            ->fetch();

	}
    //代理配置信息
    public function optionurl($id=null)
    {   

        $readurl='http://'.preg_replace("/\{\d\}/", $id, module_config('agent.agent_tuiguangurl')).'/index.php/cms/user/readold.html';
        $adminurl='http://'.preg_replace("/\{\d\}/", $id, module_config('agent.agent_dailiurl')).'/admin.php';
        return ZBuilder::make('form')
            ->setPageTips('请发给代理商下面内容.')
            ->addFormItems([
                ['text', 'readold', '阅读历史', '阅读历史链接,请在关注公众号添加此链接',$readurl],
                ['text', 'loginurl', '登陆链接', '代理商登陆链接',$adminurl],


            ])
            ->hideBtn('submit')
            ->fetch();
    }
	//代理全部订单
    public function dingdan($id=null)
    {
    	$map="";
    		$mapa = $this->getMap();
    		$key=array_keys($mapa);
    		$i=0;
    		foreach($mapa as $k=>$value)
    		{	
    			
    			$name="ien_".$key[$i];
    			$map[$name]=$value;
    			$i++;
    		}

        $user=DB::view('ien_pay_log')
            ->view('ien_admin_user','nickname','ien_admin_user.openid=ien_pay_log.uid')
            ->where('ien_pay_log.paytype <> 0')
            ->where('ien_pay_log.did',$id)->where($map)->order('ien_pay_log.addtime desc')->paginate();

            return ZBuilder::make('table')  
            ->hideCheckbox()
            ->setSearch(['admin_user.nickname' => '用户名'])
            ->addFilter('pay_log.paytype',['1'=>'VIP会员','2'=>'普通充值'])
            ->addFilter('pay_log.type',['1'=>'公众号支付','2'=>'第三方支付'])
            ->addFilter('pay_log.status',['1'=>'已支付','0'=>'未支付'])
            ->addTimeFilter('pay_log.addtime')
            ->addColumns([// 批量添加数据列

                ['payid', '订单ID','text'],
                ['paytype','订单类型',['1'=>'VIP会员','2'=>'普通充值']],
                ['nickname','用户'],
                ['money','充值金额'],
                ['type', '支付方式', ['1'=>'公众号支付']],
                ['status', '订单状态', ['1'=>'已支付','0'=>'未支付']],
                ['addtime', '添加时间', 'datetime'],

            ])
            ->setRowList($user) // 设置表格数据
            ->fetch(); // 渲染模板
    }
    //全部读者
    public function duzhe()
    {
    	$order = $this->getOrder();
    	$map = $this->getMap();
        $btn_dingdanmx = [
            'title' => '读者全部订单',
            'icon'  => 'fa fa-fw fa-shopping-cart',
            'class' => 'btn btn-xs btn-default ',
            'href'  => url('dingdanmx', ['id' => '__id__']),
        ];
        $datalist = DB::table('ien_admin_user')->where('role',3)->where($map)->order($order)->paginate();
            return ZBuilder::make('table')
            ->hideCheckbox()
            ->addTimeFilter('create_time')
            ->setSearch(['id' => 'ID', 'username' => '用户名', 'nickname' => '昵称'])
            ->addOrder('id,score')
            ->addColumns([ // 批量添加数据列
            	['id', 'ID'],
                ['username', '用户名'],
                ['nickname','昵称'],
                ['score','赠送书币','text.edit'],
                ['coin','充值书币'],
            ])
            ->setTableName('admin_user')
            ->addColumn('leiji','累计充值','callback',function($data){
                    $leiji=DB::table('ien_pay_log')->where('uid',$data['openid'])->where('status','1')->sum('money');
                    return $leiji;
                },'__data__')
                
             ->addColumns([
                ['create_time','注册时间','datetime'],
                ['last_login_time','最后登录时间','datetime'],
                ['right_button', '操作', 'btn']
            ])
            ->addRightButton('custom', $btn_dingdanmx) 
            ->setRowList($datalist) // 设置表格数据
            ->fetch(); // 渲染模板

    }
    //读者订单明细
    public function dingdanmx($id=null)
    {

    		$map="";
    		$mapa = $this->getMap();
    		$key=array_keys($mapa);
    		$i=0;
    		foreach($mapa as $k=>$value)
    		{	
    			
    			$name="ien_".$key[$i];
    			$map[$name]=$value;
    			$i++;
    		}

            $user=DB::view('ien_pay_log')
            ->view('ien_admin_user','nickname','ien_admin_user.openid=ien_pay_log.uid')
            ->where('ien_pay_log.paytype <> 0')
            ->where('ien_admin_user.id',$id)->where($map)->order('ien_pay_log.addtime desc')->paginate();


            return ZBuilder::make('table')  
            ->hideCheckbox()
            //->setSearch(['admin_user.nickname' => '用户名'])
            ->addFilter('pay_log.paytype',['1'=>'VIP会员','2'=>'普通充值'])
            ->addFilter('pay_log.type',['1'=>'公众号支付','2'=>'第三方支付'])
            ->addFilter('pay_log.status',['1'=>'已支付','0'=>'未支付'])
            ->addTimeFilter('pay_log.addtime')
            ->addColumns([// 批量添加数据列

                ['payid', '订单ID','text'],
                ['paytype','订单类型',['1'=>'VIP会员','2'=>'普通充值']],
                ['nickname','用户'],
                ['money','充值金额'],
                ['type', '支付方式', ['1'=>'公众号支付']],
                ['status', '订单状态', ['1'=>'已支付','0'=>'未支付']],
                ['addtime', '添加时间', 'datetime'],

            ])
            ->setRowList($user) // 设置表格数据
            ->fetch(); // 渲染模板
    }
    //全部订单明细
    public function dingdanall($id=null)
    {
    		$map="";
    		$mapa = $this->getMap();
    		$key=array_keys($mapa);
    		$i=0;
    		foreach($mapa as $k=>$value)
    		{	
    			
    			$name="ien_".$key[$i];
    			$map[$name]=$value;
    			$i++;
    		}
            $ien_admin_userSql =  Db::table('ien_admin_user')
            ->field('nickname,openid,id')
            ->buildSql();
            $ien_agentSql =  Db::table('ien_agent')
            ->field('name,id')
            ->buildSql();
            $ien_bookSql =  Db::table('ien_book')
            ->field('title,id')
            ->buildSql();
            // $user=DB::view('ien_pay_log')

            // ->view('ien_admin_user','nickname,id as userid','ien_admin_user.openid=ien_pay_log.uid')
            // ->view('ien_agent', 'name', 'ien_agent.id=ien_pay_log.tgid', 'left')
            // ->view('ien_book', 'title', 'ien_book.id=ien_pay_log.bookid', 'left')
            // ->where('ien_pay_log.paytype <> 0')->where($map)->order('ien_pay_log.addtime desc')->paginate();
            
            

            $user=DB::table('ien_pay_log','addtime,money,status')
            ->alias('a')
            ->join($ien_admin_userSql.' ien_admin_user','ien_admin_user.openid=a.uid')
            ->join($ien_agentSql.' ien_agent','ien_agent.id=a.tgid', 'LEFT')
            ->join($ien_bookSql.' d', 'd.id=a.bookid', 'LEFT')
            ->field('a.addtime,a.money,a.status,ien_admin_user.nickname,ien_admin_user.id userid,ien_agent.name,d.title')
            ->where('a.paytype <> 0')->where($map)->order('a.addtime desc')->paginate();
            // dump(DB::getLastSql());
            // exit;
            $today=DB::table('ien_pay_log')->where('status','1')->whereTime('paytime', 'today')->sum('money');

            if(empty($today)){
                $today = 0;
            }
            $allday=DB::table('ien_pay_log')->where('status','1')->sum('money');

            return ZBuilder::make('table')
            ->hideCheckbox()
            ->setPageTips('今日平台充值合计:'.$today.'元<Br>累计平台充值合计:'.$allday.'元')
            ->setSearch(['admin_user.nickname' => '用户名','admin_user.id' => '用户ID', 'agent.name'=> '渠道名'])
            ->addFilter('pay_log.paytype',['1'=>'VIP会员','2'=>'普通充值'])
            ->addFilter('pay_log.type',['1'=>'公众号支付','2'=>'第三方支付'])
            ->addFilter('pay_log.status',['1'=>'已支付','0'=>'未支付'])
            ->addTimeFilter('pay_log.addtime')
            ->addColumns([// 批量添加数据列

                ['addtime', '添加时间', 'datetime'],
                ['userid','用户ID'],
                ['nickname','用户'],
                ['money','充值金额'],
                // ['tgid','渠道ID'],
                ['name','渠道名称'],
                ['title','书名'],
                ['status', '订单状态', ['1'=>'<span style="color:#4bcc18">已支付</span>','0'=>'未支付']],
                // ['paytype','订单类型',['1'=>'VIP会员','2'=>'普通充值']],
                // ['type', '支付方式', ['1'=>'公众号支付','2'=>'第三方支付']],
                // ['payid', '订单ID','text']

            ])
            ->setRowList($user) // 设置表格数据
            ->fetch(); // 渲染模板
    }

    //修改配置信息
    public function uconfigedit($id = null){
        if ($id === 0) $this->error('参数错误');
        // 添加代理商
        if ($this->request->isPost()) {
            
            $data = $this->request->post();
            if(empty($data["isopen"]))
            {
            $data["isopen"]="0";
            }

        if(DB::table('ien_wechat_uconfig')->where('id',$data['id'])->find())
        {
            if (false === DB::table('ien_wechat_uconfig')->where('id',$data['id'])->update($data)) {
                $this->error('更新失败');
            }
        }
        else{
            if (false === DB::table('ien_wechat_uconfig')->insert($data)) {
                $this->error('更新失败');
            }

        }

            $this->success('更新成功');
        }


    }
    //添加配置信息
    public function uconfig(){
        $user="";

        $user=DB::table('ien_wechat_uconfig')->where('uid',UID)->find();
        if(empty($user['token']))
        {
            $user['token']=$this->getRandom("32");
        }

          // 显示修改页面
        return ZBuilder::make('form')
            ->setUrl(url('uconfigedit'))
            ->setPageTips('如果不进行对接,请关闭!对接后可提升用户关注精度. <br/>公众号服务器地址为  http://'.preg_replace("/\{\d\}/", UID, module_config('agent.agent_tuiguangurl')).'/index.php/wechat.html<br/>加入公众号请在公众号中添加IP白名单: '.$_SERVER['SERVER_ADDR'])
            ->addFormItems([
                ['hidden', 'id'],
                ['hidden', 'uid',UID],
                ['text', 'name', '公众号名称', '必填'],
                ['text', 'gid', '公众号原始ID', '必填'],
                ['text', 'wxh', '微信号', '必填'],
                ['text', 'appid', 'Appid', '必填'],
                ['text', 'appsecret', 'Appsecret', '必填'],
                ['text', 'token', 'token', '必填'],
                ['text', 'encodingaeskey', 'EncodingAESKey', '必填'],
            ])
            ->addSwitch('isopen', '是否开启')
            ->isAjax(true)
            ->layout(['name' => 6, 'gid' => 6, 'wxh' => 6, 'appid' => 4, 'appsecret' => 4, 'token' => 4, 'encodingaeskey' => 6])
            ->setFormData($user)
            ->fetch();

    }
    //随机字符串
    function getRandom($param){
    $str="0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $key = "";
    for($i=0;$i<$param;$i++)
     {
         $key .= $str{mt_rand(0,32)};    //生成php随机数
     }
     return $key;
 }

    //生成自定义菜单
    public function diymenu(){
        $data=module_config('agent.agent_diy_menu');
        $tgurl=preg_replace("/\{\d\}/", UID, module_config('agent.agent_tuiguangurl'));
        $rooturl=module_config('agent.agent_rooturl');
        $diymenu=str_replace($rooturl,$tgurl,$data);
        
        $userdl=DB::table('ien_wechat_uconfig')->where('uid',UID)->where('isopen',"on")->find();
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
                    
                    
                    // try {
                        $app = new Application($config);
                        $menu = $app->menu;
                        $return = $menu->add(json_decode($diymenu));
                        
                        if($return['errcode'] == 0){
                            $this->success('更新成功!');
                        }else
                        {
                            $this->success('更新失败,请先接入认证公众号!'.$return);
                        }
                    // } catch(\Exception $e){
                    //     $this->success('更新失败,请先接入认证公众号!'.$return);
                    // }

        } else {
            $this->success('请先接入认证公众号!');
        }
    }

    //模板消息
    public function teminfo(){
            $user=DB::table('ien_tem_info')->where('uid',UID)->paginate();

            $btnindex = [
                'class' => 'btn btn-primary confirm',
                'icon'  => 'fa fa-plus-circle',
                'title' => '新建模板消息',
                'href'  => url('user/teminfoadd')
            ];
             $btnorder = [
                'class' => 'btn btn-xs btn-default',
                'icon'  => 'fa fa-fw fa-forward',
                'title' => '发送',
                'href'  => url('user/tmsend', ['id' => '__id__'])
            ];

            return ZBuilder::make('table')  
            ->setPageTips('本功能只有对接认证服务号并且添加模板之后才可以使用!!!<br>推送给所有关注用户!<br>为接入成功或未添加模板报错!!!','danger')
            ->hideCheckbox()
            ->addColumns([// 批量添加数据列

                ['name', '任务名称','text'],
                ['issend','发送状态',['1'=>'已发送','0'=>'未发送']],
                ['temp','发送内容'],
                ['addtime', '添加时间', 'datetime'],
                ['right_button', '操作', 'btn']

            ])
            ->addTopButton('custom',$btnindex)
            ->addRightButton('custom',$btnorder)
            ->setRowList($user) // 设置表格数据
            ->fetch(); // 渲染模板

    }
    //新建模板消息
    public function teminfoadd(){

        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['temp']=$data['TaskValue'];

            if (false === DB::table('ien_tem_info')->insert($data)) {
                $this->error('创建失败');
            }
            $this->success('创建成功');
            
        }
        $userdl=DB::table('ien_wechat_uconfig')->where('uid',UID)->where('isopen',"on")->find();
        if(!empty($userdl) || UID==1)
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

                    //如果管理员的话,用平台服务号
                    if(UID==1)
                    {
                    $config2 = module_config('wechat');
                    }
                    $config2['cache']=$cacheDriver;
                    $config = array_merge($config, $config2);
                    $app = new Application($config);
                    $notice = $app->notice;
                    try{
                             $a=$notice->getPrivateTemplates();
                             }
                   catch(\Exception $e){
                      $this->success('公众号对接有误!');
                    }
                   foreach($a->template_list as $key=>$value)
                   {
                    
                   
                        if($key>0)
                        {

                        $data[$key]['name']=$value['title'];
                        $data[$key]['template_id']=$value['template_id'];
                        $data[$key]['content']=$value['content'];
                        }
                   }
                   $this->assign('tmp', $data);

            
        }
        else{
            $this->success('请先接入认证公众号!');
        }







        return ZBuilder::make('form')
            ->setPageTitle('请在认证服务号里面添加模板: 会员卡升级通知 TM405959619')// 设置页面标题
            ->addFormItems([
                ['text', 'name', '任务名称', '必填'],
                ['select', 'tmid', '模板ID', '必填' ,'','',''],
                //['text', 'title', '推送标题', '必填',"您获得一次七夕会员活动奖励,充77元送77元!"],
               // ['text', 'temp', '推送内容', '必填'],
                //['text', 'youxiao', '推送第三行', '必填',"活动截止日期:8月28日24点"],
               // ['text', 'yindao', '推送第四行', '必填',"点击立即参加活动~"],
                ['text', 'url', '跳转链接', '只可以本平台内链接'],
                //['datetime', 'sendtime', '发布时间', ''],
                ['hidden', 'uid', UID],
                ['hidden', 'issend', 0],
                ['hidden', 'addtime', $this->request->time()],


            ])
            //->addStatic('', '当前小说章节', '', $data_listxs['title'])
            ->isAjax(true)
            ->fetch('teminfoadd');

    }

    //发送模板消息
    public function tmsend($id=null)
    {

        $userdl=DB::table('ien_wechat_uconfig')->where('uid',UID)->where('isopen',"on")->find();
        if(!empty($userdl) || UID==1)
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

                    //如果管理员的话,用平台服务号
                    if(UID==1)
                    {
                    $config2 = module_config('wechat');
                    }
                    $config2['cache']=$cacheDriver;
                    $config = array_merge($config, $config2);
                    $app = new Application($config);
                    $notice = $app->notice;


                   // $a=$notice->getPrivateTemplates();
                   // dump($a);
                   // die;

                    $tminfo=DB::table('ien_tem_info')->where('id',$id)->find();
                    $userId = 'oZN9Q1NSkKFBCwnvCEKrN0jHHQ9Y';
                    $templateId = $tminfo['tmid'];
                    $url = $tminfo['url'];
                    $data = json_decode($tminfo['temp'],true);
                    
					
                    $userService = $app->user;
                    $users = $userService->lists();
                    $udata=$users->data['openid'];
                    foreach($udata as $key=>$value)
                    {
                      try{
                        $userId=$value;
                        $result = $notice->uses($templateId)->withUrl($url)->andData($data)->andReceiver($userId)->send();
                      }
                      catch(\Exception $e){
                        return true;
                      }
                      
                    }


                    DB::table('ien_admin_user')->where('id',$id)->update(['issend'=>1]);
                    $this->success('更新成功');
                   




            }
        else{
            $this->success('请先接入认证公众号!');
        }



    }

    //促销列表
    public function cuxiao(){
        $cuxiao=DB::table('ien_cuxiaolist')->whereTime('endtime','>',time())->paginate();
        $btnindex = [
                'class' => 'btn btn-primary confirm',
                'icon'  => 'fa fa-plus-circle',
                'title' => '新建促销',
                'href'  => url('user/cuxiaoadd')
            ];
        $btndel = [
                'class' => 'btn btn-primary confirm',
                'icon'  => 'fa fa-fw fa-remove',
                'title' => '删除促销',
                'href'  => url('user/cuxiaodelete',['id' => '__id__'])
            ];
        $btnedit = [
                'class' => 'btn btn-primary confirm',
                'icon'  => 'list-icon fa fa-pencil fa-fw',
                'title' => '修改促销',
                'href'  => url('user/cuxiaoedit',['id' => '__id__'])
            ];
        $btnpro = [
                'class' => 'btn btn-primary confirm',
                'icon'  => 'fa fa-plus-circle',
                'title' => '商品管理',
                'href'  => url('user/product',['id' => '__id__', 'leixing' => 2])
            ];

        return ZBuilder::make('table')  
            //->setPageTips('本功能只有对接认证服务号并且添加模板之后才可以使用!!!<br>推送给所有关注用户!<br>为接入成功或未添加模板报错!!!','danger')
            ->hideCheckbox()
            ->addColumns([// 批量添加数据列

                ['name', '活动名称','text'],              
                ['starttime', '开始时间', 'datetime'],
                ['endtime', '结束时间', 'datetime'],
                ['id','活动链接','callback', function($value){
        return "http://".preg_replace("/\{\d\}/", UID, module_config('agent.agent_tuiguangurl'))."/index.php/cms/pay/index/cxid/".$value;
    }],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButton('custom',$btnindex)
            ->addRightButton('custom',$btnpro)
            ->addRightButton('custom',$btnedit)
            ->addRightButton('custom',$btndel)
            ->setRowList($cuxiao) // 设置表格数据
            ->fetch(); // 渲染模板
    }
    //新增促销活动
    public function cuxiaoadd(){
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['starttime']=strtotime($data['starttime']);
            $data['endtime']=strtotime($data['endtime']);
            if (false === DB::table('ien_cuxiaolist')->insert($data)) {
                $this->error('创建失败');
            }
            $this->success('创建成功');
            
        }


        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'name', '促销名称', '必填'],
                ['datetime','starttime','活动开始时间','必填'],
                ['datetime','endtime','活动结束时间','必填'],

            ])
            ->isAjax(true)
            ->fetch();

    }
    public function cuxiaodelete($id=null)
    {
        Db::table('ien_cuxiaolist')->where('id',$id)->delete();
        $this->success('删除成功!');
    }

    public function cuxiaoedit($id=null)
    {
        if ($id === 0) $this->error('参数错误');

        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['starttime']=strtotime($data['starttime']);
            $data['endtime']=strtotime($data['endtime']);
            if (false === DB::table('ien_cuxiaolist')->update($data)) {
                $this->error('更新失败');
            }
            $this->success('更新成功');
            
        }

        $info=DB::table('ien_cuxiaolist')->where('id',$id)->find();
        return ZBuilder::make('form')
            ->addFormItems([
                ['hidden', 'id',$id],
                ['text', 'name', '促销名称', '必填'],
                ['datetime','starttime','活动开始时间','必填'],
                ['datetime','endtime','活动结束时间','必填'],

            ])
            ->setFormData($info)
            ->isAjax(true)
            ->fetch();
        
    }



        //商品列表
    public function product($id=null, $leixing = 1){
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        if(!empty($id))
        {
            $btnindex = [
                'class' => 'btn btn-primary confirm',
                'icon'  => 'fa fa-plus-circle',
                'title' => '新建商品',
                'href'  => url('user/proadd',['cxid' => $id, 'leixing' => $leixing])
            ];
            $cuxiao=DB::table('ien_cuxiao')->where('leixing', $leixing)->where('cxid',$id)->paginate();
        }
        else
        {
            $btnindex = [
                'class' => 'btn btn-primary confirm',
                'icon'  => 'fa fa-plus-circle',
                'title' => '新建商品',
                'href'  => url('user/proadd')
            ];
        $cuxiao=DB::table('ien_cuxiao')->where('leixing',1)->paginate();
        }
        
        $btndel = [
                'class' => 'btn btn-primary confirm',
                'icon'  => 'fa fa-fw fa-remove',
                'title' => '删除商品',
                'href'  => url('user/prodel',['id' => '__id__'])
            ];
        $btnedit = [
                'class' => 'btn btn-primary confirm',
                'icon'  => 'list-icon fa fa-pencil fa-fw',
                'title' => '修改商品',
                'href'  => url('user/proedit',['id' => '__id__'])
            ];

        return ZBuilder::make('table')  
            ->hideCheckbox()
            ->addColumns([// 批量添加数据列

                ['name', '商品名称','text'],              
                ['orderby', '排序', 'text'],
                ['money', '金额', 'text'],
                ['coin', '书币', 'text'],
                ['score', '赠送书币', 'text'],
                ['day', '天数', 'text'],
                ['titilea', '提示一', 'text'],
                ['titileb', '提示二', 'text'],
                ['titilec', '提示三', 'text'],
                ['left_title', '左侧标签', 'text'],
                ['offer_title', '右侧标签', 'text'],
                ['status', '默认选中', 'switch'],
                ['right_button', '操作', 'btn']
            ])
            ->setTableName('cuxiao')
            ->addTopButton('custom',$btnindex)
            ->addRightButton('custom',$btndel)
            ->addRightButton('custom',$btnedit)
            ->setRowList($cuxiao) // 设置表格数据
            ->fetch(); // 渲染模板
    }

     /**
     * 快速编辑
     * @param array $record 行为日志
     * @author 拼搏 <378184@qq.com>
     * @return mixed
     */
    // public function quickEdit($record = [])
    // {
    //     $id      = input('post.pk', '');
    //     $field   = input('post.name', '');
    //     $value   = input('post.value', '');
    //     $table = input('post.table', '');
    //     if ($table == 'ien_cuxiao') {
    //         $leixing = Db::table('ien_cuxiao')->field('leixing')->where('id',$id)->find();
    //         if ($value) {
    //             Db::table('ien_cuxiao')->where('leixing',$leixing['leixing'])->setField('status', 0);
    //             $res =  Db::table('ien_cuxiao')->where('id',$id)->setField('status', 1);
    //         }else{
    //             $res =  Db::table('ien_cuxiao')->where('id',$id)->setField('status', 0);
    //         }
    //         if($res){
    //             $this->success('修改成功');
    //         }else{
    //             $this->error('修改失败');
    //         }
    //     }
      
    // }
    //新增商品
    public function proadd($cxid=null, $leixing = 1){
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if (false === DB::table('ien_cuxiao')->insert($data)) {
                $this->error('创建失败');
            }
            $this->success('创建成功', cookie('__forward__'));
            
        }

        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'name', '商品名称', '必填'],
                ['text', 'money', '价格', '必填'],
                ['select', 'type', '活动类型', '必填',['1' => '书币', '2' => '年费'],1],
                ['text', 'day', '天数'],
                ['text', 'coin', '充值书币'],
                ['text', 'score', '赠送书币'],
                ['text', 'titilea', '前台显示第一行', '必填'],
                ['text', 'titileb', '前台显示第二行', '必填'],
                ['text', 'titilec', '前台显示第三行', '必填'],
                ['text', 'left_title', '左侧标签'],
                ['text', 'offer_title', '右侧标签'],
                ['hidden', 'leixing', $leixing],
                ['text', 'orderby', '排序'],
                ['hidden','cxid',$cxid]

            ])
            //->addStatic('', '当前小说章节', '', $data_listxs['title'])
            ->setTrigger('type', '1', 'coin')
            ->setTrigger('type', '1', 'score')
            ->setTrigger('type', '2', 'day')
            ->isAjax(true)
            ->fetch();

    }
    public function prodel($id=null)
    {
        Db::table('ien_cuxiao')->where('id',$id)->delete();
        $this->success('删除成功!', cookie('__forward__'));
    }
    public function proedit($id=null)
    {
        if ($id === 0) $this->error('参数错误');

        if ($this->request->isPost()) {
            $data = $this->request->post();
            if (false === DB::table('ien_cuxiao')->update($data)) {
                $this->error('更新失败');
            }
            $this->success('更新成功', cookie('__forward__'));
            
        }

        $info=DB::table('ien_cuxiao')->where('id',$id)->find();
        return ZBuilder::make('form')
            ->addFormItems([
                ['hidden', 'id',$id],
                ['text', 'name', '商品名称', '必填'],
                ['text', 'money', '价格', '必填'],
                ['select', 'type', '活动类型', '必填',['1' => '书币', '2' => '年费'],1],
                ['text', 'day', '天数'],
                ['text', 'coin', '充值书币'],
                ['text', 'score', '赠送书币'],
                ['text', 'titilea', '前台显示第一行', '必填'],
                ['text', 'titileb', '前台显示第二行', '必填'],
                ['text', 'titilec', '前台显示第三行', '必填'],
                ['text', 'left_title', '左侧标签'],
                ['text', 'offer_title', '右侧标签'],
                ['hidden', 'leixing'],
                ['text', 'orderby', '排序'],

            ])
            //->addStatic('', '当前小说章节', '', $data_listxs['title'])
            ->setTrigger('type', '1', 'coin')
            ->setTrigger('type', '1', 'score')
            ->setTrigger('type', '2', 'day')
            ->setFormData($info)
            ->isAjax(true)
            ->fetch();
        
    }



    public function firstcharge(){
        $subpro = DB::table('ien_cuxiao')->field('count(*) as num,cxid')->where('leixing', 3)->group('cxid')->buildSql();
        $allactive = DB::table('ien_cuxiaolist')
        ->field('ifnull(temp.num, 0) as num,ien_cuxiaolist.*')
        ->join($subpro . ' temp', 'ien_cuxiaolist.id=temp.cxid', 'left')
        ->where('ien_cuxiaolist.type=1')
        ->select();
        $noproid = array();
        foreach ($allactive as $key => $value) {
            if(empty($value['num'])){
                $noproid[] = $value['id'];
            }
        }
        //活动过期自动关闭
        $overdue = DB::table('ien_cuxiaolist')
        ->where(['endtime' => ['<',time()]])
        ->whereOr(['id' => ['in', implode(',', $noproid)]])
        ->update(['status' => 0]);

        $subsql = DB::table('ien_free_limit_book')->field('count(*) as num,xsid')->where('type',1)->group('xsid')->buildSql();
        $firstcharge=DB::table('ien_cuxiaolist')
        ->where(['ien_cuxiaolist.type' => 1, 'is_delete' => 0])
        ->join($subsql . ' temp', 'ien_cuxiaolist.id=temp.xsid', 'left')
        ->field('ien_cuxiaolist.*,ifnull(temp.num,0) as num')
        ->order('status desc,createtime desc')
        ->paginate();
        $btnindex = [
                'class' => 'btn btn-primary confirm',
                'icon'  => 'fa fa-plus-circle',
                'title' => '新建活动',
                'href'  => url('user/fcadd')
            ];
        $btndel = [
                'class' => 'btn btn-primary confirm ajax-get',
                'icon'  => 'fa fa-fw fa-remove',
                'title' => '删除',
                'href'  => url('user/del',['id' => '__id__', 'table' => 'cuxiaolist']),
                'data-tips' => '删除后无法恢复。'
            ];
        $btnedit = [
                'class' => 'btn btn-primary confirm',
                'icon'  => 'list-icon fa fa-pencil fa-fw',
                'title' => '修改',
                'href'  => url('user/fcedit',['id' => '__id__'])
            ];
        $btnpro = [
                'class' => 'btn btn-primary confirm',
                'icon'  => 'fa fa-plus-circle',
                'title' => '商品管理',
                'href'  => url('user/product',['id' => '__id__', 'leixing' => 3])
            ];
        $btnAdd = [
                'title' => '添加小说',
                'icon'  => 'fa fa-plus-circle',
                'class' => 'btn btn-primary confirm',
                'href'  => url('user/bookadd', ['id' => '__id__'])
        ];
        return ZBuilder::make('table')  
            //->setPageTips('本功能只有对接认证服务号并且添加模板之后才可以使用!!!<br>推送给所有关注用户!<br>为接入成功或未添加模板报错!!!','danger')
            ->hideCheckbox()
            ->addColumns([// 批量添加数据列

                ['name', '活动名称','text'],              
                ['starttime', '开始时间', 'datetime'],
                ['endtime', '结束时间', 'datetime'],
                ['num', '适用小说', 'link',url('free/booklist', ['id' => '__id__', 'type' => '__type__'])],
                ['status', '状态', 'switch'],
                ['createtime', '创建时间', 'datetime'],
                ['right_button', '操作', 'btn'],
            ])
            ->setTableName('cuxiaolist')
            ->addTopButton('custom',$btnindex)
            ->addRightButton('custom',$btnAdd)
            ->addRightButton('custom',$btnpro)
            ->addRightButton('custom',$btnedit)
            ->addRightButton('custom',$btndel)
            ->setRowList($firstcharge) // 设置表格数据
            ->fetch(); // 渲染模板
    }
    //新增促销活动
    public function fcadd(){
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['starttime']=strtotime($data['starttime']);
            $data['endtime']=strtotime($data['endtime']);
            $data['createtime']=time();
            $data['type'] = 1;
            $data['status'] = 0;
            if(empty($data['starttime']) || empty($data['endtime']) || empty($data['name'])){
                $this->error('必填项不能为空');
            }
            if (false === DB::table('ien_cuxiaolist')->insert($data)) {
                $this->error('创建失败');
            }
            $this->success('创建成功', url('user/firstcharge'));
            
        }


        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'name', '促销名称', '必填'],
                ['text', 'active_page_title', '充值副标题', '选填'],
                ['datetime','starttime','活动开始时间','必填'],
                ['datetime','endtime','活动结束时间','必填'],

            ])
            ->isAjax(true)
            ->fetch();

    }

    public function fcedit($id=null)
    {
        if ($id === 0) $this->error('参数错误');

        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['starttime']=strtotime($data['starttime']);
            $data['endtime']=strtotime($data['endtime']);
            $data['type'] = 1;
            if(empty($data['starttime']) || empty($data['endtime']) || empty($data['name'])){
                $this->error('必填项不能为空');
            }
            if (false === DB::table('ien_cuxiaolist')->update($data)) {
                $this->error('更新失败');
            }
            $this->success('更新成功', url('user/firstcharge'));
            
        }
        $info=DB::table('ien_cuxiaolist')->where('id',$id)->find();
        $info['starttime'] = date('Y-m-d H:i:s',$info['starttime']);
        $info['endtime'] = date('Y-m-d H:i:s',$info['endtime']);
        return ZBuilder::make('form')
            ->addFormItems([
                ['hidden', 'id',$id],
                ['text', 'name', '促销名称', '必填'],
                ['text', 'active_page_title', '充值副标题', '选填'],
                ['datetime','starttime','活动开始时间','必填'],
                ['datetime','endtime','活动结束时间','必填'],

            ])
            ->setFormData($info)
            ->isAjax(true)
            ->fetch();
        
    }

    /**
     * 添加活动适用书籍
     * @return  [description]
     */
    public function bookadd($id = null)
    {
        if ($id === 0) $this->error('参数错误');
        $this->assign('id', $id);
        $map = $this->getMap();
         if ($this->request->isPost()) {
            $bid = input('post.bid/a');//书id
            $ids = input('param.ids');//限时活动表id
            $insertData = [];
            $insertData['type'] = 1;
            foreach ($bid as $k => $v) {
                $insertData['xsid'] = $ids;
                $insertData['bid'] = $v;
                $num = Db::table('ien_free_limit_book')->insert($insertData);
            }
            if ($num != 0) {
                return 1;
            }else{
                return 0;
            }

            // $old_bids = Db::table('ien_free_limit')->field('bids')->where('id','eq',$ids)->find();
            // if ($old_bids['bids'] == 0 ) {//没有记录则直接改字段
            //     $bids = implode(',', $bid);
            //     if (false === Db::table('ien_free_limit')->where('id','eq',$ids)->update(['bids'=>$bids])) {
            //         return 0;
            //     }
            //     return 1;
            // }else{//有记录则先取出再合并插入
            //     $old_bids = explode(',', $old_bids['bids']);
            //     $bidArr = array_merge($bid,$old_bids);
            //     $bids = implode(',', $bidArr);
            //     if (false === Db::table('ien_free_limit')->where('id','eq',$ids)->update(['bids'=>$bids])) {
            //         return 0;
            //     }
            //     return 1;
            // }
         
            
        }
        // $old_bids = Db::table('ien_free_limit')->field('bids')->where('id','eq',$id)->select();
        $data_list = Db::table('ien_free_limit_book')->field('bid')->where('xsid','eq',$id)->select();      
        $arr = array_column($data_list,'bid');  
        $bids = implode(',', $arr);
        $book=DB::table('ien_book book')
            ->where($map)
            // ->where('id','not in',$old_bids[0]['bids'])
            ->where('id','not in',$bids)
            ->where('status',1)
            ->order('zhishu desc')->paginate();
      $html = <<<EOF
                       <a class="btn btn-success btn_min" id="pass">确定</a>
EOF;
    $js = <<<EOF
            <script type="text/javascript">
                $("#pass").click(function(){
                    var cId = [];
                    $('.ids').each(function(){
                        if($(this).is(':checked')){
                            cId.push($(this).val());
                        }
                    });
                    $.ajax({  
                        url: "{:url('bookadd')}",
                        type: 'POST',
                        data:{"bid":cId,"ids":{$id}},
                        dataType: 'text',
                        error:function(data){
                            console.log(data);
                        },
                        success:function(data){       
                            if (data == 1) {
                                  layer.msg('保存成功！');
                             window.location.reload(); 
                            }      
                        }
                    });
                });
            </script>
EOF;
        return ZBuilder::make('table')
            ->addColumns([ // 批量添加数据列
                ['zuozhe', '作者'],
                ['title', '书名'],
                ['cid','类别','text', '', ['2'=>'男生','3'=>'女生']],
                ['zishu', '字数'],
                ['xstype', '完结状态','text', '', ['0'=>'连载中','1'=>'已完结']],
            ])
            ->setTableName('book')
            ->setSearch(['zuozhe' => '作者', 'title' => '书名'])
            ->addFilter('cid', ['2'=>'男生','3'=>'女生'])
            ->setExtraHtml($html, 'toolbar_top')
            ->setExtraJs($js)
            ->setRowList($book) // 设置表格数据
            ->fetch(); // 渲染模板
    }
    public function booklist($id=null, $type = 0)
    {
        if ($id === 0) $this->error('参数错误');
        $map = $this->getMap();
        // $data_list = Db::table('ien_free_limit')->field('bids')->where('id','eq',$id)->find();
        $data_list = Db::table('ien_free_limit_book')->field('bid')->where(['xsid' => $id, 'type' => $type])->select();      
        $arr = array_column($data_list,'bid');  
        $bids = implode(',', $arr);
        // $book=DB::table('ien_book')
        // ->where($map)
        // // ->where('id','in',$data_list['bids'])
        // ->where('id','in',$bids)
        // ->order('id desc')->paginate();

        $book=DB::table('ien_free_limit_book')
        ->alias('a')
        ->join('ien_book b','a.bid=b.id','LEFT')
        ->field('a.sort,zuozhe,title,cid,zishu,xstype,a.bid,a.id,zhishu')
        ->where($map)
        // ->where('id','in',$data_list['bids'])
        ->where('bid','in',$bids)
        ->where('xsid','eq',$id)
        ->order('a.sort desc,a.bid desc')->paginate();

        return ZBuilder::make('table')
        ->hideCheckbox()
        ->addColumns([ // 批量添加数据列
            ['bid', '书ID'],
            ['title', '书名'],
            ['zuozhe', '作者'],
            ['cid','类别','text', '', ['2'=>'男生','3'=>'女生']],
            ['zishu', '字数'],
            ['xstype', '完结状态','text', '', ['0'=>'连载中','1'=>'已完结']],
            ['sort','排序','text.edit'],
            ['right_button', '操作', 'btn']
        ])
        ->setTableName('free_limit_book')
        ->addRightButtons(['delete' => ['data-tips' => '删除后无法恢复。']]) // 批量添加右侧按钮
        ->setRowList($book) // 设置表格数据
        ->fetch(); // 渲染模板


        // return ZBuilder::make('table')
        // ->hideCheckbox()
        // ->addColumns([ // 批量添加数据列
        //     ['zuozhe', '作者'],
        //     ['title', '书名'],
        //     ['cid','类别','text', '', ['2'=>'男生','3'=>'女生']],
        //     ['zishu', '字数'],
        //     ['xstype', '完结状态','text', '', ['0'=>'连载中','1'=>'已完结']],
        // ])
        // ->setTableName('ien_book')
        // ->setSearch(['zuozhe' => '作者', 'title' => '书名'])
        // ->addFilter('cid', ['2'=>'男生','3'=>'女生'])
        // ->setRowList($book) // 设置表格数据
        // ->fetch(); // 渲染模板
    }

}