<?php

namespace app\wechat\home;

use think\Db;
use EasyWeChat\Message\Text;    // 文本消息
use app\index\controller\Home;
/*
 * 处理接收到的除事件消息
 * 方法名全部小写
 */

class Event extends AutoReply
{
    protected $message;
    protected $app;

    public function __construct($message, $app)
    {
        $this->message = $message;
        $this->app = $app;

    }

    // 关注
    public function subscribe()
    {
        $openid=$this->message->FromUserName;
        $userupdate = $this->app->user->get($openid);
        $ticket = $this->message->Ticket;
        $dbuser = DB::table('ien_admin_user')->where('openid', $openid)->find();
        //更新用户信息
        $userdata = [
             'username'     => $userupdate['nickname'], 
             'nickname'     => $userupdate['nickname'], 
             'avatar'       => $userupdate['headimgurl'],
             'sex'          => $userupdate['sex'],
             'isguanzhu'    => 1,
             'guanzhued'    => 1,
        ];
        if(strpos($this->message->EventKey, 'qrscene_') !== false && !empty($this->message->Ticket)){
            $qrscene = explode('_', $this->message->EventKey);
            $qrcodeInfo = DB::table('ien_wechat_qrcode')->where('scene_id', $qrscene[1])->find();
            if(empty($dbuser)){
                $newuser = [
                    'username' => "读者",
                    'nickname' => "读者",
                    'password' => '$2y$10$wwJ7bP4SLfGWZ3.DTQ0RdeglgBLAW5iY4mA6LvoDuQrvcV6qsKdou',
                    'email'    =>  " ", 
                    'avatar'    => "http://".module_config('agent.agent_rooturl')."/images/homeuser.png",
                    'create_time'    => time(),
                    'openid'    => $openid,
                    'role'      =>   '3',
                    'tgid'      => 0,
                    'bid'      => 0,
                    'status'    => '1', 
                    'sxid'      =>0,
                    'did' => 0,
                    'isvip' => 0,
                    'vipstime' => '',
                    'vipetime' => '',
                    'fcbl'  => '',
                    'xingming'  => '',
                    'fangshi' => '',
                    'zhanghao' => '',
                    'sex'  => 0,
                    'guanzhu'  => 0,
                    'ewm'  => '',
                    'isguanzhu'  => 0,
                    'isout'  => 0,
                    'gzopenid'   => '',
                    'tuijian'  => 0,
                    'ticket' => 0,
                    'scene_id' => $qrscene[1],
                    'guanzhued' => 1
                ];
                $uid = Db::table('ien_admin_user')->insertGetId($newuser);
                $comefrom_log = array(
                    'uid' => empty($dbuser) ? $uid : $dbuser['id'],
                    'scene_id' => $qrscene[1],
                    'comefrom' => '',
                    'is_new' => empty($dbuser) ? 1 : 0,
                    'create_time' => time()
                );
            }
            if(!empty($dbuser)){
                $exist = DB::table('ien_comefrom_log')->where(['uid' => $dbuser['id'], 'scene_id' => $qrscene[1]])->find();
                if(empty($exist)){
                    $comefrom_log = array(
                        'uid' => empty($dbuser) ? $uid : $dbuser['id'],
                        'scene_id' => $qrscene[1],
                        'comefrom' => '',
                        'is_new' => empty($dbuser) ? 1 : 0,
                        'create_time' => time()
                    );
                }
            }
            DB::table('ien_comefrom_log')->insert($comefrom_log);
            $qrcode_log = array(
                'uid' => empty($dbuser) ? $uid : $dbuser['id'],
                'openid' => $openid,
                'scene_id' => $qrscene[1],
                'isnew' => empty($dbuser) ? 1 : 0,
                'create_time' => time()
            );
            DB::table('ien_qrcode_log')->insert($qrcode_log);
        }

            $data = $this->needAutoReply('subscribe');
        DB::table('ien_admin_user')->where('openid',$openid)->update($userdata);
        if ($data) { // 设置了自动回复
            $res = $this->reply($data, $this->app);
            if ($res !== false) {
                return $res;
            }
        }
        // 没设自动回复就执行下面代码
        $text = new Text();

        $old=DB::table('ien_read_log')->where('uid',$openid)->order('update_time desc')->find();
        $recp=DB::table('ien_chapter')->where('id',$old['zid'])->find();
        if(!empty($recp['title'])){
            $conten='欢迎关注,您上次看到了';  
            $conten.="\n";
            $conten.="\n";
            $conten.=$recp['title'];
            $conten.="\n";
            $conten.="\n";
            $conten.='<a href="http://'.$_SERVER['HTTP_HOST'].'/index.php/cms/user/readold/openid/'.$openid.'">【点此继续阅读】</a>';
            $conten.="\n";
            $conten.="\n";
            $conten.='为方便小词阅读,请置顶公众号!';
        } else {
            $conten='终于等到你!';
            $conten.="\n";
            $conten.="\n";
            $conten.='请点击下方按钮阅读小说';
            $conten.="\n";
            $conten.="\n";
            $conten.='<a href="http://'.$_SERVER['HTTP_HOST'].'/index.php/cms/user/readold/openid/'.$openid.'">【点此直接阅读】</a>';
            $conten.="\n";
            $conten.="\n";
            $conten.='为方便小词阅读,请置顶公众号!';
        }
        $text->content = $conten;    // 文本内容
        //$text->content = $this->message->ToUserName; 公众号ID

        return $text;
    }

