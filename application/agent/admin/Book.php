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

class Book extends Admin{
	
	function index(){
        $config = Admin::getConfig();
		$map = $this->getMap();
		$order = $this->getOrder();
		$book=DB::table('ien_book book')
            ->where($map)
            ->group('id')
            ->order($order)->order('id desc')->paginate();
		$xstype=DB::table('ien_cms_field')->where('id',82)->find();
		$xstype = explode("\r\n", $xstype['options']); 
        $tstypeArr = Db::query("select tstype,name from ien_book_sort where status=1");
        $temp_key = array_column($tstypeArr,'tstype'); 
        $temp_value = array_column($tstypeArr,'name'); 
        $tstypes = array_combine($temp_key, $temp_value);
		$btnRecycle = [
                'title' => '章节管理',
                'icon'  => 'fa fa-fw fa-book',
                
                'href'  => url('book/chapter', ['id' => '__id__'])
            ];
         $btnAdd = [
                'title' => '添加小说',
                'icon'  => 'fa fa-plus-circle',
                'class' => 'btn btn-primary confirm',
                'href'  => url('book/bookadd')
            ];
         $btnDelete = [
                'title' => '删除',
                'icon'  => 'fa fa-fw fa-remove',
                'class' => 'btn btn-xs btn-default ajax-get confirm',
                'href'  => url('book/bookdel', ['id' => '__id__']),
                'data-title' => '真的要删除吗？无法恢复!',
			    'data-tips' => '删除小说同时删除所有章节',
			    'data-confirm' => '删除吧',
			    'data-cancel' => '再想想'

            ];
            // dump($config);
            // exit;
		return ZBuilder::make('table')
			->hideCheckbox()
            ->addColumns([ // 批量添加数据列
            	['id', 'ID'],
				['image','封面','picture'],
				['title', '小说名称'],
				['cid','频道','text', '', ['0'=>'通用','2'=>'男生','3'=>'女生']],
				['xstype','小说状态','text', '', $xstype],
				['tstype','小说类型','text', '', $tstypes],
				['zhishu','派单指数','text.edit'],
                ['packsell','整本销售','switch'],
                ['price','折扣价格','text.edit'],
				['status','状态','switch'],
				['tips','打赏金额'],
				// ['score','消费书币'],
                ['zishu','字数','text.edit'],
                ['gzzj','关注章节','text.edit'],
                ['qrcodeimg','关注二维码','pictureimg'],
                ['jpv','虚拟pv','text.edit'],
				['right_button', '操作', 'btn']

            ])
            ->addValidate('Book', 'jpv') // 添加快捷编辑的验证器
            ->setTableName('book')
            ->addOrder('id,zhishu,tips,gzzj')
            ->setSearch(['id' => 'ID', 'title' => '小说名称'])
			->addFilter('cid', ['2'=>'男生','3'=>'女生'])
			->addFilter('xstype', $xstype)
			->addFilter('tstype', $tstypes)
			->addTopButton('custom', $btnAdd,[], true) 
			->addRightButton('custom', $btnRecycle, true) 
			->addRightButton('edit','', true)
			->addRightButton('custom', $btnDelete) 
            ->setRowList($book) // 设置表格数据
            // ->setPages($page)
            ->fetch(); // 渲染模板
		}
		
