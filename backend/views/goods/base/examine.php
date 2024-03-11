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
    .lay-image{
        float: left;padding: 20px; border: 1px solid #eee;margin: 5px
    }
    .layui-tab-title .layui-this {
        background-color: #fff;
        color: #000;
    }
    .layui-tab-brief > .layui-tab-title .layui-this a{
        color: rgb(0, 150, 136);
    }
    .layui-tab{
        margin-top: 0;
    }
    .layui-table-th{
        background: #FAFAFA;
        color: #666;
        width: 100px;
        text-align: right;
    }
    .el-cascader {
        width: 600px;
    }
</style>
<div class="layui-col-md9 layui-col-xs12" style="margin:10px 20px 30px 20px">
    <form class="layui-form layui-row" id="grab_goods" action="<?=Url::to(['goods-'.$url_platform_name.'/examine?'.$uri])?>">
        <div class="layui-col-md9 layui-col-xs12" style="padding: 0 10px">
            <div class="layui-inline">
                <label class="layui-form-label">审查状态</label>
                <div class="layui-input-block" style="width: 1000px">
                    <?php
                    $type_map = \common\services\goods\GoodsService::$platform_goods_audit_status_map;
                    unset($type_map[0]);
                    foreach ($type_map as $item_k=>$item_v) { ?>
                        <input type="radio" name="audit_status" value="<?=$item_k?>" title="<?=$item_v?>" <?php if(isset($base_goods['audit_status']) && $item_k == $base_goods['audit_status']){ echo 'checked'; } ?>>
                    <?php }?>
                </div>
            </div>

            <div class="layui-form-item" style="margin-top: 10px">
                <div class="layui-input-block">
                    <input type="hidden" name="goods_no" value="<?=$goods['goods_no']?>">
                    <?php if(!empty($prev_goods_no)){ ?>
                    <a href="<?=Url::to(['goods-'.$url_platform_name.'/examine?goods_no='.$prev_goods_no.'&'.$uri])?>" class="layui-btn">上一条</a>
                    <?php }?>
                    <button class="layui-btn" lay-submit="" lay-filter="form" data-form="grab_goods">下一条</button>
                </div>
            </div>
        </div>

<table class="layui-table" id="update_goods">
    <tbody>
    <tr>
        <td class="layui-table-th">商品编号</td>
        <td><?=$goods['goods_no']?></td>
        <td class="layui-table-th">SKU</td>
        <td><?=$goods['sku_no']?></td>
    </tr>
    <tr>
        <td class="layui-table-th">商品分类</td>
        <td colspan="5" style="600px">
                <input type="text" id="category_id" name="category_id" value="<?=$goods['category_id']?>" style="display: none;" />
        </td>
    </tr>
    <tr>
        <td class="layui-table-th">标题</td>
        <td colspan="5"><input type="text" name="goods_name" lay-verify="required" placeholder="请输入标题" value="<?=htmlentities($goods['goods_name'], ENT_COMPAT);?>"  class="layui-input" autocomplete="off"></td>
    </tr>
    <?php if(!empty($goods['goods_short_name'])){ ?>
    <tr>
        <td class="layui-table-th">短标题</td>
        <td colspan="5"><input type="text" name="goods_short_name" lay-verify="required" placeholder="请输入短标题" value="<?=htmlentities($goods['goods_short_name'], ENT_COMPAT);?>"  class="layui-input" autocomplete="off"></td>
    </tr>
    <?php }?>
    <tr>
        <td class="layui-table-th">中文标题</td>
        <td colspan="5"><input type="text" name="goods_name_cn" lay-verify="required" placeholder="请输入短标题" value="<?=htmlentities($goods['goods_name_cn'], ENT_COMPAT);?>"  class="layui-input" autocomplete="off"></td>
    </tr>
    <tr>
        <td class="layui-table-th">中文短标题</td>
        <td colspan="5"><input type="text" name="goods_short_name_cn" value="<?=htmlentities($goods['goods_short_name_cn'], ENT_COMPAT);?>"  class="layui-input" autocomplete="off"></td>
    </tr>
    <tr>
        <td class="layui-table-th">关键字</td>
        <td colspan="5">
            <div style="border:1px solid #eee;">
                <div id="goods_keywords_div" style="padding: 0 5px">
                </div>
                <input type="text" style="width: 850px;border:0px" id="goods_keywords" placeholder="请输入关键词按回车添加" value="" class="layui-input" autocomplete="off">
            </div>
        </td>
    </tr>
    <tr>
        <td class="layui-table-th" style="width: 150px">颜色</td>
        <td>
            <?php
            $exist = array_key_exists($goods['colour'],\common\services\goods\GoodsService::$colour_map);
            $colour = null;
            if($exist){
                $colour = $goods['colour'];
            }
            echo \yii\helpers\Html::dropDownList('colour', $colour,\common\services\goods\GoodsService::getColourOpt() ,['lay-ignore'=>'lay-ignore','lay-verify'=>"required" ,'prompt' => '请选择','class'=>"layui-input search-con ys-select2","style"=>"width: 150px"]);
            ?>
            <?php
            if(!$exist){
                echo $goods['colour'];
            } ?>
        </td>
        <td class="layui-table-th" style="width: 150px">货品种类<a style="font-size: 18px;color:#FFB800;font-weight: bold" class="js-help layui-icon layui-icon-about" href="javascript:;" title="帮助" data-content="●普货主要指常规商品，不含电、不含磁、不含液体、颗粒、粉末，例如衣服、鞋子等;</br>●敏感货主要指液体，粉末，膏状，乳状，凝胶状等化妆品及日化品;化妆品类液体、粉末和膏状产品及绘画颜料、染料粉、口腔清洁剂、墨水等，不接受其他任何类型的液体、所有含酒精的都不送，液体不超过500ml（其它类型的液体要归为不可寄送，其它类型的膏状物质暂归为正常）</br>●特货主要指含电含磁类商品，如手机，电子手表等"></a></td>
        <td>
            <?php foreach (\common\components\statics\Base::$electric_map as $item_k=>$item_v) { ?>
                <input type="radio" name="electric" value="<?=$item_k?>" title="<?=$item_v?>" <?php if(isset($goods['electric']) && $item_k == $goods['electric']){ echo 'checked'; } ?>>
            <?php }?>
        </td>
        <td class="layui-table-th">规格型号</td>
        <td><?=$goods['specification']?></td>
    </tr>
    <tr>
        <td class="layui-table-th">重量(kg)</td>
        <td><?=$goods['weight']?></td>
        <td class="layui-table-th">包装尺寸(cm)</td>
        <td><?=$goods['size']?></td>
        <td class="layui-table-th">实际重量(kg)</td>
        <td><?=$goods['real_weight']>0?$goods['real_weight']:'暂无';?></td>
    </tr>
    <tr>
        <td class="layui-table-th">商品类型</td>
        <td><?=\common\models\Goods::$goods_type_map[$goods['goods_type']]?></td>
        <td class="layui-table-th">价格</td>
        <td><?=$goods['price']?></td>
        <td class="layui-table-th">价格(GBP)</td>
        <td><?=empty($goods['gbp_price'])?0:$goods['gbp_price']?></td>
    </tr>
    <tr>
        <td class="layui-table-th">状态</td>
        <td><?=\common\models\Goods::$status_map[$goods['status']]?></td>
        <td class="layui-table-th">归类</td>
        <td><?php
            $goods_tort_type_map = \common\services\goods\GoodsService::getGoodsTortTypeMap($goods['source_method_sub']);
            echo !empty($goods_tort_type_map[$goods['goods_tort_type']])?$goods_tort_type_map[$goods['goods_tort_type']]:'';?></td>
        <!--<td class="layui-table-th">库存</td>
        <td><?=\common\models\Goods::$stock_map[$goods['stock']]?></td>
        <td class="layui-table-th">品牌</td>
        <td><?=$goods['brand']?></td>-->
    </tr>
    <tr>
        <td class="layui-table-th">商品图片</td>
        <td colspan="5">
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
                <input type="hidden" name="goods_img" class="layui-input" value="<?=htmlentities($goods['goods_img'], ENT_COMPAT);?>">
                <div class="layui-upload-con">
                </div>
            </div>
        </td>
    </tr>
    <tr>
        <td class="layui-table-th">简要描述</td>
        <td colspan="5"><textarea placeholder="请输入商品简要说明" class="layui-textarea" style="height: 150px" name="goods_desc"><?=$goods['goods_desc']?></textarea></td>
    </tr>
    <tr>
        <td class="layui-table-th">详细描述</td>
        <td colspan="5"><textarea placeholder="请输入商品详细说明" class="layui-textarea" style="height: 200px" name="goods_content" id="goods_content"><?=$goods['goods_content']?></textarea></td>
    </tr>
    </tbody>
