<?php
namespace console\controllers;

use backend\controllers\WarehouseProductSalesController;
use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\goods\GoodsStock;
use common\models\goods\GoodsStockDetails;
use common\models\goods_shop\GoodsShopOverseasWarehouse;
use common\models\GoodsShop;
use common\models\Order;
use common\models\order\OrderTransport;
use common\models\OrderGoods;
use common\models\Shop;
use common\models\warehousing\BlContainerGoods;
use common\models\warehousing\Warehouse;
use common\models\warehousing\WarehouseProductSales;
use common\models\warehousing\WarehouseProvider;
use common\services\FApiService;
use common\services\FFBWService;
use common\services\goods\GoodsShopService;
use common\services\warehousing\WarehouseService;
use Darabonba\GatewaySpi\Models\InterceptorContext\request;
use yii\console\Controller;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

class WarehouseController extends Controller
{

    /**
     * @param $warehouse_id
     * @param $fun
     * @param $param
     * @return void
     * @throws \yii\base\Exception
     */
    public function actionFbwTest($warehouse_id, $fun, $param)
    {
        $api_service = FFBWService::factory($warehouse_id);
        $result = $api_service->$fun($param);
        echo CommonUtil::jsonFormat($result);
        exit;
    }

    /**
     * 更新所有仓库商品销售情况
     * @return void
     */
    public function actionUpdateAllWarehouseProduct()
    {
        $one_day_time =  $this->getBeforeTime('-1');
        $seven_day_time =  $this->getBeforeTime('-7');
        $fifteen_day_time =  $this->getBeforeTime('-15');
        $thirty_time =  $this->getBeforeTime('-30');
        $ninety_time =  $this->getBeforeTime('-90');
        $hundred_time = $this->getBeforeTime('-100');
        $select[] = 'o.warehouse,og.cgoods_no,sum(og.goods_num) as total_sales';
        $select[] = $this->combinationSql($one_day_time,'one_day_sales');
        $select[] = $this->combinationSql($seven_day_time,'seven_day_sales');
        $select[] = $this->combinationSql($fifteen_day_time,'fifteen_day_sales');
        $select[] = $this->combinationSql($thirty_time,'thirty_day_sales');
        $select[] = $this->combinationSql($ninety_time,'ninety_day_sales');
        $select = implode(',',$select);
        $limit = 0;
        $where['and'][] = ['!=', 'o.order_status', Order::ORDER_STATUS_CANCELLED];
        $where['and'][] = ['>=', 'o.date', $hundred_time];
        $where['and'][] = ['<=', 'o.date', strtotime(date('Y-m-d',time()))];
        $count = OrderGoods::dealWhere($where)->alias('og')->select($select)
            ->leftJoin(Order::tableName().' o','o.order_id = og.order_id')
            ->groupBy('og.cgoods_no,o.warehouse')->count();
        $overseas_warehouse = WarehouseService::getOverseasWarehouse();
        $overseas_warehouse = array_keys($overseas_warehouse);
        while (true) {
            $limit ++;
            $goods_num = OrderGoods::dealWhere($where)->alias('og')->select($select)
                ->leftJoin(Order::tableName().' o','o.order_id = og.order_id')
                ->groupBy('og.cgoods_no,o.warehouse')->offset(1000*($limit-1))->limit(1000)->asArray()->all();
            if (empty($goods_num)) {
                break;
            }
            echo $limit."/".ceil($count/1000)."\n";
            foreach ($goods_num as $v) {
                $warehouse_where['cgoods_no'] = $v['cgoods_no'];
                $warehouse_where['warehouse_id'] = $v['warehouse'];
                if (empty($v['cgoods_no']) || empty($v['warehouse'])) {
                    continue;
                }
                $model = WarehouseProductSales::find()->where($warehouse_where)->one();
                unset($warehouse_where['warehouse_id']);
                $total_sales = OrderGoods::find()->alias('og')->select('sum(og.goods_num) as total_sales')
                    ->leftJoin(Order::tableName().' o','o.order_id = og.order_id')
                    ->where($warehouse_where)
                    ->andWhere(['=', 'o.warehouse', $v['warehouse']])
                    ->andWhere(['!=', 'o.order_status', Order::ORDER_STATUS_CANCELLED])->scalar();

                if (empty($model)) {
                    $model = new WarehouseProductSales();
                }

                $model['cgoods_no'] = $v['cgoods_no'];
                $model['warehouse_id'] = $v['warehouse'];
                $model['one_day_sales'] = $v['one_day_sales'];
                $model['seven_day_sales'] = $v['seven_day_sales'];
                $model['fifteen_day_sales'] = $v['fifteen_day_sales'];
                $model['thirty_day_sales'] = $v['thirty_day_sales'];
                $model['ninety_day_sales'] = $v['ninety_day_sales'];
                $model['total_sales'] = $total_sales;

                if (in_array($v['warehouse'],$overseas_warehouse)) {
                    $order_frequency = $this->getOrderFrequency($v['cgoods_no'],$v['warehouse']);
                    if ($order_frequency != 0) {
                        $model['order_frequency'] = $order_frequency;
                    }
                }

                if ($model['safe_stock_type'] == WarehouseProductSales::TYPE_STOCK_UP) {
                    $stock_up_where['og.cgoods_no'] = $v['cgoods_no'];
                    $stock_up_where['o.warehouse'] = $v['warehouse'];
                    $safe_stock_day = WarehouseProductSalesController::getStockUpNum($stock_up_where,$model['stock_up_day']);
                    $model['safe_stock_num'] = round($safe_stock_day);
                }

                if ($model['safe_stock_type'] == WarehouseProductSales::TYPE_SALES_WEIGHT) {
                    $safe_stock_param = current(json_decode($model['safe_stock_param'],true));
                    $days = WarehouseProductSalesController::dealDaysTotal($model);
                    $days_key = array_keys($days);
                    $weight_arr = [];
                    $sum = 0;
                    foreach ($safe_stock_param['weight_arr'] as $k => $weight_v) {
                        $weight_arr[$days_key[$k]] = $weight_v;
                    }
                    foreach ($days as $day_k => $day_v) {
                        $sum = $day_v['average_day'] * ($weight_arr[$day_k] / 100) + $sum;
                    }
                    $sum = round($sum,2);
                    $model['safe_stock_num'] = round($sum * $model['stock_up_day']);
                }

                $model->save();
                echo $v['cgoods_no'] . "\n";
            }
        }
        echo "执行完毕！";
    }

