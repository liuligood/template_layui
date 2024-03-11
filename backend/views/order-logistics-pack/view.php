<?php

use common\models\TransportProviders;
use common\services\warehousing\WarehouseService;
use yii\helpers\Url;
use common\models\User;
use common\models\OrderLogisticsPack;
use common\models\Order;
use common\services\purchase\PurchaseOrderService;
use function GuzzleHttp\json_encode;
use common\models\OrderLogisticsPackAssociation;
use common\components\statics\Base;
use yii\widgets\LinkPager;
use common\services\goods\GoodsService;

$pack_id = new OrderLogisticsPackAssociation();
$order = new OrderLogisticsPackAssociation();
?>
<style>
    html {
        background: #fff;
    }
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
</style>

<div class="layui-col-md9 layui-col-xs12" style="margin:0 ">
    <div class="lay-lists" style="padding:20px;">  
            <div class="layui-inline">
                <a class="layui-btn layui-btn-normal layui-btn-sm" data-ignore="ignore" href="<?=Url::to(['order-logistics-pack/update?id='.$info['id']])?>">订单包裹编辑</a>
            </div>
        </div>
    </div>
    
<table class="layui-table" >
    <tbody>
    <tr>
        <td class="layui-table-th">发货时间</td>
        <td colspan="5">
        <?php
        $value = Yii::$app->formatter->asDate($info['ship_date']);
        ?>
        <?=$value?>
        </td>
    </tr>
    <tr>
        <td class="layui-table-th">快递单号</td>
        <td colspan="5"><?=$info['tracking_number']?></td>
    </tr>
    <tr>	
        <td class="layui-table-th">物流渠道</td>
        <td colspan="5">
            <?php $transport = TransportProviders::getTransportName();?>
            <?=empty($transport[$info['channels_type']])?'':$transport[$info['channels_type']]?>
        </td>
    </tr>
    <tr>
        <td class="layui-table-th">快递商</td>
        <td colspan="5">
        <?php
        $channels = PurchaseOrderService::getLogisticsChannels();
        ?>
        <?=empty($channels[$info['courier']])?'':$channels[$info['courier']]?>
        </td>
    </tr>
    <tr>
        <td class="layui-table-th">件数</td>
        <td colspan="5"><?=$info['quantity']?></td>
    </tr>
    <tr>
        <td class="layui-table-th">重量</td>
        <td colspan="5"><?=$info['weight']?></td>
    </tr>
    <tr>
        <td class="layui-table-th">操作者</td>
        <td colspan="5"><?=User::getInfoNickname($info['admin_id'])?></td>
    </tr>
    <tr>
        <td class="layui-table-th">备注</td>
        <td colspan="5"><?=$info['remarks']?></td>
    </tr>
    </tbody>
</table>

<form class="layui-form layui-row" id="delete" action="<?=Url::to(['order-logistics-pack-association/delete?id='.$info['id']])?>">
        <div class="layui-col-md9 layui-col-xs12" style="margin:0">
    		<div class="layui-inline">
                <a class="layui-btn  layui-btn-sm" data-ignore="ignore" href="<?=Url::to(['order-logistics-pack-association/create?id='.$info['id']])?>">添加订单</a>
            </div>
   			 <input type="hidden" name="shop_id" value="<?=$info['id']?>">
    		 <button class="layui-btn layui-btn-sm layui-btn-danger" lay-submit="" lay-filter="form" data-form="delete">批量删除订单</button>
    </div>
    <br>
    <br>
    <br>
    <div class="lay-lists">
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
                <th><input type="checkbox" lay-filter="select_all" id="select_all" name="select_all" lay-skin="primary" title=""></th>
                <th style="width: 60px">商品图片</th>
                <th>商品信息</th>
                <th>订单号</th>
                <th>来源</th>
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
                    <td rowspan="<?=$v['goods_count']?>"><input type="checkbox" class="select_order" name="logistics[]" value="<?=$v['order_id']?>" lay-skin="primary" title=""></td>
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

                        <td align="left" rowspan="<?=$v['goods_count']?>" style="width:250px;word-wrap: break-word;word-break: break-all;">
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

    <div class="lay-lists">
    <?= LinkPager::widget(['pagination' => $pages,'options' => ['class' => 'layui-box layui-laypage layui-laypage-default'],]) ?></div>

	</form>


<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.7")?>
<?=$this->registerJsFile("@adminPageJs/order/lists.js?v=".time())?>
<?php
    $this->registerJsFile('@adminPlugins/extend/lodop.js?v=0.1.1',['depends'=>'yii\web\JqueryAsset']);
    $this->registerJsFile('@adminPlugins/export/xlsx.core.min.js?v=1',['depends'=>'yii\web\JqueryAsset']);
    $this->registerJsFile('@adminPlugins/export/export-excel.js?v=1',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export-function.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
?>
<?=$this->registerJsFile("@adminPageJs/order-logistics-pack-association/delete.js?v=0.0.7")?>





