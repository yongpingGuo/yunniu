<?php if (!defined('THINK_PATH')) exit();?><div id="ajax-content">
    <div id="comment_list">
    	<table class="table-code table-condensed">
	        <tbody>
	            <tr>
	                <td></td>
	                <td>{{langData.tableNumber[lang]}}</td>
	                <td class="text-center">{{langData.machineCode[lang]}}</td>
	                <td></td>
	                <td></td>
	            </tr>
	            <?php if(is_array($deskInfo)): $i = 0; $__LIST__ = $deskInfo;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><tr>
	                    <td><?php echo ($i); ?></td>
	                    <td><?php echo ($vo["desk_code"]); ?></td>
	                    <td class="text-center">
	                        <img src="<?php echo ($vo["code_img"]); ?>" class="table-code-img">
	                    </td>
	                    <td>
	                        <button class="blue-btn" data-img_path="<?php echo ($vo["code_img"]); ?>" onclick="downloadImg(this)">{{langData.downloadPicture[lang]}}</button>
	                    </td>
	                    <td>
	                        <button class="edit-btn" data-desk_id="<?php echo ($vo["desk_id"]); ?>" data-desk_code="<?php echo ($vo["desk_code"]); ?>" onclick="editDesk(this)"></button>
	                        <button class="remove-btn" data-desk_id="<?php echo ($vo["desk_id"]); ?>" onclick="delDesk(this)"></button>
	                    </td>
	                </tr><?php endforeach; endif; else: echo "" ;endif; ?>
	            <tr>
	                <td colspan="5" class="text-center">
	                    <ul class="pagination" id="detail-page"><?php echo ($page); ?></ul>
	                </td>
	            </tr>
	        </tbody>
	    </table>
    </div>
</div>
<script src="/Public/js/vue.js"></script>
<script type="text/javascript">
new Vue({
    el: "#ajax-content",
    data: {
        lang: language,
        langData: langData
    }
})
$("#detail-page").click(function(event) {
      var page = parseInt($(event.target).data("page"));
      console.log(page);
      $.ajax({
          url: "/index.php/admin/device/deskInfo",
          data: { "page": page },
          type: "get",
          success: function(data) {
              $("#comment_list").html(data);
          },
          error: function() {
              layer.msg(vm.langData.error[vm.lang]);
          }
      });
 });
</script>