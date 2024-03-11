<?php
/**
 * @var $this \yii\web\View;
 */

use common\components\statics\Base;
use yii\bootstrap\Html;
use yii\helpers\Url;
?>
    <style>
        html {
            background: #fff;
        }
    </style>
    <form class="layui-form layui-row" id="add" action="<?=Url::to(['goods-error-solution/create'])?>">

        <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px">

            <div class="layui-form-item">
                <label class="layui-form-label">平台</label>
                <div class="layui-input-block" style="width: 225px">
                    <?= Html::dropDownList('platform_type',null,Base::$platform_maps,
                        ['lay-ignore'=>'lay-ignore', 'class'=>'layui-input search-con ys-select2','lay-search'=>'lay-search']) ?>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">错误信息</label>
                <div class="layui-input-block">
                    <textarea type="text" name="error_message" lay-verify="required" placeholder="请输入错误信息" class="layui-input" style="height: 75px"></textarea>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">解决方案</label>
                <div class="layui-input-block">
                    <textarea type="text" name="solution"  placeholder="请输入解决方案" class="layui-input" style="height: 75px"></textarea>
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