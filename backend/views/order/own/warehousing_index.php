
<?php
use yii\helpers\Url;
use yii\helpers\Html;
use yii\widgets\LinkPager;
use common\components\statics\Base;
use common\models\Order;
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
    .layui-tab-brief > .layui-tab-title .layui-this a{
        color: rgb(0, 150, 136);
    }
    .layui-btn-a {
        border: none;
        background-color: rgba(0, 0, 0, 0);
        -webkit-user-select:text;
    }
    .span-circular-red{
        display: inline-block;
        min-width: 16px;
        height: 16px;
        border-radius: 50%;
        background-color: #ff6666;
        color: #fff;
        text-align: center;
        padding: 5px;
        line-height: 16px;
    }
    .span-circular-grey{
        display: inline-block;
        min-width: 16px;
        height: 16px;
        border-radius: 50%;
        background-color: #999999;
        color: #fff;
        text-align: center;
        padding: 5px;
        line-height: 16px;
    }
    .span-circular-blue{
        display: inline-block;
        min-width: 16px;
        height: 16px;
        border-radius: 80%;
        background-color: #3b97d7;
        color: #fff;
        text-align: center;
        padding: 5px;
        line-height: 16px;
    }
    .span-goode-name{
        color:#a0a3a6;
        font-size: 13px;
    }
</style>
<div class="layui-fluid">
    <div class="layui-card">
<form class="layui-form">
    <blockquote class="layui-elem-quote quoteBox">
        运单号
        <div class="layui-inline " style="width: 250px">
            <input class="layui-input search-con" name="logistics_no" id="logistics_no" value="" autocomplete="off"
                   style="height: 50px;line-height: 50px;font-size: 20px;font-weight: bold">
        </div>
        <div class="layui-inline">
            <a class="layui-btn layui-btn-normal" data-width="1000px" data-height="600px" data-title="发货" data-url="<?=Url::to(['order/scan-ship?logistics_no='])?>" id="logistics_sign" >提交</a>
        </div>
    </blockquote>
</form>
<form class="layui-form">
    <div class="lay-search" style="padding-left: 10px;display: none">
        <div class="layui-inline">
            <label>物流单号</label>
            <input class="layui-input search-con" id="sel_logistics_no" name="OrderSearch[relation_track_no]" value="<?=$searchModel['relation_track_no'];?>"  autocomplete="off">
        </div>
        <div class="layui-inline layui-vertical-20">
            <input type="hidden" name="tag" value="<?=$tag;?>" >
            <input type="hidden" name="relation_track_no" value="<?=$relation_track_no?>">
            <button class="layui-btn" data-ignore="ignore" data-type="search_lists" id="sel_btn">搜索</button>
        </div>
    </div>
