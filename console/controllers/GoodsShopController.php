<?php
namespace console\controllers;

use common\components\statics\Base;
use common\models\goods\GoodsStock;
use common\models\goods_shop\GoodsShopSalesTotal;
use common\models\GoodsEvent;
use common\models\GoodsShop;
use common\models\Order;
use common\models\OrderGoods;
use common\models\sys\Exectime;
use common\services\api\GoodsEventService;
use common\services\goods\GoodsShopService;
use common\services\ShopService;
use yii\console\Controller;
use yii\helpers\ArrayHelper;

class GoodsShopController extends Controller
{
    /**
     * 更新所有店铺商品销售
     * @return void
     */
    public function actionUpdateAllGoodsShopSales()
    {
        $time = time();
        $order = Order::find()->alias('o')
            ->leftJoin(OrderGoods::tableName() . ' og', 'o.order_id = og.order_id')
            ->select('o.source,shop_id,cgoods_no,sum(og.goods_num) as num')->groupBy('shop_id,cgoods_no')
            ->where(['!=','o.source',Base::PLATFORM_ALLEGRO])->andWhere(['!=','o.order_status',Order::ORDER_STATUS_CANCELLED])
            ->asArray()->all();

        $this->updateAllOrderAllegroGoodsShopSales();
        foreach ($order as $v) {
            if (empty($v['cgoods_no'])) {
                continue;
            }
            $this->operateGoodsShopSale($v,true);
        }
        $object_type = Exectime::TYPE_SHOP_GOODS_SALES;
        Exectime::setTime($time, $object_type);
    }

    /**
     * 增量更新商品销售数量
     * @return void
     */
    public function actionUpdateGoodsShopSales()
    {
        $object_type = Exectime::TYPE_SHOP_GOODS_SALES;
        $exec_time = Exectime::getTime($object_type);
        if (empty($exec_time)) {
            $this->actionUpdateAllGoodsShopSales();
            return;
        }
        $end_exec_time = time();
        $allegro_shop = ShopService::getShopMap(Base::PLATFORM_ALLEGRO);
        $allegro_shop_id = array_keys($allegro_shop);
        $this->updateOrderAllegroGoodsShopSales($exec_time,$allegro_shop_id);
        $order_update = Order::find()->alias('o')
            ->leftJoin(OrderGoods::tableName() . ' og', 'o.order_id = og.order_id')
            ->select('shop_id,cgoods_no')
            ->where(['>', 'og.update_time', $exec_time])
            ->andWhere(['not in', 'shop_id', $allegro_shop_id])->groupBy('shop_id,cgoods_no')
            ->asArray()->all();
        foreach ($order_update as $order) {
            if (empty($order['cgoods_no'])) {
                continue;
            }
            $order_sales = Order::find()->alias('o')
                ->leftJoin(OrderGoods::tableName() . ' og', 'o.order_id = og.order_id')
                ->select('o.source,shop_id,cgoods_no,sum(og.goods_num) as num')
                ->where([
                    'shop_id' => $order['shop_id'],
                    'cgoods_no' => $order['cgoods_no'],
                ])->andWhere(['!=','o.order_status',Order::ORDER_STATUS_CANCELLED])
                ->asArray()->one();
            $this->operateGoodsShopSale($order_sales,true);
        }
        Exectime::setTime($end_exec_time, $object_type);
        echo '执行完成' . "\n";
    }


    /**
     * 更新allegro平台商品销售
     * @param boolean $is_all
     * @return array
     */
    public function updateAllOrderAllegroGoodsShopSales($is_all = true,$where = [])
    {
        $order_allegro = Order::find()->alias('o')
            ->leftJoin(OrderGoods::tableName() . ' og','o.order_id = og.order_id')
            ->leftJoin(GoodsShop::tableName() . ' gs','gs.shop_id = o.shop_id and gs.country_code = o.country and gs.cgoods_no = og.cgoods_no')
            ->select('o.source,o.shop_id,o.country,og.cgoods_no,sum(og.goods_num) as num,gs.id as goods_shop_id')
            ->where(['=','o.source',Base::PLATFORM_ALLEGRO])->andWhere(['!=','o.order_status',Order::ORDER_STATUS_CANCELLED])
            ->groupBy('o.shop_id,o.country,og.cgoods_no');

        if (!empty($where)) {
            $order_allegro->andWhere($where);
        }
        if ($is_all) {
            $order_allegro = $order_allegro->asArray()->all();
        } else {
            $order_allegro = $order_allegro->asArray()->one();
            return $order_allegro;
        }

        foreach ($order_allegro as $v) {
            if (empty($v['cgoods_no'])) {
                continue;
            }
            $this->operateGoodsShopSale($v);
        }
    }


