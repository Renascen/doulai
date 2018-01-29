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

/**
 * 仪表盘控制器
 * @package app\cms\admin
 */
class Index extends Admin
{
    /**
     * 首页
     * @author 拼搏 <378184@qq.com>
     * @return mixed
     */
    public function
    index(){
        $this->assign('document', Db::name('cms_document')->where('trash', 0)->count());
        $this->assign('column', Db::name('cms_column')->count());
        $this->assign('page', Db::name('cms_page')->count());
        $this->assign('model', Db::name('cms_model')->count());
        $this->assign('page_title', '仪表盘');
        return $this->fetch(); // 渲染模板
    }
}