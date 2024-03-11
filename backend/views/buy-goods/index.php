
<?php

use common\services\ShopService;
use yii\helpers\Url;
use common\models\BuyGoods;
use yii\helpers\Html;
?>

<style>
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
</style>
<div class="layui-fluid">
    <div class="layui-card">
<div class="layui-tab layui-tab-brief">
    <ul class="layui-tab-title">
        <li <?php if($tag == 10){?>class="layui-this" <?php }?>><a href="<?=Url::to(['buy-goods/index?tag=10'])?>">全部</a></li>
        <li <?php if($tag == 1){?>class="layui-this" <?php }?>><a href="<?=Url::to(['buy-goods/index?tag=1'])?>">未购买</a></li>
        <li <?php if($tag == 2){?>class="layui-this" <?php }?>><a href="<?=Url::to(['buy-goods/index?tag=2'])?>">已购买</a></li>
        <li <?php if($tag == 3){?>class="layui-this" <?php }?>><a href="<?=Url::to(['buy-goods/index?tag=3'])?>">已发货</a></li>
        <li <?php if($tag == 4){?>class="layui-this" <?php }?>><a href="<?=Url::to(['buy-goods/index?tag=4'])?>">已完成</a></li>
        <li <?php if($tag == 5){?>class="layui-this" <?php }?>><a href="<?=Url::to(['buy-goods/index?tag=5'])?>">缺货</a></li>
        <li <?php if($tag == 6){?>class="layui-this" <?php }?>><a href="<?=Url::to(['buy-goods/index?tag=6'])?>">信息错误</a></li>
        <li <?php if($tag == 7){?>class="layui-this" <?php }?>><a href="<?=Url::to(['buy-goods/index?tag=7'])?>">退货</a></li>
        <li <?php if($tag == 8){?>class="layui-this" <?php }?>><a href="<?=Url::to(['buy-goods/index?tag=8'])?>">退款</a></li>
        <li <?php if($tag == 9){?>class="layui-this" <?php }?>><a href="<?=Url::to(['buy-goods/index?tag=9'])?>">换货</a></li>
        <li <?php if($tag == 11){?>class="layui-this" <?php }?>><a href="<?=Url::to(['buy-goods/index?tag=11'])?>">已有货</a></li>
    </ul>
</div>
<div class="lay-lists">
<!--<form class="layui-form">
    <blockquote class="layui-elem-quote quoteBox">
        <div class="layui-inline">
            <a class="layui-btn" data-type="url" data-title="添加" data-url="<?=Url::to(['buy-goods/create'])?>" data-callback_title = "购买商品列表" >添加</a>
        </div>
    </blockquote>
