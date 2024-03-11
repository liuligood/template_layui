<?php

use common\components\statics\Base;
use common\models\warehousing\WarehouseProvider;
use common\services\warehousing\WarehouseService;
use yii\helpers\Html;
use yii\helpers\Url;
?>
<object id="LODOP_OB" classid="clsid:2105C259-1E0C-4534-8141-A753534CB4CA" width=0 height=0 style="display: none">
    <embed id="LODOP_EM" type="application/x-print-lodop" width=0 height=0 pluginspage="install_lodop32.exe"></embed>
</object>
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
        width: 240px;
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
                <?php foreach ($warehouse_lists as $v){ ?>
                <li <?php if($warehouse_id == $v['id']){?>class="layui-this" <?php }?>><a href="<?=Url::to(['warehouse-goods/index?warehouse_id='.$v['id']])?>"><?=$v['warehouse_name']?></a></li>
                <?php }?>
            </ul>
        </div>
<div class="lay-lists">

    <!--<form class="layui-form">
        <blockquote class="layui-elem-quote quoteBox">
            <div class="layui-inline">
                <a class="layui-btn" data-type="url" data-title="创建单个货架" data-url="<?=Url::to(['warehouse-goods/create'])?>" data-callback_title = "货架列表" >创建单个货架</a>
            </div>
        </blockquote>
    </form>-->
    <div class="layui-card-body">
    <form>
    <div class="layui-form lay-search" style="padding-bottom: 10px">
        <div class="layui-inline">
            商品编号
            <textarea name="WarehouseGoodsSearch[goods_no]" class="layui-textarea search-con" style="height: 39px; min-height:39px"></textarea>
        </div>
        <div class="layui-inline">
            SKU
            <textarea id="sku_no" name="WarehouseGoodsSearch[sku_no]" class="layui-textarea search-con" style="height: 39px; min-height:39px"></textarea>
        </div>
        <?php if($warehouse_id==\common\services\warehousing\WarehouseService::WAREHOUSE_ANJ){?>
            <div class="layui-inline">
                SKU(安骏)
                <textarea name="WarehouseGoodsSearch[other_sku]" class="layui-textarea search-con" style="height: 39px; min-height:39px"></textarea>
            </div>
        <?php } ?>
        <div class="layui-inline">
            货架编号
            <input class="layui-input search-con" name="WarehouseGoodsSearch[shelves_no]" autocomplete="off">
        </div>
        <div class="layui-inline">
            平台类目
            <div id="div_category_id" style="width: 180px;"></div>
            <input id="category_id" class="layui-input search-con" type="hidden" name="WarehouseGoodsSearch[category_id]" autocomplete="off">
        </div>
        <div class="layui-inline">
            添加时间
            <input  class="layui-input search-con ys-date" name="WarehouseGoodsSearch[start_time]" id="start_time" autocomplete="off">
        </div>
        <span class="layui-inline layui-vertical-20">
            -
        </span>
        <div class="layui-inline layui-vertical-20">
            <input  class="layui-input search-con ys-date" name="WarehouseGoodsSearch[end_time]" id="end_time" autocomplete="off" style="margin-top: 4px">
        </div>
        <div class="layui-inline">
            <div style="padding-left: 10px">
                <input class="layui-input search-con" type="checkbox" value="1" name="WarehouseGoodsSearch[has_num]" lay-skin="primary" title="有库存">
            </div>
        </div>
        <?php if(in_array($warehouse['warehouse_provider']['warehouse_provider_type'],[WarehouseProvider::TYPE_PLATFORM,WarehouseProvider::TYPE_THIRD_PARTY])){?>
        <div class="layui-inline">
            <div style="padding-left: 10px">
                <input class="layui-input search-con" type="checkbox" value="1" name="WarehouseGoodsSearch[has_normal]" lay-skin="primary" title="异常库存">
            </div>
        </div>
        <?php }?>
        <div class="layui-inline layui-vertical-20">
            <button id="search-btn" class="layui-btn" data-type="search_lists">搜索</button>

            <button class="layui-btn layui-btn-primary" data-type="url" data-title="添加商品" data-url="<?=Url::to(['goods/select-index?tag=1&sub_tag='.$warehouse_id])?>" >添加商品</button>

            <?php if($warehouse_id==\common\services\warehousing\WarehouseService::WAREHOUSE_ANJ){?>
                <button class="layui-btn layui-btn-primary ys-upload" lay-data="{url: '/warehouse-goods/import-sku/',accept: 'file'}">导入sku</button>
            <?php } ?>

            <?php if(in_array($warehouse['warehouse_provider']['warehouse_provider_type'],[WarehouseProvider::TYPE_PLATFORM,WarehouseProvider::TYPE_THIRD_PARTY])){?>
                <button class="layui-btn layui-btn-warm" data-type="export_lists" data-url="<?=Url::to(['warehouse-goods/export?warehouse_id='.$warehouse_id])?>">导出</button>
            <?php }?>
            <!--<button class="layui-btn ys-pri" data-url="http://yadmin.sanlinmail.site/order/printed-pdf?order_id=O06456385266088">测试打印</button>-->
        </div>
    </div>
    </form>
    <table id="warehouse-goods" class="layui-table" lay-data="{url:'<?=Url::to(['warehouse-goods/list?warehouse_id='.$warehouse_id])?>', height : 'full-20', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20}" lay-filter="warehouse-goods">
    <thead>
    <tr>
        <th lay-data="{width:140, align:'center',templet:'#goodsImgTpl'}">商品主图</th>
        <th lay-data="{width:200, align:'center',templet:'#goodsTpl'}">商品信息</th>
        <th lay-data="{width:280, align:'center',templet:'#goodsNameTpl'}">商品标题</th>
        <?php if($warehouse_id==\common\services\warehousing\WarehouseService::WAREHOUSE_ANJ || $warehouse['warehouse_provider']['warehouse_provider_type'] == \common\models\warehousing\WarehouseProvider::TYPE_THIRD_PARTY){?>
        <th lay-data="{field: 'other_sku', align:'center', width:150}">平台商品id</th>
        <?php } ?>
        <th lay-data="{field: 'shelves_no', align:'center', width:150}">货架位</th>
        <th lay-data="{field: 'num', align:'center', width:80, templet:'#numTpl'}">库存</th>
        <?php if(in_array($warehouse['warehouse_provider']['warehouse_provider_type'],[WarehouseProvider::TYPE_PLATFORM,WarehouseProvider::TYPE_THIRD_PARTY])){?>
        <th lay-data="{field: 'real_num', align:'center', width:100}">实时库存</th>
        <?php } ?>
        <th lay-data="{minWidth:220, templet:'#listBar',align:'center'}">操作</th>
    </tr>
    </thead>
