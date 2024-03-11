
<?php

use common\components\statics\Base;
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
    .span-circular-ai {
        display: inline-block;
        min-width: 16px;
        height: 25px;
        border-radius: 80%;
        background-color: #00aa00;
        color: #fff;
        text-align: center;
        padding: 5px;
        line-height: 16px;
        cursor: pointer;
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

    .layui-table-body .layui-table-cell{
        height:auto;
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
<?php if($tag == 4){?>
<div class="layui-tab layui-tab-brief">
    <ul class="layui-tab-title">
        <?php
        $goods_tort_type_map = Goods::$grab_goods_tort_type_map;
        $goods_tort_type_map = ['-1'=>'全部']+$goods_tort_type_map;
        foreach ($goods_tort_type_map as $k=>$v) { ?>
            <li <?php if($k == $goods_tort_type){?>class="layui-this" <?php }?>><a href="<?=Url::to(['goods/match-list?goods_tort_type='.$k])?>"><?=$v?></a></li>
        <?php } ?>
        <li <?php if($goods_stamp_tag  == -2){?>class="layui-this" <?php }?>><a href="<?=Url::to(['goods/match-list?goods_stamp_tag=-2'])?>">禁用</a></li>
    </ul>
</div>
<?php } ?>
<?php if(in_array($tag ,[2,5,6]) && $source_method == 1){?>
    <div class="layui-tab layui-tab-brief">
        <ul class="layui-tab-title">
            <?php
            $goods_tort_type_map = Goods::$goods_tort_type_map;
            $goods_tort_type_map = ['-1'=>'全部']+$goods_tort_type_map;
            foreach ($goods_tort_type_map as $k=>$v) { ?>
            <li <?php if($k == $goods_tort_type){?>class="layui-this" <?php }?>><a href="<?=Url::to(['goods/'.($tag==2?'index':($tag==5?'fine-index':'fine-match-list')).'?goods_tort_type='.$k])?>"><?=$v?></a></li>
            <?php } ?>
        </ul>
    </div>
<?php } ?>
<div class="lay-lists">
<?php if($source_method == GoodsService::SOURCE_METHOD_OWN && in_array($tag,[1,2,5,7])){?>
<form class="layui-form">
    <blockquote class="layui-elem-quote quoteBox">
        <?php if(in_array($tag,[2,5,7])){
            $source_method_sub = $tag == 5?Goods::GOODS_SOURCE_METHOD_SUB_FINE:($tag == 7?Goods::GOODS_SOURCE_METHOD_SUB_DISTRIBUTION:0); ?>
            <div class="layui-inline">
                <a class="layui-btn" data-type="url" data-title="添加商品" data-url="<?=Url::to(['goods/create?source_method_sub='.$source_method_sub])?>" data-callback_title = "商品列表" >添加商品</a>
            </div>
        <?php }?>
        <?php if($tag == 2 && $goods_tort_type == 0){ ?>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-normal" target="_blank" data-ignore="ignore" href="<?=Url::to(['goods/examine?tag='.$tag.'&goods_stamp_tag='.(empty($goods_stamp_tag)?0:$goods_stamp_tag) .'&goods_tort_type='.$goods_tort_type .'&source_method='.$source_method])?>" data-title="审查商品" data-callback_title = "商品列表" >审查商品</a>
            </div>
        <?php }?>
        <?php if($tag == 1){?>
        <div class="layui-inline">
            <a class="layui-btn layui-btn-normal" data-type="open" data-title="采集商品" data-url="<?=Url::to(['goods/grab'])?>" data-width="600px" data-height="400px" >采集商品</a>
        </div>
        <?php }?>
    </blockquote>
</form>
<?php }?>
    <div class="layui-card-body">
    <form>
<div class="layui-form lay-search">
    <div class="layui-inline">
        商品编号
        <textarea name="GoodsSearch[goods_no]" class="layui-textarea search-con" style="height: 39px; min-height:39px"></textarea>
    </div>
    <div class="layui-inline">
        SKU
        <!--<input class="layui-input search-con" name="GoodsSearch[sku_no]" autocomplete="off">-->
        <textarea name="GoodsSearch[sku_no]" class="layui-textarea search-con" style="height: 39px; min-height:39px"></textarea>
    </div>
    <div class="layui-inline">
        标题
        <input class="layui-input search-con" name="GoodsSearch[goods_name]" autocomplete="off">
    </div>
    <?php if(in_array($tag ,[7])){

        ?>
        <div class="layui-inline">
            仓库
            <?= Html::dropDownList('GoodsSearch[distribution_warehouse_id]', null, \common\models\goods\GoodsDistributionWarehouse::$warehouse_map,['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>"layui-input search-con ys-select2",'style'=>'width:100px']) ?>
        </div>
    <?php } ?>
    <?php if(in_array($tag ,[3,4,6])){?>
    <div class="layui-inline">
        来源平台
        <?= Html::dropDownList('GoodsSearch[source_platform_type]', null, \common\services\goods\GoodsService::getGoodsSource($source_method),
            ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px' ]); ?>
    </div>
    <?php } ?>
    <?php if($tag == 4){?>
        <div class="layui-inline">
            来源平台品牌
            <input class="layui-input search-con" name="GoodsSearch[source_platform_title]" autocomplete="off">
        </div>
    <!--<div class="layui-inline">
        来源平台品牌
        <?php //echo Html::dropDownList('GoodsSearch[source_platform_title]', null, $source_platform_title_arr,['prompt' => '全部','class'=>' search-con' ]) ?>
    </div>
    <div class="layui-inline" style="width: 200px">
        来源平台类目
        <?php // echo Html::dropDownList('GoodsSearch[source_platform_category_id]',null, $source_platform_category_arr,['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>"layui-input search-con ys-select2"]) ?>
    </div>-->
    <?php } ?>
    <div class="layui-inline">
        平台类目
        <?php if($tag != 3){?>
        <div id="div_category_id" style="width: 180px;"></div>
        <input id="category_id" class="layui-input search-con" type="hidden" name="GoodsSearch[category_id]" autocomplete="off">
        <?php }else{?>
        <?= Html::dropDownList('GoodsSearch[category_id]', null, $category_arr,
            ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px' ]) ?>
        <?php }?>
    </div>
    <?php if($tag == 2 || $tag == 4 || $tag == 5 || $tag == 6 || $tag == 7){?>
    <div class="layui-inline" style="width: 200px;">
        店铺(已认领)
        <select name="GoodsSearch[claim_shop_name]" class="layui-input search-con ys-select2" data-placeholder="请选择" lay-ignore >
            <option value="" >请选择</option>
            <option value="find_0" >【 精选 】</option>
            <option value="find_<?=Base::PLATFORM_OZON?>" >【 Ozon精选 】</option>
            <?php foreach ($shop_arr as $ptype_v){ ?>
                <option value="P_<?=$ptype_v['id']?>">【 <?=$ptype_v['title']?> 】</option>
                <?php foreach ($ptype_v['child'] as $shop_v){ ?>
                    <option value="<?=$shop_v['id']?>"><?=$shop_v['title']?></option>
                <?php }?>
            <?php }?>
        </select>
    </div>
    <!--<div class="layui-inline">
        店铺(未认领)
        <input class="layui-input search-con" name="GoodsSearch[un_claim_shop_name]" autocomplete="off" id="un_claim_shop_id">
    </div>-->
    <div class="layui-inline" style="width: 220px">
        <label>排除已认领店铺</label>
        <?php //echo Html::dropDownList('GoodsSearch[exclude_claim_shop_name]', null, [],['lay-ignore'=>'lay-ignore','data-placeholder' => '全部',"multiple"=>"multiple",'class'=>"layui-input search-con ys-select2"]) ?>
        <select name="GoodsSearch[un_claim_shop_name][]" class="layui-input search-con ys-select2" multiple="multiple" data-placeholder="全部" lay-ignore >
            <!--<?php foreach ($shop_arr as $ptype_v){ ?>
            <optgroup label="<?=$ptype_v['title']?>">
                <?php foreach ($ptype_v['child'] as $shop_v){ ?>
                <option value="<?=$shop_v['id']?>"><?=$shop_v['title']?></option>
                <?php }?>
            </optgroup>
            <?php }?>-->
            <option value="find_0" >【 精选 】</option>
            <option value="find_<?=Base::PLATFORM_OZON?>" >【 Ozon精选 】</option>
            <?php foreach ($shop_arr as $ptype_v){ ?>
                <option value="P_<?=$ptype_v['id']?>">【 <?=$ptype_v['title']?> 】</option>
                <?php foreach ($ptype_v['child'] as $shop_v){ ?>
                    <option value="<?=$shop_v['id']?>"><?=$shop_v['title']?></option>
                <?php }?>
            <?php }?>
        </select>
    </div>
    <?php }?>
    <!--<div class="layui-inline">
        颜色
        <input class="layui-input search-con" name="GoodsSearch[colour]" autocomplete="off">
    </div>-->
    <?php if($source_method == GoodsService::SOURCE_METHOD_OWN && $tag == 2){?>
    <div class="layui-inline">
        属性
        <?= Html::dropDownList('GoodsSearch[goods_stamp_tag]', null, Goods::$goods_stamp_tag_map,
            ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px' ]) ?>
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
        商品类型
        <?= Html::dropDownList('GoodsSearch[goods_type]', null, \common\models\Goods::$goods_type_map,
            ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px' ]) ?>
    </div>
    <?php if($source_method == GoodsService::SOURCE_METHOD_AMAZON || $tag == 2){?>
        <div class="layui-inline">
            缺货
            <?= Html::dropDownList('GoodsSearch[stock]', null, \common\models\Goods::$stock_map,
                ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px' ]) ?>
        </div>
    <?php }?>
    <?php if($all_goods_access) {?>
        <div class="layui-inline">
            归属者
            <?= Html::dropDownList('GoodsSearch[owner_id]', null, $owner_admin_arr,
                ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
        </div>
        <div class="layui-inline" style="width: 220px">
            <label>排除归属者</label>
            <?php //echo Html::dropDownList('GoodsSearch[exclude_claim_shop_name]', null, [],['lay-ignore'=>'lay-ignore','data-placeholder' => '全部',"multiple"=>"multiple",'class'=>"layui-input search-con ys-select2"]) ?>
            <select name="GoodsSearch[un_owner_id][]" class="layui-input search-con ys-select2" multiple="multiple" data-placeholder="全部" lay-ignore >
                <?php foreach ($owner_admin_arr as $arr_k => $arr_v){ ?>
                     <option value="<?=$arr_k?>"><?=$arr_v?></option>
                <?php }?>
            </select>
        </div>
        <div class="layui-inline">
            操作者
            <?= Html::dropDownList('GoodsSearch[admin_id]', null, $admin_arr,
                ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
        </div>
    <?php }?>
    <?php if($tag == 3){?>
        <div class="layui-inline">
            状态
            <?= Html::dropDownList('GoodsSearch[status]', null, [
                Goods::GOODS_STATUS_UNALLOCATED => '待分配',
                Goods::GOODS_STATUS_WAIT_ADDED => '待完善',
            ], ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px' ]) ?>
        </div>
    <?php }?>
    <?php if(in_array($tag ,[2,5])){?>
        <div class="layui-inline">
            状态
            <?= Html::dropDownList('GoodsSearch[status]', null, [
                Goods::GOODS_STATUS_VALID => '正常',
                Goods::GOODS_STATUS_INVALID => '禁用',
            ], ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px' ]) ?>
        </div>
    <?php }?>
    <?php if(in_array($tag ,[2,5,6]) && $goods_tort_type == -1){?>
    <div class="layui-inline" style="width: 220px">
        <label>商品归类</label>
        <select name="GoodsSearch[goods_tort_type_sel][]" class="layui-input search-con ys-select2" multiple="multiple" data-placeholder="全部" lay-ignore >
            <?php foreach (Goods::$goods_tort_type_map as $arr_k => $arr_v){ ?>
                <option value="<?=$arr_k?>"><?=$arr_v?></option>
            <?php }?>
        </select>
    </div>
    <?php }?>
    <div class="layui-inline layui-vertical-20">
    <button class="layui-btn" data-type="search_lists">搜索</button>
        <?php if($tag == 4){?>
        <button class="layui-btn layui-btn-normal" data-type="export_lists" data-url="<?=Url::to(['goods/export-wait-match-category'])?>">导出未匹配类目</button>
        <?php } ?>
        <button class="layui-btn layui-btn-normal" data-type="export_load_lists" data-url="<?=Url::to(['goods/export-title?tag='.$tag.'&goods_stamp_tag='.(empty($goods_stamp_tag)?0:$goods_stamp_tag) .'&goods_tort_type='.$goods_tort_type .'&source_method='.$source_method])?>">导出标题关键词</button>

        <button class="layui-btn layui-btn-primary ys-upload" lay-data="{url: '/goods/import-keywords/',accept: 'file'}">导入关键词</button>

        <button class="layui-btn  ys-upload" lay-data="{url: '/goods/import-prices/',accept: 'file'}">导入价格</button>
    </div>
</div>
    </form>


<div class="layui-form" style="padding: 10px 0">
    <?php if(in_array($tag,[2,4,5,6,7])){?>
        <?php if($tag !=2 || $goods_tort_type == 1 || \common\services\sys\AccessService::hasAllGoodsClaim()){ ?>
        <div class="layui-inline">
            <a class="layui-btn layui-btn-sm layui-btn-normal valid_btn" data-url="<?=Url::to(['goods/batch-claim?source_method='.$source_method])?>" >选中批量认领</a>
        </div>
        <div class="layui-inline">
            <a class="layui-btn layui-btn-sm layui-btn-normal valid_all_btn" data-url="<?=Url::to(['goods/batch-claim?tag='.$tag.'&goods_stamp_tag='.(empty($goods_stamp_tag)?0:$goods_stamp_tag) .'&goods_tort_type='.$goods_tort_type.'&source_method='.$source_method])?>" >全部批量认领</a>
        </div>
        <?php }?>
        <div class="layui-inline">
            <a class="layui-btn layui-btn-sm layui-btn-danger batch_invalid_btn"  data-url="<?=Url::to(['goods/disable?source_method='.$source_method])?>" >批量禁用</a>
        </div>
        <!--<div class="layui-inline">
            <a class="layui-btn layui-btn-sm layui-btn-normal batch_colour_btn" data-url="<?=Url::to(['goods/batch-update-colour?source_method='.$source_method])?>" >批量设置颜色</a>
        </div>-->

    <?php }?>
    <?php if(in_array($tag,[2,3,4,5,6,7])){?>
        <div class="layui-inline">
            <a class="layui-btn layui-btn-sm layui-btn-normal batch_category_btn" data-url="<?=Url::to(['goods/batch-update-category?source_method='.$source_method])?>" >批量设置类目</a>
        </div>
        <div class="layui-inline">
            <a class="layui-btn layui-btn-sm batch_find_btn" data-title="移入精选" data-url="<?=Url::to(['goods/batch-add-find?source_method='.$source_method])?>" >批量精选</a>
        </div>
    <?php }?>
    <?php if($tag == 3){?>
        <div class="layui-inline">
            <a class="layui-btn layui-btn-sm layui-btn-normal allo_btn" data-url="<?=Url::to(['goods/batch-allo?source_method='.$source_method])?>" >批量分配</a>
        </div>
    <?php }?>
    <?php if(in_array($tag,[1,3,4,6])){?>
        <div class="layui-inline">
            <a class="layui-btn layui-btn-sm layui-btn-danger batch_del_btn" data-url="<?=Url::to(['goods/batch-del?source_method='.$source_method])?>" >批量删除</a>
        </div>
    <?php }?>

    <?php if(in_array($tag,[4])){?>
        <div class="layui-inline">
            <a class="layui-btn layui-btn-sm layui-btn-danger batch_error_category" data-url="<?=Url::to(['goods/batch-error-category?source_method='.$source_method])?>" >类目错误</a>
        </div>
    <?php }?>

    <?php if($all_goods_access && in_array($tag ,[2,4,6,5])){?>
        <div class="layui-inline">
            <a class="layui-btn layui-btn-sm layui-btn-normal allo_btn" data-url="<?=Url::to(['goods/batch-allo?source_method='.$source_method.'&type=2'])?>" >选中批量分配</a>
        </div>
        <div class="layui-inline">
            <a class="layui-btn layui-btn-sm layui-btn-normal allo_all_btn" data-url="<?=Url::to(['goods/batch-allo?tag='.$tag.'&goods_stamp_tag='.(empty($goods_stamp_tag)?0:$goods_stamp_tag) .'&goods_tort_type='.$goods_tort_type.'&source_method='.$source_method.'&type=2'])?>">全部批量分配</a>
        </div>
    <?php }?>

    <?php if(in_array($tag,[2,5])){?>
        <?php if($all_goods_access || true){?>
        <div class="layui-inline">
            <a class="layui-btn layui-btn-sm layui-btn-normal batch_tort_type_btn" data-url="<?=Url::to(['goods/batch-update-tort-type?source_method='.$source_method])?>" >批量归类</a>
        </div>
        <?php }
    }?>
    <?php if(in_array($tag,[2])){?>
        <div class="layui-inline">
            <a class="layui-btn layui-btn-sm js-batch" data-title="移入海外仓" data-url="<?=Url::to(['goods/batch-add-overseas?source_method='.$source_method])?>" >批量移入海外仓</a>
        </div>

        <div class="layui-inline">
            <a class="layui-btn layui-btn-sm js-batch" data-title="复制到精品" data-url="<?=Url::to(['goods/copy-fine?source_method='.$source_method])?>" >复制到精品</a>
        </div>
    <?php }?>
    <?php if ($tag == 7){ ?>
        <div class="layui-inline">
            <a class="layui-btn layui-btn-sm layui-btn-warm close_btn"  data-title="暂停销售" data-url="<?=Url::to(['goods/batch-close-view?source_method='.$source_method.'&operate=batch'])?>" >批量暂停销售</a>
        </div>
    <?php }?>
</div>
<table id="goods" class="layui-table" lay-data="{url:'<?=Url::to(['goods/list?tag='.$tag.'&source_method='.$source_method.'&goods_stamp_tag='.$goods_stamp_tag.'&goods_tort_type='.$goods_tort_type])?>', height : 'full-210',method :'post', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20}" lay-filter="goods">
    <thead>
    <tr>
        <th lay-data="{type: 'checkbox', width:50}">ID</th>
        <th lay-data="{ width:130, align:'center',templet:'#goodsImgTpl'}">商品主图</th>
        <th lay-data="{ width:190, align:'center',templet:'#goodsTplNo'}">商品编号</th>
        <th lay-data="{ width:350, align:'center',templet:'#goodsTplTitle'}">商品标题</th>
        <?php if(in_array($tag,[3,4,6])){?>
        <th lay-data="{ width:180, align:'left',templet:'#goodsGrabTpl'}">来源平台信息</th>
        <?php }?>
        <th lay-data="{ width:130,templet:'#goodsTpl'}">商品信息</th>
        <th lay-data="{ width:175, align:'left',templet:'#userTpl'}">时间</th>
        <th lay-data="{minWidth:155, templet:'#goodsListBar',align:'center'}">操作</th>
    </tr>
    </thead>
</table>
</div>
</div>
    </div>
</div>

<!--操作-->
<script type="text/html" id="goodsListBar">
    {{# if(d['status'] == 10 || d['status'] == 8){ }}
    <div class="layui-inline">
    <a class="layui-btn layui-btn-xs" lay-event="open" data-url="<?=Url::to(['goods/claim'])?>?goods_no={{ d.goods_no }}" data-width="600px" data-height="500px" data-title="认领" data-callback_title="商品列表">认领</a>
    </div>
    {{# } }}
    <?php if(in_array($tag ,[2,5,7])){?>
    <div class="layui-inline">
        <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['goods/update'])?>?id={{ d.id }}" data-title="编辑商品" data-callback_title="商品列表">编辑</a>
        <!--<a class="layui-btn layui-btn-normal layui-btn-xs" target="_blank" href="<?=Url::to(['goods/update'])?>?id={{ d.id }}" data-title="编辑商品" data-callback_title="商品列表">编辑</a>-->
    </div>
    <?php }?>
    <?php if($tag == 1 || $tag == 4 || $tag == 6){?>
    <div class="layui-inline">
        <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['goods/update'])?>?id={{ d.id }}" data-title="编辑商品" data-callback_title="商品列表">提交到商品库</a>
    </div>
    <?php }?><br/>
    <?php if(!in_array($tag ,[2,5]) || \common\services\sys\AccessService::hasAllGoods()){?>
    <div class="layui-inline">
    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete" data-url="<?=Url::to(['goods/delete'])?>?id={{ d.id }}">删除</a>
    </div>
    <?php }?>
    <div class="layui-inline">
        <a class="layui-btn layui-btn-xs" lay-event="operating" data-url="<?=Url::to(['goods/copy'])?>?goods_no={{ d.goods_no }}" data-title="复制">复制商品</a>
    </div>
</script>

<script type="text/html" id="goodsImgTpl">
    <a href="{{d.image}}" data-lightbox="pic">
        <img class="layui-circle" src="{{d.image}}?imageView2/2/h/100" width="100"/>
    </a>
</script>
<script type="text/html" id="goodsTplNo">
    {{# if(d.goods_status == 20){ }}<span style="color: #FFFFFF;background: red;padding: 2px 4px;" class="layui-font-12">禁</span>{{# } }}
    <b>{{d.sku_no}}</b><br/>
    <a lay-event="update" data-title="商品详情" data-url="<?=Url::to(['goods/view'])?>?goods_no={{ d.goods_no }}" style="color: #00a0e9">{{d.goods_no}}</a><br/>
    <?php if($tag != 1){?>
    类目：<b>{{d.category_name}}</b><br/>
    <?php }?>
</script>

<script type="text/html" id="goodsGrabTpl">
    <?php if(in_array($tag,[3,4,6])){?>
    平台:{{d.source_platform_type}}<br/>
    <?php }?>
    <?php if(in_array($tag,[4,6])){?>
        品牌:{{d.source_platform_title}}<br/>
        类目id:{{d.source_platform_category_id}}<br/>
        类目:{{d.source_platform_category_name}}<br/>
    <?php }?>
</script>

<script type="text/html" id="goodsTplTitle">
    <div class="span-goode-name">{{d.goods_name||''}}</div>
    <div class="span-goode-name">{{d.goods_name_cn || (d.goods_name_cn||'')}}</div>
    {{# if(d.count == 1){ }}<span style="position: absolute; bottom: 5px; right: 5px;" class="span-circular-ai">G</span>{{# } }}
</script>

<script type="text/html" id="goodsTpl">
    价格:{{d.price}}<br/>
    <?php if($source_method == GoodsService::SOURCE_METHOD_OWN){?>
    重量:{{d.weight}}<br/>
    颜色:{{d.colour}}<br/>
    <?php } else{?>
    品牌:{{d.price}}<br/>
    <?php }?>
    <?php if($source_method == GoodsService::SOURCE_METHOD_AMAZON){?>
        库存:{{d.stock_desc}}<br/>
    <?php } ?>
    <?php if(in_array($tag ,[2]) && $goods_tort_type == -1){?>
        商品归类:{{d.goods_tort_type_desc}}<br/>
    <?php } ?>
    状态:{{d.status_desc}}
</script>

<script type="text/html" id="userTpl">
    创建者:{{d.admin_name}}<br/>
    <?php if($all_goods_access){?>
    归属者:{{d.owner_name||'无'}}<br/>
    <?php }?>
    创建:{{d.add_time ||''}}<br/>
    <?php if(in_array($tag,[2])){?>
    更新:{{d.update_time ||''}}
    <?php }?>
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
$this->registerJsFile('@adminPlugins/export/export-excel.js?v=1.1',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export-function.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
?>