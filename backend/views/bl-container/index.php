
<?php

use backend\models\search\PromoteCampaignSearch;
use common\models\warehousing\BlContainer;
use common\models\warehousing\BlContainerTransportation;
use common\services\ShopService;
use common\services\sys\CountryService;
use common\services\warehousing\WarehouseService;
use yii\helpers\Html;
use yii\helpers\Url;
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
                <li <?php if($tag == 10){?>class="layui-this" <?php }?>><a href="<?=Url::to(['bl-container/index?tag=10'])?>">全部</a></li>
                <li <?php if($tag == 3){?>class="layui-this" <?php }?>><a href="<?=Url::to(['bl-container/index?tag=3'])?>">待发货</a></li>
                <li <?php if($tag == 1){?>class="layui-this" <?php }?>><a href="<?=Url::to(['bl-container/index?tag=1'])?>">未到货</a></li>
                <li <?php if($tag == 2){?>class="layui-this" <?php }?>><a href="<?=Url::to(['bl-container/index?tag=2'])?>">已到货</a></li>
            </ul>
        </div>
        <div class="layui-card-body">
        <div class="lay-lists">
            <!--<form class="layui-form">
                <blockquote class="layui-elem-quote quoteBox">
                    <div class="layui-inline">
                        <a class="layui-btn" data-type="url" data-title="新增提箱" data-url="<?=Url::to(['bl-container/create'])?>" data-callback_title = "提箱单列表" >新增提单箱</a>
                    </div>
                </blockquote>
            </form>-->
            <form class="layui-form">
                <div class="lay-search" style="padding-left: 10px">

                    <div class="layui-inline">
                        <label>提单箱编号</label>
                        <input class="layui-input search-con" name="BlContainerSearch[bl_no]" value="<?=$searchModel['bl_no']?>" autocomplete="off">
                    </div>

                    <div class="layui-inline">
                        <label>SKU</label>
                        <input class="layui-input search-con" name="BlContainerSearch[sku_no]" value="<?=$searchModel['sku_no']?>" autocomplete="off">
                    </div>

                    <?php if ($tag == 3){?>
                    <div class="layui-inline">
                        <label>序号</label>
                        <input class="layui-input search-con" name="BlContainerSearch[initial_number]" value="<?=$searchModel['initial_number']?>" autocomplete="off">
                    </div>
                    <?php }?>

                    <div class="layui-inline" style="width: 150px">
                        <label>仓库</label>
                        <?= Html::dropDownList('BlContainerSearch[warehouse_id]',$searchModel['warehouse_id'],WarehouseService::getOverseasWarehouse(),
                            ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search', 'style' => 'width:150px']) ?>
                    </div>

                    <div class="layui-inline" style="width: 220px">
                        <label>物流编号</label>
                        <input class="layui-input search-con" name="BlContainerSearch[bl_transportation]" value="<?=$searchModel['bl_transportation']?>" autocomplete="off">
                    </div>

                    <?php if ($tag == 10){?>
                    <div class="layui-inline" style="width: 150px">
                        <label>状态</label>
                        <?= Html::dropDownList('BlContainerSearch[status]',$searchModel['status'],BlContainer::$status_maps,
                            ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search', 'style' => 'width:150px']) ?>
                    </div>
                    <?php }?>

                    <div class="layui-inline" style="width: 150px">
                        <label>运输方式</label>
                        <?= Html::dropDownList('BlContainerSearch[transport_type]',$searchModel['transport_type'],BlContainer::$transport_maps,
                            ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search', 'style' => 'width:150px']) ?>
                    </div>

                    <div class="layui-inline">
                        装箱时间：
                        <input  class="layui-input search-con ys-date" name="BlContainerSearch[star_packing_time]" value="<?=$searchModel['star_packing_time']?>" id="star_packing_time" autocomplete="off">
                    </div>
                    <span class="layui-inline layui-vertical-20">
                            -
                        </span>

                    <div class="layui-inline layui-vertical-20">
                        <input  class="layui-input search-con ys-date" name="BlContainerSearch[end_packing_time]" value="<?=$searchModel['end_packing_time']?>" id="end_packing_time" autocomplete="off">
                    </div>

                    <?php if ($tag == 3) {?>
                    <div class="layui-inline">
                        <div style="padding-left: 10px">
                            <input class="layui-input search-con" type="checkbox" value="1" name="BlContainerSearch[has_measure]" lay-skin="primary" title="未测量" <?=$searchModel['has_measure'] == 1 ? 'checked':''?>>
                        </div>
                    </div>
                    <?php }?>

                    <div class="layui-inline layui-vertical-20" style="padding-top: 20px">
                        <input type="hidden" name="tag" value="<?=$tag;?>" >
                        <button class="layui-btn" data-type="search_lists">搜索</button>
                    </div>
                </div>
            </form>
            <div class="layui-form" style="padding: 8px 10px">
                <button class="layui-btn layui-btn-sm layui-btn-lg" data-type="export_lists" data-url="<?=Url::to(['bl-container/export?tag='.$tag])?>">全部导出</button>
                <?php if ($tag == 1) { ?>
                    <div class="layui-inline">
                        <a class="layui-btn layui-btn-sm layui-btn-warm js-batch-wait" data-title="打回待发货" data-url="<?=Url::to(['bl-container/reset-wait-ship'])?>">打回待发货</a>
                    </div>
                <?php }?>
                <?php if ($tag == 3) { ?>
                    <div class="layui-inline">
                        <a class="layui-btn layui-btn-sm layui-btn-normal js-batch-delivery" data-title="批量发货" data-url="<?=Url::to(['bl-container/delivery?is_batch=1'])?>">批量发货</a>
                    </div>
                <?php }?>
            </div>

            <div>
                <?php
                $startCount = (0 == $pages->totalCount) ? 0 : $pages->offset + 1;
                $endCount = ($pages->page + 1) * $pages->pageSize;
                $endCount = ($endCount > $pages->totalCount) ? $pages->totalCount : $endCount;
                ?>
                <div class="layui-form">
                    <table class="layui-table">
                        <thead>
                        <tr>
                            <th style="width: 30px"><input type="checkbox" lay-filter="select_all" id="select_all" name="select_all" lay-skin="primary" title=""></th>
                            <th>仓库</th>
                            <?php if ($tag != 3){?>
                            <th>提单箱编号</th>
                            <?php }?>
                            <th>重量</th>
                            <th>总商品数量</th>
                            <?php if ($tag != 3){?>
                            <th>价格</th>
                            <?php }?>
                            <th>状态</th>
                            <th>时间</th>
                            <th style="text-align: center">操作</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($list)): ?>
                            <tr>
                                <td colspan="17" style="text-align: center">无数据</td>
                            </tr>
                        <?php else: foreach ($list as $k => $v):
                            $i = 0;?>
                            <tr>
                                <td><input type="checkbox" class="select_collection" name="sales_id[]" value="<?=$v['id']?>" lay-skin="primary" title=""></td>
                                <td>
                                    序号：<?=$v['initial_number']?><br/>
                                    <?=$v['warehouse_id'] == 0 ? '' : $warehouse_name[$v['warehouse_id']]?>
                                </td>
                                <?php if ($tag != 3){?>
                                <td>
                                    <?php if ($v['status'] != BlContainer::STATUS_WAIT_SHIP){?>
                                    <?=$v['bl_no']?><br/>
                                    物流编号：<?=empty($v['track_no']) ? '' : $v['track_no']?><br/>
                                    运输方式：<?=empty($v['transport_name']) ? '' : $v['transport_name']?>
                                    <?php }?>
                                </td>
                                <?php }?>
                                <td>
                                    重量：<?=$v['weight']?><br/>
                                    尺寸：<?=$v['size']?><br/>
                                    <?php if ($v['status'] != BlContainer::STATUS_WAIT_SHIP){?>
                                    材积重：<?=empty($v['tr_cjz']) ? '' : $v['tr_cjz']?>
                                    <?php }?>
                                </td>
                                <td><?=$v['goods_count']?></td>
                                <?php if ($tag != 3){?>
                                    <td>
                                        <?php if ($v['status'] != BlContainer::STATUS_WAIT_SHIP){?>
                                            <?=empty($v['bl_price']) ? '' : $v['bl_price']?>
                                        <?php }?>
                                    </td>
                                <?php }?>
                                <td><?=BlContainer::$status_maps[$v['status']]?></td>
                                <td>
                                    发货时间：<?=empty($v['tr_delivery_time']) ? '' : $v['tr_delivery_time']?><br/>
                                    预计到达时间：<?=empty($v['tr_arrival_time']) ? '' : $v['tr_arrival_time']?><br/>
                                    装箱时间：<?=empty($v['packing_time']) ? '' : date("Y-m-d H:i:s",$v['packing_time'])?>
                                </td>
                                <td style="text-align: center">
                                    <?php if (in_array($v['status'],[BlContainer::STATUS_WAIT_SHIP,BlContainer::STATUS_NOT_DELIVERED,BlContainer::STATUS_PARTIAL_DELIVERED])){?>
                                        <?php if ($v['status'] == BlContainer::STATUS_WAIT_SHIP) {?>
                                            <a class="layui-btn layui-btn-normal layui-btn-xs" data-type="open" data-width="600px" data-height="400px"  data-url="<?=Url::to(['bl-container/delivery?id='.$v['id'].'&is_batch=2'])?>" data-title="发货" data-callback_title="bl-container列表">发货</a>
                                        <?php } ?>
                                        <?php if (in_array($v['status'], [BlContainer::STATUS_NOT_DELIVERED, BlContainer::STATUS_PARTIAL_DELIVERED])) {?>
                                            <a class="layui-btn layui-btn-normal layui-btn-xs" data-type="open" data-width="1000px" data-height="600px"  data-url="<?=Url::to(['bl-container/arrival?id='.$v['id']])?>" data-title="到货" data-callback_title="bl-container列表">到货</a>
                                        <?php } ?>
                                        <?php if ($v['status'] == BlContainer::STATUS_PARTIAL_DELIVERED) {?>
                                            <a class="layui-btn layui-btn-danger layui-btn-xs" data-type="operating" data-title="终止部分到货" data-url="<?=Url::to(['bl-container/finish-arrival?id='.$v['id']])?>">终止部分到货</a>
                                        <?php } else { ?>
                                            <a class="layui-btn layui-btn-normal layui-btn-xs" data-type="url"  data-url="<?=Url::to(['bl-container/update?id='.$v['id']])?>" data-title="编辑" data-callback_title="bl-container列表">编辑</a><br/>
                                            <a class="layui-btn layui-btn-danger layui-btn-xs" data-type="cancel" data-url="<?=Url::to(['bl-container/delete?id='.$v['id']])?>">删除</a>
                                        <?php }?>
                                    <?php }?>
                                    <a class="layui-btn layui-btn-xs" data-type="url"  data-url="<?=Url::to(['bl-container/view?id='.$v['id']])?>" data-title="提单箱详情" data-callback_title="bl-container列表">查看详情</a>
                                </td>
                            </tr>
                        <?php endforeach;?>
                        <?php
                        endif;
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?= \yii\widgets\LinkPager::widget(['pagination' => $pages,'options' => ['class' => 'layui-box layui-laypage layui-laypage-default'],]) ?>
        </div>
    </div>
    </div>
</div>
</div>
<?php
$this->registerJsFile("@adminPageJs/base/lists.js?v=1.2.2");
$this->registerJsFile("@adminPageJs/goods/base_lists.js?".time());
$this->registerJsFile("@adminPageJs/bl-container/lists.js?".time());
$this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/xlsx.core.min.js?v=1',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export-excel.js?v=1',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export-function.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
?>