<?php

use backend\models\search\OrderRefundSearch;
use common\models\Order;
use common\models\order\OrderRefund;
use yii\helpers\Url;
use yii\helpers\Html;
?>
<style>
    .layui-table-body .layui-table-cell{
        height:auto;
    }
    .layui-card {
        padding: 10px 15px;
    }
    .layui-btn-a {
        border: none;
        background-color: rgba(0, 0, 0, 0);
        -webkit-user-select:text;
    }
</style>
<div class="layui-fluid">
    <div class="layui-card">
        <div class="lay-lists">


            <form class="layui-form">
                <div class="lay-search" style="padding-left: 10px">
                <div class="layui-inline">
                    <label>订单号</label>
                    <input class="layui-input search-con" name="OrderRefundSearch[order_id]" autocomplete="off">
                </div>
                <div class="layui-inline">
                    <label>销售单号</label>
                    <input class="layui-input search-con" name="OrderRefundSearch[relation_no]"   autocomplete="off">
                </div>
                <div class="layui-inline">
                    <label>物流单号</label>
                    <input class="layui-input search-con" name="OrderRefundSearch[track_no]"   autocomplete="off">
                </div>
                <div class="layui-inline">
                    退款原因
                    <?= Html::dropDownList('OrderRefundSearch[refund_reason]', '',Order::$refund_reason_map,
                        ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search' ]) ?>
                </div>
                <div class="layui-inline">
                    店铺
                    <?= Html::dropDownList('OrderRefundSearch[shop_id]', '', \common\services\ShopService::getOrderShop(),
                        ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search' ]) ?>
                </div>
                <div class="layui-inline">
                    退款时间：
                    <input  class="layui-input search-con ys-date" name="OrderRefundSearch[start_time]" id="start_time" autocomplete="off">
                </div>
                <span class="layui-inline layui-vertical-20">
                    <br>
                    -
                </span>
                    <div class="layui-inline layui-vertical-20">
                        <br>
                        <input  class="layui-input search-con ys-date" name="OrderRefundSearch[end_time]" id="end_time" autocomplete="off">
                    </div>
                    <div class="layui-inline layui-vertical-20">
                        <br>
                        <button class="layui-btn" data-type="search_lists">搜索</button>
                    </div>
                </div>
            </form>
            <div class="layui-card-body">
                <table id="ys_order_refund" class="layui-table" lay-data="{url:'<?=Url::to(['order-refund/list'])?>', height : 'full-20', cellMinWidth : 95, page:{limits:[10, 50, 100, 500, 1000]}}" lay-filter="ys_order_refund">
                    <thead>
                    <tr>
                        <th lay-data="{field: 'id', width:80}">ID</th>
                        <th lay-data="{templet:'#orderTpl',width:250}">订单号</th>
                        <th lay-data="{field: 'refund_reason',width:120}">退款原因</th>
                        <th lay-data="{field: 'refund_type',width:120}">退款类型</th>
                        <th lay-data="{field: 'refund_num',width:120}">退款金额</th>
                        <th lay-data="{field: 'admin_name',width:120}">操作者</th>
                        <th lay-data="{templet:'#remarksTpl'}">备注</th>
                        <th lay-data="{field: 'refund_time',width:240}">退款时间</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<script type="text/html" id="orderTpl">
    {{ d.shop_id }}<br>
    <a lay-event="update" class="layui-btn layui-btn-xs layui-btn-a" data-title="订单详情" data-url="<?=Url::to(['order/view'])?>?order_id={{ d.order_id }}" style="color: #00a0e9;padding-left: 0px;font-size: 14px">{{d.order_id}}</a><br/>
    <div class="span-goode-name" style="white-space:pre-wrap;">{{d.relation_no}}</div>
</script>
<script type="text/html" id="remarksTpl">
    <div class="span-goode-name" style="white-space:pre-wrap;">{{d.refund_remarks}}</div>
</script>
<script>
    const tableName="ys_order_refund";
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js")?>
