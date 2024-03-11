<?php
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
    <form class="layui-form layui-row" id="update" action="<?=Url::to(['financial-platform-sales-period/update'])?>">

        <div class="layui-col-md12 layui-col-xs12" style="padding-top: 15px">

            <div class="layui-form-item" style="padding-left: 40px">
                店铺名称:
                <div class="layui-inline">
                    <?= \yii\helpers\Html::dropDownList('shop_id', $info['shop_id'], \common\services\ShopService::getOrderShop(),
                        ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2 xiala' ,'lay-search'=>'lay-search']); ?>
                </div>
                货币：
                <div class="layui-inline">
                    <?= \yii\helpers\Html::dropDownList('currency',$info['currency'], $cuns,
                        ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2 xiala' ,'lay-search'=>'lay-search']); ?>
                </div>
                日期:
                <div class="layui-inline">
                    <input  class="layui-input search-con ys-date" name="date" value="<?= date('Y-m-d',$info['data'])?>"  lay-verify="required" id="date" autocomplete="off">
                </div>
                日期:
                <div class="layui-inline">
                    <input  class="layui-input search-con ys-date" name="stop_date" value="<?= date('Y-m-d',$info['stop_data'])?>"  lay-verify="required" id="stop_date" autocomplete="off">
                </div>
            </div>
            <?php if($count == 0){ ?>
            <div class="layui-form-item" style="padding-left: 40px">
                销售金额:
                <div class="layui-inline">
                    <input type="text" name="sales_amount" lay-verify="required" placeholder="请输入销售金额" value="<?=$info['sales_amount']?>" class="layui-input">
                </div>
                退款金额:
                <div class="layui-inline">
                    <input type="text" name="refund_amount" lay-verify="required" placeholder="请输入退款金额" value="<?=$info['refund_amount']?>" class="layui-input">
                </div>
                佣金:
                <div class="layui-inline">
                    <input type="text" name="commission_amount" lay-verify="required" placeholder="请输入佣金" value="<?=$info['commission_amount']?>" class="layui-input">
                </div>
                运费:
                <div class="layui-inline">
                    <input type="text" name="freight" lay-verify="required" placeholder="请输入运费" value="<?=$info['freight']?>" class="layui-input">
                </div>
                促销活动:
                <div class="layui-inline">
                    <input type="text" name="promotions_amount" lay-verify="required" placeholder="请输入促销活动" value="<?=$info['promotions_amount']?>" class="layui-input">
                </div>
                其他费用:
                <div class="layui-inline">
                    <input type="text" name="order_amount" lay-verify="required" placeholder="请输入其他费用" value="<?=$info['order_amount']?>" class="layui-input">
                </div>
            </div>
            <div class="layui-form-item" style="padding-left: 40px">
                回款:
                <div class="layui-inline">
                    <input type="text" name="payment_amount" lay-verify="required" value="0"  placeholder="请输入回款" value="<?=$info['payment_amount']?>" class="layui-input">
                </div>
            </div>
            <?php }?>
            <div class="layui-form-item layui-col-md6">
                <label class="layui-form-label">备注</label>
                <div class="layui-input-block">
                    <textarea type="text" name="remark"  placeholder="请输入备注" style="height: 100px" class="layui-input"><?=$info['remark']?></textarea>
                </div>
            </div>
            <div class="layui-form-item">
                <div class="layui-input-block">
                    <input type="hidden" name="id" value="<?=$info['id']?>">
                    <button class="layui-btn" lay-submit="" lay-filter="form" data-form="update">立即提交</button>
                </div>
            </div>
        </div>
    </form>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>