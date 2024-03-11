
<?php
use yii\helpers\Url;
?>
<div class="layui-fluid">
    <div class="layui-card">
    <div class="lay-lists">
<form class="layui-form">
    <blockquote class="layui-elem-quote quoteBox">
        <div class="layui-inline">
		<a class="layui-btn" data-type="url" data-title="添加权限" data-url="<?=Url::to(['permission/create'])?>" data-callback_title = "permission列表" >添加权限</a>
        </div>
    </blockquote>
</form>
<div class="lay-search" style="padding-left: 10px">
    名称：
    <div class="layui-inline">
        <input class="layui-input search-con" name="AuthItemSearch[name]" autocomplete="off">
    </div>
    <button class="layui-btn" data-type="search_lists">搜索</button>
</div>
    <div class="layui-card-body">
<table id="authitem" class="layui-table" lay-data="{url:'<?=Url::to(['permission/list'])?>', height : 'full-20', cellMinWidth : 95, page:true,limits:[20,50,100,1000],limit:20}" lay-filter="authitem">
    <thead>
    <tr>
        <th lay-data="{field: 'name', width:180}">名称</th>
        <th lay-data="{field: 'rulename', align:'left',width:100}">规则名称</th>
        <th lay-data="{field: 'description', width:250}">简述</th>
        <th lay-data="{field: '$data', width:120, templet: '#statusTpl', unresize: true}">扩展数据</th>
        <th lay-data="{field: 'updatedAt',  align:'left',minWidth:50}">更新时间</th>
        <th lay-data="{field: 'createdAt',  align:'left',minWidth:50}">创建时间</th>
        <th lay-data="{minWidth:175, templet:'#listBar',fixed:'right',align:'center'}">操作</th>
    </tr>
    </thead>
</table>
</div>
<!--操作-->
<script type="text/html" id="listBar">
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['permission/update'])?>?per_name={{ d.name }}" data-title="编辑" data-callback_title="permission列表">编辑</a>
    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete" data-url="<?=Url::to(['permission/delete'])?>?per_name={{ d.name }}">删除</a>
	<a class="layui-btn layui-btn-xs" lay-event="update"   data-url="<?=Url::to(['permission/view'])?>?per_name={{ d.name }}">查看</a>
</script>
<script>
    const tableName="authitem";
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=2")?>
