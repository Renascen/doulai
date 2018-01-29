<?php
namespace app\agent\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;


/**
 * 阅读页热门推荐
 * @package app\agent\admin
 */
class Hot extends Admin
{
    //热门推荐
    public function push()
    {
        //更新数量
        $tjidArr = Db::table('ien_hot')->field('id')->select();
        // var_dump($tjidArr);
        foreach ($tjidArr as $k => $v) {
            $num = Db::query("select count(*) num from ien_hot_tj where hotid={$v['id']}");
            // echo(Db::getlastsql());exit();
            DB::table('ien_hot')->where('id',$v['id'])->update(['num'=>$num[0]['num']]);
        }
        Db::table("ien_hot")->where('end_time',['<',time()],['<>',0],'and')->setField('status', 0);
        $order = $this->getOrder();
        $map = $this->getMap();
        $data_list = DB::table('ien_hot')->where($map)->order('status desc,id desc')->paginate();
        // 使用ZBuilder快速创建数据表格
        $btnindex = [
                'class' => 'btn btn-primary confirm',
                'icon'  => 'fa fa-plus-circle',
                'title' => '添加方案',
                'href'  => url('hot/hotadd')
            ];
        $btnAdd = [
                'title' => '添加推荐内容',
                'icon'  => 'fa fa-plus-circle',
                'class' => 'btn btn-xs btn-default',
                'href'  => url('hot/tjadd', ['id' => '__id__'])
        ];
         $js = <<<EOF
            <script type="text/javascript">
             $("#_end_time").before("<span style='font-size:14px;font-weight:bold;margin-left:10px'>结束时间:<span/>");
            </script>
EOF;
        return ZBuilder::make('table')
            ->hideCheckbox()
            ->setSearch(['name' => '方案名称']) // 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['name', '方案名称'],
                ['num', '推荐数量','link',url('tjlist', ['id' => '__id__'])],
                ['part', '适用章节',['0'=>'全部','1'=>'免费','2'=>'收费']],
                ['display_num', '显示数量'],
                ['start_time', '开始时间', 'datetime'],
                ['end_time', '结束时间', 'datetime'],
                ['status', '状态', 'switch'],
                ['right_button', '操作', 'btn']
            ])
            ->setTableName('hot')
            ->addTopButton('custom',$btnindex)
            ->addRightButton('custom',$btnAdd,true) // 批量添加右侧按钮
            ->addRightButton('edit') // 批量添加右侧按钮
            ->addRightButton('delete')
            ->addFilter('status', ['0'=>'禁用','1'=>'已启用'])
            ->addTimeFilter('end_time') // 添加时间段筛选
            ->setExtraJs($js)
            ->setRowList($data_list) // 设置表格数据
            ->fetch(); // 渲染模板
    }
      //添加阅读页热门推荐方案
    public function hotadd(){
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 验证
            $result = $this->validate($data, 'Num');
            if(true !== $result) $this->error($result);
            if (count($data['part'])>1 || !$data['part']) {
                $data['part'] = 0;
            }else{
                $data['part'] = $data['part'][0];
            }
            $data['start_time']=strtotime($data['start_time']);
            $data['end_time']=strtotime($data['end_time']);
           
            if (false === DB::table('ien_hot')->insert($data)) {
                $this->error('创建失败');
            }
            $this->success('创建成功','push');
            
        }


        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'name', '方案名称'],
                ['checkbox','part','适用章节','不选或全选，则默认全部',['1' => '免费','2' => '收费']],
                ['datetime','start_time','活动开始时间'],
                ['datetime','end_time','活动结束时间'],
                ['select', 'display_num', '显示数量', '',['3' => '3', '4' => '4', '5' => '5'],3],
            ])
            ->isAjax(true)
            ->fetch();

    }
