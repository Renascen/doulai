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

use app\cms\model\Column as ColumnModel;
use function PHPSTORM_META\type;
use think\Db;
use util\Tree;

/**
 * 前台栏目文档列表控制器
 * @package app\cms\admin
 */
class Column extends Common
{
    /**
     * 栏目文章列表
     * @param null $id 栏目id
     * @author 拼搏 <378184@qq.com>
     * @return mixed
     */
    public function index($id=null,$tstype=null)
    {
        if ($id === null) $this->error('缺少参数');
        $user = DB::table('ien_admin_user')->where('openid', $_SESSION['wechat_user']['original']['openid'])->find();
        $book= Db::table('ien_cms_column')->where('id', $id)->value('name');
        $this->assign('book', $book);
        $this->assign('id', $id);
        $this->assign('user', $user);
		    $this->assign('tstype', $tstype);

        return $this->fetch("list_book");
    }


    /**
     * 分类查看
     * @param  int $start 开始
     * @param  int $limit 长度
     * @param  int $tstype 小说分类
     * @param  int $xstype 是否连载
     * @param  int $sex 人群
     * @return false|mixed|\PDOStatement|string|\think\Collection
     */
    public function doajax( $start = '', $limit= '' , $tstype = '', $xstype = '', $sex = '') {
        /**********分类************/
        if ($sex == "" && $tstype == "") {
            $tstypes = Db::query("select tstype from ien_book_sort where status=1");
            $arr = array_column($tstypes,'tstype');
            $tstypeStr = implode(',', $arr);
            $map['ien_book.tstype'] = ['in',$tstypeStr];
        }elseif ($sex == "" && $tstype != "") {
            $map['ien_book.tstype'] = $tstype;
        }

        if ($sex == 2 && $tstype == "") {
            $tstypes = Db::query("select tstype from ien_book_sort where status=1 and (cid=2 or cid=0)");
            $arr = array_column($tstypes,'tstype');
            $tstypeStr = implode(',', $arr);
            $map['ien_book.tstype'] = ['in',$tstypeStr];
            $map['ien_book.cid'] = ['in','0,2'];
        }elseif ($sex == 2 && $tstype != "") {
            $map['ien_book.tstype'] = $tstype;
            $all = Db::query("select cid from ien_book_sort where status=1 and tstype=$tstype");
            if ($all[0]['cid'] == 2) {
                $map['ien_book.cid'] = $sex;
            }elseif ($all[0]['cid'] == 0) {
                $map['ien_book.cid'] = ['in','0,2'];
            }
        }

        if ($sex == 3 && $tstype == "") {
            $tstypes = Db::query("select tstype from ien_book_sort where status=1 and (cid=3 or cid=0)");
            $arr = array_column($tstypes,'tstype');
            $tstypeStr = implode(',', $arr);
            $map['ien_book.tstype'] = ['in',$tstypeStr];
            $map['ien_book.cid'] = ['in','0,3'];
        }elseif ($sex == 3 && $tstype != "") {
            $map['ien_book.tstype'] = $tstype;
            $all = Db::query("select cid from ien_book_sort where status=1 and tstype=$tstype");
            if ($all[0]['cid'] == 3) {
                $map['ien_book.cid'] = $sex;
            }elseif ($all[0]['cid'] == 0) {
                $map['ien_book.cid'] = ['in','0,3'];
            }

        }
        /**********分类************/
        $map['ien_book.status'] = 1;
        if ($xstype !== ''){
            $map['ien_book.xstype'] = $xstype;
	      }
        $tstypeArr = Db::query("select tstype,name from ien_book_sort");
        $temp_key = array_column($tstypeArr,'tstype');
        $temp_value = array_column($tstypeArr,'name');
        $category = array_combine($temp_key, $temp_value);
        $data_list = Db::table('ien_book book')->field('book.id,(book.jpv+ifnull(pvt.pv,0)) as pv,book.title,book.view,book.jpv,book.zuozhe,book.image,book.images,book.desc,book.zishu,book.model,book.cover,book.tstype,book.recommend,book.xstype')
                ->join('ien_book_pv pvt', 'book.id=pvt.bid','LEFT')
                ->where($map)
                ->limit($start,$limit)
                ->order('pv desc,id desc')
                ->select();
//		if (empty($data_list)) {
//            return $data_list;
//        }

        foreach ($data_list as $key=>$value) {
            $data['data'][$key]['id'] = (string)$value['id'];
            $data['data'][$key]['title'] = $value['title'];
            $data['data'][$key]['zuozhe'] = $value['zuozhe'];
            $data['data'][$key]['summary'] = empty(trim($value['recommend'])) ? $value['desc'] : $value['recommend'];
            $data['data'][$key]['is_new'] = true;
            $data['data'][$key]['num'] = Db::table('ien_comment')->where('bid', $value['id'])->count();
            $data['data'][$key]['serial'] = $value['xstype'];
            $data['data'][$key]['avatar'] = $value['images'] > 0 ? get_thumb($value['images']) :"/public/static/cms/img/default_across.jpg";
            $data['data'][$key]['zid'] = $value['zid'];
            $data['data'][$key]['view'] = $value['view'];
            $data['data'][$key]['tstype'] = $category[$value['tstype']];
            $data['data'][$key]['tuijian'] = $value['tuijian'];
            $data['data'][$key]['sql'] = DB::getLastSql();
        }
            foreach ($data['data'] as $k => $v) {
                if ($v['view']>=100000) {
                    $data['data'][$k]['view'] = round($v['view']/10000).'万';
                }
            }
        $data_list=json_decode(json_encode($data),true);
        return  $data_list;
    }


