
<?php

use common\models\FinancialPeriodRollover;
use common\services\ShopService;
use yii\helpers\Url;
?>
<style>
    .layui-table-cell {
        height:auto;}
    i{
        color: red;
    }
    #red{
        color: red;
    }
    #order td{
        border: 0px;
        padding:0px;
    }
    .layui-card {
        padding: 10px 15px;
    }
    .layui-laypage li{
        float: left;
    }
    .layui-laypage li{
        float: left;
    }
    .layui-laypage .active a{
        background-color: #009688;
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
        color: rgb(0, 150, 136);
    }
    .layui-btn-a {
        border: none;
        background-color: rgba(0, 0, 0, 0);
        -webkit-user-select:text;
    }
    .span-circular-red{
        display: inline-block;
        min-width: 16px;
        height: 16px;
        border-radius: 50%;
        background-color: #ff6666;
        color: #fff;
        text-align: center;
        padding: 5px;
        line-height: 16px;
    }
    .span-circular-grey{
        display: inline-block;
        min-width: 16px;
        height: 16px;
        border-radius: 50%;
        background-color: #999999;
        color: #fff;
        text-align: center;
        padding: 5px;
        line-height: 16px;
    }
    .span-circular-blue{
        display: inline-block;
        min-width: 16px;
        height: 16px;
        border-radius: 80%;
        background-color: #3b97d7;
        color: #fff;
        text-align: center;
        padding: 5px;
        line-height: 16px;
    }
    .span-goode-name{
        color:#a0a3a6;
        font-size: 13px;
    }
    .layui-table .layui-btn{
        margin-bottom: 3px;
    }
