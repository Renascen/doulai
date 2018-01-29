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

namespace app\user\validate;

use think\Validate;

/**
 * 用户验证器
 * @package app\admin\validate
 * @author 拼搏 <378184@qq.com>
 */
class User extends Validate
{
    //定义验证规则
    protected $rule = [
        'username|用户名' => 'require|alphaNum|unique:admin_user',
        'nickname|昵称'  => 'require|unique:admin_user',
        'role|角色'      => 'require',
        'email|邮箱'     => 'email|unique:admin_user',
        'password|密码'  => 'require|length:6,20',
        'mobile|手机号'   => 'regex:^1\d{10}|unique:admin_user',
    ];

    //定义验证提示
    protected $message = [
        'username.require' => '请输入用户名',
        'email.require'    => '邮箱不能为空',
        'email.email'      => '邮箱格式不正确',
        'email.unique'     => '该邮箱已存在',
        'password.require' => '密码不能为空',
        'password.length'  => '密码长度6-20位',
        'mobile.regex'     => '手机号不正确',
    ];

    //定义验证场景
    protected $scene = [
        //更新
        'update'  =>  ['email', 'password' => 'length:6,20', 'mobile', 'role'],
        //登录
        'signin'  =>  ['username' => 'require', 'password' => 'require'],
    ];
}
