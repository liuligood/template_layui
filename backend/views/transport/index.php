
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

    <table id="transport" class="layui-table" lay-data="{url:'<?=Url::to(['transport/list'])?>', height : 'full-30', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20}" lay-filter="transport">
    <thead>
    <tr>
        <th lay-data="{field: 'transport_code', width:100, align:'center', width:200}">物流商代码</th>
        <th lay-data="{field: 'transport_name', align:'center', width:120}">物流商名</th>
        <th lay-data="{field: 'track_url', align:'center', width:220}">物流跟踪链接</th>
        <th lay-data="{field: 'status_desc', align:'center', width:100}">状态</th>
        <th lay-data="{minWidth:220, templet:'#listBar',fixed:'right',align:'center'}">操作</th>
    </tr>
    </thead>
</table>
</div>
    </div>
</div>
<!--操作-->
<script type="text/html" id="listBar">
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['transport/update'])?>?transport_code={{ d.transport_code }}" data-title="编辑" data-callback_title="物流配置">编辑</a>

    <a class="layui-btn layui-btn-xs" lay-event="update" data-url="<?=Url::to(['shipping-method/index'])?>?transport_code={{ d.transport_code }}" data-title="物流方式" data-callback_title="物流配置">物流方式</a>
</script>

<script>
    const tableName="transport";
</script>
<?php
    $this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.4.1");
?>

