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
        </style>
<form class="layui-form layui-row" id="add" action="<?=Url::to([$model->isNewRecord?'real-order/create':'real-order/update'])?>">

    <div class="layui-col-md9 layui-col-xs12" style="padding-left: 20px; padding-top: 15px">

        <div class="layui-form-item">
            <div class="layui-inline">
                <label class="layui-form-label">订单号</label>
                <div class="layui-input-block">
                    <input type="text" name="order_id" lay-verify="required" placeholder="请输入订单号" value="<?=$model['order_id']?>" class="layui-input">
                </div>
            </div>
            <div class="layui-inline">
                <label class="layui-form-label">日期</label>
                <div class="layui-input-inline">
                    <input type="text" name="date" id="date" lay-verify="date" placeholder="yyyy-MM-dd" autocomplete="off"  value="<?=$model['date']?>" class="layui-input ys-date">
                </div>
            </div>

            <div class="layui-inline">
                <label class="layui-form-label">店铺</label>
                <div class="layui-input-inline">
                    <?= Html::dropDownList('shop_id', $model['shop_id'], $shop,
                        ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
                </div>
            </div>

            <!--<div class="layui-inline">
                <label class="layui-form-label">状态</label>
                <div class="layui-input-block">
                    <input type="checkbox" name="status" value="1" lay-skin="switch" lay-text="正常|禁用" lay-filter="statusSwitch" <?php if($model['status'] == 1){?>checked=""<?php } ?>}}>
                </div>
            </div>-->
        </div>

        <div style="padding: 10px; background-color: #F2F2F2;">
            <div class="layui-row layui-col-space15">
            <div class="layui-col-md12">
                <div class="layui-card">
                    <div class="layui-card-header">商品信息</div>
                    <div class="layui-card-body">

                        <div class="layui-field-box">

                            <?php if(!$model->isNewRecord){ ?>
                            <div class="layui-form-item">
                                <label class="layui-form-label">亚马逊买货链接</label>
                                <div class="layui-input-block">
                                    <input type="text" disabled name="amazon_buy_url" lay-verify="url" placeholder="请输入亚马逊买货链接"  value="<?=$model['amazon_buy_url']?>" class="layui-input">
                                </div>
                            </div>
                            <?php } ?>

                            <div class="layui-form-item">
                                <div class="layui-inline">
                                    <label class="layui-form-label">产品名称</label>
                                    <div class="layui-input-block">
                                        <input type="text" name="goods_name" placeholder="请输入产品名称" value="<?=$model['goods_name']?>" class="layui-input">
                                    </div>
                                </div>
                            </div>

                            <div class="layui-form-item">
                                <div class="layui-inline">
                                    <label class="layui-form-label">产品ASIN</label>
                                    <div class="layui-input-block">
                                        <input type="text" name="asin" lay-verify="required" placeholder="请输入产品ASIN" value="<?=$model['asin']?>" class="layui-input">
                                    </div>
                                </div>

                                <div class="layui-inline">
                                    <label class="layui-form-label">规格型号</label>
                                    <div class="layui-input-block">
                                        <input type="text" name="specification" placeholder="请输入规格型号" value="<?=$model['specification']?>" class="layui-input">
                                    </div>
                                </div>
                            </div>

                            <div class="layui-form-item">
                                <label class="layui-form-label">产品图片</label>
                                <div class="layui-input-block">
                                    <input type="text" name="image" lay-verify="url" value="<?=$model['image']?>" placeholder="请输入产品图片链接" class="layui-input">
                                </div>
                            </div>


                            <div class="layui-form-item">
                                <div class="layui-inline layui-col-md3">
                                    <label class="layui-form-label">购买数量</label>
                                    <div class="layui-input-block">
                                        <input type="text" id="count" name="count" lay-verify="required" value="<?=$model['count']?>" placeholder="数量" class="layui-input">
                                    </div>
                                </div>

                                <div class="layui-inline layui-col-md3">
                                    <label class="layui-form-label">亚马逊售价</label>
                                    <div class="layui-input-block">
                                        <input type="text" id="amazon_price" name="amazon_price" lay-verify="required" value="<?=$model['amazon_price']?>" placeholder="亚马逊售价" class="layui-input">
                                    </div>
                                </div>
                                <label class="layui-form-label" style="width: 10px;padding-left: 0">€</label>


                                <div class="layui-inline layui-col-md3">
                                    <label class="layui-form-label">Real售价</label>
                                    <div class="layui-input-block">
                                        <input type="text" id="real_price" name="real_price" lay-verify="required" value="<?=$model['real_price']?>" placeholder="Real售价" class="layui-input">
                                    </div>
                                </div>
                                <label class="layui-form-label" style="width: 10px;padding-left: 0">€</label>
                            </div>

                            <div class="layui-form-item">
                                <div class="layui-inline layui-col-md3">
                                    <label class="layui-form-label">Real订单金额</label>
                                    <div class="layui-input-block">
                                        <input type="text" id="real_order_amount" name="amazon_price" value="<?=$model['real_order_amount']?>" disabled class="layui-input">
                                    </div>
                                </div>
                                <label class="layui-form-label" style="width: 10px;padding-left: 0">€</label>


                                <div class="layui-inline layui-col-md3">
                                    <label class="layui-form-label">利润</label>
                                    <div class="layui-input-block">
                                        <input type="text" id="profit" name="profit" value="<?=$model['profit']?>" disabled class="layui-input">
                                    </div>
                                </div>
                                <label class="layui-form-label" style="width: 10px;padding-left: 0">€</label>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            </div>
        </div>

        <div style="padding: 10px; background-color: #F2F2F2; margin-top: 20px;">
            <div class="layui-row layui-col-space15">
                <div class="layui-col-md12">
                    <div class="layui-card">
                        <div class="layui-card-header">用户信息</div>
                        <div class="layui-card-body">

                            <div class="layui-field-box">

                                <div class="layui-form-item">
                                    <div class="layui-inline">
                                        <label class="layui-form-label">买家名称</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="buyer_name" lay-verify="required" value="<?=$model['buyer_name']?>" placeholder="请输入买家名称" class="layui-input">
                                        </div>
                                    </div>

                                    <div class="layui-inline">
                                        <label class="layui-form-label">买家电话</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="buyer_phone" lay-verify="required" value="<?=$model['buyer_phone']?>" placeholder="请输入买家电话" class="layui-input">
                                        </div>
                                    </div>

                                    <div class="layui-inline">
                                        <label class="layui-form-label">客户编号</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="user_no" value="<?=$model['user_no']?>" placeholder="请输入客户编号" class="layui-input">
                                        </div>
                                    </div>
                                </div>

                                <div class="layui-form-item">
                                    <label class="layui-form-label">地址</label>

                                    <div class="layui-input-inline">
                                        <input type="text" name="country" lay-verify="required" value="<?=$model['country']?>" placeholder="国家" class="layui-input">
                                    </div>

                                    <div class="layui-input-inline">
                                        <input type="text" name="city" lay-verify="required" value="<?=$model['city']?>" placeholder="城市" class="layui-input">
                                    </div>

                                    <div class="layui-input-inline">
                                        <input type="text" name="area" lay-verify="required" value="<?=$model['area']?>" placeholder="区" class="layui-input">
                                    </div>
                                </div>

                                <div class="layui-form-item">
                                    <div class="layui-inline layui-col-md6">
                                        <label class="layui-form-label"></label>
                                        <div class="layui-input-block">
                                            <input type="text" name="address" lay-verify="required" value="<?=$model['address']?>" placeholder="请输入详细地址" class="layui-input">
                                        </div>
                                    </div>

                                    <div class="layui-inline">
                                        <label class="layui-form-label">邮编</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="postcode" lay-verify="required" value="<?=$model['postcode']?>" placeholder="请输入邮编" class="layui-input">
                                        </div>
                                    </div>
                                </div>

                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div style="padding: 10px; background-color: #F2F2F2; margin-top: 20px;">
            <div class="layui-row layui-col-space15">
                <div class="layui-col-md12">
                    <div class="layui-card">
                        <div class="layui-card-header">物流信息</div>
                        <div class="layui-card-body">

                            <div class="layui-field-box">

                                <div class="layui-form-item">
                                    <div class="layui-inline layui-col-md4">
                                        <label class="layui-form-label">Real跟踪号</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="real_track_no" value="<?=$model['real_track_no']?>" placeholder="请输入Real跟踪号" class="layui-input">
                                        </div>
                                    </div>

                                    <div class="layui-inline layui-col-md4">
                                        <label class="layui-form-label">刷单买家号机器编号</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="swipe_buyer_id" value="<?=$model['swipe_buyer_id']?>" placeholder="请输入刷单买家号机器编号" class="layui-input">
                                        </div>
                                    </div>
                                </div>

                                <div class="layui-form-item">
                                    <div class="layui-inline layui-col-md4">
                                        <label class="layui-form-label">亚马逊订单号</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="amazon_order_id" value="<?=$model['amazon_order_id']?>" placeholder="请输入亚马逊订单号" class="layui-input">
                                        </div>
                                    </div>

                                    <div class="layui-inline layui-col-md4">
                                        <label class="layui-form-label">亚马逊物流订单号</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="logistics_id" value="<?=$model['logistics_id']?>" placeholder="请输入亚马逊物流订单号" class="layui-input">
                                        </div>
                                    </div>
                                </div>

                                <div class="layui-form-item">
                                    <div class="layui-inline layui-col-md4">
                                        <label class="layui-form-label">亚马逊状态</label>
                                        <div class="layui-input-block">
                                            <?= Html::dropDownList('amazon_status', $model['amazon_status'], RealOrder::$amazon_status_map,
                                                ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
                                        </div>
                                    </div>
                                    <div class="layui-inline layui-col-md4">
                                        <label class="layui-form-label">亚马逊预计到货时间</label>
                                        <div class="layui-input-block">
                                            <input type="text" id="amazon_arrival_time" value="<?=$model['amazon_arrival_time']?>" name="amazon_arrival_time" placeholder="yyyy-MM-dd" class="layui-input ys-date">
                                        </div>
                                    </div>
                                </div>
                                <div class="layui-form-item">

                                    <div class="layui-inline layui-col-md6">
                                        <label class="layui-form-label">Real发货状态</label>
                                        <div class="layui-input-block">
                                            <input type="radio" name="real_delivery_status" value="0" title="未发货" <?php if($model['real_delivery_status'] == 0){?>checked=""<?php } ?>>
                                            <input type="radio" name="real_delivery_status" value="10" title="已发货" <?php if($model['real_delivery_status'] == 10){?>checked=""<?php } ?>>
                                            <input type="radio" name="real_delivery_status" value="20" title="无跟踪信息发" <?php if($model['real_delivery_status'] == 20){?>checked=""<?php } ?>>
                                        </div>
                                    </div>

                                    <div class="layui-inline layui-col-md4">
                                        <label class="layui-form-label">Real订单状态</label>
                                        <div class="layui-input-block">
                                            <?= Html::dropDownList('real_order_status', $model['real_order_status'], RealOrder::$real_order_status_map,
                                                ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
                                        </div>
                                    </div>

                                </div>

                                <div class="layui-form-item">
                                    <label class="layui-form-label">备注</label>
                                    <div class="layui-input-block">
                                        <textarea class="layui-textarea" style="height: 100px" name="desc"><?=$model['desc']?></textarea>
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
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="add">立即提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </div>
</form>
<?=$this->registerJsFile("@adminPageJs/base/form.js?v=0.0.1.5")?>
<?=$this->registerJsFile("@adminPageJs/real-order/form.js?v=0.0.1.5")?>