<?php
/**
 * @var $this \yii\web\View;
 */
use yii\helpers\Url;
?>
    <style>
        html {
            background: #fff;
        }
        .layui-table-th{
            background: #FAFAFA;
            color: #666;
            width: 100px;
            text-align: right;
        }
    </style>
<form class="layui-form layui-row" id="add" action="<?=Url::to(['order-abnormal/follow'])?>">

    <div class="layui-col-md12 layui-col-xs12" style="padding: 0 20px">

        <table class="layui-table">
            <tbody>
                <tr>
                    <td class="layui-table-th">订单号</td>
                    <td><?=$model['order_id']?></td>
                    <td class="layui-table-th">提交时间</td>
                    <td><?= date('Y-m-d H:i:s',$model['add_time'])?></td>
                </tr>
                <tr>
                    <td class="layui-table-th">异常类型</td>
                    <td><?=\common\services\order\OrderAbnormalService::$abnormal_type_maps[$model['abnormal_type']]?></td>
                    <td class="layui-table-th">异常状态</td>
                    <td><?= \common\models\order\OrderAbnormal::$order_abnormal_status_map[$model['abnormal_status']]?></td>
                </tr>
                <tr>
                    <td class="layui-table-th">提交者</td>
                    <td><?=empty($model['admin_id'])?'':\common\models\User::getInfoNickname($model['admin_id'])?></td>
                    <td class="layui-table-th">跟进者</td>
                    <td><?=empty($model['follow_admin_id'])?'':\common\models\User::getInfoNickname($model['follow_admin_id'])?></td>
                </tr>
                <tr>
                    <td class="layui-table-th">异常内容</td>
                    <td colspan="3"><?=$model['abnormal_remarks']?></td>
                </tr>
            </tbody>
        </table>

        跟进记录
        <table class="layui-table">
            <thead>
            <tr>
                <th>跟进内容</th>
                <th>状态</th>
                <th>跟进者</th>
                <th>跟进时间</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($order_abnormal_follow as $v){ ?>
            <tr>
                <td><?=$v['follow_remarks']?></td>
                <td><?=\common\models\order\OrderAbnormal::$order_abnormal_status_map[$v['abnormal_status']]?></td>
                <td><?=\common\models\User::getInfoNickname($v['admin_id'])?></td>
                <td><?=date('Y-m-d H:i:s',$v['add_time'])?></td>
            </tr>
            <?php }?>
            </tbody>
        </table>

        <?php if($model['abnormal_status'] != \common\models\order\OrderAbnormal::ORDER_ABNORMAL_STATUS_CLOSE && !$is_view){ ?>
        <div class="layui-form-item" style="margin-top: 20px">
            <div class="layui-inline">
                <label class="layui-form-label">状态</label>
                <div class="layui-input-block">
                    <?php $status_map = \common\models\order\OrderAbnormal::$order_abnormal_status_map;
                    unset($status_map[\common\models\order\OrderAbnormal::ORDER_ABNORMAL_STATUS_UNFOLLOW]);
                    ?>
                    <?= \yii\helpers\Html::dropDownList('abnormal_status', null,$status_map,
                        ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
                </div>
            </div>
            <div class="layui-inline">
                <label class="layui-form-label">跟进人</label>
                <div class="layui-input-inline">
                    <?= \yii\helpers\Html::dropDownList('follow_admin_id',$model['follow_admin_id'], $admin_arr,
                        ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
                </div>
            </div>

            <div class="layui-inline">
                <label class="layui-form-label">同步备注</label>
                <input type="checkbox" lay-filter="type" lay-skin="primary" name="remarks_status" value="1" >
            </div>
        </div>

        <div class="layui-form-item" style="margin-top: 20px">
            <label class="layui-form-label">跟进内容</label>
            <div class="layui-input-block">
                <textarea placeholder="请输入备注" class="layui-textarea" style="height: 120px" name="follow_remarks"></textarea>
            </div>
        </div>

        <div class="layui-form-item" style="margin-top: 20px">
            <label class="layui-form-label">计划下次跟进时间</label>
            <div class="layui-input-block">
                <input type="radio" name="next_follow_time" value="1H" title="一小时内">
                <input type="radio" name="next_follow_time" value="3H" title="三小时内">
                <input type="radio" name="next_follow_time" value="1D" title="一天内" checked>
                <input type="radio" name="next_follow_time" value="3D" title="三天内">
                <input type="radio" name="next_follow_time" value="7D" title="七天内">
            </div>
        </div>

        <div class="layui-form-item" style="margin-top: 20px">
            <div class="layui-input-block">
                <input type="hidden" name="abnormal_id" value="<?=$model['id']?>">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="add">立即提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
        <?php }?>
    </div>
</form>
<?=$this->registerJsFile("@adminPageJs/base/form.js?v=0.0.1.4")?>