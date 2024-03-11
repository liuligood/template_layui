
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

    <?php if(empty($buyer_id)){ ?>
    <form class="layui-form">
        <blockquote class="layui-elem-quote quoteBox">
            <div class="layui-inline">
                <a class="layui-btn" data-type="url" data-title="充值" data-url="<?=Url::to(['buyer-account-transaction-log/recharge'])?>" data-callback_title = "买家账号交易流水">充值</a>
                <!--<a class="layui-btn" data-type="url" data-title="订单支付" data-url="<?=Url::to(['buyer-account-transaction-log/order'])?>" data-callback_title = "买家账号交易流水">订单支付</a>-->
                <a class="layui-btn" data-type="url" data-title="后台变更" data-url="<?=Url::to(['buyer-account-transaction-log/admin'])?>" data-callback_title = "买家账号交易流水">后台变更</a>
            </div>
        </blockquote>
    </form>
    <?php }?>

    <form>
    <div class="layui-form lay-search" style="padding-left: 10px">
        <?php if(empty($buyer_id)){ ?>
        <div class="layui-inline">
            买家分机号
            <input class="layui-input search-con" name="BuyerAccountTransactionLogSearch[ext_no]" value="" autocomplete="off">
        </div>
        <?php }?>

        <div class="layui-inline">
            交易方式
            <?= Html::dropDownList('BuyerAccountTransactionLogSearch[transaction_type]', null, \common\services\buyer_account\BuyerAccountTransactionService::$transaction_type_map,
                ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px' ]) ?>
        </div>

        <div class="layui-inline">
            类型
            <?= Html::dropDownList('BuyerAccountTransactionLogSearch[type]', null, \common\services\buyer_account\BuyerAccountTransactionService::$type_map,
                ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px' ]) ?>
        </div>

        <div class="layui-inline">
            关联销售单号
            <input class="layui-input search-con" name="BuyerAccountTransactionLogSearch[relation_no]" value="" autocomplete="off">
        </div>

        <div class="layui-inline">
            关联亚马逊订单号
            <input class="layui-input search-con" name="BuyerAccountTransactionLogSearch[buy_relation_no]" value="" autocomplete="off">
        </div>

        <div class="layui-inline">
            变动时间
            <input class="layui-input search-con ys-date" name="BuyerAccountTransactionLogSearch[start_add_time]" id="start_add_time" >
        </div>
        <span class="layui-inline layui-vertical-20">
        -
        </span>
        <div class="layui-inline layui-vertical-20">
            <input class="layui-input search-con ys-date" name="BuyerAccountTransactionLogSearch[end_add_time]" id="end_add_time" >
        </div>

        <div class="layui-inline layui-vertical-20">
            <input class="layui-input search-con" type="hidden" name="BuyerAccountTransactionLogSearch[buyer_id]" value="<?=$buyer_id?>">
            <button class="layui-btn" data-type="search_lists">搜索</button>

            <button class="layui-btn layui-btn-normal" data-type="export_lists" data-url="<?=Url::to(['buyer-account-transaction-log/export?buyer_id='.$buyer_id])?>">导出</button>

            <button class="layui-btn layui-btn-primary ys-upload" lay-data="{url: '/buyer-account-transaction-log/recharge-import/',accept: 'file'}">充值导入</button>
        </div>
    </div>
    </form>
    <div class="layui-card-body">
    <table id="buyer-account-transaction-log" class="layui-table" lay-data="{url:'<?=Url::to(['buyer-account-transaction-log/list?buyer_id='.$buyer_id])?>', height : 'full-20', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20}" lay-filter="buyer-account-transaction-log">
    <thead>
    <tr>
        <th lay-data="{field: 'id', align:'center',width:90}">id</th>
        <th lay-data="{field: 'ext_no', align:'center',width:160}">买家分机号</th>
        <th lay-data="{field: 'amazon_account', align:'center', width:180}">亚马逊邮箱</th>
        <th lay-data="{field: 'transaction_type_desc', align:'center', width:120}">交易方式</th>
        <th lay-data="{field: 'type_desc', width:100, align:'center', width:100}">类型</th>
        <th lay-data="{field: 'relation_no', align:'center', width:150}">关联销售单号</th>
        <th lay-data="{field: 'buy_relation_no', align:'center', width:180}">关联亚马逊订单号</th>
        <th lay-data="{field: 'org_money',  align:'center',width:100}">原金额</th>
        <th lay-data="{field: 'money',  align:'center',width:100}">变动金额</th>
        <th lay-data="{field: 'now_money',  align:'center',width:100}">变动后金额</th>
        <th lay-data="{field: 'desc',  align:'center',width:100}">描述</th>
        <th lay-data="{field: 'add_time_desc',  align:'center',width:180}">变动时间</th>
        <!--<th lay-data="{minWidth:100, templet:'#listBar',fixed:'right',align:'center'}">操作</th>-->
    </tr>
    </thead>
</table>
</div>
    </div>
</div>
<!--操作-->
<script type="text/html" id="listBar">
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['buyer-account-transaction-log/update'])?>?id={{ d.id }}" data-title="编辑" data-callback_title="买家账号管理列表">编辑</a>
</script>

<script>
    const tableName="buyer-account-transaction-log";
</script>
<?php
    $this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.4.1");
    $this->registerJsFile("@adminPageJs/buyer-account-transaction-log/lists.js?v=0.0.2");
    $this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
    $this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
    $this->registerJsFile('@adminPlugins/export/xlsx.core.min.js?v=1',['depends'=>'yii\web\JqueryAsset']);
    $this->registerJsFile('@adminPlugins/export/export-excel.js?v=1',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export-function.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
?>

