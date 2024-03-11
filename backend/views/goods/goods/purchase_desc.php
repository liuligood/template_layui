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
    </style>
    <form class="layui-form layui-row" id="add" action="<?=Url::to(['goods/update-purchase-desc'])?>">

        <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px">

            <div class="layui-form-item">
                <label class="layui-form-label">采购备注</label>
                <div class="layui-input-block">
                    <textarea name="purchase_desc" placeholder="请输入采购备注" class="layui-textarea" style="width: 400px;height: 200px"><?=empty($goods['purchase_desc']) ? '' : $goods['purchase_desc']?></textarea>
                </div>
            </div>

            <div class="layui-form-item">
                <div class="layui-input-block">
                    <input name="goods_no" value="<?=$goods_no?>" type="hidden">
                    <button class="layui-btn" lay-submit="" lay-filter="form" data-form="add">立即提交</button>
                    <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                </div>
            </div>
        </div>
    </form>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>