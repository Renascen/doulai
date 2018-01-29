<?php

namespace app\common\taglib;
use think\Db;
use think\template\TagLib;

class TagLibFront extends TagLib{
    /**
     * 定义标签列表
     */
    protected $tags   =  [
        // 标签定义： attr 属性列表 close 是否闭合（0 或者1 默认1） alias 标签别名 level 嵌套层次
        'close'     => ['attr' => 'time,format', 'close' => 0], //闭合标签，默认为不闭合
        'open'      => ['attr' => 'name,type', 'close' => 1], 
        'loop'      => ['attr' => 'table,where,order,limit,relation,field,result,page,purl,purlvars', 'close' => 1], 

    ];

    /**
     * 这是一个闭合标签的简单演示
     */
    public function tagClose($tag)
    {
        $format = empty($tag['format']) ? 'Y-m-d H:i' : $tag['format'];
        $time = empty($tag['time']) ? time() : $tag['time'];
        $parse = '<?php ';
        $parse .= 'echo date("' . $format . '",' . $time . ');';
        $parse .= ' ?>';
        return $parse;
    }

    /**
     * 这是一个非闭合标签的简单演示
     */
    public function tagOpen($tag, $content)
    {
        $type = empty($tag['type']) ? 0 : 1; // 这个type目的是为了区分类型，一般来源是数据库
        $name = $tag['name']; // name是必填项，这里不做判断了
        $parse = '<?php ';
        $parse .= '$test_arr=[[1,3,5,7,9],[2,4,6,8,10]];'; // 这里是模拟数据
        $parse .= '$__LIST__ = $test_arr[' . $type . '];';
  
        $parse .= ' ?>';
        $parse .= '{volist name="__LIST__" id="' . $name . '"}';
        $parse .= $content;
        $parse .= '{/volist}';
        return $parse;
    }
    public function tagLoop($tag, $content) {
       // $tag = $this->parseXmlAttr($attr, 'list');
    
        $result = !empty($tag['result']) ? $tag['result'] : 'data';
        if (!empty($tag['table'])) {
            $model = '\think\Db::("' . $tag['table'] . '")';
        } else {
            return '';
        }
        $key = !empty($tag['key']) ? $tag['key'] : 'i';
        $mod = isset($tag['mod']) ? $tag['mod'] : '2';
        //拼接SQL查询语句
        $parseStr = '<?php ';
        if($tag['where']!= null && $tag['where']=='$where') {
            $parseStr .=$tag['where'] != null ? '$map='.$tag["where"].';' : '';
        }else {
            $parseStr .=$tag['where'] != null ? '$map=array(' . trim(str_replace("=", "=>", $tag["where"]),',') . ');' : '';
        }
       
        if ($tag["page"]) {
            $parseStr .= '$' . $result . '_count=' . $model;
            $parseStr .= $tag['where'] != null ? '->where($map)' : '';
            $parseStr .='->cache(true)->count(' . $model . '->getPk());';
            $parseStr .= 'import("ORG.Util.Page"); $' . $result . '_p = new Page($' . $result . '_count, ' . $tag["page"] . ');';
            if($tag['purlvars']!=null) {
                $parseStr .= '$' . $result . '_page = $' . $result . '_p->show("'.$tag['purl'].'",'.$tag['purlvars'].');';
            }else {
                $parseStr .= '$' . $result . '_page = $' . $result . '_p->show("'.$tag['purl'].'");';
            }
        }
        $parseStr .= '$' . $result . '_result = ' . $model ;
        $parseStr .= $tag['relation'] != null ? '->relation(array(' . trim($tag["relation"],',') . '))' : '';
        $parseStr .= $tag['where'] != null ? '->where($map)' : '';
        $parseStr .= $tag['field'] != null ? '->field("'.trim($tag["field"],',').'")' : '';
        $parseStr .= $tag['order'] != null ? '->order("'.trim($tag["order"],',').'")' : '';
        if ($tag["page"]) {
            $parseStr .= $tag['page'] != null ? '->limit("$' . $result . '_p->firstRow , $' . $result . '_p->listRows")' : '';
        }else {
            $parseStr .= $tag['limit'] != null ? '->limit("'.trim($tag["limit"],',').'")' : '';
        }
        $parseStr .= '->select();?>';
        $parseStr .= '<?php if($' . $result . '_result){ $' . $key . '=0;';
        $parseStr .= 'foreach($' . $result . '_result as $key=>$' . $result . '){ ?>';
        $parseStr .= '<?php ++$' . $key . ';$mod = ($' . $key . ' % ' . $mod . ');?>';
        $parseStr .= $content;
        $parseStr .= '<?php }};?>';
        return $parseStr;
    }


}