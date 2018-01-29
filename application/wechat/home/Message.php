<?php

namespace app\wechat\home;

use EasyWeChat\Message\Text;    // 文本消息
use think\Db;
use EasyWeChat\Core\AccessToken;

/*
 * 处理接收到的除事件外的消息
 * 方法名全部小写
 */

class Message extends AutoReply
{
    protected $message;
    protected $app;

    public function __construct($message, $app)
    {
        $this->message = $message;
        $this->app = $app;
    }

    // 收到文字消息的处理方法
    public function text()
    {
        $mode1_data = Db::name('we_reply')->where(['keyword' => $this->message->Content, 'msg_type' => 'text', 'expires_date' => ['>', time()], 'status' => 1])->order('id desc')->find();
        if (isset($mode1_data) && $mode1_data['mode'] == 1) {   // 设置了自动回复。完整匹配
            $res = $this->reply($mode1_data, $this->app);
            if ($res !== false) {
                if(isset($res['is_multi']) && $res['is_multi']){
                    $res['content'] = $mode1_data['content'];
                }
                return $res;
            }
        }

        $mode0_data = Db::name('we_reply')->where('keyword', 'like', '%' . $this->message->Content . '%')->where(['msg_type' => 'text', 'expires_date' => ['>', time()], 'status' => 1])->order('id desc')->find();
        if (isset($mode0_data) && $mode0_data['mode'] == 0) {   // 设置了自动回复。模糊搜索
            $res = $this->reply($mode0_data, $this->app);
            if ($res !== false) {
                return $res;
            }
        }

        // 没设自动回复就执行下面代码
        $text = new Text();
        $book=Db::table('ien_book')->where('title', 'like', '%' . $this->message->Content . '%')->select();
        $nr="已为您找到图书:\n";
        if(empty($book))
        {
           $text->content = '很抱歉,没有找到这本小说';    // 文本内容
           return $text; 
        }
        else{
            foreach($book as $value)
            {
                $cp=DB::table('ien_chapter')->where('bid',$value['id'])->where('idx','1')->find();
                if(!empty($cp))
                {
                $nr.=$value['title']."<a href='http://".$_SERVER['HTTP_HOST']."/index.php/cms/document/detail/id/".$cp['id'].".html'>【点我阅读】</a>";
                $nr.="\n";
                }
            }

            $text->content = $nr;    // 文本内容
            return $text;
        }

        $text->content = '我们已收到您反馈的内容：' . $this->message->Content;   // 文本内容
        return $text;
        
    }
    
    public function sendNews($openid, $content)
    {
        $config = module_config('wechat');
        $AccessToken = new AccessToken($config['app_id'], $config['secret']);
        $access_token = $AccessToken->getToken();
        // $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' . $access_token;
        $material = Db::table('ien_we_material')->whereIn('id', $content)->select();
        // $ids = explode(',', $content);
        // $sort_material = array();
        // foreach($material as $temp){
        //     foreach($ids as $key => $id){
        //         if($temp['id'] == $id){
        //             $sort_material[$key] = $temp;
        //             break;
        //         }
        //     }
        // }
        // ksort($sort_material);
        // $data = '{"touser":"'.$openid.'","msgtype":"news","news":{"articles": [';
        // if ($sort_material) {
        //     foreach ($sort_material as $key => $value) {
        //         $material_content = json_decode($value['content'], true);
        //         $data .= '{"title":"'.$material_content['title'].'","description":"'.$material_content['description'].'","url":"'.$material_content['url'].'","picurl":"'.$_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'].get_thumb($material_content['image']).'"},';
        //     }
        //     $data = trim($data, ',');
        // }
        // $data .= ']}}';
        // $res = ihttp_request($url, $data, 'post');
        // if(isset($res['errcode']) && $res['errcode'] == '40001'){
        //     $access_token = $AccessToken->getToken(true);
        //     $this->sendNews($openid, $content);
        // }
    }

    // 收到图片消息的处理方法
    public function image()
    {
        $data = $this->needAutoReply('image');
        if ($data) { // 设置了自动回复
            $res = $this->reply($data, $this->app);
            if ($res !== false) {
                return $res;
            }
        }

        // 没设自动回复就执行下面代码
        $text = new Text();
        $text->content = '我们已收到您发来的图片内容';    // 文本内容
        return $text;
    }

    // 收到语音消息的处理方法
    public function voice()
    {
        $data = $this->needAutoReply('voice');
        if ($data) { // 设置了自动回复
            $res = $this->reply($data, $this->app);
            if ($res !== false) {
                return $res;
            }
        }

        // 没设自动回复就执行下面代码
        $text = new Text();
        if (isset($this->message->Recognition)) {  // 已开通语音识别
            $text->content = '语音识别内容如下：' . $this->message->Recognition;
        } else {
            $text->content = '我们已收到您发来的语音消息，但无法识别其内容';    // 文本内容
        }
        return $text;
    }

    // 收到视频消息的处理方法
    public function video()
    {
        $data = $this->needAutoReply('video');
        if ($data) { // 设置了自动回复
            $res = $this->reply($data, $this->app);
            if ($res !== false) {
                return $res;
            }
        }

        // 没设自动回复就执行下面代码
        $text = new Text();
        $text->content = '我们已收到您发来的视频内容';    // 文本内容
        return $text;
    }

    // 收到地理位置消息的处理方法
    public function location()
    {
        $data = $this->needAutoReply('location');
        if ($data) { // 设置了自动回复
            $res = $this->reply($data, $this->app);
            if ($res !== false) {
                return $res;
            }
        }

        // 没设自动回复就执行下面代码
        $text = new Text();
        $text->content = '我们已收到您发来的地理位置：' . $this->message->Label;    // 文本内容
        return $text;
    }
}