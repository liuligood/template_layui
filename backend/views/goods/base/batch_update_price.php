<?php
use yii\helpers\Url;
$platform_name = \common\components\statics\Base::$platform_maps[$platform_type];
$url_platform_name = strtolower($platform_name);
?>
<style>
    html {
        background: #fff;
    }
    .layui-form-item{
        margin-bottom: 5px;
    }
</style>
<form class="layui-form layui-row" id="grab_goods" action="<?=Url::to(['goods-'.$url_platform_name.'/batch-update-price?'.$_SERVER['QUERY_STRING']])?>" method="post">
    <div class="layui-col-md9 layui-col-xs12" style="padding: 10px">

        <div class="layui-form-item">
            <div class="layui-inline layui-col-md6">
                <label class="layui-form-label">锁定价格</label>
                <div class="layui-input-block">
                    <input type="text" name="fixed_price" lay-verify="required|number" placeholder="锁定价格" class="layui-input">
                </div>
            </div>
        </div>

        <div class="layui-form-item">
            <div class="layui-input-block">
                <input type="hidden" name="id" value="<?=$id?>">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="grab_goods">提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </div>
</form>
<?php
$this->registerJsFile("@adminPageJs/base/form.js?".time());
?>