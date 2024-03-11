<?php
/**
 * @var $this \yii\web\View;
 */
use yii\helpers\Url;
use yii\helpers\Html;
?>
<style>
    html {
        background: #fff;
    }
    body{
        background-color: #999;
    }
    #print {
        margin:10px auto;
        width: 210mm;
        background-color: #fff;
    }
    .print_con{
        padding: 10px
    }
    .layui-laypage li{
        float: left;
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
    .sku_div {

        border:1px solid #ccc ;
        margin-top: 5px;
    }
    .sku_con{
        float: left; width: 300px;padding: 5px;
    }
    .sku_box_num {
        display: block;
        padding: 3px;
        border: 1px dashed #ccc;
        border-top: none;
        border-right: none;
    }
    .sku_num{
        float: left; width: 100px;font-size: 20px;font-weight: bold;text-align: center;padding: 5px;
    }
    .last_num{
        border-bottom: none;
    }
    .box_lists{
        width: 235px;
        border: 1px solid #ccc ;
        padding: 5px;
        float: left;
        margin-left: 5px;
        margin-bottom: 5px;
    }
    .box_num{
        text-align: center;line-height: 40px;font-size: 25px;font-weight: bold;margin: 5px 0;
    }
</style>
    <div id="print">
        <div class="noprint" style="width:215mm;position: fixed;height: 0;">
            <div style="position: relative;left: 100%;">
            <button class="layui-btn layui-btn-normal" id="print_btn">打印</button>
            </div>
        </div>
        <?php foreach ($lists as $list){ ?>
        <div class="print_con">
        <div>
            <h1 style="float: left">拣货单</h1>
            <span style="float: right;line-height: 30px">打印时间：<?=date('Y-m-d H:i')?></span>
        </div>
        <div style="clear: both;"></div>
        <span>包裹总数：<?= count($list['order'])?> </span>

        <?php foreach ($list['order_goods'] as $order_good){ ?>
        <div class="sku_div">
            <div style="float: left;padding: 5px">
            <img class="pic" width="50" src="<?=$order_good['goods_img']?>"/>
            </div>
            <div class="sku_con">
                SKU：<?=$order_good['sku_no']?>【<?=$order_good['colour']?>】<br/>
                货架位：<?=$order_good['shelves_no']?><br/>
                商品编码：<?=$order_good['cgoods_no']?>
            </div>
            <div class="sku_num"><?=$order_good['goods_num']?></div>
            <div style="float: right;">
                <?php foreach ($order_good['index'] as $box_index_v){ ?>
                    <span class="sku_box_num"><?=$box_index_v['track_no']?> <?=$box_index_v['index']?>号:<?=$box_index_v['num']?>个</span>
                <?php } ?>
            </div>
            <div style="clear: both;"></div>
        </div>
        <?php } ?>

        <div style="border: 1px solid #ccc ;padding: 5px;margin-top: 10px">
            <h1 style="float: left">包裹信息</h1>
            <div style="clear: both;"></div>
        </div>
        <div style="border: 1px solid #ccc ;border-top: none;padding: 5px">
            <?php foreach ($list['order'] as $order){ ?>
            <div class="box_lists">
                <p>盒子号：</p>
                <div class="box_num"><?=$order['index']?></div>
                <p style="font-size: 12px">运单号：<?=$order['track_no']?></p>
                <div style="height: 80px; width: 200px;">
                    <svg class="barcode" jsbarcode-format="auto" jsbarcode-height="40" jsbarcode-width="1" jsbarcode-fontSize="20" jsbarcode-value="<?=$order['order_id']?>" jsbarcode-textmargin="0"></svg>
                </div>
            </div>
            <?php } ?>
            <div style="clear: both;"></div>
        </div>
        </div>
        <div style="page-break-after:always;"></div>
        <?php } ?>

    </div>
<script type="text/javascript">
    var goods = <?=empty($order_goods)?"''":$order_goods;?>;
</script>
<?php
$this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/jquery-ui.min.js", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/export/JsBarcode.all.js", ['depends' => 'yii\web\JqueryAsset'])?>
<?php
$this->registerJs("
    //打印
    function exe_print() {
        $('#print_btn').hide();//打印时隐藏
        window.print();//打印
        setTimeout(function(){ $('#print_btn').show();},10);
    }
    
    $('#print_btn').click(function(){
        exe_print();
    });
    
    JsBarcode(\".barcode\").init();
")
?>