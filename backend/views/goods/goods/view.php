<?php

use common\components\statics\Base;
use yii\helpers\Url;
use common\models\Goods;
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
    .layui-form-switch{
        margin-top: 0px;
    }
    #input {position: absolute;top: 0;left: 0;opacity: 0;z-index: -10;}
</style>


<div class="layui-tab layui-tab-brief">
    <ul class="layui-tab-title">
        <li class="layui-this"><a href="<?=Url::to(['goods/view?goods_no='.$goods['goods_no']])?>">商品信息</a></li>
        <li><a href="<?=Url::to(['goods/view-multilingual?goods_no='.$goods['goods_no']])?>">多语言</a></li>
        <li><a href="<?=Url::to(['goods/view-outside-package?goods_no='.$goods['goods_no']])?>">采购信息</a></li>
        <li><a href="<?=Url::to(['goods/view-order?goods_no='.$goods['goods_no']])?>">订单</a></li>
        <li><a href="<?=Url::to(['goods/view-purchase?goods_no='.$goods['goods_no']])?>">采购</a></li>
    </ul>
</div>
<div class="layui-col-md9 layui-col-xs12" style="margin:0 20px 30px 20px;">
    <div class="lay-lists" style="padding:10px;">
        <?php if($goods['goods_type'] == \common\models\Goods::GOODS_TYPE_SINGLE) { ?>
        <div class="layui-inline">
            <a class="layui-btn layui-btn-sm layui-btn-normal" data-type="open" data-url="<?=Url::to(['goods/set-stock'])?>?cgoods_no=<?=$goods['goods_no']?>" data-width="850px" data-height="600px" data-title="库存" data-callback_title="商品列表">库存</a>
        </div>
        <?php }?>
        <div class="layui-inline">
            <a class="layui-btn layui-btn-sm" data-type="open" data-url="<?=Url::to(['goods/claim'])?>?goods_no=<?=$goods['goods_no']?>" data-width="600px" data-height="500px" data-title="库存" data-callback_title="商品列表">认领</a>
        </div>

        <div style="float: right;margin-left: 200px;">
            <?php if(in_array($goods['status'],[\common\models\Goods::GOODS_STATUS_VALID,\common\models\Goods::GOODS_STATUS_WAIT_MATCH])){?>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-sm" data-type="open" data-url="<?=Url::to(['goods/update-price'])?>?goods_no=<?=$goods['goods_no']?>" data-width="1000px" data-height="500px" data-title="编辑价格" data-callback_title="商品列表">编辑价格</a>
            </div>
            <?php }?>
            <?php if(in_array($goods['status'],[\common\models\Goods::GOODS_STATUS_INVALID])){?>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-sm" data-type="operating" data-url="<?=Url::to(['goods/batch-update-status?id='.$goods['id'].'&status='.\common\models\Goods::GOODS_STATUS_VALID])?>" data-title="启用">启用</a>
            </div>
            <?php }?>
            <?php if(in_array($goods['status'],[\common\models\Goods::GOODS_STATUS_VALID,\common\models\Goods::GOODS_STATUS_WAIT_MATCH])){?>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-sm layui-btn-warm" data-type="open" data-url="<?=Url::to(['goods/disable?id='.$goods['id'].'&status='.\common\models\Goods::GOODS_STATUS_INVALID])?>"  data-width="450px" data-height="300px" data-title="禁用">禁用</a>
            </div>
            <?php }?>
            <?php if(in_array($goods['status'],[Goods::GOODS_STATUS_VALID,Goods::GOODS_STATUS_WAIT_MATCH])){?>
                <?php if($goods['stock']==0){?>
                    <div class="layui-inline">
                        <a class="layui-btn layui-btn-sm" data-type="operating" data-url="<?=Url::to(['goods/open-stock?id='.$goods['id']])?>" data-title="恢复销售">恢复销售</a>
                    </div>
                <?php }else{?>
                    <div class="layui-inline">
                        <a class="layui-btn layui-btn-sm close" data-type="open" data-url="<?=Url::to(['goods/close-view?id='.$goods['id']])?>" data-width="450px" data-height="300px" data-title="暂停销售">暂停销售</a>
                    </div>
                <?php }?>
            <?php }?>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-normal layui-btn-sm" data-ignore="ignore" href="<?=Url::to(['goods/update?id='.$goods['id']])?>">编辑</a>
            </div>

            <div class="layui-inline">
                <a class="layui-btn layui-btn-danger layui-btn-sm" data-type="operating" data-title="删除" data-url="<?=Url::to(['goods/delete?id='.$goods['id']])?>">删除</a>
            </div>
        </div>
    </div>
    <form class="layui-form" action="">
