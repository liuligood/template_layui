
<?php
use yii\helpers\Url;
use yii\helpers\Html;
?>
<style>
    .layui-table-cell {
        height:auto;

    }
    .layui-fluid {
        padding: 30px 25px;
    }

</style>
<div class="layui-fluid">
    <div class="layui-card">
        <div class="lay-lists">
            <div class="layui-card-body" >
                <form>
                    <div class="layui-form lay-search" style="padding-bottom: 10px">
                        <div class="layui-inline">
                            商品编号
                            <input class="layui-input search-con" name="OrderDescSearch[goods_no]" autocomplete="off">
                        </div>
                        <div class="layui-inline">
                            SKU
                            <input class="layui-input search-con" name="OrderDescSearch[sku_no]" autocomplete="off">
                        </div>
                        <div class="layui-inline">
                            订单号
                            <input class="layui-input search-con" name="OrderDescSearch[order_id]" autocomplete="off">
                        </div>
                        <div class="layui-inline">
                            物流单号
                            <input class="layui-input search-con" name="OrderDescSearch[track_no]" autocomplete="off">
                        </div>
                        <div class="layui-inline">
                            平台
                            <?= Html::dropDownList('OrderOverseasStockSearch[status]','', \common\models\OrderOverseasStock::$stutas_map,
                                ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px' ]) ?>
                        </div>
                        <div class="layui-inline layui-vertical-20"style="margin-top: 22px;">
                            <button class="layui-btn" data-type="search_lists">搜索</button>
                        </div>
                    </div>
                </form>
                <table id="order-overseas-stock" class="layui-table" lay-data="{url:'<?=Url::to(['order-overseas-stock/list'])?>', height : 'full-10', cellMinWidth : 95, page:{limits:[10]}}" lay-filter="order-overseas-stock">
                    <thead>
                    <tr style="height: 100px">
                        <th lay-data="{width:140, align:'center',templet:'#goodsImgTpl'}">商品主图</th>
                        <th lay-data="{width:300, align:'center',templet:'#goodsTpl'}">商品信息</th>
                        <th lay-data="{width:250, align:'center',templet:'#ordersTpl'}">订单信息</th>
                        <th lay-data="{width:220, align:'center',templet:'#dataTpl'}">日期</th>
                        <th lay-data="{width:80, align:'center',templet:'#Tpl'}">仓库</th>
                        <th lay-data="{field: 'desc', width:100}">备注</th>
                        <th lay-data="{minWidth:220,templet:'#listBar',align:'center'}">操作</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<!--操作-->
<script type="text/html" id="listBar">
    {{# if(d.status == "未处理"){ }}
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['order-overseas-stock/update'])?>?id={{ d.id }}" data-title="编辑" data-callback_title="order-desc列表">编辑</a>
    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="update" data-url="<?=Url::to(['order-overseas-stock/view'])?>?id={{ d.id }}">重发</a>
    {{# } }}
</script>
<script type="text/html" id="goodsImgTpl">
    {{# if(d.image){ }}
    <a href="{{d.image}}" data-lightbox="pic">
        <img class="layui-circle" src="{{d.image}}?imageView2/2/h/100" width="100"/>
    </a>
    {{# } }}
</script>
<script type="text/html" id="dataTpl">
    <div class="span-goode-name" style="white-space:pre-wrap;">退件日期：{{d.return_data}}</div>
    <div class="span-goode-name" style="white-space:pre-wrap;">重发日期：{{d.expire_time}}</div>
    <div class="span-goode-name">状态：{{d.status}}</div>
    <div class="span-goode-name">更新时间：{{d.update_time}}</div>
    <div class="span-goode-name">创建时间：{{d.add_time}}</div>
    <div class="span-goode-name">预计过期时间：{{d.expire_time}}</div>
</script>

<script type="text/html" id="goodsTpl">
    <a lay-event="update" data-url="<?=Url::to(['goods/view'])?>?goods_no={{ d.goods_no }}" style="color: #00a0e9">{{d.goods_no}}</a><br>
    <b class="order_id">SKU:{{d.sku_no}}</b><br/>
    <div class="span-goode-name" style="white-space:pre-wrap;">{{d.goods_name_cn || (d.goods_name||'')}}</div>
    <div class="span-goode-name">{{d.colour || ''}} {{d.size || ''}}</div>
    <div class="span-goode-name" style="white-space:pre-wrap;">数量：{{d.number}}</div>
</script>
<script type="text/html" id="ordersTpl">
    <b class="order_id">订单号：{{d.order_id}}</b><br/>
    <div class="span-goode-name" style="white-space:pre-wrap;">销售单号：{{d.relation_no}}</div>
    <div class="span-goode-name">物流单号：{{d.track_no}}</div>
    <div class="span-goode-name">操作人：{{d.user_id}}</div>
    <div class="span-goode-name">重发订单号：{{d.rewire_id}}</div>
</script>
<script type="text/html" id="Tpl">
    <div class="span-goode-name" style="white-space:pre-wrap;">来源平台：{{d.source_platform_type}}</div>
    <div class="span-goode-name" style="white-space:pre-wrap;">国家：{{d.country}}</div>
</script>

<script>
    const tableName="order-overseas-stock";
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js")?>
<?php
$this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/xlsx.core.min.js?v=1',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export-excel.js?v=1.1',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export-function.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
?>
