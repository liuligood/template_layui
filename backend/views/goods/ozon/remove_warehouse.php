<?php

/**
 * @var $this \yii\web\View;
 */

use yii\helpers\Url;

?>
    <style xmlns="http://www.w3.org/1999/html">
        html {
            background: #fff;
        }
        .layui-form-item{
            margin-bottom: 5px;
        }
        .update_table {
            border: none;
            background-color: rgba(0, 0, 0, 0);
            -webkit-user-select:text;
        }
    </style>
    <form class="layui-form layui-row" id="add" action="<?= Url::to(['goods-ozon/set-warehouse']) ?>">

        <div class="layui-col-md6 layui-col-xs12" style="padding: 40px 60px">
            <div class="layui-form-item">
                <?= \yii\helpers\Html::dropDownList('warehouse_id', '', \backend\controllers\GoodsOzonController::$usmap,
                    ['lay-ignore' => 'lay-ignore', 'data-placeholder' => '请输入仓库', 'prompt' => '无', 'class' => 'layui-input search-con ys-select2', 'lay-search' => 'lay-search']) ?>
            </div>
            <div class="layui-form-item">
                <div class="layui-input-block">
                    <input type="hidden" name="ids" value="<?= $data['ids'] ?>">
                    <button class="layui-btn" lay-submit="" lay-filter="form" data-form="add">立即提交</button>

                </div>
            </div>
        </div>
    </form>
<?= $this->registerJsFile("@adminPageJs/base/form.js") ?>