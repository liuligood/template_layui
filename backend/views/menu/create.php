<?php
use yii\helpers\Url;
?>
<style>
    html {
        background: #fff;
    }
</style>
<form class="layui-form layui-row" lay-filter="create-menuForm">

    <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px">

        <div class="layui-form-item">
            <label class="layui-form-label">上级菜单名称</label>
            <div class="layui-input-block">
                <input type="text" name="parent_name" value="<?=$parent_info['name']?>" disabled class="layui-input layui-disabled">
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">链接地址</label>
            <div class="layui-input-block">
                <input type="text" name="route" id="route-select" placeholder="请输入链接地址" autocomplete="off" class="layui-input ">
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label">菜单名称</label>
            <div class="layui-input-block">
                <input type="text" name="name" placeholder="请输入菜单名称" lay-verify="required" class="layui-input ">
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">菜单图标</label>
            <div class="layui-input-block">
                <input type="text" name="icon" placeholder="菜单图标"  id="iconPicker" lay-filter="iconPicker" class="layui-input layui-icon">
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">排序</label>
            <div class="layui-input-block">
                <input type="text" name="order" placeholder="值越大越靠前"  class="layui-input ">
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">状态</label>
            <div class="layui-input-block">
                <?= \yii\helpers\Html::dropDownList('status', 1, \backend\controllers\MenuController::$status_map,
                    ['prompt' => '全部','lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
            </div>
        </div>
        <div class="layui-form-item">
            <div class="layui-input-block">
                <input type="hidden" name="parent_id" value="<?=$parent_info['id']?>">
                <button class="layui-btn" lay-submit="" lay-filter="createMenu">立即提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </div>
</form>
<script>
    const routeAssignedListUrl="<?=Url::to(['route/assigned-list'])?>"
    const menuCreateUrl="<?=Url::to(['menu/create'])?>"
</script>

<?=$this->registerJsFile("@adminPageJs/menu/create.js")?>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>