//编辑方案
    public function edit($id='')
    {
        if ($id === 0) $this->error('参数错误');
        if ($this->request->isPost()) {
            $data = $this->request->post();
                // 验证
            $result = $this->validate($data, 'Num');
            if(true !== $result) $this->error($result);
            if (count($data['part'])>1 || !$data['part']) {
                $data['part'] = 0;
            }else{
                $data['part'] = $data['part'][0];
            }
       
            $data['start_time']=strtotime($data['start_time']);
            $data['end_time']=strtotime($data['end_time']);
         
            $res = DB::table('ien_hot')->where('id',$data['id'])->update($data);
        // echo(Db::getlastsql());exit();

            if ($res) {
                $this->success('更新成功', 'push');
            } else {
                $this->error('无更新内容');
            }
        }

        $info=DB::table('ien_hot')->where('id',$id)->find();
        $info['start_time'] = date('Y-m-d H:i:s',$info['start_time']);
        $info['end_time'] = date('Y-m-d H:i:s',$info['end_time']);
        return ZBuilder::make('form')
        ->addFormItems([
            ['text', 'name', '方案名称'],
            ['checkbox','part','适用章节','不选或全选，则默认全部',['1' => '免费','2' => '收费']],
            ['datetime','start_time','活动开始时间'],
            ['datetime','end_time','活动结束时间'],
            ['select', 'display_num', '显示数量', '',['3' => '3', '4' => '4', '5' => '5'],3],
            ['hidden', 'id'],
        ])
        ->setFormData($info)
        ->isAjax(true)
        ->fetch();

    }
    //添加推荐内容
    public function tjadd($id=null)
    {
        if ($id === 0) $this->error('参数错误');
         if ($this->request->isPost()) {
            $data = $this->request->post();
            $result = $this->validate($data, 'Hot');
            if(true !== $result) $this->error($result);
            // var_dump($data);exit();
            $data['create_time'] = time();
            if(empty($data['linktype'])){
                $data['bid'] = 0;
            }else{
                $data['link'] = '';
            }
            $res = Db::table('ien_hot_tj')->insert($data);
            if ($res) {
                $this->success('添加成功');
            } else {
                $this->error('添加失败');
            }
        }

        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'name', '方案名称'],
                ['radio', 'linktype', '链接类型', '', ['0' => '链接地址', '1' => '小说id'], 0],
                ['text','link','跳转链接'],
                ['text','bid','小说id'],
            ])
            ->setTrigger('linktype', '0', 'link')
            ->setTrigger('linktype', '1', 'bid')
            ->addHidden('hotid',$id)
            ->isAjax(true)
            ->fetch();
    }
    //推荐内容列表
    public function tjlist($id=null)
    {
        $map = $this->getMap();

        $hottj=DB::table('ien_hot_tj')
        ->field('create_time,name,link,id,bid')
        ->where($map)
        ->where('hotid',$id)
        ->order('create_time desc')->paginate();
        $btnEdit = [
                'title' => '编辑',
                'icon'  => 'fa fa-plus-circle',
                'class' => 'btn btn-xs btn-default',
                'href'  => url('hot/tjlistEdit', ['id' => '__id__'])
        ];
        return ZBuilder::make('table')
        ->hideCheckbox()
        ->addColumns([ // 批量添加数据列
            ['name', '推荐内容'],
            ['link', '跳转链接'],
            ['bid', '小说id'],
            ['create_time', '创建时间', 'datetime'],
            ['right_button', '操作', 'btn']
        ])
        ->setTableName('hot_tj')
        ->addRightButton('custom',$btnEdit) // 批量添加右侧按钮
        ->addRightButtons(['delete' => ['data-tips' => '删除后无法恢复。']]) // 批量添加右侧按钮
        ->setRowList($hottj) // 设置表格数据
        ->fetch(); // 渲染模板
    }
    //阅读页广告管理
    public function tjlistedit($id='')
    {
        if ($id === 0) $this->error('参数错误');
        if ($this->request->isPost()) {
            $data = $this->request->post();
                // 验证
            $result = $this->validate($data, 'Hot');
            if(true !== $result) $this->error($result);
            if(empty($data['linktype'])){
                $data['bid'] = 0;
            }else{
                $data['link'] = '';
            }
         
            $res = DB::table('ien_hot_tj')->where('id',$data['id'])->update($data);

            if ($res) {
                $this->success('更新成功', url('hot/tjlist', ['id' => $data['hotid']]));
            } else {
                $this->error('无更新内容');
            }
        }
        $info=DB::table('ien_hot_tj')->where('id',$id)->find();
            return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'name', '方案名称'],
                ['radio', 'linktype', '链接类型', '', ['0' => '链接地址', '1' => '小说id'], $info['linktype']],
                ['text','link','跳转链接'],
                ['text','bid','小说id'],
                ['hidden', 'id'],
            ])
            ->setTrigger('linktype', '0', 'link')
            ->setTrigger('linktype', '1', 'bid')
            ->setFormData($info)
            ->addHidden('hotid',$id)
            ->isAjax(true)
            ->fetch();

    }
    //阅读页广告管理
    public function advertise()
    {
        //更新数量
        $tjidArr = Db::table('ien_advertise')->where('is_delete', 0)->field('id')->select();
        foreach ($tjidArr as $k => $v) {
            $num = Db::query("select count(*) num from ien_advertise_book where advid={$v['id']}");
            DB::table('ien_advertise')->where('id',$v['id'])->update(['num'=>$num[0]['num']]);
        }
        Db::table("ien_advertise")->where('end_time',['<',time()],['<>',0],'and')->setField('status', 0);
        $map = $this->getMap();
        $order = $this->getOrder();
        $data_list =  DB::table('ien_advertise')->where($map)->where('is_delete', 0)->order('status desc,id desc')->paginate();
            $btnindex = [
                'class' => 'btn btn-primary confirm',
                'icon'  => 'fa fa-plus-circle',
                'title' => '添加广告',
                'href'  => url('hot/advadd')
            ];
            $btnAdd = [
                'title' => '添加小说',
                'icon'  => 'fa fa-plus-circle',
                'class' => 'btn btn-xs btn-default',
                'href'  => url('hot/bookadd', ['id' => '__id__'])
            ];
            $btnEdit = [
                'title' => '修改',
                'icon'  => 'fa fa-pencil',
                'class' => 'btn btn-xs btn-default confirm',
                'href'  => url('hot/adedit', ['id' => '__id__'])
            ];
            $btnDel = [
                'title' => '删除',
                'icon'  => 'fa fa-times',
                'class' => 'btn btn-xs btn-default ajax-get confirm',
                'href'  => url('hot/del', ['id' => '__id__', 'table' => 'advertise'])
            ];
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setTableName('advertise')
            ->hideCheckbox()
            ->setSearch(['name' => '广告名称']) // 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['cover', '图片', 'picture'],
                ['name', '广告名称'],
                ['num', '适用小说','link',url('booklist', ['id' => '__id__'])],
                ['url', '链接'],
                ['bid', '小说id'],
                ['start_time', '开始时间', 'datetime'],
                ['end_time', '结束时间', 'datetime'],
                ['status', '状态', 'switch'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButton('custom',$btnindex)
            ->addRightButton('custom',$btnAdd,true) // 批量添加右侧按钮
            ->addRightButton('custom',$btnEdit)
            ->addRightButton('custom', $btnDel)
            ->setRowList($data_list) // 设置表格数据
            ->fetch(); // 渲染模板
    }
    //编辑
    public function adedit($id='')
    {
            if ($id === 0) $this->error('参数错误');
            if ($this->request->isPost()) {
                $data = $this->request->post();
                // 验证
                $result = $this->validate($data, 'Slider');
                // var_dump($data);exit();
                if(true !== $result) $this->error($result);
                $data['start_time']=strtotime($data['start_time']);
                $data['end_time']=strtotime($data['end_time']);
                if(empty($data['linktype'])){
                    $data['bid'] = 0;
                }else{
                    $data['link'] = '';
                }
                 $res = DB::table('ien_advertise')->where('id',$data['id'])->update($data);
                if ($res) {
                    $this->success('更新成功', 'advertise');
                } else {
                    $this->error('无更新内容');
                }
                
            }

            $info=DB::table('ien_advertise')->where(['id' => $id])->find();
            if($info['is_delete'])return $this->error('没有找到该数据');
            $info['start_time'] = date('Y-m-d H:i:s',$info['start_time']);
            $info['end_time'] = date('Y-m-d H:i:s',$info['end_time']);
            return ZBuilder::make('form')
                ->addFormItems([
                    ['text', 'name', '广告名称'],
                    ['datetime','start_time','活动开始时间'],
                    ['datetime','end_time','活动结束时间'],
                    ['image', 'cover', '图片'],
                    ['radio', 'linktype', '链接类型', '', ['0' => '链接地址', '1' => '小说id'], $info['linktype']],
                    ['text', 'url', '链接'],
                    ['text', 'bid', '小说id'],
                    ['hidden', 'id'],
                ])
                ->setTrigger('linktype', 0, 'url')
                ->setTrigger('linktype', 1, 'bid')
                ->setFormData($info)
                ->isAjax(true)
                ->fetch();
    }
    //添加广告
    public function advadd()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
                        // 验证
            $result = $this->validate($data, 'Slider');
            if(true !== $result) $this->error($result);
            $data['start_time']=strtotime($data['start_time']);
            $data['end_time']=strtotime($data['end_time']);
           
            if (false === DB::table('ien_advertise')->insert($data)) {
                $this->error('创建失败');
            }
            $this->success('创建成功','advertise');
        }

        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'name', '广告名称'],
                ['datetime','start_time','活动开始时间'],
                ['datetime','end_time','活动结束时间'],
                ['image', 'cover', '图片'],
                ['radio', 'linktype', '链接类型', '', [0 => '链接地址', 1 => '小说id'], 0],
                ['text', 'url', '链接'],
                ['text', 'bid', '小说id']
            ])
            ->setTrigger('linktype', 0, 'url')
            ->setTrigger('linktype', 1, 'bid')
            ->fetch();
    }

     public function bookadd($id=null)
    {
        if ($id === 0) $this->error('参数错误');
        $this->assign('id', $id);
        $map = $this->getMap();
         if ($this->request->isPost()) {
            $bid = input('post.bid/a');//书id
            $ids = input('param.ids');//限时活动表id
            $insertData = [];
            foreach ($bid as $k => $v) {
                $insertData['advid'] = $ids;
                $insertData['bid'] = $v;
                $num = Db::table('ien_advertise_book')->insert($insertData);
            }
            if ($num != 0) {
                return 1;
            }else{
                return 0;
            }
            
        }
        $data_list = Db::table('ien_advertise_book')->field('bid')->where('advid','eq',$id)->select();      
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
    //小说列表
    public function booklist($id=null)
    {
        if ($id === 0) $this->error('参数错误');
        $map = $this->getMap();
        $data_list = Db::table('ien_advertise_book')->field('bid')->where('advid','eq',$id)->select();      
        $arr = array_column($data_list,'bid');  
        $bids = implode(',', $arr);

        $book=DB::table('ien_advertise_book')
        ->alias('a')
        ->join('ien_book b','a.bid=b.id','LEFT')
        ->field('zuozhe,title,cid,zishu,xstype,a.bid,a.id,zhishu')
        ->where($map)
        ->where('bid','in',$bids)
        ->where('advid','eq',$id)
        ->order('a.bid desc')->paginate();

        return ZBuilder::make('table')
        ->hideCheckbox()
        ->addColumns([ // 批量添加数据列
            ['bid', '书ID'],
            ['title', '书名'],
            ['zuozhe', '作者'],
            ['cid','类别','text', '', ['2'=>'男生','3'=>'女生']],
            ['zishu', '字数'],
            ['xstype', '完结状态','text', '', ['0'=>'连载中','1'=>'已完结']],
            ['right_button', '操作', 'btn']
        ])
        ->setTableName('advertise_book')
        ->addRightButtons(['delete' => ['data-tips' => '删除后无法恢复。']]) // 批量添加右侧按钮
        ->setRowList($book) // 设置表格数据
        ->fetch(); // 渲染模板
    }

}