<?php
use yii\helpers\Url;
?>
<style>
    html {
        background: #fff;
    }
</style>
<form class="layui-form layui-row" id="category_update" action="<?=Url::to(['category/update'])?>">

<div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px">

    <!--<div class="layui-form-item">
        <div class="layui-inline layui-col-md6">
        <label class="layui-form-label">上级类目</label>
        <div class="layui-input-block">
            <div id="parent" class="xm-select-demo"></div>
        </div>
        </div>
    </div>-->
    <div class="layui-form-item">
        <div class="layui-inline layui-col-md6">
            <label class="layui-form-label">上级类目</label>
            <div class="layui-input-block">
                <div class="rc-cascader">
                    <input class="layui-input" type="text" id="category_id" value="<?=$category_info['parent_id']?>" name="parent_id" style="display: none;" />
                </div>
            </div>
        </div>
    </div>

    <div class="layui-form-item">
        <div class="layui-inline layui-col-md6">
        <label class="layui-form-label">类目编号</label>
        <div class="layui-input-block">
            <input type="text" name="sku_no" placeholder="请输入类目编号"  lay-verify="required"  value="<?=$category_info['sku_no']?>" class="layui-input ">
        </div>
        </div>
    </div>

    <div class="layui-form-item">
        <div class="layui-inline layui-col-md6">
        <label class="layui-form-label">类目名称</label>
        <div class="layui-input-block">
            <input type="text" name="name" placeholder="请输入类目名称"  lay-verify="required"  value="<?=$category_info['name']?>" class="layui-input ">
        </div>
        </div>
    </div>

    <div class="layui-form-item">
        <div class="layui-inline layui-col-md6">
        <label class="layui-form-label">类目名称(EN)</label>
        <div class="layui-input-block">
            <input type="text" name="name_en" placeholder="请输入类目EN名称"  lay-verify="required"  value="<?=$category_info['name_en']?>" class="layui-input ">
        </div>
        </div>
    </div>
    <div class="layui-form-item">
        <div class="layui-inline layui-col-md6">
            <label class="layui-form-label">海关编码</label>
            <div class="layui-input-block">
                <input type="text" name="hs_code" placeholder="请输入海关编码"    value="<?=$category_info['hs_code']?>" class="layui-input ">
            </div>
        </div>
    </div>

    <div class="layui-form-item">
        <div class="layui-inline layui-col-md6">
        <label class="layui-form-label">排序</label>
        <div class="layui-input-block">
            <input type="text" name="sort" placeholder="值越大越靠前" value="<?=$category_info['sort']?>" class="layui-input ">
        </div>
        </div>
    </div>

    <div class="layui-form-item">
        <div class="layui-input-block">
            <input type="hidden" id="category_mine_id" name="category_id" value="<?=$category_info['id']?>">
            <button class="layui-btn" lay-submit="" lay-filter="form" data-form="category_update">立即提交</button>
            <button type="reset" class="layui-btn layui-btn-primary">重置</button>
        </div>
    </div>
</div>
</form>
<?php \backend\assets\CategoryJsAsset::register($this);?>
<script>
    const categoryUpdateUrl="<?=Url::to(['category/update'])?>"
    const categoryArr = '<?=json_encode($category_arr)?>'
</script>

<?php $this->registerJsFile("@adminPageJs/category/update.js?v=".time())?>
<?=$this->registerJsFile("@adminPageJs/base/form.js?v=0.0.7")?>