<?php
use yii\helpers\Url;
?>
<div class="layui-fluid">
    <div class="layui-card">
<form class="layui-form">
    <blockquote class="layui-elem-quote quoteBox">
        <div class="layui-inline">
            <a class="layui-btn addNewRoute_btn">添加路由</a>
        </div>
        <div class="layui-inline">
            <a class="layui-btn layui-btn-danger remove_btn">移除路由</a>
        </div>
    </blockquote>
</form>
        <div class="layui-card-body">

        <table id="assigned" lay-filter="assigned"></table>
        </div>
    </div>
</div>
<!--操作-->
<script type="text/html" id="routeListBar">
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update">编辑</a>
    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete">删除</a>
</script>
<script>
    const routeRemoveUrl="<?=Url::to(['route/remove-route'])?>"
    const routeAssignedListUrl="<?=Url::to(['route/assigned-list'])?>"
    const routeViewUrl="<?=Url::to(['route/view'])?>"
    const routeUpdateUrl="<?=Url::to(['route/update'])?>"
    const routeCreateUrl="<?=Url::to(['route/create'])?>"
</script>
<?=$this->registerJsFile("@adminPageJs/route/as-index.js")?>
