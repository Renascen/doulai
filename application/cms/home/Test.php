<?php
namespace app\cms\home;
use util\Tree;
use think\Db;
use app\agent\admin\book;

class Test extends Common
{
	private $key = 'ed9dc1af9485f1a044b366010429ab31';
	private $pid = '88';
	private $sn = '';
	private $url = '';
	public function __construct(){
		parent::__construct();
		$this->sn = md5('pid=' . $this->pid . '&key=' . $this->key);
		$this->url = 'http://Inf.9kus.com/communalData/{type}/returnType/JSON/pid/' . $this->pid . '/sn/' . $this->sn;
	}
	public function getBookList(){
		$time = time();
		$bookList = $this->ihttp_request(str_replace('{type}', 'bookList', $this->url));
		$bookInfo = array();
		$onTable = DB::query('select sn from ien_book where sn>0');
		$sn = array();
		foreach($onTable as $v){
			$sn[] = $v['sn'];
		}
		foreach($bookList['data'] as $key => $book){
			$tstype = DB::table('ien_cms_field')->where('id',49)->find();
			$tstype = explode("\r\n", $tstype['options']);

			if(in_array($book['id'], $sn)){
				continue;
			}
			$bookZishu = 0;
			$bookInfo = $this->getBook($book['id']);
			if(!in_array($bookInfo['data']['category'], $tstype)){
				$data['tstype'] = count($tstype);
				$tstype[] = $bookInfo['data']['category'];
				$update = implode("\r\n", $tstype);
				DB::table('ien_cms_field')->where('id',49)->update(['options' => $update]);
			}else{
				$data['tstype'] = array_search($bookInfo['data']['category'], $tstype);
			}
			if($bookInfo['status'] === 1){	
				$data = array(
					'sn' => $book['id'],
					'title' => $bookInfo['data']['title'],
					'zuozhe' => $bookInfo['data']['author'],
					'cover' => $bookInfo['data']['cover'],
					'desc' => $bookInfo['data']['summary'],
					'zishu' => 0,
					'zhishu' => 0,
					'tj' => '',
					'xstype' => $bookInfo['data']['isFull'],
					'create_time' => time(),
					'update_time' => $book['updatetime'],
					'tag' => $bookInfo['data']['tag'],
					'isvip' => $bookInfo['data']['isVip'],
					'role' => $bookInfo['data']['role']
				);
				$insertid = DB::table('ien_book')->insertGetId($data);
				if(!$insertid > 0){
					continue;
				}
				$chapterData = array();
				$chapterList = $this->getChapterList($book['id']);
				if($chapterList['status'] === 1){
					foreach($chapterList['data'] as $val){
						$bookZishu += $val['chapterLength'];
						$content = $this->getChapter($book['id'], $val['id']);
						$chapterData[] = array(
							'sn' => $val['id'],
							'cid' => 5,
							'uid' => 1,
							'model' => 7,
							'title' => $val['title'],
							'create_time' => time(),
							'update_time' => $val['updatetime'],
							'status' => 1,
							'content' => $content['data'],
							'isvip' => 1,
							'bid' => $insertid,
							'idx' => $val['chapterOrder'],
							'zishu' => $val['chapterLength'],
							'volume' => $val['volume'],
							'volumeOrder' => $val['volumeOrder'],
							'gisvip' => $val['isVip'],
							'chapterOrder' => $val['chapterOrder']
						);
					}

					// $this->inserterAll($chapterData);
					// exit;
				}
				DB::table('ien_chapter')->insertAll($chapterData);
			}
			DB::table('ien_book')->where('id', $insertid)->update(['zishu' => $bookZishu]);
			$sn[] = $book['id'];
			echo $insertid;
			break;
		}
	}

	public function getBookListAll(){
		$bookList = $this->ihttp_request(str_replace('{type}', 'bookList', $this->url));
		dump($bookList);
	}

	private function inserterAll($data){
		// $con = mysqli_connect('10.66.168.171', 'novel', 'novel', 'novel_huotun_com');
		// $sql= 'insert into ien_chapter (`sn`, `cid`, `uid`, `model`, `title`, `create_time`, `update_time`, `status`, `content`, `isvip`, `bid`, `idx`, `zishu`, `volume`, `volumeOrder`, `gisvip`, `chapterOrder`) values ';
		// foreach($data as $val){
		// 	$sql .= "('" . implode("','", $val) . "') ";
		// }
		// $res = mysqli_query($con, $sql);
		// dump(mysqli_error($con));
		// return $res;
	}

