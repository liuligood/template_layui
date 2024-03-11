<?php
/**
 * @var $this \yii\web\View;
 */

use common\models\Supplier;
use yii\helpers\Html;
use yii\helpers\Url;
?>
    <style>
        html {
            background: #fff;
        }
        .layui-input {
            width: 220px;
        }
    </style>
    <?php if (isset($info)) { ?>
        <form class="layui-form layui-row" id="add" action="<?=Url::to(['supplier-relationship/update'])?>">
    <?php } else {?>
        <form class="layui-form layui-row" id="add" action="<?=Url::to(['supplier-relationship/create'])?>">
    <?php }?>
        <div class="layui-col-md12 layui-col-xs12" style="padding-top: 15px;">

            <div class="layui-form-item">
                <div class="layui-inline">
                <label class="layui-form-label">供应商</label>
                <div class="layui-input-block">
                    <?= Html::dropDownList('supplier_id',!isset($info) ? null : $info['supplier_id'], Supplier::allSupplierName(),
                        ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style' => 'width:220px' ]) ?>
                </div>
                </div>

                <div class="layui-inline">
                <label class="layui-form-label">采购金额</label>
                <div class="layui-input-block">
                    <input type="text" name="purchase_amount" lay-verify="required" value="<?=!isset($info) ? 0 : $info['purchase_amount']?>"  placeholder="请输入采购金额" class="layui-input ">
                </div>
                </div>
            </div>

            <div class="layui-form-item">
                <div class="layui-inline">
                <label class="layui-form-label">起购量</label>
                <div class="layui-input-block">
                    <input type="text" name="purchase_count" value="<?=!isset($info) ? 1 : $info['purchase_count']?>" placeholder="请输入起购量" class="layui-input ">
                </div>
                </div>

                <div class="layui-inline">
                <label class="layui-form-label">优先</label>
                <div class="layui-input-block">
                    <?= Html::dropDownList('is_prior',!isset($info) ? null : $info['is_prior'], [1 => '是', 2 => '否'],
                        ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style' => 'width:150px' ]) ?>
                </div>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">备注</label>
                <div class="layui-input-block">
                    <textarea name="desc"  placeholder="请输入备注" class="layui-textarea" style="width: 330px"><?=!isset($info) ? '' : $info['desc']?></textarea>
                </div>
            </div>

            <div class="layui-form-item">
                <div class="layui-input-block">
                    <input type="hidden" name="goods_no" value="<?=!isset($info) ? $goods_no : $info['goods_no']?>">
                    <?php if (isset($info)) {?>
                        <input type="hidden" name="id" value="<?=$info['id']?>">
                    <?php }?>
                    <button class="layui-btn" lay-submit="" lay-filter="form" data-form="add">立即提交</button>
                    <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                </div>
            </div>
        </div>
    </form>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>