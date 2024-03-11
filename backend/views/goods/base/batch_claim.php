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
<form class="layui-form layui-row" id="grab_goods" action="<?=Url::to(['goods-'.$url_platform_name.'/batch-claim?'.$_SERVER['QUERY_STRING']])?>" method="post">
    <div class="layui-col-md9 layui-col-xs12" style="padding: 10px">

        <?php foreach ($platform as $platform_v) {?>
        <div class="layui-form-item">
            <div class="layui-inline layui-col-md6">
                <label class="layui-form-label"><?=$platform_v['name']?></label>
                <div class="layui-input-block">
                    <?php foreach ($platform_v['shop'] as $k=>$v){?>
                    <input type="checkbox" name="shop[]" value="<?=$k?>" lay-skin="primary" title="<?=$v?>">
                    <?php }?>
                </div>
            </div>
        </div>
        <?php }?>

        <?php if (empty($id)) {?>
        <div class="layui-form-item">
            <div class="layui-inline layui-col-md6">
                <label class="layui-form-label">认领数量</label>
                <div class="layui-input-block" style="width: 80px">
                    <input type="text" name="limit" lay-verify="required|number" placeholder="认领数量" value="" class="layui-input">
                </div>
            </div>
        </div>
        <?php }?>

        <div class="layui-form-item">
            <div class="layui-input-block">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="grab_goods">提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </div>
</form>
