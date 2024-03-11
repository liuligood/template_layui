
<?php

use common\services\purchase\PurchaseOrderService;
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
</style>
<form class="layui-form layui-row" id="add" action="<?=Url::to(['order-logistics-pack/create-logistics'])?>">
<div class="layui-fluid">
    <div class="layui-card">
        <div class="layui-form">
            <table class="layui-table" style="text-align: center">
                <thead>
                <tr>
                    <th style="text-align: center">发货日期</th>
                    <th style="text-align: center">快递商</th>
                    <th style="text-align: center">物流渠道</th>
                    <th style="text-align: center">数量</th>
                    <th style="text-align: center">重量(kg)</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($list)): ?>
                    <tr>
                        <td colspan="17">无数据</td>
                    </tr>
                <?php else: foreach ($list as $k => $v):?>
                    <tr>
                        <td><input name="ship_date[]" type="hidden" value="<?=$v['ship_date']?>"><?=date("Y-m-d",$v['ship_date'])?></td>
                        <td style="width: 150px;">
                            <?= \yii\bootstrap\Html::dropDownList('courier[]',null,PurchaseOrderService::getLogisticsChannels(),
                                ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:150px' ]) ?>
                        </td>
                        <td><input name="channels_type[]" type="hidden" value="<?=$v['channels_type']?>"><?=$v['channels_name']?></td>
                        <td>
                            <input name="quantity[]" type="hidden" value="<?=$v['quantity']?>"><?=$v['quantity']?>
                            <input name="order_id[]" type="hidden" value="<?=$v['order_id']?>">
                        </td>
                        <td><input name="weight[]" type="hidden" value="<?=$v['weight']?>"><?=$v['weight']?></td>
                    </tr>
                <?php
                endforeach;
                endif;
                ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="layui-form-item layui-layout-admin">
        <div class="layui-input-block">
            <div class="layui-footer" style="left: 0;">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="add">立即提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </div>
</div>
</form>
<?=$this->registerJsFile("@adminPageJs/base/form.js?v=0.0.5")?>
