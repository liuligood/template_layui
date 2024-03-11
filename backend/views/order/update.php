<?php
/**
 * @var $this \yii\web\View;
 */

use common\models\warehousing\WarehouseProvider;
use common\services\ShopService;
use common\services\warehousing\WarehouseService;
use yii\helpers\Url;
use yii\helpers\Html;
use common\models\RealOrder;
?>

<form class="layui-form layui-row" id="update-order" action="<?=Url::to([!empty($again)?'order/again':($model->isNewRecord?'order/create':'order/update')])?>">

    <div class="layui-col-md9 layui-col-xs12" style="margin-left: 10px">

        <div style="padding: 10px; background-color: #F2F2F2;">
        <div class="layui-row layui-col-space15">
            <div class="layui-col-md12">
                <div class="layui-card">
                    <div class="layui-card-header">基本信息</div>
                    <div class="layui-card-body">

                        <div class="layui-field-box">

                            <div class="layui-form-item">
                                <div class="layui-inline">
                                    <label class="layui-form-label">来源平台</label>
                                    <div class="layui-input-block">
                                        <?= Html::dropDownList('source', $model['source'], \common\components\statics\Base::$order_source_maps,
                                            ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px','id'=>'platform']) ?>
                                    </div>
                                </div>

                                <div class="layui-inline">
                                    <label class="layui-form-label">店铺</label>
                                    <div class="layui-input-inline">
                                        <?= \backend\widgets\LinkageDropDownList::widget(['id' => 'shop','parent_id'=>'platform','name'=>'shop_id','select'=>$model['shop_id'],'option'=> ShopService::getOrderShopMap(),'param'=>['data-placeholder' => '全部','prompt' => '全部','style'=>'width:180px']]) ?>
                                    </div>
                                </div>
                                <?php if($model['order_status'] != \common\models\Order::ORDER_STATUS_FINISH){ ?>
                                <div class="layui-inline">
                                    <label class="layui-form-label">仓库</label>
                                    <?php if (in_array($model['warehouse'],!isset($platform_warehouse) ? [] : $platform_warehouse)) {?>
                                        <?php foreach ($warehouse_list as $warehouse_id => $warehouse_name) {?>
                                            <?php if ($warehouse_id == $model['warehouse']){?>
                                            <label class="layui-form-label" style="text-align: left;width: 220px">
                                                <?=$warehouse_name?>
                                            </label>
                                            <?php }?>
                                        <?php }?>
                                    <?php } else if (in_array($model['warehouse'],!isset($own_third_warehouse) ? [] : $own_third_warehouse) || empty($model['warehouse'])){?>
                                    <div class="layui-input-inline">
                                        <?= Html::dropDownList('warehouse', $model['warehouse'], $own_third_warehouse_list,
                                            ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
                                    </div>
                                    <?php }?>
                                </div>
                                <?php } ?>
                            </div>

                            <div class="layui-form-item">
                                <div class="layui-inline">
                                    <label class="layui-form-label">销售单号</label>
                                    <div class="layui-input-block">
                                        <input type="text" name="relation_no" lay-verify="required" placeholder="请输入订单号" value="<?=$model['relation_no']?>" class="layui-input">
                                    </div>
                                </div>
                                <div class="layui-inline">
                                    <label class="layui-form-label">货币</label>
                                    <div class="layui-input-inline">
                                        <?= Html::dropDownList('currency', $model['currency'], \common\services\sys\ExchangeRateService::getCurrencyOption(), ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
                                    </div>
                                </div>
                                <div class="layui-inline">
                                    <label class="layui-form-label">下单时间</label>
                                    <div class="layui-input-inline">
                                        <input type="text" name="date" id="date" lay-verify="datetime" placeholder="yyyy-MM-dd HH:mm:ss" autocomplete="off"  value="<?=$model['date']?>" class="layui-input ys-datetime">
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
                        <div class="layui-card-header">用户信息</div>
                        <div class="layui-card-body">

                            <div class="layui-field-box">
                                <div class="layui-form-item">
                                    <div class="layui-inline">
                                        <label class="layui-form-label">公司名称</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="company_name" value="<?=$model['company_name']?>" placeholder="请输入公司名称" class="layui-input">
                                        </div>
                                    </div>
                                    <div class="layui-inline">
                                        <label class="layui-form-label">邮箱</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="email" value="<?=$model['email']?>" placeholder="请输入邮箱" class="layui-input">
                                        </div>
                                    </div>
                                </div>

                                <div class="layui-form-item">
                                    <div class="layui-inline">
                                        <label class="layui-form-label">买家名称</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="buyer_name" <?php if($model['integrated_logistics'] == \common\models\Order::INTEGRATED_LOGISTICS_NO){ ?>lay-verify="required"<?php }?> value="<?=$model['buyer_name']?>" placeholder="请输入买家名称" class="layui-input">
                                        </div>
                                    </div>

                                    <div class="layui-inline">
                                        <label class="layui-form-label">买家电话</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="buyer_phone" <?php if($model['integrated_logistics'] == \common\models\Order::INTEGRATED_LOGISTICS_NO){ ?>lay-verify="required"<?php }?> value="<?=$model['buyer_phone']?>" placeholder="请输入买家电话" class="layui-input">
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

                                    <div class="layui-input-inline" >
                                        <?= Html::dropDownList('country', $model['country'], \common\services\sys\CountryService::getSelectOption(),['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '国家','class'=>"layui-input ys-select2"]) ?>
                                    </div>

                                    <div class="layui-input-inline">
                                        <input type="text" name="city" <?php if($model['integrated_logistics'] == \common\models\Order::INTEGRATED_LOGISTICS_NO){ ?>lay-verify="required"<?php }?> value="<?=$model['city']?>" placeholder="城市" class="layui-input">
                                    </div>

                                    <div class="layui-input-inline">
                                        <input type="text" name="area" <?php if($model['integrated_logistics'] == \common\models\Order::INTEGRATED_LOGISTICS_NO){ ?>lay-verify="required"<?php }?> value="<?=$model['area']?>" placeholder="省/州" class="layui-input">
                                    </div>
                                </div>

                                <div class="layui-form-item">
                                    <div class="layui-inline layui-col-md6">
                                        <label class="layui-form-label"></label>
                                        <div class="layui-input-block">
                                            <input type="text" name="address" <?php if($model['integrated_logistics'] == \common\models\Order::INTEGRATED_LOGISTICS_NO){ ?>lay-verify="required"<?php }?> value="<?=$model['address']?>" placeholder="请输入详细地址" class="layui-input">
                                        </div>
                                    </div>

                                    <div class="layui-inline">
                                        <label class="layui-form-label">邮编</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="postcode" <?php if($model['integrated_logistics'] == \common\models\Order::INTEGRATED_LOGISTICS_NO){ ?>lay-verify="required"<?php }?> value="<?=$model['postcode']?>" placeholder="请输入邮编" class="layui-input">
                                        </div>
                                    </div>
                                </div>

                                <div class="layui-form-item">
                                    <div class="layui-inline">
                                        <label class="layui-form-label">税号</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="tax_number" value="<?=$model['tax_number']?>" placeholder="请输入税号" class="layui-input">
                                        </div>
                                    </div>

                                    <div class="layui-inline">
                                        <label class="layui-form-label">销售单号(税号)</label>
                                        <div class="layui-input-block" >
                                            <input type="text" name="tax_relation_no" value="<?=$model['tax_relation_no']?>" placeholder="销售单号(税号)" class="layui-input">
                                        </div>
                                    </div>

                                    <?php if($model['source_method'] == \common\services\goods\GoodsService::SOURCE_METHOD_AMAZON && !empty($model['tax_number'])){?>
                                    <div class="layui-inline">
                                        <label class="layui-form-label">税号是否已使用</label>
                                        <div class="layui-input-block" >
                                            <?= Html::dropDownList('tax_number_use', $model['tax_number_use'], \common\models\Order::$tax_number_use_map,['prompt' => '请选择','lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
                                        </div>
                                    </div>
                                    <?php }?>
                                </div>

                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div style="padding: 10px; background-color: #F2F2F2; ">
            <div class="layui-row layui-col-space15">
                <div class="layui-col-md12">
                    <div class="layui-card">
                        <div class="layui-card-header">商品信息 <a class="layui-btn layui-btn-normal layui-btn-xs" id="add-goods" href="javascript:;">添加商品</a>
                        </div>
                        <div class="layui-card-body">

                            <div class="layui-field-box">

                                <div id="goods">

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

        <?php
        $order_declare_arr = empty($order_declare)?'':json_decode($order_declare,true);
        if(empty($again) && $model['source_method'] == \common\services\goods\GoodsService::SOURCE_METHOD_OWN && !empty($order_declare_arr)) {?>
        <div style="padding: 10px; background-color: #F2F2F2; ">
            <div class="layui-row layui-col-space15">
                <div class="layui-col-md12">
                    <div class="layui-card">
                        <div class="layui-card-header">报关信息 <!--<a class="layui-btn layui-btn-normal layui-btn-xs" id="add-declare" href="javascript:;">添加报关信息</a>-->
                        </div>
                        <div class="layui-card-body">
                            <div class="layui-field-box">
                                <div id="declare">

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
                        <div class="layui-card-header">物流信息 </div>
                        <div class="layui-card-body">

                            <div class="layui-field-box">

                                <div class="layui-form-item">
                                    <div class="layui-inline">
                                        <label class="layui-form-label">物流方式</label>
                                        <div class="layui-input-block" style="width: 300px">
                                            <?= Html::dropDownList('logistics_channels_id', $model['logistics_channels_id'], $logistics_channels_id,['prompt' => '请选择','lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
                                        </div>
                                    </div>
                                </div>

                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php }?>

        <div class="layui-form-item" style="margin-top: 20px">
            <div class="layui-input-block">
                <input type="hidden" name="id" value="<?=$model['id']?>">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="update-order">立即提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </div>
</form>
<script id="goods_tpl" type="text/html">
<blockquote class="layui-elem-quote layui-quote-nm">
    <div class="layui-fluid layui-form-item" style="margin-bottom:5px">
        <div class="layui-inline">
            <label>商品名称</label>
            <div>
                <input type="text" name="goods[goods_name][]" placeholder="商品名称" value="{{ d.goods.goods_name || '' }}" class="layui-input">
            </div>
        </div>
        <div class="layui-inline">
            <label>商品图片</label>
            <div>
                <input type="text" name="goods[goods_pic][]" placeholder="链接" value="{{ d.goods.goods_pic || '' }}" class="layui-input">
            </div>
        </div>
        <div class="layui-inline">
            <label>商品SKU</label>
            <div>
                <input type="text" lay-verify="required" name="goods[platform_asin][]" placeholder="SKU" value="{{ d.goods.platform_asin || '' }}" class="layui-input">
            </div>
        </div>
        <div class="layui-inline">
            <label>规格</label>
            <div>
                <input type="text" name="goods[goods_specification][]" placeholder="规格" value="{{ d.goods.goods_specification || '' }}" class="layui-input">
            </div>
        </div>
        <div class="layui-inline" style="width: 100px">
            <label>数量</label>
            <div>
                <input type="text" name="goods[goods_num][]" placeholder="数量" value="{{ d.goods.goods_num || 1 }}" class="layui-input">
            </div>
        </div>
        <div id="del-goods">
            <span class="layui-layer-setwin"><a class="layui-layer-ico layui-layer-close layui-layer-close1" href="javascript:;"></a></span>
        </div>
    </div>
    <div class="layui-fluid layui-form-item" style="margin-bottom:5px">
        <div class="layui-inline">
            <label>采购平台</label>
            <div class="">
                <select lay-verify="required" name="goods[platform_type][]">
                    <?php
                        foreach (\common\components\statics\Base::$buy_platform_maps as $k=> $v){
                    ?>
                    <option value="<?=$k?>" {{#  if(d.goods.platform_type && d.goods.platform_type == <?=$k?> ){ }} selected {{#  } }} ><?=$v?></option>
                    <?php }?>
                </select>
            </div>
        </div>
        <div class="layui-inline" style="width: 100px">
            <label>平台售价</label>
            <div>
                <input type="text" name="goods[goods_income_price][]" placeholder="售价" value="{{ d.goods.goods_income_price || '' }}" class="layui-input">
            </div>
        </div>
        <div class="layui-inline" style="width: 100px">
            <label>亚马逊售价</label>
            <div>
                <input type="text" name="goods[goods_cost_price][]" placeholder="成本" value="{{ d.goods.goods_cost_price || '' }}" class="layui-input">
            </div>
        </div>

        <div class="layui-inline" style="width: 80px">
            <label></label>
            <div>
                <input type="checkbox" name="goods[out_stock][]" value="1" title="缺货">
            </div>
        </div>
        <div class="layui-inline" style="width: 100px">
            <label></label>
            <div>
                <input type="checkbox" name="goods[error_con][]" value="1" title="信息错误">
            </div>
        </div>

    </div>
    <input type="hidden" name="goods[goods_id][]" value="{{ d.goods.id || '' }}" class="layui-input">
</blockquote>
</script>

<script id="declare_tpl" type="text/html">
    <blockquote class="layui-elem-quote layui-quote-nm">
        <div class="layui-fluid layui-form-item" style="margin-bottom:5px">
            <div class="layui-inline">
                <label>中文名称</label>
                <div>
                    <input type="text" lay-verify="required" name="declare[declare_name_cn][]" placeholder="中文名称" value="{{ d.declare.declare_name_cn || '' }}" class="layui-input">
                </div>
            </div>
            <div class="layui-inline">
                <label>英文名称</label>
                <div>
                    <input type="text" lay-verify="required" name="declare[declare_name_en][]" placeholder="英文名称" value="{{ d.declare.declare_name_en || '' }}" class="layui-input">
                </div>
            </div>
            <div class="layui-inline" style="width: 100px">
                <label>申报金额(USD)</label>
                <div>
                    <input type="text" lay-verify="required" name="declare[declare_price][]" placeholder="申报金额" value="{{ d.declare.declare_price || '' }}" class="layui-input">
                </div>
            </div>
            <div class="layui-inline" style="width: 100px">
                <label>申报重量(kg)</label>
                <div>
                    <input type="text" lay-verify="required" name="declare[declare_weight][]" placeholder="申报重量" value="{{ d.declare.declare_weight || '' }}" class="layui-input">
                </div>
            </div>
            <div class="layui-inline" style="width: 100px">
                <label>申报数量</label>
                <div>
                    <input type="text" lay-verify="required" name="declare[declare_num][]" placeholder="申报数量" value="{{ d.declare.declare_num || 1 }}" class="layui-input">
                </div>
            </div>

            {{# if(d.index!=0){ }}
            <div id="del-declare">
                <span class="layui-layer-setwin"><a class="layui-layer-ico layui-layer-close layui-layer-close1" href="javascript:;"></a></span>
            </div>
            {{# } }}
        </div>
        <div class="layui-fluid layui-form-item" style="margin-bottom:5px">
            <div class="layui-inline">
                <label>材质</label>
                <div>
                    <input type="text" name="declare[declare_material][]" placeholder="材质" value="{{ d.declare.declare_material || '' }}" class="layui-input">
                </div>
            </div>
            <div class="layui-inline">
                <label>用途</label>
                <div>
                    <input type="text" name="declare[declare_purpose][]" placeholder="用途" value="{{ d.declare.declare_purpose || '' }}" class="layui-input">
                </div>
            </div>
            <div class="layui-inline">
                <label>海关编码</label>
                <div>
                    <input type="text" name="declare[declare_customs_code][]" placeholder="海关编码" value="{{ d.declare.declare_customs_code || '' }}" class="layui-input">
                </div>
            </div>
        </div>
        <input type="hidden" name="declare[declare_id][]" value="{{ d.declare.id || '' }}" class="layui-input">
        <input type="hidden" name="declare[order_goods_id][]" value="{{ d.declare.order_goods_id || '' }}" class="layui-input">
    </blockquote>
</script>
<script type="text/javascript">
    var goods = <?=empty($order_goods)?"''":$order_goods;?>;
    var declare = <?=empty($order_declare)?"''":$order_declare;?>;
</script>
<?=$this->registerJsFile("@adminPageJs/base/form.js?v=0.0.2.1")?>
<?=$this->registerJsFile("@adminPageJs/order/form.js?v=".time())?>
