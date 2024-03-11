<?php
/**
 * @var $this \yii\web\View;
 */

use common\models\financial\CollectionAccount;
use common\models\Shop;
use yii\bootstrap\Html;
use yii\helpers\Url;
?>
    <style>
        html {
            background: #fff;
        }
    </style>
    <form class="layui-form layui-row" id="add" action="<?=Url::to(['collection-bank-cards/create'])?>">

        <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px">

            <div class="layui-form-item">
                <label class="layui-form-label">收款账号</label>
                <div class="layui-input-block">
                    <?= Html::dropDownList('collection_account_id',null,CollectionAccount::getListAccount(),
                        ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width: 260px']) ?>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">收款银行卡</label>
                <div class="layui-input-block">
                    <input type="text" name="collection_bank_cards" lay-verify="required" placeholder="请输入收款银行卡" class="layui-input" style="width: 260px">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">收款币种</label>
                <div class="layui-input-block">
                    <input type="text" name="collection_currency" lay-verify="required" placeholder="请输入收款币种" class="layui-input" style="width: 260px">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">店铺</label>
                <div class="layui-input-block">
                    <?= \yii\helpers\Html::dropDownList('shop_id', null, \common\services\ShopService::getShopMap(),
                        ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width: 260px']); ?>
                </div>
            </div>

            <div class="layui-form-item">
                <div class="layui-input-block">
                    <button class="layui-btn" lay-submit="" lay-filter="form" data-form="add">立即提交</button>
                    <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                </div>
            </div>
        </div>
    </form>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>