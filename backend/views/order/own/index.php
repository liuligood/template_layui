
<?php

use common\services\ShopService;
use common\services\warehousing\WarehouseService;
use yii\helpers\Url;
use common\models\RealOrder;
use yii\helpers\Html;
use yii\widgets\LinkPager;
use common\components\statics\Base;
use common\services\goods\GoodsService;
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
    .layui-table .layui-btn{
        margin-bottom: 3px;
    }
</style>
<div class="layui-fluid">
    <div class="layui-card">
<div class="layui-tab layui-tab-brief">
    <ul class="layui-tab-title">
        <li <?php if($tag == 10){?>class="layui-this" <?php }?>><a href="<?=Url::to(['order/own-index?tag=10'])?>">全部</a></li>
        <li <?php if($tag == 1){?>class="layui-this" <?php }?>><a href="<?=Url::to(['order/own-index?tag=1'])?>">未确认 <span class="span-circular-red"><?=$order_count[1]?></span></a></li>
        <li <?php if($tag == 2){?>class="layui-this" <?php }?>><a href="<?=Url::to(['order/own-index?tag=2'])?>">待处理 <span class="span-circular-red"><?=$order_count[2]?></span></a></li>
        <li <?php if($tag == 3){?>class="layui-this" <?php }?>><a href="<?=Url::to(['order/own-index?tag=3'])?>">运单号申请 <span class="span-circular-red"><?=$order_count[3]?></span></a></li>
        <li <?php if($tag == 9){?>class="layui-this" <?php }?>><a href="<?=Url::to(['order/own-index?tag=9'])?>">缺货 <span class="span-circular-red"><?=$order_count[9]?></span></a></li>
        <li <?php if($tag == 4){?>class="layui-this" <?php }?>><a href="<?=Url::to(['order/own-index?tag=4'])?>">待打包 <span class="span-circular-red"><?=$order_count[4]?></span></a></li>
        <li <?php if($tag == 5){?>class="layui-this" <?php }?>><a href="<?=Url::to(['order/own-index?tag=5'])?>">待发货 <span class="span-circular-red"><?=$order_count[5]?></span></a></li>
        <li <?php if($tag == 15){?>class="layui-this" <?php }?>><a href="<?=Url::to(['order/own-index?tag=15'])?>">剩余未发货 <span class="span-circular-red"><?=$order_count[15]?></span></a></li>
        <li <?php if($tag == 6){?>class="layui-this" <?php }?>><a href="<?=Url::to(['order/own-index?tag=6'])?>">已发货 <span class="span-circular-red"><?=$order_count[6]?></span></a></li>
        <li <?php if($tag == 11){?>class="layui-this" <?php }?>><a href="<?=Url::to(['order/own-index?tag=11'])?>">已完成</a></li>
        <li <?php if($tag == 7){?>class="layui-this" <?php }?>><a href="<?=Url::to(['order/own-index?tag=7'])?>">已取消</a></li>
        <!--<li <?php if($tag == 8){?>class="layui-this" <?php }?>><a href="<?=Url::to(['order/own-index?tag=8'])?>">异常</a></li>-->
        <li <?php if($tag == 12){?>class="layui-this" <?php }?>><a href="<?=Url::to(['order/own-index?tag=12'])?>">已退款</a></li>
    </ul>
</div>
<div class="layui-card-body">
<div class="lay-lists">
<form class="layui-form">
    <blockquote class="layui-elem-quote quoteBox">
        <div class="layui-inline">
            <a class="layui-btn" data-type="url" data-title="人工录单" data-url="<?=Url::to(['order/create'])?>" data-callback_title = "订单列表" >人工录单</a>
        </div>
    </blockquote>
