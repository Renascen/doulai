<?php
/**
 * User: pinbo
 * Date: 2017/4/8
 * Time: 上午9:50
 */
//后台访问控制器
namespace app\agent\admin;//修改ann为模块名

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\agent\model\Novel as NovelModel;
use think\Db;

class Novel extends Admin{

    public function index(){
		$map = $this->getMap();
        $data_list = DB::table('ien_book')->where($map)->order('view desc')->paginate();
        $typeArr = Db::query("select tstype,name from ien_cartoon_sort where status=1");

        $columnArr = Db::query("select id,name from ien_cms_column where model = 11");

        // 获取类别列表
        $column_key = array_column($columnArr,'id');
        $column_value = array_column($columnArr,'name');
        $column = array_combine($column_key, $column_value);

        // 获取类型列表

        $temp_key = array_column($typeArr,'tstype');
        $temp_value = array_column($typeArr,'name');
        $types = array_combine($temp_key, $temp_value);
        // 自定义按钮
            $btn = [
                'class' => 'btn btn-xs btn-default',
                'icon'  => 'fa fa-fw fa-folder-open-o',
                'title' => '查看',
                'href'  => url('Chapter', ['id' => '__id__'])
            ];
		$css = <<<EOF
           <style>
                .column-desc{width:400px;}
           </style>
EOF;

		return ZBuilder::make('table')
			->setPageTips('【注意】请在右侧点击查看，生成推广链接')
			->setSearch(['title' => '名称', 'desc' => '描述'])
			->hideCheckbox()
            ->addColumns([ // 批量添加数据列

			   ['image', '封面', 'picture'],

               ['title', '名称','link',url('Chapter', ['id' => '__id__'])],
			   
			   ['cid', '类别','','',$column],
			   
			   ['desc', '描述'],
			   
//			   ['tstype', '类型', '','', parse_attr(module_config('agent.agent_novel_type'))],
			   ['mold', '类型', '','', $types],

			   ['status', '状态', '', '',parse_attr(module_config('agent.agent_book_is'))],
			   
			   ['view', '点击量'],
				
				['right_button', '查看章节', 'btn']

            ])
			->setTableName('book')
			->addFilter('cid',$column) // 添加筛选
			->addFilter('mold',$types) // 添加筛选
			->addFilter('status',parse_attr(module_config('agent.agent_book_is'))) // 添加筛选
			->addOrder('view')
			->setExtraCss($css)
			->addRightButton('custom',$btn)
            ->setRowList($data_list) // 设置表格数据
            ->fetch(); // 渲染模板

    }

    // 查看章节列表
	public function Chapter($id = null)
	{
		if ($id === 0) $this->error('参数错误');
		// 自定义按钮
            $btnwa = [
                'class' => 'btn btn-xs btn-default',
                'icon'  => 'fa fa-fw fa-gears',
                'title' => '生成推广文案，下一章链接',
                'href'  => url('agent/wxcreate', ['id' => '__id__']),
				'target' => '_blank'
            ];
		// 自定义按钮
            $btnlj = [
                'class' => 'btn btn-xs btn-default',
                'icon'  => 'fa fa-fw fa-reply',
                'title' => '生成推广链接，当前章链接',
                'href'  => url('agent/linkcreate', ['id' => '__id__'])
            ];

		$map = $this->getMap();
		$map['bid']=$id;
        $data_list = DB::table('ien_chapter')->where($map)->order('idx asc')->paginate();
		$data_listxs = DB::table('ien_book')->where('id',$id)->find();


		return ZBuilder::make('table')
			->setPageTips('【注意】请在右侧点击生成推广链接<br>【注意】推广链接是当前章节链接，推广文案生成的是下一张的链接！')
			->hideCheckbox()
			->setPageTitle($data_listxs['title'])
            ->addColumns([ // 批量添加数据列

				['idx','章节ID'],

                ['title', '名称'],

				['right_button', '生成推广链接', 'btn']

            ])
			->setTableName('chapter')
			->addRightButton('custom',$btnwa)
			->addRightButton('custom',$btnlj,true)
            ->setRowList($data_list) // 设置表格数据
            ->fetch('index'); // 渲染模板
			
		}

    // 展示某一章节的内容
	 public function show($id = null){
		if ($id === 0) $this->error('参数错误');
		$table="ien_pictures";
		$result = Db::table($table)->where('id',$id)->field("content,title")->find();
		$res = explode(',',$result['content']);
		$domain = config('__DOMAIN__');
		foreach ($res as $key=>$v){
		    $path[] = Db::table('ien_admin_attachment')->where('id',$v)->field("path")->find();
        }
         echo "<h1>".$result['title']."</h1>";
         foreach ($path as $k=>$val){
            echo '<img style="width:97%" src="'.$domain.'/public/'.$val['path'].'" />';
         }


     }


}