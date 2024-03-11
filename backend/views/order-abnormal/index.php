
<?php

use common\services\ShopService;
use yii\helpers\Url;
use common\models\RealOrder;
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
    .span-goods-name{
        color:#a0a3a6;
        font-size: 13px;
    }
    .layui-table td {
        padding: 5px 8px;
    }
    .layui-table img{
        max-width: 80px;
    }
</style>
<div class="layui-fluid">
    <div class="layui-card">
<div class="layui-tab layui-tab-brief">
    <ul class="layui-tab-title">
        <li <?php if($tag == 1){?>class="layui-this" <?php }?>><a href="<?=Url::to(['order-abnormal/index?tag=1'])?>">待跟进 <span class="span-circular-red"><?=$order_abnormal_count[1]?></span></a></li>
        <li <?php if($tag == 3){?>class="layui-this" <?php }?>><a href="<?=Url::to(['order-abnormal/index?tag=3'])?>">待重派 <span class="span-circular-red"><?=$order_abnormal_count[3]?></span></a></li>
        <li <?php if($tag == 4){?>class="layui-this" <?php }?>><a href="<?=Url::to(['order-abnormal/index?tag=4'])?>">物流商退件 <span class="span-circular-red"><?=$order_abnormal_count[4]?></span></a></li>
        <li <?php if($tag == 2){?>class="layui-this" <?php }?>><a href="<?=Url::to(['order-abnormal/index?tag=2'])?>">已关闭</a></li>
    </ul>
