  //新增菜品类别模态框
  function add_food_type(){
  		var food_id = $("#type_form").data('id');
  		if(food_id != undefined){
  			$("#add-dishes-sort").modal('show');
            $('input[name="count_type"]' ).val(0);
	  		$("#reset1").trigger("click");
	  		$(".attr-upload").children().attr('src',"");
  		}else{
  			layer.msg(vm.langData.pleaseSaveDish[vm.lang]);
  		}
  }
  
  
  //编辑菜别属性
  function editAttr(obj){
        var food_attribute_id = $(obj).data("attr_id");
        $("#food_attribute_id").val(food_attribute_id);
        $("#type").val("edit");
        $.ajax({
        	type:"get",
        	url:"/index.php/admin/dishes/getDishesAttr/food_attribute_id/"+food_attribute_id+"",
        	async:true,
        	success:function(data){
        		$("input[name='attribute_name']").val(data.attribute_name);    		
        		$("input[name='attribute_price']").val(data.attribute_price);
        		$("input[name='food_attribute_id']").val(data.food_attribute_id);
        		$("#attrimg").attr('src',"/"+data.attribute_img+"");
        	}
        });
    }


    function addDishesAttr1(obj){
        var attribute_type_id = $(obj).data("type_id");
        $("#attribute_type_id").val(attribute_type_id);
        $("#type").val("add");
        $("#reset2").trigger("click");
    }

    function addDishesAttr(obj){
        $("#edit-attr").modal("hide");
        var type = $("#type").val();
        var form = $("#add_attr")[0];
        var formData = new FormData(form);
        var url;
        if(type == "add"){
            url = '/index.php/admin/dishes/addDishesAttr';
            $.ajax({
                url:url,
                type:'post',
                data:formData,
                dataType:'json',
                contentType:false,
                processData:false,
                cache:false,
                async:false,
                success:function(msg){
                    console.log(msg);
                    if(msg.code == 1){
                        var data = msg.data;
                        var str='<div class="dishes-attr-item">\
                           <input type="hidden" name="attribute_type_id" class="attribute_type_id" value="'+data['attribute_type_id']+'"> \
                                    <div>'+data['attribute_name']+'</div>\
                                    <div>\
                                        <span class="text-danger">+</span>\
                                        <span>'+data['attribute_price']+vm.langData.yuan[vm.lang]+'</span>\
                                    </div>\
                                    <button class="edit-btn dishes-attr-edit" data-attr_id="'+data['food_attribute_id']+'"  data-toggle="modal" data-target="#edit-attr" onclick="editAttr(this)"></button>\
                                    <button class="remove-btn dishes-attr-del" data-attr_id="'+data['food_attribute_id']+'" onclick="deleteAttr(this)"></button>\
                                </div>'
                       $("#attrType"+data['attribute_type_id']).find('.dishes-attr-add').before(str);

                    }
                }
            });
        }else if(type == "edit"){
            var food_id = $("#type_form").data("id");
            url = '/index.php/admin/dishes/editDishesAttr';
            $.ajax({
                url:url,
                type:'post',
                data:formData,
                dataType:'json',
                contentType:false,
                processData:false,
                cache:false,
                async:false,
                success:function(msg){
                    console.log(msg);
                    if(msg.code == 1){
                        self.location.href = "/index.php/admin/Dishes/edit/food_id/"+food_id;
                    }
                }
            });
        }

    }

    function editType(obj){
        var type_id = $(obj).data('type_id');
        $.ajax({
            url:"/index.php/admin/Dishes/getTypeAttrs",
            type:"post",
            data:{"type_id":type_id},
            success:function(data){
                $("#attr_content_byId").html(data);
            }
        });
    }

    function editDishesType(){
        var food_id = $("#type_form").data('id');
        var form = $("#editDishesType")[0];
        var formData = new FormData(form);
        formData.append("food_id",food_id);
        $.ajax({
            url:"/index.php/admin/dishes/editDishesType",
            type:"post",
            data:formData,
            dataType:"json",
            contentType:false,
            processData:false,
            cache:false,
            success:function(msg){
                if(msg.code == 1){
                    self.location.href = "/index.php/admin/Dishes/edit/food_id/"+food_id;
                }
            }
        });
    }

    function deleteAttr(obj){
        var attr_id = $(obj).data("attr_id");
        $.ajax({
            url:"/index.php/admin/dishes/deleteAttr",
            data:{"attr_id":attr_id},
            type:"post",
            dataType:"json",
            success:function(msg){
                if(msg.code == 1){
                    $(obj).parent().remove();
                }
            }
        });
    }

    function deleteType(obj){
        var type_id = $(obj).data("type_id");
        $.ajax({
            url:"/index.php/admin/dishes/deleteType",
            data:{"type_id":type_id},
            type:"post",
            dataType:"json",
            success:function(msg){
                if(msg.code == 1){
                    $(obj).parent().parent().remove();
                }
            }
        });
    }
    

    
    //新增菜品页面-新增菜品类别
    function addDishesAttrType(){
        $("#add-dishes-sort").modal("hide");
        var form = $("#addDishesAttrType")[0];
        var food_id = $("#type_form").data('id');
        $("#type_form").val(food_id);
        var formData = new FormData(form);
        $.ajax({
            url:'/index.php/admin/dishes/addDishesAttrType',
            data:formData,
            type:"post",
            dataType:'json',
            contentType:false,
            processData:false,
            cache:false,
            async:false,
            success:function(msg){
                console.log(msg);
                if(msg.code == 1){
                    console.log("操作成功");
                    var data = msg.data;

                    var str = '<div class="dishes-spec">\
                        <div class="dishes-spec-header"> \
                            <b>'+data['type_name']+'</b>\
                            <button class="edit-btn" data-toggle="modal" data-target="#edit-dishes-sort" data-type_id="'+data["attribute_type_id"]+'" onclick="editType(this)"></button>\
                            <button class="remove-btn" data-type_id="'+data["attribute_type_id"]+'" onclick="deleteType(this)"></button>\
                        </div>\
                        <div id="attrType'+data["attribute_type_id"]+'" class="clearfix">\
                            <div class="pull-left dishes-attr-left">'+vm.langData.specificationName[vm.lang]+':</div> \
                            <div class="pull-left dishes-attr-list">\
                                <div class="dishes-attr-add">\
                                    <button type="button" data-type_id="'+data["attribute_type_id"]+'" onclick="addAttr(this)" data-toggle="modal" data-target="#edit-attr">\
                                        <img src="'+publicURL+'/images/add_down.png">\
                                    </button>\
                                </div>\
                            </div>\
                        </div>\
                    </div>';
                    $("#dishesAttrList").append(str);
                }
            }
        });
    }
    
    //新增属性值
    function addAttr(obj){
        var attribute_type_id = $(obj).data("type_id");
        $("#attribute_type_id").val(attribute_type_id);
        $("#type").val("add");
        $("input[type='reset']").trigger('click');


    }
    function test(obj) {

        var attributename = $(obj).closest("form").find("input[name='attribute_name']").val();
        var attributeprice = $(obj).closest("form").find("input[name='attribute_price']").val();
        var attributetypeid = $(obj).closest("form").find("input[name='attribute_type_id']").val();
        var foodattributeid = $(obj).closest("form").find("input[name='food_attribute_id']").val();

        $.ajax({
        
            url: '/index.php/admin/dishes/subm',
            data:{'attribute_name' : attributename, 'attribute_price' : attributeprice, 'attribute_type_id' : attributetypeid, 'food_attribute_id' : foodattributeid},
            type: 'POST',
            dataType: 'json',
            error:function(data){
                console.log(data);
            },
            success:function(json){
         // console.log(json);
                $(obj).closest("form").find("input[name='attribute_name']").val(json.data.attribute_name);         
                $(obj).closest("form").find("input[name='attribute_price']").val(json.data.attribute_price);
                $(obj).closest("form").find("input[name='food_attribute_id']").val(json.data.food_attribute_id);
                layer.msg(vm.langData.success[vm.lang]);
            }
        });
    }


    function select_all(obj){
        var tt = $(obj).val();
        if (tt == 0) {
            $(".menu_input").prop("checked", true);
            $(obj).val(1);
        } else if (tt == 1) {
            $(".menu_input").prop("checked", false);
            $(obj).val(0)
        }
    }

    function preview(file) {
        var prevDiv = document.getElementById('preview');
        var picinfo = file.files[0]; //input 
            if( picinfo.size > 1*1024*1024 ){  //用size属性判断文件大小不能超过5M 
                layer.msg(vm.langData.uploadLimit[vm.lang]);
                $("input[name='food_pic']").val('');
                prevDiv.innerHTML = '';
                return false;
            }
        if (file.files && file.files[0]) {
            var reader = new FileReader();
            reader.onload = function (evt) {
                prevDiv.innerHTML = '<img src="' + evt.target.result + '"/>';
            }
            reader.readAsDataURL(file.files[0]);
        }
    }
    function mypreview(file) {
        var prevDiv = $(file).parent().prev();
        console.log();
        prevDiv = prevDiv[0];
        console.log(prevDiv);
        if (file.files && file.files[0]) {
            var reader = new FileReader();
            reader.onload = function (evt) {
                prevDiv.innerHTML = "";
                prevDiv.innerHTML = '<img src="' + evt.target.result + '" class="pre100 center-block" style="width:100%;height:100%;" />';
            }
            reader.readAsDataURL(file.files[0]);
        }
        else {
            prevDiv.innerHTML = "";
            prevDiv.innerHTML = '<div style="width:100%;height:100%;" class="img" style="filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(sizingMethod=scale,src=\'' + file.value + '\'"></div>';
        }
    }
    
    $("input[name='is_prom']").change(function(){
    	//alert($(this).val());
    	var value = $(this).val();
    	if(value == 1){
    		$("#showdiscount").show();
    	}else{
    		$("#showdiscount").hide();
    	}
    });
    
