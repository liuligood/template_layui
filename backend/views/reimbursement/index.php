
<?php
use yii\helpers\Url;
use yii\helpers\Html;
?>

<style>
    .layui-vertical-20{
        padding-top: 20px;
    }
</style>
<div class="layui-fluid">
    <div class="layui-card">
        <div class="lay-lists">
            <form class="layui-form">
                <blockquote class="layui-elem-quote quoteBox">
                    <div class="layui-inline">
                        <a class="layui-btn" data-type="url" data-title="添加" data-url="<?=Url::to(['reimbursement/create'])?>" data-callback_title = "Real订单列表" >添加</a>
                    </div>
                </blockquote>
            </form>
            <div class="layui-card-body">
                <table id="reimbursement" class="layui-table" lay-data="{url:'<?=Url::to(['reimbursement/list'])?>', height : 'full-20', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20}" lay-filter="reimbursement">
                    <thead>
                    <tr>
                        <th lay-data="{field: 'id', align:'center',width:60}">ID</th>
                        <th lay-data="{field: 'reimbursement_name', width:100, align:'center',templet:'#goodsImgTpl'}">报销人名</th>
                        <th lay-data="{field: 'add_time',  align:'center',width:120}">添加时间</th>
                        <th lay-data="{field: 'update_time',  align:'center',width:120}">更新时间</th>
                        <th lay-data="{minWidth:200, templet:'#listBar',fixed:'right',align:'center'}">操作</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!--操作-->
<script type="text/html" id="listBar">
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['reimbursement/update'])?>?id={{ d.id }}" data-title="编辑" data-callback_title="Real订单列表">编辑</a>
    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete" data-url="<?=Url::to(['reimbursement/delete'])?>?id={{ d.id }}">删除</a>
</script>

<script>
    const tableName="reimbursement";
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=2")?>