</form>
<form class="layui-form">
<div class="lay-search">
    <div class="layui-inline">
        <label>订单号</label>
        <textarea name="OrderSearch[order_id]" class="layui-textarea search-con" style="height: 39px; min-height:39px"><?=$searchModel['order_id'];?></textarea>
    </div>
    <div class="layui-inline">
        平台
        <?= Html::dropDownList('OrderSearch[source]', $searchModel['source'], Base::$platform_maps,
            ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px','id'=>'platform' ]) ?>
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
            ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px' ]) ?>
    </div>
    <?php if($tag == 2){?>
    <div class="layui-inline">
        推荐物流方式
        <?= Html::dropDownList('OrderSearch[recommended_logistics_channels_id]', $searchModel['recommended_logistics_channels_id'], $recommended_logistics_channels_ids,
            ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px' ]) ?>
    </div>
    <?php } ?>
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
            ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px' ]) ?>
    </div>
    <?php if($tag == 7){?>
    <div class="layui-inline">
        取消原因
        <?= Html::dropDownList('OrderSearch[cancel_reason]', $searchModel['cancel_reason'], \common\models\Order::$cancel_reason_map,
            ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px' ]) ?>
    </div>
    <?php } ?>
    <?php if($tag == 12){?>
        <div class="layui-inline">
            退款原因
            <?= Html::dropDownList('OrderSearch[cancel_reason]', $searchModel['cancel_reason'], \common\models\Order::$refund_reason_map,
                ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px' ]) ?>
        </div>
    <?php } ?>
    <?php if(in_array($tag ,[11])){?>
        <div class="layui-inline">
            签收状态
            <?= Html::dropDownList('OrderSearch[delivered]', $searchModel['delivered'], [
                10 => '未签收',
                30 => '已签收',
            ], ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px' ]) ?>
        </div>
    <?php }?>
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
    <?php if(in_array($tag,[6,11])){?>
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
    <?php }?>
    <?php if($tag == 8){?>
    <div class="layui-inline">
        <label>异常日期</label>
        <input  class="layui-input search-con ys-date" name="OrderSearch[abnormal_start_date]" value="<?=$searchModel['abnormal_start_date'];?>"  id="abnormal_start_date" autocomplete="off">
    </div>
    <span class="layui-inline layui-vertical-20">
        -
    </span>
    <div class="layui-inline layui-vertical-20">
        <input  class="layui-input search-con ys-date" name="OrderSearch[abnormal_end_date]" value="<?=$searchModel['abnormal_end_date'];?>" id="abnormal_end_date" autocomplete="off">
    </div>
    <?php }?>
    <div class="layui-inline layui-vertical-20">
        <input type="hidden" name="tag" value="<?=$tag;?>" >
        <button class="layui-btn" data-type="search_lists">搜索</button>
        <button class="layui-btn layui-btn-normal" data-type="export_lists" data-url="<?=Url::to(['order/own-export'])?>">导出</button>
        <button class="layui-btn layui-btn-primary ys-upload" lay-data="{url: '/order/import-freight/',accept: 'file'}">运费导入</button>
        <?php if($tag == 11){ ?>
        <button class="layui-btn layui-btn-primary ys-upload" lay-data="{url: '/order/import-delivered/',accept: 'file'}">订单签收导入</button>
        <button class="layui-btn layui-btn-primary ys-upload" lay-data="{url: '/order/import-first-logistics/',accept: 'file'}">国内快递导入</button>
        <?php }?>
    </div>
</div>
</form>

    <div>
        <div class="layui-form" style="margin-top: 10px">
            <?php if(in_array($tag,[9])){?>
                <div class="layui-inline">
                    <a class="layui-btn layui-btn-sm js-refresh-out-stock" data-title="刷新库存" data-url="<?=Url::to(['order/refresh-out-stock-status'])?>">刷新库存</a>
                </div>
            <?php }?>
            <?php if($tag == 1){?>
                <div class="layui-inline">
                    <a class="layui-btn layui-btn-sm layui-btn-normal js-batch" data-title="批量确认" data-url="<?=Url::to(['order/batch-confirm'])?>" >批量确认</a>
                </div>
            <?php }?>
            <?php if(in_array($tag,[2,8])){?>
                <div class="layui-inline">
                    <a class="layui-btn layui-btn-sm layui-btn-normal js-batch" data-title="批量申请运单号"  data-url="<?=Url::to(['order/batch-transport-no'])?>" >批量申请运单号</a>
                </div>
                <div class="layui-inline">
                    <a class="layui-btn layui-btn-sm layui-btn-normal" id="js-logistics-channels" data-title="批量选择物流方式"  data-url="<?=Url::to(['order/batch-logistics-channels'])?>" >批量选择物流方式</a>
                </div>
            <?php }?>
            <?php if($tag == 3){?>
                <div class="layui-inline">
                    <a class="layui-btn layui-btn-sm layui-btn-normal js-batch" data-title="批量移入待打包" data-url="<?=Url::to(['order/batch-update-status?order_status='.\common\models\Order::ORDER_STATUS_WAIT_PRINTED_OUT_STOCK])?>">批量移入待打包</a>
                </div>
            <?php }?>
            <?php if(in_array($tag,[4,5,6,9,10,11])){?>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-sm layui-btn-normal" id="js-printed" data-url="<?=Url::to(['order/batch-printed'])?>" >批量打印面单</a>
            </div>
            <?php }?>
            <?php if(in_array($tag,[4,5,6,9,11])){?>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-sm layui-btn-normal" id="js-picking-printed" data-url="<?=Url::to(['order/batch-picking-printed'])?>" >批量打印拣货单</a>
            </div>
            <?php }?>
            <?php if(in_array($tag,[4,9])){?>
                <div class="layui-inline">
                    <a class="layui-btn layui-btn-sm layui-btn-normal js-batch" data-title="批量移入待发货" data-url="<?=Url::to(['order/batch-update-status?order_status='.\common\models\Order::ORDER_STATUS_WAIT_SHIP])?>" >批量移入待发货</a>
                </div>
            <?php }?>
            <?php if($tag == 4){?>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-sm layui-btn-warm js-batch" data-title="批量移入缺货" data-url="<?=Url::to(['order/batch-update-status?order_status='.\common\models\Order::ORDER_STATUS_WAIT_PRINTED_OUT_STOCK])?>">移入缺货</a>
            </div>
            <?php }?>
            <?php if(in_array($tag,[9,10])){?>
                <div class="layui-inline">
                    <a class="layui-btn layui-btn-sm layui-btn-normal js-batch" data-title="批量移入有货" data-url="<?=Url::to(['order/batch-update-status?order_status='.\common\models\Order::ORDER_STATUS_WAIT_PRINTED])?>">移入有货</a>
                </div>
            <?php }?>
            <?php if($tag == 5){?>
                <div class="layui-inline">
                    <a class="layui-btn layui-btn-sm layui-btn-warm js-batch" data-title="批量移入待打包" data-url="<?=Url::to(['order/batch-update-status?go_back=1&order_status='.\common\models\Order::ORDER_STATUS_WAIT_PRINTED])?>" >批量移入待打包</a>
                </div>
                <div class="layui-inline">
                    <a class="layui-btn layui-btn-sm layui-btn-normal" id="js-ship" data-url="<?=Url::to(['order/batch-ship'])?>" >批量发货</a>
                </div>
            <?php }?>
            <?php if(in_array($tag,[6,10])){?>
                <div class="layui-inline">
                    <a class="layui-btn layui-btn-sm layui-btn-warm js-batch" data-title="批量打回待发货" data-url="<?=Url::to(['order/batch-update-status?go_back=1&order_status='.\common\models\Order::ORDER_STATUS_WAIT_SHIP])?>" >打回待发货</a>
                </div>
            <?php }?>
            <?php if(in_array($tag,[3,4,5,6,9])){?>
                <div class="layui-inline">
                    <a class="layui-btn layui-btn-sm layui-btn-warm js-batch" data-title="批量打回待处理「打回待处理会清除原有的物流单号」" data-url="<?=Url::to(['order/reset-logistics'])?>" >批量打回待处理</a>
                </div>
            <?php }?>
            <?php if(!in_array($tag,[8])){?>
                <div class="layui-inline">
                    <a class="layui-btn layui-btn-sm layui-btn-danger" id="js-abnormal" data-title="批量移入异常" data-url="<?=Url::to(['order/batch-move-abnormal'])?>" >批量移入异常</a>
                </div>
            <?php }?>
            <?php if(in_array($tag,[8,10])){?>
                <!--<div class="layui-inline">
                    <a class="layui-btn layui-btn-sm layui-btn-warm js-batch" data-title="批量恢复异常「恢复异常将恢复到原来订单状态」" data-url="<?=Url::to(['order/batch-abnormal-recovery'])?>" >批量恢复</a>
                </div>-->

                <div class="layui-inline">
                    <a class="layui-btn layui-btn-sm js-batch" data-url="<?=Url::to(['order/batch-update-goods-status?status='.\common\models\Goods::GOODS_STATUS_VALID])?>" data-title="批量启用商品">批量启用商品</a>
                </div>
                <div class="layui-inline">
                    <a class="layui-btn layui-btn-sm layui-btn-danger js-batch" data-url="<?=Url::to(['order/batch-update-goods-status?status='.\common\models\Goods::GOODS_STATUS_INVALID])?>" data-title="批量禁用商品">批量禁用商品</a>
                </div>
            <?php }?>
            <?php if(in_array($tag,[3,4,5,9,10])){?>
                <div class="layui-inline">
                    <a class="layui-btn layui-btn-sm js-batch" data-title="虚拟发货" data-url="<?=Url::to(['order/batch-virtual-ship'])?>" >选中批量虚拟发货</a>
                </div>
                <?php if(in_array($tag,[9])){?>
                <div class="layui-inline">
                    <a class="layui-btn layui-btn-sm js-refresh-out-stock" data-title="虚拟发货" data-url="<?=Url::to(['order/batch-virtual-ship?all=1'])?>" >全部虚拟发货</a>
                </div>
                <?php }?>
            <?php }?>
            <?php if(in_array($tag,[10,11])){?>
                <div class="layui-inline">
                    <a class="layui-btn layui-btn-sm layui-btn-normal js-batch" data-title="批量打回已发货「打回已发货需要重新买货或占有库存」" data-url="<?=Url::to(['order/batch-update-status?go_back=1&order_status='.\common\models\Order::ORDER_STATUS_SHIPPED])?>">打回已发货</a>
                </div>
            <?php }?>

            <?php if(in_array($tag,[2,10])){?>
                <div class="layui-inline">
                    <a class="layui-btn layui-btn-sm layui-btn-normal js-batch" data-title="批量设置带电「将会变更重新设置物流方式」" data-url="<?=Url::to(['order/goods-electric?electric='.Base::ELECTRIC_SPECIAL])?>">批量设置带电</a>
                </div>
                <div class="layui-inline">
                    <a class="layui-btn layui-btn-sm layui-btn-normal js-batch" data-title="批量设置不带电「将会变更重新设置物流方式」" data-url="<?=Url::to(['order/goods-electric?electric='.Base::ELECTRIC_ORDINARY])?>">批量设置不带电</a>
                </div>
            <?php }?>
        </div>
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
                <th width="100">金额</th>
                <th>订单号</th>
                <th>来源</th>
                <th>收件人信息</th>
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
                        <?php if(!empty($goods_v['has_ov_stock'])){?>
                            <span style="float: left;" class="span-circular-red">海</span>
                        <?php }?>
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
                        </b>  x️ <span class="<?= $goods_v['goods_num'] != 1?'span-circular-red':'span-circular-grey'; ?>"><?=empty($goods_v['goods_num'])?'':$goods_v['goods_num']?></span>
                        <a class="layui-btn layui-btn-sm layui-btn-a" onclick="copyText('<?=$sku_no?>')" style="color: #00a0e9"><i class="layui-icon layui-icon-list"></i></a>
                        <br/>
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
                            <?=empty($goods_v['foam_weight'])?'':(' 泡重比:'.$goods_v['foam_weight'])?>
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
                            $is_estimate = !(new \common\components\HelperStamp(Order::$settlement_status_map))->isExistStamp($v['settlement_status'], Order::SETTLEMENT_STATUS_COST);?>
                        平台费用：<?= $v['platform_fee'] ?> <?=$v['currency']?><br/>
                        <?= $is_estimate?'预估':'' ?>成本：￥<?= $v['order_cost_price'] ?><br/>
                        <?= $is_estimate?'预估':'' ?>运费：￥<?= $v['freight_price'] ?><br/>
                        <?= $is_estimate?'预估':'' ?>利润：<span style="color: <?= $v['order_profit']>=0?'green':'red' ?>">￥<?= $v['order_profit'] ?></span>
                        <?php }?>
                    </td>
                    <td align="left" rowspan="<?=$v['goods_count']?>">
                        订单号：
                        <a class="layui-btn layui-btn-primary layui-btn-xs layui-btn-a" data-type="url"  data-url="<?=Url::to(['order/view?order_id='.$v['order_id']])?>" data-title="订单详情"><?= $v['order_id'] ?></a>
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
                            <a class="layui-btn layui-btn-xs layui-btn-a" data-type="open" data-width="600px" data-url="<?=Url::to(['order/recommended-logistics?order_id='.$v['order_id']])?>" data-title="估算运费" style="color: #00a0e9"><i class="layui-icon layui-icon-date"></i></a>
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
                                <span style="color: red">
                                    退款金额：<?=empty(\common\models\order\OrderRefund::$refund_map[$v['order_refund_type']])?'':('【'.\common\models\OrderRefund::$refund_map[$v['order_refund_type']].'】')?><?= $v['order_refund_num'] ?><br/>
                                    退款原因：<?=empty(\common\models\Order::$refund_reason_map[$v['order_refund_reason']])?'':('【'.\common\models\Order::$refund_reason_map[$v['order_refund_reason']].'】')?><?= $v['order_refund_remarks'] ?>
                                </span><br/>
                            <?php } ?>

                            <?php if ($v['remaining_shipping_time'] != 0 && !in_array($v['order_status'],[Order::ORDER_STATUS_SHIPPED,Order::ORDER_STATUS_FINISH,Order::ORDER_STATUS_CANCELLED,Order::ORDER_STATUS_REFUND])) {?>
                                剩余发货时间：<span class="remaining_shipping_time" style="color: green">
                                    <input class="remaining_time" type="hidden" value="<?=$v['remaining_shipping_time']?>">
                                    <span class="show_reaming_time"></span>
                                </span><br/>
                            <?php }?>
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
                    <td rowspan="<?=$v['goods_count']?>">
                        <?php if($v['order_status'] != \common\models\Order::ORDER_STATUS_CANCELLED && $v['order_status'] != \common\models\Order::ORDER_STATUS_REFUND ){ ?>
                            <a class="layui-btn layui-btn-normal layui-btn-xs" data-type="url"  data-url="<?=Url::to(['order/update?id='.$v['id']])?>" data-title="编辑" data-callback_title="订单列表">编辑</a>
                            <a class="layui-btn layui-btn-danger layui-btn-xs" data-type="open" data-height="450px" data-url="<?=Url::to(['order/cancel?order_id='.$v['order_id']])?>"  data-title="取消订单">取消订单</a>
                        <?php }else{?>
                            <a class="layui-btn layui-btn-normal layui-btn-xs" data-type="url"  data-url="<?=Url::to(['order/again?re_id='.$v['id']])?>" data-title="编辑" data-callback_title="订单列表">重新下单</a>
                        <?php }?>
                        <?php if($v['order_status'] == \common\models\Order::ORDER_STATUS_WAIT_PURCHASE){ ?>
                            <a class="layui-btn layui-btn-xs" data-type="open" data-url="<?=Url::to(['order/input-logistics?order_id='.$v['order_id']])?>" data-height="500px" data-title="录入运单号" data-callback_title="订单列表">录入运单号</a>
                        <?php }?>
                        <?php if($v['order_status'] == \common\models\Order::ORDER_STATUS_WAIT_PRINTED || $v['order_status'] == \common\models\Order::ORDER_STATUS_WAIT_SHIP || $v['order_status'] == \common\models\Order::ORDER_STATUS_SHIPPED || $v['order_status'] == \common\models\Order::ORDER_STATUS_FINISH){ ?>
                            <a class="layui-btn layui-btn-xs" data-type="open"  data-url="<?=Url::to(['order/input-logistics?order_id='.$v['order_id'].'&gen_logistics=1'])?>" data-height="500px" data-title="更换运单号" data-callback_title="订单列表">更换运单号</a>
                        <?php }?>
                        <?php if($v['order_status'] == \common\models\Order::ORDER_STATUS_SHIPPED || $v['order_status'] == \common\models\Order::ORDER_STATUS_FINISH){ ?>
                            <a class="layui-btn layui-btn-normal layui-btn-xs" data-type="url"  data-url="<?=Url::to(['order/again?re_id='.$v['id']])?>" data-title="编辑" data-callback_title="订单列表">重新下单</a>
                        <?php }?>
                        <?php if($v['order_status'] == \common\models\Order::ORDER_STATUS_FINISH){ ?>
                        <a class="layui-btn layui-btn-danger layui-btn-xs" data-type="open" data-height="450px" data-url="<?=Url::to(['order/refund?order_id='.$v['order_id']])?>"  data-title="订单退款">订单退款</a>
                        <?php }?>

                        <?php if(in_array($v['order_status'],[\common\models\Order::ORDER_STATUS_WAIT_PRINTED_OUT_STOCK,\common\models\Order::ORDER_STATUS_WAIT_PRINTED,\common\models\Order::ORDER_STATUS_WAIT_SHIP,\common\models\Order::ORDER_STATUS_SHIPPED,\common\models\Order::ORDER_STATUS_FINISH])){ ?>
                        <a class="layui-btn layui-btn-xs layui-btn-primary js-direct-printed" data-url="<?=Url::to(['order/direct-printed?order_id='.$v['order_id']])?>">打印面单</a>
                        <?php }?>
                        <a class="layui-btn layui-btn-warm layui-btn-xs" data-type="open" data-height="370px" data-width="800px" data-url="<?=Url::to(['order/remarks?order_id='.$v['order_id']])?>"  data-title="备注">备注</a>
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
<script type="text/javascript">
    function copyText(sku_no) {
        var copyUrl= sku_no;
        var oInput = document.createElement('input');     //创建一个隐藏input（重要！）
        oInput.value = copyUrl;    //赋值
        document.body.appendChild(oInput);
        oInput.select(); // 选择对象
        document.execCommand("Copy"); // 执行浏览器复制命令
        oInput.className = 'oInput';
        oInput.style.display='none';
        layer.msg("复制成功",{icon: 1});
    }
</script>
</div>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.8")?>
<?=$this->registerJsFile("@adminPageJs/order/lists.js?v=".time())?>
<?php
    $this->registerJsFile('@adminPlugins/extend/lodop.js?v=0.2',['depends'=>'yii\web\JqueryAsset']);
    $this->registerJsFile('@adminPlugins/export/xlsx.core.min.js?v=1',['depends'=>'yii\web\JqueryAsset']);
    $this->registerJsFile('@adminPlugins/export/export-excel.js?v=1',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export-function.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
?>