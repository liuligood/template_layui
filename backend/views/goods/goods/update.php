<?php

use common\components\statics\Base;
use common\models\Goods;
use common\models\Supplier;
use common\services\warehousing\WarehouseService;
use yii\helpers\Html;
use common\services\goods\GoodsService;
use yii\helpers\Url;
?>
<style>
    html {
        background: #fff;
    }
    .layui-form-item{
        margin-bottom: 5px;
    }
    .lay-image{
        float: left;padding: 20px; border: 1px solid #eee;margin: 5px
    }
    .layui-field-box{
        padding: 5px;
    }
    .layui-card-body{
        padding: 10px;
    }
    .layui-col-space15>*{
        padding: 3px;
    }
    .m_property_color{
        display: inline-block;
        border: 1px solid #e8e8e8;
        border-radius: 2px;
        padding: 0 20px;
        width: 50px;
        height: 30px;
        font-size: 13px;
        line-height: 30px;
        text-align: center;
        color: #fff;
        vertical-align: middle;
        margin-right: 20px;
    }
    .span-circular-ai{
        display: inline-block;
        min-width: 16px;
        height: 16px;
        border-radius: 80%;
        background-color: #00aa00;
        color: #fff;
        text-align: center;
        padding: 5px;
        line-height: 16px;
        cursor: pointer;
    }
    .siz_title_a{
        display: inline-block;
        padding: 0 10px;
        height: 30px;
        line-height: 30px;
        margin: 5px 20px 5px 0;
        width: 90px;
        text-align: center;
    }
    .siz_title_a_sel,.siz_title_a:hover{
        background: #1E9FFF;
        color: #FFFFFF;
    }
    .layui-table th,.text-center{
        text-align: center;
        font-weight: bold;
    }
    .f-black{
        color: #000000;
    }
    .edit-icon{
        margin-left: 10px;
        vertical-align: middle;
        cursor: pointer;
    }
    .edit-label{
        display: inline-block
    }
    .edit-input {
        display: none;
    }
    .edit-customize{
        padding: 0 10px;
    }
    .frequently_category_a{
        color: #00a0e9;
        padding-right: 5px;
        cursor: pointer;
        display: inline-block;
    }
    .layui-table td, .layui-table th {
        padding: 9px 10px;
    }
