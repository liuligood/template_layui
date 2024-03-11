<?php
/**
 * @var $this \yii\web\View;
 */

use common\services\financial\PlatformSalesPeriodService;
use yii\helpers\Url;
?>
    <style>
        html {
            background: #fff;
        }
        .xiala{
            width: 200px;
        }
    </style>
    <form class="layui-form layui-row" id="add" action="<?=Url::to(['financial-period-rollover/create-rollover'])?>">

        <div class="layui-col-md12 layui-col-xs12" style="padding-top: 15px">

            <div class="layui-form-item" style="padding-left: 40px">
                流水订单号:
                <div class="layui-inline" style="width: 200px">
                    <input type="text" name="relation_no" placeholder="请输入流水订单号" class="layui-input">
                </div>
                操作类型：
                <div class="layui-inline">
                    <?= \yii\helpers\Html::dropDownList('operation','', PlatformSalesPeriodService::$OPREATION_MAP,
                        ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2 xiala' ,'lay-search'=>'lay-search']); ?>
                </div>
                金额:
                <div class="layui-inline">
                    <input type="text" name="amount" lay-verify="number" placeholder="请输入金额" class="layui-input">
                </div>
            </div>

            <div class="layui-form-item" style="padding-left: 40px">
                出账时间:
                <div class="layui-inline" style="width: 200px">
                    <input  class="layui-input search-con ys-date" name="date" id="date" value="<?=$stop_data?>" autocomplete="off">
                </div>
                回款时间：
                <div class="layui-inline">
                    <input  class="layui-input search-con ys-date" name="collection_time"  id="collection_time" autocomplete="off">
                </div>
                交易流水号:
                <div class="layui-inline">
                    <input type="text" name="identifier" placeholder="请输入交易流水号" class="layui-input">
                </div>
            </div>

            <div class="layui-form-item" style="padding-left: 40px">
                操作人消息:
                <div class="layui-inline" style="width: 200px">
                    <input type="text" name="buyer" placeholder="请输入操作人消息" class="layui-input">
                </div>
                操作单消息：
                <div class="layui-inline">
                    <input type="text" name="offer" placeholder="请输入操作单消息" class="layui-input">
                </div>
            </div>
        </div>
        <div class="layui-form-item">
            <div class="layui-input-block">
                <input type="hidden" name="shop_id" value="<?=$shop_id?>">
                <input type="hidden" name="financial_id" value="<?=$id?>">
                <input type="hidden" name="currency" value="<?=$currency?>">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="add">立即提交</button>
            </div>
        </div>
        </div>
    </form>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>