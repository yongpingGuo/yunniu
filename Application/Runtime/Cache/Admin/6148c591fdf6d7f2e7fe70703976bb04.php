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
            
    <ul class="nav nav-tabs">
        <li class="active">
            <a href="#home" data-toggle="tab">{{langData.orderProcess[lang]}}</a>
        </li>
        <li>
            <a href="#ad" data-toggle="tab">{{langData.advertiseSetting[lang]}}</a>
        </li>
    </ul>
    <div class="tab-content">
        <div class="tab tab-pane in active" id="home">
            <!-- 点餐流程选择 start -->
            <section class="section">
                <div class="section-header">
                    <span>{{langData.orderProcess[lang]}}</span>
                    <span class="section-tips"><{{langData.orderProcessTips[lang]}}></span>
                </div>
                <div class="section-content">
                    <!-- <div class="order-process flex-content" id="diancan">
                        <?php if(is_array($info2)): $i = 0; $__LIST__ = $info2;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v2): $mod = ($i % 2 );++$i;?><div class="flex-main process-item">
                                <div class="flex-content process-item-header">
                                    <span class="flex-main"><?php echo ($v2["process_name"]); ?></span>
                                    <?php if($v2['process_id'] == 3): elseif($v2['process_id'] == 5): ?>
                                        <?php else: ?>
                                        <?php if($v2["process_status"] == 1): ?><div class="checkbox-switch">
                                                <input type="checkbox" name="adPage<?php echo ($v2["process_id"]); ?>" class="adPage<?php echo ($v2["process_id"]); ?>" onchange="changestatu(this,<?php echo ($v2["process_id"]); ?>)" checked>
                                                <label></label>
                                            </div>
                                            <?php else: ?>
                                            <div class="checkbox-switch">
                                                <input type="checkbox" name="adPage<?php echo ($v2["process_id"]); ?>" class="adPage<?php echo ($v2["process_id"]); ?>" onchange="changestatu(this,<?php echo ($v2["process_id"]); ?>)">
                                                <label></label>
                                            </div><?php endif; endif; ?>
                                </div>
                                <img src="<?php echo ($v2["process_img"]); ?>">
                                <?php if($v2['process_id'] == 1): ?><div class="section-tips">* {{langData.adPageTips[lang]}}</div>
                                    <?php elseif($v2['process_id'] == 2): ?>
                                    <div class="section-tips">* {{langData.eatWayTips[lang]}}</div>
                                    <?php elseif($v2['process_id'] == 4): ?>
                                    <div class="section-tips">* {{langData.DeliveryMode[lang]}}</div>
                                    <?php else: ?>
                                    <div class="section-tips"> </div><?php endif; ?>
                            </div><?php endforeach; endif; else: echo "" ;endif; ?>
                    </div> -->
                    <div id="diancan" class="order-process flex-content">
                        <div class="flex-main process-item">
                            <div class="flex-content process-item-header">
                                <span class="flex-main">{{langData.adPage[lang]}}</span>
                                <!-- 判断-->
                                <?php if($info2['0']['process_status'] == 1): ?><div class="checkbox-switch">
                                    <input type="checkbox" name="adPage1" onchange="changestatu(this,1)"  class="adPage1"  checked>
                                    <label></label>
                                </div>
                                <?php else: ?>
                                    <div class="checkbox-switch">
                                        <input type="checkbox" name="adPage1" onchange="changestatu(this,1)" class="adPage1">
                                        <label></label>
                                    </div><?php endif; ?>
                            </div> 
                            <img src="/Public/images/adPage.png">
                            <div class="section-tips">* {{langData.adPageTips[lang]}}</div>
                        </div>
                        <div class="flex-main process-item">
                            <div class="flex-content process-item-header">
                                <span class="flex-main">{{langData.packageHall[lang]}}</span>
                                <!-- 判断-->
                                <?php if($info2['1']['process_status'] == 1): ?><div class="checkbox-switch">
                                    <input type="checkbox" name="adPage2" onchange="changestatu(this,2)"  class="adPage2"  checked>
                                    <label></label>
                                </div>
                                <?php else: ?>
                                    <div class="checkbox-switch">
                                        <input type="checkbox" name="adPage2" onchange="changestatu(this,2)" class="adPage2">
                                        <label></label>
                                    </div><?php endif; ?>
                            </div> 
                            <img src="/Public/images/eatPage.png">
                            <div class="section-tips">* {{langData.eatWayTips[lang]}}</div>
                        </div>
                        <div class="flex-main process-item">
                            <div class="flex-content process-item-header">
                                <span class="flex-main">{{langData.orderPage[lang]}}</span>
                            </div> 
                            <img src="/Public/images/orderPage.png">
                            <div class="section-tips"></div>
                        </div>
                        <div class="flex-main process-item">
                            <div class="flex-content process-item-header">
                                <span class="flex-main">{{langData.numberPage[lang]}}</span>
                                <!-- 判断-->
                                <?php if($info2['3']['process_status'] == 1): ?><div class="checkbox-switch">
                                    <input type="checkbox" name="adPage4" onchange="changestatu(this,4)" class="adPage4" checked>
                                    <label></label>
                                </div>
                                <?php else: ?>
                                    <div class="checkbox-switch">
                                        <input type="checkbox" name="adPage4" onchange="changestatu(this,4)" class="adPage4">
                                        <label></label>
                                    </div><?php endif; ?>
                            </div> 
                            <img src="/Public/images/numberPage.png">
                            <div class="section-tips">* {{langData.DeliveryMode[lang]}}</div>
                        </div>
                        <div class="flex-main process-item">
                            <div class="flex-content process-item-header">
                                <span class="flex-main">{{langData.paymentPage[lang]}}</span>
                            </div> 
                            <img src="/Public/images/payPage.png">
                            <div class="section-tips"></div>
                        </div>
                    </div>
                    <div>
                        <span class="text-danger">*</span>
                        <span>{{langData.OrdersuccessfulHint[lang]}}</span>
                        <input type="text" name="advlang" value="<?php echo ($info4); ?>" onchange="changeadvlang()" class="larger-input">
                    </div>
                </div>
            </section>
            <!-- 点餐流程选择 end -->
        </div>
        <div class="tab tab-pane" id="ad">
            <!-- 横屏点餐机广告 start -->
            <section class="section">
                <div class="section-header">
                    <span>{{langData.horizontalAD[lang]}}</span>
                    <span class="section-tips">{{langData.horizontalAdClaim[lang]}}</span>
                </div>
                <div class="section-content">
                    <div class="clearfix" id="mytr">
                        <?php if(is_array($info)): $i = 0; $__LIST__ = array_slice($info,0,1,true);if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v): $mod = ($i % 2 );++$i;?><div class="showImg pull-left">
                                <div class="imgHorizontal" id="<?php echo ($v["advertisement_id"]); ?>">
                                    <img src="/<?php echo ($v["advertisement_image_url"]); ?>" class="uploadImg">
                                    <input type="file" name="default" onchange="preview1(this)">
                                </div>
                            </div><?php endforeach; endif; else: echo "" ;endif; ?>
                        <?php if(is_array($info)): $i = 0; $__LIST__ = array_slice($info,1,null,true);if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v): $mod = ($i % 2 );++$i;?><div class="showImg pull-left">
                                <div class="imgHorizontal" id="<?php echo ($v["advertisement_id"]); ?>">
                                    <img src="/<?php echo ($v["advertisement_image_url"]); ?>" class="uploadImg">
                                    <button class="delete-btn" onclick="deladver(<?php echo ($v["advertisement_id"]); ?>)">
                                        <img src="/Public/images/delete.png">
                                    </button>
                                    <input type="file" name="change" onchange="preview1(this)">
                                </div>
                            </div><?php endforeach; endif; else: echo "" ;endif; ?>
                        <div class="showImg pull-left">
                            <div class="imgHorizontal">
                                <img src="/Public/images/add.png" class="uploadImg" data-img="add">
                                <input type="file" name="change" onchange="preview1(this)">
                            </div>
                        </div>
                    </div>
                    <div>
                        <span>{{langData.pictureIntervals[lang]}}</span>
                        <input type="text" value="<?php echo ($info3); ?>" onchange="changetime()" id="interval" class="mini-input">
                        <span>{{langData.seconds[lang]}}</span>
                    </div>
                </div>
            </section>
            <!-- 横屏点餐机广告 end -->
            <!-- 竖屏点餐机广告 start -->
            <section class="section">
                <div class="section-header">
                    <span>{{langData.VerticalAD[lang]}}</span>
                    <span class="section-tips">{{langData.VerticalAdClaim[lang]}}{{langData.deviceAdClaim[lang]}}</span>
                </div>
                <div class="section-content">
                    <!-- 竖屏广告 start -->
                    <div class="clearfix" id="mytr1">
                        <?php if(is_array($info1)): $i = 0; $__LIST__ = array_slice($info1,0,1,true);if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v1): $mod = ($i % 2 );++$i;?><div class="showImg pull-left">
                                <div class="imgVertical" id="<?php echo ($v1["advertisement_id"]); ?>">
                                    <img src="/<?php echo ($v1["advertisement_image_url"]); ?>" class="uploadImg">
                                    <input type="file" name="default" onchange="preview(this)">
                                </div>
                            </div><?php endforeach; endif; else: echo "" ;endif; ?>
                        <?php if(is_array($info1)): $i = 0; $__LIST__ = array_slice($info1,1,null,true);if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v1): $mod = ($i % 2 );++$i;?><div class="showImg pull-left">
                                <div class="imgVertical" id="<?php echo ($v1["advertisement_id"]); ?>">
                                    <img src="/<?php echo ($v1["advertisement_image_url"]); ?>" class="uploadImg">
                                    <button class="delete-btn" onclick="deladver1(<?php echo ($v1["advertisement_id"]); ?>)">
                                        <img src="/Public/images/delete.png">
                                    </button>
                                    <input type="file" name="change" onchange="preview(this)">
                                </div>
                            </div><?php endforeach; endif; else: echo "" ;endif; ?>
                        <div class="showImg pull-left">
                            <div class="imgVertical">
                                <img src="/Public/images/add_vertical.png" class="uploadImg" data-img="add">
                                <input type="file" name="change" onchange="preview(this)">
                                </a>
                            </div>
                        </div>
                    </div>
                    <!-- 竖屏广告 start -->
                    <div>
                        <span class="text-danger">*</span>
                        <span>{{langData.verticalScreenSlogan[lang]}}</span>
                        <input type="text" name="shuping_adv_lang" value="<?php echo ($info5); ?>" onchange="changeShuPingLang()" class="larger-input" :placeholder="langData.verticalScreenSlogan[lang]">
                    </div>
                </div>
            </section>
            <!-- 双屏客显广告 start -->
            <section class="section">
                <div class="section-header">
                    <span>{{langData.doubleScreenAds[lang]}}</span>
                    <span class="section-tips">{{langData.doubleAdClaim[lang]}}</span>
                </div>
                <div class="section-content">
                    <div class="clearfix" id="mytr88">
                        <?php if(is_array($doubleDisplay)): $i = 0; $__LIST__ = array_slice($doubleDisplay,0,1,true);if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v1): $mod = ($i % 2 );++$i;?><div class="showImg pull-left">
                                <div class="imgRectangle" id="<?php echo ($v1["advertisement_id"]); ?>">
                                    <img src="/<?php echo ($v1["advertisement_image_url"]); ?>" class="uploadImg">
                                    <input type="file" name="default" onchange="doubleDisplay(this)">
                                </div>
                            </div><?php endforeach; endif; else: echo "" ;endif; ?>
                        <?php if(is_array($doubleDisplay)): $i = 0; $__LIST__ = array_slice($doubleDisplay,1,null,true);if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v1): $mod = ($i % 2 );++$i;?><div class="showImg pull-left">
                                <div class="imgRectangle" id="<?php echo ($v1["advertisement_id"]); ?>">
                                    <img src="/<?php echo ($v1["advertisement_image_url"]); ?>" class="uploadImg">
                                    <button class="delete-btn" onclick="deladver88(<?php echo ($v1["advertisement_id"]); ?>)">
                                        <img src="/Public/images/delete.png">
                                    </button>
                                    <input type="file" name="change" onchange="doubleDisplay(this)">
                                </div>
                            </div><?php endforeach; endif; else: echo "" ;endif; ?>
                        <div class="showImg pull-left">
                            <div class="imgRectangle">
                                <img src="/Public/images/addRectangle.png" class="uploadImg" data-img="add">
                                <input type="file" name="change" onchange="doubleDisplay(this)">
                                </a>
                            </div>
                        </div>
                    </div>
                    <div>
                        <span class="text-danger">*</span>
                        <span>{{langData.doubleScreenSlogan[lang]}}:</span>
                        <input type="text" name="double_display" value="<?php echo ($info6); ?>" onchange="changeDoubleLang()" class="larger-input">
                    </div>
                </div>
            </section>
        </div>
    </div>

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
    
    <script type="text/javascript" src="/Public/js/Admin/Moudle_index.js?20171022"></script>

</body>

</html>