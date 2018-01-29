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

namespace app\cms\validate;

use think\Validate;

/**
 * 菜单验证器
 * @package app\cms\validate
 * @author 拼搏 <378184@qq.com>
 */
class Menu extends Validate
{
    // 定义验证规则
    protected $rule = [
        'column|栏目' => 'requireIf:type,0',
        'page|单页' => 'requireIf:type,1',
        'title|菜单标题' => 'requireIf:type,2|length:1,30',
        'url|URL' => 'requireIf:type,2',
    ];

    // 定义验证提示
    protected $message = [
        'column.requireIf' => '请选择栏目',
        'page.requireIf'   => '请选择单页',
        'title.requireIf'  => '菜单标题不能为空',
        'url.requireIf'    => 'URL不能为空'
    ];

    // 定义验证场景
    protected $scene = [
        'title' => ['title']
    ];
}
