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
<?php if (empty($list)) {?>
    <div style="margin: 25px">
        物流编号：
        <table class="layui-table" style="text-align: center;font-size: 13px;">
            <thead>
            <tr>
                <th style="text-align: center;height: 35px">序号</th>
                <th style="text-align: center">提单箱编号</th>
                <th style="text-align: center">重量</th>
                <th style="text-align: center">预计运费(单价)</th>
                <th style="text-align: center">数量</th>
                <th style="text-align: center">预计运费(总价)</th>
                <th style="text-align: center">时间</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td colspan="17">无数据</td>
            </tr>
            </tbody>
        </table>
    </div>
<?php } ?>
<?php foreach ($list as $track_no => $goods_v){?>
<div style="margin: 25px">
    物流编号：<?=$track_no?>
    <table class="layui-table" style="text-align: center;font-size: 13px;">
        <thead>
        <tr>
            <th style="text-align: center;height: 35px">序号</th>
            <th style="text-align: center">提单箱编号</th>
            <th style="text-align: center">重量</th>
            <th style="text-align: center">预计运费(单价)</th>
            <th style="text-align: center">数量</th>
            <th style="text-align: center">预计运费(总价)</th>
            <th style="text-align: center">时间</th>
        </tr>
        </thead>
        <?php foreach ($goods_v as $v){?>
        <tbody>
        <tr>
            <td><?=$v['initial_number']?></td>
            <td><?=$v['bl_no']?></td>
            <td><?=$v['weight']?></td>
            <td><?=$v['price']?></td>
            <td><?=$v['num']?></td>
            <td><?=round($v['price'] * $v['num'],2)?></td>
            <td>
                <?=empty($v['delivery_time'])?'':'发货时间:'.$v['delivery_time']?><br/>
                <?=empty($v['arrival_time'])?'':'预计到达时间:'.$v['arrival_time']?>
            </td>
        </tr>
        </tbody>
        <?php }?>
    </table>
</div>
<?php }?>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.7")?>

