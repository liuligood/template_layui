<?php

use common\models\Category;
use yii\helpers\Url;

$platform_name = \common\components\statics\Base::$platform_maps[$platform_type];
$url_platform_name = strtolower($platform_name);
/**
 * @var array $category_info
 * @var array $category_mapping
 */
?>
<style>
    html {
        background: #fff;
    }
</style>
<form class="layui-form layui-row" id="mappingMenu" action="<?=Url::to(['category/mapping-category'])?>" data-reload="false">

<div class="layui-col-md10 layui-col-xs11" style="padding-top: 15px;">

    <div class="layui-form-item">
        <div class="layui-inline">
            <label class="layui-form-label">类目</label>
            <label class="layui-form-label" style="width:auto;text-align: left"><?=Category::getCategoryNamesTreeByCategoryId($category_info['id']);?></label>
        </div>
    </div>

    <div class="layui-form-item">
        <label class="layui-form-label"><?=$platform_name?>类目</label>
        <div class="layui-input-block">
            <div class="rc-cascader">
                <input type="text" id="o_category_id" name="o_category_name" value="<?=empty($category_mapping['o_category_name'])?'':$category_mapping['o_category_name']?>" style="display: none;" />
            </div>
        </div>
    </div>

    <div id="attribute">

    </div>

    <div class="layui-form-item layui-layout-admin">
        <div class="layui-input-block">
            <div class="layui-footer" style="left: 0;">
            <input type="hidden" name="category_id" value="<?=$category_info['id']?>">
            <input type="hidden" name="platform_type" value="<?=$platform_type?>">
            <button class="layui-btn" lay-submit="" lay-filter="form" data-form="mappingMenu">提交</button>
            <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </div>
</div>
</form>

