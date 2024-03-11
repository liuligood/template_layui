
<?php

use common\components\HelperStamp;
use common\models\Order;
use common\services\ShopService;
use common\services\warehousing\WarehouseService;
use yii\helpers\Url;
use common\models\RealOrder;
use yii\helpers\Html;
use yii\widgets\LinkPager;
use common\components\statics\Base;
use common\services\goods\GoodsService;
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
    .layui-table .layui-btn{
        margin-bottom: 3px;
    }
    .summary i{
        color: red;
    }
</style>
<div class="layui-fluid">
    <div class="layui-card">
        <div class="layui-card-body">
            <div class="lay-lists">
                <form class="layui-form">
                    <div class="lay-search">
                        <div class="layui-inline">
                            <label>订单号</label>
                            <textarea name="OrderSearch[order_id]" class="layui-textarea search-con" style="height: 39px; min-height:39px"><?=$searchModel['order_id'];?></textarea>
                        </div>
                        <div class="layui-inline">
                            平台
                            <?= Html::dropDownList('OrderSearch[source]', $searchModel['source'], Base::$platform_maps,
                                ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','id'=>'platform']) ?>
                        </div>
                        <div class="layui-inline">
                            店铺
                            <?= \backend\widgets\LinkageDropDownList::widget(['id' => 'shop','parent_id'=>'platform','name'=>'OrderSearch[shop_id]','select'=>$searchModel['shop_id'],'option'=> ShopService::getOrderShopMap(),'param'=>['data-placeholder' => '全部','prompt' => '全部','style'=>'width:180px']]) ?>
                        </div>
                        <div class="layui-inline">
                            <label>销售单号</label>
                            <textarea name="OrderSearch[relation_no]" class="layui-textarea search-con" style="height: 39px; min-height:39px"><?=$searchModel['relation_no'];?></textarea>
                        </div>
                        <div class="layui-inline">
                            <label>SKU</label>
                            <textarea name="OrderSearch[platform_asin]" class="layui-textarea search-con" style="height: 39px; min-height:39px"><?=$searchModel['platform_asin'];?></textarea>
                        </div>
                        <div class="layui-inline">
                            <label>商品名称</label>
                            <input class="layui-input search-con" name="OrderSearch[goods_name]" value="<?=htmlentities($searchModel['goods_name'], ENT_COMPAT);?>"  autocomplete="off">
                        </div>
                        <div class="layui-inline">
                            <label>买家名称</label>
                            <input class="layui-input search-con" name="OrderSearch[buyer_name]" value="<?=htmlentities($searchModel['buyer_name'], ENT_COMPAT);?>"  autocomplete="off">
                        </div>
                        <div class="layui-inline" style="width: 200px">
                            <label>国家</label>
                            <?= Html::dropDownList('OrderSearch[country]', $searchModel['country'], $country_arr,['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>"layui-input search-con ys-select2"]) ?>
                        </div>
                        <?php if($tag != 1){?>
                            <div class="layui-inline">
                                物流方式
                                <?= Html::dropDownList('OrderSearch[logistics_channels_id]', $searchModel['logistics_channels_id'], $logistics_channels_ids,
                                    ['prompt' => '全部','class'=>'search-con' ]) ?>
                            </div>
                            <?php if($tag != 2){?>
                                <div class="layui-inline">
                                    <label>物流单号</label>
                                    <textarea name="OrderSearch[track_no]" class="layui-textarea search-con" style="height: 39px; min-height:39px"><?=$searchModel['track_no'];?></textarea>
                                </div>
                            <?php } ?>
                        <?php } ?>
                        <div class="layui-inline">
                            仓库
                            <?= Html::dropDownList('OrderSearch[warehouse]', $searchModel['warehouse'], WarehouseService::$warehouse_map,
                                ['prompt' => '全部','class'=>' search-con' ]) ?>
                        </div>
                        <div class="layui-inline">
                            <label>下单日期</label>
                            <input  class="layui-input search-con ys-date" name="OrderSearch[start_date]" value="<?=$searchModel['start_date'];?>"  id="start_date" autocomplete="off">
                        </div>
                        <span class="layui-inline layui-vertical-20">
                            -
                        </span>
                        <div class="layui-inline layui-vertical-20">
                            <input  class="layui-input search-con ys-date" name="OrderSearch[end_date]" value="<?=$searchModel['end_date'];?>" id="end_date" autocomplete="off">
                        </div>
                        <div class="layui-inline">
                            发货时间
                            <input class="layui-input search-con ys-date" name="OrderSearch[start_delivery_time]" value="<?=$searchModel['start_delivery_time'];?>" id="start_delivery_time" autocomplete="off">
                        </div>
                        <span class="layui-inline layui-vertical-20">
                            -
                        </span>
                        <div class="layui-inline layui-vertical-20">
                            <input class="layui-input search-con ys-date" name="OrderSearch[end_delivery_time]" value="<?=$searchModel['end_delivery_time'];?>" id="end_delivery_time" autocomplete="off">
                        </div>
                        <div class="layui-inline layui-vertical-20">
                            <input type="hidden" name="tag" value="<?=$tag;?>" >
                            <button class="layui-btn" data-type="search_lists">搜索</button>
                        </div>
                    </div>
                </form>

                <div>
                    <?php
                    $startCount = (0 == $pages->totalCount) ? 0 : $pages->offset + 1;
                    $endCount = ($pages->page + 1) * $pages->pageSize;
                    $endCount = ($endCount > $pages->totalCount) ? $pages->totalCount : $endCount;
                    ?>
                    <div class="summary" style="margin-top: 10px;">
                        第<b><?= $startCount ?>-<?= $endCount ?></b>条，共<b><?= $pages->totalCount ?></b>条数据
                        <?php if(!empty($order_statistics)) {?>
                            销售总金额:
                            <?php
                            $o_freight_price = 0;
                            $o_order_cost_price = 0;
                            foreach ($order_statistics as $stat_v) {
                                echo '<i>'.$stat_v['order_income_price'] . ' ' . $stat_v['currency'] . '</i> ';
                                $o_freight_price += $stat_v['freight_price'];
                                $o_order_cost_price += $stat_v['order_cost_price'];
                            }
                            echo ' 运费：<i>'.$o_freight_price . '</i> 成本：<i>' . $o_order_cost_price.'</i>';
                         }?>
                    </div>
                    <div class="layui-form">
                        <table class="layui-table" style="text-align: center">
                            <thead>
                            <tr>
                                <th><input type="checkbox" lay-filter="select_all" id="select_all" name="select_all" lay-skin="primary" title=""></th>
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
                                    <?php if($i == 1):?>
                                        <td rowspan="<?=$v['goods_count']?>"><input type="checkbox" class="select_order" name="id[]" value="<?=$v['order_id']?>" lay-skin="primary" title=""></td>
                                    <?php endif;?>
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
                                        <a class="layui-btn layui-btn-xs layui-btn-a" data-type="url" data-url="<?=Url::to(['purchase-order/index?search=1&PurchaseOrderSearch%5Bsku_no%5D='.$sku_no])?>" data-title="采购信息" style="color: #00a0e9"><i class="layui-icon layui-icon-tabs"></i></a>
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
                                        <?php if(!empty($goods_v['ccolour']) || !empty($goods_v['csize'])) {?>
                                            <br/>
                                            <span style="color:#ff0000"><?=$goods_v['ccolour']?> <?=$goods_v['csize']?></span>
                                        <?php }?>
                                        <?php
                                        if(in_array($v['order_status'] ,[\common\models\Order::ORDER_STATUS_WAIT_PURCHASE, \common\models\Order::ORDER_STATUS_APPLY_WAYBILL])){
                                            ?>
                                            <br/>
                                            <?=empty($goods_v['weight'])?'':('重量:'.$goods_v['weight'].'kg ')?> <?=empty($goods_v['size'])?'':(' 尺寸:'.$goods_v['size'])?>
                                        <?php }?>
                                        <!--
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
                                                $is_estimate = !(new HelperStamp(Order::$settlement_status_map))->isExistStamp($v['settlement_status'], Order::SETTLEMENT_STATUS_COST);?>
                                                平台费用：<?= $v['platform_fee'] ?> <?=$v['currency']?><br/>
                                                <?= $is_estimate?'预估':'' ?>成本：￥<?= $v['order_cost_price'] ?><br/>
                                                <?= $is_estimate?'预估':'' ?>运费：￥<?= $v['freight_price'] ?><br/>
                                                <?= $is_estimate?'预估':'' ?>利润：<span style="color: <?= $v['order_profit']>=0?'green':'red' ?>">￥<?= $v['order_profit'] ?></span>
                                            <?php }?>
                                        </td>
                                        <td align="left" rowspan="<?=$v['goods_count']?>">
                                            订单号：
                                            <?php if($v['order_status'] != \common\models\Order::ORDER_STATUS_CANCELLED){ ?>
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
                                            <?= empty($v['shop_name'])?'':$v['shop_name'] ?><br/>

                                            仓库：<?= empty(WarehouseService::$warehouse_map[$v['warehouse']])?'': WarehouseService::$warehouse_map[$v['warehouse']] ?>
                                            <span style="padding: 1px 5px;float: right" class="layui-font-12 <?=!empty($v['abnormal_time'])?'layui-bg-red':'layui-bg-orange'?>"><?= \common\models\Order::$order_status_map[$v['order_status']] ?></span>
                                            <?php if(!empty($v['abnormal_time'])){?>
                                                <a class="layui-btn layui-btn-a layui-btn-xs" data-type="open" data-height="600px" data-width="800px"  data-url="<?=Url::to(['order-abnormal/follow?order_id='.$v['order_id']])?>" data-title="异常跟进" style="color: #00a0e9"><i class="layui-icon layui-icon-survey"></i></a>
                                            <?php } ?>
                                        </td>
                                        <td align="left" rowspan="<?=$v['goods_count']?>">
                                            收件人：<?= $v['buyer_name'] ?><br/>
                                            国家：<?= $v['country'] ?>
                                        </td>
                                        <td align="left" rowspan="<?=$v['goods_count']?>" style="width:250px;word-wrap: break-word;word-break: break-all;">
                                            <?php if(!empty($v['logistics_channels_name'])){ ?><span style="color: #009183">平台指定物流：<?= $v['logistics_channels_name'] ?></span><br/><?php }?>
                                            <?php if(!empty($v['order_recommended']) && in_array($v['order_status'], [\common\models\Order::ORDER_STATUS_UNCONFIRMED, \common\models\Order::ORDER_STATUS_WAIT_PURCHASE,\common\models\Order::ORDER_STATUS_APPLY_WAYBILL])) { ?><span style="color: red">推荐物流方式：<?= $v['order_recommended']['logistics_channels_desc'] ?>(￥<?= $v['order_recommended']['freight_price']?>)</span><br/><?php } ?>
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
                                            <?php if($v['first_track_no']){ ?>国内物流单号：<?= $v['first_track_no'] ?><br/><?php } ?>
                                            <?php if(!empty($v['remarks'])){?>
                                                <span style="color: red">备注：<?= $v['remarks'] ?></span><br/>
                                            <?php } ?>
                                            <?php if($v['order_status'] == \common\models\Order::ORDER_STATUS_CANCELLED){?>
                                                <span style="color: red">取消原因：<?=empty(\common\models\Order::$cancel_reason_map[$v['cancel_reason']])?'':('【'.\common\models\Order::$cancel_reason_map[$v['cancel_reason']].'】')?><?= $v['cancel_remarks'] ?></span><br/>
                                            <?php } ?>
                                            <?php if($v['order_status'] == \common\models\Order::ORDER_STATUS_REFUND){?>
                                                <span style="color: red">退款原因：<?=empty(\common\models\Order::$refund_reason_map[$v['cancel_reason']])?'':('【'.\common\models\Order::$refund_reason_map[$v['cancel_reason']].'】')?><?= $v['cancel_remarks'] ?></span><br/>
                                            <?php } ?>

                                            <?php if($v['pdelivery_status'] == 10){ ?>
                                                <span style="padding: 1px 5px;float: right;" class="layui-font-12 layui-bg-green">发</span>
                                            <?php }?>
                                            <?php if($v['integrated_logistics'] == \common\models\Order::INTEGRATED_LOGISTICS_YES){ ?>
                                                <span style="padding: 1px 5px;float: right; margin-left: 5px" class="layui-font-12 layui-bg-red">自有物流</span>
                                            <?php }?>
                                        </td>
                                        <td rowspan="<?=$v['goods_count']?>">
                                            下单时间：<?= date('Y-m-d H:i:s',$v['date'])?><br/>
                                            <?php if(!empty($v['delivery_time'])){ ?>
                                                发货时间：<?= empty($v['delivery_time'])?'':date('Y-m-d H:i:s',$v['delivery_time']) ?><br/>
                                            <?php }?>
                                            <?php if(!empty($v['abnormal_time'])){?>
                                                异常时间：<?= date('Y-m-d H:i:s',$v['abnormal_time'])?><br/>
                                            <?php } ?>
                                            <?php if(!empty($v['cancel_time']) && in_array($v['order_status'],[\common\models\Order::ORDER_STATUS_CANCELLED,\common\models\Order::ORDER_STATUS_REFUND])){
                                                echo $v['order_status'] == \common\models\Order::ORDER_STATUS_CANCELLED?'取消':'退款';
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
                <?= LinkPager::widget(['pagination' => $pages,'options' => ['class' => 'layui-box layui-laypage layui-laypage-default'],]) ?></div>
        </div>
    </div>
</div>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.7")?>
<?=$this->registerJsFile("@adminPageJs/order/lists.js?v=".time())?>
<?php
$this->registerJsFile('@adminPlugins/extend/lodop.js?v=0.2',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/xlsx.core.min.js?v=1',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export-excel.js?v=1',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export-function.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
?>