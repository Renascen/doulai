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

namespace app\admin\model;

use think\Model;

/**
 * 后台配置模型
 * @package app\admin\model
 */
class Config extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__ADMIN_CONFIG__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    /**
     * 获取配置信息
     * @param  string $name 配置名
     * @return mixed
     */
    public function getConfig($name = '')
    {
        $configs = self::column('value,type', 'name');

        $result = [];
        foreach ($configs as $config) {
            switch ($config['type']) {
                case 'array':
                    $result[$config['name']] = parse_attr($config['value']);
                    break;
                case 'checkbox':
                    if ($config['value'] != '') {
                        $result[$config['name']] = explode(',', $config['value']);
                    } else {
                        $result[$config['name']] = [];
                    }
                    break;
                default:
                    $result[$config['name']] = $config['value'];
                    break;
            }
        }

        return $name != '' ? $result[$name] : $result;
    }
}