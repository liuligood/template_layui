<?php

namespace common\models\sys;

use common\components\statics\Base;
use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%sys_exectime}}".
 *
 * @property int $id
 * @property string $object_type 类型
 * @property string $object_no 对象编号
 * @property int $exec_time 执行时间
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class Exectime extends BaseAR
{

    const TYPE_SHOP_BILLING = 'shop_billing';//allegro店铺账单
    const TYPE_SHOP_PAYMENT = 'shop_payment';//allegro店铺付款
    const TYPE_SHOP_LOGISTICS_PRICE = 'shop_logistics_price';//店铺物流价格
    const TYPE_ORDER_FIRST_TRACK = 'order_first_track';//订单国内物流
    const TYPE_CAMPAIGN_STATISTICS = 'campaign_statistics';//广告统计
    const TYPE_SHOP_GOODS_SALES = 'shop_goods_sales';//店铺商品销售
    const TYPE_GOODS_DISTRIBUTION_ADD = 'goods_distribution_add';//分销商品添加
    const TYPE_SHOP_GOODS_STOCK = 'shop_goods_stock';//店铺商品库存


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%sys_exectime}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['exec_time', 'add_time', 'update_time'], 'integer'],
            [['object_type', 'object_no'], 'string', 'max' => 24],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'object_type' => 'Object Type',
            'object_no' => 'Object No',
            'exec_time' => 'Exec Time',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }

    /**
     * 获取执行时间
     * @param $object_type
     * @param $object_no
     * @return mixed
     */
    public static function getTime($object_type,$object_no = '')
    {
        $where = ['object_type' => $object_type];
        if (!empty($object_no)) {
            $where['object_no'] = (string)$object_no;
        }
        $exec_model = Exectime::find()->where($where)->select('exec_time')->scalar();
        return empty($exec_model) ? 0 : $exec_model;
    }

    /**
     * 设置执行时间
     * @param $exec_time
     * @param $object_type
     * @param string $object_no
     * @return mixed
     */
    public static function setTime($exec_time,$object_type,$object_no = '')
    {
        $where = ['object_type' => $object_type];
        if (!empty($object_no)) {
            $where['object_no'] = (string)$object_no;
        }
        $exec_model = Exectime::find()->where($where)->one();
        if (empty($exec_model)) {
            $exec_model = new Exectime();
            $exec_model->object_type = $object_type;
            $exec_model->object_no = (string)$object_no;
        }
        $exec_model->exec_time = $exec_time;
        return $exec_model->save();
    }

}