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
<form class="layui-form layui-row" id="update_goods" action="<?=Url::to(['purchase-order/associate-create'])?>">
    <div class="layui-col-md12 layui-col-xs12" style="padding: 0 10px">

        <div class="layui-field-box">
            <div id="source" class="layui-field-box">
            </div>

            <div class="layui-form-item">
                <div class="layui-inline">
                    <label class="layui-form-label" style="120px" >采购订单号</label>
                    <div class="layui-input-block" style="width: 230px">
                        <input type="text" name="relation_no" lay-verify="required" placeholder="请输入阿里巴巴订单号"  value="" class="layui-input" autocomplete="off">
                    </div>
                </div>
                <div class="layui-inline">
                    <label class="layui-form-label">采购数量</label>
                    <div class="layui-input-block" style="width: 70px">
                        <input type="text" name="goods_num" lay-verify="required|number" placeholder="请输入采购数量"  value="1" class="layui-input" autocomplete="off">
                    </div>
                </div>
            </div>

            <div class="layui-form-item">
                <div class="layui-inline">
                    <label class="layui-form-label">重量(kg)</label>
                    <div class="layui-input-block">
                        <input type="text" name="weight" lay-verify="required|number" placeholder="请输入重量" value="<?php $weight = $goods['real_weight'] == 0?$goods['weight']:$goods['real_weight']; echo $weight>0?$weight:'' ?>" class="layui-input" autocomplete="off">
                    </div>
                </div>
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
            </div>

            <div class="layui-form-item">
                <div class="layui-inline">
                    <label class="layui-form-label">规格型号</label>
                    <div class="layui-input-block">
                        <input type="text" name="specification" placeholder="请输入规格型号" value="<?=$goods['specification']?>" class="layui-input" autocomplete="off">
                    </div>
                </div>
                <?php if($goods['goods_type'] == \common\models\Goods::GOODS_TYPE_SINGLE) { ?>
                    <div class="layui-inline">
                        <label class="layui-form-label">颜色</label>
                        <div class="layui-input-block">
                            <?php
                            $exist = array_key_exists($goods['colour'],\common\services\goods\GoodsService::$colour_map);
                            $colour = null;
                            if($exist) {
                                $colour = $goods['colour'];
                            }
                            echo \yii\helpers\Html::dropDownList('colour', $colour,\common\services\goods\GoodsService::getColourOpt() ,['lay-ignore'=>'lay-ignore','lay-verify'=>"required" ,'prompt' => '请选择','class'=>"layui-input search-con ys-select2"]);
                            ?>
                        </div>
                    </div>
                <?php } ?>
            </div>

            <div class="layui-form-item">
                <div class="layui-inline">
                    <label class="layui-form-label">货品种类<a style="font-size: 18px;color:#FFB800;font-weight: bold" class="js-help layui-icon layui-icon-about" href="javascript:;" title="帮助" data-content="●普货主要指常规商品，不含电、不含磁、不含液体、颗粒、粉末，例如衣服、鞋子等;</br>●敏感货主要指液体，粉末，膏状，乳状，凝胶状等化妆品及日化品;化妆品类液体、粉末和膏状产品及绘画颜料、染料粉、口腔清洁剂、墨水等，不接受其他任何类型的液体、所有含酒精的都不送，液体不超过500ml（其它类型的液体要归为不可寄送，其它类型的膏状物质暂归为正常）</br>●特货主要指含电含磁类商品，如手机，电子手表等"></a></label>
                    <div class="layui-input-block">
                        <?php foreach (\common\components\statics\Base::$electric_map as $item_k=>$item_v) { ?>
                            <input type="radio" name="electric" value="<?=$item_k?>" title="<?=$item_v?>" <?php if(isset($goods['electric']) && $item_k == $goods['electric']){ echo 'checked'; } ?>>
                        <?php }?>
                    </div>
                </div>
            </div>

            <div class="layui-form-item" style="margin-top: 30px">
                <div class="layui-input-block">
                    <input type="hidden" name="proposal_id" value="<?=$proposal_id?>">
                    <button class="layui-btn" lay-submit="" lay-filter="form" data-form="update_goods">立即提交</button>
                </div>
            </div>
        </div>

    </div>

</form>

<?php
$this->registerJsFile("@adminPageJs/base/form.js?".time());
?>