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
<form class="layui-form layui-row" id="update_mapping" action="<?=Url::to(['independence-category/mapping?id='.$info['id']])?>">
    <div class="layui-col-md9 layui-col-xs12" style="padding: 10px;margin-top: 30px">

        <div class="layui-form-item">
            <div class="layui-inline layui-col-md6">
                <label class="layui-form-label">类目映射</label>
                <div class="layui-input-block">
                    <input type="text" lay-verify="number" placeholder="请输入类目映射" name="mapping" value="<?=$info['mapping']?>" class="layui-input" style="width: 300px"/>
                </div>
            </div>
        </div>

        <div class="layui-form-item">
            <div class="layui-input-block">
                <input type="hidden" name="id" value="<?=$info['id']?>">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="update_mapping">提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </div>
</form>
<?php
$this->registerJsFile("@adminPageJs/base/form.js?".time());
?>
