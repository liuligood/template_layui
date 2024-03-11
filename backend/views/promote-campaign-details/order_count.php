
<?php

use backend\models\search\PromoteCampaignSearch;
use common\services\goods\GoodsService;
use common\services\ShopService;
use yii\helpers\Html;
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
                    <div class="layui-inline" style="width: 120px">
                        店铺名称：
                        <?= Html::dropDownList('shop_id', $searchModel['shop_id'], ShopService::getShopMap(\common\components\statics\Base::PLATFORM_OZON),
                            ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','id'=>'platform']) ?>
                    </div>
                    <div class="layui-inline">
                        活动时间：
                        <input  class="layui-input search-con ys-date" name="start_date" value="<?=$searchModel['start_date']?>" id="start_date" autocomplete="off">
                    </div>
                    <span class="layui-inline layui-vertical-20">
                            -
                        </span>

                    <div class="layui-inline layui-vertical-20">
                        <input  class="layui-input search-con ys-date" name="end_date" value="<?=$searchModel['end_date']?>" id="end_date" autocomplete="off">
                    </div>
                    <div class="layui-inline layui-vertical-20" style="padding-top: 15px">
                        <button class="layui-btn" data-type="search_lists">搜索</button>
                    </div>
                </div>
            </form>

            <div>
                <div class="summary" style="margin-top: 10px;">
                    <?php if (empty($order_count)){?>
                        <div style="float: right">总展示量：<i>0</i> 总点击量：<i>0</i> 总推广费用: <i>0</i>总订单量:<i>0</i> 总订单收入额:<i>0</i>总模型订单量:<i>0</i>总模型订单收入额:<i>0</div>
                    <?php }else{?>
                        <div class="summary" style="margin-top: 10px;">
                            <div style="float: right">总展示量：<i><?=empty($all_impressions)?'-':$all_impressions?></i> 总点击量：<i><?=empty($all_hits)?'-':$all_hits?></i> 总推广费用: <i><?=empty(round($all_promotes,2))?'-':round($all_promotes,2)?></i>总订单量:<i><?=empty($all_order_volume)?'-':$all_order_volume?></i> 总订单收入额:<i><?= empty(round($all_order_sales,2))?'-':round($all_order_sales,2)?></i>总模型订单量:<i><?= empty($all_model_orders)?'-':$all_model_orders?></i>总模型订单收入额:<i><?= empty(round($all_model_sales,2))?'-':round($all_model_sales,2)?></div>
                        </div>
                    <?php }?>
                </div>
                <div class="layui-form">
                    <table class="layui-table" style="text-align: center">
                        <thead>
                        <tr>
                            <th style="width: 30px"><input type="checkbox" lay-filter="select_all" id="select_all" name="select_all" lay-skin="primary" title=""></th>
                            <th>平台</th>
                            <th>展示量</th>
                            <th>点击量</th>
                            <th>CTR (%)</th>
                            <th>每1000展现量平均价格</th>
                            <th>推广费用</th>
                            <th>订单量</th>
                            <th>订单销售额</th>
                            <th>型号订单量</th>
                            <th>型号订单销售额</th>
                            <th>ACOS</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($order_count)): ?>
                            <tr>
                                <td colspan="17">无数据</td>
                            </tr>
                        <?php else: foreach ($order_count as $k => $v):
                            $i = 0;?>
                            <tr>
                                <td><input type="checkbox" class="select_collection" name="sales_id[]" value="<?=$v['id']?>" lay-skin="primary" title=""></td>
                                <td><?= GoodsService::$own_platform_type[$v['platform_type']] ?><br><?= $shop_map[$v['shop_id']]?></td>
                                <td><?= ($v['impressions']<=0)?'-':$v['impressions'] ?></td>
                                <td><?= ($v['hits']<=0)?'-':$v['hits'] ?></td>
                                <td><?=(!($v['hits']<=0)&&!($v['impressions']<=0))?((round((int)$v['hits']/(int)$v['impressions']*100,2)<=0)?'-':round((int)$v['hits']/$v['impressions']*100,2)):'-' ?></td>
                                <td><?=(!($v['promotes']<=0)&&!($v['impressions']<=0))?((round((float)$v['promotes']/(int)$v['impressions']*1000,2)<=0)?'-':round((float)$v['promotes']/(int)$v['impressions']*1000,2)):'-' ?></td>
                                <td><?= (round($v['promotes'],2)<=0)?'-':round($v['promotes'],2) ?></td>
                                <td><?= ($v['order_volume']<=0)?'-':$v['order_volume'] ?></td>
                                <td><?=  ($v['order_sales']<=0)?'-':$v['order_sales'] ?></td>
                                <td><?= ($v['model_orders']<=0)?'-':$v['model_orders'] ?></td>
                                <td><?= ($v['model_sales']<=0)?'-':$v['model_sales'] ?></td>
                                <td><?= (!($v['promotes'])<=0&&(!($v['order_sales']<=0)||!($v['model_sales']<=0)))?((round((float)$v['promotes']/((float)$v['order_sales']+(float)$v['model_sales']),2)<=0)?'-':round((float)$v['promotes']/((float)$v['order_sales']+(float)$v['model_sales']),2)):'-' ?></td>
                            </tr>
                        <?php endforeach;?>
                        <?php
                        endif;
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.5")?>
<?=$this->registerJsFile("@adminPageJs/promote-campaign-details/index.js?v=".time())?>
<?=$this->registerJsFile("@adminPageJs/collection/lists.js?v=".time())?>

