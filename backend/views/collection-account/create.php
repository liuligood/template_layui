<?php
/**
 * @var $this \yii\web\View;
 */

use common\models\Shop;
use yii\bootstrap\Html;
use yii\helpers\Url;
?>
    <style>
        html {
            background: #fff;
        }
    </style>
    <form class="layui-form layui-row" id="add" action="<?=Url::to(['collection-account/create'])?>">

        <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px">

            <div class="layui-form-item">
                <label class="layui-form-label">收款账号</label>
                <div class="layui-input-block">
                    <input type="text" name="collection_account" lay-verify="required" placeholder="请输入收款账号" class="layui-input" style="width: 260px">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">收款平台</label>
                <div class="layui-input-block">
                    <?= Html::dropDownList('collecton_platform',null,Shop::$collection_maps,
                        ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width: 260px']) ?>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">收款归属者</label>
                <div class="layui-input-block">
                    <input type="text" name="collection_owner"  placeholder="请输入收款归属者" class="layui-input" style="width: 260px">
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