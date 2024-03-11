
<?php

use common\components\statics\Base;
use yii\helpers\Url;
use common\models\Goods;
use common\models\FindGoods;
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
        <div class="layui-tab layui-tab-brief">
            <ul class="layui-tab-title">
                <li <?php if($tag == FindGoods::FIND_GOODS_STATUS_UNTREATED){?>class="layui-this" <?php }?>><a href="<?=Url::to(['find-goods/'.$url_platform_name.'-index?tag='.FindGoods::FIND_GOODS_STATUS_UNTREATED.'&platform_type='.$platform_type])?>">未完善</a></li>
                <li <?php if($tag == FindGoods::FIND_GOODS_STATUS_NORMAL){?>class="layui-this" <?php }?>><a href="<?=Url::to(['find-goods/'.$url_platform_name.'-index?tag='.FindGoods::FIND_GOODS_STATUS_NORMAL.'&platform_type='.$platform_type])?>">正常</a></li>
                <?php if (in_array($platform_type,[Base::PLATFORM_OZON,Base::PLATFORM_ALLEGRO])){?>
                <li <?php if($tag == $platform_type){?>class="layui-this" <?php }?>>
                    <a href="<?=Url::to(['find-goods/'.$url_platform_name.'-index?tag='.$platform_type.'&platform_type='.$platform_type])?>">
                        <?=$platform_type == Base::PLATFORM_OZON ? '俄语' : '波兰语'?>
                    </a></li>
                <?php }?>
            </ul>
        </div>
        <div class="lay-lists">
            <form>
                <div class="layui-form lay-search" style="padding: 10px">
                    <div class="layui-inline">
                        商品编号
                        <textarea name="FindGoodsSearch[goods_no]" class="layui-textarea search-con" style="height: 39px; min-height:39px"></textarea>
                    </div>
                    <div class="layui-inline">
                        SKU
                        <textarea name="FindGoodsSearch[sku_no]" class="layui-textarea search-con" style="height: 39px; min-height:39px"></textarea>
                    </div>
                    <div class="layui-inline">
                        标题
                        <input class="layui-input search-con" name="FindGoodsSearch[goods_name]" autocomplete="off">
                    </div>
                    <div class="layui-inline">
                        平台类目
                        <?php if($tag != 3){?>
                            <div id="div_category_id" style="width: 180px;"></div>
                            <input id="category_id" class="layui-input search-con" type="hidden" name="FindGoodsSearch[category_id]" autocomplete="off">
                        <?php }else{?>
                            <?= Html::dropDownList('FindGoodsSearch[category_id]', null, $category_arr,
                                ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search' ]) ?>
                        <?php }?>
                    </div>
                    <div class="layui-inline" style="width: 200px;">
                        店铺(已认领)
                        <select name="FindGoodsSearch[claim_shop_name]" class="layui-input search-con ys-select2" data-placeholder="请选择" lay-ignore >
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
                        <select name="FindGoodsSearch[un_claim_shop_name][]" class="layui-input search-con ys-select2" multiple="multiple" data-placeholder="全部" lay-ignore >
                            <?php foreach ($shop_arr as $ptype_v){ ?>
                                <option value="P_<?=$ptype_v['id']?>">【 <?=$ptype_v['title']?> 】</option>
                                <?php foreach ($ptype_v['child'] as $shop_v){ ?>
                                    <option value="<?=$shop_v['id']?>"><?=$shop_v['title']?></option>
                                <?php }?>
                            <?php }?>
                        </select>
                    </div>
                    <div class="layui-inline">
                        <label>创建时间</label>
                        <input  class="layui-input search-con ys-datetime" name="FindGoodsSearch[start_add_time]" id="start_add_time" autocomplete="off">
                    </div>
                    <span class="layui-inline layui-vertical-20">
        -
    </span>
                    <div class="layui-inline layui-vertical-20">
                        <input  class="layui-input search-con ys-datetime" name="FindGoodsSearch[end_add_time]" id="end_add_time" autocomplete="off">
                    </div>
                    <div class="layui-inline">
                        状态
                        <?= Html::dropDownList('FindGoodsSearch[status]', null, [
                            Goods::GOODS_STATUS_VALID => '正常',
                            Goods::GOODS_STATUS_INVALID => '禁用',
                        ], ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px' ]) ?>
                    </div>
                    <div class="layui-inline layui-vertical-20">
                        <button class="layui-btn" data-type="search_lists">搜索</button>
                    </div>
                </div>
            </form>

            <div class="layui-form" style="padding: 10px">
                <div class="layui-inline">
                    <a class="layui-btn layui-btn-sm layui-btn-normal valid_btn" data-url="<?=Url::to(['goods/batch-claim?source_method='.$source_method])?>" >选中批量认领</a>
                </div>
                <!--<div class="layui-inline">
            <a class="layui-btn layui-btn-sm layui-btn-normal valid_all_btn" data-url="<?=Url::to(['goods/batch-claim?tag='.$tag.'&goods_stamp_tag='.(empty($goods_stamp_tag)?0:$goods_stamp_tag) .'&source_method='.$source_method])?>" >全部批量认领</a>
        </div>
        <div class="layui-inline">
            <a class="layui-btn layui-btn-sm layui-btn-danger batch_invalid_btn" data-url="<?=Url::to(['goods/batch-update-status?source_method='.$source_method])?>" >批量禁用</a>
        </div>-->
            </div>
            <div class="layui-card-body">
                <table id="goods" class="layui-table" lay-data="{url:'<?=Url::to(['find-goods/list?tag='.$tag.'&source_method='.$source_method.'&platform_type='.$platform_type])?>', height : 'full-210',method :'post', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20}" lay-filter="goods">
                    <thead>
                    <tr>
                        <th lay-data="{type: 'checkbox', fixed:'left', width:50}">ID</th>
                        <th lay-data="{align:'left',width:200,templet:'#goodsTpl'}">商品编号</th>
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
        </div>
    </div>
</div>

<!--操作-->
<script type="text/html" id="goodsListBar">
    <?php if (in_array($platform_type,[Base::PLATFORM_OZON,Base::PLATFORM_ALLEGRO])){ ?>
    <div class="layui-inline">
        <a class="layui-btn layui-btn-xs" lay-event="update" data-title="添加分类属性" data-url="<?=Url::to(['goods/update-multilingual?type=2&platform_type='.$platform_type])?>&goods_no={{ d.goods_no }}" data-callback_title="商品列表">分类属性</a>
    </div>

    <div class="layui-inline">
        <a class="layui-btn layui-btn-xs" lay-event="update" data-title="添加语言" data-url="<?=Url::to(['goods/update-multilingual?type=3&platform_type='.$platform_type])?>&goods_no={{ d.goods_no }}" data-callback_title="商品列表"><?=$platform_type == Base::PLATFORM_OZON ? '俄语' : '波兰语'?></a>
    </div>
    <?php }?>
    <div class="layui-inline">
        <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['goods/update'])?>?id={{ d.id }}&ogid={{ d.ogid }}&aid={{ 2 }}" data-title="编辑商品" data-callback_title="商品列表">编辑</a>
    </div>
    <div class="layui-inline">
        <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete" data-url="<?=Url::to(['find-goods/delete'])?>?id={{ d.ogid }}">移除</a>
    </div>
</script>

<script type="text/html" id="statusTpl">
    <input type="checkbox" name="状态" value="{{d.id}}" data-url="<?=Url::to(['goods/update-status'])?>?id={{ d.id }}" lay-skin="switch" lay-text="正常|禁用" lay-filter="statusSwitch" {{ d.status ==10 ? 'checked' : '' }}>
</script>

<script type="text/html" id="goodsImgTpl">
    <a href="{{d.image}}" data-lightbox="pic">
        <img class="layui-circle" src="{{d.image}}?imageView2/2/h/100" height="26"/>
    </a>
</script>

<script type="text/html" id="goodsTpl">
    <a lay-event="update" data-title="商品详情" data-url="<?=Url::to(['goods/view'])?>?goods_no={{ d.goods_no }}" style="color: #00a0e9">{{d.goods_no}}</a>
    {{# if(d.url != '') {}}
    <a style="color: #00a0e9;padding-left: 5px" href="{{ d.url || '' }}" target="_blank"><i class="layui-icon layui-icon-website"></i></a>
    {{# } }}
</script>

<script>
    const tableName="goods";
    const categoryArr ='<?php echo $tag == 3 ? '' :addslashes(json_encode($category_arr))?>';
    const shopArr ='<?=json_encode($shop_arr)?>';
    const source_method='<?=$source_method?>';
    const property_data = '';
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?".time())?>
<?=$this->registerJsFile("@adminPageJs/goods/lists.js?".time()); ?>
<?php
$this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/xlsx.core.min.js?v=1',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export-excel.js?v=1',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export-function.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
?>