    /**
     * @param string $start
     * @param string $limit
     * @param string $tstype
     * @param string $xstype
     * @param string $sex
     * @return false|mixed|\PDOStatement|string|\think\Collection
     */
    public function xsajax($start = '', $limit= '' , $tstype = '', $xstype = '', $sex = ''){
        if ($sex == "" && $tstype == "") {
            $tstypes = Db::query("select tstype from ien_book_sort where status=1");
            $arr = array_column($tstypes,'tstype');
            $tstypeStr = implode(',', $arr);
            $map['ien_book.tstype'] = ['in',$tstypeStr];
        }elseif ($sex == "" && $tstype != "") {
            $map['ien_book.tstype'] = $tstype;
        }

        if ($sex == 2 && $tstype == "") {
            $tstypes = Db::query("select tstype from ien_book_sort where status=1 and (cid=2 or cid=0)");
            $arr = array_column($tstypes,'tstype');
            $tstypeStr = implode(',', $arr);
            $map['ien_book.tstype'] = ['in',$tstypeStr];
            $map['ien_book.cid'] = ['in','0,2'];
        }elseif ($sex == 2 && $tstype != "") {
            $map['ien_book.tstype'] = $tstype;
            $all = Db::query("select cid from ien_book_sort where status=1 and tstype=$tstype");
            if ($all[0]['cid'] == 2) {
                $map['ien_book.cid'] = $sex;
            }elseif ($all[0]['cid'] == 0) {
                $map['ien_book.cid'] = ['in','0,2'];
            }
        }

        if ($sex == 3 && $tstype == "") {
            $tstypes = Db::query("select tstype from ien_book_sort where status=1 and (cid=3 or cid=0)");
            $arr = array_column($tstypes,'tstype');
            $tstypeStr = implode(',', $arr);
            $map['ien_book.tstype'] = ['in',$tstypeStr];
            $map['ien_book.cid'] = ['in','0,3'];
        }elseif ($sex == 3 && $tstype != "") {
            $map['ien_book.tstype'] = $tstype;
            $all = Db::query("select cid from ien_book_sort where status=1 and tstype=$tstype");
            if ($all[0]['cid'] == 3) {
                $map['ien_book.cid'] = $sex;
            }elseif ($all[0]['cid'] == 0) {
                $map['ien_book.cid'] = ['in','0,3'];
            }

        }
        /**********分类************/
        $map['ien_book.status'] = 1;
        if ($xstype !== ''){
            $map['ien_book.xstype'] = $xstype;
        }
        $tstypeArr = Db::query("select tstype,name from ien_book_sort");
        $temp_key = array_column($tstypeArr,'tstype');
        $temp_value = array_column($tstypeArr,'name');
        $category = array_combine($temp_key, $temp_value);
        $data_list = Db::table('ien_book book')->field('book.id,(book.jpv+ifnull(pvt.pv,0)) as pv,book.title,book.jpv,book.zuozhe,book.image,book.images,book.desc,book.zishu,book.model,book.cover,book.tstype,book.recommend')
            ->join('ien_book_pv pvt', 'book.id=pvt.bid','LEFT')
            ->where($map)
            ->page($start,$limit)
            ->order('pv desc,id desc')
            ->select();
        if (empty($data_list)) {
            return $data_list;
        }
        foreach ($data_list as $key=>$value) {
            $data['data'][$key]['id'] = (string)$value['id'];
            $data['data'][$key]['title'] = $value['title'];
            $data['data'][$key]['zuozhe'] = $value['zuozhe'];
            $data['data'][$key]['summary'] = empty(trim($value['recommend'])) ? $value['desc'] : $value['recommend'];
            $data['data'][$key]['is_new'] = true;
            $data['data'][$key]['avatar'] = $value['image'] > 0 ? get_thumb($value['image']) : "/public/static/cms/img/default_across.jpg";
            $data['data'][$key]['zid'] = $value['zid'];
            $data['data'][$key]['view'] = $value['pv'] == "" ? 0 : $value['pv'];
            $data['data'][$key]['zishu'] = $value['zishu'];
            $data['data'][$key]['tstype'] = $category[$value['tstype']];
            $data['data'][$key]['tuijian'] = $value['tuijian'];
        }
        foreach ($data['data'] as $k => $v) {
            if ($v['view']>=100000) {
                $data['data'][$k]['view'] = round($v['view']/1000000).'万';
            }
            if($v['zishu'] >= 100000){
                $data['data'][$k]['zishu'] = round($v['zishu'] / 1000000) . '万';
            }
        }
        $data_list=json_decode(json_encode($data),true);
        return  $data_list;
    }

