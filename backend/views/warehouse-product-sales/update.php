<?php
/**
 * @var $this \yii\web\View;
 */

use common\models\warehousing\WarehouseProductSales;
use yii\helpers\Url;
?>
<style>
    html {
        background: #fff;
    }
    .layui-form-radio {
        margin-right: 0px;
        padding-right: 0px;
    }
    .safe_stock_type2 {
        font-weight: bold;
        font-size: 16px
    }
    .layui-btn-a {
        border: none;
        background-color: rgba(0, 0, 0, 0);
        -webkit-user-select:text;
    }
</style>
<form class="layui-form layui-row" id="update_warehouse_product" action="<?=Url::to(['warehouse-product-sales/update?id='.$data['info']['id']])?>">

    <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px;width: 900px">

        <div class="layui-form-item" style="margin-left: 25px">
            商品信息：
            <div style="padding: 2px; background-color: #f2f2f2;margin-top: 3px">
                <div class="layui-row layui-col-space15">
                <div class="layui-col-md12">
                    <div class="layui-card" style="padding: 5px">
                        <div class="layui-inline lay-lists" style="display: flex">
                            <div>
                                <?php if(!empty($data['image'])):?>
                                    <a href="<?=$data['image']?>" data-lightbox="pic">
                                        <img class="layui-upload-img" style="max-width: 95px;height: 95px"  src="<?=$data['image']?>">
                                    </a>
                                <?php endif;?>
                                </div>
                            <div style="margin-left: 8px;padding-top: 5px">
                                <?php
                                if(!empty($data['goods_no'])){?>
                                    <a class="layui-btn layui-btn-xs layui-btn-a" data-type="url" data-url="<?=Url::to(['goods/view'])?>?goods_no=<?=$data['goods_no'] ?>" data-title="商品信息" style="color: #00a0e9;margin-left: 0px;font-size: 13px"><?=$data['sku_no']?></a>
                                <?php } else { ?>
                                    <?=$data['sku_no']?>
                                <?php } ?>
                                <br/>
                                <?=empty($data['goods_name']) ? '' : $data['goods_name']?>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </div>

            <div>
            <table class="layui-table" style="text-align: center;font-size: 13px">
                <thead>
                <tr>
                    <th></th>
                    <th style="text-align: center;">1日</th>
                    <th style="text-align: center">7日</th>
                    <th style="text-align: center">15日</th>
                    <th style="text-align: center">30日</th>
                    <th style="text-align: center">90日</th>
                </tr>
                <tr>
                    <th style="text-align: center">总销量</th>
                    <?php foreach ($data['days'] as $v) {?>
                        <td style="background-color: white"><?=$v['total_sales']?></td>
                    <?php }?>
                </tr>
                <tr>
                    <th style="text-align: center">日均销量</th>
                    <?php foreach ($data['days'] as $v) {?>
                        <td style="background-color: white"><?=$v['average_day']?></td>
                    <?php }?>
                </tr>
                </thead>
            </table>
            </div>

            <div class="layui-form-item radio" style="margin-bottom: 5px">
                <?php foreach (WarehouseProductSales::$type_maps as $k => $v) {?>
                    <input type="radio" name="safe_stock_type" value="<?=$k?>" title="<?=$v?>" lay-filter="safe_stock_type" <?php if ($data['info']['safe_stock_type'] == $k) {?> checked="" <?php }?>>
                    <div class="layui-unselect layui-form-radio"></div>
                    <div class="layui-inline" style="margin-top: 10px;margin-right: 60px">
                        <?php if ($k == WarehouseProductSales::TYPE_STOCK_UP) {?>
                        <a style="font-size: 18px;color:#FFB800;font-weight: bold;" class="js-helps layui-icon layui-icon-about" href="javascript:;" title="帮助" data-content="●此模式为根据您设置的[备货天数]，向前取出相应天数的[销量]，设置为[安全库存] ，并且随销量情况每日动态变化，方便快捷"></a>
                        <?php } elseif ($k == WarehouseProductSales::TYPE_SALES_WEIGHT) {?>
                        <a style="font-size: 18px;color:#FFB800;font-weight: bold;" class="js-helps layui-icon layui-icon-about" href="javascript:;" title="帮助" data-content="●此模式下[安全库存数]为[备货天数*加权平均日销量]，并且随销量情况每日动态变化，您可根据自身情况设置不同时段的销量权重。"></a>
                        <?php } else {?>
                        <a style="font-size: 18px;color:#FFB800;font-weight: bold;" class="js-helps layui-icon layui-icon-about" href="javascript:;" title="帮助" data-content="●此模式下[安全库存] 为设置的固定值，不会动态变化。"></a>
                        <?php }?>
                    </div>
                <?php }?>
            </div>

            <div style="padding: 3px; background-color: #f2f2f2;">
            <div class="layui-row layui-col-space15">
                <div class="layui-col-md12">
                    <div class="layui-card" style="padding: 10px">
                    <div id="safe_stock">
                    </div>
                    </div>
                </div>
            </div>
            </div>

        </div>

        <div class="layui-form-item">
            <div class="layui-input-block">
                <input type="hidden" value="<?=$data['info']['id']?>" name="id">
                <input type="hidden" value="<?=$data['info']['safe_stock_num']?>" name="safe_stock_num_val" id="safe_stock_num_val">
                <input type="hidden" name="cgoods_no" value="<?=$data['info']['cgoods_no']?>" id="cgoods_no">
                <input type="hidden" name="warehouse_id" value="<?=$data['info']['warehouse_id']?>" id="warehouse_id">
                <input type="hidden" value="<?=$data['info']['safe_stock_param']?>" id="safe_stock_param" name="safe_stock_param">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="update_warehouse_product">立即提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </div>
