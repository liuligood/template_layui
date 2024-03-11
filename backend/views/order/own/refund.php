<?php
use yii\helpers\Url;
?>
<style>
    html {
        background: #fff;
    }
    .layui-form-item{
        margin-bottom: 5px;
    }
</style>
<form class="layui-form layui-row" id="grab_goods" action="<?=Url::to(['order/refund'])?>">
    <div class="layui-col-md9 layui-col-xs12" style="padding: 0 10px">

        <div class="layui-form-item" style="padding: 10px">
            <label class="layui-form-label">退款原因</label>
            <div class="layui-input-block" style="width: 300px">
                <?= \yii\helpers\Html::dropDownList('cancel_reason', null, \common\models\Order::$refund_reason_map,
                    ['prompt' => '请选择','id'=>'sel_cancel_reason','lay-filter'=>"sel_cancel_reason",'lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
            </div>
        </div>
        <div class="layui-form-item" style="padding: 10px">
            <label class="layui-form-label">退款类型</label>
            <div class="layui-input-block" style="width: 300px">
                <?= \yii\helpers\Html::dropDownList('cancel_type', null, \common\models\order\OrderRefund::$refund_map,
                    ['id'=>'sel_cancel_type','lay-filter'=>"sel_cancel_type",'lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
            </div>
        </div>
        <div class="layui-form-item" style="padding: 10px">
            <label class="layui-form-label">金额</label>
            <div class="layui-input-block">
                <input type="text"  id="bechange" name="num" lay-verify="required" placeholder="请输入退款金额" class="layui-input" style="width: 300px">
            </div>
        </div>

        <div class="layui-form-item" style="padding: 20px">
            <div style="margin-bottom: 8px">备注</div>
            <textarea class="layui-textarea" style="height: 100px" name="cancel_remarks"></textarea>
        </div>

        <div class="layui-form-item">
            <div class="layui-input-block">
                <input type="hidden" name="order_id" value="<?=$id?>">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="grab_goods">提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </div>
</form>

<?php
$this->registerJsFile("@adminPageJs/base/form.js");
$this->registerJsFile("@adminPageJs/order/move-abnormal.js?".time());
?>