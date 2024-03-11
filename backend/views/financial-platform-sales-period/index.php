
<?php

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
                <blockquote class="layui-elem-quote quoteBox">
                    <div class="layui-inline">
                        <a class="layui-btn" id="createSale" data-type="url" data-title="添加" data-url="<?=Url::to(['financial-platform-sales-period/create?shop_id='.$searchModel['shop_id']])?>" data-callback_title = "demo列表" >添加</a>
                        <input id="oldUrl" type="hidden" value="/financial-platform-sales-period/create?shop_id=">
                    </div>
                </blockquote>
            </form>
            <form class="layui-form">
                <div class="lay-search" style="padding-left: 10px">
                    <div class="layui-inline">
                        <label>平台</label>
                        <?= \yii\helpers\Html::dropDownList('FinancialPlatformSalesPeriodSearch[platform_type]', $searchModel['platform_type'], \common\services\goods\GoodsService::$own_platform_type,
                            ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','id'=>'platform']); ?>
                    </div>
                    <div class="layui-inline">
                        <label>店铺名称</label>
                        <?= \backend\widgets\LinkageDropDownList::widget(['id' => 'shop','parent_id'=>'platform','name'=>'FinancialPlatformSalesPeriodSearch[shop_id]','select'=>$searchModel['shop_id'],'option'=> ShopService::getShopMapIndexPlatform(),'param'=>['data-placeholder' => '全部','prompt' => '全部','style'=>'width:180px']]) ?>
                    </div>
                    <div class="layui-inline">
                        <label>货币</label>
                        <?= \yii\helpers\Html::dropDownList('FinancialPlatformSalesPeriodSearch[currency]', $searchModel['currency'], $cun,
                            ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']); ?>
                    </div>
                    <div class="layui-inline">
                        <label>回款</label>
                        <?= \yii\helpers\Html::dropDownList('FinancialPlatformSalesPeriodSearch[payment_back]', $searchModel['payment_back'], \backend\controllers\FinancialPlatformSalesPeriodController::$PAYMENT_MAP,
                            ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']); ?>
                    </div>
                    <div class="layui-inline">
                        <label>异议</label>
                        <?= \yii\helpers\Html::dropDownList('FinancialPlatformSalesPeriodSearch[objection]', $searchModel['objection'], \backend\controllers\FinancialPlatformSalesPeriodController::$OBJECTION_MAP,
                            ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']); ?>
                    </div>
                    <div class="layui-inline">
                        <label>开始日期</label>
                        <input  class="layui-input search-con ys-date" name="FinancialPlatformSalesPeriodSearch[start_date]"  value="<?=$searchModel['start_date']?>"  id="start_date" autocomplete="off">
                    </div>
                    <div class="layui-inline">
                        <label>结束日期</label>
                        <input  class="layui-input search-con ys-date" name="FinancialPlatformSalesPeriodSearch[end_date]"  value="<?=$searchModel['end_date']?>"  id="end_date" autocomplete="off">
                    </div>
                    <div class="layui-inline layui-vertical-20" style="padding-top: 15px">
                        <input type="hidden" name="collection_id" value="<?=$collection_id?>">
                        <input type="hidden" name="collection_payment_back" value="<?=$collection_shop?>">
                        <button class="layui-btn" data-type="search_lists">搜索</button>
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
                    <div style="float: right">总销售金额：<i><?=$item[0]['sales_amount']?></i> 总退款金额：<i><?=$item[0]['refund_amount']?></i> 总异议金额：<i><?=$item[0]['objection_amount']?></i> 总佣金: <i><?=$item[0]['commission_amount']?></i>总促销活动:<i><?=$item[0]['promotions_amount']?></i> 总其他费用:<i><?= $item[0]['order_amount']?></i>总运费:<i><?= $item[0]['freight']?></i>总退款佣金:<i><?= $item[0]['refund_commission_amount']?></i>总广告费用:<i><?= $item[0]['advertising_amount']?></i>总取消费用:<i><?= $item[0]['cancellation_amount']?></i>总商品服务费用:<i><?= $item[0]['goods_services_amount']?></i>总手续费:<i><?= $item[0]['premium']?></i>未回款金额:<i><?= $item[0]['payment_amount_no']?></i>总回款金额:<i><?=  $item[0]['payment_amount']?></i> </div>
                </div>
                <?php if (!empty($collection_shop)){?>
                    <div class="layui-form">
                        <div class="layui-inline">
                            <a class="layui-btn layui-btn-sm layui-btn-normal js-batch" data-title="批量回款" data-url="<?=Url::to(['collection/batch-collection?collection_id='.$collection_shop])?>" style="margin-top: 10px;">批量回款</a>
                        </div>
                    </div>
                <?php }?>
                <div class="layui-form">
                    <table class="layui-table" style="text-align: center">
                        <thead>
                        <tr>
                            <th style="width: 30px"><input type="checkbox" lay-filter="select_all" id="select_all" name="select_all" lay-skin="primary" title=""></th>
                            <th>id</th>
                            <th>店铺</th>
                            <th>日期</th>
                            <th>金额</th>
                            <th>回款</th>
                            <?php if (!empty($collection_shop)){?>
                            <th>回款表金额</th>
                            <?php }?>
                            <th>货币</th>
                            <th>是否有异议</th>
                            <th>是否回款</th>
                            <th>备注</th>
                            <th><span>操作</span></th>
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
                                <td><input type="checkbox" class="select_collection" name="sales_id[]" value="<?=$v['id']?>" lay-skin="primary" title=""></td>
                                <td><?= $v['id'] ?></td>
                                <td><?= $v['platform_type'] ?><br>
                                <a class="layui-btn layui-btn-xs layui-btn-a" data-width="1100px" data-height="425px" data-type="open" data-url="<?=Url::to(['shop/view'])?>?shop_id=<?=$v['shop_id']?>&sales_id=<?=$v['id']?>" data-title="店铺信息" style="color: #00a0e9;font-size: 13px"><?= $v['shop'] ?></a>
                                </td>
                                <td align="left""><?= date('Y-m-d',$v['data'])?>-<?= date('Y-m-d',$v['stop_data'])?></td>
                                <td align="left"">
                                <table id="order">
                                <tr><td >
                                销售金额：<i id="red"><?= $v['sales_amount'] ?>&nbsp;</i> </td><td>佣金：<i id="red"><?=$v['commission_amount']?>&nbsp;</i></td><td>运费：<i id="red"><?=$v['freight']?>&nbsp;</i></td>
                                </tr><tr><td>
                                退款金额：<i id="red"><?= $v['refund_amount'] ?>&nbsp;</i></td><td>退款佣金：<i id="red"><?=$v['refund_commission_amount']?>&nbsp;</i></td><td> 取消费用：<i id="red"><?=$v['cancellation_amount']?>&nbsp;</i></td>
                                    </tr><tr><td>
                                广告费用：<i id="red"><?=$v['advertising_amount']?>&nbsp;</i></td><td>商品服务费：<i id="red"><?=$v['goods_services_amount']?>&nbsp;</i></td><td>促销活动:<i id="red"><?=$v['promotions_amount']?>&nbsp;</i></td>
                                    </tr><tr><td>
                                            其他费用：<i id="red"><?=$v['order_amount']?></i></td><td>手续费：<i id="red"><?=$v['premium']?></i></td><td>异议费用：<i id="red"><?=$v['objection_amount']?></i></td></tr>
                                </table>
                                </td>
                                <td>
                                    <?php if ($v['payment_amount_original'] == $v['payment_amount']){?>
                                            <?=$v['payment_amount_original']?>
                                    <?php }else{ ?>
                                        <p style="text-decoration: line-through"><?=$v['payment_amount_original']?></p>
                                        <?=$v['payment_amount']?>
                                    <?php }?>
                                </td>
                                <?php if (!empty($collection_shop)){?>
                                <td><?=$collection['collection_amount']?></td>
                                <?php }?>
                                <td><?=$v['currency']?></td>
                                <td><?php if($v['objection']==\backend\controllers\FinancialPlatformSalesPeriodController::OBJECTION_YES){?>是<?php }else{?>否 <?php }?></td>
                                <td><?php if($v['payment_back']==1){?>是<?php }else{?>否 <?php }?><br/>
                                    <?=$v['collection_time']?>
                                </td>
                                <td><?=$v['remark']?></td>
                                <td>
                                    <a class="layui-btn layui-btn-normal layui-btn-xs" data-type="url"  data-url="<?=Url::to(['financial-platform-sales-period/update?id='.$v['id']])?>" data-title="编辑" data-callback_title="账期列表">编辑</a>
                                    <a class="layui-btn layui-btn layui-btn-xs" data-type="url"  data-url="<?=Url::to(['financial-period-rollover/mindex?id='.$v['id']])?>" data-title="查看流水" data-callback_title="账期列表">查看流水</a>
                                    <?php if($v['count']==0){ ?>
                                    <a class="layui-btn layui-btn-danger layui-btn-xs" data-type="operating" data-url="<?=Url::to(['financial-platform-sales-period/delete?id='.$v['id']])?>" data-title="删除">删除</a>
                                    <?php }?>
                                    <?php if($v['objection']==\backend\controllers\FinancialPlatformSalesPeriodController::OBJECTION_NO){ ?>
                                        <a class="layui-btn layui-btn-normal layui-btn-xs" data-type="open" data-url="<?=Url::to(['financial-platform-sales-period/change?id='.$v['id']])?>" data-height="300px" data-title="异议" data-callback_title="账期列表">异议</a>
                                    <?php }else{?>
                                    <a class="layui-btn layui-btn-normal layui-btn-xs" data-type="open" data-url="<?=Url::to(['financial-platform-sales-period/change-again?id='.$v['id']])?>" data-height="300px" data-title="取消异议" data-callback_title="账期列表">取消异议</a>
                                    <?php } ?>
                                    <p>
                                        <?php if (!empty($collections)){ ?>
                                            <?php if (empty($collection_shop)){ ?>
                                                <a class="layui-btn layui-btn-danger layui-btn-xs collection_operating"  data-url="<?=Url::to(['collection/collection-status?sale_id='.$v['id'].'&collection_id='.$collections])?>" data-title="取消回款" data-callback_title="账期列表">取消回款</a>
                                            <?php }else{ ?>
                                                <a class="layui-btn layui-btn-normal layui-btn-xs collection_operating"  data-url="<?=Url::to(['collection/collection-status?sale_id='.$v['id'].'&collection_id='.$collections])?>" data-title="回款" data-callback_title="账期列表">回款</a>
                                            <?php }?>
                                        <?php }else{ ?>
                                            <a class="layui-btn layui-btn-normal layui-btn-xs" data-type="open" data-width="500px" data-height="500px" data-url="<?=Url::to(['financial-platform-sales-period/collection?id='.$v['id']])?>" data-title="回款" data-callback_title="账期列表">回款</a>
                                        <?php }?>
                                        <a class="layui-btn layui-btn-warm layui-btn-xs" data-type="open" data-width="470px" data-height="300px" data-url="<?=Url::to(['financial-platform-sales-period/amount-adjust?id='.$v['id']])?>" data-title="回款调整" data-callback_title="回款调整">回款调整</a>
                                    </p>
                                </td>
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
<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.5")?>
<?=$this->registerJsFile("@adminPageJs/financial-platform-sales-period/lists.js?v=".time())?>
<?=$this->registerJsFile("@adminPageJs/collection/lists.js?v=".time())?>

