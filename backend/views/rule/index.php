<?php
use yii\helpers\Url;
?>
<div class="layui-fluid">
    <div class="layui-card">
<form class="layui-form">
    <blockquote class="layui-elem-quote quoteBox">
        <div class="layui-inline">
            <a class="layui-btn addNewRule_btn">添加规则</a>
        </div>
    </blockquote>
</form>
        <div class="layui-card-body">
        <table id="rule" lay-filter="rule"></table>
        </div>
    </div>
</div>
<!--操作-->
<script type="text/html" id="ruleListBar">
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update">编辑</a>
    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete">删除</a>



</script>
<script>
    const ruleListUrl="<?=Url::to(['rule/list'])?>";
    const ruleDeleteUrl="<?=Url::to(['rule/delete'])?>";
    const ruleUpdateUrl="<?=Url::to(['rule/update'])?>";
    const ruleCreateUrl="<?=Url::to(['rule/create'])?>";
</script>
<?=$this->registerJsFile("@adminPageJs/rule/index.js")?>

