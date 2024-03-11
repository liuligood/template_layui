
<?php

use common\models\purchase\PurchaseProposal;
use yii\helpers\Url;
use yii\helpers\Html;
?>

<style>
    .layui-vertical-20{
        padding-top: 20px;
    }
    .layui-table-body .layui-table-cell{
        height:auto;
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
    .layui-tab{
        margin-top: 0;
    }
    .span-goode-name{
        color:#a0a3a6;
        font-size: 13px;
        width: 240px;
        height: auto;
        display: block;
        word-wrap:break-word;
        white-space:pre-wrap;
    }
    .span-circular-red{
        display: inline-block;
        min-width: 26px;
        height: 26px;
        border-radius: 50%;
        background-color: #ff6666;
        color: #fff;
        text-align: center;
        padding: 5px;
        line-height: 18px;
    }
    .span-circular-grey{
        display: inline-block;
        min-width: 26px;
        height: 26px;
        border-radius: 50%;
        background-color: #999999;
        color: #fff;
        text-align: center;
        padding: 5px;
        line-height: 18px;
    }
</style>
<div class="layui-fluid">
    <div class="layui-card">
<div class="layui-tab layui-tab-brief">
    <ul class="layui-tab-title">
        <li <?php if($tag == 1){?>class="layui-this" <?php }?>><a href="<?=Url::to(['purchase-proposal/index?tag=1'])?>">安骏</a></li>
        <li <?php if($tag == 2){?>class="layui-this" <?php }?>><a href="<?=Url::to(['purchase-proposal/index?tag=2'])?>">三林豆</a></li>
    </ul>
</div>
<div class="layui-card-body">
<div class="lay-lists">

    <form>
    <div class="layui-form lay-search">
        <div class="layui-inline" style="border: solid 2px rgb(0, 150, 136);margin-right: 10px;">
            <div style="width: 100px;float: right;">
                <select name="PurchaseProposalSearch[shelve]" class="search-con" lay-filter="sel_submit">
                    <option value="-1">全部</option>
                    <option value="0" selected>正常</option>
                    <option value="1">搁置</option>
                    <option value="2">问题件</option>
                    <option value="3">敏感货</option>
                    <option value="4">定制款</option>
                </select>
            </div>
        </div>
        <div class="layui-inline">
            商品编号
            <textarea name="PurchaseProposalSearch[goods_no]" class="layui-textarea search-con" style="height: 39px; min-height:39px"></textarea>
        </div>
        <div class="layui-inline">
            SKU
            <textarea name="PurchaseProposalSearch[sku_no]" class="layui-textarea search-con" style="height: 39px; min-height:39px"></textarea>
        </div>
        <div class="layui-inline">
            采购状态
            <?= Html::dropDownList('PurchaseProposalSearch[has_procured]', null, [
                0 => '未采购',
                1 => '已采购',
                2 => '未匹配',
            ], ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:195px' ]) ?>
        </div>
        <div class="layui-inline">
            分类
            <?= Html::dropDownList('PurchaseProposalSearch[category_id]', null, $category_arr,
                ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:195px' ]) ?>
        </div>
        <div class="layui-inline">
            平台
            <?= Html::dropDownList('PurchaseProposalSearch[platform_types]', null, \common\components\statics\Base::$platform_maps,
                ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:195px' ]) ?>
        </div>
        <div class="layui-inline">
            <label>日期</label>
            <input  class="layui-input search-con ys-date" name="PurchaseProposalSearch[start_order_add_time]" id="start_order_add_time" autocomplete="off">
        </div>
        <span class="layui-inline layui-vertical-20">
        -
        </span>
        <div class="layui-inline layui-vertical-20">
            <input  class="layui-input search-con ys-date" name="PurchaseProposalSearch[end_order_add_time]" id="end_order_add_time" autocomplete="off">
        </div>
        <?php if($all_goods_access){?>
        <div class="layui-inline">
            采购员
            <?= Html::dropDownList('PurchaseProposalSearch[admin_id]', null, $admin_arr,
                ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:195px' ]) ?>
        </div>
        <?php }?>
        <div class="layui-inline layui-vertical-20">
            <button class="layui-btn" id="search_btn" data-type="search_lists">搜索</button>
        </div>
    </div>
    </form>
    <div class="layui-form" style="padding:10px">
        <?php if($all_goods_access){?>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-sm layui-btn-normal allo_btn" data-url="<?=Url::to(['purchase-proposal/batch-allo?tag='.$tag])?>">批量分配</a>
            </div>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-sm layui-btn-normal batch_shelve_btn" data-title="移入搁置" data-url="<?=Url::to(['purchase-proposal/shelve-batch-allo?tag='.$tag.'&status=1'])?>">批量移入搁置</a>
            </div>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-sm layui-btn-normal batch_shelve_btn" data-title="移入问题件" data-url="<?=Url::to(['purchase-proposal/shelve-batch-allo?tag='.$tag.'&status=2'])?>">批量移入问题件</a>
            </div>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-sm layui-btn-normal batch_shelve_btn" data-title="移入敏感货" data-url="<?=Url::to(['purchase-proposal/shelve-batch-allo?tag='.$tag.'&status=3'])?>">批量移入敏感货</a>
            </div>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-sm layui-btn-normal batch_shelve_btn" data-title="移入敏感货" data-url="<?=Url::to(['purchase-proposal/shelve-batch-allo?tag='.$tag.'&status=4'])?>">批量移入定制款</a>
            </div>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-sm layui-btn-danger batch_shelve_btn" data-title="移除敏感货" data-url="<?=Url::to(['purchase-proposal/shelve-normal-batch-allo?tag='.$tag])?>">批量取消</a>
            </div>
        <?php }?>
    </div>

    <table id="purchase-proposal" class="layui-table" lay-data="{url:'<?=Url::to(['purchase-proposal/list?tag='.$tag])?>', height : 'full-140', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 100}" lay-filter="purchase-proposal">
    <thead>
    <tr>
        <th lay-data="{type: 'checkbox', width:50}">ID</th>
        <th lay-data="{ width:140, align:'center',templet:'#goodsImgTpl'}">商品主图</th>
        <th lay-data="{ width:280, align:'center',templet:'#goodsTpl'}">商品信息</th>
        <th lay-data="{ width:180, templet:'#goodsTpl1'}">采购信息</th>
        <th lay-data="{ width:180, templet:'#goodsCutTpl'}">采购量</th>
        <th lay-data="{ width:280, templet:'#orderTpl'}">订单信息</th>
        <!--<th lay-data="{field: 'stock', align:'center', width:80}">库存</th>
        <th lay-data="{field: 'purchase_stock', align:'center', width:80}">在途</th>
        <th lay-data="{field: 'order_stock', align:'center', width:80}">订单量</th>
        <th lay-data="{field: 'proposal_stock', align:'center', width:100}">建议采购量</th>-->
        <th lay-data="{minWidth:220, templet:'#listBar',align:'center'}">操作</th>
    </tr>
    </thead>
</table>
</div>
</div>
    </div>
</div>
<!--操作-->
<script type="text/html" id="listBar">
    {{# if(d.goods_no && d.sku_no){ }}

    <a class="layui-btn layui-btn-xs layui-btn-normal" lay-event="open" data-height="450px" data-width="800px" data-url="<?=Url::to(['purchase-order/associate-create'])?>?proposal_id={{ d.id }}" data-title="关联采购单">关联采购单</a>

    <a class="layui-btn layui-btn-xs" lay-event="update" data-url="<?=Url::to(['purchase-order/create'])?>?proposal_id={{ d.id }}" data-title="生成采购订单">生成采购订单</a>

    <p>
    {{# if(d.shelve_status == 1){ }}
        <a class="layui-btn layui-btn-xs layui-btn-danger" lay-event="open" data-height="370px" data-width="800px" data-url="<?=Url::to(['purchase-proposal/shelve'])?>?proposal_id={{ d.id }}&status=1" data-title="取消搁置">取消搁置</a>
        {{# }else{ }}
        <a class="layui-btn layui-btn-xs" lay-event="open" data-height="370px" data-width="800px" data-url="<?=Url::to(['purchase-proposal/shelve'])?>?proposal_id={{ d.id }}&status=1" data-title="移入搁置">移入搁置</a>
        {{# } }}

        {{# if(d.shelve_status == 2){ }}
        <a class="layui-btn layui-btn-xs layui-btn-danger" lay-event="open" data-height="370px" data-width="800px" data-url="<?=Url::to(['purchase-proposal/shelve'])?>?proposal_id={{ d.id }}&status=2" data-title="移出问题件">移出问题件</a>
        {{# }else{ }}
        <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="open" data-height="370px" data-width="800px" data-url="<?=Url::to(['purchase-proposal/shelve'])?>?proposal_id={{ d.id }}&status=2" data-title="移入问题件">移入问题件</a>

        {{# } }}
    <div>
        {{# if(d.shelve_status == 3){ }}
        <a class="layui-btn layui-btn-xs layui-btn-danger" lay-event="open" data-height="370px" data-width="800px" data-url="<?=Url::to(['purchase-proposal/shelve'])?>?proposal_id={{ d.id }}&status=3" data-title="移出敏感货">移出敏感货</a>
        {{# }else{ }}
        <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="open" data-height="370px" data-width="800px" data-url="<?=Url::to(['purchase-proposal/shelve'])?>?proposal_id={{ d.id }}&status=3" data-title="移入敏感货">移入敏感货</a>

        {{# } }}

        {{# if(d.shelve_status == 4){ }}
        <a class="layui-btn layui-btn-xs layui-btn-danger" lay-event="open" data-height="370px" data-width="800px" data-url="<?=Url::to(['purchase-proposal/shelve'])?>?proposal_id={{ d.id }}&status=4" data-title="移出定制款">移出定制款</a>
        {{# }else{ }}
        <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="open" data-height="370px" data-width="800px" data-url="<?=Url::to(['purchase-proposal/shelve'])?>?proposal_id={{ d.id }}&status=4" data-title="移入定制款">移入定制款</a>
        {{# } }}
    </div>
    <div>
    <a class="layui-btn layui-btn-warm layui-btn-xs" lay-event="open" data-height="370px" data-width="800px" data-url="<?=Url::to(['purchase-proposal/remakes'])?>?proposal_id={{ d.id }}" data-title="备注">备注</a>
    </div>
    </p>
    <span class="span-goode-name" style="color: red">{{d.remarks}}</span>


    {{# }else{ }}
    <a class="layui-btn layui-btn-xs" lay-event="operating" data-url="<?=Url::to(['purchase-proposal/goods-perfect'])?>?proposal_id={{ d.id }}" data-title="修复">已修复商品</a>
    {{# if(d.order_lists == ''){ }}
    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete" data-url="<?=Url::to(['purchase-proposal/delete'])?>?proposal_id={{ d.id }}">删除</a>
    {{# } }}
    {{# } }}
</script>

<script type="text/html" id="goodsImgTpl">
    {{# if(d.sku_no){ }}
    <a href="{{d.image}}" data-lightbox="pic">
        <img class="layui-circle" src="{{d.image}}?imageView2/2/h/100" width="100"/>
    </a>
    {{# } }}
</script>
<script type="text/html" id="goodsTpl">
    {{# if(d.goods_status == 20){ }}<span class="layui-badge layui-font-12">禁</span>{{# } }}
    {{# if(d.stock_status == 0){ }}<span class="layui-badge layui-bg-orange layui-font-12">暂</span>{{# } }}
    {{# if(d.has_supplier == 1){ }}<a lay-event="update" data-url="<?=Url::to(['goods/view-outside-package'])?>?goods_no={{ d.goods_no }}" style="cursor: pointer"><span class="layui-badge layui-bg-blue layui-font-12">供</span></a>{{# } }}
    {{# if(d.sku_no){ }}
    <b>{{d.sku_no}}</b><br/>
    <div class="span-goode-name">{{d.purchase_title||''}}</div>
    <div class="span-goode-name">{{d.goods_name_cn || (d.goods_name||'')}}</div>
    {{# if(d.ccolour || d.csize){ }}
    <div class="span-goode-name" style="color: orangered">{{d.ccolour||''}} {{d.csize||''}}</div>
    {{# } }}
    <a lay-event="update" data-url="<?=Url::to(['goods/view'])?>?goods_no={{ d.goods_no }}" style="color: #00a0e9">{{d.goods_no}}</a><br/>
    {{# if(d.reference_purchase_url){ }}<a href="{{d.reference_purchase_url}}" target="_blank" style="color: #00a0e9">参考采购链接</a>{{# } }}
    {{# } }}
</script>
<script type="text/html" id="goodsTpl1">
    {{# if(d.sku_no){ }}
    采购价：{{d.price||''}}<br/>
    重量：{{d.real_weight||''}}<br/>
    尺寸：{{d.size||''}}<br/>
    货品种类：{{d.electric_desc||''}}<br/>
    规格型号：{{d.specification||''}}<br/>
    {{# if(d.purchase_url){ }}
     <a lay-event="update" data-url="/purchase-order/index?search=1&PurchaseOrderSearch%5Bsku_no%5D={{d.sku_no}}" data-title="采购订单" style="color: #00a0e9"><i class="layui-icon layui-icon-tabs"></i></a>
     <a href="{{d.purchase_url}}" target="_blank" style="color: #00a0e9">采购链接</a>
    {{# } }}
    {{# } }}
</script>
<script type="text/html" id="goodsCutTpl">
    总销售单量：{{d.all_order||0}}<br/>
    库存：{{d.stock||0 }}<br/>
    在途：{{d.purchase_stock||0 }}<br/>
    订单量：{{d.order_stock||0 }}<br/>
    建议采购量：<span class="{{# if(d.proposal_stock != 1){ }}span-circular-red{{# }else{ }}span-circular-grey{{# }}}">{{d.proposal_stock||0 }}</span><br/>
    采购员：{{d.admin_name||'' }}
</script>
<script type="text/html" id="orderTpl">
    {{# for(let i in d.order_lists){
        var item = d.order_lists[i];}}
    {{# if(i != 0){ }}
    <hr class="layui-border-red">
    {{# } }}
    <a lay-event="update" data-url="<?=Url::to(['order/view'])?>?order_id={{item.order_id ||'' }}" style="color: #00a0e9">{{item.order_id ||'' }}</a> {{item.shop_name ||'' }}{{# if(item.has_ov_stock){ }}<span class="layui-badge layui-font-12">海</span>{{# } }}<br/>
    <?php if(\common\services\sys\AccessService::hasAmount()) { ?>
    {{item.price || 0 }} {{item.currency || '' }} (<span style="color: red"> ￥{{item.rmb_price || 0 }} </span>)  数量：{{item.num|| 0 }}<br/>
    <?php }?>
    {{item.country ||'' }} {{item.add_time ||'' }}
    <span class="span-goode-name" style="color: red;width: 210px">{{item.remarks || ''}}</span>
    {{# } }}
</script>
<script>
    const tableName="purchase-proposal";
</script>
<?php
    $this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.4.4");
    $this->registerJsFile("@adminPageJs/purchase/lists.js?v=0.0.3");
    $this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
    $this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
?>