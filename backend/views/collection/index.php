
<?php

use common\models\financial\Collection;
use common\services\goods\GoodsService;
use common\services\ShopService;
use yii\helpers\Html;
use yii\helpers\Url;
?>
<style>
    .layui-card {
    padding: 10px 15px;
    }
</style>
<div class="layui-fluid">
    <div class="layui-card">
        <div class="lay-lists">
            <form class="layui-form">
                <blockquote class="layui-elem-quote quoteBox">
                    <div class="layui-inline">
                        <a class="layui-btn" data-type="url" data-title="添加回款" data-url="<?=Url::to(['collection/create'])?>" data-callback_title = "collection列表" >添加回款</a>
                    </div>
                </blockquote>
            </form>
            <div class="lay-search" style="padding-left: 10px">

                <div class="layui-inline">
                    <label>回款银行卡</label>
                    <input class="layui-input search-con" name="CollectionSearch[collection_bank_cards]" autocomplete="off">
                </div>

                <div class="layui-inline">
                    <label>回款账号</label>
                    <input class="layui-input search-con" name="CollectionSearch[collection_account]" autocomplete="off">
                </div>

                <div class="layui-inline layui-vertical-20" style="width: 120px">
                    <label>平台</label>
                    <?= Html::dropDownList('CollectionSearch[platform_type]', null,GoodsService::$own_platform_type,
                        ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','id'=>'platform']); ?>
                </div>

                <div class="layui-inline layui-vertical-20">
                    <label>店铺名称</label>
                    <?= \backend\widgets\LinkageDropDownList::widget(['id' => 'shop','parent_id'=>'platform','name'=>'CollectionSearch[shop_id]','select'=>null,'option'=> ShopService::getShopMapIndexPlatform(),'param'=>['data-placeholder' => '全部','prompt' => '全部','style'=>'width:180px']]) ?>
                </div>

                <div class="layui-inline layui-vertical-20" style="width: 120px">
                    <label>状态</label>
                    <?= Html::dropDownList('CollectionSearch[status]', null,Collection::$status_maps,
                        ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search' ]) ?>
                </div>

                <div class="layui-inline">
                    回款日期
                    <input  class="layui-input search-con ys-date" name="CollectionSearch[start_collection_date]" id="start_collection_date" autocomplete="off">
                </div>
                <span class="layui-inline layui-vertical-20">
                    <br>
                    -
                </span>
                <div class="layui-inline layui-vertical-20">
                    <br>
                    <input  class="layui-input search-con ys-date" name="CollectionSearch[end_collection_date]" id="end_collection_date" autocomplete="off">
                </div>

                <div class="layui-inline layui-vertical-20">
                    <br>
                    <button class="layui-btn" data-type="search_lists">搜索</button>
                    <button class="layui-btn layui-btn-primary ys-upload" lay-data="{url: '/collection/import-amount/',accept: 'file'}">导入</button>
                </div>
            </div>
            <div class="layui-card-body">
                <table id="collection" class="layui-table" lay-data="{url:'<?=Url::to(['collection/list'])?>', height : 'full-20', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]},limit:20}" lay-filter="collection">
                    <thead>
                    <tr>
                        <th lay-data="{minWidth:310, templet:'#listBar',align:'center'}">操作</th>
                        <th lay-data="{field: 'collection_date',width:105}">回款日期</th>
                        <th lay-data="{field: 'platform',width:120}">平台</th>
                        <th lay-data="{field: 'shop_name',align:'center',width:200}">店铺</th>
                        <th lay-data="{field: 'collection_bank_cards',width:170}">回款银行卡</th>
                        <th lay-data="{field: 'collection_account',width:130}">回款账号</th>
                        <th lay-data="{field: 'collection_currency',  align:'left',width:95}">回款币种</th>
                        <th lay-data="{field: 'collection_amount',  align:'left',width:145}">金额</th>
                        <th lay-data="{field: 'status',  align:'left',width:100}">状态</th>
                        <th lay-data="{field: 'add_time',  align:'left',width:170}">创建时间</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<!--操作-->
<script type="text/html" id="listBar">
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['financial-platform-sales-period/index'])?>?collection_payment_back={{ d.id }}" data-title="回款" >回款</a>
    <a class="layui-btn layui-btn-xs" lay-event="update" data-title="查看账期" data-url="<?=Url::to(['financial-platform-sales-period/index'])?>?collection_id={{ d.id }}">查看账期</a>
    {{# if(d.status == '未处理'){ }}
        <a class="layui-btn layui-btn-xs layui-btn-warm" lay-event="operating" data-title="调整状态" data-url="<?=Url::to(['collection/update-status'])?>?collection_id={{ d.id }}">调整状态</a>
    {{# } }}
</script>
<script>
    const tableName="collection";
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js")?>