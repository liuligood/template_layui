<?php
namespace console\controllers\api;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\CategoryMapping;
use common\models\FinancialPeriodRollover;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsOzon;
use common\models\goods_shop\GoodsErrorAssociation;
use common\models\goods_shop\GoodsShopPriceChangeLog;
use common\models\GoodsEvent;
use common\models\GoodsShop;
use common\models\GoodsShopExpand;
use common\models\GoodsSource;
use common\models\Order;
use common\models\OrderGoods;
use common\models\platform\PlatformCategory;
use common\models\platform\PlatformCategoryAttribute;
use common\models\platform\PlatformCategoryField;
use common\models\platform\PlatformCategoryFieldValue;
use common\models\platform\PlatformShopConfig;
use common\models\PromoteCampaign;
use common\models\PromoteCampaignExec;
use common\models\ReportUserCount;
use common\models\Shop;
use common\models\sys\Exectime;
use common\services\api\GoodsEventService;
use common\services\category\OzonCategoryService;
use common\services\FApiService;
use common\services\goods\FGoodsService;
use common\services\goods\GoodsErrorSolutionService;
use common\services\goods\GoodsFollowService;
use common\services\goods\GoodsService;
use common\services\goods\GoodsShopService;
use common\services\goods\platform\OzonPlatform;
use common\services\id\PlatformGoodsSkuIdService;
use common\services\order\OrderService;
use common\services\ShopService;
use common\services\sys\ChatgptService;
use common\services\sys\ExchangeRateService;
use yii\console\Controller;
use yii\helpers\ArrayHelper;

class OzonController extends Controller
{

    public function actionTest()
    {
        $shop = Shop::findOne(327);
        $api_service = FApiService::factory($shop);
        //$result = $api_service->getOrderInfo('51851695-0017-1');
        //$result = $api_service->getOrderInfo('32224951-0092-1');


        //$result = $api_service->getOrderLists('2022-06-01','2022-06-02T00:00');
        //$result = $api_service->getTask('529657669');
        //$result = $api_service->getTask('497923957');
        //$result = $api_service->getTask('497958103');
        //$result = $api_service->getGoodsList(1,'VALIDATION_STATE_PENDING');
        //$result = $api_service->getGoodsList(1,'TO_SUPPLY');

        ///$result = $api_service->getWarehouse();
        $sku = 'P06462144421462';
        //$sku = 'XXX-brush-258';
        $id = '197895443';
        //$result = $api_service->getCategoryAttributes('17031194');
        //$result = $api_service->archive($id);
        //echo $sku ."\n";
        $result = $api_service->getProductsToAsin('P06666002708079');
        //$result = $api_service->getTask(529053331);
        //$result = $api_service->updatePrice1($sku,1899);

        //$result = $api_service->getProductsToAsinList(['ZSC-Watches-399']);

        //$result = $api_service->getOrderInfo('06483482-0462-4');

        //$result = $api_service->getProductsToAsin($sku);
        //$result = $api_service->getProductsAttributesToAsin($sku);
        //$result = $api_service->getCategoryAttributes('17038207');
        //$result = $api_service->getCategoryAttributesValue(17031225, 8229);
        //$result = $api_service->getCategory();

        //$result = $api_service->getCategoryProductParameters(261627);
        //$result = $api_service->getCategory();
        echo CommonUtil::jsonFormat($result);
        exit;
    }

    /**
     * 获取促销商品
     * @return void
     */
    public function actionPromotionGoods($shop_id = null)
    {
        $where = ['platform_type' => Base::PLATFORM_OZON];
        if (!empty($shop_id)) {
            $where['id'] = $shop_id;
        }
        $shop_lists = Shop::find()->where($where)->all();
        echo '店铺id,店铺名称,子商品编号,自定义sku,现有价格,促销价格,预计最低价'. "\n";
        foreach ($shop_lists as $shop) {
            $limit = 0;
            while (true) {
                //echo '##,'.$limit . "\n";
                $limit++;
                try {
                    $goods_shop = GoodsShop::find()->where(['shop_id' => $shop['id'], 'status' => GoodsShop::STATUS_SUCCESS])->offset(1000 * ($limit - 1))->limit(1000)->indexBy('platform_sku_no')->asArray()->all();
                    if (empty($goods_shop)) {
                        //echo '##,'.$shop['id'] . ',' . $shop['name'] . ',执行完毕' . "\n";
                        break;
                    }
                    $sku_no = array_keys($goods_shop);
                    $api_service = FApiService::factory($shop);
                    $result = $api_service->getProductsPriceToAsin($sku_no);
                    foreach ($result['items'] as $v) {
                        if ($v['price']['price'] - $v['price']['marketing_seller_price'] > 0.00001) {//促销价
                            $goods_shop_info = $goods_shop[$v['offer_id']];
                            $goods_child = GoodsChild::find()->where(['goods_no' => $goods_shop_info['cgoods_no']])->asArray()->one();
                            $min_cost_arr = GoodsFollowService::getMinCostPrice($goods_child, $goods_shop_info);
                            echo $shop['id'] . ',' . $shop['name'] .','. $goods_shop_info['cgoods_no'].','.$v['offer_id'].',' . $v['price']['price']. ',' .$v['price']['marketing_seller_price']. ',' . $min_cost_arr[0] . "\n";
                        }
                    }
                } catch (\Exception $e) {
                    //echo '##,'.$shop['id'] . ',' . $shop['name'] . ',出错' . $e->getMessage() . "\n";
                }
            }
        }
    }

    /**
     * 订单结算
     * @param $shop_id
     * @return void
     * @throws \yii\base\Exception
     */
    public function actionOrderSettlement($shop_id = null)
    {
        $where = ['platform_type' => Base::PLATFORM_OZON,'status'=>Shop::STATUS_VALID];
        if (!empty($shop_id)) {
            $where['id'] = $shop_id;
        }
        $shop_lists = Shop::find()->where($where)->all();
        foreach ($shop_lists as $shop) {
            $shop_id = $shop['id'];
            $api_service = FApiService::factory($shop);

            $this->repeatALl($api_service, function ($v) use ($shop_id, $shop) {
                $id = (string)$v['operation_id'];
                $financial_rollover = FinancialPeriodRollover::find()->where(['shop_id' => $shop_id, 'identifier' => $id])->one();
                if (!empty($financial_rollover)) {
                    return false;
                }
                if ($v['amount'] == 0) {
                    return false;
                }

                $data = [
                    'platform_type' => $shop['platform_type'],
                    'shop_id' => $shop_id,
                    'identifier' => $id,
                    'currency' => 'RUB',
                    'date' => strtotime($v['operation_date']),
                    'collection_time' => strtotime($v['operation_date']),
                    'buyer' => $v['operation_type_name'],
                    'params' => json_encode($v, JSON_UNESCAPED_UNICODE)
                ];

                $relation_no = '';
                if (!empty($v['posting']) && !empty($v['posting']['posting_number'])) {
                    $relation_no = $v['posting']['posting_number'];
                }

                $relation_no_lists = [];
                if (!empty($relation_no)) {
                    $relation_no_arr = explode('-', $relation_no);
                    if (count($relation_no_arr) == 2) {
                        $relation_no_lists = Order::find()->where(['like', 'relation_no', $relation_no, 'shop_id' => $shop_id])->select('relation_no')->column();
                    }
                }
                if(empty($relation_no_lists)){
                    $relation_no_lists[] = $relation_no;
                }

                $relation_no_num = count($relation_no_lists);
                foreach ($relation_no_lists as $relation_no_v) {
                    $data['relation_no'] = $relation_no_v;
                    if (!empty($v['items'])) {
                        $itmes = current($v['items']);
                        $data['offer'] = $itmes['name'] . '(' . $itmes['sku'] . ')';
                    }
                    $is_amount = false;
                    $data['operation'] = $v['operation_type'];
                    if ($v['type'] == 'orders' && $v['operation_type'] == 'OperationAgentDeliveredToCustomer') {//销售
                        $f_data = $data;
                        $f_data['amount'] = $v['sale_commission'] / $relation_no_num;
                        $f_data['operation'] = 'commission';
                        FinancialPeriodRollover::add($f_data);
                        $data['amount'] = $v['accruals_for_sale'] / $relation_no_num;
                    } else if ($v['type'] == 'returns' && $v['operation_type'] == 'ClientReturnAgentOperation') {//退货
                        $f_data = $data;
                        $f_data['amount'] = $v['sale_commission'] / $relation_no_num;
                        $f_data['operation'] = 'Refundscommissions';
                        FinancialPeriodRollover::add($f_data);
                        $data['amount'] = $v['accruals_for_sale'] / $relation_no_num;
                    } else {
                        //$data['amount'] = $v['amount'] / $relation_no_num;
                        $is_amount = true;
                    }
                    $service_amount = 0;
                    foreach ($v['services'] as $s_v) {
                        if (empty($s_v['price'])) {
                            continue;
                        }
                        $f_data = $data;
                        $f_data['amount'] = $s_v['price'] / $relation_no_num;
                        $f_data['operation'] = $s_v['name'];
                        FinancialPeriodRollover::add($f_data);
                        $service_amount += $s_v['price'];
                    }

                    if ($is_amount) {
                        $data['amount'] = ($v['amount'] - $service_amount) / $relation_no_num;
                    }

                    if($data['amount'] != 0) {
                        FinancialPeriodRollover::add($data);
                    }
                }

            });
        }
    }


    public function repeatALl($api_service,$fun, $from_time = null , $to_time = null, $page = 1)
    {
        $limit = 1000;
        $type = 'getBilling';
        $object_type  = Exectime::TYPE_SHOP_BILLING;

        $shop_id = $api_service->shop['id'];
        if(empty($to_time)) {
            $exec_time = Exectime::getTime($object_type,$shop_id);
            if (empty($exec_time)) {
                $date = Order::find()->select('date')->where(['shop_id' => $shop_id])->orderBy('date asc')->limit(1)->scalar();
                $exec_time = $date < strtotime('2023-11-01') ? strtotime('2023-11-01') : $date;
            }
            $to_time = $exec_time + 15 * 24 * 60 * 60;
            $yes_time = strtotime(date("Y-m-d")) - 1;
            $to_time = min($to_time, $yes_time);
            $exec_time = $exec_time - 24 * 60 * 60;//多执行一天
            $from_time = date('Y-m-d', $exec_time);
            $to_time = date('Y-m-d\TH:i:s\Z', $to_time);
        }

        echo 'type:'.$type.' shop_id:'.$shop_id.' from_time:'.$from_time.' to_time:'.$to_time.' page:'.$page ."\n";
        CommonUtil::logs('type:'.$type.' shop_id:'.$shop_id.' from_time:'.$from_time.' to_time:'.$to_time.' page:'.$page,'ozon_settlement_time');
        try {
            $result = $api_service->$type($from_time, $to_time, $page, $limit);
        } catch (\Exception $e) {
            echo 'type:'.$type.' shop_id:'.$shop_id.' from_time:'.$from_time.' to_time:'.$to_time.' page:'.$page .' err:'. $e->getMessage()."\n";
            CommonUtil::logs('type:'.$type.' shop_id:'.$shop_id.' from_time:'.$from_time.' to_time:'.$to_time.' page:'.$page.' err:'. $e->getMessage(),'ozon_settlement_time');
            return false;
        }
        $result = $result['result'];
        foreach ($result['operations'] as $v) {
            $fun($v);
        }
        if (count($result) == $limit) {
            $page += 1;
            return $this->repeatALl($api_service, $fun, $from_time, $to_time, $page);
        } else {
            Exectime::setTime(strtotime($to_time),$object_type,$shop_id);
            if(strtotime(date('Y-m-d',strtotime($to_time) + 1)) < strtotime(date("Y-m-d"))) {
                return $this->repeatALl($api_service, $fun, null, null, 1);
            }
        }
    }

