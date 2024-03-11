
<?php

use common\models\PromoteCampaign;
use yii\bootstrap\Html;
use yii\helpers\Url;
?>
<style>
    .layui-table-body .layui-table-cell{
        height:auto;
    }
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
    .layui-tab-brief > .layui-tab-title .layui-this a{
        color: rgb(0, 150, 136);
    }
    .layui-btn-a {
        border: none;
        background-color: rgba(0, 0, 0, 0);
        -webkit-user-select:text;
    }
    .layui-table .layui-btn{
        margin-bottom: 3px;
    }
</style>
<div class="layui-fluid">
    <div class="layui-card">
        <div class="lay-lists">

            <div class="lay-search" style="padding-left: 10px;padding-top: 15px">

                <?php if (!empty($is_all)){ ?>
                <div class="layui-inline">
                    平台：
                    <?= Html::dropDownList('PromoteCampaignDetailsSearch[platform_type]', null,\common\services\goods\GoodsService::$own_platform_type,
                        ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','id'=>'platform']); ?>
                </div>
                <div class="layui-inline" style="width: 120px">
                    类型：
                    <?= Html::dropDownList('PromoteCampaignDetailsSearch[type]', null,PromoteCampaign::$type_maps,
                        ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style' => 'width: 120px']); ?>
                </div>
                <div class="layui-inline">
                    店铺名称：
                    <?= \backend\widgets\LinkageDropDownList::widget(['id' => 'shop','parent_id'=>'platform','name'=>'PromoteCampaignDetailsSearch[shop_id]','select'=>null,'option'=> \common\services\ShopService::getShopMapIndexPlatform(),'param'=>['data-placeholder' => '全部','prompt' => '全部','style'=>'width:180px']]) ?>
                </div>
                <?php }?>

                <div class="layui-inline">
                    sku：
                    <input class="layui-input search-con" name="PromoteCampaignDetailsSearch[platform_goods_opc]" autocomplete="off">
                </div>

                <div class="layui-inline">
                    商品编号：
                    <input class="layui-input search-con" name="PromoteCampaignDetailsSearch[cgoods_no]" autocomplete="off">
                </div>

                <div class="layui-inline">
                    活动时间：
                    <input  class="layui-input search-con ys-date" name="PromoteCampaignDetailsSearch[start_date]"  id="start_date" autocomplete="off">
                </div>
                <span class="layui-inline layui-vertical-20">
                    -
                </span>
                <div class="layui-inline layui-vertical-20">
                    <input  class="layui-input search-con ys-date" name="PromoteCampaignDetailsSearch[end_date]"  id="end_date" autocomplete="off">
                </div>

                <button class="layui-btn" data-type="search_lists" id="search_btn" style="margin-top: 21px">搜索</button>
                <div class="layui-inline layui-vertical-20" style="margin-left: 10px">
                    <a class="day layui-btn layui-btn-primary layui-btn-xs layui-btn-a" data-title="昨日" data-day="1" style="text-decoration:none;color:#00a0e9;font-size: 14px" >昨日</a>
                </div>
                <div class="layui-inline layui-vertical-20">
                    <a class="day layui-btn layui-btn-primary layui-btn-xs layui-btn-a" data-title="7日" data-day="7" style="text-decoration:none;color:#00a0e9;font-size: 14px">7日</a>
                </div>
                <div class="layui-inline layui-vertical-20">
                    <a class="day layui-btn layui-btn-primary layui-btn-xs layui-btn-a" data-title="15日" data-day="15" style="text-decoration:none;color:#00a0e9;font-size: 14px">15日</a>
                </div>
                <div class="layui-inline layui-vertical-20">
                    <a class="day layui-btn layui-btn-primary layui-btn-xs layui-btn-a" data-title="30日" data-day="30" style="text-decoration:none;color:#00a0e9;font-size: 14px">30日</a>
                </div>
            </div>
            <div class="layui-card-body">
                <table id="promote-campaign-details" class="layui-table" lay-data="{url:'<?=Url::to(['promote-campaign-details/list?id='.$id.'&stime='.$stime.'&etime='.$etime])?>', height : 'full-20', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 100, autoSort: false}" lay-filter="promote-campaign-details">
                    <thead>
                    <tr>
                        <th lay-data="{templet:'#imageBar', width:130,fixed: 'left'}">商品图片</th>
                        <th lay-data="{templet:'#listBar', align:'center', minWidth:240,fixed: 'left'}">商品编号</th>
                        <?php if (!empty($is_all)){?>
                            <th lay-data="{templet:'#platformBar', align:'center', minWidth:240,fixed: 'left'}">平台</th>
                        <?php }?>
                        <th lay-data="{field: 'impressions', align:'left', width:120, sort: true}">展示量</th>
                        <th lay-data="{field: 'hits',  align:'left', width:120, sort: true}">点击量</th>
                        <th lay-data="{field: 'ctr',  align:'left', width:120, sort: true}">CTR (%)</th>
                        <th lay-data="{field: 'average',  align:'left', width:190, sort: true}"><?=$prompt?></th>
                        <th lay-data="{field: 'promotes',  align:'left', width:170, sort: true}">推广费</th>
                        <th lay-data="{field: 'order_volume',  align:'left', width:120, sort: true}">订单量</th>
                        <th lay-data="{field: 'order_sales',  align:'left', width:120, sort: true}">订单销售额</th>
                        <th lay-data="{field: 'model_orders',  align:'left', width:120, sort: true}">型号订单量</th>
                        <th lay-data="{field: 'model_sales',  align:'left', width:120, sort: true}">型号订单销售额</th>
                        <th lay-data="{field: 'acos',  align:'left', width:120, sort: true}">ACOS</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<!--操作-->
<script type="text/html" id="imageBar">
    <a href="{{d.goods_img}}" data-lightbox="pic">
        <img class="layui-circle" src="{{d.goods_img}}?imageView2/2/h/100" width="80"/>
    </a>
</script>
<script type="text/html" id="listBar">
    {{# if(d.platform_goods_opc && d.platform_goods_opc != 0){ }}
    <a style="color: #00a0e9" href="https://ozon.ru/context/detail/id/{{ d.platform_goods_opc }}" target="_blank">{{ d.platform_goods_opc }}</a><br/>
    {{# } else { }}
        {{ d.platform_goods_opc || '无' }}<br/>
    {{# } }}

    {{# if (d.sku_no != '' && d.sku_no) { }}
    <?php if (!empty($types)){?>
    <a class="layui-btn layui-btn-xs layui-btn-a" lay-event="update" data-url="<?=Url::to(['order/overseas-index?search=1&tag=10&OrderSearch%5Bsource%5D='.$types.'&OrderSearch%5Bplatform_asin%5D='])?>{{ d.sku_no }}" data-title="订单信息" style="color: #00a0e9">
        <i class="layui-icon layui-icon-template"></i>
    </a>
    <?php }else{?>
    <a class="layui-btn layui-btn-xs layui-btn-a" lay-event="update" data-url="<?=Url::to(['order/overseas-index?search=1&tag=10&OrderSearch%5Bsource%5B&OrderSearch%5Bplatform_asin%5D='])?>{{ d.sku_no }}" data-title="订单信息" style="color: #00a0e9">
        <i class="layui-icon layui-icon-template"></i>
    </a>
    <?php }?>
    {{# } }}
    {{ d.cgoods_no || '' }}<br/>

    {{# if (d.sid != '' && d.sid) { }}
    <div class="lay-lists">
        <a class="layui-btn layui-btn-xs" lay-event="open" data-width="950px" data-height="550px" data-url="<?=Url::to(['goods-ozon/update-price'])?>?id={{ d.sid }}" data-title="编辑价格" data-callback_title="商品列表">价格</a>
    </div>
    {{# } }}
</script>
<script type="text/html" id="platformBar">
    平台：{{ d.platform_type_name }}<br>
    店铺：{{ d.shop }}<br/>
    类型：{{ d.type_name || '' }}
</script>
<script>
    const tableName="promote-campaign-details";
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=".time())?>
<?=$this->registerJsFile("@adminPageJs/promote-campaign-details/lists.js?v=".time())?>
<?php
$this->registerJsFile("@adminPageJs/goods/base_lists.js?".time());
$this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
?>
