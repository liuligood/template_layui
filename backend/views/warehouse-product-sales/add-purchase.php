<?php
use yii\helpers\Url;
?>
<style>
    html {
        background: #fff;
    }
    .layui-form-item{
        margin-bottom: 5px;
    }
    .lay-image{
        float: left;padding: 20px; border: 1px solid #eee;margin: 5px
    }
    .layui-btn-a {
        border: none;
        background-color: rgba(0, 0, 0, 0);
        -webkit-user-select:text;
    }
</style>
<form class="layui-form layui-row" id="update_goods" action="<?=Url::to(['warehouse-product-sales/add-purchase'])?>">
    <div class="layui-col-md12 layui-col-xs12" style="padding: 0 10px">

        <div class="layui-field-box">
            <div style="padding: 2px; background-color: #f2f2f2;margin: 15px 0">
                <div class="layui-row layui-col-space15">
                    <div class="layui-col-md12">
                        <div class="layui-card" style="padding: 5px">
                            <div class="layui-inline lay-lists" style="display: flex">
                                <div>
                                    <?php if(!empty($data['image'])):?>
                                        <a href="<?=$data['image']?>" data-lightbox="pic">
                                            <img class="layui-upload-img" style="max-width: 95px;height: 95px"  src="<?=$data['image']?>">
                                        </a>
                                    <?php endif;?>
                                </div>
                                <div style="margin-left: 8px;padding-top: 5px">
                                    仓库:<?=$data['warehouse_name']?>
                                    <br/>
                                    <?php
                                    if(!empty($data['goods_no'])){?>
                                        <a class="layui-btn layui-btn-xs layui-btn-a open_window" data-type="url" data-url="<?=Url::to(['goods/view'])?>?goods_no=<?=$data['goods_no'] ?>" data-title="商品信息" style="color: #00a0e9;margin-left: 0px;font-size: 13px"><?=$data['sku_no']?></a>
                                    <?php } else { ?>
                                        <?=$data['sku_no']?>
                                    <?php } ?>
                                    <br/>
                                    <?=empty($data['goods_name']) ? '' : $data['goods_name']?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="layui-form-item">
                <div class="layui-inline">
                    <label class="layui-form-label">供应商</label>
                    <div class="layui-input-block">
                        <select class="layui-input search-con ys-select2 select_weight" lay-verify="required" style="width: 240px" lay-ignore name="supplier_id">
                            <option value="0">无供应商【<?=$data['price']?>】</option>
                            <?php foreach ($data['supplier'] as $k => $v){?>
                                <option value="<?=$v['supplier_id']?>" <?php if($v['is_prior'] == 1){?>selected<?php }?> ><?=$v['name']?>【<?=$v['purchase_amount']?>】</option>
                            <?php }?>
                        </select>
                    </div>
                </div>
                <div class="layui-inline">
                    <label class="layui-form-label">采购数量</label>
                    <div class="layui-input-block" style="width: 70px">
                        <input type="text" name="num" lay-verify="required|number" placeholder="请输入采购数量"  value="1" class="layui-input" autocomplete="off">
                    </div>
                </div>
            </div>

            <div class="layui-form-item layui-layout-admin">
                <div class="layui-input-block">
                    <div class="layui-footer" style="left: 0;">
                        <input type="hidden" name="cgoods_no" value="<?=$data['cgoods_no']?>" class="layui-input">
                        <input type="hidden" name="warehouse_id" value="<?=$data['warehouse_id']?>" class="layui-input">
                        <button class="layui-btn" lay-submit="" lay-filter="form" data-form="update_goods">立即提交</button>
                    </div>
                </div>
            </div>
        </div>

    </div>

</form>

<?php
$this->registerJsFile("@adminPageJs/base/form.js?".time());
$this->registerJsFile("@adminPageJs/warehouse-product-sales/lists.js?".time());
$this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
?>