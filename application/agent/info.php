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

/**
 * 模块信息
 */
return [
  'name' => 'agent',
  'title' => '小说代理商模块',
  'identifier' => 'agent.anline.module',
  'icon' => 'fa fa-fw fa-newspaper-o',
  'description' => '小说代理商模块，推广链接生成，文案编辑，结算。',
  'author' => '拼搏',
  'author_url' => 'http://www.zhizuowangzhan.cn',
  'version' => '1.0.0',
  'need_module' => [
    [
      'admin',
      'admin.ieasynet.module',
      '1.0.0',
    ],
  ],
  'need_plugin' => [],
  'tables' => [],
  'database_prefix' => 'ien_',
  'config' => [
  [
      'switch',
      'agent_short_url',
      '推广链接短连接形式',
      '',
      '',
      '',
    ],

  [
      'text',
      'agent_pay_money',
      '阅读章节消费积分',
      '',
      '21',
      21,
    ],
      [
      'text',
      'agent_guanzhu',
      '强制关注章节',
      '',
      '18',
      18,
    ],
      [
      'text',
      'agent_qzgzewm',
      '强制关注二维码链接',
      '',
      '',
      '',
    ],
     [
      'text',
      'agent_fltime',
      '扣量福利时间(天)',
      '用户到期之后,转移到平台收益',
      '',
      '',
    ],
    [
      'text',
      'agent_klbili',
      '扣量比例',
      '按照比例扣除订单,例1000,那么1000单扣除一单给平台',
      '',
      '',
    ],
    [
      'tags',
      'agent_nokou',
      '扣量排除代理',
      '排除扣量的代理ID,用英文逗号隔开',
      '',
      '',
    ],
    [
      'text',
      'agent_rooturl',
      '根域名',
      '平台的根域名,登陆授权域名,例:novel.huotun.com',
      '',
      '',
    ],
    [
      'text',
      'agent_payurl',
      '支付域名',
      '平台的支付域名,例:pay.huotun.com',
      '',
      '',
    ],
    [
      'text',
      'agent_dailiurl',
      '代理后台域名',
      '平台的代理后台域名,只需要填写二级域名前缀,例:d1.huotun.com',
      '',
      '',
    ],
    [
      'text',
      'agent_tuiguangurl',
      '代理推广落地域名',
      '网站平台的代理推广落地域名,只需要填写前缀,例:t1.huotun.com',
      '',
      '',
    ],
    [
      'text',
      'agent_pay_fangshi',
      '支付方式',
      '1为微信支付,2为连连支付',
      '1',
      1,
    ],

    [
      'textarea',
      'agent_pay_type',
      '支付类型',
      '',
      '1:VIP购买
2:普通购买',
      2,
    ],
    [
      'textarea',
      'agent_diy_menu',
      '自定义菜单',
      '',
      '',
      '',
    ],
    [
      'textarea',
      'agent_novel_type',
      '小说类型',
      '',
      '0:都市言情
1:历史架空
2:总裁豪门
3:官场红文
4:恐怖悬疑
5:青春校园
6:黑道兵王
7:东方玄幻',
      1,
    ],
    [
      'textarea',
      'agent_pay_is',
      'status',
      '',
      '0:未支付
1:已支付',
      0,
    ],
    [
      'textarea',
      'agent_book_is',
      'status',
      '',
      '0:已下架
1:已上架',
      0,
    ],
  ],
  'action' => [
    [
      'module' => 'agent',
      'name' => 'slider_delete',
      'title' => '删除滚动图片',
      'remark' => '删除滚动图片',
      'rule' => '',
      'log' => '[user|get_nickname] 删除了滚动图片：[details]',
      'status' => 1,
    ],
  ],
  'access' => [
    'group' => [
      'tab_title' => '栏目授权',
      'table_name' => 'ien_column',
      'primary_key' => 'id',
      'parent_id' => 'pid',
      'node_name' => 'name',
    ],
  ],
];
