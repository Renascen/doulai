<?php
/**
 * User: pinbo
 * Date: 2017/4/8
 * Time: 上午9:41
 */
//模块共用的函数


function ajaxxs(){
	$data_list = DB::table('ien_book')->column('id','title');
	return $data_list;
	}


