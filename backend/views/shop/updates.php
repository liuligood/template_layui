<?php
/**
 * @var $this \yii\web\View;
 */
use yii\helpers\Url;
use yii\helpers\Html;
use common\models\Shop;

$admin_id = new Shop();
?>
    <style>
        html {
            background: #fff;
        }
        .layui-form-item{
            margin-bottom: 5px;
        }
    </style>
<form class="layui-form layui-row" id="updates" action="<?=Url::to(['shop/updates?'.$_SERVER['QUERY_STRING']])?>">

    <div class="layui-col-md9 layui-col-xs12" style="padding: 10px">

        <div class="layui-card-body">

    		<div class="layui-form-item">
                <label class="layui-form-label">负责人</label>
                <div class="layui-input-block">
                    <?= Html::dropDownList('admin_id',null,$admin_id->adminArr(),
                    ['lay-ignore'=>'lay-ignore','data-placeholder' => '请选择店铺负责人','prompt' => '无','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
                </div>
    		</div>
        </div>

        <div class="layui-form-item" style="margin-top: 20px">
            <div class="layui-input-block">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="updates">立即提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </div>
</form>
<?=$this->registerJsFile("@adminPageJs/base/form.js?v=0.0.1.4")?>
