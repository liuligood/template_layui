
<?php

use common\models\order\OrderTransport;
use common\models\sys\ShippingMethod;
use common\services\ShopService;
use common\services\transport\TransportService;
use common\services\warehousing\WarehouseService;
use yii\helpers\Url;
use yii\helpers\Html;
use yii\widgets\LinkPager;
use common\components\statics\Base;
use common\models\Order;
?>

<style>
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
    .amount {
      color: red;
    }
</style>
<div class="layui-fluid">
    <div class="layui-card">
        <div class="layui-card-body">
            <div class="lay-lists">
                <form class="layui-form">
                    <div class="lay-search">
                        <div class="layui-inline">
                            <label>订单号</label>
                            <textarea name="OrderTransportSearch[order_id]" class="layui-textarea search-con" style="height: 39px; min-height:39px"><?=$searchModel['order_id'];?></textarea>
                        </div>
                        <div class="layui-inline">
                            <label>第三方订单号</label>
                            <textarea name="OrderTransportSearch[order_code]" class="layui-textarea search-con" style="height: 39px; min-height:39px"><?=$searchModel['order_code'];?></textarea>
                        </div>
                        <div class="layui-inline">
                            平台
                            <?= Html::dropDownList('OrderTransportSearch[source]', $searchModel['source'], Base::$platform_maps,
                                ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px','id'=>'platform' ]) ?>
                        </div>
                        <div class="layui-inline">
                            店铺
                            <?= \backend\widgets\LinkageDropDownList::widget(['id' => 'shop','parent_id'=>'platform','name'=>'OrderTransportSearch[shop_id]','select'=>$searchModel['shop_id'],'option'=> ShopService::getOrderShopMap(),'param'=>['data-placeholder' => '全部','prompt' => '全部','style'=>'width:180px']]) ?>
                        </div>
                        <div class="layui-inline">
                            <label>销售单号</label>
                            <textarea name="OrderTransportSearch[relation_no]" class="layui-textarea search-con" style="height: 39px; min-height:39px"><?=$searchModel['relation_no'];?></textarea>
                        </div>
                        <div class="layui-inline">
                            <label>SKU</label>
                            <textarea name="OrderTransportSearch[platform_asin]" class="layui-textarea search-con" style="height: 39px; min-height:39px"><?=$searchModel['platform_asin'];?></textarea>
                        </div>
                        <div class="layui-inline">
                            <label>商品名称</label>
                            <input class="layui-input search-con" name="OrderTransportSearch[goods_name]" value="<?=htmlentities($searchModel['goods_name'], ENT_COMPAT);?>"  autocomplete="off">
                        </div>
                        <div class="layui-inline">
                            <label>买家名称</label>
                            <input class="layui-input search-con" name="OrderTransportSearch[buyer_name]" value="<?=htmlentities($searchModel['buyer_name'], ENT_COMPAT);?>"  autocomplete="off">
                        </div>
                        <div class="layui-inline" style="width: 200px">
                            <label>国家</label>
                            <?= Html::dropDownList('OrderTransportSearch[country]', $searchModel['country'], $country_arr,['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>"layui-input search-con ys-select2",'style' => 'width:200px']) ?>
                        </div>
                        <div class="layui-inline">
                            仓库
                            <?= Html::dropDownList('OrderTransportSearch[warehouse_id]', $searchModel['warehouse_id'], WarehouseService::getWarehouseMap(),
                                ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px' ]) ?>
                        </div>
                        <div class="layui-inline">
                            物流方式
                            <?= Html::dropDownList('OrderTransportSearch[shipping_method_id]', $searchModel['shipping_method_id'], TransportService::getShippingMethodOptions(false,true),
                                ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px' ]) ?>
                        </div>
                        <div class="layui-inline">
                            状态
                            <?= Html::dropDownList('OrderTransportSearch[status]', $searchModel['status'], OrderTransport::$status_maps,
                                ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px' ]) ?>
                        </div>

                        <div class="layui-inline layui-vertical-20">
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
                        <div style="float: right">
                        <?php foreach ($amount as $item) {?>
                            <?=$item['exchange_name']?>总额: <i class="amount"><?=$item['origin_total']?> (<?=$item['cn_total']?> CNY)</i>
                            &nbsp;
                        <?php }?>
                            人民币总额: <i class="amount"><?=round($cn_amount,2)?></i>
                        </div>
                    </div>
                    <div class="layui-form">
                        <table class="layui-table" style="text-align: center">
                            <thead>
                            <tr>
                                <th><input type="checkbox" lay-filter="select_all" id="select_all" name="select_all" lay-skin="primary" title=""></th>
                                <th style="width: 60px">商品图片</th>
                                <th>商品信息</th>
                                <th>订单号</th>
                                <th>来源</th>
                                <th>收件人信息</th>
                                <th style="width: 250px">物流信息</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($list)): ?>
                                <tr>
                                    <td colspan="17">无数据</td>
                                </tr>
                            <?php else: foreach ($list as $k => $v):
                                $i = 0;?>
                                <?php foreach ($v['goods'] as $goods_k => $goods_v):
                                $sku_no = empty($goods_v['platform_asin'])?'':$goods_v['platform_asin'];
                                $i ++;
                                ?>
                                <tr>
                                    <?php if($i == 1):?>
                                        <td rowspan="<?=$v['goods_count']?>"><input type="checkbox" class="select_order" name="id[]" value="<?=$v['order_id']?>" lay-skin="primary" title=""></td>
                                    <?php endif;?>
                                    <td>
                                        <?php if(!empty($goods_v['goods_pic'])):?>
                                            <div class="goods_img" style="position:relative;cursor: pointer;">
                                                <img class="layui-circle pic" src="<?=empty($goods_v['goods_pic'])?'':$goods_v['goods_pic']?>"/>
                                                <div class="big_img" style="top: auto;bottom: 0px;position:absolute; z-index: 100;left: 120px; display: none ;">
                                                    <div>
                                                        <img src="<?=empty($goods_v['goods_pic'])?'':$goods_v['goods_pic']?>" width="300" style="max-width:350px;border:2px solid #666;">
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif;?>
                                    </td>
                                    <td align="left" style="width: 250px">
                                        <a class="layui-btn layui-btn-xs layui-btn-a" data-type="url" data-url="<?=Url::to(['purchase-order/index?search=1&PurchaseOrderSearch%5Bsku_no%5D='.$sku_no])?>" data-title="采购信息" style="color: #00a0e9"><i class="layui-icon layui-icon-tabs"></i></a>
                                        <b>
                                            <?php
                                            if(!empty($goods_v['goods_no'])){?>
                                                <a class="layui-btn layui-btn-xs layui-btn-a" data-width="550px" data-type="url" data-url="<?=Url::to(['goods/view'])?>?goods_no=<?= $goods_v['goods_no'] ?>" data-title="商品信息" style="color: #00a0e9"><?=$sku_no?></a>
                                            <?php } else { ?>
                                                <?=$sku_no?>
                                            <?php } ?>
                                        </b>  x️ <span class="<?= $goods_v['goods_num'] != 1?'span-circular-red':'span-circular-grey'; ?>"><?=empty($goods_v['goods_num'])?'':$goods_v['goods_num']?></span>
                                        <a class="layui-btn layui-btn-sm layui-btn-a" onclick="copyText('<?=$sku_no?>')" style="color: #00a0e9"><i class="layui-icon layui-icon-list"></i></a>
                                        <br/>
                                        <span class="span-goode-name"><?=empty($goods_v['goods_name'])?'':$goods_v['goods_name']?></span>
                                    </td>
                                    <?php if($i == 1):?>
                                        <td align="left" rowspan="<?=$v['goods_count']?>">
                                            订单号：
                                            <a class="layui-btn layui-btn-primary layui-btn-xs layui-btn-a" data-type="url"  data-url="<?=Url::to(['order/view?order_id='.$v['order_id']])?>" data-title="订单详情"><?= $v['order_id'] ?></a>
                                            <br/>
                                            第三方订单号：
                                            <?= $v['order_code'] ?>
                                            <br/>
                                            销售单号：
                                            <?= $v['relation_no'] ?>
                                        </td>
                                        <td align="left" rowspan="<?=$v['goods_count']?>">
                                            <?= $v['platform_type'] ?><br/>
                                            <?= empty($v['shop_name'])?'':$v['shop_name'] ?><br/>

                                            仓库：<?=$v['warehouse_name']?>
                                            <span style="padding: 1px 5px;float: right" class="layui-font-12 layui-bg-orange"><?= $v['status'] ?></span>
                                        </td>
                                        <td align="left" rowspan="<?=$v['goods_count']?>">
                                            收件人：<?= $v['buyer_name'] ?><br/>
                                            国家：<?= $v['country'] ?>
                                        </td>
                                        <td align="left" rowspan="<?=$v['goods_count']?>">
                                            <?= $v['size'] ?> <?= $v['weight'] ?><br/>
                                            物流方式：<?= $v['shipping_method'] ?><br/>
                                            物流单号：<?= $v['track_no'] ?><br/>
                                            费用：
                                            <a class="layui-btn layui-btn-a" data-width="995px" data-height="430px" data-type="open" data-url="<?=Url::to(['order-transport/fee-detail-view'])?>?order_transport_id=<?= $v['id'] ?>" data-title="费用详情" style="color: #00a0e9;margin: 0;padding: 0;font-size: 14px"><?=$v['total_fee']?></a>
                                            <?=$v['currency']?> (<?=$v['cn_total']?> CNY)
                                        </td>
                                    <?php endif;?>
                                </tr>
                            <?php endforeach;?>
                            <?php
                            endforeach;
                            endif;
                            ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?= LinkPager::widget(['pagination' => $pages,'options' => ['class' => 'layui-box layui-laypage layui-laypage-default'],]) ?></div>
        </div>
    </div>
</div>
<script type="text/javascript">
    function copyText(sku_no) {
        var copyUrl= sku_no;
        var oInput = document.createElement('input');     //创建一个隐藏input（重要！）
        oInput.value = copyUrl;    //赋值
        document.body.appendChild(oInput);
        oInput.select(); // 选择对象
        document.execCommand("Copy"); // 执行浏览器复制命令
        oInput.className = 'oInput';
        oInput.style.display='none';
        layer.msg("复制成功",{icon: 1});
    }
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.8")?>
<?php
$this->registerJsFile('@adminPlugins/extend/lodop.js?v=0.2',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/xlsx.core.min.js?v=1',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export-excel.js?v=1',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export-function.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
?>