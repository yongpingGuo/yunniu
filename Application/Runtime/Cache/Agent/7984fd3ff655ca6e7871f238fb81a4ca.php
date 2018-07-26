<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="/Public/bootstrap/css/bootstrap.min.css">
    <script src="/Public/js/jquery-3.1.0.min.js"></script>
    <script src="/Public/bootstrap/js/bootstrap.min.js"></script>
    <script src="/Public/js/Agent/code.js"></script>
    <title></title>
</head>
<body>
<div class="container-fluid">
    <span>温馨提示：注册码关联店铺和设备，请谨慎修改绑定</span>
    <table class="table table-condensed">
        <thead>
        <tr>
            <th>序号</th><th>注册码</th><!--<th>开始时间</th><th>结束时间</th>--><th>状态</th><th>绑定店铺</th>
            <!--<th>操作</th>-->
        </tr>
        </thead>
        <tbody>
        <?php if(is_array($codeList)): $k = 0; $__LIST__ = $codeList;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($k % 2 );++$k;?><tr><td><?php echo ($k); ?></td><td><?php echo ($vo["code"]); ?></td><!--<td><?php echo ($vo['code_timestamp']); ?></td><td><?php echo ($vo['rest_timestamp']); ?></td>-->
                <td>
                    <?php if($vo["code_status"] == 0): ?>已使用
                        <?php else: ?>
                        待使用<?php endif; ?>
                </td>
                <td>
                    <select name="code_restaurant" id="code_restaurant" data-code_id="<?php echo ($vo["code_id"]); ?>" onchange="changeCodeRestaurant(this)">
                        <option value="">未绑定</option >
                        <?php if(is_array($restaurantList)): $n = 0; $__LIST__ = $restaurantList;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo1): $mod = ($n % 2 );++$n; if($vo['restaurant_id'] == $vo1['restaurant_id']): ?><option value="<?php echo ($vo1["restaurant_id"]); ?>" selected>
                                    <?php echo ($vo1["restaurant_name"]); ?>
                                </option>
                                <?php else: ?>
                                <option value="<?php echo ($vo1["restaurant_id"]); ?>">
                                    <?php echo ($vo1["restaurant_name"]); ?>
                                </option><?php endif; endforeach; endif; else: echo "" ;endif; ?>
                    </select>
                </td>
                <!--<td><button>修改</button></td>    -->
            </tr><?php endforeach; endif; else: echo "" ;endif; ?>  
        </tbody>
    </table>
     <div class="col-md-8"><?php echo ($page); ?></div>
</div>
</body>
</html>