</style>
<form class="layui-form layui-row" id="update_goods" action="<?=Url::to([$goods->isNewRecord?'goods/create':'goods/update'])?>">
    <div class="layui-col-md9 layui-col-xs12" style="padding: 10px">

        <div id="con1" style="padding: 10px; background-color: #F2F2F2;">
            <div class="layui-row layui-col-space15">
                <div class="layui-col-md12">
                    <div class="layui-card">
                        <div class="layui-card-header">平台信息</div>
                        <div class="layui-card-body">

                            <div class="layui-field-box">
                                <div class="layui-form-item">
                                    <div class="layui-inline layui-col-md6">
                                        <label class="layui-form-label">分类</label>
                                        <div class="layui-input-block" style="width: 600px">
                                            <div class="rc-cascader">
                                                <input type="text" id="category_id" name="category_id" value="<?=$goods['category_id']?>" style="display: none;" />
                                            </div>
                                        </div>
                                    </div>
                                    <!--<div class="layui-inline">
                                        <label class="layui-form-label"><a class="ys-way layui-btn layui-btn-xs layui-btn-normal">切换输入方式</a></label>
                                    </div>-->
                                </div>


                            <?php if(count($frequently_operation) > 1){ ?>

                                <div class="layui-form-item">
                                    <div class="layui-inline layui-col-md6">
                                        <label class="layui-form-label">最近分类</label>
                                        <div class="layui-input-block">
                                            <label class="layui-form-label" style="width: 560px;text-align: left">
                                            <?php foreach ($frequently_operation as $k=>$v){?>
                                                <a class="frequently_category_a" data-id="<?=$k?>" href="javascript:;"><?=$v?></a>
                                            <?php }?>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                            <?php }?>

                            <div id="source" class="layui-field-box">
                            </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="con2" style="padding: 10px; background-color: #F2F2F2; margin-top: 10px;">
            <div class="layui-row layui-col-space15">
                <div class="layui-col-md12">
                    <div class="layui-card">
                        <div class="layui-card-header">基本信息</div>

                        <div class="layui-card-body">

                            <div class="layui-field-box">

                            <div class="layui-form-item">
                                <?php if(!$goods->isNewRecord){?>
                                <div class="layui-inline ">
                                    <label class="layui-form-label">商品编号</label>
                                    <div class="layui-input-block">
                                        <label class="layui-form-label" style="width: 120px;text-align: left"><?=$goods['goods_no']?></label>
                                    </div>
                                </div>
                                <?php }?>

                                <?php if($is_fine_goods != 1){?>
                                    <div class="layui-inline">
                                        <label class="layui-form-label">SKU</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="sku_no" placeholder="请输入sku" value="<?=$goods['sku_no']?>"  class="layui-input" autocomplete="off">
                                        </div>
                                    </div>
                                <?php }else { ?>
                                    <?php if (!empty($goods['sku_no'])) { ?>
                                        <div class="layui-inline layui-col-md4">
                                            <label class="layui-form-label">SKU</label>
                                            <div class="layui-input-block">
                                                <label class="layui-form-label" style="width: 180px;text-align: left"><?= $goods['sku_no'] ?></label>
                                                <input type="hidden" name="sku_no" placeholder="请输入SKU编号" value="<?= $goods['sku_no'] ?>" class="layui-input">
                                            </div>
                                        </div>
                                    <?php }
                                }?>

                                <div class="layui-inline">
                                    <label class="layui-form-label">语言</label>
                                    <div class="layui-inline" style="width:140px">
                                        <?php
                                        echo \yii\helpers\Html::dropDownList('language', $goods['language'],\common\services\sys\CountryService::$goods_language ,['lay-ignore'=>'lay-ignore' ,'class'=>"layui-input search-con ys-select2"]);
                                        ?>
                                    </div>
                                </div>
                            </div>

                            <div class="layui-form-item">
                                <div class="layui-inline layui-col-md10">
                                    <label class="layui-form-label">英文标题</label>
                                    <div class="layui-input-block" style="position: relative;">
                                        <input type="text" id="goods_name" name="goods_name" lay-verify="required" placeholder="请输入标题" value="<?=htmlentities($goods['goods_name'], ENT_COMPAT);?>"  class="layui-input" autocomplete="off">
                                        <input type="hidden" id="goods_name_old" name="goods_name_old"  placeholder="请输入标题" value="<?=htmlentities($goods['goods_name'], ENT_COMPAT);?>"  class="layui-input" autocomplete="off">
                                    </div>
                                    <a class="open-ai" id="goods_name_ai" data-type="open" data-url="<?=Url::to(['tool/chatgpt?type=goods_name&html=1'])?>" data-width="750px" data-input="goods_name" data-height="500px" data-title="AI"><span style="position: absolute; bottom: 5px; right: 5px;" class="span-circular-ai">AI</span></a>
                                </div>
                                <div class="layui-inline">
                                    <label class="layui-form-label">字数：<span id="goods_name_count"></span></label>
                                </div>
                            </div>


                            <?php if(!empty($goods['goods_short_name'])){ ?>
                            <div class="layui-form-item">
                                <div class="layui-inline layui-col-md8">
                                    <label class="layui-form-label">英文短标题</label>
                                    <div class="layui-input-block">
                                        <input type="text" id="goods_short_name" name="goods_short_name" placeholder="请输入短标题" value="<?=htmlentities($goods['goods_short_name'], ENT_COMPAT);?>"  class="layui-input" autocomplete="off">
                                    </div>
                                </div>
                                <div class="layui-inline">
                                    <label class="layui-form-label">字数：<span id="goods_short_name_count"></span></label>
                                </div>
                            </div>
                            <?php }else{ ?>
                                <input type="hidden" name="goods_short_name" value=""  class="layui-input" autocomplete="off">
                            <?php } ?>


                            <div class="layui-form-item">
                                <div class="layui-inline layui-col-md8">
                                    <label class="layui-form-label">中文标题</label>
                                    <div class="layui-input-block">
                                        <input type="text" id="goods_name_cn" name="goods_name_cn" placeholder="请输入中文标题" value="<?=htmlentities($goods['goods_name_cn'], ENT_COMPAT);?>"  class="layui-input" autocomplete="off">
                                        <a class="open-ai" id="goods_name_cn_ai" data-type="open" data-url="<?=Url::to(['tool/chatgpt?type=goods_name_cn&html=1'])?>" data-width="750px" data-input="goods_name" data-height="500px" data-title="AI"><span style="position: absolute; bottom: 5px; right: 5px;" class="span-circular-ai">AI</span></a>
                                    </div>
                                </div>
                                <div class="layui-inline">
                                    <label class="layui-form-label">字数：<span id="goods_name_cn_count"></span></label>
                                </div>
                            </div>

                            <div class="layui-form-item">
                                <div class="layui-inline layui-col-md8">
                                    <label class="layui-form-label">中文短标题</label>
                                    <div class="layui-input-block">
                                        <input type="text" id="goods_short_name_cn" name="goods_short_name_cn" placeholder="请输入短标题" value="<?=htmlentities($goods['goods_short_name_cn'], ENT_COMPAT);?>"  class="layui-input" autocomplete="off">
                                    </div>
                                </div>
                                <div class="layui-inline">
                                    <label class="layui-form-label">字数：<span id="goods_short_name_cn_count"></span></label>
                                </div>
                            </div>

                            <div class="layui-form-item">
                                <div class="layui-inline layui-col-md12">
                                    <label class="layui-form-label">关键词</label>
                                    <div class="layui-input-block" style="border:1px solid #eee;">
                                        <div id="goods_keywords_div" style="padding: 0 5px">
                                        </div>
                                        <input type="text" style="width: 850px;border:0px" id="goods_keywords" placeholder="请输入关键词按回车添加" value="" class="layui-input" autocomplete="off">
                                    </div>
                                </div>
                            </div>

                            <div class="layui-form-item">
                                <?php if(empty($goods['source_method']) || $goods['source_method'] == \common\services\goods\GoodsService::SOURCE_METHOD_OWN){?>
                                <?php if(!$has_multi){?>
                                        <input type="hidden" name="goods_type" value="<?=empty($goods['goods_type'])?1:$goods['goods_type']?>">
                                <?php }else{ ?>
                                <div class="layui-inline">
                                    <label class="layui-form-label">商品类型</label>
                                    <div class="layui-input-block">
                                        <?php foreach (\common\models\Goods::$goods_type_map as $item_k=>$item_v) { ?>
                                            <input type="radio" lay-filter="goods_type"  name="goods_type" value="<?=$item_k?>"  <?php if(!empty($goods['goods_type'])) if(!$selection_id){{echo 'disabled=""';}}?>  title="<?=$item_v?>" <?php if(!empty($goods['goods_type']) && $item_k == $goods['goods_type']){ echo 'checked'; } ?>>
                                        <?php }?>
                                    </div>
                                </div>
                                <?php } ?>
                                <div class="layui-inline">
                                    <label class="layui-form-label">颜色</label>
                                    <div class="layui-inline">
                                        <!--<input type="text" name="colour" placeholder="请输入颜色" lay-verify="required" value="<?=$goods['colour']?>" class="layui-input" autocomplete="off">-->
                                        <?php
                                        $exist = array_key_exists($goods['colour'],\common\services\goods\GoodsService::$colour_map);
                                        $colour = null;
                                        if($exist){
                                            $colour = $goods['colour'];
                                        }
                                        echo \yii\helpers\Html::dropDownList('colour', $colour,\common\services\goods\GoodsService::getColourOpt() ,['lay-ignore'=>'lay-ignore','lay-verify'=>"required" ,'prompt' => '请选择','class'=>"layui-input search-con ys-select2"]);
                                        ?>
                                    </div>
                                    <div class="layui-inline" style="width: 80px">
                                        <label><?php
                                            if(!$exist){
                                                echo $goods['colour'];
                                            } ?></label>
                                    </div>
                                </div>

                                <div class="layui-inline">
                                    <label class="layui-form-label">规格型号</label>
                                    <div class="layui-input-block">
                                        <input type="text" name="specification" placeholder="请输入规格型号" value="<?=$goods['specification']?>" class="layui-input" autocomplete="off">
                                    </div>
                                </div>

                                <?php } ?>

                                <?php if(empty($goods['source_method']) || ($goods['source_method'] == \common\services\goods\GoodsService::SOURCE_METHOD_OWN && $goods['goods_stamp_tag'] != \common\models\Goods::GOODS_STAMP_TAG_OPEN_SHOP)){
                                }else{?>
                                    <div class="layui-inline">
                                        <label class="layui-form-label">品牌</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="brand" lay-verify="required" placeholder="请输入品牌"  value="<?=$goods['brand']?>" class="layui-input">
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>


                            <?php if(empty($goods['source_method']) || $goods['source_method'] == \common\services\goods\GoodsService::SOURCE_METHOD_OWN){?>
                                <div class="layui-form-item layui-form-text">
                                    <label class="layui-form-label">简要描述</label>
                                    <div class="layui-input-block">
                                        <textarea placeholder="请输入商品简要说明" class="layui-textarea" name="goods_desc"><?=$goods['goods_desc']?></textarea>
                                        <a class="open-ai" id="goods_desc_ai" data-type="open" data-url="<?=Url::to(['tool/chatgpt?type=goods_desc&html=1'])?>" data-width="750px" data-input="goods_desc" data-height="500px" data-title="AI"><span style="position: absolute; bottom: 5px; right: 5px;" class="span-circular-ai">AI</span></a>
                                    </div>
                                </div>
                            <?php }?>

                            <div class="layui-form-item layui-form-text">
                                <label class="layui-form-label">详细描述</label>
                                <div class="layui-input-block" style="position: relative;">
                                    <textarea placeholder="请输入商品详细说明" class="layui-textarea" style="height: 200px" name="goods_content" id="goods_content"><?=$goods['goods_content']?></textarea>
                                    <a class="open-ai" id="goods_content_ai" data-type="open" data-url="<?=Url::to(['tool/chatgpt?type=goods_content&html=1'])?>" data-width="750px" data-input="goods_content" data-height="500px" data-title="AI"><span style="position: absolute; bottom: 5px; right: 5px;" class="span-circular-ai">AI</span></a>
                                </div>
                            </div>

                           <?php if ($goods['source_platform_type'] != 0){?>
                               <div class="layui-form-item">
                                   <label class="layui-form-label" style="width: 90px;padding-bottom: 0px;">清除来源平台</label>
                                   <div class="layui-input-block">
                                       <input type="checkbox" lay-filter="type" lay-skin="primary" name="clear_source" value="1" >
                                   </div>
                               </div>
                           <?php }?>

                            <?php if(in_array($goods['status'],[\common\models\Goods::GOODS_STATUS_UNCONFIRMED,\common\models\Goods::GOODS_STATUS_UNALLOCATED,\common\models\Goods::GOODS_STATUS_WAIT_ADDED,\common\models\Goods::GOODS_STATUS_WAIT_MATCH])) {?>
                                <input type="hidden" name="status" value="<?=\common\models\Goods::GOODS_STATUS_VALID?>">
                            <?php }else{
                                $status_map = \common\models\Goods::$status_map;
                                unset($status_map[\common\models\Goods::GOODS_STATUS_UNCONFIRMED]);
                                unset($status_map[\common\models\Goods::GOODS_STATUS_UNALLOCATED]);
                                unset($status_map[\common\models\Goods::GOODS_STATUS_WAIT_ADDED]);
                                ?>
                                <div class="layui-form-item">
                                    <div class="layui-inline">
                                        <label class="layui-form-label">状态</label>
                                        <div class="layui-input-block">
                                            <?= \yii\helpers\Html::dropDownList('status', $goods['status'], $status_map,
                                                ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:150px']) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php }?>

                            <div id="attribute" style="display:none;">
                            </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="con3" style="padding: 10px; background-color: #F2F2F2; margin-top: 10px;">
            <div class="layui-row layui-col-space15">
                <div class="layui-col-md12">
                    <div class="layui-card">
                        <div class="layui-card-header">图片信息</div>

                        <div class="layui-card-body">

                            <div class="layui-form-item" style="padding-left: 20px">
                                <div class="layui-inline">
                                    <div class="layui-inline" style="width: 250px">
                                        <input type="text" name="img" id="img_url" placeholder="图片链接"  value="" class="layui-input" autocomplete="off">
                                    </div>
                                    <div class="layui-inline" style="width: 80px">
                                        <button type="button" class="layui-btn layui-btn-normal" id="js_add_img_url">添加</button>
                                    </div>
                                </div>

                                <div class="layui-upload ys-upload-img-multiple" data-number="10">
                                    <button type="button" class="layui-btn">上传图片</button>
                                    <input type="hidden" name="goods_img" class="layui-input" value="<?=empty($goods['goods_img'])?'[]':htmlentities($goods['goods_img'], ENT_COMPAT);?>">
                                    <ol class="layui-upload-con">
                                    </ol>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="con10" style="padding: 10px; background-color: #F2F2F2; margin-top: 10px;">
            <div class="layui-row layui-col-space15">
                <div class="layui-col-md12">
                    <div class="layui-card">
                        <div class="layui-card-header">视频信息</div>
                        <div class="layui-card-body">
                            <div class="layui-form-item">
                            <div class="layui-input-inline ys-upload-video" style="padding-left: 20px">
                                <div class="layui-inline" data-number="10">
                                    <a class="layui-btn layui-btn-warm ys-upload-file" lay-data="{url: '/app/upload-video',accept: 'file'}">上传视频</a>
                                    <input type="hidden" name="additional_video" id="video" class="layui-input" value="<?=empty($goods_additional['video'])?'':$goods_additional['video'];?>">
                                    <input type="hidden" id="create_video" value="10">
                                </div>
                                <ol class="layui-upload-video">
                                </ol>
                            </div>
                            <div class="layui-input-inline ys-upload-tk-video" style="padding-left: 20px">
                                <div class="layui-inline" data-number="10">
                                    <a class="layui-btn layui-btn-normal ys-upload-file" lay-data="{url: '/app/upload-video?type=tk',accept: 'file'}">上传tiktok视频</a>
                                    <input type="hidden" name="additional_tk_video" id="tk_video" class="layui-input" value="<?=empty($goods_additional['tk_video'])?'':$goods_additional['tk_video'];?>">
                                    <input type="hidden" id="create_tk_video" value="10">
                                </div>
                                <ol class="layui-upload-tk-video">
                                </ol>
                            </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="con14" style="padding: 10px; background-color: #F2F2F2; margin-top: 10px;">
            <div class="layui-row layui-col-space15">
                <div class="layui-col-md12">
                    <div class="layui-card">
                        <div class="layui-card-header">商品属性</div>
                        <div class="layui-card-body">
                            <div class="layui-form-item">
                            <div id="attribute_property">
                            </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="con4" style="padding: 10px; background-color: #F2F2F2; margin-top: 10px;">
            <div class="layui-row layui-col-space15">
                <div class="layui-col-md12">
                    <div class="layui-card">
                        <div class="layui-card-header">价格和运输</div>

                        <div class="layui-card-body">

                            <div class="layui-field-box">
                                <div class="layui-form-item">
                                    <div class="layui-inline">
                                        <label class="layui-form-label">价格</label>
                                        <div class="layui-inline">
                                            <input type="text" name="price" lay-verify="required|number" placeholder="请输入价格"  value="<?=$goods['price']?>" class="layui-input" autocomplete="off" style="width: 110px">
                                        </div>
                                        <?php if(GoodsService::isDistribution($goods['source_method_sub'])) { ?>
                                        <div class="layui-inline" style="width: 150px">
                                            <?= Html::dropDownList('currency',$goods['currency'],Goods::$goods_currency_maps,
                                                ['lay-ignore'=>'lay-ignore', 'class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
                                        </div>
                                        <?php }?>
                                    </div>
                                    <?php if($is_fine_goods != 1 || $has_gbp_price){?>
                                    <div class="layui-inline">
                                        <label class="layui-form-label">价格(GBP)</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="gbp_price" lay-verify="number" placeholder="请输入英镑价格"  value="<?=empty($goods['gbp_price'])?0:$goods['gbp_price']?>" class="layui-input" autocomplete="off" style="width: 110px">
                                        </div>
                                    </div>
                                    <?php }?>
                                    <div class="layui-inline">
                                        <label class="layui-form-label">货品种类<a style="font-size: 18px;color:#FFB800;font-weight: bold" class="js-help layui-icon layui-icon-about" href="javascript:;" title="帮助" data-content="●普货主要指常规商品，不含电、不含磁、不含液体、颗粒、粉末，例如衣服、鞋子等;</br>●敏感货主要指液体，粉末，膏状，乳状，凝胶状等化妆品及日化品;化妆品类液体、粉末和膏状产品及绘画颜料、染料粉、口腔清洁剂、墨水等，不接受其他任何类型的液体、所有含酒精的都不送，液体不超过500ml（其它类型的液体要归为不可寄送，其它类型的膏状物质暂归为正常）</br>●特货主要指含电含磁类商品，如手机，电子手表等"></a></label>
                                        <div class="layui-input-block">
                                            <?php foreach (\common\components\statics\Base::$electric_map as $item_k=>$item_v) { ?>
                                                <input type="radio" name="electric" value="<?=$item_k?>" title="<?=$item_v?>" <?php if(isset($goods['electric']) && $item_k == $goods['electric']){ echo 'checked'; } ?>>
                                            <?php }?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if ($goods['source_platform_type'] == Base::PLATFORM_HEPSIGLOBAL) {?>
                            <div class="layui-form-item">
                                <label class="layui-form-label">最高价格</label>
                                <label class="layui-form-label" style="padding-left: 10px;width: 120px;text-align: left">
                                    <span id="max_price" style="color: red">0.00</span>
                                </label>
                            </div>
                            <?php }?>

                            <?php if(empty($goods['source_method']) || $goods['source_method'] == \common\services\goods\GoodsService::SOURCE_METHOD_OWN){?>
                            <div class="layui-form-item">
                                <div class="layui-inline">
                                    <label class="layui-form-label">重量(kg)</label>
                                    <div class="layui-input-block">
                                        <input type="text" name="weight" lay-verify="required|number" id="weight" placeholder="请输入重量" value="<?=$goods['weight']?>" class="layui-input" autocomplete="off"><?php if($goods['real_weight'] > 0){?> 实际重量:<?=$goods['real_weight']?>kg<?php }?>
                                    </div>
                                </div>
                                <div class="layui-inline">
                                    <label class="layui-form-label">包装尺寸(cm)</label>
                                    <div class="layui-inline" style="width: 80px">
                                        <input type="text" name="size_l" lay-verify="number" placeholder="长"  value="<?=empty($size['size_l'])?0:$size['size_l']?>" class="layui-input" autocomplete="off">
                                    </div>
                                    <div class="layui-inline" style="width: 80px">
                                        <input type="text" name="size_w" lay-verify="number" placeholder="宽"  value="<?=empty($size['size_w'])?0:$size['size_w']?>" class="layui-input" autocomplete="off">
                                    </div>
                                    <div class="layui-inline" style="width: 80px">
                                        <input type="text" name="size_h" lay-verify="number" placeholder="高"  value="<?=empty($size['size_h'])?0:$size['size_h']?>" class="layui-input" autocomplete="off">
                                    </div>
                                </div>
                                <div class="layui-inline">
                                    <label class="layui-form-label">商品大小<a style="font-size: 18px;color:#FFB800;font-weight: bold" class="js-help layui-icon layui-icon-about" href="javascript:;" title="帮助" data-content="微件同时满足三边之和<=40cm,重量<=0.5kg</br>小件同时满足三边之和<=60cm,重量<=1.2kg</br>适中同时满足三边之和<=90cm,重量<=2kg</br>大件三边之和>90cm或重量>2kg"></a>
                                        </label>
                                    <div class="layui-input-block">
                                        <?php foreach (\common\models\Goods::$goods_size_type as $item_k=>$item_v) { ?>
                                            <input type="radio" name="size_type" value="<?=$item_k?>" title="<?=$item_v?>" <?php if(isset($goods['size_type']) && $item_k == $goods['size_type']){ echo 'checked'; } ?>>
                                        <?php }?>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="layui-col-md10 layui-col-xs12" style="padding: 10px">
        <?php if($has_multi){?>
        <div id="con5" class="m_property" style="padding: 10px; background-color: #F2F2F2; margin-top: 10px;<?php if(empty($goods['goods_type']) || $goods['goods_type'] != 2){ echo 'display: none';}  ?>">
            <div class="layui-row layui-col-space15">
                <div class="layui-col-md12">
                    <div class="layui-card">
                        <div class="layui-card-header">变体信息</div>
                        <div class="layui-card-body">

                            <div style="padding: 10px; background-color: #F2F2F2;margin:10px;">
                                <div class="layui-row layui-col-space15">
                                    <div class="layui-card">
                                        <div class="layui-card-header" style="line-height: 30px;height: 30px">颜色<span></div>
                                        <div class="layui-card-body" style="margin:0 20px" >

                                            <div id="colour-content">

                                            </div>
                                            <div class="layui-row" id="colour_other_con">
                                            </div>

                                            <!--其它：<div class="layui-inline" style="width: 200px">
                                                <input type="text" name="other" placeholder=""  value="" class="layui-input add_property_inp" autocomplete="off">
                                            </div>
                                            <div class="layui-inline" style="width: 80px">
                                                <button type="button" class="layui-btn layui-btn-normal add_property" data-type="colour">添加</button>
                                            </div>-->

                                        </div>
                                    </div>
                                </div>
                            </div>


                            <div style="padding: 10px; background-color: #F2F2F2;margin:10px;">
                                <div class="layui-row layui-col-space15">
                                    <div class="layui-card">
                                        <div class="layui-card-header" style="line-height: 30px;height: 30px">规格<span></div>
                                        <div class="layui-card-body" style="margin:0 20px">

                                            <div class="size-head">
                                                <!--<a href="javascript:" class="siz_title_a siz_title_a_sel">男装</a> <a href="javascript:" class="siz_title_a">男装</a> <a href="javascript:" class="siz_title_a">男装</a> <a href="javascript:" class="siz_title_a">男装</a>-->
                                            </div>
                                            <div id="size-content" style="margin-top: 10px">

                                            </div>
                                        </div>
                                        <div style="clear: both;margin-bottom: 20px"></div>
                                    </div>
                                </div>
                            </div>

                            <table class="layui-table" id="property_table" style="font-size: 13px">
                                <tbody>
                                <tr>
                                    <td class="layui-table-th" style="text-align: center;width: 75px">编号</td>
                                    <td class="layui-table-th" style="text-align: center;width: 160px;">图片</td>
                                    <td class="layui-table-th prop-colour" style="text-align: center;width: 130px;">颜色</td>
                                    <td class="layui-table-th prop-size" style="text-align: center;width: 130px; ">规格</td>
                                    <td class="layui-table-th" style="text-align: center;width: 90px;">价格</td>
                                    <td class="layui-table-th" style="text-align: center;width: 260px;">包装信息</td>
                                    <td class="layui-table-th" style="text-align: center;">操作
                                        <a id="batch_set_pro"><i class="layui-icon layui-icon-set"></i></a>
                                    </td>
                                </tr>
                                </tbody>
                            </table>

                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php }?>

            <div class="layui-form-item layui-layout-admin">
                <div class="layui-input-block">
                    <div class="layui-footer" style="left: 0;">
                    <input type="hidden" name="ogid" value="<?=(!empty($ogid)?$ogid:0);?>">
                    <input type="hidden" name="aid" value="<?=(!empty($aid)?$aid:0);?>">
                    <input type="hidden" id="goods_id" name="id" value="<?=$goods['id']?>">
                    <input type="hidden" name="source_method" value="<?=$goods['source_method']?>">
                    <input type="hidden" name="property_name" value="<?=$goods['property']?>">
                    <input type="hidden" name="source_method_sub" value="<?=$goods['source_method_sub']?>">
                    <input type="hidden" name="selection_id" id="selection_id" value="<?=$selection_id?>">
                    <input type="hidden" name="goods_no" value="<?=$goods['goods_no']?>">
                    <button class="layui-btn" lay-submit="" lay-filter="form" data-form="update_goods">立即提交</button>
                    <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                    <?php if(($goods['status'] == \common\models\Goods::GOODS_STATUS_VALID) || $goods['source_method_sub'] == \common\models\Goods::GOODS_SOURCE_METHOD_SUB_GRAB && $goods['status'] != \common\models\Goods::GOODS_STATUS_INVALID){ ?>
                        <button id="invalid_btn" data-url="<?=Url::to(['goods/batch-update-status?source_method='.$goods['source_method']])?>" class="layui-btn layui-btn-danger">立即禁用</button>
                    <?php } ?>
                    <?php if($goods['source_method_sub'] == \common\models\Goods::GOODS_SOURCE_METHOD_SUB_GRAB && $goods['status'] != \common\models\Goods::GOODS_STATUS_INVALID){ ?>
                        <button id="error_category_btn" data-url="<?=Url::to(['goods/batch-error-category?source_method='.$goods['source_method']])?>" class="layui-btn layui-btn-danger">类目错误</button>
                    <?php } ?>
                    </div>
                </div>
            </div>
    </div>

    <div>
        <div style="width:200px;height:0;position: fixed;bottom:425px;right: 0;">
        <ul class="layui-timeline">
            <li class="layui-timeline-item">
                <a href="#con1"><i class="layui-icon layui-timeline-axis">&#xe63f;</i>
                <div class="layui-timeline-content layui-text">
                    <h3 class="layui-timeline-title">平台信息</h3>
                </div></a>
            </li>
            <li class="layui-timeline-item">
                <a href="#con2"><i class="layui-icon layui-timeline-axis">&#xe63f;</i>
                <div class="layui-timeline-content layui-text">
                    <h3 class="layui-timeline-title">基本信息</h3>
                </div></a>
            </li>
            <li class="layui-timeline-item">
                <a href="#con3"><i class="layui-icon layui-timeline-axis">&#xe63f;</i>
                <div class="layui-timeline-content layui-text">
                    <h3 class="layui-timeline-title">图片信息</h3>
                </div></a>
            </li>
            <li class="layui-timeline-item">
                <a href="#con10"><i class="layui-icon layui-timeline-axis">&#xe63f;</i>
                    <div class="layui-timeline-content layui-text">
                        <h3 class="layui-timeline-title">视频信息</h3>
                </div></a>
            </li>
            <li class="layui-timeline-item">
                <a href="#con14"><i class="layui-icon layui-timeline-axis">&#xe63f;</i>
                    <div class="layui-timeline-content layui-text">
                        <h3 class="layui-timeline-title">商品属性</h3>
                </div></a>
            </li>
            <li class="layui-timeline-item">
                <a href="#con4"><i class="layui-icon layui-timeline-axis">&#xe63f;</i>
                <div class="layui-timeline-content layui-text">
                    <h3 class="layui-timeline-title">价格和运输</h3>
                </div></a>
            </li>
            <?php if($has_multi){?>
            <li class="layui-timeline-item m_property" <?php  if(empty($goods['goods_type']) || $goods['goods_type'] != 2){ echo 'style="display: none"';}  ?> >
                <a href="#con5"><i class="layui-icon layui-timeline-axis">&#xe63f;</i>
                <div class="layui-timeline-content layui-text">
                    <h3 class="layui-timeline-title">变体信息</h3>
                </div></a>
            </li>
            <?php }?>
        </ul>
        </div>
    </div>
</form>
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
<script id="colour_tpl" type="text/html">
    <div class="layui-inline">
        <input type="checkbox" lay-filter="property_colour" id="colour_{{ d.id || '' }}" name="p_Colour" data-name="Colour" value="{{ d.color || '' }}" title="" lay-skin="primary">
        <div style="line-height: 50px;display: inline-block;">
            <div class="m_property_color {{ d.class || '' }}" style="background: {{ d.rgb || '' }};background-size:100% 100%;">{{ d.name || '' }}</div>
        </div>
    </div>
</script>

<script id="property_tpl" type="text/html">
    <tr id="protr_{{ d.property.val_id || '' }}">
        <td>{{ d.property.sku_no || '' }}</td>
        <td width="200">
            <div class="layui-inline" style="padding-bottom: 10px">
                <div class="layui-inline" style="width: 150px">
                    <input type="text" name="img" placeholder="图片链接"  value="" class="layui-input img-url-inp" autocomplete="off">
                </div>
                <div class="layui-inline" style="width: 40px">
                    <a class="add-url-img">
                        <i class="layui-icon layui-icon-add-circle" title="添加图片链接" style="font-size: 25px;color:#1E9FFF;font-weight: 200;"></i>
                    </a>
                </div>
            </div>
            <div class="layui-upload ys-upload-img" >
                <button type="button" class="layui-btn layui-btn-xs" style="float: left">上传图片</button>
                <div class="layui-upload-list" style="float: left;margin: 0 10px">
                    <img class="layui-upload-img" style="max-width: 100px" src="{{ d.property.goods_img || '' }}">
                </div>
                <div class="img-tool">
                    <span class="layui-layer-setwin white_img_single" style="top: 135px;left: 35px;"><a style="font-size: 18px;color:#1E9FFF" class="layui-icon layui-icon-layer" href="javascript:;" title="图片白底"></a></span>
                </div>
                <input type="hidden" name="property[goods_img][]" class="layui-input" value="{{ d.property.goods_img || '' }}">
            </div>
        </td>
        <td class="prop-colour">
            <label class="l-prop-colour">{{ d.property.colour || '' }} {{# if(d.property.colour_name != ''){ }} ( {{ d.property.colour_name || ''}} ) {{# } }}</label>
            <input type="hidden" name="property[colour][]" value="{{ d.property.colour || '' }}">
        </td>
        <td class="prop-size">
            <label class="l-prop-size">{{ d.property.size || '' }}</label>
            <input type="hidden" name="property[size][]" value="{{ d.property.size || '' }}">
        </td>
        <td>
            <input type="text" name="property[price][]" lay-verify="required|number" placeholder="价格"  value="{{ d.property.price && d.property.price >0 ? d.property.price:'' }}" class="layui-input" autocomplete="off">
            <?php if($has_gbp_price){ ?>
            <hr class="layui-border-cyan">
            GBP:<input type="text" name="property[gbp_price][]" lay-verify="required|number" placeholder="GBP价格"  value="{{ d.property.gbp_price || '' }}" class="layui-input" autocomplete="off">
            <?php }?>
        </td>
        <td>
            <div class="layui-inline">
                <label style="padding-right: 5px;">重量</label>
                <div class="layui-inline">
                    <input type="text" name="property[weight][]" style="width: 90px" lay-verify="required|number" placeholder="重量" value="{{ d.property.weight &&  d.property.weight > 0 ?  d.property.weight : '' }}" class="layui-input" autocomplete="off">  {{# if(d.property.real_weight > 0){ }} 实际重量:{{ d.property.real_weight }}kg {{# } }}
                </div>
            </div>
            <hr class="layui-border-cyan">
            <div class="layui-inline">
                <label style="padding-right: 5px;">尺寸</label>
                <div class="layui-inline" style="width: 70px">
                    <input type="text" name="property[size_l][]" lay-verify="number" placeholder="长"  value="{{ d.property.size_l || '0' }}" class="layui-input" autocomplete="off">
                </div>
                <div class="layui-inline" style="width: 70px">
                    <input type="text" name="property[size_w][]" lay-verify="number" placeholder="宽"  value="{{ d.property.size_w || '0' }}" class="layui-input" autocomplete="off">
                </div>
                <div class="layui-inline" style="width: 70px">
                    <input type="text" name="property[size_h][]" lay-verify="number" placeholder="高"  value="{{ d.property.size_h || '0' }}" class="layui-input" autocomplete="off">
                </div>
            </div>
        </td>
        <td>
            <div class="layui-inline">
                <input type="hidden" name="property[id][]" value="{{ d.property.id || '' }}" class="layui-input">
                <a class="layui-btn layui-btn-danger layui-btn-xs del-property" href="javascript:;">删除</a>

                <a class="batch_set_pro_child" style="margin-left: 5px"><i class="layui-icon layui-icon-set"></i></a>
            </div>
        </td>
    </tr>
</script>

<script id="size_tpl" type="text/html">
    {{# if(d.type=='Man'){ }}
    <table class="layui-table">
        <thead>
        <tr>
            <th rowspan="2"></th>
            <th colspan="2">Chest</th>
            <th colspan="2">Waist</th>
            <th colspan="2">Neck</th>
            <th colspan="2">Sleeve</th>
        </tr>
        <tr>
            <th>厘米</th>
            <th>英寸</th>
            <th>厘米</th>
            <th>英寸</th>
            <th>厘米</th>
            <th>英寸</th>
            <th>厘米</th>
            <th>英寸</th>
        </tr>
        </thead>
        <tbody>
        {{# layui.each(d.data, function(index, item){ }}
        <tr>
            <td><input type="checkbox" lay-filter="property_size" id="size_{{ item.id }}" name="p_{{ d.id }}" data-name="{{ d.id }}" value="{{ item.size }}" title="{{ item.size }}" lay-skin="primary"></td>
            <td>{{ item.chestA }}</td>
            <td>{{ item.chestB }}</td>
            <td>{{ item.waistA }}</td>
            <td>{{ item.waistB }}</td>
            <td>{{ item.neckA }}</td>
            <td>{{ item.neckB }}</td>
            <td>{{ item.sleeveA }}</td>
            <td>{{ item.sleeveB }}</td>
        </tr>
        {{#  }); }}
        </tbody>
    </table>
    {{# } }}

    {{# if(d.type=='Women'){ }}
    <table class="layui-table">
        <thead>
        <tr>
            <th rowspan="2"></th>
            <th colspan="2">Bust/Chest</th>
            <th colspan="2">Waist</th>
            <th colspan="2">Hip</th>
        </tr>
        <tr class="text-center">
            <th>厘米</th>
            <th>英寸</th>
            <th>厘米</th>
            <th>英寸</th>
            <th>厘米</th>
            <th>英寸</th>
        </tr>
        </thead>
        <tbody>
        {{# layui.each(d.data, function(index, item){ }}
        <tr>
            <td><input type="checkbox" lay-filter="property_size" id="size_{{ item.id }}" name="p_{{ d.id }}" data-name="{{ d.id }}" value="{{ item.size }}" title="{{ item.size }}" lay-skin="primary"></td>
            <td>{{ item.bustA }}</td>
            <td>{{ item.bustB }}</td>
            <td>{{ item.waistA }}</td>
            <td>{{ item.waistB }}</td>
            <td>{{ item.hipA }}</td>
            <td>{{ item.hipB }}</td>
        </tr>
        {{#  }); }}
        </tbody>
    </table>
    {{# } }}

    {{# if(d.type=='Checkbox'){ }}
        {{# layui.each(d.data, function(index, item){ }}
        <div class="layui-col-xs4"><input type="checkbox" lay-filter="property_size" id="size_{{ item.id }}" name="p_{{ d.id }}" data-name="{{ d.id }}" value="{{ item.size }}" title="{{ item.size }}" lay-skin="primary"></div>
        {{#  }); }}
    {{# } }}

    {{# if(d.type=='ElectricPlugs'){ }}
    {{# layui.each(d.data, function(index, item){ }}
    <div class="layui-col-xs4"><input type="checkbox" lay-filter="property_size" id="size_{{ item.id }}" name="p_{{ d.id }}" data-name="{{ d.id }}" value="{{ item.size }}" title="{{ item.size }}" lay-skin="primary"><img src="/static/img/{{ item.img }}.png" style="margin-left:10px;width:20px;"></div>
    {{#  }); }}
    {{# } }}

    {{# if(d.type=='ManShoes'){ }}
    <table class="layui-table">
        <thead>
        <tr>
            <th colspan="2"></th>
            <th colspan="2">Length of Foot</th>
        </tr>
        <tr>
            <th>US Size</th>
            <th>European</th>
            <th>英寸</th>
            <th>厘米</th>
        </tr>
        </thead>
        <tbody>
        {{# layui.each(d.data, function(index, item){ }}
        <tr>
            <td><input type="checkbox" lay-filter="property_size" id="size_{{ item.id }}" name="p_{{ d.id }}" data-name="{{ d.id }}" value="{{ item.size }}" title="{{ item.size }}" lay-skin="primary"></td>
            <td>{{ item.sizeA }}</td>
            <td>{{ item.sizeB }}</td>
            <td>{{ item.sizeC }}</td>
        </tr>
        {{#  }); }}
        </tbody>
    </table>
    {{# } }}

    {{# if(d.type=='MenSuitTuxedos'){ }}
    <table class="layui-table">
        <thead>
        <tr>
            <th rowspan="2"></th>
            <th colspan="2">Chest</th>
            <th colspan="2" class="text-center">高度</th>
        </tr>
        <tr>
            <th>厘米</th>
            <th>英寸</th>
            <th>厘米</th>
            <th>英寸</th>
        </tr>
        </thead>
        <tbody>
        {{# layui.each(d.data, function(index, item){ }}
        <tr>
            <td><input type="checkbox" lay-filter="property_size" id="size_{{ item.id }}" name="p_{{ d.id }}" data-name="{{ d.id }}" value="{{ item.size }}" title="{{ item.size }}" lay-skin="primary"></td>
            <td>{{ item.sizeA }}</td>
            <td>{{ item.sizeB }}</td>
            <td>{{ item.sizeC }}</td>
            <td>{{ item.sizeD }}</td>
        </tr>
        {{#  }); }}
        </tbody>
    </table>
    {{# } }}

    <!--<div>
        {{# layui.each(d.data, function(index, item){ }}
        <input type="checkbox" name="{{ d.id }}" value="{{ item.size }}" title="{{ item.size }}" lay-skin="primary">
        {{#  }); }}
    </div>-->

    <div class="layui-row" id="size_other_con">
    </div>

    <div class="layui-row" style="margin-top: 5px">
    {{# if(d.example){ }} {{ d.example }} <br/> {{# } }}

    {{# if(d.other == true){ }}
    其它：<div class="layui-inline" style="width: 200px">
            <input type="text" name="other" placeholder=""  value="" class="layui-input add_property_inp" autocomplete="off">
        </div>
        <div class="layui-inline" style="width: 80px">
            <button type="button" class="layui-btn layui-btn-normal add_property" data-type="size" data-sizetype="{{ d.id }}">添加</button>
        </div>
    {{# } }}
    </div>
</script>

<script id="customize_checkbox_tpl" type="text/html">
    <div class="edit-customize  {{# if(d.type!='size'){ }}layui-inline {{# }else{ }}layui-col-xs4{{# } }}">
        <input type="checkbox" lay-filter="property_{{ d.type }}" id="{{ d.type }}_{{ d.id }}" data-type="{{ d.type }}" name="p_{{ d.t_type }}" data-name="{{ d.t_type }}" value="{{ d.value }}" lay-skin="primary">
        <div class="edit-label">
            <span class="label-name">{{ d.value }}</span>
            <a class="edit-icon edit-edit-icon"><i class="layui-icon layui-icon-edit" style="font-size: 20px;font-weight: 200;"></i></a>
        </div>
        <div class="edit-input">
            <div class="layui-inline" style="width: 80px">
                <input type="text" name="size_other" placeholder=""  value="" class="layui-input add_size_inp" autocomplete="off">
            </div>
            <a class="edit-icon edit-ok-icon"><i class="layui-icon layui-icon-ok-circle" style="font-size: 20px;font-weight: 200;"></i></a>
            <a class="edit-icon edit-close-icon"><i class="layui-icon layui-icon-close-fill" style="font-size: 20px;font-weight: 200;"></i></a>
        </div>
    </div>
</script>
<script id="tag_tpl" type="text/html">
    <span class="label layui-bg-blue" style="border-radius: 15px;margin: 5px 5px 0 0; padding: 3px 7px 3px 15px; font-size: 14px; display: inline-block;">
        {{d.tag_name}}
        <a href="javascript:;"><i class="layui-icon layui-icon-close del_tag" style="color: #FFFFFF;margin-left: 5px"></i></a>
        <input class="goods_keywords_ipt" type="hidden" name="goods_keywords[]" value="{{d.tag_name}}" >
    </span>
</script>

<script id="img_tpl" type="text/html">
    <li class="layui-fluid lay-image">
        <div class="layui-upload-list">
            <a href="{{ d.img || '' }}" data-lightbox="pic">
                <img class="layui-upload-img" style="max-width: 200px;height: 100px"  src="{{ d.img || '' }}">
            </a>
        </div>
        <div class="del-img">
            <span class="layui-layer-setwin"><a class="layui-layer-ico layui-layer-close layui-layer-close1" href="javascript:;"></a></span>
        </div>
        <div class="img-tool">
            <span class="layui-layer-setwin translate_img" style="top: 135px;left: 10px;"><a style="font-size: 18px;color:#1E9FFF" class="layui-icon layui-icon-fonts-clear" href="javascript:;" title="翻译成英文"></a></span>

            <span class="layui-layer-setwin white_img" style="top: 135px;left: 35px;"><a style="font-size: 18px;color:#1E9FFF" class="layui-icon layui-icon-layer" href="javascript:;" title="图片白底"></a></span>
        </div>
    </li>
</script>
<script id="video_tpl" type="text/html">
    <li class="layui-fluid lay-image">
        <div class="layui-upload-list">
            <video id="video_d" width="142" height="162" controls>
                <source  src="{{ d.video }}" type="video/mp4">
                <source  src="{{ d.video }}" type="video/ogg">
                <source  src="{{ d.video }}" type="video/webm">
                <object data="{{ d.video }}" width="142" height="162">
                    <embed src="{{ d.video }}" width="162" height="162">
                </object>
            </video>
        </div>
        <div class="del-video">
            <span class="layui-layer-setwin"><a class="layui-layer-ico layui-layer-close layui-layer-close1" href="javascript:;"></a></span>
        </div>
    </li>
</script>
<script id="tk_video_tpl" type="text/html">
    <li class="layui-fluid lay-image">
        <div class="layui-upload-list">
            <video id="tk_video_d" width="142" height="162" controls>
                <source src="{{ d.video }}" type="video/mp4">
                <source src="{{ d.video }}" type="video/ogg">
                <source src="{{ d.video }}" type="video/webm">
                <object data="{{ d.video }}" width="142" height="162">
                    <embed src="{{ d.video }}" width="162" height="162">
                </object>
            </video>
        </div>
        <div class="del-tk-video">
            <span class="layui-layer-setwin"><a class="layui-layer-ico layui-layer-close layui-layer-close1" href="javascript:;"></a></span>
        </div>
    </li>
</script>
<script id="source_tpl" type="text/html">
    <div class="layui-form-item change">
        <div class="layui-inline layui-col-md3">
            <label class="layui-form-label">{{# if(d.is_init==0){ }}来源{{# } }}</label>
            <div class="layui-input-block">
                <select lay-verify="required" class="layui-input search-con ys-select2 source_platform"  lay-ignore name="source[platform_type][]">
                    <?php
                    $source_method = empty($goods['source_method'])?\common\services\goods\GoodsService::SOURCE_METHOD_OWN:$goods['source_method'];
                    foreach (\common\services\goods\GoodsService::getGoodsSource($source_method) as $k=> $v){
                        ?>
                        <option value="<?=$k?>" {{# if(d.source.platform_type && d.source.platform_type == <?=$k?> ){ }} selected {{#  } }} ><?=$v?></option>
                    <?php }?>
                </select>
            </div>
        </div>
        <div class="layui-inline layui-col-md6 platform_url" style="{{# if(d.source.platform_type == 9999 || d.source.platform_type == 9000){ }} display: none {{# } }}">
            <input type="text" name="source[platform_url][]" placeholder="来源URL" value="{{ d.source.platform_url || '' }}"  class="layui-input">
        </div>

        <div class="layui-inline layui-col-md6 select_supplier" style="{{# if(d.source.platform_type != 9999){ }} display: none {{# } }}">
            <select lay-verify="required" class="layui-input search-con ys-select2"  lay-ignore name="source[supplier_id][]">
                <?php
                foreach (Supplier::allSupplierName() as $k=> $v){
                    ?>
                    <option value="<?=$k?>" {{# if(d.source.supplier_id && d.source.supplier_id == <?=$k?> ){ }} selected {{#  } }}><?=$v?></option>
                <?php }?>
            </select>
        </div>

        <div class="layui-inline layui-col-md6 select_supplier_warehouse" style="{{# if(d.source.platform_type != 9000){ }} display: none; {{# } }}">
            <select lay-verify="required" class="layui-input search-con ys-select2"  lay-ignore name="source[warehouse_supplier_id][]">
                <?php
                foreach (WarehouseService::getWarehouseMap(5) as $k=> $v){
                    ?>
                    <option value="<?=$k?>" {{# if(d.source.supplier_id && d.source.supplier_id == <?=$k?> ){ }} selected {{#  } }}><?=$v?></option>
                <?php }?>
            </select>
        </div>

        <div class="layui-inline layui-col-md1">
            <input type="text" name="source[price][]" placeholder="价格" value="{{ d.source.price || '' }}"  class="layui-input source_price">
        </div>
        {{# if(d.is_init==0){ }}
        <div class="layui-inline " id="add-source">
            <a href="javascript:;"><i class="layui-icon layui-icon-add-1"  style="font-size: 20px; font-weight: bold;line-height: 40px"></i></i></a>
        </div>
        {{# }else{ }}
        <div class="layui-inline " id="del-source">
            <a href="javascript:;"><i class="layui-icon layui-icon-close layui-icon-close1" style="font-size: 20px; font-weight: bold;line-height: 40px"></i></i></a>
        </div>
        {{# } }}
        <input type="hidden" name="source[id][]" value="{{ d.source.id || '' }}" class="layui-input">

        <div class="layui-inline" >
            <a href="{{# if(d.source.platform_type == 9999 && d.source.supplier_id != 0) { }} {{ d.source.url || '' }} {{# }else{ }} {{ d.source.platform_url || '' }} {{# } }}" target="_blank"><i class="layui-icon layui-icon-link" style="color: #00a0e9;font-size: 20px; font-weight: bold;line-height: 40px"></i></i></a>
        </div>
    </div>
</script>

<script id="attribute_tpl" type="text/html">
    <div class="layui-form-item" style="margin-bottom:0">
        <label class="layui-form-label">{{# if(d.is_init==0){ }}商品属性{{# } }}</label>
        <div class="layui-input-block">
            <div class="layui-inline layui-col-md2">
                <input type="text" name="attribute[attribute_name][]" placeholder="属性名称" value="{{ d.attribute.attribute_name || '' }}"  class="layui-input">
            </div>
            <div class="layui-inline layui-col-md2">
                <input type="text" name="attribute[attribute_value][]" placeholder="属性值" value="{{ d.attribute.attribute_value || '' }}"  class="layui-input">
            </div>
            {{# if(d.is_init==0){ }}
            <div class="layui-inline" id="add-attribute">
                <a href="javascript:;"><i class="layui-icon layui-icon-add-1"  style="font-size: 20px; font-weight: bold;line-height: 40px"></i></i></a>
            </div>
            {{# }else{ }}
            <div class="layui-inline" id="del-attribute">
                <a href="javascript:;"><i class="layui-icon layui-icon-close layui-icon-close1" style="font-size: 20px; font-weight: bold;line-height: 40px"></i></i></a>
            </div>
            {{# } }}
        </div>
        <input type="hidden" name="attribute[id][]" value="{{ d.attribute.id || '' }}" class="layui-input">
    </div>
</script>

<script id="attribute_property_tpl" type="text/html">
        <div class="layui-inline layui-col-md5" style="min-height: 49px">
            <label class="layui-form-label" style="width: 150px">{{ d.category_property.property_name || '' }}
                {{# if(d.category_property.is_required == 1){ }}<span style="color: red">*</span>{{# } }}</label>
            <div class="layui-input-block property_change" style="margin-left: 180px;">
                {{# if(d.category_property.property_type == 'select'){ }}
                <div class="layui-inline">
                <select class="layui-input search-con ys-select2 select_property" {{# if(d.category_property.is_multiple == 1){ }}multiple="multiple"{{# } }}  lay-ignore="lay-ignore" style="width: {{ d.category_property.width }}px" name="attribute_value[{{ d.category_property.id }}]{{# if(d.category_property.is_multiple == 1){ }}[]{{# } }}">
                    <option value="">请选择</option>
                    {{# for(let i in d.category_property_value){
                    var item = d.category_property_value[i]; }}
                    {{# if(d.property_value_id instanceof Array){
                        var sel_val = false;
                        layui.each(d.property_value_id, function(sel_index,sel_item){
                        if(item.id == sel_item){
                        sel_val = true;
                    }
                    }); }}
                        <option {{# if(sel_val){ }}selected {{# } }} value="{{ item.id }}">{{ item.property_value }}</option>
                    {{# }else{ }}
                        <option {{# if(d.property_value_id == item.id){ }}selected {{# } }} value="{{ item.id }}">{{ item.property_value }}</option>
                    {{# } }}
                    {{# } }}
                </select>
                </div>
                <div class="layui-inline">{{ d.category_property.unit }}</div>
                <input type="hidden" class="custom_property_id"  value="{{ d.category_property.custom_property_value_id }}">
                {{# if(d.property_value_id instanceof Array){ }}
                <input type="text" class="layui-input text_custom_property" value="{{ d.property_value || '' }}" placeholder="请输入" name="attribute_value_custom[{{ d.category_property.custom_property_value_id }}]" {{# if(d.property_value_id.indexOf(d.category_property.custom_property_value_id) != '-1'){ }} style="display: block;width: {{ d.category_property.width }}px;margin-top: 10px" {{# } }} style="display: none;width: {{ d.category_property.width }}px;margin-top: 10px">
                {{# }else{ }}
                <input type="text" class="layui-input text_custom_property" value="{{ d.property_value || '' }}" placeholder="请输入" name="attribute_value_custom[{{ d.category_property.custom_property_value_id }}]" {{# if(d.property_value_id == d.category_property.custom_property_value_id){ }} style="display: block;width: {{ d.category_property.width }}px;margin-top: 10px" {{# } }} style="display: none;width: {{ d.category_property.width }}px;margin-top: 10px">
                {{# } }}
                {{# } else if(d.category_property.property_type == 'radio'){ }}
                <input type="hidden" value="" name="attribute_value[{{ d.category_property.id }}]">
                    {{# for(let i in d.category_property_value){
                    var item = d.category_property_value[i]; }}
                    <input class="layui-input search-con" type="radio" value="{{ item.id }}" name="attribute_value[{{ d.category_property.id }}]" {{# if(d.property_value_id == item.id){ }}checked {{# } }} lay-skin="primary" title="{{ item.property_value }}">
                    {{# } }}
                {{# } else if(d.category_property.property_type == 'text'){ }}
                <div class="layui-inline">
                    <input type="text" name="attribute_value[{{ d.category_property.id }}]" placeholder="请输入" value="{{ d.property_value || '' }}"  class="layui-input" style="width: {{ d.category_property.width }}px">
                </div>
                <div class="layui-inline">{{ d.category_property.unit }}</div>
                {{# } else { }}
                <div class="layui-inline" style="width: {{ d.category_property.width / 3 }}px">
                    <input type="text" name="attribute_value[{{ d.category_property.id }}][size_l]"  placeholder="长"  {{# if(d.property_value instanceof Array){ }} value="{{ d.property_value[0] || '' }}" {{# } }} class="layui-input" autocomplete="off">
                </div>
                <div class="layui-inline" style="width: {{ d.category_property.width / 3 }}px">
                    <input type="text" name="attribute_value[{{ d.category_property.id }}][size_w]"  placeholder="宽"  {{# if(d.property_value instanceof Array){ }} value="{{ d.property_value[1] || '' }}" {{# } }} class="layui-input" autocomplete="off">
                </div>
                <div class="layui-inline" style="width: {{ d.category_property.width / 3 }}px">
                    <input type="text" name="attribute_value[{{ d.category_property.id }}][size_h]"  placeholder="高"  {{# if(d.property_value instanceof Array){ }} value="{{ d.property_value[2] || '' }}" {{# } }} class="layui-input" autocomplete="off">
                </div>
                <div class="layui-inline">
                    {{ d.category_property.unit }}
                </div>
                {{# } }}
            </div>
        </div>
    </div>
</script>

<?php \backend\assets\CategoryJsAsset::register($this);?>
<?php
$this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/jquery-ui.min.js", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPageJs/goods/goods_param.js?".time());
?>
<script type="text/javascript">
    var cat_sel_cascader = '1';
    var source = <?=empty($source)?"''":$source;?>;
    var attribute = <?=empty($attribute)?"''":$attribute;?>;
    var property = <?=empty($property)?"''":$property;?>;
    var source_method = <?=$goods['source_method']?>;
    var tag_name = '<?=addslashes($goods['goods_keywords'])?>';
    var property_data = <?=empty($goods['property'])?'{type:{},customize:{},size:[],colour:[]}':$goods['property'];?>;
    var goods_property = <?=empty($goods_property)? "'1'" :json_encode($goods_property,JSON_UNESCAPED_UNICODE);?>;
    //var category_tree = 1;
</script>
<?php
$this->registerJsFile("@adminPageJs/goods/form.js?".time());
?>



