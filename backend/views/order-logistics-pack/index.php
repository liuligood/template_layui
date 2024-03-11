
<?php

use common\models\TransportProviders;
use yii\helpers\Url;
use yii\helpers\Html;
use common\models\Order;
use common\models\OrderLogisticsPack;
use common\models\OrderLogisticsPackAssociation;
$order = new OrderLogisticsPackAssociation();

?>

<style>
    .layui-vertical-20{
        padding-top: 20px;
    }
    .layui-table-body .layui-table-cell{
        height:auto;
    }
</style>
<div class="layui-fluid">
    <div class="layui-card">
<div class="lay-lists">
<form class="layui-form">
    <blockquote class="layui-elem-quote quoteBox">
        <div class="layui-inline">
            <a class="layui-btn" data-type="url" data-title="添加订单包裹" data-url="<?=Url::to(['order-logistics-pack/create'])?>" data-callback_title = "订单包裹列表" >添加订单包裹</a>
        </div>
    </blockquote>
</form>
    <form>

        <div class="layui-form lay-search" style="padding: 10px">
        
            <div class="layui-inline">
            	<label>快递单号</label>
        		<input class="layui-input search-con" name="OrderLogisticsPackSearch[tracking_number]" autocomplete="off">
    		</div>
    		
        <div class="layui-inline">
            <label>订单号</label>
            <textarea name="OrderLogisticsPackSearch[order_id]" class="layui-textarea search-con" style="height: 39px; min-height:39px"></textarea>
        </div>
    		  		
            <div class="layui-inline">
               物流渠道：
                <?= Html::dropDownList('OrderLogisticsPackSearch[channels_type]',null,TransportProviders::getTransportName(),
                    ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px' ]) ?>
            </div>
                                    
    
    		<div class="layui-inline">
               操作者：
                <?= Html::dropDownList('OrderLogisticsPackSearch[admin_id]',null,$order->admin(),
                    ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search'])?>
            </div>
    
            
             <div class="layui-inline">
                <label>发货日期</label>
                <input  class="layui-input search-con ys-date" name="OrderLogisticsPackSearch[start_date]"  id="start_date"  autocomplete="off">
            </div>
            <span class="layui-inline layui-vertical-20">
                -
            </span>
            <div class="layui-inline layui-vertical-20">
                <input  class="layui-input search-con ys-date" name="OrderLogisticsPackSearch[end_date]"  id="end_date" autocomplete="off">
            </div>
            
            
            <div class="layui-inline layui-vertical-20">
                <button class="layui-btn" data-type="search_lists">搜索</button>
                <button class="layui-btn layui-btn-normal" data-title="生成包裹"  data-width="1250px" data-height="550px" data-type="open" data-url="<?=Url::to(['order-logistics-pack/create-logistics'])?>">生成包裹</button>
            </div>
        </div>
    </form>
    <div class="layui-card-body">
<table id="order-logistics-pack" class="layui-table" lay-data="{url:'<?=Url::to(['order-logistics-pack/list'])?>', height : 'full-100', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20}" lay-filter="order-logistics-pack">
    <thead>
    <tr>
    	<th lay-data="{field: 'ship_dates', align:'center',width:150}">发货日期</th>
    	<th lay-data="{field: 'tracking_number', align:'center', width:200}">快递单号</th>
    	<th lay-data="{field: 'courier', align:'center', width:100}">快递商</th>
        <th lay-data="{field: 'channels_type', width:100, align:'center', width:150}">物流渠道</th>
        <th lay-data="{field: 'quantity', align:'center', width:100}">件数</th>
        <th lay-data="{field: 'weight', align:'center', width:100}">重量(kg)</th>
        <th lay-data="{field: 'remarks', align:'center', width:150}">备注</th> 
        <th lay-data="{field: 'admin_id', align:'center', width:100}">操作者</th>
        <th lay-data="{field: 'add_time', align:'center', minwidth:100}">添加时间</th>
        <th lay-data="{field: 'update_time', align:'center', minwidth:100}">修改时间</th>
        <th lay-data="{minWidth:220, templet:'#listBar',fixed:'right',align:'center'}">操作</th>
    </tr>
    </thead>
</table>
</div>
</div>
    </div>
</div>

<!--操作-->
<script type="text/html" id="listBar">
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update"  data-url="<?=Url::to(['order-logistics-pack/update'])?>?id={{ d.id }}" data-title="订单包裹编辑" >编辑</a>
     <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete" data-url="<?=Url::to(['order-logistics-pack/delete'])?>?id={{ d.id }}">删除</a><br>
     <a class="layui-btn layui-btn-xs" lay-event="update" data-url="<?=Url::to(['order-logistics-pack/view'])?>?id={{ d.id }}" data-title="订单包裹列表">查看订单</a>
    <a class="layui-btn layui-btn-xs layui-btn-primary layui-border-black" lay-event="fun" data-fun="print_tag" data-id="{{ d.id }}">打印包裹单</a><br>
    {{# if(d.now_time >= d.start_time && d.now_time < d.end_time){ }}
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="operating" data-url="<?=Url::to(['order-logistics-pack/update-logistics'])?>?id={{ d.id }}" data-title="重新更新" >更新包裹</a>
    {{# } }}
    <a class="layui-btn layui-btn-warm layui-btn-xs " lay-event="export" data-url="<?=Url::to(['order-logistics-pack/exports'])?>?id={{ d.id }}" data-title="导出" >导出</a>
</script>

<script>
    const tableName="order-logistics-pack";
</script>
<?php
    $this->registerJsFile("@adminPageJs/base/lists.js?".time());
?>`
<?php
$this->registerJsFile('@adminPlugins/extend/lodop.js?v=0.2',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPageJs/order-logistics-pack/lists.js?v=".time());
$this->registerJsFile('@adminPlugins/export/xlsx.core.min.js?v=1',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export-excel.js?v=1',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export-function.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
?>

