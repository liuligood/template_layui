<?php
/**
 * @var $this \yii\web\View;
 */

use common\services\warehousing\WarehouseService;
use yii\helpers\Url;
use yii\helpers\Html;
?>
<style>
    html {
        background: #fff;
    }
    .layui-laypage li{
        float: left;
    }
    .span-goode-name{
        color:#a0a3a6;
        font-size: 13px;
    }
    .layui-form-label {
        padding: 9px 0;
    }
    .layui-form-item .layui-inline {
        margin-right: 0px;
    }
    .layui-table th{
        font-size: 13px;
    }
    .childrenBody {
        padding-top: 0px;
    }
    .layui-btn-a {
        border: none;
        background-color: rgba(0, 0, 0, 0);
        -webkit-user-select:text;
    }
</style>
<form class="layui-form layui-row" id="pu-arrival" action="<?=Url::to(['order/scan-ship'])?>">
    <div class="layui-col-xs12" style="padding: 0 10px;">
        <div style="padding: 10px;margin-right:20px;color: <?=$transport['color']?>;font-size: 24px;float: right;font-weight: bold;">
        <?=$transport['transport_name']?>
        </div>
        <?php
        $goods_nos = [];
        foreach ($order as $order_v){ ?>
            <div style="padding: 5px; background-color: #F2F2F2; margin-top: 10px;clear: both;">
                <div class="layui-row layui-col-space15">
                    <input type="hidden" name="order_id[]" value="<?=$order_v['order_id']?>">
                    <div class="layui-col-md12">
                        <div class="layui-card">
                            <div class="layui-card-header">
                                <b>订单号：</b>
                                <a class="layui-btn layui-btn-primary layui-btn-a" data-type="url"  data-url="<?=Url::to(['order/view?order_id='.$order_v['order_id']])?>" data-title="订单详情" style="color: #00a0e9;padding-left: 0px"><?= $order_v['order_id'] ?></a>
                                <span style="padding-left: 10px;">
                        <b>销售单号：</b>「<?= \common\components\statics\Base::$order_source_maps[$order_v['source']] ?> 」<?=$order_v['relation_no'];?>
                    </span>
                                <span style="float: right;padding-right: 10px;">
                    <b>下单时间：</b><?= date('Y-m-d H:i:s',$order_v['date'])?>
                    </span>
                                <span style="float: right;padding-right: 10px;"><b>仓库：</b><?= WarehouseService::$warehouse_map[$order_v['warehouse']] ?></span>
                            </div>
                            <div class="layui-card-body">

                                <table class="layui-table" style="text-align: center;font-size: 13px">
                                    <thead>
                                    <tr>
                                        <th colspan="2">商品</th>
                                        <th>单价</th>
                                        <th>数量</th>
                                        <th>剩余库存</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $i = 0 ;
                                    foreach ($order_v['order_goods'] as $goods_v){
                                        $i++; ?>
                                        <tr>
                                            <td>
                                                <?php if(!empty($goods_v['goods_pic'])):?>
                                                    <a href="<?=empty($goods_v['goods_pic'])?'':$goods_v['goods_pic']?>" data-lightbox="pic">
                                                        <img class="layui-upload-img" style="max-width: 200px;height: 100px"  src="<?=empty($goods_v['goods_pic'])?'':$goods_v['goods_pic']?>">
                                                    </a>
                                                <?php endif;?>
                                            </td>
                                            <td align="left" width="500">
                                                <?php
                                                $sku_no = $goods_v['platform_asin'];
                                                if(!empty($goods_v['goods_id'])){?>
                                                <a class="layui-btn layui-btn-xs layui-btn-a" data-type="url" data-url="<?=Url::to(['goods/update'])?>?id=<?= $goods_v['goods_id'] ?>" data-title="商品信息" style="color: #00a0e9"><?=$sku_no?></a>
                                                <?php } else { ?>
                                                    <?=$sku_no?>
                                                <?php } ?><br/>
                                                <span class="span-goode-name"><?=empty($goods_v['goods_name'])?'':$goods_v['goods_name']?></span><br/>
                                                <?php if(!empty($goods_v['ccolour']) || !empty($goods_v['csize'])) {?>
                                                    <span style="color:#ff0000"><?=$goods_v['ccolour']?> <?=$goods_v['csize']?></span><br/>
                                                <?php }else{?>
                                                    规格：<?=$goods_v['specification']?><br/>
                                                <?php }?>
                                            </td>
                                            <td><?=$goods_v['goods_income_price']?></td>
                                            <td><?=$goods_v['goods_num']?></td>
                                            <td><?=$goods_v['goods_stock_num']?></td>
                                        </tr>
                                    <?php
                                    $goods_nos[] = $goods_v['goods_no'];
                                    }?>
                                    </tbody>
                                </table>

                                <div style="padding-top: 5px">
                                    <div class="layui-inline">
                                        <label class="layui-form-label" style="width: 85px;">包装尺寸(cm)</label>
                                        <div class="layui-inline" style="width: 60px;">
                                            <input type="text" name="size_l[<?=$order_v['order_id']?>]" lay-verify="number" placeholder="长"  value="<?=empty($order_v['size']['size_l'])?0:$order_v['size']['size_l']?>" class="layui-input" autocomplete="off">
                                        </div>
                                        <div class="layui-inline" style="width: 60px;">
                                            <input type="text" name="size_w[<?=$order_v['order_id']?>]" lay-verify="number" placeholder="宽"  value="<?=empty($order_v['size']['size_w'])?0:$order_v['size']['size_w']?>" class="layui-input" autocomplete="off">
                                        </div>
                                        <div class="layui-inline" style="width: 60px;">
                                            <input type="text" name="size_h[<?=$order_v['order_id']?>]" lay-verify="number" placeholder="高"  value="<?=empty($order_v['size']['size_h'])?0:$order_v['size']['size_h']?>" class="layui-input" autocomplete="off">
                                        </div>
                                    </div>

                                    <div class="layui-inline">
                                        <label class="layui-form-label" style="width:55px;">重量(kg)</label>
                                        <div class="layui-input-block" style="width: 60px; margin-left: 60px;">
                                            <input type="text" name="weight[<?=$order_v['order_id']?>]" lay-verify="required|number" placeholder="重量" value="<?= $order_v['weight'] ?>" class="layui-input" autocomplete="off">
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php }?>

        <div class="layui-form-item" style="margin-top: 20px;padding-left: 300px">
            <div class="layui-input-block">
                <input type="hidden" id="force_ship" name="force" value="0">
                <button class="layui-btn layui-btn-lg " lay-submit="" lay-filter="form" data-form="pu-arrival">提交发货</button>
            </div>
        </div>
    </div>
</form>
<script type="text/javascript">
    var goods = <?=empty($order_goods)?"''":$order_goods;?>;
</script>
<?php
$this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/jquery-ui.min.js", ['depends' => 'yii\web\JqueryAsset']);
?>
<?=$this->registerJsFile("@adminPageJs/order/scan_ship.js?v=".time())?>