
<?php
use yii\helpers\Url;
use common\models\Goods;
use common\services\goods\GoodsService;
use yii\helpers\Html;
use common\models\goods\UserGoods;
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
<div class="layui-tab layui-tab-brief">
    <ul class="layui-tab-title">
        <li <?php if($tag == UserGoods::USER_GOODS_STATUS_UNTREATED){?>class="layui-this" <?php }?>><a href="<?=Url::to(['user-goods/index?tag='.UserGoods::USER_GOODS_STATUS_UNTREATED])?>">未处理</a></li>
        <li <?php if($tag == UserGoods::USER_GOODS_STATUS_VALID){?>class="layui-this" <?php }?>><a href="<?=Url::to(['user-goods/index?tag='.UserGoods::USER_GOODS_STATUS_VALID])?>">通过</a></li>
        <li <?php if($tag == UserGoods::USER_GOODS_STATUS_INVALID){?>class="layui-this" <?php }?>><a href="<?=Url::to(['user-goods/index?tag='.UserGoods::USER_GOODS_STATUS_INVALID])?>">不通过</a></li>
    </ul>
</div>
<div class="lay-lists">
<form>
<div class="layui-form lay-search" style="padding-left: 10px">
    <div class="layui-inline">
        商品编号
        <textarea name="GoodsSearch[goods_no]" class="layui-textarea search-con" style="height: 39px; min-height:39px"></textarea>
    </div>
    <div class="layui-inline">
        SKU
        <textarea name="GoodsSearch[sku_no]" class="layui-textarea search-con" style="height: 39px; min-height:39px"></textarea>
    </div>
    <div class="layui-inline">
        标题
        <input class="layui-input search-con" name="GoodsSearch[goods_name]" autocomplete="off">
    </div>
    <div class="layui-inline">
        平台类目
        <?php if($tag != 3){?>
        <div id="div_category_id" style="width: 180px;"></div>
        <input id="category_id" class="layui-input search-con" type="hidden" name="GoodsSearch[category_id]" autocomplete="off">
        <?php }else{?>
        <?= Html::dropDownList('GoodsSearch[category_id]', null, $category_arr,
            ['prompt' => '全部','lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
        <?php }?>
    </div>
    <?php if($tag == 1){?>
    <div class="layui-inline" style="width: 200px;">
        店铺(已认领)
        <select name="GoodsSearch[claim_shop_name]" class="layui-input search-con ys-select2" data-placeholder="请选择" lay-ignore >
            <option value="">请选择</option>
            <?php foreach ($shop_arr as $ptype_v){ ?>
                <?php foreach ($ptype_v['child'] as $shop_v){ ?>
                    <option value="<?=$shop_v['id']?>"><?=$shop_v['title']?></option>
                <?php }?>
            <?php }?>
        </select>
    </div>
    <div class="layui-inline" style="width: 220px">
        <label>排除已认领店铺</label>
        <select name="GoodsSearch[un_claim_shop_name][]" class="layui-input search-con ys-select2" multiple="multiple" data-placeholder="全部" lay-ignore >
            <?php foreach ($shop_arr as $ptype_v){ ?>
                <option value="P_<?=$ptype_v['id']?>">【 <?=$ptype_v['title']?> 】</option>
                <?php foreach ($ptype_v['child'] as $shop_v){ ?>
                    <option value="<?=$shop_v['id']?>"><?=$shop_v['title']?></option>
                <?php }?>
            <?php }?>
        </select>
    </div>
    <?php }?>
    <div class="layui-inline">
        <label>创建时间</label>
        <input  class="layui-input search-con ys-datetime" name="GoodsSearch[start_add_time]" id="start_add_time" autocomplete="off">
    </div>
    <span class="layui-inline layui-vertical-20">
        -
    </span>
    <div class="layui-inline layui-vertical-20">
        <input  class="layui-input search-con ys-datetime" name="GoodsSearch[end_add_time]" id="end_add_time" autocomplete="off">
    </div>
    <div class="layui-inline">
        状态
        <?= Html::dropDownList('GoodsSearch[status]', null, [
            Goods::GOODS_STATUS_VALID => '正常',
            Goods::GOODS_STATUS_INVALID => '禁用',
        ], ['prompt' => '全部','lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
    </div>
    <div class="layui-inline layui-vertical-20">
    <button class="layui-btn" data-type="search_lists">搜索</button>
    </div>
</div>
</form>

<div class="layui-form" style="padding-left: 10px;margin-top: 10px">
    <?php if(in_array($tag,[0,2])){?>
    <div class="layui-inline">
        <a class="layui-btn layui-btn-sm js-batch" data-title="审查通过" data-url="<?=Url::to(['user-goods/batch-update-user-goods-status?status=1'])?>" >批量审查通过</a>
    </div>
    <?php }?>
    <?php if(in_array($tag,[0,1])){?>
    <div class="layui-inline">
        <a class="layui-btn layui-btn-sm layui-btn-danger js-batch"  data-title="审查不通过" data-url="<?=Url::to(['user-goods/batch-update-user-goods-status?status=2'])?>" >批量审查不通过</a>
    </div>
    <?php }?>
    <?php if($tag == 1){?>
        <div class="layui-inline">
            <a class="layui-btn layui-btn-sm layui-btn-normal valid_btn" data-url="<?=Url::to(['goods/batch-claim?source_method='.$source_method])?>" >选中批量认领</a>
        </div>
        <div class="layui-inline">
            <a class="layui-btn layui-btn-sm layui-btn-normal valid_all_btn" data-url="<?=Url::to(['goods/batch-claim?tag='.$tag.'&goods_stamp_tag='.(empty($goods_stamp_tag)?0:$goods_stamp_tag) .'&source_method='.$source_method])?>" >全部批量认领</a>
        </div>
        <!--<div class="layui-inline">
            <a class="layui-btn layui-btn-sm layui-btn-danger batch_invalid_btn" data-url="<?=Url::to(['goods/batch-update-status?source_method='.$source_method])?>" >批量禁用</a>
        </div>-->
    <?php }?>
    <!--<div class="layui-inline">
        <a class="layui-btn layui-btn-sm layui-btn-normal batch_category_btn" data-url="<?=Url::to(['goods/batch-update-category?source_method='.$source_method])?>" >批量设置类目</a>
    </div>
    <div class="layui-inline">
        <a class="layui-btn layui-btn-sm layui-btn-danger batch_del_btn" data-url="<?=Url::to(['goods/batch-del?source_method='.$source_method])?>" >批量删除</a>
    </div>-->
</div>
<table id="goods" class="layui-table" lay-data="{url:'<?=Url::to(['user-goods/list?tag='.$tag.'&source_method='.$source_method])?>', height : 'full-210',method :'post', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20}" lay-filter="goods">
    <thead>
    <tr>
        <th lay-data="{type: 'checkbox', fixed:'left', width:50}">ID</th>
        <th lay-data="{field: 'goods_no', align:'left',width:150}">商品编号</th>
        <th lay-data="{field: 'sku_no', align:'left',width:150}">SKU</th>
        <th lay-data="{field: 'category_name', align:'left',width:150}">平台类目</th>
        <th lay-data="{field: 'goods_name', width:200}">标题</th>
        <th lay-data="{field: 'goods_name_cn', width:180}">中文标题</th>
        <th lay-data="{field: 'image', width:100, align:'center',templet:'#goodsImgTpl'}">商品主图</th>
        <th lay-data="{field: 'price',  align:'center',width:80}">价格</th>
        <th lay-data="{field: 'weight',  align:'center',width:90}">重量(kg)</th>
        <th lay-data="{field: 'colour',  align:'center',width:100}">颜色</th>
        <th lay-data="{field: 'status_desc',  align:'center',width:80}">状态</th>
        <th lay-data="{field: 'add_time',  align:'left',width:150}">创建时间</th>
        <th lay-data="{minWidth:250, templet:'#goodsListBar',fixed:'right',align:'center'}">操作</th>
    </tr>
    </thead>
</table>
</div>

<!--操作-->
<script type="text/html" id="goodsListBar">
    <?php if($tag == 1){?>
    {{# if(d['status'] == 10 || d['status'] == 8){ }}
    <div class="layui-inline">
    <a class="layui-btn layui-btn-xs" lay-event="open" data-url="<?=Url::to(['goods/claim'])?>?goods_no={{ d.goods_no }}" data-width="600px" data-height="500px" data-title="认领" data-callback_title="商品列表">认领</a>
    </div>
    {{# } }}
    <?php }?>
    <div class="layui-inline">
        <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['goods/update'])?>?id={{ d.id }}" data-title="编辑商品" data-callback_title="商品列表">编辑</a>
    </div>
    <!--<?php if(!in_array($tag ,[2,5])){?>
    <div class="layui-inline">
    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete" data-url="<?=Url::to(['goods/delete'])?>?id={{ d.id }}">删除</a>
    </div>
    <?php }?>
    <div class="layui-inline">
        <a class="layui-btn layui-btn-xs" lay-event="operating" data-url="<?=Url::to(['goods/copy'])?>?goods_no={{ d.goods_no }}" data-title="复制">复制商品</a>
    </div>-->
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
    const categoryArr ='<?php echo $tag == 3 ? '' :addslashes(json_encode($category_arr))?>';
    const shopArr ='<?=json_encode($shop_arr)?>';
    const source_method='<?=$source_method?>';
    const property_data = '';
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
