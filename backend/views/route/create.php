<?php
use yii\helpers\Url;
?>
<style>
    html {
        background: #fff;
    }
</style>
<form class="layui-form layui-row" style="padding-top: 15px;">

    <div class="layui-col-md6 layui-col-xs12">

        <div class="layui-form-item">
            <label class="layui-form-label">路由地址</label>
            <div class="layui-input-block">
                <input type="text" name="name" lay-verify="required" class="layui-input">
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">路由名称</label>
            <div class="layui-input-block">
                <input type="text" name="data_name" placeholder="请输入路由名称"  class="layui-input ">
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label">路由描述</label>
            <div class="layui-input-block">
                <input type="text" name="data" placeholder="请输入路由描述"  class="layui-input ">
            </div>
        </div>

        <div class="layui-form-item">
            <div class="layui-input-block">
                <button class="layui-btn" lay-submit="" lay-filter="createRoute">立即提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </div>
</form>
<script>
    const routeCreateUrl="<?=Url::to(['route/create'])?>"
</script>
<?=$this->registerJsFile("@adminPageJs/route/create.js")?>
