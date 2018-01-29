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
use app\admin\controller\Admin;
use think\Db;

use util\Tree;
use \think\Request;
use util\ClientResponseHandler;
use util\PayHttpClient;
use util\RequestHandler;

use EasyWeChat\Foundation\Application;

use EasyWeChat\Payment\Order;

/**
 * 前台首页控制器
 * @package app\cms\admin
 */

class Pay extends Common

{
    /**
     * 首页
     * @author 拼搏 <378184@qq.com>
     * @return mixed
     */

    public function duobaopay(){
        session_start();
        if (empty(input('payprodcutid'))) {
            //header('location:http://'. module_config('agent.agent_payurl').'/index.php/cms/pay/index/');
            $this->redirect('pay/index');
        } else {
            $id=input('payprodcutid');
        }
        // if($id==''){
        //     die("参数错误！");
        // }

        $orderid='BOOK'.time().rand(10000,99999);

        //添加代理ID和渠道ID
        $user = DB::table('ien_admin_user')->where('openid',$_SESSION['wechat_user']['original']['openid'])->find();
        if (!empty($user['tgid'])) {
            $ddid = DB::table('ien_agent')->where('id',$user['tgid'])->find();
            $bookid = DB::table('ien_chapter')->where('id',$ddid['zid'])->value('bid');
            if (!empty($ddid)) {
                $did = $ddid['uid'];
                $sjid = DB::table('ien_admin_user')->where('id',$did)->value('did');
                if(!empty($sjid)) {
                    $qid=$sjid;
                } else {
                    $qid=0;
                }
            } else {
                $did=0;
                $qid=0;
            }
        } else {
            $did=0;
            $qid=0;
        }

        $data = ['uid' =>$_SESSION['wechat_user']['original']['openid'],
           'type' => '1',
           'status' => '0',
           'addtime' => time(),
           'paytime' => '0',
           'bookid' => $bookid ? $bookid : 0,
           'payid' => $orderid,
           'did' => $did,
           'qid' => $qid,
           'isout' => 0,
           'tgid' =>$user['tgid'],
           'cxid' => $id,
           'userid' => $user['id']
        ];
        //判断超期用户订单
        $isout=DB::table('ien_admin_user')->where('openid',$_SESSION['wechat_user']['original']['openid'])->value('isout');
        if($isout==1){
            $data['isout']=1;
        }
        //判断比例黑单
        $bili=module_config('agent.agent_klbili');
        $nokou=explode(',',module_config('agent.agent_nokou'));
        if ($bili>0) {
            $sxid=DB::table('ien_admin_user')->where('openid',$_SESSION['wechat_user']['original']['openid'])->value('sxid');
            if(!in_array($sxid,$nokou)  || empty($nokou['0'])){
                $kl=mt_rand(1,$bili);
                if($kl<=2){
                    $data['isout']=1;
                }

            }
        }

        //设置订单金额类型商品ID
        $infodata=DB::table('ien_cuxiao')->where('id',$id)->find();
        $data['money'] =$infodata['money'];
        if ($infodata['type']==1) {
            $data['paytype'] =2;
        } else {
            $data['paytype'] =1;
        }
        $res = DB::table('ien_pay_log')->insert($data);
        // 商户id
        $customerid = 200447;
        //密钥
        $key = 'E14E58299BC41E7FB10C701130C5CB27';
        //订单id
        $sdcustomno = $orderid;
        //订单金额
        $orderAmount = $infodata['money'] * 100;
        //支付方式
        $cardno = 41;
        //支付状态通知页面
        $noticeurl = 'http://'.module_config('agent.agent_payurl').'/index.php/cms/pay/dbpaycess/';
        // echo $noticeurl;
        // exit;
        //支付成功跳转页面
        $backurl = "http://pay.doulai.com/index.php/cms/pay/index/";
        //备注
        $mark = $orderAmount;
        //
        $Md5str = 'customerid=' . $customerid . '&sdcustomno=' . $sdcustomno . '&orderAmount=' . $orderAmount . '&cardno=' . $cardno . '&noticeurl=' . $noticeurl . '&backurl=' . $backurl . $key;
        //md5验证字符串
        $sign = strtoupper(md5($Md5str));
        //接口地址
        $gourl = "http://api.51upay.com/PayMegerHandler.ashx?customerid=" . $customerid . '&sdcustomno=' . $sdcustomno . '&orderAmount=' . $orderAmount . '&cardno=' . $cardno . '&noticeurl=' . $noticeurl . '&backurl=' . $backurl . '&sign=' . $sign . '&mark=' . $mark;
        $res = $this->ihttp_request($gourl, '', 'get', '');
        //请求成功，跳转到第三方支付
        exit($res);
    }

