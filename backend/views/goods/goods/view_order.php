<?php
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
        <li><a href="<?=Url::to(['goods/view?goods_no='.$goods['goods_no']])?>">商品信息</a></li>
        <li><a href="<?=Url::to(['goods/view-multilingual?goods_no='.$goods['goods_no']])?>">多语言</a></li>
        <li><a href="<?=Url::to(['goods/view-outside-package?goods_no='.$goods['goods_no']])?>">采购信息</a></li>
        <li class="layui-this"><a href="<?=Url::to(['goods/view-order?goods_no='.$goods['goods_no']])?>">订单</a></li>
        <li><a href="<?=Url::to(['goods/view-purchase?goods_no='.$goods['goods_no']])?>">采购</a></li>
    </ul>
</div>

<div class="layui-col-md9 layui-col-xs12" style="margin:0 20px 30px 20px">

    <div class="lay-lists">
        <?php
        $startCount = (0 == $pages->totalCount) ? 0 : $pages->offset + 1;
        $endCount = ($pages->page + 1) * $pages->pageSize;
        $endCount = ($endCount > $pages->totalCount) ? $pages->totalCount : $endCount;
        ?>
        <div class="summary" style="margin-top: 10px;">
            第<b><?= $startCount ?>-<?= $endCount ?></b>条，共<b><?= $pages->totalCount ?></b>条数据
        </div>
        <div class="layui-form">
            <table class="layui-table" style="text-align: center">
                <thead>
                <tr>
                    <th style="width: 60px">商品图片</th>
                    <th>商品信息</th>
                    <th width="100">金额</th>
                    <th>订单号</th>
                    <th>来源</th>
                    <th>收件人信息</th>
                    <th>时间</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($list)): ?>
                    <tr>
                        <td colspan="17">无数据</td>
                    </tr>
                <?php else: foreach ($list as $k => $v):
                    $i = 0;?>
                    <?php foreach ($v['goods'] as $goods_k => $goods_v):
                    $sku_no = empty($goods_v['platform_asin'])?'':$goods_v['platform_asin'];
                    $i ++;
                    ?>
                    <tr>
                        <td>
                            <?php if(!empty($goods_v['goods_pic'])):?>
                                <div class="goods_img" style="position:relative;cursor: pointer;">
                                    <img class="layui-circle pic" src="<?=empty($goods_v['goods_pic'])?'':$goods_v['goods_pic']?>"/>
                                    <div class="big_img" style="top: auto;bottom: 0px;position:absolute; z-index: 100;left: 120px; display: none ;">
                                        <div>
                                            <img src="<?=empty($goods_v['goods_pic'])?'':$goods_v['goods_pic']?>" width="100" style="max-width:150px;border:2px solid #666;">
                                        </div>
                                    </div>
                                </div>
                            <?php endif;?>
                        </td>
                        <td align="left" style="width: 250px">
                            <b>
                                <?=$sku_no?>
                            </b>  x️ <span class="<?= $goods_v['goods_num'] != 1?'span-circular-red':'span-circular-grey'; ?>"><?=empty($goods_v['goods_num'])?'':$goods_v['goods_num']?></span><br/>
                            <span class="span-goode-name"><?=empty($goods_v['goods_name'])?'':$goods_v['goods_name']?></span>

                        </td>
                        <?php if($i == 1):?>
                            <td align="left" rowspan="<?=$v['goods_count']?>">
                                金额：<?= $v['order_income_price'] ?> <?=$v['currency']?><br/>
                                <?php if(\common\services\sys\AccessService::hasAmount()) { ?>
                                    成本：<?= $v['order_cost_price'] ?><br/>
                                    运费：<?= $v['freight_price'] ?><br/>
                                    收入：<?= $v['order_profit'] ?> <?=$v['currency']?>(<span style="color: red">￥<?=\common\services\sys\CountryService::getConvertRMB($v['order_profit'],$v['currency']);?></span>)
                                <?php }?>
                            </td>
                            <td align="left" rowspan="<?=$v['goods_count']?>">
                                订单号：
                                <?php if($v['order_status'] != \common\models\Order::ORDER_STATUS_CANCELLED){ ?>
                                    <a class="layui-btn layui-btn-primary layui-btn-xs layui-btn-a" data-type="url"  data-url="<?=Url::to(['order/view?order_id='.$v['order_id']])?>" data-title="订单详情"><?= $v['order_id'] ?></a>
                                <?php }else{?>
                                    <?= $v['order_id'] ?>
                                <?php }?>
                                <br/>
                                销售单号：
                                <?= $v['relation_no'] ?>
                            </td>
                            <td align="left" rowspan="<?=$v['goods_count']?>">
                                <?= Base::$order_source_maps[$v['source']] ?><br/>
                                <?= empty($v['shop_name'])?'':$v['shop_name'] ?>
                                <span style="padding: 1px 5px;float: right" class="layui-font-12 <?=!empty($v['abnormal_time'])?'layui-bg-red':'layui-bg-orange'?>"><?= \common\models\Order::$order_status_map[$v['order_status']] ?></span>
                            </td>
                            <td align="left" rowspan="<?=$v['goods_count']?>">
                                收件人：<?= $v['buyer_name'] ?><br/>
                                国家：<?= $v['country'] ?>
                            </td>

                            <td rowspan="<?=$v['goods_count']?>">
                                下单时间：<?= date('Y-m-d H:i:s',$v['date'])?><br/>
                                <?php if(in_array($v['order_status'],[\common\models\Order::ORDER_STATUS_SHIPPED,\common\models\Order::ORDER_STATUS_FINISH])){ ?>
                                    发货时间：<?= empty($v['delivery_time'])?'':date('Y-m-d H:i:s',$v['delivery_time']) ?><br/>
                                <?php }?>
                                <?php if(!empty($v['abnormal_time'])){?>
                                    异常时间：<?= date('Y-m-d H:i:s',$v['abnormal_time'])?><br/>
                                <?php } ?>
                                <?php if(!empty($v['cancel_time']) && in_array($v['order_status'],[\common\models\Order::ORDER_STATUS_CANCELLED,\common\models\Order::ORDER_STATUS_REFUND])){
                                    echo $v['order_status'] == \common\models\Order::ORDER_STATUS_CANCELLED?'取消':'退款';
                                    ?>时间：<?= date('Y-m-d H:i:s',$v['cancel_time'])?><br/>
                                <?php } ?>
                            </td>
                        <?php endif;?>
                    </tr>
                <?php endforeach;?>
                <?php
                endforeach;
                endif;
                ?>
                </tbody>
            </table>
        </div>
    </div>
    <?= \yii\widgets\LinkPager::widget(['pagination' => $pages,'options' => ['class' => 'layui-box layui-laypage layui-laypage-default'],]) ?>
</div>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.7")?>
<?=$this->registerJsFile("@adminPageJs/order/lists.js?v=".time())?>