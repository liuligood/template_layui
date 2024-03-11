<?php
/**
 * @var $this \yii\web\View;
 */

use common\models\warehousing\BlContainer;
use common\services\warehousing\WarehouseService;
use yii\helpers\Url;
use yii\helpers\Html;
?>
    <style>
        html {
            background: #fff;
        }
        .country {
            width: 200px;
            padding-left: 7px;
            padding-right: 15px;
        }
    </style>
    <form class="layui-form layui-row" id="add" action="<?=Url::to([$info->isNewRecord?'bl-container/create':'bl-container/update'])?>">
        <div class="layui-col-md12   layui-col-xs12" style="padding-top: 15px; padding-left: 60px;">
            <div class="layui-form-item" >
                <?php if ($info['status'] == BlContainer::STATUS_NOT_DELIVERED) {?>

                提单箱编号
                <div class="layui-inline country">
                    <input type="text" name="bl_no" lay-verify="required" value="<?=$info['bl_no']?>" placeholder="请输入提单箱编号" class="layui-input ">
                </div>
                <?php } ?>


                箱子序号
                <div class="layui-inline country">
                    <input type="text" name="initial_number" lay-verify="required" value="<?=$info['initial_number']?>" placeholder="请输入序号" class="layui-input ">
                </div>
            </div>

            <div class="layui-form-item">
                包装尺寸(cm)
                <div class="layui-inline" style="width: 80px">
                    <input type="text" name="size_l" lay-verify="number" placeholder="长"  value="<?=empty($size)?'':$size['size_l']?>" class="layui-input" autocomplete="off">
                </div>
                <div class="layui-inline" style="width: 80px">
                    <input type="text" name="size_w" lay-verify="number" placeholder="宽"  value="<?=empty($size)?'':$size['size_w']?>" class="layui-input" autocomplete="off">
                </div>
                <div class="layui-inline" style="width: 80px">
                    <input type="text" name="size_h" lay-verify="number" placeholder="高"  value="<?=empty($size)?'':$size['size_h']?>" class="layui-input" autocomplete="off">
                </div>
                重量
                <div class="layui-inline country" style="width: 130px">
                    <input type="text" name="weight" lay-verify="required" placeholder="请输入重量" value="<?=$info['weight']?>" class="layui-input">
                </div>
            </div>

            <div class="lay-lists" style="width: 750px">
                <div class="layui-card-header" style="border-bottom: 0px;">商品信息
                    <?php if (empty($ovg_id)) { ?>
                    <a class="layui-btn layui-btn-normal layui-btn-xs" data-type="open" data-width="1200px" data-height="600px" data-title="添加商品" style="margin: 15px;float: right" data-url="<?=Url::to(['goods/select-index?tag=1'])?>">添加商品</a>
                    <?php } ?>
                </div>
                <table class="layui-table" style="text-align: center;font-size: 13px">
                    <thead>
                    <tr>
                        <th colspan="2">商品</th>
                        <th>数量</th>
                        <?php if (empty($ovg_id)) { ?>
                        <th></th>
                        <?php } ?>
                    </tr>
                    </thead>
                    <tbody id="goods">
                    </tbody>
                </table>
            </div>
            <div class="layui-form-item layui-layout-admin">
                <div class="layui-input-block">
                    <div class="layui-footer" style="left: 0;">
                    <input type="hidden" name="id" value="<?=$info['id']?>">
                    <input type="hidden" name="warehouse_id" value="<?=empty($info['warehouse_id']) ? '' : $info['warehouse_id']?>">
                    <button class="layui-btn" lay-submit="" lay-filter="form" data-form="add">立即提交</button>
                    <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
<script id="goods_tpl" type="text/html">
    <tr class="goods_list">
        <td>
            <img class="layui-upload-img" style="max-width: 100px;height: 80px"  src="{{ d.goods.goods_img || '' }}">
        </td>
        <td align="left" width="400">
            {{ d.goods.sku_no || '' }}<br/>
            <span class="span-goode-name">{{ d.goods.goods_name || '' }}</span><br/>
            <div style="color: red">{{d.ccolour || ''}} {{d.csize || ''}}</div>
        </td>
        <td>
            <input style="width: 70px;text-align:center;padding-left:0"  type="text" name="num[{{ d.goods.cgoods_no || '' }}]" lay-verify="number" value="{{ d.goods.num || '' }}" class="layui-input goods_num">
            {{# if(typeof d.goods.ovg_id !== 'undefined') { }}
            <input type="hidden" class="ovg_ids" name="ovg[{{ d.goods.cgoods_no }}][{{ d.goods.ovg_id }}]" value="{{ d.goods.num || '' }}">
            {{# } }}
            {{# if(typeof d.goods.ovg_id === 'undefined' && d.is_ovg_id == 1) { }}
            <input type="hidden" name="ovg" value="1">
            {{# } }}
        </td>
        {{# if(d.is_ovg_id == 0 ) { }}
        <td>
            <a class="del-goods" style="cursor: pointer;color: #00a0e9">删除</a>
        </td>
        {{# } }}
    </tr>
</script>
<script>
    var bl_container_goods = <?=empty($goods) ? 0 : json_encode($goods)?>;
    var ovg_id = <?=empty($ovg_id) ? 0 : 1?>;
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.8")?>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>
<?=$this->registerJsFile("@adminPageJs/bl-container/form.js?v=".time())?>
