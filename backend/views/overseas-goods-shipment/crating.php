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
        .country {
            width: 200px;
            padding-left: 7px;
            padding-right: 15px;
        }
    </style>
    <form class="layui-form layui-row" id="crating" action="<?=Url::to(['overseas-goods-shipment/crating'])?>">
        <div class="layui-col-md12 layui-col-xs12" style="padding-top: 15px; padding-left: 60px;">
            <?php if(!empty($goods_packaging)){ ?>
                <div class="layui-form-item" >
                    包装信息
                    <div class="layui-inline">
                        <select data-placeholder="请选择" class="layui-input search-con ys-select2 js-box" lay-ignore name="split_box[]">
                            <option value="">请选择</option>
                            <?php foreach ($goods_packaging as $k=> $v){?>
                                <option value="<?=$v['size']?>" data-size="<?=$v['size']?>" data-weight="<?=$v['weight']?>"><?=$v['size']?><?=!empty($v['show_name'])?(' ('.$v['show_name'].$v['show_name'].') '):'' ?></option>
                            <?php }?>
                        </select>
                    </div>
                    箱子数量
                    <div class="layui-inline" style="width: 80px">
                        <input type="text" lay-verify="required" value="1" class="layui-input js-box-num">
                    </div>
                </div>
            <?php }?>
            <div class="layui-form-item" >
                箱子序号
                <div class="js-initial" style="display: inline">

                </div>
            </div>

            <div class="layui-form-item">
                包装尺寸(cm)
                <div class="layui-inline" style="width: 80px">
                    <input type="text" name="size_l" lay-verify="number|required" placeholder="长"  value="" class="layui-input" autocomplete="off">
                </div>
                <div class="layui-inline" style="width: 80px">
                    <input type="text" name="size_w" lay-verify="number|required" placeholder="宽"  value="" class="layui-input" autocomplete="off">
                </div>
                <div class="layui-inline" style="width: 80px">
                    <input type="text" name="size_h" lay-verify="number|required" placeholder="高"  value="" class="layui-input" autocomplete="off">
                </div>
                重量
                <div class="layui-inline country" style="width: 130px">
                    <input type="text" name="weight" lay-verify="required" placeholder="重量" value="" class="layui-input">
                </div>
            </div>

            <div class="lay-lists" style="width: 750px">
                <div class="layui-card-header" style="border-bottom: 0px;">商品信息</div>
                <table class="layui-table" style="text-align: center;font-size: 13px">
                    <thead>
                    <tr>
                        <th colspan="2">商品</th>
                        <th>数量</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody id="goods">
                    </tbody>
                </table>
            </div>
            <div class="layui-form-item layui-layout-admin">
                <div class="layui-input-block">
                    <div class="layui-footer" style="left: 0;">
                    <input type="hidden" name="warehouse_id" value="<?=$warehouse_id?>">
                    <button class="layui-btn" lay-submit="" lay-filter="form" data-form="crating">立即提交</button>
                    <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
<script id="initial_tpl" type="text/html">
    <div class="layui-inline initial_div" style="width: 80px">
        <input type="text" name="initial_number[]" lay-verify="required" value="" placeholder="序号" class="layui-input">
    </div>
</script>
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
        <td>
            <a class="del-goods" style="cursor: pointer;color: #00a0e9">删除</a>
        </td>
    </tr>
</script>
<script>
    var bl_container_goods = <?=empty($goods) ? 0 : json_encode($goods)?>;
    var ovg_id = <?=empty($ovg_id) ? 0 : 1?>;
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.8")?>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>
<?=$this->registerJsFile("@adminPageJs/bl-container/form.js?v=".time())?>
