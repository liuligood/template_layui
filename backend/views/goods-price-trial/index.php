<?php

use common\components\statics\Base;
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
                <li <?php if($platform_type == Base::PLATFORM_OZON){?>class="layui-this" <?php }?>><a href="<?=Url::to(['goods_price_trial/index?platform_type='.Base::PLATFORM_OZON])?>">Ozon</a></li>
            </ul>
        </div>
        <div class="layui-card-body">
            <div class="lay-lists">
                <div class="layui-form lay-search" style="padding-left: 10px">

                    <div class="layui-inline">
                        <label>商品编号</label>
                        <textarea name="GoodsPriceTrialSearch[goods_no]" class="layui-textarea search-con" style="height: 39px; min-height:39px"></textarea>
                    </div>

                    <div class="layui-inline">
                        <label>SKU</label>
                        <textarea name="GoodsPriceTrialSearch[sku_no]" class="layui-textarea search-con" style="height: 39px; min-height:39px"></textarea>
                    </div>

                    <button class="layui-btn" data-type="search_lists" style="margin-top: 24px">搜索</button>
                    <button class="layui-btn layui-btn-primary" data-type="url" data-title="添加商品" data-url="<?=Url::to(['goods/select-index?tag=1'])?>" style="margin-top: 24px">添加商品</button>
                </div>
                <div class="layui-form" style="padding: 8px 10px">

                </div>
                <div class="layui-card-body">
                    <table id="goods-price-trial" class="layui-table" lay-data="{url:'<?=Url::to(['goods-price-trial/list?platform_type='.$platform_type])?>', height : 'full-20', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20}" lay-filter="goods-price-trial">
                        <thead>
                        <tr>
                            <th lay-data="{type: 'checkbox', width:50,field: 'id'}"></th>
                            <th lay-data="{width:140, align:'center',templet:'#goodsImgTpl'}">商品主图</th>
                            <th lay-data="{width:240, align:'center',templet:'#goodsTpl'}">商品信息</th>
                            <th lay-data="{width:120, align:'left',field: 'price', edit: 'price'}"><?=$title['price']?></th>
                            <th lay-data="{width:120, align:'left',field: 'cost_price', edit: 'price'}"><?=$title['cost_price']?></th>
                            <th lay-data="{width:120, align:'left',field: 'start_logistics_cost', edit: 'price'}"><?=$title['start_logistics_cost']?></th>
                            <th lay-data="{width:140, align:'left',templet:'#volumetricTpl'}"><?=$title['volumetric']?></th>
                            <th lay-data="{width:190, align:'left',templet:'#index1Tpl'}"><?=$title['index1']?></th>
                            <th lay-data="{width:190, align:'left',templet:'#index2Tpl'}"><?=$title['index2']?></th>
                            <th lay-data="{width:190, align:'left',templet:'#logistics_fee_1Tpl'}"><?=$title['logistics_fee_1']?></th>
                            <th lay-data="{minWidth:175, align:'left',templet:'#logistics_fee_2Tpl'}"><?=$title['logistics_fee_2']?></th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/html" id="goodsImgTpl">
    {{# if(d.image){ }}
    <a href="{{d.image}}" data-lightbox="pic">
        <img class="layui-circle" src="{{d.image}}?imageView2/2/h/100" style="max-width: 100px;max-height: 100px" />
    </a>
    {{# } }}
</script>

<script type="text/html" id="volumetricTpl">
    升：{{ d.litre || '0' }}<br/>
    体积：{{ d.cube || '0' }}<br/>
    头程费用：{{ d.star_cost_price || '0' }}<br/>
</script>

<script type="text/html" id="index1Tpl">
    {{# for(let i in d.index1){
    var item = d.index1[i];}}
    {{ i }}：{{ item }}<br/>
    {{# } }}
</script>

<script type="text/html" id="index2Tpl">
    {{# for(let i in d.index2){
    var item = d.index2[i];}}
    {{ i }}：{{ item }}<br/>
    {{# } }}
</script>

<script type="text/html" id="logistics_fee_1Tpl">
    {{# for(let i in d.logistics_fee_1){
    var item = d.logistics_fee_1[i];}}
    {{ i }}：
    {{#if (i == '利润'){}}
    {{#if(item > 0){}}
    <span style="color: #00aa00">{{ item }}</span>
    {{# }else{ }}
    <span style="color: #e03e2d">{{ item }}</span>
    {{# } }}
    {{# }else{ }}
    {{ item }}
    {{# } }}
    <br/>
    {{# } }}
</script>

<script type="text/html" id="logistics_fee_2Tpl">
    {{# for(let i in d.logistics_fee_2){
    var item = d.logistics_fee_2[i];}}
    {{ i }}：
    {{#if (i == '利润'){}}
        {{#if(item > 0){}}
            <span style="color: #00aa00">{{ item }}</span>
        {{# }else{ }}
            <span style="color: #e03e2d">{{ item }}</span>
        {{# } }}
    {{# }else{ }}
        {{ item }}
    {{# } }}
    <br/>
    {{# } }}
</script>

<script type="text/html" id="goodsTpl">
    <b class="sku_no">{{d.sku_no}}</b><br/>
    <div class="span-goode-name" style="white-space:pre-wrap;">{{d.goods_name_cn || (d.goods_name||'')}}</div>
    <div class="span-goode-name">{{d.colour || ''}} {{d.package_size || ''}} {{d.weight || ''}}</div>
    <a lay-event="update" data-url="<?=Url::to(['goods/view'])?>?goods_no={{ d.goods_no }}" style="color: #00a0e9">{{d.goods_no}}</a><br/>
    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete" data-url="<?=Url::to(['goods-price-trial/delete'])?>?id={{ d.id }}">删除</a>
</script>

<script>
    const tableName="goods-price-trial";
    const platform_type = '<?=$platform_type?>';
</script>
<?php
$this->registerJsFile("@adminPageJs/base/lists.js");
$this->registerJsFile("@adminPageJs/goods-price-trial/lists.js?v=".time());
$this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
?>