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
use app\agent\model\Novel as NovelModel;
use app\agent\model\Agent as AgentModel;
use think\Db;

class Agent extends Admin{
	
	function index(){
		$map           = $this->getMap();
		$map['uid']=UID;
		$usercount="";
		$usermoney="";
		//单独拼接组合查询
        $data_list = AgentModel::where($map)->order('id desc')->paginate();
		$data=array();
		foreach($data_list as $key=>$value)
		{
			$data[$key]['click']=$value['click'];
			$data[$key]['name']=$value['name'];
			$data[$key]['id']=$value['id'];
			if($value['ljlx']==3)
			{
			$data[$key]['url']="http://".$_SERVER['SERVER_NAME']."/index.php/cms/index/index/?t=".$value['id'];}
			else{
			$data[$key]['url']="http://".$_SERVER['SERVER_NAME']."/index.php/cms/document/detail/id/".$value['zid'].".html?t=".$value['id'];}
			
			$data[$key]['count']=DB::table('ien_admin_user')->where('tgid',$value['id'])->count();
			$data[$key]['money']=DB::view('ien_admin_user')
			->where('ien_admin_user.tgid',$value['id'])
			->view('ien_pay_log','money','ien_admin_user.openid=ien_pay_log.uid')
			->where('ien_pay_log.status',1)
			->sum('ien_pay_log.money');
			
			}
			
		
		// 自定义按钮
            $btnindex = [
                'class' => 'btn btn-primary confirm',
                'icon'  => 'fa fa-plus-circle',
                'title' => '添加首页推广链接',
                'href'  => url('agent/addindex')
            ];
			 $btnorder = [
                'class' => 'btn btn-xs btn-default',
                'icon'  => 'fa fa-fw fa-shopping-cart',
                'title' => '订单明细',
                'href'  => url('agent/order', ['id' => '__id__'])
            ];
			 $btnwxedit = [
                'class' => 'btn btn-xs btn-default',
                'icon'  => 'fa fa-fw fa-edit',
                'title' => '文案',
                'href'  => url('agent/wxedit', ['id' => '__id__']),
				'target' => '_blank',
            ];
		return ZBuilder::make('table')
			
			->hideCheckbox()
            ->addColumns([ // 批量添加数据列

				['name', '推广名称'],
				['url','入口页面'],
                ['click','点击'],
				['count','注册用户数'],
				['money','充值金额'],
				['right_button', '操作', 'btn']

            ])
			
			->addTopButton('custom',$btnindex)
			->addRightButton('custom',$btnorder)
			->addRightButton('custom',$btnwxedit)
			->addRightButton('edit')
			->addRightButton('delete')
            ->setRowList($data) // 设置表格数据
            ->fetch(); // 渲染模板
		
		
		
		}
		//微信文案创建
	function wxcreate($id = null){
		if ($id === 0) $this->error('参数错误');
		$nextid=$this->nextchapter($id);
		$leixing=$this->getleixing($id);
		$title=$this->getbook($id);
		$this->assign('title', $title);
		$this->assign('id', $id);
		$this->assign('nextid', $nextid);
		$this->assign('leixing', $leixing);
		return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'name', '派单渠道名称', '必填'],
                ['radio', 'gzh', '派单渠道类型', '必填' ,['1'=>'认证公众号','0'=>'未认证公众号'],1],
                ['hidden', 'uid', UID],
                ['hidden', 'zid', $id],
                ['hidden', 'ljlx', 1],
                ['hidden', 'titleid',0],
                ['hidden', 'imageid',0],
				['hidden', 'tempid',0],
                ['hidden', 'footid',0],
				['hidden', 'create_time', $this->request->time()],
				['hidden', 'update_time', $this->request->time()],

            ])
			//->addStatic('', '当前小说章节', '', $data_listxs['title'])
			
			->isAjax(true)
            ->fetch('wxcreate');
		
		
		
		
		
		
		}
	//ajax 下一章
	function nextchapter($id = null){
		if ($id === 0) $this->error('参数错误');
		$chapter=DB::table('ien_chapter')->where('id',$id)->find();
		$chapternextidx=$chapter['idx']+1;
		$data=Db::query('select id from ien_chapter where bid='.$chapter['bid'].' and idx='.$chapternextidx);
		return $data;
		}
	//ajax 类型查询
	function getleixing($id = null){
		if ($id === 0) $this->error('参数错误');
		$chapter=DB::table('ien_chapter')->where('id',$id)->find();
		$data=DB::table('ien_book')->where('id',$chapter['bid'])->find();
		return $data;
		}
		
	//ajax保存文案推广链接
	function savewa($id=null,$type=null,$article_id=null,$referrer_type=null,$force_follow_chapter_idx=null,$description=null,$wx_article_title_id=null,$wx_article_cover_id=null,$wx_article_body_template_id=null,$wx_article_footer_template_id=null){
	
		$data['uid']=UID;
		$data['zid']=$article_id;
		$data['titleid']=$wx_article_title_id;
		$data['imageid']=$wx_article_cover_id;
		$data['tempid']=$wx_article_body_template_id;
		$data['footid']=$wx_article_footer_template_id;
		$data['name']=$description;
		$data['gzh']=$referrer_type;
		$data['create_time']=time();	
		$data['update_time']=time();	
		$data['ljlx']=2;
		$res['id']=DB::table('ien_agent')->insertGetId($data);
		$res['url']="http://".$_SERVER['SERVER_NAME']."/index.php/cms/document/detail/id/".$data['zid'].".html?t=".$res['id'];
		if($res['id'])
		{
			return $res;
			}
		else{return false;}
		
		}
	//ajax 获取推广记录-没写完
	function getagent($id=null){
		if ($id === 0) $this->error('参数错误');
		$map['id']=$id;
		$data=DB::table('ien_agent')->where($map)->find();
		return $data;
		}
		
	//ajax 上一章id
	function getpre($id)
	{
		if ($id === 0) $this->error('参数错误');
		$map['id']=$id;
		$data=DB::table('ien_chapter')->where($map)->find();
		$idx=$data['idx']-1;
		$mapa['idx']=$idx;
		$mapa['bid']=$data['bid'];
		$res=DB::table('ien_chapter')->where($mapa)->find();
		return $res;
		}	
	//微信文案编辑
	function wxedit($id=null)
	{
		if ($id === 0) $this->error('参数错误');
		$agent=DB::table('ien_agent')->where('id',$id)->find();
		$this->assign('agent', $agent);
		$preactirle=$this->getpre($agent['zid']);
		$this->assign('preactirle', $preactirle);
		$this->assign('id', $id);
		$book=DB::table('ien_book')->where('id',$preactirle['bid'])->find();
		$this->assign('book', $book);
		$url="http://".$_SERVER['SERVER_NAME']."/index.php/cms/document/detail/id/".$agent['zid'].".html?t=".$id;
		$this->assign('url', $url);

		return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'name', '派单渠道名称', '必填'],
            ])
			//->addStatic('', '当前小说章节', '', $data_listxs['title'])
			
			->isAjax(true)
            ->fetch('wxedit');
		
		}
		
	//ajax 调用标题
	function gettitle($id = null)
	{
		//if ($id === 0) $this->error('参数错误');
		$leixing=$this->getleixing($id);
		$map['fenlei']=0;
		$map['leixing']=$id;
		$data=DB::table('ien_fodder')->where($map)->select();
		foreach($data as $key=>$value )
		{
			$datab[$key]['id']=$value['id'];
			$datab[$key]['category_id']=$value['leixing'];
			$datab[$key]['title']=$value['title'];
			$datab[$key]['created_at']=$value['create_time'];
		}
		return $datab;
		}
	
	//ajax 调用图片
	function getimage($id = null){
		//if ($id == "") $this->error('参数错误');
		//$leixing=$this->getleixing($id);
		$map['fenlei']=1;
		$map['leixing']=$id;
		$data=DB::table('ien_fodder')->where($map)->select();
		foreach($data as $key=>$value )
		{
			$datab[$key]['id']=$value['id'];
			$datab[$key]['category_id']=$value['leixing'];
			$datab[$key]['cover_url']="http://".$_SERVER['SERVER_NAME']."/public/static/agent/image/".$value['title'];
			$datab[$key]['created_at']=$value['create_time'];
		}
		return $datab;
		
		
		}
	//ajax获取小说信息
	function getbook($id = null)
	{
		if ($id === 0) $this->error('参数错误');
		$map['id']=$id;
		$data=DB::table('ien_chapter')->where($map)->find();
		$mapb['id']=$data['bid'];
		$datab=DB::table('ien_book')->where($mapb)->find();
		return $datab;
	}
	
	//ajax 文章信息
	function getactirle($id = null){
		if ($id === 0) $this->error('参数错误');
		$book=$this->getbook($id);
		$image="http://".$_SERVER['SERVER_NAME'].get_thumb($book['image']);
		$chapter=DB::table('ien_chapter')->where('id',$id)->find();
		$data=array('id'=>$id,'title'=>$chapter['title'],'novel'=>array('id'=>$book['id'],'title'=>$book['title'],'avatar'=>$image));
		return json($data);
		
		
		}
	
	
	//ajax 调用底部
	function getfooter()
	{
		$map['fenlei']=3;
		$data=DB::table('ien_fodder')->where($map)->select();
		foreach($data as $key=>$value )
		{
			$datab[$key]['id']=$value['title'];
			$datab[$key]['preview_img'] =  "http://".$_SERVER['SERVER_NAME']."/public/static/agent/image/".$value['title'].".jpg";
			$datab[$key]['template']=$value['content'];
		}
		
		
		return $datab;
		}
	
	//ajax 调用模板
	function gettemp(){
		
		$map['fenlei']=2;
		$data=DB::table('ien_fodder')->where($map)->select();
		foreach($data as $key=>$value )
		{
			$datab[$key]['id']=$value['title'];
			$datab[$key]['preview_img'] =  "http://".$_SERVER['SERVER_NAME']."/public/static/agent/image/".$value['title'].".jpg";
			$datab[$key]['template']=$value['content'];
		}
		
		
		return $datab;
		
		}
	
	//ajax 调用文章内容
	function getcontent($id = null)
	{
		if ($id === 0) $this->error('参数错误');
		$chapter=DB::table('ien_chapter')->where('id',$id)->find();
		if($chapter['idx']>5)
		{
			return false;
		}
		$data=Db::query('select id,idx,title,content as paragraphs from ien_chapter where bid='.$chapter['bid'].' and idx<='.$chapter['idx'].' order by idx asc');
		//$data=json_decode(json_encode($data),true);
		foreach($data as $key=>$value )
		{
			
			$data[$key]['paragraphs'] = explode("<br />&nbsp;&nbsp;&nbsp;&nbsp;",$value['paragraphs']);
			
			
		}
		return json($data);	
		
		}
	
	
	//创建生成链接
   function linkcreate($id = null){
	   if ($id === 0) $this->error('参数错误');
	   // 保存文档数据
        if ($this->request->isPost()) {
            $data = $this->request->post();
			
		if ($data['name'] == '') {
            $this->error('标题不能为空');
            return false;
        }
            if (false === DB::table('ien_agent')->insert($data)) {
                $this->error('创建失败');
            }
            $this->success('创建成功');
        }
		
		$data_zj = DB::table('ien_chapter')->where('id',$id)->find();
		$data_xs = DB::table('ien_book')->where('id',$data_zj['bid'])->find();
		$this->assign('data_zj',$data_zj);
		$this->assign('data_xs',$data_xs);
		  // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'name', '派单渠道名称', '必填'],
                ['radio', 'gzh', '派单渠道类型', '必填' ,['1'=>'认证公众号','0'=>'未认证公众号'],1],
                ['hidden', 'uid', UID],
                ['hidden', 'zid', $id],
                ['hidden', 'ljlx', 1],
                ['hidden', 'titleid',0],
                ['hidden', 'imageid',0],
				['hidden', 'tempid',0],
                ['hidden', 'footid',0],
				['hidden', 'create_time', $this->request->time()],
				['hidden', 'update_time', $this->request->time()],

            ])
			//->addStatic('', '当前小说章节', '', $data_listxs['title'])
			
			->isAjax(true)
            ->fetch('linkcreate');
	   
	   
	   
	   }
	   
	   //编辑标题
	   function edit($id=null){
		   if ($id === 0) $this->error('参数错误');
		   if ($this->request->isPost()) {
            $data = $this->request->post();
					
			
            if ($model = AgentModel::update($data)) {
               // Cache::clear();
                $this->success('新增成功');
            } else {
                $this->error('新增失败');
            }
			}
		   $info = AgentModel::get($id);
		   $this->assign('info',$info);
		   return ZBuilder::make('form')
            ->addFormItems([
				['hidden','id',$id],
                ['text', 'name', '派单渠道名称', '必填'],
                ['radio', 'gzh', '派单渠道类型', '必填' ,['1'=>'认证公众号','0'=>'未认证公众号']],
				['hidden', 'update_time', $this->request->time()],

            ])
			->setFormData($info)
			->isAjax(true)
            ->fetch('editym');
		   
		   
		   }
		   //添加首页链接
	   function addindex(){
		   if ($this->request->isPost()) {
            $data = $this->request->post();
					
			
            if ($model = AgentModel::create($data)) {
               // Cache::clear();
                $this->success('新增成功');
            } else {
                $this->error('新增失败');
            }
			}
		   return ZBuilder::make('form')
            ->addFormItems([
				['hidden','ljlx','3'],
				['hidden','uid',UID],
                ['text', 'name', '派单渠道名称', '必填'],
                ['radio', 'gzh', '派单渠道类型', '必填' ,['1'=>'认证公众号','0'=>'未认证公众号']],
				['hidden', 'create_time', $this->request->time()],
				['hidden', 'update_time', $this->request->time()],

            ])
			->isAjax(true)
            ->fetch();
		   
		   
		   }
		  function delete($ids=null)
		  {
			  if ($ids === null) $this->error('参数错误');
			  // 删除并记录日志
			if ($model = DB::table('ien_agent')->delete($ids)) {
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
			  }

		public function order($id=null)
		{

			$user=DB::view('ien_admin_user')
			->view('ien_pay_log','payid,money,type,status,addtime as laddtime,paytype','ien_admin_user.openid=ien_pay_log.uid')
			->where('ien_pay_log.paytype <> 0')
			->where('ien_admin_user.tgid',$id)->select();

			return ZBuilder::make('table')	
			->hideCheckbox()
            ->addColumns([// 批量添加数据列

				['payid', '订单ID','text'],
				['paytype','订单类型',['1'=>'VIP会员','2'=>'普通充值']],
                ['nickname','用户'],
				['money','充值金额'],
				['type', '支付方式', ['1'=>'公众号支付']],
				['status', '订单状态', ['1'=>'已支付','0'=>'未支付']],
				['laddtime', '添加时间', 'datetime'],

            ])
            ->setRowList($user) // 设置表格数据
            ->fetch(); // 渲染模板
			

		}

}