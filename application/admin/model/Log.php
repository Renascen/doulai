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
 * 日志记录模型
 * @package app\admin\model
 */
class Log extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__ADMIN_LOG__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    /**
     * 获取所有日志
     * @param array $map 条件
     * @param string $order 排序
     * @author 拼搏 <378184@qq.com>
     * @return mixed
     */
    public static function getAll($map = [], $order = '')
    {
        $data_list = self::view('admin_log', true)
            ->view('admin_action', 'title,module', 'admin_action.id=admin_log.action_id', 'left')
            ->view('admin_user', 'username', 'admin_user.id=admin_log.user_id', 'left')
            ->view('admin_module', ['title' => 'module_title'], 'admin_module.name=admin_action.module')
            ->where($map)
            ->order($order)
            ->paginate();
        return $data_list;
    }
}