<?php if (!defined('THINK_PATH')) exit();?><div class="modal-content">
    <img src="/<?php echo ($info3["food_img"]); ?>" class="modal-dish-icon">
    <div class="modal-dish-info flex-content vertical-flex">
        <div class="flex-main">
            <div class="modal-dish-name"><?php echo ($info3["food_name"]); ?></div>
            <div class="dish-price">
                <small>&yen;</small>
                <b class="price-num" id="food_price" data-food_price="<?php echo ($food_price); ?>"><?php echo ($food_price); ?></b>
            </div>
        </div>
        <div class="modal-dish-right text-right">
            <button class="plus-btn" onclick="decreaseNum(this)" data-food_id="<?php echo ($info3["food_id"]); ?>" data-food_price="<?php echo ($food_price); ?>">
                <i class="iconfont icon-minus"></i>
            </button>
            <b id = "food_num">1</b>
            <button class="plus-btn" onclick="increaseNum(this)" data-food_id="<?php echo ($info3["food_id"]); ?>" data-food_price="<?php echo ($food_price); ?>">
                <i class="iconfont icon-plus"></i>
            </button>
        </div>
    </div>
    <div class="modal-dish-describe"><?php echo ($info3["food_desc"]); ?></div>
    <form action="javascript:void(0)" id="attr_form">
        <?php if(is_array($at_list)): $i = 0; $__LIST__ = $at_list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$at_vo): $mod = ($i % 2 );++$i; if($at_vo['select_type'] == 0): ?><ul class="attr-list clearfix" id="first">
                <li class="attr-item-name"><?php echo ($at_vo["type_name"]); ?></li>
                <!--<li class="attr-select-item attr-sm attr-select">
                    <input type="radio">
                    <div class="attr-name">
                        <span>可乐</span>
                    </div>
                    <div>+0.00元</div>
                </li>
                <li class="attr-select-item attr-lg">
                    <input type="radio">
                    <div class="attr-name">
                        <span>雪碧</span>
                    </div>
                    <div>+0.00元</div>
                </li>-->
                <?php if(is_array($at_vo['attrs'])): $k = 0; $__LIST__ = $at_vo['attrs'];if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$ats_vo): $mod = ($k % 2 );++$k;?><!--<li class="attr-select-item <?php echo ($ats_vo["length_type"]); ?>" onclick="changePrice()">
                        <input type="radio" name = "radio<?php echo ($ats_vo["food_attribute_id"]); ?>"  value="<?php echo ($ats_vo["attribute_price"]); ?>" data-fd_at_id="<?php echo ($ats_vo["food_attribute_id"]); ?>" data-key = "<?php echo ($k); ?>"/>
                        <div class="attr-name">
                            <span><?php echo ($ats_vo["attribute_name"]); ?></span>
                        </div>
                        <div>+<?php echo ($ats_vo["attribute_price"]); ?>元</div>
                    </li>-->

                    <li class="attr-select-item attr-lg" onclick="changePrice()">
                        <input type="radio" name = "radio<?php echo ($ats_vo["food_attribute_id"]); ?>"  value="<?php echo ($ats_vo["attribute_price"]); ?>" data-fd_at_id="<?php echo ($ats_vo["food_attribute_id"]); ?>" data-key = "<?php echo ($k); ?>">
                        <div class="attr-name">
                            <span><?php echo ($ats_vo["attribute_name"]); ?></span>
                        </div>
                        <div>+<?php echo ($ats_vo["attribute_price"]); ?>元</div>
                    </li><?php endforeach; endif; else: echo "" ;endif; ?>
            </ul>
            <?php else: ?>
            <ul class="attr-list clearfix" id="third">
                <li class="attr-item-name"><?php echo ($at_vo["type_name"]); ?></li>
                <?php if(is_array($at_vo['attrs'])): $i = 0; $__LIST__ = $at_vo['attrs'];if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$ats_vo): $mod = ($i % 2 );++$i;?><!--<li class="attr-select-item <?php echo ($ats_vo["length_type"]); ?>" onclick="changePrice()">
                        <input type="radio" name = "radio<?php echo ($ats_vo["food_attribute_id"]); ?>"  value="<?php echo ($ats_vo["attribute_price"]); ?>" data-fd_at_id="<?php echo ($ats_vo["food_attribute_id"]); ?>" data-key = "<?php echo ($k); ?>"/>
                        <div class="attr-name">
                            <span><?php echo ($ats_vo["attribute_name"]); ?></span>
                        </div>
                        <div>+<?php echo ($ats_vo["attribute_price"]); ?>元</div>
                    </li>-->

                    <li class="attr-select-item attr-sm" onclick="changePrice()">
                        <input type="checkbox" name = "checkbox<?php echo ($ats_vo["food_attribute_id"]); ?>"  value="<?php echo ($ats_vo["attribute_price"]); ?>" data-fd_at_id="<?php echo ($ats_vo["food_attribute_id"]); ?>">
                        <div class="attr-name">
                            <span><?php echo ($ats_vo["attribute_name"]); ?></span>
                        </div>
                        <div>+<?php echo ($ats_vo["attribute_price"]); ?>元</div>
                    </li><?php endforeach; endif; else: echo "" ;endif; ?>
            </ul><?php endif; endforeach; endif; else: echo "" ;endif; ?>
    </form>
</div>
<div class="modal-bottom flex-content">
    <button class="flex-main" type="button" data-dismiss="modal"><span>&lt;&nbsp;</span>返回</button>
    <button class="flex-main" type="button" id="food-checked" data-single_price="<?php echo ($food_price); ?>" data-attrs="" data-food_name="<?php echo ($info3["food_name"]); ?>" data-food_id="<?php echo ($info3["food_id"]); ?>" onclick="addOrderItem(this)">确认</button>
</div>

    <script type="text/javascript" src="/Public/js/Mobile/food_detail.js?v20171021"></script>