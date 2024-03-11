<?php
use common\models\Order;
use common\services\warehousing\WarehouseService;
use yii\helpers\Url;
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
    .layui-card-body{
        padding: 3px 5px;
    }
    .layui-btn-a {
        border: none;
        background-color: rgba(0, 0, 0, 0);
        -webkit-user-select:text;
    }
    .layui-card{
        margin-bottom:7px;
    }
</style>
<form class="layui-form layui-row" id="arrival" action="<?=Url::to(['bl-container-transportation/arrival?id='.$id])?>">
    <div class="layui-col-xs12" style="padding: 0 10px;margin-top: 15px">
        <div style="padding: 5px; background-color: #F2F2F2; margin-top: 10px;">
            <div class="layui-row layui-col-space15">
                <div class="layui-col-md12">
                    <?php foreach ($bl_container_goods as $bl_container_v){?>
                    <div class="layui-card">
                        <div class="layui-card-header">
                            <span style="padding-right: 10px"><b>序号：</b><?=empty($bl_container_v['initial_number']) ? '' : $bl_container_v['initial_number']?></span>
                            <span><b>提单箱编号：</b><?=$bl_container_v['bl_no'];?></span>
                            <span style="float: right;padding-right: 10px;">
                                <b>仓库：</b><?=$bl_container_v['warehouse_id'] == 0 ? '' : $warehouse_name[$bl_container_v['warehouse_id']]?>
                            </span>
                        </div>
                        <div class="layui-card-body">
                            <table class="layui-table" style="text-align: center;font-size: 13px">
                                <thead>
                                <tr>
                                    <th colspan="2">商品</th>
                                    <th>单价</th>
                                    <th>到货数/需到货数</th>
                                    <th colspan="2">本次到货数量</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($bl_container_v['goods'] as $goods){
                                    $sku_no = $goods['sku_no']?>
                                    <tr class="goods_list" data-sku="<?=$sku_no?>">
                                        <td style="padding: 2px">
                                            <?php if(!empty($goods['goods_img'])):?>
                                                <a href="<?=$goods['goods_img']?>" data-lightbox="pic">
                                                    <img class="layui-upload-img" style="max-width: 200px;height: 80px"  src="<?=$goods['goods_img']?>">
                                                </a>
                                            <?php endif;?>
                                        </td>
                                        <td align="left" width="400">
                                            <?php
                                            if(!empty($goods['goods_no'])){?>
                                                <a class="layui-btn layui-btn-xs layui-btn-a" data-type="url" data-url="<?=Url::to(['goods/view'])?>?goods_no=<?= $goods['goods_no'] ?>" data-title="商品信息" style="color: #00a0e9;margin-left: 0px"><?=$sku_no?></a>
                                            <?php } else { ?>
                                                <?=$sku_no?>
                                            <?php } ?>
                                            <br/>
                                            <span class="span-goode-name"><?=empty($goods['goods_name'])?'':$goods['goods_name']?></span><br/>
                                        </td>
                                        <td><?=$goods['price']?></td>
                                        <td><span style="color: green"> <?=$goods['finish_num']?> / </span><?=$goods['num']?></td>
                                        <td>
                                            <?php if ($goods['num'] - $goods['finish_num'] > 0) {?>
                                            <input style="width: 70px;text-align:center;padding-left:0" data-num="<?=$goods['num'] - $goods['finish_num']?>"  type="text" name="finish_num[<?=$bl_container_v['id']?>][<?=$goods['id']?>]" lay-verify="number" value="0" class="layui-input arrival_num">
                                            <?php }?>
                                        </td>
                                    </tr>
                                <?php }?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <?php }?>
                </div>
            </div>
        </div>
        <div class="layui-form-item" style="margin-top: 20px;float: right; padding-right: 20px">
            <div class="layui-input-block">
                <button type="reset" class="layui-btn layui-btn-normal layui-btn-sm">清空</button>
                <button type="button" class="layui-btn layui-btn-normal layui-btn-sm" id="btn_all_arrival">全部到货</button>
            </div>
        </div>

        <div class="layui-form-item" style="margin-top: 20px;padding-left: 300px">
            <div class="layui-input-block">
                <input type="hidden" name="id" value="<?=$id?>">
                <button class="layui-btn layui-btn-lg " lay-submit="" lay-filter="form" data-form="arrival">提交</button>
            </div>
        </div>
    </div>
</form>
<?php
$this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/jquery-ui.min.js", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/extend/lodop.js?v=0.2',['depends'=>'yii\web\JqueryAsset']);
?>
<?=$this->registerJsFile("@adminPageJs/bl-container/arrival.js?v=".time())?>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.7")?>
