<?php

use common\components\statics\Base;
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
<form class="layui-form layui-row" id="add" action="<?=Url::to(['goods/batch-add-find?'.$_SERVER['QUERY_STRING']])?>" method="post">
    <div class="layui-col-md9 layui-col-xs12" style="padding: 10px">

        <div class="layui-form-item">
            <div class="layui-inline layui-col-md6">
                <label class="layui-form-label">平台</label>
                <div class="layui-input-block">
                    <?php foreach ($platform_types as $k => $v){?>
                        <input type="checkbox" name="platform_type[]" value="<?=$k?>" lay-skin="primary" title="<?=$v?>">
                    <?php } ?>
                </div>
            </div>
        </div>

        <div class="layui-form-item">
            <div class="layui-input-block">
                <input type="hidden" name="id" value="<?=$id?>">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="add">提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </div>
</form>
<?php
$this->registerJsFile("@adminPageJs/base/form.js?".time());
?>
