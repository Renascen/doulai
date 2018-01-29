<?php
/**
 * User: pinbo
 * Date: 2017/4/8
 * Time: 上午10:06
 */
namespace app\ien\admin;
use app\admin\controller\Admin;

class Api extends Admin{
    public function index(){
        return $this->fetch();
    }
}