		//添加小说
		public function bookadd(){

			if ($this->request->isPost()) {
                $data = $this->request->post();
                // var_dump($data);exit();

                if(!empty($data['tj']))
                {
                    $data['tj']=implode(',',$data['tj']);
                }
                else{
                    $data['tj']="";
                }
                // var_dump($data);exit();
                $data['score'] = isset($data['score']) ? $data['score'] : "";
                $data['gzzj'] = isset($data['gzzj']) ? $data['gzzj'] : 0;
                $bid = DB::table('ien_book')->insertGetId($data);
                /**********添加推荐位管理************/
                if ($bid && $data['tj'] != "") {
                    $tjArr = explode(',', $data['tj']);
                    foreach ($tjArr as $k => $v) {
                        $tj = [];
                        $tj['tjid'] = $v;
                        $tj['bid'] = $bid;
                        Db::table('ien_tj_book')->insert($tj);
                    }
                } 
                /**********添加推荐位管理************/
                if ($bid) {
                   // Cache::clear();
                    $this->success('添加成功');
                } else {
                    $this->error('添加失败');
                }
			}
			$xstype=DB::table('ien_cms_field')->where('id',82)->find();
			$xstype = explode("\r\n", $xstype['options']); 
			// $tstype=DB::table('ien_cms_field')->where('id',49)->find();
			// $tstype = explode("\r\n", $tstype['options']); 
            $tstypeArr = Db::query("select tstype,name from ien_book_sort where status=1 order by sort desc,id desc");
            $temp_key = array_column($tstypeArr,'tstype'); 
            $temp_value = array_column($tstypeArr,'name'); 
            $tstypes = array_combine($temp_key, $temp_value);
			$tj=DB::table('ien_cms_field')->where('id',53)->find();
			$tj = explode("\r\n", $tj['options']); 
            $js = <<<EOF
            <script type="text/javascript">
                $("input[name='tstype']").change(function(){
                    $("#cid").empty();
                var ts = $("input[name='tstype']:checked").val();
                    $.ajax({
                        url: '/admin.php/agent/book/tstypecid',
                        type: 'GET',
                        data:{"tstype":ts},
                        dataType: 'text',
                        success:function(data){
                      
                            if (data==0) {
                                var str='<option value="0">通用</option><option value="2">男生</option><option value="3">女生</option><option value="18">畅销</option><option value="19">新作</option>';
                            }
                            if (data==2) {
                                var str='<option value="2">男生</option>';
                            }
                            if (data==3) {
                                var str='<option value="3">女生</option>';
                            }
                             if (data==18) {
                                var str='<option value="18">畅销</option>';
                            }
                            if (data==3) {
                                var str='<option value="19">新作</option>';
                            }
                             $("#cid").append(str);
                        }
                    });
                })
            </script>
EOF;
			return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'title', '小说名称', '必填'],
                ['textarea', 'desc', '简介', '必填' ],
                ['textarea', 'recommend', '推荐词'],
                ['image','image','封面图片','必填'],
                ['jcrop','images','宽封面图','必填','',['minSize' => [720, 435], 'maxSize' => [720, 435]]],
                ['text', 'zuozhe', '作者', '必填'],
                ['text', 'zhishu', '指数', '必填'],
                ['text', 'tag', '标签', '多个标签之间以英文逗号","隔开'],
                ['radio','tstype','小说类型','必填',$tstypes],
                ['radio','xstype','小说状态','必填',$xstype],
                ['checkbox','tj','推荐位','选填',$tj],
                ['select', 'cid','分类','必填'],
                ['text', 'tips', '打赏金额', '选填'],
                ['select', 'status', '启用','',['1'=>'启用','0'=>'下架'],1],
                ['text', 'pay_desc', '充值描述','选填'],
                ['hidden', 'uid', UID],
				['hidden', 'sort'],
                ['hidden', 'model',6],
                ['hidden', 'zishu',0],
				['hidden', 'create_time', $this->request->time()],
				['hidden', 'update_time', $this->request->time()],

            ])	
            ->layout(['zuozhe' => 4, 'zishu' => 4, 'zhishu' => 4, 'cid' => 4,'tips' => 4,'status' => 4])
			->isAjax(true)
            ->setExtraJs($js)
            ->fetch();

		}

				//修改小说
		public function edit($id=null){
			if ($id === 0) $this->error('参数错误');
			if ($this->request->isPost()) {
                $data = $this->request->post();
                /**********添加推荐位管理************/
                    if ($data['tj'] != "") {
                        $originBook = DB::table('ien_tj_book')->where('bid', $data['id'])->select();
                        Db::table('ien_tj_book')->where('bid',$data['id'])->delete();
                        foreach ($data['tj'] as $k => $v) {
                            foreach($originBook as $origin){
                                if($v == $origin['tjid']){
                                    $sort = $origin['sort'];
                                    break;
                                }
                            }
                            $tj = [];
                            $tj['tjid'] = $v;
                            $tj['bid'] = $data['id'];
                            $tj['sort'] = $sort;
                            Db::table('ien_tj_book')->insert($tj);
                        }
                    } 
                /**********添加推荐位管理************/
                if(empty($data['xstype'])){
                    $data['packsell'] = 0;
                }
                if(!empty($data['tj']))
                {
                    $data['tj']=implode(',',$data['tj']);
            	}
            	else{
            	   $data['tj']="";
            	}
                if (DB::table('ien_book')->where('id',$data['id'])->update($data)) {
                   // Cache::clear();
                    $this->success('更新成功');
                } else {
                    $this->error('更新失败');
                }
			}
			$xstype=DB::table('ien_cms_field')->where('id',82)->find();
			$xstype = explode("\r\n", $xstype['options']); 
			// $tstype=DB::table('ien_cms_field')->where('id',49)->find();
			// $tstype = explode("\r\n", $tstype['options']); 
            $tstypeArr = Db::query("select tstype,name from ien_book_sort where status=1 order by sort desc,id desc");
            $temp_key = array_column($tstypeArr,'tstype'); 
            $temp_value = array_column($tstypeArr,'name'); 
            $tstypes = array_combine($temp_key, $temp_value);
			$tj=DB::table('ien_cms_field')->where('id',53)->find();
			$tj = explode("\r\n", $tj['options']); 
			$info=DB::table('ien_book')->where('id',$id)->find();
            $res = DB::table('ien_book_sort')->where('tstype',$info['tstype'])->find();
            if ($res['cid'] == 0) {
                $cid = ['0'=>'通用','2'=>'男生','3'=>'女生','18'=>'畅销','19'=>'新作'];
            }
            if ($res['cid'] == 2) {
                $cid = ['2'=>'男生'];
            }
            if ($res['cid'] == 3) {
                $cid = ['3'=>'女生'];
            }
            if ($res['cid'] == 18) {
                $cid = ['18'=>'畅销'];
            }
            if ($res['cid'] == 19) {
                $cid = ['19'=>'新作'];
            }
    $js = <<<EOF
            <script type="text/javascript">
                $("input[name='tstype']").change(function(){
                    $("#cid").empty();
                var ts = $("input[name='tstype']:checked").val();
                    $.ajax({
                        url: '/admin.php/agent/book/tstypecid',
                        type: 'GET',
                        data:{"tstype":ts},
                        dataType: 'text',
                        success:function(data){
                           if (data==0) {
                                var str='<option value="0">通用</option><option value="2">男生</option><option value="3">女生</option><option value="18">畅销</option><option value="19">新作</option>';
                            }
                            if (data==2) {
                                var str='<option value="2">男生</option>';
                            }
                            if (data==3) {
                                var str='<option value="3">女生</option>';
                            }
                             if (data==18) {
                                var str='<option value="18">畅销</option>';
                            }
                            if (data==3) {
                                var str='<option value="19">新作</option>';
                            }
                             $("#cid").append(str);
                        }
                    });
                })
            </script>
EOF;
			return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'title', '小说名称', '必填'],
                ['textarea', 'desc', '简介', '必填' ],
                ['textarea', 'recommend', '推荐词'],
                ['image','image','封面图片','必填'],
                ['j','image','宽封面图','必填'],
                ['text', 'zuozhe', '作者', '必填'],
                ['text', 'zhishu', '指数', '必填'],
                ['text', 'tag', '标签', '多个标签之间以英文逗号","隔开'],
                ['radio','tstype','小说类型','必填',$tstypes],
                ['radio','xstype','小说状态','必填',$xstype],
                ['checkbox','tj','推荐位','选填',$tj],
                ['jcrop','images','宽封面图','必填','',['minSize' => [720, 435], 'maxSize' => [720, 435]]],
                ['select', 'cid','分类','必填',$cid],
                ['text', 'tips', '打赏金额', '选填'],
                ['select', 'status', '启用','',['1'=>'启用','0'=>'下架'],1],
                ['text', 'pay_desc', '充值描述','选填',$info['pay_desc']],
                ['hidden', 'id'],
                ['hidden', 'uid'],
				['hidden', 'sort',100],
                ['hidden', 'model'],
				['hidden', 'create_time', $this->request->time()],
				['hidden', 'update_time', $this->request->time()],

            ])	
            ->setFormData($info)
            ->layout(['zuozhe' => 4, 'zishu' => 4, 'zhishu' => 4, 'cid' => 4,'tips' => 4,'status' => 4])
			->isAjax(true)
            ->setExtraJs($js)
            ->fetch();

		}

		//删除小说同时删除章节
		public function bookdel($id=null)
		{
			if ($id === 0) $this->error('参数错误');
			DB::table('ien_book')->where('id',$id)->delete();
			DB::table('ien_chapter')->where('bid',$id)->delete();
			$this->success('删除成功');

		}

		//章节管理
		public function chapter($id=null)
		{
			if ($id === 0) $this->error('参数错误');
			// 自定义按钮
		$btnAdd = [
                'title' => '添加',
                'icon'  => 'fa fa-plus-circle',
                'class' => 'btn btn-primary confirm',
                'href'  => url('book/chapteradd')
            ];
         $btnEdit = [
                'title' => '修改',
                'icon'  => 'fa fa-pencil',
                'class' => 'btn btn-xs btn-default confirm',
                'href'  => url('book/chapteredit', ['id' => '__id__'])
            ];
         $btnDelete = [
                'title' => '删除',
                'icon'  => 'fa fa-fw fa-remove',
                'class' => 'btn btn-xs btn-default ajax-get confirm',
                'href'  => url('book/chapterdel', ['id' => '__id__']),
                'data-title' => '真的要删除吗？',
			    'data-tips' => '删除后,无法恢复!',
			    'data-confirm' => '删除吧',
			    'data-cancel' => '再想想'

            ];
			
		$map = $this->getMap();
		$map['bid']=$id;
        $data_list = DB::table('ien_chapter')->where($map)->order('idx asc')->paginate();
		$data_listxs = DB::table('ien_book')->where('id',$id)->find();
        $data_listxs['newzishu'] = DB::table('ien_chapter')->where($map)->sum('zishu');
		
		return ZBuilder::make('table')
			->setPageTips('【注意】删除后不可恢复！')
			->hideCheckbox()
			->setPageTitle($data_listxs['title']." 总字数：".$data_listxs['newzishu'])
            ->addColumns([ // 批量添加数据列
			
				['idx','章节ID'],
				
                ['title', '名称'],
                ['isvip', '是否VIP','switch'],
                ['zishu', '字数','text.edit'],

				['right_button', '操作', 'btn']

            ])
			->setTableName('chapter')
			->addTopButton('custom',$btnAdd)
            ->addRightButton('custom',$btnEdit)
			->addRightButton('custom',$btnDelete)
            ->setRowList($data_list) // 设置表格数据
            ->fetch(); // 渲染模板
		}

		//添加章节
		public function chapteradd(){

			if ($this->request->isPost()) {
            $data = $this->request->post();
            
            if (DB::table('ien_chapter')->insert($data)) {
               // Cache::clear();
                $this->success('添加成功');
            } else {
                $this->error('添加失败');
            }
			}
			
			return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'title', '标题', '必填'],
                ['images', 'content', '内容', '必填' ],
                ['text','idx','章节排序','必填'],
                ['text','bid','小说ID','必填'],
                ['select', 'isvip', '是否VIP','',['1'=>'是','0'=>'否'],1],

                ['hidden', 'cid',5],
                ['hidden', 'status',1],
                ['hidden', 'uid', UID],
				['hidden', 'sort',100],
                ['hidden', 'model',7],
				['hidden', 'create_time', $this->request->time()],
				['hidden', 'update_time', $this->request->time()],

            ])	
            ->layout(['bid' => 4, 'idx' => 4, 'isvip' => 4])
			->isAjax(true)
            ->fetch();

		}

		//修改章节
		public function chapteredit($id=null){

			if ($id === 0) $this->error('参数错误');
			if ($this->request->isPost()) {
                $data = $this->request->post();
                if (DB::table('ien_chapter')->where('id',$data['id'])->update($data)) {
                   // Cache::clear();
                    $this->success('更新成功');
                } else {
                    $this->error('更新失败');
                }
			}
			$info=DB::table('ien_chapter')->where('id',$id)->find();
			return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'title', '标题', '必填'],
                ['images', 'content', '内容', '必填' ],
                ['text','idx','章节排序','必填'],
                ['text','bid','小说ID','必填'],
                ['select', 'isvip', '是否VIP','',['1'=>'是','0'=>'否']],

                ['hidden', 'id'],
				['hidden', 'update_time', $this->request->time()],

            ])	
            ->layout(['bid' => 4, 'idx' => 4, 'isvip' => 4])
            ->setFormData($info)
			->isAjax(true)
            ->fetch();

		}

		//删除小说同时删除章节
		public function chapterdel($id=null)
		{
			if ($id === 0) $this->error('参数错误');
			DB::table('ien_chapter')->where('id',$id)->delete();
			$this->success('删除成功');

		}

        //删除评论
        public function commentdel($id=null)
        {
            if ($id === 0) $this->error('参数错误');
            DB::table('ien_comment')->where('id',$id)->delete();
            $this->success('删除成功');

        }
        public function comment()
        {
           $map = $this->getMap();
           $order = $this->getOrder();
           $comment = DB::table('ien_comment')
           ->alias('comment')
           ->join('ien_book book', 'comment.bid=book.id', 'left')
           ->join('ien_admin_user user', 'comment.uid=user.id', 'left')
           ->field('comment.id,comment.content,comment.status,comment.createtime,comment.zan,book.title,user.nickname')
           ->where($map)
           ->order($order)
           ->paginate();
           $btnDelete = [
                'title' => '删除',
                'icon'  => 'fa fa-fw fa-remove',
                'class' => 'btn btn-xs btn-default ajax-get confirm',
                'href'  => url('book/commentdel', ['id' => '__id__']),
                'data-title' => '真的要删除吗？无法恢复!',
                'data-confirm' => '删除吧',
                'data-cancel' => '再想想'

            ];
           $page = $comment->render();
           return ZBuilder::make('table')
            ->hideCheckbox()
            ->setPageTitle('小说评论管理')
            ->addColumns([ // 批量添加数据列
                ['title', '书名'],
                ['nickname', '用户名'],
                ['content', '评论内容'],
                ['status', '状态','switch'],
                ['zan', '点赞数'],
                ['createtime', '评论时间','datetime'],
                ['right_button', '操作', 'btn']
            ])
            ->setTableName('comment')
            ->setSearch(['title' => '书名', 'nickname' => '用户名'])
            ->addFilter('comment.status', ['0'=>'未审核','1'=>'已审核'])
            ->addOrder('zan,createtime')
            ->addRightButton('custom',$btnDelete)
            ->setRowList($comment) // 设置表格数据
            ->setPages($page)
            ->fetch(); // 渲染模板
        }
        //书籍分类
        public function sort()
        {
            $order = $this->getOrder();
            $map = $this->getMap();
            $data_list = DB::table('ien_book_sort')->where($map)->order('status desc,sort desc,id desc')->paginate();
            $btnindex = [
                'class' => 'btn btn-primary confirm',
                'icon'  => 'fa fa-plus-circle',
                'title' => '添加书籍类型',
                'href'  => url('book/sortadd')
            ];
            $btnEdit = [
                'title' => '修改',
                'icon'  => 'fa fa-pencil',
                'class' => 'btn btn-xs btn-default confirm',
                'href'  => url('book/sortedit', ['id' => '__id__'])
            ];
            return ZBuilder::make('table')
            ->hideCheckbox()
            ->addTopButton('custom',$btnindex)
            ->addColumns([ // 批量添加数据列
                ['sort', '排序','text.edit'],
                ['tstype', '类型ID'],
                ['name', '类型','text.edit'],
                ['cid', '类别', ['0' => '通用', '2' => '男生', '3' => '女生']],
                ['status', '状态', 'switch'],
                ['right_button', '操作', 'btn']
            ])
            ->setTableName('book_sort')
            ->addFilter('cid', ['0'=>'通用','2'=>'男生','3'=>'女生'])
            ->addRightButton('custom',$btnEdit)
            ->setRowList($data_list) // 设置表格数据
            ->fetch(); // 渲染模板
        }
          //新增书籍分类
    public function sortadd(){
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 验证
            $result = $this->validate($data, 'Book');
            if(true !== $result) $this->error($result);
            if (count($data['cid'])>1 || !$data['cid']) {
                $data['cid'] = 0;
            }else{
                $data['cid'] = $data['cid'][0];
            }
            if (isset($data['status'])) {
                $data['status'] = $data['status'][0];
            }else{
                $data['status'] = 0;
            }
            $res = Db::query("select * from ien_book_sort order by id desc limit 1");
            // var_dump($res);exit();
            if (!$res) {
                $data['tstype'] = 0;
            }else{
                $data['tstype'] = $res[0]['tstype']+1;
            }
            if (false === DB::table('ien_book_sort')->insert($data)) {
                $this->error('创建失败');
            }
            $this->success('创建成功','sort');
            
        }
        $sort = Db::table('ien_cms_column')->field('id,name')->where('pid',1)->select();
        $temp_key = array_column($sort,'id');
        $temp_value = array_column($sort,'name');
        $sort = array_combine($temp_key, $temp_value);

        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'name', '类型', '必填'],
                ['checkbox','cid','类别','不选或全选，则默认通用',$sort],
                ['checkbox','status','状态','',['1' => '启用']],
                ['text','sort','排序'],
 
            ])
            ->isAjax(true)
            ->fetch();

    }
    //编辑书籍分类
        public function sortedit($id='')
        {
            if ($id === 0) $this->error('参数错误');
            if ($this->request->isPost()) {
                $data = $this->request->post();
                // 验证
                $result = $this->validate($data, 'Book');
                // var_dump($data);exit();
                if(true !== $result) $this->error($result);
                if (count($data['cid'])>1 || !$data['cid']) {
                    $data['cid'] = 0;
                }else{
                    $data['cid'] = $data['cid'][0];
                }
                if (isset($data['status'])) {
                    $data['status'] = $data['status'][0];
                }else{
                    $data['status'] = 0;
                }
                
                 $res = DB::table('ien_book_sort')->where('id',$data['id'])->update($data);
                if ($res) {
                    $this->success('更新成功', 'sort');
                } else {
                    $this->error('无更新内容');
                }
                
            }

            $info=DB::table('ien_book_sort')->where('id',$id)->find();
            $sort = Db::table('ien_cms_column')->field('id,name')->where('pid',1)->select();
            $temp_key = array_column($sort,'id');
            $temp_value = array_column($sort,'name');
            $sort = array_combine($temp_key, $temp_value);
            return ZBuilder::make('form')
                ->addFormItems([
                    ['text', 'name', '类型', '必填'],
                    ['checkbox','cid','类别','不选或全选，则默认通用',$sort],
                    ['checkbox','status','状态','',['1' => '启用']],
                    ['text','sort','排序'],
                    ['hidden', 'id'],
     
                ])
                ->setFormData($info)
                ->isAjax(true)
                ->fetch();

        }
        //推荐位管理
        public function tjadmin()
        {
            //更新数量
            $tjidArr = Db::table('ien_tj_admin')->field('tj')->select();
            // var_dump($tjidArr);
            foreach ($tjidArr as $k => $v) {
                $num = Db::query("select count(*) num from ien_tj_book where tjid={$v['tj']}");
                // echo(Db::getlastsql());exit();
                DB::table('ien_tj_admin')->where('tj',$v['tj'])->update(['num'=>$num[0]['num']]);
            }
            $order = $this->getOrder();
            $map = $this->getMap();
            $data_list = DB::table('ien_tj_admin')->where($map)->paginate();
            $btnAdd = [
                'title' => '添加小说',
                'icon'  => 'fa fa-plus-circle',
                'class' => 'btn btn-xs btn-default',
                'href'  => url('book/tjbookadd', ['id' => '__tj__'])
            ];
            return ZBuilder::make('table')
            ->hideCheckbox()
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['name', '类型','text.edit'],
                ['position', '推荐位位置',['0'=>'首页','1'=>'我的书架','2'=>'阅读历史']],
                ['num', '书籍数量','link',url('booklist', ['id' => '__tj__'])],
                ['list_num', '单次数量','text.edit'],
                ['right_button', '操作', 'btn']
            ])
            ->setTableName('tj_admin')
            ->addRightButton('custom',$btnAdd,true) // 批量添加右侧按钮
            ->setRowList($data_list) // 设置表格数据
            ->fetch(); // 渲染模板
        }
        //推荐位添加小说
        public function tjbookadd($id=null)
        {
            $this->assign('id', $id);
            $map = $this->getMap();
             if ($this->request->isPost()) {
                $bid = input('post.bid/a');//书id
                $ids = input('param.ids');//推荐位id
                $insertData = [];
                foreach ($bid as $k => $v) {
                    $insertData['tjid'] = $ids;
                    $insertData['bid'] = $v;
                    $num = Db::table('ien_tj_book')->insert($insertData);
                    $oldTjList = Db::table('ien_book')->field('tj')->where('id',$v)->find();
                    if ($oldTjList['tj'] == "") {
                        Db::table('ien_book')->where('id',$v)->update(['tj'=>$ids]);//没有记录直接更新
                    }else{
                        $newTjList = $oldTjList['tj'].','.$ids;
                        Db::table('ien_book')->where('id',$v)->update(['tj'=>$newTjList]);//有记录先拼接再更新
                    }
                    
                }
                if ($num != 0) {
                    return 1;
                }else{
                    return 0;
                }

            }
            $data_list = Db::table('ien_tj_book')->field('bid')->where('tjid','eq',$id)->select();      
            $arr = array_column($data_list,'bid');  
            $bids = implode(',', $arr);
            $book=DB::table('ien_book book')
            ->where($map)
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
                        url: "{:url('tjbookadd')}",
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
                ['cid','类别','text', '', ['0'=>'通用','2'=>'男生','3'=>'女生']],
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
        //推荐位小说列表
        public function booklist($id=null)
        {
        $btnEdit = [
            'title' => '编辑',
            'icon'  => 'fa fa-pencil',
            'class' => 'btn btn-xs btn-default confirm',
            'href'  => url('book/imageedit', ['id' => '__id__'])
        ];
        $map = $this->getMap();
        $data_list = Db::table('ien_tj_book')->field('bid')->where('tjid','eq',$id)->select();      
        $arr = array_column($data_list,'bid');  
        $bids = implode(',', $arr);

        $book=DB::table('ien_tj_book')
        ->alias('a')
        ->join('ien_book b','a.bid=b.id','LEFT')
        ->field('a.image,a.sort,zuozhe,title,cid,zishu,xstype,a.bid,a.id,zhishu')
        ->where($map)
        ->where('bid','in',$bids)
        ->where('tjid','eq',$id)
        ->order('a.sort desc,a.bid desc')->paginate();

        return ZBuilder::make('table')
        ->hideCheckbox()
        ->addColumns([ // 批量添加数据列
            ['bid', '书ID'],
            ['image', '封面图','picture'],
            ['title', '书名'],
            ['zuozhe', '作者'],
            ['cid','类别','text', '', ['0'=>'通用','2'=>'男生','3'=>'女生']],
            ['zishu', '字数'],
            ['xstype', '完结状态','text', '', ['0'=>'连载中','1'=>'已完结']],
            ['sort','排序','text.edit'],
            ['right_button', '操作', 'btn']

        ])
        ->setTableName('tj_book')
        ->addRightButton('edit',$btnEdit) // 添加编辑按钮
        ->addRightButtons(['delete' => ['data-tips' => '删除后无法恢复。']]) // 批量添加右侧按钮
        ->setRowList($book) // 设置表格数据
        ->fetch(); // 渲染模板
    }
    // public function delete($value='')
    // {
    //     # code...
    // }
        /**********通过类型得到对应的cid************/
        public function tstypecid()
        {
            $tstype = input('param.');
            // var_dump($tstype);exit();
            $tstype = $tstype['tstype'];
            $res = Db::query("select cid from ien_book_sort where tstype=$tstype");
            // var_dump($res[0]['cid'],intval($res[0]['cid']));
            return intval($res[0]['cid']);
        }

        public function imageedit($id=null){
            if ($id === 0) $this->error('参数错误');
            if ($this->request->isPost()) {
                $data = $this->request->post();
                if (DB::table('ien_tj_book')->where('id',$data['id'])->update($data)) {
                    $this->success('更新成功');
                } else {
                    dump(Db::getLastSql());
                    $this->error('更新失败');
                }
            }
            $info=DB::table('ien_tj_book')->where('id',$id)->find();
            return ZBuilder::make('form')
                ->addFormItems([
                    ['hidden', 'id'],
                    ['jcrop','image','宽封面图','必填','',['minSize' => [310, 206], 'maxSize' => [650, 242]]],
                ])
                ->setFormData($info)
                ->isAjax(true)
                ->fetch();
        }


}