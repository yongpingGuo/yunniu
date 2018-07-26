<?php
namespace Admin\Controller;
use data\service\Category;
use data\service\SellOut as ServiceSellOut;


class DishesController extends BaseController{
    public function __construct(){
        parent::__construct();
        $admin_id = session("re_admin_id");
        if(!$admin_id){
            redirect("Index/login");
        }

        $restaurant_manager_model = D('restaurant_manager');
        $restaurant_id = $restaurant_manager_model->where("id = $admin_id")->field("restaurant_id")->find()['restaurant_id'];
        session('restaurant_id',$restaurant_id);
    }

    //excel的数据导出
    public function excel_out2()
    {
        //查询food表关联的数据
        if(!$_SESSION['restaurant_id']){
            echo "店铺id获取失败";
            die();
        }

        $map1['restaurant_id'] = $_SESSION['restaurant_id'];
        $data = M('food')->where($map1)->select();


        foreach($data as $k => $val){
            $where['food_id'] = $val['food_id'];
            $category_relative_ids = M('food_category_relative')->field('food_category_id')->where($where)->select();
            $data["$k"]["category_ids"] = $category_relative_ids;

            $data["$k"]["attribute_type"] =M('attribute_type')->field('type_name,attribute_type_id,print_id,select_type,count_type,tag_print_id')->where($where)->select();

        }

        foreach($data as $key => $v){
            $str = '';
            foreach($v['attribute_type'] as $k1=>$v1){
                if($v1['print_id'] == null){
                    $v1['print_id'] = 0;
                }
                if($v['select_type'] == null){
                    $v['select_type'] = 0;
                }
                if($v['count_type'] == null){
                    $v['count_type'] = 0;
                }
                if($v['tag_print_id'] == null){
                    $v['tag_print_id'] = 0;
                }


                $data["$key"]["attribute_type_str"] .=  $v1['type_name'].',';//逗号拼接type_name
                $data["$key"]['print_id_str'] .= $v1['print_id'].','; //拼接print_id
                $data["$key"]['select_type_str'] .=$v['select_type'].',';//拼接select_type
                $data["$key"]['count_type_str'] .=$v['count_type'].',';//拼接count_type
                $data["$key"]['tag_print_id_str'] .=$v['tag_print_id'].',';//拼接tag_print_id

                $where['attribute_type_id'] = $v1['attribute_type_id'];
                $data["$key"]["attribute_type"]["$k1"]["food_attribute"] = M('food_attribute')->field('attribute_name,attribute_price')->where($where)->select();
            }

            foreach($v['category_ids'] as $k2=>$v2){
                $food_category[] = M('food_category')->field('food_category_name')->where($v2)->find();
                $img_url[] = M('food_category')->field('img_url')->where($v2)->find();
                $ico_category_type[] = M('food_category')->field('ico_category_type')->where($v2)->find();

//                dump($data["$key"]['category_names']);

            }
            $data["$key"]['category_name'] = $food_category;
            $data["$key"]['img_url'] = $img_url;
            $data["$key"]['ico_category_type'] = $ico_category_type;

            $food_category = null;
            $img_url = null;
            $ico_category_type = null;

        }

        foreach($data as $key => $v){
            $tmp = $v['attribute_type_str'];
            $data["$key"]["attribute_type_str"] = rtrim("$tmp",',');
            $data["$key"]["print_id_str"] = rtrim($v['print_id_str'],',');
            $data["$key"]["select_type_str"] = rtrim($v['select_type_str'],',');
            $data["$key"]["count_type_str"] = rtrim($v['count_type_str'],',');
            $data["$key"]["tag_print_id_str"] = rtrim($v['tag_print_id_str'],',');

            $sum = '';
            foreach($v['attribute_type'] as $k1=>$v1){
                $count = count($v1['food_attribute']);
                for($i = 0; $i < $count; $i++){
                    $sum .= $v1['food_attribute'][$i]['attribute_name'].'_'.$v1['food_attribute'][$i]['attribute_price'].',';
                }
                $sum = rtrim($sum,',');
                $data["$key"]["attribute_val"] .= $sum.'|';
                $sum = '';
            }

            //遍历菜品分类名
            $sum2 = '';
            foreach($v['category_name'] as $k2=>$v2){
                $count2 = count($v2);
                for($j = 0;$j<$count2;$j++){
                    $sum2 .= $v2['food_category_name'].'|';
                }
                $data["$key"]["category_names"] = rtrim($sum2,'|');
                $sum2 = '';
            }

            //遍历菜品图标url
            $sum3 = '';
            foreach($v['img_url'] as $k3=>$v3){
                $count3 = count($v3);
                for($j = 0;$j<$count3;$j++){
                    $sum3 .= $v3['img_url'].'|';
                }
                $data["$key"]["img_urls"] = rtrim($sum3,'|');
                $sum3 = '';
            }

            //遍历菜品图标url
            $sum3 = '';
            foreach($v['img_url'] as $k3=>$v3){
                $count3 = count($v3);
                for($j = 0;$j<$count3;$j++){
                    $sum3 .= $v3['img_url'].'|';
                }
                $data["$key"]["img_urls"] = rtrim($sum3,'|');
                $sum3 = '';
            }

            //遍历菜品分类图标类型
            $sum4 = '';
            foreach($v['ico_category_type'] as $k4=>$v4){
                $count4 = count($v4);
                for($j = 0;$j<$count4;$j++){
                    $sum4 .= $v4['ico_category_type'].'|';
                }
                $data["$key"]["ico_category_types"] = rtrim($sum4,'|');
                $sum4 = '';
            }

        }

        //处理'|'
        foreach($data as $key1 => $v1){
            $data["$key1"]["attribute_val"] = rtrim($v1['attribute_val'],'|');
        }

//        dump($data);
//        die();
        //写入Excel
        import("Org.Util.PHPExcel");
        $PHPExcel = new \PHPExcel();

        //实例化后创建了第一个sheet
        $objSheet = $PHPExcel->getActiveSheet();//获取当前活动sheet
        $objSheet->setTitle('food');//给当前活动sheet设置名称
        $objSheet->setCellValue("A1","food_id")->setCellValue("B1","food_name")->setCellValue("C1","food_name_en")->setCellValue("D1","time_category")->setCellValue("E1","discount")
            ->setCellValue("F1","food_price")->setCellValue("G1","star_level")->setCellValue("H1","hot_level")->setCellValue("I1","is_prom")->setCellValue("J1","is_tax")->setCellValue("K1","foods_num_day")
            ->setCellValue("L1","food_desc")->setCellValue("M1","restaurant_id")->setCellValue("N1","is_sale")->setCellValue("O1","print_id")->setCellValue("P1","sort")->setCellValue("Q1","district_id")
            ->setCellValue("R1","tag_print_id")->setCellValue("S1","dianzan")->setCellValue("T1","category_names")
            ->setCellValue("U1","attribute_type_str")->setCellValue("V1","print_id_str")
            ->setCellValue('W1',"select_type_str")->setCellValue('X1',"count_type_str")
            ->setCellValue('Y1','tag_print_id_str')->setCellValue('Z1',"attribute_val")
            ->setCellValue('AA1','food_img')->setCellValue('AB1','img_urls')->setCellValue('AC1','ico_category_types');//给当前活动sheet填充数据


        $k = 2;
        foreach($data as $key => $val2){
            $objSheet->setCellValue("A".$k,$val2["food_id"])->setCellValue("B".$k,$val2["food_name"])->setCellValue("C".$k,$val2["food_name_en"])->setCellValue("D".$k,$val2["time_category"])->setCellValue("E".$k,$val2["discount"])
                ->setCellValue("F".$k,$val2["food_price"])->setCellValue("G".$k,$val2["star_level"])->setCellValue("H".$k,$val2["hot_level"])->setCellValue("I".$k,$val2["is_prom"])->setCellValue("J".$k,$val2["is_tax"])->setCellValue("K".$k,$val2["foods_num_day"])
                ->setCellValue("L".$k,$val2["food_desc"])->setCellValue("M".$k,$val2["restaurant_id"])->setCellValue("N".$k,$val2["is_sale"])->setCellValue("O".$k,$val2["print_id"])->setCellValue("P".$k,$val2["sort"])->setCellValue("Q".$k,$val2["district_id"])
                ->setCellValue("R".$k,$val2["tag_print_id"])->setCellValue("S".$k,$val2["dianzan"])->setCellValue("T".$k,$val2["category_names"])
                ->setCellValue("U".$k,$val2["attribute_type_str"])->setCellValue("V".$k,$val2["print_id_str"])
                ->setCellValue('W'.$k,$val2["select_type_str"])->setCellValue('X'.$k,$val2["count_type_str"])
                ->setCellValue('Y'.$k,$val2['tag_print_id_str'])->setCellValue('Z'.$k,$val2["attribute_val"])
                ->setCellValue('AA'.$k,$val2['food_img'])->setCellValue('AB'.$k,$val2['img_urls'])
                ->setCellValue('AC'.$k,$val2['ico_category_types']);//给当前活动sheet填充数据
            $k++;
        }

        $xlsxTitle = 'menu';
        $fileName = 'menu';
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsxTitle.'.xlsx"');
        header("Content-Disposition:attachment;filename=$fileName.xlsx");//attachment新窗口打印inline本窗口打印
        import("Org.Util.PHPExcel.IOFactory");
        $objWriter = \PHPExcel_IOFactory::createWriter($PHPExcel, 'Excel2007');
        $objWriter->save('php://output');
    }


    //excel的数据导入到数据库
    public function excel_to_sql($file_name)
    {
        set_time_limit(0);
        import("Org.Util.PHPExcel");
        import("Org.Util.PHPExcel.IOFactory.php");
        import("Org.Util.PHPExcel.Reader.Excel2007.php");

        $filename  =  $file_name;
        $objReader = \PHPExcel_IOFactory::createReader('Excel2007');
        $objPHPExcel = \PHPExcel_IOFactory::load($filename,$encode='utf-8');
        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow(); // 取得总行数
        $highestColumn = $sheet->getHighestColumn(); // 取得总列数

        $res_sum = 0;
        $tmp =array();
        for($j=2;$j<=$highestRow;$j++){
            $i=0;
            for($k='A';$k<=29;$k++){
                if($i == 29) break;
                $str = (string)$objPHPExcel->getActiveSheet()->getCell("$k$j")->getValue();//读取单元格
                $tmp["$i"] = $str;
                $i++;
            }

            /******************************可以取得每行的数组******************************/
            //                    $food['food_id'] = $tmp['0'];
            $food['food_name'] = $tmp['1'];
            $food['food_name_en'] = $tmp['2'];
            $food['time_category'] = $tmp['3'];
            $food['discount'] = $tmp['4'];
            $food['food_price'] = $tmp['5'];
            $food['star_level'] = $tmp['6'];
            $food['hot_level'] = $tmp['7'];
            $food['is_prom'] = $tmp['8'];
            $food['is_tar'] = $tmp['9'];
            $food['foods_num_day'] = $tmp['10'];
            $food['food_desc'] = $tmp['11'];
//                    $food['restaurant_id'] = $tmp['12'];
            $food['is_sale'] = $tmp['13'];
            $food['print_id'] = $tmp['14'];
            $food['sort'] = $tmp['15'];
            $food['district_id'] = $tmp['16'];
            $food['tag_print_id'] = $tmp['17'];
            $food['dianzan'] = $tmp['18'];
            $food['food_img'] = $tmp['26']; //菜品图片

            //处理分类,变成数组
            $category = explode('|',$tmp['19']);//分类名
            $img_url = explode('|',$tmp['27']);//分类的路径
            $ico_category_type = explode('|',$tmp['28']);//分类的类别

            $attribute_type = explode(',',$tmp['20']);
            $print_id_str = explode(',',$tmp['21']);
            $select_type_str = explode(',',$tmp['22']);
            $count_type_str = explode(',',$tmp['23']);
            $tag_print_id_str = explode(',',$tmp['24']);
            $attribute_val = explode('|',$tmp['25']);

            $attribute_type_arr['food_id'] = $tmp['0'];
            $attribute_type_arr['attribute_type'] = explode(',',$tmp['20']);
            $attribute_type_arr['attribute_val'] = explode('|',$tmp['25']);

            //处理attribute_val里面的值
            if(!empty($attribute_val['0'])){
                foreach($attribute_val as $k_val=>$v_val){
                    $tmp_val = explode(',',$v_val);
                    foreach($tmp_val as $k_v =>$v_v){
                        $attr_value["$k_val"]["$k_v"] = strstr($v_v,'_',true);
                        $aa = strlen(strstr($v_v,'_',true)) + 1;
                        $attr_price["$k_val"]["$k_v"] =  substr($v_v,$aa);
                    }

                }
            }


            //以下是对数据库进行处理
            $where['food_id'] = ($tmp['0'] == null)? 0 : $tmp['0'];
            $where['restaurant_id'] = $_SESSION['restaurant_id'];
            $exist = M('food')->where($where)->find();//查询菜品

            //该菜品存在
            if($exist){
                //删除菜品属性
                $map_attr['food_id'] = $food_id = $tmp['0'];
                $del_arr = M('attribute_type')->field('attribute_type_id')->where($map_attr)->select();
                if(!empty($del_arr)){
                    foreach($del_arr as $k_del=>$v_del){
                        M('food_attribute')->where($v_del)->delete();
                        M('attribute_type')->where($v_del)->delete();
                    }
                }

                //修改菜品
                $res = M('food')->where($where)->save($food);
                $res_sum += $res;//累加结果集

                //该分类存在就直接修改
                if(!empty($category)){
                    M()->startTrans();//开启事务
                    $map_del['food_id'] = $tmp['0'];
                    $del_res = M('food_category_relative')->where($map_del)->delete();//直接把原来的分类关联表删除掉
                    $res_add = 0;
                    foreach($category as $k=>$v){
                        $map_ca['food_category_name'] = $v;
                        $map_ca['restaurant_id'] = $_SESSION['restaurant_id'];

                        $insert = M('food_category')->where($map_ca)->field('food_category_id')->find();

                        $cate_save['img_url'] = $img_url["$k"]; //分类图片路径
                        $cate_save['ico_category_type'] = $ico_category_type["$k"];//分类类型
                        M('food_category')->where($map_ca)->save($cate_save);//修改分类

                        if($insert['food_category_id']){//该分类存在
                            $insert['food_id'] = $tmp['0'];//食物id
                            //分类存在就修改分类
                            $res_add += M('food_category_relative')->data($insert)->add();//把数据插入到中间表
                        }else{

                            //分类不存在。先插入分类再插入中间表
                            if(!empty($v)) {//分类名不能为空
                                $cate_data['food_category_name'] = $v;
                                $cate_data['restaurant_id'] = $_SESSION['restaurant_id'];
                                $cate_data['img_url'] = $img_url["$k"]; //分类图片路径
                                $cate_data['ico_category_type'] = $ico_category_type["$k"];//分类类型

                                $cate_id = M('food_category')->data($cate_data)->add();
                                if ($cate_id) {//新增分类成功再插入中间表
                                    $insert_data['food_category_id'] = $cate_id;
                                    $insert_data['food_id'] = $tmp['0'];//食物id
                                    $res_add += M('food_category_relative')->data($insert_data)->add();//把数据插入到中间表
                                    //插入数据后情况
                                    $cate_id = null;
                                }
                            }
                        }
                    }
                    //判断是否成功
                    if($res_add){
                        M()->commit();
                    }else{
                        M()->rollback();
                    }
                }

                //添加菜品属性和属性值
//                if(!empty($attribute_type['0'])){
                    foreach($attribute_type as $k_type=>$v_type){
                        $data_attribute['restaurant_id'] = $_SESSION['restaurant_id'];
                        $data_attribute['food_id'] = $tmp['0'];
                        $data_attribute['type_name'] = $v_type;
                        $data_attribute['print_id'] = $print_id_str["$k_type"];
                        $data_attribute['select_type'] = $select_type_str["$k_type"];
                        $data_attribute['count_type'] = $count_type_str["$k_type"];
                        $data_attribute['tag_print_id'] = $tag_print_id_str["$k_type"];

                        $add_res = M('attribute_type')->data($data_attribute)->add();
                        if ($add_res) {
                            $res_sum += $add_res;
                            foreach ($attr_value["$k_type"] as $_k => $_v) {
//                                if(!empty($_v)){
                                    $data_attr_v['attribute_type_id'] = $add_res;
                                    $data_attr_v['attribute_name'] = $_v;
                                    $data_attr_v['attribute_price'] = $attr_price["$k_type"]["$_k"];
                                    $res_attr_add = M('food_attribute')->data($data_attr_v)->add();
                                    $res_sum += $res_attr_add;
//                                }

                            }
                        }
                    }
//                }


            }else{
                //新增菜品
                $food['restaurant_id'] = $_SESSION['restaurant_id'];
                $res_data_add = M('food')->data($food)->add();
                $res_sum += $res_data_add;//累加结果集

                //该分类存在就直接修改
                if($category && $res_data_add){
                    $res_add2 = 0;
                    foreach($category as $k=>$v){
                        $map_ca['food_category_name'] = $v;
                        $map_ca['restaurant_id'] = $_SESSION['restaurant_id'];
                        $insert = M('food_category')->where($map_ca)->field('food_category_id')->find();

                        $cate_save['img_url'] = $img_url["$k"]; //分类图片路径
                        $cate_save['ico_category_type'] = $ico_category_type["$k"];//分类类型
                        M('food_category')->where($map_ca)->save($cate_save);//修改分类

                        if($insert['food_category_id']){//该分类存在
                            $insert['food_id'] = $res_data_add;//food_id就是这个
                            $res_add2 += M('food_category_relative')->data($insert)->add();//把数据插入到中间表
                        }else{
                            //分类不存在。先插入分类再插入中间表
                            if(!empty($v)) {//分类名不能为空
                                $cate_data['food_category_name'] = $v;
                                $cate_data['restaurant_id'] = $_SESSION['restaurant_id'];
                                $cate_data['img_url'] = $img_url["$k"]; //分类图标路径
                                $cate_data['ico_category_type'] = $ico_category_type["$k"];//分类图标类型

                                $cate_id = M('food_category')->data($cate_data)->add();
                                if ($cate_id) {//新增分类成功再插入中间表
                                    $insert_data['food_category_id'] = $cate_id;
                                    $insert_data['food_id'] = $res_data_add;//食物id
                                    $res_add2 += M('food_category_relative')->data($insert_data)->add();//把数据插入到中间表
                                    //插入数据后情况
                                    $cate_id = null;
                                }
                            }

                        }
                    }
                }

                //处理规格和规格值
                foreach($attribute_type as $k_type=>$v_type){
                    $data_attribute['restaurant_id'] = $_SESSION['restaurant_id'];
                    $data_attribute['food_id'] = $res_data_add;//$res_data_add是新增的foodid
                    $data_attribute['type_name'] = $v_type;
                    $data_attribute['print_id'] = ($print_id_str["$k_type"] == null)? 0 : $print_id_str["$k_type"];
                    $data_attribute['select_type'] = ($select_type_str["$k_type"] == null)? 0 : $select_type_str["$k_type"];
                    $data_attribute['count_type'] = ($count_type_str["$k_type"] == null)? 0 : $count_type_str["$k_type"];
                    $data_attribute['tag_print_id'] = ($tag_print_id_str["$k_type"] == null) ? 0 : $tag_print_id_str["$k_type"];
                    $add_res2 = M('attribute_type')->data($data_attribute)->add();
                    if ($add_res2) {
                        $res_sum += $add_res2;
                        foreach ($attr_value["$k_type"] as $_k => $_v) {
                            $data_attr_v['attribute_type_id'] = $add_res2;//add_res2新增的attribute_Type_id
                            $data_attr_v['attribute_name'] = $_v;
                            $data_attr_v['attribute_price'] = $attr_price["$k_type"]["$_k"];
                            $res_attr_add2 = M('food_attribute')->data($data_attr_v)->add();
                            $res_sum += $res_attr_add2;
                        }
                    }
                }

            }

            //处理完一组数据后把前者的数据清零
            $food =null;
            $category = null;
            $img_url = null;
            $ico_category_type = null;
            $attribute_type = null;
            $print_id_str = null;
            $select_type_str = null;
            $count_type_str = null;
            $tag_print_id_str = null;
            $attribute_val = null;
            $attribute_type_arr = null;
            $attr_value = null;
            $attr_price = null;

            /****************************底部******************************/
        }

        if($res_sum || $del_res || $res_add  ||$res_add2){
            return true;
        }else{
            return false;
        }
    }

    //导入excel
    public function excel_in()
    {

        $file_types = explode ( ".", $_FILES ['myfile'] ['name'] );
        $file_type = $file_types [count ( $file_types ) - 1];
        $_SESSION['file_type'] = $file_type;
        $_SESSION['file'] = $_FILES['myfile'];

        if ($_FILES["myfile"]["error"] > 0)
        {
            $return['code'] = 1;
            $return['msg'] = '上传出错,错误码为'.$_FILES["myfile"]["error"];

        }else {
            if(strtolower($file_type) == "xlsx"){
                /*设置上传路径*/
                $savePath = '.'.'/Public/Uploads/Excel/';
                /*以时间来命名上传的文件*/
                $str = date ( 'Ymdhis' ).$_SESSION['restaurant_id'];
                $file_name = $savePath.$str . "." . $file_type;
                $res = move_uploaded_file($_FILES["myfile"]["tmp_name"], $file_name);
                if($res){
                    //上传文件成功，读取数据，写入数据库
                    //$file_name为文件名
                    $res = $this->excel_to_sql($file_name);
                    if($res){
                        unlink($file_name); //删除excel文件
                        $return['code'] = 0;
                        $return['msg'] = '导入成功';
                    }else{
                        $return['code'] = 3;
                        $return['msg'] = '导入失败';
                    }

                }else{
                    $return['code'] = 1;
                    $return['msg'] = '上传失败';
                }

            }else{
                $return['code'] = 11;
                $return['msg'] = '请上传excel文件';

            }
        }

        $this->ajaxReturn($return);
    }


    public function index(){
        $dishes = D('food_category');
        $food_category_relative = D('food_category_relative');
        $condition['restaurant_id'] = session("restaurant_id");
        $arr = $dishes->where($condition)->order('sort asc')->select();
        $this->assign('data', $arr);
        $food = D('food');

        $count = $food->where($condition)->count();
        $p = I('page') ? I('page'): 1;
        $pageNum = 8;
        $Page  = new \Think\PageAjax($count,$pageNum);// 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show('');// 分页显示输出
        $this->assign('page1',$show);// 赋值分页输出,简体

        $show_fanti = str_replace('上一页','上壹頁',$show);
        $show_fanti = str_replace('下一页','下壹頁',$show_fanti);
        $show_fanti = str_replace('首页','首頁',$show_fanti);
        $this->assign('page2',$show_fanti);//繁体页数

        $show_yin = str_replace('上一页','Previous',$show);
        $show_yin = str_replace('下一页','next',$show_yin);
        $show_yin = str_replace('首页','first',$show_yin);
        $this->assign('page3',$show_yin);//英文页数

        $food_list = $food->where($condition)->page($p,$pageNum)->order('sort asc')->select();
       
        foreach ($food_list as $k => $v) {
            $food_category_id = $food_category_relative->where("food_id = {$v['food_id']}")->field('food_category_id,food_id')->select();
                 $cateData = [];
            foreach ($food_category_id as $key => $value) {
                $categoryData = $dishes->where("food_category_id={$value['food_category_id']}")->field('food_category_name,food_category_id')->select();
                foreach ($categoryData as $keys => $values) {
                    if ($values['food_category_id'] = $value['food_category_id']) {
                        $cateData[] = $values['food_category_name'];
                    }
                }
            }
                $food_list[$k]['cateData'] = implode(',', $cateData);
        }

        // var_dump($food_list);
        $this->assign('info',$food_list);

        /***************菜品分类系统图标************************/
        // 分类表ico_category表里面id为1的菜品分类图标
        $ico_detail = D('ico_manager')->where(array('relation_category_id'=>1))->find();
        $photo = explode(",",$ico_detail['photo']);
        unset($photo[0]);
        $rootpath = $ico_detail['_rootpath'];
        $arr = array();
        foreach($photo as $key=>$val){
            $arr[$key-1]['photo'] = $rootpath.'/'.$val;
            $arr[$key-1]['relation_category_id'] = $ico_detail['relation_category_id'];
            $arr[$key-1]['img_id'] = $ico_detail['img_id'];
            $arr[$key-1]['ico_type'] = $ico_detail['ico_type'];
        }

        $this->assign('ico_detail',$arr);
        /***************菜品分类系统图标************************/

        $this->display();
    }

    // 点击分类后请求该分类下的具体的图标
    public function request_ico(){
        // 分类名
        $ico_category_id = I('get.id');
//        $ico_category_id = 57;
        $ico_detail = D('ico_manager')->where(array('relation_category_id'=>$ico_category_id))->find();
        $photo = explode(",",$ico_detail['photo']);
        unset($photo[0]);
        $rootpath = $ico_detail['_rootpath'];
        $arr = array();
        foreach($photo as $key=>$val){
            $arr[$key-1]['photo'] = $rootpath.'/'.$val;
            $arr[$key-1]['relation_category_id'] = $ico_detail['relation_category_id'];
            $arr[$key-1]['img_id'] = $ico_detail['img_id'];
            $arr[$key-1]['ico_type'] = $ico_detail['ico_type'];
        }
        exit(json_encode($arr));
    }

	
	//菜品分类操作后的ajax页面刷新
	public function food_category_ajax(){
		 $dishes = D('food_category');
         $ff_condition['restaurant_id'] = session('restaurant_id');
         $arr = $dishes->where($ff_condition)->order('sort asc')->select();
         $this->assign('data', $arr);
         $this->display('showcategory');
	}

	//操作菜品页面后ajax刷新页面
	public function dishes_ajax(){
        $food = D('food');
        $dishes = D('food_category');
        $food_category_relative = D('food_category_relative');
		$condition['restaurant_id'] = session("restaurant_id");
		$p = I('p') ? I('p'): 1;
        $count = $food->where($condition)->count();	
        $pageNum = 8;
		$page  = new \Think\PageAjax($count,$pageNum);// 实例化分页类 传入总记录数和每页显示的记录数
		$food_list = $food->where($condition)->order('sort asc')->page($p,$pageNum)->select();
            foreach ($food_list as $k => $v) {
            $food_category_id = $food_category_relative->where("food_id = {$v['food_id']}")->field('food_category_id,food_id')->select();
                 $cateData = [];
            foreach ($food_category_id as $key => $value) {
                $categoryData = $dishes->where("food_category_id={$value['food_category_id']}")->field('food_category_name,food_category_id')->select();
                foreach ($categoryData as $keys => $values) {
                    if ($values['food_category_id'] = $value['food_category_id']) {
                        $cateData[] = $values['food_category_name'];
                    }
                }
            }
                $food_list[$k]['cateData'] = implode(',', $cateData);
        }

		$this->assign('info',$food_list);       
        $page2 = $page->show('');// 分页显示输出
        $this->assign('page',$page2);// 赋值分页输出   
        $this->display('showfoodinfo');
	}

	//分页
    public function deskInfo(){
        $food = D('food');
            $dishes = D('food_category');
        $food_category_relative = D('food_category_relative');
        $condition['restaurant_id'] = session('restaurant_id');
        
        $pp = I("get.page");
        $p = I("get.page") ? I("get.page") : 1;
        $count = $food->where($condition)->count();
        $page_num = 8;
        $page = new \Think\PageAjax($count,$page_num);
        $food_list = $food->where($condition)->order('sort asc')->page($p,$page_num)->select();//传入当前页数，与每页显示的行数
            foreach ($food_list as $k => $v) {
            $food_category_id = $food_category_relative->where("food_id = {$v['food_id']}")->field('food_category_id,food_id')->select();
                 $cateData = [];
            foreach ($food_category_id as $key => $value) {
                $categoryData = $dishes->where("food_category_id={$value['food_category_id']}")->field('food_category_name,food_category_id')->select();
                foreach ($categoryData as $keys => $values) {
                    if ($values['food_category_id'] = $value['food_category_id']) {
                        $cateData[] = $values['food_category_name'];
                    }
                }
            }
                $food_list[$k]['cateData'] = implode(',', $cateData);
        }

        $this->assign('info',$food_list);
        $page2 = $page->show('');
        $this->assign('page1',$page2);//中文的页数

        $show_fanti = str_replace('上一页','上壹頁',$page2);
        $show_fanti = str_replace('下一页','下壹頁',$show_fanti);
        $show_fanti = str_replace('首页','首頁',$show_fanti);
        $this->assign('page2',$show_fanti);//繁体页数

        $show_yin = str_replace('上一页','Previous',$page2);
        $show_yin = str_replace('下一页','next',$show_yin);
        $show_yin = str_replace('首页','first',$show_yin);
        $this->assign('page3',$show_yin);//英文页数

        if($pp == ""){
            $this->display('index');
        }else{
            $this->display('showfoodinfo');
        }
    }
    
    //分页2
    public function deskInfo2(){
    	$relative = D('food_category_relative');
        $map['food_category_id'] = I('get.food_category_id');;
       /* $count = $relative->where($map)->count();
     	$p = I("page") ? I("page") : 1;
        $pageNum = 8;
        $Page  = new \Think\PageAjax($count,$pageNum);// 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show();// 分页显示输出
        $this->assign('page',$show);// 赋值分页输出
        $arr = $relative->where($map)->page($p,$pageNum)->select();*/
        $arr = $relative->where($map)->select();
        $food_list = array();
        $foodModel = D('food');
        $dishes = D('food_category');
        foreach ($arr as $v) {
            $condition['food_id'] = $v['food_id'];
            $food_category_id = $v['food_category_id'];
            $food_info = $foodModel->where($condition)->find(); 
            $food_type = $dishes->where("food_category_id = $food_category_id")->field("food_category_name")->find()['food_category_name'];
            $food_info['id'] = $v['id'];
            $food_info['food_category_id'] = $v['food_category_id'];
            $food_info['food_category_name'] = $food_type;
            $food_list[] = $food_info;
        }
		$sortArr = array();
		foreach($food_list as $v1){
			$sortArr[] = $v1['sort'];
		}
		array_multisort($sortArr, SORT_ASC, $food_list);
            foreach ($food_list as $k => $v) {
            $food_category_id = $relative->where("food_id = {$v['food_id']}")->field('food_category_id,food_id')->select();
                 $cateData = [];
            foreach ($food_category_id as $key => $value) {
                $categoryData = $dishes->where("food_category_id={$value['food_category_id']}")->field('food_category_name,food_category_id')->select();
                foreach ($categoryData as $keys => $values) {
                    if ($values['food_category_id'] = $value['food_category_id']) {
                        $cateData[] = $values['food_category_name'];
                    }
                }
            }
                $food_list[$k]['cateData'] = implode(',', $cateData);
        }

      	$this->assign("info",$food_list);
     	$this->display("showfoodinfo1");
    }
    
    
	//新增菜品分类
    public function createDishetype(){
        $dishes = D('food_category');
        $dishes->startTrans();
        $info = $dishes->create();

        // 自定义图标上传
        if ($_FILES['user_define_img'] != null) {
            if ($_FILES['user_define_img']['error'] != 4) {
                // 调用函数实现上传
                $_POST['_rootpath'] = '/Public/Uploads/UserDefineIco'; // 用户自定义图标上传路径
                $res = upload();
                // 上传不成功
                if(is_string($res))
                {
                    $this->error('文件上传失败，原因为' . $res);
                }
                $final_img_url = '/'.$res['user_define_img']['savepath'] . $res['user_define_img']['savename'];
                $info['img_url'] = $final_img_url;
            }
        }

		$restaurant_id = session('restaurant_id');
		$category_num = $dishes->where("restaurant_id=$restaurant_id")->max('sort');    # 找出最大的排序号
		$info['sort'] = str_pad($category_num+1,3,"0",STR_PAD_LEFT);   //排序号           #　把字符串填充为新的长度　
        $info['restaurant_id'] = $restaurant_id;
        if($info['food_category_name_en'] == 'undefined'){
            unset($info['food_category_name_en']);
        }
        $line = $dishes->add($info);
        if($line !== false){
            $time = $_POST["time"];
            if($time){
                $categoryTimeModel = D('category_time');
                $time = json_decode($time);
                foreach($time as $t_key => $t_val){
                    if($t_val[0] && $t_val[1]){
//                                echo 111;
                        $t_condition['time1'] = strtotime($t_val[0]);
                        $t_condition['time2'] = strtotime($t_val[1]);
                        $t_condition['category_id'] = $line;
                        $result1 = $categoryTimeModel->add($t_condition);
                    }
                }
            }
            $day = $_POST["day"];      # day从哪里传递过来？
            if($day){
                $day = json_decode($day);
//                        var_dump($day);
                $food_category_Model = D('food_category_timing');
                foreach($day as $d_key => $d_val){
                    $length = count($d_val);
//                            var_dump($length);
                    if($length > 2){
                        $d_data['timing_day'] = '';
                        for($i = 0;$i<$length-2;$i++){
                            if($i == ($length-3) ){
                                $d_data['timing_day'] .= $d_val[$i];
                            }else{
                                $d_data['timing_day'] .= $d_val[$i]."-";
                            }
                        }
                        $d_data['start_time'] = $d_val[$length-2];
                        $d_data['end_time'] = $d_val[$length-1];
                        $d_data['food_category_id'] = $line;
//                                var_dump($d_data);
                        $food_category_Model->add($d_data);
                    }
                }
            }
            $dishes->commit();

        	$this->food_category_ajax();
        }else{
            $dishes->rollback();
           // unlink($_POST['image']);
            $this->error('分类添加失败');
        }
    }

    //删除菜品类别
    public function delDishestype(){
        $id = I("get.food_category_id");
		$food_category_relative = D('food_category_relative');
		$result1 = $food_category_relative->where("food_category_id=$id")->select();
		if(!$result1){
	        $category_time = D("category_time");
	        $arr = $category_time->where("category_id=$id")->select();
			if($arr){
		        foreach($arr as $a){
		        	$condition['id'] = $a['id'];
		        	$category_time->where($condition)->delete();
		        }
			}

			$food_category_timing = D('food_category_timing');
			$food_category_timingArr = $food_category_timing->where("food_category_id=$id")->select();
			if($food_category_timingArr){
				foreach($food_category_timingArr as $value){
					$condition1['food_category_timing_id'] = $value['food_category_timing_id'];
					$food_category_timing->where($condition1)->delete();
				}
			}
			$food_category = D('food_category');
            // 获取到该分类的图片信息，删除非系统、非自定义图标，也就是自定义图标
            $img_info = $food_category->where("food_category_id=$id")->field('ico_category_type,img_url')->find();
            if($img_info['ico_category_type'] == 2){
                if($img_info['img_url'] != '/Public/images/defaultFoodCate1.png'){
                    // 删除自定义图标
                    @unlink('.'.$img_info['img_url']);
                }
            }

			$food_category->where("food_category_id=$id")->delete();

			$this->food_category_ajax();
		}else{
			$code = 1;
			echo $code;
		}
    }

   //将要修改的菜品分类信息填充表单
    public function updDishestype()
    {
        $id = $_POST['food_category_id'];
        $dishes = D('food_category');
        $di_condition['restaurant_id'] = session("restaurant_id");
        $info = $dishes->where($di_condition)->find($id);

        $food_categoryModel = D("category_time");
        $t_condition['category_id'] = $info['food_category_id'];
        $categoryTimeList = $food_categoryModel->where($t_condition)->select();
        if($categoryTimeList){
            foreach($categoryTimeList as $k => $v){
                $categoryTimeList[$k]['time1'] = date("Y-m-d H:i:s",$v['time1']);
                $categoryTimeList[$k]['time2'] = date("Y-m-d H:i:s",$v['time2']);
            }
            $info['category_time'] = $categoryTimeList;
        }

        $food_category_timing_Model = D('food_category_timing');
        $tim_condition['food_category_id'] = $info['food_category_id'];
        $category_timing = $food_category_timing_Model->where($tim_condition)->select();

        if($category_timing){
            foreach($category_timing as $key => $val){
                $category_timing[$key]['timing_day'] = explode("-",$val['timing_day']);
            }
            $info['category_timing'] = $category_timing;
        }
        $this->ajaxReturn($info);
    }


		//编辑菜品分类
    public function modifyDishestype(){
        $dishes = D('food_category');
        $dishes->startTrans();

        $default_url = '/Public/images/defaultFoodCate1.png';
        $condition['img_url'] = I('post.img_url');
        $condition['ico_category_type'] = I('post.ico_category_type');
        // 自定义图标上传
        if ($_FILES['user_define_img'] != null) {
            if ($_FILES['user_define_img']['error'] != 4) {
                // 调用函数实现上传
                $_POST['_rootpath'] = '/Public/Uploads/UserDefineIco'; // 用户自定义图标上传路径
                $res = upload();
                // 上传不成功
                if(is_string($res))
                {
                    $this->error('文件上传失败，原因为' . $res);
                }
                $final_img_url = '/'.$res['user_define_img']['savepath'] . $res['user_define_img']['savename'];
                $condition['img_url'] = $final_img_url;
                // 删除掉旧的图片
                $food_category = D('food_category');
                // 获取到该分类的图片信息，删除非系统、非自定义图标，也就是自定义图标
                $id = I('post.food_category_id');
                $img_info = $food_category->where("food_category_id=$id")->field('img_url,ico_category_type')->find();
                // 0默认图标、1系统图标、2自定义图标
                if($img_info['ico_category_type'] != 0 && $img_info['ico_category_type'] != 1 && $img_info['img_url'] != $default_url){
                    // 删除自定义图标
                    @unlink('.'.$img_info['img_url']);
                }
            }
        }

        // 判断是否需要删除图片
        $sent_img_url = I('post.img_url');
        $origin_img_url_info = $dishes->where(array('food_category_id'=>I('post.food_category_id')))->field('ico_category_type,img_url')->find();
        if($origin_img_url_info['ico_category_type'] != 0 && $origin_img_url_info['ico_category_type'] != 1){
            // 自定义图标
            if($origin_img_url_info['img_url'] != $sent_img_url && $origin_img_url_info['img_url'] != $default_url){
                // 图标发生了改变
                // 删除自定义图标
                @unlink('.'.$origin_img_url_info['img_url']);
            }
        }


        $food_category_id = I('post.food_category_id');
        $condition['food_category_id'] = I('post.food_category_id');
        $condition['restaturant_id'] = session('restaurant_id');
        $condition['food_category_name'] = I('post.food_category_name');
//        $condition['food_category_name_en'] = I('post.food_category_name_en');
        $food_category_name_en = I('post.food_category_name_en');
        if($food_category_name_en != 'undefined'){
            $condition['food_category_name_en'] = I('post.food_category_name_en');
        }
		$condition['is_timing'] = I('post.is_timing');

        //$condition['image'] = $_POST['image'];
        $line = $dishes->save($condition);
        if($line !== false){
            $time = $_POST["time"];
            if($time){
                $categoryTimeModel = D('category_time');
                $wt_condition['category_id'] = $food_category_id;
                $categoryTimeModel->where($wt_condition)->delete();
                $time = json_decode($time);
                foreach($time as $t_key => $t_val){
                    if($t_val[0] && $t_val[1]){
                        $t_condition['time1'] = strtotime($t_val[0]);
                        $t_condition['time2'] = strtotime($t_val[1]);
                        $t_condition['category_id'] = $food_category_id;
                        $result1 = $categoryTimeModel->add($t_condition);
                    }
                }
            }

            $day = $_POST["day"];
            if($day){
                $day = json_decode($day);
                $food_category_Model = D('food_category_timing');
                $wd_data['food_category_id'] = $food_category_id;
                $food_category_Model->where($wd_data)->delete();
                foreach($day as $d_key => $d_val){
                    $length = count($d_val);
                    if($length > 2){
                        $d_data['timing_day'] = '';
                        for($i = 0;$i<$length-2;$i++){
                            if($i == ($length-3) ){
                                $d_data['timing_day'] .= $d_val[$i];
                            }else{
                                $d_data['timing_day'] .= $d_val[$i]."-";
                            }
                        }
                        $d_data['start_time'] = $d_val[$length-2];
                        $d_data['end_time'] = $d_val[$length-1];
                        $d_data['food_category_id'] = $food_category_id;
                        $food_category_Model->add($d_data);
                    }
                }
            }
            $dishes->commit();

            $this->food_category_ajax();
        }else{
            $dishes->rollback();

        }
    }


    //通过条件显示相关条件下所有菜单信息
    public function showDisinfoBykey(){
        $relative = D('food_category_relative');       
        $map['food_category_id'] = I('get.food_category_id');
       /* $count = $relative->where($map)->count();
        $p = I('p') ? I('p'): 1;
        $pageNum = 8;
        $Page  = new \Think\PageAjax($count,$pageNum);// 实例化分页类 传入总记录数和每页显示的记录数  
        $show = $Page->show();// 分页显示输出
        $this->assign('page',$show);// 赋值分页输出
        $arr = $relative->where($map)->page($p,$pageNum)->select();*/
        $arr = $relative->where($map)->select();
        $food_list = array();
        $foodModel = D('food');
        $dishes = D('food_category');
        foreach ($arr as $v) {
            $condition['food_id'] = $v['food_id'];
            $food_category_id = $v['food_category_id'];
            $food_info = $foodModel->where($condition)->find(); 
            $food_type = $dishes->where("food_category_id = $food_category_id")->field("food_category_name")->find()['food_category_name'];
            $food_info['id'] = $v['id'];
            $food_info['food_category_id'] = $v['food_category_id'];
            $food_info['food_category_name'] = $food_type;
            $food_list[] = $food_info;
        }
		$sortArr = array();
		foreach($food_list as $v1){
			$sortArr[] = $v1['sort'];
		}
		array_multisort($sortArr, SORT_ASC, $food_list);
            foreach ($food_list as $k => $v) {
            $food_category_id = $relative->where("food_id = {$v['food_id']}")->field('food_category_id,food_id')->select();
                 $cateData = [];
            foreach ($food_category_id as $key => $value) {
                $categoryData = $dishes->where("food_category_id={$value['food_category_id']}")->field('food_category_name,food_category_id')->select();
                foreach ($categoryData as $keys => $values) {
                    if ($values['food_category_id'] = $value['food_category_id']) {
                        $cateData[] = $values['food_category_name'];
                    }
                }
            }
                $food_list[$k]['cateData'] = implode(',', $cateData);
        }
      	$this->assign("info",$food_list);
     	$this->display("showfoodinfo1");
    }

