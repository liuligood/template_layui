
<?php
use yii\helpers\Url;
use common\models\RealOrder;
use yii\helpers\Html;
?>

<style>
    .layui-vertical-20{
        padding-top: 20px;
    }
</style>
<div class="layui-fluid">
    <div class="layui-card">
<div class="lay-lists">
<form class="layui-form">
    <blockquote class="layui-elem-quote quoteBox">
        <div class="layui-inline">
            <a class="layui-btn" data-type="url" data-title="添加" data-url="<?=Url::to(['real-order/create'])?>" data-callback_title = "Real订单列表" >添加</a>
        </div>
    </blockquote>
</form>
    <div class="layui-card-body">
<form class="layui-form">
<div class="layui-form lay-search" style="padding: 10px">

    <div class="layui-inline">
        订单号
        <input class="layui-input search-con" name="RealOrderSearch[order_id]" autocomplete="off">
    </div>

    <div class="layui-inline">
        店铺
        <?= Html::dropDownList('RealOrderSearch[shop_id]', null, $shop,
            ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px' ]) ?>
    </div>

    <div class="layui-inline">
        Real发货状态
        <?= Html::dropDownList('RealOrderSearch[real_delivery_status]', null, RealOrder::$real_delivery_status_map,
            ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px' ]) ?>
    </div>

    <div class="layui-inline">
        Real订单状态
        <?= Html::dropDownList('RealOrderSearch[real_order_status]', null, RealOrder::$real_order_status_map,
            ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px' ]) ?>
    </div>

    <div class="layui-inline">
        亚马逊状态
        <?= Html::dropDownList('RealOrderSearch[amazon_status]', null, RealOrder::$amazon_status_map,
            ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px' ]) ?>
    </div>

    <div class="layui-inline">
        刷单买家号机器编号
        <input class="layui-input search-con" name="RealOrderSearch[swipe_buyer_id]" autocomplete="off">
    </div>

<br/>

    <div class="layui-inline">
        下单日期
        <input class="layui-input search-con ys-date" name="RealOrderSearch[start_date]" id="start_date" >
    </div>
    <span class="layui-inline layui-vertical-20">
        -
    </span>
    <div class="layui-inline layui-vertical-20">
        <input class="layui-input search-con ys-date" name="RealOrderSearch[end_date]" id="end_date" >
    </div>

    <div class="layui-inline">
        亚马逊预计到货时间
        <input class="layui-input search-con ys-date" name="RealOrderSearch[start_amazon_arrival_time]" id="start_amazon_arrival_time" >
    </div>
    <span class="layui-inline layui-vertical-20">
        -
    </span>
    <div class="layui-inline layui-vertical-20">
        <input class="layui-input search-con ys-date" name="RealOrderSearch[end_amazon_arrival_time]" id="end_amazon_arrival_time" >
    </div>

    <div class="layui-inline layui-vertical-20">

    <button class="layui-btn" data-type="search_lists">搜索</button>

    <button class="layui-btn layui-btn-normal" data-type="export_lists" data-url="<?=Url::to(['real-order/export'])?>">导出</button>
    </div>
</div>
</form>
    <table id="real-order" class="layui-table" lay-data="{url:'<?=Url::to(['real-order/list'])?>', height : 'full-20', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20}" lay-filter="real-order">
    <thead>
    <tr>
        <th lay-data="{field: 'id', align:'center',width:60}">ID</th>
        <th lay-data="{field: 'image', width:100, align:'center',templet:'#goodsImgTpl'}">产品图片</th>
        <th lay-data="{field: 'shop_name', align:'center',width:100}">店铺</th>
        <th lay-data="{field: 'date',  align:'center',width:120}">日期</th>
        <th lay-data="{field: 'order_id', align:'center',width:120}">订单号</th>
        <th lay-data="{field: 'asin', align:'center', width:120}">产品ASIN</th>
        <th lay-data="{field: 'count', align:'center', width:100}">购买数量</th>
        <th lay-data="{field: 'amazon_buy_url',  align:'left',minWidth:150}">亚马逊买货链接</th>
        <th lay-data="{field: 'specification', align:'center', width:100}">规格型号</th>
        <th lay-data="{field: 'amazon_price',  align:'center',width:100}">亚马逊售价</th>
        <th lay-data="{field: 'real_price',  align:'center',width:100}">Real售价</th>
        <th lay-data="{field: 'real_order_amount',  align:'center',width:120}">Real订单金额</th>
        <th lay-data="{field: 'profit',  align:'center',width:100}">利润(欧元)</th>
        <!--<th lay-data="{field: 'buyer_name',  align:'center',minWidth:100}">买家名称</th>
        <th lay-data="{field: 'buyer_phone',  align:'center',minWidth:100}">电话</th>
        <th lay-data="{field: 'address',  align:'left',minWidth:150}">地址</th>
        <th lay-data="{field: 'city',  align:'center',width:80}">城市</th>
        <th lay-data="{field: 'area',  align:'center',minWidth:80}">区</th>
        <th lay-data="{field: 'postcode',  align:'center',minWidth:80}">邮编</th>
        <th lay-data="{field: 'country',  align:'center',width:80}">国家</th>-->
        <th lay-data="{field: 'real_track_no',  align:'center',width:100}">Real跟踪号</th>
        <th lay-data="{field: 'real_delivery_status_desc',  align:'center',width:120}">Real发货状态</th>
        <th lay-data="{field: 'real_order_status_desc',  align:'center',width:120}">Real订单状态</th>
        <th lay-data="{field: 'amazon_order_id',  align:'center',minWidth:120}">亚马逊订单号</th>
        <th lay-data="{field: 'amazon_arrival_time',  align:'center',minWidth:160}">亚马逊预计到货时间</th>
        <th lay-data="{field: 'amazon_status_desc',  align:'center',width:120}">亚马逊状态</th>
        <th lay-data="{field: 'swipe_buyer_id',  align:'center',minWidth:160}">刷单买家号机器编号</th>
        <th lay-data="{field: 'logistics_id',  align:'center',minWidth:150}">亚马逊物流订单号</th>
        <th lay-data="{field: 'admin_name', width:100}">创建者</th>
        <th lay-data="{minWidth:200, templet:'#listBar',fixed:'right',align:'center'}">操作</th>
    </tr>
    </thead>
</table>
</div>
</div>
    </div>
</div>
<script type="text/html" id="goodsImgTpl">
    <a href="{{d.image}}" data-lightbox="pic">
        <img class="layui-circle pic" src={{d.image}} height="26"/>
    </a>
</script>

<!--操作-->
<script type="text/html" id="listBar">
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['real-order/update'])?>?id={{ d.id }}" data-title="编辑" data-callback_title="Real订单列表">编辑</a>

    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete" data-url="<?=Url::to(['real-order/delete'])?>?id={{ d.id }}">删除</a>

    <a class="layui-btn layui-btn-green layui-btn-xs" href="<?=Url::to(['real-order/invoice'])?>?id={{ d.id }}">下载发票</a>
</script>

<script type="text/html" id="statusTpl">
    <input type="checkbox" name="状态" value="{{d.id}}" data-url="<?=Url::to(['ealr-order/update-status'])?>?id={{ d.id }}" lay-skin="switch" lay-text="正常|禁用" lay-filter="statusSwitch" {{ d.status ==10 ? 'checked' : '' }}>
</script>

<script>
    const tableName="real-order";
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.3")?>
<?php
    $this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
    $this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
    $this->registerJsFile('@adminPlugins/export/xlsx.core.min.js?v=1',['depends'=>'yii\web\JqueryAsset']);
    $this->registerJsFile('@adminPlugins/export/export-excel.js?v=1',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export-function.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
?>