    /**
     *
     * @return void
     */
    public function actionUpdateEubWarehouse($shop_id = 0,$cgoods_no = null)
    {
        $where = [];
        if(!empty($shop_id)) {
            $where['shop_id'] = $shop_id;
        }else{
            $where['platform_type'] = Base::PLATFORM_OZON;
        }
        if(!empty($cgoods_no)) {
            $where['cgoods_no'] = $cgoods_no;
        }
        $goods_shops = GoodsShop::find()->where($where)->asArray()->all();
        foreach ($goods_shops as $goods_shop) {
            $warehouse_lists = ShopService::getShopWarehouse(Base::PLATFORM_OZON, $goods_shop['shop_id']);
            $warehouse_id = '';
            foreach ($warehouse_lists as $v) {
                if ($v['type_val'] == 'XY-e邮宝特惠') {
                    $warehouse_id = $v['type_id'];
                }
            }

            echo "start,".$goods_shop['shop_id'].','.$goods_shop['goods_no']."\n";
            ///$goods = Goods::find()->where(['goods_no'=>$goods_shop['goods_no']])->one();
            if($goods_shop['follow_claim'] == GoodsShop::FOLLOW_CLAIM_NO) {
                $goods_child = GoodsChild::find()->where(['cgoods_no'=>$goods_shop['cgoods_no']])->one();
                if($goods_child['price'] <= 0) {
                    continue;
                }

                $goods_shop_expand = GoodsShopExpand::find()->where(['goods_shop_id' => $goods_shop['id']])->one();
                if (empty($goods_shop_expand)) {
                    continue;
                }

                $logistics_id = $goods_shop_expand['real_logistics_id'] > 0?$goods_shop_expand['real_logistics_id']:$goods_shop_expand['logistics_id'];
                if ($logistics_id == $warehouse_id) {
                    continue;
                }

                $weight = $goods_child['real_weight'] > 0 ? $goods_child['real_weight'] : $goods_child['weight'];
                $size = empty($goods_child['package_size'])?'':$goods_child['package_size'];
                //EUB
                $freight_price = OrderService::getMethodLogisticsFreightPrice(2146, 'RU', $weight, $size);
                if($freight_price <= 0) {
                    continue;
                }
                $exchange_rate = ExchangeRateService::getRealConversion('CNY', 'USD');
                $price = round(($goods_child['price'] + 8 + $freight_price) * $exchange_rate * 1.15 , 2);

                //兴远陆运到门
                $freight_price = OrderService::getMethodLogisticsFreightPrice(2011, 'RU', $weight, $size);
                if($freight_price <= 0) {
                    continue;
                }
                $price2 = round(($goods_child['price'] + 8 + $freight_price) * $exchange_rate * 1.15 , 2);

                /*采购价30以内
                OZON-XY-LUYUN-DM减OZON-e邮宝特惠，大于等于1，选EUB
                采购价30-50
                OZON-XY-LUYUN-DM减OZON-e邮宝特惠，大于等于2，选EUB
                采购价50-100
                OZON-XY-LUYUN-DM减OZON-e邮宝特惠，大于等于3，选EUB
                采购价100-200
                OZON-XY-LUYUN-DM减OZON-e邮宝特惠，大于等于5，选EUB
                采购价200-300
                OZON-XY-LUYUN-DM减OZON-e邮宝特惠，大于等于6，选EUB
                采购价300以上
                OZON-XY-LUYUN-DM减OZON-e邮宝特惠，大于等于8，选EUB
                */
                if($goods_child['price'] < 30){
                    $price_diff = 1;
                } else if ($goods_child['price'] < 50){
                    $price_diff = 2;
                } else if ($goods_child['price'] < 100){
                    $price_diff = 3;
                } else if ($goods_child['price'] < 200){
                    $price_diff = 5;
                } else if ($goods_child['price'] < 300){
                    $price_diff = 6;
                } else {
                    $price_diff = 8;
                }
                if($price2 - $price < $price_diff) {
                    continue;
                }

                if($goods_shop['status'] == GoodsShop::STATUS_SUCCESS){
                    GoodsEventService::addEvent($goods_shop, GoodsEvent::EVENT_TYPE_UPDATE_STOCK, -1);
                }
                $goods_shop_expand->real_logistics_id = $warehouse_id;
                $goods_shop_expand->save();
                echo "end,".$goods_shop['goods_no']."\n";
            }
        }
    }

    /**
     * 重新执行错误标题
     * @return void
     */
    public function actionReRunErrorTitle($goods_shop_id = null,$error_timer = 0)
    {
        $where = [
            'gs.status' => GoodsShop::STATUS_FAIL,
            'gs.platform_type' => Base::PLATFORM_OZON,
            'gse.error_type' => $error_timer ,
        ];
        if(!empty($goods_shop_id)){
            $where['gs.id'] = $goods_shop_id;
        }
        $goods_shpo_expands = GoodsShopExpand::find()->alias('gse')
            ->leftJoin(GoodsShop::tableName() . ' gs', 'gs.id = gse.goods_shop_id')
            ->andWhere($where)->all();
        $i = 0;
        foreach ($goods_shpo_expands as $goods_shop_e) {
            try {
                $goods_error_ids = GoodsErrorAssociation::find()->where(['goods_shop_id' => $goods_shop_e['goods_shop_id']])
                    ->select('error_id')->asArray()->column();
                $error_title = false;
                foreach ($goods_error_ids as $error_id) {
                    if (in_array((int)$error_id, [6, 9, 15, 228])) {
                        $error_title = true;
                    }
                }
                if ($error_title) {
                    if ($i > 50) {
                        break;
                    }
                    $goods_child = GoodsChild::find()->where(['cgoods_no' => $goods_shop_e['cgoods_no']])->asArray()->one();
                    $good_info = Goods::find()->where(['goods_no' => $goods_child['goods_no']])->one();
                    if ($good_info['language'] != '' && $good_info['language'] != 'en') {
                        continue;
                    }
                    $i++;
                    $code = 'ozon_goods_name';
                    $param = [
                        'title' => $good_info['goods_name']
                    ];
                    $ai_result = ChatgptService::templateExec($code, $param);
                    if (empty($ai_result)) {
                        $etr = $goods_shop_e['shop_id'] . ',' . $goods_shop_e['cgoods_no'] . ",AI翻译失败";
                        CommonUtil::logs($etr, 'ozon_goods_name');
                        echo $etr . "\n";
                        continue;
                    }
                    //使用AI标题
                    $goods_shpo_expand = GoodsShopExpand::find()->where(['goods_shop_id' => $goods_shop_e['goods_shop_id']])->one();
                    $goods_shpo_expand->goods_title = $ai_result;
                    $goods_shpo_expand->error_type = $error_timer + 1;
                    $goods_shpo_expand->save();

                    $goods_shop = GoodsShop::find()->where(['id' => $goods_shop_e['goods_shop_id']])->one();
                    (new GoodsShopService())->release($goods_shop, true);

                    $etr = $goods_shop['shop_id'] . ',' . $goods_shop['cgoods_no'] . ',' . $ai_result;
                    CommonUtil::logs($etr, 'ozon_goods_name');
                    echo $etr . "\n";
                    sleep(2);
                }
            } catch (\Exception $e) {
                $etr = $goods_shop_e['shop_id'] . ',' . $goods_shop_e['cgoods_no'] . ','.$e->getMessage();
                CommonUtil::logs($etr, 'ozon_goods_name');
                echo $etr . "\n";
            }
        }
        sleep(120);
        echo '执行完'. "\n";
    }

    /**
     *
     * @return void
     */
    public function actionFollowWarehouse($shop_id)
    {
        $goods_shops = GoodsShop::find()->where(['shop_id' => $shop_id])->asArray()->all();
        $warehouse_lists = ShopService::getShopWarehouse(Base::PLATFORM_OZON, $shop_id);
        $warehouse_id = '';
        foreach ($warehouse_lists as $v) {
            if ($v['type_val'] == 'XY-e邮宝特惠') {
                $warehouse_id = $v['type_id'];
            }
        }

        foreach ($goods_shops as $goods_shop) {
            echo "start,".$goods_shop['goods_no']."\n";
            $goods = Goods::find()->where(['goods_no'=>$goods_shop['goods_no']])->one();
            if($goods_shop['follow_claim'] == GoodsShop::FOLLOW_CLAIM_YES) {
                $follow_price = GoodsSource::find()
                    ->where(['platform_type' => Base::PLATFORM_OZON, 'goods_no' => $goods['goods_no']])
                    ->select('price')->scalar();
                if ($follow_price > 0 && $follow_price < 2000) {//跟卖价格小于2000卢布的使用e邮宝
                    $goods_shop_expand = GoodsShopExpand::find()->where(['goods_shop_id' => $goods_shop['id']])->one();
                    if (empty($goods_shop_expand)) {
                        continue;
                    }
                    if ($goods_shop_expand['logistics_id'] != $warehouse_id) {
                        GoodsEventService::addEvent($goods_shop, GoodsEvent::EVENT_TYPE_UPDATE_STOCK, -1);
                        $goods_shop_expand->logistics_id = $warehouse_id;
                        $goods_shop_expand->save();
                        echo "end,".$goods_shop['goods_no']."\n";
                    }
                }
            }
        }
    }

    /**
     * 查询eub渠道
     * @return void
     */
    public function actionEubWarehouse()
    {
        $shop_lists = Shop::find()->where(['platform_type'=>Base::PLATFORM_OZON])->all();
        foreach ($shop_lists as $shop) {
            $shop_id = $shop['id'];
            $goods_shops = GoodsShop::find()->where(['shop_id' => $shop_id])->asArray()->all();
            $warehouse_lists = ShopService::getShopWarehouse(Base::PLATFORM_OZON, $shop_id);
            $warehouse_id = '';
            $ly_warehouse_id = '';
            foreach ($warehouse_lists as $v) {
                if ($v['type_val'] == 'XY-e邮宝特惠') {
                    $warehouse_id = $v['type_id'];
                }

                if ($v['type_val'] == 'XY-LUYUN') {
                    $ly_warehouse_id = $v['type_id'];
                }
            }

            foreach ($goods_shops as $goods_shop) {
                if ($goods_shop['price'] >= 22) {
                    $goods_shop_expand = GoodsShopExpand::find()->where(['goods_shop_id' => $goods_shop['id']])->one();
                    if (empty($goods_shop_expand)) {
                        continue;
                    }
                    $real_logistics_id = empty($goods_shop_expand['real_logistics_id'])?$goods_shop_expand['logistics_id']:$goods_shop_expand['real_logistics_id'];
                    if ($real_logistics_id == $warehouse_id) {
                        if($ly_warehouse_id == $goods_shop_expand['logistics_id']){
                            $goods_shop_expand['real_logistics_id'] = '';
                        }else{
                            $goods_shop_expand['logistics_id'] = $ly_warehouse_id;
                            $goods_shop_expand['real_logistics_id'] = '';
                        }
                        $goods_shop_expand->save();
                        GoodsEventService::addEvent($goods_shop, GoodsEvent::EVENT_TYPE_UPDATE_STOCK, -1);
                        echo $shop['name'].','.$goods_shop['goods_no'].",".$goods_shop['price'] . "\n";
                    }
                }
            }
        }
    }

    /**
     * 广告统计数据
     * @return void
     * @throws \yii\base\Exception
     */
    public function actionCampaignStatistics($shop_id = null)
    {
        $object_type = Exectime::TYPE_CAMPAIGN_STATISTICS;
        $where = ['platform_type' => Base::PLATFORM_OZON, 'status' => 1];
        if(!empty($shop_id)) {
            $where['shop_id'] = $shop_id;
        }
        $promote_campaign = PromoteCampaign::find()
            ->where($where)->select('shop_id,promote_id')->asArray()->all();
        $promote_campaign = ArrayHelper::index($promote_campaign, 'promote_id' , 'shop_id');
        $end_day = date("Y-m-d", strtotime("-1 day"));
        foreach ($promote_campaign as $shop_id => $promote_campaign_v) {
            $exec_time = Exectime::getTime($object_type, $shop_id);
            if (empty($exec_time)) {
                $start_day = date("Y-m-d", strtotime("-10 days"));
            } else {
                //已执行过
                if ($exec_time == strtotime($end_day)) {
                    continue;
                }
                $start_day = date("Y-m-d", $exec_time);
            }
            $shop = Shop::findOne($shop_id);
            $api_service = FApiService::factory($shop);
            $promote_ids = [];
            foreach ($promote_campaign_v as $e_v){
                $promote_ids[] = (int)$e_v['promote_id'];
            }
            $exec_promote_ids = array_slice($promote_ids, 0, 10);
            $next_promote_ids = array_slice($promote_ids, 10);

            echo date('Y-m-d H:i:s').' shop_id:'.$shop_id . ' campaign_ids:' . implode(',',$exec_promote_ids) . ' start_day:'.$start_day. ' end_day:'.$end_day ."\n";
            $result = $api_service->getCampaignStatistics($exec_promote_ids, $start_day, $end_day);
            if (!empty($result) && !empty($result['UUID'])) {
                echo date('Y-m-d H:i:s').' shop_id:'.$shop_id . ' campaign_ids:' . implode(',',$exec_promote_ids) . ' start_day:'.$start_day. ' end_day:'.$end_day .' uuid:'.$result['UUID']."\n";
                PromoteCampaignExec::add([
                    'platform_type' => Base::PLATFORM_OZON,
                    'shop_id' => $shop_id,
                    'promote_ids' => implode(',',$exec_promote_ids),
                    'next_promote_ids' => !empty($next_promote_ids)?implode(',',$next_promote_ids):'',
                    'start_day' => $start_day,
                    'end_day' => $end_day,
                    'uuid' => $result['UUID'],
                    'status' => 0,
                    'plan_time' => time() + 10*60
                ]);
                Exectime::setTime(strtotime($end_day), $object_type, $shop_id);
            }
        }
        echo date('Y-m-d H:i:s').'执行完成'."\n";
    }