    public function dbpaycess()
    {
        //获取连连支付的通知返回参数，可参考技术文档中服务器异步通知参数列表
        $no_order = input('sdcustomno');//商户订单号
        // $oid_paybill = input('sd51no');//连连支付单号
        $result_pay = input('state');//支付结果，SUCCESS：为支付成功
        $money_order = input('ordermoney');// 支付金额
         // $ddd=$no_order."/////".$oid_paybill."/////".$result_pay."/////".$money_order;
        if($result_pay == 1){
            //请在这里加上商户的业务逻辑程序代(更新订单状态、入账业务)
            //——请根据您的业务逻辑来编写程序——
            //payAfter($llpayNotify->notifyResp);

            // 使用通知里的 "微信支付订单号" 或者 "商户订单号" 去自己的数据库找到订单  
            $order = Db::table('ien_pay_log')->where('payid',$no_order)->find();

            // DB::table('ien_pay_log')->where('id', 40)->update(array('payid' => $no_order));
            // echo DB::getLastSql();
            if ($order['status']==1) {
                die;
            }
            //通过订单获取商品信息
            $infodata=DB::table('ien_cuxiao')->where('id',$order['cxid'])->find();
            $score = $infodata['score'];
            $coin = $infodata['coin'];
            $money = $infodata['money'];
            $typeday = $infodata['type'];
            $day = $infodata['day'];

           //增加VIP天数
            if ($typeday == 2) {
                // 不是已经支付状态则修改为已经支付状态
                Db::table('ien_pay_log')->where( 'payid' , $no_order )->update(['status' => '1','paytime' => time()]);
                $uinfo=Db::table('ien_admin_user')->where( 'openid' , $order['uid'] )->find();
                if ($uinfo['isvip'] = 0 || $uinfo['vipetime'] < time()) {
                    $datatimer=time()+$day*86400;
                    Db::table('ien_admin_user')
                  ->where( 'openid' , $order['uid'])
                  ->update(['isvip' => '1','vipstime' => time(),'vipetime'=>$datatimer]);
                } else {
                    $datatimer=$uinfo['vipetime']+$day*86400;
                    Db::table('ien_admin_user')
                  ->where( 'openid' , $order['uid'])
                  ->update(['isvip' => '1','vipetime'=>$datatimer]);
                }
            } else if ($typeday == 1) {
                // 不是已经支付状态则修改为已经支付状态
                Db::table('ien_pay_log')->where( 'payid' , $no_order )->update(['status' => '1','paytime' => time(),'coin' => $coin,'score' => $score]);
                Db::table('ien_admin_user')->where( 'openid' , $order['uid'] )->setInc('coin', $coin);
                Db::table('ien_admin_user')->where( 'openid' , $order['uid'] )->setInc('score', $score);
                // 添加获得书币日志
                $newuserCfg = Db::table('ien_admin_user')->where( 'openid' , $order['uid'] )->find();
                $this->addCoinLog($order['userid'], $score, $coin, 1, '第三方支付充值'.$money.'元获得，赠送书币余额：'.$newuserCfg['score']. '充值书币余额：'. $newuserCfg['coin'], $order['id']);
            }
                //判断是否黑单
              //   $paylog=Db::table('ien_pay_log')->where( 'payid' , $no_order )->find();
              //   if($paylog['isout']!=1)
              //   {
              //   //充值成功给代理商增加余额
              //   $dl=DB::table('ien_admin_user')->where( 'openid' , $order['uid'] )->find();
              //   if($dl['tgid'])
              //   {
              //     $tg=DB::table('ien_agent')->where('id',$dl['tgid'])->find();
              //     if($tg['uid'])
              //     {
              //       $dls=DB::table('ien_admin_user')->where('id',$tg['uid'])->find();
              //       if($dls['fcbl'])
              //       {
              //         $fy=$dls['fcbl'];
              //       }
              //       else
              //       {
              //         $fy=0.6;
              //       }
              //       $moneyjs=$money * $fy;
              //       Db::table('ien_admin_user')->where( 'id' , $tg['uid'] )->setInc('money', $moneyjs);
              //       //渠道商如果大于代理商比例,增加差价利润
              //       if($dls['did']!="" || $dls['did']!=0)
              //       {
              //         $qds=DB::table('ien_admin_user')->where('id',$dls['did'])->value('fcbl');
              //         if($qds!="" && $qds>$fy)
              //         {
              //           $cha=$qds-$fy;
              //           $moneqds=$money * $cha;
              //           Db::table('ien_admin_user')->where( 'id' , $dls['did'] )->setInc('money', $moneqds);
              //         }

              //       }
              //     }
              //   }
              // }
          }
          file_put_contents("log.txt", "异步通知 验证成功\n", FILE_APPEND);
          die("{'ret_code':'0000','ret_msg':'交易成功'}"); //请不要修改或删除
          /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        }
    protected function options(){ //选项设置  

        $config = [
          // ...
            'payment' => [
                'merchant_id' => module_config('wechat.merchant'),
                    'key' => module_config('wechat.key'),
                'cert_path' => ROOT_PATH.'wpay/apiclient_cert.pem', // XXX: 绝对路径！！！！
                'key_path' => ROOT_PATH.'wpay/apiclient_key.pem',      // XXX: 绝对路径！！！！
                'notify_url'         => url('cms/pay/paySuccess'), 
                // 'device_info'     => '013467007045764',
                // 'sub_app_id'      => '',
                // 'sub_merchant_id' => '',
                // ...
        ],
          // ..
        ];
        $config2 = module_config('wechat');
        $config = array_merge($config, $config2);
        return $config;
    }

