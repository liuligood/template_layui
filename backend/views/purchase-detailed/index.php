
<?php

use common\components\statics\Base;
use common\models\purchase\PurchaseDetailed;
use common\models\Shop;
use yii\bootstrap\Html;
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

                    <div class="layui-inline" style="width: 170px">
                        采购订单号:
                        <textarea name="PurchaseDetailedSearch[relation_no]" class="layui-textarea search-con" style="height: 39px; min-height:39px"><?=$searchModel['relation_no'];?></textarea>
                    </div>

                    <div class="layui-inline" style="width: 150px">
                        供应商：
                        <?= Html::dropDownList('PurchaseDetailedSearch[source]',$searchModel['source'],Base::$purchase_source_maps,
                            ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search' ]) ?>
                    </div>

                    <div class="layui-inline" style="width: 150px">
                        状态：
                        <?= Html::dropDownList('PurchaseDetailedSearch[status]',$searchModel['status'],PurchaseDetailed::$status_maps,
                            ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search' ]) ?>
                    </div>

                    <div class="layui-inline">
                        订单创建时间
                        <input class="layui-input search-con ys-date" name="PurchaseDetailedSearch[start_create_date]" value="<?=$searchModel['start_create_date'];?>" id="start_create_date" autocomplete="off">
                    </div>
                    <span class="layui-inline layui-vertical-20">
                            -
                    </span>
                    <div class="layui-inline layui-vertical-20">
                        <input class="layui-input search-con ys-date" name="PurchaseDetailedSearch[end_create_date]" value="<?=$searchModel['end_create_date'];?>" id="end_create_date" autocomplete="off">
                    </div>

                    <div class="layui-inline">
                        付款时间
                        <input class="layui-input search-con ys-date" name="PurchaseDetailedSearch[start_deiburse_date]" value="<?=$searchModel['start_deiburse_date'];?>" id="start_deiburse_date" autocomplete="off">
                    </div>
                    <span class="layui-inline layui-vertical-20">
                            -
                    </span>
                    <div class="layui-inline layui-vertical-20">
                        <input class="layui-input search-con ys-date" name="PurchaseDetailedSearch[end_deiburse_date]" value="<?=$searchModel['end_deiburse_date'];?>" id="end_deiburse_date" autocomplete="off">
                    </div>

                    <div class="layui-inline layui-vertical-20" style="padding-top: 15px">
                        <button class="layui-btn" data-type="search_lists">搜索</button>
                        <button class="layui-btn layui-btn-primary ys-upload" lay-data="{url: '/purchase-detailed/import-relation/',accept: 'file'}">导入</button>
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
                </div>
                <div class="layui-form">
                    <table class="layui-table" style="text-align: center">
                        <thead>
                        <tr>
                            <th>供应商</th>
                            <th>采购订单号</th>
                            <th>实付金额</th>
                            <th>商品金额</th>
                            <th>运费</th>
                            <th>公司</th>
                            <th>状态</th>
                            <th>订单创建时间</th>
                            <th>付款时间</th>
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
                                <td><?=$v['source']?></td>
                                <td><?=$v['relation_no']?></td>
                                <td><?=$v['disburse_amount']?></td>
                                <td><?=$v['goods_amount']?></td>
                                <td><?=$v['freight']?></td>
                                <td><?=$v['company']?></td>
                                <td><?=$v['status']?></td>
                                <td><?=$v['create_date']?></td>
                                <td><?=$v['deiburse_date']?></td>
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
