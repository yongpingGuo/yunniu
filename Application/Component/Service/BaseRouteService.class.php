<?php
namespace Component\Service;
class BaseRouteService
{
    //下一流程 array("process_id"=>1,"action"=>"index","filename"=>"index");
    protected $next_process = array();

    //忽视的流程，即状态处于开启也不跳这里面的流程  array($process_id,$process_id,$process_id);
    protected $ignore_process = array();

    protected $page_screen;

    //构造函数，初始化$ignore_process
    public function __construct($ignore_process,$page_screen = 2){
        $this->page_screen = $page_screen;
        $this->ignore_process = $ignore_process;  //初始化忽视的流程
    }

    /**
     * @param $restaurant_id
     * @param $current_process
     * @return array
     */
    public function getNextProcess($restaurant_id,$current_process){
        $processModel = D("process");                           //创建一个流程的model
        $sort = 0;
        if ($current_process) {
            $condition2['process_url'] = $current_process;
            $result = $processModel->where($condition2)->field("process_id,sort")->find();
            if($result){
                $sort = $result["process_id"];
            }else{
                $this->overdue();
                exit;
            }
        }

        $restaurant_process_model = D("restaurant_process");
        $condition['process_id'] = array("not in",$this->ignore_process); //忽视的流程
        $condition["process_status"] = 1;                //流程页状态为开启状态
        $condition["restaurant_id"] = $restaurant_id;

        $restaurant_next_process = $restaurant_process_model->where($condition)->where("process_id > $sort")->order("process_id")->find();

        $next_process_id = $restaurant_next_process["process_id"];

        $condition3["process_id"] = $next_process_id;
        $next_action = $processModel->where($condition3)->field("process_url")->find()['process_url'];

        $restaurant_page_group_model = D("restaurant_page_group");
        $rpg_where['restaurant_id'] = $restaurant_id;
        $rpg_where['page_screen'] = $this->page_screen;
        $rpg_where['status'] = 1;
        $group_id = $restaurant_page_group_model->where($rpg_where)->field('group_id')->find()['group_id'];

        $group_detail_model = D("group_detail");
        $gd_where['group_id'] = $group_id;

        $page_group_info = $group_detail_model->where($gd_where)->find();

        $this->next_process["process_id"] = $next_process_id;
        $this->next_process["action"] = $next_action;
        $this->next_process["filename"] = $page_group_info[$next_action.'_page'];

        return $this->next_process;
    }

    /**
     * @param $ignore_process
     */
    public function setIgnoreProcess($ignore_process){
        $this->ignore_process = $ignore_process;
    }

    /**
     * @return array
     */
    public function getIgnoreProcess(){
        return $this->ignore_process;
    }
}