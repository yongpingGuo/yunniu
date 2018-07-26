<?php if (!defined('THINK_PATH')) exit();?><div id="ajax-content">    
    <table class="dishes-list-table">
        <?php if(is_array($info)): $i = 0; $__LIST__ = $info;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v): $mod = ($i % 2 );++$i;?><tr>
                <td><?php echo ($key+1); ?></td>
                <td>
                    <button class="rank-up" data-index="<?php echo ($key+1); ?>" data-sort="<?php echo ($v["sort"]); ?>" data-food_id="<?php echo ($v["food_id"]); ?>" onclick="moveup(this)"></button>
                    <button class="rank-down movedown" data-index="<?php echo ($key+1); ?>" data-sort="<?php echo ($v["sort"]); ?>" data-food_id="<?php echo ($v["food_id"]); ?>" onclick="movedown(this)"></button>
                </td>
                <td>
                    <img src="/<?php echo ($v["food_img"]); ?>" class="dishes-list-img">
                </td>
                <td class="dishes-list-name">
                    <span><?php echo ($v["food_name"]); ?> <?php echo ($v["food_name_en"]); ?></span>
                    <div class="section-tips">{{langData.classification[lang]}}:<?php echo ($v["cateData"]); ?></div>
                </td>
                <td class="dishes-list-price"><?php echo ($v["food_price"]); ?>{{langData.yuan[lang]}}</td>
                <td class="dishes-list-star">
                    <span class="showStar">
                        <?php if($v["star_level"] == 1): endif; ?>
                        <?php if($v["star_level"] == 2): ?>★★<?php endif; ?>
                        <?php if($v["star_level"] == 3): ?>★★★<?php endif; ?>
                        <?php if($v["star_level"] == 4): ?>★★★★<?php endif; ?>
                        <?php if($v["star_level"] == 5): ?>★★★★★<?php endif; ?>
                    </span>
                        <?php if($v["hot_level"] == 0): endif; ?>
                        <?php if($v["hot_level"] == 1): ?><img src="/Public/images/cayenne.png" class="showCayenne"><?php endif; ?>
                        <?php if($v["hot_level"] == 2): ?><img src="/Public/images/cayenne.png" class="showCayenne"> <img src="/Public/images/cayenne.png" class="showCayenne"><?php endif; ?>
                        <?php if($v["hot_level"] == 3): ?><img src="/Public/images/cayenne.png" class="showCayenne"> <img src="/Public/images/cayenne.png" class="showCayenne"> <img src="/Public/images/cayenne.png" class="showCayenne"><?php endif; ?>

                </td>
                <td class="dishes-list-price"><?php echo ($v["foods_num_day"]); ?>{{langData.copies[lang]}}</td>
                <!-- <td>
                    <?php if(($v["is_prom"]) == "0"): ?><span>关闭</span>
                        <?php else: ?>
                        <span>开启</span><?php endif; ?>
                </td>
                
                <?php if(($v["is_sale"]) == "0"): ?><td>下架</td>
                    <?php else: ?>
                    <td>上架</td><?php endif; ?> -->
                <td class="text-right">
                    <div class="checkbox-switch">
                        <?php if(($v["is_sale"]) == "1"): ?><input type="checkbox" onclick="changestatu(<?php echo ($v["food_id"]); ?>)" checked="checked">
                            <?php else: ?>
                            <input type="checkbox" onclick="changestatu(<?php echo ($v["food_id"]); ?>)" ><?php endif; ?>
                        <label></label>
                    </div>
                </td>
                <td class="dishes-list-operation">
                    <button class="edit-btn" onclick="modify_food(this)" data-food_id="<?php echo ($v["food_id"]); ?>"></button>
                    <button class="remove-btn" onclick="delfoodinfo(<?php echo ($v["food_id"]); ?>)"></button>
                </td>
            </tr><?php endforeach; endif; else: echo "" ;endif; ?>
    </table>
    <div class="text-center">
        <ul class="pagination" id="detail-page" v-if="lang=='zh-CN'"><?php echo ($page1); ?></ul>
        <ul class="pagination" id="detail-page" v-if="lang=='zh-TW'"><?php echo ($page2); ?></ul>
        <ul class="pagination" id="detail-page" v-if="lang=='en'"><?php echo ($page3); ?></ul>
    </div>
</div>
<script src="/Public/js/vue.js"></script>
<script src="/Public/language.json"></script>
<script src="/Public/js/Admin/common.js"></script>
<script type="text/javascript">
new Vue({
    el: "#ajax-content",
    data: {
        lang: language,
        langData: langData
    }
})

//点击页码执行动作
$("#detail-page").children().children("a").click(function() {
    var page = parseInt($(this).data("page"));
    console.log(page);
    $.ajax({
        url: "/index.php/admin/Dishes/deskInfo/page/" + page + "",
        type: "get",
        success: function(data) {
            $("#mytr").html(data);
        }
    });

});


//数据上移
function moveup(obj) {
    var sort = $(obj).data('sort'); //排序ID
    var food_id = $(obj).data('food_id'); //菜品自增ID
    var page = parseInt($(".current").data('page')); //当前页数
    var when_tr = parseInt($(obj).parent().prev().text());
    var pageArr = new Array();
    $(".pagination").children().children('a').each(function(index, element) {
        var when_page = parseInt($(element).data('page'));
        pageArr[index] = when_page;
    });
    var max = pageArr[0]
    for (var i = 1; i < pageArr.length; i++) {
        if (pageArr[i] > max) {
            max = pageArr[i];
        }
    }
    console.log(max);

    var last_tr = $("#mytr").children().children('tr:last').children('td:first').text();
    console.log(last_tr);
    if (page == 1 && when_tr == 1) {
        return false;
    }
    $.ajax({
        type: "post",
        url: "/index.php/admin/dishes/moveup",
        data: {
            "sort": sort,
            "food_id": food_id
        },
        dataType: "json",
        success: function(data) {
            if (data.code == 1) {
                $.ajax({
                    url: "/index.php/admin/Dishes/deskInfo/page/" + page,
                    type: "get",
                    success: function(data) {
                        $("#mytr").html(data);
                    }
                });
            }
        }
    });
}

//数据下移
function movedown(obj) {
    var sort = $(obj).data('sort');
    var food_id = $(obj).data('food_id');
    var page = parseInt($(".current").html());
    var pageArr = new Array();
    $(".pagination").children().children('a').each(function(index, element) {
        var when_page = parseInt($(element).data('page'));
        pageArr[index] = when_page;
    });
    var max = pageArr[0]
    for (var i = 1; i < pageArr.length; i++) {
        if (pageArr[i] > max) {
            max = pageArr[i]; //获取最大页数
        }
    }
    var last_tr = $("#mytr").children().children('tr:last'); //获取最后一个tr
    var downObj = $(obj).parent(); //获取点击时的tr
    if (page == max && last_tr == downObj) { //如果当前页是最后一页且所点击的tr是最后一个tr，则中止操作
        return false;
    }
    $.ajax({
        type: "post",
        url: "/index.php/admin/dishes/movedown",
        data: {
            "sort": sort,
            "food_id": food_id
        },
        dataType: "json",
        success: function(data) {
            if (data.code == 1) {
                $.ajax({
                    url: "/index.php/admin/Dishes/deskInfo/page/" + page,
                    type: "get",
                    success: function(data) {
                        $("#mytr").html(data);
                    }
                });
            }
        }
    });
}
</script>