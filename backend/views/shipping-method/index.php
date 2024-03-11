
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
<div class="lay-lists">
    <form>
        <div class="layui-form lay-search" style="padding: 10px">
            <div class="layui-inline">
                运输服务名（代码）
                <input class="layui-input search-con" name="ShippingMethodSearch[shipping_method_code]" autocomplete="off">
            </div>
            <div class="layui-inline">
                物流商运输服务名
                <input class="layui-input search-con" name="ShippingMethodSearch[shipping_method_name]" autocomplete="off">
            </div>
            <div class="layui-inline" style="width: 120px">
                状态
                <?= Html::dropDownList('ShippingMethodSearch[status]', null,\common\models\sys\ShippingMethod::$status_map,
                    ['prompt' => '全部','lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
            </div>
            <div class="layui-inline layui-vertical-20">
                <button class="layui-btn" data-type="search_lists">搜索</button>
            </div>
        </div>
    </form>
    <div class="layui-card-body">
    <table id="shipping-method" class="layui-table" lay-data="{url:'<?=Url::to(['shipping-method/list?transport_code='.$transport_code])?>', height : 'full-100', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20}" lay-filter="shipping-method">
    <thead>
    <tr>
        <th lay-data="{field: 'id', width:80, align:'left'}">id</th>
        <th lay-data="{field: 'shipping_method_code', width:100, align:'center', width:200}">运输服务名（代码）</th>
        <th lay-data="{field: 'shipping_method_name', align:'left', width:400}">物流商运输服务名</th>
        <th lay-data="{field: 'warehouse_name', align:'left', width:180}">仓库</th>
        <th lay-data="{field: 'electric_status_desc', align:'center', width:100}">货品种类</th>
        <th lay-data="{field: 'status_desc', align:'center', width:100}">状态</th>
        <th lay-data="{minWidth:220, templet:'#listBar',fixed:'right',align:'center'}">操作</th>
    </tr>
    </thead>
</table>
</div>
</div>
    </div>
</div>

<!--操作-->
<script type="text/html" id="listBar">
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="open" data-width="850px" data-height="500px" data-url="<?=Url::to(['shipping-method/update'])?>?id={{ d.id }}" data-title="编辑" data-callback_title="物流配置">编辑</a>

    <a class="layui-btn layui-btn-xs" lay-event="parent_url" data-url="<?=Url::to(['shipping-method-offer/index'])?>?shipping_method_id={{ d.id }}" data-title="报价" data-callback_title="物流报价配置">报价</a>
</script>

<script>
    const tableName="shipping-method";
</script>
<?php
    $this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.4.6");
?>

