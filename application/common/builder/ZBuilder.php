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

namespace app\common\builder;

use app\common\controller\Common;
use think\Exception;

/**
 * 构建器
 * @package app\common\builder
 * @author 蔡伟明 <314013107@qq.com>
 */
class ZBuilder extends Common
{
    /**
     * @var array 构建器数组
     * @author 蔡伟明 <314013107@qq.com>
     */
    protected static $builder = [];

    /**
     * @var array 模板参数变量
     */
    protected static $vars = [];

    /**
     * @var string 动作
     */
    protected static $action = '';

    /**
     * 初始化
     * @author 蔡伟明 <314013107@qq.com>
     */
    public function _initialize()
    {}

    /**
     * 创建各种builder的入口
     * @param string $type 构建器名称，'Form', 'Table', 'View' 或其他自定义构建器
     * @param string $action 动作
     * @author 蔡伟明 <314013107@qq.com>
     * @return table\Builder|form\Builder|aside\Builder
     * @throws Exception
     */
    public static function make($type = '', $action = '')
    {
        if ($type == '') {
            throw new Exception('未指定构建器名称', 8001);
        } else {
            $type = strtolower($type);
        }

        // 构造器类路径
        $class = '\\app\\common\\builder\\'. $type .'\\Builder';
        if (!class_exists($class)) {
            throw new Exception($type . '构建器不存在', 8002);
        }

        if ($action != '') {
            static::$action = $action;
        } else {
            static::$action = '';
        }

        return new $class;
    }

    /**
     * 加载模板输出
     * @param string $template 模板文件名
     * @param array  $vars     模板输出变量
     * @param array  $replace  模板替换
     * @param array  $config   模板参数
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function fetch($template = '', $vars = [], $replace = [], $config = [])
    {
        $vars = array_merge($vars, self::$vars);
        return parent::fetch($template, $vars, $replace, $config);
    }
}
