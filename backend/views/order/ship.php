<?php
/**
 * @var $this \yii\web\View;
 */
use yii\helpers\Url;
use yii\helpers\Html;
use common\models\RealOrder;
?>
<style>
    html {
        background: #fff;
    }
    .layui-form-item{
        margin-bottom: 0px;
    }
</style>
    <form class="layui-form layui-row" id="update-order" action="<?=Url::to(['order/ship'])?>">

    <div class="layui-col-md9 layui-col-xs12" style="margin-left: 10px">

        <div style="padding: 10px; background-color: #F2F2F2; margin-top: 20px;">
        <div class="layui-row layui-col-space15">
            <div class="layui-col-md12">
                <div class="layui-card">
                    <div class="layui-card-header">基本信息</div>
                    <div class="layui-card-body">

                        <div class="layui-field-box">

                            <div class="layui-form-item">
                                <div class="layui-inline">
                                    <label class="layui-form-label">来源平台</label>
                                    <label class="layui-form-label" style="width: 140px;text-align: left"><?=\common\components\statics\Base::$order_source_maps[$model['source']]?></label>
                                </div>

                                <div class="layui-inline">
                                    <label class="layui-form-label">店铺</label>
                                    <label class="layui-form-label" style="width: 140px;text-align: left"><?=\common\services\ShopService::getShopMap()[$model['shop_id']]?></label>
                                </div>
                            </div>

                            <div class="layui-form-item">
                                <div class="layui-inline">
                                    <label class="layui-form-label">销售单号</label>
                                    <label class="layui-form-label" style="width: 140px;text-align: left"><?=$model['relation_no']?></label>
                                </div>
                                <div class="layui-inline">
                                    <label class="layui-form-label">下单时间</label>
                                    <label class="layui-form-label" style="width: 140px;text-align: left"><?=$model['date']?></label>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

        <div style="padding: 10px; background-color: #F2F2F2; margin-top: 10px;">
            <div class="layui-row layui-col-space15">
                <div class="layui-col-md12">
                    <div class="layui-card">
                        <div class="layui-card-header">用户信息</div>
                        <div class="layui-card-body">

                            <div class="layui-field-box">

                                <div class="layui-form-item">
                                    <div class="layui-inline">
                                        <label class="layui-form-label">买家名称</label>
                                        <label class="layui-form-label" style="width: 140px;text-align: left"><?=$model['buyer_name']?></label>
                                    </div>

                                    <div class="layui-inline">
                                        <label class="layui-form-label">买家电话</label>
                                        <label class="layui-form-label" style="width: 140px;text-align: left"><?=$model['buyer_phone']?></label>
                                    </div>

                                    <div class="layui-inline">
                                        <label class="layui-form-label">客户编号</label>
                                        <label class="layui-form-label" style="width: 140px;text-align: left"><?=$model['user_no']?></label>
                                    </div>
                                </div>

                                <div class="layui-form-item">
                                    <div class="layui-inline">
                                        <label class="layui-form-label">国家</label>
                                        <label class="layui-form-label" style="width: 120px;text-align: left"><?=$model['country']?></label>
                                    </div>

                                    <div class="layui-inline">
                                        <label class="layui-form-label">城市</label>
                                        <label class="layui-form-label" style="width: 120px;text-align: left"><?=$model['city']?></label>
                                    </div>

                                    <div class="layui-inline">
                                        <label class="layui-form-label">区</label>
                                        <label class="layui-form-label" style="width: 120px;text-align: left"><?=$model['area']?></label>
                                    </div>
                                </div>

                                <div class="layui-form-item">
                                    <div class="layui-inline layui-col-md6">
                                        <label class="layui-form-label">地址</label>
                                        <label class="layui-form-label" style="width: 300px;text-align: left"><?=$model['address']?></label>
                                    </div>

                                    <div class="layui-inline">
                                        <label class="layui-form-label">邮编</label>
                                        <label class="layui-form-label" style="width: 120px;text-align: left"><?=$model['postcode']?></label>
                                    </div>

                                </div>

                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div style="padding: 10px; background-color: #F2F2F2; margin-top: 10px;">
            <div class="layui-row layui-col-space15">
                <div class="layui-col-md12">
                    <div class="layui-card">
                        <div class="layui-card-header">商品信息</div>
                        <div class="layui-card-body">

                            <div class="layui-field-box">

                                <div>
                                    <?php foreach ($order_goods as $goods_v){ ?>
                                    <blockquote class="layui-elem-quote layui-quote-nm">
                                        <div class="layui-fluid layui-form-item" style="margin-bottom:5px">
                                            <div class="layui-inline">
                                                <label>商品名称</label>
                                                <div>
                                                    <?=$goods_v['goods_name']?>
                                                </div>
                                            </div>
                                            <div class="layui-inline">
                                                <label>规格</label>
                                                <div>
                                                    <?=$goods_v['goods_specification']?>
                                                </div>
                                            </div>
                                            <div class="layui-inline">
                                                <label>商品图片</label>
                                                <div>
                                                    <a href="<?=empty($goods_v['goods_pic'])?'':$goods_v['goods_pic']?>" data-lightbox="pic">
                                                        <img class="layui-circle pic" src=<?=empty($goods_v['goods_pic'])?'':$goods_v['goods_pic']?> height="30"/>
                                                    </a>
                                                </div>
                                            </div>
                                            <div class="layui-inline" style="width: 100px">
                                                <label>数量</label>
                                                <div>
                                                    <?=$goods_v['goods_num']?>
                                                </div>
                                            </div>
                                            <div class="layui-inline" style="width: 100px;">
                                                <label>售价</label>
                                                <div>
                                                    <?=$goods_v['goods_income_price']?>
                                                </div>
                                            </div>
                                        </div>
                                    </blockquote>
                                    <?php }?>

                                </div>

                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div style="padding: 10px; background-color: #F2F2F2; margin-top: 10px;">
            <div class="layui-row layui-col-space15">
                <div class="layui-col-md12">
                    <div class="layui-card">
                        <div class="layui-card-header">发货信息</div>
                        <div class="layui-card-body">

                            <div class="layui-field-box">
                                <div class="layui-form-item">
                                    <div class="layui-inline layui-col-md6">
                                        <label class="layui-form-label">发货状态</label>
                                        <div class="layui-input-block">
                                            <input type="radio" name="delivery_status" value="0" title="未发货" <?php if($model['delivery_status'] == 0){?>checked=""<?php } ?>>
                                            <input type="radio" name="delivery_status" value="10" title="已发货" <?php if($model['delivery_status'] == 10){?>checked=""<?php } ?>>
                                            <?php if($model['source_method'] == \common\services\goods\GoodsService::SOURCE_METHOD_AMAZON){?>
                                            <input type="radio" name="delivery_status" value="20" title="无跟踪信息发" <?php if($model['delivery_status'] == 20){?>checked=""<?php } ?>>
                                            <?php }?>
                                        </div>
                                    </div>

                                    <div class="layui-inline layui-col-md4">
                                        <label class="layui-form-label">售后状态</label>
                                        <div class="layui-input-block">
                                            <?= Html::dropDownList('after_sale_status', $model['after_sale_status'], \common\models\Order::$after_sale_status_map,
                                                ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:120px']) ?>
                                        </div>
                                    </div>

                                </div>

                                <div class="layui-form-item">
                                    <div class="layui-inline layui-col-md4">
                                        <label class="layui-form-label">物流渠道</label>
                                        <?php if($model['source_method'] == \common\services\goods\GoodsService::SOURCE_METHOD_AMAZON){?>
                                    <div class="layui-input-block"><?= Html::dropDownList('logistics_channels_id', $model['logistics_channels_id'], \common\models\Order::$logistics_channels_map,
                                            ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:120px']) ?>
                                    </div>
                                        <?php }else{ ?>
                                            <label class="layui-form-label" style="width: 180px;text-align: left"><?= \common\services\transport\TransportService::getShippingMethodName($model['logistics_channels_id'])?></label>
                                        <?php }?>
                                    </div>

                                    <div class="layui-inline layui-col-md4">
                                        <label class="layui-form-label">物流订单号</label>

<?php if($model['source_method'] == \common\services\goods\GoodsService::SOURCE_METHOD_AMAZON){?>
    <div class="layui-input-block"><input type="text" name="track_no" value="<?=$model['track_no']?>" placeholder="请输入物流订单号" class="layui-input"></div>
<?php }else{ ?>
    <label class="layui-form-label" style="width: 140px;text-align: left"><?=$model['track_no']?></label>
<?php }?>
                                    </div>
                                </div>

                                <div class="layui-form-item">
                                    <label class="layui-form-label">备注</label>
                                    <div class="layui-input-block">
                                        <textarea class="layui-textarea" style="height: 100px" name="remarks"><?=$model['remarks']?></textarea>
                                    </div>
                                </div>

                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="layui-form-item" style="margin-top: 20px">
            <div class="layui-input-block">
                <input type="hidden" name="id" value="<?=$model['id']?>">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="update-order">立即提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </div>
</form>
<?=$this->registerJsFile("@adminPageJs/base/form.js?v=0.0.1.5")?>
<?=$this->registerJsFile("@adminPageJs/order/form.js?v=0.0.1.7")?>
<?php
$this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
?>