<?php if($platform_type == \common\components\statics\Base::PLATFORM_OZON) {?>
<script id="attribute_tpl" type="text/html">
    <div class="layui-form-item">
        <div class="layui-inline layui-col-md12">
            <label class="layui-form-label" style="width: 300px">{{ d.attribute_name || '' }}({{ d.attribute_name_cn || '' }})
                {{# if(d.is_required == 1){ }}<span style="color: red">*</span>{{# } }}{{# if(d.attribute_desc){ }}<a style="font-size: 18px;color:#FFB800;font-weight: bold" class="js-tips layui-icon layui-icon-about" href="javascript:;" data-content="{{ d.attribute_desc || '' }}"></a>{{# } }}</label>
            <div class="layui-input-block" style="margin-left: 330px;">
                <!--multiline String Decimal Url Integer-->
                {{# if(d.attribute_type=='Select'){ }}
                <select class="layui-input search-con ys-select2" {{# if(d.is_multiple == 1){ }}multiple="multiple"{{# } }} {{# if(d.is_required == 1){ }}lay-verify="required"{{# } }} lay-ignore="lay-ignore" {{# if(d.is_required == 1){ }}lay-verify="required"{{# } }} style="width: auto" name="attribute_value[{{ d.attribute_id }}]{{# if(d.is_multiple == 1){ }}[]{{# } }}">
                    <option value="">请选择</option>
                    {{# layui.each(d.attribute_value, function(index, item){ }}
                    {{# if(d.sel_attribute_value instanceof Array){
                    var sel_val = false;
                    layui.each(d.sel_attribute_value, function(sel_index,sel_item){
                    if(item.id == sel_item){
                    sel_val = true;
                    }
                    }); }}
                    <option {{# if(sel_val){ }}selected {{# } }} value="{{ item.id }}">{{ item.value }}</option>
                    {{# }else{ }}
                    <option {{# if(d.sel_attribute_value == item.id){ }}selected {{# } }} value="{{ item.id }}">{{ item.value }}</option>
                    {{# } }}
                    {{# }); }}
                </select>
                {{# } else if(d.attribute_type=='Boolean'){ }}
                <input class="layui-input search-con" type="checkbox" value="1" name="attribute_value[{{ d.attribute_id }}]" lay-skin="primary" title="是" {{# if(d.sel_attribute_value == 1){ }}checked {{# } }} >
                {{# } else{ }}
                <input type="text" {{# if(d.is_required == 1){ }}lay-verify="required"{{# } }} name="attribute_value[{{ d.attribute_id }}]" placeholder="请输入" value="{{d.sel_attribute_value||''}}"  class="layui-input">
                {{# } }}
            </div>
        </div>
    </div>
</script>
<?php } else if($platform_type == \common\components\statics\Base::PLATFORM_ALLEGRO) {?>
<script id="attribute_tpl" type="text/html">
    <div class="layui-form-item">
        <div class="layui-inline layui-col-md12">
            <label class="layui-form-label" style="width: 300px">{{ d.attribute_name || '' }}({{ d.attribute_name_cn || '' }})
                {{# if(d.is_required != 0){ }}<span style="color: red">*</span>{{# } }}{{# if(d.attribute_desc){ }}<a style="font-size: 18px;color:#FFB800;font-weight: bold" class="js-tips layui-icon layui-icon-about" href="javascript:;" data-content="{{ d.attribute_desc || '' }}"></a>{{# } }}</label>
            <div class="layui-input-block" style="margin-left: 330px;">
                <!--multiline String Decimal Url Integer-->
                {{# if(d.attribute_type=='Select'){ }}
                <select data-id="{{ d.attribute_id }}" class="layui-input search-con ys-select2 attr_sel" {{# if(d.is_required == 1){ }}lay-verify="required"{{# } }} lay-ignore="lay-ignore" {{# if(d.is_multiple == 1){ }}multiple="multiple"{{# } }} {{# if(d.is_required != 0){ }}lay-verify="required"{{# } }} style="width: auto" name="attribute_value[{{ d.attribute_id }}]{{# if(d.is_multiple == 1){ }}[]{{# } }}" data-ambiguous-values="{{ d.param.options.ambiguousValueId }}" data-custom="{{ d.param.options.customValuesEnabled }}" >
                    <option value="">请选择</option>
                    {{# layui.each(d.attribute_value, function(index, item){ }}
                    {{# if(d.sel_attribute_value instanceof Array){
                    var sel_val = false;
                    layui.each(d.sel_attribute_value, function(sel_index,sel_item){
                    if(item.id == sel_item){
                    sel_val = true;
                    }
                    }); }}
                    <option {{# if(sel_val){ }}selected {{# } }} value="{{ item.id }}">{{ item.value }}</option>
                    {{# }else{ }}
                    <option {{# if(d.sel_attribute_value == item.id){ }}selected {{# } }} value="{{ item.id }}">{{ item.value }}</option>
                    {{# } }}
                    {{# }); }}
                </select>
                {{# } else if(d.attribute_type=='Boolean'){ }}
                <input class="layui-input search-con" type="checkbox" value="1" name="attribute_value[{{ d.attribute_id }}]" lay-skin="primary" title="是" {{# if(d.sel_attribute_value == 1){ }}checked {{# } }} >
                {{# } else{ }}
                <input type="text" {{# if(d.is_required != 0){ }}lay-verify="required"{{# } }} name="attribute_value[{{ d.attribute_id }}]" placeholder="请输入" value="{{d.sel_attribute_value||''}}"  class="layui-input">
                {{# } }}
            </div>
        </div>
    </div>

    {{# if(d.param.options.customValuesEnabled == true){ }}
    <div class="layui-form-item" id="custom_{{ d.attribute_id }}" {{# if(d.sel_attribute_value != d.param.options.ambiguousValueId){ }}style="display: none"{{# } }}>
        <div class="layui-inline layui-col-md12">
            <label class="layui-form-label" style="width: 300px">{{ d.attribute_name_cn || d.attribute_name ||'' }} 【自定义】
                <span style="color: red">*</span></label>
            <div class="layui-input-block" style="margin-left: 330px;">
                <input id="custom_val_{{ d.attribute_id }}" type="text" name="attribute_value[custom][{{ d.attribute_id }}]" placeholder="请输入" value="{{d.sel_attribute_value_custom||''}}"  class="layui-input">
            </div>
        </div>
    </div>
    {{# } }}
</script>
<?php }?>

<script type="text/javascript">
    var sel_attribute_value = <?=empty($category_mapping['attribute_value'])?"''":$category_mapping['attribute_value'];?>;
    var platform_type = <?=$platform_type?>;
</script>
<?=$this->registerJsFile("@adminPageJs/base/form.js?v=0.0.22")?>
<?=$this->registerJsFile("@adminPageJs/category-".$platform_type.".js??v=0.0.2")?>
<?=$this->registerJsFile("@adminPageJs/category/mapping_category.js?v=0.0.1".time())?>