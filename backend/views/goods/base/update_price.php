<?php

use common\components\statics\Base;
use yii\helpers\Url;
$platform_name = \common\components\statics\Base::$platform_maps[$platform_type];
$url_platform_name = strtolower($platform_name);
?>
<style xmlns="http://www.w3.org/1999/html">
    html {
        background: #fff;
    }
    .layui-form-item{
        margin-bottom: 5px;
    }
    .update_table {
        border: none;
        background-color: rgba(0, 0, 0, 0);
        -webkit-user-select:text;
    }
    .layui-form-label{
        width: auto;
    }
    .price-row{
        margin: 10px;
        padding: 10px;
        border:1px solid #eee;
    }
    .label-right{
        color: #00a0e9;
    }
    .input-left{
        float: left;
        margin-right: 10px;
    }
</style>
<form class="layui-form layui-row" id="update_goods" action="<?=Url::to(['goods-'.$url_platform_name.'/update-price'])?>">
    <div class="layui-col-md9 layui-col-xs12" style="padding: 0 10px">
    <div class="layui-field-box" style="padding-top: 10px;">
            <div class="layui-form-item">
                <div class="layui-inline">
                    <label class="layui-form-label">商品编号</label>
                    <label class="layui-form-mid"><?=$goods['goods_no']?></label>
                </div>
                <div class="layui-inline">
                    <label class="layui-form-label">SKU</label>
                    <label class="layui-form-mid"><?=$goods['sku_no']?></label>
                </div>
                <div class="layui-inline" >
                    <label class="layui-form-label" style="width: 80px">参考表格</label>
                    <div class="layui-input-block">
                        <a class="layui-btn layui-btn-primary update_table" data-url="<?=Url::to(['goods-'.$url_platform_name.'/table?platform_type='.$platform_type.'&shop_id='.$shop_goods_model['shop_id'].'&cgoods_no='.$shop_goods_model['cgoods_no']])?>" data-type="url" data-title="价格趋势" style="color: #00a0e9">
                            <label class="layui-form-label" style="width: 180px;text-align: left;padding-left: 0px">价格趋势</label>
                        </a>
                    </div>
                </div>
            </div>
            <div class="layui-form-item">
                <div class="layui-inline">
                    <label class="layui-form-label">锁定价格</label>
                    <div class="layui-input-inline" style="width: 80px">
                        <input type="text" id="fixed_price" name="fixed_price" placeholder="请输入锁定价格" value="<?=empty($shop_goods_model['fixed_price'])?10:$shop_goods_model['fixed_price'] ?>" class="layui-input" autocomplete="off">
                    </div>
                    <div class="layui-form-mid label-left layui-word-aux"><?=$currency['base_currency']?></div>
                </div>

                <?php if (!empty($allegro_currency)) {?>
                <div class="layui-inline">
                    <label class="layui-form-label"  style="text-align: left;width: 30px;">价格</label>
                    <label class="layui-form-mid" id="allegro_price"><?=$allegro_currency['other_currency_price']?></label>
                    <div class="layui-form-mid label-left layui-word-aux"><?=$currency['base_currency']?></div>
                </div>
                <?php }?>

                <div class="layui-inline">
                    <label class="layui-form-label"  style="text-align: left;width: 30px;">折扣</label>
                    <div class="layui-input-inline" style="width: 80px">
                        <input type="text" id="discount" name="discount" placeholder="请输入折扣" value="<?=empty($shop_goods_model['discount'])?10:$shop_goods_model['discount'] ?>" class="layui-input" autocomplete="off">
                    </div>
                </div>

                <div class="layui-inline">
                    <label class="layui-form-label" style="text-align: left;width: 65px;">汇率</label>
                    <div class="layui-input-inline" style="width: 80px">
                        <input type="text" id="exchange_rate" name="exchange_rate" placeholder="请输入汇率" value="<?=$currency['exchange_rate']?>" class="layui-input" autocomplete="off">
                    </div>
                    <div class="layui-form-mid label-left layui-word-aux"><?=$currency['base_currency']?> => <?=$currency['target_currency']?></div>
                </div>

                <?php if($selling_price['original']['price'] > 0){ ?>
                    <div class="layui-inline">
                        <label class="layui-form-label">跟卖价</label>
                        <div class="layui-input-inline" style="width: 80px">
                        <input type="text" id="selling_price" name="selling_price" class="layui-input" value="<?=$selling_price['original']['price']?>" >
                        </div>
                        <div class="layui-form-mid label-left layui-word-aux""><?=$selling_price['original']['currency']?> (<span id="RealConversion"><?=$selling_price['target']['price']?></span> USD)</div>
                    </div>
                <?php } ?>
            </div>

            <?php if (!empty($shop_follow_sale)) {?>
                <div class="layui-form-item">
                    <div class="layui-inline">
                        <label class="layui-form-label">跟卖价</label>
                        <div class="layui-input-block">
                            <input type="text" id="follow_price" lay-verify="number" name="follow_price" style="width: 80px" placeholder="跟卖价" value="<?=$shop_follow_sale['price']?>" class="layui-input" autocomplete="off">
                        </div>
                    </div>

                    <div class="layui-inline">
                        <label class="layui-form-label">最低价</label>
                        <div class="layui-input-block">
                            <input type="text" id="sale_min_price" lay-verify="number" name="sale_min_price" style="width: 80px" placeholder="最低价格" value="<?=$shop_follow_sale['sale_min_price']?>" class="layui-input" autocomplete="off">
                        </div>
                    </div>

                    <div class="layui-inline">
                        <label class="layui-form-label">预计最低价</label>
                        <div class="layui-input-block">
                            <label class="layui-form-label" style="width: 180px;text-align: left"><?=$price['min_price'];?>(<?=$price['warning_price'];?>)</label>
                        </div>
                    </div>
                </div>
            <?php }?>
        <div class="layui-row price-row" id="budget-price-div">

        </div>

        <?php if(!empty($goods_shop_overseas_warehouse)){?>
            <div class="layui-form-item">
                <div class="layui-inline">
                    <label class="layui-form-label">仓库</label>
                    <div class="layui-input-block">
                        <label class="layui-form-label" style="width: 120px;text-align: left"><?=$goods_shop_overseas_warehouse['warehouse']['warehouse_name']?></label>
                    </div>
                </div>
                <div class="layui-inline">
                    <label class="layui-form-label">库存数</label>
                    <div class="layui-input-block">
                        <label class="layui-form-label" style="width: 120px;text-align: left"><?=$goods_shop_overseas_warehouse['warehouse']['inventory_quantity']?></label>
                    </div>
                </div>
                <div class="layui-inline">
                    <label class="layui-form-label">在途数</label>
                    <div class="layui-input-block">
                        <label class="layui-form-label" style="width: 120px;text-align: left"><?=$goods_shop_overseas_warehouse['warehouse']['transit_quantity']?></label>
                    </div>
                </div>
            </div>
        <?php }?>

            <?php if($platform_type == \common\components\statics\Base::PLATFORM_OZON){ ?>
            <div class="layui-form-item">
                <label class="layui-form-label">仓库</label>
                <div class="layui-inline" style="width: 200px;height: 40px;float: left">
                    <?= \yii\helpers\Html::dropDownList('warehouse_id',$good_ware,$warehouse_lists,
                        ['lay-ignore'=>'lay-ignore','data-placeholder' => '请输入仓库','prompt' => '无','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
                </div>
                <?php if(!empty($warehouse_id)){ ?>
                    <div class="layui-form-mid label-left layui-word-aux">推荐：</div>
                    <div class="layui-form-mid label-left"><?=$warehouse_id?></div>
                <?php }?>
            </div>
            <?php }?>

            <?php if($logistics_price) { ?>
            <div class="layui-form-item">
                <table  class="layui-table" style="margin-top: 10px">
                    <thead>
                    <tr>
                        <th></th>
                        <th>运费</th>
                        <th>成本(USD)</th>
                        <th>成本(RUB)</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($logistics_price as $v){ ?>
                    <tr <?php if(!$v['estimate']){ ?>style="background: #ffa80042"<?php }?>>
                        <td style="width: 200px"><?=$v['logistics_name']?></td>
                        <td><?=$v['price']?><?=$v['currency']?></td>
                        <td><?=$v['cost']?></td>
                        <td><?=$v['cost_rub']?></td>
                    </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
            <?php } ?>

            <div class="layui-form-item">
                <table class="layui-table" style="text-align: center">
                <thead>
                <tr>
                    <th>旧价格</th>
                    <th>新价格</th>
                    <th>修改人</th>
                    <th>修改类型</th>
                    <th>修改时间</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($list)): ?>
                    <tr>
                        <td colspan="17">无数据</td>
                    </tr>
                <?php else: foreach ($list as $k => $v):
                    $i = 0;?>
                    <tr>
                        <td><?=$v['old_price']?></td>
                        <td><?=$v['new_price']?></td>
                        <td><?=$v['user_id']?></td>
                        <td><?=$v['type']?></td>
                        <td><?=$v['add_time']?></td>
                    </tr>
                <?php endforeach;
                endif;?>
                </tbody>
                </table>
            </div>
        </div>
    </div>
        <div class="layui-form-item layui-layout-admin">
            <div class="layui-input-block">
                <div class="layui-footer" style="left: 0;">
                    <input type="hidden" name="id" value="<?=$shop_goods_model['id']?>">
                    <input type="hidden" name="platform_type" value="<?=$platform_type?>" class="layui-input">
                    <button class="layui-btn" lay-submit="" lay-filter="form" data-form="update_goods">提交</button>
                </div>
            </div>
        </div>
</form>

<script id="budget_price_tpl" type="text/html">
    <div class="layui-col-xs6">
        {{# for(let i in d.left){
        var item = d.left[i];
        }}
        <div class="layui-row">
            <div class="layui-form-mid label-left">{{ item.label }}</div>
            <div class="layui-form-mid label-right" style="{{# if(item.color){ }}color:{{ item.color }}{{# } }}">{{ item.value }}</div>
        </div>
        {{# } }}
    </div>
    <div class="layui-col-xs6">
        {{# for(let i in d.right){
        var item = d.right[i];
        }}
        <div class="layui-row">
            <div class="layui-form-mid label-left">{{ item.label }}</div>
            {{# if(item.type == 'input'){ }}
                <div class="input-left" style="margin-top: 5px">
                    <input type="text" name="{{ item.name }}" style="width: 70px" lay-verify="number" placeholder="请输入" class="layui-input {{# if(item.auto_ajax !== undefined && item.auto_ajax){ }}auto_ajax{{# } }}" autocomplete="off" value="{{ item.value }}">
                </div>
                {{# if(item.currency !== undefined ){ }}
                <div class="layui-form-mid label-left layui-word-aux">{{ item.currency }}</div>
                {{# } }}
                {{# if(item.estimate_value !== undefined ){ }}
                <div class="layui-form-mid label-left layui-word-aux">预估</div>
                <div class="layui-form-mid label-right">{{ item.estimate_value }}</div>
                {{# } }}
                {{# if(item.lock_weight !== undefined ){ }}
                <span style="padding-left: 5px" class="lay-lists">
                    <input type="checkbox" name="lock_weight" value="1" lay-skin="switch" lay-text="锁定|未锁定" lay-filter="statusSwitch"
                    {{# if(item.lock_weight){ }}checked = "checked"{{# } }} >
                </span>
                {{# } }}
            {{# }else{ }}
                <div class="layui-form-mid label-right" style="{{# if(item.color){ }}color:{{ item.color }}{{# } }}">{{ item.value }}</div>
            {{# } }}
        </div>
        {{# } }}
    </div>
</script>

<script>
    var conversion = <?=(isset($selling_price['RealConversion']))?$selling_price['RealConversion']:1?>;
    var platform_type = <?=$shop_goods_model['platform_type']?>;
</script>
<?php
$this->registerJsFile("@adminPageJs/base/form.js?".time());
$this->registerJsFile("@adminPageJs/goods/base_update_price.js?".time());
?>