    /**
     * 获取广告统计结果
     * @return void
     */
    public function actionCampaignStatisticsReport($id = null)
    {
        $where = ['status' => 0];
        if (!empty($id)) {
            $where['id'] = $id;
        }
        $promote_campaign = PromoteCampaignExec::find()->where($where)->andWhere(['<', 'plan_time', time()])
            ->orderBy('plan_time asc')->limit(100)->all();
        foreach ($promote_campaign as $v) {
            $shop = Shop::findOne($v['shop_id']);
            $api_service = FApiService::factory($shop);
            $e_id = explode(',',$v['promote_ids']);
            if(!empty($v['uuid'])) {
                $result = $api_service->getCampaignStatisticsStatus($v['uuid']);
                if ($result['state'] == 'OK') {
                    $result_report = $api_service->getCampaignStatisticsReportStatus($v['uuid'], count($e_id) > 1);
                    if ($result_report) {
                        if (!empty($v['next_promote_ids'])) {
                            $ne_id = explode(',', $v['next_promote_ids']);
                            $promote_ids = [];
                            foreach ($ne_id as $e_v) {
                                $promote_ids[] = (int)$e_v;
                            }
                            $exec_promote_ids = array_slice($promote_ids, 0, 10);
                            $next_promote_ids = array_slice($promote_ids, 10);
                            PromoteCampaignExec::add([
                                'platform_type' => $v['platform_type'],
                                'shop_id' => $v['shop_id'],
                                'promote_ids' => implode(',', $exec_promote_ids),
                                'next_promote_ids' => !empty($next_promote_ids) ? implode(',', $next_promote_ids) : '',
                                'start_day' => $v['start_day'],
                                'end_day' => $v['end_day'],
                                'uuid' => '',
                                'status' => 0,
                                'plan_time' => time()
                            ]);
                        }
                        $v->status = 1;
                        $v->save();
                    }
                    echo date('Y-m-d H:i:s') . ' shop_id:' . $v['shop_id'] . ' UUID:' . $v['uuid'] . ' status:' . $v->status . "\n";
                    continue;
                }
            }
            if((!empty($result['state']) && $result['state'] == 'ERROR') || empty($v['uuid'])) {
                $promote_ids = [];
                foreach ($e_id as $e_v){
                    $promote_ids[] = (int)$e_v;
                }
                $campaign_result = $api_service->getCampaignStatistics($promote_ids,$v['start_day'],$v['end_day']);
                if (!empty($campaign_result) && !empty($campaign_result['UUID'])) {
                    $v->uuid = $campaign_result['UUID'];
                }
            }
            $v->plan_time = time() + 10 * 60;
            $v->save();
            echo date('Y-m-d H:i:s').' shop_id:'.$v['shop_id'] . ' UUID:' . $v['uuid'] . ' status:'.$v->status ."\n";
        }
    }

    /**
     * 更新订单物流
     * @return void
     */
    public function actionUpdateWl()
    {
        $order_lists = Order::find()->where(['source' => Base::PLATFORM_OZON])->andWhere(['>', 'add_time', strtotime('2022-11-04')])->all();
        foreach ($order_lists as $v) {
            $shop = Shop::findOne($v['shop_id']);
            $api_service = FApiService::factory($shop);
            $order_info = $api_service->getOrderInfo($v['relation_no']);
            if(!empty($order_info['tpl_integration_type']) && $order_info['tpl_integration_type'] == 'aggregator') {
                $logistics_channels_name = $order_info['delivery_method']['name'];
                $logistics_channels_name = explode('  ',$logistics_channels_name);
                $v->logistics_channels_name = current($logistics_channels_name);
                $v->save();
            }
        }
    }

    /**
     * 统计商品数据
     * @return void
     */
    public function actionStatisticsGoodsCount($is_yesterday = 0)
    {
        $start_time = strtotime(date('Y-m-d'));
        if ($is_yesterday) {
            $start_time = $start_time - 86400;
        }
        $end_time = $start_time + 86400;
        $goods_count = GoodsShop::find()
            ->select('shop_id,status,admin_id,count(*) as cut')
            ->where(['platform_type' => Base::PLATFORM_OZON])
            ->andWhere(['>=', 'update_time', $start_time])
            ->andWhere(['<', 'update_time', $end_time])
            ->groupBy('shop_id,status,admin_id')
            ->asArray()->all();

        $admin_cut = [];
        foreach ($goods_count as $v) {
            if(empty($v['admin_id']) || empty($v['shop_id'])) {
                continue;
            }
            if ($v['status'] == GoodsShop::STATUS_SUCCESS) {
                $val = 'o_goods_success';
            }
            if ($v['status'] == GoodsShop::STATUS_FAIL) {
                $val = 'o_goods_fail';
            }
            if ($v['status'] == GoodsShop::STATUS_UNDER_REVIEW) {
                $val = 'o_goods_audit';
            }
            if ($v['status'] == GoodsShop::STATUS_UPLOADING) {
                $val = 'o_goods_upload';
            }
            if (empty($val)) {
                continue;
            }
            $admin_cut[$v['admin_id']][$v['shop_id']][$val] = $v['cut'];
        }

        $shop_ids = ArrayHelper::getColumn($goods_count, 'shop_id');
        $shop_ids = array_unique($shop_ids);
        $order_cut = Order::find()
            ->select('shop_id,count(*) as cut')
            ->where(['shop_id' => $shop_ids])
            ->andWhere(['>=', 'date', $start_time])
            ->andWhere(['<', 'date', $end_time])
            ->groupBy('shop_id')->asArray()->all();
        $order_cut = ArrayHelper::map($order_cut, 'shop_id', 'cut');
        foreach ($admin_cut as $admin_k => $admin_v) {
            foreach ($admin_v as $shop_k => $shop_v) {
                $data = ['admin_id' => $admin_k, 'shop_id' => $shop_k, 'date_time' => $start_time];
                $report = ReportUserCount::find()->where($data)->one();
                $data['o_goods_success'] = empty($shop_v['o_goods_success']) ? 0 : $shop_v['o_goods_success'];
                $data['o_goods_fail'] = empty($shop_v['o_goods_fail']) ? 0 : $shop_v['o_goods_fail'];
                $data['o_goods_audit'] = empty($shop_v['o_goods_audit']) ? 0 : $shop_v['o_goods_audit'];
                $data['o_goods_upload'] = empty($shop_v['o_goods_upload']) ? 0 : $shop_v['o_goods_upload'];
                $data['order_count'] = empty($order_cut[$shop_k]) ? 0 : $order_cut[$shop_k];
                if (empty($report)) {
                    $report = new ReportUserCount();
                }
                $report->load($data,'');
                $report->save();
            }
        }
    }

    public function actionReGoodsError($limit = 0)
    {
        while (true) {
            echo $limit . "\n";
            $limit++;
            $goods_shop_expand_lists = GoodsShopExpand::find()->offset(1000 * ($limit - 1))->limit(1000)->all();
            if (empty($goods_shop_expand_lists)) {
                break;
            }
            foreach ($goods_shop_expand_lists as $goods_shop_v) {
                (new GoodsErrorSolutionService())->addError(Base::PLATFORM_OZON,$goods_shop_v['goods_shop_id'], json_decode($goods_shop_v['error_msg'], true));
                echo '#' . $goods_shop_v['cgoods_no'] . "\n";
            }
        }
    }

    /**
     * 上架状态
     * @param $shop_id
     * @return void
     * @throws \yii\base\Exception
     */
    public function actionOnShelfStatus($shop_id)
    {
        $where = [
            'platform_type' => Base::PLATFORM_OZON,
            'warehouse_id' => 0,
        ];
        if (!empty($shop_id)) {
            $where['id'] = $shop_id;
        }
        $shop_lists = Shop::find()->where($where)->asArray()->all();
        foreach ($shop_lists as $shop) {
            if ($shop['platform_type'] != Base::PLATFORM_OZON) {
                return;
            }
            echo 'shop:' . $shop['id'] . ' ' . $shop['name'] . "\n";
            $last_id = null;
            $page = 1;
            $i = 0;
            $api_service = FApiService::factory($shop);
            while (true) {
                echo $page . "\n";
                echo $last_id . "\n";
                try {
                    $lists = $api_service->getGoodsList('VISIBLE', 1000, $last_id);
                } catch (\Exception $e) {
                    echo $e->getMessage() . "\n";
                    $lists = null;
                }
                if (empty($lists) || empty($lists['items'])) {
                    break;
                }
                $last_id = $lists['last_id'];
                $lists = $lists['items'];
                $platform_goods_ids = [];
                foreach ($lists as $v) {
                    if ($v['is_fbs_visible']) {
                        $platform_goods_ids[] = $v['product_id'];
                        $i++;
                        echo $v['product_id'] . "\n";
                    }
                }
                GoodsShop::updateAll(['status' => GoodsShop::STATUS_SUCCESS], ['shop_id' => $shop['id'], 'platform_goods_id' => $platform_goods_ids]);
                $page += 1;
            }
            echo '共成功：' . $i . '条' . "\n";
        }
    }

    /**
     * 下架状态
     * @param $shop_id
     * @return void
     * @throws \yii\base\Exception
     */
    public function actionOffShelfStatus($shop_id)
    {
        $where = [
            'platform_type'=>Base::PLATFORM_OZON,
            'warehouse_id' => 0,
        ];
        if(!empty($shop_id)){
            $where['id'] = $shop_id;
        }
        $shop_lists = Shop::find()->where($where)->asArray()->all();
        foreach ($shop_lists as $shop) {
            if ($shop['platform_type'] != Base::PLATFORM_OZON) {
                return;
            }
            echo 'shop:'.$shop['id'].' '.$shop['name']."\n";
            $last_id = null;
            $page = 1;
            $i = 0;
            $api_service = FApiService::factory($shop);
            while (true) {
                echo $page . "\n";
                echo $last_id . "\n";
                try {
                    $lists = $api_service->getGoodsList('VISIBLE', 1000, $last_id);
                } catch (\Exception $e){
                    echo $e->getMessage()."\n";
                    $lists = null;
                }
                if (empty($lists) || empty($lists['items'])) {
                    break;
                }
                $last_id = $lists['last_id'];
                $lists = $lists['items'];
                $platform_goods_ids = [];
                foreach ($lists as $v) {
                    if (!$v['is_fbs_visible']) {
                        $platform_goods_ids[] = $v['product_id'];
                        $i++;
                        echo $v['product_id']."\n";
                    }
                }
                GoodsShop::updateAll(['status'=>GoodsShop::STATUS_OFF_SHELF],['status'=>GoodsShop::STATUS_SUCCESS,'platform_goods_id'=>$platform_goods_ids]);
                $page += 1;
            }
            echo '共成功：'.$i.'条'."\n";

            echo "开始执行成功的检测\n";
            $i = 0;
            $ids = GoodsShop::find()->where(['shop_id'=>$shop['id'],'status'=>GoodsShop::STATUS_SUCCESS])->select('platform_goods_id')->column();
            $page_num = 1000;
            do {
                $top_1000 = array_slice($ids, 0, $page_num);
                $ids = array_slice($ids, $page_num);

                try {
                    $lists = $api_service->getGoodsList('ALL', 1000, null,$top_1000);
                } catch (\Exception $e){
                    echo $e->getMessage()."\n";
                    $lists = null;
                }
                if (empty($lists) || empty($lists['items'])) {
                    break;
                }
                $lists = $lists['items'];
                $platform_goods_ids = [];
                foreach ($lists as $v) {
                    if (!$v['is_fbs_visible']) {
                        $platform_goods_ids[] = $v['product_id'];
                        $i++;
                        echo $v['product_id']."\n";
                    }
                }
                GoodsShop::updateAll(['status'=>GoodsShop::STATUS_OFF_SHELF],['status'=>GoodsShop::STATUS_SUCCESS,'platform_goods_id'=>$platform_goods_ids]);
            } while (count($ids) > 0);
            echo '共成功：'.$i.'条'."\n";
        }
    }



