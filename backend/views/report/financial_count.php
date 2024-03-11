
<?php
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
        <div class="lay-lists">
            <form class="layui-form" style="padding:15px">
                <div class="lay-search" style="padding-left: 10px">
                    <div class="layui-inline">
                        <label>日期</label>
                        <input  class="layui-input search-con ys-date" name="start_date" value="<?=$searchModel['start_date'];?>"  id="start_date" autocomplete="off">
                    </div>
                    <span class="layui-inline layui-vertical-20">
        -
    </span>
                    <div class="layui-inline layui-vertical-20">
                        <input  class="layui-input search-con ys-date" name="end_date" value="<?=$searchModel['end_date'];?>" id="end_date" autocomplete="off">
                    </div>
                    <div class="layui-inline">
                        <label>平台</label>
                        <?= \yii\helpers\Html::dropDownList('platform_type', $searchModel['platform_type'], \common\services\goods\GoodsService::$own_platform_type,
                            ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']); ?>
                    </div>
                    <div class="layui-inline layui-vertical-20">
                        <button class="layui-btn" data-type="search_lists">搜索</button>
                    </div>
                    <?php
                    $b = date("Y-m-d");
                    $a = date("Y-m-d",strtotime("-1 day"));
                    ?>
                </div>
            </form>
        </div>
        <div style="padding-bottom: 15px">
            <div class="layui-row">
                <div style="background:#FAFAFA;margin: 5px;padding: 10px;border:1px solid #eee">
                    <h3 style="font-weight: bold;line-height: 35px"><?=$name?></h3>
                <table id="order" style="font-size: 18px;text-align: center;height:110px;margin: 0 auto;color: black">
                    <tr><td >
                            总销售金额：<i id="red"><?=$all_sales_amount?>&nbsp;</i> </td><td>总佣金：<i id="red"><?=$all_commission_amount?>&nbsp;</i></td><td>总运费：<i id="red"><?=$all_freight?>&nbsp;</i></td>
                    </tr><tr><td>
                            总退款金额：<i id="red"><?=$all_refund_amount?>&nbsp;</i></td><td>总退款佣金：<i id="red"><?=$all_refund_commission_amount?>&nbsp;</i></td><td> 总取消费用：<i id="red"><?=$all_cancellation_amount?>&nbsp;</i></td>
                    </tr><tr><td>
                            总广告费用：<i id="red"><?=$all_advertising_amount?>&nbsp;</i></td><td>总商品服务费：<i id="red"><?=$all_goods_services_amount?>&nbsp;</i></td><td>总促销活动:<i id="red"><?=$all_promotions_amount?>&nbsp;</i></td>
                    </tr><tr><td>
                            总其他费用：<i id="red"><?=$all_order_amount?></i></td><td>总手续费：<i id="red"><?=$all_premium?></i></td>
                    </tr><tr><td>
                            总回款：<i id="red"><?=$all_payment_amount?></i></td><td>总未回款：<i id="blue"><?=$all_payment_amount_no?></i></td></tr>
                </table>
                </div>
            </div>
        </div>
        <div class="layui-row">
            <?php foreach ($financial_count as $v){?>
                <div class="layui-col-md3">
                    <div style="background:#FAFAFA;margin: 5px;padding: 10px;border:1px solid #eee">
                        <?php if($py == 1){ ?>
                        <h3 style="font-weight: bold;line-height: 35px"><?= Base::$platform_maps[$v['platform_type']]?></h3>
                        <?php }else{?>
                        <h3 style="font-weight: bold;line-height: 35px"><?=$shop_map[$v['shop_id']]?></h3>
                        <?php }?>
                        <div style="height: 230px">
                            <table id="order" style="font-size: 16px;text-align: center;height:110px;margin: 0 auto;color: black">
                                <tr><td >
                                        销售金额：<br><i id="red"><?=$v['sales_amount']?>&nbsp;</i> </td><td>佣金：<br><i id="red"><?=$v['commission_amount']?>&nbsp;</i></td><td>运费：<br><i id="red"><?=$v['freight']?>&nbsp;</i></td>
                                </tr><tr><td>
                                        退款金额：<br><i id="red"><?=$v['refund_amount']?>&nbsp;</i></td><td>退款佣金：<br><i id="red"><?=$v['refund_commission_amount']?>&nbsp;</i></td><td> 取消费用：<br><i id="red"><?=$v['cancellation_amount']?>&nbsp;</i></td>
                                </tr><tr><td>
                                        广告费用：<br><i id="red"><?=$v['advertising_amount']?>&nbsp;</i></td><td>商品服务费：<br><i id="red"><?=$v['goods_services_amount']?>&nbsp;</i></td><td>促销活动:<br><i id="red"><?=$v['promotions_amount']?>&nbsp;</i></td>
                                </tr><tr><td>
                                        其他费用：<br><i id="red"><?=$v['order_amount']?></i></td><td>手续费：<br><i id="red"><?=$v['premium']?></i></td>
                                </tr><tr><td>
                                        回款：<br><i id="red"><?=$v['payment_amount']?></i></td><td>未回款：<br><i id="blue"><?=$v['payment_amount_no']?></i></td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            <?php }?>
        </div>
    </div>
</div>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.7")?>