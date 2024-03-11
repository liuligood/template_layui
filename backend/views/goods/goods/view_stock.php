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
            <th style="text-align: center;height: 35px"> < 30 </th>
            <th style="text-align: center"> 31-60 </th>
            <th style="text-align: center"> 61-90 </th>
            <th style="text-align: center"> 91-180 </th>
            <th style="text-align: center"> 181-360 </th>
            <th style="text-align: center"> > 360 </th>
            <th style="text-align: center"> 实时库存 </th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td style="height: 35px"><?=empty($stock_details['less_thirty']) ? '0' : $stock_details['less_thirty']?></td>
            <td><?=empty($stock_details['greater_thirty']) ? '0' : $stock_details['greater_thirty']?></td>
            <td><?=empty($stock_details['greater_sixty']) ? '0' : $stock_details['greater_sixty']?></td>
            <td><?=empty($stock_details['greater_ninety']) ? '0' : $stock_details['greater_ninety']?></td>
            <td><?=empty($stock_details['greater_hundred_eighty']) ? '0' : $stock_details['greater_hundred_eighty']?></td>
            <td><?=empty($stock_details['greater_three_hundred_sixty']) ? '0' : $stock_details['greater_three_hundred_sixty']?></td>
            <td><?=empty($stock) ? '0' : $stock?></td>
        </tr>
        </tbody>
    </table>
</div>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.7")?>