<table class="layui-table">
    <tbody>
    <tr>
        <td class="layui-table-th">商品编号</td>
        <td><?=$goods['goods_no']?></td>
        <td class="layui-table-th">SKU</td>
        <td><?=$goods['sku_no']?></td>
        <td class="layui-table-th">语言</td>
        <td><?=\common\services\sys\CountryService::$goods_language[empty($goods['language'])?'en':$goods['language']]?></td>
    </tr>
    <?php if (!empty($warehouse)) {?>
    <tr>
        <td class="layui-table-th">商品id</td>
        <td><?=$goods['source_platform_id']?></td>
        <td class="layui-table-th">仓库</td>
        <td colspan="5"><?=$warehouse?></td>
    </tr>
    <?php }?>
    <tr>
        <td class="layui-table-th">商品分类</td>
        <td colspan="5"><?= \common\models\Category::getCategoryNamesTreeByCategoryId($goods['category_id'],' > ')?></td>
    </tr>
    <tr>
        <td class="layui-table-th">标题</td>
        <td colspan="5"><?=$goods['goods_name']?></td>
    </tr>
    <tr>
        <td class="layui-table-th">短标题</td>
        <td colspan="5"><?=$goods['goods_short_name']?></td>
    </tr>
    <tr>
        <td class="layui-table-th">中文标题</td>
        <td colspan="5"><?=$goods['goods_name_cn']?></td>
    </tr>
    <tr>
        <td class="layui-table-th">中文短标题</td>
        <td colspan="5"><?=$goods['goods_short_name_cn']?></td>
    </tr>
    <tr>
        <td class="layui-table-th">关键字</td>
        <td colspan="5"><?=$goods['goods_keywords']?></td>
    </tr>
    <tr>
        <td class="layui-table-th">价格</td>
        <td><?=$goods['price']?>
            <?=empty($warehouse) ? '' : $goods['currency']?>
            <span style="padding-left: 15px"><input type="checkbox" name="状态" value="{{d.id}}" data-url="<?=Url::to(['goods/lock-price'])?>?goods_no=<?=$goods['goods_no']?>" lay-skin="switch" lay-text="锁定|未锁定" lay-filter="statusSwitch" <?php if(\common\services\goods\GoodsLockService::existLockPrice($goods['goods_no'])){ echo 'checked';} ?> ></span>
        </td>
        <td class="layui-table-th">价格(GBP)</td>
        <td><?=empty($goods['gbp_price'])?0:$goods['gbp_price']?></td>
        <td class="layui-table-th">货品种类</td>
        <td><?=\common\components\statics\Base::$electric_map[$goods['electric']];?> </td>
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
        <td class="layui-table-th">颜色</td>
        <td><?php
            $exist = array_key_exists($goods['colour'],\common\services\goods\GoodsService::$colour_map);
            $colour = $goods['colour'];
            echo $colour;
            if($exist){
                echo ' ('.\common\services\goods\GoodsService::$colour_map[$colour].')';
            }
            ?></td>
        <td class="layui-table-th">规格型号</td>
        <td><?=$goods['specification']?></td>
    </tr>
    <tr>
        <td class="layui-table-th">状态</td>
        <?php if($goods['status']==Goods::GOODS_STATUS_INVALID){?>
        <td><?=(\common\models\Goods::$status_map[$goods['status']])?><p style="color:red;"><?=((empty($reason))?'':('原因：'.$reason))?></p></td>
        <?php }else {?>
        <td><?=\common\models\Goods::$status_map[$goods['status']]?></td>
        <?php }?>
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
            <div class="layui-upload ys-upload-img-multiple" data-number="10">
                <input type="hidden" name="goods_img" class="layui-input" value="<?=htmlentities($goods['goods_img'], ENT_COMPAT);?>">
                <div class="layui-upload-con">
                </div>
            </div>
        </td>
    </tr>
    <tr>
        <td class="layui-table-th">视频</td>
        <td colspan="1">
            <div class="layui-upload ys-upload-video" data-number="10">
                <input type="hidden" name="additional_video" id="video" class="layui-input" value="<?=empty($goods_additional['video'])?'':$goods_additional['video']?>">
                <input type="hidden" id="create_video" value="10">
                <div class="layui-upload-video">
                </div>
            </div>
        </td>
        <td class="layui-table-th">TiKtok视频</td>
        <td colspan="3">
            <div class="layui-upload ys-upload-tk-video" data-number="10">
                <input type="hidden" name="additional_tk_video" id="tk_video" class="layui-input" value="<?=empty($goods_additional['tk_video'])?'':$goods_additional['tk_video']?>">
                <input type="hidden" id="create_tk_video" value="10">
                <div class="layui-upload-tk-video">
                </div>
            </div>
        </td>
    </tr>
    <tr>
        <td class="layui-table-th">简要描述</td>
        <td colspan="5">
            <textarea id="input"></textarea>
            <?php if (!empty($goods['goods_desc'])){?>
                <button type="button" class="layui-btn layui-btn-sm copy_desc"  style="float: right">复制</button>
            <?php }?>
        <?=$goods['goods_desc']?>
        </td>
    </tr>
    <tr>
        <td class="layui-table-th">详细描述</td>
        <td colspan="5">
            <textarea id="input"></textarea>
            <?php if (!empty($goods['goods_content'])){?>
                <button type="button" class="layui-btn layui-btn-sm copy_content"  style="float: right">复制</button>
            <?php }?>
        <?php foreach ($goods_content as $v){?>
        <?php if ($v == ""){?>
        <?php continue;?>
        <?php }?>
        <p><?=$v?></p>
        <?php }?>
        </td>
    </tr>
    </tbody>
