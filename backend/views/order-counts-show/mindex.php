<?php

use common\services\ShopService;
use yii\helpers\Url;
?>
<style>
    .layui-card .layui-tab {
        margin: 0;
        padding-bottom: 20px;
    }
</style>
<div class="layui-fluid">
    <div class="layui-card" style="padding-left: 20px;padding-top: 20px">
        <div class="layui-tab layui-tab-brief">
            <ul class="layui-tab-title">
                <li class="layui" ><a href="<?=Url::to(['order-counts-show/index'])?>">日</a></li>
                <li class="layui-this" ><a href="<?=Url::to(['order-counts-show/mouth-index'])?>">月</a></li>
            </ul>
        </div>
<div  class="layui-inline">
    开始时间：
    <div  class="layui-inline">
        <input type="text" class="layui-input" id="stime"  value="<?=date('Y-m',  ($datetime-12*2592000))?>">
    </div>
    结束时间：
    <div  class="layui-inline">
        <input type="text" class="layui-input" id="time"  value="<?=date('Y-m',  $datetime)?>">
    </div>
    平台：
    <div class="layui-inline">
        <?= \yii\helpers\Html::dropDownList('FinancialPlatformSalesPeriodSearch[platform_type]', '', \common\services\goods\GoodsService::$own_platform_type,
            ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','id'=>'platform_type']); ?>
    </div>

    店铺名称：
    <div class="layui-inline">
        <?= \backend\widgets\LinkageDropDownList::widget(['id' => 'shop_id','parent_id'=>'platform_type','name'=>'FinancialPlatformSalesPeriodSearch[shop_id]','select'=>null,'option'=> ShopService::getShopMapIndexPlatform(),'param'=>['data-placeholder' => '全部','prompt' => '全部','style'=>'width:185px']]) ?>
    </div>
    <button class="layui-btn" id="button"  onclick="buttonsClick()">搜索</button>
<!-- 为 ECharts 准备一个定义了宽高的 DOM -->
<div id="main" style="width: 1200px;height:600px;padding-top: 20px"></div>
</div></div>
<script>
    var day = <?=$mouths?>;
    var daytime = [];
    <?php  for ( $i =0; $i<=12;$i++){?>
    <?php if($i==0){?>
    var item = "<?=date('Y-m',  $datetime)?>";
    <?php }else {?>
    <?php $time = $datetime - ($i * 2592000); ?>
    var item = "<?=date('Y-m',  $time)?>";
    <?php }?>
    daytime.push(item);
    <?php }?>
    <?php $i =1; ?>
    daytime.reverse();
</script>
<?=$this->registerJsFile("@adminPageJs/order-counts-show/index.js?".time())?>
<?=$this->registerJsFile("@adminPageJs/base/lists.js")?>

