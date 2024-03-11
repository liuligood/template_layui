<?php
use yii\helpers\Url;
use yii\helpers\Html;
use common\services\goods\GoodsService;
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
        <li class="layui-this"><a href="<?=Url::to(['goods-'.$url_platform_name.'/platform-index'])?>">商品库</a></li>
        <li><a href="<?=Url::to(['goods-'.$url_platform_name.'/index'])?>">在线商品</a></li>
        <li><a href="<?=Url::to(['goods-ozon/ad-index'])?>">收藏</a></li>
        <li ><a href="<?=Url::to(['goods-ozon/shop-follow-sale-index'])?>">跟卖商品</a></li>
        <li ><a href="<?=Url::to(['goods-ozon/overseas-index'])?>">海外仓</a></li>
    </ul>
</div>
<!--
<div class="layui-tab layui-tab-brief">
    <ul class="layui-tab-title">
        <li <?php if($tag == GoodsService::PLATFORM_GOODS_AUDIT_STATUS_UNCONFIRMED){?>class="layui-this" <?php }?>><a href="">未审核</a></li>
        <li <?php if($tag == GoodsService::PLATFORM_GOODS_AUDIT_STATUS_NORMAL){?>class="layui-this" <?php }?>><a href="<?=Url::to(['goods-'.$url_platform_name.'/platform-index?tag='.GoodsService::PLATFORM_GOODS_AUDIT_STATUS_NORMAL])?>">正常</a></li>
        <li <?php if($tag == GoodsService::PLATFORM_GOODS_AUDIT_STATUS_ABNORMAL){?>class="layui-this" <?php }?>><a href="<?=Url::to(['goods-'.$url_platform_name.'/platform-index?tag='.GoodsService::PLATFORM_GOODS_AUDIT_STATUS_ABNORMAL])?>">异常</a></li>
    </ul>
</div>-->
<div class="lay-lists">
<!--<form class="layui-form">
    <blockquote class="layui-elem-quote quoteBox">
        <div class="layui-inline">
            <a class="layui-btn" data-type="url" data-title="添加商品" data-url="<?=Url::to(['goods-'.$url_platform_name.'/create'])?>" data-callback_title = "商品列表" >添加商品</a>
        </div>
    </blockquote>
</form>-->
    <form>
