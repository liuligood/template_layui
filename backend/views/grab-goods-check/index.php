
<?php
use yii\helpers\Url;
use yii\helpers\Html;
?>
<style>
    .layui-laypage li{
        float: left;
    }
    .layui-laypage .active a{
        background-color: #009688;
        color: #fff;
    }
    .layui-vertical-20{
        padding-top: 20px;
    }
    .layui-tab-title .layui-this {
        background-color: #fff;
        color: #000;
    }
</style>
<div class="layui-fluid">
    <div class="layui-card">
<div class="lay-lists">
    <form>
    <div class="layui-form lay-search" style="padding-left: 10px;padding-top: 15px;">
        <div class="layui-inline">
            <label>平台</label>
            <?= Html::dropDownList('GrabGoodsCheckSearch[source]', null, \yii\helpers\ArrayHelper::map(\common\services\FGrabService::$source_map,'id','name'),
                ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px']) ?>
        </div>

        <div class="layui-inline">
            <label>ASIN</label>
            <input class="layui-input search-con" name="GrabGoodsCheckSearch[asin]" value=""  autocomplete="off">
        </div>
        <div class="layui-inline">
            <label>旧商品状态</label>
            <?= Html::dropDownList('GrabGoodsCheckSearch[old_goods_status]', null, \common\models\grab\GrabGoods::$goods_status_map,
                ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search' ]) ?>
        </div>
        <div class="layui-inline">
            <label>商品状态</label>
            <?= Html::dropDownList('GrabGoodsCheckSearch[goods_status]', null, \common\models\grab\GrabGoods::$goods_status_map,
                ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search' ]) ?>
        </div>

        <div class="layui-inline">
            时间
            <input class="layui-input search-con ys-date" name="GrabGoodsCheckSearch[start_add_time]" id="start_add_time" >
        </div>
        <span class="layui-inline layui-vertical-20">
            -
        </span>
        <div class="layui-inline layui-vertical-20">
            <input class="layui-input search-con ys-date" name="GrabGoodsCheckSearch[end_add_time]" id="end_add_time" >
        </div>
        <div class="layui-inline layui-vertical-20">
        <button class="layui-btn" data-type="search_lists">搜索</button>

        <button class="layui-btn layui-btn-normal" data-type="export_lists" data-url="<?=Url::to(['grab-goods-check/export'])?>">导出</button>
        </div>
    </div>
    </form>
    <div class="layui-card-body">
    <table id="grab-goods-check" class="layui-table" lay-data="{url:'<?=Url::to(['grab-goods-check/list'])?>', height : 'full-20', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20}" lay-filter="grab-goods-check">
    <thead>
    <tr>
        <th lay-data="{field: 'id', align:'center',width:90}">ID</th>
        <th lay-data="{field: 'source_desc', align:'center', width:120}">来源平台</th>
        <th lay-data="{field: 'asin', align:'center', width:130}">ASIN</th>
        <th lay-data="{field: 'old_goods_status_desc',  align:'left',minWidth:120}">旧商品状态</th>
        <th lay-data="{field: 'goods_status_desc',  align:'left',width:120}">商品状态</th>
        <th lay-data="{field: 'add_time',  align:'center',width:150}">时间</th>
    </tr>
    </thead>
</table>
</div>
</div>
    </div>
</div>
<script>
    const tableName="grab-goods-check";
</script>
<?php
    $this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.4.1");
    $this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
    $this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
    $this->registerJsFile('@adminPlugins/export/xlsx.core.min.js?v=1',['depends'=>'yii\web\JqueryAsset']);
    $this->registerJsFile('@adminPlugins/export/export-excel.js?v=1',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export-function.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
?>

