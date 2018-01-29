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

namespace app\agent\validate;

use think\Validate;

/**
 * 单页验证器
 * @package app\cms\validate
 * @author 拼搏 <378184@qq.com>
 */
class Num extends Validate
{
    // 定义验证规则
    protected $rule = [
        'name|名称'  => 'require',
        'display_num|显示数量'  => 'require',

    ];

}