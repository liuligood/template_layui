<?php

use common\models\financial\CollectionAccount;
use common\models\financial\CollectionBankCards;
use yii\helpers\Url;
use yii\helpers\Html;
use common\components\statics\Base;
use common\models\Shop;

$admin_id = new Shop();
?>
<style>
    html {
        background: #fff;
    }
</style>
<form class="layui-form layui-row" id="update" action="<?=Url::to([empty($info['id'])?'shop/create':'shop/update'])?>">

    <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px; padding-left: 60px;width: 950px">

        <div class="layui-form-item">
            店铺名称
            <div class="layui-inline">
                <input type="text" name="name" value="<?=$info['name']?>" lay-verify="required" placeholder="请输入店铺名称" class="layui-input">
            </div>

            品牌名称
            <div class="layui-inline">
                <input type="text" name="brand_name" value="<?=$info['brand_name']?>" placeholder="请输入品牌名称" class="layui-input ">
            </div>

            平台类型
            <div class="layui-inline" style="width: 150px">
                <?= \yii\bootstrap\Html::dropDownList('platform_type',$info['platform_type'],Base::$platform_maps,
                    ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search' ]) ?>
            </div>
        </div>

        <div class="layui-form-item">
            收款账号
            <div class="layui-inline" >
                <?= \yii\bootstrap\Html::dropDownList('collection_account_id',$info['collection_account_id'],CollectionAccount::getListAccount(),
                    ['lay-ignore'=>'lay-ignore','prompt' => '无','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:210px','id'=>'account']) ?>
            </div>

            收款银行卡
            <div class="layui-inline">
                <?= \yii\bootstrap\Html::dropDownList('collection_bank_cards_id',$info['collection_bank_cards_id'],CollectionBankCards::getListBank(),
                    ['lay-ignore'=>'lay-ignore','prompt' => '无','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:210px','id'=>'bank' ]) ?>
            </div>
        </div>

        <div class="layui-form-item" style="margin-left: 13px;padding-left: 15px">
            币种
            <div class="layui-inline">
                <input type="text" name="currency" value="<?=$info['currency']?>"  placeholder="请输入币种" class="layui-input ">
            </div>

            负责人
            <div class="layui-inline" style="width: 210px">
                <?= Html::dropDownList('admin_id',$info['admin_id'],$admin_id->adminArr(),
                    ['lay-ignore'=>'lay-ignore','data-placeholder' => '请输入店铺负责人','prompt' => '无','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
            </div>

            状态
            <div class="layui-inline" style="width: 150px;">
                <?= Html::dropDownList('status',$info['status'],Shop::$status_maps,
                    ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search'  ]) ?>
            </div>
        </div>

        <div class="layui-form-item">
            接口权限
            <div class="layui-inline">
                <?php foreach (Shop::$api_assignment_maps as $item_k=>$item_v) { ?>
                    <input type="checkbox" lay-filter="type" lay-skin="primary" name="assignment[]" value="<?=$item_k?>" title="<?=$item_v?>" <?php if(isset($arr_assignment) && in_array($item_v,$arr_assignment)){ echo 'checked'; } ?>>
                <?php }?>
            </div>
        </div>

        <div class="layui-form-item" style="margin-left: 13px;padding-left: 15px">
            站点
            <div class="layui-inline">
                <input type="text" name="country_site" value="<?=$info['country_site']?>"  placeholder="请输入站点" class="layui-input ">
            </div>
        </div>

        <div class="layui-form-item" style="padding-left: -5px;margin-left: -5px">
            client_key
            <div class="layui-inline">
                <input type="text" name="client_key" value="<?=$info['client_key']?>" placeholder="请输入client_key" class="layui-input" style="width: 370px">
            </div>
        </div>

        <div class="layui-form-item" style="padding-left: -10px;margin-left: -10px">
            secret_key
            <div class="layui-inline">
                <input type="text" name="secret_key" value="<?=$info['secret_key']?>" placeholder="请输入secret_key" class="layui-input" style="width: 370px">
            </div>
        </div>

        <div class="layui-form-item" style="margin-left: 17px;padding-left: 17px">
            ioss
            <div class="layui-inline">
                <input type="text" name="ioss" value="<?=$info['ioss']?>" placeholder="请输入ioss" class="layui-input" style="width: 370px">
            </div>
        </div>
        
        <div class="layui-form-item">
            额外参数
            <div class="layui-inline">
                <input type="text" name="param"  placeholder="请输入参数"  value='<?=$info['param']?>' class="layui-input" style="width: 370px">
            </div>
        </div>

        <div class="layui-form-item" style="margin-left: 13px;padding-left: 15px">
            海外仓库
            <div class="layui-inline" style="width: 200px">
                <?= Html::dropDownList('warehouse_id',$info['warehouse_id'],$warehouse_lists,
                    ['lay-ignore'=>'lay-ignore','data-placeholder' => '请输入仓库','prompt' => '无','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
            </div>
        </div>

        <div class="layui-form-item">
            <div class="layui-input-block">
                <input type="hidden" name="shop_id" value="<?=$info['id']?>">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="update">立即提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>

    </div>
</form>
<script>
    var collection = <?=$collection?>;
    var bank_cards = <?=json_encode($bank_cards)?>;
</script>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>
<?=$this->registerJsFile("@adminPageJs/shop/lists.js?".time())?>

