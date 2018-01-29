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

    protected function options(){ //选项设置  

    	

        

            $config = [

              // ...

                'payment' => [

		            'merchant_id' => '1480077682',

		            'key' => '4iJ1tJikXI22zZ6lFY6hgGFyMQXnIErG',

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

    public function index()

    {
		/*登陆验证方法*/
		session_start();
        $_SESSION['target_url'] = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        if(empty($_SESSION['wechat_user'])){
            $this->redirect('oauth/oauth');
            //$this-> checklogin();
        }
		
		$openid=$_SESSION['wechat_user']['original']['openid'];
		$user=DB::table('ien_admin_user')->where('openid',$openid)->find();
		$this->assign('user', $user);
    	//print_r( cookie('wechat_user'));

        return $this->fetch(); // 渲染模板

    



    }







    public function pay($id=null){


	session_start();


   		if($id==''){die("参数错误！");}



   		$orderid='BOOK'.time().rand(10000,99999);



   		$data = ['uid' =>$_SESSION['wechat_user']['original']['openid'],

   				 'type' => '1',

   				 'status' => '0',

   				 'addtime' => time(),

   				 'paytime' => '0',

   				 'payid' => $orderid,

   				 ];

   		switch ($id) {

    		case 1:

    		$data['money'] =30;

   			$data['paytype'] =2;

   		break;

    		case 2:

        	$data['money'] =50;

   			$data['paytype'] =2;

        break;

    		case 3:

        	$data['money'] =100;

   			$data['paytype'] =2;

        break;

       		case 4:

        	$data['money'] =200;

   			$data['paytype'] =2;

        break;

       		case 5:

        	$data['money'] =365;

   			$data['paytype'] =1;

        break;

		}

		

		$product = [  

		    'body'             => '书币充值 - '.$data["money"].' 元',  

		    'trade_type'       => 'JSAPI',  

		    'out_trade_no'     => $orderid,  

		    'total_fee'        => $data['money']*100,  

		  	'notify_url'         => 'http://book.huotun.com/index.php/cms/pay/paysuccess', 

		    'openid'           => $_SESSION['wechat_user']['original']['openid'],  

		    'attach'           => $id,  

		];

		  

		$order = new Order($product);  

		  

		$app = new Application($this->options());  

		$payment = $app->payment;  

		$result = $payment->prepare($order);  

		$prepayId = null;  

		if ($result->return_code == 'SUCCESS' && $result->result_code == 'SUCCESS'){  

		    $prepayId = $result->prepay_id;



		    Db::table('ien_pay_log')->insert($data);  

		} else {  

		    var_dump($result);  

		    die("出错了。");  

		}  

		$json = $payment->configForPayment($prepayId);  

		  // 这个是jssdk里页面上需要用到的js参数信息。 

		//print_r($json);

		$this->assign('json', $json);

        $this->assign('ordsn', $order->ordsn); 

        

		return $this->fetch(); // 渲染模板

    



    }  

     public function paysuccess(){  

        $options = $this->options();  

        $app = new Application($options);  

        $response = $app->payment->handleNotify(function($notify, $successful){  

            // 使用通知里的 "微信支付订单号" 或者 "商户订单号" 去自己的数据库找到订单  

            $order = Db::table('ien_pay_log')->where('payid',$notify->out_trade_no)->find();

            switch ($notify->attach) {

	    		case 1:

	    		$score=3000;

	   		break;

	    		case 2:

	        	$score=8000;

	        break;

	    		case 3:

	        	$score=18000;

	        break;

	       		case 4:

	        	$score=40000;

	        break;

	       		case 5:

	        	$score=0;

	        break;

	        } 

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

                // 不是已经支付状态则修改为已经支付状态  

                Db::table('ien_pay_log')->where( 'payid' , $notify->out_trade_no )->update(['status' => '1','paytime' => time()]);

             	Db::table('ien_admin_user')->where( 'openid' , $notify->openid )->setInc('score', $score);

             	if($score==0){

             		Db::table('ien_admin_user')

             		->where( 'openid' , $notify->openid )

             		->update(['isvip' => '1','vipstime' => time(),'vipetime'=>strtotime("next year")]);

             	}

            } else { // 用户支付失败  

                Db::table('ien_pay_log')->where( 'payid' , $notify->out_trade_no )->update(['status' => '0','paytime' => time()]);

            }  





            





            

            return true; // 返回处理完成  

        });  

    }  

   

}