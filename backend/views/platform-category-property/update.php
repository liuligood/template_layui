<?php
/**
 * @var $this \yii\web\View;
 */

use common\components\statics\Base;
use yii\helpers\Url;
?>
    <style>
        html {
            background: #fff;
        }
    </style>
    <form class="layui-form layui-row" id="add" action="<?=Url::to(['platform-category-property/update'])?>">

        <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px">

            <div class="layui-form-item">
                <label class="layui-form-label">平台</label>
                <div class="layui-input-block">
                    <?= \yii\helpers\Html::dropDownList('platform_type', $info['platform_type'], Base::$platform_maps,
                        ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style' => 'width:280px']); ?>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">属性id</label>
                <div class="layui-input-block">
                    <input type="text" name="platform_property_id" value="<?=$info['platform_property_id']?>" placeholder="请输入平台属性id" class="layui-input" style="width: 280px">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">名称</label>
                <div class="layui-input-block">
                    <input type="text" name="name" value="<?=$info['name']?>" placeholder="请输入名称" class="layui-input" style="width: 280px">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">额外参数</label>
                <div class="layui-input-block">
                    <textarea type="text" name="param" placeholder="请输入额外参数" class="layui-textarea" style="width: 330px;min-height: 75px"><?=$info['param']?></textarea>
                </div>
            </div>

            <div class="layui-form-item">
                <div class="layui-input-block">
                    <input type="hidden" name="id" value="<?=$info['id']?>">
                    <input type="hidden" name="property_type" value="<?=$info['property_type']?>">
                    <input type="hidden" name="property_id" value="<?=$info['property_id']?>">
                    <button class="layui-btn" lay-submit="" lay-filter="form" data-form="add">立即提交</button>
                    <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                </div>
            </div>
        </div>
    </form>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>