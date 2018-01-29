<?php
/**
 * User: pinbo
 * Date: 2017/4/8
 * Time: 上午9:50
 */
//后台访问控制器
namespace app\agent\admin;//修改ann为模块名

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class Index extends Admin{



    public function index(){
		$table="ien_notice";
		$result = Db::table($table)->order('create_time desc')->select();
		 $btn = [
                'class' => 'btn btn-success ajax-post confirm',
                'icon'  => 'fa fa-check-circle-o',
                'title' => '查看',
                'href'  => url('index/show', ['id' => '__id__'])
            ];
			
		return ZBuilder::make('table')
			->hideCheckbox()
            ->addColumns([ // 批量添加数据列

			   ['create_time', '添加时间', 'datetime'],

               ['title', '标题'],
				
               ['right_button', '操作', 'btn']

            ])
			
			->addRightButton('custom',$btn,true)
            ->setRowList($result) // 设置表格数据

            ->fetch(); // 渲染模板

    }
	 public function show($id){
		$table="ien_notice";
		$result = Db::table($table)->where('id',$id)->find();
		echo "<h1>".$result['title']."</h1>";
		echo $result['content'];
		echo date('Y-m-d', $result['create_time']);
		

    }



}