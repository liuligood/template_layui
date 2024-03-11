<?php

use common\components\statics\Base;
use yii\bootstrap\Html;
use yii\helpers\Url;
?>
    <style>
        html {
            background: #fff;
        }
    </style>
    <form class="layui-form layui-row" id="update" action="<?=Url::to(['goods-error-solution/update'])?>">

        <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px">

            <?php if (!$goods){?>
            <div class="layui-form-item">
                <label class="layui-form-label">平台</label>
                <div class="layui-input-block" style="width: 225px">
                    <?= Html::dropDownList('platform_type',$info['platform_type'],Base::$platform_maps,
                    ['lay-ignore'=>'lay-ignore', 'class'=>'layui-input search-con ys-select2','lay-search'=>'lay-search']) ?>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">错误信息</label>
                <div class="layui-input-block">
                    <textarea type="text" name="error_message"  lay-verify="required" placeholder="请输入错误信息" class="layui-input" style="height: 75px"><?=$info['error_message']?></textarea>
                </div>
            </div>
            <?php } else{?>
                <div class="layui-form-item">
                    <label class="layui-form-label">错误信息</label>
                    <div class="layui-input-block">
                        <label class="layui-form-label" style="width: 350px;text-align: left"><?=$info['error_message']?></label>
                    </div>
                </div>
            <?php }?>
            <div class="layui-form-item">
                <label class="layui-form-label">解决方案</label>
                <div class="layui-input-block">
                    <textarea type="text" name="solution"  placeholder="请输入解决方案" class="layui-input" style="height: 75px"><?=$info['solution']?></textarea>
                </div>
            </div>

            <div class="layui-form-item">
                <div class="layui-input-block">
                    <input type="hidden" name="id" value="<?=$info['id']?>">
                    <button class="layui-btn" lay-submit="" lay-filter="form" data-form="update">立即提交</button>
                    <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                </div>
            </div>
        </div>
    </form>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>