<?php
/**
 * User: pinbo
 * Date: 2017/4/8
 * Time: 上午9:50
 */
//后台访问控制器
namespace app\agent\admin;//修改ann为模块名

use app\admin\controller\Admin;
use app\user\controller\Index;
use app\common\builder\ZBuilder;
use think\Db;

class My extends Admin{

    public function index(){
		$table="ien_admin_user";
		$result = Db::table($table)->where('id',UID)->select();
			
		return ZBuilder::make('table')
			->hideCheckbox()
            ->addColumns([ // 批量添加数据列

			   ['xingming', '姓名'],

               ['fangshi', '结算方式'],
			   
			   ['zhanghao', '结算账号'],
				

            ])
            ->setRowList($result) // 设置表格数据

            ->fetch(); // 渲染模板

    }
	



}