</style>
<div class="layui-fluid">
    <div class="layui-card">
        <div class="lay-lists">

            <form class="layui-form">
                <div class="lay-search" style="padding-left: 10px">
                    <div class="layui-inline">
                        <label>操作</label>
                        <?= \yii\helpers\Html::dropDownList('FinancialPeriodRolloverSearch[operation]', $searchModel['operation'], \common\services\financial\PlatformSalesPeriodService::$OPREATION_MAP,
                            ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']); ?>
                    </div>
                    <div class="layui-inline">
                        <label>操作类型</label>
                        <input class="layui-input search-con" name="FinancialPeriodRolloverSearch[operation_value]" value="<?=$searchModel['operation_value']?>" autocomplete="off">
                    </div>
                    <div class="layui-inline">
                        <label>流水订单号</label>
                        <input class="layui-input search-con" name="FinancialPeriodRolloverSearch[relation_no]" value="<?=$searchModel['relation_no']?>" autocomplete="off">
                    </div>
                    <div class="layui-inline">
                        <label>金额</label>
                        <input class="layui-input search-con" name="FinancialPeriodRolloverSearch[amount]" value="<?=$searchModel['amount']?>" autocomplete="off" style="width: 150px">
                    </div>
                    <div class="layui-inline">
                        <label>SKU</label>
                        <input class="layui-input search-con" name="FinancialPeriodRolloverSearch[sku_no]" value="<?=$searchModel['sku_no']?>" autocomplete="off" style="width: 150px;margin-top: 3px">
                    </div>
                    <?php if (empty($sale_id)){?>
                        <div class="layui-inline">
                            <label>平台</label>
                            <?= \yii\helpers\Html::dropDownList('FinancialPeriodRolloverSearch[platform_type]', $searchModel['platform_type'], \common\services\goods\GoodsService::$own_platform_type,
                                ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','id'=>'platform']); ?>
                        </div>
                        <div class="layui-inline">
                            <label>店铺名称</label>
                            <?= \backend\widgets\LinkageDropDownList::widget(['id' => 'shop','parent_id'=>'platform','name'=>'FinancialPeriodRolloverSearch[shop_id]','select'=>$searchModel['shop_id'],'option'=> ShopService::getShopMapIndexPlatform(),'param'=>['data-placeholder' => '全部','prompt' => '全部','style'=>'width:180px']]) ?>
                        </div>
                    <?php }?>
                    <div class="layui-inline">
                        出账时间：
                        <input class="layui-input search-con ys-date" name="FinancialPeriodRolloverSearch[start_date]" value="<?=$searchModel['start_date']?>" id="start_date" autocomplete="off">
                    </div>
                    <span class="layui-inline layui-vertical-20">
                        -
                    </span>
                    <div class="layui-inline layui-vertical-20">
                        <input  class="layui-input search-con ys-date" name="FinancialPeriodRolloverSearch[end_date]" value="<?=$searchModel['end_date']?>" id="end_date" autocomplete="off">
                    </div>
                    <?php if (empty($sale_id)){?>
                        <div class="layui-inline">
                            回款时间：
                            <input  class="layui-input search-con ys-date" name="FinancialPeriodRolloverSearch[start_collection_time]" value="<?=$searchModel['start_collection_time']?>" id="start_collection_time" autocomplete="off">
                        </div>
                        <span class="layui-inline layui-vertical-20">
                            -
                        </span>

                        <div class="layui-inline layui-vertical-20">
                            <input  class="layui-input search-con ys-date" name="FinancialPeriodRolloverSearch[end_collection_time]" value="<?=$searchModel['end_collection_time']?>" id="end_collection_time" autocomplete="off">
                        </div>
                    <?php }?>
                    <div class="layui-inline layui-vertical-20" style="padding-top: 15px">
                        <button class="layui-btn" data-type="search_lists">搜索</button>
                        <?php if (!empty($sale_id)){?>
                            <input value="<?=$sale_id?>" name="id" type="hidden">
                            <input  type="hidden" name="FinancialPeriodRolloverSearch[shop_id]"   value="<?= $searchModel['shop_id']?>"   autocomplete="off">
                            <input  type="hidden" name="FinancialPeriodRolloverSearch[id]"   value="<?= $searchModel['id']?>"   autocomplete="off">
                            <button class="layui-btn layui-btn-primary ys-uploadtwo" lay-data="{accept: 'file'}">导入</button>
                            <button class="layui-btn diaoyon"  data-url="<?=Url::to(['financial-platform-sales-period/delect?id='.$searchModel['id']])?>" >清空流水</button>
                            <button class="layui-btn diaoyon"  data-url="<?=Url::to(['financial-period-rollover/restart?id='.$searchModel['id']])?>" >更新数据</button>
                            <a class="layui-btn" data-type="url" data-title="添加" data-url="<?=Url::to(['financial-period-rollover/create-rollover?id='.$searchModel['id'].'&shop_id='.$searchModel['shop_id']])?>">添加流水</a>
                        <?php }?>
                    </div>
                </div>
            </form>

            <div>
                <?php
                $startCount = (0 == $pages->totalCount) ? 0 : $pages->offset + 1;
                $endCount = ($pages->page + 1) * $pages->pageSize;
                $endCount = ($endCount > $pages->totalCount) ? $pages->totalCount : $endCount;
                ?>
                <div class="summary" style="margin-top: 10px;">
                        第<b><?= $startCount ?>-<?= $endCount ?></b>条，共<b><?= $pages->totalCount ?></b>条数据
                    <?php if (empty($list)){?>
                        <div style="float: right">总销售金额：<i>0</i> 总退款金额：<i>0</i> 总佣金: <i>0</i>总促销活动:<i>0</i> 总其他费用:<i>0</i>总运费:<i>0</i>总退款佣金:<i>0</i>总广告费用:<i>0</i>总取消费用:<i>0</i>总商品服务费用:<i>0</i>总手续费:<i>0</i></i>未回款金额:<i>0</i>总回款金额:<i>0</i> </div>
                    <?php }else{?>
                        <div class="summary" style="margin-top: 10px;">
                            <div style="float: right">总销售金额：<i><?=$total['sales_amount']?></i> 总退款金额：<i><?=$total['refund_amount']?></i> 总佣金: <i><?=$total['commission_amount']?></i>总促销活动:<i><?=$total['promotions_amount']?></i> 总其他费用:<i><?= $total['order_amount']?></i>总运费:<i><?= $total['freight']?></i>总退款佣金:<i><?= $total['refund_commission_amount']?></i>总广告费用:<i><?= $total['advertising_amount']?></i>总取消费用:<i><?= $total['cancellation_amount']?></i>总异议费用：<i><?= $total['objection_amount']?></i>总商品服务费用:<i><?= $total['goods_services_amount']?></i>总手续费:<i><?= $total['premium']?></i>未回款金额:<i><?= $total['payment_amount_no']?></i>总回款金额:<i><?=  $total['payment_amount']?></i> </div>
                        </div>
                    <?php }?>
                </div>
            </div>
            <div class="layui-form">
                <table class="layui-table" style="text-align: center">
                    <thead>
                    <tr>
                        <th>流水订单号</th>
                        <?php if (empty($sale_id)){?>
                        <th>平台</th>
                        <th>店铺</th>
                        <?php }?>
                        <th>操作类型</th>
                        <th>金额</th>
                        <th>出账时间</th>
                        <th>回款时间</th>
                        <th>交易流水号</th>
                        <th>操作人消息</th>
                        <th>操作单消息</th>
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
                            <td><?=$v['relation_no'] ?></td>
                            <?php if (empty($sale_id)){?>
                                <td><?= $v['platform_type'] ?></td>
                                <td><?= $v['shop_id'] ?></td>
                            <?php }?>
                            <?php if(!(\common\services\financial\PlatformSalesPeriodService::findMap($v['operation']))){ ?>
                            <td align="left""><?=$v['operation'] ?></td>
                            <?php }else{?>
                            <td align="left""><?=\common\services\financial\PlatformSalesPeriodService::$OPREATION_MAP[(\common\services\financial\PlatformSalesPeriodService::findMap($v['operation']))]?>
                                (<?=$v['operation'] ?>)
                                <?php if ($v['is_manual'] == FinancialPeriodRollover::MANUAL){?>
                                <span style="float: right">
                                    <a class="layui-btn layui-btn-danger layui-btn-xs" data-type="operating" data-title="删除" data-url="<?=Url::to(['financial-period-rollover/delete-manual?id='.$v['id'].'&fin_id='.$searchModel['id']])?>">删除</a>
                                </span>
                                <?php }?>
                            </td>
                            <?php }?>
                            <td align="left""><?=$v['amount'] ?></td>
                            <td style="width: 80px"><?=$v['date']?></td>
                            <td style="width: 80px"><?=$v['collection_time']?></td>
                            <td><?=$v['identifier']?></td>
                            <td style="width: 180px;white-space: normal;word-break: break-all;"><?=$v['buyer']?></td>
                            <td  style="width: 180px;white-space: normal;word-break: break-all;"><?=$v['offer']?></td>
                        </tr>
                    <?php endforeach;?>
                    <?php
                    endif;
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?= \yii\widgets\LinkPager::widget(['pagination' => $pages,'options' => ['class' => 'layui-box layui-laypage layui-laypage-default'],]) ?>
    </div>
</div>
</div>
<script>
    var shop=<?=empty($searchModel['shop_id'])? 0 : $searchModel['shop_id']?>;
    var fin=<?=empty($searchModel['id'])? 0 : $searchModel['id']?>;
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.5")?>
<?=$this->registerJsFile("@adminPageJs/financial-period-rollover/index.js")?>
