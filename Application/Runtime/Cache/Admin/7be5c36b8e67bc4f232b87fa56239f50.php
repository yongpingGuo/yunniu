<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <!-- Bootstrap 核心 CSS 文件 -->
    <link rel="stylesheet" href="/Public/bootstrap/css/bootstrap.min.css">
    <!-- 自定义css样式表 -->
    
    <link rel="stylesheet" href="/Public/element-ui/lib/theme-default/index.css">

    <!-- admin CSS 文件 -->
    <link rel="stylesheet" href="/Public/css/base.css?v=20180428">
    <link rel="stylesheet" href="/Public/css/admin.css?v=20180719">
    <title>餐饮店云管理</title>
</head>

<body>
    <div id="lang-content" class="h100" v-cloak>
        <div class="main-content">
            
    <section class="section" v-show="!editStatus">
        <div class="section-header">{{langData.electronicMenu[lang]}}</div>
        <div class="section-content">
            <table class="table table-bordered">
                <tr>
                    <th></th>
                    <th class="text-center">{{langData.name[lang]}}</th>
                    <th class="text-center">MAC</th>
                    <th class="text-center">{{langData.dateOfExpiry[lang]}}</th>
                    <th colspan="2"></th>
                </tr>
                <tr v-for="(billBoard, index) in billBoards" :class="{'text-center':textCenter}">
                    <td>{{index+1}}</td>
                    <td>{{billBoard.bill_board_name}}</td>
                    <td v-html="billBoard.bill_board_code"></td>
                    <td v-html="myDate(billBoard.bb_end_time)"></td>
                    <td>
                        <button class="btn-success btn-sm">{{langData.renewFee[lang]}}</button>
                    </td>
                    <td>
                        <button class="edit-btn" @click="editBill(billBoard.bill_board_id)"></button>
                        <button class="remove-btn" @click="deleteBill(billBoard.bill_board_id,index)"></button>
                    </td>
                </tr>
            </table>           
        </div>
    </section>
    <div v-show="editStatus">
        <section class="section">
            <div class="section-header">{{langData.electronicMenuInfo[lang]}}</div>
            <div class="section-content">
                <div class="row">
                    <span class="col-xs-1">MAC:</span>
                    <span class="col-xs-8">{{mac}}</span>
                </div>
                <div class="row">
                    <span class="col-xs-1">{{langData.name[lang]}}:</span>
                    <div class="col-xs-8">
                        <input type="text" v-model="current_bill_board_name">
                        <button class="blue-btn" @click="editBillBoardName">{{langData.save[lang]}}</button>
                    </div>
                </div>
            </div>
        </section>
        <section class="section billBoard">
            <div class="section-header">{{langData.switchOnOffTime[lang]}}</div>
            <div class="section-content">
                <table class="table table-bordered">
                    <tr>
                        <th>{{langData.bootTime[lang]}}</th>
                        <th>{{langData.shutdownTime[lang]}}</th>
                        <th>{{langData.selectAll[lang]}}</th>
                        <th>{{langData.Monday[lang]}}</th>
                        <th>{{langData.Tuesday[lang]}}</th>
                        <th>{{langData.Wednesday[lang]}}</th>
                        <th>{{langData.Thursday[lang]}}</th>
                        <th>{{langData.Friday[lang]}}</th>
                        <th>{{langData.Saturday[lang]}}</th>
                        <th>{{langData.Sunday[lang]}}</th>
                        <th>{{langData.available[lang]}}</th>
                        <th></th>
                    </tr>
                    <tr class="text-center" v-for="(item,index) in timers">
                        <td>
                            <el-time-select v-model="item.starting_time" placeholder="00:00"></el-time-select>
                        </td>
                        <td>
                            <el-time-select v-model="item.ending_time" placeholder="23:59"></el-time-select>
                        </td>
                        <td>
                            <el-checkbox v-model="item.all" @change="is_select_all(index)"></el-checkbox>
                        </td>
                        <td>
                            <el-checkbox v-model="item.oc_week[0]" @change="is_change($event,0,index)"></el-checkbox>
                        </td>
                        <td>
                            <el-checkbox v-model="item.oc_week[1]" @change="is_change($event,1,index)"></el-checkbox>
                        </td>
                        <td>
                            <el-checkbox v-model="item.oc_week[2]" @change="is_change($event,2,index)"></el-checkbox>
                        </td>
                        <td>
                            <el-checkbox v-model="item.oc_week[3]" @change="is_change($event,3,index)"></el-checkbox>
                        </td>
                        <td>
                            <el-checkbox v-model="item.oc_week[4]" @change="is_change($event,4,index)"></el-checkbox>
                        </td>
                        <td>
                            <el-checkbox v-model="item.oc_week[5]" @change="is_change($event,5,index)"></el-checkbox>
                        </td>
                        <td>
                            <el-checkbox v-model="item.oc_week[6]" @change="is_change($event,6,index)"></el-checkbox>
                        </td>
                        <td>
                            <el-checkbox v-model="item.is_use"></el-checkbox>
                        </td>
                        <td>
                            <button class="remove-btn" @click="deleteBillBoardTimer(index)"></button>
                        </td>
                    </tr>
                </table>
                <div class="text-right">
                    <button class="blue-btn" @click="addBillBoardTimer">{{langData.add[lang]}}</button>
                    <button class="blue-btn" @click="saveBillBoardTimer">{{langData.save[lang]}}</button>
                </div>
            </div>
        </section>
        <section class="section billBoard">
            <div class="section-header">{{langData.electronicMenuSet[lang]}}</div>
            <div class="section-content">
                <table class="w100 table-condensed text-center">
                    <tr>
                        <td colspan="2"></td>
                        <td>{{langData.startDate[lang]}}</td>
                        <td>{{langData.endDate[lang]}}</td>
                        <td>{{langData.week[lang]}}</td>
                        <td>{{langData.startTime[lang]}}</td>
                        <td>{{langData.endTime[lang]}}</td>
                        <td>{{langData.carouselTime[lang]}}(s)</td>
                        <td></td>
                    </tr>
                    <template v-for="(img_group,index_g) in op_img_groups">
                        <tr>
                            <td class="text-center" rowspan="2">{{index_g+1}}</td>
                            <td rowspan="2">
                                <button class="rank-up"></button>
                                <button class="rank-down"></button>
                            </td>
                            <td>
                                <el-date-picker v-model="img_group.starting_date" :clearable="false" :editable="false" type="date" :format="'yyyy-MM-dd'"></el-date-picker>
                            </td>
                            <td>
                                <el-date-picker v-model="img_group.ending_date" :clearable="false" :editable="false" type="date" :format="'yyyy-MM-dd'"></el-date-picker>
                            </td>
                            <td>
                                <el-select v-model="img_group.value" :size="'mini'" multiple>
                                    <el-option v-for="item2 in img_group.week" :label="item2.value|week" :value="item2.value">
                                    </el-option>
                                </el-select>
                            </td>
                            <td>
                                <el-time-select v-model="img_group.starting_time"></el-time-select>
                            </td>
                            <td>
                                <el-time-select v-model="img_group.ending_time"></el-time-select>
                            </td>
                            <td>
                                <input type="text" class="form-control" v-model="img_group.carousel_time">
                            </td>
                            <td rowspan="2">
                                <button class="remove-btn" @click="deleteImgGroup(img_group.bb_group_id,index_g)"></button>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="6">
                                <div class="showImg pull-left" v-for="(img,index) in img_group.bb_group_imgs">
                                    <div class="imgHorizontal">
                                        <img class="uploadImg" :src="img.img_url">
                                        <button @click="deleteImg(img.id,index,index_g)" class="delete-btn">
                                            <img src="/Public/images/delete.png">
                                        </button>
                                    </div>
                                </div>
                                <div class="showImg pull-left">
                                    <div class="imgHorizontal">
                                        <img class="uploadImg" src="/Public/images/add.png">
                                        <input type="file" name="img" @change="uploadImg($event,index_g)">
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </template>
                </table>
                <div class="text-right">
                    <button class="blue-btn" @click="addBillBoardImgGroup">{{langData.add[lang]}}</button>
                    <button class="blue-btn" @click="saveBillBoardImgGroup">{{langData.save[lang]}}</button>
                </div>
                <div class="section-tips">{{langData.electronicMenuTips[lang]}}</div>
            </div>
        </section>
    </div>

        </div>
        
        
    </div>
    <script src="/Public/js/vue.js"></script>
    <script src="/Public/language.json?v=20180428"></script>
    <script src="/Public/js/jquery-3.1.0.min.js"></script>
    <script src="/Public/bootstrap/js/bootstrap.min.js"></script>
    <script src="/Public/layer/layer.js"></script>
    <script src="/Public/js/Admin/common.js"></script>
    
    <script src="/Public/element-ui/lib/index.js"></script>
    <script src="/Public/js/vue-axios.js"></script>
    <script src="/Public/js/vue-router.js"></script>
    <script src="/Public/js/billboard.js"></script>

    <!-- 自定义js -->
    
</body>

</html>