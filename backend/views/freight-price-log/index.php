
<?php

use common\models\RevenueExpenditureAccount;
use common\models\RevenueExpenditureLog;
use common\models\RevenueExpenditureType;
use common\models\Shop;
use common\models\TransportProviders;
use common\services\transport\TransportService;
use yii\helpers\Url;
?>
<style>
    .layui-table-cell {
        height:auto;}
    i{
        color: red;
    }
    #red{
        color: red;
    }
    #order td{
        border: 0px;
        padding:0px;
    }
    .layui-card {
        padding: 10px 15px;
    }
    .layui-laypage li{
        float: left;
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
<div class="layui-fluid">
    <div class="layui-card">
        <div class="lay-lists">
            <form class="layui-form">
                <div class="lay-search" style="padding-left: 10px">

                    <div class="layui-inline">
                        <label>订单号</label>
                        <textarea name="FreightPriceLogSearch[order_id]" class="layui-textarea search-con" style="height: 39px; min-height:39px"><?=$searchModel['order_id'];?></textarea>
                    </div>

                    <div class="layui-inline">
                        <label>物流单号</label>
                        <textarea name="FreightPriceLogSearch[track_no]" class="layui-textarea search-con" style="height: 39px; min-height:39px"><?=$searchModel['track_no'];?></textarea>
                    </div>

                    <div class="layui-inline">
                        <label>物流商</label>
                        <?= \yii\helpers\Html::dropDownList('FreightPriceLogSearch[transport_code]', $searchModel['transport_code'], TransportProviders::getTransportName($code = 1),
                            ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']); ?>
                    </div>

                    <div class="layui-inline">
                        <label>物流方式</label>
                        <?= \yii\helpers\Html::dropDownList('FreightPriceLogSearch[logistics_channels_id]', $searchModel['logistics_channels_id'], TransportService::getShippingMethodOptions(),
                            ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px']); ?>
                    </div>

                    <div class="layui-inline">
                        <label>计费时间：</label>
                        <input  class="layui-input search-con ys-date" name="FreightPriceLogSearch[start_billed_time]" id="start_billed_time" value="<?=$searchModel['start_billed_time']?>" autocomplete="off">
                    </div>
                    <span class="layui-inline layui-vertical-20">
                        -
                        <br>
                    </span>
                    <div class="layui-inline layui-vertical-20">
                        <input  class="layui-input search-con ys-date" name="FreightPriceLogSearch[end_billed_time]" id="end_billed_time" value="<?=$searchModel['end_billed_time']?>" autocomplete="off">
                    </div>

                    <div class="layui-inline layui-vertical-20" style="padding-top: 15px">
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
                </div>
                <div class="layui-form">
                    <table class="layui-table" style="text-align: center">
                        <thead>
                        <tr>
                            <th>订单号</th>
                            <th>物流单号</th>
                            <th>物流信息</th>
                            <th>计费时间</th>
                            <th>规格(cm)</th>
                            <th>运费</th>
                            <th>国家</th>
                            <th>创建时间</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($list)): ?>
                            <tr>
                                <td colspan="17">无数据</td>
                            </tr>
                        <?php else: foreach ($list as $k => $v):
                            $i = 0;?>
                            <tr>
                                <td><?=$v['order_id']?></td>
                                <td>
                                    物流单号：<?=$v['track_no']?><br>
                                    物流转单号：<?=$v['track_logistics_no']?>
                                </td>
                                <td>
                                    物流商：<?=$v['transport_name']?><br>
                                    物流渠道：<?=$v['logistics_channels_id']?><br>
                                </td>
                                <td><?=$v['billed_time']?></td>
                                <td>
                                    重量：<?=$v['weight']?> kg<br>
                                    长：<?=$v['length']?><br>
                                    宽：<?=$v['width']?><br>
                                    高：<?=$v['height']?>
                                </td>
                                <td><?=$v['freight_price']?></td>
                                <td><?=$v['country']?></td>
                                <td><?=$v['add_time']?></td>
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
<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.5")?>
<?php
$this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
?>
