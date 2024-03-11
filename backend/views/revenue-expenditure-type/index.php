
<?php
use yii\helpers\Url;
?>
<div class="layui-fluid">
    <div class="layui-card">
        <div class="lay-lists">
            <form class="layui-form">
                <blockquote class="layui-elem-quote quoteBox">
                    <div class="layui-inline">
                        <a class="layui-btn" data-type="url" data-title="添加" data-url="<?=Url::to(['revenue-expenditure-type/create'])?>" data-callback_title = "revenue-expenditure-type列表" >添加类型</a>
                    </div>
                </blockquote>
            </form>

            <div class="lay-search" style="padding-left: 10px">

                类型名称：
                <div class="layui-inline">
                    <input class="layui-input search-con" name="RevenueExpenditureTypeSearch[name]" autocomplete="off">
                </div>

                <button class="layui-btn" data-type="search_lists">搜索</button>
            </div>
            <div class="layui-card-body">
                <table id="revenue-expenditure-type" class="layui-table" lay-data="{url:'<?=Url::to(['revenue-expenditure-type/list'])?>', height : 'full-20', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20}" lay-filter="revenue-expenditure-type">
                    <thead>
                    <tr>
                        <th lay-data="{field: 'id', width:80}">ID</th>
                        <th lay-data="{field: 'name', align:'left'}">类型名称</th>
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
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['revenue-expenditure-type/update'])?>?id={{ d.id }}" data-title="编辑" data-callback_title="revenue-expenditure-type列表">编辑</a>
    {{# if(d.delete == 'true'){ }}
        <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete" data-url="<?=Url::to(['revenue-expenditure-type/delete'])?>?id={{ d.id }}">删除</a>
    {{# } }}
</script>

<script>
    const tableName="revenue-expenditure-type";
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js")?>