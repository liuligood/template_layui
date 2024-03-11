
<?php
use yii\helpers\Url;
use common\models\RealOrder;
use yii\helpers\Html;
use yii\widgets\LinkPager;
use common\components\statics\Base;
?>

<style>
    .layui-laypage li{
        float: left;
    }
    .layui-laypage .active a{
        background-color: #009688;
        color: #fff;
    }
    .layui-vertical-20{
        padding-top: 20px;
    }
    .layui-tab-title .layui-this {
        background-color: #fff;
        color: #000;
    }
    .layui-tab-brief > .layui-tab-title .layui-this a{
        color: rgb(0, 150, 136);
    }
</style>
<div class="layui-fluid">
    <div class="layui-card">
<div class="lay-lists">
<form class="layui-form" style="padding:15px">
<div class="lay-search" style="padding-left: 10px">
    <div class="layui-inline">
        <label>日期</label>
        <input  class="layui-input search-con ys-date" name="start_date" value="<?=$searchModel['start_date'];?>"  id="start_date" autocomplete="off">
    </div>
    <span class="layui-inline layui-vertical-20">
        -
    </span>
    <div class="layui-inline layui-vertical-20">
        <input  class="layui-input search-con ys-date" name="end_date" value="<?=$searchModel['end_date'];?>" id="end_date" autocomplete="off">
    </div>
    <div class="layui-inline layui-vertical-20">
        <button class="layui-btn" data-type="search_lists">搜索</button>
    </div>
    <?php
$b = date("Y-m-d");     
$a = date("Y-m-d",strtotime("-1 day"));   
?>

            <div class="layui-inline layui-vertical-20" style="margin-left: 10px">
                <a  href="/report/order-count?start_date=<?php echo $a;?>&end_date=<?php echo $a;?>" data-title="昨日"  style="text-decoration:none;padding:11px 12px;color:#00a0e9;" >昨日</a>
            </div>
                        <div class="layui-inline layui-vertical-20">
                <a  href="/report/order-count?start_date=<?php echo $b;?>&end_date=<?php echo $b;?>" data-title="今日" style="text-decoration:none;padding:11px 12px;color:#00a0e9;">今日</a>
            </div>
</div>
</form>
</div>
<div style="padding-bottom: 15px">
    <div class="layui-row">
        <div style="background:#FAFAFA;margin: 5px;padding: 10px;border:1px solid #eee">
            <h3>总数</h3>
            <div style="font-size: 26px;font-weight: bold;text-align: center;height:110px;margin: 0 auto;color: #3b97d7">
                <div style="font-size: 26px;font-weight: bold;text-align: center;height: 50px;margin: 0 auto;line-height: 50px;color: #3b97d7">
                    <?=$all_count?>
                </div>
                <div style="font-size: 22px;font-weight: bold;text-align: center;height: 35px;margin: 0 auto;line-height: 35px;color:#c76b29">
                    ￥<?=$all_income_price?> <span style="font-size:18px;color: darkgrey">(￥<?=$all_order_profit?>)</span><span style="font-size:18px;color:lightsalmon">[￥<?=$all_cost?>]</span>
                </div>
            </div>
        </div>
    </div>
    <div class="layui-row">
    <?php foreach ($order_count as $v){?>
        <div class="layui-col-md3">
            <div style="background:#FAFAFA;margin: 5px;padding: 10px;border:1px solid #eee">
                <h3><?= Base::$platform_maps[$v['source']]?></h3>
                <div style="height: 230px">
                    <div style="font-size: 26px;font-weight: bold;text-align: center;height: 50px;margin: 0 auto;line-height: 50px;color: #3b97d7">
                        <?= empty($v['cut'])?0:$v['cut']; ?>
                    </div>

                    <div style="font-size: 22px;font-weight: bold;text-align: center;height:35px;margin: 0 auto;line-height: 35px;color: #c76b29">
                        ￥<?= empty($v['income_price'])?0:$v['income_price']; ?> <span style="font-size:18px;color: darkgrey">(￥<?= empty($v['order_profit'])?0:$v['order_profit']; ?>)</span>
                    </div>

                    <?php foreach ($v['shop'] as $shop){?>
                        <div style="font-size: 16px;font-weight: bold;float: left;margin: 5px;color: #3b97d7"><span style="color:#737373"><?=$shop_map[$shop['shop_id']]?></span> <?=$shop['cut']?></div>
                    <?php }?>
                    <div style="clear: both"></div>
                </div>
            </div>
        </div>
    <?php }?>
    </div>
</div>
    </div>
</div>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.7")?>