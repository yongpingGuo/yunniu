<?php
namespace AllAgent\Controller;
use Think\Controller;

class EmptyController extends Controller{
    public function index(){
        redirect("/index.php/AllAgent/Index/login");
    }

    public function _empty(){
        redirect("/index.php/AllAgent/Index/login");
    }
}