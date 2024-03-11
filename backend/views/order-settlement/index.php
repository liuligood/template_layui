
<?php

use common\components\statics\Base;
use common\services\ShopService;
use yii\helpers\Html;
use yii\helpers\Url;
?>
<style>
    .layui-table-body .layui-table-cell{
        height:auto;
    }
    #summary i{
        color: red;
    }
</style>
<div class="layui-fluid">
    <div class="layui-card">
        <div class="layui-card-body">
        <div class="lay-lists">
            <form>
                <div class="layui-form lay-search" style="padding-left: 10px">

                    <div class="layui-inline">
                        订单号：
                        <textarea name="OrderSettlementSearch[order_id]" class="layui-textarea search-con" style="height: 39px; min-height:39px"><?=$searchModel['order_id'];?></textarea>
                    </div>

                    <div class="layui-inline">
                        销售单号：
                        <textarea name="OrderSettlementSearch[relation_no]" class="layui-textarea search-con" style="height: 39px; min-height:39px"><?=$searchModel['relation_no'];?></textarea>
                    </div>

                    <div class="layui-inline" style="width: 120px">
                        平台：
                        <?= Html::dropDownList('OrderSettlementSearch[platform_type]', $searchModel['platform_type'], Base::$platform_maps,
                            ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','id'=>'platform']) ?>
                    </div>

                    <div class="layui-inline">
                        店铺：
                        <?= \backend\widgets\LinkageDropDownList::widget(['id' => 'shop','parent_id'=>'platform','name'=>'OrderSettlementSearch[shop_id]','select'=>$searchModel['shop_id'],'option'=> ShopService::getShopMapIndexPlatform(),'param'=>['data-placeholder' => '全部','prompt' => '全部','style'=>'width:185px']]) ?>
                    </div>

                    <div class="layui-inline">
                        退款：
                        <?= Html::dropDownList('OrderSettlementSearch[has_refund]', $searchModel['has_refund'], [
                            1 => '是',
                            2 => '否',
                        ], ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:155px' ]) ?>
                    </div>

                    <div class="layui-inline">
                        下单时间：
                        <input  class="layui-input search-con ys-datetime" name="OrderSettlementSearch[start_order_time]" value="<?=$searchModel['start_order_time'];?>" id="start_order_time" autocomplete="off">
                    </div>
                    <span class="layui-inline layui-vertical-20">
                        <br>
                        -
                    </span>
                    <div class="layui-inline layui-vertical-20">
                        <br>
                        <input  class="layui-input search-con ys-datetime" name="OrderSettlementSearch[end_order_time]" value="<?=$searchModel['end_order_time'];?>" id="end_order_time" autocomplete="off">
                    </div>

                    <div class="layui-inline">
                        发货时间
                        <input class="layui-input search-con ys-date" name="OrderSettlementSearch[start_delivery_time]" value="<?=$searchModel['start_delivery_time'];?>" id="start_delivery_time" autocomplete="off">
                    </div>
                    <span class="layui-inline layui-vertical-20">
                            -
                   </span>
                    <div class="layui-inline layui-vertical-20">
                        <br>
                        <input class="layui-input search-con ys-date" name="OrderSettlementSearch[end_delivery_time]" value="<?=$searchModel['end_delivery_time'];?>" id="end_delivery_time" autocomplete="off">
                    </div>

                    <?php if ($tag != 0){ ?>
                    <div class="layui-inline">
                        账单时间：
                        <input  class="layui-input search-con ys-datetime" name="OrderSettlementSearch[start_settlement_time]"   value="<?=$searchModel['start_settlement_time'];?>" id="start_settlement_time" autocomplete="off">
                    </div>
                    <span class="layui-inline layui-vertical-20">
                        <br>
                        -
                    </span>
                    <div class="layui-inline layui-vertical-20">
                        <br>
                        <input  class="layui-input search-con ys-datetime" name="OrderSettlementSearch[end_settlement_time]" value="<?=$searchModel['end_settlement_time'];?>" id="end_settlement_time" autocomplete="off">
                    </div>
                    <?php }?>

                    <?php if ($tag == 2){ ?>
                    <div class="layui-inline">
                        回款时间：
                        <input  class="layui-input search-con ys-datetime" name="OrderSettlementSearch[start_collection_time]"  value="<?=$searchModel['start_collection_time'];?>" id="start_collection_time" autocomplete="off">
                    </div>
                    <span class="layui-inline layui-vertical-20">
                        <br>
                        -
                    </span>
                    <div class="layui-inline layui-vertical-20">
                        <br>
                        <input  class="layui-input search-con ys-datetime" name="OrderSettlementSearch[end_collection_time]" value="<?=$searchModel['end_collection_time'];?>" id="end_collection_time" autocomplete="off">
                    </div>
                    <?php }?>

                    <div class="layui-inline layui-vertical-20">
                        <br>
                        <button class="layui-btn" data-type="search_lists">搜索</button>
                    </div>
                </div>
            </form>
            <div class="layui-card-body">
                <div id="summary">
                </div>
                <table id="order-settlement" class="layui-table" lay-data="{url:'<?=Url::to(['order-settlement/list?tag='.$tag.'&'.$_SERVER['QUERY_STRING']])?>', height : 'full-20', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]},limit:20}" lay-filter="order-settlement">
                    <thead>
                    <tr>
                        <th lay-data="{templet:'#listOrder',width:190}">订单号</th>
                        <th lay-data="{templet:'#listSource',width:225}">来源</th>
                        <th lay-data="{templet:'#listAmount',width:205}">金额</th>
                        <th lay-data="{templet:'#listRefundAmount',width:205}">退款金额</th>
                        <th lay-data="{templet:'#listFreight',width:135}">成本</th>
                        <th lay-data="{templet:'#listTotal',width:175}">合计</th>
                        <th lay-data="{templet:'#listTime'}">时间</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
        </div>
    </div>
</div>
<script type="text/html" id="listOrder">
<a lay-event="update" data-title="订单详情" data-url="<?=Url::to(['order/view'])?>?order_id={{ d.order_id }}" style="color: #00a0e9">{{d.order_id}}</a><br/>
{{ d.relation_no }}
</script>
<script type="text/html" id="listSource">
    {{ d.platform_type }}<br/>
    {{ d.shop_id }}<br/>
    {{d.sales_period_name}}
</script>
<script type="text/html" id="listAmount">
    销售金额：{{ d.sales_amount }} {{ d.currency }}<br/>
    佣金：{{ d.commission_amount }} {{ d.currency }} <span style="color:red;">{{ (-d.commission_amount/d.sales_amount *100).toFixed(1) }}%</span><br/>
    其他费用：{{ d.other_amount }} {{ d.currency }}<br/>
    平台运费：<span {{# if(d.platform_type_freight != 0){ }}style="color: red"{{# }}}>{{ d.platform_type_freight}} {{ d.currency }}</span><br/>
</script>
<script type="text/html" id="listRefundAmount">
    退款金额：<span {{# if(d.refund_amount != 0){ }}style="color: red"{{# }}}>{{ d.refund_amount }} {{ d.currency }}</span><br/>
    取消费用：<span {{# if(d.cancellation_amount != 0){ }}style="color: red"{{# }}}>{{ d.cancellation_amount }} {{ d.currency }}</span><br/>
    退款佣金：<span {{# if(d.refund_commission_amount != 0){ }}style="color: red"{{# }}}>{{ d.refund_commission_amount }} {{ d.currency }}</span>
</script>
<script type="text/html" id="listFreight">
    采购：￥{{ d.procurement_amount }}<br/>
    运费：￥{{ d.freight }}
</script>
<script type="text/html" id="listTotal">
    总金额：{{ d.total_amount }} {{ d.currency }}<br/>
    利润：￥{{ d.total_profit }}
</script>
<script type="text/html" id="listTime">
    下单：{{ d.order_time }}<br/>
    发货：{{d.delivery_time}}<br/>
    <?php if ($tag != 0){ ?>
    出账：{{ d.settlement_time}}<br/>
    <?php }?>
    <?php if ($tag == 2){ ?>
    回款：{{ d.collection_time}}<br/>
    <?php }?>
</script>
<script>
    const tableName="order-settlement";
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=1.2.3")?>
<?=$this->registerJsFile("@adminPageJs/order/settlement.js?v=0.0.1")?>
