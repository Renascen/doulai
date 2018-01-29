<?php

namespace app\wechat\home;

use app\index\controller\Home;
use think\Db;
use EasyWeChat\Core\AccessToken;
/*
 * 微信服务器访问的入口，不要用浏览器访问
 */

class Index extends Home
{
    use \app\wechat\Base;
    public $openid;
    public $eventKey;
    public $ticket;
    public $event;
    public $result;
    public function index()
    {


        $this->app->server->setMessageHandler(function ($message) {
            $this->eventKey = $message->EventKey;
            $this->openid = $message->FromUserName;
            $this->ticket = $message->Ticket;
            $this->event = $message->Event;
            if ($message->MsgType == 'event') {
                $class = '\\app\\wechat\\home\\Event';

                return call_user_func([new $class($message, $this->app), strtolower($message->Event)]);
            } else {
                $class = '\\app\\wechat\\home\\Message';
                $res = call_user_func([new $class($message, $this->app), strtolower($message->MsgType)]);
                if(isset($res['is_multi']) && $res['is_multi']){
                    $this->result = $res;
                    return false;
                }
                return $res;
            }

        });

        $this->app->server->serve()->send();
        if(isset($this->result['is_multi']) && $this->result['is_multi']){
            $this->sendNews($this->openid, $this->result['content']);
        }
        if(!empty($this->ticket)){
            if(strpos($this->eventKey, 'qrscene_') !== false){
                $qrscene = explode('_', $this->eventKey);
                $scene_id = $qrscene[1];
            }else{
                $scene_id = $this->eventKey;
            }
            $qrcodeInfo = DB::table('ien_wechat_qrcode')->where('scene_id', $scene_id)->find();
            if(!empty($qrcodeInfo['type']) && !empty($qrcodeInfo['content']) && !empty($qrcodeInfo['status'])){
                switch ($qrcodeInfo['type']) {
                    case 'text':
                        $this->sendText($this->openid, $qrcodeInfo['content']);
                        break;
                    case 'news':
                        $this->sendNews($this->openid, $qrcodeInfo['content']);
                        break;
                }
            }
        }


    }

    /**
     * 发送文字消息
     * @param  string $openid  用户openid
     * @param  string $content 要发送的内容
     */
    public function sendText($openid, $content)
    {
        $config = module_config('wechat');
        //$AccessToken = new AccessToken($config['app_id'], $config['secret']);
        //$access_token = $AccessToken->getToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' . $access_token;
        $data = '{"touser":"'.$openid.'","msgtype":"text","text":{"content":"'.$content.'"}}';
        $res = ihttp_request($url, $data, 'post');
        if(isset($res['errcode']) && $res['errcode'] == '40001'){
          //  $access_token = $AccessToken->getToken(true);
            $this->sendText($openid, $content);
        }
    }

    /**
     * 发送图文消息
     * @param  string $openid  用户openid
     * @param  string $content 图文列表id
     * @return [type]          [description]
     */
    public function sendNews($openid, $content)
    {
        $config = module_config('wechat');
        $AccessToken = new AccessToken($config['app_id'], $config['secret']);
        $access_token = $AccessToken->getToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' . $access_token;
        $material = Db::table('ien_we_material')->whereIn('id', $content)->select();
        $ids = explode(',', $content);
        $sort_material = array();
        foreach($material as $temp){
            foreach($ids as $key => $id){
                if($temp['id'] == $id){
                    $sort_material[$key] = $temp;
                    break;
                }
            }
        }
        ksort($sort_material);
        $data = '{"touser":"'.$openid.'","msgtype":"news","news":{"articles": [';
        if ($sort_material) {
            foreach ($sort_material as $key => $value) {
                $material_content = json_decode($value['content'], true);
                $data .= '{"title":"'.$material_content['title'].'","description":"'.$material_content['description'].'","url":"'.$material_content['url'].'","picurl":"'.$_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'].get_thumb($material_content['image']).'"},';
            }
            $data = trim($data, ',');
        }
        $data .= ']}}';
        $res = ihttp_request($url, $data, 'post');
        if(isset($res['errcode']) && $res['errcode'] == '40001'){
            $access_token = $AccessToken->getToken(true);
            $this->sendNews($openid, $content);
        }
    }
}