</form>
<script id="safe_stock_type1" type="text/html">
    <table class="layui-table" style="text-align: center;font-size: 13px;margin: 0;position: relative">
        <thead>
        <tr>
            <th style="font-weight: bold;text-align: center;width: 100px">备货天数</th>
            <th style="font-weight: bold;text-align: center">
                过去 <span id="input_stock_up_day" style="color: orange">0</span> 天日均销量
            </th>
            <th style="font-weight: bold;text-align: center">安全库存数</th>
        </tr>
        <tr>
            <th style="text-align: center;width: 100px">
                <input type="text" name="stock_up_day" lay-verify="number" placeholder="备货天数"  value="{{ d.stock.stock_up_day || 0 }}" class="layui-input safe_stock_day1" autocomplete="off" style="width: 70px;" >
            </th>
            <th style="text-align: center">
                <span class="safe_stock_type2" id="stock_up_type1">0.00</span>
            </th>
            <th style="text-align: center">
                <span class="safe_stock_type2" style="color: orange" id="safe_stock_num">0</span>
            </th>
        </tr>
        </thead>
    </table>
</script>
<script id="safe_stock_type2" type="text/html">
    <div class="layui-form-item">
        选择权重方案：
        <div class="layui-inline" style="margin-left: 10px">
            <select class="layui-input search-con ys-select2 select_weight"  lay-ignore style="width: 240px">
                <option value="" {{#if (d.select_weight == ''){ }} selected {{# } }}></option>
                <?php foreach (WarehouseProductSales::$algorithm_maps as $k => $v){?>
                    <option value="<?=$k?>" {{#if (d.select_weight == <?=$k?> ){ }} selected {{# } }}><?=$v?></option>
                <?php }?>
            </select>
        </div>
    </div>
    <table class="layui-table" style="text-align: center;font-size: 13px;margin: 0">
        <thead>
        <tr>
            <th></th>
            <th style="text-align: center;">1日</th>
            <th style="text-align: center">7日</th>
            <th style="text-align: center">15日</th>
            <th style="text-align: center">30日</th>
            <th style="text-align: center">90日</th>
        </tr>
        <tr>
            <th style="text-align: center">日均销量</th>
            <?php foreach ($data['days'] as $v) {?>
                <td style="background-color: white" class="average_day"><?=$v['average_day']?></td>
            <?php }?>
        </tr>
        <tr>
            <th style="text-align: center">权重设置</th>
            <?php foreach ($data['days'] as $v) {?>
                <td style="background-color: white">
                    <div class="layui-inline">
                        <input type="text" value="0" lay-verify="number" class="layui-input weight" autocomplete="off" style="width: 55px;" >
                    </div>
                    <div class="layui-inline">%</div>
                </td>
            <?php }?>
        </tr>
        </thead>
    </table>
    <div class="layui-form-item" style="margin-top: 15px">
        <table class="layui-table" style="text-align: center;font-size: 13px;margin: 0">
            <thead>
            <tr>
                <th style="text-align: center;">加权平均日销量</th>
                <th style="text-align: center;width: 130px">备货天数</th>
                <th style="text-align: center">安全库存数</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td id="average_day_weight">0.00</td>
                <td>
                    <input type="text" name="stock_up_day"  lay-verify="number" placeholder="备货天数"  value="{{ d.stock.stock_up_day || 0 }}" class="layui-input" id="stock_up_day2" autocomplete="off" style="width: 100px">
                </td>
                <td class="safe_stock_type2" id="safe_stock_num" style="color: orange">{{ d.stock.safe_stock_num || 0 }}</td>
            </tr>
            </tbody>
        </table>
    </div>
</script>
<script id="safe_stock_type3" type="text/html">
    <table class="layui-table" style="text-align: center;font-size: 13px;margin: 0">
        <thead>
        <tr>
            <th style="font-weight: bold;text-align: center">安全库存数</th>
        </tr>
        <tr>
            <th>
                <input type="text" name="safe_stock_num" lay-verify="number" placeholder="安全库存数"  value="{{ d.stock.safe_stock_num || 0 }}" class="layui-input" autocomplete="off" style="width: 170px">
            </th>
        </tr>
        </thead>
    </table>
</script>
<script>
    var warehouse_product = <?=empty($data['info']) ? '0' : json_encode($data['info'])?>;
    var safe_stock_params = <?=empty($data['info']['safe_stock_param']) ? '0' : $data['info']['safe_stock_param']?>;
</script>
<?php
$this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/jquery-ui.min.js", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/extend/lodop.js?v=0.2',['depends'=>'yii\web\JqueryAsset']);
?>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>
<?=$this->registerJsFile("@adminPageJs/base/lists.js")?>
<?=$this->registerJsFile("@adminPageJs/warehouse-product-sales/form.js?v=".time())?>
