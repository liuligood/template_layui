<?php
/**
 * @var $this \yii\web\View;
 */

use common\components\statics\Base;
use common\services\financial\PlatformSalesPeriodService;
use yii\helpers\Url;
use common\models\Order;
?>
<style>
    html {
        background: #fff;
    }
    .layui-table-th{
        background: #FAFAFA;
        color: #666;
        width: 100px;
        text-align: right;
    }
    .span-goods-name{
        color:#a0a3a6;
        font-size: 13px;
    }
    .layui-tab-brief > .layui-tab-title .layui-this a{
        color: rgb(0, 150, 136);
    }
    .layui-btn-a {
        border: none;
        background-color: rgba(0, 0, 0, 0);
        -webkit-user-select:text;
    }
</style>
<div class="layui-col-md9 layui-col-xs12 lay-lists" style="margin:0 20px 30px 20px">
    <div style="padding:10px;">
        <?php if(in_array($model['order_status'],[Order::ORDER_STATUS_WAIT_PRINTED_OUT_STOCK,Order::ORDER_STATUS_WAIT_PRINTED,Order::ORDER_STATUS_WAIT_SHIP,Order::ORDER_STATUS_SHIPPED,Order::ORDER_STATUS_FINISH])){ ?>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-sm layui-btn-normal" id="js-printed" data-order_id="<?=$model['order_id']?>" data-url="<?=Url::to(['order/batch-printed'])?>" >打印面单</a>
            </div>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-sm layui-btn-normal" id="js-direct-printed" data-url="<?=Url::to(['order/direct-printed?order_id='.$model['order_id']])?>" >直接打印面单</a>
            </div>
            <?php if($model['source'] == \common\components\statics\Base::PLATFORM_HEPSIGLOBAL && $model['country']=='AZ'){ ?>
                <div class="layui-inline">
                    <a class="layui-btn layui-btn-sm layui-btn-normal" id="js-printed-invoice" data-order_id="<?=$model['order_id']?>" data-url="<?=Url::to(['order/printed-invoice'])?>" >打印发票</a>
                </div>
            <?php }?>
        <?php }?>

        <?php if($model['source'] == Base::PLATFORM_ALLEGRO){ ?>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-sm layui-btn-normal" id="down-invoice" data-url="<?=Url::to(['order/invoice?order_id='.$model['order_id']])?>">下载发票</a>
            </div>
        <?php }?>

        <?php if($model['order_status'] == Order::ORDER_STATUS_UNCONFIRMED){ ?>
        <div class="layui-inline">
            <a class="layui-btn layui-btn-sm layui-btn-normal js-batch" data-title="确认" data-order_id="<?=$model['order_id']?>" data-url="<?=Url::to(['order/batch-confirm'])?>" >确认</a>
        </div>
        <?php }?>

        <?php if(in_array($model['order_status'],[Order::ORDER_STATUS_WAIT_PRINTED_OUT_STOCK,Order::ORDER_STATUS_WAIT_PRINTED,Order::ORDER_STATUS_WAIT_SHIP,Order::ORDER_STATUS_SHIPPED,Order::ORDER_STATUS_APPLY_WAYBILL])){ ?>
        <div class="layui-inline">
            <a class="layui-btn layui-btn-sm layui-btn-warm js-batch" data-order_id="<?=$model['order_id']?>" data-title="打回待处理「打回待处理会清除原有的物流单号」" data-url="<?=Url::to(['order/reset-logistics'])?>" >打回待处理</a>
        </div>
        <?php }?>

        <?php if($model['order_status'] == Order::ORDER_STATUS_WAIT_PURCHASE){ ?>
        <div class="layui-inline">
            <a class="layui-btn layui-btn-sm" data-type="open"  data-url="<?=Url::to(['order/input-logistics?order_id='.$model['order_id'].'&gen_logistics=1'])?>" data-height="350px" data-title="申请运单号" data-callback_title="订单列表">申请运单号</a>
        </div>
        <?php }?>

        <?php if($model['order_status'] == Order::ORDER_STATUS_APPLY_WAYBILL){ ?>
        <div class="layui-inline">
            <a class="layui-btn layui-btn-sm layui-btn-normal js-batch" data-title="移入待打包" data-order_id="<?=$model['order_id']?>" data-url="<?=Url::to(['order/batch-update-status?order_status='.Order::ORDER_STATUS_WAIT_PRINTED_OUT_STOCK])?>">移入待打包</a>
        </div>
        <?php }?>

        <?php if($model['order_status'] == Order::ORDER_STATUS_WAIT_PRINTED_OUT_STOCK){ ?>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-sm layui-btn-normal js-batch" data-title="移入有货" data-order_id="<?=$model['order_id']?>" data-url="<?=Url::to(['order/batch-update-status?order_status='.Order::ORDER_STATUS_WAIT_PRINTED])?>">移入有货</a>
            </div>
        <?php }?>

        <?php if($model['order_status'] == Order::ORDER_STATUS_WAIT_PRINTED){ ?>
        <div class="layui-inline">
            <a class="layui-btn layui-btn-sm layui-btn-warm js-batch" data-title="移入缺货" data-order_id="<?=$model['order_id']?>" data-url="<?=Url::to(['order/batch-update-status?order_status='.Order::ORDER_STATUS_WAIT_PRINTED_OUT_STOCK])?>">移入缺货</a>
        </div>
        <div class="layui-inline">
            <a class="layui-btn layui-btn-sm layui-btn-normal js-batch" data-title="移入待发货" data-order_id="<?=$model['order_id']?>" data-url="<?=Url::to(['order/batch-update-status?order_status='.Order::ORDER_STATUS_WAIT_SHIP])?>" >移入待发货</a>
        </div>
        <?php }?>

        <?php if($model['order_status'] == Order::ORDER_STATUS_WAIT_SHIP){ ?>
        <div class="layui-inline">
            <a class="layui-btn layui-btn-sm layui-btn-warm js-batch" data-title="打回待打包" data-order_id="<?=$model['order_id']?>" data-url="<?=Url::to(['order/batch-update-status?go_back=1&order_status='.Order::ORDER_STATUS_WAIT_PRINTED])?>" >打回待打包</a>
        </div>
        <div class="layui-inline">
            <a class="layui-btn layui-btn-sm layui-btn-normal" id="js-ship" data-order_id="<?=$model['order_id']?>" data-url="<?=Url::to(['order/batch-ship?type=1'])?>" >发货</a>
        </div>
        <?php }?>

        <?php if($model['order_status'] == Order::ORDER_STATUS_SHIPPED){ ?>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-sm layui-btn-warm js-batch" data-order_id="<?=$model['order_id']?>" data-title="批量打回待发货" data-url="<?=Url::to(['order/batch-update-status?go_back=1&order_status='.Order::ORDER_STATUS_WAIT_SHIP])?>" >打回待发货</a>
            </div>
        <?php }?>

        <?php if($model['order_status'] == Order::ORDER_STATUS_FINISH){ ?>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-sm layui-btn-normal js-batch" data-order_id="<?=$model['order_id']?>" data-title="批量打回已发货「打回已发货需要重新买货或占有库存」" data-url="<?=Url::to(['order/batch-update-status?go_back=1&order_status='.Order::ORDER_STATUS_SHIPPED])?>">打回已发货</a>
            </div>
        <?php }?>

        <?php if($model['order_status'] == Order::ORDER_STATUS_WAIT_PRINTED || $model['order_status'] == Order::ORDER_STATUS_WAIT_SHIP || $model['order_status'] == Order::ORDER_STATUS_SHIPPED || $model['order_status'] == Order::ORDER_STATUS_FINISH){ ?>
        <div class="layui-inline">
            <a class="layui-btn layui-btn-sm" data-type="open"  data-url="<?=Url::to(['order/input-logistics?order_id='.$model['order_id'].'&gen_logistics=1'])?>" data-height="350px" data-title="更换运单号" data-callback_title="订单列表">更换运单号</a>
        </div>
        <?php }?>

        <?php if($model['pdelivery_status'] != 10 && in_array($model['order_status'],[Order::ORDER_STATUS_APPLY_WAYBILL,Order::ORDER_STATUS_WAIT_PRINTED_OUT_STOCK,Order::ORDER_STATUS_WAIT_PRINTED,Order::ORDER_STATUS_WAIT_SHIP])){ ?>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-sm js-batch" data-title="虚拟发货" data-order_id="<?=$model['order_id']?>" data-url="<?=Url::to(['order/batch-virtual-ship'])?>" >虚拟发货</a>
            </div>
        <?php }?>

        <div style="float: right;margin-left: 200px;">
        <?php if(empty($model['abnormal_time'])){ ?>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-sm layui-btn-danger" data-type="open" data-title="批量移入异常" data-url="<?=Url::to(['order/batch-move-abnormal?order_id='.$model['order_id']])?>" >移入异常</a>
            </div>
        <?php }?>

        <?php if(!in_array($model['order_status'] ,[Order::ORDER_STATUS_CANCELLED,Order::ORDER_STATUS_REFUND])) { ?>
        <div class="layui-inline">
            <a class="layui-btn layui-btn-normal layui-btn-sm" data-ignore="ignore" href="<?=Url::to(['order/update?id='.$model['id']])?>">编辑</a>
        </div>
        <div class="layui-inline">
            <a class="layui-btn layui-btn-danger layui-btn-sm" data-type="open" data-height="400px" data-title="取消订单"  data-url="<?=Url::to(['order/cancel?order_id='.$model['order_id']])?>">取消订单</a>
        </div>
        <?php }?>
            <div class="layui-inline">
                <a class="layui-btn layui-btn layui-btn-sm" data-type="open" data-height="600px" data-width="700px" data-title="退回海外仓"  data-url="<?=Url::to(['order-overseas-stock/create?id='.$model['id']])?>">退回海外仓</a>
            </div>
        <?php if(in_array($model['order_status'] ,[Order::ORDER_STATUS_FINISH])) { ?>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-danger layui-btn-sm" data-type="open" data-height="400px" data-title="订单退款"  data-url="<?=Url::to(['order/refund?order_id='.$model['order_id']])?>">订单退款</a>
            </div>
        <?php }?>
        </div>

        <div style="float: right">
            <div class="layui-inline">
                <a class="layui-btn layui-btn-sm js-batch" data-order_id="<?=$model['order_id']?>" data-url="<?=Url::to(['order/batch-update-goods-status?status='.\common\models\Goods::GOODS_STATUS_VALID])?>" data-title="启用商品">启用商品</a>
            </div>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-sm layui-btn-danger js-batch" data-order_id="<?=$model['order_id']?>" data-url="<?=Url::to(['order/batch-update-goods-status?status='.\common\models\Goods::GOODS_STATUS_INVALID])?>" data-title="禁用商品">禁用商品</a>
            </div>
        </div>
    </div>
    <div style="clear: both"></div>
    <table class="layui-table">
        <tbody>
        <tr>
            <td class="layui-table-th">订单号</td>
            <td><?=$model['order_id']?></td>
            <td class="layui-table-th">销售单号</td>
            <td><?=$model['relation_no']?></td>
        </tr>
        <tr>
            <td class="layui-table-th">来源平台</td>
            <td><?=\common\components\statics\Base::$order_source_maps[$model['source']]?></td>
            <td class="layui-table-th">店铺</td>
            <td><?=$model['shop_name']?></td>
        </tr>
        <?php if(in_array($model['order_status'],[Order::ORDER_STATUS_WAIT_PURCHASE,Order::ORDER_STATUS_UNCONFIRMED])){ ?>
        <tr>
            <td class="layui-table-th">推荐物流方式</td>
            <td colspan="3">
                <?php if(!empty($order_recommended)){ ?>
                <?=\common\services\transport\TransportService::getShippingMethodName($order_recommended['logistics_channels_id'])?>(￥<?= $order_recommended['freight_price']?>)
                <?php }?>
            </td>
        </tr>
        <?php }?>
        <tr>
            <td class="layui-table-th">物流方式</td>
            <td>
                <?=empty($model['logistics_channels_id'])?'':\common\services\transport\TransportService::getShippingMethodName($model['logistics_channels_id'])?>
                <?php if (!empty($model['logistics_channels_id'])){?>
                    <a class="layui-btn layui-btn-xs layui-btn-a" data-type="open" data-width="600px" data-url="<?=Url::to(['order/recommended-logistics?order_id='.$model['order_id']])?>" data-title="估算运费" style="color: #00a0e9"><i class="layui-icon layui-icon-date"></i></a>
                <?php }?>
            </td>
            <td class="layui-table-th">物流单号</td>
            <td><?=$model['track_no']?> <?php if(!empty($model['logistics_pdf'])){?> <a href="<?= $model['logistics_pdf'] ?>" target="_blank" style="color: #00a0e9">缓存面单</a><?php }?></td>
        </tr>
        <tr>
            <td class="layui-table-th">订单状态</td>
            <td><span style="padding: 1px 5px;float: left" class="layui-font-12 layui-bg-orange"><?= Order::$order_status_map[$model['order_status']] ?></span>
                <?php if(!empty($model['abnormal_time'])){ ?>
                <span style="padding: 1px 5px;float: left;margin-left: 10px" class="layui-font-12 layui-bg-red">异常</span>
                <?php }?>

                <?php if($model['pdelivery_status'] == 10){ ?>
                    <span style="padding: 1px 5px;float: left;margin-left: 10px" class="layui-font-12 layui-bg-green">发</span>
                <?php }?>
            </td>
            <td class="layui-table-th">下单时间</td>
            <td><?=$model['date']?></td>
        </tr>
        <tr>
            <td class="layui-table-th">发货时间</td>
            <td colspan="3"><?=$model['delivery_time'] == 0 ? '' : date('Y-m-d H:i:s',$model['delivery_time'])?></td>
        </tr>
        <?php if (!empty($refund)){?>
            <tr>
                <td class="layui-table-th">退款信息</td>
                <td><?=empty(\common\models\Order::$refund_reason_map[$refund['refund_reason']])?'':('【'.\common\models\Order::$refund_reason_map[$refund['refund_reason']].'】')?><?= $refund['refund_remarks'] ?></td>
                <td class="layui-table-th">退款时间</td>
                <td><?=date('Y-m-d H:i:s',$model['cancel_time'])?></td>
            </tr>
        <?php }elseif ($model['delivery_status'] == 30){ ?>
            <tr>
                <td class="layui-table-th">签收状态</td>
                <td><span style="padding: 1px 5px;float: left" class="layui-font-12 layui-bg-orange">已签收</span></td>
                <td class="layui-table-th">签收时间</td>
                <td><?=date('Y-m-d H:i:s',$model['delivered_time'])?></td>
            </tr>
        <?php }elseif ($model['order_status'] == Order::ORDER_STATUS_CANCELLED){ ?>
            <tr>
                <td class="layui-table-th">取消信息</td>
                <td><?=empty(\common\models\Order::$cancel_reason_map[$model['cancel_reason']])?'':('【'.\common\models\Order::$cancel_reason_map[$model['cancel_reason']].'】')?><?= $model['cancel_remarks'] ?></td>
                <td class="layui-table-th">取消时间</td>
                <td><?=date('Y-m-d H:i:s',$model['cancel_time'])?></td>
            </tr>
        <?php }?>
        <tr>
            <td class="layui-table-th">订单尺寸</td>
            <td><?=$model['size']?></td>
            <td class="layui-table-th">订单重量</td>
            <td><?=$model['weight']?></td>
        </tr>
        <tr>
            <td class="layui-table-th">备注</td>
            <td colspan="3"><?=$model['remarks']?></td>
        </tr>
        </tbody>
    </table>

    用户信息
    <table class="layui-table">
        <tbody>
        <tr>
            <td class="layui-table-th">公司名称</td>
            <td><?=$model['company_name']?></td>
            <td class="layui-table-th">邮箱</td>
            <td><?=$model['email']?></td>
            <td class="layui-table-th">客户编号</td>
            <td><?=$model['user_no']?></td>
        </tr>
        <tr>
            <td class="layui-table-th">买家名称</td>
            <td><?=$model['buyer_name']?></td>
            <td class="layui-table-th">买家电话</td>
            <td><?=$model['buyer_phone']?></td>
            <td class="layui-table-th">邮编</td>
            <td><?=$model['postcode']?></td>
        </tr>
        <tr>
            <td class="layui-table-th">国家</td>
            <td> <?= \common\services\sys\CountryService::getName($model['country'])?></td>
            <td class="layui-table-th">城市</td>
            <td><?=$model['city']?></td>
            <td class="layui-table-th">省/州</td>
            <td><?=$model['area']?></td>
        </tr>
        <tr>
            <td class="layui-table-th">详细地址</td>
            <td colspan="5"><?=$model['address']?></td>
        </tr>
        <tr>
            <td class="layui-table-th">税号</td>
            <td> <?= $model['tax_number']?></td>
            <td class="layui-table-th">销售单号(税号)</td>
            <td><?=$model['tax_relation_no']?></td>
            <td class="layui-table-th">税号是否已使用</td>
            <td><?=$model['tax_number_use']==1?'是':'否'?></td>
        </tr>
        </tbody>
    </table>

    商品信息
    <table class="layui-table">
        <tbody>
        <tr>
            <th>商品图片</th>
            <th>商品名称</th>
            <th>数量</th>
            <th>单价</th>
        </tr>
        <?php foreach ($order_goods as $goods_v){
            $sku_no = empty($goods_v['platform_asin'])?'':$goods_v['platform_asin'];
            ?>
            <tr>
                <td>
                    <a href="<?=empty($goods_v['goods_pic'])?'':$goods_v['goods_pic']?>" data-lightbox="pic">
                        <img class="layui-upload-img" style="width: 80px;"  src="<?=empty($goods_v['goods_pic'])?'':$goods_v['goods_pic']?>">
                    </a>
                </td>
                <td>
                    <a class="layui-btn layui-btn-xs layui-btn-a" data-type="url" data-url="<?=Url::to(['purchase-order/index?search=1&PurchaseOrderSearch%5Bsku_no%5D='.$sku_no])?>" data-title="采购信息" style="color: #00a0e9"><i class="layui-icon layui-icon-tabs"></i></a>
                    <b>
                        <?php if(!empty($goods_v['goods_no'])){?>
                            <a class="layui-btn layui-btn-xs layui-btn-a" data-type="url" data-url="<?=Url::to(['goods/view'])?>?goods_no=<?= $goods_v['goods_no'] ?>" data-title="商品信息" style="color: #00a0e9"><?=$sku_no?></a>
                        <?php } else { ?>
                            <?=$sku_no?>
                        <?php } ?><br/>
                        <span class="span-goods-name"><?=empty($goods_v['goods_name'])?'':$goods_v['goods_name']?></span>
                </td>
                <td><?=empty($goods_v['goods_num'])?'':$goods_v['goods_num']?></td>
                <td>
                    <?=empty($goods_v['goods_income_price'])?'':$goods_v['goods_income_price']?> <?=$model['currency']?> <br/>
                    <?php if(\common\services\sys\AccessService::hasAmount()) { ?>
                        ￥<?=round($goods_v['goods_income_price'] * $model['exchange_rate'],2);?>
                    <?php }?>
                </td>
            </tr>
        <?php }?>
        </tbody>
    </table>

    <?php if (empty($settlement)):?>
    <div style="float: right">
        订单金额：<?= $model['order_income_price'] ?> <?=$model['currency']?><br/>
        <?php if(\common\services\sys\AccessService::hasAmount()) {
            $is_estimate = !(new \common\components\HelperStamp(Order::$settlement_status_map))->isExistStamp($model['settlement_status'], Order::SETTLEMENT_STATUS_COST);?>
            平台费用：<?= $model['platform_fee'] ?> <?=$model['currency']?><br/>
            <?= $is_estimate?'预估':'' ?>成本：￥<?= $model['order_cost_price'] ?><br/>
            <?= $is_estimate?'预估':'' ?>运费：￥<?= $model['freight_price'] ?><br/>
            <?= $is_estimate?'预估':'' ?>利润：<span style="color: <?= $model['order_profit']>=0?'green':'red' ?>">￥<?= $model['order_profit'] ?></span>
        <?php }?>
    </div>
    <div style="clear: both"></div>
    <?php if(!empty($order_declare)){?>
        申报信息
        <table class="layui-table">
            <tbody>
            <tr>
                <th>中文名称</th>
                <th>英文名称</th>
                <th>申报金额(USD)</th>
                <th>申报重量(kg)</th>
                <th>申报数量</th>
                <th>材质</th>
                <th>用途</th>
                <th>海关编码</th>
            </tr>
            <?php foreach ($order_declare as $declare_v){?>
                <tr>
                    <td>
                        <?=$declare_v['declare_name_cn']?>
                    </td>
                    <td>
                        <?=$declare_v['declare_name_en']?>
                    </td>
                    <td>
                        <?=$declare_v['declare_price']?>
                    </td>
                    <td>
                        <?=$declare_v['declare_weight']?>
                    </td>
                    <td>
                        <?=$declare_v['declare_num']?>
                    </td>
                    <td>
                        <?=$declare_v['declare_material']?>
                    </td>
                    <td>
                        <?=$declare_v['declare_purpose']?>
                    </td>
                    <td>
                        <?=$declare_v['declare_customs_code']?>
                    </td>
                </tr>
            <?php }?>
            </tbody>
        </table>
    <?php }?>
    <?php else:?>
        <div style="float: right">
            销售金额：<?= $settlement['sales_amount'] ?> <?= $settlement['currency'] ?><br/>
            佣金：<?= $settlement['commission_amount'] ?> <?= $settlement['currency'] ?><br/>
            其他费用：<?= $settlement['other_amount'] ?> <?= $settlement['currency'] ?><br/>
            退款金额：<?= $settlement['refund_amount'] ?> <?= $settlement['currency'] ?><br/>
            取消费用：<?= $settlement['cancellation_amount'] ?> <?= $settlement['currency'] ?><br/>
            退款佣金：<?= $settlement['refund_commission_amount'] ?> <?= $settlement['currency'] ?><br/>
            平台运费：<?= $settlement['platform_type_freight'] ?> <?= $settlement['currency'] ?><br/>
            采购金额：￥<?= $settlement['procurement_amount'] ?><br/>
            运费：￥<?= $settlement['freight'] ?><br/>
            总金额：<?= $settlement['total_amount']?> <?= $settlement['currency'] ?><br/>
            <?php if($settlement['tax_amount'] != 0){ ?>
            税务：<span style="color: red"><?= $settlement['tax_amount'] ?></span> <?= $settlement['currency'] ?><br/>
            <?php }?>
            总利润：￥<?= $settlement['total_profit'] ?>
        </div>
    <?php endif;?>
    <div style="clear: both"></div>
    <?php if (!empty($financial)):?>
        <table class="layui-table">
            流水信息
            <tbody>
            <tr>
                <th>流水订单号</th>
                <th>操作类型</th>
                <th>金额</th>
                <th>结算时间</th>
                <th>操作时间</th>
                <th>交易流水号</th>
                <th>操作人消息</th>
                <th>操作单消息</th>
            </tr>
            <?php foreach ($financial as $financial_v):
                $operations = PlatformSalesPeriodService::findMap($financial_v['operation']);?>
                <tr>
                    <td>
                        <?=$financial_v['relation_no']?>
                    </td>
                    <td>
                        <?=!empty(PlatformSalesPeriodService::$OPREATION_MAP[$operations]) ? PlatformSalesPeriodService::$OPREATION_MAP[$operations] : $financial_v['operation'];?>
                    </td>
                    <td>
                        <?=$financial_v['amount']." ".$financial_v['currency']?>
                    </td>
                    <td>
                        <?=date('Y-m-d',$financial_v['date'])?>
                    </td>
                    <td>
                        <?=$financial_v['data_post'] == 0 ? '' : date('Y-m-d',$financial_v['data_post'])?>
                    </td>
                    <td>
                        <?=$financial_v['identifier']?>
                    </td>
                    <td>
                        <?=$financial_v['buyer']?>
                    </td>
                    <td>
                        <?=$financial_v['offer']?>
                    </td>
                </tr>
            <?php endforeach;?>
            </tbody>
        </table>
    <?php endif;?>
    <table class="layui-table">
    <tbody>
    <tr>
    	<td class="layui-table-th" style="text-align: center">操作时间</td>
        <td class="layui-table-th" style="text-align: center">操作类型</td>
        <td class="layui-table-th" style="text-align: center">操作人</td>
        <td class="layui-table-th" style="text-align: center; width:300px">操作说明</td>
    </tr>
    <?php foreach ($per_info as $v){ ?>
        <tr>
        	<td ><?=date('Y-m-d H:i:s',$v['add_time'] )?></td>
            <td ><?=$v['op_name']?></td>
            <td><?=$v['op_user_name']?></td>
  			<td><?=\common\services\sys\SystemOperlogService::getShowLogDesc($v);?></td>
        </tr>
    <?php }?>
    </tbody>
</table>
</div>
<?php
$this->registerJsFile('@adminPlugins/extend/lodop.js?v=0.2',['depends'=>'yii\web\JqueryAsset']);
$this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
?>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.7")?>
<?=$this->registerJsFile("@adminPageJs/order/view.js?v=".time())?>
