
<?php
use yii\helpers\Url;
use yii\helpers\Html;
?>

<style>
    .layui-vertical-20{
        padding-top: 20px;
    }
</style>
<div class="layui-fluid">
    <div class="layui-card">
<form class="layui-form" id="logistics_sign" action="<?=Url::to(['logistics-sign-log/create'])?>">
    <blockquote class="layui-elem-quote quoteBox">
        物流单号
        <div class="layui-inline " style="width: 250px">
            <input class="layui-input search-con" name="logistics_no" id="logistics_no" value="" autocomplete="off"
                   style="height: 50px;line-height: 50px;font-size: 20px;font-weight: bold">
        </div>
        <div class="layui-inline">
            <button class="layui-btn layui-btn-normal" lay-submit="" lay-filter="form" data-form="logistics_sign">提交</button>
        </div>
    </blockquote>
</form>
<div class="lay-lists">

    <form>
    <div class="layui-form lay-search" style="padding-left: 10px">
        <div class="layui-inline">
            物流单号
            <input class="layui-input search-con" id="sel_logistics_no" name="LogisticsSignLogSearch[logistics_no]" value="" autocomplete="off">
        </div>

        <div class="layui-inline">
            状态
            <?= Html::dropDownList('LogisticsSignLogSearch[status]', null, \common\models\warehousing\LogisticsSignLog::$status_maps,
                ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px' ]) ?>
        </div>

        <div class="layui-inline">
            签收时间
            <input class="layui-input search-con ys-date" name="LogisticsSignLogSearch[start_add_time]" id="start_add_time" >
        </div>
        <span class="layui-inline layui-vertical-20">
        -
        </span>
        <div class="layui-inline layui-vertical-20">
            <input class="layui-input search-con ys-date" name="LogisticsSignLogSearch[end_add_time]" id="end_add_time" >
        </div>

        <div class="layui-inline">
            入库时间
            <input class="layui-input search-con ys-date" name="LogisticsSignLogSearch[start_storage_time]" id="start_storage_time" >
        </div>
        <span class="layui-inline layui-vertical-20">
        -
        </span>
        <div class="layui-inline layui-vertical-20">
            <input class="layui-input search-con ys-date" name="LogisticsSignLogSearch[end_storage_time]" id="end_storage_time" >
        </div>

        <div class="layui-inline layui-vertical-20">
            <button class="layui-btn" data-type="search_lists" id="sel_btn">搜索</button>
        </div>
    </div>
    </form>
    <div class="layui-card-body">
    <table id="logistics-sign-log" class="layui-table" lay-data="{url:'<?=Url::to(['logistics-sign-log/list'])?>', height : 'full-200', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20}" lay-filter="logistics-sign-log">
    <thead>
    <tr>
        <th lay-data="{field: 'id', align:'center',width:90}">id</th>
        <th lay-data="{field: 'logistics_no', align:'center', width:180}">物流单号</th>
        <th lay-data="{field: 'source_desc', align:'center',width:160}">来源</th>
        <th lay-data="{field: 'admin_name', align:'center', width:180}">签收人</th>
        <th lay-data="{field: 'add_time_desc',  align:'center',width:180}">签收时间</th>
        <th lay-data="{field: 'status_desc', align:'center', width:120}">状态</th>
        <th lay-data="{field: 'storage_time_desc', width:100, align:'center', width:180}">入库时间</th>
    </tr>
    </thead>
</table>
</div>
</div>
    </div>
</div>
<script>
    const tableName="logistics-sign-log";
</script>
<?php
    $this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.4.1");
    $this->registerJsFile("@adminPageJs/warehousing/logistics_sign.js?v=".time()); ?>

