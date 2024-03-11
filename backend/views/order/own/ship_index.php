
<?php

use common\services\ShopService;
use yii\helpers\Url;
use common\models\Order;
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
<div class="layui-tab layui-tab-brief">
    <ul class="layui-tab-title">
        <li <?php if($tag == 10){?>class="layui-this" <?php }?>><a href="<?=Url::to(['order/own-ship-index?tag=10'])?>">全部</a></li>
        <li <?php if($tag == 1){?>class="layui-this" <?php }?>><a href="<?=Url::to(['order/own-ship-index?tag=1'])?>">未发货</a></li>
        <li <?php if($tag == 2){?>class="layui-this" <?php }?>><a href="<?=Url::to(['order/own-ship-index?tag=2'])?>">已发货</a></li>
        <li <?php if($tag == 3){?>class="layui-this" <?php }?>><a href="<?=Url::to(['order/own-ship-index?tag=3'])?>">退货</a></li>
        <li <?php if($tag == 4){?>class="layui-this" <?php }?>><a href="<?=Url::to(['order/own-ship-index?tag=4'])?>">退款</a></li>
        <li <?php if($tag == 5){?>class="layui-this" <?php }?>><a href="<?=Url::to(['order/own-ship-index?tag=5'])?>">换货</a></li>
    </ul>