//-------------------------------------------------菜品--------------------------------------------
    //新增菜品页面
    public function add(){
        //获取店铺分区信息
        $district_model = D("restaurant_district");
        $district_where['restaurant_id'] = session("restaurant_id");
        $district_list = $district_model->where($district_where)->field("district_id,district_name")->select();
        $district_list[] = array(
            "district_id" => 0,
            "district_name" => "不设分区",
        );
        $this->assign("district_list",$district_list);

        $dishes = D('food_category');
        $condition['restaurant_id'] = session('restaurant_id');
        $arr = $dishes->where($condition)->order('sort asc')->select();
        $printerModel = D("printer");
        $p_condition['restaurant_id'] = session("restaurant_id");
        $printList = $printerModel->where($p_condition)->select();
        $S_Category = new Category();
        $this->assign("time_category_list", $S_Category->getList());
        $this->assign("printerList",$printList);
        $this->assign('data', $arr);
        $this->display("addDishes");
    }
	
	//菜品关联表的ajax刷新
	public function food_ajax1(){
        $relative = D('food_category_relative');       
        $map['food_category_id'] = I('food_category_id');
		//dump(I('food_category_id'));
     /*   $count = $relative->where($map)->count();
        $p = I('p') ? I('p'): 1;
		//dump($p);
        $pageNum = 8;
        $Page  = new \Think\PageAjax($count,$pageNum);// 实例化分页类 传入总记录数和每页显示的记录数  
        $show = $Page->show();// 分页显示输出
        $this->assign('page',$show);// 赋值分页输出
        $arr = $relative->where($map)->page($p,$pageNum)->select();*/
        $arr = $relative->where($map)->select();
        $food_list = array();
        $foodModel = D('food');
        $dishes = D('food_category');
        foreach ($arr as $v) {
            $condition['food_id'] = $v['food_id'];
            $food_category_id = $v['food_category_id'];
            $food_info = $foodModel->where($condition)->find();
            $food_type = $dishes->where("food_category_id = $food_category_id")->field("food_category_name")->find()['food_category_name'];
            $food_info['id'] = $v['id'];
            $food_info['food_category_id'] = $v['food_category_id'];
            $food_info['food_category_name'] = $food_type;
            $food_list[] = $food_info;
        }

        $sortArr = array();
        foreach($food_list as $v1){
            $sortArr[] = $v1['sort'];
        }
        array_multisort($sortArr, SORT_ASC, $food_list);
            foreach ($food_list as $k => $v) {
            $food_category_id = $relative->where("food_id = {$v['food_id']}")->field('food_category_id,food_id')->select();
                 $cateData = [];
            foreach ($food_category_id as $key => $value) {
                $categoryData = $dishes->where("food_category_id={$value['food_category_id']}")->field('food_category_name,food_category_id')->select();
                foreach ($categoryData as $keys => $values) {
                    if ($values['food_category_id'] = $value['food_category_id']) {
                        $cateData[] = $values['food_category_name'];
                    }
                }
            }
                $food_list[$k]['cateData'] = implode(',', $cateData);
        }
      	$this->assign("info",$food_list);
     	$this->display("showfoodinfo1");
	}

    //新增菜品
    public function createfoodinfo()
    {
        $food = D('Food');
        if (!empty($_POST)){
            $food->startTrans();
            // var_dump($_FILES);exit();
//            $_POST['food_pic'] = './Application/Admin/Uploads/default/unupload.png';
            if($_FILES['food_pic']['error'] != 4){     //图片上传
              	$upload = new \Think\Upload();// 实例化上传类
                // $upload->maxSize = 3145728;// 设置附件上传大小
                $upload->maxSize = 1048576;// 设置附件上传大小
                $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
                $upload->savePath = 'upfoodimg/'; // 设置附件上传目录
                $upload->autoSub = false;
                $z = $upload->upload();
                $picpathname = './Application/Admin/Uploads/' . $z[food_pic]['savepath'] . $z[food_pic]['savename'];
                $image = new \Think\Image();
                $image->open($picpathname);// 按照原图的比例生成一个最大为150*150的缩略图并保存为thumb.jpg
                $image->thumb(500, 300)->save($picpathname);
                $_POST['food_pic'] = $picpathname;

                // 不需要使用到水印图片
                $need_save = false;
            }else{
                // 需要使用到水印图片
                $need_save = true;
                $_POST['food_pic'] = './Application/Admin/Uploads/default/unupload.png';
            }


            // var_dump($_POST);exit();
			$restaurant_id = session('restaurant_id');
			//表单数据
			$data['food_name'] = $_POST['food_name'];
            if($_POST['food_name_en'] != ''){
                $data['food_name_en'] = $_POST['food_name_en'];
            }
//			$data['food_name_en'] = $_POST['food_name_en'];
            if(I("time_category") != ''){
                $data['time_category'] = implode(",", I("time_category"));
            }
//            $data['time_category'] = implode(",", I("time_category"));
            $data['food_img'] = $_POST['food_pic'];
            $data['discount'] = $_POST['discount'];
            $data['food_price'] = $_POST['food_price'];
			$data['star_level'] = $_POST['star_level'];
           	$data['hot_level'] = $_POST['cayenne'];
            $data['foods_num_day'] = $_POST['foods_num_day'];
            $data['food_desc'] = $_POST['food_desc'];
			$data['is_prom'] = isset($_POST['is_prom']) ? isset($_POST['is_prom']) :0;
            $data['district_id'] = $_POST['district'];
            $data['restaurant_id'] = $restaurant_id;
            $data['print_id'] = $_POST['print_id'];
            $data['tag_print_id'] = $_POST['tag_print_id'];
            $data['erp_number'] = $_POST['erp_number'];
            // var_dump($_POST['tag_print_id']);
            // if (isset($_POST['tag_print_id'])) {
            //     $data['print_id'] = $_POST['print_id'].','.$_POST['tag_print_id'];
            // }
            // var_dump($data['print_id']);exit();
			$num = $food->where("restaurant_id=$restaurant_id")->max('sort');
			$data['sort'] = str_pad($num+1,3,"0",STR_PAD_LEFT);   //排序号
			//dump($data);
            $r = $food->add($data);
            //如果出现错误，则事务回滚
            if($r != false){
                $relative = D('food_category_relative');
	            $data2['food_id'] = $r;
	            $sort1 = $_POST['sort1'];       # $sort1菜品所属的菜品分类（一个或者多个），是个数组形式
	            foreach ($sort1 as $so){
	                $data2['food_category_id'] = $so;
	                $r2 = $relative->add($data2);
	            }
            }else{
            	$food->rollback();
                $msg['code'] = "0";
                $msg['msg'] = "失败";
                exit(json_encode($msg));
            }
			if($_POST['is_prom'] == 1){
            $prom = D('prom');
            $data1['prom_id'] = $r;
            $data1['prom_price'] = $_POST['prom_price'];
            $data1['discount'] = $_POST['prom_discount'];
            $data1['prom_goods_num'] = $_POST['prom_goods_num'];;
            $data1['prom_start_time'] = strtotime($_POST['prom_start_time']);
            $data1['prom_end_time'] = strtotime($_POST['prom_end_time']);
            $r1 = $prom->add($data1);
           		//如果出现错误，则事务回滚
		        if($r1 === false){
		            $food->rollback();
		            $msg['code'] = "0";
		            $msg['msg'] = "失败";
		            exit(json_encode($msg));
		        }
			}

            if($r && $need_save){
                $food_name = $_POST['food_name'];
                $save_path = "./Public/default_food_water_print/".$r.".png";    // 水印图片保存路径和文件名，命名为菜品id
                $image = new \Think\Image();
                $image->open('./Public/default_food_water_print/food_water_print.png')  // 水印背景图
                ->text($food_name,'./font.ttc',30,'#000000',\Think\Image::IMAGE_WATER_CENTER)   // 水印内容为菜品名
                ->save($save_path);  // 保存为新图片

                $res = $food->where(array('food_id'=>$r))->save(array('food_img'=>$save_path));
            }

			$condition['restaurant_id'] = session('restaurant_id');
			$tr_Num = $food->where($condition)->count();
			$page_Num = ceil($tr_Num/8);
            $msg['code'] = "1";
            $msg['msg'] = "菜品新增成功，请在下方添加菜品附属类别";
            $msg['food_id'] = $r;
			$msg['page_Num'] = $page_Num;

            // 新增菜品时，如果每天供应量为0，则为售罄，推送给安卓
            if($_POST['foods_num_day'] == 0){
                $food->where("food_id = $r")->save(array('is_shutdown'=>1));
                // 售罄处理 则推送消息给安卓
                $S_SellOut = new ServiceSellOut();
                $S_SellOut->whenUpdateFood($r,session('restaurant_id'),'sellOut');
            }
			$food->commit();

            exit(json_encode($msg));
        }
    }

    /**
     * 添加菜品属性类别
     */
    public function addDishesAttrType(){
        $attr_type_model = D("attribute_type");
        $data = $attr_type_model->create();
        // if (isset($_POST['tag_print_id'])) {
        //     $data['print_id'] = $_POST['print_id'].','.$_POST['tag_print_id'];
        // }
        $data['restaurant_id'] = session("restaurant_id");
        $rel = $attr_type_model->add($data);
        if($rel !== false){

            $dianpu_id = session("restaurant_id");
            // 删除模态框静态文件
            @ unlink(HTML_PATH .  "$dianpu_id/orderPopup".$data['food_id'].".html");

            $data['attribute_type_id'] = $rel;
            $msg['code'] = 1;
            $msg['msg'] = "操作成功";
            $msg['data'] = $data;
            exit(json_encode($msg));
        }
    }

    public function addDishesAttr(){
        $attrModel = D("food_attribute");
        $data1 = $attrModel->create();
        $data['attribute_type_id'] = $data1['attribute_type_id'];
        $data['attribute_name'] = $data1['attribute_name'];
        $data['attribute_price'] = $data1['attribute_price'];

        /*$data['attribute_img'] = getcwd()."/Public/images/dishes01.png";

        if ($_FILES['attribute_img']['error'] != 4){
            $upload = new \Think\Upload();// 实例化上传类
            $upload->maxSize = 3145728;// 设置附件上传大小
            $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
            $upload->savePath = 'upfoodattr/'; // 设置附件上传目录
            $upload->autoSub = false;
            $z = $upload->upload();
            //dump($z);	
            $picpathname = './Application/Admin/Uploads/' . $z[attribute_img]['savepath'] . $z[attribute_img]['savename'];
           // dump($picpathname);
            $data['attribute_img'] = $picpathname;

        }*/

        $rel = $attrModel->add($data);
        if($rel !== false){

            $dianpu_id = session("restaurant_id");
            // 删除模态框静态文件
            // 利用attribute_type_id去获取food_id
            $attr_type = D("attribute_type");
            $food_id = $attr_type->where(array("attribute_type_id"=>$data1['attribute_type_id']))->getField("food_id");
            @ unlink(HTML_PATH ."$dianpu_id/orderPopup".$food_id.".html");

            $data['food_attribute_id'] = $rel;
            $msg['code'] = 1;
            $msg['msg'] = "操作成功";
            $msg['data'] = $data;
            exit(json_encode($msg));
        }
    }

	public function getDishesAttr(){
		$attrModel = D("food_attribute");
		$food_attribute_id = I('get.food_attribute_id');
		$attrObject = $attrModel->where("food_attribute_id=$food_attribute_id")->find();
		$this->ajaxReturn($attrObject);
	}
    public function subm()
    {
        $attrModel = D("food_attribute");
        $postData = $_POST;
        $where = [];
        $data['attribute_name'] = $postData['attribute_name'];
        $data['attribute_price'] = $postData['attribute_price'];
        // var_dump($_POST);exit();
        //编辑
        if (isset($postData['food_attribute_id']) && $postData['food_attribute_id'] != '') {
            $data['food_attribute_id'] = $postData['food_attribute_id'];
            $rel = $attrModel->save($data);
            if($rel !== false){
                $msg['code'] = 1;
                $msg['msg'] = "操作成功";
                $msg['data'] = $data;
                exit(json_encode($msg));
            }
        //新增
        }else{
            $data['attribute_type_id'] = $postData['attribute_type_id'];
            $data['attribute_name'] = $postData['attribute_name'];
            $data['attribute_price'] = $postData['attribute_price'];
            $rel = $attrModel->add($data);
            $newData = $attrModel->where($data)->find();
            $data['food_attribute_id'] = $newData['food_attribute_id'];
            if($rel !== false){
                $msg['code'] = 1;
                $msg['msg'] = "操作成功";
                $msg['data'] = $data;
                exit(json_encode($msg));
            }
        }
    }
    public function editDishesAttr(){
        $attrModel = D("food_attribute");
        $data1 = $attrModel->create();
        $data['food_attribute_id'] = $data1['food_attribute_id'];
        $data['attribute_name'] = $data1['attribute_name'];
        $data['attribute_price'] = $data1['attribute_price'];

       /* if ($_FILES['attribute_img']['error'] != 4){
            $upload = new \Think\Upload();// 实例化上传类
            $upload->maxSize = 3145728;// 设置附件上传大小
            $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
            $upload->savePath = 'upfoodimg/'; // 设置附件上传目录
            $upload->autoSub = false;
            $z = $upload->upload();
            //dump($z);
            $picpathname = './Application/Admin/Uploads/' . $z[food_pic]['savepath'] . $z[food_pic]['savename'];
            //dump($picpathname);

            $data['attribute_img'] = $picpathname;

        }
        $data['attribute_img'] = getcwd()."/Public/images/dishes01.png";*/

        $rel = $attrModel->save($data);
        if($rel !== false){

            // 删除对应的模态框静态文件
            $dianpu_id = session("restaurant_id");
            // 利用attribute_type_id去获取food_id
            $attr_type = D("attribute_type");
            // 先通过food_attribute_id去获取attribute_type_id
            $attribute_type_id = $attrModel->where(array("food_attribute_id"=>$data1['food_attribute_id']))->getField("attribute_type_id");
            $food_id = $attr_type->where(array("attribute_type_id"=>$attribute_type_id))->getField("food_id");
            @ unlink(HTML_PATH ."$dianpu_id/orderPopup".$food_id.".html");

//            $data['food_attribute_id'] = $rel;
            $msg['code'] = 1;
            $msg['msg'] = "操作成功";
            $msg['data'] = $data;
            exit(json_encode($msg));
        }
    }

     //删除菜品
    public function delfoodinfo(){
        # 缓存   在还没删除数据库的数据前获取到其分类ID
        $fcr = D("food_category_relative");
        $result = $fcr->where(array("food_id"=>I("get.food_id")))->select();
        # 缓存

       	$food = D('food');
       	$food->startTrans();   //开启事务
       	$food_category_relative = D('food_category_relative');  //菜品表与菜品分类关联第三个表     	
	    $condition['food_id'] = I('get.food_id');
		$result1 =  $food_category_relative->where($condition)->delete(); //先删除菜品表与菜品分类关联的第三个表
		
	    $prom = D('prom');
	    $condition1['prom_id'] = I('get.food_id');
	   	$result2 = $prom->where($condition1)->delete();    //删除菜品定时表
	

        $addr_img = $food->where($condition)->field('food_img')->find()['food_img']; //先找到菜品图片
        if($addr_img != "./Application/Admin/Uploads/default/unupload.png"){
        	 unlink($addr_img);						//菜品删除图片
        }
        $result3 = $food->where($condition)->delete(); //再删除菜品记录
       	if($result1 || $result2 || $result3){
       		$attribute_type = D('attribute_type');
   			$addr_list = $attribute_type->where($condition)->select(); //查询菜品类别表，删除关联类别记录
   			$food_attribute = D('food_attribute');
   			foreach($addr_list as $k => $v){
   				$condition2['attribute_type_id'] = $v['attribute_type_id'];		
   				$addr_list1 = $food_attribute->where($condition2)->select(); //查谒菜品属性表，删除关联属性记录
   				foreach($addr_list1 as $k=>$v){			
   					 unlink($addr_list1[$k]['attribute_img']);
   					 $condition3['attribute_type_id']  = $addr_list1[$k]['attribute_type_id'];
   					 $food_attribute->where($condition3)->delete();
   				}		
   			}	
   			$attribute_type->where($condition)->delete();
            $msjm_condition['restaurant_id'] = session('restaurant_id');
	        $count = $food->where($msjm_condition)->count();
	        $p = I("get.page") ? I("get.page") : 1;
	        $pageNum = 8;
	        $Page  = new \Think\PageAjax($count,$pageNum);// 实例化分页类 传入总记录数和每页显示的记录数
	        $show = $Page->show('');// 分页显示输出
	        $this->assign('page',$show);// 赋值分页输出
	        $food_list = $food->where($msjm_condition)->page($p,$pageNum)->select();
	        $this->assign('info',$food_list);
			$this->display('showfoodinfo');
			$food->commit(); 						//提交事务

            // 删除相关的静态页
            $dianpu_id = session("restaurant_id");
            @ unlink(HTML_PATH . "$dianpu_id/order.html");  // 删除订单页

            // 删除分类ID对应的内容页
            // 根据food_id 去获取food_category_id
            foreach($result as $val){
                @ unlink(HTML_PATH . "$dianpu_id/orderAjax".$val['food_category_id'].".html");
            }

            // 删除对应的模态框缓存文件
            $food_id = I('get.food_id');
            @ unlink(HTML_PATH ."$dianpu_id/orderPopup".$food_id.".html");


       		}else{
       			$food->rollback();	       		
       		}
       		
    }

	//删除菜品分类关联表
	public function delfoodinfo1(){
		$food_category_relative = D('food_category_relative');
		$where['id'] = I('get.id');
		$r = $food_category_relative->where($where)->delete();
		if($r){
			$this->food_ajax1();
		}	
	}


    //将数据库的数据填充到表单
    public function edit(){
        //获取店铺分区信息
        $district_model = D("restaurant_district");
        $district_where['restaurant_id'] = session("restaurant_id");
        $district_list = $district_model->where($district_where)->field("district_id,district_name")->select();
        $district_list[] = array(
            "district_id" => 0,
            "district_name" => "不设分区",
        );
        $this->assign("district_list",$district_list);

        //获取当前所有的打印机消息
        $printerModel = D("printer");
        $pr_condition['restaurant_id'] = session("restaurant_id");
        $printerList = $printerModel->where($pr_condition)->select();
        $this->assign("printerList",$printerList);
//        dump($printerList);

        $id = $_GET['food_id'];
        $this->assign("food_id",$id);
        $food = D('Food');
        $arr = $food->find($id);
        $arr['food_img'] = $arr['food_img'];
        //根据打印机id获取打印机名称
        $where = [];
        $where['printer_id'] = $arr['tag_print_id'];
        $arr['printer_name'] = $printerModel->field('printer_name')->where($where)->find()['printer_name'];

        
        $this->assign('time_category', explode(",", $arr['time_category']));

        // 先判断关于该食物ID的订单在今天内所对应的份数是否已经超过额定的份数
        $start=mktime(0,0,0,date("m"),date("d"),date("Y"));       //当天开启时间
        $end=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;     //当天结束时间

        $Model = M(); // 实例化一个model对象 没有对应任何数据表
        /*$num = $Model->query(" select t1.food_num as num from order_food t1 inner join
                        `order` t2 on t1.order_id = t2.order_id and t1.food_id = $id and t2.order_status in ('3','11','12')
                        and t2.pay_time between $start and $end");

        if($num){
            $total = 0;
            foreach($num as $n){
                $total += $n['num'];
            }
        }else{
            $total = 0;
        }*/
        $table_time=date("Y").date("m");
        $sql = "select SUM(t1.food_num) as num from order_food_$table_time t1 LEFT JOIN
                        `order_$table_time` t2 on t1.order_id = t2.order_id
												WHERE t1.food_id = $id and t2.order_status in ('3','11','12')
                        and t2.add_time between $start and $end";
        $res = $Model->query($sql);
        // 当天到目前为止消费数量
        $total = $res[0]['num'] ? : 0;
        $this->assign("num", $total);
        $this->assign('info', $arr);
        //获取所有的菜品分类
        $dishes = D('food_category');
        $f_condition['restaurant_id'] = session('restaurant_id');
        $arr1 = $dishes->where($f_condition)->select();

        //获取该菜品所属的菜品分类
        $dishes = D('food_category_relative');
        $condition["food_id"] = $id;
        $arr2 = $dishes->where($condition)->select();

        //对对应的菜品分类做标记，以便前端显示
        foreach($arr1 as $key => $val){
            foreach($arr2 as $k =>$v){
                if($val['food_category_id'] == $v['food_category_id']){
                    $arr1[$key]["is_select"] = 1;
                    continue;
                }
            }
        }
        $this->assign("data", $arr1);


        //获取菜品的时价属性
        $prom = D('prom');
        $arr3 = $prom->find($id);
        $this->assign("info1", $arr3);

        //获取菜品的类别和属性
        $type_condition['food_id'] = $id;
        $attr_type_model = D('attribute_type');
        $attr_type_list = $attr_type_model->where($type_condition)->select();
//        var_dump($attr_type_list);
        $food_attr_model = D('food_attribute');
        $attr_list=array();
        foreach($attr_type_list as $kt => $vt){
            $ft_condition['attribute_type_id'] = $vt['attribute_type_id'];
            $temp = $food_attr_model->where($ft_condition)->select();
            $temp2 = $food_attr_model->where($ft_condition)->count();
            $attr_type_list[$kt]['num'] = $temp2;
            $attr_list[$kt+1] = $temp;
        }

        $attr_type_list2 = array(); 
        foreach($attr_type_list as$ky => $vl){
            $attr_type_list2[$ky+1] = $vl;
            $attr_type_list2[$ky+1]['attr_list'] = $attr_list[$ky+1];
        }
		
        $this->assign("attr_type_list",$attr_type_list2);
        $S_Category = new Category();
        $this->assign("time_category_list", $S_Category->getList());

        $this->display("editDishes");
    }

    public function getTypeAttrs(){
//        $attribute_type_id = 4;
        $attribute_type_id = I("post.type_id");
        $printerModel = D("printer");
        $p_condition['restaurant_id'] = session("restaurant_id");
        $printList = $printerModel->where($p_condition)->select();
//        dump($printList);
        $this->assign("printerList",$printList);
        $attributeTypeModel = D("attribute_type");
        $attr_type = $attributeTypeModel->where("attribute_type_id = $attribute_type_id")->find();

        $food_attribute_model = D("food_attribute");
        $attrs= $food_attribute_model->where("attribute_type_id = $attribute_type_id")->select();
        $attr_type['attrs'] = $attrs;
        $this->assign("attr_type",$attr_type);
        $this->display("editDishesAjax");
    }

    public function editDishesType(){
        $attr_type_model = D("attribute_type");
        $data = $attr_type_model->create();
        // var_dump($_POST);exit();
        // if (isset($_POST['tag_print_id'])) {
        //     $data['print_id'] = $_POST['print_id'].','.$_POST['tag_print_id'];
        // }

        $data['restaurant_id'] = session("restaurant_id");
        $rel = $attr_type_model->save($data);
        if($rel !== false){

            // 删除对应的模态框
            $dianpu_id = session("restaurant_id");
            // 根据传递过来的food_id去删除
            @ unlink(HTML_PATH ."$dianpu_id/orderPopup".$data['food_id'].".html");

            $msg['code'] = 1;
            $msg['msg'] = "操作成功";
            $msg['data'] = $data;
            exit(json_encode($msg));
        }
    }

    public function deleteAttr(){
        $attr_id = I('post.attr_id');
        $food_attribute_model = D('food_attribute');
        $condition['food_attribute_id'] = $attr_id;

        // 供缓存使用的attribute_type_id要在这里获取，不然走完下一个delete语句数据库就没有这个记录了
        $attribute_type_id = $food_attribute_model->where($condition)->getField("attribute_type_id");

        $rel = $food_attribute_model->where($condition)->delete();
        if($rel !== false){

            // 删除对应的模态框静态文件
            $attr_type = D("attribute_type");
            $food_id = $attr_type->where(array("attribute_type_id"=>$attribute_type_id))->getField("food_id");
            $dianpu_id = session("restaurant_id");
            @ unlink(HTML_PATH ."$dianpu_id/orderPopup".$food_id.".html");

            $msg['code'] = 1;
            $msg['msg'] = "操作成功";
            exit(json_encode($msg));
        }
    }

    public function deleteType(){
        $type_id = I('post.type_id');
        $attribute_type_model = D('attribute_type');
        $attribute_type_model->startTrans();

        $food_attribute_model = D('food_attribute');
        $condition['attribute_type_id'] = $type_id;

        // 缓存：利用attribute_type_id获取food_id,如果下面执行到了commit就删除掉模态框
        $food_id = $attribute_type_model->where($condition)->getField("food_id");

        $rel1 = $food_attribute_model->where($condition)->delete();
        if($rel1 == false){
            $attribute_type_model->rollback();
        }
        $rel2 = $attribute_type_model->where($condition)->delete();
        if($rel2 == false){
            $attribute_type_model->rollback();
        }
        if($rel2 !== false){
            $attribute_type_model->commit();

            // 删除掉模态框缓存
            $dianpu_id = session("restaurant_id");
            @ unlink(HTML_PATH . "$dianpu_id/orderPopup".$food_id.".html");

            $msg['code'] = 1;
            $msg['msg'] = "操作成功";
            exit(json_encode($msg));
        }
    }


    # 编辑菜品
    public function modifyfoodinfo(){
        $food = D('food');
        if (!empty($_POST)){
            $data = array();
            if ($_FILES['img_pic']['error'] != 4){
                $condition['food_id'] = $_GET['food_id'];
                $addr_img = $food->where($condition)->field('food_img')->find()['food_img'];
                if($addr_img != './Application/Admin/Uploads/default/unupload.png'){
                    unlink($addr_img);
                }
                $up = new \Think\Upload();
                $up->savePath = 'upfoodimg/'; // 设置附件上传目录
                $up->autoSub = false;
                $z = $up->uploadOne($_FILES['img_pic']);
                $picpathname = './Application/Admin/Uploads/' . $z['savepath'] . $z['savename'];
                $image = new \Think\Image(); 
                $image->open($picpathname);// 按照原图的比例生成一个最大为150*150的缩略图并保存为thumb.jpg
                $image->thumb(500, 300)->save($picpathname);
                $_POST['image'] = $picpathname;
                $data['food_img'] = $_POST['image'];
            }
            $food_id = $_GET['food_id'];

            // 在未更新前先查出关于售罄的详细信息
            $sellOut_info = $food->where("food_id = $food_id")->field('is_shutdown,update_time,sale_num')->find();

            $data['food_name'] = $_POST['food_name'];
            $data['food_name_en'] = $_POST['food_name_en'];
            $data['time_category'] = implode(",", $_POST['time_category']);
            $data['food_price'] = $_POST['food_price'];
            $data['discount'] = $_POST['discount'];
            $data['foods_num_day'] = $_POST['food_num_day'];
            $data['star_level'] = $_POST['star_level'];
			$data['hot_level'] = $_POST['cayenne'];
            $data['food_desc'] = $_POST['food_desc'];
			$data['is_prom'] = $_POST['is_prom'];
            $data['print_id'] = $_POST['print_id'];
            $data['tag_print_id'] = $_POST['tag_print_id'];
            $data['erp_number'] = $_POST['erp_number'];
            // if (isset($_POST['tag_print_id'])) {
            //     $data['print_id'] = $_POST['print_id'].','.$_POST['tag_print_id'];
            // }
            $data['district_id'] = $_POST['district'];
            $data['restaurant_id'] = session('restaurant_id');
            $line = $food->where("food_id = $food_id")->save($data);

			$relative = D('food_category_relative');
	        $data2['food_id'] = $food_id;
	        $sort1 = $_POST['sort1'];
	        //先删除改菜品的分类，然后重新添加关联
	        $relative->where("food_id = $food_id")->delete();
            foreach ($sort1 as $so){
                $data2['food_category_id'] = $so;
                $r2 = $relative->add($data2);
            }
			if($_POST['is_prom'] == 1){//判定编辑时，是否开启了时价
				$prom_id = $_GET['food_id'];
				$prom = D('prom');
				$result = $prom->where("prom_id = $prom_id")->find();
				$data1['prom_id'] = $prom_id;
	            $data1['prom_price'] = $_POST['prom_price'];
	            $data1['discount'] = $_POST['prom_discount'];
	            $data1['prom_goods_num'] = $_POST['prom_goods_num'];
	            $data1['prom_start_time'] = strtotime($_POST['prom_start_time']);
	            $data1['prom_end_time'] = strtotime($_POST['prom_end_time']);
				if($result){//如果开启了时价，判段之前是否存在时价,存在编辑时价表
		            $prom->save($data1);
				}else{//不存在，新增时价表
					$prom->add($data1);
				}
			}

            // 修改了每日供应量，与售罄进行对比，比售罄量多则推送消息给安卓
            $food_num_day = $_POST['food_num_day'];
            $Date = date('Y-m-d',time());
            $startTime = '00:00:00';
            $endTime = '23:59:59';
            $startTimeStr = strtotime($Date." ".$startTime);
            $endTimeStr = strtotime($Date." ".$endTime);
            $update_time = $sellOut_info['update_time'];

            // 情况一：
            // 更新时间不在今天内的，改为shutdown为0，sale_num为0，update_time为当前
            // 然后判断当前food_num_day为0的话就shutdown
            // 情况二：
            // 更新时间在今天范围内的，shutdown为1，然后判断当前food_num_day大于sale_num的话就上架，shutdown改为0
            // 更新时间在今天范围内的，shutdown为0，然后判断当前food_num_day小于等于sale_num的话就售罄，shutdown改为1
            if(($startTimeStr<$update_time && $update_time<$endTimeStr) && $update_time !== null){
                // shutdown为1
                if($sellOut_info['is_shutdown'] == 1){
                    if($food_num_day>$sellOut_info['sale_num']){
                        $food->where("food_id = $food_id")->save(array('is_shutdown'=>0));
                        // 上架处理 则推送消息给安卓
                        $S_SellOut = new ServiceSellOut();
                        $S_SellOut->whenUpdateFood($food_id,session('restaurant_id'),'onSale');
                    }
                }else{
                    if($food_num_day <= $sellOut_info['sale_num']){
                        $food->where("food_id = $food_id")->save(array('is_shutdown'=>1));
                        // 售罄处理 则推送消息给安卓
                        $S_SellOut = new ServiceSellOut();
                        $S_SellOut->whenUpdateFood($food_id,session('restaurant_id'),'sellOut');
                    }
                }
            }else{
                // 不是在今天范围内
                // shutdown置为0，已卖份数置为0，更新时间为当前
                $food->where("food_id = $food_id")->save(array('is_shutdown'=>0,'sale_num'=>0,'update_time'=>time()));
                // 如果客户设置的每天供应量为0的话，则也为shutdown售罄，推给安卓
                if($food_num_day == 0){
                    $food->where("food_id = $food_id")->save(array('is_shutdown'=>1,'update_time'=>time()));
                    $S_SellOut = new ServiceSellOut();
                    $S_SellOut->whenUpdateFood($food_id,session('restaurant_id'),'sellOut');
                }
            }

            if ($line !== false) {
                $msg['code'] = "1";
                $msg['msg'] = "成功";
                exit(json_encode($msg));
            }else {
                $msg['code'] = "0";
                $msg['msg'] = "失败";
                exit(json_encode($msg));
            }
        }
    }

    //修改上下架状态
    public function updstate(){
        $food = D('food');
                $relative = D('food_category_relative');
	            $dishes = D('food_category');
        $condition['food_id'] = I('get.food_id');
        $info = $food->where($condition)->find();
        if($info['is_sale'] == 0){
            $condition['is_sale'] = 1;      
        }else{
            $condition['is_sale'] = 0;              
        }
        $r = $food->save($condition);
        if($r){

            $dianpu_id = session("restaurant_id");
            // 删除订单页的静态页
            @ unlink(HTML_PATH . "$dianpu_id/order.html"); // @是为了抑制因文件不存在而删除失败的错误信息

            // 删除模态框缓存
            $food_id = I('get.food_id');
            @ unlink(HTML_PATH ."$dianpu_id/orderPopup".$food_id.".html");

            // 删除该菜品所属分类的缓存文件
            // 利用food_id去food_category_relative表去获取food_category_id
            $fcr = D("food_category_relative");
            $cat_data = $fcr->where(array("food_id"=>$food_id))->select();
            foreach($cat_data as $food_cat_id){
                @ unlink(HTML_PATH . "$dianpu_id/orderAjax".$food_cat_id['food_category_id'].".html");
            }


            $key = I('get.food_category_id');
            if($key == 0){
                $cc_condition['restaurant_id'] = session("restaurant_id");
                 $count = $food->where($cc_condition)->count();
                 $p = I("get.page") ? I("get.page") : 1;
                 $pageNum = 8;
                 $Page  = new \Think\PageAjax($count,$pageNum);// 实例化分页类 传入总记录数和每页显示的记录数
                 $show = $Page->show('');// 分页显示输出
                $this->assign('page1',$show);// 赋值分页输出,简体

                $show_fanti = str_replace('上一页','上壹頁',$show);
                $show_fanti = str_replace('下一页','下壹頁',$show_fanti);
                $show_fanti = str_replace('首页','首頁',$show_fanti);
                $this->assign('page2',$show_fanti);//繁体页数

                $show_yin = str_replace('上一页','Previous',$show);
                $show_yin = str_replace('下一页','next',$show_yin);
                $show_yin = str_replace('首页','first',$show_yin);
                $this->assign('page3',$show_yin);//英文页数

                 $food_list = $food->where($cc_condition)->order('sort asc')->page($p,$pageNum)->select();
                     foreach ($food_list as $k => $v) {
            $food_category_id = $relative->where("food_id = {$v['food_id']}")->field('food_category_id,food_id')->select();
            foreach ($food_category_id as $key => $value) {
                $categoryData = $dishes->where("food_category_id={$value['food_category_id']}")->field('food_category_name,food_category_id')->select();
                 $cateData = [];
                foreach ($categoryData as $keys => $values) {
                    if ($values['food_category_id'] = $value['food_category_id']) {
                        $cateData[] = $values['food_category_name'];
                    }
                }
            }
                $food_list[$k]['cateData'] = implode(',', $cateData);
        }
                 $this->assign('info',$food_list);
                 $this->display('showfoodinfo');
            }else{
                $map['food_category_id'] = $key;
                /*$count = $relative->where($map)->count();
                $p = I("get.page") ? I("get.page") : 1;
                $pageNum = 8;
                $Page  = new \Think\PageAjax($count,$pageNum);// 实例化分页类 传入总记录数和每页显示的记录数
                $show = $Page->show();// 分页显示输出
                $this->assign('page',$show);// 赋值分页输出
                $arr = $relative->where($map)->page($p,$pageNum)->order('sort asc')->select();*/
                $arr = $relative->where($map)->order('sort asc')->select();
                $food_list = array();
                $foodModel = D('food');
		            foreach ($arr as $v) {
		                $condition1['food_id'] = $v['food_id'];
		                $food_category_id = $v['food_category_id'];
		                $food_info = $foodModel->where($condition1)->find(); //查出的是个对像
		                $food_type = $dishes->where("food_category_id = $food_category_id")->field("food_category_name")->find()['food_category_name'];
		                $food_info['id'] = $v['id'];
		                $food_info['food_category_id'] = $v['food_category_id'];
		                $food_info['food_category_name'] = $food_type;
		                $food_list[] = $food_info;
	            	}

                $sortArr = array();
                foreach($food_list as $v1){
                    $sortArr[] = $v1['sort'];
                }
                array_multisort($sortArr, SORT_ASC, $food_list);
                    foreach ($food_list as $k => $v) {
            $food_category_id = $relative->where("food_id = {$v['food_id']}")->field('food_category_id,food_id')->select();
                 $cateData = [];
            foreach ($food_category_id as $key => $value) {
                $categoryData = $dishes->where("food_category_id={$value['food_category_id']}")->field('food_category_name,food_category_id')->select();
                foreach ($categoryData as $keys => $values) {
                    if ($values['food_category_id'] = $value['food_category_id']) {
                        $cateData[] = $values['food_category_name'];
                    }
                }
            }
                $food_list[$k]['cateData'] = implode(',', $cateData);
        }
            	$this->assign("info",$food_list);
   				$this->display("showfoodinfo1");
   				}
   		}	
   	}

    //数据上移
    public function moveup(){
        $food = D('food');
        $when_sort = I('post.sort');				 //当前排序ID
		$food_id = I('post.food_id');
        $map['sort'] = array('lt',I('post.sort'));   	
		$map['restaurant_id'] = session('restaurant_id');
        $last_sort = $food->where($map)->order('sort desc')->field('sort')->limit(1)->find()['sort']; 		//上一个排序ID
        // 比它前一个ID
        $last_id = $food->where($map)->order('sort desc')->field('food_id')->limit(1)->find()['food_id'];	//上一个自增ID
        if($last_sort>0){
            $newsort = $last_sort;							//新建第三个ID来存储上一个ID
            $last_sort = I('post.sort');					//上一个排序ID被赋值成当前排序ID
            // 比它前一个的ID就换成现在的ID
            $obj['sort'] = $last_sort;
			$obj['food_id'] = $last_id;
            $r = $food->save($obj);
            $when_sort = $newsort;							//将第三个排序ID值赋于当前ID
            $obj1['sort'] = $when_sort;
			$obj1['food_id'] = I('post.food_id');
            $r1 = $food->save($obj1);
			if($r && $r1){
				//$this->dishes_ajax();

                // 删除订单页缓存
                $dianpu_id = session("restaurant_id");
                @ unlink(HTML_PATH . "$dianpu_id/order.html");

				$msg['msg'] = "成功";
				$msg['code'] = 1;
				exit(json_encode($msg));
			}
        }
    }

	//数据下移
    public function movedown(){
        $food = D('food');
		$when_sort = I('post.sort');					//当前排序ID
		$food_id = I('post.food_id');					//当前自增ID
        $map['sort'] = array('Gt',I('post.sort'));   	//sort	大于传过来的sort		
		$map['restaurant_id'] = session('restaurant_id');
        $next_sort = $food->where($map)->order('sort asc')->field('sort')->limit(1)->find()['sort'];			//下一个排序ID
        $next_id = $food->where($map)->order('sort asc')->field('food_id')->limit(1)->find()['food_id'];		//下一个自增ID
        if($next_sort>0){
            $newsort = $next_sort;							//新建第三个ID来存储下一个排序ID
            $next_sort = I('post.sort');					//下一个排序ID被赋值为当前排序ID
            $obj['sort'] = $next_sort;
			$obj['food_id'] = $next_id;						//修改上一个sort
            $r = $food->save($obj);
            $when_sort = $newsort;							//将第三个ID值赋于当前ID
            $obj1['sort'] = $when_sort;
			$obj1['food_id'] = I('post.food_id');
            $r1 = $food->save($obj1);
			if($r && $r1){

                // 删除订单页缓存
                $dianpu_id = session("restaurant_id");
                @ unlink(HTML_PATH . "$dianpu_id/order.html");

				$msg['msg'] = "成功";
				$msg['code'] = 1;
				exit(json_encode($msg));
			}
        }
	}
	

	//菜品分类数据上移
    public function moveup1(){
    	//dump("进来了");
        $food_category = D('food_category');
        $condition['sort'] = I('post.sort');
		$condition['restaurant_id'] = session('restaurant_id');
        $dataOri = $food_category->where($condition)->order('sort desc')->field('sort')->limit(1)->find()['sort'];
		///dump($dataOri);
		$food_category_id = I('post.food_category_id');
       //id<传过来的ID
        $map['sort'] = array('lt',I('post.sort'));   //sort	小于传过来的sort	
		$map['restaurant_id'] = session('restaurant_id');
		//dump($map);
        $data = $food_category->where($map)->order('sort desc')->field('sort')->limit(1)->find()['sort'];//点击当前上移ID的上一个ID
        //dump($data);
        $last_id = $food_category->where($map)->order('sort desc')->field('food_category_id')->limit(1)->find()['food_category_id'];
        if($data>0){
            $newsort = $data;//新建第三个ID来存储上一个ID
            $data = I('post.sort');
            $obj['sort'] = $data;
			$obj['food_category_id'] = $last_id;//修改上一个sort
            $r = $food_category->save($obj);
            $dataOri = $newsort;//将第三个ID值赋于当前ID
            $obj1['sort'] = $dataOri;
			$obj1['food_category_id'] = I('post.food_category_id');
            $r1 = $food_category->save($obj1);
			if($r && $r1){

                // 菜品分类上移，删除订单页缓存
                $dianpu_id = session("restaurant_id");
                @ unlink(HTML_PATH . "$dianpu_id/order.html");  // 删除订单页

                $where['restaurant_id'] = session("restaurant_id");
        		$arr = $food_category->where($where)->order('sort asc')->select();
				$this->assign("data",$arr);
				$this->display('showcategory');
			}
        }
    }
	

    
		
	 //菜品分类数据下移
    public function movedown1(){
        $food_category = D('food_category');
        $condition['sort'] = I('post.sort');
		$condition['restaurant_id'] = session('restaurant_id');
        $dataOri = $food_category->where($condition)->field('sort')->limit(1)->find()['sort'];
		$food_category_id = I('post.food_category_id');
       //id<传过来的ID
        $map['sort'] = array('Gt',I('post.sort'));   //sort	小于传过来的sort		
		$map['restaurant_id'] = session('restaurant_id');
        $data = $food_category->where($map)->order('sort asc')->field('sort')->limit(1)->find()['sort'];//点击当前上移ID的上一个ID
        $next_id = $food_category->where($map)->order('sort asc')->field('food_category_id')->limit(1)->find()['food_category_id'];
        if($data>0){
            $newsort = $data;//新建第三个ID来存储上一个ID
            $data = I('post.sort');
            $obj['sort'] = $data;
			$obj['food_category_id'] = $next_id;//修改上一个sort
            $r = $food_category->save($obj);
            $dataOri = $newsort;//将第三个ID值赋于当前ID
            $obj1['sort'] = $dataOri;
			$obj1['food_category_id'] = I('post.food_category_id');
            $r1 = $food_category->save($obj1);
			if($r && $r1){

                // 删除订单页缓存
                $dianpu_id = session("restaurant_id");
                @ unlink(HTML_PATH . "$dianpu_id/order.html");

				$where['restaurant_id'] = session("restaurant_id");
        		$arr = $food_category->where($where)->order('sort asc')->select();
				$this->assign("data",$arr);
				$this->display('showcategory');
			}
			//exit(json_encode($msg));
        }
	}

	//菜品第三表数据上移
	public function moveup2(){
		$relative = D('food_category_relative');       
        $map['food_category_id'] = I('get.food_category_id');
		$relativeArr = $relative->where($map)->select();
		$food = D('food');
        $dishes = D('food_category');
		$food_list = array();
        foreach($relativeArr as $v){
            $condition['food_id'] = $v['food_id'];
            $food_category_id = $v['food_category_id'];
            $food_info = $food->where($condition)->find(); 
            $food_type = $dishes->where($map)->field("food_category_name")->find()['food_category_name'];
            $food_info['id'] = $v['id'];
            $food_info['food_category_id'] = $v['food_category_id'];
            $food_info['food_category_name'] = $food_type;
            $food_list[] = $food_info;
        }
		$sortArr = array();
		foreach($food_list as $v1){
			$sortArr[] = $v1['sort'];
		}
		
		array_multisort($sortArr, SORT_ASC, $food_list);
		$foodIdtArr = array();
		foreach($food_list as $v2){
			$foodIdtArr[] = $v2['food_id'];
		}
		sort($sortArr);
        $when_sort = I('get.when_sort');				 //当前排序ID
        $when_food_id = I('get.when_food_id');			 //当前自增ID
        $Key = array_search($when_sort,$sortArr);
		$Key1 = array_search($when_food_id,$foodIdtArr);
		$last_sort = $sortArr[$Key-1];					 //上一个排序ID		
		$last_food_id = $foodIdtArr[$Key1-1];			 //上一个自增ID
        if($last_sort>0){
            $newsort = $last_sort;						 //新建第三个ID来存储上一个ID
            $last_sort = $when_sort;					 //上一个排序ID被赋值成当前排序ID
            $obj['sort'] = $last_sort;
			$obj['food_id'] = $last_food_id;
            $r = $food->save($obj);						 //修改上一条数据排序
            $when_sort = $newsort;						 //将第三个排序ID值赋于当前ID
            $obj1['sort'] = $when_sort;
			$obj1['food_id'] = $when_food_id;
            $r1 = $food->save($obj1);
			if($r && $r1){

                // 删除分类ID缓存页
                $dianpu_id = session("restaurant_id");
                $food_category_id = I('get.food_category_id');
                @ unlink(HTML_PATH . "$dianpu_id/orderAjax".$food_category_id.".html");

				$msg['msg'] = "成功";
				$msg['code'] = 1;
				exit(json_encode($msg));
			}
        }
	}

	//菜品第三表数据下移
	public function movedown2(){
		$relative = D('food_category_relative');       
        $map['food_category_id'] = I('get.food_category_id');
		$relativeArr = $relative->where($map)->select();
		$food = D('food');
        $dishes = D('food_category');
		$food_list = array();
        foreach($relativeArr as $v){
            $condition['food_id'] = $v['food_id'];
            $food_category_id = $v['food_category_id'];
            $food_info = $food->where($condition)->find(); 
            $food_type = $dishes->where($map)->field("food_category_name")->find()['food_category_name'];
            $food_info['id'] = $v['id'];
            $food_info['food_category_id'] = $v['food_category_id'];
            $food_info['food_category_name'] = $food_type;
            $food_list[] = $food_info;
        }
		$sortArr = array();
		foreach($food_list as $v1){
			$sortArr[] = $v1['sort'];
		}
		
		array_multisort($sortArr, SORT_ASC, $food_list);
		$foodIdtArr = array();
		foreach($food_list as $v2){
			$foodIdtArr[] = $v2['food_id'];
		}
		sort($sortArr);
        $when_sort = I('get.when_sort');				 //当前排序ID
        $when_food_id = I('get.when_food_id');			 //当前自增ID
        $Key = array_search($when_sort,$sortArr);
		$Key1 = array_search($when_food_id,$foodIdtArr);
		$next_sort = $sortArr[$Key+1];					 //下一个排序ID		
		$next_food_id = $foodIdtArr[$Key1+1];			 //下一个自增ID
        if($next_sort>0){
            $newsort = $next_sort;						 //新建第三个ID来存储上一个ID
            $next_sort = $when_sort;					 //下一个排序ID被赋值成当前排序ID
            $obj['sort'] = $next_sort;
			$obj['food_id'] = $next_food_id;
            $r = $food->save($obj);						 //修改上一条数据排序
            $when_sort = $newsort;						 //将第三个排序ID值赋于当前ID
            $obj1['sort'] = $when_sort;
			$obj1['food_id'] = $when_food_id;
            $r1 = $food->save($obj1);
			if($r && $r1){

                // 删除分类ID缓存页
                $dianpu_id = session("restaurant_id");
                $food_category_id = I('get.food_category_id');
                @ unlink(HTML_PATH . "$dianpu_id/orderAjax".$food_category_id.".html");

				$msg['msg'] = "成功";
				$msg['code'] = 1;
				exit(json_encode($msg));
			}
        }
	}


}