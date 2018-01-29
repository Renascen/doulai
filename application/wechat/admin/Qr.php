<?php

namespace app\wechat\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\wechat\model\WeReply as WeReplyModel;
use app\wechat\model\WeMaterial as WeMaterialModel;
use EasyWeChat\Core\AccessToken;
use think\Db;
/*
 * 自动回复管理
 */

class Qr extends Admin
{
    use \app\wechat\Base;
    public $access_token;
    public $AccessToken;
    // 列表
    public function __construct()
    {
        parent::__construct();
        $config = module_config('wechat');
        $this->AccessToken = new AccessToken($config['app_id'], $config['secret']);
        $this->access_token = $this->AccessToken->getToken();
    }
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 获取查询条件
        $map = $this->getMap();

        // 数据列表
        $data_list = DB::table('ien_wechat_qrcode')->where($map)->order('id desc')->paginate();
        $btn = [
                'class' => 'btn btn-default ajax-post confirm',
                'icon'  => 'fa fa-check-circle-o',
                'title' => '扫码记录',
                'href'  => url('qr/show', ['id' => '__id__'])
            ];
        // 分页数据
        $page = $data_list->render();
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTitle('二维码列表')// 设置页面标题
            ->setTableName('wechat_qrcode')// 设置数据表名
            ->setSearch(['id' => 'ID', 'title' => '标题', 'scene_id' => '场景值'])// 设置搜索参数
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['title', '场景名称 '],
                ['scene_id', '场景值'],
                ['type','回复类型','text', '', ['text'=>'文字内容','news'=>'图文(外链)素材']],
                ['content', '回复内容'],
                ['create_time', '创建时间', 'datetime'],
                ['status', '状态', 'switch'],
                ['showqrcode', '二维码', 'img_url'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButtons('add,enable,disable,delete')// 批量添加顶部按钮
            ->addRightButton('custom', $btn, true)
            ->addRightButtons('edit,delete')// 批量添加右侧按钮
            ->setRowList($data_list)// 设置表格数据
            ->setPages($page)// 设置分页数据
            ->fetch(); // 渲染页面
    }

    // 添加自动回复
    public function add()
    {
        if ($this->request->isPost()) {
            $postData = $this->request->post();
            if(empty(trim($postData['title']))){
                return $this->error('场景名称不能为空');
            }
            if(empty(trim($postData['scene_id']))){
                return $this->error('场景值必须大于0');
            }
            $exists = DB::table('ien_wechat_qrcode')->where('scene_id', $postData['scene_id'])->find();
            if(!empty($exists)){
                return $this->error('场景值已存在');
            }
            $params = json_encode([
                   'action_name' => 'QR_LIMIT_SCENE',
                   'action_info' => ['scene' => array('scene_id' => $postData['scene_id'])],
                  ]);
            $res = ihttp_request('https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=' . $this->access_token, $params, 'post');
            if(isset($res['errcode']) && $res['errcode'] == '40001'){
                $this->access_token = $this->AccessToken->getToken(true);
                $this->add();
            }
            $postData['create_time'] = time();
            $postData['showqrcode'] = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=' . $res['ticket'];
            $res = DB::table('ien_wechat_qrcode')->insert($postData);
            if($res){
                return $this->success('添加成功', cookie('__forward__'));
            } else {
                return $this->error('添加失败');
            }
        }

        return ZBuilder::make('form')
            ->setPageTitle('添加二维码')// 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['text', 'title', '场景名称'],
                ['text', 'scene_id', '场景值', '目前只提供永久二维码，无过期时间，目前参数只支持1~100000。场景值不能为空'],
                ['select', 'type', '回复类型', '', ['text' => '文字内容', 'news' => '图文(外链)素材'], 'text'],
                ['textarea', 'content', '回复内容', '若回复纯文字，直接填写内容；其他类型填写素材管理列表中的数字ID'],
                // ['date', 'expires_date', '有效期', '默认有效期为30天之后。小于或等于当前日期 <code>' . date('Y-m-d') . '</code> 则表示过期', $default_expires_date, 'yyyy-mm-dd'],
                ['radio', 'status', '状态', '', ['禁用', '启用'], 1],
            ])
            ->setTrigger('msg_type', 'text', 'keyword,mode')
            ->fetch();
    }

    // 编辑
    public function edit($id = null)
    {
        if ($id === null) return $this->error('缺少参数');

        // 保存数据
        if ($this->request->isPost()) {
            $postData = $this->request->post();
            $postData['update_time'] = time();
            $postData['id'] = $id;
            $res = DB::table('ien_wechat_qrcode')->where('id', $id)->update($postData);
            if($res){
                return $this->success('添加成功', cookie('__forward__'));
            } else {
                return $this->error('添加失败');
            }
        }

        // 获取数据
        $wechat_qrcode_info = DB::table('ien_wechat_qrcode')->where('id', $id)->find();
        if (!$wechat_qrcode_info) {
            return $this->error('内容不存在');
        }
        return ZBuilder::make('form')
            ->setPageTitle('编辑二维码')// 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['text', 'title', '场景名称', '', $wechat_qrcode_info['title']],
                ['text', 'scene_id', '场景值', '场景值不能修改', $wechat_qrcode_info['scene_id'], '', 'readonly'],
                ['select', 'type', '回复类型', '', ['text' => '文字内容', 'news' => '图文(外链)素材'], $wechat_qrcode_info['type']],
                ['textarea', 'content', '回复内容', '若回复纯文字，直接填写内容；其他类型填写素材管理列表中的数字ID', $wechat_qrcode_info['content']],
                // ['date', 'expires_date', '有效期', '默认有效期为30天之后。小于或等于当前日期 <code>' . date('Y-m-d') . '</code> 则表示过期', $default_expires_date, 'yyyy-mm-dd'],
                ['radio', 'status', '状态', '', ['禁用', '启用'], $wechat_qrcode_info['status']]
            ])
            ->setTrigger('msg_type', 'text', 'keyword,mode')
            ->fetch();
    }


    public function show($id = null)
    {

        // 获取查询条件
        $map = $this->getMap();
        $scene_id = DB::table('ien_wechat_qrcode')->where('id', $id)->value('scene_id');
        $map['log.scene_id'] = $scene_id;
        // 数据列表
        $data_list = DB::table('ien_qrcode_log log')
                    ->join('ien_admin_user user', 'log.uid=user.id', 'left')
                    ->field('log.isnew as isnew,log.create_time as create_time,user.username as nickname, user.id as uid')
                    ->where($map)
                    ->order('log.id desc')
                    ->paginate();
        // 分页数据
        $page = $data_list->render();
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->hideCheckbox()
            ->setPageTitle('扫码记录')// 设置页面标题
            ->addColumns([ // 批量添加列
                ['uid','用户id','text'],
                ['nickname','粉丝昵称','text'],
                ['isnew','类型','text', '', ['1'=>'新用户','0'=>'老用户']],
                ['create_time', '扫描时间', 'datetime']
            ])
            ->setTableName('qrcode_log')
            ->addFilter('isnew',['1'=>'新用户','0'=>'老用户'])
            ->addTimeFilter('log.create_time')
            ->setRowList($data_list)// 设置表格数据
            ->setPages($page)// 设置分页数据
            ->fetch(); // 渲染页面
    }
}