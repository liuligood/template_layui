<?php

namespace common\models;

use backend\models\AdminUser;
use Yii;

/**
 * This is the model class for table "{{%report_user_count}}".
 *
 * @property int $id
 * @property int $admin_id 负责人
 * @property int $date_time 日期
 * @property int $shop_id 店铺id
 * @property int $o_goods_success 成功数量
 * @property int $o_goods_fail 失败数量
 * @property int $o_goods_audit 审核中数量
 * @property int $o_goods_upload 上传中数量
 * @property int $order_count 订单量
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class ReportUserCount extends BaseAR
{

    public static function tableName()
    {
        return '{{%report_user_count}}';
    }


    public function rules()
    {
        return [
            [['admin_id', 'date_time', 'shop_id', 'o_goods_success', 'o_goods_fail', 'o_goods_audit', 'o_goods_upload', 'order_count', 'add_time', 'update_time'], 'integer'],
        ];
    }


    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'admin_id' => 'Admin ID',
            'date_time' => 'Date Time',
            'shop_id' => 'Shop ID',
            'o_goods_success' => 'O Goods Success',
            'o_goods_fail' => 'O Goods Fail',
            'o_goods_audit' => 'O Goods Audit',
            'o_goods_upload' => 'O Goods Upload',
            'order_count' => 'Order Count',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }

}