    /**
     * 删除归档商品
     * @param $shop_id
     * @return void
     * @throws \yii\base\Exception
     */
    public function actionDeleteArchive($shop_id)
    {
        $where = [
            'platform_type'=>Base::PLATFORM_OZON
        ];
        if(!empty($shop_id)){
            $where['id'] = $shop_id;
        }
        $shop_lists = Shop::find()->where($where)->asArray()->all();
        foreach ($shop_lists as $shop) {
            if ($shop['platform_type'] != Base::PLATFORM_OZON) {
                return;
            }
            echo 'shop:'.$shop['id'].' '.$shop['name']."\n";
            $last_id = null;
            $page = 1;
            $i = 0;
            while (true) {
                echo $page . "\n";
                $api_service = FApiService::factory($shop);
                echo $last_id . "\n";
                try {
                    $lists = $api_service->getGoodsList('ARCHIVED', 1000, $last_id);
                }catch (\Exception $e){
                    echo $e->getMessage()."\n";
                    $lists = null;
                }
                if (empty($lists) || empty($lists['items'])) {
                    break;
                }
                $last_id = $lists['last_id'];
                $lists = $lists['items'];
                $offer_ids = [];
                foreach ($lists as $v) {
                    $offer_ids[] = $v['offer_id'];
                }

                $result = $api_service->deleteArchive($offer_ids);
                foreach ($result as $v) {
                    if ($v['is_deleted']) {
                        echo $v['offer_id'] . "\n";
                        $i++;
                    }
                }
                //var_dump($result);
                //echo $goods_shop['goods_no']."\n";
                $page += 1;
            }
            echo '共成功：'.$i.'条'."\n";
        }
    }

    /**
     * 移到归档
     * @param $shop_id
     * @return void
     * @throws \yii\base\Exception
     */
    public function actionArchive($shop_id)
    {
        $where = [
            'platform_type'=>Base::PLATFORM_OZON
        ];
        if(!empty($shop_id)){
            $where['id'] = $shop_id;
        }
        $shop_lists = Shop::find()->where($where)->asArray()->all();
        foreach ($shop_lists as $shop) {
            if ($shop['platform_type'] != Base::PLATFORM_OZON) {
                return;
            }
            echo 'shop:'.$shop['id'].' '.$shop['name']."\n";
            $last_id = null;
            $page = 1;
            $i = 0;
            while (true) {
                echo $page . "\n";
                $api_service = FApiService::factory($shop);
                echo $last_id . "\n";
                try {
                    $lists = $api_service->getGoodsList('VISIBLE', 1000, $last_id);
                }catch (\Exception $e){
                    echo $e->getMessage()."\n";
                    $lists = null;
                }
                if (empty($lists) || empty($lists['items'])) {
                    break;
                }
                $last_id = $lists['last_id'];
                $lists = $lists['items'];
                $product_ids = [];
                foreach ($lists as $v) {
                    if (!$v['is_fbs_visible']) {
                        $product_ids[] = $v['product_id'];
                        $i++;
                        echo $v['product_id']."\n";
                    }
                }
                if(!empty($product_ids)) {
                    $page_num = 100;
                    do {
                        $top_100 = array_slice($product_ids, 0, $page_num);
                        $product_ids = array_slice($product_ids, $page_num);
                        $result = $api_service->archive($top_100);
                    } while (count($product_ids) > 0);
                }
                $page += 1;
            }
            echo '共成功：'.$i.'条'."\n";
        }
    }



    /**
     * 修复ozon订单
     * @return void
     * @throws \yii\base\Exception
     */
    public function actionReOrder()
    {
        $order_lists = Order::find()->where(['source' => Base::PLATFORM_OZON])
            ->andWhere(['<=', 'exchange_rate', 0])
            ->andWhere(['>', 'add_time', strtotime('2022-09-01')])->all();
        foreach ($order_lists as $order) {
            $shop = Shop::findOne($order['shop_id']);
            $api_service = FApiService::factory($shop);
            $order_info = $api_service->getOrderInfo($order['relation_no']);
            $currency_code = '';
            foreach ($order_info['products'] as $v) {
                $currency_code = $v['currency_code'];
            }
            $order['currency'] = $currency_code;
            $order['exchange_rate'] = ExchangeRateService::getValue($order['currency']);
            if($order['currency'] == 'USD') {//美国重新计算利润
                $order['order_profit'] = OrderService::calculateProfitPrice($order);
            }
            $order->save();
            echo $order['order_id']."\n";
        }
    }

    /**
     * 重新发布商品
     * @return void
     * @throws \yii\base\Exception
     */
    public function actionReRelease()
    {
        $goods_shpo_expand = GoodsShopExpand::find()->where(['shop_id' => 224])
            ->andWhere(['like', 'error_msg', 'Мы смогли скачать не все картинки. Попробуйте загрузить их ещё раз'])->all();
        foreach ($goods_shpo_expand as $goods_shop_e) {
            $goods_shop = GoodsShop::find()->where(['id' => $goods_shop_e['goods_shop_id']])->one();
            if (in_array($goods_shop['status'], [GoodsShop::STATUS_SUCCESS, GoodsShop::STATUS_FAIL])) {
                (new GoodsShopService())->release($goods_shop, true);
                echo $goods_shop['shop_id'].','.$goods_shop['cgoods_no']."\n";
            }
        }
    }

    /**
     * 获取货币
     * @param $shop_id
     * @return void
     * @throws \yii\base\Exception
     */
    public function actionGetShopCurrency($shop_id = null)
    {
        $where = ['platform_type' => Base::PLATFORM_OZON];
        if (!empty($shop_id)) {
            $where['id'] = $shop_id;
        }
        $shop_lists = Shop::find()->where($where)->all();
        foreach ($shop_lists as $shop) {
            try {
                $sku_no = GoodsShop::find()->where(['shop_id' => $shop['id'], 'status' => GoodsShop::STATUS_SUCCESS])->select('platform_sku_no')->limit(1)->scalar();
                if (empty($sku_no)) {
                    echo $shop['id'] . ',' . $shop['name'] . ',不存在成功商品' . "\n";
                    continue;
                }
                $api_service = FApiService::factory($shop);
                $result = $api_service->getProductsToAsin($sku_no);
                echo $shop['id'] . ',' . $shop['name'] . ',' . $result['currency_code'] . "\n";
            } catch (\Exception $e) {
                echo $shop['id'] . ',' . $shop['name'] . ',出错' . $e->getMessage() . "\n";
            }
        }
    }

    public function actionReUpload1($limit = 0)
    {
        while (true) {
            echo $limit . "\n";
            $limit++;
            //reason_id: 193
            //reason_id: 832
            $goods_shop_expand_lists = GoodsShopExpand::find()
                ->andWhere(['like','error_msg','SPU_ALREADY_EXISTS'])->limit(1000)->all();
            if (empty($goods_shop_expand_lists)) {
                break;
            }
            foreach ($goods_shop_expand_lists as $goods_shop_v) {
                $goods_shop_expand['error_msg'] = '';
                $goods_shop_expand->save();
                $goods_shop = GoodsShop::find()->where(['id' => $goods_shop_v['goods_shop_id']])->one();
                if (in_array($goods_shop['status'], [GoodsShop::STATUS_SUCCESS, GoodsShop::STATUS_DELETE,GoodsShop::STATUS_NOT_TRANSLATED])) {
                    continue;
                }
                $goods_shop->status = GoodsShop::STATUS_NOT_UPLOADED;
                $goods_shop->save();
                (new GoodsShopService())->updateDefaultGoodsExpand($goods_shop, [1]);
                echo '#' . $goods_shop['goods_no'] . "\n";
            }
        }
    }

    public function actionReUpload($shop_id,$limit = 0)
    {
        $shop = Shop::findOne($shop_id);
        while (true) {
            echo $limit . "\n";
            $limit++;
            $goods_shop = GoodsShop::find()->where(['platform_type' => Base::PLATFORM_OZON,
                'shop_id' => $shop_id,'status'=>[GoodsShop::STATUS_SUCCESS]])
                ->indexBy('platform_sku_no')->limit(100)->all();
            if (empty($goods_shop)) {
                break;
            }
            $sku_nos = ArrayHelper::getColumn($goods_shop,'platform_sku_no');

            try {
                $api_service = FApiService::factory($shop);
                $sku_nos = array_values($sku_nos);
                $result = $api_service->getProductsToAsinList($sku_nos);
                if (empty($result)) {
                    echo json_encode($sku_nos) . ',找不到产品' . "\n";
                    CommonUtil::logs(json_encode($sku_nos) . ',找不到产品', 'pull_goods_error');
                    continue;
                }
            } catch (\Exception $e) {
                echo json_encode($sku_nos) . ',' . $e->getMessage() . "\n";
                CommonUtil::logs(json_encode($sku_nos). ',' . $e->getMessage(), 'pull_goods_error');
                exit;
            }

            $offer_ids = [];
            $goods_lists = [];
            foreach ($result as $v) {
                if(empty($goods_shop[$v['offer_id']])){
                    CommonUtil::logs($v['offer_id'] . ',错误', 'pull_goods_error');
                    continue;
                }

                $goods_shop_v = $goods_shop[$v['offer_id']];
                $sources = $v['sources'];
                if(empty($sources)){
                    $goods_shop_v->status = 11;
                    $goods_shop_v->save();
                    continue;
                }

                $fbo = false;
                $fbs = false;
                foreach ($sources as $fo_v) {
                    if ($fo_v['source'] == 'fbs') {
                        $fbs = $fo_v['is_enabled'];
                    }
                    if ($fo_v['source'] == 'fbo') {
                        $fbo = $fo_v['is_enabled'];
                    }
                }

                if($fbo && !$fbs) {
                    $offer_ids[] = $v['id'];
                    $goods_lists[] = $goods_shop_v;
                } else {
                    $goods_shop_v->status = 11;
                    $goods_shop_v->save();
                }
                echo $v['offer_id'] . "\n";
            }

            if (empty($offer_ids)){
                continue;
            }
            //删除重新认领
            $api_service->archive($offer_ids);
            foreach ($goods_lists as $goods_shop_v) {
                $id_server = new PlatformGoodsSkuIdService();
                $platform_sku_no = GoodsShop::ID_PREFIX . $id_server->getNewId();
                $goods_shop_v->platform_sku_no = $platform_sku_no;
                $goods_shop_v->platform_goods_id = '';
                $goods_shop_v->platform_goods_opc = '';
                $goods_shop_v->platform_goods_exp_id = '';
                $goods_shop_v->status = GoodsShop::STATUS_NOT_UPLOADED;
                /*while (true) {
                    $ean = CommonUtil::GenerateEan13();
                    $exist_ean = GoodsShop::find()->where(['ean' => $ean, 'platform_type' => Base::PLATFORM_OZON])->exists();
                    if (!$exist_ean) {
                        break;
                    }
                }
                $goods_shop_v->ean = $ean;*/
                $goods_shop_v->save();
                (new GoodsShopService())->updateDefaultGoodsExpand($goods_shop_v, [1]);
                echo '#'.$goods_shop_v['goods_no']."\n";
            }

        }
    }

    /**
     * 添加店铺仓库
     * @param $shop_id
     * @return void
     * @throws \yii\base\Exception
     */
    public function actionAddShopWarehouse($shop_id = null)
    {
        $where = ['platform_type' => Base::PLATFORM_OZON, 'status' => Shop::STATUS_VALID];
        if (!empty($shop_id)) {
            $where['id'] = $shop_id;
        }
        $shop_list = Shop::find()->where($where)->all();
        foreach ($shop_list as $shop) {
            try {
                $api_service = FApiService::factory($shop);
                $warehouse_lists = $api_service->getWarehouse();
                $warehouse_id = '';
                foreach ($warehouse_lists as $warehouse_v) {
                    $warehouse_id = (string)$warehouse_v['warehouse_id'];
                    $where = [];
                    $where['platform_type'] = Base::PLATFORM_OZON;
                    $where['shop_id'] = $shop['id'];
                    $where['type'] = PlatformShopConfig::TYPE_WAREHOUSE;
                    $where['type_id'] = $warehouse_id;
                    $platform_config = PlatformShopConfig::find()->where($where)->one();
                    if (!empty($platform_config)) {
                        $platform_config['type_val'] = $warehouse_v['name'];
                        $result = $platform_config->save();
                    } else {
                        $where['type_val'] = $warehouse_v['name'];
                        $result = PlatformShopConfig::add($where);
                    }
                }
                echo $shop['id'] . "," . $shop['name'] . ",执行成功\n";
            } catch (\Exception $e) {
                echo $shop['id'] . "," . $shop['name'] . ",执行错误:".$e->getMessage()."\n";
            }
        }
        ShopService::clearShopWarehouseCache(Base::PLATFORM_OZON);
    }

