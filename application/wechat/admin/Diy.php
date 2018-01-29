<?php

namespace app\wechat\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\wechat\model\Diy as DiyModel;
use util\Tree;
use think\Db;

/*
 * 自动回复管理
 */

class Diy extends Admin
{
    use \app\wechat\Base;

    // 列表
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 获取查询条件
        $map = $this->getMap();

        // 数据列表
        $data_list = DB::table('ien_wechat_diy')->where($map)->column(true);
		if (empty($map)) {
            $data_list = Tree::config(['title' => 'name'])->toList($data_list);
        }
		 $btnCreate = [
            'class' => 'btn btn-primary',
            'icon'  => 'fa fa-fw fa-plus',
            'title' => '生成自定义菜单',
            'href'  => url('create')
        ];

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTitle('自定义菜单')// 设置页面标题
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['name', '栏目名称', 'callback', function($value, $data){
                   return isset($data['title_prefix']) ? $data['title_display'] : $value;
               }, '__data__'],
                ['type', '类型',['1'=>'关键词','2'=>'链接']],
                ['orderby', '排序'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButtons('add,delete')// 批量添加顶部按钮
			->addTopButton('custom',$btnCreate)
            ->addRightButtons('edit,delete')// 批量添加右侧按钮
            ->setRowList($data_list)// 设置表格数据
            ->fetch(); // 渲染页面
    }
	
	function add(){
		// 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();
			if (false === DB::table('ien_wechat_diy')->insert($data)) {
                $this->error('创建失败');
            }
            $this->success('创建成功');
			
		}
		
		 // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
				['text', 'pid', '上级菜单ID', '必填'],
                ['text', 'name', '标题', '必填'],
                ['radio', 'type', '触发类型', '必填' ,['1'=>'关键词','2'=>'链接'],1],
				['text', 'content', '内容', '关键词写触发关键词，链接写跳转链接http://开头'],
				['text', 'orderby', '排序', '必填'],


            ])
			->isAjax(true)
            ->fetch();
		}
		
		function edit($id=null){
		if ($id === 0) $this->error('参数错误');
		// 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();
			if (false === DB::table('ien_wechat_diy')->update($data)) {
                $this->error('更新失败');
            }
            $this->success('更新成功');
			
		}
		$info=DB::table('ien_wechat_diy')->where('id',$id)->find();
		 // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
				['hidden','id',$id],
				['text', 'pid', '上级菜单ID', '必填'],
                ['text', 'name', '标题', '必填'],
                ['radio', 'type', '触发类型', '必填' ,['1'=>'关键词','2'=>'链接'],1],
				['text', 'content', '内容', '关键词写触发关键词，链接写跳转链接http://开头'],
				['text', 'orderby', '排序', '必填'],


            ])
			->setFormData($info)
			->isAjax(true)
            ->fetch();
		}
		
		function create(){
			
			$buttons="";
			$info=DB::table('ien_wechat_diy')->select();
			
			
			
	$jsonmenu = '[';
	 
	 
	 $sum=DB::table('ien_wechat_diy')->where('pid',0)->select();
	 if(count($sum)<=3) $sum=count($sum); else $sum=3;
	 
	 
	 $menu=DB::table('ien_wechat_diy')->where('pid',0)->limit(3)->select();
	 
	 foreach($menu as $val)
	 {
		  $menuej=DB::table('ien_wechat_diy')->where('pid',$val['id'])->limit(5)->select();
		  if(empty($menuej))
		  {
				  if($val['type']==1)
				$jsonmenu .= '
				   {"name":"'.$val['name'].'",
					"type":"click",
					"key":"'.$val['content'].'"},';
				  else if($val['type']==2){
					   $jsonmenu .= '
				   {"name":"'.$val['name'].'",
					"type":"view",
					"url":"'.$val['content'].'"},';
					  }	  
			}
			else{
					$jsonmenu .= '
				   {"name":"'.$val['name'].'",
				   "sub_button":[';
					foreach($menuej as $valej)
					{
						if($valej['type']==1){
							$jsonmenu.='{
					   "type":"click",
					   "name":"'.$valej['name'].'",
					   "key":"'.$valej['content'].'"
					},';}
					else if($valej['type']==2){
							$jsonmenu.='{
					   "type":"view",
					   "name":"'.$valej['name'].'",
					   "url":"'.$valej['content'].'"
					},';}
						
					}

				$jsonmenu=substr($jsonmenu,0,strlen($jsonmenu)-1);
			$jsonmenu .=']},';
			}
			
	
		 
	 }

		 
		$jsonmenu=substr($jsonmenu,0,strlen($jsonmenu)-1);
	    $jsonmenu.=']';
		$jsm=json_decode($jsonmenu,true);
		$menu = $this->app->menu;
		$return=$menu->add($jsm);
		if($return['errmsg']=="ok")
		$this->success('更新成功');
		else
		$this->success('更新失败');
		
		
	}
			


    
}