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
use app\common\builder\ZBuilder;
use app\cms\model\Slider as SliderModel;
use think\Db;

/**
 * 滚动图片控制器
 * @package app\cms\admin
 */
class Slider extends Admin
{
    /**
     * 滚动图片列表
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
        $data_list = SliderModel::join('ien_cartoon', 'ien_cartoon.id=ien_cms_slider.bid', 'left')->field('ien_cms_slider.*,ien_cartoon.title as bookname')->where($map)->order($order)->paginate();

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearch(['title' => '标题']) // 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['cover', '图片', 'picture'],
                ['title', '标题', 'text.edit'],
                ['url', '链接', 'text'],
                ['bid', '漫画id', 'text'],
                ['bookname', '漫画标题', 'text'],
                ['create_time', '创建时间', 'datetime'],
                ['sort', '排序', 'text.edit'],
                ['status', '状态', 'switch'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButtons('add,enable,disable,delete') // 批量添加顶部按钮
            ->addRightButtons(['edit', 'delete' => ['data-tips' => '删除后无法恢复。']]) // 批量添加右侧按钮
            ->addOrder('id,title,create_time')
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
            // 表单数据
            $data = $this->request->post();

            // 验证
            $result = $this->validate($data, 'Slider');
            
            if(empty($data['linktype'])){
                $data['bid'] = 0;
            }else{
                $data['url'] = '';
            }

            if(true !== $result) $this->error($result);

            if ($slider = SliderModel::create($data)) {
                // 记录行为
                action_log('slider_add', 'cms_slider', $slider['id'], UID, $data['title']);
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }

        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'title', '标题'],
                ['image', 'cover', '图片'],
                ['radio', 'linktype', '链接类型', '', [0 => '普通链接', 1 => '小说id'], 0],
                ['text', 'url', '链接'],
                ['text', 'bid', '小说id'],
                ['text', 'sort', '排序', '', 100],
                ['radio', 'status', '立即启用', '', ['否', '是'], 1],
            ])
            ->setTrigger('linktype', 0, 'url')
            ->setTrigger('linktype', 1, 'bid')
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
        if ($id === null) $this->error('缺少参数');

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            // 验证
            $result = $this->validate($data, 'Slider');
            if(empty($data['linktype'])){
                $data['bid'] = 0;
            }else{
                $data['url'] = '';
            }
            if(true !== $result) $this->error($result);

            if (SliderModel::update($data)) {
                // 记录行为
                action_log('slider_add', 'cms_slider', $id, UID, $data['title']);
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }

        $info = SliderModel::get($id);

        // 显示编辑页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['hidden', 'id'],
                ['text', 'title', '标题'],
                ['image', 'cover', '图片'],
                ['radio', 'linktype', '链接类型', '', [0 => '普通链接', 1 => '小说id'], $info['linktype']],
                ['text', 'url', '链接'],
                ['text', 'bid', '小说id'],
                ['text', 'sort', '排序', '', 100],
                ['radio', 'status', '立即启用', '', ['否', '是'], 1],
            ])
            ->setTrigger('linktype', 0, 'url')
            ->setTrigger('linktype', 1, 'bid')
            ->setFormData($info)
            ->fetch();
    }

    /**
     * 删除单页
     * @param array $record 行为日志
     * @author 拼搏 <378184@qq.com>
     * @return mixed
     */
    public function delete($record = [])
    {
        return $this->setStatus('delete');
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
    public function quickEdit($record = [])
    {
        $id      = input('post.pk', '');
        $field   = input('post.name', '');
        $value   = input('post.value', '');
        $slider  = SliderModel::where('id', $id)->value($field);
        $details = '字段(' . $field . ')，原值(' . $slider . ')，新值：(' . $value . ')';
        if($field == 'status'){
            $value = $value == 'false' ? 0 : 1;
        }
        $res = Db::name('cms_slider')->where('id', $id)->setField($field, $value);
        if($res){
            $this->success('修改成功');
        }else{
            $this->error('修改失败');
        }
    }
}