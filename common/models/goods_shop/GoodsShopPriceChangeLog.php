<?php

namespace common\models\goods_shop;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%goods_shop_price_change_log}}".
 *
 * @property int $id
 * @property int $goods_shop_id 店铺商品id
 * @property string $old_price 旧价格
 * @property string $new_price 新价格
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 *  @property int $type 变动来源
 * @property int $user_id 操作者
 */
class GoodsShopPriceChangeLog extends BaseAR
{
    const PRICE_CHANGE_FIXED = 1;
    const PRICE_CHANGE_DISCOUNT = 2;
    const PRICE_CHANGE_GOODS = 3;
    const PRICE_CHANGE_FOLLOW = 4;

    public static $price_map = [
        self::PRICE_CHANGE_FIXED => '锁定价格修改',
        self::PRICE_CHANGE_DISCOUNT => '折扣修改',
        self::PRICE_CHANGE_GOODS => '平台商品库价格修改',
        self::PRICE_CHANGE_FOLLOW => '跟卖价格修改'
    ];
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%goods_shop_price_change_log}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['goods_shop_id', 'add_time', 'update_time','type','user_id'], 'integer'],
            [['old_price', 'new_price'], 'number'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'goods_shop_id' => 'Goods Shop ID',
            'price' => 'Price',
            'new_price' => 'New Price',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }

    /**
     * 添加日志
     * @param $goods_shop_id
     * @param $old_price
     * @param $new_price
     * @param $type
     * @return void
     */
    public static function addLog($goods_shop_id,$old_price,$new_price,$type){
        $item = new GoodsShopPriceChangeLog();
        $item->goods_shop_id = $goods_shop_id;
        $item->old_price = $old_price;
        $item->user_id = empty(Yii::$app->user->identity->id)?0:(int)Yii::$app->user->identity->id;
        $item->new_price = $new_price;
        $item->type = $type;
        $item->save();
    }
}