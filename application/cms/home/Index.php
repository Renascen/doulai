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
use think\Session;
/**
 * 前台首页控制器
 * @package app\cms\admin
 */
class Index extends Common
{
    /**
     * 首页
     * @author 拼搏 <378184@qq.com>
     * @return mixed
     */
    public function index($agent=null,$t=null)
    {
		session_start();
        if ( $this->request->isAjax() ){
//            $id_array = array();
            $id_array = $_POST['ids'];
            $start = $_POST['start'];
            $limit = $_POST['limit'];
            $new_list = Db::table('ien_book book')->field('tjbook.sort,tjbook.image as tjimg,book.id,book.title,book.jpv,book.zuozhe,book.zishu,book.image,book.desc,book.model,book.cover,book.tstype,book.recommend,book.view,book.xstype')
                ->join('ien_tj_book tjbook','book.id=tjbook.bid','left')
                ->where('book.status=1')
                ->where("FIND_IN_SET( '3', book.tj)")
                ->where("tjbook.tjid=3")
                ->group('book.title')
                ->limit($start,$limit)
                ->order('sort desc,book.jpv desc')
                ->select();

            /************免费***********/
            $Gratis_id = Db::table('ien_tj_book')->field('bid')->where('tjid',2)->select();
            $Gratis_id = array_column($Gratis_id,'bid');
            /*************免费************/

            /*********限免****************/
            $time = time();
            $free_limit_book = Db::query("select distinct bid from ien_free_limit_book where xsid in (select id from ien_free_limit where start_time < $time and (end_time > $time or end_time=0) and status=1)");
            $bidArr = array_column($free_limit_book,'bid');

            /************限免*************/

            if ($new_list){
                foreach ($new_list as $k => &$v){
                    if (in_array($v['id'],$Gratis_id)){
                        $v['xstype'] = 2;       // 免费
                    }elseif (in_array($v['id'],$bidArr)){
                        $v['xstype'] = 3;          // 限免
                    }
                    if (in_array($v['id'],$id_array) ){
                       unset($new_list[$k]);unset($k);
                    }

                    $v['num'] = Db::table('ien_comment')->where('bid', $v['id'])->count();
                    $v['image'] = $v['tjimg'] ? $v['tjimg'] : $v['image'];
                    $v['image'] = get_file_path($v['image']);
                }
                unset($v);
            }else{
                return json(array('msg'=>'0'));
            }
//            dump($new_list);
            if (count($new_list)>0){
                return json($new_list);
            }
        }
        if(empty($_SESSION['wechat_user'])) {
            $this->redirect('oauth/oauth');
            //$this-> checklogin();
        }

        $user = DB::table('ien_admin_user')->where('openid', $_SESSION['wechat_user']['original']['openid'])->find();
        //更新读者推广ID
        // $urla['1']="";   
        // if (strpos($_SESSION['target_url'],"?")) {
        //     $url=explode("?",$_SESSION['target_url']);
        //     $urla=explode("=", $url[1]);
        // }
        // if (!empty($urla['1'])) {
        //     $sxid=DB::table('ien_agent')->where('id',$urla['1'])->find();
        //     if(empty($sxid))
        //     {
        //         $sxid['uid']=0;
        //     }
        // } else {
        //     $sxid['uid']=0;
        // }

        // if ($sxid['uid'] != 0 && $sxid['uid'] != "" && $urla['1'] != "") {
        //     $dataagent=['sxid'=>$sxid['uid'],'tgid'=>$urla['1']];
        //     DB::table('ien_admin_user')->where('openid', $_SESSION['wechat_user']['original']['openid'])->update($dataagent);
        // }
        //更新读者推广ID结束


        //$this->oauth($agent,'http://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]); 
        // $this->getCode("wxfcc9e317d7e4279d","8af792a6ed7ada0e26bd30c212638f49");



        $category = explode("\r\n", DB::table('ien_cms_field')->where('id', 49)->value('options'));
		$banner_list = Db::view('ien_cms_slider','id,title,cover,url,bid')
                ->where('status=1')
                ->order('sort desc,update_time desc')
                ->select();
        foreach ($banner_list as &$val) {
            if(empty($val['url'])){
                $val['url'] = empty($val['bid']) ? '' :url('document/desc', ['bid' => $val['bid'], 'comefrom' => 1, 'subid' => $val['id']]);
            }else{
                $val['url'] = strpos($val['url'], '?') ? $val['url'] . '&comefrom=1&subid=' . $val['id'] : $val['url'] . '?comefrom=1&subid=' . $val['id'];
            }
        }
        unset($val);

        $list_num_0 = Db::table('ien_tj_admin')->field('list_num')->where('tj',0)->find();
        $daily_list = Db::table('ien_book book')->field('tjbook.sort,tjbook.image as tjimg,book.id,book.title,book.zuozhe,book.image,book.desc,book.model,book.cover,book.tstype,pvt.pv as pv,book.recommend,book.xstype')
            ->join('ien_book_pv pvt', 'book.id=pvt.bid', 'left')
            ->join('ien_tj_book tjbook','book.id=tjbook.bid','left')
            ->where('book.status=1')
            ->where("FIND_IN_SET( '0', book.tj)")
            ->where("tjbook.tjid=0")
            ->limit($list_num_0['list_num'])
            ->order('tjbook.sort desc,pvt.pv desc')
            ->select();


        if ($list_num_0['list_num'] == 0) {
            $daily_list = [];
        }
        foreach($daily_list as $k => $v){
            $daily_list[$k]['image'] = $v['tjimg'] ? $v['tjimg']: $v['image'];
        }
        $zhubian_name = Db::table('ien_tj_admin')->field('name')->where('tj',0)->find();
        if (count($daily_list)>0) {
            $daily_list[0]['tjname'] = $zhubian_name['name'];
        }

        // echo '<br>';
        $daily_list['tjid'] = 0;




        $list_num_1 = Db::table('ien_tj_admin')->field('list_num')->where('tj',1)->find();
        $zhubian_list = Db::table('ien_book book')->field('tjbook.sort,tjbook.image as tjimg,book.id,book.title,book.zuozhe,book.image,book.desc,book.model,book.cover,book.tstype,pvt.pv as pv,book.recommend,book.xstype')
                ->join('ien_book_pv pvt', 'book.id=pvt.bid', 'left')
                ->join('ien_tj_book tjbook','book.id=tjbook.bid','left')
                ->where('book.status=1')
                ->where("FIND_IN_SET( '1', book.tj)")
                ->where("tjbook.tjid=1")
                ->limit($list_num_1['list_num'])
                ->order('sort desc,pvt.pv desc')
                ->select();

                // echo DB::getLastSql();exit();
                if ($list_num_1['list_num'] == 0) {
                    $zhubian_list = [];
                }
                foreach($zhubian_list as $k => $v){
                    $zhubian_list[$k]['image'] = $v['tjimg'] ? $v['tjimg']:$v['image'];
                }

                $zhubian_name = Db::table('ien_tj_admin')->field('name')->where('tj',1)->find();
                if (count($zhubian_list)>0) {
                     $zhubian_list[0]['tjname'] = $zhubian_name['name'];
                }
                // echo '<br>';
        $zhubian_list['tjid'] = 1;



//       公告
        $notice_list = Db::view('ien_cms_notice','id,title,url,bid')
        ->where('status=1')
        ->order('id desc')
        ->select();




                // echo DB::getLastSql();
                // echo '<br>';
        $list_num_2 = Db::table('ien_tj_admin')->field('list_num')->where('tj',2)->find();
        $Gratis = Db::table('ien_book book')->field('tjbook.sort,book.id,(book.jpv+ifnull(pvt.pv,0)) as pv,book.title,book.zuozhe,book.zishu,book.image,book.desc,book.model,book.cover,book.tstype,book.recommend,book.xstype,book.tag')
                ->join('ien_book_pv pvt', 'book.id=pvt.bid', 'left')
                ->join('ien_tj_book tjbook','book.id=tjbook.bid','left')
                ->where('book.status=1')
                ->where("FIND_IN_SET( '2', book.tj)")
                ->where("tjbook.tjid=2")
                ->limit($list_num_2['list_num'])
                ->order('sort desc,pv desc')
                ->select();

                /************免费***********/
                $Gratis_id = Db::table('ien_tj_book')->field('bid')->where('tjid',2)->select();
                $Gratis_id = array_column($Gratis_id,'bid');
                /*************免费************/

                /*********限免****************/
                $time = time();
                 $free_limit_book = Db::query("select distinct bid from ien_free_limit_book where xsid in (select id from ien_free_limit where start_time < $time and (end_time > $time or end_time=0) and status=1)");
                $bidArr = array_column($free_limit_book,'bid');

                /************限免*************/

                if ($list_num_2['list_num'] == 0) {
                    $Gratis = [];
                }

                // 获取推荐位名称
                $girl_list_1_name = Db::table('ien_tj_admin')->field('name')->where('tj',2)->find();
                if (count($Gratis)>0) {
                    $Gratis[0]['tjname'] = $girl_list_1_name['name'];
                }

                foreach ($Gratis as $k => &$v) {
                    if (in_array($v['id'],$Gratis_id)){
                        $v['xstype'] = 2;       // 免费
                    }elseif (in_array($v['id'],$bidArr)){
                        $v['xstype'] = 3;          // 限免
                    }
                    /* 分割标签内容*/
                    if (!is_array($v['tag'])) {
                        if (strpos($v['tag'],',')){
                            $v['tag'] =  explode(',',$v['tag']);
                        }else if (strpos($v['tag'],'，')){
                            $v['tag'] =  explode('，',$v['tag']);
                        }else if(strpos($v['tag'],' ')){
                            $v['tag'] = explode(' ',$v['tag']);
                        }
                    }
                        foreach($v['tag'] as $key => $val) {
                             if (strlen($val)>6){
                                 unset($v['tag'][$key]);
                             }
                        }

                    $v['molds'] = Db::table('ien_cartoon_sort')->field('name,tstype')->wherein('tstype',$v['tstype'])->select();
                    if ($v['pv']>=100000) {
                        $Gratis[$k]['pv'] = round($v['pv']/10000).'万+';
                    }

                }

//                dump($Gratis);
//                die;
                unset($v);
                $Gratis['tjid'] = 2;


                // echo DB::getLastSql();
                // echo '<br>';*-*

        $list_num_3 = Db::table('ien_tj_admin')->field('list_num')->where('tj',3)->find();
        $free_book = Db::table('ien_book book')->field('tjbook.sort,tjbook.image as tjimg,book.id,(book.jpv+ifnull(pvt.pv,0)) as pv,book.title,book.jpv,book.zuozhe,book.zishu,book.image,book.desc,book.model,book.cover,book.tstype,book.recommend,book.view,book.xstype,book.tag')
                ->join('ien_book_pv pvt', 'book.id=pvt.bid', 'left')
                ->join('ien_tj_book tjbook','book.id=tjbook.bid','left')
                ->where('book.status=1')
                ->where("FIND_IN_SET( '3', book.tj)")
                ->where("tjbook.tjid=3")
                ->limit($list_num_3['list_num'])
                ->order('sort desc,pv desc')
                ->select();


                if ($list_num_3['list_num'] == 0) {
                    $free_book = [];
                }
                foreach($free_book as $k => &$v){
                    $v['tag'] = explode(',',$v['tag']);
                    $v['num'] = Db::table('ien_comment')->where('bid', $v['id'])->count();
                    $v['image'] = $v['tjimg'] ? $v['tjimg']:$v['image'];
                }
                unset($v);
                $free_book_name = Db::table('ien_tj_admin')->field('name')->where('tj',3)->find();
                if (count($free_book)>0) {
                    $free_book[0]['tjname'] = $free_book_name['name'];
                }
                foreach ($free_book as $k => $v) {
                    if ($v['pv']>=100000) {
                        $free_book[$k]['pv'] = round($v['pv']/10000).'万+';
                    }    
                    if($v['zishu'] >= 100000){
                        $free_book[$k]['zishu'] = round($v['zishu'] / 10000) . '万';
                    }
                }

        $free_book['tjid'] = 3;
                // echo DB::getLastSql();
                // echo '<br>';
/******活动*********/
        $time = time();
        $act_limit = Db::query("select id,start_time,end_time from ien_free_limit where start_time < $time and (end_time > $time or end_time=0) and status=1");
        if ($act_limit) {
            foreach ($act_limit as $k => $v) {
                if ($v['end_time'] == 0) {
                    $act_limit[$k]['end_time'] = 2114265599;
                }
            }
            $flag = array();  
            foreach($act_limit as $v){  
                $flag[] = $v['end_time'];  
            }  
            array_multisort($flag, SORT_ASC, $act_limit);  
            $xsid = $act_limit[0]['id'];
            // $xsid = implode(',',$xsid);
            // $xsid = '('.$xsid.')';

                $free_limit = Db::query("select price,packsell,title,zuozhe,image,recommend,a.desc,zhishu,model,cover,b.sort,b.bid id from  ien_book a right join 
        (select distinct bid,sort from ien_free_limit_book where xsid=$xsid and type=0 group by bid) b on a.id=b.bid  order by b.sort desc,id desc");
                $z_price_bid = [];
                foreach ($free_limit as $k => $v) {
                    if ($v['packsell'] == 0) {
                        $z_price_bid[] = $v['id'];
                    }
                }
                if ($z_price_bid) {
                    $z_price_bid = implode(',', $z_price_bid);
                    $z_price_bid = '('.$z_price_bid.')';
                    $free_limit = Db::query("select price,packsell,title,zuozhe,image,a.desc,zhishu,model,cover,recommend,b.sort,b.bid id,c.zishu from  ien_book a right join 
                    (select distinct bid,sort from ien_free_limit_book where xsid=$xsid and type=0 group by bid) b on a.id=b.bid left join (select bid,sum(zishu) zishu from ien_chapter where bid in $z_price_bid and isvip=1 group by bid) c on a.id=c.bid order by sort desc,id desc");
                    foreach ($free_limit as $k => $v) {
                         if ($v['packsell'] == 1) {
                             $free_limit[$k]['price'] = round($v['price']/100);
                         }else{
                             $free_limit[$k]['price'] = round($v['zishu']/10000);
                         }
                    }
                }else{
                    foreach ($free_limit as $k => $v) {
                         if ($v['packsell'] == 1) {
                             $free_limit[$k]['price'] = round($v['price']/100);
                         }else{
                             $free_limit[$k]['price'] = round($v['zishu']/10000);
                         }
                    }
                }
 
        }else{
            $free_limit = [];
        }
       
//         $free_limit = Db::query("select title,zuozhe,image,a.desc,zhishu,model,cover,b.sort,b.bid id from  ien_book a right join 
// (select distinct bid,sort from ien_free_limit_book where xsid in (select id from ien_free_limit where start_time < $time and end_time > $time and status=1)) b 
// on a.id=b.bid  order by b.sort,zhishu");
        $this->assign('free_limit', $free_limit);
/******活动********/

        $this->assign('banner_list', $banner_list);

        $this->assign('notice_list', $notice_list);

        $this->assign('daily_list', $daily_list); // 每日必看
        $this->assign('zhubian_list', $zhubian_list);  //热门推荐

        $this->assign('Gratis', $Gratis);       //免费专区
        $this->assign('free_book', $free_book);     // 最新动漫

        $this->assign('category', $category);
   
        $this->assign('user', $user);

        return $this->fetch(); // 渲染模板
    }
    public function footer()
    {
    	return $this->fetch(); // 渲染模板
    }

    public function many()
    {
        if ($this->request->isAjax()) {
            $type = $_GET['type'];       // -1 完结  0 1 2 3 每日/热门/免费/最新/
            $start = $_GET['start'];
            $limit = $_GET['limit'];
            if ($type != null) {
                if ($type == -1) {
                    // 完结
                    $list = Db::table('ien_book book')
                        ->field('book.id,book.view,book.title,book.zuozhe,book.tag,book.image,book.desc,book.model,book.cover,book.tstype,pvt.pv as pv,book.recommend,book.zishu')
                        ->join('ien_book_pv pvt', 'book.id=pvt.bid', 'left')
                        ->where('book.status=1')
                        ->where("xstype = 1")
                        ->limit($start, $limit)
                        ->order('pvt.pv desc')
                        ->select();
                    if (!empty($list)) {
                        foreach ($list as $k => &$v) {
                            $v['tag'] = explode(',', $v['tag']);
                            $v['num'] = Db::table('ien_comment')->where('bid', $v['id'])->count();
                            $v['image'] = $v['image']!=0 ?get_file_path($v['image']) :"/public/static/cms/img/default_erect.jpg";
                        }
                        return json($list);
                    }else{
                        return [];
                    }
                } else if ($type < 50) {
                    //推荐位
                    $list = Db::table('ien_book book')
                        ->field('tjbook.sort,book.id,book.view,book.title,book.zuozhe,book.tag,book.image,book.desc,book.model,book.cover,book.tstype,pvt.pv as pv,book.recommend,book.zishu')
                        ->join('ien_book_pv pvt', 'book.id=pvt.bid', 'left')
                        ->join('ien_tj_book tjbook', 'book.id=tjbook.bid', 'left')
                        ->where('book.status=1')
                        ->where("FIND_IN_SET( $type, book.tj)")
                        ->where("tjbook.tjid=$type")
                        ->limit($start, $limit)
                        ->order('sort desc,pvt.pv desc')
                        ->select();
                    if (!empty($list)) {
                        foreach ($list as $k => &$v) {
                            $v['tag'] = explode(',', $v['tag']);
                            $v['num'] = Db::table('ien_comment')->where('bid', $v['id'])->count();
                            $v['image'] = $v['image']!=0 ?get_file_path($v['image']) :"/public/static/cms/img/default_erect.jpg";
                        }
                        unset($v);
                        return json($list);
                    }else{
                        return [];
                    }
                }
            } else {
                $msg['code'] = -1;
                $msg['msg'] = '参数错误';
                return json($msg);
            }
        }
            return $this->fetch();
        }


    public function history()
    {
        if ($this->request->isAjax()){
            $start = $_GET['start'];
            $limit = $_GET['limit'];
            $list = Db::view('read_log','id,zid,update_time')
                ->view('chapter',['idx'=>'idx','title'=>'ztitle'],'chapter.id=read_log.zid','LEFT')
                ->view('book',['title'=>'title', 'image','images','zuozhe','xstype'],'book.id=read_log.bid','LEFT')
                ->where("read_log.uid" , $_SESSION['wechat_user']['original']['openid'])
                ->group('read_log.bid')
                ->limit($start,$limit)
                ->order('read_log.update_time desc')
                ->select();
            foreach($list as $k => &$val){

                $val['images']= $val['images']!=0 ? get_file_path($val['images']):"/public/static/cms/img/default_across.jpg";
                ;
            }
            unset($val);
            return json($list);
        }

        return $this->fetch();
    }

    public function header()
    {
    	return $this->fetch(); // 渲染模板
    }
    public function booklibrary()
    {   
        // $category = explode("\r\n", DB::table('ien_cms_field')->where('id', 49)->value('options'));
        $category = Db::query("select tstype,name from ien_book_sort where status=1 order by sort desc,id desc");
        // var_dump($category);exit(); 
        $user = DB::table('ien_admin_user')->where('openid', $_SESSION['wechat_user']['original']['openid'])->find();
        $this->assign('category', $category);
        $this->assign('user', $user);
        $this->assign('id', input('id'));
    	return $this->fetch(); // 渲染模板
    }

}