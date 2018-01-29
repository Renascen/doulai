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
		if ($dayid=="all") {
			$dayid="";
		}
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
		$money=DB::table('ien_pay_log')->where('status',1)->whereTime('paytime', $dayid)->sum('money');
			$data['pay_num'] += $money;

		//求当日普通充值合计
		$moneypt=DB::table('ien_pay_log')->where('status',1)->where('paytype',2)->whereTime('paytime', $dayid)->sum('money');
				$data['pay_ptnum'] += $moneypt;

		//求当日普通成功笔数合计
		$moneybs=DB::table('ien_pay_log')->where('status',1)->where('paytype',2)->whereTime('paytime', $dayid)->count('did');
			$data['pay_ptok'] += $moneybs;


		//求当日普通未成功笔数合计
		$moneynobs=DB::table('ien_pay_log')->where('status',0)->where('paytype',2)->whereTime('addtime', $dayid)->count('did');

			$data['pay_ptno'] += $moneynobs;

		//当日成功支付率
		if ($data['pay_ptok']==0 && $data['pay_ptno']==0) {
			$data['pay_ptp']=0;
		} else {
			$data['pay_ptp']= floor($data['pay_ptok']/($data['pay_ptok']+$data['pay_ptno']) * 10000 +0.5) /100;
		}
		/////////////////////////////////////////////////////////////////////

		//求当日VIP充值合计
		$moneyvip=DB::table('ien_pay_log')->where('status',1)->where('paytype',1)->whereTime('paytime', $dayid)->sum('money');
				$data['pay_vipnum']+=$moneyvip;

		//求当日VIP成功笔数合计
		$moneyvipbs=DB::table('ien_pay_log')->where('status',1)->where('paytype',1)->whereTime('paytime', $dayid)->count('id');

			$data['pay_vipok']+=$moneyvipbs;

		//求当日VIP未成功笔数合计
		$moneyvipnobs=DB::table('ien_pay_log')->where('status',0)->where('paytype',1)->whereTime('addtime', $dayid)->count('id');

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
		if ($id === 0) {
			$this->error('参数错误');
		}
		if ($dayid === 0) {
			$this->error('参数错误');
		}
		if ($dayid=="all") {
			$dayid="";
		}
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
		// ->where('sxid',$id)
		->whereTime('create_time', $dayid)
		->where('ien_admin_user.sex=1')
		->count('id');

		$data['user_man']+=$man;

		//获取当前代理推广女性用户信息
		$user_women=DB::table('ien_admin_user')
		// ->where('sxid',$id)
		->whereTime('ien_admin_user.create_time', $dayid)
		->where('ien_admin_user.sex=2')
		->count('id');

		$data['user_women']+=$user_women;

		//获取当前代理推广未知用户信息
		$user_weizhi=DB::table('ien_admin_user')
		// ->where('sxid',$id)
		->whereTime('ien_admin_user.create_time', $dayid)
		->where('ien_admin_user.sex=0')
		->count('id');

		$data['user_weizhi']+=$user_weizhi;

		//获取当前代理推广全部用户信息
		$user_num=DB::table('ien_admin_user')
		// ->where('sxid',$id)
		->whereTime('ien_admin_user.create_time', $dayid)
		->count('id');

		$data['user_num']+=$user_num;

		//获取当前代理推广付费用户信息
		$user_xiaofei=DB::table('ien_pay_log')
		// ->where('did',$id)
		->where('status','1')
        // ->where('isout','NEQ','1')
		->field('uid')
		->whereTime('paytime', $dayid)
		->count('DISTINCT uid');

		$data['user_xiaofei']+=$user_xiaofei;

		return $data;
	}

	/**
	 * 每日充值数据汇总
	 * @return
	 */
	public function rechargeByDay(){
		$mapa = $this->getMap();
		$key=array_keys($mapa);
		$i=0;
		foreach($mapa as $k=>$value)
		{
			$name="ien_".$key[$i];
			$map[$name]=$value;
			$i++;
		}
		$recharge = DB::query('select sum(total) as total,sum(utotal) as utotal,sum(users) as users,addtime,sum(pv) as pv,sum(click) as click from (
			select sum(money) as `total`, count(DISTINCT uid) as `users`,0 as utotal,0 as pv,0 as click,
			DATE_FORMAT(FROM_UNIXTIME(addtime),"%Y-%m-%d") as addtime
			from ien_pay_log where type > 0 and status = 1 group by DATE_FORMAT(FROM_UNIXTIME(addtime),"%Y-%m-%d")
		 union 
			select 0 as `total`, 0 as `users`,count(*) as `utotal`,0 as pv,0 as click,
			DATE_FORMAT(FROM_UNIXTIME(create_time),"%Y-%m-%d") as addtime
			from ien_admin_user group by DATE_FORMAT(FROM_UNIXTIME(create_time),"%Y-%m-%d")
		union 
			select 0 as `total`, 0 as `users`,0 as `utotal`,sum(pv) as pv,sum(click) as click,addtime
			from ien_day_log group by addtime
	) temp group by addtime order by addtime desc');

		return ZBuilder::make('table')
            ->hideCheckbox()
            ->addColumns([// 批量添加数据列
                ['addtime', '日期'],
                ['click','点击','text'],
                ['pv','pv'],
                ['utotal','注册用户'],
                ['users','充值用户'],
                ['total','充值金额'],
            ])
            ->setRowList($recharge) // 设置表格数据
            ->fetch(); // 渲染模板
	}
	/**
	 * 小说充值统计
	 * @return [type] [description]
	 */
	public function bookRechargeCount(){
		/**
		 * SELECT bp.bookid, bp.title, SUM(bp.r_users) AS reg_users, SUM(bp.money) AS pay_money, SUM(bp.p_users) AS pay_users,bp.con_pv
FROM (SELECT temp.*, COUNT(*) AS r_users
	FROM (SELECT  arr.tgid, arr.bookid, arr.title, SUM(money) AS money, COUNT(DISTINCT uid) AS p_users,arr.con_pv as con_pv
		FROM  ( 
			SELECT tgid,bookid,money,uid,b.title as title,b.con_pv as con_pv FROM ien_pay_log AS pay 
			LEFT JOIN (SELECT ien_book.id,title,ien_book_pv.pv as con_pv FROM ien_book left join ien_book_pv on ien_book.id=ien_book_pv.bid) AS b ON pay.bookid = b.id 
			WHERE pay.status = 1 
		) arr
		WHERE title IS NOT NULL GROUP BY arr.tgid ) temp
		LEFT JOIN `ien_admin_user` AS reg ON reg.tgid = temp.tgid GROUP BY tgid
	) bp
GROUP BY bookid
ORDER BY pay_money DESC
		 */
		$bookRecharge = DB::query('
			select bp.bookid,bp.title,sum(bp.r_users) reg_users,sum(bp.money) pay_money,sum(bp.p_users) pay_users from (
			select temp.*,count(*) r_users from (
			select arr.tgid,arr.bookid,arr.title,sum(money) as money,count(DISTINCT uid) p_users from (
			SELECT pay.*,b.title FROM `ien_pay_log` pay
			left join `ien_book` b on pay.bookid=b.id where pay.status=1) arr where title is not null group by arr.tgid) temp
			left join `ien_admin_user` reg on reg.tgid=temp.tgid group by tgid) bp group by bookid order by pay_money desc
		');
		$bids = array();
		foreach($bookRecharge as $key => &$val){
			$bids[] = $val['bookid'];
			$val['scale'] = (round($val['pay_users'] / $val['reg_users'] * 100, 2)) . '%';
			// $val['con_pv'] = DB::table('ien_agent_pv')->where('pagetype', 1)->where('bookid', $val['bookid'])->count();
			// $bookRecharge[$key]['con_pv'] = DB::table('ien_agent_pv')->where(array('bookid'=> $val['bookid'],'pagetype'=> 1))->sum();;
		}
		$con_pv = DB::table('ien_agent_pv')->field('count(*) as con_pv,bookid')->where('pagetype', 1)->whereIn('bookid', implode(',', $bids))->group('bookid')->select();
		foreach($bookRecharge as $key => &$val){
			foreach($con_pv as $v){
				if($val['bookid'] == $v['bookid']){
					$val['con_pv'] = $v['con_pv'];
					break;
				}
			}
		}
		return ZBuilder::make('table')
            ->hideCheckbox()
            ->addColumns([// 批量添加数据列
                ['bookid', '书号','text'],
                ['title','书名','text'],
                // ['page_click','书页点击'],
                ['con_pv','内容浏览PV'],
                ['reg_users','注册用户'],
                ['pay_users','付费用户'],
                ['scale','付费率'],
                ['pay_money','付费金额'],
            ])
            ->setRowList($bookRecharge) // 设置表格数据
            ->fetch(); // 渲染模板
	}
}