    /**
     * 添加店铺仓库
     * @param $shop_id
     * @return void
     * @throws \yii\base\Exception
     */
    public function actionInitStock($shop_id = null)
    {
        $where = ['platform_type' => Base::PLATFORM_OZON, 'status' => Shop::STATUS_VALID];
        if (!empty($shop_id)) {
            $where['id'] = $shop_id;
        }
        $shop_list = Shop::find()->where($where)->all();
        foreach ($shop_list as $shop) {
            try {
                $api_service = FApiService::factory($shop);
                $result = $api_service->initStock();
                echo $shop['id'] . "," . $shop['name'] . "," . $result . "\n";
            } catch (\Exception $e) {
                echo $shop['id'] . "," . $shop['name'] . ",执行错误:".$e->getMessage()."\n";
            }
        }
    }

    /**
     * 执行亏本价格库存
     * @return void
     */
    public function actionMPrice()
    {
        foreach (OzonPlatform::$m_price_lists as $p_v) {
            $goods_shop = GoodsShop::find()->where([
                'shop_id'=>$p_v[0],
                'cgoods_no'=>$p_v[1],
            ])->one();
            if(empty($goods_shop)){
                continue;
            }
            GoodsEventService::addEvent($goods_shop, GoodsEvent::EVENT_TYPE_UPDATE_STOCK);
            echo $p_v[0].','.$p_v[1]."\n";
        }
    }

    /**
     * 添加默认标题
     */
    public function actionAddDefaultGoodsTitle($shop_id,$limit = 0)
    {
        while (true) {
            $limit++;
            /*$goods_shop = GoodsShop::find()->alias('gs')->leftJoin(GoodsShopExpand::tableName().' ge','ge.goods_shop_id = gs.id')
                ->where(['gs.platform_type' => Base::PLATFORM_OZON,'ge.goods_content'=>''])
                ->offset(10000 * ($limit - 1))->limit(10000)->all();*/
            $goods_shop = GoodsShop::find()
                ->where(['platform_type' => Base::PLATFORM_OZON,'shop_id'=>$shop_id,'status'=>[GoodsShop::STATUS_NOT_UPLOADED]])
                ->offset(10000 * ($limit - 1))->limit(10000)->all();
            if(empty($goods_shop)){
                break;
            }
            foreach ($goods_shop as $shop_v) {
                (new GoodsShopService())->updateDefaultGoodsExpand($shop_v,[3,4]);
                echo $limit .','.$shop_v['id']. "\n";
            }
            echo $limit . "\n";
            exit();
        }
    }


    /**
     * 同步商品属性
     */
    public function actionSyncAttr($shop_id,$limit =0)
    {
        $shop = Shop::findOne($shop_id);
        while (true) {
            echo $limit . "\n";
            $limit++;
            $goods_shop = GoodsShop::find()->where(['platform_type' => Base::PLATFORM_OZON,
                'shop_id' => $shop_id,'status'=>[GoodsShop::STATUS_FAIL,GoodsShop::STATUS_SUCCESS]])
                ->indexBy('platform_sku_no')->offset(100 * ($limit - 1))->limit(100)->all();
            if (empty($goods_shop)) {
                break;
            }
            $sku_nos = ArrayHelper::getColumn($goods_shop,'platform_sku_no');

            try {
                $api_service = FApiService::factory($shop);
                $sku_nos = array_values($sku_nos);
                $result = $api_service->getProductsAttributesToAsin($sku_nos);
                if (empty($result)) {
                    echo json_encode($sku_nos) . ',找不到产品' . "\n";
                    CommonUtil::logs(json_encode($sku_nos) . ',找不到产品', 'pull_goods_error');
                    continue;
                }
            } catch (\Exception $e) {
                echo json_encode($sku_nos) . ',' . $e->getMessage() . "\n";
                CommonUtil::logs(json_encode($sku_nos). ',' . $e->getMessage(), 'pull_goods_error');
                continue;
                exit;
            }

            foreach ($result as $v) {
                if(empty($goods_shop[$v['offer_id']])){
                    CommonUtil::logs($v['offer_id'] . ',错误', 'pull_goods_error');
                    continue;
                }
                $goods_shop_info = $goods_shop[$v['offer_id']];
                $goods_shop_expand = GoodsShopExpand::find()->where(['goods_shop_id' => $goods_shop_info['id']])->one();
                /*$goods_shop_expand->size_mm = $v['depth'].'x'.$v['width'].'x'.$v['height'];
                $goods_shop_expand->weight_g = $v['weight'];
                $goods_shop_expand->o_category_id = (string)$v['category_id'];
                $values = [];
                if(!empty($v['attributes'])) {
                    foreach ($v['attributes'] as $attributes) {
                        if(in_array($attributes['attribute_id'],[4194,4191,4180])) {
                            continue;
                        }
                        $attr_info = [];
                        $attr_info['id'] = $attributes['attribute_id'];
                        $attr_val = current($attributes['values']);
                        if (!empty($attr_val['dictionary_value_id'])) {
                            $attr_info['val'] = $attr_val['dictionary_value_id'];
                            $attr_info['show'] = $attr_val['value'];
                        } else {
                            if($attr_val['value'] == 'false') {
                                continue;
                            }
                            $attr_info['val'] = $attr_val['value'];
                        }
                        $values[] = $attr_info;
                    }
                }
                $goods_shop_expand->attribute_value = json_encode($values,JSON_UNESCAPED_UNICODE);*/
                $goods_content = '';
                if(!empty($v['attributes'])) {
                    foreach ($v['attributes'] as $attributes) {
                        if($attributes['attribute_id'] == 4191){
                            $goods_content = current($attributes['values'])['value'];
                        }
                    }
                }
                $goods_shop_expand->goods_title = $v['name'];
                $goods_shop_expand->goods_content = CommonUtil::dealContent($goods_content);
                $goods_shop_expand->save();
                echo $v['offer_id'] . "\n";
            }
        }
    }

    /**
     * 排除ozon标题
     * @param $shop_id
     * @throws \yii\base\Exception
     */
    public function actionDelGoods($shop_id,$page = 1)
    {
        $shop = Shop::findOne($shop_id);
        if ($shop['platform_type'] != Base::PLATFORM_OZON) {
            return;
        }

        while (true) {
            echo $page . "\n";
            $api_service = FApiService::factory($shop);
            $result = $api_service->getGoodsList($page, 'ARCHIVED');
            if (empty($result) || empty($result['items'])) {
                break;
            }
            $result = $result['items'];
            foreach ($result as $v) {
                $goods_shop = GoodsShop::find()->where(['shop_id' => $shop_id, 'platform_sku_no' => $v['offer_id']])->one();
                if (empty($goods_shop)) {
                    $cgoods_no = GoodsChild::find()->where(['sku_no' => $v['offer_id']])->select('cgoods_no')->scalar();
                    if (empty($cgoods_no)) {
                        echo $v['offer_id'].' 商品不存在'."\n";
                        continue;
                    }

                    $goods_shop = GoodsShop::find()->where(['shop_id' => $shop_id, 'cgoods_no' => $cgoods_no])->one();
                }

                if(empty($goods_shop)){
                    echo $v['offer_id'].' 商品不存在'."\n";
                    continue;
                }
                $goods_shop->status = GoodsShop::STATUS_DELETE;
                $goods_shop->save();

                echo $goods_shop['goods_no']."\n";
            }
            $page += 1;
        }
    }

    /**
     * 对比
     * @param $shop_id
     * @throws \yii\base\Exception
     */
    public function actionDiffGoods($shop_id,$page = 1)
    {
        $shop = Shop::findOne($shop_id);
        if ($shop['platform_type'] != Base::PLATFORM_OZON) {
            return;
        }
        $api_service = FApiService::factory($shop);
        $result = $api_service->getGoodsList($page, 'VISIBLE');
        $result = $result['items'];
        $offer_ids = ArrayHelper::getColumn($result,'offer_id');

        $goods_shop = GoodsShop::find()->where(['platform_type' => Base::PLATFORM_OZON, 'shop_id' => $shop_id, 'status' => GoodsShop::STATUS_SUCCESS])
            ->offset(1000 * ($page - 1))->limit(1000)->all();
        $platform_sku_no = ArrayHelper::getColumn($goods_shop,'platform_sku_no');

        $diff = array_diff($platform_sku_no,$offer_ids);
        $diff1 = array_diff($offer_ids,$platform_sku_no);
        var_dump($diff);
        var_dump($diff1);
    }

    /**
     * @param $shop_id
     * @return void
     * @throws \yii\base\Exception
     */
    public function actionSyncStatus($shop_id)
    {
        $shop = Shop::findOne($shop_id);
        /*$un_sku_no = [];
        $page = 1;
        while (true) {
            $api_service = FApiService::factory($shop);
            $result = $api_service->getGoodsList($page, 'ARCHIVED');
            if (empty($result) || empty($result['items'])) {
                break;
            }
            $page ++;
            if (!empty($result) && !empty($result['items'])) {
                $result = $result['items'];
                foreach ($result as $v) {
                    $un_sku_no[] = $v['offer_id'];
                }
            }
        }*/
        $limit = 0;
        while (true) {
            //$limit++;
            $goods_shop = GoodsShop::find()->where(['platform_type' => Base::PLATFORM_OZON, 'shop_id' => $shop_id, 'status' => GoodsShop::STATUS_NOT_UPLOADED])
                ->offset(1000 * ($limit - 1))->limit(1000)->all();
            if (empty($goods_shop)) {
                break;
            }
            foreach ($goods_shop as $shop_v) {
                try {
                    $api_service = FApiService::factory($shop);
                    $api_service->syncGoods($shop_v);
                    echo $shop_v['cgoods_no'] . "\n";
                } catch (\Exception $e) {
                    echo '#' . $shop_v['cgoods_no'] . ',' . $e->getMessage() . "\n";
                }
            }
        }
    }

    /**
     * 更新ozon
     * @param $shop_id
     * @throws \yii\base\Exception
     */
    public function actionUpdateGoodsNoOzon($shop_id){
        $shop = Shop::findOne($shop_id);
        if ($shop['platform_type'] != Base::PLATFORM_OZON) {
            return;
        }

        $page = 1;
        while (true) {
            echo $page ."\n";
            $api_service = FApiService::factory($shop);
            $result = $api_service->getGoodsList($page);
            if (empty($result) || empty($result['items'])) {
                break;
            }
            $result = $result['items'];
            foreach ($result as $v) {
                $goods_no = Goods::find()->where(['sku_no' => $v['offer_id']])->select('goods_no')->scalar();
                if (empty($goods_no)) {
                    CommonUtil::logs('商品不存在 shop_id:' . $shop_id . ' result:' . json_encode($v), 'ozon_goods');
                    continue;
                }
                $goods_shop = GoodsShop::find()->where(['shop_id' => $shop_id, 'goods_no' => $goods_no])->one();
                if (empty($goods_shop)) {
                    CommonUtil::logs('店铺商品不存在 shop_id:' . $shop_id . ' result:' . json_encode($v), 'ozon_goods');
                    continue;
                }
                $goods_shop->platform_goods_id = (string)$v['product_id'];
                $goods_shop->save();
                echo $goods_no ."\n";
            }
            $page += 1;
        }
    }

    /**
     * 排除ozon标题
     * @param $shop_id
     * @throws \yii\base\Exception
     */
    public function actionDelTitleFormOzon($shop_id,$page = 1){
        $shop = Shop::findOne($shop_id);
        if ($shop['platform_type'] != Base::PLATFORM_OZON) {
            return;
        }

        while (true) {
            echo $page ."\n";
            $api_service = FApiService::factory($shop);
            $result = $api_service->getGoodsList($page,'ARCHIVED');
            if (empty($result) || empty($result['items'])) {
                break;
            }
            $result = $result['items'];
            foreach ($result as $v) {
                $goods_no = Goods::find()->where(['sku_no' => $v['offer_id']])->select('goods_no')->scalar();
                if (empty($goods_no)) {
                    CommonUtil::logs('商品不存在 shop_id:' . $shop_id . ' result:' . json_encode($v), 'ozon_goods');
                    continue;
                }
                $goods_shop = GoodsShop::find()->where(['shop_id' => $shop_id, 'goods_no' => $goods_no])->one();
                if (empty($goods_shop)) {
                    CommonUtil::logs('店铺商品不存在 shop_id:' . $shop_id . ' result:' . json_encode($v), 'ozon_goods');
                    continue;
                }

                $goods_ozon = GoodsOzon::find()->where(['goods_no'=>$goods_no])->one();
                if(empty($goods_ozon->goods_short_name)){
                    echo $goods_no .",已处理\n";
                    continue;
                }

                try {
                    $product = $api_service->getProductsToAsin($v['offer_id']);
                    if (empty($product) || empty($product['name'])) {
                        echo $v['offer_id'] . ',找不到产品' . "\n";
                        CommonUtil::logs($goods_no . ',找不到产品','pull_goods_error');
                        continue;
                    }
                    if($product['name'] == $goods_ozon['goods_short_name']) {
                        $goods_ozon->goods_short_name = '';
                        $goods_ozon->save();
                    }
                }catch (\Exception $e){
                    echo $goods_no . ','.$e->getMessage() . "\n";
                    CommonUtil::logs($goods_no . ','.$e->getMessage(),'pull_goods_error');
                    continue;
                }
                echo $goods_no ."\n";
            }
            $page += 1;
        }
    }