    // 取消关注
    public function unsubscribe()
    {
        //取消关注更新用户
        $openid=$this->message->FromUserName;
        //更新用户信息
        $userdata = [
                     'isguanzhu'       =>0,
                    ];
        DB::table('ien_admin_user')->where('openid',$openid)->update($userdata);
        DB::table('ien_admin_user')->where('gzopenid',$openid)->update($userdata);

        trace('用户' . $this->message->FromUserName . '已取消关注了本微信', 'info');
    }

    // 上报地理位置事件
    // 用户同意上报地理位置后，每次进入公众号会话时，都会在进入时上报地理位置，或在进入会话后每5秒上报一次地理位置，公众号可以在公众平台网站中修改以上设置。上报地理位置时，微信会将上报地理位置事件推送到开发者填写的URL。
    public function location()
    {
        $data = $this->needAutoReply('location_event');
        if ($data) { // 设置了自动回复
            $res = $this->reply($data, $this->app);
            if ($res !== false) {
                return $res;
            }
        }

        // 没设自动回复就执行下面代码
        $text = new Text();
        $text->content = '我们已收到您上报的地理位置事件 纬度: ' . $this->message->Latitude . ' 经度: ' . $this->message->Longitude;    // 文本内容
        return $text;


    }

    // 点击自定义菜单事件
    public function click()
    {
        $data = $this->needAutoReply('click');
		
		$mode1_data = Db::name('we_reply')->where(['keyword' => $this->message->EventKey, 'msg_type' => 'text', 'expires_date' => ['>', time()], 'status' => 1])->order('id desc')->find();
        if (isset($mode1_data) && $mode1_data['mode'] == 1) {   // 设置了自动回复。完整匹配
            $res = $this->reply($mode1_data, $this->app);
            if ($res !== false) {
                return $res;
            }
        }

        $mode0_data = Db::name('we_reply')->where('keyword', 'like', '%' . $this->message->EventKey . '%')->where(['msg_type' => 'text', 'expires_date' => ['>', time()], 'status' => 1])->order('id desc')->find();
        if (isset($mode0_data) && $mode0_data['mode'] == 0) {   // 设置了自动回复。模糊搜索
            $res = $this->reply($mode0_data, $this->app);
            if ($res !== false) {
                return $res;
            }
        }

        // 没设自动回复就执行下面代码
        $text = new Text();
        $text->content = '我们已收到您反馈的内容：' . $this->message->EventKey;    // 文本内容
        return $text;
		
		
       /* if ($data) { // 设置了自动回复
            $res = $this->reply($data, $this->app);
            if ($res !== false) {
                return $res;
            }
        }

        // 没设自动回复就执行下面代码
        $text = new Text();
        $text->content = '用户点击了自定义菜单: ' . $this->message->EventKey;    // 文本内容
        return $text;*/
    }

    /**
     * 已经关注过再扫二维码推送事件
     * @return [type] [description]
     */
    public function scan()
    {
        $openid=$this->message->FromUserName;
        $dbuser = DB::table('ien_admin_user')->where('openid', $openid)->find();
        if(!empty($this->message->EventKey) && !empty($this->message->Ticket)){
            $qrcodeInfo = DB::table('ien_wechat_qrcode')->where('scene_id', $this->message->EventKey)->find();
            $scene_id = $this->message->EventKey;
            $exist = DB::table('ien_comefrom_log')->where(['uid' => $dbuser['id'], 'scene_id' => $scene_id])->find();
            if(empty($exist)){
                $comefrom_log = array(
                    'uid' => $dbuser['id'],
                    'scene_id' =>$scene_id,
                    'comefrom' => '',
                    'is_new' => 0,
                    'create_time' => time()
                );
                DB::table('ien_comefrom_log')->insert($comefrom_log);
            }
            $qrcode_log = array(
                'uid' => $dbuser['id'],
                'openid' => $openid,
                'scene_id' => $this->message->EventKey,
                'isnew' => 0,
                'create_time' => time()
            );
            DB::table('ien_qrcode_log')->insert($qrcode_log);
        }
        $data = $this->needAutoReply('subscribe');
        if ($data) { // 设置了自动回复
            $res = $this->reply($data, $this->app);

            if ($res !== false) {
                return $res;
            }
        }
        // 没设自动回复就执行下面代码
        $text = new Text();

        $old=DB::table('ien_read_log')->where('uid',$openid)->order('update_time desc')->find();
        $recp=DB::table('ien_chapter')->where('id',$old['zid'])->find();
        if(!empty($recp['title'])){
            $conten='欢迎关注,您上次看到了';  
            $conten.="\n";
            $conten.="\n";
            $conten.=$recp['title'];
            $conten.="\n";
            $conten.="\n";
            $conten.='<a href="http://'.$_SERVER['HTTP_HOST'].'/index.php/cms/user/readold/openid/'.$openid.'">【点此继续阅读】</a>';
            $conten.="\n";
            $conten.="\n";
            $conten.='为方便小词阅读,请置顶公众号!';
        } else {
            $conten='终于等到你!';
            $conten.="\n";
            $conten.="\n";
            $conten.='请点击下方按钮阅读小说';
            $conten.="\n";
            $conten.="\n";
            $conten.='<a href="http://'.$_SERVER['HTTP_HOST'].'/index.php/cms/user/readold/openid/'.$openid.'">【点此直接阅读】</a>';
            $conten.="\n";
            $conten.="\n";
            $conten.='为方便小词阅读,请置顶公众号!';
        }
        $text->content = $conten;    // 文本内容
        //$text->content = $this->message->ToUserName; 公众号ID

        return $text;
    }
}