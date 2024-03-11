
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
    <form class="layui-form">
        <blockquote class="layui-elem-quote quoteBox">
            <div class="layui-inline">
                <a class="layui-btn" data-type="open" data-width="880px" data-height="600px" data-title="添加报价" data-url="<?=Url::to(['shipping-method-offer/create?shipping_method_id='.$shipping_method_id])?>" data-callback_title = "物流报价列表" >添加报价</a>
            </div>
        </blockquote>
    </form>
    <form>
        <div class="layui-form lay-search" style="padding: 10px">
            <div class="layui-inline" style="width: 200px">
                <label>国家</label>
                <?= Html::dropDownList('ShippingMethodOfferSearch[country_code]', null, \common\services\sys\CountryService::getSelectOption(),['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>"layui-input search-con ys-select2"]) ?>
            </div>
            <div class="layui-inline layui-vertical-20">
                <button class="layui-btn" data-type="search_lists">搜索</button>
            </div>
        </div>
    </form>
    <div class="layui-card-body">
    <table id="shipping-method-offer" class="layui-table" lay-data="{url:'<?=Url::to(['shipping-method-offer/list?shipping_method_id='.$shipping_method_id])?>', height : 'full-100', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20}" lay-filter="shipping-method-offer">
    <thead>
    <tr>
        <th lay-data="{field: 'country_code', width:100, align:'center', width:120}">国家</th>
        <th lay-data="{field: 'weight_price', align:'left', width:150}">运费（元/kg）</th>
        <th lay-data="{field: 'deal_price', align:'left', width:120}">处理费</th>
        <th lay-data="{field: 'weight_desc', align:'left', width:200}">重量(kg）</th>
        <th lay-data="{field: 'formula', align:'left', width:200}">体积限制</th>
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
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="open" data-width="880px" data-height="600px" data-url="<?=Url::to(['shipping-method-offer/update'])?>?id={{ d.id }}" data-title="编辑" data-callback_title="物流配置">编辑</a>
</script>

<script>
    const tableName="shipping-method-offer";
</script>
<?php
    $this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.4.1");
?>