    /**
     * 排行榜
     * @param  int $start  开始
     * @param  int $limit  长度
     * @param  int $sex   类型
     */
    public function rank( $start = null, $limit= null, $sex = null) {
        $map['ien_book.status'] = 1;
        $map['ien_book.cid'] = $sex;
        if(empty($sex)){
            $map['ien_book.cid'] = 3;
        }
        $category = explode("\r\n", DB::table('ien_cms_field')->where('id', 49)->value('options'));
        $data_list = Db::table('ien_book book')->field('book.id,(book.jpv+ifnull(pvt.pv,0)) as pv,book.title,tuijian,book.jpv,book.zuozhe,book.image,book.images,book.desc,book.zishu,book.model,book.cover,book.tstype,book.recommend,book.tag')
                ->join('ien_book_pv pvt', 'book.id=pvt.bid','LEFT')
                ->where($map)
                ->limit($start,$limit)
                ->order('pv desc')
                ->select();

        if (empty($data_list)) {
            return $data_list;
        }
        foreach ($data_list as $key=>$value) {

            $data['data'][$key]['idx'] = Db::table('ien_chapter')->where('bid',$value['id'])->count();
            $data['data'][$key]['id'] = (string)$value['id'];
            $data['data'][$key]['tag'] =  explode(",",$value['tag']);
            $data['data'][$key]['title'] = $value['title'];
            $data['data'][$key]['zuozhe'] = $value['zuozhe'];
            $data['data'][$key]['summary'] = empty(trim($value['recommend'])) ? $value['desc'] : $value['recommend'];
            $data['data'][$key]['is_new'] = true;
            $data['data'][$key]['avatar'] = $value['image'] != 0 ? get_thumb($value['image']) : "/public/static/cms/img/default_erect.jpg";
            $data['data'][$key]['zishu'] = $value['zishu'];
            $data['data'][$key]['zid'] =  empty($value['zid']) ? 0 : $value['zid'];
            $data['data'][$key]['view'] = $value['pv'] == "" ? 0 : $value['pv'];
            $data['data'][$key]['tstype'] = $category[$value['tstype']];
            $data['data'][$key]['tuijian'] = $value['tuijian'];
        }
            foreach ($data['data'] as $k => $v) {
                if ($v['view']>=100000) {
                    $data['data'][$k]['view'] = round($v['view']/10000).'万+';
                }
                if($v['zishu'] >= 100000){
                    $data['data'][$k]['zishu'] = round($v['zishu'] / 10000) . '万';
                }
            }
        $data_list=json_decode(json_encode($data),true);
        return  $data_list;
    }


	 public function indexidx($bid=null)
    {
        session_start();
        $orderby = input('orderby');
        if ($bid === null) $this->error('缺少参数');
        $isbuyall  = DB::table('ien_consume_log')->where(['uid' => $_SESSION['wechat_user']['original']['openid'], 'bid' => $bid, 'zid' => 0])->find();
        $book= Db::table('ien_book')->where('id', $bid)->find();
        $z_total = DB::table('ien_chapter')->where('bid', $bid)->count();
        $max = ceil($z_total / 50);
        $optArr = array();
        for ($i=1; $i <= $max; $i++) {
          $start = (($i - 1) * 50 + 1);
          if($i == $max){
            $end = $z_total;
          }else{
            $end = $i * 50;
          }
          $optArr[] = $start . ' - ' . $end . ' 章';
        }
        $share = [
            'title' => '快看，我发现了一本好书——《'.$book['title'].'》',
            'desc' => str_replace(["\n", "\r", "\n\r"], ['', '', ''], strip_tags($book['desc'])),
            'img' => !empty($book['image']) ? $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . get_thumb($book['image']) : $book['cover']
        ];
        $this->assign('share', $share);
        $this->assign('optArr', $optArr);
        $this->assign('orderby', $orderby);
        $this->assign('z_total', $z_total);
        $this->assign('isbuyall', $isbuyall);
        $this->assign('book', $book);
        $this->assign('bid', $bid);

        return $this->fetch("change");

    }

