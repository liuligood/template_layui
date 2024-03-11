<?php
namespace common\services;

use common\components\statics\Base;
use common\models\platform\PlatformShopConfig;
use common\models\Shop;
use common\services\cache\FunCacheService;
use common\services\sys\AccessService;
use yii\helpers\ArrayHelper;
use yii;

class ShopService
{

    /**
     * 获取店铺列表
     * @param null $platform_type
     * @param int $status
     * @param null $shop_ids
     * @return array|yii\db\ActiveRecord[]
     */
    public static function getShopList($platform_type = null,$status = Shop::STATUS_VALID,$shop_ids = null)
    {
        $where =[];
        if(!is_null($status)) {
            $where['status'] = [Shop::STATUS_VALID,Shop::STATUS_PAUSE];
        }
        if(!is_null($platform_type)){
            $where['platform_type'] = $platform_type;
        }
        if(!is_null($shop_ids)){
            $where['id'] = $shop_ids;
        }else{
            if(!empty(Yii::$app->user) && !AccessService::hasAllShop()) {
                $shop_ids = Yii::$app->user->identity->shop_id;
                $shop_ids = explode(',', $shop_ids);
                $where['id'] = $shop_ids;
            }
        }
        return Shop::find()->select(['id', 'platform_type', 'name'])->where($where)->asArray()->all();
    }

    public static function getShopMap($platform_type = null,$status = null,$shop_ids = null)
    {
        $shop = self::getShopList($platform_type,$status,$shop_ids);
        return ArrayHelper::map($shop,'id','name');
    }

    public static function getShopMapIndexPlatform($platform_type = null,$status = null,$shop_ids = null)
    {
        $shop = self::getShopList($platform_type,$status,$shop_ids);
        return ArrayHelper::map($shop,'id','name','platform_type');
    }

    public static function getShopMapId($platform_type = null,$status = null,$shop_ids = null)
    {
        $shop = self::getShopList($platform_type,$status,$shop_ids);
        return ArrayHelper::map($shop,'name','id');
    }

    /**
     * 获取订单店铺
     */
    public static function getOrderShop()
    {
        $shop_ids = null;
        if(!empty(Yii::$app->user) && !AccessService::hasAllShop()) {
            $shop_ids = Yii::$app->user->identity->shop_id;
            $shop_ids = explode(',', $shop_ids);
        }
        return self::getShopMap(array_keys(Base::$order_source_maps),Shop::STATUS_VALID,$shop_ids);
    }

    /**
     * 获取平台订单店铺关联
     */
    public static function getOrderShopMap()
    {
        $shop_ids = null;
        if(!empty(Yii::$app->user) && !AccessService::hasAllShop()) {
            $shop_ids = Yii::$app->user->identity->shop_id;
            $shop_ids = explode(',', $shop_ids);
        }
        return self::getShopMapIndexPlatform(array_keys(Base::$order_source_maps),Shop::STATUS_VALID,$shop_ids);
    }


    /**
     * 获取店铺下拉列表信息
     * @return array
     */
    public static function getShopDropdown($platform_type = null)
    {
        $where = [];
        if(!empty(Yii::$app->user) && !AccessService::hasAllShop()) {
            $shop_ids = Yii::$app->user->identity->shop_id;
            $where['id'] = explode(',', $shop_ids);
        }
        if(!is_null($platform_type)) {
            $where['platform_type'] = $platform_type;
        }
        $shop = Shop::find()->select(['id', 'platform_type', 'name'])->where($where)->asArray()->all();
        $platform_types = ArrayHelper::index($shop, null, 'platform_type');
        $result = [];
        foreach ($platform_types as $platform_type_k => $platform_type_v) {
            $shop_lists = [];
            foreach ($platform_type_v as $shop_v) {
                $shop_lists[] = [
                    'id' => $shop_v['id'],
                    'title' => $shop_v['name']
                ];
            }
            $result[] = [
                'id' => $platform_type_k,
                'title' => Base::$platform_maps[$platform_type_k],
                'child' => $shop_lists
            ];
        }
        return $result;
    }

    /**
     * 获取店铺仓库
     * @param $platform_type
     * @param $shop_id
     * @return mixed
     */
    public static function getShopWarehouse($platform_type,$shop_id)
    {
        $platform_warehouse =  FunCacheService::set(['shop_warehouse', [$platform_type]], function () use ($platform_type) {
            return PlatformShopConfig::find()
            ->where(['platform_type' => $platform_type, 'type' => PlatformShopConfig::TYPE_WAREHOUSE])->asArray()->all();
        }, 60 * 60);
        $platform_warehouse = ArrayHelper::index($platform_warehouse,null,'shop_id');
        return empty($platform_warehouse[$shop_id])?[]:$platform_warehouse[$shop_id];
    }

    /**
     * 清除缓存
     * @param $platform_type
     * @return mixed
     */
    public static function clearShopWarehouseCache($platform_type)
    {
        return FunCacheService::clearOne(['shop_warehouse', [$platform_type]]);
    }

}