</table>

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
        <td class="layui-table-th" style="text-align: center">重量</td>
        <td class="layui-table-th" style="text-align: center">尺寸</td>
    </tr>
    <?php foreach ($goods_child as $child_v){ ?>
        <tr>
            <td><?=$child_v['sku_no']?></td>
            <td><img src="<?=$child_v['goods_img']?>" ></td>
            <td><?=$child_v['colour']?></td>
            <td><?=$child_v['size']?></td>
            <td><?=$child_v['price']?>
                <?php if($child_v['gbp_price'] > 0){ ?>
                    <br/>GBP:<?=$child_v['gbp_price']?>
                <?php }?>
            </td>
            <td>重量：<?=$child_v['weight']?>
                <?php if($child_v['real_weight'] > 0){ ?>
                <br/>实际重量：<?=$child_v['real_weight']?>
                <?php }?>
            </td>
            <td><?=$child_v['package_size']?></td>
        </tr>
    <?php }?>
    </tbody>
</table>
<?php }?>
<?php if (!empty($category_property)) {?>
商品属性
<table class="layui-table" >
    <tbody>
        <?php $i = 2;$j = 1;$count = count($category_property);
        foreach ($category_property as $property_v) {?>
            <?php if ($i % 3 != 0) {?>
                <tr>
            <?php }?>
            <td class="layui-table-th"><?=$property_v['property_name']?></td>
            <?php if ($j == $count && $j % 2 != 0) {?>
                <td colspan="5">
                    <?=$property_v['property_value']?>  <?=!empty($property_v['property_value']) ? $property_v['unit'] : ''?>
                </td>
            <?php }else{?>
                <td>
                    <?=$property_v['property_value']?>  <?=!empty($property_v['property_value']) ? $property_v['unit'] : ''?>
                </td>
            <?php }?>
            <?php if ($i % 3 == 0) {
                $i = 1;?>
                </tr>
            <?php }?>
        <?php $i = ++$i;$j = ++$j ;}?>
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
            <td style="width:580px;word-wrap: break-word;word-break: break-all;">
                <?php if ($v['platform_type'] == Base::PLATFORM_SUPPLIER && $v['supplier_id'] != 0) {?>
                    <span><?=$v['supplier_name']?></span>
                    <span style="float: right" class="lay-lists">
                        <a class="layui-btn layui-btn-normal layui-btn-xs" data-type="open" data-url="<?=Url::to(['supplier/view?id='.$v['supplier_id']])?>" data-width="750px" data-height="310px" data-title="供应商详情">查看详情</a>
                    </span>
                <?php } else if ($v['platform_type'] == Base::PLATFORM_DISTRIBUTOR && $v['supplier_id'] != 0) {?>
                    <span><?=$v['warehouse_name']?></span>
                    <span style="float: right" class="lay-lists">
                        <a class="layui-btn layui-btn-normal layui-btn-xs" data-type="open" data-url="<?=Url::to(['warehouse/view?id='.$v['supplier_id']])?>" data-width="750px" data-height="310px" data-title="供应商详情">查看详情</a>
                    </span>
                <?php } else {?>
                    <a href="<?=$v['platform_url']?>" target="_blank"><?=$v['platform_url']?></a>
                <?php }?>
            </td>
            <td><?=$v['price']?></td>
        </tr>
    <?php }?>
    </tbody>
