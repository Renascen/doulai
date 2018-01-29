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
use think\DB;
// 为方便系统核心升级，二次开发中需要用到的公共函数请写在这个文件，不要去修改common.php文件
if(!function_exists('ihttp_request')){
    /**
     * http请求
     * @param  string $url  请求地址
     * @param  string $data 请求数据
     * @param  string $type 请求类型
     * @param  string $res  返回数据格式
     * @return [type]       返回的数据
     */
    function ihttp_request($url, $data = '', $type = "get", $res = "json"){
        //1.初始化curl
        $curl = curl_init();
        //2.设置curl的参数
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SAFE_UPLOAD, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT,60);
        if ($type == "post"){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        //3.采集
        $output = curl_exec($curl);
        //4.关闭
        curl_close($curl);

        if ($res == 'json') {
            return json_decode($output,true);
        }
        return $output;
    }

}
if(!function_exists('get_recommend')){
    /**
     * 推荐位信息
     * @param  integer $rid 推荐位key值
     * @return [type]       [description]
     */
    function get_recommend($rid = 0){
        $rid = empty($rid) || !is_numeric($rid) ? 0 : $rid;
        $recommend = [
            0 => ['name' => '其他', 'tablename' => ''],
            1 => ['name' => '首页-banner', 'tablename' => 'ien_cms_slider'],
            2 => ['name' => '首页-广播', 'tablename' => 'ien_cms_notice'],
            3 => '',
            4 => ['name' => '首页-热门畅销', 'tablename' => ''],
            5 => ['name' => '首页-主编力推', 'tablename' => ''],
            6 => ['name' => '首页-大神专区', 'tablename' => ''],
            7 => ['name' => '首页-限时免费', 'tablename' => ''],
            8 => ['name' => '阅读页-广告', 'tablename' => 'ien_advertise'],
            9 => ['name' => '书架-推荐', 'tablename' => ''],
            10 => ['name' => '阅读历史-推荐', 'tablename' => ''],
            11 => ['name' => '阅读页-热门推荐', 'tablename' => 'ien_hot_tj']
        ];
        return $recommend[$rid];
    }
}
if(!function_exists('getCostScore')){
    /**
     * 获取字数相应的价格
     * @param  int $zishu 小说字数
     * @return float        计算后的价格
     */
    function getCostScore($zishu){
        return floor($zishu / 100);
    } 
}

if(!function_exists('coinToMoney')){
    /**
     * 人民币转成书币
     * @param  int $zishu 小说字数
     * @return float        计算后的价格
     */
    function moneyToCoin($money){
        return $money * 100;
    } 
}

if(!function_exists('coinToMoney')){
    /**
     * 书币换算人民币
     * @param  int $zishu 小说字数
     * @return float        计算后的价格
     */
    function coinToMoney($coin){
        return round($coin / 10, 2);
    } 
}

if(!function_exists('getOriginal')){
    /**
     * 获取书籍原价
     * @param  int $bid      小说id
     * @param  倍数  $multiple 折后价的倍数
     * @return floor           小说原价
     */
    function getOriginal($bid, $multiple = 1.35){
        $book = DB::table('ien_cartoon')->where('id', $bid)->find();
        $original = floor(DB::table('ien_pictures')->where('bid', $bid)->where('isvip' , 1)->sum('page') / 10);
        if(!empty($book['price']) && $original <= $book['price']){
            $original = floor($book['price'] * $multiple);
        }
        return $original;
    }
}