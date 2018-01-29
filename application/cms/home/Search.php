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

/**
 * 前台搜索控制器
 * @package app\cms\admin
 */
class Search extends Common
{
    /**
     * 搜索列表
     * @param string $keyword 关键词
     * @author 拼搏 <378184@qq.com>
     * @return mixed
     */
    public function index($keyword = '',$start=0)
    {
        $id = $_GET['id'];
        $type = $_GET['type'];
        if ($keyword != '' && $type != 1) {
            $keyword = urldecode($keyword);
            $map = [
                'ien_book.status' => 1,
                'ien_book.title' => ['like', "%$keyword%"]
            ];

            $data_list = Db::view('ien_book')
                ->view('ien_chapter', 'id as zid', 'ien_book.id=ien_chapter.bid')
                ->where('ien_chapter.idx=1')
                ->where($map)
                ->order('sort desc')
                ->limit($start,10)
                ->select();

            if ($data_list){
                foreach ($data_list as $k => &$v) {
                    $v['image'] =$v['image']!=0 ? get_file_path($v['image']): "/public/static/cms/img/default_erect.jpg";
                    $v['tag'] = explode(',', $v['tag']);
                    $cid[] = $v['id'];
                    $v['comment'] = Db::table('ien_comment')->where("bid", $v['id'])->count();
                    unset($v);
                }
                $res['data'] = $data_list;
                $res['total'] = 1;

                $data_list = json_decode(json_encode($res), true);

                $data_list = json_decode(json_encode($data_list));


                // 增加搜索记录
                return $data_list;
            }else{
                $res['data'] = [];
                $res['total']=0;
                return json($res);
            }


        }else {
            if ($type == 1) {
                $user = DB::table('ien_admin_user')->where('openid', $_SESSION['wechat_user']['original']['openid'])->find();
                    if ($user != null)
                        $data = array();
                    $openid = $user['openid'];
                    $searchLog = DB::table('ien_search_log')->where("uid ='$openid'")->whereIn('cid', $id)->find();
                    if ($searchLog) {
                        $data['updatetime'] = time();
                        $data['count'] = $searchLog['count'] + 1;
                        Db::table('ien_search_log')->where("uid ='$openid'")->whereIn('cid', $id)->update($data);
                    } else {
                        $data['cid'] = $id;
                        $data['uid'] = $openid;
                        $data['createtime'] = time();
                        $data['updatetime'] = $data['createtime'];
                        $data['count'] = 1;
                        Db::table('ien_search_log')->insert($data);
                    }

            }
        }
            return $this->fetch(); // 渲染模板

    }
        /**
     * 搜索列表
     * @param string $keyword 关键词
     * @author 拼搏 <378184@qq.com>
     * @return mixed
     */
    public function list_search($keyword = '')
    {
        if ($keyword == '') $this->error('请输入关键字');
        $map = [
            'cms_document.trash'  => 0,
            'cms_document.status' => 1,
            'cms_document.title'  => ['like', "%$keyword%"]
        ];

        $data_list = Db::view('cms_document', true)
            ->view('admin_user', 'username', 'cms_document.uid=admin_user.id', 'left')
            ->where($map)
            ->order('create_time desc')
            ->paginate(config('list_rows'));

        $this->assign('keyword', $keyword);
        $this->assign('lists', $data_list);
        $this->assign('pages', $data_list->render());

        return $this->fetch(); // 渲染模板
    }

    /**
     *  删除搜索历史
     * @param string $id 书id
     * @return json   返回删除结果
     */
    public function del($id="")
    {
        if ($id == ""){
            $this->error("参数错误");
        }
        if($this->request->isAjax()){
            $id = $_GET['id'];

            $user  = Db::table("ien_admin_user")->where('openid',$_SESSION['wechat_user']['original']['openid'])->find();

            $re = DB::table('ien_search_log')
                ->where('cid',$id)
                ->where('uid',$user['openid'])
                ->delete();

            if ($re){
                $data['msg'] = '删除成功!';
                $data['code'] ='1';
            }else{
                $data['msg'] = '删除失败,请重试';
                $data['code'] = '0';
            }

            return json($data);
        }
    }


    public function getsearch()
    {

        $start = $_POST['start'];
        $limit = $_POST['limit'];

        $user = DB::table('ien_admin_user')->where('openid', $_SESSION['wechat_user']['original']['openid'])->find();
        $list = Db::table('ien_search_log search')
            ->field("book.id,book.title,book.model,book.sort,book.zuozhe,book.desc,book.image,book.view,search.count,book.tag,search.updatetime")
            ->join('ien_book book','book.id = search.cid','LEFT')
            ->where('search.uid',$user['openid'])
            ->order('search.count desc,search.updatetime desc')
            ->limit($start,$limit)
            ->select();

        if ($list) {
            foreach ($list as $key => &$value) {
                $value['comment'] = Db::table('ien_comment')->where("bid", $value['id'])->count();
                $value['image'] =$value['image']!=0 ? get_file_path($value['image']): "/public/static/cms/img/default_erect.jpg";
                $value['tag'] = explode(',', $value['tag']);
            }
            unset($value);
            return json($list);
        }


    }


    /**
     *  点击删除触发的历史查询函数
     *
     * @return \think\response\Json
     */
    public function search()
    {
        $start = $_POST['start'];
        $limit = $_POST['limit'];
        $user = DB::table('ien_admin_user')->where('openid', $_SESSION['wechat_user']['original']['openid'])->find();
        $list = Db::table('ien_search_log search')
            ->field("book.id,book.title,book.model,book.sort,book.zuozhe,book.desc,book.image,book.view,search.count,book.tag,search.updatetime")
            ->join('ien_book book','book.id = search.cid','LEFT')
            ->where('search.uid',$user['openid'])
            ->order('search.count desc,search.updatetime desc')
            ->page($start,$limit)
            ->select();

        if ($list) {
            foreach ($list as $key => &$value) {
                $value['comment'] = Db::table('ien_comment')->where("bid", $value['id'])->count();
                $value['image'] =$value['image']!=0 ? get_file_path($value['image']): "/public/static/cms/img/default_erect.jpg";
                $value['tag'] = explode(',', $value['tag']);
            }
            unset($value);
            return json($list);
        }


    }
}