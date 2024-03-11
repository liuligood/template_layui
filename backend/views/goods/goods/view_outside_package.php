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
    .layui-table-th{
        background: #FAFAFA;
        color: #666;
        width: 100px;
        text-align: right;
    }
</style>


<div class="layui-tab layui-tab-brief">
    <ul class="layui-tab-title">
        <li><a href="<?=Url::to(['goods/view?goods_no='.$goods_no])?>">商品信息</a></li>
        <li><a href="<?=Url::to(['goods/view-multilingual?goods_no='.$goods_no])?>">多语言</a></li>
        <li class="layui-this"><a href="<?=Url::to(['goods/view-outside-package?goods_no='.$goods_no])?>">采购信息</a></li>
        <li><a href="<?=Url::to(['goods/view-order?goods_no='.$goods_no])?>">订单</a></li>
        <li><a href="<?=Url::to(['goods/view-purchase?goods_no='.$goods_no])?>">采购</a></li>
    </ul>
</div>

<div class="layui-col-md9 layui-col-xs12" style="margin:0 20px 30px 20px">

    <div class="lay-lists">

        <div class="layui-form" id="outside_package">
            <div class="summary" style="margin-top: 10px;">
                包装信息
                <a class="layui-btn layui-btn-xs" data-type="open" data-url="<?=Url::to(['goods/create-package'])?>?goods_no=<?=$goods_no?>" data-width="600px" data-height="400px" data-title="包装信息" data-callback_title="商品编辑列表" style="float: right">添加包装信息</a>
            </div>
            <table class="layui-table" style="text-align: center">
                <thead>
                <tr>
                    <th>仓库</th>
                    <th>显示名称</th>
                    <th>尺寸</th>
                    <th>重量</th>
                    <th>包装数量</th>
                    <th style="width: 270px;text-align: center">操作</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($outside_package_list)): ?>
                <tr>
                    <td colspan="17">无数据</td>
                </tr>
                <?php else: foreach ($outside_package_list as $k => $v):
                    $i = 0;?>
                <tr>
                    <td><?=$v['warehouse_name']?></td>
                    <td><?=$v['show_name']?></td>
                    <td><?=$v['size']?></td>
                    <td><?=$v['weight']?></td>
                    <td><?=$v['packages_num']?></td>
                    <td>
                        <a class="layui-btn layui-btn-normal layui-btn-xs" data-type="open"  data-url="<?=Url::to(['goods/update-package?id='.$v['id']])?>" data-width="600px" data-height="400px" data-title="编辑" data-callback_title="商品编辑列表">编辑</a>
                        <a class="layui-btn layui-btn-danger layui-btn-xs" data-type="cancel" data-url="<?=Url::to(['goods/delete-package?id='.$v['id']])?>">删除</a>
                    </td>
                </tr>
                <?php endforeach;
                      endif;?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="layui-form" id="supplier_relationship">
        <div class="summary lay-lists" style="margin-top: 10px;">
            供应关系
            <a class="layui-btn layui-btn-xs" data-type="open" data-width="900px" data-height="500px" data-url="<?=Url::to(['supplier-relationship/create?goods_no='.$goods_no])?>" style="float: right">添加供应关系</a>
        </div>
        <table class="layui-table" style="text-align: center">
            <thead>
            <tr>
                <th style="text-align: center">供应商</th>
                <th style="text-align: center;width: 220px">采购信息</th>
                <th style="text-align: center">最新交易时间</th>
                <th style="text-align: center;width: 80px">优先</th>
                <th style="text-align: center">备注</th>
                <th style="text-align: center">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($supplier_relationship_list)): ?>
                <tr>
                    <td colspan="17">无数据</td>
                </tr>
            <?php else: foreach ($supplier_relationship_list as $k => $v):
                $i = 0;?>
                <tr>
                    <td><?=$v['supplier_name']?></td>
                    <td>
                        <span>采购金额: <?=$v['purchase_amount']?></span>
                        <span style="float: right">起购量: <?=$v['purchase_count']?></span><br/>
                        交易次数:   <?=$v['transaction_num']?>
                    </td>
                    <td><?=$v['latest_transaction_date']?></td>
                    <td><?=$v['is_prior']?></td>
                    <td><?=$v['desc']?></td>
                    <td style="text-align: center" class="lay-lists">
                        <a class="layui-btn layui-btn-normal layui-btn-xs" data-type="open" data-width="900px" data-height="500px" data-url="<?=Url::to(['supplier-relationship/update?id='.$v['id']])?>">编辑</a>
                        <a class="layui-btn layui-btn-danger layui-btn-xs" data-type="operating" data-title="删除" data-url="<?=Url::to(['supplier-relationship/delete?id='.$v['id']])?>">删除</a>
                    </td>
                </tr>
            <?php endforeach;
            endif;?>
            </tbody>
        </table>
    </div>
    <table class="layui-table" style="margin-top: 25px">
        <tbody>
        <tr>
            <td class="layui-table-th">采购备注</td>
            <td colspan="5">
                <div class="layui-inline lay-lists" style="float: right">
                    <a class="layui-btn layui-btn-normal layui-btn-xs" data-type="open"  data-url="<?=Url::to(['goods/update-purchase-desc?goods_no='.$goods_no])?>" data-width="600px" data-height="400px" data-title="编辑采购备注" data-callback_title="商品编辑列表" style="float: right">编辑</a>
                </div>
                <?=empty($goods_extend['purchase_desc']) ? '' : $goods_extend['purchase_desc']?>
            </td>
        </tr>
        </tbody>
    </table>

</div>

<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.7")?>