</form>
        <div class="layui-card-body">
    <div>
        <?php
        $startCount = (0 == $pages->totalCount) ? 0 : $pages->offset + 1;
        $endCount = ($pages->page + 1) * $pages->pageSize;
        $endCount = ($endCount > $pages->totalCount) ? $pages->totalCount : $endCount;
        ?>
        <div class="summary" style="margin-top: 10px;">
            第<b><?= $startCount ?>-<?= $endCount ?></b>条，共<b><?= $pages->totalCount ?></b>条数据
        </div>
        <div class="layui-form">
        <table class="layui-table" style="text-align: center">
            <thead>
            <tr>
                <th style="width: 60px">商品图片</th>
                <th>商品信息</th>
                <th width="100">金额</th>
                <th>订单号</th>
                <th>来源</th>
                <th>收件人信息</th>
                <th>物流信息</th>
                <th>时间</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($list)): ?>
                <tr>
                    <td colspan="17">无数据</td>
                </tr>
            <?php else: foreach ($list as $k => $v):
                $i = 0;?>
                <?php foreach ($v['goods'] as $goods_k => $goods_v):
                $sku_no = empty($goods_v['platform_asin'])?'':$goods_v['platform_asin'];
                $i ++;
                ?>
                <tr>
                    <td>
                        <?php if(!empty($goods_v['goods_pic'])):?>
                            <div class="goods_img" style="position:relative;cursor: pointer;">
                                <img class="layui-circle pic" src="<?=empty($goods_v['goods_pic'])?'':$goods_v['goods_pic']?>"/>
                                <div class="big_img" style="top: auto;bottom: 0px;position:absolute; z-index: 100;left: 120px; display: none ;">
                                    <div>
                                        <img src="<?=empty($goods_v['goods_pic'])?'':$goods_v['goods_pic']?>" width="300" style="max-width:350px;border:2px solid #666;">
                                    </div>
                                </div>
                            </div>
                        <?php endif;?>
                    </td>
                    <td align="left" style="width: 250px">
                        <a class="layui-btn layui-btn-xs layui-btn-a" data-type="url" data-url="<?=Url::to(['purchase-order/index?search=1&OrderSearch%5Bsku_no%5D='.$sku_no])?>" data-title="采购信息" style="color: #00a0e9"><i class="layui-icon layui-icon-tabs"></i></a>
                        <?php if(in_array($tag,[1,2,3,9,4,5])){?>
                        <span style="float: right;" class="<?= $goods_v['stock_occupy'] != 1?'span-circular-red':'span-circular-grey'; ?>">
                            <?= $goods_v['stock_occupy'] != 1?'缺':'有';?>
                        </span>
                        <?php }?>
                        <b>
                            <?php
                            if(!empty($goods_v['goods_no'])){?>
                            <a class="layui-btn layui-btn-xs layui-btn-a" data-width="550px" data-type="url" data-url="<?=Url::to(['goods/view'])?>?goods_no=<?= $goods_v['goods_no'] ?>" data-title="商品信息" style="color: #00a0e9"><?=$sku_no?></a>
                            <?php } else { ?>
                            <?=$sku_no?>
                            <?php } ?>
                        </b>  x️ <span class="<?= $goods_v['goods_num'] != 1?'span-circular-red':'span-circular-grey'; ?>"><?=empty($goods_v['goods_num'])?'':$goods_v['goods_num']?></span><br/>
                        <span class="span-goode-name"><?=empty($goods_v['goods_name'])?'':$goods_v['goods_name']?></span>
                        <!--<br/>
                        <?=empty($goods_v['platform_type'])?'':Base::$buy_platform_maps[$goods_v['platform_type']]?>-->
                        <?php if(!in_array($tag,[1,2,3,9,4,5])){?>
                        <span style="float: right;" class="span-circular-blue">
                            <?=empty($goods_v['asin_count'])?'':$goods_v['asin_count']?>
                        </span>
                        <?php }?>
                    </td>
                    <?php if($i == 1):?>
                    <td align="left" rowspan="<?=$v['goods_count']?>">
                        订单金额：<?= $v['order_income_price'] ?> <?=$v['currency']?><br/>
                        <?php if(\common\services\sys\AccessService::hasAmount()) {
                            $is_estimate = !(new \common\components\HelperStamp(Order::$settlement_status_map))->isExistStamp($v['settlement_status'], Order::SETTLEMENT_STATUS_COST);?>
                            平台费用：<?= $v['platform_fee'] ?> <?=$v['currency']?><br/>
                        <?= $is_estimate?'预估':'' ?>成本：￥<?= $v['order_cost_price'] ?><br/>
                        <?= $is_estimate?'预估':'' ?>运费：￥<?= $v['freight_price'] ?><br/>
                        <?= $is_estimate?'预估':'' ?>利润：<span style="color: <?= $v['order_profit']>=0?'green':'red' ?>">￥<?= $v['order_profit'] ?></span>
                        <?php }?>
                    </td>
                    <td align="left" rowspan="<?=$v['goods_count']?>">
                        订单号：
                        <?php if($v['order_status'] != Order::ORDER_STATUS_CANCELLED){ ?>
                        <a class="layui-btn layui-btn-primary layui-btn-xs layui-btn-a" data-type="url"  data-url="<?=Url::to(['order/view?order_id='.$v['order_id']])?>" data-title="订单详情"><?= $v['order_id'] ?></a>
                        <?php }else{?>
                        <?= $v['order_id'] ?>
                        <?php }?>
                        <br/>
                        销售单号：
                        <?= $v['relation_no'] ?>
                    </td>
                    <td align="left" rowspan="<?=$v['goods_count']?>">
                        <?= Base::$order_source_maps[$v['source']] ?><br/>
                        <?= empty($v['shop_name'])?'':$v['shop_name'] ?>
                        <span style="padding: 1px 5px;float: right" class="layui-font-12 <?=!empty($v['abnormal_time'])?'layui-bg-red':'layui-bg-orange'?>"><?= Order::$order_status_map[$v['order_status']] ?></span>
                        <?php if(!empty($v['abnormal_time'])){?>
                            <a class="layui-btn layui-btn-a layui-btn-xs" data-type="open" data-height="600px" data-width="800px"  data-url="<?=Url::to(['order-abnormal/follow?order_id='.$v['order_id']])?>" data-title="异常跟进" style="color: #00a0e9"><i class="layui-icon layui-icon-survey"></i></a>
                        <?php } ?>
                    </td>
                        <td align="left" rowspan="<?=$v['goods_count']?>">
                            收件人：<?= $v['buyer_name'] ?><br/>
                            国家：<?= $v['country'] ?>
                        </td>
                        <td align="left" rowspan="<?=$v['goods_count']?>" style="width:250px;word-wrap: break-word;word-break: break-all;">
                            <?php if(!empty($v['order_recommended']) && in_array($v['order_status'], [Order::ORDER_STATUS_UNCONFIRMED, Order::ORDER_STATUS_WAIT_PURCHASE,Order::ORDER_STATUS_APPLY_WAYBILL])) { ?><span style="color: red">推荐物流方式：<?= $v['order_recommended']['logistics_channels_desc'] ?>(￥<?= $v['order_recommended']['freight_price']?>)</span><br/><?php } ?>
                            <a class="layui-btn layui-btn-xs layui-btn-a" data-type="open" data-url="<?=Url::to(['order/recommended-logistics?order_id='.$v['order_id']])?>" data-title="估算运费" style="color: #00a0e9"><i class="layui-icon layui-icon-date"></i></a>
                            物流方式：<?= $v['logistics_channels_desc'] ?><br/>
                            <?php if($v['track_no']){ ?>
                                物流单号：
                                <?php if($v['track_no_url']){ ?>
                                    <a href="<?= $v['track_no_url'] ?>" target="_blank" style="color: #00a0e9"><?= $v['track_no'] ?></a>
                                <?php }else{ ?>
                                <?= $v['track_no'] ?>
                                <?php }?>
                                <br/><?php } ?>
                            <?php if($v['track_logistics_no']){ ?>物流转单号：<?= $v['track_logistics_no'] ?><br/><?php } ?>
                            <?php if(!empty($v['remarks'])){?>
                                <span style="color: red">备注：<?= $v['remarks'] ?></span><br/>
                            <?php } ?>
                           <?php if($v['order_status'] == Order::ORDER_STATUS_CANCELLED){?>
                                <span style="color: red">取消原因：<?=empty(Order::$cancel_reason_map[$v['cancel_reason']])?'':('【'.Order::$cancel_reason_map[$v['cancel_reason']].'】')?><?= $v['cancel_remarks'] ?></span><br/>
                            <?php } ?>
                            <?php if($v['order_status'] == Order::ORDER_STATUS_REFUND){?>
                                <span style="color: red">退款原因：<?=empty(Order::$refund_reason_map[$v['cancel_reason']])?'':('【'.Order::$refund_reason_map[$v['cancel_reason']].'】')?><?= $v['cancel_remarks'] ?></span><br/>
                            <?php } ?>
                            <?php if(in_array($tag,[5,8])){?>
                                <?php if($v['pdelivery_status'] == 10){ ?>
                                <span style="padding: 1px 5px;float: right;" class="layui-font-12 layui-bg-green">发</span>
                                <?php }?>
                            <?php } ?>
                        </td>
                    <td rowspan="<?=$v['goods_count']?>">
                        下单时间：<?= date('Y-m-d H:i:s',$v['date'])?><br/>
                        <?php if(!empty($v['delivery_time'])){ ?>
                            发货时间：<?= empty($v['delivery_time'])?'':date('Y-m-d H:i:s',$v['delivery_time']) ?><br/>
                        <?php }?>
                        <?php if(!empty($v['abnormal_time'])){?>
                            异常时间：<?= date('Y-m-d H:i:s',$v['abnormal_time'])?><br/>
                        <?php } ?>
                        <?php if(!empty($v['cancel_time']) && in_array($v['order_status'],[Order::ORDER_STATUS_CANCELLED,Order::ORDER_STATUS_REFUND])){
                            echo $v['order_status'] == Order::ORDER_STATUS_CANCELLED?'取消':'退款';
                            ?>时间：<?= date('Y-m-d H:i:s',$v['cancel_time'])?><br/>
                        <?php } ?>
                    </td>
                    <?php endif;?>
                </tr>
                <?php endforeach;?>
            <?php
            endforeach;
            endif;
            ?>
            </tbody>
        </table>
        </div>
    </div>
    <?= LinkPager::widget(['pagination' => $pages,'options' => ['class' => 'layui-box layui-laypage layui-laypage-default'],]) ?>
    </div>
    </div>
</div>
<script>
    var onFocus = document.getElementById('logistics_no');
    onFocus.focus();
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.7")?>
<?=$this->registerJsFile("@adminPageJs/order/lists.js?v=".time())?>
<?php
    $this->registerJsFile('@adminPlugins/export/xlsx.core.min.js?v=1',['depends'=>'yii\web\JqueryAsset']);
    $this->registerJsFile('@adminPlugins/export/export-excel.js?v=1',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export-function.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
?>