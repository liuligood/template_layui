<?php

use common\components\statics\Base;
use common\models\warehousing\WarehouseProvider;
use yii\bootstrap\Html;
use yii\helpers\Url;
?>
    <style>
        html {
            background: #fff;
        }
    </style>
    <form class="layui-form layui-row" id="add" action="<?=Url::to(['warehouse-provider/create'])?>">

        <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px">

            <div class="layui-form-item">
                <label class="layui-form-label">供应商名称</label>
                <div class="layui-input-block">
                    <input type="text" name="warehouse_provider_name" lay-verify="required" placeholder="请输入供应商名称" class="layui-input" style="width: 270px">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">供应商类型</label>
                <div class="layui-input-block">
                    <?= Html::dropDownList('warehouse_provider_type',null,\common\models\warehousing\WarehouseProvider::$type_maps,
                        ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style' => 'width:270px']) ?>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">状态</label>
                <div class="layui-input-block">
                    <?= Html::dropDownList('status',null,WarehouseProvider::$status_maps,
                        ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style' => 'width:270px']) ?>
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