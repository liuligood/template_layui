
<?php

use common\services\ShopService;
use yii\helpers\Url;
use yii\helpers\Html;
?>
<style>
    .layui-laypage li{
        float: left;
    }
    .layui-laypage .active a{
        background-color: #009688;
        color: #fff;
    }
    .layui-vertical-20{
        padding-top: 20px;
    }
    .layui-tab-title .layui-this {
        background-color: #fff;
        color: #000;
    }
</style>
<div class="layui-fluid">
    <div class="layui-card">
<div class="lay-lists">
    <form class="layui-form">
        <blockquote class="layui-elem-quote quoteBox">
            <div class="layui-inline">
            <?= Html::dropDownList('GoodsStockCheckSearch[cycle_id]', null, $cycle_lists,
                ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search' ,'id'=>'ly_cycle_id','lay-filter'=>"ly_cycle_id"]) ?>
            </div>
            <div class="layui-inline">
                <a id="create-cycle" class="layui-btn" data-title="新一轮检测" data-url="<?=Url::to(['goods-stock-check/create-cycle'])?>" >新一轮检测</a>
            </div>
        </blockquote>
    <div class="layui-form lay-search" style="padding-left: 10px">
        <div class="layui-inline">
            <label>来源平台</label>
            <?= Html::dropDownList('GoodsStockCheckSearch[source]', null, \yii\helpers\ArrayHelper::map(\common\services\FGrabService::$source_map,'id','name'),
                ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px','id'=>'platform' ]) ?>
        </div>

        <div class="layui-inline">
            店铺
            <?= \backend\widgets\LinkageDropDownList::widget(['id' => 'shop','parent_id'=>'platform','name'=>'GoodsStockCheckSearch[shop_id]','select'=>null,'option'=> ShopService::getOrderShopMap(),'param'=>['data-placeholder' => '全部','prompt' => '全部','style'=>'width:185px']]) ?>
        </div>

        <div class="layui-inline">
            <label>SKU</label>
            <input class="layui-input search-con" name="GoodsStockCheckSearch[sku_no]" value=""  autocomplete="off">
        </div>
        <div class="layui-inline">
            <label>历史库存状态</label>
            <?= Html::dropDownList('GoodsStockCheckSearch[old_stock]', null, \common\models\Goods::$stock_map,
                ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px' ]) ?>
        </div>
        <div class="layui-inline">
            <label>库存状态</label>
            <?= Html::dropDownList('GoodsStockCheckSearch[stock]', null, \common\models\Goods::$stock_map,
                ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px' ]) ?>
        </div>

        <div class="layui-inline">
            时间
            <input class="layui-input search-con ys-date" name="GoodsStockCheckSearch[start_add_time]" id="start_add_time" >
        </div>
        <span class="layui-inline layui-vertical-20">
            -
        </span>
        <div class="layui-inline layui-vertical-20">
            <input class="layui-input search-con ys-date" name="GoodsStockCheckSearch[end_add_time]" id="end_add_time" >
        </div>
        <div class="layui-inline layui-vertical-20">
        <button id="sea-stock" class="layui-btn" data-type="search_lists">搜索</button>

        <button class="layui-btn layui-btn-normal" data-type="export_lists" data-url="<?=Url::to(['goods-stock-check/export'])?>">导出</button>
        </div>
    </div>
    </form>
    <div class="layui-card-body">
    <table id="goods-stock-check" class="layui-table" lay-data="{url:'<?=Url::to(['goods-stock-check/list'])?>', height : 'full-190', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20}" lay-filter="goods-stock-check">
    <thead>
    <tr>
        <th lay-data="{field: 'source_desc', align:'center', width:120}">来源平台</th>
        <th lay-data="{field: 'shop_name', align:'center', width:120}">店铺</th>
        <th lay-data="{field: 'sku_no', align:'center', width:130}">SKU</th>
        <th lay-data="{field: 'old_stock_desc',  align:'left',minWidth:120}">历史库存状态</th>
        <th lay-data="{field: 'stock_desc',  align:'left',width:120}">库存状态</th>
        <th lay-data="{field: 'add_time',  align:'center',width:150}">时间</th>
    </tr>
    </thead>
</table>
    </div>
</div>
    </div>
</div>
<script>
    const tableName="goods-stock-check";
</script>
<?php
    $this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.4.1");
    $this->registerJsFile("@adminPageJs/goods-stock-check/lists.js?v=".time());
    $this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
    $this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
    $this->registerJsFile('@adminPlugins/export/xlsx.core.min.js?v=1',['depends'=>'yii\web\JqueryAsset']);
    $this->registerJsFile('@adminPlugins/export/export-excel.js?v=1',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export-function.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
?>