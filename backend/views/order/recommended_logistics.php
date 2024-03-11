<?php
/**
 * @var $this \yii\web\View;
 */
use yii\helpers\Url;
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
    .span-goods-name{
        color:#a0a3a6;
        font-size: 13px;
    }
    .layui-tab-brief > .layui-tab-title .layui-this a{
        color: rgb(0, 150, 136);
    }
    .layui-btn-a {
        border: none;
        background-color: rgba(0, 0, 0, 0);
        -webkit-user-select:text;
    }
    .layui-vertical-20{
        padding-top: 20px;
    }
</style>
<div style="margin:20px">
    <?php if(!empty($weight)){ ?>
    <label>重量：<?= empty($weight)?'':$weight;?> kg (不算材积重)</label>
    <?php }?>
    <?php if(!empty($size)){ ?>
    <label style="margin-left: 30px">尺寸：<?= empty($size)?'':$size;?></label>
    <?php }?>
    <label style="margin-left: 30px">泡重比：<?= empty($foam_weight)?'':$foam_weight;?></label>
    <br/>    <br/>
    <?php if(!empty($order_profit)){ ?>
        <label>订单收入：<?= empty($order_profit)?0:$order_profit;?></label>
    <?php }?>
    <?php if(!empty($purchase_price)){ ?>
        <label style="margin-left: 30px">预估采购成本：<?= empty($purchase_price)?0:$purchase_price;?></label>
    <?php }?>

    <table class="layui-table">
        <tbody>
        <tr>
            <th>渠道</th>
            <th>预估重量</th>
            <th>预估运费</th>
            <th>预估收入</th>
        </tr>
        <?php
        foreach ($logistics_lists as $v){
            ?>
            <tr>
                <td><?=\common\services\transport\TransportService::getShippingMethodName($v['shipping_method_id'])?></td>
                <td>
                    <?=$v['weight']?> kg<br/>
                </td>
                <td>
                    <?=$v['price']?> <br/>
                </td>
                <td>
                    <?php if($order_profit >0 && $purchase_price >0) {
                        $price = $order_profit - $purchase_price - $v['price'];
                        ?>
                        <span <?php if($price <=0){?>style="color: red"<?php }?>><?=$price?></span>
                    <?php }?>
                </td>
            </tr>
        <?php }?>
        </tbody>
    </table>
    <form class="layui-form">
    <div style="padding-top: 5px">
        <div class="layui-inline">
            <label  style="width:55px;">重量</label>
            <div style="width: 60px;">
                <input type="text" name="weight" lay-verify="required|number" placeholder="重量" value="<?=Yii::$app->request->get('weight')?>" class="layui-input" autocomplete="off">
            </div>
        </div>
        <div class="layui-inline" style="width: 60px; margin-left: 10px">
            <label >尺寸</label>
            <input type="text" name="size_l" lay-verify="number" placeholder="长"  value="<?=Yii::$app->request->get('size_l')?>" class="layui-input" autocomplete="off">
        </div>
        <div class="layui-inline layui-vertical-20" style="width: 60px;">
            <input type="text" name="size_w" lay-verify="number" placeholder="宽"  value="<?=Yii::$app->request->get('size_w')?>" class="layui-input" autocomplete="off">
        </div>
        <div class="layui-inline layui-vertical-20" style="width: 60px;">
            <input type="text" name="size_h" lay-verify="number" placeholder="高"  value="<?=Yii::$app->request->get('size_h')?>" class="layui-input" autocomplete="off">
        </div>
        <div class="layui-inline layui-vertical-20">
            <input type="hidden" name="order_id" value="<?=Yii::$app->request->get('order_id')?>">
            <button class="layui-btn">运费试算</button>
        </div>
    </div>
    </form>
    <?php if(!empty($tmp_logistics_lists)) { ?>
    <table class="layui-table">
        <tbody>
        <tr>
            <th>渠道</th>
            <th>预估运费</th>
            <th>预估收入</th>
        </tr>
        <?php
        foreach ($tmp_logistics_lists as $v){
            ?>
            <tr>
                <td><?=\common\services\transport\TransportService::getShippingMethodName($v['shipping_method_id'])?></td>
                <td>
                    <?=$v['price']?> <br/>
                </td>
                <td>
                    <?php if($order_profit >0 && $purchase_price >0) {
                        $price = $order_profit - $purchase_price - $v['price'];
                        ?>
                        <span <?php if($price <=0){?>style="color: red"<?php }?>><?=$price?></span>
                    <?php }?>
                </td>
            </tr>
        <?php }?>
        </tbody>
    </table>
    <?php }?>
</div>
