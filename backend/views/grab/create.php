<?php
/**
 * @var $this \yii\web\View;
 */
use yii\helpers\Url;
?>
<style>
    html {
        background: #fff;
    }
</style>
<form class="layui-form layui-row" id="add_grab" action="<?=Url::to(['grab/create'])?>" style="height:435px ;padding-top: 15px">

    <div class="layui-col-md6 layui-col-xs12" >

        <div class="layui-form-item">
            <label class="layui-form-label">标题</label>
            <div class="layui-input-block">
                <input type="text" name="title" lay-verify="required" placeholder="请输入标题" class="layui-input ">
            </div>
        </div>

        <?php if ($source_method == \common\services\goods\GoodsService::SOURCE_METHOD_OWN){?>
        <div class="layui-form-item">
            <div class="layui-inline layui-col-md6">
                <label class="layui-form-label">分类</label>
                <div class="layui-input-block">
                    <div class="rc-cascader">
                        <input type="text"  id="category_id" name="category_id" value="" style="display: none;" />
                    </div>
                </div>
            </div>
        </div>
        <?php }?>

        <div class="layui-form-item">
            <label class="layui-form-label">采集链接</label>
            <div class="layui-input-block">
                <input type="text" name="url" lay-verify="required" placeholder="请输入采集链接" class="layui-input">
            </div>
        </div>

        <div class="layui-form-item" style="width: 180px">
            <label class="layui-form-label">采集页数</label>
            <div class="layui-input-block">
                <input type="text" name="page" placeholder="请输入采集页数" class="layui-input" value="1" >
            </div>
        </div>
        <?php if ($source_method == \common\services\goods\GoodsService::SOURCE_METHOD_OWN){?>
        <div class="layui-form-item" style="width: 180px">
            <label class="layui-form-label">价格系数(乘以)</label>
            <div class="layui-input-block">
                <input type="text" name="price_calculation" placeholder="请输入价格系数" class="layui-input" value="0" >
            </div>
        </div>
        <?php }?>

        <div class="layui-form-item">
            <div class="layui-input-block">
                <input type="hidden" name="source_method" value="<?=$source_method?>" class="layui-input">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="add_grab">开始采集</button>
            </div>
        </div>
    </div>
</form>
<?php \backend\assets\CategoryJsAsset::register($this);?>
<script type="text/javascript">
    var source_method = <?=$source_method?>
</script>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>
<?=$this->registerJsFile("@adminPageJs/grab-goods/form.js?".time())?>

