<?php
use yii\helpers\Url;
use Yii;
?>
<style>
    html {
        background: #fff;
    }
</style>
<form class="layui-form layui-row" id="update" action="<?=Url::to(['site/password'])?>">

    <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px">

        <div class="layui-form-item">
            <label class="layui-form-label">旧密码</label>
            <div class="layui-input-block">
                <input type="password" name="oldpassword" lay-verify="required"  placeholder="" value=""  class="layui-input">
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label">新密码</label>
            <div class="layui-input-block">
                <input type="password"  id="password1" lay-verify="required" placeholder="8~16个字符" value=""  class="layui-input">
                <div style="display: inline" id="tip2"></div>
            </div>
        </div>
                <div class="layui-form-item">
            <label class="layui-form-label">确定新密码</label>
            <div class="layui-input-block">
                <input type="password" name="password" id="password2" lay-verify="required" placeholder="" value=""  class="layui-input">
                <div style="display: inline" id="tip3"></div>
            </div>
        </div>

        <div class="layui-form-item" id = "a">
            <div class="layui-input-block" >
                <button class="layui-btn" id = "selector" lay-submit="" lay-filter="form" data-form="update">确定修改</button>
            </div>
        </div>
    </div>
</form>
<script>
$("#password1").blur(function() {
	var num = $("#password1").val().length;
	if (num <8 ) {
		$("#tip2").html("<span>密码太短，请重新输入</span>").css({'color':'red','marginLeft':'15px'});
	} else if (num > 16) {
		$("#tip2").html("<span>密码太长，请重新输入</span>").css({'color':'red','marginLeft':'15px'});
	} else{
		$("#tip2").html("").css({'color':'red','marginLeft':'15px'});
		}
	
});
/* 再次输入新密码 */
$("#password2").blur(function() {
	var tmp = $("#password1").val();
	var num = $("#password2").val().length;
	if ($("#password2").val() != tmp) {
		$("#tip3").html("<span>两次密码输入不一致，请重新输入</span>").css({'color':'red','marginLeft':'15px'});
		$("#selector").removeClass("layui-btn");
		 $("#selector").attr("disabled", "true");
		$("#selector").addClass("layui-btn layui-btn-disabled");
	} else {
		if (num >= 8 && num <= 16) {
			$("#tip3").html("").css({'color':'red','marginLeft':'15px'});
			$("#selector").removeClass("layui-btn layui-btn-disabled");
			$("#selector").addClass("layui-btn");
			$("#selector").removeAttr("disabled");

		} else {
			$("#tip3").html("<span>验证不通过，无效</span>").css({'color':'red','marginLeft':'15px'});
			$("#update").removeClass("layui-form");
			 $("#selector").attr("disabled", "true");
			$("#selector").addClass("layui-btn layui-btn-disabled");
			
		}
	}
});
</script>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>