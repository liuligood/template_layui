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
</style>
<div class="layui-fluid">
    <div class="layui-card">
        <div class="layui-tab layui-tab-brief">
            <ul class="layui-tab-title">
                <li><a href="<?=Url::to(['goods-'.$url_platform_name.'/platform-index'])?>">商品库</a></li>
                <li><a href="<?=Url::to(['goods-'.$url_platform_name.'/index'])?>">在线商品</a></li>
                <?php if (in_array($platform_type,[Base::PLATFORM_OZON,Base::PLATFORM_HEPSIGLOBAL])) {?>
                <li><a href="<?=Url::to(['goods-'.$url_platform_name.'/ad-index'])?>">收藏</a></li>
                <?php }?>
                <li class="layui-this"><a href="<?=Url::to(['goods-'.$url_platform_name.'/shop-follow-sale-index'])?>">跟卖商品</a></li>
                <?php if (in_array($platform_type,[Base::PLATFORM_OZON,Base::PLATFORM_RDC,Base::PLATFORM_WORTEN])) {?>
                <li ><a href="<?=Url::to(['goods-'.$url_platform_name.'/overseas-index'])?>">海外仓</a></li>
                <?php } ?>
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
                        标题
                        <input class="layui-input search-con" name="BaseGoodsSearch[goods_name]" autocomplete="off">
                    </div>
                    <div class="layui-inline">
                        店铺
                        <?= \yii\helpers\Html::dropDownList('BaseGoodsSearch[shop_id]', null, \common\services\ShopService::getShopMap($platform_type),
                            ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
                    </div>
                    <div class="layui-inline">
                        平台类目
                        <div id="div_category_id" style="width: 180px;"></div>
                        <input id="category_id" class="layui-input search-con" type="hidden" name="BaseGoodsSearch[category_id]" autocomplete="off">
                    </div>
                    <div class="layui-inline">
                        跟卖类型
                        <?= \yii\helpers\Html::dropDownList('BaseGoodsSearch[follow_type]', null, GoodsShopFollowSale::$type_show_map,
                            ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
                    </div>
                    <div class="layui-inline">
                        最低价
                        <?= \yii\helpers\Html::dropDownList('BaseGoodsSearch[is_min_price]', null, [ 1 => '是', 0 => '否'],
                            ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
                    </div>
                    <div class="layui-inline">
                        <div style="padding-left: 10px">
                            <input class="layui-input search-con" type="checkbox" value="1" name="BaseGoodsSearch[has_sales]" lay-skin="primary" title="有销售量">
                        </div>
                    </div>
                    <div class="layui-inline layui-vertical-20">
                        <button class="layui-btn" data-type="search_lists">搜索</button>
                    </div>
                </div>
            </form>

            <div class="layui-card-body">
                <table id="goods-<?=$url_platform_name?>" class="layui-table" lay-data="{url:'<?=Url::to(['goods-'.$url_platform_name.'/shop-follow-sale-list'])?>', height : 'full-190', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, method :'post',limit : 20}" lay-filter="goods-<?=$url_platform_name?>">
                    <thead>
                    <tr>
                        <th lay-data="{type: 'checkbox', width:50}">ID</th>
                        <th lay-data="{width:160, align:'center',templet:'#goodsImgTpl'}">商品主图</th>
                        <th lay-data="{ width:195, align:'left',templet:'#goodsNoTpl'}">商品编号</th>
                        <th lay-data="{align:'left',width:200,templet:'#shopTpl'}">店铺/分类/状态</th>
                        <th lay-data="{ width:290, align:'left',templet:'#goodsTpl'}">商品信息</th>
                        <th lay-data="{ minWidth:207, align:'left',templet:'#userTpl'}">时间</th>
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
    <a class="layui-btn layui-btn-xs" lay-event="open" data-width="950px" data-height="550px" data-url="<?=Url::to(['goods-'.$url_platform_name.'/update-price'])?>?id={{ d.goods_shop_id }}" data-title="编辑价格" data-callback_title="跟卖商品列表">价格</a>
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['goods-'.$url_platform_name.'/shop-follow-sale-log-index'])?>?goods_shop_id={{ d.goods_shop_id }}" data-title="跟卖商品明细" data-callback_title="跟卖商品列表">查看明细</a>
    {{# if(d.type == <?=GoodsShopFollowSale::TYPE_FOLLOW_OFF?>){ }}
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="operating" data-title="恢复跟卖" data-url="<?=Url::to(['goods-'.$url_platform_name.'/shop-follow-restore'])?>?id={{ d.goods_shop_id }}">恢复跟卖</a>
    {{# } }}
</script>
<script type="text/html" id="goodsImgTpl">
    <a href="{{d.image}}" data-lightbox="pic">
        <img class="layui-circle" src="{{d.image}}" width="100"/>
    </a>
</script>
<script type="text/html" id="goodsNoTpl">
    <a lay-event="update" data-url="<?=Url::to(['goods/view'])?>?goods_no={{ d.goods_no }}" style="color: #00a0e9">{{d.goods_no}}</a><br/>
    <a class="layui-btn layui-btn-xs layui-btn-a" lay-event="update" data-url="<?=Url::to(['order/own-index?search=1&tag=10&OrderSearch%5Bsource%5D='.$platform_type.'&OrderSearch%5Bplatform_asin%5D='])?>{{d.sku_no}}" data-title="订单信息" style="color: #00a0e9"><i class="layui-icon layui-icon-template"></i></a><b>{{d.sku_no}}</b><br/>
    自定义Sku：<b><a style="color: #00a0e9" href="{{ d.goods_url || ''  }}" target="_blank">{{d.platform_sku_no || '无'}}</a></b><br/>
    EAN:{{d.ean || '无'}}<br/>
    销售量:{{d.total_sales || '0'}}
</script>
<script type="text/html" id="shopTpl">
    {{d.shop_name || '' }}<br/>
    平台类目：<b>{{d.category_name || '' }}</b><br/>
    <?=$platform_name?>类目：{{ d.o_category_name || '' }}<br/>
    类型:
    {{ d.type_desc || '' }}
</script>
<script type="text/html" id="goodsTpl">
    <div class="span-goode-name">{{d.goods_name||''}}</div>
    价格: {{ d.own_price || '' }} {{ d.currency }}<br/>
    跟卖最低价: {{ d.min_price || ''  }} {{ d.currency || ''  }}<br/>
    跟卖人数: {{ d.number || ''  }}<br/>
    调整次数: {{ d.adjustment_times || ''  }}
    {{#  if(d.is_min_price == 1){ }}
        <span  style="padding: 0px 10px;float: right" class="layui-font-12 layui-bg-orange">是</span>
    {{# } }}
</script>
<script type="text/html" id="userTpl">
    修改: {{d.follow_update_time ||''}}<br/>
    计划执行: {{d.plan_time ||''}}
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
