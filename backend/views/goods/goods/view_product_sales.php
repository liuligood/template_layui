<?php
use yii\helpers\Url;
use common\models\Shop;
use common\components\statics\Base;
use common\models\User;

?>
<style>
    html {
        background: #fff;
    }
    .layui-form-item{
        margin-bottom: 5px;
    }
    .lay-image{
        float: left;padding: 20px; border: 1px solid #eee;margin: 5px
    }
    .layui-tab-title .layui-this {
        background-color: #fff;
        color: #000;
    }
    .layui-tab-brief > .layui-tab-title .layui-this a{
        color: rgb(0, 150, 136);
    }
    .layui-tab{
        margin-top: 0;
    }
    .layui-table-th{
        background: #FAFAFA;
        color: #666;
        width: 100px;
        text-align: right;
    }
</style>
<div style="margin: 25px">
    <table class="layui-table" style="text-align: center;font-size: 13px;">
        <thead>
        <tr>
            <th style="text-align: center;height: 35px">1日</th>
            <th style="text-align: center">7日</th>
            <th style="text-align: center">15日</th>
            <th style="text-align: center">30日</th>
            <th style="text-align: center">90日</th>
            <th style="text-align: center">订单频次(天)</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td style="height: 35px"><?=$data['one_day_sales']?></td>
            <td><?=$data['seven_day_sales']?></td>
            <td><?=$data['fifteen_day_sales']?></td>
            <td><?=$data['thirty_day_sales']?></td>
            <td><?=$data['ninety_day_sales']?></td>
            <td><?=$data['order_frequency'] == 0 ? '' : round($data['order_frequency'] / 86400,4)?></td>
        </tr>
        </tbody>
    </table>
</div>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.7")?>

