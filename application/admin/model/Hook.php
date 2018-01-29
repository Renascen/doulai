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
 * 钩子模型
 * @package app\admin\model
 */
class Hook extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__ADMIN_HOOK__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    /**
     * 添加钩子
     * @param array $hooks 钩子
     * @param string $plugin_name 插件名称
     * @author 拼搏 <378184@qq.com>
     * @return bool
     */
    public static function addHooks($hooks = [], $plugin_name = '')
    {
        if (!empty($hooks) && is_array($hooks)) {
            $data = [];
            foreach ($hooks as $name => $description) {
                if (is_numeric($name)) {
                    $name = $description;
                    $description = '';
                }
                if (self::where('name', $name)->find()) {
                    continue;
                }
                $data[] = [
                    'name'        => $name,
                    'plugin'      => $plugin_name,
                    'description' => $description,
                    'create_time' => request()->time(),
                    'update_time' => request()->time(),
                ];
            }
            if (!empty($data) && false === self::insertAll($data)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 删除钩子
     * @param string $plugin_name 钩子名称
     * @author 拼搏 <378184@qq.com>
     * @return bool
     */
    public static function deleteHooks($plugin_name = '')
    {
        if (!empty($plugin_name)) {
            if (false === self::where('plugin', $plugin_name)->delete()) {
                return false;
            }
        }
        return true;
    }
}