</table>

<?php if($goods['goods_type'] == \common\models\Goods::GOODS_TYPE_MULTI){?>
    变体信息
    <table class="layui-table" style="width: 600px">
        <tbody>
        <tr>
            <td class="layui-table-th" style="text-align: center" >sku</td>
            <td class="layui-table-th" style="text-align: center">图片</td>
            <td class="layui-table-th" style="text-align: center" >颜色</td>
            <td class="layui-table-th" style="text-align: center">尺寸</td>
        </tr>
        <?php foreach ($goods_child as $child_v){ ?>
            <tr>
                <td><?=$child_v['sku_no']?></td>
                <td><img src="<?=$child_v['goods_img']?>" ></td>
                <td><?=$child_v['colour']?></td>
                <td><?=$child_v['size']?></td>
            </tr>
        <?php }?>
        </tbody>
    </table>
<?php }?>

来源平台
<table class="layui-table">
    <tbody>
    <tr>
        <td class="layui-table-th" style="text-align: center">来源平台</td>
        <td class="layui-table-th" style="text-align: center">链接</td>
        <td class="layui-table-th" style="text-align: center">价格</td>
    </tr>
    <?php foreach ($source as $v){ ?>
        <tr>
            <td><?=\common\services\goods\GoodsService::getGoodsSource($goods['source_method'])[$v['platform_type']]?></td>
            <td style="width:580px;word-wrap: break-word;word-break: break-all;"><a href="<?=$v['platform_url']?>" target="_blank"><?=$v['platform_url']?></a></td>
            <td><?=$v['price']?></td>
        </tr>
    <?php }?>
    </tbody>
</table>
    </form>
</div>

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
<script id="source_tpl" type="text/html">
</script>

<script id="attribute_tpl" type="text/html">

</script>
<?php \backend\assets\CategoryJsAsset::register($this);?>
<?php
$this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/jquery-ui.min.js", ['depends' => 'yii\web\JqueryAsset']);
?>
<script type="text/javascript">
    var source = '';
    var attribute = '';
    var source_method = '';
    var tag_name = '<?=$goods['goods_keywords']?>';
    var property = '';
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.7")?>
<?php
$this->registerJsFile("@adminPageJs/goods/form.js?".time());
?>