</table>
</div>
</div>
    </div>
</div>

<!--操作-->
<script type="text/html" id="listBar">
    {{# if(d['label_no'] != '' || ('<?=$warehouse['warehouse_code']?>' == 'ozon' && d['goods_ozon_title'] != '')){ }}
    <a class="layui-btn layui-btn-xs" lay-event="fun" data-fun="print_tag" data-id="{{ d.label_no }}">打印标签</a>
    {{# } }}

    {{# if(d.warehouse_type == <?=\common\models\warehousing\WarehouseProvider::TYPE_PLATFORM?>){ }}
    {{# if(d['is_claim'] === false){ }}
        <a class="layui-btn layui-btn-xs" lay-event="open" data-url="<?=Url::to(['goods/claim?platform_type='.Base::PLATFORM_OZON])?>&goods_no={{ d.goods_no }}" data-width="600px" data-height="500px" data-title="认领">认领</a>
    {{# } }}
    {{# } }}

    <a class="layui-btn layui-btn-xs layui-btn-normal" lay-event="open" data-url="<?=Url::to(['goods/set-stock'])?>?cgoods_no={{ d.cgoods_no }}&warehouse_id={{d.warehouse}}" data-width="850px" data-height="600px" data-title="库存" data-callback_title="商品列表">库存</a>

    {{# if(d['warehouse'] == 2 || d['warehouse'] == 1){ }}
    <a class="layui-btn layui-btn-xs layui-btn-normal" lay-event="open" data-height="450px" data-width="500px" data-url="<?=Url::to(['warehouse-goods/update-shelves'])?>?cgoods_no={{ d.cgoods_no }}" data-title="更换货架">更换货架</a>
    {{# } }}

    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete" data-url="<?=Url::to(['warehouse-goods/delete'])?>?id={{ d.id }}">删除</a>
    {{# if(d['warehouse'] == 1){ }}
    <a class="layui-btn  layui-btn-xs" lay-event="operating" data-title="移入三林豆" data-url="<?=Url::to(['warehouse-goods/change-ware'])?>?cgoods_no={{ d.cgoods_no }}">移入三林豆</a>
    {{# } }}

    {{# if(d.warehouse_type == <?=\common\models\warehousing\WarehouseProvider::TYPE_THIRD_PARTY?> && d.other_sku ==''){ }}
    <a class="layui-btn  layui-btn-xs" lay-event="operating" data-title="同步商品" data-url="<?=Url::to(['warehouse-goods/sync-goods'])?>?id={{ d.id }}">同步商品</a>
    {{# } }}

    {{# if(d.warehouse_type == <?=\common\models\warehousing\WarehouseProvider::TYPE_PLATFORM?> || d.warehouse_type == <?=\common\models\warehousing\WarehouseProvider::TYPE_THIRD_PARTY?>){ }}
    <a class="layui-btn layui-btn-xs" lay-event="open" data-height="450px" data-width="800px" data-url="<?=Url::to(['warehouse-product-sales/add-purchase'])?>?cgoods_no={{ d.cgoods_no }}&warehouse_id={{ d.warehouse }}" data-title="加入采购计划">加入采购计划</a>
    {{# } }}

</script>
<script type="text/html" id="goodsImgTpl">
    {{# if(d.image){ }}
    <a href="{{d.image}}" data-lightbox="pic">
        <img class="layui-circle" src="{{d.image}}?imageView2/2/h/100" style="max-width: 100px;max-height: 100px" />
    </a>
    {{# } }}
</script>
<script type="text/html" id="numTpl">
    {{# if(d.has_normal != 2){ }}
    <span style="color: red">{{ d.num || '0' }}</span>
    {{# }else{ }}
    {{ d.num || '0' }}
    {{# } }}
</script>

<script type="text/html" id="goodsTpl">
    <b class="sku_no">{{d.sku_no}}</b><br/>
    <a lay-event="update" data-url="<?=Url::to(['goods/view'])?>?goods_no={{ d.goods_no }}" style="color: #00a0e9">{{d.goods_no}}</a><br/>
    类目：<b>{{ d.category_name || '' }}</b>
</script>
<script type="text/html" id="goodsNameTpl">
    <div class="span-goode-name" style="white-space:pre-wrap;">{{d.goods_name_cn || (d.goods_name||'')}}</div>
    <div class="span-goode-name">{{d.colour || ''}} {{d.size || ''}}</div>
</script>
<script>
    const tableName="warehouse-goods";
    const warehouse_id=<?=$warehouse_id?>;
    const categoryArr ='<?=addslashes(json_encode($category_arr))?>';
</script>
<?php
    $this->registerJsFile('@adminPlugins/extend/lodop.js?v=0.2',['depends'=>'yii\web\JqueryAsset']);
    $this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.4.3");
    $this->registerJsFile("@adminPageJs/warehouse-goods/lists.js?v=".time());
    $this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
    $this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
    $this->registerJsFile('@adminPlugins/export/xlsx.core.min.js?v=1',['depends'=>'yii\web\JqueryAsset']);
    $this->registerJsFile('@adminPlugins/export/export-excel.js?v=1',['depends'=>'yii\web\JqueryAsset']);
    $this->registerJsFile('@adminPlugins/export/export.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
    $this->registerJsFile('@adminPlugins/export/export-function.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
?>

