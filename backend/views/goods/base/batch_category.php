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
<form class="layui-form layui-row" id="grab_goods" action="<?=Url::to(['goods-'.$url_platform_name.'/batch-category?id='.$id])?>">
    <div class="layui-col-md9 layui-col-xs12" style="padding: 10px">

        <div class="layui-form-item">
            <div class="layui-inline layui-col-md6">
                <label class="layui-form-label"><?=$platform_name?>类目</label>
                <div class="layui-input-block">
                    <input type="text" name="o_category_name" placeholder="请输入类目名称" value="" lay-verify="required" class="layui-input">
                </div>
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
