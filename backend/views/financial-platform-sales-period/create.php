<?php
/**
 * @var $this \yii\web\View;
 */
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
    <form class="layui-form layui-row" id="add" action="<?=Url::to(['financial-platform-sales-period/create'])?>">

        <div class="layui-col-md12 layui-col-xs12" style="padding-top: 15px">

            <div class="layui-form-item" style="padding-left: 40px">
                店铺名称:
                <div class="layui-inline" style="width: 200px">
                    <?= \yii\helpers\Html::dropDownList('shop_id', $shop_id, \common\services\ShopService::getOrderShop(),
                        ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2 xiala' ,'lay-search'=>'lay-search']); ?>
                </div>
                货币：
                <div class="layui-inline">
                    <?= \yii\helpers\Html::dropDownList('currency','', $cuns,
                        ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2 xiala' ,'lay-search'=>'lay-search']); ?>
                </div>
                日期:
                <div class="layui-inline">
                    <input  class="layui-input search-con ys-date" name="date"  lay-verify="required" id="date" autocomplete="off">
                </div>
                结束日期:
                <div class="layui-inline">
                    <input  class="layui-input search-con ys-date" name="stop_date"  lay-verify="required" id="stop_date" autocomplete="off">
                </div>
            </div>
        </div>
        <div class="layui-form-item layui-col-md6">
            <label class="layui-form-label">备注</label>
            <div class="layui-input-block">
                <textarea type="text" name="remark" style="height: 100px"  placeholder="请输入备注" class="layui-input"></textarea>
            </div>
        </div>
        <div class="layui-form-item">
            <div class="layui-input-block">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="add">立即提交</button>
            </div>
        </div>
        </div>
    </form>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>