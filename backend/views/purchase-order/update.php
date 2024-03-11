<?php
/**
 * @var $this \yii\web\View;
 */

use common\models\Supplier;
use common\services\warehousing\WarehouseService;
use yii\helpers\Url;
use yii\helpers\Html;
?>
<style>
    .layui-fluid {
        padding: 3px;
    }
</style>
<form class="layui-form layui-row" id="update-order" action="<?=Url::to([!empty($again)?'purchase-order/again':($model->isNewRecord?'purchase-order/create':'purchase-order/update')])?>">

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
                                    <label class="layui-form-label">供应商</label>
                                    <div class="layui-input-block">
                                        <?= Html::dropDownList('source', $model['source'], \common\components\statics\Base::$purchase_source_maps,['id'=>'source','lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px']) ?>
                                    </div>
                                </div>
                                <div id="supplier" class="layui-inline" style="margin-bottom: 0px"></div>
                                <div class="layui-inline">
                                    <label class="layui-form-label">供应商单号</label>
                                    <div class="layui-input-block">
                                        <input type="text" name="relation_no" lay-verify="required" placeholder="供应商单号" value="<?=$model['relation_no']?>" class="layui-input">
                                    </div>
                                </div>
                            </div>

                            <div class="layui-form-item">
                                <div class="layui-inline">
                                    <label class="layui-form-label">物流方式</label>
                                    <div class="layui-input-block" style="width: 300px">
                                        <?= Html::dropDownList('logistics_channels_id', $model['logistics_channels_id'],  \common\services\purchase\PurchaseOrderService::getLogisticsChannels(),['prompt' => '请选择','lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
                                    </div>
                                </div>

                                <div class="layui-inline">
                                    <label class="layui-form-label">物流单号</label>
                                    <div class="layui-input-block">
                                        <input type="text" name="track_no"  placeholder="物流单号" value="<?=$model['track_no']?>" class="layui-input">
                                    </div>
                                </div>
                            </div>

                            <div class="layui-inline">
                                <label class="layui-form-label">下单时间</label>
                                <div class="layui-input-inline">
                                    <input type="text" name="date" id="date" lay-verify="datetime" placeholder="yyyy-MM-dd HH:mm:ss" autocomplete="off"  value="<?=$model['date']?>" class="layui-input ys-datetime">
                                </div>

                                <div class="layui-inline">
                                    <label class="layui-form-label">仓库</label>
                                    <div class="layui-input-block" style="width: 300px;line-height: 35px;">
                                        <?php if($lock_goods){ ?>
                                        <?= WarehouseService::getPurchaseWarehouse($model['warehouse']) ?>
                                        <input type="hidden" name="warehouse" value="<?=$model['warehouse']?>">
                                        <?php } else{ ?>
                                        <?= Html::dropDownList('warehouse', $model['warehouse'], WarehouseService::$warehouse_map,
                                                ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
                                        <?php }?>
                                    </div>
                                </div>
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
                        <div class="layui-card-header">商品信息 <?php if(!$lock_goods){?><a class="layui-btn layui-btn-normal layui-btn-xs" id="add-goods" href="javascript:;">添加商品</a><?php }?>
                        </div>
                        <div class="layui-card-body">

                            <div class="layui-field-box">

                                <div id="goods">

                                </div>

                                <div class="layui-form-item">
                                    <div class="layui-inline">
                                        <label class="layui-form-label">运费</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="freight_price"  placeholder="运费" value="<?=empty($model['freight_price'])?0:$model['freight_price']?>" class="layui-input">
                                        </div>
                                    </div>
                                    <div class="layui-inline">
                                        <label class="layui-form-label">其他费用</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="other_price"  placeholder="其他费用" value="<?=empty($model['other_price'])?0:$model['other_price']?>" class="layui-input">
                                        </div>
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
<script id="goods_tpl" type="text/html">
<blockquote class="layui-elem-quote layui-quote-nm">
    <div class="layui-fluid layui-form-item" style="margin-bottom:5px">
        <div class="layui-inline" style="width: 350px">
            <label>商品名称</label>
            <div>
                <input type="text" name="goods[goods_name][]" placeholder="商品名称" value="{{ d.goods.goods_name || '' }}" class="layui-input">
            </div>
        </div>
        <div class="layui-inline">
            <label>商品sku</label>
            <div>
                {{# if(d.goods.ovg_id > 0){ }}
                <div style="line-height: 40px;width: 170px">{{ d.goods.sku_no || '' }}</div>
                <input type="hidden"name="goods[sku_no][]" value="{{ d.goods.sku_no || '' }}">
                {{# }else{ }}
                <input type="text" lay-verify="required" name="goods[sku_no][]" placeholder="sku" value="{{ d.goods.sku_no || '' }}" class="layui-input">
                {{# } }}
            </div>
        </div>
        <div class="layui-inline" style="width: 100px">
            <label>售价</label>
            <div>
                <input type="text" name="goods[goods_price][]" lay-verify="required" placeholder="售价" value="{{ d.goods.goods_price || '' }}" class="layui-input">
            </div>
        </div>
        <div class="layui-inline" style="width: 100px">
            <label>数量</label>
            <div>
                <input type="text" name="goods[goods_num][]" lay-verify="required" placeholder="数量" value="{{ d.goods.goods_num || 1 }}" class="layui-input">
            </div>
        </div>
        <div id="del-goods">
            <span class="layui-layer-setwin"><a class="layui-layer-ico layui-layer-close layui-layer-close1" href="javascript:;"></a></span>
        </div>
    </div>
    <div class="layui-fluid layui-form-item" style="margin-bottom:5px">
        <div class="layui-inline" style="width: 700px">
            <label>商品链接</label>
            <div>
                <input type="text" name="goods[goods_url][]" placeholder="商品链接" value="{{ d.goods.goods_url || '' }}" class="layui-input">
            </div>
        </div>
        <div class="layui-inline" style="width: 100px">
            <label>重量(kg)</label>
            <div>
                <input type="text" name="goods[goods_weight][]" lay-verify="required" placeholder="重量" value="{{ d.goods.goods_weight || '' }}" class="layui-input">
            </div>
        </div>
    </div>
    <input type="hidden" name="goods[ovg_id][]" value="{{ d.goods.ovg_id || 0 }}" class="layui-input">
    <input type="hidden" name="goods[goods_id][]" value="{{ d.goods.id || '' }}" class="layui-input">
    <input type="hidden" name="goods[goods_pic][]" value="{{ d.goods.goods_pic || '' }}" class="layui-input">
</blockquote>
</script>

<script type="text/html" id="supplier_tpl">
    <div class="layui-inline" id="supplier_select">
        <select class="layui-input-block search-con ys-select2" lay-ignore name="supplier_id">
            <?php
            foreach (Supplier::allSupplierName() as $k=> $v){
                ?>
                <option value="<?=$k?>" {{# if(d.supplier && d.supplier == <?=$k?> ){ }} selected {{#  } }}><?=$v?></option>
            <?php }?>
        </select>
    </div>
</script>

<script type="text/javascript">
    var goods = <?=empty($order_goods)?"''":$order_goods;?>;
    var supplier_val = <?=empty($model['supplier_id']) ? "''" : $model['supplier_id']?>;
</script>
<?=$this->registerJsFile("@adminPageJs/base/form.js?v=0.0.2.1")?>
<?=$this->registerJsFile("@adminPageJs/purchase_order/form.js?v=".time())?>