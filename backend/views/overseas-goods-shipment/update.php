<?php
/**
 * @var $this \yii\web\View;
 */

use common\models\Supplier;
use yii\helpers\Html;
use yii\helpers\Url;
?>
    <style>
        html {
            background: #fff;
        }
    </style>
    <form class="layui-form layui-row" id="update" action="<?=Url::to(['overseas-goods-shipment/update?id='.$info['id']])?>">

        <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px">

            <div class="layui-form-item">
                <label class="layui-form-label">供应商</label>
                <div class="layui-input-block">
                    <?= Html::dropDownList('supplier_id',$info['supplier_id'],[0 => '无供应商'] + Supplier::allSupplierName(),
                        ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search', 'style' => 'width:270px']) ?>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">采购数量</label>
                <div class="layui-input-block">
                    <input type="text" name="num" lay-verify="required" value="<?=$info['num']?>" placeholder="请输入采购数量" class="layui-input " style="width: 270px">
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