		//小说ajax
    public function doajaxidx($bid = null , $start = null)
    {
      $pagenum = 30;
      $order = 'idx ' . input('orderby');
      if ($bid === null) $this->error('缺少参数');
      $map['bid']=$bid;
      $bid = intval($bid);
      $readZids = array();
      $buyZids = array();
      if(!empty($_SESSION['wechat_user']['original']['openid'])){
        $isbuyall  = DB::table('ien_consume_log')->where(['uid' => $_SESSION['wechat_user']['original']['openid'], 'bid' => $bid])->where('max_zid', '<>', 0)->find();
        $buyList = DB::table('ien_consume_log')->where(['uid' => $_SESSION['wechat_user']['original']['openid'], 'bid' => $bid])->select();
        foreach($buyList as $val){
            $buyZids[] = $val['zid'];
        }
      }
      $cur_read = $this->getReadHistory($_SESSION['wechat_user']['original']['openid'], $bid);
      $cur_idx = DB::table('ien_chapter')->where('id', $cur_read['zid'])->value('idx');
      /**********限免************/
      $time = time();
      $free_limit_book = Db::query("select distinct bid from ien_free_limit_book where xsid in (select id from ien_free_limit where start_time < $time and (end_time > $time or end_time=0) and status=1)");
      $bidArr = array_column($free_limit_book,'bid');
      /**********限免************/


      /*********免费*************/
        $Gratis_id = Db::table('ien_tj_book')->field('bid')->where('tjid',2)->select();
        $Gratis_id = array_column($Gratis_id,'bid');
//
//        dump($bid);
//        dump(in_array($bid,$Gratis_id) );
//        die;

        /*********免费*************/

      if($start!="")
      {
        $data_list=DB::table('ien_chapter')->field('ien_chapter.*,ien_book.images')->join('ien_book','ien_chapter.bid = ien_book.id')->where($map)->limit($start, $pagenum)->order($order)->select();
      } else {
        $data_list=DB::table('ien_chapter')->field('ien_chapter.*,ien_book.images')->join('ien_book','ien_chapter.bid = ien_book.id')->where($map)->limit($start, $pagenum)->order($order)->select();
      }


      foreach($data_list as $key=>$value)
      {
        $data['data'][$key]['id']=(string)$value['id'];
        $data['data'][$key]['image'] = $value['images'] != 0 ? get_file_path($value['images']) : "/public/static/cms/img/default_across.jpg";
        $data['data'][$key]['time'] =  date('m-d H:i',$value['update_time']);
        $data['data'][$key]['title']=$value['title'];
        $data['data'][$key]['idx']=$value['idx'];
        $data['data'][$key]['welth']="0";
        $data['data'][$key]['isvip']=$value['isvip'];
        $data['data'][$key]['buyZids'] = $buyZids;
        if(!empty($isbuyall) || ($value['isvip'] == 0 && $value['idx'] < $cur_idx) || (!empty($buyList) && in_array($value['id'], $buyZids))){
            $data['data'][$key]['isvip'] = 2;
        }
        if (in_array($value['bid'], $bidArr)) {
            $data['data'][$key]['isvip'] = 4;
        }

        if(in_array($bid,$Gratis_id)) {
            $data['data'][$key]['isvip'] = 5;
        }
          if($cur_read['zid'] == $value['id']){
              $data['data'][$key]['isvip'] = 3;
          }
      }

        $data_list=json_decode(json_encode($data),true);
        return  $data_list;
    }


