<?php
/**
 * @var $this \yii\web\View;
 */

use common\models\warehousing\BlContainer;
use yii\helpers\Url;

?>
<style>
    html {
        background: #fff;
    }
    .layui-table-th{
        background: #FAFAFA;
        color: #666;
        width: 100px;
        text-align: right;
    }
    .span-goods-name{
        color:#a0a3a6;
        font-size: 13px;
    }
    .layui-tab-brief > .layui-tab-title .layui-this a{
        color: rgb(0, 150, 136);
    }
    .layui-btn-a {
        border: none;
        background-color: rgba(0, 0, 0, 0);
        -webkit-user-select:text;
    }
</style>
<div class="layui-col-md9 layui-col-xs12 lay-lists" style="margin:20px 20px 30px 20px">
    <div style="clear: both"></div>
    <table class="layui-table">
        <tbody>
        <tr>
            <td class="layui-table-th">仓库</td>
            <td><?=$model['warehouse_id']?></td>
            <td class="layui-table-th">国家</td>
            <td colspan="5"><?=$model['country']?></td>
        </tr>
        <tr>
            <td class="layui-table-th">物流编号</td>
            <td colspan="5"><?=$model['track_no']?></td>
        </tr>
        <tr>
            <td class="layui-table-th">发货时间</td>
            <td><?=$model['delivery_time']?></td>
            <td class="layui-table-th">预计到达时间</td>
            <td><?=$model['arrival_time']?></td>
            <td class="layui-table-th">运输方式</td>
            <td><?=$model['transport_type']?></td>
        </tr>
        <tr>
            <td class="layui-table-th">单价</td>
            <td><?=$model['unit_price']?></td>
            <td class="layui-table-th">价格</td>
            <td colspan="5"><?=$model['price']?></td>
        </tr>
        <tr>
            <td class="layui-table-th">估算重量</td>
            <td><?=$model['estimate_weight']?></td>
            <td class="layui-table-th">重量</td>
            <td><?=$model['weight']?></td>
            <td class="layui-table-th">材积</td>
            <td><?=$model['cjz']?></td>
        </tr>
        <tr>
            <td class="layui-table-th">箱子数量</td>
            <td><?=$model['bl_container_count']?></td>
            <td class="layui-table-th">商品总数</td>
            <td><?=$model['goods_count']?></td>
            <td class="layui-table-th">状态</td>
            <td><?=$model['status']?></td>
        </tr>
        </tbody>
    </table>
    提单箱信息
    <?php if (empty($bl_container)){?>
    <table class="layui-table">
        <tbody>
        <tr>
            <th>商品图片</th>
            <th>商品名称</th>
            <th>预计运费(单价)</th>
            <th>数量</th>
            <th>预计运费(总价)</th>
            <th>到货数量</th>
        </tr>
        <tr>
            <td colspan="17" style="text-align: center">无数据</td>
        </tr>
        </tbody>
    </table>
    <?php } else {?>
        <?php foreach ($bl_container as $container) {?>
            <table class="layui-table" style="margin-bottom: 25px">
            <tbody>
            <tr>
                <td class="layui-table-th" style="text-align: left">序号：<?=$container['initial_number']?></td>
                <td class="layui-table-th" style="width: 300px;text-align: left">提单箱编号：<?=$container['bl_no']?></td>
                <td class="layui-table-th" style="text-align: left">
                    重量：<?=$container['weight']?><br/>
                    材积重：<?=$container['cjz']?>
                </td>
                <td class="layui-table-th" style="text-align: left">尺寸：<?=$container['size']?></td>
                <td class="layui-table-th" style="text-align: left">总商品数量：<?=$container['goods_count']?></td>
                <td class="layui-table-th" style="text-align: left">状态：<?=$container['status']?></td>
            </tr>
            <tr>
                <th>商品图片</th>
                <th>商品名称</th>
                <th>预计运费(单价)</th>
                <th>数量</th>
                <th>预计运费(总价)</th>
                <th>到货数量</th>
            </tr>
            <?php foreach ($container['bl_goods'] as $goods){?>
            <tr>
                <td>
                    <a href="<?=empty($goods['goods_img']) ? '':$goods['goods_img']?>" data-lightbox="pic">
                        <img class="layui-upload-img" style="width: 80px;"  src="<?=empty($goods['goods_img'])?'':$goods['goods_img']?>">
                    </a>
                </td>
                <td>
                    <b>
                        <?php if(!empty($goods['goods_no'])){?>
                            <a class="layui-btn layui-btn-xs layui-btn-a" data-type="url" data-url="<?=Url::to(['goods/view'])?>?goods_no=<?= $goods['goods_no'] ?>" data-title="商品信息" style="color: #00a0e9"><?=$goods['sku_no']?></a>
                        <?php } else { ?>
                            <?=$goods['sku_no']?>
                        <?php } ?><br/>
                        <span class="span-goods-name"><?=empty($goods['goods_name']) ? '':$goods['goods_name']?></span>
                </td>
                <td><?=$goods['price']?></td>
                <td><?=$goods['num']?></td>
                <td><?=$goods['price'] * $goods['num']?></td>
                <td><?=$goods['finish_num']?></td>
            </tr>
            <?php }?>
            </tbody>
            </table>
        <?php }?>
    <?php }?>
</div>
<?php
$this->registerJsFile('@adminPlugins/extend/lodop.js?v=0.2',['depends'=>'yii\web\JqueryAsset']);
$this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
?>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.7")?>