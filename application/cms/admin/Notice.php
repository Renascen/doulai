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

namespace app\cms\admin;

use app\admin\controller\Admin;
use think\Db;
use app\common\builder\ZBuilder;

/**
 * 滚动公告
 * @package app\cms\admin
 */
class Notice extends Admin
{
    /**
     * 滚动公告列表
     * @author 拼搏 <378184@qq.com>
     * @return mixed
     */
    public function index()
    {

        // 查询
        $map = $this->getMap();
        // 排序
        $order = $this->getOrder();
        // 数据列表
        $data_list = DB::view('ien_cms_notice')->where($map)->order($order)->paginate();
        $btnDelete = [
            'title' => '删除',
            'icon'  => 'fa fa-fw fa-remove',
            'class' => 'btn btn-xs btn-default ajax-get confirm',
            'href'  => url('notice/del', ['id' => '__id__']),
            'data-title' => '真的要删除吗？无法恢复!',
            'data-confirm' => '删除吧',
            'data-cancel' => '再想想'

        ];
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearch(['title' => '标题']) // 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['title', '标题', 'text.edit'],
                ['url', '链接', 'text.edit'],
                ['create_time', '创建时间', 'datetime'],
                ['status', '状态', 'switch'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButtons('add') // 批量添加顶部按钮
           ->addRightButton('edit','', true)
            ->addRightButton('custom', $btnDelete)
            ->setRowList($data_list) // 设置表格数据
            ->addValidate('Slider', 'title,url')
            ->fetch(); // 渲染模板
    }

    /**
     * 新增
     * @author 拼搏 <378184@qq.com>
     * @return mixed
     */
    public function add()
    {
        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if (DB::table('ien_cms_notice')->insert($data)) {
               // Cache::clear();
                $this->success('更新成功');
            } else {
                $this->error('更新失败');
            }
        }

        return ZBuilder::make('form')
        ->addFormItems([
            ['text', 'title', '标题 ', '必填'],
            ['text','url','链接','必填'],
            ['text','bid','小说id','必填'],
            ['radio', 'status', '立即启用', '', ['否', '是'], 1],
            ['hidden', 'create_time', $this->request->time()],
            ['hidden', 'update_time', $this->request->time()],

        ])  
        ->setFormData()
        ->isAjax(true)
        ->fetch();
    }

    /**
     * 编辑
     * @param null $id 滚动图片id
     * @author 拼搏 <378184@qq.com>
     * @return mixed
     */
    public function edit($id = null)
    {
        if ($id === 0) $this->error('参数错误');
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if (DB::table('ien_cms_notice')->where('id',$data['id'])->update($data)) {
               // Cache::clear();
                $this->success('更新成功');
            } else {
                $this->error('更新失败');
            }
        }
        $notice=DB::table('ien_cms_notice')->where('id',$id)->find();
        return ZBuilder::make('form')
        ->addFormItems([
            ['text', 'title', '标题 ', '必填'],
            ['text','url','链接','必填'],
            ['text','bid','小说id','必填'],
            ['radio', 'status', '立即启用', '', ['否', '是']],
            ['hidden', 'id'],
            ['hidden', 'create_time', $this->request->time()],
            ['hidden', 'update_time', $this->request->time()],

        ])  
        ->setFormData($notice)
        ->isAjax(true)
        ->fetch();
    }

    /**
     * 删除单页
     * @param array $record 行为日志
     * @author 拼搏 <378184@qq.com>
     * @return mixed
     */
    public function del($id = 0)
    {
        if ($id === 0) $this->error('参数错误');
        DB::table('ien_cms_notice')->where('id',$id)->delete();
        $this->success('删除成功');
    }

    /**
     * 启用单页
     * @param array $record 行为日志
     * @author 拼搏 <378184@qq.com>
     * @return mixed
     */
    public function enable($record = [])
    {
        return $this->setStatus('enable');
    }

    /**
     * 禁用单页
     * @param array $record 行为日志
     * @author 拼搏 <378184@qq.com>
     * @return mixed
     */
    public function disable($record = [])
    {
        return $this->setStatus('disable');
    }

    /**
     * 设置单页状态：删除、禁用、启用
     * @param string $type 类型：delete/enable/disable
     * @param array $record
     * @author 拼搏 <378184@qq.com>
     * @return mixed
     */
    public function setStatus($type = '', $record = [])
    {
        $ids          = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        $slider_title = SliderModel::where('id', 'in', $ids)->column('title');
        return parent::setStatus($type, ['slider_'.$type, 'cms_slider', 0, UID, implode('、', $slider_title)]);
    }

    /**
     * 快速编辑
     * @param array $record 行为日志
     * @author 拼搏 <378184@qq.com>
     * @return mixed
     */
//     public function quickEdit($record = [])
//     {
//         $id      = input('post.pk', '');
//         $field   = input('post.name', '');
//         $value   = input('post.value', '');
//         $slider  = SliderModel::where('id', $id)->value($field);
//         $details = '字段(' . $field . ')，原值(' . $slider . ')，新值：(' . $value . ')';
//         return parent::quickEdit(['slider_edit', 'cms_slider', $id, UID, $details]);
//     }
}