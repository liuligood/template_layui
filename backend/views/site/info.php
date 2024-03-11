<?php
use yii\helpers\Url;
?>
    <style>
        html {
            background: #fff;
        }
    </style>
<form class="layui-form layui-row" style="margin-top: 10px;">

    <div class="layui-col-md4 layui-col-xs12">

        <div class="layui-form-item">
            <label class="layui-form-label">登录帐号</label>
            <div class="layui-input-block">
                <input type="text" name="username" lay-verify="required"  value="<?=$admin_user['username']?>" disabled class="layui-input layui-disabled">
            </div>
        </div>
                <div class="layui-form-item">
            <label class="layui-form-label">邮箱</label>
            <div class="layui-input-block">
                <input type="text" name="email" lay-verify="required" value="<?=$admin_user['email']?>" disabled class="layui-input layui-disabled">
            </div>
            </div>


        <div class="layui-form-item">
            <label class="layui-form-label">真实姓名</label>
            <div class="layui-input-block">
                <input type="text" name="nickname" lay-verify="required"  value="<?=$admin_user['nickname']?>" disabled class="layui-input layui-disabled">
            </div>
        </div>
    
            <div class="layui-form-item">
            <label class="layui-form-label">所属店铺</label>
            <div class="layui-input-block">
                <input type="text"  placeholder="暂无" disabled class="layui-input layui-disabled">
            </div>
        </div>
</div>
</form>
<?=$this->registerCssFile("@adminExtCss/layuiformSelects/dist/formSelects-v4.css")?>