    /**
     * 从ozon拉取标题
     */
    public function actionPullTitleFromOzon($shop_id,$limit =0)
    {
        $shop = Shop::findOne($shop_id);
        if ($shop['platform_type'] != Base::PLATFORM_OZON) {
            return;
        }

        $un_sku_no = [];
        $page = 1;
        while (true) {
            $api_service = FApiService::factory($shop);
            $result = $api_service->getGoodsList($page, 'ARCHIVED');
            if (empty($result) || empty($result['items'])) {
                break;
            }
            $page ++;
            if (!empty($result) && !empty($result['items'])) {
                $result = $result['items'];
                foreach ($result as $v) {
                    $un_sku_no[] = $v['offer_id'];
                }
            }
        }

        while (true) {
            $limit++;
            $goods_shop = GoodsShop::find()->where(['platform_type' => Base::PLATFORM_OZON, 'shop_id' => $shop_id])
                ->offset(1000 * ($limit - 1))->limit(1000)->all();
            if(empty($goods_shop)){
                break;
            }
            foreach ($goods_shop as $shop_v) {
                if (empty($shop_v['platform_goods_id'])) {
                    continue;
                }
                $goods = Goods::find()->where(['goods_no' => $shop_v['goods_no']])->one();
                if(in_array($goods['sku_no'],$un_sku_no)) {
                    continue;
                }
                try {
                    $api_service = FApiService::factory($shop);
                    $result = $api_service->getProductsToAsin($goods['sku_no']);
                    if (empty($result) || empty($result['name'])) {
                        echo $goods['sku_no'] . ',找不到产品' . "\n";
                        CommonUtil::logs($goods['goods_no'] . ',找不到产品','pull_goods_error');
                        continue;
                    }
                }catch (\Exception $e){
                    echo $goods['goods_no'] . ','.$e->getMessage() . "\n";
                    CommonUtil::logs($goods['goods_no'] . ','.$e->getMessage(),'pull_goods_error');
                    continue;
                }

                $goods_ozon = GoodsOzon::find()->where(['goods_no'=>$goods['goods_no']])->one();
                $exist = false;
                if(empty($goods_ozon->goods_short_name) || strlen($goods_ozon->goods_short_name) > strlen($result['name'])){
                    $goods_ozon->goods_short_name = $result['name'];
                    $exist = true;
                }

                if(strlen($goods_ozon->goods_short_name) > 70){
                    $goods_ozon->goods_short_name = '';
                    $exist = true;
                }

                if($exist){
                    $goods_ozon->save();
                }
                echo $goods['sku_no']."\n";
            }
            echo $limit . "\n";
            exit();
        }
    }

    /**
     * 从ozon拉取类目属性
     */
    public function actionPullCategoryAttributeFromOzon($shop_id,$limit =0)
    {
        $shop = Shop::findOne($shop_id);
        if ($shop['platform_type'] != Base::PLATFORM_OZON) {
            return;
        }

        /*$un_sku_no = [];
        $page = 1;
        while (true) {
            $api_service = FApiService::factory($shop);
            $result = $api_service->getGoodsList($page, 'ARCHIVED');
            if (empty($result) || empty($result['items'])) {
                break;
            }
            $page++;
            if (!empty($result) && !empty($result['items'])) {
                $result = $result['items'];
                foreach ($result as $v) {
                    $un_sku_no[] = $v['offer_id'];
                }
            }
        }*/

        while (true) {
            echo $limit . "\n";
            $limit++;
            $goods_shop = GoodsShop::find()->where(['platform_type' => Base::PLATFORM_OZON, 'shop_id' => $shop_id])
                ->offset(100 * ($limit - 1))->limit(100)->all();
            if (empty($goods_shop)) {
                break;
            }

            $goods_nos = ArrayHelper::getColumn($goods_shop,'goods_no');
            $goods_lists = Goods::find()->where(['goods_no' => $goods_nos])->indexBy('sku_no')->asArray()->all();
            $sku_nos = ArrayHelper::getColumn($goods_lists,'sku_no');

            try {
                $api_service = FApiService::factory($shop);
                $sku_nos = array_values($sku_nos);
                $result = $api_service->getProductsAttributesToAsin($sku_nos);
                if (empty($result)) {
                    echo json_encode($sku_nos) . ',找不到产品' . "\n";
                    CommonUtil::logs(json_encode($sku_nos) . ',找不到产品', 'pull_goods_error');
                    continue;
                }
            } catch (\Exception $e) {
                echo json_encode($goods_nos) . ',' . $e->getMessage() . "\n";
                CommonUtil::logs(json_encode($goods_nos). ',' . $e->getMessage(), 'pull_goods_error');
                continue;
                exit;
            }

            foreach ($result as $v) {
                $v['category_id'] = (string)$v['category_id'];
                if(empty($goods_lists[$v['offer_id']])){
                    CommonUtil::logs($v['offer_id'] . ',错误', 'pull_goods_error');
                    continue;
                }
                $goods = $goods_lists[$v['offer_id']];
                $goods_ozon = GoodsOzon::find()->where(['goods_no' => $goods['goods_no']])->one();
                $goods_ozon->o_category_name = $v['category_id'];
                if (empty($goods_ozon->goods_short_name) || strlen($goods_ozon->goods_short_name) > strlen($v['name'])) {
                    $goods_ozon->goods_short_name = $v['name'];
                }
                $goods_ozon->save();

                echo $goods['sku_no'] . "\n";
                if(!empty($v['attributes'])) {
                    $exist = PlatformCategoryAttribute::find()->where(['platform_type'=>Base::PLATFORM_OZON,'category_id'=>$v['category_id']])->exists();
                    if($exist){
                        continue;
                    }
                    foreach ($v['attributes'] as $attributes) {
                        if(in_array($attributes['attribute_id'],[85,4194,4191,10096,9048,9024,4180,9790,11794,10015,10100])) {
                            continue;
                        }
                        $values = [];
                        foreach ($attributes['values']as $val_v){
                            $attr_info = [];
                            if (!empty($val_v['dictionary_value_id'])){
                                $attr_info['dictionary_value_id'] = $val_v['dictionary_value_id'];
                            }
                            $attr_info['value'] = $val_v['value'];
                            $values[] = $attr_info;
                        }

                        $attr_data = [
                            'platform_type'=>Base::PLATFORM_OZON,
                            'category_id'=>$v['category_id'],
                            'attribute_id' => (string)$attributes['attribute_id'],
                            'attribute_name' => '',
                            'attribute_value' => json_encode($values,JSON_UNESCAPED_UNICODE),
                        ];
                        PlatformCategoryAttribute::add($attr_data);
                        echo 'category:'.$v['category_id'] ."\n";
                    }
                }

            }
        }
    }

    /**
     * 获取类别
     */
    public function actionGetCategory()
    {
        $platform_type = Base::PLATFORM_OZON;
        $sp = ' / ';
        $shop = Shop::find()->where(['platform_type' => $platform_type])->asArray()->one();
        $api_service = FApiService::factory($shop);
        $result = $api_service->getCategory();
        //echo CommonUtil::jsonFormat($result);
        //exit();
        foreach ($result as $v1) {
            $name1 = empty($v1['title']) ? '' : $v1['title'];
            $code1 = $v1['category_id'];
            $pl_cate = PlatformCategory::find()->where(['id' => $code1, 'platform_type' => $platform_type])->one();
            if (empty($pl_cate)) {
                $pl_cate = new PlatformCategory();
                $pl_cate->platform_type = $platform_type;
                $pl_cate->id = (string)$code1;
                $pl_cate->parent_id = (string)0;
                $pl_cate->name = $name1;
                $pl_cate->crumb = $name1;
            }
            $pl_cate->status = 1;
            $pl_cate->save();

            foreach ($v1['children'] as $v2) {
                $name2 = $v2['title'];
                $child2 = $v2['children'];
                $code2 = $v2['category_id'];

                echo $code2 . '::' . $name1 . $sp . $name2 . "\n";
                $pl_cate = PlatformCategory::find()->where(['id' => $code2, 'platform_type' => $platform_type])->one();
                if (empty($pl_cate)) {
                    $pl_cate = new PlatformCategory();
                    $pl_cate->platform_type = $platform_type;
                    $pl_cate->id = (string)$code2;
                    $pl_cate->parent_id = (string)$code1;
                    $pl_cate->name = $name2;
                    $pl_cate->crumb = $name1 . $sp . $name2;
                }
                $pl_cate->status = 1;
                $pl_cate->save();

                foreach ($child2 as $v3) {
                    $name3 = $v3['title'];
                    $child3 = $v3['children'];

                    $code3 = $v3['category_id'];
                    echo $code3 . '::' . $name1 . $sp . $name2 . $sp . $name3 . "\n";
                    $pl_cate = PlatformCategory::find()->where(['id' => $code3, 'platform_type' => $platform_type])->one();
                    if (empty($pl_cate)) {
                        $pl_cate = new PlatformCategory();
                        $pl_cate->platform_type = $platform_type;
                        $pl_cate->id = (string)$code3;
                        $pl_cate->parent_id = (string)$code2;
                        $pl_cate->name = $name3;
                        $pl_cate->crumb = $name1 . $sp . $name2 . $sp . $name3;
                    }
                    $pl_cate->status = 1;
                    $pl_cate->save();

                    foreach ($child3 as $v4) {
                        $name4 = $v4['title'];
                        $code4 = $v4['category_id'];
                        echo $code4 . '::' . $name1 . $sp . $name2 . $sp . $name3 . $sp . $name4 . "\n";
                        $pl_cate = PlatformCategory::find()->where(['id' => $code4, 'platform_type' => $platform_type])->one();
                        if (empty($pl_cate)) {
                            $pl_cate = new PlatformCategory();
                            $pl_cate->platform_type = $platform_type;
                            $pl_cate->id = (string)$code4;
                            $pl_cate->parent_id = (string)$code2;
                            $pl_cate->name = $name4;
                            $pl_cate->crumb = $name1 . $sp . $name2 . $sp . $name3 . $sp . $name4;
                        }
                        $pl_cate->status = 1;
                        $pl_cate->save();
                    }

                }
            }
        }
        PlatformCategory::updateAll(['status'=>3],['platform_type' => $platform_type,'status'=>2]);
    }


