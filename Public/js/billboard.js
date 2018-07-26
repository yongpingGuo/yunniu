
//重定义date原型对象的format属性赋值
Date.prototype.format = function(format) {
    var date = {
        "M+": this.getMonth() + 1,
        "d+": this.getDate(),
        "h+": this.getHours(),
        "m+": this.getMinutes(),
        "s+": this.getSeconds(),
        "q+": Math.floor((this.getMonth() + 3) / 3),
        "S+": this.getMilliseconds()
    };
    if (/(y+)/i.test(format)) {
        format = format.replace(RegExp.$1, (this.getFullYear() + '').substr(4 - RegExp.$1.length));
    }
    for (var k in date) {
        if (new RegExp("(" + k + ")").test(format)) {
            format = format.replace(RegExp.$1, RegExp.$1.length == 1
                ? date[k] : ("00" + date[k]).substr(("" + date[k]).length));
        }
    }
    return format;
}

Vue.filter('week', function(value) {
    var value=parseInt(value);
    switch (value) {
        case 0:
            return vm.langData.Monday[vm.lang]
            break;
        case 1:
            return vm.langData.Tuesday[vm.lang]
            break;
        case 2:
            return vm.langData.Wednesday[vm.lang]
            break;
        case 3:
            return vm.langData.Thursday[vm.lang]
            break;
        case 4:
            return vm.langData.Friday[vm.lang]
            break;
        case 5:
            return vm.langData.Saturday[vm.lang]
            break;
        case 6:
            return vm.langData.Sunday[vm.lang]
            break;
        default:
            // statements_def
            break;
    }
});
//载入编辑界面
function initEditBill(){
    $("#bill_list").hide();
    $("#bill_board_info").show();
    setHBillHeight();
}

//给Vue全局对象添加myDate过滤器（时间格式化：yyyy-MM-dd）
Vue.prototype.myDate = function(data){
    var newDate = new Date();
    newDate.setTime(data * 1000);
    return newDate.format('yyyy-MM-dd')
}

//给Vue全局对象添加$axios属性
Vue.prototype.$axios = axios;

