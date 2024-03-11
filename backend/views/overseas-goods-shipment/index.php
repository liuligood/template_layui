<?php

use common\models\Shop;
use common\models\Supplier;
use common\models\warehousing\OverseasGoodsShipment;
use common\models\warehousing\WarehouseProductSales;
use common\services\warehousing\WarehouseService;
use common\models\warehousing\WarehouseProvider;
use yii\helpers\Html;
use yii\helpers\Url;
?>
<style>
    .layui-vertical-20{
        padding-top: 20px;
    }
    .layui-table-body .layui-table-cell{
        height:auto;
    }
    .layui-vertical-20{
        padding-top: 20px;
    }
    .layui-tab-title .layui-this {
        background-color: #fff;
        color: #000;
    }
    .layui-tab-brief > .layui-tab-title .layui-this a{
        color: rgb(0, 150, 136);
    }
    .layui-tab{
        margin-top: 0;
    }
    .span-goode-name{
        color:#a0a3a6;
        font-size: 12px;
        width: 200px;
        height: auto;
        display: block;
        word-wrap:break-word;
        line-height: 20px;
    }
</style>
<div class="layui-fluid">
    <div class="layui-card">
        <div class="layui-tab layui-tab-brief">
            <ul class="layui-tab-title">
                <li <?php if($tag == 99){?>class="layui-this" <?php }?>><a href="<?=Url::to(['overseas-goods-shipment/index?tag=99'])?>">全部</a></li>
                <li <?php if($tag == 1){?>class="layui-this" <?php }?>><a href="<?=Url::to(['overseas-goods-shipment/index?tag=1'])?>">计划采购</a></li>
                <li <?php if($tag == 10){?>class="layui-this" <?php }?>><a href="<?=Url::to(['overseas-goods-shipment/index?tag=10'])?>">待采购</a></li>
                <li <?php if($tag == 20){?>class="layui-this" <?php }?>><a href="<?=Url::to(['overseas-goods-shipment/index?tag=20'])?>">待到货</a></li>
                <li <?php if($tag == 30){?>class="layui-this" <?php }?>><a href="<?=Url::to(['overseas-goods-shipment/index?tag=30'])?>">待装箱</a></li>
                <li <?php if($tag == 40){?>class="layui-this" <?php }?>><a href="<?=Url::to(['overseas-goods-shipment/index?tag=40'])?>">已完成</a></li>
                <li <?php if($tag == 50){?>class="layui-this" <?php }?>><a href="<?=Url::to(['overseas-goods-shipment/index?tag=50'])?>">作废</a></li>
            </ul>
        </div>
        <div class="layui-card-body">
            <div class="lay-lists">
                <div class="layui-form lay-search" style="padding-left: 10px">

                    <div class="layui-inline">
                        <label>商品编号</label>
                        <textarea name="OverseasGoodsShipmentSearch[goods_no]" class="layui-textarea search-con" style="height: 39px; min-height:39px"></textarea>
                    </div>

                    <div class="layui-inline">
                        <label>SKU</label>
                        <textarea name="OverseasGoodsShipmentSearch[sku_no]" class="layui-textarea search-con" style="height: 39px; min-height:39px"></textarea>
                    </div>

                    <div class="layui-inline">
                        <label>仓库</label>
                        <?= Html::dropDownList('OverseasGoodsShipmentSearch[warehouse_id]',null,WarehouseService::getOverseasWarehouse(),
                            ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search', 'style' => 'width:155px']) ?>
                    </div>

                    <div class="layui-inline">
                        <label>店铺</label>
                        <?= Html::dropDownList('OverseasGoodsShipmentSearch[shop_id]',null,$overseas_shop,
                            ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search', 'style' => 'width:155px']) ?>
                    </div>

                    <?php if ($tag == 99) {?>
                    <div class="layui-inline">
                        <label>状态</label>
                        <?= Html::dropDownList('OverseasGoodsShipmentSearch[select_status]',null,OverseasGoodsShipment::$status_maps,
                            ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search', 'style' => 'width:155px']) ?>
                    </div>
                    <?php }?>

                    <div class="layui-inline">
                        <label>供应商</label>
                        <?= Html::dropDownList('OverseasGoodsShipmentSearch[supplier_id]',null,[0 => '无供应商'] + Supplier::allSupplierName(),
                            ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search', 'style' => 'width:155px']) ?>
                    </div>

                    <button class="layui-btn" data-type="search_lists" style="margin-top: 24px">搜索</button>
                </div>
                <div class="layui-form" style="padding: 8px 10px">
                    <?php if ($tag == 1) { ?>
                    <div class="layui-inline">
                        <a class="layui-btn layui-btn-sm layui-btn-normal js-batch" data-title="批量确认" data-url="<?=Url::to(['overseas-goods-shipment/batch-confirm?status='.OverseasGoodsShipment::STATUS_WAIT_PURCHASE])?>">批量确认</a>
                    </div>
                    <?php }?>
                    <?php if ($tag == 10) { ?>
                        <div class="layui-inline">
                            <a class="layui-btn layui-btn-sm layui-btn-normal js-batch-purchase" data-title="批量采购" data-url="<?=Url::to(['purchase-order/create'])?>">批量采购</a>
                        </div>
                    <?php }?>
                    <?php if (in_array($tag,[1,10,30])){?>
                    <div class="layui-inline">
                        <a class="layui-btn layui-btn-sm layui-btn-danger js-batch" data-title="批量作废" data-url="<?=Url::to(['overseas-goods-shipment/batch-confirm?status='.OverseasGoodsShipment::STATUS_CANCELLED])?>">批量作废</a>
                    </div>
                    <?php }?>
                    <?php if ($tag == 20) { ?>
                        <div class="layui-inline">
                            <a class="layui-btn layui-btn-sm layui-btn-normal js-batch-arrival" data-title="批量到货" data-url="<?=Url::to(['overseas-goods-shipment/arrival'])?>">批量到货</a>
                        </div>
                        <div class="layui-inline">
                            <a class="layui-btn layui-btn-sm layui-btn-warm js-batch" data-title="批量打回待采购" data-url="<?=Url::to(['overseas-goods-shipment/batch-confirm?status='.OverseasGoodsShipment::STATUS_WAIT_PURCHASE.'&is_del=1'])?>">打回待采购</a>
                        </div>
                    <?php }?>
                    <?php if ($tag == 30) { ?>
                        <div class="layui-inline">
                            <a class="layui-btn layui-btn-sm js-batch-packed" data-title="批量装箱" data-url="<?=Url::to(['overseas-goods-shipment/crating'])?>">批量装箱</a>
                        </div>
                        <div class="layui-inline">
                            <a class="layui-btn layui-btn-sm layui-btn-normal js-batch" data-title="批量设置空运" data-url="<?=Url::to(['overseas-goods-shipment/set-air-logistics'])?>">批量设置空运</a>
                        </div>
                    <?php }?>
                </div>
                <div class="layui-card-body">
                    <table id="overseas-goods-shipment" class="layui-table" lay-data="{url:'<?=Url::to(['overseas-goods-shipment/list?tag='.$tag])?>', height : 'full-20', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20}" lay-filter="overseas-goods-shipment">
                        <thead>
                        <tr>
                            <th lay-data="{type: 'checkbox', width:50,field: 'id'}"></th>
                            <th lay-data="{width:140, align:'center',templet:'#goodsImgTpl'}">商品主图</th>
                            <th lay-data="{width:240, align:'center',templet:'#goodsTpl'}">商品信息</th>
                            <th lay-data="{width:180, align:'left',templet:'#purchaseTpl'}">采购信息</th>
                            <th lay-data="{width:210, align:'left',templet:'#packageTpl'}">包装信息</th>
                            <th lay-data="{width:210, align:'left',templet:'#warehouseTpl'}">库存信息</th>
                            <th lay-data="{minWidth:50, align:'left',templet:'#dateTpl'}">时间</th>
                            <th lay-data="{minWidth:175, templet:'#listBar',align:'center'}">操作</th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!--操作-->
<script type="text/html" id="listBar">
    {{# if (d.status == <?=OverseasGoodsShipment::STATUS_WAIT_PURCHASE?> || d.status == <?=OverseasGoodsShipment::STATUS_UNCONFIRMED?> ) { }}
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="open" data-width="600px" data-height="450px" data-url="<?=Url::to(['overseas-goods-shipment/update'])?>?id={{ d.id }}" data-title="编辑" data-callback_title="海外仓发货列表">编辑</a>
    {{# } }}
    {{# if (d.status == <?=OverseasGoodsShipment::STATUS_WAIT_SHIP?>) { }}
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="open" data-width="1000px" data-height="600px" data-url="<?=Url::to(['overseas-goods-shipment/arrival'])?>?id={{ d.id }}" data-title="到货" data-callback_title="海外仓发货列表">到货</a>
    {{# } }}
</script>

<script type="text/html" id="purchaseTpl">
    数量：{{ d.num || '0' }}<br/>
    供应商：{{ d.supplier_name || '' }}<br/>
    仓库：{{ d.warehouse_name || '' }}<br/>
    {{#if (d.shop_name != '') { }}店铺: {{d.shop_name}}{{# }  }}
</script>

<script type="text/html" id="packageTpl">
    {{# for(let i in d.goods_packing){
    var item = d.goods_packing[i];}}
    {{ item.size }}
    <span>{{#if (item.show_name != '') { }}  ({{ item.show_name }})  {{# }  }}</span>
    *
    {{ item.packages_num }}<br/>
    {{# } }}
    备注：<span class="span-goode-name" style="display:inline;white-space:pre-wrap;color: #666">
        {{ d.purchase_desc || '' }}
    </span>
</script>

<script type="text/html" id="goodsImgTpl">
    {{# if(d.image){ }}
    <a href="{{d.image}}" data-lightbox="pic">
        <img class="layui-circle" src="{{d.image}}?imageView2/2/h/100" style="max-width: 100px;max-height: 100px" />
    </a>
    {{# } }}
</script>

<script type="text/html" id="warehouseTpl">
    在途：{{ d.transit_quantity || 0 }}<br/>
    库存数：{{ d.inventory_quantity || 0 }}<br/>
    <?php if ($tag == 99) {?>
    状态：{{ d.status_name || '' }}
    <?php }?>
</script>

<script type="text/html" id="goodsTpl">
    <b class="sku_no">{{d.sku_no}}</b>
    {{#if (d.air_logistics == 1) { }}
    <span style="padding: 5px 10px;height: 20px" class="layui-font-12 layui-bg-orange">空</span>
    {{# } }}
    <br/>
    <div class="span-goode-name" style="white-space:pre-wrap;">{{d.goods_name_cn || (d.goods_name||'')}}</div>
    <div class="span-goode-name">{{d.colour || ''}} {{d.size || ''}}</div>
    <a lay-event="update" data-url="<?=Url::to(['goods/view'])?>?goods_no={{ d.goods_no }}" style="color: #00a0e9">{{d.goods_no}}</a>
</script>

<script type="text/html" id="dateTpl">
    添加：{{ d.add_time }}<br/>
    {{# if (d.status != <?=OverseasGoodsShipment::STATUS_WAIT_PURCHASE?> || d.status != <?=OverseasGoodsShipment::STATUS_UNCONFIRMED?> ) { }}
    采购：{{ d.purchase_time }}<br/>
    {{# } }}
    {{# if (d.status == <?=OverseasGoodsShipment::STATUS_WAIT_PACKED?> || d.status == <?=OverseasGoodsShipment::STATUS_FINISH?>) { }}
    到货：{{ d.arrival_time }}<br/>
    {{# } }}
    {{# if (d.status == <?=OverseasGoodsShipment::STATUS_FINISH?>) { }}
    装箱：{{ d.packing_time }}<br/>
    {{# } }}
</script>

<script>
    const tableName="overseas-goods-shipment";
</script>
<?php $this->registerJsFile("@adminPageJs/base/lists.js");
$this->registerJsFile("@adminPageJs/overseas-goods-shipment/lists.js?v=".time());
$this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
?>