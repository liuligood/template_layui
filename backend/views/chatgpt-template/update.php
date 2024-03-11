<?php

use common\models\sys\ChatgptTemplate;
use yii\helpers\Html;
use yii\helpers\Url;
?>
<style>
    html {
        background: #fff;
    }
    .layui-input {
        width: 340px;
    }
</style>
<form class="layui-form layui-row" id="add" action="<?=Url::to(['chatgpt-template/update?id='.$model['id']])?>">

    <div class="layui-col-md12 layui-col-xs12" style="padding-top: 15px">

        <div class="layui-form-item">
            <label class="layui-form-label">名称</label>
            <div class="layui-input-block">
                <input type="text" name="template_name" value="<?=$model['template_name']?>" lay-verify="required" placeholder="请输入模板名称" class="layui-input">
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label">编号</label>
            <div class="layui-input-block">
                <input type="text" name="template_code" value="<?=$model['template_code']?>" lay-verify="required" placeholder="请输入模板编号" class="layui-input ">
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label">类型</label>
            <div class="layui-input-block">
                <?= Html::dropDownList('template_type', $model['template_type'],ChatgptTemplate::$template_maps,
                    ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','id'=>'template_type' ]) ?>
                <input id="template_type" value="<?=$model['template_type']?>" type="hidden">
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label">状态</label>
            <div class="layui-input-block">
                <?= Html::dropDownList('status', $model['status'],ChatgptTemplate::$status_maps,
                    ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search' ]) ?>
            </div>
        </div>

        <div id="template_content">
        </div>

        <div id="param_content">
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label">参数说明</label>
            <div class="layui-input-block">
                <textarea name="template_param_desc" placeholder="请输入模板参数说明" class="layui-textarea" style="width: 340px;height: 80px"><?=$model['template_param_desc']?></textarea>
            </div>
        </div>


        <div class="layui-form-item">
            <div class="layui-input-block">
                <input type="hidden" name="id" value="<?=$model['id']?>">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="add">立即提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </div>
</form>
<script id="template_content_completions" type="text/html">
    <div class="layui-form-item">
        <label class="layui-form-label">内容</label>
        <div class="layui-input-block">
            <textarea name="template_content" lay-verify="required" placeholder="请输入模板内容" class="layui-textarea" style="width: 340px;height: 80px">{{ d.template_content_value || '' }}</textarea>
        </div>
    </div>
</script>
<script id="template_content_chat" type="text/html">
    <div class="layui-form-item">
        <label class="layui-form-label"> {{# if(d.is_init==0){ }}内容{{# } }}</label>
        <div class="layui-input-inline" style="width: 105px">
            <select lay-verify="required" class="layui-input search-con ys-select2" lay-ignore name="role[]" style="width: 105px">
                <?php
                foreach (ChatgptTemplate::$chat_role as $v){?>
                    <option value="<?=$v?>" {{# if(d.content_chat.role == '<?=$v?>'){ }}selected {{# } }}><?=$v?></option>
                <?php }?>
            </select>
        </div>
        <div class="layui-inline layui-col-md6" style="width: 620px">
            <textarea name="template_content[]" lay-verify="required" placeholder="请输入模板内容" class="layui-textarea auto_textarea" style="width: 620px;min-height: 38px;height: 38px">{{ d.content_chat.content || '' }}</textarea>
        </div>
        {{# if(d.is_init==0){ }}
        <div class="layui-inline" id="add-chat">
            <a href="javascript:;"><i class="layui-icon layui-icon-add-1"  style="font-size: 20px; font-weight: bold;line-height: 40px"></i></i></a>
        </div>
        {{# }else{ }}
        <div class="layui-inline" id="del-chat">
            <a href="javascript:;"><i class="layui-icon layui-icon-close layui-icon-close1" style="font-size: 20px; font-weight: bold;line-height: 40px"></i></i></a>
        </div>
        {{# } }}
    </div>
</script>
<script id="template_content_param" type="text/html">
    <div class="layui-form-item">
        <label class="layui-form-label"> {{# if(d.is_init_param==0){ }}参数{{# } }}</label>
        <div class="layui-input-inline" style="width: 105px">
            <input name="param_name[]" value="{{ d.param.name || '' }}" class="layui-input search-con" placeholder="请输入参数名" style="width: 105px">
        </div>
        <div class="layui-inline layui-col-md6" style="width: 620px">
            <textarea name="param_content[]" placeholder="请输入参数内容" class="layui-textarea auto_textarea" style="width: 620px;min-height: 38px;height: 38px">{{ d.param.content || '' }}</textarea>
        </div>
        {{# if(d.is_init_param==0){ }}
        <div class="layui-inline" id="add-param">
            <a href="javascript:;"><i class="layui-icon layui-icon-add-1"  style="font-size: 20px; font-weight: bold;line-height: 40px"></i></i></a>
        </div>
        {{# }else{ }}
        <div class="layui-inline" id="del-param">
            <a href="javascript:;"><i class="layui-icon layui-icon-close layui-icon-close1" style="font-size: 20px; font-weight: bold;line-height: 40px"></i></i></a>
        </div>
        {{# } }}
    </div>
</script>
<script>
    var is_update = 1;
    var template_content_value = <?=empty($model['template_content']) ? '' : json_encode($model['template_content'])?>;
    var template_param = <?=json_encode($model['param'])?>;
</script>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>
<?=$this->registerJsFile("@adminPageJs/chatgpt-template/form.js?v=".time())?>

