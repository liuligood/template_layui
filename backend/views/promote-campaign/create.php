<?php
/**
 * @var $this \yii\web\View;
 */

use common\models\PromoteCampaign;
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
    <form class="layui-form layui-row" id="add" action="<?=Url::to(['promote-campaign/create'])?>">

        <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px">

            <div class="layui-form-item">
                <label class="layui-form-label">店铺名称</label>
                <div class="layui-inline" style="width: 200px">
                    <?= \yii\helpers\Html::dropDownList('shop_id', '', \common\services\ShopService::getOrderShop(),
                        ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2 xiala' ,'lay-search'=>'lay-search']); ?>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">类型</label>
                <div class="layui-inline" style="width: 200px">
                    <?= \yii\helpers\Html::dropDownList('type', '', PromoteCampaign::$type_maps,
                        ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2 xiala' ,'lay-search'=>'lay-search']); ?>
                </div>
            </div>

            <div class="layui-form-item layui-col-md6">
                <label class="layui-form-label">推广活动编号</label>
                <div class="layui-input-block">
                    <input type="text" name="promote_id"  placeholder="请输入推广活动编号" class="layui-input">
                </div>
            </div>

            <div class="layui-form-item layui-col-md6">
                <label class="layui-form-label">推广活动名称</label>
                <div class="layui-input-block">
                    <input type="text" name="promote_name"  placeholder="请输入推广活动名称" class="layui-input">
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