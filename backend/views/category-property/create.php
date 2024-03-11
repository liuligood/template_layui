<?php
/**
 * @var $this \yii\web\View;
 */
use yii\helpers\Url;
use yii\helpers\Html;
?>
    <style>
        html {
            background: #fff;
        }
        .ys-select2{float: left}
        .select2{float: left}
    </style>
    <form class="layui-form layui-row" id="add" action="<?=Url::to(['category-property/create'])?>">

        <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px">

            <div class="layui-form-item">
                <label class="layui-form-label">类目</label>
                <div class="rc-cascader" style="float: left">
                    <input type="text" id="category_id" name="category_id" value="" style="display: none;" />
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">属性类型</label>
                <?= Html::dropDownList('property_type', null,\backend\controllers\CategoryPropertyController::$map ,
                    ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:220px' ]) ?>

                <label class="layui-form-label">属性名称</label>
                <div class="layui-input-inline">
                    <input type="text" name="property_name" lay-verify="required" placeholder="请输入属性名称" class="layui-input"  style="width: 220px">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">是否必选</label>
                <?= Html::dropDownList('is_required',\backend\controllers\CategoryPropertyController::TWO ,\backend\controllers\CategoryPropertyController::$map_two ,
                    ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:220px' ]) ?>

                <label class="layui-form-label">是否多选</label>
                <?= Html::dropDownList('is_multiple', \backend\controllers\CategoryPropertyController::TWO,\backend\controllers\CategoryPropertyController::$map_two  ,
                    ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:220px' ]) ?>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">宽度</label>
                <div class="layui-input-inline" style="width: 210px">
                    <input type="text" name="width" lay-verify="required" value="200" placeholder="请输入宽度" class="layui-input"  style="width: 220px">
                </div>

                <label class="layui-form-label">单位</label>
                <div class="layui-input-inline">
                    <input type="text" name="unit" placeholder="请输入单位" class="layui-input"  style="width: 220px">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">其他</label>
                <?= Html::dropDownList('is_other', \backend\controllers\CategoryPropertyController::TWO,\backend\controllers\CategoryPropertyController::$map_two  ,
                    ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:220px' ]) ?>

                <label class="layui-form-label">排序</label>
                <div class="layui-input-inline">
                    <input type="text" name="sort" placeholder="请输入排序，值越大越靠前" class="layui-input"  style="width: 220px">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">状态</label>
                <?= Html::dropDownList('status', null,\backend\controllers\CategoryPropertyController::$map_tre ,
                    ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:220px' ]) ?>
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
<?php
$this->registerJsFile("@adminPageJs/category-property/froms.js?");
?>
<?php \backend\assets\CategoryJsAsset::register($this);?>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>
