
<?php
use yii\helpers\Url;
use yii\bootstrap\Html;
use common\models\Shop;
use common\components\statics\Base;


$admin_id = new Shop();
?>
<style>
    .layui-table-body .layui-table-cell{
        height:auto;
    }
    .span-goode-name{
        font-size: 13px;
        width: 150px;
        height: auto;
        display: block;
        word-wrap:break-word;
        white-space:pre-wrap;
    }
</style>
<div class="layui-fluid">
    <div class="layui-card">
<div class="lay-lists">
<form class="layui-form">
    <blockquote class="layui-elem-quote quoteBox">
        <div class="layui-inline">
            <a class="layui-btn" data-type="url" data-title="添加" data-url="<?=Url::to(['shop/create'])?>" data-callback_title = "shop列表" >添加店铺</a>
        </div>
    </blockquote>
</form>
    <form>
        <div class="layui-form lay-search" style="padding-left: 10px">

            <div class="layui-inline" style="width: 150px">
                <label>ID</label>
                <input class="layui-input search-con" name="ShopSearch[id]" autocomplete="off">
    		</div>

            <div class="layui-inline" style="width: 150px">
                <label>店铺名称</label>
                <input class="layui-input search-con" name="ShopSearch[name]" autocomplete="off">
    		</div>

            <div class="layui-inline" style="width: 150px">
                <label>品牌名称</label>
                <input class="layui-input search-con" name="ShopSearch[brand_name]" autocomplete="off">
            </div>
    		
            <div class="layui-inline layui-vertical-20" style="width: 120px">
                <label>平台类型</label>
                <?= Html::dropDownList('ShopSearch[platform_type]',null,Base::$platform_maps,
                    ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search' ]) ?>
            </div>
            
            <div class="layui-inline layui-vertical-20" style="width: 120px">
                <label>状态</label>
                <?= Html::dropDownList('ShopSearch[status]', null,Shop::$status_maps,
                    ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search' ]) ?>
            </div>

            <div class="layui-inline layui-vertical-20" style="width: 120px">
                <label>店铺销售状态</label>
                <?= Html::dropDownList('ShopSearch[sale_status]',null,Shop::$sale_status_maps,
                    ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
            </div>

             <div class="layui-inline layui-vertical-20" style="width: 120px">
                 <label>店铺负责人</label>
                 <?= Html::dropDownList('ShopSearch[admin_id]',null,$admin_id->adminArr(),
                    ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
             </div>
        

             <div class="layui-inline layui-vertical-20" style="margin-top: 18px">
                 <button class="layui-btn" data-type="search_lists">搜索</button>
             </div>

             <div class="lay-lists" style="padding:5px;">
                 <div class="layui-inline">
                    <a class="layui-btn layui-btn-sm layui-btn-normal batch_category_btn" data-url="<?=Url::to(['shop/updates'])?>">批量设置负责人</a>
                 </div>
             </div>
        </div>
    </form>
     <div class="layui-card-body">
<table id="shop" class="layui-table" lay-data="{url:'<?=Url::to(['shop/list'])?>', height : 'full-20', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000],limit:20}}" lay-filter="shop">
    <thead>
    <tr>
        <th lay-data="{type: 'checkbox', width:50,field: 'id'}"></th>
        <th lay-data="{field: 'id', width:70}">ID</th>
        <th lay-data="{ width:180 ,templet:'#shopTplName'}">店铺</th>
        <th lay-data="{ width:120 ,templet:'#shopTplCountry'}">站点</th>
        <th lay-data="{ width:180 ,templet:'#shopTplMes'}">信息</th>
        <th lay-data="{ width:140 ,templet:'#shopTplSaleStatus'}">店铺状态</th>
        <th lay-data="{align:'left',width:200,templet:'#shopTplAssignment'}">接口权限</th>
        <th lay-data="{templet:'#shopTplTime',  align:'left',minWidth:235}">时间</th>
        <th lay-data="{minWidth:175, templet:'#listBar',align:'center'}">操作</th>
    </tr>
    </thead>
</table>
</div>
</div>
    </div>
</div>
<script type="text/html" id="shopTplName">
    {{ d.platform_type }}<br/>
    {{ d.name }}<br/>
    品牌：{{ d.brand_name }}
</script>
<script type="text/html" id="shopTplCountry">
    站点：{{ d.country_site }}<br/>
    币种：{{ d.currency }}
</script>
<script type="text/html" id="shopTplMes">
    <div class="span-goode-name">ioss：{{ d.ioss }}</div>
    <div class="span-goode-name">client_key：{{ d.client_key }}</div>
</script>
<script type="text/html" id="shopTplSaleStatus">
    {{# if(d.sale_status == 1){ }}
        销售状态：<span style="color: green">正常</span><br/>
    {{# }else{ }}
        销售状态：<span style="color: red">{{ d.sales_status }}</span><br/>
    {{# } }}
    状态：{{ d.status }}<br/>
    销售量：{{ d.order_num }}<br/>
</script>
<script type="text/html" id="shopTplTime">
    {{# if(d.sale_status == 1){ }}
    最后出单：{{ d.last_order_time }}<br/>
    {{# }else{ }}
    最后出单：<span style="color: red">{{ d.last_order_time }}</span><br/>
    {{# } }}
    添加：{{ d.add_time }}<br/>
    更新：{{ d.update_time }}
</script>
<script type="text/html" id="shopTplAssignment">
    接口：{{ d.api_assignment }}<br/>
    负责人：{{ d.admin_id }}
</script>
<!--操作-->
<script type="text/html" id="listBar">
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['shop/create'])?>?shop_id={{ d.id }}" data-title="新增" data-callback_title="shop列表">复制</a>
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['shop/update'])?>?shop_id={{ d.id }}" data-title="编辑" data-callback_title="shop列表">编辑</a>
    <!--<a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete" data-url="<?=Url::to(['shop/delete'])?>?shop_id={{ d.id }}">删除</a>-->
    <a class="layui-btn layui-btn-xs layui-btn-xs" lay-event="update" data-url="<?=Url::to(['shop/view'])?>?shop_id={{ d.id }}">查看</a>
</script>
<script>
    const tableName="shop";
    var collection = 0;
</script>
<?php
    $this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.4.6");
    $this->registerJsFile("@adminPageJs/shop/lists.js?v=".time());
?>
    

