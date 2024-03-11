
<?php
use yii\helpers\Url;
?>
<div class="layui-fluid">
    <div class="layui-card">
<div class="lay-lists">
<form class="layui-form">
    <blockquote class="layui-elem-quote quoteBox">
        <div class="layui-inline">
            <a class="layui-btn" data-type="url" data-title="添加" data-url="<?=Url::to(['demo/create'])?>" data-callback_title = "demo列表" >添加</a>
        </div>
    </blockquote>
</form>

<div class="lay-search" style="padding-left: 10px">
    ID：
    <div class="layui-inline">
        <input class="layui-input search-con" name="DemoSearch[id]" autocomplete="off">
    </div>

    标题：
    <div class="layui-inline">
        <input class="layui-input search-con" name="DemoSearch[title]" autocomplete="off">
    </div>

    <button class="layui-btn" data-type="search_lists">搜索</button>
</div>
    <div class="layui-card-body">
<table id="demo" class="layui-table" lay-data="{url:'<?=Url::to(['demo/list'])?>', height : 'full-20', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}}" lay-filter="demo">
    <thead>
    <tr>
        <th lay-data="{field: 'id', width:80}">ID</th>
        <th lay-data="{field: 'title', align:'left',width:100}">标题</th>
        <th lay-data="{field: 'desc', width:100}">备注</th>
        <th lay-data="{field: 'status', width:120, templet: '#statusTpl', unresize: true}">状态</th>
        <th lay-data="{field: 'update_time',  align:'left',minWidth:50}">更新时间</th>
        <th lay-data="{field: 'add_time',  align:'left',minWidth:50}">创建时间</th>
        <th lay-data="{minWidth:175, templet:'#listBar',fixed:'right',align:'center'}">操作</th>
    </tr>
    </thead>
</table>
</div>
</div>
    </div>
</div>
<!--操作-->
<script type="text/html" id="listBar">
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['demo/update'])?>?id={{ d.id }}" data-title="编辑" data-callback_title="demo列表">编辑</a>
    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete" data-url="<?=Url::to(['demo/delete'])?>?id={{ d.id }}">删除</a>
</script>

<script type="text/html" id="statusTpl">
    <input type="checkbox" name="状态" value="{{d.id}}" data-url="<?=Url::to(['demo/update-status'])?>?id={{ d.id }}" lay-skin="switch" lay-text="正常|禁用" lay-filter="statusSwitch" {{ d.status ==10 ? 'checked' : '' }}>
</script>

<script>
    const tableName="demo";
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js")?>