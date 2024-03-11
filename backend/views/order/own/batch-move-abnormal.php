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
<form class="layui-form layui-row" id="grab_goods" action="<?=Url::to(['order/batch-move-abnormal?'.$_SERVER['QUERY_STRING']])?>">
    <div class="layui-col-md9 layui-col-xs12" style="padding: 0 10px">

        <div class="layui-form-item" style="padding: 20px">
            <label class="layui-form-label">异常类型</label>
            <div class="layui-input-block" style="width: 300px">
                <?= \yii\helpers\Html::dropDownList('abnormal_type', null, \common\services\order\OrderAbnormalService::$abnormal_type_maps,['prompt' => '请选择','id'=>'sel_abnormal_type','lay-filter'=>"sel_abnormal_type",'lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
            </div>
        </div>
        <div class="layui-form-item" id="div_next_follow_time" style="padding: 20px;display: none">
            <label class="layui-form-label">计划执行时间</label>
            <div class="layui-input-inline">
                <input type="text" name="next_follow_time" id="date" lay-verify="datetime" placeholder="yyyy-MM-dd HH:mm:ss" autocomplete="off"  value="" class="layui-input ys-datetime">
            </div>
        </div>

        <div class="layui-form-item" style="padding: 20px">
            <label>备注</label>
            <textarea class="layui-textarea" style="height: 100px" name="abnormal_remarks"></textarea>
        </div>

        <div class="layui-form-item">
            <div class="layui-input-block">
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
