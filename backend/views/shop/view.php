<?php
use yii\helpers\Url;
use common\models\Shop;
use common\components\statics\Base;
use common\models\User;

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
    .layui-table-th{
        background: #FAFAFA;
        color: #666;
        width: 100px;
        text-align: right;
    }
</style>


<?php if (!$sales_id):?>
<div class="layui-col-md9 layui-col-xs12" style="margin:0 20px 5px 20px">
    <div class="lay-lists" style="padding:10px;">  
            <div class="layui-inline">
                <a class="layui-btn layui-btn-normal layui-btn-sm" data-ignore="ignore" href="<?=Url::to(['shop/update?shop_id='.$info['id']])?>">编辑</a>
            </div>

            <div class="layui-inline">
                <a class="layui-btn layui-btn-danger layui-btn-sm" data-type="operating" data-title="删除" data-url="<?=Url::to(['shop/delete?shop_id='.$info['id']])?>">删除</a>
            </div>

            <?php if(!empty($info['auth_url'])){ ?>
            <div class="layui-inline">
                <a class="layui-btn layui-btn-sm" target="_blank" data-ignore="ignore" href="<?=$info['auth_url']?>">授权</a>
            </div>
            <?php }?>
    </div>
<table class="layui-table">
    <tbody>
    <tr>
        <td class="layui-table-th">平台类型</td>
        <td><?=Base::$platform_maps[$info['platform_type']]?></td>
        <td class="layui-table-th">店铺负责人</td>
        <td><?= User::getInfoNickname($info['admin_id'])?></td>
        <td class="layui-table-th">店铺名称</td>
        <td colspan="5"><?=$info['name']?></td>
    </tr>
    <tr>
        <td class="layui-table-th">收款账号</td>
        <td><?=$info['collection_account']?></td>
        <td class="layui-table-th">收款平台</td>
        <td><?=$info['collection_platform']?></td>
        <td class="layui-table-th">收款归属者</td>
        <td colspan="5"><?=$info['collection_owner']?></td>
    </tr>
    <tr>
        <td class="layui-table-th">接口权限</td>
        <td><?=$assignment?></td>
        <td class="layui-table-th">收款账号币种</td>
        <td colspan="5"><?=$info['collection_currency']?></td>
    </tr>
    <tr>
        <td class="layui-table-th">出单量</td>
        <td><?=$info['order_num']?></td>
        <td class="layui-table-th">销售状态</td>
        <td><?=$info['sale_status'] == 0 ? '' : Shop::$sale_status_maps[$info['sale_status']]?></td>
        <td class="layui-table-th">最后出单时间</td>
        <td colspan="5"><?=$info['last_order_time'] == 0 ? '' : date('Y-m-d H:i:s',$info['last_order_time'])?></td>
    </tr>
    <tr>
        <td class="layui-table-th">站点</td>
        <td colspan="5"><?=$info['country_site']?></td>
    </tr>
    <tr>
        <td class="layui-table-th">币种</td>
        <td colspan="5"><?=$info['currency']?></td>
    </tr>
    <tr>
        <td class="layui-table-th">ioss</td>
        <td colspan="5"><?=$info['ioss']?></td>
    </tr>
    <tr>
        <td class="layui-table-th">client_key</td>
        <td colspan="5"><?=$info['client_key']?></td>
    </tr>
    <tr>
        <td class="layui-table-th">secret_key</td>
        <td colspan="5"><?=$info['secret_key']?></td>
    </tr>
    <tr>
        <td class="layui-table-th">状态</td>
        <td colspan="5"><?=Shop::$status_maps[$info['status']]?></td>
    </tr>
    <tr>
        <td class="layui-table-th">额外参数</td>
        <td colspan="5"><?=$info['param']?></td>
    </tr>
    <?php if(!empty($info['auth_url'])){ ?>
    <tr>
        <td class="layui-table-th">授权链接</td>
        <td colspan="5"><?=$info['auth_url']?></td>
    </tr>
    <?php }?>
    </tbody>
</table>
</div>
<?php else:?>
<div class="layui-col-md9 layui-col-xs12" style="margin:10px 20px 5px 30px">
<table class="layui-table">
    <tbody>
    <tr>
        <td class="layui-table-th">平台类型</td>
        <td colspan="5"><?=Base::$platform_maps[$info['platform_type']]?></td>
    </tr>
    <tr>
        <td class="layui-table-th">店铺负责人</td>
        <td><?= User::getInfoNickname($info['admin_id'])?></td>
        <td class="layui-table-th">店铺名称</td>
        <td colspan="5"><?=$info['name']?></td>
    </tr>
    <tr>
        <td class="layui-table-th">收款平台</td>
        <td colspan="5"><?=$info['collection_platform']?></td>
    </tr>
    <tr>
        <td class="layui-table-th">收款账号</td>
        <td><?=$info['collection_account']?></td>
        <td class="layui-table-th">收款归属者</td>
        <td colspan="5"><?=$info['collection_owner']?></td>
    </tr>
    <tr>
        <td class="layui-table-th">站点</td>
        <td colspan="5"><?=$info['country_site']?></td>
    </tr>
    <tr>
        <td class="layui-table-th">币种</td>
        <td colspan="5"><?=$info['currency']?></td>
    </tr>
    </tbody>
</table>
</div>
<?php endif;?>
<div class="layui-col-md9 layui-col-xs12" style="margin-left: 20px">
<table class="layui-table">
    <tbody>
    <tr style="background-color: #eee">
        <td>配对类型</td>
        <td>平台id</td>
        <td>名称</td>
        <td>最后更新时间</td>
    </tr>
    <?php foreach ($ware as $item){ ?>
    <tr>
        <td><?=\common\models\platform\PlatformShopConfig::$warehousemap[$item['type']]?></td>
        <td><?=$item['type_id']?></td>
        <td><?=$item['type_val']?></td>
        <td><?=date ("Y-m-d",$item['update_time'])?></td>
    </tr>
    <?php }?>
    </tbody>
</table>
</div>
 <?=$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.7")?>