    /**
     * 增量更新allegro平台销售
     * @param $exec_time
     * @param $allegro_shop_id
     */
    public function updateOrderAllegroGoodsShopSales($exec_time = '',$allegro_shop_id)
    {
        $order_allegro_update = Order::find()->alias('o')
            ->leftJoin(OrderGoods::tableName() . ' og', 'o.order_id = og.order_id')
            ->leftJoin(GoodsShop::tableName() . ' gs','gs.shop_id = o.shop_id and gs.country_code = o.country and gs.cgoods_no = og.cgoods_no')
            ->select('o.shop_id,og.cgoods_no,o.country')
            ->where(['>', 'og.update_time', $exec_time])
            ->andWhere(['o.shop_id' => $allegro_shop_id])->groupBy('o.shop_id,og.cgoods_no,o.country')
            ->asArray()->all();
        foreach ($order_allegro_update as $v) {
            if (empty($v['cgoods_no'])) {
                continue;
            }
            $where = [];
            $where['o.shop_id'] = $v['shop_id'];
            $where['og.cgoods_no'] = $v['cgoods_no'];
            $where['o.country'] = $v['country'];
            $order_allegro = $this->updateAllOrderAllegroGoodsShopSales(false,$where);
            $this->operateGoodsShopSale($order_allegro);
        }
    }


    /**
     * 操作商品销量
     * @param $data
     * @param bool $select_shop
     * @return void
     */
    public function operateGoodsShopSale($data,$select_shop = false)
    {
        $where = [];
        $where['shop_id'] = $data['shop_id'];
        $where['cgoods_no'] = $data['cgoods_no'];
        if (!empty($data['goods_shop_id'])) {
            $where['goods_shop_id'] = $data['goods_shop_id'];
        }

        $goods_shop_sales_total = GoodsShopSalesTotal::find()->where($where)->one();
        if(empty($goods_shop_sales_total)){
            if ($select_shop) {
                $goods_shop = GoodsShop::find()->where(['shop_id' => $data['shop_id'], 'cgoods_no' => $data['cgoods_no']])->one();
                $data['goods_shop_id'] = empty($goods_shop) ? '' : $goods_shop['id'];
            }
            $goods_shop_sales_total = new GoodsShopSalesTotal();
            $goods_shop_sales_total->goods_shop_id = empty($data['goods_shop_id']) ? 0 : $data['goods_shop_id'];
            $goods_shop_sales_total->platform_type = $data['source'];
            $goods_shop_sales_total->shop_id = $data['shop_id'];
            $goods_shop_sales_total->cgoods_no = $data['cgoods_no'];
        }
        $goods_shop_sales_total->total_sales = $data['num'];
        $goods_shop_sales_total->save();
        echo $data['shop_id'].','.$data['cgoods_no'].','.$data['num']."\n";
    }

    /**
     * 更新商品
     * @param $warehouse_id
     * @return void
     * @throws \yii\base\Exception
     */
    public function actionPlatformStock($warehouse_id)
    {
        $goods_stock = GoodsStock::find()->where(['warehouse'=>$warehouse_id])->all();
        foreach ($goods_stock as $v) {
            GoodsShopService::updateGoodsStock($warehouse_id,$v['cgoods_no'],true);
            echo $warehouse_id.','.$v['cgoods_no']."\n";
        }
    }

    /**
     * 复制价格
     * @param int $o_shop_id 原店铺
     * @param int $shop_id 现店铺
     * @return void
     */
    public function actionCopyPrice($o_shop_id,$shop_id)
    {
        $o_goods_shop_lists = GoodsShop::find()->where(['shop_id'=>$o_shop_id])->asArray()->all();
        foreach ($o_goods_shop_lists as $o_goods_shop) {
            $goods_shop = GoodsShop::find()->where(['shop_id'=>$shop_id,'cgoods_no'=>$o_goods_shop['cgoods_no']])->one();
            if(empty($goods_shop)) {
                continue;
            }
            $goods_shop['fixed_price'] = $o_goods_shop['price'];
            $goods_shop['price'] = $o_goods_shop['price'];
            $goods_shop->save();
            if (GoodsEventService::hasEvent(GoodsEvent::EVENT_TYPE_UPDATE_PRICE,$goods_shop['platform_type'])) {
                GoodsEventService::addEvent($goods_shop,GoodsEvent::EVENT_TYPE_UPDATE_PRICE, 0);
            }
            echo $goods_shop['shop_id'] .','.$goods_shop['cgoods_no']."\n";
        }
    }

}