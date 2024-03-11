<?php
use yii\helpers\Url;

$owner_id = new \common\models\Shop();
?>
<style>
    html {
        background: #fff;
    }
    .layui-form-item{
        margin-bottom: 5px;
    }
</style>
<form class="layui-form layui-row" id="bath-allo">
    <div class="layui-col-md9 layui-col-xs12" style="padding: 10px">

        <div class="layui-form-item" style="margin: 20px 0">
            <div class="layui-inline layui-col-md3">
                <?= \yii\helpers\Html::dropDownList('owner_id',null,$owner_id->adminArr(),
                    ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
            </div>
        </div>


        <div class="layui-form-item">
            <div class="layui-input-block">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="bath-allo">提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </div>
</form>
<?php
$this->registerJsFile("@adminPageJs/base/form.js?".time());
?>
