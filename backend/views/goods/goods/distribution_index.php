
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
        <div class="layui-tab layui-tab-brief">
            <ul class="layui-tab-title">
                <?php foreach ($warehouse_lists['data'] as $v){ ?>
                    <li <?php if($warehouse_lists['warehouse_id'] == $v['id']){?>class="layui-this" <?php }?>><a href="<?=Url::to(['goods/distribution-index?warehouse_id='.$v['id']])?>"><?=$v['warehouse_name']?></a></li>
                <?php }?>
            </ul>
        </div>
        <div class="lay-lists">
            <div class="layui-card-body">
                <form>
                    <div class="layui-form lay-search">
                        <div class="layui-inline">
                            商品编号
                            <textarea name="GoodsSearch[goods_no]" class="layui-textarea search-con" style="height: 39px; min-height:39px"></textarea>
                        </div>
                        <div class="layui-inline">
                            商品ID
                            <textarea name="GoodsSearch[source_platform_id]" class="layui-textarea search-con" style="height: 39px; min-height:39px"></textarea>
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
                            <div id="div_category_id" style="width: 180px;"></div>
                            <input id="category_id" class="layui-input search-con" type="hidden" name="GoodsSearch[category_id]" autocomplete="off">
                        </div>
                            <div class="layui-inline" style="width: 200px;">
                                店铺(已认领)
                                <select name="GoodsSearch[claim_shop_name]" class="layui-input search-con ys-select2" data-placeholder="请选择" lay-ignore >
                                    <option value="" >请选择</option>
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
                        <div class="layui-inline">
                            <div style="padding-left: 10px">
                                <input class="layui-input search-con" type="checkbox" value="1" name="GoodsSearch[has_warehouse_num]" lay-skin="primary" title="有库存">
                            </div>
                        </div>

                        <div class="layui-inline layui-vertical-20">
                            <button class="layui-btn" data-type="search_lists">搜索</button>
                        </div>
                    </div>
                </form>


                <div class="layui-form" style="padding: 10px 0">
                    <div class="layui-inline">
                        <a class="layui-btn layui-btn-sm layui-btn-normal valid_btn" data-url="<?=Url::to(['goods/batch-claim?source_method='.$source_method])?>" >选中批量认领</a>
                    </div>
                    <div class="layui-inline">
                        <a class="layui-btn layui-btn-sm layui-btn-normal valid_all_btn" data-url="<?=Url::to(['goods/batch-claim?tag='.$tag.'&goods_stamp_tag='.(empty($goods_stamp_tag)?0:$goods_stamp_tag) .'&goods_tort_type='.$goods_tort_type.'&source_method='.$source_method])?>" >全部批量认领</a>
                    </div>

                    <div class="layui-inline">
                        <a class="layui-btn layui-btn-sm layui-btn-danger batch_invalid_btn"  data-url="<?=Url::to(['goods/disable?source_method='.$source_method])?>" >批量禁用</a>
                    </div>

                    <div class="layui-inline">
                        <a class="layui-btn layui-btn-sm layui-btn-normal batch_category_btn" data-url="<?=Url::to(['goods/batch-update-category?source_method='.$source_method])?>" >批量设置类目</a>
                    </div>

                    <div class="layui-inline">
                        <a class="layui-btn layui-btn-sm layui-btn-warm close_btn"  data-title="暂停销售" data-url="<?=Url::to(['goods/batch-close-view?source_method='.$source_method.'&operate=batch'])?>" >批量暂停销售</a>
                    </div>
                </div>
                <table id="goods" class="layui-table" lay-data="{url:'<?=Url::to(['goods/distribution-list?tag='.$tag.'&source_method='.$source_method.'&warehouse='.$warehouse_lists['warehouse_id']])?>', height : 'full-210',method :'post', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20}" lay-filter="goods">
                    <thead>
                    <tr>
                        <th lay-data="{type: 'checkbox', width:50}">ID</th>
                        <th lay-data="{ width:130, align:'center',templet:'#goodsImgTpl'}">商品主图</th>
                        <th lay-data="{ width:190, align:'center',templet:'#goodsTplNo'}">商品编号</th>
                        <th lay-data="{ width:350, align:'center',templet:'#goodsTplTitle'}">商品标题</th>
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
    <div class="layui-inline">
        <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['goods/update'])?>?id={{ d.id }}" data-title="编辑商品" data-callback_title="商品列表">编辑</a>
        <!--<a class="layui-btn layui-btn-normal layui-btn-xs" target="_blank" href="<?=Url::to(['goods/update'])?>?id={{ d.id }}" data-title="编辑商品" data-callback_title="商品列表">编辑</a>-->
    </div><br/>
    <?php if(\common\services\sys\AccessService::hasAllGoods()){?>
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
    {{d.source_platform_id}}<br/>
    <a lay-event="update" data-title="商品详情" data-url="<?=Url::to(['goods/view'])?>?goods_no={{ d.goods_no }}&warehouse=<?=$warehouse_lists['warehouse_id']?>" style="color: #00a0e9">{{d.goods_no}}</a><br/>
    类目：<b>{{d.category_name}}</b><br/>
</script>

<script type="text/html" id="goodsTplTitle">
    <div class="span-goode-name">{{d.goods_name||''}}</div>
    <div class="span-goode-name">{{d.goods_name_cn || (d.goods_name_cn||'')}}</div>
    {{# if(d.count == 1){ }}<span style="position: absolute; bottom: 5px; right: 5px;" class="span-circular-ai">G</span>{{# } }}
</script>

<script type="text/html" id="goodsTpl">
    价格:{{d.price}}<br/>
    重量:{{d.weight}}<br/>
    颜色:{{d.colour}}<br/>
    状态:{{d.status_desc}}<br/>
    库存:{{d.warehouse_num}}
</script>

<script type="text/html" id="userTpl">
    创建:{{d.add_time ||''}}<br/>
</script>

<script>
    const tableName="goods";
    const categoryArr ='<?php addslashes(json_encode($category_arr))?>';
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