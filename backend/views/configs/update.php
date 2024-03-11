<?php
use yii\helpers\Url;
use yii\helpers\Html;
?>
    <style>
        html {
            background: #fff;
        }
    </style>
    <form class="layui-form layui-row" id="update" action="<?=Url::to(['configs/update'])?>"

    <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px">

        <div class="layui-form-item">
            <label class="layui-form-label">配置代码</label>
            <div class="layui-input-block"style="width: 200px">
                <input type="text" name="code" lay-verify="required" placeholder="请输入配置代码" value="<?=$info['code']?>"  class="layui-input">
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">配置名称</label>
            <div class="layui-input-block"style="width: 200px">
                <input type="text" name="name" lay-verify="required" placeholder="请输入配置名称" value="<?=$info['name']?>"  class="layui-input">
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">类型</label>
            <div class="layui-input-inline" style="width: 200px">
                <?= Html::dropDownList('type', null, \common\models\Configs::$type_map,['options' => [\common\models\Configs::$type_change_map[$info['type']] => ['selected' => true]],'lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search' ]) ?>
            </div>
            <div class="layui-form-mid layui-word-aux">当选择单选，多选，下拉时注意必须将选项填上；</div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">输入框宽度</label>
            <div class="layui-input-block"style="width: 200px">
                <input type="text" name="width" lay-verify="required" placeholder="输入框宽度" value="<?=$info['width']?>"  class="layui-input">
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">配置值</label>
            <div class="layui-input-block"style="width: 200px">
                <input type="text" name="value" lay-verify="required" placeholder="请输入配置值" value="<?=$info['value']?>"  class="layui-input">
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">选项</label>
            <div class="layui-input-inline"style="width: 600px">
                <input type="text" name="option"  placeholder="请输入选项" value='<?=$info['option']?>'  class="layui-input">
            </div>
            <div class="layui-form-mid layui-word-aux">格式如下，请严格安装格式书写：[{"key":11,"val":"ss1"},{"key":22,"val":"ss"}]</div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">说明</label>
            <div class="layui-input-block"style="width: 600px">
                <textarea   class="layui-textarea"  placeholder="请输入说明" value="<?=$info['desc']?>?>" name="desc"></textarea>
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