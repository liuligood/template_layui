<?php
use yii\helpers\Url;
$platform_name = \common\components\statics\Base::$platform_maps[$platform_type];
$url_platform_name = strtolower($platform_name);
?>
<style>
    .layui-form-item{
        margin-bottom: 5px;
    }
    .layui-btn-a {
        border: none;
        background-color: rgba(0, 0, 0, 0);
        -webkit-user-select:text;
    }
</style>
<div class="layui-fluid">
<form class="layui-form" id="update_goods" action="<?=Url::to(['goods-'.$url_platform_name.'/update'])?>">

    <div class="layui-row layui-col-space15">
        <div class="layui-col-md9 layui-col-xs12">
            <?php if($shop_goods_model['status'] == \common\models\GoodsShop::STATUS_FAIL){ ?>
            <table class="layui-table" style="margin-top: 0px;padding-top: 0px">
                <tbody>
                <tr>
                    <td>
                        <?php foreach ($shop_goods_expand_model['error_msg'] as $v){?>
                        <div class="lay-lists">
                            <p style="color: #ff0000"><?=$v['error_message']?>
                                <a class="layui-btn layui-btn-normal layui-btn-xs" data-type="open" data-url="<?=Url::to(['goods-error-solution/update?id='.$v['id'].'&goods_no='.$goods['goods_no']])?>" data-width="750px" data-height="450px" data-title="编辑解决方案" data-callback_title="goods-error-solution列表">编辑解决方案</a>
                            </p>
                        </div>
                        <p><b>解决方案: </b><?=$v['solution']?></p>
                        <?php }?>
                    </td>
                </tr>
                </tbody>
            </table>
            <?php }?>
            <div class="layui-card">
                <div class="layui-card-header">基本信息</div>
                <div class="layui-card-body layui-row layui-col-space10">

                    <div class="layui-form-item">
                        <div class="layui-inline layui-col-md4">
                            <label class="layui-form-label">商品编号</label>
                            <div class="layui-input-block">
                                <label class="layui-form-label lay-lists" style="width: 120px;text-align: left"><a class="layui-btn layui-btn-xs layui-btn-a"  data-type="url" data-url="<?=Url::to(['goods/view?goods_no='.$goods['goods_no']])?>" style="color: #00a0e9"><?=$goods['goods_no']?></a></label>
                            </div>
                        </div>
                        <div class="layui-inline layui-col-md4">
                            <label class="layui-form-label">SKU</label>
                            <div class="layui-input-block">
                                <label class="layui-form-label" style="width: 160px;text-align: left"><?=$goods['sku_no']?></label>
                            </div>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <div class="layui-inline layui-col-md12">
                            <label class="layui-form-label"><?=$platform_name?>类目</label>
                            <div class="layui-input-block">
                                <input type="hidden" name="o_category_name" placeholder="请输入类目名称" value="<?=$category['id']?>" class="layui-input">
                                <span style="line-height: 30px"><?=$category['name']?></span>
                            </div>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <div class="layui-inline layui-col-md8">
                            <label class="layui-form-label">标题</label>
                            <div class="layui-input-block">
                                <input type="text" id="goods_name" name="goods_title" placeholder="不填使用系统默认生成标题" value="<?=!empty($shop_goods_expand_model['goods_title'])?$shop_goods_expand_model['goods_title']:'';?>"  class="layui-input">
                            </div>
                        </div>
                        <div class="layui-inline layui-col-md12">
                            <label class="layui-form-label">中文标题</label>
                            <div class="layui-input-block">
                                <span style="line-height: 30px;color: #a0a3a6"><?=$goods['goods_name_cn']?></span>
                            </div>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <div class="layui-inline">
                            <div class="layui-inline">
                                <label class="layui-form-label">售价</label>
                                <label class="layui-form-label" style="width: 80px;text-align: left"><?=$shop_goods_model['price']?></label>
                            </div>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <label class="layui-form-label">商品图片</label>
                        <div class="layui-input-block">
                            <div class="layui-upload ys-upload-img-multiple" data-number="10">
                                <input type="hidden" name="goods_img" class="layui-input" value="<?=htmlentities($goods['goods_img'], ENT_COMPAT);?>">
                                <div class="layui-upload-con">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="layui-form-item layui-form-text">
                        <label class="layui-form-label">详细描述<span style="color: red">*</span></label>
                        <div class="layui-input-block">
                            <textarea placeholder="请输入商品详细说明" class="layui-textarea" style="height: 200px" name="goods_content" id="goods_content"><?=$shop_goods_expand_model['goods_content']??'';?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="layui-card">
                <div class="layui-card-header">Allegro参数</div>
                <div id="attribute" style="padding: 15px">

                </div>
            </div>
            <?php if($shop_goods_model['status'] != \common\models\GoodsShop::STATUS_SUCCESS){?>
            <div class="layui-form-item layui-layout-admin">
                <div class="layui-input-block">
                    <div class="layui-footer" style="left: 0;">
                        <input type="hidden" name="id" value="<?=$shop_goods_model['id']?>">
                        <input type="hidden" name="platform_type" value="<?=$platform_type?>" class="layui-input">
                        <button class="layui-btn" lay-submit="" lay-filter="form" data-form="update_goods">保存</button>
                        <button class="layui-btn" lay-submit="" lay-filter="form" data-save="release" data-form="update_goods">保存并发布</button>
                    </div>
                </div>
            </div>
            <?php }?>
        </div>
    </div>
</form>
</div>

<script id="img_tpl" type="text/html">
    <div class="layui-fluid" style="float: left;padding: 10px; border: 1px solid #eee;margin: 5px">
        <div class="layui-upload-list">
            <a href="{{ d.img || '' }}" data-lightbox="pic">
            <img class="layui-upload-img" style="max-width: 100px;height: 60px"  src="{{ d.img || '' }}">
            </a>
        </div>
    </div>
</script>

<script id="attribute_tpl" type="text/html">
    <div class="layui-form-item">
        <div class="layui-inline layui-col-md12">
            <label class="layui-form-label" style="width: 300px">{{ d.attribute_name || '' }}({{ d.attribute_name_cn || '' }})
                {{# if(d.is_required != 0){ }}<span style="color: red">*</span>{{# } }}</label>
            <div class="layui-input-block" style="margin-left: 330px;">
                <!--multiline String Decimal Url Integer-->
                {{# if(d.attribute_type=='Select'){ }}
                <select data-id="{{ d.attribute_id }}" class="layui-input search-con ys-select2 attr_sel" {{# if(d.is_required == 1){ }}lay-verify="required"{{# } }} lay-ignore="lay-ignore" {{# if(d.is_multiple == 1){ }}multiple="multiple"{{# } }} {{# if(d.is_required != 0){ }}lay-verify="required"{{# } }} style="width: auto" name="attribute_value[{{ d.attribute_id }}]" data-ambiguous-values="{{ d.param.options.ambiguousValueId }}" data-custom="{{ d.param.options.customValuesEnabled }}" >
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

<script type="text/javascript">
    var platform_type = 23;
    var sel_attribute_value = <?=empty($shop_goods_expand_model['attribute_value'])?"''":$shop_goods_expand_model['attribute_value'];?>;
</script>
<?php
$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.7");
$this->registerJsFile("@adminPageJs/base/form.js?".time());
$this->registerJsFile("@adminPageJs/goods/platform_from.js?".time());
$this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
?>

