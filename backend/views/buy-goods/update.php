<?php
/**
 * @var $this \yii\web\View;
 */
use yii\helpers\Url;
use yii\helpers\Html;
use common\models\BuyGoods;
?>
<style>
    .layui-form-item{
        margin-bottom: 0px;
    }
</style>
<form class="layui-form layui-row" id="update-order" action="<?=Url::to(['buy-goods/update'])?>">

    <div class="layui-col-md9 layui-col-xs12" style="margin-left: 10px">

        <div style="padding: 10px; background-color: #F2F2F2; margin-top: 10px;">
        <div class="layui-row layui-col-space15">
            <div class="layui-col-md12">
                <div class="layui-card">
                    <div class="layui-card-header">基本信息</div>
                    <div class="layui-card-body">

                        <div class="layui-field-box">

                            <div class="layui-form-item">
                                <div class="layui-inline">
                                    <label class="layui-form-label">订单号</label>
                                    <label class="layui-form-label" style="width: 140px;text-align: left"><?=$model['order_id']?></label>
                                </div>

                                <div class="layui-inline">
                                    <label class="layui-form-label">平台</label>
                                    <label class="layui-form-label" style="width: 140px;text-align: left">
                                        <?= empty($model['platform_type'])?'':\common\components\statics\Base::$buy_platform_maps[$model['platform_type']]; ?>
                                    </label>
                                </div>

                                <div class="layui-inline">
                                    <label class="layui-form-label">添加时间</label>
                                    <label class="layui-form-label" style="width: 140px;text-align: left"><?= date('Y-m-d H:i:s',$model['add_time'])?></label>
                                </div>
                            </div>

                            <div class="layui-form-item">
                                <div class="layui-inline">
                                    <label class="layui-form-label">销售单号</label>
                                    <label class="layui-form-label" style="width: 140px;text-align: left"><?=$order['relation_no']?></label>
                                </div>

                                <div class="layui-inline">
                                    <label class="layui-form-label">销售店铺</label>
                                    <label class="layui-form-label" style="width: 140px;text-align: left">
                                        <?= empty($order['shop_id'])?'':\common\services\ShopService::getShopMap()[$order['shop_id']]; ?>
                                    </label>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

        <div style="padding: 10px; background-color: #F2F2F2;">
            <div class="layui-row layui-col-space15">
                <div class="layui-col-md12">
                    <div class="layui-card">
                        <div class="layui-card-header">用户信息</div>
                        <div class="layui-card-body">

                            <div class="layui-field-box">

                                <div class="layui-form-item">
                                    <div class="layui-inline">
                                        <label class="layui-form-label">公司名称</label>
                                        <label class="layui-form-label" style="width: 120px;text-align: left"><?=$order['company_name']?></label>
                                    </div>
                                </div>

                                <div class="layui-form-item">
                                    <div class="layui-inline">
                                        <label class="layui-form-label">买家名称</label>
                                        <label class="layui-form-label" style="width: 120px;text-align: left"><?=$order['buyer_name']?></label>
                                    </div>

                                    <div class="layui-inline">
                                        <label class="layui-form-label">买家电话</label>
                                        <label class="layui-form-label" style="width: 120px;text-align: left"><?=$order['buyer_phone']?></label>
                                    </div>

                                    <div class="layui-inline">
                                        <label class="layui-form-label">邮编</label>
                                        <label class="layui-form-label" style="width: 120px;text-align: left"><?=$order['postcode']?></label>
                                    </div>
                                </div>

                                <div class="layui-form-item">
                                    <div class="layui-inline">
                                        <label class="layui-form-label">国家</label>
                                        <label class="layui-form-label" style="width: 120px;text-align: left"><?=$order['country']?></label>
                                    </div>

                                    <div class="layui-inline">
                                        <label class="layui-form-label">城市</label>
                                        <label class="layui-form-label" style="width: 120px;text-align: left"><?=$order['city']?></label>
                                    </div>

                                    <div class="layui-inline">
                                        <label class="layui-form-label">区</label>
                                        <label class="layui-form-label" style="width: 120px;text-align: left"><?=$order['area']?></label>
                                    </div>
                                </div>

                                <div class="layui-form-item">
                                    <div class="layui-inline layui-col-md6">
                                        <label class="layui-form-label">地址</label>
                                        <label class="layui-form-label" style="width: 300px;text-align: left"><?=$order['address']?></label>
                                    </div>

                                </div>

                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div style="padding: 10px; background-color: #F2F2F2;">
            <div class="layui-row layui-col-space15">
                <div class="layui-col-md12">
                    <div class="layui-card">
                        <div class="layui-card-header">商品信息</div>
                        <div class="layui-card-body">

                            <div class="layui-field-box">
                                <div class="layui-form-item">
                                    <div class="layui-inline">
                                        <label class="layui-form-label">商品ASIN</label>
                                        <label class="layui-form-label" style="width: 120px;text-align: left"><?=$model['asin']?></label>
                                    </div>

                                    <div class="layui-inline">
                                        <label class="layui-form-label">商品图片</label>
                                        <label class="layui-form-label" style="width: 120px;text-align: left">
                                            <a href="<?=empty($model['buy_goods_pic'])?'':$model['buy_goods_pic']?>" data-lightbox="pic">
                                            <img class="layui-circle pic" src=<?=empty($model['buy_goods_pic'])?'':$model['buy_goods_pic']?> height="30"/>
                                            </a>
                                        </label>
                                    </div>

                                    <div class="layui-inline">
                                        <label class="layui-form-label">商品数量</label>
                                        <label class="layui-form-label" style="width: 120px;text-align: left"><?=$model['buy_goods_num']?></label>
                                    </div>
                                </div>

                                <div class="layui-form-item">
                                    <div class="layui-inline">
                                        <label class="layui-form-label">买货链接</label>
                                        <label class="layui-form-label" style="width: 400px;text-align: left"><?=$model['buy_goods_url']?></label>
                                    </div>
                                </div>

                                <div class="layui-form-item">
                                    <div class="layui-inline">
                                        <label class="layui-form-label">商品价格</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="buy_goods_price" value="<?=$model['buy_goods_price']?>" placeholder="请输入商品价格" class="layui-input">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div style="padding: 10px; background-color: #F2F2F2;">
            <div class="layui-row layui-col-space15">
                <div class="layui-col-md12">
                    <div class="layui-card">
                        <div class="layui-card-header">物流信息</div>
                        <div class="layui-card-body">

                            <div class="layui-field-box">

                                <div class="layui-form-item">

                                    <div class="layui-inline">
                                        <label class="layui-form-label">状态</label>
                                        <?php if($model['buy_goods_status'] < BuyGoods::BUY_GOODS_STATUS_BUY){ ?>
                                        <div class="layui-input-block">
                                            <?= Html::dropDownList('buy_goods_status', $model['buy_goods_status'], BuyGoods::$buy_goods_unpay_status_map,
                                                ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:120px']) ?>
                                        </div>
                                        <?php } else{ ?>
                                            <label class="layui-form-label" style="width: 120px;text-align: left"><?=BuyGoods::$buy_goods_status_map[$model['buy_goods_status']]?></label>
                                        <?php } ?>
                                    </div>

                                    <!--<div class="layui-inline layui-col-md4">
                                        <label class="layui-form-label">售后状态</label>
                                        <div class="layui-input-block">
                                            <?= Html::dropDownList('after_sale_status', $model['after_sale_status'], BuyGoods::$after_sale_status_map,
                                        ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:120px']) ?>
                                        </div>
                                    </div>-->
                                </div>

                                <div class="layui-form-item">
                                    <div class="layui-inline layui-col-md4">
                                        <label class="layui-form-label">亚马逊订单号</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="buy_relation_no" value="<?=$model['buy_relation_no']?>" placeholder="请输入亚马逊订单号" class="layui-input">
                                        </div>
                                    </div>

                                    <div class="layui-inline layui-col-md4">
                                        <label class="layui-form-label">刷单买家号机器编号</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="swipe_buyer_id" value="<?=$model['swipe_buyer_id']?>" placeholder="请输入刷单买家号机器编号" class="layui-input">
                                        </div>
                                    </div>
                                </div>

                                <?php if(in_array($model['buy_goods_status'],[BuyGoods::BUY_GOODS_STATUS_DELIVERY,BuyGoods::BUY_GOODS_STATUS_FINISH])){ ?>
                                <div class="layui-form-item">
                                    <div class="layui-inline layui-col-md4">
                                        <label class="layui-form-label">亚马逊物流单号</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="logistics_id" lay-verify="required" value="<?=$model['logistics_id']?>" placeholder="请输入亚马逊物流订单号" class="layui-input">
                                        </div>
                                    </div>

                                    <div class="layui-inline layui-col-md4">
                                        <label class="layui-form-label">预计到货时间</label>
                                        <div class="layui-input-block">
                                            <input type="text" id="arrival_time" lay-verify="required" value="<?=$model['arrival_time']?>" name="arrival_time" placeholder="yyyy-MM-dd" class="layui-input ys-date">
                                        </div>
                                    </div>
                                </div>
                                <?php } ?>

                                <?php if(in_array($model['buy_goods_status'],[BuyGoods::BUY_GOODS_STATUS_FINISH])){ ?>
                                <div class="layui-form-item">
                                    <div class="layui-inline layui-col-md4">
                                        <label class="layui-form-label">物流渠道</label>
                                        <div class="layui-input-block">
                                            <?= Html::dropDownList('logistics_channels_id', $model['logistics_channels_id'], \common\models\Order::$logistics_channels_map,
                                                ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
                                        </div>
                                    </div>

                                    <div class="layui-inline layui-col-md4">
                                        <label class="layui-form-label">物流订单号</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="track_no" lay-verify="required" value="<?=$model['track_no']?>" placeholder="请输入物流订单号" class="layui-input">
                                        </div>
                                    </div>
                                </div>
                                <?php } ?>

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
<script type="text/javascript">
</script>
<?=$this->registerJsFile("@adminPageJs/base/form.js?v=0.0.1.5")?>
<?php
$this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
?>