</form>-->
<form class="layui-form">
<div class="layui-form lay-search" style="padding-left: 10px">
    <div class="layui-inline">
        订单号
        <input class="layui-input search-con" name="BuyGoodsSearch[order_id]" autocomplete="off">
    </div>

    <div class="layui-inline">
        <label>销售单号</label>
        <input class="layui-input search-con" name="BuyGoodsSearch[relation_no]"  autocomplete="off">
    </div>

    <div class="layui-inline">
        商品ASIN
        <input class="layui-input search-con" name="BuyGoodsSearch[asin]" autocomplete="off">
    </div>

    <div class="layui-inline">
        平台
        <?= Html::dropDownList('BuyGoodsSearch[platform_type]', null, \common\components\statics\Base::$buy_platform_maps,
            ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px' ]) ?>
    </div>
    <div class="layui-inline">
        销售平台
        <?= Html::dropDownList('BuyGoodsSearch[source]', null, \common\components\statics\Base::$platform_maps,
            ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px','id'=>'platform' ]) ?>
    </div>
    <div class="layui-inline">
        销售店铺
        <?= \backend\widgets\LinkageDropDownList::widget(['id' => 'shop','parent_id'=>'platform','name'=>'BuyGoodsSearch[shop_id]','select'=>null,'option'=> ShopService::getOrderShopMap(),'param'=>['data-placeholder' => '全部','prompt' => '全部','style'=>'width:185px']]) ?>
    </div>
    <?php if(in_array($tag ,[10])){ ?>
    <div class="layui-inline">
        下单时间
        <input class="layui-input search-con ys-date" name="BuyGoodsSearch[add_start_date]" id="add_start_date" >
    </div>
    <span class="layui-inline layui-vertical-20">
        -
    </span>
    <div class="layui-inline layui-vertical-20">
        <input class="layui-input search-con ys-date" name="BuyGoodsSearch[add_end_date]" id="add_end_date" >
    </div>
    <?php }?>
    <?php if(in_array($tag ,[3,4])){ ?>
    <div class="layui-inline">
        预计到货时间
        <input class="layui-input search-con ys-date" name="BuyGoodsSearch[start_arrival_time]" id="start_arrival_time" >
    </div>
    <span class="layui-inline layui-vertical-20">
        -
    </span>
    <div class="layui-inline layui-vertical-20">
        <input class="layui-input search-con ys-date" name="BuyGoodsSearch[end_arrival_time]" id="end_arrival_time" >
    </div>
    <?php }?>
    <?php if(in_array($tag ,[7,8,9])){ ?>
        <div class="layui-inline">
            申请售后时间
            <input class="layui-input search-con ys-date" name="BuyGoodsSearch[start_after_sale_time]" id="start_after_sale_time" >
        </div>
        <span class="layui-inline layui-vertical-20">
        -
    </span>
        <div class="layui-inline layui-vertical-20">
            <input class="layui-input search-con ys-date" name="BuyGoodsSearch[end_after_sale_time]" id="end_after_sale_time" >
        </div>
    <?php }?>
    <?php if(in_array($tag ,[2,3,4,10])){ ?>
    <div class="layui-inline">
        亚马逊订单号
        <input class="layui-input search-con" name="BuyGoodsSearch[buy_relation_no]" autocomplete="off">
    </div>
    <?php }?>
    <?php if(in_array($tag ,[2,3,4])){ ?>
    <!--<div class="layui-inline">
        刷单买家号机器编号
        <input class="layui-input search-con" name="BuyGoodsSearch[swipe_buyer_id]" autocomplete="off">
    </div>-->
    <div class="layui-inline" style="width: 110px">
        刷单买家号编号
        <input class="layui-input search-con" name="BuyGoodsSearch[start_swipe_buyer_id]" autocomplete="off">
    </div>
    <span class="layui-inline layui-vertical-20">
        -
    </span>
    <div class="layui-inline layui-vertical-20" style="width: 110px">
        <input class="layui-input search-con" name="BuyGoodsSearch[end_swipe_buyer_id]" >
    </div>
    <div class="layui-inline">
        real发货状态
        <?= Html::dropDownList('BuyGoodsSearch[delivery_status]', null,\common\models\Order::$delivery_status_map,
            ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search' ]) ?>
    </div>
    <?php }?>

    <div class="layui-inline layui-vertical-20">
    <button class="layui-btn" data-type="search_lists">搜索</button>

    <button class="layui-btn layui-btn-normal" data-type="export_lists" data-url="<?=Url::to(['buy-goods/export?tag='.$tag])?>">导出</button>

    <?php if(in_array($tag ,[1])){ ?>
        <button class="layui-btn layui-btn-primary ys-upload" lay-data="{url: '/buy-goods/import-buy/',accept: 'file'}">购买导入</button>
    <?php }?>

    <?php if(in_array($tag ,[3])){ ?>
        <button class="layui-btn layui-btn-primary ys-upload" lay-data="{url: '/buy-goods/import-logistics/',accept: 'file'}">物流订单号导入</button>
    <?php }?>
    </div>
</div>
</form>
<?php if($tag == 2){?>
    <div class="layui-form" style="padding-left: 10px;margin-top: 10px">
        <div class="layui-inline">
            <a class="layui-btn layui-btn-sm layui-btn-normal batch_ship_btn" data-url="<?=Url::to(['buy-goods/batch-ship'])?>" >批量发货</a>
        </div>
    </div>
<?php }?>
    <div class="layui-card-body">
    <table id="buy-goods" class="layui-table" lay-data="{url:'<?=Url::to(['buy-goods/list?tag='.$tag])?>', height : 'full-150', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20}" lay-filter="buy-goods">
    <thead>
    <tr>
        <th lay-data="{type: 'checkbox', fixed:'left', width:50}">ID</th>
        <th lay-data="{field: 'order_id', align:'center',width:160}">订单号</th>
        <th lay-data="{field: 'relation_no', align:'center', width:120}">销售单号</th>
        <th lay-data="{field: 'shop_name', align:'center', width:90}">销售店铺</th>
        <th lay-data="{field: 'buy_goods_pic', width:100, align:'center',templet:'#goodsImgTpl'}">商品图片</th>
        <th lay-data="{field: 'platform_type_desc', align:'center', width:100}">平台</th>
        <th lay-data="{field: 'asin', align:'center', width:120}">商品ASIN</th>
        <th lay-data="{field: 'buy_goods_num', align:'center', width:80}">数量</th>
        <th lay-data="{field: 'buy_goods_price', align:'center', width:100}">商品价格</th>
        <th lay-data="{field: 'goods_price', align:'center', width:100}">销售平台价格</th>
        <th lay-data="{field: 'buy_goods_url',  align:'left',minWidth:150}">买货链接</th>
        <th lay-data="{field: 'buy_relation_no',  align:'center',minWidth:120}">亚马逊订单号</th>
        <th lay-data="{field: 'arrival_time',  align:'center',minWidth:160}">预计到货时间</th>
        <th lay-data="{field: 'buy_goods_status_desc',  align:'center',width:120}">状态</th>
        <th lay-data="{field: 'order_add_time', align:'center',width:120}">下单时间</th>
        <?php if(in_array($tag ,[5,6,7,8,9])){ ?>
        <th lay-data="{field: 'remarks',  align:'center',width:220}">备注</th>
        <?php }?>
        <?php if(in_array($tag ,[2,3,4])){ ?>
        <th lay-data="{field: 'delivery_status_desc',  align:'center',width:120}">real发货状态</th>
        <?php }?>
        <th lay-data="{field: 'swipe_buyer_id',  align:'center',minWidth:160}">刷单买家号机器编号</th>
        <th lay-data="{field: 'logistics_id',  align:'center',minWidth:150}">亚马逊物流单号</th>
        <?php if(in_array($tag ,[4])){ ?>
        <th lay-data="{field: 'logistics_channels_id_desc',  align:'center',minWidth:150}">物流渠道</th>
        <th lay-data="{field: 'track_no',  align:'center',minWidth:150}">物流订单号</th>
        <?php }?>
        <th lay-data="{field: 'after_sale_status_desc',  align:'center',width:120}">售后状态</th>
        <th lay-data="{field: 'company_name',  align:'center',minWidth:120}">公司名称</th>
        <th lay-data="{field: 'buyer_name',  align:'center',minWidth:120}">买家名称</th>
        <th lay-data="{field: 'buyer_phone',  align:'center',minWidth:120}">电话</th>
        <th lay-data="{field: 'address',  align:'left',minWidth:150}">地址</th>
        <th lay-data="{field: 'city',  align:'center',width:100}">城市</th>
        <th lay-data="{field: 'area',  align:'center',minWidth:100}">区</th>
        <th lay-data="{field: 'postcode',  align:'center',minWidth:80}">邮编</th>
        <th lay-data="{field: 'country',  align:'center',width:120}">国家</th>
        <th lay-data="{field: 'update_time',  align:'center',width:180}">更新时间</th>
        <th lay-data="{minWidth:215, templet:'#listBar',fixed:'right',align:'center'}">操作</th>
    </tr>
    </thead>
</table>
</div>
</div>
    </div>
</div>
<script type="text/html" id="goodsImgTpl">
    <a href="{{d.buy_goods_pic}}" data-lightbox="pic">
        <img class="layui-circle pic" src={{d.buy_goods_pic}} height="26"/>
    </a>
</script>

<!--操作-->
<script type="text/html" id="listBar">
    {{#  if(d.buy_goods_status != <?=BuyGoods::BUY_GOODS_STATUS_DELETE?>){ }}
    <?php if(!in_array($tag,[4,7,8,9])){?>
    {{#  if(d.buy_goods_status !== <?=BuyGoods::BUY_GOODS_STATUS_FINISH?> ){ }}
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['buy-goods/change-status'])?>?id={{ d.id }}" data-title="详情" data-callback_title="亚马逊订单处理">
        {{#  if(d.buy_goods_status == <?=BuyGoods::BUY_GOODS_STATUS_DELIVERY?>){ }}
        购买单号
        {{#  }else if(d.buy_goods_status == <?=BuyGoods::BUY_GOODS_STATUS_BUY?>){ }}
        发货
        {{#  }else{ }}
        购买
        {{#  } }}
    </a>
    {{#  } }}
    <?php }?>

    <a class="layui-btn layui-btn-xs" lay-event="update" data-url="<?=Url::to(['buy-goods/update'])?>?id={{ d.id }}" data-title="编辑" data-callback_title="亚马逊订单处理">
        编辑</a>

    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="update" data-url="<?=Url::to(['buy-goods/after-sale'])?>?id={{ d.id }}" data-title="售后处理" data-callback_title="亚马逊订单处理">售后处理</a>
    {{#  } }}
</script>

<script>
    const tableName="buy-goods";
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.5.1")?>
<?=$this->registerJsFile("@adminPageJs/buy-goods/lists.js?v=".time())?>
<?php
    $this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
    $this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
    $this->registerJsFile('@adminPlugins/export/xlsx.core.min.js?v=1',['depends'=>'yii\web\JqueryAsset']);
    $this->registerJsFile('@adminPlugins/export/export-excel.js?v=1',['depends'=>'yii\web\JqueryAsset']);
    $this->registerJsFile('@adminPlugins/export/export-function.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
?>

