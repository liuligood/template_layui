<?php

use common\components\statics\Base;
use common\models\WarehouseProvider;
use common\services\sys\CountryService;
use yii\bootstrap\Html;
use yii\helpers\Url;
?>
    <style>
        html {
            background: #fff;
        }
    </style>
    <form class="layui-form layui-row" id="add" action="<?=Url::to(['warehouse/update'])?>">

        <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px">

            <div class="layui-form-item">
                <label class="layui-form-label">仓库名称</label>
                <div class="layui-input-block">
                    <input type="text" name="warehouse_name" value="<?=$info['warehouse_name']?>"  lay-verify="required" placeholder="请输入供应商仓库名称" class="layui-input" style="width: 270px">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">仓库编码</label>
                <div class="layui-input-block">
                    <input type="text" name="warehouse_code" value="<?=$info['warehouse_code']?>"  placeholder="请输入供应商仓库编码" class="layui-input" style="width: 270px">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">所属平台</label>
                <div class="layui-input-block">
                    <?= Html::dropDownList('platform_type',$info['platform_type'],Base::$platform_maps,
                        ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style' => 'width:270px']) ?>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">所在国家</label>
                <div class="layui-input-block">
                    <?= Html::dropDownList('country',$info['country'],CountryService::getSelectOption(),
                        ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style' => 'width:270px']) ?>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">可发国家</label>
                <div class="layui-input-block">
                    <?= Html::dropDownList('eligible_country[]',$info['eligible_country'],CountryService::getSelectOption(),
                        ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style' => 'width:270px',"multiple"=>"multiple"]) ?>
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
                    <input type="hidden" name="warehouse_provider_id" value="<?=$warehouse_provider_id?>">
                    <input type="hidden" name="id" value="<?=$info['id']?>">
                    <button class="layui-btn" lay-submit="" lay-filter="form" data-form="add">立即提交</button>
                    <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                </div>
            </div>
        </div>
    </form>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>