</div>
<div class="layui-card-body">
<div class="lay-lists">
<form class="layui-form">
<div class="lay-search" style="padding-left: 10px">
    <div class="layui-inline">
        <label>订单号</label>         
        <input class="layui-input search-con" name="OrderSearch[order_id]" value="<?=$searchModel['order_id'];?>" autocomplete="off">
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
        <input class="layui-input search-con" name="OrderSearch[relation_no]" value="<?=$searchModel['relation_no'];?>"  autocomplete="off">
    </div>

    <div class="layui-inline">
        物流方式
        <?= Html::dropDownList('OrderSearch[logistics_channels_id]', $searchModel['logistics_channels_id'], $logistics_channels_ids,
            ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px' ]) ?>
    </div>
    <div class="layui-inline">
        <label>物流单号</label>
        <input class="layui-input search-con" name="OrderSearch[track_no]" value="<?=$searchModel['track_no'];?>"  autocomplete="off">
    </div>

    <div class="layui-inline">
        预计到货时间
        <input class="layui-input search-con ys-date" name="OrderSearch[start_arrival_time]" value="<?=$searchModel['start_arrival_time'];?>" autocomplete="off" id="start_arrival_time" >
    </div>
    <span class="layui-inline layui-vertical-20">
        -
    </span>
    <div class="layui-inline layui-vertical-20">
        <input class="layui-input search-con ys-date" name="OrderSearch[end_arrival_time]" value="<?=$searchModel['end_arrival_time'];?>" autocomplete="off" id="end_arrival_time" >
    </div>

    <?php if(in_array($tag,[2])){?>
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
    <?php }?>

    <div class="layui-inline layui-vertical-20">
        <input type="hidden" name="tag" value="<?=$tag;?>" >
        <button class="layui-btn" data-type="search_lists">搜索</button>

        <!--<button class="layui-btn layui-btn-normal" data-type="export_lists" data-url="<?=Url::to(['order/export'])?>">导出</button>-->
    </div>
</div>
</form>
</div>

    <div>
        <?php if($tag == 1){?>
            <div class="layui-form" style="padding-left: 10px;margin-top: 10px">
                <div class="layui-inline">
                    <a class="layui-btn layui-btn-sm layui-btn-normal" id="js-ship" data-url="<?=Url::to(['order/batch-ship'])?>" >批量发货</a>
                </div>
            </div>
        <?php }?>
        <?php
        $startCount = (0 == $pages->totalCount) ? 0 : $pages->offset + 1;
        $endCount = ($pages->page + 1) * $pages->pageSize;
        $endCount = ($endCount > $pages->totalCount) ? $pages->totalCount : $endCount;
        ?>
        <div class="summary" style="margin-top: 10px;">
            第<b><?= $startCount ?>-<?= $endCount ?></b>条，共<b><?= $pages->totalCount ?></b>条数据
        </div>
        <div class="layui-form" >
        <table class="layui-table" style="text-align: center">
            <thead>
            <tr>
                <th><input type="checkbox" lay-filter="select_all" id="select_all" name="select_all" lay-skin="primary" title=""></th>
                <th style="width: 60px">商品图片</th>
                <th>商品信息</th>
                <th style="width: 100px;">金额</th>
                <th>订单号</th>
                <th>来源</th>
                <th>物流信息</th>
                <th>时间</th>
                <?php if(in_array($tag,[4,5,6])){?>
                <th>备注</th>
                <?php }?>
                <th>状态</th>
                <th><span>操作</span></th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($list)): ?>
                <tr>
                    <td colspan="18">无数据</td>
                </tr>
            <?php else: foreach ($list as $k => $v):
                $i = 0;?>
                <?php foreach ($v['goods'] as $goods_k => $goods_v):
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
                        <b><?=empty($goods_v['platform_asin'])?'':$goods_v['platform_asin']?></b>  x️ <span class="<?= $goods_v['goods_num'] != 1?'span-circular-red':'span-circular-grey'; ?>"><?=empty($goods_v['goods_num'])?'':$goods_v['goods_num']?></span><br/>
                        <span class="span-goode-name"><?=empty($goods_v['goods_name'])?'':$goods_v['goods_name']?></span><br/>
                        <?=empty($goods_v['platform_type'])?'':Base::$buy_platform_maps[$goods_v['platform_type']]?>
                    </td>
                    <?php if($i == 1):?>
                    <td align="left" rowspan="<?=$v['goods_count']?>">
                        金额：<?= $v['order_income_price'] ?><br/>
                        成本：<?= $v['order_cost_price'] ?><br/>
                        利润：<?= $v['order_profit'] ?>
                    </td>
                    <td align="left" rowspan="<?=$v['goods_count']?>">
                        <span class="lay-lists"> 订单号：
                        <a class="layui-btn layui-btn-primary layui-btn-xs layui-btn-a" data-type="url"  data-url="<?=Url::to(['order/ship?id='.$v['id']])?>" data-title="发货" data-callback_title="订单发货处理"><?= $v['order_id'] ?></a>
                         </span><br/>
                        销售单号：<?= $v['relation_no'] ?><br/>
                        <?php if(in_array($tag,[3])){?>
                        亚马逊订单号：<?=empty($goods_v['buy_relation_no'])?'':$goods_v['buy_relation_no']?>
                        <?php }?>
                    </td>
                    <td align="left" rowspan="<?=$v['goods_count']?>">
                        <?= Base::$order_source_maps[$v['source']] ?><br/>
                        <?= empty($v['shop_name'])?'':$v['shop_name'] ?>
                    </td>
                    <td align="left" rowspan="<?=$v['goods_count']?>">
                        物流方式：<?= $v['logistics_channels_desc'] ?><br/>
                        物流单号：<?= $v['track_no'] ?>
                    </td>
                    <td align="left" rowspan="<?=$v['goods_count']?>">
                        下单时间：<?= date('Y-m-d H:i:s',$v['date'])?><br/>
                        <?php if(in_array($tag,[2])){?>
                        发货时间：<?= empty($v['delivery_time'])?'':date('Y-m-d',$v['delivery_time']) ?>
                        <?php }?>
                    </td>
                    <?php if(in_array($tag,[4,5,6])){?>
                    <td rowspan="<?=$v['goods_count']?>"><?= $v['remarks'] ?></td>
                    <?php }?>
                    <td align="left" rowspan="<?=$v['goods_count']?>">
                        发货状态：<?= Order::$delivery_status_map[$v['delivery_status']] ?><br/>
                        售后状态：<?= Order::$after_sale_status_map[$v['after_sale_status']] ?>
                    </td>
                    <td rowspan="<?=$v['goods_count']?>">
                        <span class="lay-lists">

                        <a class="layui-btn layui-btn-normal layui-btn-xs" data-type="url"  data-url="<?=Url::to(['order/ship?id='.$v['id']])?>" data-title="发货" data-callback_title="订单发货处理">发货</a>
                        </span>

                        <?php if(in_array($tag,[2,3])){?>
                        <a class="layui-btn layui-btn-green layui-btn-xs" href="<?=Url::to(['order/invoice?order_id='.$v['order_id']])?>">下载发票</a>
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
    </div></div></div>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.4")?>
<?=$this->registerJsFile("@adminPageJs/order/lists.js?v=".time())?>
<?php
    $this->registerJsFile('@adminPlugins/export/xlsx.core.min.js?v=1',['depends'=>'yii\web\JqueryAsset']);
    $this->registerJsFile('@adminPlugins/export/export-excel.js?v=1',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export-function.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
?>