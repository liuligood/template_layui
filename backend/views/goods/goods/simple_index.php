
<?php
use yii\helpers\Url;
use common\models\Goods;
use common\services\goods\GoodsService;
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
    .layui-tab{
        margin-top: 0;
    }
    .span-circular-red{
        display: inline-block;
        min-width: 26px;
        height: 26px;
        border-radius: 50%;
        background-color: #ff6666;
        color: #fff;
        text-align: center;
        padding: 5px;
        line-height: 18px;
    }
    .span-circular-grey{
        display: inline-block;
        min-width: 26px;
        height: 26px;
        border-radius: 50%;
        background-color: #999999;
        color: #fff;
        text-align: center;
        padding: 5px;
        line-height: 18px;
    }
</style>
<div class="layui-fluid">
    <div class="layui-card">
<div class="lay-lists">
    <form>
<div class="layui-form lay-search" style="padding: 10px">
    <div class="layui-inline">
        商品编号
        <textarea name="GoodsSearch[goods_no]" class="layui-textarea search-con" style="height: 39px; min-height:39px"></textarea>
    </div>
    <div class="layui-inline">
        SKU
        <!--<input class="layui-input search-con" name="GoodsSearch[sku_no]" autocomplete="off">-->
        <textarea name="GoodsSearch[sku_no]" class="layui-textarea search-con" style="height: 39px; min-height:39px"></textarea>
    </div>
    <div class="layui-inline layui-vertical-20">
    <button class="layui-btn" data-type="search_lists">搜索</button>
    </div>
    <div class="layui-inline layui-vertical-20">
        <a class="layui-btn layui-btn-normal" data-type="open" data-url="<?=Url::to(['goods/supplementary-claim'])?>" data-width="650px" data-height="400px" data-title="补认领">补认领</a>
    </div>
</div>
    </form>
    <div class="layui-card-body">
<table id="goods" class="layui-table" lay-data="{url:'<?=Url::to(['goods/list?tag='.$tag.'&source_method='.$source_method.'&goods_stamp_tag='.$goods_stamp_tag])?>', height : 'full-90',method :'post', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20}" lay-filter="goods">
    <thead>
    <tr>
        <th lay-data="{type: 'checkbox', fixed:'left', width:50}">ID</th>
        <th lay-data="{field: 'goods_no',align:'left',width:150}">商品编号</th>
        <th lay-data="{field: 'sku_no', align:'left',width:150}">SKU</th>
        <?php if(in_array($tag,[3,4])){?>
        <th lay-data="{field: 'source_platform_type', align:'left',width:100}">来源平台</th>
        <?php }?>
        <?php if(in_array($tag,[4])){?>
            <th lay-data="{field: 'source_platform_title', align:'left',width:100}">来源平台品牌</th>
            <th lay-data="{field: 'source_platform_category_id', align:'left',width:130}">来源平台类目id</th>
            <th lay-data="{field: 'source_platform_category_name', align:'left',width:200}">来源平台类目</th>
        <?php }?>
        <?php if($tag != 1){?>
        <th lay-data="{field: 'category_name', align:'left',width:150}">平台类目</th>
        <?php }?>
        <th lay-data="{field: 'goods_name', width:200}">标题</th>
        <th lay-data="{field: 'goods_short_name', width:180}">短标题</th>
        <th lay-data="{field: 'image', width:100, align:'center',templet:'#goodsImgTpl'}">商品主图</th>
        <th lay-data="{field: 'price',  align:'center',width:80}">价格</th>
        <th lay-data="{field: 'weight',  align:'center',width:90}">重量(kg)</th>
        <th lay-data="{field: 'colour',  align:'center',width:100}">颜色</th>
        <!--<th lay-data="{field: 'goods_content', width:180}">详细描述</th>-->
        <?php if($source_method == GoodsService::SOURCE_METHOD_AMAZON){?>
        <th lay-data="{field: 'stock_desc',  align:'center',width:80}">库存</th>
        <?php } ?>
        <th lay-data="{field: 'status_desc',  align:'center',width:80}">状态</th>
        <?php if($all_goods_access){?>
        <th lay-data="{field: 'owner_name',  align:'center',width:110}">归属者</th>
        <?php }?>
        <th lay-data="{field: 'admin_name',  align:'center',width:110}">操作者</th>
        <!--<th lay-data="{field: 'status', width:120, templet: '#statusTpl', unresize: true}">状态</th>-->
        <th lay-data="{field: 'add_time',  align:'left',minWidth:50}">创建时间</th>
        <th lay-data="{minWidth:250, templet:'#goodsListBar',fixed:'right',align:'center'}">操作</th>
    </tr>
    </thead>
</table>
</div>
</div>
    </div>
</div>

<!--操作-->
<script type="text/html" id="goodsListBar">

    <div class="layui-inline">
        <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="url" data-url="<?=Url::to(['goods/view'])?>?goods_no={{ d.goods_no }}" data-title="查看商品" data-callback_title="商品列表">查看</a>
    </div>
    
    {{# if(d['status'] == 10 || d['status'] == 8 || d['status'] == 20){ }}
    {{# if( d.source_method == 1){ }}
    <!--<div class="layui-inline">
        <a class="layui-btn layui-btn-xs layui-btn-normal" lay-event="open" data-url="<?=Url::to(['goods/set-stock'])?>?goods_no={{ d.goods_no }}" data-width="850px" data-height="700px" data-title="库存" data-callback_title="商品列表">库存</a>
    </div>-->
    {{# } }}
    {{# } }}

    {{# if(d['status'] == 10 || d['status'] == 8){ }}
    <!--<a class="layui-btn layui-btn-xs" lay-event="operating" data-url="<?=Url::to(['goods/claim'])?>?goods_no={{ d.goods_no }}" data-title="认领到Allegro" data-callback_title="商品列表">认领到Allegro</a>-->
    <div class="layui-inline">
    <a class="layui-btn layui-btn-xs" lay-event="open" data-url="<?=Url::to(['goods/claim'])?>?goods_no={{ d.goods_no }}" data-width="600px" data-height="500px" data-title="认领" data-callback_title="商品列表">认领</a>
    </div>
    {{# } }}
    <?php if($tag == 2){?>
    <div class="layui-inline">
        <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['goods/update'])?>?id={{ d.id }}" data-title="编辑商品" data-callback_title="商品列表">编辑</a>
    </div>
    <?php }?>
    <?php if($tag != 2 || \common\services\sys\AccessService::hasAllGoods()){?>
    <div class="layui-inline">
    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete" data-url="<?=Url::to(['goods/delete'])?>?id={{ d.id }}">删除</a>
    </div>
    <?php }?>
</script>

<script type="text/html" id="statusTpl">
    <input type="checkbox" name="状态" value="{{d.id}}" data-url="<?=Url::to(['goods/update-status'])?>?id={{ d.id }}" lay-skin="switch" lay-text="正常|禁用" lay-filter="statusSwitch" {{ d.status ==10 ? 'checked' : '' }}>
</script>

<script type="text/html" id="goodsImgTpl">
    <a href="{{d.image}}" data-lightbox="pic">
        <img class="layui-circle" src="{{d.image}}?imageView2/2/h/100" height="26"/>
    </a>
</script>

<script>
    const tableName="goods";
    const shopArr ='<?=json_encode($shop_arr)?>';
    const source_method='<?=$source_method?>';
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?".time())?>
<?=$this->registerJsFile("@adminPageJs/goods/lists.js?".time())?>
<?php
$this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/xlsx.core.min.js?v=1',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export-excel.js?v=1',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export-function.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
?>
