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
<form class="layui-form layui-row" id="grab_goods" action="<?=Url::to(['goods-'.$url_platform_name.'/init-info?id='.$id])?>">
    <div class="layui-col-md9 layui-col-xs12" style="padding: 10px">

        <div class="layui-form-item">
            <div class="layui-inline layui-col-md6">
                <label class="layui-form-label">重置类型</label>
                <div class="layui-input-block">
                    <input type="checkbox" lay-skin="primary" name="type[]" value="1" title="分类/属性" checked>
                    <?php if($platform_type == \common\components\statics\Base::PLATFORM_OZON){?>
                        <input type="checkbox" lay-skin="primary" name="type[]" value="2" title="重量/尺寸">
                    <?php }?>
                    <input type="checkbox" lay-skin="primary" name="type[]" value="3" title="商品标题">
                    <input type="checkbox" lay-skin="primary" name="type[]" value="4" title="商品描述">
                </div>
            </div>
        </div>

        <div class="layui-form-item" style="margin-top: 20px">
            <div class="layui-input-block">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="grab_goods">提交</button>
                <button class="layui-btn" lay-submit="" lay-filter="form" data-save="release" data-form="grab_goods">提交并发布</button>
            </div>
        </div>
    </div>
</form>
<?php
$this->registerJsFile("@adminPageJs/base/form.js?".time());