    /**
     * 组合sql语句
     * @param $time
     * @param $time_name
     * @return string
     */
    public function combinationSql($time, $time_name)
    {
        $now_time = strtotime(date('Y-m-d',time()));
        $case_time = 'sum(case when o.date >= '.$time.' and o.date <= '.$now_time.' then og.goods_num else 0 end) as '.$time_name;
        return $case_time;
    }


    /**
     * 获取往期时间戳
     * @param $day
     * @return int
     */
    public function getBeforeTime($day)
    {
        return strtotime(date('Y-m-d',strtotime($day." day")));
    }

    /**
     * 更新订单频次
     * @param $cgoods_no
     * @param $warehouse
     * @return int
     */
    public function getOrderFrequency($cgoods_no, $warehouse)
    {
        $info = GoodsStockDetails::find()
            ->select(new Expression("from_unixtime(inbound_time,'%Y-%m-%d') as date,MIN(inbound_time) as inbound_time"))
            ->where(['cgoods_no' => $cgoods_no,'warehouse' => $warehouse])
            ->groupBy('cgoods_no,warehouse,date')->asArray()->all();
        $phase_time = [];
        $order_frequency = 0;

        if (empty($info)) {
            return $order_frequency;
        }

        foreach ($info as $gsd) {
            $star_inbound_time = strtotime(date('Y-m-d',$gsd['inbound_time']));
            $end_inbound_time = $star_inbound_time + 86400;

            $info_details = GoodsStockDetails::find()
                ->select(new Expression("count(*) as count,MAX(outgoing_time) as outgoing_time"))
                ->where(['cgoods_no' => $cgoods_no,'warehouse' => $warehouse,'status' => 3])->andWhere(['!=','outgoing_time',0])
                ->andWhere('inbound_time >='.$star_inbound_time)
                ->andWhere('inbound_time <'.$end_inbound_time)->asArray()->one();
            if (empty($info_details['outgoing_time'])) {
                continue;
            }
            $cancelled_count = GoodsStockDetails::find()->alias('gsd')
                ->leftJoin(Order::tableName().' o','o.order_id = gsd.order_id')
                ->where(['gsd.cgoods_no' => $cgoods_no,'gsd.warehouse' => $warehouse,'gsd.status' => 3])->andWhere(['!=','gsd.outgoing_time',0])
                ->andWhere(['o.order_status' => Order::ORDER_STATUS_CANCELLED])
                ->andWhere('gsd.inbound_time >='.$star_inbound_time)
                ->andWhere('gsd.inbound_time <'.$end_inbound_time)->count();
            $info_details['count'] = $info_details['count'] - $cancelled_count;
            $phase_time[] = ($info_details['outgoing_time'] - $gsd['inbound_time']) / $info_details['count'];
        }

        if (!empty($phase_time)) {
            $count = count($phase_time);
            $sum = array_sum($phase_time);
            $order_frequency = $sum / $count;
        }

        return (int)$order_frequency;
    }


