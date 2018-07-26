<?php
namespace Admin\Controller;
use Think\Controller;

class EmptyController extends Controller{
    public function index(){
        redirect("/index.php/Admin/Index/login");
    }

    public function _empty(){
        redirect("/index.php/Admin/Index/login");
    }
}