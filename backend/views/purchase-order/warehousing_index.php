
<?php

use common\services\warehousing\WarehouseService;
use yii\helpers\Url;
use yii\helpers\Html;
use yii\widgets\LinkPager;
use common\components\statics\Base;
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
    }
    .span-goode-name{
        color:#a0a3a6;
        font-size: 13px;
    }
</style>
<div class="layui-fluid">
    <div class="layui-card">
<div class="lay-lists">
<form class="layui-form">
    <blockquote class="layui-elem-quote quoteBox">
        物流单号
        <div class="layui-inline " style="width: 250px">
            <input class="layui-input search-con" name="logistics_no" id="logistics_no" value="" autocomplete="off"
                   style="height: 50px;line-height: 50px;font-size: 20px;font-weight: bold">
        </div>
        <div class="layui-inline">
            <a class="layui-btn layui-btn-normal" data-width="1000px" data-height="600px" data-title="到货" data-url="<?=Url::to(['purchase-order/arrival?logistics_no='])?>" id="logistics_sign" >提交</a>
        </div>
    </blockquote>
</form>
    <div class="layui-card-body">
<form class="layui-form">
<div class="lay-search" style="display: none">
    <div class="layui-inline">
        <label>物流单号</label>
        <input class="layui-input search-con" id="sel_logistics_no" name="PurchaseOrderSearch[track_no]" value="<?=$searchModel['track_no'];?>"  autocomplete="off">
    </div>
    <div class="layui-inline layui-vertical-20">
        <input type="hidden" name="tag" value="<?=$tag;?>" >
        <input type="hidden" name="track_no" value="<?=$track_no?>">
        <button class="layui-btn" data-ignore="ignore" data-type="search_lists" id="sel_btn">搜索</button>
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
        </div>
        <div class="layui-form">
        <table class="layui-table" style="text-align: center">
            <thead>
            <tr>
                <th style="width: 60px">商品图片</th>
                <th>商品信息</th>
                <th width="120">金额</th>
                <th>采购单号</th>
                <th>物流信息</th>
                <th>时间</th>
                <th><span>操作</span></th>
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
                $sku_no = empty($goods_v['sku_no'])?'':$goods_v['sku_no'];
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
                        <a class="layui-btn layui-btn-xs layui-btn-a" data-type="url" data-url="<?=Url::to(['order/own-index?OrderSearch%5Bplatform_asin%5D='.$sku_no.'&tag=10'])?>" data-title="订单信息" style="color: #00a0e9"><i class="layui-icon layui-icon-template"></i></a>
                        <b><?=$sku_no?></b>  x️ <span class="<?= $goods_v['goods_num'] != 1?'span-circular-red':'span-circular-grey'; ?>"><?=empty($goods_v['goods_num'])?'':$goods_v['goods_num']?></span><br/>
                        <span class="span-goode-name"><?=empty($goods_v['goods_name'])?'':$goods_v['goods_name']?></span><br/>
                    </td>
                    <?php if($i == 1):?>
                    <td align="left" rowspan="<?=$v['goods_count']?>">
                        商品金额：<?= $v['goods_price'] ?><br/>
                        运费：<?= $v['freight_price'] ?><br/>
                        其他费用：<?= $v['other_price'] ?><br/>
                        总计：<?= $v['order_price'] ?><br/>
                        <span style="color: green">
                        <?= $v['goods_finish_num'] ==0?'未到货':($v['goods_num'] - $v['goods_finish_num'] >0?'部分到货':'全部到货') ?> [ <?= $v['goods_finish_num'] ?> / <?= $v['goods_num'] ?> ]
                        </span>
                    </td>
                    <td align="left" rowspan="<?=$v['goods_count']?>">
                        采购单号：
                        <?php if($v['order_status'] != \common\models\purchase\PurchaseOrder::ORDER_STATUS_CANCELLED){ ?>
                        <a class="layui-btn layui-btn-primary layui-btn-xs layui-btn-a" data-type="url"  data-url="<?=Url::to(['purchase-order/update?id='.$v['id']])?>" data-title="编辑" data-callback_title="订单列表"><?= $v['order_id'] ?></a>
                        <?php }else{?>
                        <?= $v['order_id'] ?>
                        <?php }?>
                        <br/>
                        供应商单号：<?= $v['relation_no'] ?><br/>
                        供应商：<?= Base::$purchase_source_maps[$v['source']] ?><br/>
                        仓库：<?= WarehouseService::$warehouse_map[$v['warehouse']] ?>
                        <span style="padding: 1px 5px;float: right" class="layui-font-12 layui-bg-orange"><?= \common\models\purchase\PurchaseOrder::$order_start_map[$v['order_status']] ?></span>
                    </td>
                        <td align="left" rowspan="<?=$v['goods_count']?>">
                            <?php if($v['logistics_channels_desc']){ ?>物流方式：<?= $v['logistics_channels_desc'] ?><?php } ?><br/>
                            <?php if($v['track_no']){ ?>物流单号：<a class="layui-btn layui-btn-xs layui-btn-a" data-type="open" data-url="<?=Url::to(['purchase-order/logistics-trace?order_id='.$v['order_id']])?>" data-title="物流跟踪信息" style="color: #00a0e9"><?= $v['track_no'] ?></a> <br/><?php } ?>
                        </td>
                    <td align="left" rowspan="<?=$v['goods_count']?>">
                        下单时间：<?= date('Y-m-d H:i:s',$v['add_time'])?><br/>
                        采购时间：<?= date('Y-m-d H:i:s',$v['date'])?><br/>
                        <?php if($v['ship_time']){ ?>发货时间：<?= date('Y-m-d H:i',$v['ship_time'])?><br/><?php }?>
                        <?php if($v['arrival_time']){ ?>到货时间：<?= date('Y-m-d H:i',$v['arrival_time'])?><br/><?php }?>
                        采购员：<?=$v['admin_name'] ?>
                    </td>
                    <td rowspan="<?=$v['goods_count']?>">

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
</div>
<script>
    const tableName="";
</script>
<script>
        var onFocus = document.getElementById('logistics_no');
        onFocus.focus();
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=0.1.0")?>
<?=$this->registerJsFile("@adminPageJs/purchase_order/lists.js?v=".time())?>
<?php
    $this->registerJsFile('@adminPlugins/export/xlsx.core.min.js?v=1',['depends'=>'yii\web\JqueryAsset']);
    $this->registerJsFile('@adminPlugins/export/export-excel.js?v=1',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export-function.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
?>