	public function getBookAll($bid,$sortid = 1, $updatetime = null){
		if($updatetime == null){
			$updatetime = time();
		}
		$bookInfo = $this->getBook($bid);
		// dump($bookInfo);
		if($bookInfo['status'] === 1){
			$data = array(
				'sn' 			=> $bid,
				'title' 		=> $bookInfo['data']['title'],
				'zuozhe' 		=> $bookInfo['data']['author'],
				'cover' 		=> $bookInfo['data']['cover'],
				'desc' 			=> $bookInfo['data']['summary'],
				'zishu' 		=> 0,
				'zhishu' 		=> 0,
				'tj' 			=> '',
				'xstype' 		=> $bookInfo['data']['isFull'],
				'create_time' 	=> time(),
				'update_time' 	=> $updatetime,
				'tag' 			=> $bookInfo['data']['tag'],
				'isvip' 		=> $bookInfo['data']['isVip'],
				'role' 			=> $bookInfo['data']['role']
			);
			$mybookCfg = DB::table('ien_book')->where('sn',$bid)->find();
			if(empty($mybookCfg)){
				$insertid = DB::table('ien_book')->insertGetId($data);
			} else {
				$insertid = $mybookCfg['id'];
			}
			$chapterList = $this->getChapterList($bid);
			// dump($chapterList);
			$chapterData = array();
			$mychapterListCfg = DB::view('ien_chapter','sn')->where('bid',$mybookCfg['id'])->select();
			if($chapterList['status'] === 1){
				$index = $sortid;
				$i = 1;
				foreach($chapterList['data'] as $val){
					if($i >= $index){
						$index++;
						$bookZishu += $val['chapterLength'];
						$isfind = 0;
						foreach ($mychapterListCfg as $value) {
							if($value['sn'] === $val['id']){
								$isfind = 1;
								break;
							}
						}
						if($isfind == 0){
							$content = $this->getChapter($bid, $val['id']);
							$content['data'] = '<P>'. str_replace(array("\r\n", "\r", "\n"), "</p></p>", $content['data']) . '</P>';
							$chapterData[] = array(
								'sn' => $val['id'],
								'cid' => 5,
								'uid' => 1,
								'model' => 7,
								'title' => $val['title'],
								'create_time' => time(),
								'update_time' => $val['updatetime'],
								'status' => 1,
								'content' => $content['data'],
								'isvip' => 1,
								'bid' => $insertid,
								'idx' => $val['chapterOrder'],
								'zishu' => $val['chapterLength'],
								'volume' => $val['volume'],
								'volumeOrder' => $val['volumeOrder'],
								'gisvip' => $val['isVip'],
								'chapterOrder' => $val['chapterOrder']
							);
						}
						if($index >= ($sortid + 500)){
							break;
						}
					}
					$i++;
				}
			}
		}
		dump($insertid);
		dump($index);
		if(count($chapterData)>0){
			// dump($chapterData);
			DB::table('ien_chapter')->insertAll($chapterData);
			DB::table('ien_book')->where('id', $insertid)->setInc('zishu', $bookZishu);
		}
		// dump(($index-$sortid) % 100);
		// dump($sortid != $index && ($index-$sortid) % 100 == 0);
		if($sortid != $index && ($index-$sortid) % 100 == 0){
			header("Location:http://novel.huotun.com/index.php/cms/Test/getBookAll/bid/". $bid ."/sortid/".$index);
		}
	}

	public function getBook($bid){
		$url = str_replace('{type}', 'bookInfo', $this->url) . '/bookId/' . $bid;
		$bookInfo = $this->ihttp_request($url);
		// dump($bookInfo);
		return $bookInfo;
	}

	public function getChapterList($bid){
		// $bid = '19679';
		$url = str_replace('{type}', 'chapters', $this->url) . '/bookId/' . $bid;
		$res = $this->ihttp_request($url);
		// dump($res);
		return $res;
	}
	public function testchapter($bid){
		$url = str_replace('{type}', 'chapters', $this->url) . '/bookId/' . $bid;
		$res = $this->ihttp_request($url);
		dump($res);
	}
	public function getChapter($bid, $cid){
		$url = str_replace('{type}', 'content', $this->url) . '/bookId/' . $bid . '/id/' . $cid;
		$res = $this->ihttp_request($url);
		// dump($res);
		return $res;
	}

	public function getCategory(){
		$url = str_replace('{type}', 'typeList', $this->url);
		$res = $this->ihttp_request($url);
		// dump($res);
		return $res;
	}
	
	
}