<div class="layui-form lay-search" style="padding-left: 10px">
    <div class="layui-inline" style="border: solid 2px rgb(0, 150, 136);margin-right: 10px;">
        <div class="layui-form" style="width: 100px;float: right;">
            <select lay-filter="sel_url">
                <option <?php if($tag == GoodsService::PLATFORM_GOODS_AUDIT_STATUS_UNCONFIRMED){?>selected <?php }?> value="<?=Url::to(['goods-'.$url_platform_name.'/platform-index?tag='.GoodsService::PLATFORM_GOODS_AUDIT_STATUS_UNCONFIRMED])?>">未审核</option>
                <option <?php if($tag == GoodsService::PLATFORM_GOODS_AUDIT_STATUS_NORMAL){?>selected <?php }?> value="<?=Url::to(['goods-'.$url_platform_name.'/platform-index?tag='.GoodsService::PLATFORM_GOODS_AUDIT_STATUS_NORMAL])?>">正常</option>
                <option <?php if($tag == GoodsService::PLATFORM_GOODS_AUDIT_STATUS_ABNORMAL){?>selected <?php }?> value="<?=Url::to(['goods-'.$url_platform_name.'/platform-index?tag='.GoodsService::PLATFORM_GOODS_AUDIT_STATUS_ABNORMAL])?>">异常</option>
            </select>
        </div>
    </div>
    <div class="layui-inline">
        商品编号
        <textarea name="BaseGoodsSearch[goods_no]" class="layui-textarea search-con" style="height: 39px; min-height:39px"></textarea>
    </div>
    <div class="layui-inline">
        SKU
        <textarea name="BaseGoodsSearch[sku_no]" class="layui-textarea search-con" style="height: 39px; min-height:39px"></textarea>
    </div>
    <!--<div class="layui-inline">
        标题
        <input class="layui-input search-con" name="BaseGoodsSearch[goods_name]" autocomplete="off">
    </div>-->
    <div class="layui-inline">
        平台类目
        <div id="div_category_id" style="width: 180px;"></div>
        <input id="category_id" class="layui-input search-con" type="hidden" name="BaseGoodsSearch[category_id]" autocomplete="off">
    </div>
    <div class="layui-inline" style="width: 200px;">
        店铺(已认领)
        <select name="BaseGoodsSearch[claim_shop_name]" class="layui-input search-con ys-select2" data-placeholder="请选择" lay-ignore >
            <option value="" >请选择</option>
            <?php foreach ($shop_arr as $ptype_v){ ?>
                <option value="P_<?=$ptype_v['id']?>">【 <?=$ptype_v['title']?> 】</option>
                <?php foreach ($ptype_v['child'] as $shop_v){ ?>
                    <option value="<?=$shop_v['id']?>"><?=$shop_v['title']?></option>
                <?php }?>
            <?php }?>
        </select>
    </div>
    <div class="layui-inline" style="width: 220px">
        <label>排除已认领店铺</label>
        <select name="BaseGoodsSearch[un_claim_shop_name][]" class="layui-input search-con ys-select2" multiple="multiple" data-placeholder="全部" lay-ignore >
            <?php foreach ($shop_arr as $ptype_v){ ?>
                <option value="P_<?=$ptype_v['id']?>">【 <?=$ptype_v['title']?> 】</option>
                <?php foreach ($ptype_v['child'] as $shop_v){ ?>
                    <option value="<?=$shop_v['id']?>"><?=$shop_v['title']?></option>
                <?php }?>
            <?php }?>
        </select>
    </div>
    <?php if($all_goods_access) {?>
        <div class="layui-inline">
            归属者
            <?= Html::dropDownList('BaseGoodsSearch[admin_id]', null, $admin_arr,
                ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'data-placeholder' => '请选择','prompt' => '请选择','lay-search'=>'lay-search']) ?>
        </div>
        <div class="layui-inline" style="width: 220px">
            <label>排除归属者</label>
            <?php //echo Html::dropDownList('GoodsSearch[exclude_claim_shop_name]', null, [],['lay-ignore'=>'lay-ignore','data-placeholder' => '全部',"multiple"=>"multiple",'class'=>"layui-input search-con ys-select2"]) ?>
            <select name="BaseGoodsSearch[un_admin_id][]" class="layui-input search-con ys-select2" multiple="multiple" data-placeholder="全部" lay-ignore >
                <?php foreach ($admin_arr as $arr_k => $arr_v){ ?>
                    <option value="<?=$arr_k?>"><?=$arr_v?></option>
                <?php }?>
            </select>
        </div>
    <?php }?>
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
    <div class="layui-inline layui-vertical-20">
    <button class="layui-btn" data-type="search_lists">搜索</button>
    </div>
    <?php if($platform_type == 30){ ?>
    <div class="layui-inline layui-vertical-20">
        <a class="layui-btn layui-btn-normal" target="_blank" data-ignore="ignore" href="<?=Url::to(['goods-'.$url_platform_name.'/examine?tag='.$tag])?>" data-title="审查商品" data-callback_title = "商品列表" >审查商品</a>
    </div>
    <?php }?>
