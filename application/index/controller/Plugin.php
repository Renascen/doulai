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

namespace app\index\controller;

/**
 * 插件控制器
 * @package app\index\home
 */
class Plugin extends Home
{
    /**
     * 执行插件内部方法
     * @author 拼搏 <378184@qq.com>
     * @return mixed|void
     */
    public function execute()
    {
        $plugin     = input('param._plugin');
        $controller = input('param._controller');
        $action     = input('param._action');
        $params     = $this->request->except(['_plugin', '_controller', '_action'], 'param');

        if (empty($plugin) || empty($controller) || empty($action)) {
            return $this->error('没有指定插件名称、控制器名称或操作名称');
        }

        if (!plugin_action_exists($plugin, $controller, $action)) {
            return $this->error("找不到方法：{$plugin}/{$controller}/{$action}");
        }
        return plugin_action($plugin, $controller, $action, $params);
    }
}
