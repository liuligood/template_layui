
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
    }
    .span-goode-name{
        color:#a0a3a6;
        font-size: 13px;
    }
</style>
<div class="layui-fluid">
    <div class="layui-card">
<div class="layui-tab layui-tab-brief">
    <ul class="layui-tab-title">
        <li <?php if($tag == 10){?>class="layui-this" <?php }?>><a href="<?=Url::to(['purchase-order/index?tag=10'])?>">全部</a></li>
        <!--<li <?php if($tag == 1){?>class="layui-this" <?php }?>><a href="<?=Url::to(['purchase-order/index?tag=1'])?>">未确认</a></li>-->
        <li <?php if($tag == 2){?>class="layui-this" <?php }?>><a href="<?=Url::to(['purchase-order/index?tag=2'])?>">待发货 <span class="span-circular-red"><?=$purchase_order_count[2]?></span></a></li>
        <li <?php if($tag == 3){?>class="layui-this" <?php }?>><a href="<?=Url::to(['purchase-order/index?tag=3'])?>">已发货 | 安骏 <span class="span-circular-red"><?=$purchase_order_count[3]?></span></a></li>
        <li <?php if($tag == 6){?>class="layui-this" <?php }?>><a href="<?=Url::to(['purchase-order/index?tag=6'])?>">已发货 <span class="span-circular-red"><?=$purchase_order_count[6]?></span></a></li>
        <li <?php if($tag == 4){?>class="layui-this" <?php }?>><a href="<?=Url::to(['purchase-order/index?tag=4'])?>">已完成</a></li>
        <li <?php if($tag == 7){?>class="layui-this" <?php }?>><a href="<?=Url::to(['purchase-order/index?tag=7'])?>">未出发</a></li>
        <li <?php if($tag == 5){?>class="layui-this" <?php }?>><a href="<?=Url::to(['purchase-order/index?tag=5'])?>">已取消</a></li>
    </ul>
</div>
<div class="lay-lists">
<form class="layui-form">
    <blockquote class="layui-elem-quote quoteBox">
        <div class="layui-inline">
            <a class="layui-btn" data-type="url" data-title="新增采购单" data-url="<?=Url::to(['purchase-order/create'])?>" data-callback_title = "采购订单列表" >新增采购单</a>
        </div>
    </blockquote>
</form>
    <div class="layui-card-body">
