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

namespace app\cms\model;

use think\Model as ThinkModel;

/**
 * 单页模型
 * @package app\cms\model
 */
class Page extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__CMS_PAGE__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    /**
     * 获取单页标题列表
     * @author 拼搏 <378184@qq.com>
     * @return array|mixed
     */
    public static function getTitleList()
    {
        $result = cache('cms_page_title_list');
        if (!$result) {
            $result = self::where('status', 1)->column('id,title');
            // 非开发模式，缓存数据
            if (config('develop_mode') == 0) {
                cache('cms_page_title_list', $result);
            }
        }
        return $result;
    }
}