</div>
    </form>

    <div class="layui-form" style="padding: 10px">

        <?php if(in_array($tag,[0,2])){?>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-sm js-batch" data-title="移入正常" data-url="<?=Url::to(['goods-'.$url_platform_name.'/batch-update-audit-status?status=1'])?>" >批量移入正常</a>
            </div>
        <?php }?>
        <?php if(in_array($tag,[0,1])){?>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-sm layui-btn-danger js-batch"  data-title="移入异常" data-url="<?=Url::to(['goods-'.$url_platform_name.'/batch-update-audit-status?status=2'])?>" >批量移入异常</a>
            </div>
        <?php }?>

        <?php if($all_goods_access && in_array($tag ,[0])){?>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-sm layui-btn-normal allo_btn" data-url="<?=Url::to(['goods-'.$url_platform_name.'/batch-allo?'])?>" >选中批量分配</a>
            </div>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-sm layui-btn-normal allo_all_btn" data-url="<?=Url::to(['goods-'.$url_platform_name.'/batch-allo?tag='.$tag])?>">全部批量分配</a>
            </div>
        <?php }?>

        <?php if($all_goods_access && in_array($tag ,[0,1])){?>
        <div class="layui-inline">
            <a class="layui-btn layui-btn-sm layui-btn-normal valid_btn" data-url="<?=Url::to(['goods-'.$url_platform_name.'/batch-claim?'])?>" >选中批量认领</a>
        </div>
        <div class="layui-inline">
            <a class="layui-btn layui-btn-sm layui-btn-normal valid_all_btn" data-url="<?=Url::to(['goods-'.$url_platform_name.'/batch-claim?tag='.$tag])?>" >全部批量认领</a>
        </div>
        <?php }?>
    </div>
    <div class="layui-card-body">
    <table id="goods-<?=$url_platform_name?>" class="layui-table" lay-data="{url:'<?=Url::to(['goods-'.$url_platform_name.'/platform-list?tag='.$tag])?>', height : 'full-195', cellMinWidth : 95, page:{limits:[50, 100, 500, 1000]}, method :'post',limit : 100}" lay-filter="goods-<?=$url_platform_name?>">
    <thead>
    <tr>
        <th lay-data="{type: 'checkbox', <?php echo $tag==0?'LAY_CHECKED: true,':'';?> width:50}">ID</th>
        <th lay-data="{ width:140, align:'center',templet:'#goodsImgTpl'}">商品主图</th>
        <th lay-data="{ width:200, align:'center',templet:'#goodsTpl1'}">商品编号</th>
        <th lay-data="{ width:350, align:'center',templet:'#goodsTpl'}">商品信息</th>
        <th lay-data="{ width:220, align:'center',templet:'#userTpl'}">时间</th>
        <th lay-data="{minWidth:175, templet:'#goodsListBar',align:'center'}">操作</th>
    </tr>
    </thead>
</table>
</div>
</div>
    </div>
</div>
<!--操作-->
<script type="text/html" id="goodsListBar">
    {{#  if(d.audit_status != <?=\common\services\goods\GoodsService::PLATFORM_GOODS_AUDIT_STATUS_NORMAL?>){ }}
    <a class="layui-btn layui-btn-normal layui-btn-xs" data-fun="update-audit-status" lay-event="fun" data-title="移入正常" data-url="<?=Url::to(['goods-'.$url_platform_name.'/batch-update-audit-status?status=1&'])?>id={{ d.id }}">移入正常</a>
    {{# } }}
    {{#  if(d.audit_status != <?=\common\services\goods\GoodsService::PLATFORM_GOODS_AUDIT_STATUS_ABNORMAL?>){ }}
    <a class="layui-btn layui-btn-danger layui-btn-xs" data-fun="update-audit-status" lay-event="fun" data-title="移入异常" data-url="<?=Url::to(['goods-'.$url_platform_name.'/batch-update-audit-status?status=2&'])?>id={{ d.id }}">移入异常</a>
    {{# } }}
</script>

<script type="text/html" id="goodsImgTpl">
    <a href="{{d.image}}" data-lightbox="pic">
        <img class="layui-circle" src="{{d.image}}?imageView2/2/h/100" width="100"/>
    </a>
</script>

<script type="text/html" id="goodsTpl">
    <div class="span-goode-name">{{d.goods_name||''}}</div>
    <div class="span-goode-name">{{d.goods_name_cn || (d.goods_name_cn||'')}}</div>
</script>

<script type="text/html" id="goodsTpl1">
    {{# if(d.goods_status == 20){ }}<span style="color: #FFFFFF;background: red;padding: 2px 4px;" class="layui-font-12">禁</span>{{# } }}
    <b>{{d.sku_no}}</b><br/>
    <a lay-event="update" data-url="<?=Url::to(['goods/view'])?>?goods_no={{ d.goods_no }}" style="color: #00a0e9">{{d.goods_no}}</a><br/>
    平台类目：<b>{{d.category_name}}</b><br/>
    <?=$platform_name?>类目：{{d.o_category_name || '无'}}<br/>
</script>

<script type="text/html" id="userTpl">
    所属者:{{d.admin_name||'无'}}<br/>
    创建:{{d.add_time ||''}}<br/>
    更新:{{d.update_time ||''}}
</script>

<script>
    const tableName="goods-<?=$url_platform_name?>";
    const categoryArr ='';
</script>
<?php
$this->registerJsFile("@adminPageJs/base/lists.js?v=1.2.2");
$this->registerJsFile("@adminPageJs/goods/base_lists.js?".time());
$this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
?>
