
<?php
use yii\helpers\Url;
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
    .layui-tab{
        margin-top: 0;
    }
</style>
<div class="layui-fluid">
    <div class="layui-card">
<div class="layui-tab layui-tab-brief">
    <ul class="layui-tab-title">
        <li><a href="<?=Url::to(['goods-fruugo/platform-index'])?>">商品库</a></li>
        <li class="layui-this"><a href="<?=Url::to(['goods-fruugo/index'])?>">在线商品</a></li>
    </ul>
</div>
<div class="lay-lists">
<!--<form class="layui-form">
    <blockquote class="layui-elem-quote quoteBox">
        <div class="layui-inline">
            <a class="layui-btn" data-type="url" data-title="添加商品" data-url="<?=Url::to(['goods-fruugo/create'])?>" data-callback_title = "商品列表" >添加商品</a>
        </div>
    </blockquote>
</form>-->
    <form>
<div class="layui-form lay-search" style="padding-left: 10px">
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
        标题
        <input class="layui-input search-con" name="BaseGoodsSearch[goods_name]" autocomplete="off">
    </div>
    <div class="layui-inline">
        店铺
        <?= \yii\helpers\Html::dropDownList('BaseGoodsSearch[shop_id]', null, \common\services\ShopService::getShopMap(\common\components\statics\Base::PLATFORM_FRUUGO),
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
        <input  class="layui-input search-con ys-datetime" name="BaseGoodsSearch[end_add_time]" id="end_add_time" autocomplete="off">
    </div>
    <div class="layui-inline">
        <div style="padding-left: 10px">
            <input class="layui-input search-con" type="checkbox" value="1" name="BaseGoodsSearch[un_o_category_name]" lay-skin="primary" title="未设置Fruugo类目">
        </div>
    </div>
    <div class="layui-inline layui-vertical-20">
    <button class="layui-btn" data-type="search_lists">搜索</button>
        <button class="layui-btn layui-btn-normal" data-type="export_load_lists"  data-method="post" data-url="<?=Url::to(['goods-fruugo/export'])?>">导出</button>
    </div>
</div>
    </form>
<div class="layui-form" style="padding: 10px">
    <div class="layui-inline">
        <a class="layui-btn layui-btn-sm layui-btn-normal batch_o_category_btn" data-url="<?=Url::to(['goods-fruugo/batch-category'])?>">批量设置Fruugo类目</a>
    </div>
    <div class="layui-inline">
        <a class="layui-btn layui-btn-sm layui-btn-danger batch_del_btn" data-url="<?=Url::to(['goods-fruugo/batch-del'])?>" >批量删除</a>
    </div>
</div>
    <div class="layui-card-body">
<table id="goods-fruugo" class="layui-table" lay-data="{url:'<?=Url::to(['goods-fruugo/list'])?>', height : 'full-190', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]},method :'post', limit : 20}" lay-filter="goods-fruugo">
    <thead>
    <tr>
        <th lay-data="{type: 'checkbox', fixed:'left', width:50}">ID</th>
        <th lay-data="{field: 'goods_no', align:'left',width:150}">商品编号</th>
        <th lay-data="{field: 'shop_name', align:'left',width:130}">店铺名称</th>
        <th lay-data="{field: 'category_name', align:'left',width:130}">平台类目</th>
        <th lay-data="{field: 'o_category_name', align:'left',width:130}">Fruugo类目</th>
        <th lay-data="{field: 'sku_no', align:'left',width:130}">SKU</th>
        <th lay-data="{field: 'ean', align:'left',width:140}">EAN</th>
        <th lay-data="{field: 'goods_name', width:230}">标题</th>
        <th lay-data="{field: 'goods_short_name', width:180}">短标题</th>
        <th lay-data="{field: 'image', width:100, align:'center',templet:'#goodsImgTpl'}">商品主图</th>
        <th lay-data="{field: 'status_desc',  align:'center',width:110}">状态</th>
        <th lay-data="{field: 'price',  align:'center',width:80}">价格</th>
        <th lay-data="{field: 'brand',  align:'center',width:120}">品牌</th>
        <th lay-data="{field: 'colour',  align:'center',width:120}">颜色</th>
        <th lay-data="{field: 'size',  align:'center',width:100}">尺寸</th>
        <th lay-data="{field: 'weight',  align:'center',width:100}">重量</th>
        <th lay-data="{field: 'goods_content', width:180}">详细描述</th>
        <th lay-data="{field: 'admin_name',  align:'center',width:110}">操作者</th>
        <th lay-data="{field: 'add_time',  align:'left',width:150}">创建时间</th>
        <th lay-data="{minWidth:175, templet:'#goodsListBar',fixed:'right',align:'center'}">操作</th>
    </tr>
    </thead>
</table>
</div>
</div>
    </div>
</div>
<!--操作-->
<script type="text/html" id="goodsListBar">
    <a class="layui-btn layui-btn-xs" lay-event="open" data-width="950px" data-height="550px" data-url="<?=Url::to(['goods-fruugo/update-price'])?>?id={{ d.id }}" data-title="编辑价格" data-callback_title="商品列表">价格</a>
    {{#  if(d.status != <?=\common\services\goods\GoodsService::PLATFORM_GOODS_STATUS_UNCONFIRMED?>){ }}
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['goods-fruugo/update'])?>?id={{ d.id }}" data-title="编辑商品" data-callback_title="商品列表">编辑</a>
    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete" data-url="<?=Url::to(['goods-fruugo/delete'])?>?id={{ d.id }}">删除</a>
    {{# } }}
</script>

<script type="text/html" id="goodsImgTpl">
    <a href="{{d.image}}" data-lightbox="pic">
        <img class="layui-circle" src={{d.image}} height="26"/>
    </a>
</script>

<script>
    const tableName="goods-fruugo";
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
