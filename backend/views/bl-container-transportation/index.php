
<?php

use common\models\warehousing\BlContainerTransportation;
use common\services\sys\CountryService;
use common\services\warehousing\WarehouseService;
use yii\helpers\Url;
?>
<style>
    .layui-table-body .layui-table-cell{
        height:auto;
    }
</style>
<div class="layui-fluid">
    <div class="layui-card">
        <div class="lay-lists">
            <form class="layui-form">
                <blockquote class="layui-elem-quote quoteBox">
                    <div class="layui-inline">
                        <a class="layui-btn" data-type="url" data-title="添加" data-url="<?=Url::to(['bl-container-transportation/create'])?>" data-callback_title = "提单箱发货列表" >添加</a>
                    </div>
                </blockquote>
            </form>

            <div class="lay-search" style="padding-left: 10px">

                <div class="layui-inline">
                    物流编号：
                    <textarea class="layui-textarea search-con" name="BlContainerTransportationSearch[track_no]" autocomplete="off" style="height: 39px; min-height:39px"></textarea>
                </div>

                <div class="layui-inline">
                    国家：
                    <?= \yii\helpers\Html::dropDownList('BlContainerTransportationSearch[country]', null, CountryService::getSelectOption(),
                        ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:200px']); ?>
                </div>

                <div class="layui-inline">
                    仓库：
                    <?= \yii\helpers\Html::dropDownList('BlContainerTransportationSearch[warehouse_id]', null, WarehouseService::getWarehouseMap(),
                        ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:170px']); ?>
                </div>

                <div class="layui-inline">
                    状态：
                    <?= \yii\helpers\Html::dropDownList('BlContainerTransportationSearch[status]', null, BlContainerTransportation::$status_maps,
                        ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:170px']); ?>
                </div>

                <button class="layui-btn" data-type="search_lists" style="margin-top: 19px">搜索</button>
            </div>
            <div class="layui-card-body">
                <table id="bl-container-transportation" class="layui-table" lay-data="{url:'<?=Url::to(['bl-container-transportation/list'])?>', height : 'full-20', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20}" lay-filter="bl-container-transportation">
                    <thead>
                    <tr>
                        <th lay-data="{align:'left', templet:'#warehouseBar',width:165}">仓库</th>
                        <th lay-data="{align:'left', templet:'#trackBar',width:195}">物流编号</th>
                        <th lay-data="{align:'left', templet:'#weightBar',width:165}">提单箱信息</th>
                        <th lay-data="{align:'left', templet:'#priceBar',width:135}">价格</th>
                        <th lay-data="{align:'left', templet:'#goodsBar',width:125}">商品数</th>
                        <th lay-data="{field: 'status_desc', align:'left',width:100}">状态</th>
                        <th lay-data="{align:'left', templet:'#timeBar',width:220}">时间</th>
                        <th lay-data="{minWidth:175, templet:'#listBar',align:'center'}">操作</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<!--操作-->
<script type="text/html" id="listBar">
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['bl-container-transportation/update'])?>?id={{ d.id }}" data-title="编辑" data-callback_title="提单箱发货列表">编辑</a>
    <a class="layui-btn layui-btn-xs" data-title="同步估算重量" lay-event="operating" data-url="<?=Url::to(['bl-container/reset-estimate-weight'])?>?id={{ d.id }}">同步重量</a>
    <a class="layui-btn layui-btn-warm layui-btn-xs" lay-event="operating"  data-url="<?=Url::to(['bl-container/update-logistics-cost?id={{ d.id }}'])?>" data-title="同步运费" data-callback_title="bl-container列表">同步运费</a><br/>
    {{# if(d.status != 20){ }}
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="open" data-width="1000px" data-height="600px"  data-url="<?=Url::to(['bl-container-transportation/arrival'])?>?id={{ d.id }}" data-title="到货" data-callback_title="bl-container列表">到货</a>
    {{# }}}
    <a class="layui-btn layui-btn-xs" lay-event="update" data-url="<?=Url::to(['bl-container-transportation/view'])?>?id={{ d.id }}">查看详情</a>
    {{# if(d.exists == false){ }}
    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete" data-url="<?=Url::to(['bl-container-transportation/delete'])?>?id={{ d.id }}">删除</a><br/>
    {{# }}}
</script>

<script type="text/html" id="warehouseBar">
    {{ d.country }}<br/>
    {{ d.warehouse_name }}
</script>

<script type="text/html" id="trackBar">
    {{ d.track_no }}<br/>
    运输方式：{{ d.transport_type }}
</script>

<script type="text/html" id="weightBar">
    重量：{{ d.weight }}<br/>
    估算重量：{{ d.estimate_weight }}<br/>
    材积：{{ d.cjz }}
</script>

<script type="text/html" id="priceBar">
    单价：{{ d.unit_price }}<br/>
    价格：{{ d.price }}
</script>

<script type="text/html" id="timeBar">
    发货：{{ d.delivery_time }}<br/>
    预计到达：{{ d.arrival_time }}
</script>

<script type="text/html" id="goodsBar">
    商品总数：{{ d.goods_count }}<br/>
    箱子数量：{{ d.bl_container_count }}
</script>

<script>
    const tableName="bl-container-transportation";
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js")?>