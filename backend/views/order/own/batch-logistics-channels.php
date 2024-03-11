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
<form class="layui-form layui-row" id="grab_goods" action="<?=Url::to(['order/batch-logistics-channels?'.$_SERVER['QUERY_STRING']])?>">
    <div class="layui-col-md9 layui-col-xs12" style="padding: 0 10px">

        <div class="layui-form-item" style="margin: 20px 0">
            <?= \yii\helpers\Html::dropDownList('logistics_channels_id', null, $logistics_channels_id,
                ['prompt' => '请选择','lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>

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
$this->registerJsFile("@adminPageJs/base/form.js?".time());
?>