    /**
     * 获取类别
     */
    public function actionGetCategoryNew()
    {
        $platform_type = Base::PLATFORM_OZON;
        $sp = ' / ';
        $shop = Shop::find()->where(['platform_type' => $platform_type])->asArray()->one();
        $api_service = FApiService::factory($shop);
        $result_ru = $api_service->getCategoryNew();
        $result = $api_service->getCategoryNew('ZH_HANS');
        //echo CommonUtil::jsonFormat($result);
        //exit();

        $category_cn = [];
        foreach ($result as $v1) {
            $category_cn[$v1['description_category_id']] = $v1['category_name'];
            foreach ($v1['children'] as $v2) {
                $category_cn[$v2['description_category_id']] = $v2['category_name'];
                foreach ($v2['children'] as $v3) {
                    $category_cn[$v3['type_id']] = $v3['type_name'];
                }
            }
        }

        foreach ($result_ru as $v1) {
            if ($v1['disabled'] === true) {
                continue;
            }
            $name1 = empty($v1['category_name']) ? '' : $v1['category_name'];
            $code1 = $v1['description_category_id'];
            $name1_cn = $category_cn[$code1];
            $pl_cate = PlatformCategory::find()->where(['id' => $code1, 'platform_type' => $platform_type])->one();
            if (empty($pl_cate)) {
                $pl_cate = new PlatformCategory();
                $pl_cate->platform_type = $platform_type;
                $pl_cate->id = (string)$code1;
                $pl_cate->parent_id = (string)0;
                $pl_cate->name = $name1;
                $pl_cate->name_cn = $name1_cn;
                $pl_cate->crumb = $name1;
            }
            //$pl_cate->name_cn = $name1;
            $pl_cate->status = 1;
            $pl_cate->save();

            foreach ($v1['children'] as $k2 => $v2) {
                if ($v2['disabled'] === true) {
                    continue;
                }
                $name2 = $v2['category_name'];
                $child2 = $v2['children'];
                $code2 = $v2['description_category_id'];
                $name2_cn = $category_cn[$code2];

                echo $code2 . '::' . $name1 . $sp . $name2 . $name2_cn . "\n";
                $pl_cate = PlatformCategory::find()->where(['id' => $code2, 'platform_type' => $platform_type])->one();
                if (empty($pl_cate)) {
                    $pl_cate = new PlatformCategory();
                    $pl_cate->platform_type = $platform_type;
                    $pl_cate->id = (string)$code2;
                    $pl_cate->parent_id = (string)$code1;
                    $pl_cate->name = $name2;
                    $pl_cate->name_cn = $name2_cn;
                    $pl_cate->crumb = $name1 . $sp . $name2;
                }
                //$pl_cate->name_cn = $name2;
                $pl_cate->status = 1;
                $pl_cate->save();

                foreach ($child2 as $k3 => $v3) {
                    if ($v3['disabled'] === true) {
                        continue;
                    }
                    $name3 = empty($v3['category_name']) ? $v3['type_name'] : $v3['category_name'];
                    $child3 = $v3['children'];
                    $code3 = empty($v3['description_category_id']) ? $v3['type_id'] : $v3['description_category_id'];
                    $name3_cn = $category_cn[$code3];
                    echo $code3 . '::' . $name1 . $sp . $name2 . $sp . $name3 . $name3_cn . "\n";
                    $pl_cate = PlatformCategory::find()->where(['id' => $code3, 'platform_type' => $platform_type])->one();
                    if (empty($pl_cate)) {
                        $pl_cate = new PlatformCategory();
                        $pl_cate->platform_type = $platform_type;
                        $pl_cate->id = (string)$code3;
                        $pl_cate->parent_id = (string)$code2;
                        $pl_cate->name = $name3;
                        $pl_cate->name_cn = $name3_cn;
                        $pl_cate->crumb = $name1 . $sp . $name2 . $sp . $name3;
                    }
                    //$pl_cate->name_cn = $name3;
                    $pl_cate->status = 1;
                    $pl_cate->save();

                    foreach ($child3 as $k4 => $v4) {
                        if ($v4['disabled'] === true) {
                            continue;
                        }
                        $name4 = empty($v4['category_name']) ? $v4['type_name'] : $v4['category_name'];
                        $code4 = empty($v4['description_category_id']) ? $v4['type_id'] : $v4['description_category_id'];
                        $name4_cn = $category_cn[$code4];
                        echo $code4 . '::' . $name1 . $sp . $name2 . $sp . $name3 . $sp . $name4 . $name4_cn . "\n";
                        $pl_cate = PlatformCategory::find()->where(['id' => $code4, 'platform_type' => $platform_type])->one();
                        if (empty($pl_cate)) {
                            $pl_cate = new PlatformCategory();
                            $pl_cate->platform_type = $platform_type;
                            $pl_cate->id = (string)$code4;
                            $pl_cate->parent_id = (string)$code2;
                            $pl_cate->name = $name4;
                            $pl_cate->name_cn = $name4_cn;
                            $pl_cate->crumb = $name1 . $sp . $name2 . $sp . $name3 . $sp . $name4;
                        }
                        //$pl_cate->name_cn = $name4;
                        $pl_cate->status = 1;
                        $pl_cate->save();
                    }

                }
            }
        }
        PlatformCategory::updateAll(['status' => 3], ['platform_type' => $platform_type, 'status' => 2]);
    }

    /**
     * 获取类别字段
     */
    public function actionGetCategoryFieldNew()
    {
        //1 7 33 73 74 75 77 83 88 87 95 102 104 105 106 111 112 117 121 125 126 127 129 20034 太多先暂时不显示
        //31 85 品牌 126745801 Нет бренда 无品牌
        $platform_type = Base::PLATFORM_OZON;
        $platform_category = PlatformCategory::find()->where(['platform_type' => $platform_type, 'status' => 1])->offset(0)->limit(1000)->all();
        $shop = Shop::find()->where(['platform_type' => $platform_type])->offset(4)->asArray()->one();
        $api_service = FApiService::factory($shop);
        foreach ($platform_category as $category) {
            $category_id = $category['id'];
            $par_category = PlatformCategory::find()->where(['parent_id' => $category_id,'platform_type' => $platform_type])->exists();
            if (!empty($par_category)) {
                $category->status = 2;
                $category->save();
                continue;
            }

            try {
                $api_result = $api_service->getCategoryAttributesNew($category_id,'ZH_HANS');
                $result = $api_service->getCategoryAttributesNew($category_id);
                //echo CommonUtil::jsonFormat($result);
                //exit();
                $attr_ru = [];
                foreach ($result as $v1) {
                    $attr_ru[$v1['id']] = (string)$v1['name'];
                }
                if (empty($api_result)) {
                    $category->status = 4;
                    $category->save();
                    continue;
                }
                //$attribute_ids = PlatformCategoryField::find()->where(['platform_type' => $platform_type, 'category_id' => $category_id])->select('attribute_id,id')->asArray()->all();
                //$attribute_ids = ArrayHelper::map($attribute_ids,'attribute_id','id');
                $data = [];
                foreach ($api_result as $api_v) {
                    if (empty($attribute_ids[$api_v['id']])) {
                        /*$pl_cate = new PlatformCategoryField();
                        $pl_cate->platform_type = $platform_type;
                        $pl_cate->category_id = (string)$category_id;
                        $pl_cate->attribute_id = (string)$api_v['id'];
                        $pl_cate->attribute_name_cn = (string)$api_v['name'];
                        $pl_cate->attribute_name = empty($attr_ru[$api_v['id']])?'':$attr_ru[$api_v['id']];
                        $pl_cate->attribute_type = (string)$api_v['type'];
                        $pl_cate->is_required = $api_v['is_required'] ? 1 : 0;
                        $pl_cate->is_multiple = $api_v['is_collection'] ? 1 : 0;
                        $pl_cate->dictionary_id = (string)$api_v['dictionary_id'];
                        $pl_cate->attribute_desc =  (string)$api_v['description'];
                        $pl_cate->status = 0;
                        $pl_cate->param = json_encode($api_v, JSON_UNESCAPED_UNICODE);
                        $pl_cate->save();*/
                        $data[] = [
                            'platform_type' => $platform_type,
                            'category_id' => (string)$category_id,
                            'attribute_id' => (string)$api_v['id'],
                            'attribute_name_cn' => (string)$api_v['name'],
                            'attribute_name' => empty($attr_ru[$api_v['id']])?'':$attr_ru[$api_v['id']],
                            'attribute_type' => (string)$api_v['type'],
                            'is_required' =>  $api_v['is_required'] ? 1 : 0,
                            'is_multiple'=>$api_v['is_collection'] ? 1 : 0,
                            'dictionary_id'=>(string)$api_v['dictionary_id'],
                            'attribute_desc'=>(string)$api_v['description'],
                            'status'=>0,
                            'param'=> json_encode($api_v, JSON_UNESCAPED_UNICODE),
                            'add_time' => time(),
                            'update_time' => time(),
                        ];
                    } else {
                        PlatformCategoryField::updateAll(['status'=>0],['id'=>$attribute_ids[$api_v['id']]]);
                    }
                }
                $add_columns = [
                    'platform_type',
                    'category_id',
                    'attribute_id',
                    'attribute_name_cn',
                    'attribute_name',
                    'attribute_type',
                    'is_required',
                    'is_multiple',
                    'dictionary_id',
                    'attribute_desc',
                    'status',
                    'param',
                    'add_time',
                    'update_time'
                ];
                PlatformCategoryField::getDb()->createCommand()->batchIgnoreInsert(PlatformCategoryField::tableName(), $add_columns, $data)->execute();

                $category->status = 2;
                $category->save();
            } catch (\Exception $e) {
                $category->status = 4;
                $category->save();
                echo  $e->getMessage(). "\n";
            }
            echo $category_id . "\n";
            //exit;
        }
    }

    /**
     * 获取类别字段
     */
    public function actionGetCategoryField()
    {
        //1 7 33 73 74 75 77 83 88 87 95 102 104 105 106 111 112 117 121 125 126 127 129 20034 太多先暂时不显示
        //31 85 品牌 126745801 Нет бренда 无品牌
        $platform_type = Base::PLATFORM_OZON;
        $platform_category = PlatformCategory::find()->where(['platform_type' => $platform_type, 'status' => 1])->all();
        $shop = Shop::find()->where(['platform_type' => $platform_type])->offset(1)->asArray()->one();
        $api_service = FApiService::factory($shop);
        foreach ($platform_category as $category) {
            $category_id = $category['id'];
            $par_category = PlatformCategory::find()->where(['parent_id' => $category_id])->exists();
            if (!empty($par_category)) {
                $category->status = 2;
                $category->save();
                continue;
            }
            try {
                $api_result = $api_service->getCategoryAttributes($category_id);
                if (empty($api_result)) {
                    $category->status = 4;
                    $category->save();
                    continue;
                }
                $attribute_ids = PlatformCategoryField::find()->where(['platform_type' => $platform_type, 'category_id' => $category_id])->select('attribute_id,id')->asArray()->all();
                $attribute_ids = ArrayHelper::map($attribute_ids,'attribute_id','id');
                foreach ($api_result as $api_v) {
                    if (empty($attribute_ids[$api_v['id']])) {
                        $pl_cate = new PlatformCategoryField();
                        $pl_cate->platform_type = $platform_type;
                        $pl_cate->category_id = (string)$category_id;
                        $pl_cate->attribute_id = (string)$api_v['id'];
                        $pl_cate->attribute_name = (string)$api_v['name'];
                        $pl_cate->attribute_type = (string)$api_v['type'];
                        $pl_cate->is_required = $api_v['is_required'] ? 1 : 0;
                        $pl_cate->is_multiple = $api_v['is_collection'] ? 1 : 0;
                        $pl_cate->dictionary_id = (string)$api_v['dictionary_id'];
                        $pl_cate->status = 0;
                        $pl_cate->save();
                    } else {
                        PlatformCategoryField::updateAll(['status'=>0],['id'=>$attribute_ids[$api_v['id']]]);
                    }
                }

                $category->status = 2;
                $category->save();
            } catch (\Exception $e) {
                $category->status = 4;
                $category->save();
                echo  $e->getMessage(). "\n";
            }
            echo $category_id . "\n";
        }
    }

    /**
     * 获取类别字段
     */
    public function actionGetCategoryFieldValue($page = 1,$attribute_id = 0,$last_id = 0)
    {
        $platform_type = Base::PLATFORM_OZON;
        $where = ['platform_type' => $platform_type, 'status' => 0];
        if(!empty($attribute_id)) {
            $where['attribute_id'] = $attribute_id;
        }
        $platform_category = PlatformCategoryField::find()->where($where)
            ->andWhere(['!=','dictionary_id',0])->offset(100*($page-1))->limit(100)->groupBy('attribute_id')->all();
        $shop = Shop::find()->where(['platform_type' => $platform_type])->asArray()->one();
        $api_service = FApiService::factory($shop);
        foreach ($platform_category as $category) {
            $attribute_id = $category['attribute_id'];
            echo $attribute_id . "\n";
            try {
                $result = (new OzonCategoryService())->addPlatformCategoryFieldValue($api_service,$category,$last_id,true);
                if ($result) {
                    //$category->status = 1;
                    //$category->save();
                    PlatformCategoryField::updateAll(['status' => 1], ['attribute_id' => $attribute_id, 'status' => 0]);
                }else{
                    PlatformCategoryField::updateAll(['status' => 2], ['attribute_id' => $attribute_id, 'status' => 0]);
                }
            } catch (\Exception $e) {
                //$category->status = 3;
                //$category->save();
                echo $e->getLine() . ' ' . $e->getMessage() . "\n";
            }
            if (!empty($last_id)) {
                exit();
            }
            //exit();
        }
    }

    /**
     * 获取类别字段
     */
    public function actionGetCategoryFieldValueToType($attribute_id = 8229,$page = 1,$category_id = 0,$last_id = 0)
    {
        $platform_type = Base::PLATFORM_OZON;
        $where = ['platform_type' => $platform_type, 'status' => 0,'attribute_id'=>$attribute_id];
        if(!empty($category_id)) {
            $where['category_id'] = $category_id;
        }
        $platform_category = PlatformCategoryField::find()->where($where)
            ->andWhere(['!=','dictionary_id',0])->offset(100*($page-1))->limit(100)->all();
        $shop = Shop::find()->where(['platform_type' => $platform_type])->asArray()->one();
        $api_service = FApiService::factory($shop);
        foreach ($platform_category as $category) {
            $category_id = $category['category_id'];
            echo $category_id ."\n";
            try {
                $result = (new OzonCategoryService())->addPlatformCategoryFieldValue($api_service,$category,$last_id,true);
                if($result) {
                    $category->status = 1;
                    $category->save();
                }else{
                    $category->status = 2;
                    $category->save();
                }
            } catch (\Exception $e) {
                //$category->status = 3;
                //$category->save();
                echo $e->getLine(). ' '.$e->getMessage()."\n";
            }
            if(!empty($last_id)){
                exit();
            }
            //exit();
        }
    }

