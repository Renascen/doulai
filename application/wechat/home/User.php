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

namespace app\wechat\home;

use app\index\controller\Home;
use app\agent\model\Agent as AgentModel;
use think\Db;

use EasyWeChat\Foundation\Application;
/**
 * 前台首页控制器
 * @package app\cms\admin
 */
class User extends Home
{
    
    /**
     * 首页
     * @author 拼搏 <378184@qq.com>
     * @return 
     */
    public function agent($id=null)

    {
        if($id === null){
            return $this->error('参数错误');
        }
        $agent=AgentModel::where('id', $id)->value('zid');
       
		    $this->oauth($agent);
    }

    
   
}


