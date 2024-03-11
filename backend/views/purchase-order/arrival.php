<?php
/**
 * @var $this \yii\web\View;
 */

use common\models\Order;
use common\services\warehousing\WarehouseService;
use yii\helpers\Url;
use yii\helpers\Html;
?>
<object id="LODOP_OB" classid="clsid:2105C259-1E0C-4534-8141-A753534CB4CA" width=0 height=0>
    <embed id="LODOP_EM" type="application/x-print-lodop" width=0 height=0 pluginspage="install_lodop32.exe"></embed>
</object>
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
    .remarks{
        min-width: 120px;
        max-width: 270px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .layui-table-grid-down {
        display: none;
    }
    .layui-icon-down{
        display: none;
    }
    .layui-table-tips-main{
        margin-left: 0px;
        padding-left: 7px;
        padding-right: 7px;
        padding-top: 0px;
        padding-bottom: 0px;
        width: 350px;
        min-height:20px;
        max-height: 40px;
        margin-top: 0px;
    }
    .childrenBody {
        padding-top: 0px;
    }
    .layui-btn-a {
        border: none;
        background-color: rgba(0, 0, 0, 0);
        -webkit-user-select:text;
    }
    .print_lab{
        padding-left: 5px;color: red;font-size: 12px
    }
    .order_lists{
        margin-right: 10px;padding-bottom: 5px
    }
</style>
<form class="layui-form layui-row" id="pu-arrival" action="<?=Url::to(['purchase-order/arrival'])?>">
    <div class="layui-col-xs12" style="padding: 0 10px;">
        <?php
        $cgoods_nos = [];
        $j = 0;
        foreach ($order as $order_v){ ?>
            <div style="padding: 5px; background-color: #F2F2F2; margin-top: 10px;">
                <div class="layui-row layui-col-space15">
                    <input type="hidden" name="order_id[]" value="<?=$order_v['order_id']?>">
                    <div class="layui-col-md12">
                        <div class="layui-card">
                            <div class="layui-card-header">
                                <b>订单号：</b><?=$order_v['order_id'];?>
                                <span style="padding-left: 10px;">
                        <b>供应商单号：</b>「<?= \common\components\statics\Base::$purchase_source_maps[$order_v['source']] ?> 」<?=$order_v['relation_no'];?>
                    </span>
                                <span style="float: right;padding-right: 10px;">
                    <b>采购时间：</b><?= date('Y-m-d H:i:s',$order_v['date'])?>
                    </span>
                                <span style="float: right;padding-right: 10px;"><b>仓库：</b><?= WarehouseService::$warehouse_map[$order_v['warehouse']] ?></span>
                            </div>
                            <div class="layui-card-body">

                                <table class="layui-table" style="text-align: center;font-size: 13px">
                                    <thead>
                                    <tr>
                                        <th colspan="2">商品</th>
                                        <th>单价</th>
                                        <th>到货数/采购数量</th>
                                        <th colspan="2">本次到货数量</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $i = 0 ;
                                    foreach ($order_v['order_goods'] as $goods_v){
                                        $i++;$sku_no = $goods_v['sku_no'];
                                        ?>
                                        <tr class="goods_list" data-sku="<?=$sku_no?>" data-shelves_no="<?=empty($goods_v['shelves_no'])?'':$goods_v['shelves_no']?>">
                                            <td>
                                                <?php if(!empty($goods_v['goods_pic'])):?>
                                                    <a href="<?=$goods_v['goods_pic']?>" data-lightbox="pic">
                                                        <img class="layui-upload-img" style="max-width: 200px;height: 100px"  src="<?=$goods_v['goods_pic']?>">
                                                    </a>
                                                <?php endif;?>
                                            </td>
                                            <td align="left" width="400">
                                                <a class="layui-btn layui-btn-xs layui-btn-a" data-type="url" data-url="<?=Url::to(['order/own-index?OrderSearch%5Bplatform_asin%5D='.$sku_no.'&tag=10'])?>" data-title="订单信息" style="color: #00a0e9"><i class="layui-icon layui-icon-template"></i></a>
                                                <?php
                                                if(!empty($goods_v['goods_no'])){?>
                                                <a class="layui-btn layui-btn-xs layui-btn-a" data-type="url" data-url="<?=Url::to(['goods/view'])?>?goods_no=<?= $goods_v['goods_no'] ?>" data-title="商品信息" style="color: #00a0e9;margin-left: 0px"><?=$sku_no?></a>
                                                <?php } else { ?>
                                                    <?=$sku_no?>
                                                <?php } ?>
                                                <a class="layui-btn layui-btn-sm layui-btn-a"  onclick="copySku('<?=$sku_no?>')" style="color: #00a0e9;margin-left: 1px;padding-left: 0px"><i class="layui-icon layui-icon-list"></i></a>
                                                <div class="lay-lists layui-inline" style="float: right">
                                                    <a class="layui-btn layui-btn-normal layui-btn-xs" data-type="open" data-url="<?=Url::to(['warehouse-goods/update-shelves?cgoods_no='.$goods_v['cgoods_no']])?>" data-width="500px" data-height="450px" data-title="更换货架"  style="float: right">更换货架</a>
                                                </div>
                                                <br/>
                                                <span class="span-goode-name"><?=empty($goods_v['goods_name'])?'':$goods_v['goods_name']?></span><br/>
                                                <?php if(!empty($goods_v['ccolour']) || !empty($goods_v['csize'])) {?>
                                                <span style="color:#ff0000"><?=$goods_v['ccolour']?> <?=$goods_v['csize']?></span><br/>
                                                <?php }else{?>
                                                规格：<?=$goods_v['specification']?><br/>
                                                <?php }?>
                                                <?php if(!in_array($goods_v['cgoods_no'],$cgoods_nos)) {?>
                                                <div class="layui-form-item">
                                                    <div class="layui-inline">
                                                        <label class="layui-form-label" style="width:55px;">重量(kg)</label>
                                                        <div class="layui-input-block" style="width: 60px; margin-left: 60px;">
                                                            <input type="text" name="weight[<?=$goods_v['cgoods_no']?>]" lay-verify="required|number" placeholder="请输入重量" value="<?= $goods_v['real_weight'] ?>" class="layui-input" autocomplete="off">
                                                        </div>
                                                    </div>
                                                    <div class="layui-inline">
                                                        <label class="layui-form-label" style="width: 85px;">包装尺寸(cm)</label>
                                                        <div class="layui-inline" style="width: 60px;">
                                                            <input type="text" name="size_l[<?=$goods_v['cgoods_no']?>]" lay-verify="number" placeholder="长"  value="<?=empty($goods_v['size']['size_l'])?0:$goods_v['size']['size_l']?>" class="layui-input" autocomplete="off">
                                                        </div>
                                                        <div class="layui-inline" style="width: 60px;">
                                                            <input type="text" name="size_w[<?=$goods_v['cgoods_no']?>]" lay-verify="number" placeholder="宽"  value="<?=empty($goods_v['size']['size_w'])?0:$goods_v['size']['size_w']?>" class="layui-input" autocomplete="off">
                                                        </div>
                                                        <div class="layui-inline" style="width: 60px;">
                                                            <input type="text" name="size_h[<?=$goods_v['cgoods_no']?>]" lay-verify="number" placeholder="高"  value="<?=empty($goods_v['size']['size_h'])?0:$goods_v['size']['size_h']?>" class="layui-input" autocomplete="off">
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php } ?>
                                            </td>
                                            <td><?=$goods_v['goods_price']?></td>
                                            <td><span style="color: green"><?=$goods_v['goods_finish_num']?></span> / <?=$goods_v['goods_num']?>
                                            <br/><br/>货架：<?=empty($goods_v['shelves_no'])?'无':$goods_v['shelves_no']?>
                                            </td>
                                            <td>
                                                <?php if($goods_v['goods_num'] - $goods_v['goods_finish_num'] > 0){?>
                                                    <input style="width: 70px; text-align: center;padding-left:0" data-num="<?=$goods_v['goods_num'] - $goods_v['goods_finish_num']?>" type="text" name="finish_num[<?=$order_v['order_id']?>][<?=$goods_v['id'];?>]" lay-verify="number" value="0" class="layui-input arrival_num">
                                                <?php }?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="5" style="text-align: left;padding: 0px">
                                                <div style="float: left;margin: 10px">销售订单：</div><br>
                                                <div style="float: left">
                                                    <table class="layui-table" style="text-align: center;font-size: 13px;width: 890px;margin-left: 10px">
                                                        <?php foreach($goods_v['order'] as $olorder_v){
                                                            $olorder_info = current($olorder_v);
                                                            ?>
                                                        <tr class="order_lists_tr">
                                                            <td width="80"><?php if(!$olorder_info['has_move']){ ?>
                                                                    <?php if ($olorder_info['order_status'] == Order::ORDER_STATUS_WAIT_SHIP){?>
                                                                        <a class="layui-btn layui-btn-xs layui-btn-warm print_logistics" data-title="重打印" data-url="<?=Url::to(['order/direct-printed?order_id='.$olorder_info['order_id']])?>" >打印面单</a>
                                                                    <?php }else{?>
                                                                        <a class="layui-btn layui-btn-xs print_logistics" data-url="<?=Url::to(['order/direct-printed?order_id='.$olorder_info['order_id']])?>" >打印面单</a>
                                                                    <?php }?>
                                                                    <span class="print_lab"></span>
                                                                <?php }?>
                                                            </td>
                                                            <td align="left">
                                                                <?php foreach($olorder_v as $oorder_v){?>
                                                                        <div class="order_lists">
                                                                <div class="layui-inline lists" style="line-height: 36px;padding-bottom: 5px;">
                                                                    <?php if($oorder_v['has_move']){ ?>
                                                                        <span style="padding: 1px 5px;" class="layui-font-12 layui-bg-red">多</span>
                                                                    <?php }?>
                                                                    【<?=$oorder_v['platform']?>】
                                                                    <a class="layui-btn layui-btn-xs layui-btn-a" data-type="url" data-url="<?=Url::to(['order/view?order_id='.$oorder_v['order_id']])?>" data-title="订单详情" style="color: #00a0e9;line-height: 20px;font-size: 14px;"><?=$oorder_v['order_id']?></a> x️ <?=$oorder_v['num']?> <span style="color:#a0a3a6"><?=$oorder_v['country']?></span>
                                                                </div>
                                                                <?php if ($oorder_v['remarks']){?>
                                                                    <div class="layui-inline" style="margin-left: 35px;">
                                                                        <div class="layui-layer-content contents"  style="display:none;">
                                                                            <div class="layui-table-tips-main">
                                                                                <?=$oorder_v['remarks']?>
                                                                            </div>
                                                                            <i class="layui-icon layui-table-tips-c layui-icon-close" onclick="closeDiv('<?=$j?>')"></i>
                                                                        </div>
                                                                        <div class="remarks remark" onmouseover="hoverRemark('<?=$j?>')" onmouseout="moveRemark('<?=$j?>')"  style="padding: 5px 5px 5px 5px;" >
                                                                            <?=$oorder_v['remarks']?>
                                                                            <div class="layui-table-grid-down down" onclick="showOtherDiv('<?=$j?>')"  style="padding-bottom: 0px;padding-top: 0px;width: 23px">
                                                                                <i class="layui-icon layui-icon-down icon"></i>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <?php $j++;}?>
                                                                        </div>
                                                                <?php }?>
                                                            </td>
                                                        </tr>
                                                        <?php }?>
                                                    </table>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php
                                    $cgoods_nos[] = $goods_v['cgoods_no'];
                                    }?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php }?>

        <div class="layui-form-item" style="margin-top: 20px;float: right; padding-right: 20px">
            <div class="layui-input-block">
                <button type="button" class="layui-btn layui-btn-normal layui-btn-sm" id="btn_print_tag">打印标签</button>
                <button type="reset" class="layui-btn layui-btn-normal layui-btn-sm">清空</button>
                <button type="button" class="layui-btn layui-btn-normal layui-btn-sm" id="btn_all_arrival">全部到货</button>
            </div>
        </div>

        <div class="layui-form-item" style="margin-top: 20px;padding-left: 300px">
            <div class="layui-input-block">
                <input type="hidden" name="logistics_no" value="<?=$logistics_no?>">
                <button class="layui-btn layui-btn-lg " lay-submit="" lay-filter="form" data-form="pu-arrival">提交</button>
            </div>
        </div>
    </div>
</form>
<script type="text/javascript">
    var goods = <?=empty($order_goods)?"''":$order_goods;?>;

    function copySku(i){
        var sku_no= i;
        var oInput = document.createElement('input');
        oInput.value = sku_no;
        document.body.appendChild(oInput);
        oInput.select();
        document.execCommand("Copy"); // 执行浏览器复制命令
        oInput.className = 'oInput';
        oInput.style.display='none';
        layer.msg("复制成功",{icon: 1});
    }

    function hoverRemark(i) {
        var num = i;
        var cWidth = document.getElementsByClassName('remarks')[num].clientWidth;
        var sWidth = document.getElementsByClassName('remarks')[num].scrollWidth;
        var down = document.getElementsByClassName('down')[num];
        var icon = document.getElementsByClassName('icon')[num];
        if (sWidth > cWidth){
            down.style.display = "block";
            icon.style.display = "block";
        }
    }

    function moveRemark(i) {
        var num = i;
        var down = document.getElementsByClassName('down')[num];
        var icon = document.getElementsByClassName('icon')[num];
        down.style.display = "none";
        icon.style.display = "none";
    }

    function showOtherDiv(i){
        var num = i;
        var  judge = document.getElementsByClassName('contents');
        var remark = document.getElementsByClassName('remark');
        remark[num].style.display = "none";
        judge[num].style.display = "block";
    }
    
    function closeDiv(i){
        var num = i;
        var  judge = document.getElementsByClassName('contents');
        var remark = document.getElementsByClassName('remark');
        remark[num].style.display = "block";
        judge[num].style.display = "none";
    }
</script>
<?php
$this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/jquery-ui.min.js", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/extend/lodop.js?v=0.2',['depends'=>'yii\web\JqueryAsset']);
?>
<?=$this->registerJsFile("@adminPageJs/purchase_order/arrival.js?v=".time())?>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.7")?>
