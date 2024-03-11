<?php
/**
 * @var $this \yii\web\View;
 */
use yii\helpers\Url;
?>
<div class="layui-fluid">
    <div class="layui-card">
        <div class="layui-tab layui-tab-brief">
            <ul class="layui-tab-title">
                <li <?php if($tag == 1){?>class="layui-this" <?php }?>><a href="<?=Url::to(['shop-statistics/index?tag=1'])?>">Ozon</a></li>
            </ul>
        </div>
        <div class="layui-card-body">
            <table class="layui-table" style="text-align: center">
                <div class="layui-row layui-col-space15">
            <?php if (empty($list)): ?>
                <tr>
                    <td colspan="17">无数据</td>
                </tr>
            <?php else: foreach ($list as $v):?>
                    <div class="layui-col-sm6 layui-col-md3" style="width: 330px">
                        <div class="layui-card" style="border: 1px solid #ccc">
                            <div class="layui-card-header" style="text-align: center">
                                <?=$v['shop_name']?>
                            </div>
                            <div class="layui-card-body layuiadmin-card-list">
                                在线商品数：
                                <p class="layuiadmin-big-font" style="text-align: center;color: #3b97d7"><?=$v['online_products']?></p>
                            </div>
                        </div>
                    </div>
            <?php endforeach;?>
            <?php endif;?>
                </div>
            </table>
        </div>
    </div>
</div>