    public function index($error = null, $cxid = 0) {
        $chapterId = input('cid') ? input('cid') : 0;
        $bookId = input('bid') ? input('bid') : 0;
        /*登陆验证方法*/
        session_start();
        $url = 'pay/index.html?bid=' . $bookId .'&cid='.$chapterId;


        if($_SERVER['HTTP_HOST'] != module_config('agent.agent_payurl'))
        {
            $a='location:http://'. module_config('agent.agent_payurl').'/index.php/cms/'.$url;
            header($a);
        }

        $_SESSION['target_url'] = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];


        if(empty($_SESSION['wechat_user'])){
            $this->redirect('oauth/oauth');
            //$this-> checklogin();
        }
        if (!empty($chapterId)) {
            $chapter=DB::table('ien_chapter')->where('id',$chapterId)->find();
            $this->assign('chaptername', $chapter['title']);
            $book=Db::table('ien_book')->field('title,id,packsell,zishu,price')->where('id', $chapter['bid'])->find();
            $bookprice = !empty($book['price']) ? coinToMoney($book['price']) : coinToMoney(getCostScore($book['zishu']));
            $original = coinToMoney(getOriginal($book['id']));
            $this->assign('book', $book);
            $this->assign('bookprice', $bookprice);
            $this->assign('original', $original);
            $this->assign('chapterzishu', $chapter['zishu']);
            $this->assign('shubi', floor($chapter['zishu'] / 100.0));
        }

        $openid=$_SESSION['wechat_user']['original']['openid'];
        $user = DB::table('ien_admin_user')->where('openid',$openid)->find();
        $usercoin = $user['coin'] + $user['score'];
        $this->assign('user', $user);
        $this->assign('usercoin', $usercoin );
        $this->assign('chapterId', $chapterId);
        $this->assign('bookid', $bookId);
        $this->assign('err', $error ? $error : 0);
        $this->assign('zffs', module_config('agent.agent_pay_fangshi'));

        //获取商品信息
        $cuxiaotitle="";
        $cuxiaoshij="";
        $pro=DB::table('ien_cuxiao')->where('leixing',1)->order('orderby asc')->select();
        $noselect = Db::table('ien_cuxiao')->where('leixing',1)->where('status',1)->select();

