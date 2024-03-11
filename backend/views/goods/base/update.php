<?php
use yii\helpers\Url;
$platform_name = \common\components\statics\Base::$platform_maps[$platform_type];
$url_platform_name = strtolower($platform_name);
?>
<style>
    html {
        background: #fff;
    }
    .layui-form-item{
        margin-bottom: 5px;
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
</style>
<form class="layui-form layui-row" id="update_goods" action="<?=Url::to([$main_goods->isNewRecord?'goods-'.$url_platform_name.'/create':'goods-'.$url_platform_name.'/update'])?>">
    <div class="layui-col-md9 layui-col-xs12" style="padding: 10px">

        <div style="padding: 10px; background-color: #F2F2F2; margin-top: 10px;">
            <div class="layui-row layui-col-space15">
                <div class="layui-col-md12">
                    <div class="layui-card">
                        <div class="layui-card-header">商品信息</div>

                        <div class="layui-card-body">

                            <div class="layui-field-box">

                            <div class="layui-form-item">
                                <div class="layui-inline layui-col-md4">
                                    <label class="layui-form-label">商品编号</label>
                                    <div class="layui-input-block">
                                        <label class="layui-form-label" style="width: 120px;text-align: left"><?=$goods['goods_no']?></label>
                                    </div>
                                </div>
                                <div class="layui-inline layui-col-md4">
                                    <label class="layui-form-label">SKU</label>
                                    <div class="layui-input-block">
                                        <label class="layui-form-label" style="width: 120px;text-align: left"><?=$goods['sku_no']?></label>
                                    </div>
                                </div>
                            </div>

                            <div class="layui-form-item">
                                <div class="layui-inline layui-col-md6">
                                    <label class="layui-form-label"><?=$platform_name?>类目</label>
                                    <div class="layui-input-block">
                                        <input type="text" name="o_category_name" placeholder="请输入类目名称" value="<?=$main_goods['o_category_name']?>"  class="layui-input">
                                    </div>
                                </div>
                            </div>

                            <div class="layui-form-item">
                                <div class="layui-inline layui-col-md9">
                                    <label class="layui-form-label">标题</label>
                                    <div class="layui-input-block">
                                        <input type="text" id="goods_name" name="goods_name" lay-verify="required" placeholder="请输入标题" value="<?=$main_goods['goods_name']?>"  class="layui-input">
                                    </div>
                                </div>
                                <div class="layui-inline">
                                    <label class="layui-form-label">字数：<span id="title-count"></span></label>
                                </div>
                            </div>

                            <div class="layui-form-item">
                                <div class="layui-inline layui-col-md9">
                                    <label class="layui-form-label">短标题</label>
                                    <div class="layui-input-block">
                                        <input type="text" id="goods_short_name" name="goods_short_name" placeholder="请输入短标题" value="<?=$main_goods['goods_short_name']?>"  class="layui-input">
                                    </div>
                                    <?php if($platform_type == \common\components\statics\Base::PLATFORM_ALLEGRO){?>
                                        <a class="open-ai" id="base_goods_name_ai" data-type="open" data-url="<?=Url::to(['tool/chatgpt?type=allegro_goods_name&html=1'])?>" data-width="750px" data-input="goods_short_name" data-height="500px" data-title="AI"><span style="position: absolute; bottom: 5px; right: 5px;" class="span-circular-ai">AI</span></i></a>
                                    <?php } ?>
                                </div>
                                <div class="layui-inline">
                                    <label class="layui-form-label">字数：<span id="short-title-count"></span></label>
                                </div>
                            </div>

                            <div class="layui-form-item">
                                <div class="layui-inline">
                                    <!--<div class="layui-inline">
                                        <label class="layui-form-label">原价</label>
                                        <label class="layui-form-label" style="width: 80px;text-align: left"><?=$shop_goods_model['original_price']?></label>
                                    </div>

                                    <div class="layui-inline">
                                        <label class="layui-form-label">折扣</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="discount" style="width: 80px" placeholder="请输入折扣" value="<?=empty($shop_goods_model['discount'])?10:$shop_goods_model['discount'] ?>" class="layui-input">
                                        </div>
                                    </div>-->

                                    <div class="layui-inline">
                                        <label class="layui-form-label">售价</label>
                                        <label class="layui-form-label" style="width: 80px;text-align: left"><?=$shop_goods_model['price']?></label>
                                    </div>
                                </div>
                            </div>

                            <div class="layui-form-item">
                                <div class="layui-inline">
                                    <label class="layui-form-label">品牌</label>
                                    <div class="layui-input-block">
                                        <input type="text" name="brand" placeholder="请输入品牌"  value="<?=$main_goods['brand']?>" class="layui-input">
                                    </div>
                                </div>

                                <div class="layui-inline">
                                    <label class="layui-form-label">颜色</label>
                                    <div class="layui-input-block">
                                        <input type="text" name="colour" placeholder="请输入颜色"  value="<?=$main_goods['colour']?>" class="layui-input">
                                    </div>
                                </div>
                            </div>

                            <div class="layui-form-item">
                                <div class="layui-inline">
                                    <label class="layui-form-label">尺寸</label>
                                    <label class="layui-form-label" style="width: 120px;text-align: left"><?=$goods['size']?></label>
                                </div>

                                <div class="layui-inline">
                                    <label class="layui-form-label">重量(kg)</label>
                                    <label class="layui-form-label" style="width: 120px;text-align: left"><?=$goods['weight']?></label>
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
                                <label class="layui-form-label">简要描述</label>
                                <div class="layui-input-block">
                                    <textarea placeholder="请输入商品简要说明" class="layui-textarea" name="goods_desc"><?=$main_goods['goods_desc']?></textarea>
                                </div>
                            </div>

                            <div class="layui-form-item layui-form-text">
                                <label class="layui-form-label">详细描述</label>
                                <div class="layui-input-block">
                                    <textarea placeholder="请输入商品详细说明" class="layui-textarea" style="height: 200px" name="goods_content" id="goods_content"><?=$main_goods['goods_content']?></textarea>
                                </div>
                            </div>

                            <div class="layui-form-item">
                                <div class="layui-input-block">
                                    <input type="hidden" name="id" value="<?=$shop_goods_model['id']?>">
                                    <input type="hidden" name="platform_type" value="<?=$platform_type?>" class="layui-input">
                                    <button class="layui-btn" lay-submit="" lay-filter="form" data-form="update_goods">立即提交</button>
                                    <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                                </div>
                            </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script id="img_tpl" type="text/html">
    <div class="layui-fluid" style="float: left;padding: 20px; border: 1px solid #eee;margin: 5px">
        <div class="layui-upload-list">
            <img class="layui-upload-img" style="max-width: 200px;height: 100px"  src="{{ d.img || '' }}">
        </div>
    </div>
</script>
<?php
$this->registerJsFile("@adminPageJs/goods/base_form.js?".time());
?>

