<?php
use yii\helpers\Url;
use yii\helpers\Html;
?>

<style>
    .layui-vertical-20{
        padding-top: 20px;
    }
</style>
<div class="layui-fluid">
    <div class="layui-card">
<div class="lay-lists">

    <form class="layui-form">
        <blockquote class="layui-elem-quote quoteBox">
            <div class="layui-inline">
                <a class="layui-btn" data-type="url" data-title="添加货币" data-url="<?=Url::to(['collection-currency/create?collection_account_id='.$collection_account_id])?>" data-callback_title = "货币列表" >添加货币</a>
            </div>
        </blockquote>
    </form>

    <div class="layui-card-body">
    <table id="collection-currency" class="layui-table" lay-data="{url:'<?=Url::to(['collection-currency/list?collection_account_id='.$collection_account_id])?>', height : 'full-20', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20}" lay-filter="collection-currency">
    <thead>
    <tr>
        <th lay-data="{field: 'currency', width:100, align:'center', width:100}">币种</th>
        <th lay-data="{field: 'money', align:'center', width:120}">金额</th>
        <th lay-data="{minWidth:220, templet:'#listBar',fixed:'right',align:'center'}">操作</th>
    </tr>
    </thead>
</table>
</div>
    </div>
</div>
<!--操作-->
<script type="text/html" id="listBar">
    <a class="layui-btn layui-btn-xs" lay-event="open" data-width="650px" data-height="400px" data-url="<?=Url::to(['collection-transaction-log/withdrawal'])?>?id={{ d.id }}" data-title="提现" data-callback_title="收款账号货币列表">提现</a>

    <a class="layui-btn layui-btn-xs" lay-event="open" data-width="650px" data-height="400px" data-url="<?=Url::to(['collection-transaction-log/admin'])?>?id={{ d.id }}" data-title="调整金额" data-callback_title="收款账号货币列表">调整金额</a>

    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['collection-transaction-log/index'])?>?collection_currency_id={{ d.id }}" data-title="交易流水" data-callback_title="收款账号货币列表">查看流水</a>
</script>

<script>
    const tableName="collection-currency";
</script>
<?php
    $this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.4.1");
?>