        // var_dump($pro);exit();
        if(!empty($bookId)){
            $curtime = time();
            //是否有活动进行中
            $bookactive = DB::table('ien_free_limit_book')->where(['bid' => $bookId, 'type' => 1])->select();
            $bookactiveid = array();
            foreach($bookactive as $val){
                $bookactiveid[] = $val['xsid'];
            }
            $booklist = DB::table('ien_free_limit_book')->whereIn('bid', $bookId)->where('type', 1)->select();
            $activeid = array();
            foreach ($booklist as $val) {
                $activeid[] = $val['xsid'];
            }
            $active = DB::table('ien_cuxiaolist')->where(['starttime' => ['<', $curtime], 'endtime' => ['>', $curtime], 'is_delete' => '0', 'status' => 1, 'type' => 1])->whereIn('id', implode(',', $activeid))->order('createtime asc')->find();
            if(empty($active)){
                $subsql = DB::table('ien_free_limit_book')->field('count(*) as num,xsid')->where('type', 1)->group('xsid')->buildSql();
                $activelist = DB::table('ien_cuxiaolist')
                ->join($subsql . ' temp', 'ien_cuxiaolist.id=temp.xsid', 'left')
                ->field('ien_cuxiaolist.*,ifnull(temp.num,0) as num')
                ->where(['starttime' => ['<', $curtime], 'endtime' => ['>', $curtime], 'is_delete' => '0', 'status' => 1, 'type' => 1])
                ->order('createtime asc')
                ->select();
                foreach($activelist as $val){
                    if(empty($val['num'])){
                        $active = $val;
                        break;
                    }
                }
            }

                //是否第一次充值
            $is_first = DB::table('ien_pay_log')->where(['userid' => $user['id'], 'status' => 1])->find();
            if(empty($is_first) && !empty($active)){
                $pro = DB::table('ien_cuxiao')->where(['leixing' => 3, 'cxid' => $active['id']])->order('orderby asc')->select();
                $noselect = Db::table('ien_cuxiao')->where(['leixing' => 3, 'status' => 1])->select();
            }

        }
        // if(!empty($cxid))
        // {
        //     $cuxiao=DB::table('ien_cuxiaolist')->where('id',$cxid)->whereTime('endtime','>',time())->find();
        //     if(empty($cuxiao))
        //     {
        //       $this->redirect($url);
        //     }
        //     $pro=DB::table('ien_cuxiao')->where('cxid',$cxid)->where('leixing',2)->order('orderby asc')->select();
        //     $this->assign('pro', $pro);
        //     $this->assign('cuxiaotitle', $cuxiao['name']);
        //     $cuxiaoshij="活动日期:".date("Y/m/d",$cuxiao['starttime'])."-".date("Y/m/d",$cuxiao['endtime']);
        //     $this->assign('cuxiaoshij', $cuxiaoshij);
        // }
        // else
        // {

//        dump($pro);
//        die;
        $this->assign('noselect', $noselect);
        $this->assign('pro', $pro);
        // }

