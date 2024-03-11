<?php
use common\models\Shop;
use common\models\ReportUserCount;
use common\services\sys\AccessService;
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

</style>
<div class="layui-fluid">
    <div class="layui-card">
        <div class="lay-lists">
            <form class="layui-form">
                <div class="lay-search" style="padding-left: 10px">
                    <?php if(AccessService::hasAllUser()) {?>
                    负责人:
                    <div class="layui-inline" style="width: 155px">
                        <?= \yii\helpers\Html::dropDownList('ReportUserCountSearch[admin_id]', $searchModel['admin_id'], Shop::adminArr(),['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']); ?>
                    </div>
                    <?php }?>
                    店铺名称:
                    <div class="layui-inline">
                        <?= \yii\helpers\Html::dropDownList('ReportUserCountSearch[shop_id]', $searchModel['shop_id'], \common\services\ShopService::getShopMap(),
                            ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']); ?>
                    </div>

                    时间:
                    <div class="layui-inline">
                        <input  class="layui-input search-con ys-date" name="ReportUserCountSearch[start_date]" value="<?=$searchModel['start_date']?>" id="start_date" autocomplete="off">
                    </div>
                        -
                    <div class="layui-inline">
                        <input  class="layui-input search-con ys-date" name="ReportUserCountSearch[end_date]"  value="<?=$searchModel['end_date']?>"  id="end_date" autocomplete="off">
                    </div>
                    <div class="layui-inline layui-vertical-20">
                        <button class="layui-btn" data-type="search_lists">搜索</button>
                    </div>
                </div>
            </form>

            <div>
                <?php
                $startCount = (0 == $pages->totalCount) ? 0 : $pages->offset + 1;
                $endCount = ($pages->page + 1) * $pages->pageSize;
                $endCount = ($endCount > $pages->totalCount) ? $pages->totalCount : $endCount;
                ?>
                <div class="layui-row layui-col-space15">
                    <div class="layui-col-sm6 layui-col-md3" style="width: 330px">
                        <div class="layui-card">
                            <div class="layui-card-header" style="text-align: center">
                                总成功数
                            </div>
                            <div class="layui-card-body layuiadmin-card-list">
                                <p class="layuiadmin-big-font" style="text-align: center"><?=empty($item[0]['o_goods_success']) ? 0 : $item[0]['o_goods_success']?></p >
                            </div>
                        </div>
                    </div>
                    <div class="layui-col-sm6 layui-col-md3" style="width: 330px">
                        <div class="layui-card">
                            <div class="layui-card-header" style="text-align: center">
                                总失败数
                            </div>
                            <div class="layui-card-body layuiadmin-card-list">
                                <p class="layuiadmin-big-font" style="text-align: center"><?=empty($item[0]['o_goods_fail']) ? 0 : $item[0]['o_goods_fail']?></p >
                            </div>
                        </div>
                    </div>
                    <div class="layui-col-sm6 layui-col-md3" style="width: 330px">
                        <div class="layui-card">
                            <div class="layui-card-header" style="text-align: center">
                                总审核中数
                            </div>
                            <div class="layui-card-body layuiadmin-card-list">
                                <p class="layuiadmin-big-font" style="text-align: center"><?=empty($item[0]['o_goods_audit']) ? 0 : $item[0]['o_goods_audit']?></p >
                            </div>
                        </div>
                    </div>
                    <div class="layui-col-sm6 layui-col-md3" style="width: 320px">
                        <div class="layui-card">
                            <div class="layui-card-header" style="text-align: center">
                                总上传中数
                            </div>
                            <div class="layui-card-body layuiadmin-card-list">
                                <p class="layuiadmin-big-font" style="text-align: center"><?=empty($item[0]['o_goods_upload']) ? 0 : $item[0]['o_goods_upload']?></p >
                            </div>
                        </div>
                    </div>
                    <div class="layui-col-sm6 layui-col-md3" style="width: 320px">
                        <div class="layui-card">
                            <div class="layui-card-header" style="text-align: center">
                                总订单数
                            </div>
                            <div class="layui-card-body layuiadmin-card-list">
                                <p class="layuiadmin-big-font" style="text-align: center"><?=empty($item[0]['order_count']) ? 0 : $item[0]['order_count']?></p >
                            </div>
                        </div>
                    </div>
                </div>
                <div class="layui-form">
                    <table class="layui-table" style="text-align: center">
                        <thead>
                        <tr>
                            <th style="width: 60px">负责人</th>
                            <th style="text-align: center">店铺名称</th>
                            <th style="text-align: center">日期</th>
                            <th style="text-align: center">成功数量</th>
                            <th style="text-align: center">失败数量</th>
                            <th style="text-align: center">审核中数量</th>
                            <th style="text-align: center">上传中数量</th>
                            <th style="text-align: center">订单量</th>
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
                                <td><?= $v['admin_id'] ?></td>
                                <td><?= $v['shop_id'] ?></td>
                                <td><?= $v['date_time']?></td>
                                <td><?= $v['o_goods_success'] ?></td>
                                <td><?= $v['o_goods_fail'] ?></td>
                                <td><?=$v['o_goods_audit']?></td>
                                <td><?=$v['o_goods_upload']?></td>
                                <td><?=$v['order_count']?></td>
                            </tr>
                        <?php endforeach;?>
                        <?php
                        endif;
                        ?>

                        </tbody>
                    </table>
                </div>
            </div>
            <?= \yii\widgets\LinkPager::widget(['pagination' => $pages,'options' => ['class' => 'layui-box layui-laypage layui-laypage-default'],]) ?>
        </div>
    </div>
</div>

<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.5")?>
<?=$this->registerJsFile("@adminPageJs/order/lists.js?v=".time())?>