    /**
     * 更新所有订单频次
     * @return void
     */
    public function actionUpdateAllOrderFrequency()
    {
        $overseas_warehouse = WarehouseService::getOverseasWarehouse();
        $overseas_warehouse = array_keys($overseas_warehouse);
        $limit = 0;
        $count = WarehouseProductSales::find()->where(['warehouse_id' => $overseas_warehouse])->count();
        while (true) {
            $limit++;
            $warehouse_product_sales = WarehouseProductSales::find()->where(['warehouse_id' => $overseas_warehouse])
                ->offset(1000*($limit-1))->limit(1000)->all();

            if (empty($warehouse_product_sales)) {
                break;
            }

            echo $limit."/".ceil($count/1000)."\n";
            foreach ($warehouse_product_sales as $wps) {
                $order_frequency = $this->getOrderFrequency($wps['cgoods_no'], $wps['warehouse_id']);

                if ($order_frequency == 0) {
                    continue;
                }

                $wps['order_frequency'] = $order_frequency;
                $wps->save();
                echo $wps['cgoods_no'] . "\n";
            }
        }
        echo '执行成功！';
    }

    /**
     * 同步现有库存
     * @return void
     */
    public function actionPresentStock()
    {
        $where = ['warehouse' => [WarehouseService::WAREHOUSE_ALLEGRO, WarehouseService::WAREHOUSE_OZON, WarehouseService::WAREHOUSE_EMAGE]];
        $goods_stock_lists = GoodsStock::find()->where($where)->andWhere(['<', 'real_num_time', time() - 60 * 60])
            ->orderBy('real_num_time asc')->all();
        foreach ($goods_stock_lists as $goods_stock_v) {
            $shop_list = Shop::find()->cache(300)->where(['warehouse_id' => $goods_stock_v['warehouse']])->indexBy('id')->all();
            $shop_id = ArrayHelper::getColumn($shop_list,'id');
            $goods_shop = GoodsShop::find()
                ->where(['cgoods_no' => $goods_stock_v['cgoods_no'], 'shop_id' => $shop_id])->one();
            if (empty($goods_shop)) {
                CommonUtil::logs($goods_stock_v['cgoods_no'] . ' 获取商品失败', 'warehouse_stock');
                continue;
            }
            $shop = $shop_list[$goods_shop['shop_id']];
            try {
                $api_service = FApiService::factory($shop);
                $stock = $api_service->getPresentStock($goods_shop);
            } catch (\Exception $e) {
                continue;
            }
            if ($stock === false) {
                CommonUtil::logs($goods_stock_v['cgoods_no'] . ' 获取商品库存失败', 'warehouse_stock');
                continue;
            }
            $goods_stock_v->real_num = $stock;
            $goods_stock_v->real_num_time = time();
            $goods_stock_v->save();
            GoodsShopService::updateGoodsStock($goods_stock_v['warehouse'],$goods_stock_v['cgoods_no']);
            echo date('m-d H:i:s').' ,'.$goods_shop['platform_type'].','.$goods_stock_v['cgoods_no'] .','.$stock. "\n";
        }
    }

    /**
     * 更新第三方库存
     * @return void
     */
    public function actionFbwStock()
    {
        $warehouse_provider = Warehouse::find()->alias('w')
            ->leftJoin(WarehouseProvider::tableName() . ' wp', 'wp.id = w.warehouse_provider_id')
            ->select('w.id,wp.warehouse_provider_type,w.warehouse_name')
            ->where(['warehouse_provider_type' => WarehouseProvider::TYPE_THIRD_PARTY])
            ->asArray()->all();

        foreach ($warehouse_provider as $warehouse_v) {
            $warehouse_id = $warehouse_v['id'];
            $where = ['warehouse' => $warehouse_id];
            while (true) {
                $goods_stock_lists = GoodsStock::find()->where($where)->andWhere(['<', 'real_num_time', time() - 60 * 60])
                    ->orderBy('real_num_time asc')->limit(50)->all();
                if (empty($goods_stock_lists)) {
                    break;
                }
                $cgoods_nos = ArrayHelper::getColumn($goods_stock_lists, 'cgoods_no');
                $cgoods_nos = array_values($cgoods_nos);
                try {
                    $result = FFBWService::factory($warehouse_id)->getInventory($cgoods_nos);
                } catch (\Exception $e) {
                    break;
                }
                foreach ($goods_stock_lists as $goods_stock) {
                    $cgoods_no = $goods_stock['cgoods_no'];
                    if (isset($result[$cgoods_no])) {
                        $stock = $result[$cgoods_no];
                        if ($goods_stock['real_num'] != $stock) {
                            $goods_stock['real_num'] = $stock;
                        }
                    }
                    $goods_stock['real_num_time'] = time();
                    $goods_stock->save();
                    GoodsShopService::updateGoodsStock($warehouse_id,$cgoods_no);
                    echo $warehouse_id . ',' . $cgoods_no . "\n";
                }
            }
        }
    }


