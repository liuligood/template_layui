<?php
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
</style>
<form class="layui-form layui-row" id="update_goods" action="<?=Url::to(['purchase-proposal/update'])?>">
    <div class="layui-col-md12 layui-col-xs12" style="padding: 0 10px">

        <div class="layui-field-box">
            <div id="source" class="layui-field-box">
            </div>

            <div class="layui-form-item">
                <div class="layui-inline">
                    <label class="layui-form-label">商品编号</label>
                    <div class="layui-input-block">
                        <label class="layui-form-label" style="width: 120px;text-align: left"><?=$goods['goods_no']?></label>
                    </div>
                </div>

                <div class="layui-inline">
                    <label class="layui-form-label">SKU</label>
                    <div class="layui-input-block">
                        <label class="layui-form-label" style="width: 200px;text-align: left"><?=$goods['sku_no']?></label>
                    </div>
                </div>
            </div>

            <div class="layui-form-item">
                <div class="layui-inline">
                    <label class="layui-form-label">价格</label>
                    <div class="layui-input-block">
                        <input type="text" name="price" lay-verify="required|number" placeholder="请输入价格"  value="<?=$goods['price']?>" class="layui-input" autocomplete="off">
                    </div>
                </div>
                <div class="layui-inline">
                    <label class="layui-form-label">实际重量(kg)</label>
                    <div class="layui-input-block">
                        <input type="text" name="real_weight" lay-verify="required|number" placeholder="请输入重量" value="<?=$goods['real_weight'] == 0?$goods['weight']:$goods['real_weight'] ?>" class="layui-input" autocomplete="off">
                    </div>
                </div>

                <div class="layui-inline">
                    <label class="layui-form-label">规格型号</label>
                    <div class="layui-input-block">
                        <input type="text" name="specification" placeholder="请输入规格型号" value="<?=$goods['specification']?>" class="layui-input" autocomplete="off">
                    </div>
                </div>
            </div>

            <div class="layui-form-item">
                <div class="layui-inline" >
                    <label class="layui-form-label" style="width: 120px;">包装尺寸(cm)</label>
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
                    <label class="layui-form-label">货品种类<a style="font-size: 18px;color:#FFB800;font-weight: bold" class="js-help layui-icon layui-icon-about" href="javascript:;" title="帮助" data-content="●普货主要指常规商品，不含电、不含磁、不含液体、颗粒、粉末，例如衣服、鞋子等;</br>●敏感货主要指液体，粉末，膏状，乳状，凝胶状等化妆品及日化品;化妆品类液体、粉末和膏状产品及绘画颜料、染料粉、口腔清洁剂、墨水等，不接受其他任何类型的液体、所有含酒精的都不送，液体不超过500ml（其它类型的液体要归为不可寄送，其它类型的膏状物质暂归为正常）</br>●特货主要指含电含磁类商品，如手机，电子手表等"></a></label>
                    <div class="layui-input-block">
                        <?php foreach (\common\components\statics\Base::$electric_map as $item_k=>$item_v) { ?>
                            <input type="radio" name="electric" value="<?=$item_k?>" title="<?=$item_v?>" <?php if(isset($goods['electric']) && $item_k == $goods['electric']){ echo 'checked'; } ?>>
                        <?php }?>
                    </div>
                </div>
            </div>

            <div class="layui-form-item">
                <div class="layui-input-block">
                    <input type="hidden" name="id" value="<?=$goods['id']?>">
                    <input type="hidden" name="source_method" value="<?=$goods['source_method']?>">
                    <button class="layui-btn" lay-submit="" lay-filter="form" data-form="update_goods">立即提交</button>
                    <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                </div>
            </div>
        </div>

    </div>

</form>

<script id="source_tpl" type="text/html">
    <div class="layui-form-item">
        <div class="layui-inline layui-col-md3">
            <label class="layui-form-label">{{# if(d.is_init==0){ }}采购平台{{# } }}</label>
            <div class="layui-input-block">
                <select lay-verify="required" name="source[platform_type][]">
                    <?php
                    foreach (\common\services\goods\GoodsService::getPurchaseSource() as $k=> $v){
                        ?>
                        <option value="<?=$k?>" {{# if(d.source.platform_type && d.source.platform_type == <?=$k?> ){ }} selected {{#  } }} ><?=$v?></option>
                    <?php }?>
                </select>
            </div>
        </div>
        <div class="layui-inline layui-col-md6">
            <input type="text" name="source[platform_url][]" placeholder="来源URL" value="{{ d.source.platform_url || '' }}"  class="layui-input">
        </div>

        <div class="layui-inline layui-col-md1">
            <input type="text" name="source[price][]" placeholder="价格" value="{{ d.source.price || '' }}"  class="layui-input">
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
            <a href="{{ d.source.platform_url || '' }}" target="_blank"><i class="layui-icon layui-icon-link" style="color: #00a0e9;font-size: 20px; font-weight: bold;line-height: 40px"></i></i></a>
        </div>
    </div>
</script>

<script type="text/javascript">
    var source = <?=empty($source)?"''":$source;?>;
    var attribute = <?=empty($attribute)?"''":$attribute;?>;
    var source_method = <?=$goods['source_method']?>
</script>
<?php
$this->registerJsFile("@adminPageJs/goods/form.js?".time());
?>