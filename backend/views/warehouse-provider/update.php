<?php

use common\components\statics\Base;
use common\models\WarehouseProvider;
use yii\bootstrap\Html;
use yii\helpers\Url;
?>
    <style>
        html {
            background: #fff;
        }
    </style>
    <form class="layui-form layui-row" id="add" action="<?=Url::to(['warehouse-provider/update'])?>">

        <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px">

            <div class="layui-form-item">
                <label class="layui-form-label">供应商名称</label>
                <div class="layui-input-block">
                    <input type="text" name="warehouse_provider_name" value="<?=$info['warehouse_provider_name']?>" lay-verify="required" placeholder="请输入供应商名称" class="layui-input" style="width: 270px">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">供应商类型</label>
                <div class="layui-input-block">
                    <?= Html::dropDownList('warehouse_provider_type',$info['warehouse_provider_type'],\common\models\warehousing\WarehouseProvider::$type_maps,
                        ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style' => 'width:270px']) ?>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">状态</label>
                <div class="layui-input-block">
                    <?= Html::dropDownList('status',$info['status'],\common\models\warehousing\WarehouseProvider::$status_maps,
                        ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style' => 'width:270px']) ?>
                </div>
            </div>

            <div class="layui-form-item">
                <div class="layui-input-block">
                    <input type="hidden" name="id" value="<?=$info['id']?>">
                    <button class="layui-btn" lay-submit="" lay-filter="form" data-form="add">立即提交</button>
                    <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                </div>
            </div>
        </div>
    </form>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>