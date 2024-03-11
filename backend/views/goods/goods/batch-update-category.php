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
<form class="layui-form layui-row" id="grab_goods" action="<?=Url::to(['goods/batch-update-category?'.$_SERVER['QUERY_STRING']])?>">
    <div class="layui-col-md9 layui-col-xs12" style="padding: 10px">

        <div class="layui-form-item" style="margin: 20px 0">
            <div class="rc-cascader">
                <input type="text" id="category_id" name="category_id" value="" style="display: none;" />
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
<script id="source_tpl" type="text/html">
</script>

<script id="attribute_tpl" type="text/html">

</script>
<?php \backend\assets\CategoryJsAsset::register($this);?>
<script type="text/javascript">
    var source_method = <?=$source_method?>;
    var source = '';
    var attribute = '';
    var tag_name = '';
    var property = '';
</script>
<?php
$this->registerJsFile("@adminPageJs/goods/form.js?".time());
?>
