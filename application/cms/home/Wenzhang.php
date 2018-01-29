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

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

/**
 * 文档控制器
 * @package app\cms\admin
 */
class Wenzhang extends Common
{
    /**
     * 文档列表
     * @author 拼搏 <378184@qq.com>
     */
    public function index()
    {
		
		 if ($this->request->isPost()) {
            $data = $this->request->post();
			if($data['idx']<=20)
			{
			$data['isvip']=0;
			}
			if (false === DB::table('ien_chapter')->insert($data)) {
                $this->error('创建失败');
            }
            $this->success('创建成功');
			
		}
		
		
//cid,uid,model,title,create_time,update_time,sort,status,content,isvip,bid,idx
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'cid', '', '',5],
                ['text', 'uid', '', '',1],
                ['text', 'model', '', '', 7],
				['text', 'title', '标题', ''],
				['text', 'create_time', '', '',time()],
				['text', 'update_time', '', '',time()],
				['text', 'sort', '', '',100],
				['text', 'status', '', '',1],
				['text', 'content', '内容', ''],
				['text', 'isvip', '', '',1],
				['text', 'bid', '小说ID', '',1],
				['text', 'idx', '章节排序', ''],
				
               
            ])
            ->isAjax(true)
            ->fetch();
       
    }

 
}