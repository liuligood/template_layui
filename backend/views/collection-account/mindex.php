
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
                <div class="lay-search" style="padding-left: 10px">
                    <div class="layui-inline">
                        <label>收款账号</label>
                        <input class="layui-input search-con" name="CollectionAccountSearch[collection_account]" value="<?=$searchModel['collection_account']?>" autocomplete="off">
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
                </div>
            </div>
            <div class="layui-form">
                <table class="layui-table" style="text-align: center">
                    <thead>
                    <tr>
                        <th>收款账号</th>
                        <th>收款平台</th>
                        <th>币种</th>
                        <th>金额</th>
                        <th>操作</th>
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
                            <td><?=$v['collection_account']?></td>
                            <td><?=$v['collection_platform']?></td>
                            <td><?=$v['currency']?></td>
                            <td><?=$v['money']?></td>
                            <td>
                                <div class="lay-lists">
                                <a class="layui-btn layui-btn-xs" data-type="open" data-width="650px" data-height="400px" data-url="<?=Url::to(['collection-transaction-log/withdrawal?id='.$v['id']])?>" data-title="提现" data-callback_title="收款账号货币列表">提现</a>
                                <a class="layui-btn layui-btn-xs" data-type="open" data-width="650px" data-height="400px" data-url="<?=Url::to(['collection-transaction-log/admin?id='.$v['id']])?>" data-title="调整金额" data-callback_title="收款账号货币列表">调整金额</a>
                                <a class="layui-btn layui-btn-normal layui-btn-xs" data-type="url" data-url="<?=Url::to(['collection-transaction-log/index?collection_currency_id='.$v['id']])?>" data-title="交易流水" data-callback_title="收款账号货币列表">查看流水</a>
                                </div>
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
<?php
$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.4.1");
?>