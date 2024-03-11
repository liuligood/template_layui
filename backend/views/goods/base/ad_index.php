
<?php
use yii\helpers\Url;
use common\components\statics\Base;

$platform_name = \common\components\statics\Base::$platform_maps[$platform_type];
$url_platform_name = strtolower($platform_name);
?>
<style>
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
    .layui-btn-a {
        border: none;
        background-color: rgba(0, 0, 0, 0);
        -webkit-user-select:text;
    }
    .span-goode-name{
        color:#a0a3a6;
        font-size: 13px;
        width: 320px;
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
                <li class="layui-this"><a href="<?=Url::to(['goods-'.$url_platform_name.'/ad-index'])?>">收藏</a></li>
                <?php if (in_array($platform_type,[Base::PLATFORM_HEPSIGLOBAL,Base::PLATFORM_RDC,Base::PLATFORM_WORTEN])) {?>
                <li><a href="<?=Url::to(['goods-'.$url_platform_name.'/shop-follow-sale-index'])?>">跟卖商品</a></li>
                <?php }?>
                <?php if (in_array($platform_type,[Base::PLATFORM_RDC,Base::PLATFORM_CDISCOUNT,Base::PLATFORM_EPRICE,Base::PLATFORM_WORTEN,Base::PLATFORM_FYNDIQ,Base::PLATFORM_WILDBERRIES,Base::PLATFORM_MIRAVIA,Base::PLATFORM_EMAG])) {?>
                    <li><a href="<?=Url::to(['goods-'.$url_platform_name.'/overseas-index'])?>">海外仓</a></li>
                <?php }?>
            </ul>
        </div>
        <div class="lay-lists">
            <form>
                <div class="layui-form lay-search" style="padding-left: 10px;padding-top: 10px">
                    <div class="layui-inline">
                        商品编号
                        <textarea name="BaseGoodsSearch[goods_no]" class="layui-textarea search-con" style="height: 39px; min-height:39px"></textarea>
                    </div>
                    <div class="layui-inline">
                        SKU
                        <textarea name="BaseGoodsSearch[sku_no]" class="layui-textarea search-con" style="height: 39px; min-height:39px"></textarea>
                    </div>
                    <div class="layui-inline">
                        自定义SKU
                        <textarea name="BaseGoodsSearch[platform_sku_no]" class="layui-textarea search-con" style="height: 39px; min-height:39px"></textarea>
                    </div>
                    <div class="layui-inline">
                        EAN
                        <textarea name="BaseGoodsSearch[ean]" class="layui-textarea search-con" style="height: 39px; min-height:39px"></textarea>
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
                        <label>创建时间</label>
                        <input  class="layui-input search-con ys-datetime" name="BaseGoodsSearch[start_add_time]" id="start_add_time" autocomplete="off">
                    </div>
                    <span class="layui-inline layui-vertical-20">
        -
    </span>
                    <div class="layui-inline layui-vertical-20">
                        <input class="layui-input search-con ys-datetime" name="BaseGoodsSearch[end_add_time]" id="end_add_time" autocomplete="off">
                    </div>
                    <div class="layui-inline layui-vertical-20">
                        <button class="layui-btn" id="search_btn" data-type="search_lists">搜索</button>
                    </div>
                </div>
            </form>

            <div class="layui-form" style="padding: 10px">
            </div>
            <div class="layui-card-body">
                <table id="goods-<?=$url_platform_name?>" class="layui-table" lay-data="{url:'<?=Url::to(['goods-'.$url_platform_name.'/list?ad_status=2'])?>', height : 'full-190', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, method :'post',limit : 20}" lay-filter="goods-<?=$url_platform_name?>">
                    <thead>
                    <tr>
                        <th lay-data="{type: 'checkbox', width:50}">ID</th>
                        <th lay-data="{ width:140, align:'center',templet:'#goodsImgTpl'}">商品主图</th>
                        <th lay-data="{ width:200, align:'left',templet:'#goodsNoTpl'}">商品编号</th>
                        <th lay-data="{field:'shop_name', align:'left',width:240,templet:'#shopTpl'}">店铺/分类/状态</th>
                        <th lay-data="{ width:350, align:'left',templet:'#goodsTpl'}">商品信息</th>
                        <th lay-data="{ width:180, align:'left',templet:'#userTpl'}">时间</th>
                        <th lay-data="{minWidth:175, templet:'#goodsListBar',align:'center'}">操作</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<script type="text/html" id="goodsImgTpl">
    <a href="{{d.image}}" data-lightbox="pic">
        <img class="layui-circle" src="{{d.image}}?imageView2/2/h/100" width="100"/>
    </a>
</script>

<script type="text/html" id="goodsTpl">
    <div class="span-goode-name">{{d.goods_name||''}}</div>
    价格：<span style="color: {{# if(d.price_level==3){ }}#FF5722{{# }else if (d.price_level==2) { }}#FFB800{{# } else{ }}#009688{{# } }}">{{d.price||''}}</span>
</script>

<script type="text/html" id="goodsNoTpl">
    <a lay-event="update" data-url="<?=Url::to(['goods/view'])?>?goods_no={{ d.goods_no }}" style="color: #00a0e9">{{d.goods_no}}</a><br/>
    <a class="layui-btn layui-btn-xs layui-btn-a" lay-event="update" data-url="<?=Url::to(['order/own-index?search=1&tag=10&OrderSearch%5Bsource%5D='.$platform_type.'&OrderSearch%5Bplatform_asin%5D='])?>{{d.sku_no}}" data-title="订单信息" style="color: #00a0e9"><i class="layui-icon layui-icon-template"></i></a><b>{{d.sku_no}}</b><br/>
    自定义Sku：<b>{{d.platform_sku_no || '无'}}</b><br/>
    EAN:{{d.ean || '无'}}
</script>

<script type="text/html" id="shopTpl">
    {{d.shop_name}}<br/>
    平台类目：<b>{{d.category_name}}【{{d.o_category_name || '无'}}】</b><br/>
    <!--<span style="padding: 0px 10px;float: left;line-height: 24px" class="layui-font-12 {{#  if(d.gs_status == <?=\common\models\GoodsShop::STATUS_FAIL?>){ }} layui-bg-red {{# }else if(d.gs_status == <?=\common\models\GoodsShop::STATUS_SUCCESS?>){ }} layui-bg-green {{# } else{ }} layui-bg-orange{{# } }}">{{d.gs_status_desc}}</span>-->
</script>

<!--操作-->
<script type="text/html" id="goodsListBar">
    {{#  if(d.gs_status != <?=\common\models\GoodsShop::STATUS_OFF_SHELF?>){ }}
    <a class="layui-btn layui-btn-xs" lay-event="open" data-width="950px" data-height="550px" data-url="<?=Url::to(['goods-'.$url_platform_name.'/update-price'])?>?id={{ d.id }}" data-title="编辑价格" data-callback_title="商品列表">价格</a> <br/>
    {{# if(d.gs_status != <?=\common\models\GoodsShop::STATUS_NOT_TRANSLATED?>){ }}
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['goods-'.$url_platform_name.'/update'])?>?id={{ d.id }}" data-title="编辑商品" data-callback_title="商品列表">编辑</a>
    {{# } }}
    {{# } }}
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="operating" data-title="移除" data-url="<?=Url::to(['goods-'.$url_platform_name.'/delete-ad'])?>?id={{ d.id}}">移除</a>
</script>

<script type="text/html" id="userTpl">
    操作者:{{d.admin_name||'无'}}<br/>
    创建:{{d.add_time ||''}}<br/>
    修改:{{d.update_time ||''}}
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
