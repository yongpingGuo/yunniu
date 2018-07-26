<?php if (!defined('THINK_PATH')) exit();?><table class="dishes-classify-table">
    <?php if(is_array($data)): $i = 0; $__LIST__ = $data;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v): $mod = ($i % 2 );++$i;?><tr>
            <td class="text-right"><?php echo ($key+1); ?></td>
            <td>
                <button class="rank-up" data-sort="<?php echo ($v["sort"]); ?>" data-food_category_id="<?php echo ($v["food_category_id"]); ?>" onclick="moveup1(this)"></button>
                <button class="rank-down movedown" data-sort="<?php echo ($v["sort"]); ?>" data-food_category_id="<?php echo ($v["food_category_id"]); ?>" onclick="movedown1(this)"></button>
            </td>
            <td>
                <button data-id="<?php echo ($v["food_category_id"]); ?>" onclick="showinfo(this)"><?php echo ($v['food_category_name']); ?></button>
                <br/><?php echo ($v["food_category_name_en"]); ?>
            </td>
            <td class="text-right">
                <button class="edit-btn" data-toggle="modal" data-target="#addSort" onclick="modify1(<?php echo ($v["food_category_id"]); ?>)" id="modify">
                </button>
                <button class="remove-btn" onclick="deltype(<?php echo ($v["food_category_id"]); ?>)"></button>
            </td>
        </tr><?php endforeach; endif; else: echo "" ;endif; ?>
</table>