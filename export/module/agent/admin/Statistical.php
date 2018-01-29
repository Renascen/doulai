<?php
/**
 * User: pinbo
 * Date: 2017/4/8
 * Time: 上午9:50
 */
//后台访问控制器
namespace app\agent\admin;//修改ann为模块名

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\agent\model\Novel as NovelModel;
use think\Db;

class Statistical extends Admin{
	

	//支付统计
   public function paylog()
	{
		//金额天，昨天，月，所有
		$pay_today=$this->pay_day(UID,"today");
		$pay_yesterday=$this->pay_day(UID,"yesterday");
		$pay_month=$this->pay_day(UID,"month");
		$pay_all=$this->pay_day(UID,"all");
		$this->assign('pay_today',$pay_today);
		$this->assign('pay_yesterday',$pay_yesterday);
		$this->assign('pay_month',$pay_month);
		$this->assign('pay_all',$pay_all);
		return ZBuilder::make('table')->fetch('paylog');

	}
	//充值金额统计
	//day today当天/yesterday/昨天/month/当月/all/全部
	public function pay_day($id=null,$dayid=null)
	{
		if ($id === 0) $this->error('参数错误');
		if ($dayid === 0) $this->error('参数错误');
		
		if($dayid=="all")
		{$dayid="";}
		
		//当日充值合计
		$data['pay_num']=0;
		
		//当日普通充值合计
		$data['pay_ptnum']=0;
		
		//当日普通支付成功笔数
		$data['pay_ptok']=0;
		
		//当日普通未支付笔数
		$data['pay_ptno']=0;
		
		//当日普通笔数率
		$data['pay_ptp']=0;
		
		//当日vip充值合计
		$data['pay_vipnum']=0;
		//当日vip支付成功笔数
		$data['pay_vipok']=0;
		//当日vip未支付笔数
		$data['pay_vipno']=0;
		//当日vip笔数率
		$data['pay_vipp']=0;
		
		
		
		
		
		//求当日充值合计

			$money=DB::table('ien_pay_log')->where('did',$id)->where('status',1)->whereTime('paytime', $dayid)->sum('money');
			$data['pay_num']+=$money;

		
		
		//求当日普通充值合计

			$moneypt=DB::table('ien_pay_log')->where('did',$id)->where('status',1)->where('paytype',2)->whereTime('paytime', $dayid)->sum('money');
				$data['pay_ptnum']+=$moneypt;
		
		//求当日普通成功笔数合计
		
		$moneybs=DB::table('ien_pay_log')->where('did',$id)->where('status',1)->where('paytype',2)->whereTime('paytime', $dayid)->count('did');
			$data['pay_ptok']+=$moneybs;


		//求当日普通未成功笔数合计

		$moneynobs=DB::table('ien_pay_log')->where('did',$id)->where('status',0)->where('paytype',2)->whereTime('addtime', $dayid)->count('did');

			$data['pay_ptno']+=$moneynobs;

		//当日成功支付率
		if($data['pay_ptok']==0 && $data['pay_ptno']==0)
		{$data['pay_ptp']=0;}
		else{
		$data['pay_ptp']=$data['pay_ptok']/($data['pay_ptok']+$data['pay_ptno'])*100;
		}
		/////////////////////////////////////////////////////////////////////
		
		//求当日VIP充值合计

			$moneyvip=DB::table('ien_pay_log')->where('did',$id)->where('status',1)->where('paytype',1)->whereTime('paytime', $dayid)->sum('money');
				$data['pay_vipnum']+=$moneyvip;

		//求当日VIP成功笔数合计

		$moneyvipbs=DB::table('ien_pay_log')->where('did',$id)->where('status',1)->where('paytype',1)->whereTime('paytime', $dayid)->count('id');

			$data['pay_vipok']+=$moneyvipbs;

		//求当日VIP未成功笔数合计

		$moneyvipnobs=DB::table('ien_pay_log')->where('did',$id)->where('status',0)->where('paytype',1)->whereTime('addtime', $dayid)->count('id');

			$data['pay_vipno']+=$moneyvipnobs;

		//当日VIP成功支付率
		if($data['pay_vipok']==0 && $data['pay_vipno']==0)
		{$data['pay_vipp']=0;}
		else{
		$data['pay_vipp']=$data['pay_vipok']/($data['pay_vipok']+$data['pay_vipno'])*100;
		}
		
		
		return $data;
		}
		/////////////////////////////////////////////
		public function userlog(){
			$today=$this->use_log(UID,"today");
			$yesterday=$this->use_log(UID,"yesterday");
			$month=$this->use_log(UID,"month");
			$all=$this->use_log(UID,"all");
			$this->assign('today',$today);
			$this->assign('yesterday',$yesterday);
			$this->assign('month',$month);
			$this->assign('all',$all);
			$this->assign('alla',1);
			return ZBuilder::make('table')->fetch('userlog');
			
			}
	
	
		//当日用户统计
		//day today当天/yesterday/昨天/month/当月/all/全部
		public function use_log($id=null,$dayid=null){
			
		if ($id === 0) $this->error('参数错误');
		if ($dayid === 0) $this->error('参数错误');
		if($dayid=="all")
		{$dayid="";}
		
		//当日新增人数合计
		$data['user_num']=0;
		
		//男性
		$data['user_man']=0;
		
		//女性
		$data['user_women']=0;
		
		//未知
		$data['user_weizhi']=0;
		//消费
		$data['user_xiaofei']=0;

		//SELECT `ien_admin_user`.`id`,count(ien_admin_user.id) as num,`ien_agent`.`uid` FROM `ien_admin_user` `ien_admin_user` INNER JOIN `ien_agent` `ien_agent` ON `ien_agent`.`uid`='1' WHERE  (  ien_admin_user.tgid=ien_agent.id )  AND `ien_admin_user`.`create_time` BETWEEN '1501084800' AND '1501171200'  AND (  ien_admin_user.sex=1 )
		//获取当前代理推广男性用户信息
		$man=DB::table('ien_admin_user')
		->where('sxid',$id)
		->whereTime('create_time', $dayid)
		->where('ien_admin_user.sex=1')
		->count('id');

		$data['user_man']+=$man;

		
		//获取当前代理推广女性用户信息
		$user_women=DB::table('ien_admin_user')
		->where('sxid',$id)
		->whereTime('ien_admin_user.create_time', $dayid)
		->where('ien_admin_user.sex=2')
		->count('id');
		
		$data['user_women']+=$user_women;

		
		//获取当前代理推广未知用户信息
		$user_weizhi=DB::table('ien_admin_user')
		->where('sxid',$id)
		->whereTime('ien_admin_user.create_time', $dayid)
		->where('ien_admin_user.sex=0')
		->count('id');

		$data['user_weizhi']+=$user_weizhi;

		
		//获取当前代理推广全部用户信息
		$user_num=DB::table('ien_admin_user')
		->where('sxid',$id)
		->whereTime('ien_admin_user.create_time', $dayid)
		->count('id');

		$data['user_num']+=$user_num;

		
		//获取当前代理推广付费用户信息
		$user_xiaofei=DB::table('ien_pay_log')
		->where('did',$id)
		->where('status','1')
		->field('uid')
		->whereTime('paytime', $dayid)
		->count('DISTINCT uid');

		$data['user_xiaofei']+=$user_xiaofei;
		
			
		return $data;
			
			
			}
	
		
	
	
}