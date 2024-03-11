<?php
/**
 * @var $this \yii\web\View;
 */
use yii\helpers\Url;
use yii\helpers\Html;
?>
    <style>
        html {
            background: #fff;
        }
    </style>
    <form class="layui-form layui-row" id="add" action="<?=Url::to(['configs/create'])?>">

        <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px">

            <div class="layui-form-item">
                <label class="layui-form-label">配置代码</label>
                <div class="layui-input-block">
                    <input type="text" name="code" lay-verify="required" placeholder="请输入配置代码" class="layui-input">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">配置名称</label>
                <div class="layui-input-block">
                    <input type="text" name="name" lay-verify="required" placeholder="请输入配置名称" class="layui-input ">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">类型</label>
                <div class="layui-input-block">
                    <?= Html::dropDownList('type', null, \common\models\Configs::$type_map,
                        ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">输入框宽度</label>
                <div class="layui-input-block">
                    <input type="text" name="width" lay-verify="required" placeholder="输入框宽度" class="layui-input ">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">配置值</label>
                <div class="layui-input-block">
                    <input type="text" name="value" lay-verify="required" placeholder="请输入配置值" class="layui-input ">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">选项</label>
                <div class="layui-input-block">
                    <input type="text" name="option"  placeholder="请输入选项" class="layui-input ">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">说明</label>
                <div class="layui-input-block">
                    <input type="text" name="desc" placeholder="请输入说明" class="layui-input ">
                </div>
            </div>
            <div class="layui-form-item">
                <div class="layui-input-block">
                    <button class="layui-btn" lay-submit="" lay-filter="form" data-form="add">立即提交</button>
                    <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                </div>
            </div>
        </div>
    </form>

<?=$this->registerJsFile("@adminPageJs/base/form.js")?>