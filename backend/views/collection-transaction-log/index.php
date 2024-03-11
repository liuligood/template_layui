
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

    <form>
    <div class="layui-form lay-search" style="padding-left: 10px">
        <div class="layui-inline">
            变动时间
            <input class="layui-input search-con ys-date" name="CollectionTransactionLogSearch[start_add_time]" id="start_add_time" >
        </div>
        <span class="layui-inline layui-vertical-20">
        -
        </span>
        <div class="layui-inline layui-vertical-20">
            <input class="layui-input search-con ys-date" name="CollectionTransactionLogSearch[end_add_time]" id="end_add_time" >
        </div>

        <div class="layui-inline layui-vertical-20">
            <input class="layui-input search-con" type="hidden" name="CollectionTransactionLogSearch[collection_currency_id]" value="<?=$collection_currency_id?>">
            <button class="layui-btn" data-type="search_lists">搜索</button>
        </div>
    </div>
    </form>
    <div class="layui-card-body">
    <table id="collection-transaction-log" class="layui-table" lay-data="{url:'<?=Url::to(['collection-transaction-log/list?collection_currency_id='.$collection_currency_id])?>', height : 'full-20', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20}" lay-filter="collection-transaction-log">
    <thead>
    <tr>
        <th lay-data="{field: 'id', align:'center',width:90}">id</th>
        <th lay-data="{field: 'collection_bank_cards', align:'center',width:160}">收款银行卡号</th>
        <th lay-data="{field: 'type_desc', align:'center',width:160}">类型</th>
        <th lay-data="{field: 'org_money',  align:'center',width:100}">原金额</th>
        <th lay-data="{field: 'money',  align:'center',width:100}">变动金额</th>
        <th lay-data="{field: 'now_money',  align:'center',width:100}">变动后金额</th>
        <th lay-data="{field: 'desc',  align:'center',width:100}">描述</th>
        <th lay-data="{field: 'admin_desc',  align:'center',width:100}">操作者</th>
        <th lay-data="{field: 'add_time_desc',  align:'center',width:180}">变动时间</th>
    </tr>
    </thead>
</table>
</div>
    </div>
</div>

<script>
    const tableName="collection-transaction-log";
</script>
<?php
    $this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.4.1");
?>

