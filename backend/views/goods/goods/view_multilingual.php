<?php

use common\services\warehousing\WarehouseService;
use yii\helpers\Url;
use common\components\statics\Base;
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
    .span-circular-red{
        display: inline-block;
        min-width: 16px;
        height: 16px;
        border-radius: 50%;
        background-color: #ff6666;
        color: #fff;
        text-align: center;
        padding: 5px;
        line-height: 16px;
    }
    .span-circular-grey{
        display: inline-block;
        min-width: 16px;
        height: 16px;
        border-radius: 50%;
        background-color: #999999;
        color: #fff;
        text-align: center;
        padding: 5px;
        line-height: 16px;
    }
    .span-circular-blue{
        display: inline-block;
        min-width: 16px;
        height: 16px;
        border-radius: 80%;
        background-color: #3b97d7;
        color: #fff;
        text-align: center;
        padding: 5px;
        line-height: 16px;
    }
    .span-goode-name{
        color:#a0a3a6;
        font-size: 13px;
    }
    .layui-btn-a {
        border: none;
        background-color: rgba(0, 0, 0, 0);
        -webkit-user-select:text;
    }

    .layui-laypage li{
        float: left;
    }
    .layui-laypage .active a{
        background-color: #009688;
        color: #fff;
    }
</style>


<div class="layui-tab layui-tab-brief">
    <ul class="layui-tab-title">
        <li><a href="<?=Url::to(['goods/view?goods_no='.$goods_no])?>">商品信息</a></li>
        <li class="layui-this"><a href="<?=Url::to(['goods/view-multilingual?goods_no='.$goods_no])?>">多语言</a></li>
        <li><a href="<?=Url::to(['goods/view-outside-package?goods_no='.$goods_no])?>">采购信息</a></li>
        <li><a href="<?=Url::to(['goods/view-order?goods_no='.$goods_no])?>">订单</a></li>
        <li><a href="<?=Url::to(['goods/view-purchase?goods_no='.$goods_no])?>">采购</a></li>
    </ul>
</div>

<div class="layui-col-md9 layui-col-xs12" style="margin:0 20px 30px 20px">
    <div class="lay-lists">

        <div class="layui-form" id="outside_package">
            <div class="summary" style="margin-top: 10px;">
                语言信息
                <a class="layui-btn layui-btn-xs" data-type="url" data-url="<?=Url::to(['goods/update-multilingual'])?>?goods_no=<?=$goods_no?>" data-title="添加语言" data-callback_title="商品编辑列表" style="float: right">添加语言</a>
            </div>
            <table class="layui-table" style="text-align: center">
                <thead>
                <tr>
                    <th style="width: 60px">商品图片</th>
                    <th style="width: 275px;text-align: center">标题</th>
                    <th>语言</th>
                    <th>修改时间</th>
                    <th>添加时间</th>
                    <th style="width: 270px;text-align: center">操作</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($list)): ?>
                    <tr>
                        <td colspan="17">无数据</td>
                    </tr>
                <?php else: foreach ($list as $k => $v):
                    $i = 0;?>
                    <tr>
                        <td>
                            <?php if(!empty($v['goods_image'])):?>
                                <div class="goods_img" style="position:relative;cursor: pointer;">
                                    <img class="layui-circle pic" src="<?=empty($v['goods_image'])?'':$v['goods_image']?>"/>
                                    <div class="big_img" style="top: auto;bottom: 0px;position:absolute; z-index: 100;left: 120px; display: none ;">
                                        <div>
                                            <img src="<?=empty($v['goods_image'])?'':$v['goods_image']?>" width="300" style="max-width:350px;border:2px solid #666;">
                                        </div>
                                    </div>
                                </div>
                            <?php endif;?>
                        </td>
                        <td><?=$v['goods_name']?></td>
                        <td><?=$v['language_name']?></td>
                        <td><?=$v['update_time']?></td>
                        <td><?=$v['add_time']?></td>
                        <td>
                            <a class="layui-btn layui-btn-normal layui-btn-xs" data-type="url"  data-url="<?=Url::to(['goods/update-multilingual?goods_no='.$v['goods_no'].'&language='.$v['language']])?>" data-title="修改语言" data-callback_title="商品编辑列表">编辑</a>
                            <a class="layui-btn layui-btn-danger layui-btn-xs" data-type="cancel" data-url="<?=Url::to(['goods/delete-multilingual?id='.$v['id']])?>">删除</a>
                        </td>
                    </tr>
                <?php endforeach;
                endif;?>
                </tbody>
            </table>
        </div>

        <div class="layui-form" id="outside_package">
            <div class="summary" style="margin-top: 10px;">
                平台信息
                <a class="layui-btn layui-btn-xs" data-type="url" data-url="<?=Url::to(['goods/update-multilingual?type=2'])?>&goods_no=<?=$goods_no?>&all_category=1" data-title="添加语言" data-callback_title="商品编辑列表" style="float: right">添加分类属性</a>
            </div>
            <table class="layui-table" style="text-align: center">
                <thead>
                <tr>
                    <th style="width: 275px;text-align: center">平台</th>
                    <th>分类名称</th>
                    <th>修改时间</th>
                    <th>添加时间</th>
                    <th style="width: 270px;text-align: center">操作</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($platform_information)): ?>
                    <tr>
                        <td colspan="17">无数据</td>
                    </tr>
                <?php else: foreach ($platform_information as $k => $v):
                    $i = 0;?>
                    <tr>
                        <td><?=$v['platform_name']?></td>
                        <td><?=$v['category_name']?></td>
                        <td><?=$v['update_time']?></td>
                        <td><?=$v['add_time']?></td>
                        <td>
                            <a class="layui-btn layui-btn-normal layui-btn-xs" data-type="url"  data-url="<?=Url::to(['goods/update-multilingual?type=2&goods_no='.$v['goods_no'].'&platform_type='.$v['platform_type'].'&all_category=1'])?>" data-title="修改语言" data-callback_title="商品编辑列表">编辑</a>
                            <a class="layui-btn layui-btn-danger layui-btn-xs" data-type="cancel" data-url="<?=Url::to(['goods/delete-information?id='.$v['id']])?>">删除</a>
                        </td>
                    </tr>
                <?php endforeach;
                endif;?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.7")?>
<?=$this->registerJsFile("@adminPageJs/purchase_order/lists.js?v=".time())?>
