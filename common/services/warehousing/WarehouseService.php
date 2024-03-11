<?php

namespace common\services\warehousing;

use common\models\goods\GoodsStock;
use common\models\Shop;
use common\models\warehousing\Warehouse;
use common\models\warehousing\WarehouseProvider;
use common\services\cache\FunCacheService;
use common\services\FFBWService;
use yii\helpers\ArrayHelper;

class WarehouseService
{

    const WAREHOUSE_ANJ = 1;
    const WAREHOUSE_OWN = 2;
    const WAREHOUSE_ALLEGRO = 4;
    const WAREHOUSE_COUPANG = 9;
    const WAREHOUSE_OZON = 10;
    const WAREHOUSE_EMAGE = 12;

    public static $warehouse_map = [
        WarehouseService::WAREHOUSE_OWN => '三林豆',
        WarehouseService::WAREHOUSE_ANJ => '安骏',
    ];

    /**
     * 获取库存详情
     * @param $warehouse_id
     * @return array|mixed
     */
    public static function getInfo($warehouse_id)
    {
        return FunCacheService::set(['warehouse_info', [$warehouse_id]], function () use ($warehouse_id) {
            $warehouse = Warehouse::find()->where(['id'=>$warehouse_id])->asArray()->one();
            $warehouse_provider = WarehouseProvider::find()->where(['id' => $warehouse['warehouse_provider_id']])->asArray()->one();
            $warehouse['warehouse_provider'] = $warehouse_provider;
            return $warehouse;
        }, 60 * 60);
    }

    /**
     * 清除缓存
     * @param $warehouse_id
     * @return mixed
     */
    public static function clearInfoCache($warehouse_id)
    {
        FunCacheService::clearOne(['all_warehouse']);
        FunCacheService::clearOne(['purchase_warehouse']);
        return FunCacheService::clearOne(['warehouse_info', [$warehouse_id]]);
    }

    /**
     * 获取可设置库存列表
     * @return array
     */
    public static function getSettableWareHouseLists($warehouse_provider_id = '')
    {
        $where = [];
        $where['status'] = WarehouseProvider::STATUS_ENABLE;
        if (!empty($warehouse_provider_id)) {
            $where['warehouse_provider_id'] = $warehouse_provider_id;
        }
        return Warehouse::find()->where($where)->asArray()->all();
    }

    /**
     * 获取仓库列表
     * @return array
     */
    public static function getWarehouseMap($warehouse_provider_id = '')
    {
        $warehouse_lists = self::getSettableWareHouseLists($warehouse_provider_id);
        return ArrayHelper::map($warehouse_lists,'id','warehouse_name');
    }

    /**
     * 获取仓库类型
     * @return array
     */
    public static function getWarehouseProviderType($warehouse_id = null)
    {
        $warehouse = FunCacheService::set(['all_warehouse'], function () {
            return Warehouse::find()->alias('w')
                ->leftJoin(WarehouseProvider::tableName() . ' wp', 'wp.id = w.warehouse_provider_id')
                ->select('w.id,wp.warehouse_provider_type,w.warehouse_name')
                ->asArray()->all();
        }, 60 * 60);
        $warehouse = ArrayHelper::map($warehouse, 'id', 'warehouse_provider_type');
        if (empty($warehouse_id)) {
            return $warehouse;
        } else {
            return empty($warehouse[$warehouse_id]) ? false : $warehouse[$warehouse_id];
        }
    }

    /**
     * 获取海外供应商仓库
     * @param $warehouse_provider_type
     */
    public static function getOverseasWarehouse($warehouse_provider_type = '')
    {
        $where = [];
        $where['wp.status'] = WarehouseProvider::STATUS_ENABLE;
        $where['w.status'] = WarehouseProvider::STATUS_ENABLE;
        $where['wp.warehouse_provider_type'] = [WarehouseProvider::TYPE_PLATFORM,WarehouseProvider::TYPE_THIRD_PARTY];
        if (!empty($warehouse_provider_type)) {
            $where['wp.warehouse_provider_type'] = $warehouse_provider_type;
        }
        $warehouse = Warehouse::find()->alias('w')
            ->leftJoin(WarehouseProvider::tableName().' wp','wp.id = w.warehouse_provider_id')
            ->select('w.id,w.warehouse_name')
            ->where($where)
            ->asArray()->all();
        $list = [];
        foreach ($warehouse as $v) {
            $list[$v['id']] = $v['warehouse_name'];
        }
        return $list;
    }

