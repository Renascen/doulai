<?php
/**
 * 接口
 */
namespace app\cms\home;
use think\Controller;
use think\Db;
class Api extends Controller
{
	/**
	 * 统计书籍pv
	 */
	public function countpv()
	{
		//ien_book_pv表里存储的ien_agent_pv表最大id
		$max_pv_id = Db::table('ien_book_pv')->value('max(max_pv_id)');
		//新的ien_agent_pv最大id
		$new_max_pv_id = DB::table('ien_agent_pv')->order('id desc')->value('id');
		//新旧最大ien_agent_pv相同，不更新和插入
		if($max_pv_id >= $new_max_pv_id){
			exit;
		}
		//当前ien_book_pv表的所有数据
		$temp_pv_data = DB::table('ien_book_pv')->field('bid')->select();
		$cur_pv_data = array();
		foreach($temp_pv_data as $val){
			$cur_pv_data[] = $val['bid'];
		}
		$condition = empty($max_pv_id) ? '' : 'where id > ' . $max_pv_id;
		//获取最新的数据
		$countpv = Db::query('SELECT book.id as bid,ifnull(pvt.pv,0) as pv FROM `ien_book` `book` INNER JOIN ( SELECT count(*) as pv,`bookid` FROM `ien_agent_pv` ' . $condition . ' GROUP BY bookid ) `pvt` ON `book`.`id`=`pvt`.`bookid` ORDER BY pvt.pv desc');
		//条件为空，证明是第一次插入数据
		if(empty($condition)){
		    DB::execute('truncate table ien_book_pv');
		    DB::table('ien_book_pv')->insertAll($countpv);
		    DB::table('ien_book_pv')->where('id', Db::table('ien_book_pv')->value('max(id)'))->update(['max_pv_id' => $new_max_pv_id]);
		    exit;
		}

		foreach($countpv as $val){
			//小说id已存在ien_book_pv表里则更新
			if(in_array($val['bid'], $cur_pv_data)){
				DB::table('ien_book_pv')->where('bid', $val['bid'])->setInc('pv', $val['pv']);
				DB::table('ien_book_pv')->where('bid', $val['bid'])->update(['max_pv_id' => $new_max_pv_id]);
			}else{
				$val['max_pv_id'] = $new_max_pv_id;
				DB::table('ien_book_pv')->insert($val);
			}
		}
	}

	public function getBanner($md5 = '')
	{
		$validate = md5($this->key);
		// if($md5 != $validate){
		// 	return json_encode(['code' => -2, 'list' => array()]);
		// }
		$map = array(
			'status' => 1,
			// 'is_delete' => 0
		);
		$banner = Db::name('cms_slider')->field('bid,url,cover,linktype')->where($map)->select();
		if(empty($banner)){
			return json_encode(['code' => 1, 'list' => array()]);
		}

		foreach ($banner as $key => &$value) {
			$cover = module_config('agent.agent_rooturl') . '/' . get_thumb($value['cover']);
			if(empty($value['linktype'])){
				if(strpos($value['url'], '/detail/id/') !== false){
					$value['type'] = 3;
					$postion = strpos($value['url'], '/bid/');
				}elseif(strpos($value['url'], '/pay/index') !== false){
					$value['type'] = 2;
					$postion = strpos($value['url'], '/bid/');
				}elseif(strpos($value['url'], '/document/desc/bid/') !== false){
					$value['type'] = 1;
					$postion = strpos($value['url'], '/bid/');
					$bidStr = substr($value['url'], $postion + 5);
					$bid = substr($bidStr, 0, strpos($bidStr, '/'));
					if(strpos($bid, '.html') !== false){
						$bid = substr($bid, 0, strpos($bid, '.html'));
					}
					dump($bid);
				}else{
					unset($value);
				}
			}else{
				$value['type'] = 1;
			}
		}
		dump($banner);
	}
}