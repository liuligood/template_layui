<?php

use common\models\Supplier;
use common\services\warehousing\WarehouseService;
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
<form class="layui-form layui-row" id="update_goods" action="<?=Url::to(['goods/update-price'])?>">
    <div class="layui-col-md12 layui-col-xs12" style="padding: 10px">

            <div id="source" class="layui-field-box">
            </div>


            <div class="layui-form-item">
                <div class="layui-inline">
                    <label class="layui-form-label">价格</label>
                    <div class="layui-input-block">
                        <input type="text" name="price" lay-verify="required|number" placeholder="请输入价格"  value="<?=$goods['price']?>" class="layui-input" autocomplete="off">
                    </div>
                </div>

                <?php if($has_gbp_price){ ?>
                <div class="layui-inline">
                    <label class="layui-form-label">价格(GBP)</label>
                    <div class="layui-input-block">
                        <input type="text" name="gbp_price" lay-verify="number" placeholder="请输入英镑价格"  value="<?=empty($goods['gbp_price'])?0:$goods['gbp_price']?>" class="layui-input" autocomplete="off">
                    </div>
                </div>
                <?php }?>

                <?php if($goods['goods_type'] == \common\models\Goods::GOODS_TYPE_SINGLE){?>
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
                    <div class="layui-inline" style="width: 60px">
                        <label><?php
                            if(!$exist){
                                echo $goods['colour'];
                            } ?></label>
                    </div>
                </div>
                <?php }?>
            </div>

            <?php if(empty($goods['source_method']) || $goods['source_method'] == \common\services\goods\GoodsService::SOURCE_METHOD_OWN){?>
            <div class="layui-form-item">
                <div class="layui-inline">
                    <label class="layui-form-label">重量(kg)</label>
                    <div class="layui-input-block">
                        <input type="text" name="weight" lay-verify="required|number" placeholder="请输入重量" value="<?=$goods['weight']?>" class="layui-input" autocomplete="off">
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

                <?php if($goods['real_weight'] > 0){?>
                <div class="layui-inline">
                    <label class="layui-form-label">实际重量(kg)</label>
                    <label class="layui-form-label"><?=$goods['real_weight']?></label>
                </div>
                <?php }?>
            </div>

            <div class="layui-form-item">
                <div class="layui-inline">
                    <label class="layui-form-label">规格型号</label>
                    <div class="layui-input-block">
                        <input type="text" name="specification" placeholder="请输入规格型号" value="<?=$goods['specification']?>" class="layui-input" autocomplete="off">
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
            <?php } ?>

            <?php if($goods['goods_type'] == \common\models\Goods::GOODS_TYPE_MULTI){?>
                变体信息
                <table class="layui-table" >
                    <tbody>
                    <tr>
                        <td class="layui-table-th" style="text-align: center">sku</td>
                        <td class="layui-table-th" style="text-align: center">图片</td>
                        <td class="layui-table-th" style="text-align: center">颜色</td>
                        <td class="layui-table-th" style="text-align: center">规格</td>
                        <td class="layui-table-th" style="text-align: center">价格</td>
                        <td class="layui-table-th" style="text-align: center">包装信息<a id="batch_set_pro"><i class="layui-icon layui-icon-set"></i></a>
                        </td>
                    </tr>
                    <?php foreach ($goods_child as $child_v){ ?>
                        <tr>
                            <td><input type="hidden" name="property[id][]" value="<?=$child_v['id']?>" class="layui-input"><?=$child_v['sku_no']?></td>
                            <td><img src="<?=$child_v['goods_img']?>" ></td>
                            <td><?=$child_v['colour']?></td>
                            <td><?=$child_v['size']?></td>
                            <td>
                                <input type="text" name="property[price][]" style="width: 90px" lay-verify="required|number" placeholder="价格"  value="<?=$child_v['price']?>" class="layui-input" autocomplete="off">
                                <?php if($has_gbp_price){ ?>
                                    <hr class="layui-border-cyan">
                                    GBP:<input type="text" name="property[gbp_price][]" style="width: 90px" lay-verify="required|number" placeholder="GBP价格"  value="<?=$child_v['gbp_price']?>" class="layui-input" autocomplete="off">
                                <?php }?>
                            </td>
                            <td>
                                <div class="layui-inline">
                                    <label style="padding-right: 5px;">重量</label>
                                    <div class="layui-inline">
                                        <input type="text" name="property[weight][]" style="width: 90px" lay-verify="required|number" placeholder="重量" value="<?=$child_v['weight']?>" class="layui-input" autocomplete="off">  <?php if($child_v['real_weight'] > 0){ ?> 实际重量:<?=$child_v['real_weight']?>kg <?php }?>
                                    </div>
                                </div>
                                <hr class="layui-border-cyan">
                                <div class="layui-inline">
                                    <label style="padding-right: 5px;">尺寸</label>
                                    <div class="layui-inline" style="width: 70px">
                                        <input type="text" name="property[size_l][]" lay-verify="number" placeholder="长"  value="<?=empty($child_v['size_l'])?0:$child_v['size_l']?>" class="layui-input" autocomplete="off">
                                    </div>
                                    <div class="layui-inline" style="width: 70px">
                                        <input type="text" name="property[size_w][]" lay-verify="number" placeholder="宽"  value="<?=empty($child_v['size_w'])?0:$child_v['size_w']?>" class="layui-input" autocomplete="off">
                                    </div>
                                    <div class="layui-inline" style="width: 70px">
                                        <input type="text" name="property[size_h][]" lay-verify="number" placeholder="高"  value="<?=empty($child_v['size_h'])?0:$child_v['size_h']?>" class="layui-input" autocomplete="off">
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php }?>
                    </tbody>
                </table>
            <?php }?>

            <div class="layui-form-item" style="padding-top: 10px">
                <div class="layui-input-block">
                    <?php if(in_array($goods['status'],[\common\models\Goods::GOODS_STATUS_WAIT_MATCH])) {?>
                        <input type="hidden" name="status" value="<?=\common\models\Goods::GOODS_STATUS_VALID?>">
                    <?php }?>
                    <input type="hidden" id="goods_id" name="id" value="<?=$goods['id']?>">
                    <input type="hidden" name="goods_no" value="<?=$goods['goods_no']?>">
                    <input type="hidden" name="source_method" value="<?=$goods['source_method']?>">
                    <button class="layui-btn" lay-submit="" lay-filter="form" data-form="update_goods">立即提交</button>
                    <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                </div>
            </div>
            </div>
</form>

<script id="tag_tpl" type="text/html">
</script>

<script id="img_tpl" type="text/html">
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
</script>
<?php
$this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/jquery-ui.min.js", ['depends' => 'yii\web\JqueryAsset']);
?>
<script type="text/javascript">
    var source = <?=empty($source)?"''":$source;?>;
    var attribute = '';
    var source_method = <?=$goods['source_method']?>;
    var tag_name = '';
    var property = '';

</script>
<?php
$this->registerJsFile("@adminPageJs/goods/form.js?".time());
?>



