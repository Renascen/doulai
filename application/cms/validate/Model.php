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
 * 文档模型验证器
 * @package app\cms\validate
 * @author 拼搏 <378184@qq.com>
 */
class Model extends Validate
{
    // 定义验证规则
    protected $rule = [
        'name|模型标识'  => 'require|regex:^[a-z]+[a-z0-9_]{0,39}$|unique:cms_model',
        'title|模型标题' => 'require|length:1,30|unique:cms_model',
        'table|附加表'  => 'regex:^[#@a-z]+[a-z0-9#@_]{0,60}$|unique:cms_model',
    ];

    // 定义验证提示
    protected $message = [
        'name.regex' => '模型标识由小写字母、数字或下划线组成，不能以数字开头',
        'table.regex' => '附加表由小写字母、数字或下划线组成，不能以数字开头',
    ];

    // 定义场景
    protected $scene = [
        'edit' =>  ['title'],
    ];
}
