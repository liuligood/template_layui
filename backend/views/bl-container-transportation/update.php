<?php
/**
 * @var $this \yii\web\View;
 */

use common\models\warehousing\BlContainer;
use common\services\warehousing\WarehouseService;
use yii\helpers\Url;
use yii\helpers\Html;
?>
<style>
    html {
        background: #fff;
    }
    .country {
        width: 200px;
        padding-left: 7px;
        padding-right: 15px;
    }
</style>
<form class="layui-form layui-row" id="add" action="<?=Url::to(['bl-container-transportation/update'])?>">
    <div class="layui-col-md12   layui-col-xs12" style="padding-top: 15px; padding-left: 60px;">
        <div class="layui-form-item" >
            国家
            <div class="layui-inline country">
                <?= Html::dropDownList('country',$info['country'], \common\services\sys\CountryService::getSelectOption(),
                    ['lay-ignore'=>'lay-ignore','class'=>"layui-input ys-select2"]) ?>
            </div>

            物流编号
            <div class="layui-inline country">
                <input type="text" name="track_no" value="<?=$info['track_no']?>" lay-verify="required" placeholder="请输入物流编号" class="layui-input ">
            </div>

        </div>

        <div class="layui-form-item">
            仓库
            <div class="layui-inline country" style="width: 150px">
                <?= Html::dropDownList('warehouse_id',$info['warehouse_id'], WarehouseService::getOverseasWarehouse(),
                    ['lay-ignore'=>'lay-ignore','class'=>"layui-input ys-select2"]) ?>
            </div>


            运输方式
            <div class="layui-inline country" style="width: 130px">
                <?= Html::dropDownList('transport_type',$info['transport_type'], BlContainer::$transport_maps,
                    ['lay-ignore'=>'lay-ignore','class'=>"layui-input ys-select2"]) ?>
            </div>
        </div>

        <div class="layui-form-item">
            单价
            <div class="layui-inline country" style="width: 130px">
                <input type="text" name="unit_price" value="<?=$info['unit_price']?>" lay-verify="required" placeholder="请输入单价"  class="layui-input">
            </div>

            价格
            <div class="layui-inline country" style="width: 130px">
                <input type="text" name="price" value="<?=$info['price']?>" lay-verify="required" placeholder="请输入价格"  class="layui-input">
            </div>
        </div>

        <div class="layui-form-item" >
            重量
            <div class="layui-inline country" style="width: 130px">
                <input type="text" name="weight" value="<?=$info['weight']?>" lay-verify="required" placeholder="请输入重量"  class="layui-input">
            </div>

            材积
            <div class="layui-inline country" style="width: 150px">
                <input type="text" name="cjz" value="<?=$info['cjz']?>" lay-verify="required" placeholder="请输入材积重"  class="layui-input">
            </div>
        </div>

        <div class="layui-form-item">
            发货时间
            <div class="layui-inline" style="width: 200px">
                <input class="layui-input search-con ys-date"  name="delivery_time" id="delivery_time"  autocomplete="off" value="<?=$info['delivery_time'] == 0 ? '' : date('Y-m-d',$info['delivery_time'])?>" placeholder="请选择发货时间" >
            </div>

            预计到达时间
            <div class="layui-inline" style="width: 200px">
                <input class="layui-input search-con ys-date"  name="arrival_time" id="arrival_time" autocomplete="off" value="<?=$info['arrival_time'] == 0 ? '' : date('Y-m-d',$info['arrival_time'])?>"  placeholder="请选择预计到达时间" >
            </div>
        </div>

        <div class="layui-form-item layui-layout-admin">
            <div class="layui-input-block">
                <input type="hidden" name="id" value="<?=$info['id']?>">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="add">立即提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </div>
</form>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>
