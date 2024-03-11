
<?php

use common\models\warehousing\WarehouseProvider;
use yii\helpers\Url;
use common\models\RealOrder;
use yii\helpers\Html;
use yii\widgets\LinkPager;
use common\components\statics\Base;
?>

<style>
    .layui-laypage li{
        float: left;
    }
    .layui-laypage .active a{
        background-color: #171818;
        color: #fff;
    }
    .layui-vertical-20{
        padding-top: 20px;
    }
    .layui-tab-title .layui-this {
        background-color: #fff;
        color: #000;
    }
    .layui-tab-brief > .layui-tab-title .layui-this a{
        color: rgb(20, 21, 21);
    }
    #red{
        color: rgb(0, 150, 136);
    }
    #blue{
        color: red;
    }
</style>
<div class="layui-fluid">
    <div class="layui-card">
    <div class="layui-tab layui-tab-brief">
        <ul class="layui-tab-title">
            <?php foreach ($warehouse_lists as $id => $warehouse_name){ ?>
                <li <?php if($warehouse_id == $id){?>class="layui-this" <?php }?>><a href="<?=Url::to(['warehouse-goods-statistics/index?warehouse_id='.$id])?>"><?=$warehouse_name?></a></li>
            <?php }?>
        </ul>
    </div>
        <div class="lay-lists" style="padding-top: 15px">
        </div>
        <div style="padding-bottom: 15px">
            <div class="layui-row" style="margin-left: 15px;margin-right: 15px;">
                <table class="layui-table " style="text-align: center;font-size: 13px;">
                    <thead>
                    <tr>
                        <th style="text-align: center;height: 35px"></th>
                        <th style="text-align: center;height: 35px"> < 30 </th>
                        <th style="text-align: center"> 31-60 </th>
                        <th style="text-align: center"> 61-90 </th>
                        <th style="text-align: center"> 91-180 </th>
                        <th style="text-align: center"> 181-360 </th>
                        <th style="text-align: center"> > 360 </th>
                        <th style="text-align: center"> 总在途数 </th>
                        <th style="text-align: center"> 总采购中 </th>
                        <th style="text-align: center"> 总库存 </th>
                        <th style="text-align: center"> 总实时库存 </th>
                        <th style="text-align: center"> 总计 </th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>库存</td>
                        <td><i id="red" style="<?=empty($stock_details['less_thirty']) || $stock_details['less_thirty'] == 0 ? 'color:red' : ''?>"><?=empty($stock_details['less_thirty']) ? 0 : $stock_details['less_thirty']?></i></td>
                        <td><i id="red" style="<?=empty($stock_details['greater_thirty']) || $stock_details['greater_thirty'] == 0 ? 'color:red' : ''?>"><?=empty($stock_details['greater_thirty']) ? 0 : $stock_details['greater_thirty']?></i></td>
                        <td><i id="red" style="<?=empty($stock_details['greater_sixty']) || $stock_details['greater_sixty'] == 0 ? 'color:red' : ''?>"><?=empty($stock_details['greater_sixty']) ? 0 : $stock_details['greater_sixty']?></i></td>
                        <td><i id="red" style="<?=empty($stock_details['greater_ninety']) || $stock_details['greater_ninety'] == 0 ? 'color:red' : ''?>"><?=empty($stock_details['greater_ninety']) ? 0 : $stock_details['greater_ninety']?></i></td>
                        <td><i id="red" style="<?=empty($stock_details['greater_hundred_eighty']) || $stock_details['greater_hundred_eighty'] == 0 ? 'color:red' : ''?>"><?=empty($stock_details['greater_hundred_eighty']) ? 0 : $stock_details['greater_hundred_eighty']?></i></td>
                        <td><i id="red" style="<?=empty($stock_details['greater_three_hundred_sixty']) || $stock_details['greater_three_hundred_sixty'] == 0 ? 'color:red' : ''?>"><?=empty($stock_details['greater_three_hundred_sixty']) ? 0 : $stock_details['greater_three_hundred_sixty']?></i></td>
                        <td><i id="red" style="<?=empty($transit['num']) || $transit['num'] == 0 ? 'color:red' : ''?>"><?=empty($transit['num']) ? 0 : $transit['num']?></i></td>
                        <td><i id="red" style="<?=empty($purchasing['purchase_num']) || $purchasing['purchase_num'] == 0 ? 'color:red' : ''?>"><?=empty($purchasing['purchase_num']) ? 0 : $purchasing['purchase_num']?></i></td>
                        <td><i id="red" style="<?=empty($stock['num']) || $stock['num'] == 0 ? 'color:red' : ''?>"><?=empty($stock['num']) ? 0 : $stock['num']?></i></td>
                        <td><i id="red" style="<?=empty($stock['real_num']) || $stock['real_num'] == 0 ? 'color:red' : ''?>"><?=empty($stock['real_num']) ? 0 : $stock['real_num']?></i></td>
                        <td><i id="red" style="<?=empty($total['stock']) || $total['stock'] == 0 ? 'color:red' : ''?>"><?=empty($total['stock']) ? 0 : $total['stock']?></i></td>
                    </tr>
                    <tr>
                        <td>采购成本</td>
                        <td><i id="red" style="<?=empty($stock_details['less_thirty_price']) || $stock_details['less_thirty_price'] == 0 ? 'color:red' : ''?>"><?=empty($stock_details['less_thirty_price']) ? 0 : $stock_details['less_thirty_price']?></i></td>
                        <td><i id="red" style="<?=empty($stock_details['greater_thirty_price']) || $stock_details['greater_thirty_price'] == 0 ? 'color:red' : ''?>"><?=empty($stock_details['greater_thirty_price']) ? 0 : $stock_details['greater_thirty_price']?></i></td>
                        <td><i id="red" style="<?=empty($stock_details['greater_sixty']) || $stock_details['greater_sixty'] == 0 ? 'color:red' : ''?>"><?=empty($stock_details['greater_sixty_price']) ? 0 : $stock_details['greater_sixty_price']?></i></td>
                        <td><i id="red" style="<?=empty($stock_details['greater_ninety']) || $stock_details['greater_ninety'] == 0 ? 'color:red' : ''?>"><?=empty($stock_details['greater_ninety_price']) ? 0 : $stock_details['greater_ninety_price']?></i></td>
                        <td><i id="red" style="<?=empty($stock_details['greater_hundred_eighty']) || $stock_details['greater_hundred_eighty'] == 0 ? 'color:red' : ''?>"><?=empty($stock_details['greater_hundred_eighty_price']) ? 0 : $stock_details['greater_hundred_eighty_price']?></i></td>
                        <td><i id="red" style="<?=empty($stock_details['greater_three_hundred_sixty']) || $stock_details['greater_three_hundred_sixty'] == 0 ? 'color:red' : ''?>"><?=empty($stock_details['greater_three_hundred_sixty_price']) ? 0 : $stock_details['greater_three_hundred_sixty_price']?></i></td>
                        <td><i id="red" style="<?=empty($transit['goods_price']) || $transit['goods_price'] == 0 ? 'color:red' : ''?>"><?=empty($transit['goods_price']) ? 0 : $transit['goods_price']?></i></td>
                        <td><i id="red" style="<?=empty($purchasing['goods_price']) || $purchasing['goods_price'] == 0 ? 'color:red' : ''?>"><?=empty($purchasing['goods_price']) ? 0 : $purchasing['goods_price']?></i></td>
                        <td><i id="red" style="<?=empty($total_price) || $total_price == 0 ? 'color:red' : ''?>"><?=empty($total_price) ? 0 : round($total_price,2)?></i></td>
                        <td></td>
                        <td><i id="red" style="<?=empty($total['purchasing']) || $total['purchasing'] == 0 ? 'color:red' : ''?>"><?=empty($total['purchasing']) ? 0 : $total['purchasing']?></i></td>
                    </tr>
                    <?php if (in_array($warehouse_type,[WarehouseProvider::TYPE_PLATFORM,WarehouseProvider::TYPE_THIRD_PARTY])){?>
                        <tr>
                            <td>运费</td>
                            <td><i id="red" style="<?=empty($stock_freight['less_thirty_freight']) || $stock_freight['less_thirty_freight'] == 0 ? 'color:red' : ''?>"><?=empty($stock_freight['less_thirty_freight']) ? 0 : $stock_freight['less_thirty_freight']?></i></td>
                            <td><i id="red" style="<?=empty($stock_freight['greater_thirty_freight']) || $stock_freight['greater_thirty_freight'] == 0 ? 'color:red' : ''?>"><?=empty($stock_freight['greater_thirty_freight']) ? 0 : $stock_freight['greater_thirty_freight']?></i></td>
                            <td><i id="red" style="<?=empty($stock_freight['greater_sixty_freight']) || $stock_freight['greater_sixty_freight'] == 0 ? 'color:red' : ''?>"><?=empty($stock_freight['greater_sixty_freight']) ? 0 : $stock_freight['greater_sixty_freight']?></i></td>
                            <td><i id="red" style="<?=empty($stock_freight['greater_ninety_freight']) || $stock_freight['greater_ninety_freight'] == 0 ? 'color:red' : ''?>"><?=empty($stock_freight['greater_ninety_freight']) ? 0 : $stock_freight['greater_ninety_freight']?></i></td>
                            <td><i id="red" style="<?=empty($stock_freight['greater_hundred_eighty_freight']) || $stock_freight['greater_hundred_eighty_freight'] == 0 ? 'color:red' : ''?>"><?=empty($stock_freight['greater_hundred_eighty_freight']) ? 0 : $stock_freight['greater_hundred_eighty_freight']?></i></td>
                            <td><i id="red" style="<?=empty($stock_freight['greater_three_hundred_sixty_freight']) || $stock_freight['greater_three_hundred_sixty_freight'] == 0 ? 'color:red' : ''?>"><?=empty($stock_freight['greater_three_hundred_sixty_freight']) ? 0 : $stock_freight['greater_three_hundred_sixty_freight']?></i></td>
                            <td><i id="red" style="<?=empty($transit['freight_price']) || $transit['freight_price'] == 0 ? 'color:red' : ''?>"><?=empty($transit['freight_price']) ? 0 : $transit['freight_price']?></i></td>
                            <td><i id="red" style="color:red">0</i></td>
                            <td><i id="red" style="<?=empty($total_freight) || $total_freight == 0 ? 'color:red' : ''?>"><?=empty($total_freight) ? 0 : round($total_freight,2)?></i></td>
                            <td></td>
                            <td><i id="red" style="<?=empty($total['freight']) || $total['freight'] == 0 ? 'color:red' : ''?>"><?=empty($total['freight']) ? 0 : $total['freight']?></i></td>
                        </tr>
                    <?php }?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.7")?>