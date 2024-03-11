<?php
/**
 * Created by PhpStorm.
 * User: ahanfeng
 * Date: 18-12-14
 * Time: 下午2:39
 */
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
<form class="layui-form layui-row" id="category_update" action="<?=Url::to(['category/create'])?>">

    <div class="layui-col-md9 layui-col-xs12" style="padding-top: 15px">

        <div class="layui-form-item">
            <div class="layui-inline layui-col-md6">
                <label class="layui-form-label">上级类目名称</label>
                <div class="layui-input-block">
                    <div class="rc-cascader">
                        <input class="layui-input" type="text" id="category_id" name="parent_id" style="display: none;" />
                    </div>
                </div>
            </div>
        </div>

        <div class="layui-form-item">
            <div class="layui-inline layui-col-md6">
                <label class="layui-form-label">类目编号</label>
                <div class="layui-input-block">
                    <input type="text" name="sku_no" placeholder="请输入类目编号" lay-verify="required"  value="" class="layui-input ">
                </div>
            </div>
        </div>

        <div class="layui-form-item">
            <div class="layui-inline layui-col-md6">
            <label class="layui-form-label">类目名称</label>
            <div class="layui-input-block">
                <input type="text" name="name" placeholder="请输入类目名称" lay-verify="required" class="layui-input ">
            </div>
            </div>
        </div>
        <div class="layui-form-item">
            <div class="layui-inline layui-col-md6">
                <label class="layui-form-label">类目名称(EN)</label>
                <div class="layui-input-block">
                    <input type="text" name="name_en" placeholder="请输入类目EN名称"  lay-verify="required" class="layui-input ">
                </div>
            </div>
        </div>

        <div class="layui-form-item">
            <div class="layui-inline layui-col-md6">
            <label class="layui-form-label">排序</label>
            <div class="layui-input-block">
                <input type="text" name="sort" placeholder="值越大越靠前" class="layui-input">
            </div>
            </div>
        </div>

        <div class="layui-form-item">
            <div class="layui-input-block">
                <input type="hidden" name="source_method" value="<?=$source_method?>">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="category_update">立即提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </div>
</form>
<?php \backend\assets\CategoryJsAsset::register($this);?>
<?=$this->registerJsFile("@adminPageJs/base/form.js?v=0.0.7")?>
<?=$this->registerJsFile("@adminPageJs/category/update.js?v=0.0.7")?>
