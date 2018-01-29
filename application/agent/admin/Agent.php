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
		$map        = $this->getMap();
		$order 		= $this->getOrder();
		// $map['uid']=UID;
		$usercount="";
		$usermoney="";
		if(empty($order)){
			$order = 'create_time desc';
		}
		// 'select agent.name,agent.click,agent.pv,from_unixtime(agent.create_time),chapter.bid,book.title,agent.id from ien_agent agent left join ien_chapter chapter on chapter.id=agent.zid left join ien_book book on book.id=chapter.bid'
		//单独拼接组合查询
		$subsql_1 = DB::table('ien_agent')->join('ien_agent_category', 'ien_agent.category=ien_agent_category.id', 'left')->join('ien_chapter', 'ien_agent.zid=ien_chapter.id', 'left')->join('ien_book', 'ien_chapter.bid=ien_book.id', 'left')->field('ien_agent.name as name,ien_agent.id,ien_agent.pv as pv,ien_agent.click as click,ien_agent.create_time as create_time,ien_book.title as title,ien_agent_category.title as catetitle,ien_book.id as bid')->buildSql();
		$uvsql = DB::table('ien_agent_uv')->field('count(*) as uv,tgid')->group('tgid')->buildSql();
		$subsql_2 = DB::table($subsql_1 . ' t1')->field('t1.*,ifnull(uv.uv,0) as uv')->join($uvsql . ' uv', 't1.id=uv.tgid', 'left')->buildSql();
		$paysql = DB::table('ien_pay_log')->field('tgid,sum(money) as total_money,count(distinct uid) as total_user')->group('tgid')->where('status=1')->buildSql();
		$subsql_3 = DB::table($subsql_2 . ' t2')->field('t2.*,ifnull(pay.total_money,0) as total_money,ifnull(pay.total_user,0) as total_user')->join($paysql . ' pay', 't2.id=pay.tgid', 'left')->buildSql();
		$usersql = DB::table('ien_admin_user')->field('tgid,count(*) as total_reg')->group('tgid')->buildSql();
		$data_list = DB::table($subsql_3 . ' t3')->join($usersql . ' user', 't3.id=user.tgid', 'left')
		->field('t3.*,ifnull(user.total_reg,0) as total_reg')
		->where($map)
		->order($order)
        ->paginate();
		// 自定义按钮
            $btnindex = [
                'class' => 'btn btn-primary confirm',
                'icon'  => 'fa fa-plus-circle',
                'title' => '添加首页推广链接',
                'href'  => url('agent/addindex'),
            ];
			 $btnorder = [
                'class' => 'btn btn-xs btn-default',
                'icon'  => 'fa fa-fw fa-shopping-cart',
                'title' => '充值明细',
                'href'  => url('agent/order', ['id' => '__id__']),
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
			->addOrder('money,count,create_time,uv')
            ->addColumns([ // 批量添加数据列
				['name', '渠道名称'],
				['catetitle', '渠道类别'],
				['click','点击'],
                ['pv','PV'],
                ['uv','UV'],
				['total_reg','注册用户'],
				['total_user','充值用户'],
				['total_money','充值金额'],
				['create_time','创建时间','datetime'],
				['title','书名'],
				['bid','书号'],
                ['id','推广ID'],
				['right_button', '操作', 'btn']
            ])

			// ->addTopButton('custom',$btnindex)
			->addRightButton('custom',$btnorder)
			->addRightButton('custom',$btnwxedit)
			// ->addRightButton('custom',$copyurl)
			->addRightButton('edit')
			->addRightButton('delete')
			// ->setExtraJs($copyurl_js)
            ->setRowList($data_list) // 设置表格数据
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

		$categoryArr = DB::table('ien_agent_category')->field('title,id')->where('status', 1)->order('sort desc,id desc')->select();
		$temp_key = array_column($categoryArr,'id'); 
        $temp_value = array_column($categoryArr,'title'); 
        $category = array_combine($temp_key, $temp_value);
        $this->assign('category', $category);
		return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'name', '渠道名称', '必填'],
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
	function savewa($id=null,$type=null,$article_id=null,$referrer_type=null,$follow_type=null,$force_follow_chapter_idx=null,$description=null,$wx_article_title_id=null,$wx_article_cover_id=null,$wx_article_body_template_id=null,$wx_article_footer_template_id=null,$category=null){
		$lastname = DB::table('ien_agent')->where('id', $id)->value('name');
		if($lastname != $description && DB::table('ien_agent')->where('name', $description)->find()){
			$this->error('渠道名称已存在');
        	return false;
		}
		$data['uid']=UID;
		$data['zid']=$article_id;
		$data['titleid']=empty($wx_article_title_id) ? 0 : $wx_article_title_id;
		$data['imageid']=empty($wx_article_cover_id) ? 0 : $wx_article_cover_id;
		$data['tempid']=empty($wx_article_body_template_id) ? 0 : $wx_article_body_template_id;
		$data['footid']=empty($wx_article_footer_template_id) ? 0 : $wx_article_footer_template_id;
		$data['name']=empty($description) ? 0 : $description;
		$data['gzh']=0;
		$data['follow_q']=empty($follow_type) ? 0 : $follow_type;
		$data['category'] = empty($referrer_type) ? 0 : $referrer_type;
		$data['create_time']=time();
		$data['update_time']=time();
		$data['ljlx']=2;
		$data['click'] = 0;
		$res['id']=DB::table('ien_agent')->insertGetId($data);
		$res['url']="http://".preg_replace("/\{\d\}/", '', module_config('agent.agent_tuiguangurl'))."/index.php/cms/document/detail/id/".$data['zid'].".html?t=".$res['id'];
		if( $data['follow_q'] == 1){
			$res['url'] .= "&f=".$data['follow_q'];
		}

		if(module_config("agent.agent_short_url")=="on")
				{
					$apiurl="http://api.t.sina.com.cn/short_url/shorten.xml?source=3271760578&url_long=";
					$shorturl = simplexml_load_file($apiurl.$res['url']);
					foreach (json_decode(json_encode($shorturl->url->url_short),true) as $k => $v)
					{
					$res['url']=$v;
					}
				}
		

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
		$url="http://".preg_replace("/\{\d\}/", '', module_config('agent.agent_tuiguangurl'))."/index.php/cms/document/detail/id/".$agent['zid'].".html?t=".$id;
		if ($agent['follow_q'] == 1) {
			$url .= "&f=".$agent['follow_q'];
		}
		if(module_config("agent.agent_short_url")=="on")
		{
			$apiurl="http://api.t.sina.com.cn/short_url/shorten.xml?source=3271760578&url_long=";
			$shorturl = simplexml_load_file($apiurl.$url);
			$url=$shorturl->url->url_short;
		}
		
		$this->assign('url', $url);

		return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'name', '渠道名称', '必填'],
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
			$data[$key]['paragraphs'] = str_replace(" ","",$value['paragraphs']);
			$data[$key]['paragraphs'] = str_replace("</P><P>","<br />&nbsp;&nbsp;&nbsp;&nbsp;　　",$data[$key]['paragraphs']);
			$data[$key]['paragraphs'] = str_replace("</p><p>","<br />&nbsp;&nbsp;&nbsp;&nbsp;　　",$data[$key]['paragraphs']);
			$data[$key]['paragraphs'] = str_replace("<p>","　　",$data[$key]['paragraphs']);
			$data[$key]['paragraphs'] = str_replace("<P>","　　",$data[$key]['paragraphs']);
			$data[$key]['paragraphs'] = str_replace("</P>","",$data[$key]['paragraphs']);
			$data[$key]['paragraphs'] = str_replace("</p>","",$data[$key]['paragraphs']);
			$data[$key]['paragraphs'] = explode("<br />&nbsp;&nbsp;&nbsp;&nbsp;",$data[$key]['paragraphs']);
			
		}
		return json($data);
	}
	
	
	//创建生成链接
   	function linkcreate($id = null){
	   	if ($id === 0) $this->error('参数错误');
	   	// 保存文档数据
        if ($this->request->isPost()) {
            $data = $this->request->post();
			$data['click'] = 0;
			$data['gzh'] = 0;
			if (empty(trim($data['name']))) {
	            $this->error('标题不能为空');
	            return false;
	        }
	        $res = DB::table('ien_agent')->where('name', $data['name'])->find();
	        $lastname = DB::table('ien_agent')->where('id', $id)->value('name');
			if($lastname != $data['name'] && $res){
				$this->error('渠道名称已存在');
            	return false;
			}
            if (false === DB::table('ien_agent')->insert($data)) {
                $this->error('创建失败');
            }
            $this->success('创建成功');
        }
		
		$data_zj = DB::table('ien_chapter')->where('id',$id)->find();
		$data_xs = DB::table('ien_book')->where('id',$data_zj['bid'])->find();
		$categoryArr = DB::table('ien_agent_category')->field('title,id')->where('status', 1)->order('sort desc,id desc')->select();
		$temp_key = array_column($categoryArr,'id'); 
        $temp_value = array_column($categoryArr,'title'); 
        $category = array_combine($temp_key, $temp_value);
		$this->assign('data_zj',$data_zj);
		$this->assign('data_xs',$data_xs);
		$js = <<<EOF
            <script type="text/javascript">
                $('input[name=follow_q]').click(function(){
                	if($(this).val() == 1){
                		$('#form_group_imageid').show();
                	}else{
                		$('#form_group_imageid').hide();
                	}
                });
            </script>
EOF;
		  // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'name', '渠道名称', '必填'],
                ['radio', 'follow_q', '强制关注', '', ['1'=>'是','0'=>'否'],1],
                ['image','imageid','强关二维码'],
                ['radio','category','渠道类型','',$category, 1],
                ['hidden', 'uid', UID],
                ['hidden', 'zid', $id],
                ['hidden', 'ljlx', 1],
                ['hidden', 'titleid',0],
				['hidden', 'tempid',0],
                ['hidden', 'footid',0],
				['hidden', 'create_time', $this->request->time()],
				['hidden', 'update_time', $this->request->time()],

            ])
			//->addStatic('', '当前小说章节', '', $data_listxs['title'])

			->isAjax(true)
			->setExtraJs($js)
            ->fetch('linkcreate');
	   
	   
	   
	   }
	   
	   //编辑标题
	   function edit($id=null){
		   if ($id === 0) $this->error('参数错误');
		   if ($this->request->isPost()) {
	            $data = $this->request->post();
				if(empty(trim($data['name']))){
					$this->error('渠道名称不能为空');
				}
				$res = DB::table('ien_agent')->where('name', $data['name'])->find();
				$lastname = DB::table('ien_agent')->where('id', $id)->value('name');
				if($lastname != $data['name'] && $res){
					$this->error('渠道名称已存在');
					return false;
				}
	            if ($model = AgentModel::update($data)) {
	               // Cache::clear();
	                $this->success('修改成功', url('agent/index'));
	            } else {
	                $this->error('修改失败');
	            }
			}
		   $info = AgentModel::get($id);
		   $zid = DB::table('ien_agent')->where('id', $id)->value('zid');
		   $this->assign('info',$info);

		$js = <<<EOF
            <script type="text/javascript">
            	if({$info["follow_q"]} == 0){
            		$('#form_group_imageid').css({'opacity': '0', 'height': 0});
            	}
                $('input[name=follow_q]').click(function(){
                	if($(this).val() == 1){
	            		$('#form_group_imageid').css({'opacity': '1', 'height': '100%'});
                	}else{
            			$('#form_group_imageid').css({'opacity': '0', 'height': 0});
                	}
                });
            </script>
EOF;
		   $categoryArr = DB::table('ien_agent_category')->field('title,id')->where('status', 1)->order('sort desc,id desc')->select();
			$temp_key = array_column($categoryArr,'id'); 
	        $temp_value = array_column($categoryArr,'title'); 
	        $category = array_combine($temp_key, $temp_value);
	        $formItems = array(
	        	['hidden','id',$id],
                ['text', 'name', '渠道名称', '必填']
	        );
	        if(!empty($zid)){
	        	$formItems[] = ['radio', 'follow_q', '强制关注', '', ['1'=>'是','0'=>'否'],$info['follow_q']];
                $formItems[] = ['image','imageid','强关二维码'];
	        }
	        $formItems[] =  ['radio','category','渠道类型','',$category,$info['category']];
			$formItems[] = ['hidden', 'update_time', $this->request->time()];
		   return ZBuilder::make('form')
            ->addFormItems(
				$formItems
            )
			->setFormData($info)
			->isAjax(true)
			->setExtraJs($js)
            ->fetch();
		   
		   
		   }
		   //添加首页链接
	   function addindex(){
		   if ($this->request->isPost()) {
	            $data = $this->request->post();
						
	            $data['zid'] = $data['zid'] ? $data['zid'] : 0;
	            $data['titleid'] = $data['titleid'] ? $data['titleid'] : 0;
	            $data['imageid'] = $data['imageid'] ? $data['imageid'] : 0;
	            $data['tempid'] = $data['tempid'] ? $data['tempid'] : 0;
	            $data['footid'] = $data['footid'] ? $data['footid'] : 0;
	            $data['click'] = $data['click'] ? $data['click'] : 0;
	            $data['gzh'] = $data['gzh'] ? $data['gzh'] : 0;
	            $data['follow_q'] = $data['follow_q'] ? $data['follow_q'] : 0;
	            $data['update_time'] = $data['update_time'] ? $data['update_time'] : 0;
	            
	            if ($model = AgentModel::create($data)) {
	               // Cache::clear();
	                $this->success('新增成功');
	            } else {
	                $this->error('新增失败');
	            }
			}
			$categoryArr = DB::table('ien_agent_category')->field('title,id')->where('status', 1)->order('sort desc,id desc')->select();
			$temp_key = array_column($categoryArr,'id'); 
	        $temp_value = array_column($categoryArr,'title'); 
	        $category = array_combine($temp_key, $temp_value);
		   return ZBuilder::make('form')
            ->addFormItems([
				['hidden','ljlx','3'],
				['hidden','uid',UID],
                ['text', 'name', '渠道名称', '必填'],
                ['radio','category','渠道类型','',$category,1],
				['hidden', 'create_time', $this->request->time()],

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

			$user=DB::view('ien_admin_user')
			->view('ien_pay_log','payid,money,qid,type,status,addtime as laddtime,paytype','ien_admin_user.openid=ien_pay_log.uid')
			->where('ien_pay_log.paytype <> 0')
            ->where('ien_pay_log.isout',0)
            ->where('ien_pay_log.status',1)
			->where('ien_pay_log.tgid',$id)
			->where($map)
			->order('ien_pay_log.addtime desc')->paginate();

			return ZBuilder::make('table')
			->hideCheckbox()
			->setSearch(['admin_user.id' => '用户ID'])
            // ->addFilter('pay_log.paytype',['1'=>'VIP会员','2'=>'普通充值'])
            // ->addFilter('pay_log.type',['1'=>'公众号支付','2'=>'第三方支付'])
            // ->addFilter('pay_log.status',['1'=>'已支付','0'=>'未支付'])
            ->addTimeFilter('pay_log.addtime')
            ->addColumns([// 批量添加数据列
				['laddtime', '添加时间', 'datetime'],
				['id','用户ID'],
                ['nickname','用户'],
				['money','充值金额'],
				['qid','渠道ID'],
				// ['paytype','订单类型',['1'=>'VIP会员','2'=>'普通充值']],
				// ['type', '支付方式', ['1'=>'公众号支付']],
				['status', '订单状态', ['1'=>'已支付','0'=>'未支付']],
				['payid', '订单ID','text'],

            ])
            ->setRowList($user) // 设置表格数据
            ->fetch(); // 渲染模板
		}

		public function kouliang($id=null)
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

			$user=DB::view('ien_admin_user')
			->view('ien_pay_log','payid,money,type,status,addtime as laddtime,paytype','ien_admin_user.openid=ien_pay_log.uid')
			->where('ien_pay_log.paytype <> 0')
			->where('ien_pay_log.isout','1')->where($map)->order('ien_pay_log.addtime desc')
			->paginate();

			$today=DB::table('ien_pay_log')->where('status','1')->where('isout','1')->whereTime('paytime', 'today')->sum('money');
            $allday=DB::table('ien_pay_log')->where('status','1')->where('isout','1')->sum('money');

			return ZBuilder::make('table')	
			->hideCheckbox()
			->setPageTips('今日平台扣量合计:'.$today.'元<Br>累计平台扣量合计:'.$allday.'元')
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
				['type', '支付方式', ['1'=>'公众号支付','0'=>'未知']],
				['status', '订单状态', ['1'=>'已支付','0'=>'未支付']],
				['laddtime', '添加时间', 'datetime'],

            ])
            ->setRowList($user) // 设置表格数据
            ->fetch(); // 渲染模板
			

		}

	public function linkcategory(){
		$map        = $this->getMap();
		$order 		= $this->getOrder();
		$data_list = DB::table('ien_agent_category')
		->where($map)
		->order('sort desc, id desc')
        ->paginate();
		// 自定义按钮
        $btnindex = [
            'class' => 'btn btn-primary confirm',
            'icon'  => 'fa fa-plus-circle',
            'title' => '添加分类',
            'href'  => url('agent/addcategory'),
        ];

		return ZBuilder::make('table')
			->hideCheckbox()
            ->addColumns([ // 批量添加数据列
				['id', 'ID'],
				['title', '分类名称','text.edit'],
				['status','状态','switch'],
                ['sort','排序','text.edit']
            ])
            ->setTableName('agent_category')
			->setSearch(['title' => '分类名称'])
			->addTopButton('custom',$btnindex)
			->setRowList($data_list)
            ->fetch(); // 渲染模板
	}

	public function addcategory(){
		if ($this->request->isPost()) {
			$data = $this->request->post();
			if(empty(trim($data['title']))){
				$this->error('分类名称不能为空');
			}
			if (DB::table('ien_agent_category')->insert($data)) {
			$this->success('新增成功', url('agent/linkcategory'));
			} else {
			$this->error('新增失败');
			}
		}
		return ZBuilder::make('form')
		->addFormItems([
			['text', 'title', '分类名称', '必填'],
			['text', 'sort', '排序', '', 0],
			['radio', 'status', '状态', '',['1'=>'启用','0'=>'禁用']],
			['hidden', 'create_time', $this->request->time()],
		])
		->isAjax(true)
		->fetch();
	}


}