    /**
     * 获取类别字段
     */
    public function actionGetCategoryFieldValueNew($page = 1,$attribute_id = 0,$last_id = 0)
    {
        $page_num = 500;
        $platform_type = Base::PLATFORM_OZON;
        $where = ['platform_type' => $platform_type, 'status' => 0];
        if(!empty($attribute_id)) {
            $where['attribute_id'] = $attribute_id;
        }
        $platform_category = PlatformCategoryField::find()->where($where)
            ->andWhere(['!=','dictionary_id',0])->offset($page_num*($page-1))->limit($page_num)->groupBy('attribute_id')->all();
        $shop = Shop::find()->where(['platform_type' => $platform_type])->asArray()->one();
        $api_service = FApiService::factory($shop);
        foreach ($platform_category as $category) {
            $attribute_id = $category['attribute_id'];
            echo $attribute_id . "\n";
            try {
                $result = (new OzonCategoryService())->addPlatformCategoryFieldValueNew($api_service,$category,$last_id,true);
                if ($result) {
                    //$category->status = 1;
                    //$category->save();
                    PlatformCategoryField::updateAll(['status' => 1], ['attribute_id' => $attribute_id, 'status' => 0]);
                }else{
                    PlatformCategoryField::updateAll(['status' => 2], ['attribute_id' => $attribute_id, 'status' => 0]);
                }
            } catch (\Exception $e) {
                //$category->status = 3;
                //$category->save();
                echo $e->getLine() . ' ' . $e->getMessage() . "\n";
            }
            if (!empty($last_id)) {
                exit();
            }
            //exit();
        }
    }

    /**
     * 获取类别字段
     */
    public function actionGetCategoryFieldValueToTypeNew($attribute_id = 8229,$page = 1,$category_id = 0,$last_id = 0)
    {
        $page_num = 500;
        $platform_type = Base::PLATFORM_OZON;
        $where = ['platform_type' => $platform_type, 'status' => 0,'attribute_id'=>$attribute_id];
        if(!empty($category_id)) {
            $where['category_id'] = $category_id;
        }
        $platform_category = PlatformCategoryField::find()->where($where)
            ->andWhere(['!=','dictionary_id',0])->offset($page_num*($page-1))->limit($page_num)->all();
        $shop = Shop::find()->where(['platform_type' => $platform_type])->offset(1)->asArray()->one();
        $api_service = FApiService::factory($shop);
        foreach ($platform_category as $category) {
            $category_id = $category['category_id'];
            echo $category_id ."\n";
            try {
                $result = (new OzonCategoryService())->addPlatformCategoryFieldValueNew($api_service,$category,$last_id,true);
                if($result) {
                    $category->status = 1;
                    $category->save();
                }else{
                    $category->status = 2;
                    $category->save();
                }
            } catch (\Exception $e) {
                //$category->status = 3;
                //$category->save();
                echo $e->getFile(). $e->getLine(). ' '.$e->getMessage(). $e->getTraceAsString()."\n";
            }
            if(!empty($last_id)){
                exit();
            }
            //exit();
        }
    }

    public function actionUpdateNewCategory()
    {
        $category_lists = CategoryMapping::find()->where(['platform_type'=>Base::PLATFORM_OZON])
            ->andWhere(['!=','attribute_value',''])
            ->all();
        foreach ($category_lists as $v) {
            if (empty($v['attribute_value'])) {
                continue;
            }
            $attr_val = json_decode($v['attribute_value'], true);
            $cate_id = 0;
            foreach ($attr_val as $att_v) {
                if ($att_v['id'] == 8229 && !empty($att_v['val'])) {
                    if(is_array($att_v['val'])){
                        $cate_id = current($att_v['val'])['val'];
                    }else{
                        $cate_id = $att_v['val'];
                    }
                }
            }
            if (empty($cate_id)) {
                continue;
            }
            $v->o_category_name = (string)$cate_id;
            $v->save();
            echo $v['category_id']."\n";
        }

        $category_lists = GoodsShopExpand::find()->where(['platform_type'=>Base::PLATFORM_OZON])
            ->andWhere(['!=','attribute_value',''])
            ->all();
        foreach ($category_lists as $v) {
            if (empty($v['attribute_value'])) {
                continue;
            }
            $attr_val = json_decode($v['attribute_value'], true);
            $cate_id = 0;
            foreach ($attr_val as $att_v) {
                if ($att_v['id'] == 8229 && !empty($att_v['val'])) {
                    if(is_array($att_v['val'])){
                        $cate_id = current($att_v['val'])['val'];
                    }else{
                        $cate_id = $att_v['val'];
                    }
                }
            }
            if (empty($cate_id)) {
                continue;
            }
            $v->o_category_id = (string)$cate_id;
            $v->save();
            echo $v['cgoods_no']."\n";
        }

        $category_lists = \common\models\PlatformInformation::find()->where(['platform_type'=>Base::PLATFORM_OZON])
            ->andWhere(['!=','attribute_value',''])
            ->all();
        foreach ($category_lists as $v) {
            if (empty($v['attribute_value'])) {
                continue;
            }
            $attr_val = json_decode($v['attribute_value'], true);
            $cate_id = 0;
            foreach ($attr_val as $att_v) {
                if ($att_v['id'] == 8229 && !empty($att_v['val'])) {
                    if(is_array($att_v['val'])){
                        $cate_id = current($att_v['val'])['val'];
                    }else{
                        $cate_id = $att_v['val'];
                    }
                }
            }
            if (empty($cate_id)) {
                continue;
            }
            $v->o_category_name = (string)$cate_id;
            $v->save();
            echo $v['goods_no']."\n";
        }

    }

    /**
     * 设置为公用值
     */
    public function actionSetCommonCategoryFieldValue($attribute_id)
    {
        PlatformCategoryFieldValue::setPlatform(Base::PLATFORM_OZON);
        $plat_field = PlatformCategoryFieldValue::find()->where(['attribute_id'=>$attribute_id])->limit(1)->asArray()->one();
        var_dump(PlatformCategoryFieldValue::updateAll(['category_id'=>'0'],['attribute_id'=>$attribute_id,'category_id'=>$plat_field['category_id']]));
        var_dump(PlatformCategoryFieldValue::deleteAll([
            'and',['attribute_id'=>$attribute_id],
            ['!=','category_id','0']
        ]));
        var_dump(PlatformCategoryField::updateAll(['status'=>1],['attribute_id'=>$attribute_id,'status'=>0]));
        echo '成功';
    }


    public function actionOrderOzon($shop_id = null)
    {
        $add_order_exe_time = strtotime(date('2022-03-02 00:00:00'));
        $where = [
            'platform_type' => [Base::PLATFORM_OZON],
            'status' => Shop::STATUS_VALID
        ];
        if(!empty($shop_id)){
            $where['id'] = $shop_id;
        }
        $shop = Shop::find()->where($where)->andWhere(['<>', 'client_key', ''])->orderBy('add_order_exe_time asc')->all();
        foreach ($shop as $shop_v) {
            echo $shop_v['id']." ".$shop_v['name']."\n";
            if(empty($shop_v['client_key']) || empty($shop_v['secret_key'])){
                continue;
            }

            try {
                $api_service = FApiService::factory($shop_v);
            }catch (\Exception $e){
                CommonUtil::logs('add_order error: shop_id:' . $shop_v['id'] . ' platform_type:' . $shop_v['platform_type'] .' '. $e->getMessage().' '.$e->getFile().$e->getLine(), 'order_api_error');
                continue;
            }

            if (empty($api_service)) {
                continue;
            }

            $limit = 50;
            $end_order_exe_time = $add_order_exe_time;
            $old_end_order_exe_time = 0;
            while (true) {
                if($old_end_order_exe_time == $end_order_exe_time){
                    break;
                }
                $old_end_order_exe_time = $end_order_exe_time;

                $order_lists = [];
                try {
                    $order_lists = $api_service->getOrderLists(date('Y-m-d H:i:s',$end_order_exe_time));
                } catch (\Exception $e) {
                    CommonUtil::logs('add_order shop error: shop_id:' . $shop_v['id'] . ' platform_type:' . $shop_v['platform_type'] . ' exec_time:' . $end_order_exe_time .' '. $e->getMessage().' '.$e->getFile().$e->getLine(), 'order_api_error');
                    break;
                }
                if(empty($order_lists)) {
                    break;
                }
                $count = 0;
                foreach ($order_lists as $order_api) {
                    try {
                        $count++;
                        $relation_no = $order_api['posting_number'];
                        $order_info = $api_service->getOrderInfo($relation_no);
                        if(!empty($order_info['tpl_integration_type']) && $order_info['tpl_integration_type'] == 'aggregator') {
                            $order = Order::find()->where(['relation_no'=>$relation_no])->one();
                            if(!in_array($order['order_status'],[Order::ORDER_STATUS_FINISH])) {
                                continue;
                            }

                            if($order['integrated_logistics'] == Order::INTEGRATED_LOGISTICS_YES){
                                continue;
                            }

                            if(!empty($order_info['delivery_method']) && !empty($order_info['delivery_method']['tpl_provider'])) {
                                $order['logistics_channels_name'] = $order_info['delivery_method']['tpl_provider'];
                            }
                            $old_track_no = $order['track_no'];
                            $order['logistics_channels_id'] = 0;
                            $order['track_no'] = $order_info['tracking_number'];
                            //$order['integrated_logistics'] = Order::INTEGRATED_LOGISTICS_YES;
                            //$order->save();
                            echo $shop_v['name'] .',' . $relation_no. ',old:'.$old_track_no .',new:'. $order_info['tracking_number'] ."\n";
                            //exit();
                        }
                    } catch (\Exception $e) {
                        echo $shop_v['name'] .' ' .$relation_no." error ".$e->getMessage()."\n";
                    }
                }

                if($count < $limit || $shop_v['platform_type'] == Base::PLATFORM_FRUUGO){
                    break;
                }
            }
        }
    }

    /**
     * 更新类目
     * @param $category_id
     * @return void
     */
    public function actionUpdateCategoryAttributeValue($category_id)
    {
        $where =  ['platform_type' => Base::PLATFORM_OZON,'status'=>1];
        if(!empty($category_id)){
            $where['id'] = $category_id;
        }
        $category = PlatformCategory::find()->andWhere($where)->all();
        foreach ($category as $v) {
            try {
                echo 'category_id:'.$v['id'] .' '.$v['name']."\n";
                $par_category = PlatformCategory::find()->where(['parent_id' => $v['id']])->exists();
                if (!empty($par_category)) {
                    $v->status = 2;
                    $v->save();
                    continue;
                }
                (new OzonCategoryService())->updateCategoryAttributeValue($v['id']);
                $v->status = 2;
                $v->save();
            } catch (\Exception $e) {
                $v->status = 4;
                $v->save();
                echo $e->getLine(). ' '.$e->getMessage()."\n";
            }
        }
    }

    /**
     * 更新采集汇率
     * @return void
     */
    public function actionUpGrabExchangeRate()
    {
        $goods_lists = Goods::find()->where(['source_platform_type' => Base::PLATFORM_OZON])->asArray()->all();
        foreach ($goods_lists as $goods_list) {
            $goods_shop = GoodsShop::find()->where(['goods_no' => $goods_list['goods_no'], 'platform_type' => Base::PLATFORM_OZON,'follow_claim'=>1])->orderBy('add_time asc')->limit(1)->one();
            if (empty($goods_shop)) {
                if (empty($goods_list['gbp_price'])) {
                    continue;
                }
                if ($goods_list['add_time'] < strtotime('2023-06-12 00:00:00')) {
                    $price = $goods_list['gbp_price'];
                } else {
                    $price = $goods_list['gbp_price'] * 1.24;
                }
            } else {
                $goods_shop_price_change_log = GoodsShopPriceChangeLog::find()->where(['goods_shop_id' => $goods_shop['id']])->orderBy('add_time asc')->one();
                $price = $goods_shop['price'];
                if (!empty($goods_shop_price_change_log) && !empty($goods_shop_price_change_log['old_price'])) {
                    $price = $goods_shop_price_change_log['old_price'];
                }
            }
            $goods_source = GoodsSource::find()->where(['goods_no' => $goods_shop['goods_no'], 'platform_type' => Base::PLATFORM_OZON])->one();
            if (empty($goods_source) || $goods_source['exchange_rate'] > 0) {
                continue;
            }
            $goods_source['exchange_rate'] = $price / $goods_source['price'];
            $goods_source->save();
            echo $goods_shop['goods_no'] . ',' . $goods_source['exchange_rate'] . "\n";
        }
    }

}