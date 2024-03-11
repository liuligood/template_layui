<?php
use yii\helpers\Url;
use common\models\Goods;
use yii\helpers\Html;
?>
<style>
    html {
        background: #fff;
    }
    .layui-input-block{
        padding-top: 10px;
    }
</style>
<div class="lay-lists">
<div style="padding-right: 120px" >
            <div class="layui-input-block">
                <label>店铺名称</label>
                <?= \yii\helpers\Html::dropDownList('FinancialPlatformSalesPeriodSearch[shop_id]', $ida, \common\services\ShopService::getShopMap(),
                    ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','onchange'=>'change()','id'=>'shop']); ?>
            </div>
        <div class="layui-inline">
			<div class="layui-form-item">
				<div class="layui-input-block" >
                    <button class="layui-btn layui-btn-primary ys-uploadone" lay-data="{accept: 'file'}">导入</button>
				</div>
			</div>

		</div>
</div>
</div>
<script>
    var shop= <?=$ida?>;
    function change(){
        var val = document.getElementById('shop').value;
        var url ='view?id='+val;
        location.href = url;
    }
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js")?>
<?=$this->registerJsFile("@adminPageJs/financial-period-rollover/index.js")?>
