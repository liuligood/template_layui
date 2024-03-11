<?php
use common\models\Shop;
?>
<style>
    .layui-table-cell {
        height:auto;}
    .layui-card {
        padding: 10px 15px;
    }
    .layui-laypage li{
        float: left;
    }
    .layui-vertical-20{
        padding-top: 20px;
    }
    .layui-rate-up{
        color: green;
        padding-left: 10px;
    }
    .layui-rate-down{
        color: red;
        padding-left: 10px;
    }
    .card-report{
        width: 330px;
    }
    .card-report-list{
        height: 100px;
    }
</style>
<div class="layui-fluid">
    <div class="layui-card">
        <div class="lay-lists">
            <form class="layui-form">
                <div class="lay-search" style="padding-left: 10px">
                    <div class="layui-inline">
                        <label>店铺</label>
                        <?= \yii\helpers\Html::dropDownList('shop_id', $searchModel['shop_id'], \common\services\ShopService::getShopMap(),
                            ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']); ?>
                    </div>
                    <div class="layui-inline">
                        <label>时间</label>
                        <input id="month" class="layui-input search-con ys-date-month" name="start_month" value="<?=$searchModel['start_month']?>" autocomplete="off">
                    </div>
                    <div class="layui-inline layui-vertical-20">
                        <button class="layui-btn" data-type="search_lists">搜索</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div>
        <div class="layui-row layui-col-space15">
            <div class="layui-col-sm6 layui-col-md3 card-report">
                <div class="layui-card">
                    <div class="layui-card-header">
                        销售额
                    </div>
                    <div class="layui-card-body layuiadmin-card-list card-report-list">
                        <p class="layuiadmin-big-font" style="text-align: center"><?=$item['income_price']['current']?></p>
                        <p>
                            环比
                            <span class="layuiadmin-span-color"><?=$item['income_price']['last']?> <span class="<?= $item['income_price']['m_rate']>=0?'layui-rate-up':'layui-rate-down';?>"><?=$item['income_price']['m_rate']?>%</span></span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="layui-col-sm6 layui-col-md3 card-report">
                <div class="layui-card">
                    <div class="layui-card-header">
                        销售量
                    </div>
                    <div class="layui-card-body layuiadmin-card-list card-report-list">
                        <p class="layuiadmin-big-font" style="text-align: center"><?=$item['all_cut']['current']?></p>
                        <p>
                            环比
                            <span class="layuiadmin-span-color"><?=$item['all_cut']['last']?> <span class="<?= $item['all_cut']['m_rate']>=0?'layui-rate-up':'layui-rate-down';?>"><?=$item['all_cut']['m_rate']?>%</span></span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="layui-col-sm6 layui-col-md3 card-report">
                <div class="layui-card">
                    <div class="layui-card-header">
                        取消量
                    </div>
                    <div class="layui-card-body layuiadmin-card-list card-report-list">
                        <p class="layuiadmin-big-font" style="text-align: center"><?=$item['cancel_cut']['current']?></p>
                        <p>
                            环比
                            <span class="layuiadmin-span-color"><?=$item['cancel_cut']['last']?> <span class="<?= $item['cancel_cut']['m_rate']>=0?'layui-rate-down':'layui-rate-up';?>"><?=$item['cancel_cut']['m_rate']?>%</span></span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="layui-col-sm6 layui-col-md3 card-report">
                <div class="layui-card">
                    <div class="layui-card-header">
                        退款率
                    </div>
                    <div class="layui-card-body layuiadmin-card-list card-report-list">
                        <p class="layuiadmin-big-font" style="text-align: center"><?=$item['refund']['current']?>%</p>
                        <p>
                            退款量
                            <span class="layuiadmin-span-color"><?=$item['refund']['refund_cut']?></span>
                        </p>
                        <p>
                            环比
                            <span class="layuiadmin-span-color"><?=$item['refund']['last']?>% <span class="<?= $item['refund']['m_rate']>=0?'layui-rate-down':'layui-rate-up';?>"><?=$item['refund']['m_rate']?>%</span></span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="layui-col-sm6 layui-col-md3 card-report">
                <div class="layui-card">
                    <div class="layui-card-header">
                        广告投入产出比
                    </div>
                    <div class="layui-card-body layuiadmin-card-list card-report-list">
                        <p class="layuiadmin-big-font" style="text-align: center"><?=$item['promote']['current']?>%</p>
                        <p>
                            广告费用
                            <span class="layuiadmin-span-color"><?=$item['promote']['fee']?></span>
                        </p>
                        <p>
                            环比
                            <span class="layuiadmin-span-color"><?=$item['promote']['last']?>% <span class="<?= $item['promote']['m_rate']>=0?'layui-rate-up':'layui-rate-down';?>"><?=$item['promote']['m_rate']?>%</span></span>
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.5")?>
<?=$this->registerJsFile("@adminPageJs/report/index.js?v=".time())?>

