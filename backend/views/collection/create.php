<?php
/**
 * @var $this \yii\web\View;
 */

use common\components\statics\Base;
use common\models\financial\Collection;
use common\models\financial\CollectionAccount;
use common\models\financial\CollectionBankCards;
use yii\bootstrap\Html;
use yii\helpers\Url;
?>
    <style>
        html {
            background: #fff;
        }
        .layui-input {
            width: 330px;
        }
    </style>
    <form class="layui-form layui-row" id="add" action="<?=Url::to(['collection/create'])?>">

        <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px">

            <div class="layui-form-item">
                <label class="layui-form-label">回款时间</label>
                <div class="layui-input-block">
                    <input class="layui-input search-con ys-datetime" name="collection_date" lay-verify="required" id="collection_date" autocomplete="off">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">回款账号</label>
                <div class="layui-input-block">
                    <?= Html::dropDownList('collection_account_id',null,CollectionAccount::getListAccount(),
                        ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','id'=>'account' ]) ?>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">回款银行卡</label>
                <div class="layui-input-block">
                    <?= Html::dropDownList('collection_bank_id',null,CollectionBankCards::getListBank(),
                        ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2','prompt' => '全部' ,'lay-search'=>'lay-search','id'=>'bank' ]) ?>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">平台</label>
                <div class="layui-input-block">
                    <?= Html::dropDownList('platform_type',null,Base::$platform_maps,
                        ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2','prompt' => '全部','lay-search'=>'lay-search' ]) ?>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">状态</label>
                <div class="layui-input-block">
                    <?= Html::dropDownList('status',null,Collection::$status_maps,
                        ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2','lay-search'=>'lay-search' ]) ?>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">回款金额</label>
                <div class="layui-input-block">
                    <input name="collection_amount" lay-verify="required" placeholder="请输入回款金额" class="layui-input" style="width: 330px">
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
<script>
    var collection = <?=$collection?>;
    var bank_cards = <?=json_encode($bank_cards)?>;
</script>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>
<?=$this->registerJsFile("@adminPageJs/shop/lists.js?".time())?>

