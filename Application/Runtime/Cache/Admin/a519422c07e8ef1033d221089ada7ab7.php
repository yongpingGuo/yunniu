<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <!-- Bootstrap 核心 CSS 文件 -->
    <link rel="stylesheet" href="/Public/bootstrap/css/bootstrap.min.css">
    <!-- 自定义css样式表 -->
    
    <!-- admin CSS 文件 -->
    <link rel="stylesheet" href="/Public/css/base.css?v=20180428">
    <link rel="stylesheet" href="/Public/css/admin.css?v=20180719">
    <title>餐饮店云管理</title>
</head>

<body>
    <div id="lang-content" class="h100" v-cloak>
        <div class="main-content">
            
    <form id="search_form" action="/index.php/Admin/Dishes/excel_out2" method="post"><form/>
    <section class="section">
        <div class="section-header">{{langData.foodInfoImportExport[lang]}}</div>
        <div class="section-content">
            <p>
                <button class="file-content blue-btn">
                    <span>{{langData.import[lang]}}</span>
                    <img src="/Public/images/in.png" class="mini-icon">
                    <input type="file" id="myfile">
                </button>
                <button type="button" class="blue-btn" id="getdata" onclick="exportway()">
                    <span>{{langData.export[lang]}}</span>
                    <img src="/Public/images/out.png" class="mini-icon">
                </button>
            </p>
            <p>{{langData.menuModificationInstructions[lang]}}:</p>
            <table class="table-condensed table-bordered text-small">
                <tr>
                    <td>food_id</td>
                    <td class="text-danger">{{langData.food_id[lang]}}</td>
                </tr>
                <tr>
                    <td>food_name</td>
                    <td>{{langData.food_name[lang]}}</td>
                </tr>
                <tr>
                    <td>food_name_en</td>
                    <td>{{langData.food_name_en[lang]}}</td>
                </tr>
                <tr>
                    <td>time_category</td>
                    <td>{{langData.time_category[lang]}}</td>
                </tr>
                <tr>
                    <td>discount</td>
                    <td>{{langData.second_price[lang]}}</td>
                </tr>
                <tr>
                    <td>food_price</td>
                    <td>{{langData.food_price[lang]}}</td>
                </tr>
                <tr>
                    <td>star_level</td>
                    <td>{{langData.star_level[lang]}}</td>
                </tr>
                <tr>
                    <td>hot_level</td>
                    <td>{{langData.hot_level[lang]}}</td>
                </tr>
                <tr>
                    <td>is_prom</td>
                    <td>{{langData.is_prom[lang]}}</td>
                </tr>
                <tr>
                    <td>is_tax</td>
                    <td>{{langData.is_tax[lang]}}</td>
                </tr>
                <tr>
                    <td>foods_num_day</td>
                    <td>{{langData.foods_num_day[lang]}}</td>
                </tr>
                <tr>
                    <td>food_desc</td>
                    <td>{{langData.food_desc[lang]}}</td>
                </tr>
                <tr>
                    <td>restaurant_id</td>
                    <td>{{langData.restaurant_id[lang]}}</td>
                </tr>
                <tr>
                    <td>is_sale</td>
                    <td>{{langData.is_sale[lang]}}</td>
                </tr>
                <tr>
                    <td>print_id</td>
                    <td>{{langData.print_id[lang]}}</td>
                </tr>
                <tr>
                    <td>sort</td>
                    <td>{{langData.sort[lang]}}</td>
                </tr>
                <tr>
                    <td>district_id</td>
                    <td>{{langData.district_id[lang]}}</td>
                </tr>
                <tr>
                    <td>tag_print_id</td>
                    <td>{{langData.tag_print_id[lang]}}</td>
                </tr>
                <tr>
                    <td>dianzan</td>
                    <td>{{langData.dianzan[lang]}}</td>
                </tr>
                <tr>
                    <td>category_names</td>
                    <td>{{langData.category_names[lang]}}</td>
                </tr>
                <tr>
                    <td>attribute_type_str</td>
                    <td>{{langData.attribute_type_str[lang]}}</td>
                </tr>
                <tr>
                    <td>print_id_str</td>
                    <td>{{langData.print_id_str[lang]}}</td>
                </tr>
                <tr>
                    <td>select_type_str</td>
                    <td>{{langData.select_type_str[lang]}}</td>
                </tr>
                <tr>
                    <td>count_type_str</td>
                    <td>{{langData.count_type_str[lang]}}</td>
                </tr>
                <tr>
                    <td>tag_print_id_str</td>
                    <td>{{langData.tag_print_id_str[lang]}}</td>
                </tr>
                <tr>
                    <td>attribute_val</td>
                    <td>{{langData.attribute_val[lang]}}</td>
                </tr>
                <tr>
                    <td>food_img</td>
                    <td>{{langData.food_img[lang]}}</td>
                </tr>
                <tr>
                    <td>img_urls</td>
                    <td>{{langData.img_urls[lang]}}</td>
                </tr>
                <tr>
                    <td>ico_category_types</td>
                    <td>{{langData.ico_category_types[lang]}}</td>
                </tr>
            </table>
        </div>
    </section>

        </div>
        
        
    </div>
    <script src="/Public/js/vue.js"></script>
    <script src="/Public/language.json?v=20180428"></script>
    <script src="/Public/js/jquery-3.1.0.min.js"></script>
    <script src="/Public/bootstrap/js/bootstrap.min.js"></script>
    <script src="/Public/layer/layer.js"></script>
    <script src="/Public/js/Admin/common.js"></script>
    
        <script type="text/javascript">
        var vm = new Vue({
            el: "#lang-content",
            data: {
                lang: language,
                langData: langData
            }
        })
        </script>
    
    <!-- 自定义js -->
    
<script>
function exportway() {
    $("#search_form").attr('action', '/index.php/Admin/Dishes/excel_out2');
    $("#search_form").submit();
}

$("input[type='file']").change(function() {
    var formData = new FormData();
    formData.append("myfile", $(this)[0].files[0]);
    console.log($(this)[0].files[0]);
    $.ajax({
        url: "/index.php/Admin/Dishes/excel_in",
        type: "POST",
        data: formData,
        //              data:$(this)[0].files[0],
        /**
         *必须false才会自动加上正确的Content-Type
         */
        contentType: false,
        /*
         * 必须false才会避开jQuery对 formdata 的默认处理
         * XMLHttpRequest会对 formdata 进行正确的处理
         */
        processData: false,
        beforeSend: function() {
            layer.open({
                type: 3,
                icon: 2,
                skin: "loading"
            });
        },
        complete: function() {
            layer.closeAll('loading');
        },
        success: function(data) {
            if (data.code == 0) {
                layer.msg(vm.langData.success[vm.lang]);
            } else {
                layer.msg(data.msg);

            }

        }
    });

});
</script>

</body>

</html>