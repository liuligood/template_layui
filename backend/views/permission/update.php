<?php
use yii\helpers\Url;
?>
<style>
    html {
        background: #fff;
    }
</style>
<form class="layui-form layui-row" style="padding-top: 15px">

    <div class="layui-col-md6 layui-col-xs12">

        <div class="layui-form-item">
            <label class="layui-form-label">权限名称</label>
            <div class="layui-input-block">
                <input type="text" name="name" value="<?=$per_info['name']?>" lay-verify="required" class="layui-input">
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">描述</label>
            <div class="layui-input-block">
                <input type="text" name="description" value="<?=$per_info['description']?>" placeholder="请输入描述"  class="layui-input ">
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label">规则名称</label>
            <div class="layui-input-block">
                <input type="text" name="ruleName" id="rule-name-select"  placeholder="请输入规则名称" autocomplete="off" value="<?=$per_info['ruleName']?>" class="layui-input ">
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">扩展数据</label>
            <div class="layui-input-block">
                <input type="text" name="data" value="<?=$per_info['data']?>" placeholder="请输入扩展数据"  class="layui-input ">
            </div>
        </div>


        <div class="layui-form-item">
            <div class="layui-input-block">
                <input type="hidden" name="per_name" value="<?=$per_info['name']?>">
                <button class="layui-btn" lay-submit="" lay-filter="updatePermission">立即提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </div>
</form>
<script>
    const permissionUpdateUrl="<?=Url::to(['permission/update'])?>"
    const ruleListUrl="<?=Url::to(['rule/list'])?>";
</script>
<?=$this->registerJsFile("@adminPageJs/permission/update.js")?>

