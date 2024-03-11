<?php
/**
 * @var $this \yii\web\View;
 */

use common\services\warehousing\WarehouseService;
use yii\helpers\Url;
use yii\helpers\Html;
?>
    <style>
        html {
            background: #fff;
        }
        .layui-form-item{
            margin-bottom: 5px;
        }
    </style>
<form class="layui-form layui-row" id="add" action="<?=Url::to([$model->isNewRecord?'shipping-method/create':'shipping-method/update'])?>">

    <div class="layui-col-md9 layui-col-xs12" style="padding: 10px">

        <div class="layui-card-body">

            <div class="layui-field-box">
                <div class="layui-form-item">
                    <div class="layui-inline" style="width: 400px">
                        <label class="layui-form-label">物流商运输服务名</label>
                        <div class="layui-input-block">
                            <input type="text" name="shipping_method_name" placeholder="请输入物流商运输服务名" value="<?=$model['shipping_method_name']?>" class="layui-input">
                        </div>
                    </div>
                </div>

                <div class="layui-form-item">
                    <div class="layui-inline ">
                        <label class="layui-form-label">货品种类</label>
                        <div class="layui-input-block">
                            <?= Html::dropDownList('electric_status', $model['electric_status'], \common\components\statics\Base::$electric_map,
                                ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:120px']) ?>
                        </div>
                    </div>
                    <div class="layui-inline">
                        <label class="layui-form-label">推荐物流</label>
                        <div class="layui-input-block">
                            <?= Html::dropDownList('recommended', $model['recommended'], \common\models\sys\ShippingMethod::$recommended_map,
                                ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:120px']) ?>
                        </div>
                    </div>
                </div>
                <div class="layui-form-item">
                    <div class="layui-inline">
                        <label class="layui-form-label">状态</label>
                        <div class="layui-input-block">
                            <?= Html::dropDownList('status', $model['status'], \common\models\sys\ShippingMethod::$status_map,
                                ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:120px']) ?>
                        </div>
                    </div>
                    <div class="layui-inline">
                        <label class="layui-form-label">材积</label>
                        <div class="layui-input-block">
                            <input type="text" name="cjz" placeholder="材积" value="<?=$model['cjz']?>" class="layui-input">
                        </div>
                    </div>
                </div>
                <div class="layui-form-item">
                    <div class="layui-inline">
                        <label class="layui-form-label">货币</label>
                        <div class="layui-input-block">
                            <input type="text" name="currency" value="<?=empty($model['currency'])?'CNY':$model['currency']?>" lay-verify="required" placeholder="请输入货币" class="layui-input ">
                        </div>
                    </div>
                    <div class="layui-inline">
                        <label class="layui-form-label">仓库</label>
                        <div class="layui-input-block">
                            <?= Html::dropDownList('warehouse_id', $model['warehouse_id'], WarehouseService::getWarehouseMap(),
                                ['lay-ignore'=>'lay-ignore','prompt' => '请选择','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:180px']) ?>
                        </div>
                    </div>
                </div>
                <div class="layui-form-item">
                    <div class="layui-inline"  style="width: 600px">
                        <label class="layui-form-label">免计泡公式</label>
                        <div class="layui-input-block">
                            <textarea class="layui-textarea" style="height: 100px" name="formula"><?=$model['formula']?></textarea>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="layui-form-item" style="margin-top: 20px">
            <div class="layui-input-block">
                <input type="hidden" name="id" value="<?=$model['id']?>">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="add">立即提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </div>
</form>
<?=$this->registerJsFile("@adminPageJs/base/form.js?v=0.0.1.4")?>