</table>
</form>
<table class="layui-table">
    <tbody>
    <tr>
    	<td class="layui-table-th" style="text-align: center">操作时间</td>
        <td class="layui-table-th" style="text-align: center">操作类型</td>
        <td class="layui-table-th" style="text-align: center">操作人</td>
        <td class="layui-table-th" style="text-align: center; width:300px">操作说明</td>
    </tr>
    <?php foreach ($per_info as $v){ ?>
        <tr>
        	<td ><?=date('Y-m-d H:i:s',$v['add_time'] )?></td>
            <td ><?=$v['op_name']?></td>
            <td><?=$v['op_user_name']?></td>
  			<td><?=\common\services\sys\SystemOperlogService::getShowLogDesc($v);?></td>
        </tr>
    <?php }?>
    </tbody>
</table>
</div>
<script id="img_tpl" type="text/html">
    <li class="layui-fluid lay-image">
        <div class="layui-upload-list">
            <a href="{{ d.img || '' }}" data-lightbox="pic">
                <img class="layui-upload-img" style="max-width: 200px;height: 100px"  src="{{ d.img || '' }}">
            </a>
        </div>
    </li>
</script>
<script id="video_tpl" type="text/html">
    <li class="layui-fluid lay-image" style="padding: 13px">
        <div class="layui-upload-list">
            <video id="video_d" width="130" height="120" controls>
                <source src="{{ d.video }}" type="video/mp4">
                <source src="{{ d.video }}" type="video/ogg">
                <source src="{{ d.video }}" type="video/webm">
                <object data="{{ d.video }}" width="130" height="120">
                    <embed src="{{ d.video }}" width="162" height="162">
                </object>
            </video>
        </div>
    </li>
</script>
<script id="tk_video_tpl" type="text/html">
    <li class="layui-fluid lay-image" style="padding: 13px">
        <div class="layui-upload-list">
            <video id="tk_video_d" width="130" height="120" controls>
                <source src="{{ d.video }}" type="video/mp4">
                <source src="{{ d.video }}" type="video/ogg">
                <source src="{{ d.video }}" type="video/webm">
                <object data="{{ d.video }}" width="130" height="120">
                    <embed src="{{ d.video }}" width="162" height="162">
                </object>
            </video>
        </div>
    </li>
</script>
<script id="source_tpl" type="text/html">
</script>

<script id="attribute_tpl" type="text/html">

</script>
<?php
$this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/jquery-ui.min.js", ['depends' => 'yii\web\JqueryAsset']);
?>
<script type="text/javascript">
    var source = '';
    var attribute = '';
    var source_method = '';
    var tag_name = '';
    var property = '';
    var content_copy = <?=json_encode($goods['goods_content'])?>;
    var desc_copy = <?=json_encode($goods['goods_desc'])?>;
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.8")?>
<?php
$this->registerJsFile("@adminPageJs/goods/form.js?".time());
?>

