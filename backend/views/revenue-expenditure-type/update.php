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
    <form class="layui-form layui-row" id="update" action="<?=Url::to(['revenue-expenditure-type/update'])?>">

        <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px">

            <div class="layui-form-item">
                <label class="layui-form-label">类型名称</label>
                <div class="layui-input-block">
                    <input type="text" name="name" lay-verify="required" value="<?=$info['name']?>" placeholder="请输入类型名称" class="layui-input ">
                </div>
            </div>

            <div class="layui-form-item">
                <div class="layui-input-block">
                    <input value="<?=$info['id']?>" name="id" type="hidden">
                    <button class="layui-btn" lay-submit="" lay-filter="form" data-form="update">立即提交</button>
                    <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                </div>
            </div>
        </div>
    </form>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>