
<?php

use common\models\PromoteCampaign;
use common\models\Shop;
use yii\bootstrap\Html;
use yii\helpers\Url;
?>
    <style>
    .layui-table-body .layui-table-cell{
        height:auto;
    }
    #summary  i {
        color: red;
    }
    .layui-laypage .active a{
        background-color: #009688;
        color: #fff;
    }
    .layui-btn-a {
        border: none;
        background-color: rgba(0, 0, 0, 0);
        -webkit-user-select:text;
    }
    </style>
<div class="layui-fluid">
    <div class="layui-card">
        <div class="lay-lists">
            <form class="layui-form">
                <blockquote class="layui-elem-quote quoteBox">
                    <div class="layui-inline">
                        <a class="layui-btn" data-type="url" data-title="添加" data-url="<?=Url::to(['promote-campaign/create'])?>" data-callback_title = "collection-account列表" >添加</a>
                        <a class="layui-btn layui-btn-normal" data-type="url" data-title="查看明细" data-url="<?=Url::to(['promote-campaign-details/index?is_all=1'])?>" data-callback_title = "collection-account列表" >查看明细</a>
                    </div>
                </blockquote>
            </form>

            <div class="lay-search" style="padding-left: 10px">
                <div class="layui-inline layui-vertical-20" style="width: 120px">
                    <label>平台</label>
                    <?= Html::dropDownList('PromoteCampaignSearch[platform_type]', null,\common\services\goods\GoodsService::$own_platform_type,
                        ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','id'=>'platform']); ?>
                </div>

                <div class="layui-inline layui-vertical-20" style="width: 120px">
                    <label>类型</label>
                    <?= Html::dropDownList('PromoteCampaignSearch[type]', null,PromoteCampaign::$type_maps,
                        ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']); ?>
                </div>

                <div class="layui-inline layui-vertical-20">
                    <label>店铺名称</label>
                    <?= \backend\widgets\LinkageDropDownList::widget(['id' => 'shop','parent_id'=>'platform','name'=>'PromoteCampaignSearch[shop_id]','select'=>null,'option'=> \common\services\ShopService::getShopMapIndexPlatform(),'param'=>['data-placeholder' => '全部','prompt' => '全部','style'=>'width:180px']]) ?>
                </div>
                <div class="layui-inline">
                    活动时间：
                    <input  class="layui-input search-con ys-date" name="PromoteCampaignSearch[start_date]"  id="start_date" autocomplete="off">
                </div>
                <span class="layui-inline layui-vertical-20">
                    <br/>
                    -
                </span>

                <div class="layui-inline layui-vertical-20">
                    结束时间
                    <input  class="layui-input search-con ys-date" name="PromoteCampaignSearch[end_date]"  id="end_date" autocomplete="off">
                </div>

                <button class="layui-btn" data-type="search_lists" id="search_btn" style="margin-top: 18px">搜索</button>
                <button class="layui-btn layui-btn-primary ys-uploadtwo" lay-data="{accept: 'file'}" style="margin-top: 18px">导入</button>

                <div class="layui-inline layui-vertical-20" style="margin-left: 10px;margin-top: 20px">
                    <a class="day layui-btn layui-btn-primary layui-btn-xs layui-btn-a" data-title="昨日" data-day="1" style="text-decoration:none;color:#00a0e9;font-size: 14px" >昨日</a>
                </div>
                <div class="layui-inline layui-vertical-20" style="margin-top: 20px">
                    <a class="day layui-btn layui-btn-primary layui-btn-xs layui-btn-a" data-title="7日" data-day="7" style="text-decoration:none;color:#00a0e9;font-size: 14px">7日</a>
                </div>
                <div class="layui-inline layui-vertical-20" style="margin-top: 20px">
                    <a class="day layui-btn layui-btn-primary layui-btn-xs layui-btn-a" data-title="15日" data-day="15" style="text-decoration:none;color:#00a0e9;font-size: 14px">15日</a>
                </div>
                <div class="layui-inline layui-vertical-20" style="margin-top: 20px">
                    <a class="day layui-btn layui-btn-primary layui-btn-xs layui-btn-a" data-title="30日" data-day="30" style="text-decoration:none;color:#00a0e9;font-size: 14px">30日</a>
                </div>
            </div>
            <div class="layui-card-body">
                <div id="summary">
                </div>
                <table id="promote-campaign" class="layui-table" lay-data="{url:'<?=Url::to(['promote-campaign/list'])?>', height : 'full-20', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20, id:'promote-campaign'}" lay-filter="promote-campaign">
                    <thead>
                    <tr>
                        <th lay-data="{templet:'#listBars', minWidth:270,align:'center', fixed: 'left'}">平台</th>
                        <th lay-data="{templet:'#listBarsw', minWidth:200,align:'center', fixed: 'left'}">推广活动</th>
                        <th lay-data="{field: 'status', width:120, templet: '#statusTpl', unresize: true}">状态</th>
                        <th lay-data="{field: 'all_impressions', width:120, }">展示量</th>
                        <th lay-data="{field: 'all_hits', width:120, }">点击量</th>
                        <th lay-data="{field: 'all_promotes', width:120, }">推广费用</th>
                        <th lay-data="{field: 'all_order_volume', width:120, }">订单量</th>
                        <th lay-data="{field: 'all_order_sales', width:120, }">订单销售额</th>
                        <th lay-data="{field: 'all_model_orders', width:120, }">型号订单量</th>
                        <th lay-data="{field: 'all_model_sales', width:145, }">型号订单销售额</th>
                        <th lay-data="{field: 'ACOS', width:120, }">ACOS</th>
                        <th lay-data="{field: 'CTR', width:120, }">CTR%</th>
                        <th lay-data="{field: 'every', width:220, }">每1000展现(点击)平均价格</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<!--操作-->
<script type="text/html" id="listBar">

</script>
<script type="text/html" id ="listBars">
平台：{{ d.platform_type_name }}<br>
店铺：{{ d.shop }}<br/>
<a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['promote-campaign/update'])?>?id={{ d.id }}" data-title="编辑" data-callback_title="promote-campaign列表">编辑</a>

<a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['promote-campaign-details/index'])?>?id={{ d.id }}&stime={{ d.stime }}&etime={{ d.etime }}&platform_type={{ d.platform_type }}" data-title="查看明细" data-callback_title="查看明细">查看明细</a>
{{# if(d.count == 0){ }}
<a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete" data-url="<?=Url::to(['promote-campaign/delete'])?>?id={{ d.id }}">删除</a>
{{# } }}
</script>
<script type="text/html" id ="listBarsw">
    推广活动编号：{{ d.promote_id }}<br>
    推广活动名称：{{ d.promote_name }}<br/>
    类型：{{ d.type || '' }}
</script>
<script type="text/html" id="statusTpl">
    <input type="checkbox" name="状态" value="{{d.id}}" data-url="<?=Url::to(['promote-campaign/update-status'])?>?id={{ d.id }}" lay-skin="switch" lay-text="正常|禁用" lay-filter="statusSwitch" {{ d.status == 1 ? 'checked' : '' }}>
</script>

<script>
    const tableName="promote-campaign";
</script>
<?=$this->registerJsFile("@adminPageJs/promote-campaign-details/index.js?v=".time())?>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.5")?>
<?=$this->registerJsFile("@adminPageJs/financial-platform-sales-period/lists.js?v=".time())?>
<?=$this->registerJsFile("@adminPageJs/collection/lists.js?v=".time())?>
<?=$this->registerJsFile("@adminPageJs/promote-campaign-details/lists.js?v=".time())?>


