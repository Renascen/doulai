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
		



		return ZBuilder::make('table')
			->hideCheckbox()
            ->addColumns([ // 批量添加数据列
				['username', '用户名'],
				['nickname','昵称'],
				//['czje','充值金额'],
            ])
            ->addColumn('czje','推广充值金额','callback',function($data){
					$czje=DB::table('ien_pay_log')->where('did',$data['id'])->sum('money');
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
        	->setPageTips('新建账号密码为"123456",请用户登录后自行修改!')
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

		
	
	
}