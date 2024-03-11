<?php

namespace common\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "{{%shop_statistics}}".
 *
 * @property int $id
 * @property int $platform_type 平台
 * @property int $shop_id 店铺id
 * @property int $online_products 在线商品数
 * @property int $add_time 添加时间
 * @property int $update_time 更新时间
 */
class ShopStatistics extends BaseAR
{

    public static function tableName()
    {
        return '{{%shop_statistics}}';
    }


    public function rules()
    {
        return [
            [['platform_type', 'shop_id', 'online_products', 'add_time', 'update_time'], 'integer'],
        ];
    }


    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'platform_type' => 'Platform Type',
            'shop_id' => 'Shop ID',
            'online_products' => 'Online Products',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }


    /**
     * 获取在线商品订单数
     * @param $platform_type
     * @return array
     */
    public static function getPlatformType($platform_type){
        $shop = Shop::find()->where(['platform_type'=>$platform_type])->asArray()->all();
        foreach ($shop as $v){
            //存在 》》 跳过,不存在 》》 新增
            $model = ShopStatistics::find()->where(['shop_id'=>$v['id']])->one();
            if (!empty($model)){
                continue;
            }
            $model = new ShopStatistics();
            $model['platform_type'] = $platform_type;
            $model['shop_id'] = $v['id'];
            $model['online_products'] = 0;
            $model->save();
        }
        $shop_statistics = ShopStatistics::find()->where(['platform_type'=>$platform_type])->select('shop_id')->asArray()->all();
        $goods_shop = GoodsShop::find()->select('count(shop_id) as online_products,shop_id')
            ->where(['platform_type'=>$platform_type,'status'=>GoodsShop::STATUS_SUCCESS])
            ->groupBy('shop_id')->asArray()->all();
        foreach ($goods_shop as $v){
            $model = ShopStatistics::find()->where(['shop_id'=>$v['shop_id']])->one();
            if (!empty($model)){
                $model['online_products'] = $v['online_products'];
                $model->save();
            }
        }
        $new_shop_id = ArrayHelper::getColumn($goods_shop,'shop_id');
        $old_shop_id = ArrayHelper::getColumn($shop_statistics,'shop_id');
        $now_shop_id = array_diff($old_shop_id,$new_shop_id);
        //当更新时店铺没有在线订单,删除之前的数据
        foreach ($now_shop_id as $v){
            $info = ShopStatistics::find()->where(['shop_id'=>$v])->one();
            $info['online_products'] = 0;
            $info->save();
        }
    }
}
