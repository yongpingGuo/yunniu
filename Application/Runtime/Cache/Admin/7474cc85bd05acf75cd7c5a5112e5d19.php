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
            
    <!-- 叫号取餐屏广告 start -->
    <section class="section">
        <div class="section-header">
            <span>{{langData.eatingDinnerScreenAdvertising[lang]}}</span>
            <span class="section-tips">{{langData.VerticalAdClaim[lang]}}</span>
        </div>
        <div class="section-content">
            <div class="clearfix" id="mytr88">
                <?php if(is_array($info888)): $i = 0; $__LIST__ = array_slice($info888,0,1,true);if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v5): $mod = ($i % 2 );++$i;?><div class="showImg pull-left">
                        <div class="imgVertical" id="<?php echo ($v5["advertisement_id"]); ?>">
                            <img src="/<?php echo ($v5["advertisement_image_url"]); ?>" class="uploadImg">
                            <input type="file" name="default" onchange="uploadAd(this)">
                        </div>
                    </div><?php endforeach; endif; else: echo "" ;endif; ?>
                <?php if(is_array($info888)): $i = 0; $__LIST__ = array_slice($info888,1,null,true);if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v5): $mod = ($i % 2 );++$i;?><div class="showImg pull-left">
                        <div class="imgVertical" id="<?php echo ($v5["advertisement_id"]); ?>">
                            <img src="/<?php echo ($v5["advertisement_image_url"]); ?>" class="uploadImg">
                            <button class="delete-btn" onclick="deleteAd(<?php echo ($v5["advertisement_id"]); ?>)">
                                <img src="/Public/images/delete.png">
                            </button>
                            <input type="file" name="change" onchange="uploadAd(this)">
                        </div>
                    </div><?php endforeach; endif; else: echo "" ;endif; ?>
                <div class="showImg pull-left">
                    <div class="imgVertical">
                        <img src="/Public/images/add_vertical.png" class="uploadImg" data-img="add">
                        <input type="file" name="change" onchange="uploadAd(this)">
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- 叫号取餐屏广告 end -->
    <section class="section partition">
        <div class="section-header">
            <span>{{langData.divisionCalledScreenNumber[lang]}}</span>
        </div>
        <div class="section-content">
            <div id="app">
                <el-row>
                    <el-col>{{langData.partitionCalledScreenFeatures[lang]}}
                        <el-switch v-model="is_open" on-color="#13ce66" off-color="#ff4949" @change="openOrClose()">
                        </el-switch>
                    </el-col>
                </el-row>
                <el-row v-if="is_open">
                    <el-col>
                        <el-tabs v-model="activeName" @tab-click="handleClick">
                            <el-tab-pane :label="langData.callScreenEquipment[lang]" name="yell">
                                <el-row>
                                    <el-table :data="yellEquipment" style="width: 100%">
                                        <el-table-column prop="equipment_name" :label="langData.deviceName[lang]" width="180">
                                        </el-table-column>
                                        <el-table-column prop="equipment_code" :label="langData.machineCode[lang]" width="180">
                                        </el-table-column>
                                        <el-table-column prop="terminal_time" :label="langData.dateOfExpiry[lang]">
                                        </el-table-column>
                                        <el-table-column :label="langData.CorrespondingDiningArea[lang]">
                                            <template scope="scope">
                                                <el-select v-model="scope.row.district" clearable @change="updateDistricts(scope.row.equipment_id,scope.row.district)">
                                                    <el-option v-for="item in districts" :label="item.district_name" :value="item.district_id" :disabled="item.disabled">
                                                    </el-option>
                                                </el-select>
                                            </template>
                                        </el-table-column>
                                    </el-table>
                                </el-row>
                            </el-tab-pane>
                            <el-tab-pane :label="langData.verificationEquipment[lang]" name="cancel">
                                <el-row>
                                    <el-table :data="cancelEquipment" style="width: 100%">
                                        <el-table-column prop="equipment_name" :label="langData.deviceName[lang]" width="180">
                                        </el-table-column>
                                        <el-table-column prop="equipment_code" :label="langData.machineCode[lang]" width="180">
                                        </el-table-column>
                                        <el-table-column prop="terminal_time" :label="langData.dateOfExpiry[lang]">
                                        </el-table-column>
                                        <el-table-column :label="langData.correspondsCallScreen[lang]">
                                            <template scope="scope">
                                                <el-select v-model="scope.row.yell_equipment_id" clearable @change="changeYellCancelRelation(scope.row.equipment_id,scope.row.yell_equipment_id)">
                                                    <el-option v-for="item in yellEquipment" :label="item.equipment_name" :value="item.equipment_id" :disabled="item.disabled">
                                                    </el-option>
                                                </el-select>
                                            </template>
                                        </el-table-column>
                                    </el-table>
                                </el-row>
                            </el-tab-pane>
                            <el-tab-pane :label="langData.summaryCallNumberScreen[lang]" name="summary">
                                <el-row>
                                    <el-table :data="summaryEquipment" style="width: 100%">
                                        <el-table-column prop="equipment_name" :label="langData.deviceName[lang]" width="180">
                                        </el-table-column>
                                        <el-table-column prop="equipment_code" :label="langData.machineCode[lang]" width="180">
                                        </el-table-column>
                                        <el-table-column prop="terminal_time" :label="langData.dateOfExpiry[lang]">
                                        </el-table-column>
                                    </el-table>
                                </el-row>
                            </el-tab-pane>
                            <el-tab-pane :label="langData.diningArea[lang]" name="district">
                                <el-row :gutter="10">
                                    <el-col :span="16">
                                        <el-tag :key="tag" v-for="tag in districts" :closable="true" :close-transition="false" @close="handleClose(tag.district_id)">
                                            {{tag.district_name}}
                                        </el-tag>
                                    </el-col>
                                    <el-col :span="4">
                                        <el-input class="input-new-tag" v-model="inputValue" ref="saveTagInput" size="small" :placeholder="langData.pleaseEnterPartitionName[lang]">
                                        </el-input>
                                    </el-col>
                                    <el-col :span="4" class="text-right">
                                        <el-button class="button-new-tag" size="small" type="success" @click="addDistrict">{{langData.addPartition[lang]}}</el-button>
                                    </el-col>
                                </el-row>
                            </el-tab-pane>
                        </el-tabs>
                    </el-col>
                </el-row>
            </div>
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
    
    <script src="/Public/element-ui/lib/index.js"></script>
    <script src="/Public/js/vue-router.js"></script>
    <script src="/Public/js/vue-axios.js"></script>
    <script type="text/javascript" src="/Public/js/Admin/show_num_adv.js"></script>
    <script>
    Vue.prototype.$http = axios;

    var vm = new Vue({
        el: "#lang-content",
        data: {
            lang: language,
            langData: langData,
            activeName: "yell",
            inputVisible: false,
            summaryEquipment: [],
            yellEquipment: [],
            cancelEquipment: [],
            districts: [],
            inputValue: "",
            is_open: false
        },
        mounted: function() {
            var _self = this;
            var is_open = "<?php echo ($is_open); ?>";
            if (is_open == "1") {
                _self.is_open = true;
                this.$http
                    .get("/index.php/admin/api/getEquipmentList/equipment_type/yell")
                    .then(function(rel) {
                        console.log(rel);
                        _self.yellEquipment = rel.data.data;
                    });
                this.$http
                    .get("/index.php/admin/api/getDistrictList")
                    .then(function(rel) {
                        console.log(rel);
                        _self.districts = rel.data.data;
                    });
            }
        },
        methods: {
            showInput: function() {
                this.inputVisible = true;
            },
            handleClick: function() {
                _self = this;
                var activeName = this.activeName;
                if (activeName != "district") {
                    this.$http
                        .get("/index.php/admin/api/getEquipmentList/equipment_type/" + activeName)
                        .then(function(rel) {
                            console.log(rel);
                            var equipment = activeName + "Equipment";
                            _self[equipment] = rel.data.data;
                        });
                }
            },
            updateDistricts: function(equipment_id, district_id) {
                _self = this;
                var formData = new FormData();
                formData.append("yell_equipment_id", equipment_id);
                formData.append("district_id", district_id);
                this.$http
                    .post("/index.php/admin/api/changeYellEquipmentDistrict", formData)
                    .then(function(rel) {
                        console.log(rel);
                        _self.districts = rel.data.data;
                    });
            },
            changeYellCancelRelation: function(cancel_equipment_id, yell_equipment_id) {
                _self = this;
                var formData = new FormData();
                formData.append("cancel_equipment_id", cancel_equipment_id);
                formData.append("yell_equipment_id", yell_equipment_id);

                this.$http
                    .post("/index.php/admin/api/changeYellCancelRelation", formData)
                    .then(function(rel) {
                        console.log(rel);
                        _self.yellEquipment = rel.data.data;
                    });
            },
            addDistrict: function() {
                var district_name = this.inputValue;
                if (district_name == "") {
                    layer.msg(vm.langData.partitionNotEmpty[vm.lang]);
                    return;
                }
                var _seft = this;
                var formData = new FormData();
                formData.append("district_name", district_name);
                this.$http.post("/index.php/admin/api/addDistrict", formData)
                    .then(
                        function(returnData) {
                            _seft.districts = returnData.data.data;
                        }
                    );
            },
            handleClose: function(district_id) {
                var _seft = this;
                this.$http.get("/index.php/admin/api/delDistrict/district_id/" + district_id)
                    .then(
                        function(returnData) {
                            var data = returnData.data;
                            _seft.districts = data.data;
                        }
                    );
            },
            openOrClose: function() {
                var _seft = this;
                var temp = 0;
                if (_seft.is_open) {
                    temp = 1;
                }
                this.$http.get("/index.php/admin/device/openOrCloseShowNum/is_open/" + temp)
                    .then(
                        function(returnData) {
                            if (returnData.data.code == 1) {
                                if (temp == 1) {
                                    this.$http
                                        .get("/index.php/admin/api/getEquipmentList/equipment_type/yell")
                                        .then(function(rel) {
                                            console.log(rel);
                                            _self.yellEquipment = rel.data.data;
                                        });
                                    this.$http
                                        .get("/index.php/admin/api/getDistrictList")
                                        .then(function(rel) {
                                            console.log(rel);
                                            _self.districts = rel.data.data;
                                        });
                                }
                            }
                        }
                    );
            }
        }
    });
    </script>

    <!-- 自定义js -->
    
</body>

</html>