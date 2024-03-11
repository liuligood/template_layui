<?php

use common\models\Supplier;
use common\services\goods\GoodsService;
use yii\helpers\Url;
use yii\widgets\LinkPager;

?>
<style>
    html {
        background: #fff;
    }
    .layui-tab-title .layui-this {
        background-color: #fff;
        color: #000;
    }
    .layui-tab-brief > .layui-tab-title .layui-this a{
        color: rgb(0, 150, 136);
    }
    .layui-tab{
        margin-top: 0;
    }
    .layui-table-th{
        background: #FAFAFA;
        color: #666;
        width: 100px;
        text-align: right;
    }
    .layui-laypage li{
        float: left;
    }
    .layui-laypage .active a{
        background-color: #009688;
        color: #fff;
    }
    .layui-btn-a {
        border: none;
        background-color: rgba(0, 0, 0, 0);
        -webkit-user-select:text;
    }
    .span-goode-name{
        color:#a0a3a6;
        font-size: 13px;
    }
</style>
<div class="layui-col-md9 layui-col-xs11" style="margin:10px 20px 5px 20px">
    <table class="layui-table">
        <tbody>
        <tr>
            <td class="layui-table-th">名称</td>
            <td><?=$info['name']?></td>
            <td class="layui-table-th">联系人</td>
            <td colspan="5"><?=$info['contacts']?></td>
        </tr>
        <tr>
            <td class="layui-table-th">联系电话</td>
            <td><?=$info['contacts_phone']?></td>
            <td class="layui-table-th">链接</td>
            <td colspan="5"><?=$info['url']?></td>
        </tr>
        <tr>
            <td class="layui-table-th">微信号</td>
            <td><?=$info['wx_code']?></td>
            <td class="layui-table-th">合作</td>
            <td colspan="5"><?=empty(Supplier::$is_cooperate_maps[$info['is_cooperate']]) ? $info['is_cooperate'] : Supplier::$is_cooperate_maps[$info['is_cooperate']]?></td>
        </tr>
        <tr>
            <td class="layui-table-th">地址</td>
            <td colspan="5"><?=$info['address']?></td>
        </tr>
        <tr>
            <td class="layui-table-th">备注</td>
            <td colspan="5"><?=$info['desc']?></td>
        </tr>
        </tbody>
    </table>


    <?php
    $startCount = (0 == $pages->totalCount) ? 0 : $pages->offset + 1;
    $endCount = ($pages->page + 1) * $pages->pageSize;
    $endCount = ($endCount > $pages->totalCount) ? $pages->totalCount : $endCount;
    ?>
    <div class="summary" style="margin-top: 25px;">
        第<b><?= $startCount ?>-<?= $endCount ?></b>条，共<b><?= $pages->totalCount ?></b>条数据
    </div>
    <div class="lay-lists">
    <div class="layui-form">
    <table class="layui-table" style="text-align: center">
        <thead>
        <tr>
            <th style="width: 150px">商品主图</th>
            <th style="width: 275px;text-align: center">商品编号</th>
            <th style="width: 425px;text-align: center">商品标题</th>
            <th style="text-align: left">商品信息</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($goods_list)) :?>
            <tr>
                <td colspan="17">无数据</td>
            </tr>
        <?php else: foreach ($goods_list as $k => $v):
        $i = 0;?>
            <tr>
                <td>
                    <a href="<?=$v['image']?>" data-lightbox="pic">
                        <img class="layui-circle" src="<?=$v['image']?>?imageView2/2/h/100" width="100"/>
                    </a>
                </td>
                <td>
                    <b><?=$v['sku_no']?></b><br/>
                    <a class="layui-btn layui-btn-xs layui-btn-a" data-type="url" data-url="<?=Url::to(['goods/view?goods_no='.$v['goods_no']])?>" style="color: #00a0e9;"><?=$v['goods_no']?></a><br/>
                    类目：<b><?=$v['category_name']?></b><br/>
                </td>
                <td>
                    <div class="span-goode-name"><?=$v['goods_name']?></div>
                    <div class="span-goode-name"><?=$v['goods_name_cn']?></div>
                </td>
                <td style="text-align: left">
                    价格:<?=$v['purchase_amount']?><br/>
                    重量:<?=$v['weight']?><br/>
                    颜色:<?=$v['colour']?><br/>
                    状态:<?=$v['status_desc']?>
                </td>
            </tr>
        <?php endforeach;?>
        <?php endif;?>
        </tbody>
    </table>
    </div>
    </div>
    <div class="lay-lists">
        <?= LinkPager::widget(['pagination' => $pages,'options' => ['class' => 'layui-box layui-laypage layui-laypage-default'],]) ?>
    </div>
</div>
<?php
$this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
?>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=".time())?>