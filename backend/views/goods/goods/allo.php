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
<form class="layui-form layui-row" id="grab_goods" action="<?=Url::to(['goods/batch-allo?'.$_SERVER['QUERY_STRING']])?>">
    <div class="layui-col-md9 layui-col-xs12" style="padding: 10px">

        <div class="layui-form-item" style="margin: 20px 0">
            <div class="layui-inline layui-col-md3">
                <?= \yii\helpers\Html::dropDownList('admin_id', null, $admin_lists,
                    ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
            </div>
        </div>

        <?php if (empty($id)) {?>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md6">
                    <label class="layui-form-label">分配数量</label>
                    <div class="layui-input-block" style="width: 80px">
                        <input type="text" name="limit" lay-verify="required|number" placeholder="分配数量" value="" class="layui-input">
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
<?php
$this->registerJsFile("@adminPageJs/goods/grab.js?".time());
?>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>

