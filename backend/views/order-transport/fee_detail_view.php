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
    .layui-table-th{
        background: #FAFAFA;
        color: #666;
        width: 100px;
        text-align: right;
    }
</style>
<div class="layui-col-md9 layui-col-xs12" style="margin:0 20px 5px 20px">
    <table class="layui-table" >
        <tbody>
        <?php $i = 2;$j = 1;$count = count($fee_detail);
        foreach ($fee_detail as $property_v) {?>
            <?php if ($i % 3 != 0) {?>
                <tr>
            <?php }?>
            <td class="layui-table-th"><?=$property_v['fee_name']?> (<?=$property_v['fee_code']?>)</td>
            <?php if ($j == $count && $j % 2 != 0) {?>
                <td colspan="5">
                    <?=$property_v['fee']?>  <?=$property_v['currency']?> (<?=$property_v['cn_fee']?>  CNY)
                </td>
            <?php }else{?>
                <td>
                    <?=$property_v['fee']?>  <?=$property_v['currency']?> (<?=$property_v['cn_fee']?>  CNY)
                </td>
            <?php }?>
            <?php if ($i % 3 == 0) {
                $i = 1;?>
                </tr>
            <?php }?>
            <?php $i = ++$i;$j = ++$j ;}?>
        </tbody>
    </table>
</div>