        return $this->fetch(); // 渲染模板
    }
    
    public function savellpayid($id=null) {
        session_start();
        if ($id=='') {
            header("status: 400 Bad Request");
            return false;
        } else {
            $_SESSION['payprodcutid']=$id;
            return true;
        }
    }

    
    public function addPayLog(){

    }

    /**
     * 支付
     * @param  [type] $id    [description]
     * @param  [type] $error [description]
     * @return [type]        [description]
     */
    public function pay($id=null,$error=null){
        session_start();
        if ($id=='') {
            die("参数错误！");
        }
        $bookid = empty(input('bid')) ? 0 : input('bid');

        $orderid = 'BOOK'.time().rand(10000, 99999);
        //添加代理ID和渠道ID
        $userCfg = DB::table('ien_admin_user')->where('openid',$_SESSION['wechat_user']['original']['openid'])->find();
        if($userCfg){
            if ($userCfg['tgid'] > 0) {
                $agentCfg = DB::table('ien_agent')->where('id',$userCfg['tgid'])->find();
                // if ($bookid == 0) {
                //     $bookid = DB::table('ien_chapter')->where('id',$agentCfg['zid'])->value('bid');
                // }
                if (!empty($agentCfg)) {
                    $did = $agentCfg['uid'];
                    if (!empty($userCfg['did'])) {
                        $qid = $userCfg['did'];
                    } else {
                        $qid = 0;
                    }
                } else {
                    $did=0;
                    $qid=0;
                }
            } else {
                $did=0;
                $qid=0;
            }
            $data = ['uid' => $userCfg['openid'],
                'userid' => $userCfg['id'],
                'type' => '1',
                'status' => '0',
                'addtime' => time(),
                'paytime' => '0',
                'payid' => $orderid,
                'did' => $did,
                'qid' => $qid,
                'isout' => 0,
                'tgid' =>$userCfg['tgid'],
                'bookid' => $bookid ? $bookid : 0,
            ];
            //判断超期用户订单
            if ($userCfg['isout'] == 1) {
                $data['isout'] = 1;
            }
            //判断比例黑单
            $bili = module_config('agent.agent_klbili');                        // ''
            $nokou = explode(',',module_config('agent.agent_nokou'));  // ''
            if ($bili > 0) {

                if (!in_array($userCfg['sxid'],$nokou)  || empty($nokou['0'])) {
                    $kl=mt_rand(1,$bili);
                    if ($kl<=2) {
                        $data['isout']=1;
                    }
                }
            }

            //设置订单金额类型商品ID
            $infodata = DB::table('ien_cuxiao')->where('id',$id)->find();


            $data['money'] =$infodata['money'];     // 需要支付的金额
            if ($infodata['type']==1) {         // 类型为1 普通充值书币
                $data['paytype'] =2;
            } else {                        // 类型为2  充值年卡
                $data['paytype'] =1;
            }
            $data['cxid']=$id;
            $product = [
                'body'             => '书币充值 - '.$data["money"].' 元',
                'trade_type'       => 'JSAPI',
                'out_trade_no'     => $orderid,
                'total_fee'        => $data['money'] * 100,
                'notify_url'       => 'http://'.module_config('agent.agent_payurl').'/index.php/cms/pay/paysuccess',
                'openid'           => $_SESSION['wechat_user']['original']['openid'],
                'attach'           => $id,
            ];

            $order = new Order($product);

            $app = new Application($this->options());
            $payment = $app->payment;

            $result = $payment->prepare($order);
            $prepayId = null;

            if ($result->return_code == 'SUCCESS' && $result->result_code == 'SUCCESS') {
                $prepayId = $result->prepay_id;
                Db::table('ien_pay_log')->insert($data);
            } else {
                die("出错了。");
            }
            $json = $payment->configForPayment($prepayId);

            // 这个是jssdk里页面上需要用到的js参数信息。
            // $this->assign('json', $json);
            // $this->assign('ordsn', $order->ordsn);
            return $json; // 渲染模板
        }

    }

    public function paysuccess(){
        $options = $this->options();
        $app = new Application($options);
        $response = $app->payment->handleNotify(function($notify, $successful) {
            // 使用通知里的 "微信支付订单号" 或者 "商户订单号" 去自己的数据库找到订单
            $order = Db::table('ien_pay_log')->where('payid',$notify->out_trade_no)->find();
            // 通过订单获取商品信息
            $infodata=DB::table('ien_cuxiao')->where('id',$order['cxid'])->find();
            $score = $infodata['score'];
            $coin = $infodata['coin'];
            $money = $infodata['money'];
            $typeday = $infodata['type'];
            $day = $infodata['day'];

            if (count($order) == 0) { // 如果订单不存在
                return 'Order not exist.'; // 告诉微信，我已经处理完了，订单没找到，别再通知我了
            }

            // 如果订单存在
            // 检查订单是否已经更新过支付状态
            if ($order['paytime']) { // 假设订单字段“支付时间”不为空代表已经支付
                return true; // 已经支付成功了就不再更新了
            }
            // 用户是否支付成功
            if ($successful) {
                //增加VIP天数
                if ($typeday == 2) {
                    // 不是已经支付状态则修改为已经支付状态
                    Db::table('ien_pay_log')->where( 'payid' , $order['payid'] )->update(['status' => '1','paytime' => time()]);
                    $uinfo=Db::table('ien_admin_user')->where( 'openid' , $order['uid'] )->find();
                    if ($uinfo['isvip'] = 0 || $uinfo['vipetime'] < time()) {
                        $datatimer=time() + $day * 86400;
                        Db::table('ien_admin_user')
                      ->where( 'openid' , $order['uid'])
                      ->update(['isvip' => '1','vipstime' => time(),'vipetime'=>$datatimer]);
                    } else {
                        $datatimer=$uinfo['vipetime'] + $day * 86400;
                        Db::table('ien_admin_user')
                      ->where( 'openid' , $order['uid'])
                      ->update(['isvip' => '1','vipetime'=>$datatimer]);
                    }
                } else if ($typeday == 1) {
                    // 不是已经支付状态则修改为已经支付状态
                    Db::table('ien_pay_log')->where( 'payid' , $order['payid'] )->update(['status' => '1','paytime' => time(),'coin' => $coin,'score' => $score]);
                    Db::table('ien_admin_user')->where( 'openid' , $order['uid'] )->setInc('coin', $coin);
                    Db::table('ien_admin_user')->where( 'openid' , $order['uid'] )->setInc('score', $score);
                    // 添加获得书币日志
                    $newuserCfg = Db::table('ien_admin_user')->where( 'openid' , $order['uid'] )->find();
                    $this->addCoinLog($order['userid'], $score, $coin, 1, '微信支付充值'.$money.'元获得，赠送书币余额：'.$newuserCfg['score']. '充值书币余额：'. $newuserCfg['coin'], $order['id']);
                }
            } else { // 用户支付失败
                Db::table('ien_pay_log')->where( 'payid' , $notify->out_trade_no )->update(['status' => '0','paytime' => time()]);
            }
            return true; // 返回处理完成
        });
    }

    public function addCoinLog($uid,$score,$coin,$from,$remark,$orderid){
        $data = [
            'userid' => $uid,
            'orderid' => $orderid,
            'score' => $score,
            'coin' => $coin,
            'from' => $from,
            'remark' => $remark,
            'createtime' => time(),
        ];
        return DB::table('ien_addcoin_log')->insert($data);
    }

    public function paybybook($packsell = false){
        session_start();
        $user = DB::table('ien_admin_user')->where('openid',$_SESSION['wechat_user']['original']['openid'])->find();
        $config = Admin::getConfig('agent');
        $bid = empty(input('bid')) ? 0 : input('bid');
        $zid = empty(input('zid')) ? 0 : input('zid');
        // $chapter = DB::table('ien_chapter')->where('id', $zid)->find();
        //$ztotal = DB::table('ien_chapter')->where('bid', $bid)->count();
        $book = DB::table('ien_book')->where('id', $bid)->find();
        $book['total'] = floor(DB::table('ien_chapter')->where('bid', $bid)->where('isvip' , 1)->sum('zishu') / 100);
        $book['xstype'] = intval($book['xstype']) == 0 ? '连载中' : '已完结';
        $book['avtar'] = empty($book['image']) ? $book['cover'] : get_thumb($book['image']);
        $book['taglist'] = explode(',',$book['tag']);

        if (!$book['zishu'])$book['zishu'] = 0;

        if (!$book['price']) {
            $book['price'] = $book['total'];
        }
        if( $book['total'] <= $book['price'] ){
            $book['total'] = floor($book['price'] * 1.35);
        }
        if ($_POST['isPost'] == 1 || $packsell) {
            if (empty($book)) {
              return array('status' => 0, 'message' => '参数错误！','title' => '购买失败'); 
            }
            if (empty($user)) {
              return array('status' => 2, 'message' => '用户没有登录','title' => '购买失败');
            }
            
            $max_zid = DB::table('ien_chapter')->where('bid', $bid)->max('idx');
            $buyed = DB::table('ien_consume_log')->where(['bid' => $bid, 'uid' => $user['openid']])->select();
            if (!empty($buyed)) {
                $summoney = 0;
                foreach ($buyed as $key => $value) {
                    $summoney += $value['money'] + $value['coin'];
                    if($max_zid <= $value['max_zid'])
                    {
                        return array('status' => 5, 'message' => '点击“返回”继续阅读','title' => '已购买');
                    }
                }
                $book['price'] = $book['price'] - $summoney;
                $book['price'] = $book['price'] > 0 ? $book['price'] : 0;
            }

            if (($user['score'] + $user['coin']) < $book['price']) {
              return array('status' => 1, 'message' => '帐户余额不足','title' => '购买失败');
            }
            
            // $res = DB::table('ien_admin_user')->where('openid', $user['openid'])->setDec('score', $book['price']);
            // if (!$res) {
            //   return array('status' => 3, 'message' => '购买失败，刷新页面重试');
            // }
            $data['uid'] = $user['openid'];
            $data['userid'] = $user['id'];
            $score = $book['price'];
            // $data['money'] = $book['price'];
            $data['bid'] = $bid;
            $data['zid'] = $zid;
            $data['max_zid'] = $max_zid;
            $data['is_over'] = $book['status'];
            $data['addtime'] = time();

            // 优先使用赠送书币
            if ($user['score'] >= $score) {
                $data['money'] = $score;
                DB::table('ien_admin_user')->where('openid',$_SESSION['wechat_user']['original']['openid'])->setDec('score',$score);
            } else {
                if($user['score'] > 0){
                    $data['coin'] = $score - $user['score'];
                    $data['money'] = $user['score'];
                    DB::table('ien_admin_user')->where('openid',$_SESSION['wechat_user']['original']['openid'])->setDec('coin', $data['coin']);
                    DB::table('ien_admin_user')->where('openid',$_SESSION['wechat_user']['original']['openid'])->setDec('score', $data['money']);
                } else {
                    $data['coin'] = $score;
                    DB::table('ien_admin_user')->where('openid',$_SESSION['wechat_user']['original']['openid'])->setDec('coin', $score);
                }
            }
            DB::table('ien_consume_log')->insert($data);
            $newuser = DB::table('ien_admin_user')->where('openid', $user['openid'])->find();
            $cur_score = $newuser['score'] + $newuser['coin'];
            return array('status' => 4,
                    'curscore' => $cur_score,
                    'message' => '购买本书花费' . $score . '书币',
                    'title' => '购买成功'
                );
        }
        $this->assign('book', $book);
        $this->assign('chapter', $zid);
        $this->assign('user', $user);
        return $this->fetch();
    }

    public function testpay()
    {
        $id = input('param.');//包含商品id或者书id
      
        $ClientResponseHandler = new \util\ClientResponseHandler();
        $PayHttpClient = new \util\PayHttpClient();
        $RequestHandler = new \util\RequestHandler();
        $data = [];
        $infodata = DB::table('ien_cuxiao')->where('id',$id['id'])->find();
        $data['service'] = 'pay.weixin.jspay';
        $data['mch_id'] = config('mchId');
        // $data['is_raw'] = 1;//是否原生态
        $data['out_trade_no'] = 'BOOK'.date('YmdHis',time()).mt_rand(10000,99999).substr(uniqid(),  0,9);
        $data['body'] = '测试商品';
        // $data['sub_openid'] = $_SESSION['wechat_user']['original']['openid'];
        // $data['sub_openid'] = '';
        // $data['total_fee'] = $infodata['money'];
        $data['total_fee'] = 1;
        $data['mch_create_ip'] = $_SERVER['SERVER_ADDR'];
        // $data['mch_create_ip'] = '127.0.0.1';
        $data['notify_url'] = 'http://'.module_config('agent.agent_payurl').'/index.php/cms/pay/notify_url';//通知地址
        // $data['notify_url'] = 'http://novelt.huotun.com/index.php/cms/pay/notify_url';//通知地址
        $chapterId = isset($id['cid']) ? $id['cid'] : 0;
        if ($chapterId) {
            $data['callback_url'] = 'http://'.module_config('agent.agent_payurl').'/index.php/cms/document/detail/id/'.$chapterId;//交易完成跳转地址
        }else{
            $data['callback_url'] = 'http://'.module_config('agent.agent_payurl').'/index.php/cms/pay/index.html';//交易完成跳转地址
        }
        $data['nonce_str'] = mt_rand(time(),time()+rand());
        $data['sign'] = $RequestHandler->createSign($data);
        import('util.Utils', EXTEND_PATH);
        $Utils = new \Utils();
        $postData = $Utils::toXml($data);
        $url = config('url');
        $xmldata = $this->ihttp_request($url,$postData,'post','');
        // echo($xmldata);exit();
        $postData = $Utils::parseXML($xmldata);
        if (isset($postData['token_id'])) {
            /**********************/
            $bookid = empty($id['bid']) ? 0 : $id['bid'];//书id
            //添加代理ID和渠道ID
            $userCfg = DB::table('ien_admin_user')->where('openid',$_SESSION['wechat_user']['original']['openid'])->find();
            if($userCfg){
                if ($userCfg['tgid'] > 0) {
                    $agentCfg = DB::table('ien_agent')->where('id',$userCfg['tgid'])->find();
                    if (!empty($agentCfg)) {
                        $did = $agentCfg['uid'];
                        if (!empty($userCfg['did'])) {
                            $qid = $userCfg['did'];
                        } else {
                            $qid = 0;
                        }
                    } else {
                        $did=0;
                        $qid=0;
                    }
                } else {
                    $did=0;
                    $qid=0;
                }
                $insert = ['uid' => $userCfg['openid'],
                    'userid' => $userCfg['id'],
                    'type' => '1',
                    'status' => '0',
                    'addtime' => time(),
                    'paytime' => '0',
                    'payid' => $data['out_trade_no'],
                    'did' => $did,
                    'qid' => $qid,
                    'isout' => 0,
                    'tgid' =>$userCfg['tgid'],
                    'bookid' => $bookid,
                ];
                //判断超期用户订单
                if ($userCfg['isout'] == 1) {
                    $insert['isout'] = 1;
                }
                //判断比例黑单
                $bili = module_config('agent.agent_klbili');
                $nokou = explode(',',module_config('agent.agent_nokou'));
                if ($bili > 0) {
                    if (!in_array($userCfg['sxid'],$nokou)  || empty($nokou['0'])) {
                        $kl=mt_rand(1,$bili);
                        if ($kl<=2) {
                            $insert['isout']=1;
                        }
                    }
                }
                //设置订单金额类型商品ID
                $infodata = DB::table('ien_cuxiao')->where('id',$id['id'])->find();
                $insert['money'] =$infodata['money'];
                if ($infodata['type']==1) {
                    $insert['paytype'] =2;
                } else {
                    $insert['paytype'] =1;
                }
                $insert['cxid']=$id['id'];
                Db::table('ien_pay_log')->insert($insert);
                // echo(Db::getLastSql());exit();
            }
        /**********************/
            return $postData;
        }else{
            return 0;
        }
    }

    public function notify_url()
    {
        import('util.Utils', EXTEND_PATH);
        $Utils = new \Utils();
        $xmldata=file_get_contents("php://input"); 
        $postData = $Utils::parseXML($xmldata);

        // 使用通知里的 "微信支付订单号" 或者 "商户订单号" 去自己的数据库找到订单
        $order = Db::table('ien_pay_log')->where('payid',$postData['out_trade_no'])->find();
        // 通过订单获取商品信息
        $infodata=DB::table('ien_cuxiao')->where('id',$order['cxid'])->find();
        $score = $infodata['score'];
        $coin = $infodata['coin'];
        $money = $infodata['money'];
        $typeday = $infodata['type'];
        $day = $infodata['day'];

        if (count($order) == 0) { // 如果订单不存在
            return 'Order not exist.'; // 告诉微信，我已经处理完了，订单没找到，别再通知我了
        }

        // 如果订单存在
        // 检查订单是否已经更新过支付状态
        if ($order['status'] == 1) { // 假设订单字段“支付时间”不为空代表已经支付
            return "success"; // 已经支付成功了就不再更新了
        }
        // 用户是否支付成功
        if ($postData['result_code'] == 0) {
            //增加VIP天数
            if ($typeday == 2) {
                // 不是已经支付状态则修改为已经支付状态
                Db::table('ien_pay_log')->where( 'payid' , $order['payid'] )->update(['status' => 1,'paytime' => time()]);
                $uinfo=Db::table('ien_admin_user')->where( 'openid' , $order['uid'] )->find();
                if ($uinfo['isvip'] = 0 || $uinfo['vipetime'] < time()) {
                    $datatimer=time() + $day * 86400;
                    Db::table('ien_admin_user')
                  ->where( 'openid' , $order['uid'])
                  ->update(['isvip' => '1','vipstime' => time(),'vipetime'=>$datatimer]);
                } else {
                    $datatimer=$uinfo['vipetime'] + $day * 86400;
                    Db::table('ien_admin_user')
                  ->where( 'openid' , $order['uid'])
                  ->update(['isvip' => '1','vipetime'=>$datatimer]);
                }
            } else if ($typeday == 1) {
                // 不是已经支付状态则修改为已经支付状态
                Db::table('ien_pay_log')->where( 'payid' , $order['payid'] )->update(['status' => 1,'paytime' => time(),'coin' => $coin,'score' => $score]);
                Db::table('ien_admin_user')->where( 'openid' , $order['uid'] )->setInc('coin', $coin);
                Db::table('ien_admin_user')->where( 'openid' , $order['uid'] )->setInc('score', $score);
                // 添加获得书币日志
                $newuserCfg = Db::table('ien_admin_user')->where( 'openid' , $order['uid'] )->find();
                $this->addCoinLog($order['userid'], $score, $coin, 1, '微信支付充值'.$money.'元获得，赠送书币余额：'.$newuserCfg['score']. '充值书币余额：'. $newuserCfg['coin'], $order['id']);
            }
        } else { // 用户支付失败
            Db::table('ien_pay_log')->where( 'payid' ,$postData['out_trade_no'])->update(['status' => 0,'paytime' => time()]);
            return "fail";
        }
        return "success"; // 返回处理完成


    }


}