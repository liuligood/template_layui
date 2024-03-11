
<?php

use common\models\RevenueExpenditureAccount;
use common\models\RevenueExpenditureLog;
use common\models\RevenueExpenditureType;
use common\models\Shop;
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
                        <a class="layui-btn" data-type="url" data-title="添加" data-url="<?=Url::to(['revenue-expenditure-log/create'])?>" data-callback_title = "revenue-expenditure-log列表" >添加收支账号明细</a>                    </div>
                </blockquote>
            </form>
            <form class="layui-form">
                <div class="lay-search" style="padding-left: 10px">
                    <div class="layui-inline">
                        <label>收支账号</label>
                        <?= \yii\helpers\Html::dropDownList('RevenueExpenditureLogSearch[revenue_expenditure_account_id]', $searchModel['revenue_expenditure_account_id'], RevenueExpenditureAccount::getAllAccount(),
                            ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']); ?>
                    </div>

                    <div class="layui-inline">
                        <label>收支类型</label>
                        <?= \yii\helpers\Html::dropDownList('RevenueExpenditureLogSearch[revenue_expenditure_type]', $searchModel['revenue_expenditure_type'], RevenueExpenditureType::getAllType(),
                            ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']); ?>
                    </div>

                    <div class="layui-inline">
                        <label>转账</label>
                        <?= \yii\helpers\Html::dropDownList('RevenueExpenditureLogSearch[payment_back]', $searchModel['payment_back'], RevenueExpenditureLog::$payment_back_maps,
                            ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:120px']); ?>
                    </div>

                    <div class="layui-inline">
                        <label>核查</label>
                        <?= \yii\helpers\Html::dropDownList('RevenueExpenditureLogSearch[examine]', $searchModel['examine'], RevenueExpenditureLog::$examine_maps,
                            ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:120px']); ?>
                    </div>

                    <div class="layui-inline">
                        <label>操作者</label>
                        <?= \yii\helpers\Html::dropDownList('RevenueExpenditureLogSearch[admin_id]', $searchModel['admin_id'], Shop::adminArr(),
                            ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']); ?>
                    </div>

                    <div class="layui-inline">
                        <label>开始日期</label>
                        <input  class="layui-input search-con ys-date" name="RevenueExpenditureLogSearch[start_time]"  value="<?=$searchModel['start_time']?>"  id="start_time" autocomplete="off">
                    </div>

                    <div class="layui-inline">
                        <label>结束日期</label>
                        <input  class="layui-input search-con ys-date" name="RevenueExpenditureLogSearch[end_time]"  value="<?=$searchModel['end_time']?>"  id="end_time" autocomplete="off">
                    </div>

                    <div class="layui-inline layui-vertical-20" style="padding-top: 15px">
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
                    第<b><?= $startCount ?>-<?= $endCount ?></b>条，共<b><?= $pages->totalCount ?></b>条数据
                    <div style="float: right">总收入：<i><?=$earn?></i>  总支出：<i><?=$lose?></i> 银行卡余额：<i><?=$bank_balance?></i></div>
                </div>
                <div class="layui-form">
                    <table class="layui-table" style="text-align: center">
                        <thead>
                        <tr>
                            <th style="width: 30px">id</th>
                            <th>记账日期</th>
                            <th>变动金额</th>
                            <th>报销人</th>
                            <th>余额</th>
                            <th>收支类型</th>
                            <th>摘要</th>
                            <th>图片</th>
                            <th>收支账号</th>
                            <th>转账</th>
                            <th>核查</th>
                            <th>操作者</th>
                            <th>创建时间</th>
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
                                <td><?=$v['id']?></td>
                                <td><?=$v['date']?></td>
                                <td>
                                    <?php if ($v['money'] >= 0){?>
                                        <span style="color: #00a2d4"><?=$v['money']?></span>
                                    <?php }else{?>
                                        <span style="color: red"><?=$v['money']?></span>
                                    <?php }?>
                                </td>
                                <td><?=$v['reimbursement_id']?></td>
                                <td><?=$v['total_amount']?></td>
                                <td><?=$v['revenue_expenditure_type']?></td>
                                <td><?=$v['desc']?></td>
                                <td>
                                    <?php if(!empty($v['img'])){?>
                                    <?php foreach ($v['img'] as $s){?>
                                        <a href="<?=$s['img']?>" data-lightbox="pic">
                                            <img class="layui-circle" src="<?=$s['img']?>?imageView2/2/h/100" width="25" style="margin-right: 8px"/>
                                        </a>
                                    <?php }?>
                                    <?php } ?>
                                </td>
                                <td><?=$v['revenue_expenditure_account']?></td>
                                <td><?=$v['payment_back_status']?></td>
                                <td><?=$v['examine']?></td>
                                <td><?=$v['admin_id']?></td>
                                <td><?=$v['add_time']?></td>
                                <td>
                                    <?php if ($v['payment_back'] == 20){?>
                                        <a class="layui-btn layui-btn-normal layui-btn-xs" data-type="url"  data-url="<?=Url::to(['revenue-expenditure-log/update?id='.$v['id']])?>" data-title="编辑" data-callback_title="revenue-expenditure-log列表">编辑</a>
                                        <a class="layui-btn layui-btn-danger layui-btn-xs" data-type="operating" data-title="删除" data-url="<?=Url::to(['revenue-expenditure-log/delete?id='.$v['id']])?>">删除</a>
                                    <?php }else{?>
                                        <a class="layui-btn layui-btn-warm layui-btn-xs" data-type="operating" data-title="修改核查状态" data-url="<?=Url::to(['revenue-expenditure-log/payment-status?id='.$v['id']])?>">核查</a>
                                    <?php }?>
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
<?php
$this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
?>