//创建vue 的实例
var vm = new Vue({
    data:{
        lang:language,
        langData: langData,
        billBoards:[],
        textCenter:true,
        mac:"",
        current_bill_board_name:"",
        timers:{},
        op_img_groups:[],
        current_bill_board_id:"",
        editStatus:false
    },
    created:function(){
        //初始化获取机器列表
        var _self = this;
        this.$axios({
            method:'GET',
            url:'/index.php/admin/billBoard/bill_list',
            emulateJSON: true
        }).then(function(response){
            console.log(response.data);
            _self.billBoards = response.data;
        });
    },
    methods:{
        editBillBoardName:function(){
            var current_bill_board_id = this.current_bill_board_id;
            var current_bill_board_name = this.current_bill_board_name;
            $.ajax({
                url:"/index.php/admin/billBoard/editBillBoardName",
                data:{"bill_board_name":current_bill_board_name,"bill_board_id":current_bill_board_id},
                type:"post",
                dataType:"json",
                success:function(returnData){
                   layer.msg(vm.langData.success[vm.lang]);
                }
            });
        },
        editBill:function(id){
            //编辑按钮点击事件
            var _self = this;
            this.$axios({
                method:"get",
                url:"/index.php/admin/billBoard/getBillBoardInfo/id/"+id,
                emulateJSON:true
            }).then(function(response){
                var re_data = response.data;
                _self.mac = re_data.bill_board_code;
                _self.current_bill_board_name = re_data.bill_board_name;
                _self.timers = re_data.timers;
                _self.op_img_groups = re_data.img_group;
                //_self.op_img_groups = [{value1:"1212",value2:"1212"},{value1:"1212"},{value1:"1212"}];
                _self.current_bill_board_id = re_data.bill_board_id;
                _self.editStatus=true;
                console.log(response.data);
            });
        },
        deleteBill:function(id,index){
            //删除按钮点击事件
            var _self = this;
            this.$axios({
                method:"get",
                url:"/index.php/admin/billBoard/deleteBill/id/"+id,
                emulateJSON:true
            }).then(function(response){
                var data = response.data;
                console.log(data);
                if(data.code == 1){
                    _self.billBoards.splice(index,1);
                }
                layer.msg(vm.langData.success[vm.lang]);
            });
        },
        saveBillBoardTimer:function(){
            //定时保存按钮点击事件
            var _self = this;
            console.log(_self.timers);
            $.ajax({
                url:"/index.php/admin/billBoard/saveBillBoardTimer",
                data:{"billBoardTimers":_self.timers},
                type:"post",
                dataType:"json",
                success:function(returnData){
                    layer.msg(vm.langData.success[vm.lang]);
                }
            });
        },
        is_select_all:function(index) {
            var timers = this.timers;
            //全选按钮点击事件
            var timer = this.timers[index];

            var all = timer.all;
            var i_timer = 0;
            if(timers.length == 1){
                if(all == true){
                    timer.oc_week = [true,true,true,true,true,true,true];
                }else if(all == false){
                    timer.oc_week = [false,false,false,false,false,false,false];
                }
            }else{
                var is_select_all = true;
                for(i_timer in timers){
                    if(i_timer != index){
                        for(i_week in timers[i_timer].oc_week){
                            if( timers[i_timer].oc_week[i_week] == true){
                                is_select_all = false;
                            }
                        }
                    }
                }
                if(is_select_all == true){
                    if(all == true){
                        timer.oc_week = [true,true,true,true,true,true,true];
                    }else if(all == false){
                        timer.oc_week = [false,false,false,false,false,false,false];
                    }
                }else{
                    layer.msg(vm.langData.electronicMenuLimit[vm.lang]);
                    timer.all = !all;
                }
            }
        },
        is_change:function(event,item_index,index){
            //选择按钮

            var timers = this.timers;
            var timer_item;
            var n = 0;

            var starting_time = timers[index].starting_time;
            var ending_time = timers[index].ending_time;

            for(timer_item in timers){
                if(timers[timer_item].oc_week[item_index] == true){
                    //
                    n++;
                    if(n >= 2){
                        var temp = timers[index].oc_week[item_index] = false;
                        Vue.set(timers[index].oc_week,item_index,temp);
                        layer.msg(vm.langData.electronicMenuLimit[vm.lang]);
                    }
                }
            }
            var item;
            var timer = this.timers[index];
            var oc_week = timer.oc_week;
            for(item in oc_week){
                if(oc_week[item] == false){
                    timer.all = false;
                    return;
                }

                if(item == 6 && oc_week[item] == true){
                    timer.all = true;
                }
            }
        },
        addBillBoardTimer:function(){
            //添加按钮点击事件
            var billBoardTimer = {
                all:false,
                bill_board_id:this.current_bill_board_id,
                bill_timer_id:"",
                starting_time:"00:00",
                ending_time:"23:59",
                is_use:true,
                //oc_week:[true,true,true,true,true,true,true],
                oc_week:[false,false,false,false,false,false,false],
            }
            this.timers.push(billBoardTimer);
        },
        deleteBillBoardTimer:function(index){
            //删除按钮点击事件
            var bill_timer_id = this.timers[index].bill_timer_id;
            var _self = this;
            if(bill_timer_id != ""){
                $.ajax({
                    url:"/index.php/admin/billBoard/deleteBillBoardTimer/bill_timer_id/"+bill_timer_id,
                    type:"get",
                    dataType:"json",
                    success:function(returnData){
                        _self.timers.splice(index,1);
                        layer.msg(vm.langData.success[vm.lang]);
                    }
                });
            }else{
                this.timers.splice(index,1);
                layer.msg(vm.langData.successfullyDeleted[vm.lang]);
            }
        },
        saveBillBoardImgGroup:function(){
            var _self = this;
            var bb_img_groups = _self.op_img_groups;
            console.log(bb_img_groups);
            $.ajax({
                url:"/index.php/admin/billBoard/saveBillBoardImgGroup",
                type:"post",
                data:{'bb_img_groups':bb_img_groups},
                dataType:"json",
                success:function(returnData){
                    layer.msg(vm.langData.success[vm.lang]);
                }
            });
        },
        addBillBoardImgGroup:function(){
            var bb_img_group = this.op_img_groups;
            var bill_board_id = this.current_bill_board_id;
            $.ajax({
                url:"/index.php/admin/billBoard/addBillBoardImgGroup",
                data:{'bill_board_id':bill_board_id},
                type:"post",
                dataType:"json",
                success:function(returnData){
                    if(returnData.code == 1){
                        var t_img_group = returnData.data;
                        bb_img_group.push(t_img_group);
                        layer.msg(vm.langData.success[vm.lang]);
                    }
                }
            });
        },
        deleteImgGroup:function(bb_group_id,index){
            var _self = this;
            $.ajax({
                url:"/index.php/admin/billBoard/deleteImgGroup/bb_group_id/"+bb_group_id,
                type:"get",
                dataType:"json",
                success:function(returnData){
                    _self.op_img_groups.splice(index,1);
                    layer.msg(vm.langData.success[vm.lang]);
                }
            });
        },
        uploadImg:function(obj,index){
            var bb_img_group = this.op_img_groups[index];
            console.log(bb_img_group);
            var file = $(obj.target)[0].files[0];
            var bb_group_id = bb_img_group.bb_group_id;
            var formData = new FormData();
            formData.append("bb_group_id",bb_group_id);
            formData.append("file",file);
            $.ajax({
                url:"/index.php/admin/billBoard/uploadImg",
                data:formData,
                type:"post",
                dataType:"json",
                async: false,
                cache: false,
                contentType: false,
                processData: false,
                success:function(returnData){
                    if(returnData.code == 1){
                        bb_img_group.bb_group_imgs.push(returnData.data);
                        layer.msg(vm.langData.success[vm.lang]);
                    }
                    
                }
            });
        },
        deleteImg:function(id,index,index_g){
            var bb_group_imgs = this.op_img_groups[index_g].bb_group_imgs;
            $.ajax({
                url:"/index.php/admin/billBoard/deleteImg/id/"+id,
                type:"get",
                dataType:"json",
                success:function(returnData){
                    if(returnData.code == 1){
                        bb_group_imgs.splice(index,1);
                        layer.msg(vm.langData.success[vm.lang]);
                    }else{
                        layer.msg(vm.langData.error[vm.lang]);
                    }
                }
            });
        }
    },
}).$mount("#lang-content");