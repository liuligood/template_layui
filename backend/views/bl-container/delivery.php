<?php
/**
 * @var $this \yii\web\View;
 */

use common\models\warehousing\BlContainerTransportation;
use yii\helpers\Html;
use yii\helpers\Url;
?>
    <style>
        html {
            background: #fff;
        }
    </style>
    <form class="layui-form layui-row" id="add" action="<?=Url::to(['bl-container/delivery'])?>">

        <div class="layui-col-md12 layui-col-xs12" style="padding-top: 15px">

            <div class="layui-form-item">
                <label class="layui-form-label">物流编号</label>
                <div class="layui-input-block">
                    <?= Html::dropDownList('track_no', null,BlContainerTransportation::getAllTrackNo($warehouse_id),
                        ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style' => 'width:350px' ]) ?>
                </div>
            </div>

            <?php if ($is_batch == 2) {?>
                <div class="layui-form-item">
                    <label class="layui-form-label">序号</label>
                    <label class="layui-form-label" style="width: 200px;text-align: left"><?=$bl_container['initial_number']?></label>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">提单箱编号</label>
                    <div class="layui-input-block">
                        <input type="text" name="bl_nos[<?=$bl_container['id']?>]" lay-verify="required" placeholder="请输入提单箱编号" class="layui-input " style="width: 350px">
                    </div>
                </div>
            <?php }?>

            <?php if ($is_batch == 1) {?>
            <div class="layui-fluid">
                <div class="layui-card">
                    <div class="layui-form">
                        <table class="layui-table" style="text-align: center">
                            <thead>
                            <tr>
                                <th style="text-align: center">序号</th>
                                <th style="text-align: center">仓库</th>
                                <th style="text-align: center;width: 220px">提单箱编号</th>
                                <th style="text-align: center">重量</th>
                                <th style="text-align: center">尺寸</th>
                                <th style="text-align: center">商品数量</th>
                            </tr>
                            </thead>
                            <?php if (empty($bl_container)): ?>
                                <tr>
                                    <td colspan="17">无数据</td>
                                </tr>
                            <?php else: foreach ($bl_container as $k => $v):?>
                                <tr>
                                    <td><?=$v['initial_number']?></td>
                                    <td><?=empty($warehouse[$v['warehouse_id']]) ? '' : $warehouse[$v['warehouse_id']]?></td>
                                    <td>
                                        <input type="text" name="bl_nos[<?=$v['id']?>]" lay-verify="required" placeholder="请输入提单箱编号" class="layui-input" style="width: 220px">
                                    </td>
                                    <td><?=$v['weight']?></td>
                                    <td><?=$v['size']?></td>
                                    <td><?=$v['goods_count']?></td>
                                </tr>
                            <?php
                            endforeach;
                            endif;
                            ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php }?>

            <div class="layui-form-item layui-layout-admin">
                <div class="layui-input-block">
                    <div class="layui-footer" style="left: 0;">
                    <button class="layui-btn" lay-submit="" lay-filter="form" data-form="add">立即提交</button>
                    <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>