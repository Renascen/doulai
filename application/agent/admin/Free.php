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

namespace app\agent\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;


/**
 * 限时免费控制器
 * @package app\agent\admin
 */
class Free extends Admin
{
    public function index()
    {
        Db::table("ien_free_limit")->where('end_time',['<',time()],['<>',0],'and')->setField('status', 0);
        $order = $this->getOrder();
        $map = $this->getMap();
        $data_list = DB::table('ien_free_limit')->where($map)->order('status desc,id desc')->paginate();
        // 使用ZBuilder快速创建数据表格
        $btnindex = [
                'class' => 'btn btn-primary confirm',
                'icon'  => 'fa fa-plus-circle',
                'title' => '添加活动',
                'href'  => url('free/xianshiadd')
            ];
        $btnAdd = [
                'title' => '添加小说',
                'icon'  => 'fa fa-plus-circle',
                'class' => 'btn btn-xs btn-default',
                'href'  => url('free/bookadd', ['id' => '__id__'])
        ];
         $js = <<<EOF
            <script type="text/javascript">
             $("#_end_time").before("<span style='font-size:14px;font-weight:bold;margin-left:10px'>结束时间:<span/>");
            </script>
EOF;
        return ZBuilder::make('table')
            ->hideCheckbox()
            ->setSearch(['name' => '活动名称']) // 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['hdid', '活动ID'],
                ['name', '活动名称','link',url('booklist', ['id' => '__id__'])],
                ['start_time', '开始时间', 'datetime'],
                ['end_time', '结束时间', 'datetime'],
                ['type', '有效期类型', ['1' => '时间范围', '2' => '固定天数']],
                ['hdlx', '活动类型'],
                ['hddx', '活动对象'],
                // ['status', '状态', 'switch'],
                ['status', '状态',['1'=>'已启用','0'=>'禁用']],
                ['right_button', '操作', 'btn']
            ])
            ->setTableName('free_limit')
            ->addTopButton('custom',$btnindex)
            // ->addTopButton('enable') // 批量添加右侧按钮
            // ->addTopButton('disable') // 批量添加右侧按钮
            ->addRightButton('custom',$btnAdd,true) // 批量添加右侧按钮
            ->addRightButton('enable', ['href' => url('enable', ['ids' => '__id__','day' => '__day__','table' => 'free_limit'])]) // 批量添加右侧按钮
            ->addRightButton('disable') // 批量添加右侧按钮
            ->addRightButton('edit') // 批量添加右侧按钮
            ->addRightButton('delete')
            ->addFilter('status', ['0'=>'禁用','1'=>'已启用'])
            ->addTimeFilter('end_time') // 添加时间段筛选
            ->setExtraJs($js)
            ->setRowList($data_list) // 设置表格数据
            ->fetch(); // 渲染模板
    }

     //新增限时免费活动
    public function xianshiadd(){
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 验证
            $result = $this->validate($data, 'Free');
            if(true !== $result) $this->error($result);

            $today = date('Ymd',time());
            $data_list = Db::query("select hdid from ien_free_limit order by id desc limit 1");
            if ($data_list) {
                $date = substr($data_list[0]['hdid'],0,8);
                if ($today == $date) {
                    $data['hdid'] = $data_list[0]['hdid']+1;
                }else{
                    $data['hdid'] = $today.'01';
                }
            }else{
                $data['hdid'] = $today.'01';
            }
            if ($data['type'] == 1) {
                $data['start_time']=strtotime($data['start_time']);
                $data['end_time']=strtotime($data['end_time']);
            }else{
                if ($data['day'] != "") {
                    $data['start_time']=time();
                    $data['end_time']=time()+$data['day']*86400;
                }else{
                    $data['start_time']=time();
                    $data['end_time']="";
                }
              
            }
           
            if (false === DB::table('ien_free_limit')->insert($data)) {
                $this->error('创建失败');
            }
            $this->success('创建成功', 'cartoon');
            
        }


        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'name', '活动名称', '必填'],
                ['select', 'type', '活动有效期类型', '必填',['1' => '时间范围', '2' => '固定天数'],1],
                ['text', 'day', '天数','必填'],
                ['datetime','start_time','活动开始时间','必填'],
                ['datetime','end_time','活动结束时间','必填'],
            ])
            ->addSelect('hdlx', '活动类型', '必填', ['限时免费' => '限时免费'])
            ->addSelect('hddx', '活动对象', '必填', ['所有用户' => '所有用户'])
            ->setTrigger('type', '1', 'start_time')
            ->setTrigger('type', '1', 'end_time')
            ->setTrigger('type', '2', 'day')
            ->isAjax(true)
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
                ['cid','类别','text', '', ['0'=>'通用','2'=>'男生','3'=>'女生']],
                ['zishu', '字数'],
                ['xstype', '完结状态','text', '', ['0'=>'连载中','1'=>'已完结']],
            ])
            ->setTableName('book')
            ->setSearch(['zuozhe' => '作者', 'title' => '书名'])
            ->addFilter('cid', ['0'=>'通用','2'=>'男生','3'=>'女生'])
            ->setExtraHtml($html, 'toolbar_top')
            ->setExtraJs($js)
            ->setRowList($book) // 设置表格数据
            ->fetch(); // 渲染模板
    }

    //限免小说列表
    public function booklist($id=null, $type = 0)
    {
        if ($id === 0) $this->error('参数错误');
        $map = $this->getMap();
        // $data_list = Db::table('ien_free_limit')->field('bids')->where('id','eq',$id)->find();
        $data_list = Db::table('ien_free_limit_book')->field('bid')->where('xsid','eq',$id)->select();      
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
        ->where('type', $type)
        ->order('a.sort desc,a.bid desc')->paginate();

        return ZBuilder::make('table')
        ->hideCheckbox()
        ->addColumns([ // 批量添加数据列
            ['bid', '书ID'],
            ['title', '书名'],
            ['zuozhe', '作者'],
            ['cid','类别','text', '', ['0'=>'通用','2'=>'男生','3'=>'女生']],
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

    //编辑限免活动
    public function edit($id='')
    {
        if ($id === 0) $this->error('参数错误');
        if ($this->request->isPost()) {
            $data = $this->request->post();
                // 验证
            $result = $this->validate($data, 'Free');
            if(true !== $result) $this->error($result);

            if ($data['type'] == 1) {
                $data['start_time']=strtotime($data['start_time']);
                $data['end_time']=strtotime($data['end_time']);
            }else{
                if ($data['day'] != "") {
                    $data['start_time']=time();
                    $data['end_time']=time()+$data['day']*86400;
                }else{
                    $data['start_time']=time();
                    $data['end_time']="";
                }
              
            }
            $res = DB::table('ien_free_limit')->where('id',$data['id'])->update($data);
            // var_dump($res);exit();
            if ($res) {
                $this->success('更新成功', 'cartoon');
            } else {
                $this->error('无更新内容');
            }
        }

        $info=DB::table('ien_free_limit')->where('id',$id)->find();
        $info['start_time'] = date('Y-m-d H:i:s',$info['start_time']);
        $info['end_time'] = date('Y-m-d H:i:s',$info['end_time']);
        return ZBuilder::make('form')
          ->addFormItems([
            ['text', 'name', '活动名称', '必填'],
            ['select', 'type', '活动有效期类型', '必填',['1' => '时间范围', '2' => '固定天数'],1],
            ['text', 'day', '天数'],
            ['datetime','start_time','活动开始时间','必填'],
            ['datetime','end_time','活动结束时间','必填'],
            ['hidden', 'id'],
        ])
        // ->addFormItem('start_time', '创建时间', 'datetime', '')
        // ->addFormItem('end_time', '创建时间', 'datetime', '')
        // ->addTime('start_time', '活动开始时间', '', '')
        // ->addTime('end_time', '活动结束时间', '', '')
        ->addSelect('hdlx', '活动类型', '', ['限时免费' => '限时免费'])
        ->addSelect('hddx', '活动对象', '', ['所有用户' => '所有用户'])
        ->setTrigger('type', '1', 'start_time')
        ->setTrigger('type', '1', 'end_time')
        ->setTrigger('type', '2', 'day')
        ->setFormData($info)
        ->isAjax(true)
        ->fetch();

    }

    /**
     * 启用栏目
     * @param array $record 行为日志
     * @author 拼搏 <378184@qq.com>
     * @return mixed
     */
    public function enable($record = [])
    {
        $param = input('param.');
        // $ids = $param['ids'];
        // var_dump($param);
        if ($param['day'] != 0) {
            $time = time()+86400*$param['day'];
            $start_time = time();
            DB::table('ien_free_limit')->where('id', $param['ids'])->update(['end_time'=>$time]);
            DB::table('ien_free_limit')->where('id', $param['ids'])->update(['start_time'=>$start_time]);
        }
        return $this->setStatus('enable');
    }

    /**
     * 禁用栏目
     * @param array $record 行为日志
     * @author 拼搏 <378184@qq.com>
     * @return mixed
     */
    public function disable($record = [])
    {
        return $this->setStatus('disable');
    }

 
}