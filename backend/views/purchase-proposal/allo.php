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
<form class="layui-form layui-row" id="grab_goods" action="<?=Url::to(['purchase-proposal/batch-allo?'.$_SERVER['QUERY_STRING']])?>">
    <div class="layui-col-md9 layui-col-xs12" style="padding: 0 10px">

        <div class="layui-form-item" style="margin: 20px 0">
            <div class="layui-inline layui-col-md3">
                <?= \yii\helpers\Html::dropDownList('admin_id', null, $admin_lists,
                    ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
            </div>
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
$this->registerJsFile("@adminPageJs/goods/grab.js?".time());
?>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>