    // 上拉加载更多
    public function ajaxidx(){
        $start = $_GET['start'];
        $bid = $_GET['bid'];
        $map['bid'] = $bid;
        $pagenum = $_GET['count'];
        $readZids = array();
        $buyZids = array();
        if(!empty($_SESSION['wechat_user']['original']['openid'])){
            $isbuyall  = DB::table('ien_consume_log')->where(['uid' => $_SESSION['wechat_user']['original']['openid'], 'bid' => $bid])->where('max_zid', '<>', 0)->find();
            $buyList = DB::table('ien_consume_log')->where(['uid' => $_SESSION['wechat_user']['original']['openid'], 'bid' => $bid])->select();
            foreach($buyList as $val){
                $buyZids[] = $val['zid'];
            }
        }
        $cur_read = $this->getReadHistory($_SESSION['wechat_user']['original']['openid'], $bid);
        $cur_idx = DB::table('ien_chapter')->where('id', $cur_read['zid'])->value('idx');
        /**********限免************/
        $time = time();
        $free_limit_book = Db::query("select distinct bid from ien_free_limit_book where xsid in (select id from ien_free_limit where start_time < $time and (end_time > $time or end_time=0) and status=1)");
        $bidArr = array_column($free_limit_book,'bid');
        $data_list=DB::table('ien_chapter')->field('ien_chapter.*,ien_book.images')->join('ien_book','ien_chapter.bid = ien_book.id')->where($map)->page($start, $pagenum)->select();

        foreach($data_list as $key=>$value)
        {
            $data['data'][$key]['id']=(string)$value['id'];
            $data['data'][$key]['image'] = get_file_path($value['images']);
            $data['data'][$key]['time'] =  date('m-d H:i',$value['update_time']);
            $data['data'][$key]['title']=$value['title'];
            $data['data'][$key]['idx']=$value['idx'];
            $data['data'][$key]['welth']="0";
            $data['data'][$key]['isvip']=$value['isvip'];
            $data['data'][$key]['buyZids'] = $buyZids;
            if(!empty($isbuyall) || ($value['isvip'] == 0 && $value['idx'] < $cur_idx) || (!empty($buyList) && in_array($value['id'], $buyZids))){
                $data['data'][$key]['isvip'] = 2;
            }
            if (in_array($value['bid'], $bidArr)) {
                $data['data'][$key]['isvip'] = 4;
            }
            if($cur_read['zid'] == $value['id']){
                $data['data'][$key]['isvip'] = 3;
            }
        }
        $data_list=json_decode(json_encode($data),true);

        return  $data_list;
    }






    /**
     * 获取栏目面包屑导航
     * @param int $id
     * @author 拼搏 <378184@qq.com>
     */
    public function getBreadcrumb($id)
    {
        $columns = ColumnModel::where('status', 1)->column('id,pid,name,url,target,type');
        foreach ($columns as &$column) {
            if ($column['type'] == 0) {
                $column['url'] = url('cms/column/index', ['id' => $column['id']]);
            }
        }

        return Tree::config(['title' => 'name'])->getParents($columns, $id);
    }



/**********Ajax通过cid取相关类型************/
    public function cid2sort()
    {
        $cid = input('param.');
        $cid = $cid['cid'];
        $tstype = $cid['tstype'];
        if ($cid == "") {
            $category = Db::query("select tstype,name from ien_book_sort where status=1 order by sort desc,tstype desc");
        }else{
            $category = Db::query("select tstype,name from ien_book_sort where (cid=$cid or cid=0) and status=1 order by sort desc,tstype desc");
        }
        return $category;
    }
/**********Ajax通过cid和tstype判断此cid下是否有此tstype************/
    public function confirm2cate()
    {
        $getData = input('param.');
        $cid = $getData['cid'];
        $tstype = $getData['tstype'];
        // var_dump($getData);
        if ($cid != "") {
            $res = Db::query("select tstype from ien_book_sort where status=1 and (cid=$cid or cid=0)");
        }else{
            $res = Db::query("select tstype from ien_book_sort where status=1");
        }

        $arr = array_column($res,"tstype");
        if (in_array($tstype, $arr)) {
            return 1;
        }else{
            return 0;
        }
    }

  /**********重新选择类别后是否还包含此分类************/
    public function cid2resort()
    {
        $getData = input('param.');
        $cid = $getData['cid'];
        $tstype = $getData['tstype'];
        if ($cid != "") {
            $res = Db::query("select tstype from ien_book_sort where status=1 and cid=$cid");
        }else{
            $res = Db::query("select tstype from ien_book_sort where status=1");
        }

        $arr = array_column($res,"tstype");
        if (in_array($tstype, $arr)) {
            $return=[];
            $return['code'] = 1;
            $return['tstype'] = $tstype;
            return $return;
        }else{
            $return=[];
            $return['code'] = 0;
            $return['tstype'] = $tstype;
            return $return;
        }
    }
}