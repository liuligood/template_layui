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
<form class="layui-form layui-row" id="grab_goods" action="<?=Url::to(['financial-platform-sales-period/change-again?id='.$model['id']])?>">
    <div class="layui-col-md9 layui-col-xs12" style="padding: 0 10px">

        <div class="layui-form-item layui-col-md10" style="width: 430px;padding-top: 30px;">
            <label class="layui-form-label">备注</label>
            <div class="layui-input-block">
                <textarea type="text" name="remark"  placeholder="请输入备注" style="height: 100px;" class="layui-input"><?=$model['remark']?></textarea>
            </div>
        </div>

        <div class="layui-form-item">
            <div class="layui-input-block">
                <input type="hidden" name="id" value="<?=$model['id']?>">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="grab_goods">提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </div>
</form>
<?php
$this->registerJsFile("@adminPageJs/base/form.js?".time());
$this->registerJsFile("@adminPageJs/financial-platform-sales-period/collection.js?".time());

?>
