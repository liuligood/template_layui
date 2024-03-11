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

        .lay-image{
            float: left;padding: 20px; border: 1px solid #eee;margin: 5px
        }
    </style>
    <form class="layui-form layui-row" id="update_multilingual" action="<?=Url::to(['goods/update-multilingual?type='.$type])?>">
        <div class="layui-col-md9 layui-col-xs12" style="padding-top: 15px">

            <?php if (!empty($platform)){?>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md8">
                    <label class="layui-form-label">平台</label>
                    <div class="layui-input-block">
                        <?= \yii\bootstrap\Html::dropDownList('platform',empty($platform_type)?Base::PLATFORM_OZON:$platform_type,$platform,
                            ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style' =>'width:210px','id' => 'platform' ]) ?>
                    </div>
                </div>
            </div>
            <?php }?>

            <div class="layui-form-item">
                <div class="layui-inline">
                    <label class="layui-form-label">商品编号</label>
                    <div class="layui-input-block">
                        <label class="layui-form-label" style="width: 120px;text-align: left"><?=$goods['goods_no']?></label>
                    </div>
                </div>
                <?php if(in_array($type,[1,3])){ ?>
                <div class="layui-inline">
                    <label class="layui-form-label">语言</label>
                    <div class="layui-inline" style="width:140px">
                        <?php
                        echo \yii\helpers\Html::dropDownList('language', isset($language) ? $language : 'en',$goods_language ,
                            ['lay-ignore'=>'lay-ignore' ,'class'=>"layui-input search-con ys-select2", 'id' => 'languages']);
                        ?>
                    </div>
                </div>
                <?php }?>
            </div>

            <div class="layui-form-item layui-form-text">
                <div class="layui-inline layui-col-md8">
                    <label class="layui-form-label">中文标题</label>
                    <div class="layui-input-block">
                        <label class="layui-form-label" style="text-align: left;width: 650px"><?=$goods['goods_name_cn']?></label>
                    </div>
                </div>
            </div>
            <?php if(in_array($type,[1,3])){ ?>
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md8">
                    <label class="layui-form-label">标题</label>
                    <div class="layui-input-block">
                        <input type="text" id="goods_name" name="goods_name" lay-verify="required" placeholder="请输入标题" value="<?=!empty($origin_name['goods_name']) ? $origin_name['goods_name'] : htmlentities($goods['goods_name'], ENT_COMPAT);?>"  class="layui-input" autocomplete="off">
                    </div>
                </div>
                <div class="layui-inline">
                    <label class="layui-form-label">字数：<span id="goods_name_count"></span></label>
                </div>
            </div>

            <div class="layui-form-item layui-form-text">
                <label class="layui-form-label">
                    简要描述
                    <div>
                        <input type="checkbox" name="is_goods_desc" value="1"  lay-skin="switch" lay-text="开启|关闭" lay-filter="statusSwitchs" <?= !empty($origin_name['goods_desc']) || empty($origin_name['goods_name'])?'checked':''?>>
                    </div>
                </label>
                <div class="layui-input-block">
                    <textarea placeholder="请输入商品简要说明" class="layui-textarea" name="goods_desc"><?=!empty($origin_name['goods_desc']) ? $origin_name['goods_desc'] : $goods['goods_desc']?></textarea>
                </div>
            </div>

            <div class="layui-form-item layui-form-text">
                <label class="layui-form-label">
                    详细描述
                    <div>
                        <input type="checkbox" name="is_goods_content" value="1"  lay-skin="switch" lay-text="开启|关闭" lay-filter="statusSwitchs" <?= !empty($origin_name['goods_content']) || empty($origin_name['goods_name'])?'checked':''?>>
                    </div>
                </label>
                <div class="layui-input-block" style="position: relative;">
                    <textarea placeholder="请输入商品详细说明"  lay-verify="required" class="layui-textarea" style="height: 200px" name="goods_content" id="goods_content"><?=!empty($origin_name['goods_content']) ? $origin_name['goods_content'] : $goods['goods_content']?></textarea>
                </div>
            </div>

            <div class="layui-form-item layui-form-text" style="margin: 0">
                <div class="layui-input-block">
                    <a class="layui-btn layui-btn-warm layui-btn-xs ys-upload-file" lay-data="{url: '/app/upload-video',accept: 'file'}" style="float: left">上传视频</a>
                    <a class="layui-btn layui-btn-xs create_new_img" style="float: right">自有图片</a>
                </div>
            </div>
            <?php }?>
            <input type="hidden" name="video" id="video_value">
            <div class="layui-input-block" id="percent" style="display: none">
                <div class="layui-progress layui-progress-big" lay-filter="percent" lay-showPercent="yes">
                    <div class="layui-progress-bar layui-bg-green" lay-percent="0%"></div>
                </div>
            </div>
            <div class="layui-form-item layui-form-text" id="video">
            </div>
            <div class="layui-form-item layui-form-text">
                <label class="layui-form-label">图片信息</label>
                <div class="layui-input-block">
                    <div class="layui-upload ys-upload-img-multiple" data-number="10">
                        <input type="hidden" id="goods_img" name="goods_img" class="layui-input" value="<?=htmlentities($goods['goods_img'], ENT_COMPAT);?>">
                        <div class="layui-upload-con">
                        </div>
                    </div>
                </div>
            </div>

            <div class="layui-form-item layui-form-text" id="own">
            </div>



            <?php if(in_array($type,[2,3])){
                $platform_name = empty($platform_type)?'':\common\components\statics\Base::$platform_maps[$platform_type];
                ?>
            <?php if ($platform_type == Base::PLATFORM_WILDBERRIES){?>
            <div class="layui-form-item">
                <div class="layui-inline">
                    <label class="layui-form-label">颜色</label>
                    <div class="layui-input-inline">
                        <input type="text" name="color" placeholder="请输入颜色" value="<?=$color?>" class="layui-input" autocomplete="off">
                    </div>
                </div>

                <div class="layui-inline">
                    <label class="layui-form-label">重量</label>
                    <div class="layui-input-inline">
                        <input type="text" name="weight" placeholder="请输入重量" value="<?=$weight?>" class="layui-input" autocomplete="off">
                    </div>
                </div>
            </div>
            <?php }?>
            <div class="layui-form-item layui-form-text" id="own_platform">
            </div>
            <div id="ozon_category" class="layui-row layui-col-space15 layui-input-block" style="background-color: #F2F2F2; margin-top: 10px;">
            <div class="layui-col-md12" style="padding: 3px;">
                <div class="layui-card">
                    <div class="layui-card-header"><?=$platform_name?>信息</div>
                    <div class="layui-card-body">
                    <div class="layui-form-item">
                        <label class="layui-form-label">富文本描述</label>
                        <div id="ozon_editor" class="layui-form-block">
                            <a class="layui-btn layui-btn-normal layui-btn-xs editor" style="margin-top: 8px" data-src="<?=Url::to(['goods/editor?goods_no='.$goods['goods_no'].'&platform_type='.$platform_type])?>">打开编辑器</a>
                        </div>
                    </div>
                    <div class="layui-form-item" id="platform_category">
                        <label class="layui-form-label"><?=$platform_name?>类目</label>
                        <div class="layui-input-block" style="position: relative;">
                            <div class="rc-cascader">
                                <input type="text" id="ozon_category_id" name="o_category_name" value="<?=empty($goods_information['o_category_name']) ? $o_category_name : $goods_information['o_category_name']?>" style="display: none;" />
                            </div>
                        </div>
                    </div>

                    <div id="attribute">

                    </div>
                </div>
                </div>
            </div>
            </div>
            <?php }?>
            <div style="height: 50px"></div>

            <div class="layui-form-item layui-layout-admin">
                <div class="layui-input-block">
                    <div class="layui-footer" style="left: 0;">
                    <input type="hidden" name="goods_no" value="<?=$goods['goods_no']?>">
                    <input type="hidden" name="old_language" value="<?=isset($language) ? $language : '' ?>">
                    <input type="hidden" name="old_goods_name" value="<?=$goods['goods_name']?>">
                    <input type="hidden" name="old_goods_content" value="<?=$goods['goods_content']?>">
                    <input type="hidden" id="platform_type" name="platform_type" value="<?=$platform_type?>">
                    <button class="layui-btn" lay-submit="" lay-filter="form" data-form="update_multilingual">立即提交</button>
                    <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
<script id="video_tpl" type="text/html">
    <div class="layui-row layui-col-space15 layui-input-block" style="background-color: #F2F2F2; margin-top: 10px;">
        <div class="layui-col-md12" style="padding: 3px">
            <div class="layui-card">
                <div class="layui-card-header">
                    视频信息
                    <div class="del-video">
                        <span class="layui-layer-setwin"><a class="layui-layer-ico layui-layer-close layui-layer-close1" href="javascript:;"></a></span>
                    </div>
                </div>
                <div class="layui-card-body">
                    <div class="layui-form-item" style="padding-left: 20px">
                    <ol class="layui-upload-video">
                        <li class="layui-fluid lay-image">
                            <div class="layui-upload-list">
                                <video id="video_d" width="102" height="122" controls>
                                    <source  src="{{ d.video }}" type="video/mp4">
                                    <source  src="{{ d.video }}" type="video/ogg">
                                    <source  src="{{ d.video }}" type="video/webm">
                                    <object data="{{ d.video }}" width="102" height="122">
                                        <embed src="{{ d.video }}" width="102" height="122">
                                    </object>
                                </video>
                            </div>
                        </li>
                    </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>
<script id="own_images" type="text/html">
    <div class="layui-row layui-col-space15 layui-input-block" style="background-color: #F2F2F2; margin-top: 10px;">
        <div class="layui-col-md12" style="padding: 3px">
            <div class="layui-card">
                <div class="layui-card-header">
                    自有图片
                    <div class="del-create-img">
                        <span class="layui-layer-setwin"><a class="layui-layer-ico layui-layer-close layui-layer-close1" href="javascript:;"></a></span>
                    </div>
                </div>
                <div class="layui-card-body">
                    <div class="layui-form-item" style="padding-left: 20px">
                        <div class="layui-inline">
                            <div class="layui-inline" style="width: 250px">
                                <input type="text" name="own_img" id="own_img_url" placeholder="图片链接"  value="" class="layui-input" autocomplete="off">
                            </div>
                            <div class="layui-inline" style="width: 80px">
                                <button type="button" class="layui-btn layui-btn-normal" id="js_add_own_img_url">添加</button>
                            </div>
                        </div>

                        <div class="ys-upload-own" data-number="10">
                            <a class="layui-btn ys-upload-own-img" lay-data="{url: '/app/upload-img'}">上传图片</a>
                            <input type="hidden" name="goods_own_img" class="layui-input" value="">
                            <ol class="layui-upload-own">
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>
<script id="platform_images" type="text/html">
    <div class="layui-row layui-col-space15 layui-input-block" style="background-color: #F2F2F2; margin-top: 10px;">
        <div class="layui-col-md12" style="padding: 3px">
            <div class="layui-card">
                <div class="layui-card-header">
                    平台图片信息
                </div>
                <div class="layui-card-body">
                    <div class="layui-form-item" style="padding-left: 20px">
                        <div class="layui-inline">
                            <div class="layui-inline" style="width: 250px">
                                <input type="text" name="platform_img" id="platform_img_url" placeholder="图片链接"  value="" class="layui-input" autocomplete="off">
                            </div>
                            <div class="layui-inline" style="width: 80px">
                                <button type="button" class="layui-btn layui-btn-normal" id="js_add_platform_img_url">添加</button>
                            </div>
                        </div>

                        <div class="ys-upload-platform" data-number="10">
                            <a class="layui-btn ys-upload-platform-img" lay-data="{url: '/app/upload-img'}">上传图片</a>
                            <input type="hidden" name="goods_platform_img" class="layui-input" value="">
                            <ol class="layui-upload-platform">
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>
<script id="white_img_tmp" type="text/html">
    <div style="padding: 10px;margin-left: 35px;float: left">
        <div>原图</div>
        <img id="old_white_img" src="{{ d.img || '' }}" width="300px" style="border:4px solid #cccccc">
    </div>
    <div style="padding: 10px;margin-left: 70px;float: left">
        <div>效果图</div>
        <img id="new_white_img" src="{{ d.new_img || '' }}" width="300px" style="border:4px solid #cccccc">
    </div>
</script>
<script id="img_tpl" type="text/html">
    <li class="layui-fluid lay-image" style="padding:5px">
        <div class="layui-upload-list" style="margin: 5px 0;">
            <a href="{{ d.img || '' }}" data-lightbox="pic">
                <img class="layui-upload-img" style="max-width: 100px;height: 60px;"  src="{{ d.img || '' }}">
            </a>
        </div>
    </li>
</script>
<script id="own_img_tpl" type="text/html">
    <li class="layui-fluid lay-image images">
        <div class="layui-upload-list">
            <a href="{{ d.img || '' }}" data-lightbox="pic">
                <img class="layui-upload-img" data-image_id="{{ d.id || '' }}" style="max-width: 150px;height: 80px"  src="{{ d.img || '' }}">
            </a>
        </div>
        <div class="del-img">
            <span class="layui-layer-setwin"><a class="layui-layer-ico layui-layer-close layui-layer-close1" href="javascript:;"></a></span>
        </div>
        <div class="img-tool">
            <span class="layui-layer-setwin translate_img" style="top: 115px;left: 10px;"><a style="font-size: 18px;color:#1E9FFF" class="layui-icon layui-icon-fonts-clear" href="javascript:;" title="翻译成英文"></a></span>

            <span class="layui-layer-setwin white_img" style="top: 115px;left: 35px;"><a style="font-size: 18px;color:#1E9FFF" class="layui-icon layui-icon-layer" href="javascript:;" title="图片白底"></a></span>
        </div>
    </li>
</script>
<script id="source_tpl" type="text/html">
</script>

<script id="attribute_tpl" type="text/html">
</script>
<?php if($platform_type == \common\components\statics\Base::PLATFORM_OZON) {?>
<script id="ozon_attribute_tpl" type="text/html">
    <div class="layui-form-item">
        <div class="layui-inline layui-col-md12">
            <label class="layui-form-label" style="width: 300px">{{ d.attribute_name || '' }}({{ d.attribute_name_cn || '' }})
                {{# if(d.is_required == 1){ }}<span style="color: red">*</span>{{# } }}{{# if(d.attribute_desc){ }}<a style="font-size: 18px;color:#FFB800;font-weight: bold" class="js-tips layui-icon layui-icon-about" href="javascript:;" data-content="{{ d.attribute_desc || '' }}"></a>{{# } }}</label>
            <div class="layui-input-block" style="margin-left: 330px;">
                {{# if(d.attribute_type=='Select'){ }}
                <select class="layui-input search-con ys-select2" {{# if(d.is_multiple == 1){ }}multiple="multiple"{{# } }} lay-ignore="lay-ignore" style="width: auto" name="attribute_value[{{ d.attribute_id }}]{{# if(d.is_multiple == 1){ }}[]{{# } }}">
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
                <input type="text" name="attribute_value[{{ d.attribute_id }}]" placeholder="请输入" value="{{d.sel_attribute_value||''}}"  class="layui-input">
                {{# } }}
            </div>
        </div>
    </div>
</script>
<?php } else if($platform_type == \common\components\statics\Base::PLATFORM_ALLEGRO) {?>
    <script id="ozon_attribute_tpl" type="text/html">
        <div class="layui-form-item">
            <div class="layui-inline layui-col-md12">
                <label class="layui-form-label" style="width: 300px">{{ d.attribute_name || '' }}({{ d.attribute_name_cn || '' }})
                    {{# if(d.is_required != 0){ }}<span style="color: red">*</span>{{# } }}{{# if(d.attribute_desc){ }}<a style="font-size: 18px;color:#FFB800;font-weight: bold" class="js-tips layui-icon layui-icon-about" href="javascript:;" data-content="{{ d.attribute_desc || '' }}"></a>{{# } }}</label>
                <div class="layui-input-block" style="margin-left: 330px;">
                    <!--multiline String Decimal Url Integer-->
                    {{# if(d.attribute_type=='Select'){ }}
                    <select data-id="{{ d.attribute_id }}" class="layui-input search-con ys-select2 attr_sel" {{# if(d.is_multiple == 1){ }}multiple="multiple"{{# } }} lay-ignore="lay-ignore" style="width: auto" name="attribute_value[{{ d.attribute_id }}]{{# if(d.is_multiple == 1){ }}[]{{# } }}" data-ambiguous-values="{{ d.param.options.ambiguousValueId }}" data-custom="{{ d.param.options.customValuesEnabled }}" >
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
                    <input type="text" name="attribute_value[{{ d.attribute_id }}]" placeholder="请输入" value="{{d.sel_attribute_value||''}}"  class="layui-input">
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
    var source = '';
    var attribute = '';
    var source_method = '';
    var tag_name = '';
    var property = '';
    var goods_no = '<?=$goods['goods_no']?>';
    var platform_type = '<?=empty($platform_type)?'':$platform_type?>';
    var goods_own_image = <?=isset($goods_own_image) ? $goods_own_image : "''"?>;
    var sel_attribute_value = <?=empty($information_attribute_value)?"$mapping_attribute_value":"$information_attribute_value";?>;
    var editor_value = <?=empty($editor)?"''":json_encode($editor,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);?>;
    var information_image = <?=empty($goods_platform_image) ? "''" : $goods_platform_image?>;
    var video = '<?=isset($video) ? $video : ""?>';
</script>
<?php
$this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/jquery-ui.min.js", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPageJs/goods/update_multilingual.js?".time());
if (!in_array($platform_type,[Base::PLATFORM_WILDBERRIES,Base::PLATFORM_AMAZON]) && $platform_type != 0) {
    $this->registerJsFile("@adminPageJs/category-".$platform_type.".js?v=0.0.2");
}
?>

