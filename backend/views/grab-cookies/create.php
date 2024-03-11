<?php
/**
 * @var $this \yii\web\View;
 */

use common\components\statics\Base;
use common\models\grab\GrabCookies;
use yii\helpers\Url;
?>
    <style>
        html {
            background: #fff;
        }
    </style>
    <form class="layui-form layui-row" id="add" action="<?=Url::to(['grab-cookies/create'])?>">

        <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px">

            <div class="layui-form-item">
                <label class="layui-form-label">cookie</label>
                <div class="layui-input-block">
                    <textarea name="cookie" lay-verify="required" placeholder="请输入cookie" class="layui-textarea" style="width: 420px;height: 150px"></textarea>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">平台</label>
                <div class="layui-input-block">
                    <?= \yii\helpers\Html::dropDownList('platform_type', Base::PLATFORM_OZON, Base::$platform_maps,
                        ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:200px']); ?>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">状态</label>
                <div class="layui-input-block">
                    <?= \yii\helpers\Html::dropDownList('status', null, GrabCookies::$status_maps,
                        ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:200px']); ?>
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