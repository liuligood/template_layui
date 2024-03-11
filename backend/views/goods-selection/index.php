
<?php

use common\models\Goods;
use common\models\OrderLogisticsPack;
use common\services\goods\GoodsService;
use yii\helpers\Url;
use yii\bootstrap\Html;
use common\models\Shop;
use common\components\statics\Base;


$owner_id = new Shop();
?>
<style>
    .layui-table-body .layui-table-cell{
        height:auto;
    }
</style>
<div class="layui-fluid">
    <form class="layui-card">
        <div class="lay-lists">
            <form class="layui-form">
                <blockquote class="layui-elem-quote quoteBox">
                    <div class="layui-inline">
                        <a class="layui-btn" data-type="url" data-title="添加预备商品" data-url="<?=Url::to(['goods-selection/create'])?>" data-callback_title = "goods-selection列表" >添加预备商品</a>
                    </div>
                </blockquote>
            </form>
            <form>
                <div class="layui-form lay-search" style="padding-left: 10px">

                    <div class="layui-inline layui-vertical-20" style="width: 150px">
                        商品编号:
                        <textarea name="GoodsSelectionSearch[goods_no]" class="layui-textarea search-con" style="height: 39px; min-height:39px"></textarea>
                    </div>

                    <div class="layui-inline layui-vertical-20" style="width: 150px">
                        来源平台：
                        <?= Html::dropDownList('GoodsSelectionSearch[platform_type]',null,Base::$goods_source,
                            ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search' ]) ?>
                    </div>

                    <div class="layui-inline layui-vertical-20" style="width: 150px">
                        商品类型：
                        <?= Html::dropDownList('GoodsSelectionSearch[goods_type]',null,\common\models\Goods::$goods_type_map,
                            ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search' ]) ?>
                    </div>

                    <div class="layui-inline layui-vertical-20" style="width: 150px">
                        归属者：
                        <?= Html::dropDownList('GoodsSelectionSearch[owner_id]',null,$owner_id->adminArr(),
                            ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
                    </div>

                    <div class="layui-inline layui-vertical-20" style="width: 150px">
                        操作者：
                        <?= Html::dropDownList('GoodsSelectionSearch[admin_id]',null,$owner_id->adminArr(),
                            ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
                    </div>

                    <div class="layui-inline layui-vertical-20" style="width: 150px">
                        状态：
                        <?= Html::dropDownList('GoodsSelectionSearch[status]',null,\common\models\GoodsSelection::$status_maps,
                            ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search' ]) ?>
                    </div>

                    <div class="layui-inline">
                        平台类目：
                        <div id="div_category_id" style="width: 180px;"></div>
                        <input id="category_id" class="layui-input search-con" type="hidden" name="GoodsSelectionSearch[category_id]" autocomplete="off">
                    </div>  

                    <div class="layui-inline">
                        添加时间：
                        <input  class="layui-input search-con ys-datetime" name="GoodsSelectionSearch[start_time]" id="start_time" autocomplete="off">
                    </div>
                    <span class="layui-inline layui-vertical-20">
                        <br>
                        -
                    </span>
                    <div class="layui-inline layui-vertical-20">
                        <br>
                        <input  class="layui-input search-con ys-datetime" name="GoodsSelectionSearch[end_time]" id="end_time" autocomplete="off">
                    </div>



                    <div class="layui-inline layui-vertical-20">
                        <br>
                        <button class="layui-btn" data-type="search_lists">搜索</button>
                    </div>

                    <div class="lay-lists" style="padding:5px;">
                        <div class="layui-inline">
                            <a class="layui-btn layui-btn-sm  layui-btn-normal allo_btn" data-url="<?=Url::to(['goods-selection/batch-allo'])?>">批量分配归属者</a>
                        </div>
                    </div>

                    <br>
                </div>
            </form>
            <div class="layui-card-body">
                <table id="goods-selection" class="layui-table" lay-data="{url:'<?=Url::to(['goods-selection/list'])?>', height : 'full-20', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000],limit:20}}" lay-filter="goods-selection">
                    <thead>
                    <tr>
                        <th lay-data="{type: 'checkbox', width:50,field: 'id'}"></th>
                        <th lay-data="{ width:130, align:'center',templet:'#goodsImgTpl'}">商品主图</th>
                        <th lay-data="{ width:225, align:'center',templet:'#goodsTplNo'}">商品编号</th>
                        <th lay-data="{ width:275, align:'center',templet:'#goodsGrabTpl'}">来源平台信息</th>
                        <th lay-data="{ width:165,templet:'#goodsTpl',align:'left'}">商品信息</th>
                        <th lay-data="{ width:215, align:'left',templet:'#userTpl'}">时间</th>
                        <th lay-data="{minWidth:155, templet:'#listBar',align:'center'}">操作</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<!--操作-->
<script type="text/html" id="listBar">
    {{#  if(d.status != '已生成'){ }}
    <a class="layui-btn  layui-btn-xs" lay-event="update" data-url="<?=Url::to(['goods/create?source_method_sub='.Goods::GOODS_SOURCE_METHOD_SUB_FINE])?>&selection_id={{ d.id }}" data-title="生成商品" data-callback_title="goods-selection列表">生成商品</a>
    {{# } }}
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['goods-selection/update'])?>?id={{ d.id }}" data-title="编辑预备商品" data-callback_title="goods-selection列表">编辑</a>
    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete" data-url="<?=Url::to(['goods-selection/delete'])?>?id={{ d.id }}">删除</a>
    <!--<a class="layui-btn layui-btn-xs layui-btn-xs" lay-event="update" data-url="<?=Url::to(['goods-selection/view'])?>?shop_id={{ d.id }}">查看</a>-->
</script>
<script type="text/html" id="goodsImgTpl">
    <a href="{{d.goods_img}}" data-lightbox="pic">
        <img class="layui-circle" src="{{d.goods_img}}?imageView2/2/h/100" width="100"/>
    </a>
</script>
<script type="text/html" id="goodsTplNo">
    类目：<b>{{d.category_id}}</b><br/>
    备注：{{d.remarks}}
</script>
<script type="text/html" id="goodsGrabTpl">
    来源平台：{{d.platform_type}}<br/>
    来源平台链接：{{d.platform_url}}
</script>
<script type="text/html" id="goodsTpl">
    商品类型：{{d.goods_type}}<br/>
    件数：{{d.quantity}}<br/>
    状态：{{d.status}}<br/>
    编号：<b>{{d.goods_no}}</b>
</script>
<script type="text/html" id="userTpl">
    操作者：{{d.admin_id}}<br/>
    归属者：{{d.owner_id}}<br/>
    创建：{{d.add_time}}
</script>
<script>
    const tableName="goods-selection";
    const categoryArr ='<?php addslashes(json_encode(1))?>';
</script>
<?php
$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.4.6");
$this->registerJsFile("@adminPageJs/goods-selection/lists.js?v=0.0.4.6");
$this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
?>


