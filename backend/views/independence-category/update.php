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
    .layui-input {
        width: 490px;
    }
    .el-input {
        width: 490px;
    }
</style>
<form class="layui-form layui-row" id="category_update" action="<?=Url::to(['independence-category/update?id='.$info['id']])?>">

    <div class="layui-col-md9 layui-col-xs12" style="padding-top: 15px">

        <div class="layui-form-item">
            <div class="layui-inline layui-col-md6">
                <label class="layui-form-label">上级类目名称</label>
                <label class="layui-form-label" style="text-align: left"><?=$parent_name?></label>
            </div>
        </div>

        <div class="layui-form-item">
            <div class="layui-inline layui-col-md6">
                <label class="layui-form-label">商品类目名称</label>
                <div class="layui-input-block">
                    <div class="rc-cascader">
                        <input class="layui-input" type="text" id="category_id" value="<?=$info['category_id']?>" name="category_id" style="display: none;" />
                    </div>
                </div>
            </div>
        </div>

        <div class="layui-form-item">
            <div class="layui-inline layui-col-md6">
                <label class="layui-form-label">类目名称</label>
                <div class="layui-input-block">
                    <input type="text" name="name" value="<?=$info['name']?>"  placeholder="请输入类目名称" class="layui-input ">
                </div>
            </div>
        </div>

        <div class="layui-form-item">
            <div class="layui-inline layui-col-md6">
                <label class="layui-form-label">类目名称(EN)</label>
                <div class="layui-input-block">
                    <input type="text" name="name_en" value="<?=$info['name_en']?>"  placeholder="请输入类目EN名称"  class="layui-input ">
                </div>
            </div>
        </div>

        <div class="layui-form-item">
            <div class="layui-inline layui-col-md6">
                <label class="layui-form-label">排序</label>
                <div class="layui-input-block">
                    <input type="text" name="sort" value="<?=$info['sort']?>"  placeholder="值越大越靠前" class="layui-input">
                </div>
            </div>
        </div>

        <div class="layui-form-item">
            <div class="layui-input-block">
                <input type="hidden" name="platform_type" value="<?=$info['platform_type']?>">
                <input type="hidden" name="parent_id" value="<?=$info['parent_id']?>">
                <input type="hidden" name="id" value="<?=$info['id']?>">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="category_update">立即提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </div>
</form>
<?php \backend\assets\CategoryJsAsset::register($this);?>
<?=$this->registerJsFile("@adminPageJs/base/form.js?v=0.0.7")?>
<?=$this->registerJsFile("@adminPageJs/category/update.js?v=".time())?>

