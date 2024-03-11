
<?php

use common\components\statics\Base;
use common\services\ShopService;
use yii\helpers\Html;
use yii\helpers\Url;
?>
<style>
    .layui-table-body .layui-table-cell{
        height:auto;
    }
    #summary i{
        color: red;
    }
    .layui-btn-a {
        border: none;
        background-color: rgba(0, 0, 0, 0);
        -webkit-user-select:text;
    }
</style>
<div class="layui-fluid">
    <div class="layui-card">
        <div class="layui-card-body">
        <div class="lay-lists">
            <form>
                <div class="layui-form lay-search" style="padding-left: 10px">

                    <div class="layui-inline" style="width: 120px">
                        平台：
                        <?= Html::dropDownList('OrderSettlementSearch[platform_type]', $searchModel['platform_type'], Base::$platform_maps,
                            ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','id'=>'platform']) ?>
                    </div>

                    <div class="layui-inline">
                        店铺：
                        <?= \backend\widgets\LinkageDropDownList::widget(['id' => 'shop','parent_id'=>'platform','name'=>'OrderSettlementSearch[shop_id]','select'=>$searchModel['shop_id'],'option'=> ShopService::getShopMapIndexPlatform(),'param'=>['data-placeholder' => '全部','prompt' => '全部','style'=>'width:185px']]) ?>
                    </div>

                    <div class="layui-inline">
                        下单时间：
                        <input  class="layui-input search-con ys-datetime"  name="OrderSettlementSearch[start_order_time]" value="<?=$searchModel['start_order_time'];?>" id="start_order_time" autocomplete="off">
                    </div>
                    <span class="layui-inline layui-vertical-20">
                        <br>
                        -
                    </span>
                    <div class="layui-inline layui-vertical-20">
                        <br>
                        <input  class="layui-input search-con ys-datetime" name="OrderSettlementSearch[end_order_time]"  value="<?=$searchModel['end_order_time'];?>"  id="end_order_time" autocomplete="off">
                    </div>

                    <div class="layui-inline">
                        发货时间
                        <input class="layui-input search-con ys-date" name="OrderSettlementSearch[start_delivery_time]" value="<?=$searchModel['start_delivery_time'];?>" id="start_delivery_time" autocomplete="off">
                    </div>
                    <span class="layui-inline layui-vertical-20">
                            -
                   </span>
                    <div class="layui-inline layui-vertical-20">
                        <br>
                        <input class="layui-input search-con ys-date" name="OrderSettlementSearch[end_delivery_time]" value="<?=$searchModel['end_delivery_time'];?>" id="end_delivery_time" autocomplete="off">
                    </div>
                    
                    <?php if ($tag != 0){ ?>
                    <div class="layui-inline">
                        账单时间：
                        <input  class="layui-input search-con ys-datetime" name="OrderSettlementSearch[start_settlement_time]"  value="<?=$searchModel['start_settlement_time'];?>" id="start_settlement_time" autocomplete="off">
                    </div>
                    <span class="layui-inline layui-vertical-20">
                        <br>
                        -
                    </span>
                    <div class="layui-inline layui-vertical-20">
                        <br>
                        <input  class="layui-input search-con ys-datetime" name="OrderSettlementSearch[end_settlement_time]" value="<?=$searchModel['end_settlement_time'];?>"   id="end_settlement_time" autocomplete="off">
                    </div>
                    <?php }?>

                    <?php if ($tag == 2){ ?>
                    <div class="layui-inline">
                        回款时间：
                        <input  class="layui-input search-con ys-datetime" name="OrderSettlementSearch[start_collection_time]"  value="<?=$searchModel['start_collection_time'];?>" id="start_collection_time" autocomplete="off">
                    </div>
                    <span class="layui-inline layui-vertical-20">
                        <br>
                        -
                    </span>
                    <div class="layui-inline layui-vertical-20">
                        <br>
                        <input  class="layui-input search-con ys-datetime" name="OrderSettlementSearch[end_collection_time]" value="<?=$searchModel['end_collection_time'];?>"   id="end_collection_time" autocomplete="off">
                    </div>
                    <?php }?>

                    <div class="layui-inline layui-vertical-20">
                        <br>
                        <input type="hidden" name="tag" value="<?=$tag;?>" >
                        <button class="layui-btn" data-type="search_lists">搜索</button>
                    </div>
                </div>
            </form>
            <div class="layui-card-body">
                <div class="layui-form">
                <div class="layui-table-body layui-table-main">
                    <table class="layui-table" style="text-align: center;width: 2000px">
                        <thead>
                        <tr>
                            <th>来源</th>
                            <th>订单量</th>
                            <th>退款订单量</th>
                            <th>退款率</th>
                            <th>货币</th>
                            <th>销售金额</th>
                            <th>佣金</th>
                            <th>其他费用</th>
                            <th>平台运费</th>
                            <th>退款金额</th>
                            <th>取消费用</th>
                            <th>退款佣金</th>
                            <th>总金额</th>
                            <th>采购(成本)</th>
                            <th>运费(成本)</th>
                            <th>预计利润</th>
                            <?php if ($tag == 1 && $is_shop){?>
                                <th>最后回款时间</th>
                                <th>最后账期</th>
                            <?php }?>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($list)){ ?>
                            <tr>
                                <td colspan="17">无数据</td>
                            </tr>
                        <?php }else{
                            $sett_url = $tag == 0?'/order-settlement/unconfirmed-index':($tag == 1?'/order-settlement/index':'/order-settlement/settled-index');
                            foreach ($list as $platform_k=>$platform_v){
                                $i = 0;
                                $platform_cut = count($platform_v['currency']);
                                foreach ($platform_v['currency'] as $item){
                                    $i ++;
                            ?>
                            <tr>
                                <?php if($i == 1){ ?>
                                <td rowspan="<?=$platform_cut?>"><?=$platform_v['source_name']?></td>
                                    <td rowspan="<?=$platform_cut?>"><a class="layui-btn layui-btn-xs layui-btn-a" data-type="url" data-url="<?=Url::to([$sett_url.'?'.$_SERVER['QUERY_STRING'].'&OrderSettlementSearch['.$platform_v['type'].']='.$platform_k])?>" data-title="订单" style="color: #00a0e9"><?=$platform_v['order_cut']?></a></td>
                                    <td rowspan="<?=$platform_cut?>"><a class="layui-btn layui-btn-xs layui-btn-a" data-type="url" data-url="<?=Url::to([$sett_url.'?'.$_SERVER['QUERY_STRING'].'&OrderSettlementSearch['.$platform_v['type'].']='.$platform_k.'&OrderSettlementSearch[has_refund]=1'])?>" data-title="订单" style="color: #00a0e9"><?=$platform_v['refund_order_cut']?></a></td>
                                <td rowspan="<?=$platform_cut?>"><?=number_format($platform_v['refund_order_cut']/$platform_v['order_cut'] * 100,2) ?>%</td>
                                <?php }?>
                                <td><?=$item['currency']?></td>
                                <td><?=$item['sales_amount']?></td>
                                <td><?=$item['commission_amount']?></td>
                                <td><?=$item['other_amount']?></td>
                                <td><?=$item['platform_type_freight']?></td>
                                <td><?=$item['refund_amount']?></td>
                                <td><?=$item['cancellation_amount']?></td>
                                <td><?=$item['refund_commission_amount']?></td>
                                <td><?=$item['total_amount']?></td>
                                <?php if($i == 1){ ?>
                                <td rowspan="<?=$platform_cut?>"><?=$platform_v['procurement_amount']?></td>
                                <td rowspan="<?=$platform_cut?>"><?=$platform_v['freight']?></td>
                                <td rowspan="<?=$platform_cut?>"><?=$platform_v['total_profit']?></td>
                                    <?php if ($tag == 1 && $is_shop){ ?>
                                        <td rowspan="<?=$platform_cut?>"><?=$platform_v['last_collection_time']?></td>
                                        <td rowspan="<?=$platform_cut?>"><?=$platform_v['last_financial']?></td>
                                    <?php }?>
                                <?php }?>
                            </tr>
                        <?php }
                            }
                        } ?>
                        </tbody>
                    </table>
                </div>
           </div>
            </div>
        </div>
        </div>
    </div>
</div>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.7")?>