    /**
     * 获取采购仓库
     * @return array
     */
    public static function getPurchaseWarehouse($warehouse_id = null)
    {
        $warehouse = FunCacheService::set(['purchase_warehouse'], function () {
            $where = [];
            $where['wp.status'] = WarehouseProvider::STATUS_ENABLE;
            $where['w.status'] = WarehouseProvider::STATUS_ENABLE;
            $where['wp.warehouse_provider_type'] = [WarehouseProvider::TYPE_PLATFORM, WarehouseProvider::TYPE_THIRD_PARTY, WarehouseProvider::TYPE_LOCAL, WarehouseProvider::TYPE_LOCAL_THIRD_PARTY];
            return Warehouse::find()->alias('w')
                ->leftJoin(WarehouseProvider::tableName() . ' wp', 'wp.id = w.warehouse_provider_id')
                ->select('w.id,w.warehouse_name')
                ->where($where)
                ->asArray()->all();
        }, 60 * 60);
        $list = [];
        foreach ($warehouse as $v) {
            $list[$v['id']] = $v['warehouse_name'];
        }
        if (empty($warehouse_id)) {
            return $list;
        } else {
            return empty($list[$warehouse_id])?'':$list[$warehouse_id];
        }
    }

    /**
     * 同步商品
     * @param int $warehouse_id
     * @param string $cgoods
     * @return string|boolean
     * @throws \yii\base\Exception
     */
    public function syncGoods($warehouse_id,$cgoods_no)
    {
        $warehouse = self::getInfo($warehouse_id);
        if (empty($warehouse['api_params']) && $warehouse['warehouse_provider']['warehouse_provider_type'] != WarehouseProvider::TYPE_THIRD_PARTY) {
            return true;
        }

        $goods_stock = GoodsStock::find()->where(['cgoods_no' => $cgoods_no, 'warehouse' => $warehouse_id])->one();
        if (!empty($goods_stock['other_sku'])) {
            return true;
        }

        $result = FFBWService::factory($warehouse_id)->addGoods($cgoods_no);
        if ($result) {
            $goods_stock->other_sku = (string)$result;
            $goods_stock->save();
        }

        return $result;
    }

    /**
     * 获取商品标签编号
     * @param int $warehouse_id
     * @param array $cgoods
     * @return string|boolean
     * @throws \yii\base\Exception
     */
    public function getGoodsLabelNo($warehouse_id,$cgoods)
    {
        $warehouse = self::getInfo($warehouse_id);
        if(in_array($warehouse['warehouse_provider']['warehouse_provider_type'],[WarehouseProvider::TYPE_LOCAL,WarehouseProvider::TYPE_LOCAL_THIRD_PARTY])){
            return $cgoods['sku_no'];
        }

        if (!empty($warehouse['api_params']) && $warehouse['warehouse_provider']['warehouse_provider_type'] == WarehouseProvider::TYPE_THIRD_PARTY) {
            try {
                return FFBWService::factory($warehouse_id)->getGoodsLabelNo($cgoods['cgoods_no']);
            } catch (\Exception $e) {
                return '';
            }
        }

        return '';
    }

    /**
     * 获取平台海外仓店铺
     * @return array
     */
    public static function getPlatformOverseasWarehouseShop(){
        $warehouse = self::getOverseasWarehouse(WarehouseProvider::TYPE_PLATFORM);
        $shop = Shop::find()->where(['warehouse_id'=>array_keys($warehouse)])->select(['id','name'])->asArray()->all();
        return ArrayHelper::map($shop,'id','name');
    }

}