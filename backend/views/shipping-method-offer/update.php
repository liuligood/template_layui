<?php
/**
 * @var $this \yii\web\View;
 */
use yii\helpers\Url;
use yii\helpers\Html;
?>
<style>
    html {
        background: #fff;
    }
    .layui-vertical-20{
        padding-top: 20px;
    }
    .layui-inline{
        margin-right:5px
    }
    .layui-form-item{
        margin-bottom:10px
    }
</style>
    <form class="layui-form layui-row" id="shipping-method-offer" action="<?=Url::to([$model->isNewRecord?'shipping-method-offer/create':'shipping-method-offer/update'])?>">

        <div class="layui-col-md9 layui-col-xs12" style="padding: 10px">
            <div style="padding: 8px; background-color: #F2F2F2;">
                <div class="layui-row layui-col-space15">
                    <div class="layui-col-md12">
                        <div class="layui-card">
                            <div class="layui-card-body">

                                <div class="layui-field-box" style="padding: 10px 0 0 0">
                                    <div class="layui-form-item">
                                        <div class="layui-inline" style="width: 400px">
                                            <label class="layui-form-label">运输服务名</label>
                                            <div class="layui-input-block">
                                                <input type="hidden" name="transport_code" value="<?=$shipping_method['transport_code']?>"  class="layui-input">
                                                <input type="hidden" name="shipping_method_code" value="<?=$shipping_method['shipping_method_code']?>"  class="layui-input">
                                                <input type="hidden" name="shipping_method_id" value="<?=$shipping_method['id']?>"  class="layui-input">
                                                <input type="text" name="shipping_method_name" disabled placeholder="请输入物流商运输服务名" value="<?=$shipping_method['shipping_method_name']?>" class="layui-input">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="layui-form-item">
                                        <div class="layui-inline" style="width: 400px">
                                            <label class="layui-form-label">国家</label>
                                            <div class="layui-input-block">
                                                <?= Html::dropDownList('country_code', $model['country_code'], \common\services\sys\CountryService::getSelectOption(),['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '国家','class'=>"layui-input ys-select2"]) ?>                                        </div>
                                        </div>
                                    </div>

                                    <!--<div class="layui-form-item">
                                        <div class="layui-inline">
                                            <label class="layui-form-label">状态</label>
                                            <div class="layui-input-block">
                                                <?= Html::dropDownList('status', $model['status'], \common\models\sys\ShippingMethod::$status_map,
                                            ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
                                            </div>
                                        </div>
                                    </div>-->

                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div style="padding: 8px; background-color: #F2F2F2; margin-top: 10px;">
                <div class="layui-row layui-col-space15">
                    <div class="layui-col-md12">
                        <div class="layui-card">
                            <div class="layui-card-header">报价 <a class="layui-btn layui-btn-normal layui-btn-xs" id="add-offer" href="javascript:;">添加</a>
                            </div>
                            <div class="layui-card-body">
                                <div class="layui-field-box" style="padding: 0">
                                    <div id="offer">

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
                    <button class="layui-btn" lay-submit="" lay-filter="form" data-form="shipping-method-offer">立即提交</button>
                    <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                </div>
            </div>
        </div>
    </form>
    <script id="offer_tpl" type="text/html">
        <blockquote class="layui-elem-quote layui-quote-nm" style="padding-top: 8px;padding-bottom: 0;margin-bottom:5px;">
            <div class="layui-fluid layui-form-item" style="margin-bottom:5px">
                <div class="layui-inline" style="width: 100px">
                    <label>运费(元/kg)</label>
                    <div>
                        <input type="text" lay-verify="required" name="offer[weight_price][]" placeholder="运费" value="{{ d.offer.weight_price || '' }}" class="layui-input">
                    </div>
                </div>
                <div class="layui-inline" style="width: 100px">
                    <label>处理费</label>
                    <div>
                        <input type="text" lay-verify="required" name="offer[deal_price][]" placeholder="处理费" value="{{ d.offer.deal_price || 0 }}" class="layui-input">
                    </div>
                </div>
                <div class="layui-inline" style="width: 80px;margin-right:3px">
                    <label>重量段(kg)</label>
                    <div>
                        <input type="text" lay-verify="required" name="offer[start_weight][]" placeholder="起始重量" value="{{ d.offer.start_weight || '' }}" class="layui-input">
                    </div>
                </div>
                <span class="layui-inline layui-vertical-20" style="margin-right:3px">
                    -
                </span>
                <div class="layui-inline layui-vertical-20" style="width: 80px;">
                    <label></label>
                    <div>
                        <input type="text" lay-verify="required" name="offer[end_weight][]" placeholder="结束重量" value="{{ d.offer.end_weight || '' }}" class="layui-input">
                    </div>
                </div>
                <div class="layui-inline" style="width: 80px;margin-right:3px">
                    <label>体积限制</label>
                    <div>
                        <textarea class="layui-textarea" style="width:150px;min-height: 50px" name="offer[formula][]">{{ d.offer.formula || '' }}</textarea>
                    </div>
                </div>
                <div id="del-offer">
                    <span class="layui-layer-setwin"><a class="layui-layer-ico layui-layer-close layui-layer-close1" href="javascript:;"></a></span>
                </div>
            </div>
            <input type="hidden" name="offer[offer_id][]" value="{{ d.offer.id || '' }}" class="layui-input">
        </blockquote>
    </script>
<script type="text/javascript">
    var offer = <?=empty($offer)?"''":$offer;?>;
</script>
<?=$this->registerJsFile("@adminPageJs/base/form.js?v=0.0.1.4")?>
<?=$this->registerJsFile("@adminPageJs/shipping-method/offer_form.js?v=".time())?>
