
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
                <a class="layui-btn" data-type="url" data-title="添加买家账号" data-url="<?=Url::to(['buyer-account/create'])?>" data-callback_title = "买家账号列表" >添加买家账号</a>
            </div>
        </blockquote>
    </form>

    <form>
    <div class="layui-form lay-search" style="padding-left: 10px">
        <div class="layui-inline" style="width: 110px">
            分机号
            <input class="layui-input search-con" name="BuyerAccountSearch[start_ext_no]" autocomplete="off">
        </div>
        <span class="layui-inline layui-vertical-20">
            -
        </span>
        <div class="layui-inline layui-vertical-20" style="width: 110px">
            <input class="layui-input search-con" name="BuyerAccountSearch[end_ext_no]" >
        </div>
        <div class="layui-inline">
            亚马逊邮箱
            <input class="layui-input search-con" name="BuyerAccountSearch[amazon_account]" autocomplete="off">
        </div>
        <div class="layui-inline" style="width: 120px">
            平台
            <?= Html::dropDownList('BuyerAccountSearch[platform]', null,\common\components\statics\Base::$buy_platform_maps,
                ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search' ]) ?>
        </div>
        <div class="layui-inline" style="width: 80px">
            会员
            <?= Html::dropDownList('BuyerAccountSearch[member]', null,\common\models\BuyerAccount::$member_map,
                ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search' ]) ?>
        </div>
        <div class="layui-inline" style="width: 100px">
            余额
            <input class="layui-input search-con" name="BuyerAccountSearch[start_amount]" >
        </div>
        <span class="layui-inline layui-vertical-20">
            -
        </span>
        <div class="layui-inline layui-vertical-20" style="width: 100px">
            <input class="layui-input search-con" name="BuyerAccountSearch[end_amount]" >
        </div>
        <div class="layui-inline layui-vertical-20">
            <button class="layui-btn" data-type="search_lists">搜索</button>

            <button class="layui-btn layui-btn-normal" data-type="export_lists" data-url="<?=Url::to(['buyer-account/export'])?>">导出</button>

            <button class="layui-btn layui-btn-primary ys-upload" lay-data="{url: '/buyer-account/import/',accept: 'file'}">导入</button>
        </div>
    </div>
    </form>

    <div class="layui-card-body">
    <table id="buyer-account" class="layui-table" lay-data="{url:'<?=Url::to(['buyer-account/list'])?>', height : 'full-20', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20}" lay-filter="buyer-account">
    <thead>
    <tr>
        <th lay-data="{field: 'ext_no', width:100, align:'center', width:100}">分机号</th>
        <th lay-data="{field: 'platform_desc', align:'center', width:120}">平台</th>
        <th lay-data="{field: 'amazon_account', align:'center', width:180}">亚马逊邮箱</th>
        <th lay-data="{field: 'amazon_password', align:'center', width:150}">亚马逊密码</th>
        <th lay-data="{field: 'username', align:'center', width:120}">买家用户名</th>
        <th lay-data="{field: 'card_type_desc',  align:'center',width:100}">卡类型</th>
        <th lay-data="{field: 'amount', align:'center', width:90}">余额</th>
        <th lay-data="{field: 'consume_amount',  align:'center',width:100}">消费金额</th>
        <th lay-data="{field: 'member_desc',  align:'center',width:100}">会员</th>
        <th lay-data="{field: 'become_member_time_desc',  align:'center',width:120}">激活会员时间</th>
        <th lay-data="{field: 'swipe_num',  align:'center',width:100}">刷单数</th>
        <th lay-data="{field: 'evaluation_num',  align:'center',minWidth:100}">评价数</th>
        <th lay-data="{field: 'status_desc', align:'center', width:100}">状态</th>
        <th lay-data="{field: 'remarks', align:'center', width:100}">备注</th>
        <th lay-data="{minWidth:220, templet:'#listBar',fixed:'right',align:'center'}">操作</th>
    </tr>
    </thead>
</table>
</div>
    </div>
</div>
<!--操作-->
<script type="text/html" id="listBar">
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['buyer-account/update'])?>?id={{ d.id }}" data-title="编辑" data-callback_title="买家账号管理列表">编辑</a>

    <a class="layui-btn layui-btn-xs" lay-event="update" data-url="<?=Url::to(['buyer-account-transaction-log/recharge'])?>?ext_no={{ d.ext_no }}" data-title="充值" data-callback_title="买家账号管理列表">充值</a>

    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['buyer-account-transaction-log/index'])?>?buyer_id={{ d.buyer_id }}" data-title="交易流水" data-callback_title="买家账号管理列表">查看交易流水</a>
</script>

<script type="text/html" id="statusTpl">
    <input type="checkbox" name="状态" value="{{d.id}}" data-url="<?=Url::to(['buyer-account/update-status'])?>?id={{ d.id }}" lay-skin="switch" lay-text="正常|禁用" lay-filter="statusSwitch" {{ d.status ==10 ? 'checked' : '' }}>
</script>

<script>
    const tableName="buyer-account";
</script>
<?php
    $this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.4.1");
    $this->registerJsFile("@adminPageJs/buyer-account/lists.js?v=0.0.2");
    $this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
    $this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
    $this->registerJsFile('@adminPlugins/export/xlsx.core.min.js?v=1',['depends'=>'yii\web\JqueryAsset']);
    $this->registerJsFile('@adminPlugins/export/export-excel.js?v=1',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export-function.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
?>