</div>
<div class="layui-card-body">
<div class="lay-lists">
<form class="layui-form">
<div class="lay-search">
    <div class="layui-inline">
        <label>订单号</label>         
        <input class="layui-input search-con" name="OrderAbnormalSearch[order_id]" value="<?=$searchModel['order_id'];?>" autocomplete="off">
    </div>
    <div class="layui-inline">
        异常类型
        <?= Html::dropDownList('OrderAbnormalSearch[abnormal_type]', $searchModel['abnormal_type'], \common\services\order\OrderAbnormalService::$abnormal_type_maps,
            ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px' ]) ?>
    </div>
    <div class="layui-inline">
        异常状态
        <?= Html::dropDownList('OrderAbnormalSearch[abnormal_status]', $searchModel['abnormal_status'],
            \common\models\order\OrderAbnormal::$order_abnormal_status_map,
            ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px' ]) ?>
    </div>
    <div class="layui-inline">
        <label>销售单号</label>
        <textarea class="layui-textarea search-con" name="OrderAbnormalSearch[relation_no]" autocomplete="off" style="height: 39px;min-height: 39px"><?=$searchModel['relation_no'];?></textarea>
    </div>
    <div class="layui-inline">
        <label>SKU</label>
        <input class="layui-input search-con" name="OrderAbnormalSearch[platform_asin]" value="<?=$searchModel['platform_asin'];?>"  autocomplete="off">
    </div>
    <div class="layui-inline">
        平台
        <?= Html::dropDownList('OrderAbnormalSearch[source]', $searchModel['source'], Base::$platform_maps,
            ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px','id'=>'platform' ]) ?>
    </div>
    <div class="layui-inline">
        店铺
        <?= \backend\widgets\LinkageDropDownList::widget(['id' => 'shop','parent_id'=>'platform','name'=>'OrderAbnormalSearch[shop_id]','select'=>$searchModel['shop_id'],'option'=> ShopService::getOrderShopMap(),'param'=>['data-placeholder' => '全部','prompt' => '全部','style'=>'width:180px']]) ?>
    </div>
    <div class="layui-inline">
        <label>物流单号</label>
        <textarea class="layui-textarea search-con" name="OrderAbnormalSearch[track_no]"   autocomplete="off" style="height: 39px;min-height: 39px;"><?=$searchModel['track_no'];?></textarea>
    </div>
    <div class="layui-inline">
        跟进者
        <?= Html::dropDownList('OrderAbnormalSearch[follow_admin_id]', $searchModel['follow_admin_id'], $admin_arr,
            ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px' ]) ?>
    </div>
    <div class="layui-inline">
        <label>下单日期</label>
        <input  class="layui-input search-con ys-date" name="OrderAbnormalSearch[start_date]" value="<?=$searchModel['start_date'];?>"  id="start_date" autocomplete="off">
    </div>
    <span class="layui-inline layui-vertical-20">
        -
    </span>
    <div class="layui-inline layui-vertical-20">
        <input  class="layui-input search-con ys-date" name="OrderAbnormalSearch[end_date]" value="<?=$searchModel['end_date'];?>" id="end_date" autocomplete="off">
    </div>
    <div class="layui-inline">
        <label>异常日期</label>
        <input  class="layui-input search-con ys-date" name="OrderAbnormalSearch[abnormal_start_date]" value="<?=$searchModel['abnormal_start_date'];?>"  id="abnormal_start_date" autocomplete="off">
    </div>
    <span class="layui-inline layui-vertical-20">
        -
    </span>
    <div class="layui-inline layui-vertical-20">
        <input  class="layui-input search-con ys-date" name="OrderAbnormalSearch[abnormal_end_date]" value="<?=$searchModel['abnormal_end_date'];?>" id="abnormal_end_date" autocomplete="off">
    </div>
    <div class="layui-inline">
        <div style="padding-left: 10px">
            <input class="layui-input search-con" type="checkbox" value="1" name="OrderAbnormalSearch[my_abnormal]" <?= $searchModel['my_abnormal']==1?'checked':'';?> lay-skin="primary" title="我跟进的异常">
        </div>
    </div>
    <div class="layui-inline layui-vertical-20">
        <input type="hidden" name="tag" value="<?=$tag;?>" >
        <button class="layui-btn" data-type="search_lists">搜索</button>
    </div>
</div>
</form>

    <div>
        <div class="layui-form" style="padding-left: 10px;margin-top: 10px">
            <?php if($tag == 1){?>
                <div class="layui-inline">
                    <a class="layui-btn layui-btn-sm layui-btn-warm js-batch-open" data-title="批量关闭异常"  data-url="<?=Url::to(['order-abnormal/batch-close'])?>" >批量关闭异常</a>
                    <button class="layui-btn layui-btn-sm layui-btn-lg" data-type="export_lists" data-url="<?=Url::to(['order-abnormal/export?tag='.$tag])?>">全部导出</button>
                    <button class="layui-btn layui-btn-sm layui-btn-warm js-batch-export" data-title="批量导出" data-url="<?=Url::to(['order-abnormal/export?tag='.$tag])?>" >批量导出</button>
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
                <th>异常信息</th>
                <th>订单信息</th>
                <th>时间</th>
                <th>物流信息</th>
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
                    <td style="padding: 0 2px;">
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
                    <td align="left" style="width: 200px">
                        <a class="layui-btn layui-btn-xs layui-btn-a" data-type="url" data-url="<?=Url::to(['purchase-order/index?search=1&PurchaseOrderSearch%5Bsku_no%5D='.$sku_no])?>" data-title="采购信息" style="color: #00a0e9"><i class="layui-icon layui-icon-tabs"></i></a>
                        <b>
                            <?php
                            if(!empty($goods_v['goods_no'])){?>
                                <a class="layui-btn layui-btn-xs layui-btn-a" data-width="550px" data-type="url" data-url="<?=Url::to(['goods/view'])?>?goods_no=<?= $goods_v['goods_no'] ?>" data-title="商品信息" style="color: #00a0e9"><?=$sku_no?></a>
                            <?php } else { ?>
                                <?=$sku_no?>
                            <?php } ?>
                        </b>  x️ <span class="<?= $goods_v['goods_num'] != 1?'span-circular-red':'span-circular-grey'; ?>"><?=empty($goods_v['goods_num'])?'':$goods_v['goods_num']?></span><br/>
                        <span class="span-goods-name"><?=empty($goods_v['goods_name'])?'':$goods_v['goods_name']?></span>
                    </td>
                    <?php if($i == 1):?>
                    <td align="left" rowspan="<?=$v['goods_count']?>" style="width:300px;word-wrap: break-word;word-break: break-all;">
                        <span style="padding: 1px 5px;float: right" class="layui-font-12 layui-bg-orange"><?= \common\models\order\OrderAbnormal::$order_abnormal_status_map[$v['abnormal_status']] ?></span>
                        类型：<?=\common\services\order\OrderAbnormalService::$abnormal_type_maps[$v['abnormal_type']]?><br/>
                        备注：<span class="span-goods-name"><?=$v['abnormal_remarks']?></span><br/>
                        最近跟进备注：<span class="span-goods-name"><?=$v['last_follow_abnormal_remarks']?></span><br/>
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
                        <?= $v['relation_no'] ?><br/>
                        店铺：<?= empty($v['shop_name'])?'':$v['shop_name'] ?><br/>
                        订单状态：<?=\common\models\Order::$order_status_map[$v['order_status']]?><br/>
                        提交者：<?=empty($v['admin_id'])?'':\common\models\User::getInfoNickname([$v['admin_id']])?><br/>
                        跟进者：<?=empty($v['follow_admin_id'])?'':\common\models\User::getInfoNickname($v['follow_admin_id'])?><br/>
                        <?php if($v['pdelivery_status'] == 10){ ?>
                            <span style="padding: 1px 5px;float: right;" class="layui-font-12 layui-bg-green">发</span>
                        <?php }?>
                    </td>
                    <td rowspan="<?=$v['goods_count']?>" align="left" >
                        下单时间：<?= date('Y-m-d H:i:s',$v['date'])?><br/>
                        异常时间：<?= date('Y-m-d H:i:s',$v['ab_time'])?><br/>
                        最近跟进时间：<?= empty($v['last_follow_time'])?'无':date('Y-m-d H:i:s',$v['last_follow_time'])?><br/>
                        <?php if($v['abnormal_status'] != \common\models\order\OrderAbnormal::ORDER_ABNORMAL_STATUS_CLOSE){ ?>计划跟进时间：<span style="color: <?= $v['next_follow_time'] < time()?'#FF5722':($v['next_follow_time'] < time() + 12*60*60? '#FFB800':'#01AAED'); ?>"><?= date('Y-m-d H:i:s',$v['next_follow_time'])?></span><br/><?php }?>
                    </td>
                    <td align="left" rowspan="<?=$v['goods_count']?>" style="width:250px;word-wrap: break-word;word-break: break-all;">
                        <?php if(!empty($v['order_recommended']) && in_array($v['order_status'], [\common\models\Order::ORDER_STATUS_UNCONFIRMED, \common\models\Order::ORDER_STATUS_WAIT_PURCHASE])) { ?><span style="color: red">推荐物流方式：<?= $v['order_recommended']['logistics_channels_desc'] ?>(￥<?= $v['order_recommended']['freight_price']?>)</span><br/><?php } ?>
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
                        <?php if(in_array($tag,[8,19]) && !empty($v['abnormal_time'])){?>
                            <span style="color: red">备注：<?= $v['remarks'] ?></span>
                        <?php } ?>
                        <?php if(in_array($tag,[5,8])){?>
                            <?php if($v['pdelivery_status'] == 10){ ?>
                                <span style="padding: 1px 5px;float: right;" class="layui-font-12 layui-bg-green">发</span>
                            <?php }?>
                        <?php } ?>
                    </td>
                    <td rowspan="<?=$v['goods_count']?>">
                        <a class="layui-btn layui-btn-normal layui-btn-xs" data-type="open" data-height="600px" data-width="800px"  data-url="<?=Url::to(['order-abnormal/follow?id='.$v['abnormal_id']])?>" data-title="异常跟进">跟进</a>
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
<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.7")?>
<?=$this->registerJsFile("@adminPageJs/order/lists.js?v=".time())?>
<?php
$this->registerJsFile('@adminPlugins/export/xlsx.core.min.js?v=1',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export-excel.js?v=1',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export-function.js?v=1.3',['depends'=>'yii\web\JqueryAsset']);
?>