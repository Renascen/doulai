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

use app\cms\model\Page as PageModel;
use think\Db;

/**
 * 前台单页控制器
 * @package app\cms\admin
 */
class Page extends Common
{
    /**
     * 单页详情
     * @param null $id 单页id
     * @author 拼搏 <378184@qq.com>
     * @return mixed
     */
    public function detail($id = null)
    {
        $info = PageModel::where('status', 1)->find($id);
        $info['url']  = url('cms/page/detail', ['id' => $info['id']]);
        $info['tags'] = explode(',', $info['keywords']);

        // 更新阅读量
        PageModel::where('id', $id)->setInc('view');

        $this->assign('page_info', $info);
        return $this->fetch(); // 渲染模板
    }
	
	//添加阅读历史记录
	public function readold($zid=null)
	{
		//查询是否有这本书的记录
		$bid=DB::table('ien_chapter')->where('id',$zid)->find();
		$openid=cookie('wechat_user')['id'];
		$map['bid']=$bid['bid'];
		$map['uid']=$openid;
		//$map['zid']=$zid;
		$res=DB::table('ien_read_log')->where($map)->find();
		//如果有，更新章节和更新时间，如果没有插入记录。
		if($res)
		{
			$data['zid']=$zid;
			$data['update_time']=time();
			$add=DB::teble('ien_read_log')->where($map)->update($data);
			
			}
		else{
			$datai['uid']=$openid;
			$datai['zid']=$zid;
			$datai['bid']=$bid['bid'];
			$datai['create_time']=time();
			$add=DB::teble('ien_read_log')->insert($data);
			}
		
		}
//判断当前用户，代理商设置的关注章节	
public function gzzj(){
	$openid = cookie('wechat_user')['id'];
	//获取用户信息
	$user = DB::table('ien_admin_user')->where('openid',cookie('wechat_user')['id'])->find();
	if ($user) {
		//判断是否从推广链接进来
		$agent=DB::table('ien_agent')->where('id',$user['tgid'])->find();
		if ($agent) {
			//判断代理商是否设置了强制关注的ID
			$agentuser=DB::table('ien_admin_user')->where('id',$agent['tgid'])->find();
			if ($agentuser['guanzhu']) {
				return $agentuser['guanzhu'];
			} else {
				return model_config('agent_guanzhu');
			}
		} else {
			return model_config('agent_guanzhu');
		}
	} else {
		return model_config('agent_guanzhu');
	}
}

	//判断是否需要关注
	public function isguanzhu($zid=null)
	{
		
		//查找当前代理商是否设置强制关注章节数
		$guanzhu=$this->gzzj();
		$chapter=DB::table('ien_chapter')->where('id',$zid)->find();
		//判断是否大于关注章节
		if($chapter['idx']>$guanzhu)
		{
			$user=DB::table('ien_admin_user')->where('openid',cookie('wechat_user')['id'])->find();
			if($user['isguanzhu']=0)
			{
			//跳转关注页面
			}
			else
			{
				return true;
				}
		}
		else{
			return true;
			}
		
		
	}
	
	//阅读历史，自动进入
	function auto($id=null)
	{
		//判断是否通过公众号菜单进来
		if($id)
		{
			$user=DB::table('ien_admin_user')->where('openid',cookie('wechat_user')['id'])->find();
			if($user['isguanzhu']=0)
			$res=DB::table('ien_admin_user')->where('openid',cookie('wechat_user')['id'])->insert('isguanzhu',1);
			$zid=DB::table('ien_read_log')->where('openid',cookie('wechat_user')['id'])->order('update_time desc')->find();
			return $zid['zid'];
			
		}
		else
		{
			$zid=DB::table('ien_read_log')->where('openid',cookie('wechat_user')['id'])->order('update_time desc')->find();
			return $zid['zid'];
		}
		
	}
	
	//每日首次登陆赠送积分
	function addcore(){
		$cur_date = strtotime(date('Y-m-d'));
		$map['create_time']=$cur_date;
		$map['openid']=cookie('wechat_user')['id'];
		$map['type']=0;
		$addlog=DB::table('ien_pay_log')->where($map)->find();
		if(!$addlog)
		{
			$data['uid']=cookie('wechat_user')['id'];
			$data['addtime']=time();
			$data['type']=0;
			$add=DB::table('ien_pay_log')->where($map)->insert();
			$user=DB::table('ien_admin_user')->where('openid',cookie('wechat_user')['id'])->find();
			$core=$user['score']+50;
			$user=DB::table('ien_admin_user')->where('openid',cookie('wechat_user')['id'])->update(['score',$core]);
			return true;
			}
		
		return true;
		}
	
	//判断是否VIP章节
	public function isvip($zid=null)
	{
		$chapter=DB::table('ien_chapter')->where('id',$zid)->find();
		$user=DB::table('ien_admin_user')->where('openid',cookie('wechat_user')['id'])->find();
		if($chapter['isvip']=1)
		{
			//判断年费会员
			$user=DB::table('ien_admin_user')->where('openid',cookie('wechat_user')['id'])->find();
			if($user['isvip']=1 && time()>$user['vipstime'] && time()<=$user['vipstime'])
			{
				$this->readold($zid);
				return true;
			}
			//判断是否消费过
			$map['uid']=cookie('wechat_user')['id'];
			$map['zid']=$zid;
			$pay=DB::table('ien_consume_log')->where($map)->find();
			if(!$pay){
				//判断余额是否够
				if(!$user || $user['score']<model_config('agent_pay_money'))
				$this->readold($zid);
				//跳转支付充值
				
				//支付充值成功跳转阅读历史
				}
				else
				{
					//消费积分，保存消费记录，添加阅读记录
					$data['zid']=$zid;
					$data['uid']=cookie('wechat_user')['id'];
					$data['money']=model_config('agent_pay_money');
					$data['addtime']=time();
					$res=DB::table('ien_consume_log')->insert($data);
					//减少会员积分
					
					$core=$user['score'] - model_config('agent_pay_money');
					$user=DB::table('ien_admin_user')->where('openid',cookie('wechat_user')['id'])->update(['score',$core]);
					$this->readold($zid);
					//显示文章
					return true;
					}
				}
			else{$this->readold($zid);return true;}
		else{
			//添加阅读记录
			$this->readold($zid);
			return true;
			
			}
		
		
		
		
		
		
		}
	
}