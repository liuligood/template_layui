<?php

use common\services\ShopService;
use yii\helpers\Html;
use yii\helpers\Url;
?>
<style>
    html {
        background: #fff;
    }
    .layui-form-item{
        margin: 0 auto;

    }
</style>
<form class="layui-form layui-row" id="grab_goods" action="<?=Url::to(['goods/supplementary-claim'])?>">
    <div class="layui-col-md9 layui-col-xs12 " style="padding: 10px">

        <div class="layui-form-item">
            <label class="layui-form-label">子商品</label>
            <div class="layui-input-block">
                <input type="text" name="cgoods_no" lay-verify="required" placeholder="请输入子商品编号" class="layui-input" style="width: 275px">
            </div>
        </div>
        <br>
        <div class="layui-form-item">
            <label class="layui-form-label">店铺</label>
            <div class="layui-input-block" style="width: 275px">
                <?= \yii\bootstrap\Html::dropDownList('shop_id',null,$platform,
                    ['lay-ignore'=>'lay-ignore','data-placeholder' => '请选择店铺','prompt' => '无','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
            </div>
        </div>
        <br>
        <div class="layui-form-item">
            <label class="layui-form-label">自定义SKU</label>
            <div class="layui-input-block">
                <input type="text" name="sku_no" lay-verify="required" placeholder="请输入自定义SKU" class="layui-input" style="width: 275px;">
            </div>
        </div>
        <br>
        <div class="layui-form-item">
            <div class="layui-input-block">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="grab_goods">提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </div>
</form>


<?php
$this->registerJsFile("@adminPageJs/goods/grab.js?".time());
?>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>

