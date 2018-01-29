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

namespace app\cms\home;
use think\Db;
use util\Tree;


/**
 * 前台首页控制器
 * @package app\cms\admin
 */
class Res extends Common
{
    /**
     * 首页
     * @author 拼搏 <378184@qq.com>
     * @return mixed
     */
    public function index()
    {
    	// 微信网页授权接口
		
		    $this->oauth($agent,'http://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]);
        if(cookie('wechat_user')){}
        if(session('wechat_user')){} 
        header('location:'. $targetUrl); // 跳转到 user/profile 
        return $this->fetch(); // 渲染模板
    }
   
    public function readhistory()
    {
      // 微信网页授权接口
    
        $history = Db::view('read_log','id,bid,zid')
        ->view('chapter',['title'=>'ctitle','idx'=>'idx'],'chapter.id=read_log.zid','LEFT')
        ->view('book',['title'=>'btitle'],'book.id=read_log.bid','LEFT')
        ->where("read_log.uid" , cookie('wechat_user')['id'])
        ->order('read_log.id desc')
        ->select();
                
        $this->assign('history', $history);    
        return $this->fetch(); // 渲染模板
    }
   
}