<form class="layui-form">
<div class="lay-search">
    <div class="layui-inline">
        <label>采购单号</label>
        <input class="layui-input search-con" name="PurchaseOrderSearch[order_id]" value="<?=$searchModel['order_id'];?>" autocomplete="off">
    </div>
    <div class="layui-inline">
        供应商
        <?= Html::dropDownList('PurchaseOrderSearch[source]', $searchModel['source'], Base::$purchase_source_maps,
            ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px' ]) ?>
    </div>
    <div class="layui-inline">
        <label>供应商单号</label>
        <textarea name="PurchaseOrderSearch[relation_no]" class="layui-textarea search-con" style="height: 39px; min-height:39px"><?=$searchModel['relation_no'];?></textarea>
    </div>
    <div class="layui-inline">
        <label>SKU</label>
        <input class="layui-input search-con" name="PurchaseOrderSearch[sku_no]" value="<?=$searchModel['sku_no'];?>"  autocomplete="off">
    </div>
    <div class="layui-inline">
        <label>商品名称</label>
        <input class="layui-input search-con" name="PurchaseOrderSearch[goods_name]" value="<?=htmlentities($searchModel['goods_name'], ENT_COMPAT);?>"  autocomplete="off">
    </div>
    <?php if(!empty($sub_status_map)){ ?>
        <div class="layui-inline">
            状态
            <?= Html::dropDownList('PurchaseOrderSearch[order_sub_status]', $searchModel['order_sub_status'],$sub_status_map,
                ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px' ]) ?>
        </div>
    <?php } ?>
    <?php if($tag != 3 && $tag != 6){?>
    <div class="layui-inline">
        仓库
        <?= Html::dropDownList('PurchaseOrderSearch[warehouse]', $searchModel['warehouse'], WarehouseService::getPurchaseWarehouse(),
            ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px' ]) ?>
    </div>
    <?php } ?>
    <?php if($tag != 1 && $tag != 2){?>
    <div class="layui-inline">
        <label>物流单号</label>
        <textarea name="PurchaseOrderSearch[track_no]" id="sel_logistics_no" class="layui-textarea search-con" style="height: 39px; min-height:39px"><?=$searchModel['track_no'];?></textarea>
    </div>
    <?php } ?>
    <div class="layui-inline">
        <label>下单日期</label>
        <input  class="layui-input search-con ys-date" name="PurchaseOrderSearch[start_date]" value="<?=$searchModel['start_date'];?>"  id="start_date" autocomplete="off">
    </div>
    <span class="layui-inline layui-vertical-20">
        -
    </span>
    <div class="layui-inline layui-vertical-20">
        <input  class="layui-input search-con ys-date" name="PurchaseOrderSearch[end_date]" value="<?=$searchModel['end_date'];?>" id="end_date" autocomplete="off">
    </div>
    <?php if($tag != 1 && $tag != 2 && $tag != 5){?>
    <div class="layui-inline">
        <label>发货时间</label>
        <input  class="layui-input search-con ys-datetime" name="PurchaseOrderSearch[start_ship_time]" value="<?=$searchModel['start_ship_time'];?>"  id="start_ship_date" autocomplete="off">
    </div>
    <span class="layui-inline layui-vertical-20">
        -
    </span>
    <div class="layui-inline layui-vertical-20">
        <input  class="layui-input search-con ys-datetime" name="PurchaseOrderSearch[end_ship_time]" value="<?=$searchModel['end_ship_time'];?>" id="end_ship_date" autocomplete="off">
    </div>
    <?php } ?>
    <div class="layui-inline">
        采购员
        <?= Html::dropDownList('PurchaseOrderSearch[admin_id]', $searchModel['admin_id'], $admin_arr,
            ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px' ]) ?>
    </div>
    <div class="layui-inline layui-vertical-20">
        <input type="hidden" name="tag" value="<?=$tag;?>" >
        <button class="layui-btn" data-ignore="ignore" data-type="search_lists" id="sel_btn">搜索</button>
        <?php if($tag == 3 || $tag == 4 || $tag == 6){ ?>
        <button class="layui-btn layui-btn-normal" data-type="export_lists" data-url="<?=Url::to(['purchase-order/export?tag='.$tag])?>">导出物流单号</button>
        <?php }?>
    </div>
</div>
</form>

    <div>
        <div class="layui-form" style="padding-left: 10px;margin-top: 10px">
            <?php if($tag == 3 || $tag == 6){?>
                <div class="layui-inline">
                    <a class="layui-btn layui-btn-sm layui-btn-normal js-batch" data-title="批量到货" data-url="<?=Url::to(['purchase-order/batch-received'])?>" >批量到货</a>
                </div>
                <div class="layui-inline">
                    <a class="layui-btn layui-btn-sm layui-btn-normal js-batch" data-title="全部到货" data-url="<?=Url::to(['purchase-order/batch-received?all=1&tag='.$tag])?>" >全部到货</a>
                </div>
            <?php }?>
            <?php if($tag == 7){?>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-sm layui-btn-normal js-batch" data-title="标记已出发" data-url="<?=Url::to(['purchase-order/batch-on-way'])?>" >标记已出发</a>
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
                        <a class="layui-btn layui-btn-xs layui-btn-a" data-type="url" data-url="<?=Url::to(['order/own-index?search=1&OrderSearch%5Bplatform_asin%5D='.$sku_no.'&tag=10'])?>" data-title="订单信息" style="color: #00a0e9"><i class="layui-icon layui-icon-template"></i></a>
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
                        仓库：<?= WarehouseService::getPurchaseWarehouse($v['warehouse']) ?>
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
                        <?php if($v['order_status'] != \common\models\purchase\PurchaseOrder::ORDER_STATUS_CANCELLED){ ?>
                            <?php if($v['order_status'] == \common\models\purchase\PurchaseOrder::ORDER_STATUS_SHIPPED){ ?>
                                <a class="layui-btn layui-btn-normal layui-btn-xs" data-type="open" data-width="1000px" data-height="600px" data-url="<?=Url::to(['purchase-order/arrival?order_id='.$v['order_id']])?>" data-title="到货">到货</a>
                            <?php }?>
                            <a class="layui-btn layui-btn-normal layui-btn-xs" data-type="url"  data-url="<?=Url::to(['purchase-order/update?id='.$v['id']])?>" data-title="编辑" data-callback_title="订单列表">编辑</a>
                        <?php if($v['order_sub_status'] == \common\models\purchase\PurchaseOrder::ORDER_SUB_STATUS_SHIPPED_PART){ ?>
                            <a class="layui-btn layui-btn-danger layui-btn-xs" data-type="operating" data-title="终止采购「终止采购将完成采购订单，并清空在途库存」" data-url="<?=Url::to(['purchase-order/finish?order_id='.$v['order_id']])?>">终止剩余采购</a>
                        <?php } else {?>
                            <a class="layui-btn layui-btn-danger layui-btn-xs" data-type="cancel" data-url="<?=Url::to(['purchase-order/cancel?order_id='.$v['order_id']])?>">取消订单</a>
                        <?php }?>
                        <?php }else{?>
                            <a class="layui-btn layui-btn-normal layui-btn-xs" data-type="url"  data-url="<?=Url::to(['purchase-order/again?re_id='.$v['id']])?>" data-title="编辑" data-callback_title="订单列表">重新下单</a>
                        <?php }?>
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
<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=0.1.0")?>
<?=$this->registerJsFile("@adminPageJs/purchase_order/lists.js?v=".time())?>
<?php
    $this->registerJsFile('@adminPlugins/export/xlsx.core.min.js?v=1',['depends'=>'yii\web\JqueryAsset']);
    $this->registerJsFile('@adminPlugins/export/export-excel.js?v=1',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export-function.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
?>