<?php

use common\models\TransportProviders;
use yii\helpers\Url;
use yii\helpers\Html;
use common\components\statics\Base;
use common\models\Shop;
use common\models\OrderLogisticsPackAssociation;
use common\models\OrderLogisticsPack;
use common\models\Order;
use common\services\purchase\PurchaseOrderService;

$order = new OrderLogisticsPackAssociation();
?>
<style>
    html {
        background: #fff;
    }
</style>
<form class="layui-form layui-row" id="update" action="<?=Url::to(['order-logistics-pack/update'])?>">

    <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px">
    
		<div class="layui-form-item">
            <label class="layui-form-label">发货时间</label>
            <div class="layui-input-block">
            <?php
            $date = Yii::$app->formatter->asDate($info['ship_date']);
            ?>
            <input type="text" name="ship_date" value="<?=$date?>" placeholder="请输入发货时间" value="<?=$info['ship_date']?>"  disabled class="layui-input layui-disabled">
            </div>
        </div>
		
		
        <div class="layui-form-item">
            <label class="layui-form-label">快递单号</label>
            <div class="layui-input-block">
                <input type="text" name="tracking_number" lay-verify="required" placeholder="请输入快递单号" value="<?=$info['tracking_number']?>"  class="layui-input">
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label">物流渠道类型</label>
            <div class="layui-input-block">
                <?= Html::dropDownList('channels_type',$info['channels_type'],TransportProviders::getTransportName(),
                    ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search' ]) ?>
            </div>
        </div>
        
        <div class="layui-form-item">
            <label class="layui-form-label">快递商</label>
            <div class="layui-input-block">
                <?= Html::dropDownList('courier',$info['courier'],PurchaseOrderService::getLogisticsChannels(),
                    ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search' ]) ?>
            </div>
        </div>
        
        <div class="layui-form-item">
            <label class="layui-form-label">重量</label>
            <div class="layui-input-block">
                <input type="text" name="weight" lay-verify="required" placeholder="请输入重量" value="<?=$info['weight']?>"  class="layui-input">
            </div>
        </div>
        
		
		<div class="layui-form-item">
            <label class="layui-form-label">备注</label>
            <div class="layui-input-block">
                <textarea name="remarks"   placeholder="请输入备注"  class="layui-input"	style="height:150px"><?=$info['remarks'];?></textarea>
            </div>
        </div>
  		
         
        <div class="layui-form-item">
            <div class="layui-input-block">
                <input type="hidden" name="id" value="<?=$info['id']?>">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="update">立即提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>

    </div>
</form>
<script>
</script>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>
