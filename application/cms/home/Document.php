<?php
namespace app\cms\home;

use app\cms\model\Column as ColumnModel;
use app\cms\model\Document as DocumentModel;
use function GuzzleHttp\Promise\queue;
use util\Tree;
use think\Db;
/**
 * 文档控制器
 * @package app\cms\home
 */
class Document extends Common {
    /**
     * 文档详情页
     * @param null $id 文档id
     * @param string $model 独立模型id
     * @author
     * @return mixed
     */
	public $gift = array(array('money' => '100', 'name' => '玫瑰'), array('money' => '200', 'name' => '礼包'), array('money' => '500', 'name' => '金牌'), array('money' => '1000', 'name' => '钻石'), array('money' => '5000', 'name' => '皇冠'));
    public function detail($id = null,$t=null, $updateReadHistory = false,$zpay=null)
    {
        //if ($idx === null) $this->error('缺少参数');
        //if ($bid === null) $this->error('缺少参数');
        /*登陆验证方法*/

        session_start();

        $user = DB::table('ien_admin_user')->where('openid', $_SESSION['wechat_user']['original']['openid'])->find();
        // ajax加载更多
        if ($this->request->isAjax()) {
            $start = $_GET['start'];
            $limit = $_GET['limit'];
            $idx = $_GET['idx'];
            $bid = $_GET['bid'];


            $pictureCfg = DB::table('ien_chapter')->field('id,content')->where('idx', $idx)->where('bid', $bid)->find();
                $res = explode(',', $pictureCfg['content']);
                $res = array_splice($res, $start, $limit);
                if ($res == null){
                    return json([]);
                }
                $domain = config('__DOMAIN__');     // 读取配置的域名
                foreach ($res as $key => $v) {
                    $picture[] = $domain . get_file_path($v);
                }

                $updateReadHistory = true;
            if ($updateReadHistory == 'true') {
                $this->readold($pictureCfg['id'], $bid, $user);

            }
            return json($picture);

        }




        $_SESSION['target_url'] = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        if(empty($_SESSION['wechat_user'])){
            $this->redirect('oauth/oauth');
        }
        $f = input('f');
        $yz['id']=$id;
        $chapterCfg=DB::table('ien_chapter')->where($yz)->find();
      $chapterCfg['content'] =   explode(',',$chapterCfg['content']);
        $chapterCfg['content'] = array_splice($chapterCfg['content'],0,4);
        foreach($chapterCfg['content'] as $key => &$value){
            $value = get_file_path($value);
        }
        $user=DB::table('ien_admin_user')->where('openid',$_SESSION['wechat_user']['original']['openid'])->find();

        if(empty($chapterCfg)) {
            $this->redirect('index/index');
        }
        // $book=Db::table('ien_book')->field('image, title')->where('id', $chapterCfg['bid'])->find();
        // $chapterCfg['content']= nl2br($chapterCfg['content']);

        // $chapterCfg['content'] = '<p>' . str_replace(array("\r\n", "\r", "\n"), "</p><p>", $chapterCfg['content']) . '</p>';

        $book=Db::table('ien_book')->field('image,title,cover,id,packsell,pay_desc,xstype')->where('id', $chapterCfg['bid'])->find();
        $is_collect = DB::table('ien_book_collect')->where(['bid' => $chapterCfg['bid'], 'uid' => $user['openid']])->find();
        $share = [
            'title' => '《' . $book['title'] . '》第' . $chapterCfg['idx'] . '章',
            'desc' => strip_tags(mb_substr($chapterCfg['content'], 0, 300)),
            'img' => !empty($book['image']) ? $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . get_thumb($book['image']) : $book['cover']
        ];
        $this->assign('share', $share);

//        //判断强制关注章节
        $guanzhu=$this->gzzj($id);

//        //判断关注章节跳转
        $isgz=$this->isguanzhu($id,$guanzhu,$f,$t);
        /**********限免************/
        $time = time();
        $free_limit_book = Db::query("select distinct bid from ien_free_limit_book where xsid in (select id from ien_free_limit where start_time < $time and (end_time > $time or end_time=0) and status=1) and type=0");
        $bidArr = array_column($free_limit_book,'bid');
        $free_book_id = DB::table('ien_chapter')->where($yz)->value('bid');

        /********免费**********/
        $Gratis_id = Db::table('ien_tj_book')->field('bid')->where('tjid',2)->select();
        $Gratis_id = array_column($Gratis_id,'bid');

        /***********免费********/
        if (!in_array($free_book_id, $bidArr) && !in_array($free_book_id,$Gratis_id)) {

            //验证VIP章节，是否消费过
            $isvip = $this->isvip($id);
            if(isset($isvip['code'])){
                $chapterCfg['content'] = array_slice($chapterCfg['content'],0,2);
                $usercoin = $user['coin'] + $user['score'];


                $pro=DB::table('ien_cuxiao')->where('leixing',1)->order('orderby asc')->select();


                $noselect = Db::table('ien_cuxiao')->where('leixing',1)->where('status',1)->select();

                if(!empty($book['id'])){
                    $curtime = time();
                    //是否有活动进行中
                    $bookactive = DB::table('ien_free_limit_book')->where(['bid' => $book['id'], 'type' => 1])->select();

                    $bookactiveid = array();
                    foreach($bookactive as $val){
                        $bookactiveid[] = $val['xsid'];
                    }
                    $booklist = DB::table('ien_free_limit_book')->whereIn('bid', $book['id'])->where('type', 1)->select();
                    $activeid = array();
                    foreach ($booklist as $val) {
                        $activeid[] = $val['xsid'];
                    }
                    $active = DB::table('ien_cuxiaolist')
                        ->where(['starttime' => ['<', $curtime], 'endtime' => ['>', $curtime], 'is_delete' => '0', 'status' => 1, 'type' => 1])
                        ->whereIn('id', implode(',', $activeid))
                        ->order('createtime asc')
                        ->find();


                    if(empty($active)){
                        $subsql = DB::table('ien_free_limit_book')
                            ->field('count(*) as num,xsid')
                            ->where('type', 1)
                            ->group('xsid')
                            ->buildSql();


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
                    $is_first = DB::table('ien_pay_log')
                        ->where(['userid' => $user['id'], 'status' => 1])
                        ->find();


                    if(empty($is_first) && !empty($active)){
                        $pro = DB::table('ien_cuxiao')
                            ->where(['leixing' => 3, 'cxid' => $active['id']])
                            ->order('orderby asc')
                            ->select();

                        $noselect = Db::table('ien_cuxiao')
                            ->where(['leixing' => 3, 'status' => 1])
                            ->select();

                        $this->assign('active', $active);

                    }


                }

                $this->assign('usercoin', coinToMoney($usercoin));
                $this->assign('noselect', $noselect);
                $this->assign('pro', $pro);
                $this->assign('zffs', module_config('agent.agent_pay_fangshi'));
                $chapter=DB::table('ien_chapter')->where('id',$id)->find();
                $this->assign('chaptername', $chapter['title']);
                $bookzpay=Db::table('ien_book')->field('title,id,packsell,zishu,price')->where('id', $chapter['bid'])->find();
                $this->assign('bookzpay', $bookzpay);
//                $bookprice = !empty($bookzpay['price']) ? coinToMoney($bookzpay['price']) : coinToMoney(getCostScore($bookzpay['zishu']));
//                $original = coinToMoney(getOriginal($bookzpay['id']));
//                $this->assign('bookprice', $bookprice);
//                $this->assign('original', $original);



                /** 定义单章价格*/
                $this->assign('shubi', 7000);

                if ($isvip['status'] != true && $isvip['code'] == 2) {
                    //单章购买,不够钱
                    $this->assign('zpay', $isvip);
                }
            }

        }
        /**********限免end************/
//        /**********广告管理************/
//        $advid = Db::table('ien_advertise_book')->field('advid')->where('bid',$free_book_id)->select();
//
//        $arr = array_column($advid,'advid');
//        $advids = implode(',', $arr);
//        $advids = '('.$advids.')';
//        if ($advid) {
//            $num0id = Db::query("select id from ien_advertise where start_time < $time and (end_time > $time or end_time=0) and status=1 and num=0 order by rand() limit 1");
//            if ($num0id) {//有适用全部的就加入
//                $advertise = Db::query("select * from ien_advertise where start_time < $time and (end_time > $time or end_time=0) and status=1 and (id in $advids or id={$num0id[0]['id']}) order by rand() limit 1");
//            }else{
//                $advertise = Db::query("select * from ien_advertise where start_time < $time and (end_time > $time or end_time=0) and status=1 and id in $advids order by rand() limit 1");
//            }
//
//            if (!$advertise) {//加了小说的广告都关了就取适用全部的
//                $advertise = Db::query("select * from ien_advertise where start_time < $time and (end_time > $time or end_time=0) and status=1 and num=0 order by rand() limit 1");
//            }
//        }else{
//            $advertise = Db::query("select * from ien_advertise where start_time < $time and (end_time > $time or end_time=0) and status=1 and num=0 order by rand() limit 1");
//        }
//        if(!empty($advertise)){
//            if(empty($advertise[0]['url'])){
//                $advertise[0]['url'] = empty($advertise[0]['bid']) ? '' :url('document/desc', ['bid' => $advertise[0]['bid'], 'comefrom' => 11, 'subid' => $advertise[0]['id']]);
//            }else{
//                $advertise[0]['url'] = strpos($advertise[0]['url'], '?') ? $advertise[0]['url'] . '&comefrom=11&subid=' . $advertise[0]['id'] : $advertise[0]['url'] . '?comefrom=11&subid=' . $advertise[0]['id'];
//            }
//        }
//        $this->assign('advertise', $advertise);
//
//        /**********广告管理end*********/
        /**********热门推荐************/
//        $chapter = DB::table('ien_chapter')->field('isvip')->where('id',$id)->find();
//        $time = time();
//        if ($chapter['isvip'] == 1) {
//            $act_limit = Db::query("select display_num,id,start_time,end_time from ien_hot where start_time < $time and (end_time > $time or end_time=0) and status=1 and (part=0 or part=2)");
//        }else{
//            $act_limit = Db::query("select display_num,id,start_time,end_time from ien_hot where start_time < $time and (end_time > $time or end_time=0) and status=1 and (part=0 or part=1)");
//        }
//        if ($act_limit) {
//            foreach ($act_limit as $k => $v) {
//                if ($v['end_time'] == 0) {
//                    $act_limit[$k]['end_time'] = 2114265599;
//                }
//            }
//            $flag = array();
//            foreach($act_limit as $v){
//                $flag[] = $v['end_time'];
//            }
//            array_multisort($flag, SORT_ASC, $act_limit);
//            $hotid = $act_limit[0]['id'];
//            $display_num = $act_limit[0]['display_num'];
//        }
//        if ($hotid) {
//            $hot_tj = Db::table('ien_hot_tj')->field('id,name,link,bid')->where('hotid',$hotid)->limit($display_num)->order('rand()')->select();
//
//            foreach($hot_tj as &$val){
//                if(empty($val['link'])){
//                    $val['link'] = empty($val['bid']) ? '' :url('document/desc', ['bid' => $val['bid'], 'comefrom' => 11, 'subid' => $val['id']]);
//                }else{
//                    $val['link'] = strpos($val['link'], '?') ? $val['link'] . '&comefrom=11&subid=' . $val['id'] : $val['link'] . '?comefrom=11&subid=' . $val['id'];
//                }
//            }
//            unset($val);
//        }else{
//            $hot_tj = [];
//        }
//        $this->assign('hot_tj', $hot_tj);
        /**********热门推荐end*********/

        //添加阅读历史
        $updateReadHistory = true;
        if($updateReadHistory == 'true'){
            $this->readold($id, $chapterCfg['bid'], $user);
        }

        //每日签到积分

        $this->assign('user', $user);

        $this->assign('book', $book);
        $this->assign('zffs', module_config('agent.agent_pay_fangshi'));
        $this->assign('document', $chapterCfg);
        $this->assign('is_collect', $is_collect);
        $this->assign('style', explode(',', $user['style']));
        $this->assign('isgz', $isgz);
        $this->assign('next', $this->getNext($id));

//
//        //上一张
        $this->assign('prev', $this->getPrev($id));
        $this->assign('tgid', $t);
        $this->assign('f', $f);

        $sxid=0;
        if ($user['sxid']==0) {
            $sxid=0;
        } else {
            $sxid=$user['sxid'];
        }
        //跳转关注页面
        $this->assign('sxid', $sxid);
        $this->assign('tgid', $t);
        $this->assign('chapterId', $id);
        return $this->fetch('read');
    }





    /**
     * 获取栏目面包屑导航
     * @param int $id 栏目id
     * @author 拼搏 <378184@qq.com>
     */
    private function getBreadcrumb($id) {
        $columns = ColumnModel::where('status', 1)->column('id,pid,name,url,target,type');
        foreach ($columns as &$column) {
            if ($column['type'] == 0) {
                $column['url'] = url('cms/column/index', ['id' => $column['id']]);
            }
        }
        return Tree::config(['title' => 'name'])->getParents($columns, $id);
    }

    /**
     * 获取上一篇文档
     * @param int $id 当前文档id
     * @param string $model 独立模型id
     * @author 拼搏 <378184@qq.com>
     * @return array|false|\PDOStatement|string|\think\Model
     */
    private function getPrev($id) {
        $cha = DB::table('ien_chapter')->where('id',$id)->find();
		$idx = $cha['idx'] - 1;
		if ($idx > 0 ) {
			$map['bid']=$cha['bid'];
			$map['idx']=$idx;
			$doc = DB::table('ien_chapter')->where($map)->find();
	        if ($doc) {
	            $doc['url'] = url('cms/document/detail', ['id' => $doc['id']]);
	        } else {
				$doc['url'] = url('cms/index/index');
			}
		} else {
			$doc['url'] = url('cms/index/index');
		}
        return $doc;
    }

    /**
     * 获取下一篇文档
     * @param int $id 当前文档id
     * @param string $model 独立模型id
     * @author 拼搏 <378184@qq.com>
     * @return array|false|\PDOStatement|string|\think\Model
     */
    private function getNext($id) {
        $cha=DB::table('ien_chapter')->where('id',$id)->find();
		$idx=$cha['idx']+1;
		$map['bid']=$cha['bid'];
		$map['idx']=$idx;
		$doc=DB::table('ien_chapter')->where($map)->find();
        if ($doc) {
            $doc['url'] = url('cms/document/detail', ['id' => $doc['id']]);
        } else {
			$doc['url'] = url('cms/index/index');
		}
        return $doc;
    }

    public function addbookmark($id=null){
		session_start();
        $_SESSION['target_url'] = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        if(empty($_SESSION['wechat_user'])){
            $this->redirect('oauth/oauth');
        }
        if($id=='') {
        	return false;
        }
        $data = ['uid' =>$_SESSION['wechat_user']['original']['openid'],
			'zid' => $id,
		];
        Db::table('ien_bookmarks')->insert($data);
        return true;
    }

	//添加阅读历史记录
	public function readold($zid = null,$bid = null,$user = null) {
		if ($user != null) {
			$data = array();
			//查询是否有这本书的记录
			$map['bid'] = $bid;
			$map['uid'] = $user['openid'];
			$readCfg = DB::table('ien_read_log')->where($map)->find();
			//如果有，更新章节和更新时间，如果没有插入记录。
			if ($readCfg) {
				$data['zid'] 			= $zid;
				$data['userid']			= $user['id'];
				$data['update_time'] 	= time();
				Db::table('ien_read_log')->where($map)->update($data);
			} else {
				$data['uid']			= $user['openid'];
				$data['userid']			= $user['id'];
				$data['zid']			= $zid;
				$data['bid']			= $bid;
				$data['create_time'] 	= time();
				$data['update_time'] 	= $data['create_time'];
				Db::table('ien_read_log')->insert($data);
			}
		}
	}

	//判断当前用户，代理商设置的关注章节
	public function gzzj($id = null) {
		$openid = $_SESSION['wechat_user']['original']['openid'];
		//获取用户信息
		$user = DB::table('ien_admin_user')->where('openid',$openid)->find();

		if ($user['tgid']!=0) {
			//判断是否从推广链接进来
			$agent=DB::table('ien_agent')->where('id',$user['tgid'])->find();
			if ($agent) {
				//判断代理商是否设置了强制关注的ID
				$agentuser=DB::table('ien_admin_user')->where('id',$agent['uid'])->find();
				if ($agentuser['guanzhu']) {
					return $agentuser['guanzhu'];
				} else {
	              	try{
	                	$bid = DB::table('ien_chapter')->where('id',$id)->column('bid');
	    				$gzid = DB::table('ien_book')->where('id',$bid['0'])->column('gzzj');
	                  	if (!empty($gzid['0']) && $gzid['0']>0) {
	                		return $gzid['0'];
	                  	} else {
	                    	return module_config('agent.agent_guanzhu');
	                  	}
	                } catch (\Exception $e){
	                	return module_config('agent.agent_guanzhu');
	              	}
				}
			} else {
				try{
	                $bid = DB::table('ien_chapter')->where('id',$id)->column('bid');
	    			$gzid = DB::table('ien_book')->where('id',$bid['0'])->column('gzzj');
	                if (!empty($gzid['0']) && $gzid['0']>0) {
						return $gzid['0'];
	                } else {
	                    return module_config('agent.agent_guanzhu');
	                }
	            } catch(\Exception $e) {
					return module_config('agent.agent_guanzhu');
				}
			}
		} else {
			try{
	            $bid = DB::table('ien_chapter')->where('id',$id)->column('bid');
				$gzid = DB::table('ien_book')->where('id',$bid['0'])->column('gzzj');
				if (!empty($gzid['0']) && $gzid['0']>0) {
					return $gzid['0'];
				} else{
					return module_config('agent.agent_guanzhu');
				}
	        } catch(\Exception $e) {
	            return module_config('agent.agent_guanzhu');
	        }
		}
	}

	//判断是否需要关注
	public function isguanzhu($zid=null,$guanzhu=null,$f=0,$t=0) {

		$chapterCfg=DB::table('ien_chapter')->where('id',$zid)->find();
		$gz=DB::table('ien_admin_user')->where('openid',$_SESSION['wechat_user']['original']['openid'])->find();
		$data['forceFollow']="true";
		$data['showFollowPopupOnNext']="false";
		$data['zindex'] = $chapterCfg['idx'];
		// if($gz['isguanzhu'] == 0) {
		// 	$data['forceFollow']="false";
	 //      	if($chapterCfg['idx'] >= $guanzhu){
		// 		$data['showFollowPopupOnNext']="true";
		// 	}
		// }
		if($f == 1) {
			$data['forceFollow']="false";
	      	if($chapterCfg['idx'] >= $guanzhu){
				$data['showFollowPopupOnNext']="true";
			}
		}
		$imageid = '';
		if(!empty($t)){
			$imageid = DB::table('ien_agent')->where('id', $t)->value('imageid');
			$data['ewm'] = get_thumb($imageid);
		}
		//跳转关注页面
		// return $imageid;
		if(empty($imageid) || empty($data['ewm']) || strpos($data['ewm'], 'none.png')){
			$data['ewm'] = DB::table('ien_book')->where('id',$chapterCfg['bid'])->value('qrcodeimg');
			if ($data['ewm'] == '') {
				$user = DB::table('ien_admin_user')->where('openid', $_SESSION['wechat_user']['original']['openid'])->find();
				if ($user['sxid'] == 0) {
					$sxid = 0;
				} else {
					$sxid = $user['sxid'];
				}
				$data['ewm'] = $this->erweima($sxid);
			}
		}

		return $data;
		//$this->redirect('document/erweima',['id'=>$sxid]);
	}

	public function erweima($id=null) {
		$erweima=DB::table('ien_admin_user')->where('id',$id)->find();
		if (strlen($erweima['ewm'])<5) {
			$ewm=get_thumb(module_config('agent.agent_qzgzewm'));
		} else {
			$ewm=$erweima['ewm'];
		}
		//$this->assign('ewm', $ewm);

		return $ewm;
	}

	public function payerweima($id=null) {
		$erweima=DB::table('ien_admin_user')->where('id',$id)->find();
		if (strlen($erweima['ewm'])<5) {
			$ewm=module_config('agent.agent_qzgzewm');
		} else {
			$ewm=$erweima['ewm'];
		}

		$this->assign('ewm', $ewm);
		return $this->fetch('payerweima');
	}

	//判断是否VIP章节
	public function isvip($zid=null) {
		$chapter = DB::table('ien_chapter')->where('id',$zid)->find();
        if ($chapter['isvip'] == 1) {

            //判断年费会员
			$user=DB::table('ien_admin_user')->where('openid',$_SESSION['wechat_user']['original']['openid'])->find();
			$userscore = $user['score'] + $user['coin'];
			if ($user['isvip']==1 && time()>$user['vipstime'] && time()<=$user['vipetime']) {

				$this->readold($zid);
				return true;
			}

			//判断是否消费过
			$map['uid'] = $_SESSION['wechat_user']['original']['openid'];
			$map['zid'] = $zid;
			$map['max_zid'] = 0;
			$pay = DB::table('ien_consume_log')->where($map)->find();

//			// 整书购买
//			$isbuyall  = DB::table('ien_consume_log')->where(['uid' => $_SESSION['wechat_user']['original']['openid'], 'bid' => $chapter['bid']])->where('max_zid', '<>', 0)->find();

//			if (empty($isbuyall) && !$pay) {
			if (!$pay) {


				//判断余额是否够金额
				//会员为空，余额不足，

                /*****/
//                单章价格 需要修改
				$score = 7000 ;//当前章节价格

                /****/
				$bookCfg = DB::table('ien_book')->where('id',$chapter['bid'])->find();
//				if($bookCfg['packsell'] == 1){
//					$bookprice = !empty($bookCfg['price']) ? $bookCfg['price'] : getCostScore($bookCfg['zishu']);//整本价格
//					if($bookprice > $userscore){
//						return ['status' => false,'code' => '1'];
//					}else{
//						return ['status' => true,'code' => '1','zid' => $chapter['id'],'bid' => $bookCfg['id']];
//					}
//				}else{
					if($score > $userscore) {
						return ['status' => false,'code' => '2'];
					}else{
						//消费积分，保存消费记录，添加阅读记录
						$data['zid'] = $zid;
						$data['bid'] = $bookCfg['id'];
						$data['uid'] = $user['openid'];
						$data['userid'] = $user['id'];
						$data['addtime'] = time();
						//减少会员积分
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
						$res = DB::table('ien_consume_log')->insert($data);
						$this->readold($zid);
						return true;
					}
//				}
			} else {
				$this->readold($zid);
				return true;
			}
		} else {
			//添加阅读记录
			$this->readold($zid);
			return true;
		}
	}

	// 按字收费计算书币
	public function getCostScore($zishu){
		return floor($zishu / 100);
	}

	//每日首次登陆赠送积分
	public function addcore($usecenter=null){
		// $cur_date = strtotime(date('Y-m-d'));
		//如果会员中心过来的开启session;
		if (empty($_SESSION['wechat_user'])) {
			session_start();
		}

		//$map['create_time']=$cur_date;
		$user = DB::table('ien_admin_user')->where('openid',$_SESSION['wechat_user']['original']['openid'])->find();
		$map['userid'] = $user['id'];
		$map['from'] = 2;
		$addlog=DB::table('ien_addcoin_log')->where($map)->whereTime('createtime', 'today')->find();

		if (!$addlog) {
			$data['uid']=$_SESSION['wechat_user']['original']['openid'];
			$data['addtime']=time();
			$data['type'] = 0;
			$data['score'] = 8;
			$data['money'] = 0;
			//验证必须关注
			if ($user['isguanzhu'] == 1) {
				DB::table('ien_admin_user')->where('openid',$_SESSION['wechat_user']['original']['openid'])->setInc('score', $data['score']);

				// 添加获得书币日志
				$this->addCoinLog($user['id'], $data['score'], 0, 2, '签到获得');
				// 小于2张推荐票时，每天赠送2张
				if ($user['tuijian'] < 2) {
					DB::table('ien_admin_user')->where('id', $user['id'])->setInc('tuijian', 2);
				}

				$data['status'] = 1;
				return json($data);
			} else {
				// header("status: 400 Bad Request");
				$data['status'] = 0;
				$data['code']="already_checked_in";
				return json($data);
			}
		} else {
			// header("status: 400 Bad Request");
			$data['status'] = -1;
			$data['code'] = "已签到";
			return json($data);
		}
		// header("status: 400 Bad Request");
		$data['status']=0;
		$data['code']="already_checked_in";
		return json($data);
	}


	// 添加获得书币日志
	public function addCoinLog($uid,$score,$coin,$from,$remark){
        $data = ['userid' => $uid,
            'score' => $score,
            'coin' => $coin,
            'from' => $from,
            'remark' => $remark,
            'createtime' => time(),
        ];
        return DB::table('ien_addcoin_log')->insert($data);
    }
	/**
	 * 添加打赏记录
	 */
	public function tips(){
		session_start();
		$num = input('num');
		$novel_id = input('bid');


//		$gift_id = input('item');
//		$score = $this->gift[$gift_id]['money'];
//		if(empty($num) || strpos($num, '.') !== false || $num == 0) {
//			$payload=['status'=> 0, 'message'=> "数量必须大于0,且不能是小数", 'code'=> 9999];
//			return $payload;
//		}
//		$total = $num * $score;


		$book=DB::table('ien_book')->where('id',$novel_id)->find();
		if (!empty($book)) {
			$user = DB::table('ien_admin_user')->where('openid', $_SESSION['wechat_user']['original']['openid'])->find();
			if (($user['score'] + $user['coin']) >= $num) {
				// 优先使用赠送书币
				if ($user['score'] >= $num) {
					$data['money'] = $num;
					DB::table('ien_admin_user')->where('id', $user['id'])->setDec('score',$num);
				} else {
					if($user['score'] > 0){
						$data['coin'] = $num - $user['score'];
						$data['money'] = $user['score'];
						DB::table('ien_admin_user')->where('id', $user['id'])->setDec('coin', $data['coin']);
						DB::table('ien_admin_user')->where('id', $user['id'])->setDec('score', $data['money']);
					} else {
						$data['coin'] = $num;
						DB::table('ien_admin_user')->where('id', $user['id'])->setDec('coin', $num);
					}
				}
				DB::table('ien_book')->where('id', $novel_id)->setInc('tips',$num);
				// $data['cid']=$novel_id;
				$data['bid'] = $book['id'];
				$data['uid'] = $_SESSION['wechat_user']['original']['openid'];
				$data['num'] = $num;
				$data['createtime'] = time();
				DB::table('ien_reward_log')->insert($data);

				// 一次性消费1000书币以上，增加1张月票，上不封顶
//				if ($num >= 1000) {
//					DB::table('ien_admin_user')->where('id', $user['id'])->setInc('ticket', 1);
//				}
//				$res = $this->rand_comment(1, $this->gift[$gift_id]['name'], $novel_id, $user, $num);


				$payload = ['status' => 1, 'message' => '打赏成功', 'total' => $num,];
				return $payload;
			}else{
				$payload=['status'=> 0, 'message'=> "余额不足", 'code'=> 9999, 'total' => 0];
				return $payload;
			}
		}
		return ['status' => 0, 'message' => '参数错误', 'total' => 0];
	}

	//打赏页
	public function reward(){
		session_start();
		$user = DB::table('ien_admin_user')->where('openid', $_SESSION['wechat_user']['original']['openid'])->find();
		$bid = input('bid');
		$item = input('item');
		$this->assign('item', $item);
		$this->assign('gif', $this->gift[$item]);
		$this->assign('user', $user);
		$this->assign('bid', $bid);
		return $this->fetch();
	}

	/**
	 * 小说简介
	 */
	public function desc(){
		session_start();

		// 添加点赞记录
        if ($this->request->isAjax()){
            $bid = intval($_POST['bid']);
            $zan = intval($_POST['zan']);
            $zan =  $zan+1;
            $data['view']			= $zan;
            $data['update_time'] 	= time();
            Db::table('ien_book')->where("id = $bid")->update($data);
            $map['uid']     = $_SESSION['wechat_user']['original']['openid'];
            $map['cid']     = $bid;
            $map['type']    = 0;
            $map['status']  = 1;
            $map['create_time'] = time();
            $map['updata_time'] = $map['create_time'];
            Db::table('ien_likenum')->insert($map);
            $a = ['zan'=> $zan];
            return json($a);
        }


		$id = input('id');
		$user = DB::table('ien_admin_user')->where('openid', $_SESSION['wechat_user']['original']['openid'])->find();
		$bid= empty(input('bid')) ? DB::table('ien_chapter')->where('id', $id)->value('bid') : input('bid');

		$map['bid'] = $bid;
		$map['uid'] = $user['openid'];

		$is_collect = DB::table('ien_book_collect')->where($map)->count();
		$chapterLast = DB::table('ien_chapter')->where('bid', $bid)->order('idx desc')->limit(0,1)->count();

		$readCfg = DB::table('ien_read_log')->where($map)->order('update_time desc')->find();

		if ($readCfg) {
			$readCid = $readCfg['zid'];
		} else {
			$readCid = DB::table('ien_chapter')->where('bid', $bid)->order('idx asc')->value('id');
		}

		// 查询有无点赞记录
        $is_like  = Db::table('ien_likenum')->where("cid =$bid")->where('uid',$_SESSION['wechat_user']['original']['openid'])->count();

        // 本月打赏

        $month = Db::query("select sum(money+coin) as sum from ien_reward_log where date_format(FROM_UNIXTIME(`createtime`),'%Y-%m')=date_format(now(),'%Y-%m') and bid = $bid");
if (empty($month[0]['sum'])){
    $month[0]['sum'] = 0;
}


        $openid =  $_SESSION['wechat_user']['original']['openid'];
        // 当天可打赏
        $re = Db::query("select SUM(num) as sum  from ien_reward_log where to_days(FROM_UNIXTIME(`createtime`)) = to_days(now()) and bid = $bid and uid ='$openid' ");

        $num = 8 - intval($re[0]['sum'] ) ;


		// $tstype = explode("\r\n", DB::table('ien_cms_field')->where('id', 49)->value('options'));
		$tstypeArr = Db::query("select tstype,name from ien_book_sort");
        $temp_key = array_column($tstypeArr,'tstype');
        $temp_value = array_column($tstypeArr,'name');
        $tstype = array_combine($temp_key, $temp_value);

        $subsql = DB::field('count(distinct uid) as u_total,count(*) c_total,bid')->table('ien_comment')->group('bid')->where('status=1')->buildSql();
        // dump($subsql);
        // die();
		$book = Db::table('ien_book book', 'book.*,temp.c_total,temp.u_total')
		->join([$subsql => 'temp'], 'book.id=temp.bid', 'left')
		->where('book.id', $bid)
		->find();
        $book['molds'] = Db::table('ien_cartoon_sort')->field('name,tstype')->wherein('tstype',$book['tstype'])->select();


		$book['lastupdate'] = date('m-d H:i', $book['update_time']);
		$book['avtar'] = empty($book['image']) ? $book['cover'] : get_thumb($book['image']);
		$book['taglist'] = empty($book['tag']) ? '' : explode(',',$book['tag']);
		$book['c_total'] = empty($book['c_total']) ? 0 : $book['c_total'];
		$book['u_total'] = empty($book['u_total']) ? 0 : $book['u_total'];
        $share = [
            'title' => '快看，我发现了一本好书——《'.$book['title'].'》',
            'desc' => str_replace(["\n", "\r", "\n\r"], ['', '', ''], strip_tags($book['desc'])),
            'img' => $book['images'] != 0 ? $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . get_thumb($book['images']) : "/public/static/cms/img/default_across.jpg"
        ];
        $this->assign('share', $share);

		$this->assign('is_collect', $is_collect);
		$this->assign('user', $user);
		$this->assign('tstype', $tstype);
		$this->assign('chapterLast', $chapterLast);
		$this->assign('readCid', $readCid);
		$this->assign('book', $book);
		$this->assign('readCfg', $readCfg);

		$this->assign('num', $num);
		$this->assign('month', $month);
        $this->assign('is_like', $is_like);


		return $this->fetch();
	}

	public function getDayCostTicket($uid = null,$type = null){
		if ($uid != null && $type != null) {
			$res = 0;
			if ($type == 1) {
				$res = DB::table('ien_tuijian_log')->where('uid', $uid)->whereTime('createtime', 'today')->sum('tuijian');
			} else {
				$res = DB::table('ien_ticket_log')->where('uid', $uid)->whereTime('createtime', 'month')->sum('ticket');
			}
			return $res > 0 ? $res : 0;
		}
		return 0;
	}
	/**
	 * 投票、推荐操作
	 * @return [type] [description]
	 */
	public function operate(){
		session_start();
		$res = array();
		$data = array();
		$type = input('type');
		$num = input('num');
		$bid = input('bid');
		$collect = input('collect');
		$ticket_name = array('月票', '推荐票');
		if(empty($bid) || empty($num)){
			$res['code'] = -1;
			$res['msg'] = "参数错误";
			return $res;
		}
		$user = DB::table('ien_admin_user')->where('openid', $_SESSION['wechat_user']['original']['openid'])->find();
		if(empty($user)){
			$res['code'] = -2;
			$res['msg'] = "用户没有登录";
			return $res;
		}
		$data['uid'] = $user['openid'];
		$data['userid'] = $user['id'];
		$data['bid'] = $bid;
		$data['createtime'] = time();
		if($collect == 'collect'){
			$is_exist = DB::table('ien_book_collect')->where(array('uid' => $user['openid'], 'bid' => $bid))->find();
			//已经收藏过了
			if(!empty($is_exist)){
				$res['code'] = 1;
				$res['msg'] = "已经收藏过了";
				return $res;
			}
			$res['id'] = DB::table('ien_book_collect')->insertGetId($data);
			$res['code'] = 2;
			$res['msg'] = "收藏成功";

			return $res;
		}
		$table_arr = array('ien_ticket_log', 'ien_tuijian_log');
		$field_arr = array('ticket', 'tuijian');
		if(empty($table_arr[$type])){
			$res['code'] = -3;
			$res['msg'] = "参数错误，类型不存在";
			return $res;
		}
		//检查用户持有数量是否足够
		//抱歉，您今天的推荐票已用完，明天再来吧
		if($user[$field_arr[$type]] < $num){
			$res['code'] = 3;
			if($type == 1){
				$res['msg'] = "推荐票已用完";
			} else {
				$res['msg'] = "抱歉，票数不够";
			}
			return $res;
		}
		$data[$field_arr[$type]] = $num;
		$re = DB::table('ien_book')->where('id', $bid)->setInc($field_arr[$type], $num);
		if($re){

			DB::table($table_arr[$type])->insert($data);
			//投票成功减少用户对应的票类数量
			DB::table('ien_admin_user')->where('id', $user['id'])->setDec($field_arr[$type], $num);
			if($type == 1){
				// 投票赠送书币
				$sendscore = 2;
			} else {
				// 投票赠送书币
				$sendscore = 5;
			}
			DB::table('ien_admin_user')->where('id', $user['id'])->setInc('score', $num * $sendscore);
			$res['msg'] = "投票成功！获得". $num * $sendscore ."书币";

			$residue = DB::table('ien_admin_user')->where('openid', $user['openid'])->value($field_arr[$type]);
			$cur_num = DB::table('ien_book')->where('id', $bid)->value($field_arr[$type]);
			$res['code'] = 4;
			$res['cur_num'] = $cur_num;
			$res['residue'] = $residue;
			$res['comment'] = $this->rand_comment(0, $ticket_name[$type], $bid, $user, $num);
			return $res;
		}
	}

	/**
	 * 添加评论
	 * @param  string  $comment 评论内容
	 * @param  integer $bid     小说id
	 * @return [type]
	 */
	public function comment($comment = '', $bid = 0, $operate = null){
		if(empty($_SESSION['wechat_user']['original']['openid'])){
			return array('status' => 0, 'message' => '登录后才能评论');
		}
		$uid = DB::table('ien_admin_user')->where('openid', $_SESSION['wechat_user']['original']['openid'])->value('id');
		if($operate && !empty($uid)){
			$id = input('id');
			$zanList = DB::table('ien_comment')->where('id', $id)->value('zan_list');
			$zanList = unserialize($zanList);
			if(!empty($zanList) && in_array($uid, $zanList)){
				return array('status' => 0, 'message' => '已赞');
			}
			$zanList[] = "$uid";
			$zanList = serialize($zanList);
			DB::table('ien_comment')->where('id', $id)->setInc('zan');
			$res = DB::table('ien_comment')->where('id', $id)->update(['zan_list' => $zanList]);
			return array('status' => 1);
		}


		if(empty($comment)){
			return array('status' => 0, 'message' => '内容不能为空');
		}

		if(mb_strlen($comment) > 200){
			return array('status' => 0, 'message' => '评论内容不能超过200个字');
		}

		if(empty($uid) || empty($bid)){
			return array('status' => 0, 'message' => '评论失败，参数错误');
		}

		$data = array('uid' => $uid, 'bid' => $bid, 'content' => $comment, 'status' => 1, 'createtime' => time());

		$res = DB::table('ien_comment')->insert($data);

		if(!$res){
			return array('status' => 0, 'message' => '评论失败，请重新尝试');
		}

        $commentList = DB::view('ien_comment')
            ->view('ien_admin_user', 'avatar,nickname', 'ien_comment.uid=ien_admin_user.id')
            ->where(['ien_comment.uid' => $uid, 'ien_comment.content' => $comment])
            ->find();


		$num = Db::table('ien_comment')->where('uid',$uid)->where('bid',$bid)->count();
		if ($num>=2){
            $commentList['people'] =false;
        }
		if (is_numeric($commentList['avatar'])){
		    $commentList['avatar'] = get_file_path($commentList['avatar']);
        }
        $commentList['date'] = date('m-d H:i', $commentList['createtime']);
        $commentList['status']= 1;
        $commentList['message']= '已评论';
		return $commentList;
	}

	public function getComment($bid = 0, $start = 0, $limit = 5){
		if(empty($bid)){
			$this->error('参数错误');
		}
		$commentList = DB::view('ien_comment')
		->view('ien_admin_user', 'avatar,nickname', 'ien_comment.uid=ien_admin_user.id')
		->where(['ien_comment.bid' => $bid, 'ien_comment.status' => 1])
		->limit($start, $limit)
		->order('zan desc,createtime desc')
		->select();

		if($commentList){
			foreach($commentList as &$val){
				if(is_numeric($val['avatar'])){
					$val['avatar'] = get_thumb($val['avatar']);
				}
				$val['zan_list'] = unserialize($val['zan_list']);
				$val['date'] = date('m-d H:i', $val['createtime']);
			}
			unset($val);

            return $commentList;

        }

	}

	public function rand_comment($type = false, $name = '', $bid = 0, $user = array(), $num = 1){
		if($type === false || $name == '' || empty($bid) || empty($user)){
			return false;
		}
		$comment_list = array(
			['小说非常精彩，作者大大辛苦了，{num}张{name}立马奉上！', '这本书太棒了，{num}张{name}鼓励一下，希望后续更加精彩~', '小说情节越来越精彩，{num}张{name}略表心意，作者大大继续加油！'],
			['情节跌宕起伏，精彩绝伦，打赏作者{name}{num}件！', '难得一见的好书，{name}{num}件立马奉上，作者大大继续加油~', '发现一本好小说，{name}{num}件以作鼓励，请大大笑纳！']
		);
		$rand = mt_rand(0, count($comment_list[$type]) - 1);
		$comment = str_replace(array('{num}', '{name}'), array($num, $name), $comment_list[$type][$rand]);
		$res = $this->comment($comment, $bid);
		if($res['status'] == 1){
			return true;
		}
		return false;
	}

}