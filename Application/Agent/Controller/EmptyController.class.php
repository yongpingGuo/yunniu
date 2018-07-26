<?php
namespace Agent\Controller;
use Think\Controller;

class EmptyController extends Controller{
    public function index(){
        redirect("/index.php/Agent/Index/login");
    }

    public function _empty(){
        redirect("/index.php/Agent/Index/login");
    }
}