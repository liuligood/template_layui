<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%goods_event}}".
 *
 * @property int $id
 * @property int $goods_shop_id 店铺商品id
 * @property int $platform 平台
 * @property int $shop_id 店铺id
 * @property string $goods_no 商品编号
 * @property string $cgoods_no 子商品编号
 * @property string $sku_no sku
 * @property string $event_type 事件类型
 * @property string $queue_id 队列id
 * @property int $plan_time 计划执行时间
 * @property int $status 状态
 * @property string $error_msg 错误信息
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class GoodsEvent extends BaseAR
{

    const STATUS_WAIT_RUN = 0;//待执行
    const STATUS_RUNNING = 10;//执行中
    const STATUS_RUNNING_RESULT = 15;//执行中等待返回
    const STATUS_SUCCESS = 20;//执行成功
    const STATUS_FAILURE = 30;//执行失败

    const EVENT_TYPE_UPDATE_STOCK = 'update_stock';//更新库存
    const EVENT_TYPE_UPDATE_PRICE = 'update_price';//更新价格
    const EVENT_TYPE_ADD_GOODS = 'add_goods';//添加商品
    const EVENT_TYPE_UPLOAD_IMAGE = 'upload_image';//上传图片
    const EVENT_TYPE_DEL_GOODS = 'del_goods';//删除商品
    const EVENT_TYPE_GET_GOODS_ID = 'get_goods_id';//获取商品id
    const EVENT_TYPE_UPDATE_GOODS_CONTENT = 'update_goods_content';//更新商品内容
    const EVENT_TYPE_ADD_GOODS_CONTENT = 'add_goods_content';//添加商品详情
    const EVENT_TYPE_UPDATE_GOODS = 'update_goods';//更新商品标题
    const EVENT_TYPE_ADD_LISTINGS = 'add_listings';//添加报价
    const EVENT_TYPE_ADD_VARIANT = 'add_variant';//添加变体
    const EVENT_TYPE_RESUME_GOODS = 'resume_goods';//恢复商品



    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%goods_event}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['goods_shop_id', 'platform', 'shop_id',  'status', 'add_time', 'update_time','plan_time'], 'integer'],
            [['goods_no','sku_no','event_type','cgoods_no'], 'string', 'max' => 32],
            [['queue_id'], 'string', 'max' => 100],
            [['error_msg'], 'string', 'max' => 1000],

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'platform' => 'Platform',
            'shop_id' => 'Shop ID',
            'goods_no' => 'Goods No',
            'sku_no' => 'Sku No',
            'event_type' => 'Event Type',
            'status' => 'Status',
            'error_msg' => 'Error Msg',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}
