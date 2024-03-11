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
    .ys-select2{float: left}
    .select2{float: left}
</style>
<form class="layui-form layui-row" id="add" action="<?=Url::to(['category-property-value/update'])?>">

    <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px">

        <div class="layui-form-item">
            <label class="layui-form-label">属性值</label>
            <div class="layui-input-block">
                <input type="text" name="property_value" value="<?=$info['property_value']?>" lay-verify="required" placeholder="请输入属性名称" class="layui-input" style="width: 220px">
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label">状态</label>
            <?= Html::dropDownList('status', $info['status'],\backend\controllers\CategoryPropertyController::$map_tre ,
                ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:220px' ]) ?>
        </div>
    </div>

    <div class="layui-form-item">
        <div class="layui-input-block">
            <input type="hidden" name="id" value="<?=$info['id']?>">
            <button class="layui-btn" lay-submit="" lay-filter="form" data-form="add">立即提交</button>
            <button type="reset" class="layui-btn layui-btn-primary">重置</button>
        </div>
    </div>
    </div>
</form>

<?=$this->registerJsFile("@adminPageJs/base/form.js")?>
