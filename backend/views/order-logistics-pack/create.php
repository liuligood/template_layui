<?php
/**
 * @var $this \yii\web\View;
 */

use common\models\TransportProviders;
use yii\helpers\Url;
use yii\bootstrap\Html;
use common\components\statics\Base;
use common\models\Shop;
use common\services\purchase\PurchaseOrderService;
use common\models\OrderLogisticsPack;

$admin_id = new OrderLogisticsPack();
?>
<style>
    html {
        background: #fff;
    }
</style>
<form class="layui-form layui-row" id="add" action="<?=Url::to(['order-logistics-pack/create'])?>">

    <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px">
    
    	<div class="layui-form-item">
            <label class="layui-form-label">发货日期</label>
            <div class="layui-input-block">
                <input  class="layui-input search-con ys-date" name="ship_date"  id="ship_date"  autocomplete="off">
            </div>
        </div>
		
			
        <div class="layui-form-item">
            <label class="layui-form-label">快递单号</label>
            <div class="layui-input-block">
                <input type="text" name="tracking_number" lay-verify="required" placeholder="请输入快递单号" class="layui-input" >
            </div>
        </div>

        
        <div class="layui-form-item">
            <label class="layui-form-label">快递商</label>
            <div class="layui-input-block">
                <?= Html::dropDownList('courier',null,PurchaseOrderService::getLogisticsChannels(),
                    ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search' ]) ?>
            </div>
        </div>
        
        <div class="layui-form-item">
            <label class="layui-form-label">物流渠道</label>
            <div class="layui-input-block">
                <?= Html::dropDownList('channels_type',null,TransportProviders::getTransportName(),
                    ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search' ]) ?>
            </div>
        </div>
        
        
        <div class="layui-form-item">
            <label class="layui-form-label">重量</label>
            <div class="layui-input-block">
                <input type="text" name="weight" lay-verify="required" placeholder="请输入重量" value="0" class="layui-input">
            </div>
        </div>
        
        
        <div class="layui-form-item">
            <label class="layui-form-label">备注</label>
            <div class="layui-input-block">
                <textarea name="remarks"   placeholder="请输入备注"  class="layui-input"	style="height:150px"></textarea>
            </div>
        </div>
       
         
        <div class="layui-form-item">
            <div class="layui-input-block">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="add">立即提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </div>
</form>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>