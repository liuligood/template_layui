
<?php

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
    .layui-btn-a {
        border: none;
        background-color: rgba(0, 0, 0, 0);
        -webkit-user-select:text;
    }
</style>
<div class="layui-fluid">
    <div class="layui-card">
        <div class="layui-card-body">
        <div class="lay-lists">
            <div class="layui-form lay-search" style="padding-left: 10px">

                <div class="layui-inline">
                    <label>商品编号</label>
                    <textarea name="WarehouseProductSalesSearch[goods_no]" class="layui-textarea search-con" style="height: 39px; min-height:39px"></textarea>
                </div>

                <div class="layui-inline">
                    <label>SKU</label>
                    <textarea name="WarehouseProductSalesSearch[sku_no]" class="layui-textarea search-con" style="height: 39px; min-height:39px"></textarea>
                </div>

                <div class="layui-inline">
                    <label>仓库</label>
                    <?= Html::dropDownList('WarehouseProductSalesSearch[warehouse_id]',null,WarehouseService::getWarehouseMap(),
                        ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search', 'style' => 'width:155px']) ?>
                </div>

                <div class="layui-inline">
                    <label>安全库存类型</label>
                    <?= Html::dropDownList('WarehouseProductSalesSearch[safe_stock_type]',null,[0 => '未设置类型'] + WarehouseProductSales::$type_maps,
                        ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search', 'style' => 'width:155px']) ?>
                </div>

                <div class="layui-inline">
                    <label>有数量</label>
                    <?= Html::dropDownList('WarehouseProductSalesSearch[has_numbers]',null,[1 => '销量', 2 => '库存', 3 => '在途', 4 => '采购中',5 => '无销量', 6 => '无库存', 7 => '无在途', 8 => '无采购'],
                        ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style' => 'width:210px',"multiple"=>"multiple"]) ?>
                </div>

                <button class="layui-btn" data-type="search_lists" style="margin-top: 24px">搜索</button>
            </div>
            <div class="layui-card-body">
                <table id="warehouse-product-sales" class="layui-table" lay-data="{url:'<?=Url::to(['warehouse-product-sales/list'])?>', height : 'full-20', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20}" lay-filter="warehouse-product-sales">
                    <thead>
                    <tr>
                        <th lay-data="{width:140, align:'center',templet:'#goodsImgTpl'}">商品主图</th>
                        <th lay-data="{width:240, align:'center',templet:'#goodsTpl'}">商品信息</th>
                        <th lay-data="{field: 'one_day_sales',  align:'center',width:80}">1日</th>
                        <th lay-data="{field: 'seven_day_sales',  align:'center',width:80}">7日</th>
                        <th lay-data="{field: 'fifteen_day_sales',  align:'center',width:80}">15日</th>
                        <th lay-data="{field: 'thirty_day_sales',  align:'center',width:80}">30日</th>
                        <th lay-data="{field: 'ninety_day_sales',  align:'center',width:80}">90日</th>
                        <th lay-data="{field: 'order_frequency',  align:'left',width:110}">出单频率(天)</th>
                        <th lay-data="{field: 'total_sales',  align:'center',width:90}">总销量</th>
                        <th lay-data="{width:175, templet:'#stockBar',align:'left'}">库存</th>
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
    {{# if(d.warehouse_type != <?=WarehouseProvider::TYPE_B2B?>){ }}
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['warehouse-product-sales/update'])?>?cgoods_no={{ d.ccgoods_no }}&warehouse_id={{ d.warehouse }}" data-title="编辑" data-callback_title="仓库商品销售情况列表">安全库存</a>
    {{# } }}

    {{# if(d.warehouse_type == <?=WarehouseProvider::TYPE_PLATFORM?> || d.warehouse_type == <?=WarehouseProvider::TYPE_THIRD_PARTY?>){ }}
    <a class="layui-btn layui-btn-xs" lay-event="open" data-height="450px" data-width="800px" data-url="<?=Url::to(['warehouse-product-sales/add-purchase'])?>?cgoods_no={{ d.ccgoods_no }}&warehouse_id={{ d.warehouse }}" data-title="加入采购计划">加入采购计划</a>
    {{# } }}
</script>

<script type="text/html" id="stockBar">
    仓库：{{ d.warehouse_name || '0' }}<br/>
    库存数：<a lay-event="open" data-width="850px" data-height="250px" data-title="库存数" data-url="<?=Url::to(['goods/stock-view'])?>?warehouse_id={{ d.warehouse }}&cgoods_no={{ d.ccgoods_no }}" style="color: {{# if(d.inventory_quantity != '' || d.inventory_quantity > 0){ }}#009688{{# } else{ }}#FF5722{{# } }}">{{d.inventory_quantity ||'0'}}</a>
    <br/>
    在途数：{{#if (d.transit_quantity != 0 && d.transit_quantity) { }}<a lay-event="open" data-width="1050px" data-height="600px" data-title="在途数" data-url="<?=Url::to(['goods/transit-quantity-view'])?>?warehouse_id={{ d.warehouse }}&cgoods_no={{ d.ccgoods_no }}" style="color: #00a0e9">{{d.transit_quantity}}</a>
    {{# }else{ }}{{d.transit_quantity ||'0'}}{{# } }}
    <br/>
    采购中：{{ d.purchasing || '0' }}<br/>
    安全库存数：{{# if(d.safe_stock_type == 0 || d.safe_stock_type == null){}}未设置{{# }else{ }} {{ d.safe_stock_num || '0' }}{{# }}}
</script>

<script type="text/html" id="goodsImgTpl">
    {{# if(d.image){ }}
    <a href="{{d.image}}" data-lightbox="pic">
        <img class="layui-circle" src="{{d.image}}?imageView2/2/h/100" style="max-width: 100px;max-height: 100px" />
    </a>
    {{# } }}
</script>

<script type="text/html" id="goodsTpl">
    <b class="sku_no">{{d.sku_no}}</b><br/>
    <div class="span-goode-name" style="white-space:pre-wrap;">{{d.goods_name_cn || (d.goods_name||'')}}</div>
    <div class="span-goode-name">{{d.colour || ''}} {{d.size || ''}}</div>
    <a class="layui-btn layui-btn-xs layui-btn-a" lay-event="update" data-url="<?=Url::to(['order/{{ d.construct }}-index?OrderSearch%5Bplatform_asin%5D='])?>{{ d.sku_no }}&OrderSearch%5Bwarehouse%5D={{ d.warehouse }}&tag=10" data-title="订单信息" style="color: #00a0e9"><i class="layui-icon layui-icon-template"></i></a>
    <a lay-event="update" data-url="<?=Url::to(['goods/view'])?>?goods_no={{ d.goods_no }}" style="color: #00a0e9">{{d.goods_no}}</a>
</script>

<script>
    const tableName="warehouse-product-sales";
</script>
<?php $this->registerJsFile("@adminPageJs/base/lists.js");
$this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
?>