<?php

use common\models\goods_shop\GoodsShopFollowSale;
use yii\helpers\Url;
use \common\components\statics\Base;
$platform_name = \common\components\statics\Base::$platform_maps[$platform_type];
$url_platform_name = strtolower($platform_name);
?>
<style>
    .layui-vertical-20{
        padding-top: 20px;
    }
    .layui-table-body .layui-table-cell{
        height:auto;
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
    .layui-btn-a {
        border: none;
        background-color: rgba(0, 0, 0, 0);
        -webkit-user-select:text;
    }
    .span-goode-name{
        color:#a0a3a6;
        font-size: 13px;
        width: 260px;
        height: auto;
        display: block;
        word-wrap:break-word;
        white-space:pre-wrap;
    }
    .span-circular {
        display: inline-block;
        width: 25px;
        height: 25px;
        border-radius: 80%;
        background-color: #00aa00;
        color: #fff;
        text-align: center;
        padding: 5px;
        line-height: 16px;
    }
    .language {
        float: left;
        background-color: #a0a3a6;
        height: 24px;
        margin-left: 5px
    }
</style>
<div class="layui-fluid">
    <div class="layui-card">
        <div class="layui-tab layui-tab-brief">
            <ul class="layui-tab-title">
                <?php if (!in_array($platform_type,[Base::PLATFORM_EPRICE,Base::PLATFORM_NOCNOC])){?>
                <li><a href="<?=Url::to(['goods-'.$url_platform_name.'/platform-index'])?>">商品库</a></li>
                <?php }?>
                <li><a href="<?=Url::to(['goods-'.$url_platform_name.'/index'])?>">在线商品</a></li>
                <?php if ($platform_type == Base::PLATFORM_OZON) {?>
                    <li><a href="<?=Url::to(['goods-'.$url_platform_name.'/ad-index'])?>">广告商品</a></li>
                <?php } ?>
                <?php if (in_array($platform_type,[Base::PLATFORM_OZON,Base::PLATFORM_RDC,Base::PLATFORM_WORTEN])) {?>
                    <li><a href="<?=Url::to(['goods-'.$url_platform_name.'/shop-follow-sale-index'])?>">跟卖商品</a></li>
                <?php }?>
                <li class="layui-this"><a href="<?=Url::to(['goods-'.$url_platform_name.'/overseas-index'])?>">海外仓</a></li>
            </ul>
        </div>
        <div class="lay-lists">
            <form>
                <div class="layui-form lay-search" style="padding: 10px">
                    <div class="layui-inline">
                        商品编号
                        <textarea name="BaseGoodsSearch[goods_no]" class="layui-textarea search-con" style="height: 39px; min-height:39px"></textarea>
                    </div>
                    <div class="layui-inline">
                        SKU
                        <textarea name="BaseGoodsSearch[sku_no]" class="layui-textarea search-con" style="height: 39px; min-height:39px"></textarea>
                    </div>
                    <div class="layui-inline">
                        EAN
                        <textarea name="BaseGoodsSearch[ean]" class="layui-textarea search-con" style="height: 39px; min-height:39px"></textarea>
                    </div>
                    <div class="layui-inline">
                        自定义SKU
                        <textarea name="BaseGoodsSearch[platform_sku_no]" class="layui-textarea search-con" style="height: 39px; min-height:39px"></textarea>
                    </div>
                    <div class="layui-inline">
                        编号
                        <textarea name="BaseGoodsSearch[<?=$platform_type == Base::PLATFORM_OZON?'platform_goods_opc':'platform_goods_id'?>]" class="layui-textarea search-con" style="height: 39px; min-height:39px"></textarea>
                    </div>
                    <div class="layui-inline">
                        标题
                        <input class="layui-input search-con" name="BaseGoodsSearch[goods_name]" autocomplete="off">
                    </div>
                    <div class="layui-inline">
                        店铺
                        <?= \yii\helpers\Html::dropDownList('BaseGoodsSearch[shop_id]', null, \common\services\ShopService::getShopMap($platform_type),
                            ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
                    </div>
                    <div class="layui-inline" style="width: 200px">
                        <label>国家</label>
                        <?= \yii\helpers\Html::dropDownList('BaseGoodsSearch[country_code]', null, $country_arr,['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>"layui-input search-con ys-select2"]) ?>
                    </div>
                    <div class="layui-inline">
                        平台类目
                        <div id="div_category_id" style="width: 180px;"></div>
                        <input id="category_id" class="layui-input search-con" type="hidden" name="BaseGoodsSearch[category_id]" autocomplete="off">
                    </div>
                    <div class="layui-inline" style="width: 170px">
                        <label>剩余库存(天)</label>
                        <?= \yii\helpers\Html::dropDownList('BaseGoodsSearch[has_stock]', null, [1 => '31-60',2 => '61-90',3 => '91-180',4 => '181-360',5 => '> 360'],
                            ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search', 'style' => 'width:170px']) ?>
                    </div>
                    <div class="layui-inline" style="width: 210px">
                        <label>状态</label>
                        <?= \yii\helpers\Html::dropDownList('BaseGoodsSearch[has_numbers]', null, [1 => '有销量',2 => '无销量',3 => '有库存',4 => '无库存',5 => '有在途',6 => '无在途'],
                            ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search', 'style' => 'width:210px',"multiple"=>"multiple"]) ?>
                    </div>
                    <div class="layui-inline layui-vertical-20">
                        <button class="layui-btn" data-type="search_lists">搜索</button>
                    </div>
                </div>
            </form>

            <div class="layui-card-body">
                <table id="goods-<?=$url_platform_name?>" class="layui-table" lay-data="{url:'<?=Url::to(['goods-'.$url_platform_name.'/overseas-list?sku_no='.$sku_no])?>', height : 'full-190', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, method :'post',limit : 20}" lay-filter="goods-<?=$url_platform_name?>">
                    <thead>
                    <tr>
                        <th lay-data="{type: 'checkbox', width:50}">ID</th>
                        <th lay-data="{width:160, align:'center',templet:'#goodsImgTpl'}">商品主图</th>
                        <th lay-data="{ width:195, align:'left',templet:'#goodsNoTpl'}">商品编号</th>
                        <th lay-data="{align:'left',width:200,templet:'#shopTpl'}">店铺/分类/状态</th>
                        <th lay-data="{ width:290, align:'left',templet:'#goodsTpl'}">商品信息</th>
                        <th lay-data="{ minWidth:207, align:'left',templet:'#userTpl'}">价格/库存</th>
                        <th lay-data="{minWidth:135, templet:'#listBar',align:'center'}">操作</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<!--操作-->
<script type="text/html" id="listBar">
    <a class="layui-btn layui-btn-xs" lay-event="open" data-width="950px" data-height="550px" data-url="<?=Url::to(['goods-'.$url_platform_name.'/update-price'])?>?id={{ d.goods_shop_id }}" data-title="编辑价格">价格</a>
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="open" data-height="450px" data-width="800px" data-url="<?=Url::to(['warehouse-product-sales/add-purchase'])?>?cgoods_no={{ d.cgoods_no }}&warehouse_id={{ d.warehouse_id }}" data-title="加入采购计划">加入采购计划</a>
</script>
<script type="text/html" id="goodsImgTpl">
    <a href="{{d.image}}" data-lightbox="pic">
        <img class="layui-circle" src="{{d.image}}" width="100"/>
    </a>
</script>
<script type="text/html" id="goodsNoTpl">
    <a lay-event="update" data-url="<?=Url::to(['goods/view'])?>?goods_no={{ d.goods_no }}" style="color: #00a0e9">{{d.goods_no}}</a><br/>
    <a class="layui-btn layui-btn-xs layui-btn-a" lay-event="update" data-url="<?=Url::to(['order/overseas-index?search=1&tag=10&OrderSearch%5Bsource%5D='.$platform_type.'&OrderSearch%5Bplatform_asin%5D='])?>{{d.sku_no}}" data-title="订单信息" style="color: #00a0e9"><i class="layui-icon layui-icon-template"></i></a><b>{{d.sku_no}}</b><br/>
    自定义Sku：<b>{{d.platform_sku_no || '无'}}</b><br/>
    EAN:{{d.ean || '无'}}<br/>
    编号:
    <?php if ($platform_type == Base::PLATFORM_ALLEGRO) {?>
    <b>{{# if(d.platform_goods_id && d.platform_goods_id != 0){ }}<a style="color: #00a0e9" href="https://allegro.pl/oferta/{{d.platform_goods_id || '0'}}" target="_blank">{{d.platform_goods_id || '无'}}</a></b>{{# } else{ }} {{d.platform_goods_id || '无'}} {{# } }}<br/>
    <?php } else if ($platform_type == Base::PLATFORM_OZON) {?>
        <b>{{# if(d.platform_goods_opc && d.platform_goods_opc != 0){ }}<a style="color: #00a0e9" href="https://ozon.ru/context/detail/id/{{d.platform_goods_opc || '0'}}" target="_blank">{{d.platform_goods_opc || '无'}}</a></b>{{# } else{ }} {{d.platform_goods_opc || '无'}} {{# } }}
    <?php } else if ($platform_type == Base::PLATFORM_WILDBERRIES) {?>
        <b>{{# if(d.platform_goods_id && d.platform_goods_id != 0){ }}<a style="color: #00a0e9" href="https://www.wildberries.ru/catalog/{{d.platform_goods_id || '0'}}/detail.aspx?targetUrl=GP" target="_blank">{{d.platform_goods_id || '无'}}</a></b>{{# } else{ }} {{d.platform_goods_id || '无'}} {{# } }}<br/>
    <?php } else if ($platform_type == Base::PLATFORM_CDISCOUNT) {?>
        {{ d.platform_goods_opc || '无' }}
    <?php } else { ?>
        {{ d.platform_goods_id || '无' }}
    <?php } ?>
</script>
<script type="text/html" id="shopTpl">
    {{d.shop_name || '' }}<br/>
    <?php if ($platform_type == Base::PLATFORM_ALLEGRO){?>
    国家：{{ d.country }}<br/>
    <?php } ?>
    平台类目：<b>{{d.category_name || '' }}</b><br/>
    <?=$platform_name?>类目：{{ d.o_category_name || '' }}<br/>
    <?php if (in_array($platform_type,[Base::PLATFORM_ALLEGRO,Base::PLATFORM_OZON])){?>
    <a class="layui-btn language layui-btn-xs" lay-event="update" data-title="添加语言" data-url="<?=Url::to(['goods/update-multilingual?type=3&platform_type='.$platform_type])?>&goods_no={{ d.goods_no }}" data-callback_title="商品列表" style="padding: 0px 10px;{{# if(d.is_editor == '1'){}}background-color: #009688{{#}}}">A+</a>
    <?php }?>
</script>
<script type="text/html" id="goodsTpl">
    <div class="span-goode-name">
        {{d.goods_name||''}}<br/>
        <span style="color: #00b7ee">
        {{ d.size || '' }} {{ d.weight || '0' }}kg<br/>
        {{ d.square_l || '' }} {{# if(d.square_l!=''){}} 公升 ({{ d.square_m || '' }}m³)
        {{#if (d.classify != ''){}}
            <span class="span-circular">{{ d.classify }}</span>
        {{#}}}
        {{#}}}
        </span>
    </div>
</script>
<script type="text/html" id="userTpl">
    价格：<span>{{d.price||''}}</span>
    <span style="padding-left: 10px">推荐价: {{ d.original_price }}</span><br/>
    仓库: {{ d.warehouse_name }}<br/>
    库存: <b>
        <a lay-event="open" data-width="850px" data-height="250px" data-title="库存数" data-url="<?=Url::to(['goods/stock-view'])?>?warehouse_id={{ d.warehouse_id }}&cgoods_no={{ d.cgoods_no }}" style="color: {{# if(d.inventory_quantity != '' || d.inventory_quantity > 0){ }}#009688{{# } else{ }}#FF5722{{# } }}">{{d.inventory_quantity ||'0'}}</a>
    </b>
    <span style="padding-left: 10px">
        在途数:
    {{#if (d.transit_quantity != 0 && d.transit_quantity) { }}
            <a lay-event="open" data-width="1050px" data-height="600px" data-title="在途数" data-url="<?=Url::to(['goods/transit-quantity-view'])?>?warehouse_id={{ d.warehouse_id }}&cgoods_no={{ d.cgoods_no }}" style="color: #00a0e9">{{d.transit_quantity}}</a>
    {{# }else{ }}
        <b>{{d.transit_quantity ||'0'}}</b>
    {{# } }}
    </span><br/>
    销售量:
    {{#if (d.total_sales != 0 && d.total_sales) { }}
    <a lay-event="open" data-width="700px" data-height="250px" data-title="商品销量" data-url="<?=Url::to(['goods/product-sales-view'])?>?cgoods_no={{ d.cgoods_no }}&warehouse_id={{ d.warehouse_id }}" style="color: #00a0e9">{{d.total_sales}}</a>
    {{# }else{ }}
    {{ d.total_sales || '0' }}
    {{# } }}
    <span style="padding-left: 10px">采购中：{{ d.purchasing || '0' }}</span>
</script>
<script>
    const tableName="goods-<?=$url_platform_name?>";
    const categoryArr ='<?=addslashes(json_encode($category_arr))?>';
</script>
<?php
$this->registerJsFile("@adminPageJs/base/lists.js?v=1.2.2");
$this->registerJsFile("@adminPageJs/goods/base_lists.js?".time());
$this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/xlsx.core.min.js?v=1',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export-excel.js?v=1',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export-function.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
?>