    /**
     * 同步第三方商品
     * @return void
     */
    public function actionSyncGoodscang()
    {
        $warehouse_id = Warehouse::find()->where(['country' => 'CZ'])->select('id')->scalar();
        $page = 1;

        while (true) {
            try {
                $result = FFBWService::factory($warehouse_id)->getProductSku($page);
                if (empty($result)) {
                    break;
                }

                $lists = ArrayHelper::map($result,'product_sku','product_status');

                $product_sku = array_keys($lists);
                $goods_stock = GoodsStock::find()->where(['cgoods_no' => $product_sku,'warehouse' => $warehouse_id])->indexBy('cgoods_no')->asArray()->all();
                foreach ($lists as $cgoods_no => $status) {

                    if (!empty($goods_stock[$cgoods_no])) {
                        $model = GoodsStock::find()->where(['cgoods_no' => $cgoods_no,'warehouse' => $warehouse_id])->one();
                        if ($model['other_sku'] != 'G8797-' . $cgoods_no || empty($model['other_sku'])) {
                            $model['other_sku'] = 'G8797-' . $cgoods_no;
                            $model->save();
                        }
                        continue;
                    }

                    if ($status != 'S') {
                        continue;
                    }

                    $model = new GoodsStock();
                    $model['cgoods_no'] = $cgoods_no;
                    $model['other_sku'] = 'G8797-' . $cgoods_no;
                    $model['warehouse'] = $warehouse_id;
                    $model->save();

                    echo $cgoods_no."\n";
                }

                $page++;
            } catch (\Exception $e) {
                break;
            }
        }
        echo '执行成功！';
    }

    /**
     * 更新订单物流状态
     * @return void
     */
    public function actionUpdateOrderTransport()
    {
        $order_transport = OrderTransport::find()->where(['status'=>OrderTransport::STATUS_CONFIRMED])->all();
        foreach ($order_transport as $v) {
            $ffbw = FFBWService::factory($v['warehouse_id']);
            $order_exist = $ffbw->getOrder($v['order_id']);
            $order_exist = $order_exist['data'];
            $track_no = $order_exist['tracking_no'];
            if(empty($track_no)){
                continue;
            }
            $v->track_no = $track_no;
            $v->status = OrderTransport::STATUS_EXISTING_TRACKING;
            $v->save();
            Order::updateAll(['track_no'=>$track_no],['order_id'=>$v['order_id']]);
            echo $v['order_id'].','.$track_no."\n";
        }
    }

    /**
     * 店铺商品加入仓库
     * @return void
     */
    public function actionGoodsShopJoin($shop_id,$warehouse_id = 8)
    {
        $cgoods_nos = GoodsStock::find()->where(['warehouse' => $warehouse_id])->andWhere(['>', 'num', 0])->select('cgoods_no')->column();
        $goods_shops = GoodsShop::find()->where(['shop_id' => $shop_id, 'cgoods_no' => $cgoods_nos])->all();
        foreach ($goods_shops as $data) {
            $cgoods_no = $data['cgoods_no'];
            $platform_type = $data['platform_type'];
            $goods_shop_id = $data['id'];
            $data['fixed_price'] = $data['price'];
            $data['other_tag'] = GoodsShop::OTHER_TAG_OVERSEAS;
            $data->save();

            $goods_shop_overseas = GoodsShopOverseasWarehouse::find()->where(['goods_shop_id'=>$goods_shop_id])->one();
            if(empty($goods_shop_overseas)) {
                $goods_shop_overseas = new GoodsShopOverseasWarehouse();
                $goods_shop_overseas->goods_shop_id = $goods_shop_id;
                $goods_shop_overseas->shop_id = $shop_id;
                $goods_shop_overseas->platform_type = $platform_type;
                $goods_shop_overseas->cgoods_no = $cgoods_no;
                $bl_goods = BlContainerGoods::find()->where(['warehouse_id' => $warehouse_id, 'cgoods_no' => $cgoods_no])->orderBy('add_time desc')->one();
                $goods_shop_overseas->estimated_start_logistics_cost = !empty($bl_goods['price']) ? $bl_goods['price'] : 0;

                if ($platform_type == Base::PLATFORM_ALLEGRO) {
                    $country_code = $data['country_code'];
                    $goods_shop_overseas->estimated_end_logistics_cost = $country_code == 'CZ' ? 99 : ($country_code == 'PL' ? 9.99 : 0);
                }
                if ($platform_type == Base::PLATFORM_COUPANG) {
                    $warehouse_id = WarehouseService::WAREHOUSE_COUPANG;
                }
                $goods_shop_overseas->warehouse_id = $warehouse_id;
                $goods_shop_overseas->save();
            }
            echo $shop_id.','.$cgoods_no."\n";
        }
    }

}