
<?php

use common\models\financial\CollectionAccount;
use common\models\Shop;
use yii\bootstrap\Html;
use yii\helpers\Url;
?>
<div class="layui-fluid">
    <div class="layui-card">
        <div class="lay-lists">
            <form class="layui-form">
                <blockquote class="layui-elem-quote quoteBox">
                    <div class="layui-inline">
                        <a class="layui-btn" data-type="url" data-title="添加" data-url="<?=Url::to(['collection-bank-cards/create'])?>" data-callback_title = "collection-account列表" >添加银行卡</a>
                    </div>
                </blockquote>
            </form>

            <div class="lay-search" style="padding-left: 10px">
                收款账号：
                <div class="layui-inline">
                    <?= Html::dropDownList('CollectionBankCardsSearch[collection_account_id]',null,CollectionAccount::getListAccount(),
                        ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width: 165px']) ?>
                </div>

                银行卡号：
                <div class="layui-inline">
                    <input class="layui-input search-con" name="CollectionBankCardsSearch[collection_bank_cards]" autocomplete="off">
                </div>

                <button class="layui-btn" data-type="search_lists">搜索</button>
                <button class="layui-btn layui-btn-primary ys-upload" lay-data="{url: '/collection-bank-cards/import-bank',accept: 'file'}">导入</button>
            </div>
            <div class="layui-card-body">
                <table id="collection-bank-cards" class="layui-table" lay-data="{url:'<?=Url::to(['collection-bank-cards/list'])?>', height : 'full-20', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20}" lay-filter="collection-bank-cards">
                    <thead>
                    <tr>
                        <th lay-data="{field: 'id', width:80}">ID</th>
                        <th lay-data="{field: 'collection_bank_cards', width:180}">收款银行卡</th>
                        <th lay-data="{field: 'collection_account', align:'left',width:170}">收款账号</th>
                        <th lay-data="{field: 'shop_name', align:'left',width:190}">店铺</th>
                        <th lay-data="{field: 'collection_currency', width:200}">收款币种</th>
                        <th lay-data="{field: 'update_time',  align:'left',minWidth:50}">更新时间</th>
                        <th lay-data="{field: 'add_time',  align:'left',minWidth:50}">创建时间</th>
                        <th lay-data="{minWidth:175, templet:'#listBar',align:'center'}">操作</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<!--操作-->
<script type="text/html" id="listBar">
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['collection-bank-cards/update'])?>?id={{ d.id }}" data-title="编辑收款账号" data-callback_title="collection-bank-cards列表">编辑</a>
    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete" data-url="<?=Url::to(['collection-bank-cards/delete'])?>?id={{ d.id }}">删除</a>
</script>

<script>
    const tableName="collection-bank-cards";
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js")?>