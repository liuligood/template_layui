<?php
namespace console\controllers\api;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\FinancialPeriodRollover;
use common\models\Goods;
use common\models\goods\GoodsAllegro;
use common\models\goods\GoodsChild;
use common\models\goods_shop\GoodsShopOverseasWarehouse;
use common\models\GoodsEvent;
use common\models\GoodsShop;
use common\models\Order;
use common\models\OrderGoods;
use common\models\platform\PlatformCategory;
use common\models\platform\PlatformCategoryField;
use common\models\platform\PlatformCategoryFieldValue;
use common\models\Shop;
use common\models\sys\Exectime;
use common\models\warehousing\BlContainerGoods;
use common\services\FApiService;
use common\services\goods\FGoodsService;
use common\services\goods\GoodsService;
use common\services\goods\GoodsShopService;
use yii\console\Controller;
use yii\helpers\ArrayHelper;

class AllegroController extends Controller
{

    public function actionTest()
    {
        $shop = Shop::findOne(4);
        $api_service = FApiService::factory($shop);
        //$result = $api_service->getBilling('2023-01-01', 0);
        //$result = $api_service->getPayment('2023-01-01', 0);
        $result = $api_service->getOrderToPayment('be8b4ca6-6a63-11ed-b362-0590ca01dcf3');
        //$result = $api_service->getCategoryProductParameters('100006');

        //$result = $api_service->getOrderLists('2022-06-01','2022-06-02T00:00');
        //$result = $api_service->getTask('497883991');
        //$result = $api_service->getTask('497923957');
        //$result = $api_service->getTask('497958103');
        //$result = $api_service->getGoodsList(1,'VALIDATION_STATE_PENDING');

        ////$result = $api_service->getCategoryAttributes('17037099');
        //$result = $api_service->getCategory();

        //$result = $api_service->getCategoryProductParameters(261627);
        //$result = $api_service->getCategory();

        //$result = $api_service->getBilling('2022-05-20');
        ///echo CommonUtil::jsonFormat($result);
//exit;
        //$resi = $api_service->updateStockToId(10884349255,0);
        //$result = $api_service->getProductsToAsin('Shaver68');

        //$result = $api_service->getAfterSalesServiceImplied();

        echo CommonUtil::jsonFormat($result);
        exit();
    }

    public function actionOrderSettlement($shop_id = null)
    {
        $where = ['platform_type'=>Base::PLATFORM_ALLEGRO];
        if(!empty($shop_id)) {
            $where['id'] = $shop_id;
        }
        $shop_lists = Shop::find()->where($where)->all();
        foreach ($shop_lists as $shop) {
            $shop_id = $shop['id'];
            $api_service = FApiService::factory($shop);

            /*$exec_time = Exectime::find()->where(['object_type'=>Exectime::TYPE_SHOP_BILLING,'object_no'=>$shop_id])
                ->select('exec_time')->scalar();
            if(empty($exec_time)) {
                $date = Order::find()->select('date')->where(['shop_id'=>$shop_id])->orderBy('date asc')->limit(1)->scalar();
                $exec_time = $date < strtotime('2022-01-01')?strtotime('2022-01-01'):$date;
            }*/

            $this->repeatALl($api_service, 'getBilling', function ($v) use ($shop_id, $shop) {
                if ($v['type']['id'] == 'PAD') {
                    return false;
                }
                $financial_rollover = FinancialPeriodRollover::find()->where(['shop_id' => $shop_id, 'identifier' => $v['id']])->one();
                if (!empty($financial_rollover)) {
                    return false;
                }
                if ($v['value']['amount'] == 0) {
                    return false;
                }
                $financial_rollover = new FinancialPeriodRollover();
                $financial_rollover->platform_type = $shop['platform_type'];
                $financial_rollover->shop_id = $shop_id;
                $financial_rollover->identifier = $v['id'];
                if (!empty($v['order']) && !empty($v['order']['id'])) {
                    $financial_rollover->relation_no = $v['order']['id'];
                }
                $financial_rollover->currency = $v['value']['currency'];
                $financial_rollover->amount = $v['value']['amount'];
                if (!empty($v['offer'])) {
                    $financial_rollover->offer = $v['offer']['name'] . '(' . $v['offer']['id'] . ')';
                }
                $financial_rollover->operation = $v['type']['name'];
                $financial_rollover->date = strtotime($v['occurredAt']);
                $financial_rollover->collection_time = strtotime($v['occurredAt']);
                $financial_rollover->params = json_encode($v, JSON_UNESCAPED_UNICODE);
                $financial_rollover->save();
            });

            $this->repeatALl($api_service, 'getPayment', function ($v) use ($api_service, $shop_id, $shop) {
                if (in_array($v['type'], ['PAYOUT', 'DEDUCTION_CHARGE'])) {
                    return false;
                }
                if (empty($v['payment']['id'])) {
                    CommonUtil::logs('pay:' . json_encode($v, JSON_UNESCAPED_UNICODE), 'allegro_settlement');
                    return false;
                }
                $type = $v['type'];
                $pay_id = $v['payment']['id'];
                $financial_rollover = FinancialPeriodRollover::find()->where(['shop_id' => $shop_id, 'operation' => $type, 'identifier' => $pay_id])->one();
                if (!empty($financial_rollover)) {
                    return false;
                }
                $pay_lists = $api_service->getOrderToPayment($pay_id);
                $pay_lists = current($pay_lists);
                if (empty($pay_lists)) {
                    CommonUtil::logs('pay2:' . json_encode($v, JSON_UNESCAPED_UNICODE), 'allegro_settlement');
                    //return false;
                }
                if ($v['value']['amount'] == 0) {
                    return false;
                }
                $financial_rollover = new FinancialPeriodRollover();
                $financial_rollover->platform_type = $shop['platform_type'];
                $financial_rollover->shop_id = $shop_id;
                //CONTRIBUTION 支付 REFUND_CHARGE 退款
                $financial_rollover->identifier = $pay_id;
                $financial_rollover->relation_no = empty($pay_lists['id']) ? '' : $pay_lists['id'];
                $financial_rollover->currency = $v['value']['currency'];
                $financial_rollover->amount = $v['value']['amount'];
                $financial_rollover->operation = $v['type'];
                $financial_rollover->date = strtotime($v['occurredAt']);
                $financial_rollover->collection_time = strtotime($v['occurredAt']);
                $financial_rollover->params = json_encode($v, JSON_UNESCAPED_UNICODE);
                $financial_rollover->save();
            });
        }
    }

    public function repeatALl($api_service,$type,$fun, $to_time = null, $from_time = null ,$offset = 0)
    {
        $limit = 0;
        if ($type == 'getBilling') {
            $limit = 100;
            $object_type  = Exectime::TYPE_SHOP_BILLING;
        }
        if ($type == 'getPayment') {
            $limit = 50;
            $object_type  = Exectime::TYPE_SHOP_PAYMENT;
        }
        if (empty($limit)) {
            return false;
        }

        $shop_id = $api_service->shop['id'];
        if(empty($to_time)) {
            $exec_time = Exectime::getTime($object_type,$shop_id);
            if (empty($exec_time)) {
                $date = Order::find()->select('date')->where(['shop_id' => $shop_id])->orderBy('date asc')->limit(1)->scalar();
                $exec_time = $date < strtotime('2022-06-01') ? strtotime('2022-06-01') : $date;
            }
            $from_time = $exec_time + 15 * 24 * 60 * 60;
            $yes_time = strtotime(date("Y-m-d")) - 1;
            $from_time = min($from_time, $yes_time);
            $exec_time = $exec_time - 24 * 60 * 60;//多执行一天
            $to_time = date('Y-m-d', $exec_time);
            $from_time = date('Y-m-d\TH:i:s\Z', $from_time);
        }

        echo 'type:'.$type.' shop_id:'.$shop_id.' to_time:'.$to_time.' from_time:'.$from_time.' offset:'.$offset ."\n";
        CommonUtil::logs('type:'.$type.' shop_id:'.$shop_id.' to_time:'.$to_time.' from_time:'.$from_time.' offset:'.$offset,'allegro_settlement_time');
        $result = $api_service->$type($to_time, $from_time, $offset);
        foreach ($result as $v) {
            $fun($v);
        }
        if (count($result) == $limit) {
            $offset += $limit;
            return $this->repeatALl($api_service, $type, $fun, $to_time, $from_time, $offset);
        } else {
            Exectime::setTime(strtotime($from_time),$object_type,$shop_id);
            if(strtotime(date('Y-m-d',strtotime($from_time) + 1)) < strtotime(date("Y-m-d"))) {
                return $this->repeatALl($api_service, $type, $fun, null, null, 0);
            }
        }
    }

    /**
     * 删除草稿商品
     * @param null $shop_id
     * @return void
     * @throws \yii\base\Exception
     */
    public function actionDelDraftGoods($shop_id = null)
    {
        $where = ['platform_type' => Base::PLATFORM_ALLEGRO];
        if (!empty($shop_id)) {
            $where['id'] = $shop_id;
        }
        $shop_lists = Shop::find()->where($where)->all();
        foreach ($shop_lists as $shop) {
            try {
                $api_service = FApiService::factory($shop);
                $result = $api_service->getProductsToStatus('INACTIVE', 1, 1000);
                if (empty($result)) {
                    continue;
                }
                foreach ($result as $v) {
                    $api_service->delDraftGoods($v['id']);
                    echo date('Y-m-d H:i:s') .' '.$shop['id'] . ',' . $v['id'] . "\n";
                }
            } catch (\Exception $e) {
                echo $e->getMessage() . "\n";
            }
        }
    }

    /**
     * 删除扣款商品
     */
    public function actionRemoveChargedGoods($shop_id,$add_time='2022-05-20')
    {
        $shop = Shop::findOne($shop_id);
        $api_service = FApiService::factory($shop);
        $ids = $this->getBilling($api_service, $add_time);
        if(empty($ids)){
            return;
        }
        do {
            $top_100 = array_slice($ids, 0, 100);
            $ids = array_slice($ids, 100);
            var_dump($top_100);
            $resi = $api_service->updateStockToId($top_100,0);
            var_dump($resi);
        } while (count($ids) > 0);
    }

    /**
     * @param $api_service
     * @param $add_time
     * @param int $offset
     * @return array
     */
    public function getBilling($api_service, $add_time,$offset = 0)
    {
        $ids = [];
        $result = $api_service->getBilling($add_time, null, $offset, 'ILF');
        foreach ($result as $v) {
            $ids[] = $v['offer']['id'];
            $ids = array_unique($ids);
            echo $v['offer']['id'] . "," . $v['value']['amount'] . ',' . $v['occurredAt'] . "\n";
        }
        if (count($result) == 100) {
            $offset += 100;
            $n_ids = $this->getBilling($api_service, $add_time, $offset);
            $ids = array_merge($n_ids, $ids);
            $ids = array_unique($ids);
        }
        return $ids;
    }

    /**
     * 重新删除商品
     * @param $file
     * @throws \yii\base\Exception
     */
    public function actionAgainDel($file)
    {
        $file = fopen($file, "r") or exit("Unable to open file!");
        while (!feof($file)) {
            $line = trim(fgets($file));
            if (empty($line)) continue;

            list($shop_id,$goods_no) = explode(',', $line);
            if (empty($goods_no) || empty($shop_id)) {
                continue;
            }

            (new GoodsService())->claim($goods_no, [$shop_id], GoodsService::SOURCE_METHOD_OWN, [
                'is_sync' => false,
            ]);

            $exist_goods = OrderGoods::find()->alias('og')
                ->leftJoin(Order::tableName() . ' o', 'o.order_id=og.order_id')
                ->where(['goods_no' => $goods_no, 'shop_id' => $shop_id])
                ->exists();
            if ($exist_goods) {//存在订单的加半年
                continue;
            }

            $goods_shop = GoodsShop::find()->where([
                'shop_id' => $shop_id,
                'goods_no' => $goods_no
            ])->one();
            if (empty($goods_shop)) {
                echo $goods_no . ',' . '店铺商品不存在' . "\n";
                continue;
            }
            //删除商品
            (new GoodsShopService())->delGoods($goods_shop);
            echo $goods_no . ',' . $shop_id . "\n";
        }
        fclose($file);
        echo "all done\n";
    }

    /**
     * 删除过期商品
     */
    public function actionDelExpiredGoods()
    {
        /*
            //Soul-In GIVENI
            //GIVENI FTYY
            //FTYY TPKE
            //TPKE SL-BEAN
            //SL-BEAN Qixun-Coltd
            //Qixun-Coltd Soul-In

            //GuoRan GHCD
            //GHCD GuoRan
         */
        $shop_map = [
            69=>70,
            70=>71,
            71=>72,
            72=>78,
            78=>332,
            332 => 69,
            271=>61,
            61=>271,
        ];
        $goods_shop = GoodsShop::find()->where(['platform_type' => Base::PLATFORM_ALLEGRO,'shop_id'=>271])
            ->andWhere(['!=','status',GoodsShop::STATUS_DELETE])
            ->andWhere(['<', 'add_time', time() - 11 * 30 * 24 * 60 * 60])->orderBy('add_time asc')->limit(1000)->all();
        foreach ($goods_shop as $v) {
            $goods_no = $v['goods_no'];

            $exist_goods = OrderGoods::find()->alias('og')
                ->leftJoin(Order::tableName() . ' o', 'o.order_id=og.order_id')
                ->where(['goods_no'=>$goods_no,'shop_id'=>$v['shop_id']])
                ->exists();
            if($exist_goods) {//存在订单的加半年
                $v->add_time = $v['add_time'] + 6 * 30 * 24 * 60 * 60;
                $v->save();
                echo '存在订单：' . $v['shop_id'] . ',' . $goods_no . ',' . date('Y-m-d H:i:s', $v['add_time'])."\n";
                CommonUtil::logs('存在订单：' . $v['shop_id'] . ',' . $goods_no . ',' . date('Y-m-d H:i:s', $v['add_time']), 'del_expired_goods');
                continue;
            }

            //删除商品
            (new GoodsShopService())->delGoods($v);
            $new_shop_id = 0;
            if(!empty($shop_map[$v['shop_id']])) {
                $new_shop_id = $shop_map[$v['shop_id']].'_'.$v['country_code'];
                $params = [
                    'discount' => 9.8,
                ];
                //添加商品
                (new GoodsService())->claim($goods_no, [$new_shop_id], GoodsService::SOURCE_METHOD_OWN, $params);
            }
            echo $v['shop_id'] . ',' . $new_shop_id . ',' . $goods_no . ','.date('Y-m-d H:i:s',$v['add_time'])."\n";
            CommonUtil::logs($v['shop_id'] . ',' . $new_shop_id . ',' . $goods_no . ','.date('Y-m-d H:i:s',$v['add_time']), 'del_expired_goods');
        }
    }

    /**
     * 修复平台商品id
     * @return void
     */
    public function actionRePlatformGoodsId($shop_id)
    {
        $goods_shop_lists = GoodsShop::find()->where(['shop_id'=>$shop_id,'platform_goods_id'=>''])->limit(1000)->all();
        if (empty($goods_shop_lists)){
            return ;
        }
        $shop = Shop::findOne($shop_id);
        $api_service = FApiService::factory($shop);
        foreach ($goods_shop_lists as $v) {
            $result = $api_service->getProductsToAsin($v['platform_sku_no']);
            if(!empty($result) && !empty($result['id'])){
                $v->platform_goods_id = $result['id'];
                $v->save();
                echo $v['platform_sku_no'].','.$result['id']."\n";
            } else {
                echo $v['platform_sku_no'].',-1'."\n";
            }
        }
        echo "执行完"."\n";
    }

    /**
     * 补充平台未认领商品
     * @param $shop_id
     * @param int $page
     * @throws \yii\base\Exception
     */
    public function actionSyncAddGoods($shop_id, $page = 1)
    {
        $shop = Shop::findOne($shop_id);
        if ($shop['platform_type'] != Base::PLATFORM_ALLEGRO) {
            return;
        }
        $limit = 1000;
        $api_service = FApiService::factory($shop);
        while (true) {
            echo $page . "\n";
            $order_lists = $api_service->getProductsToStatus('ACTIVE', $page, $limit);
            if (empty($order_lists)) {
                break;
            }

            $del_ids = [];
            foreach ($order_lists as $v) {
                $goods = GoodsChild::find()->where(['sku_no' => $v['external']['id']])->asArray()->one();
                if (empty($goods)) {
                    $del_ids[] = $v['id'];
                    continue;
                }
                $add_time = strtotime($v['publication']['startedAt']);

                $goods_shop = GoodsShop::find()->where(['shop_id' => $shop_id, 'cgoods_no' => $goods['cgoods_no']])->one();
                if (empty($goods_shop)) {
                    if ($add_time < time() - 11 * 30 * 24 * 60 * 60) {
                        $del_ids[] = $v['id'];
                        continue;
                    } else {
                        try {
                            (new GoodsService())->claim($goods['goods_no'], [$shop_id], GoodsService::SOURCE_METHOD_OWN, [
                                'is_sync' => false,
                            ]);
                            $goods_shop = GoodsShop::find()->where(['shop_id' => $shop_id, 'cgoods_no' => $goods['cgoods_no']])->one();
                            echo "claim goods" . $goods['goods_no'] . "\n";
                        } catch (\Exception $e) {
                            echo "error_claim goods" . $goods['goods_no'] . "\n";
                        }
                    }
                }

                $data = ['add_time' => $add_time];
                if (empty($goods_shop->platform_goods_id)) {
                    $data['platform_goods_id'] = (string)$v['id'];
                }
                GoodsShop::updateAll($data, ['id' => $goods_shop['id']]);
                echo $v['id'] . ' ' . $goods['cgoods_no'] . "\n";
            }

            if (!empty($del_ids)) {
                do {
                    $top_100 = array_slice($del_ids, 0, 100);
                    $del_ids = array_slice($del_ids, 100);
                    var_dump($top_100);
                    $resi = $api_service->updateStockToId($top_100, 0);
                    var_dump($resi);
                } while (count($del_ids) > 0);
            }

            $page++;
        }
    }

    /**
     * 更新allegro商品id
     */
    public function actionUpdateAgGoodsId($shop_id, $page = 1)
    {
        $shop = Shop::findOne($shop_id);
        if ($shop['platform_type'] != Base::PLATFORM_ALLEGRO) {
            return;
        }
        $limit = 1000;
        $api_service = FApiService::factory($shop);
        while (true) {
            echo $page . "\n";
            $order_lists = $api_service->getProductsToStatus('ACTIVE', $page, $limit);
            if (empty($order_lists)) {
                break;
            }

            foreach ($order_lists as $v) {
                /*$goods = GoodsChild::find()->where(['sku_no' => $v['external']['id']])->asArray()->one();
                if (empty($goods)) {
                    continue;
                }

                $goods_shop = GoodsShop::find()->where(['shop_id' => $shop_id, 'cgoods_no' => $goods['cgoods_no']])->one();*/
                $goods_shop = GoodsShop::find()->where(['shop_id' => $shop_id, 'platform_sku_no' => $v['external']['id']])->one();
                if (empty($goods_shop) || !empty($goods_shop->platform_goods_id)) {
                    continue;
                }

                $goods_shop->platform_goods_id = (string)$v['id'];
                $goods_shop->save();
                echo $v['id'] . ' ' . $goods_shop['cgoods_no'] . "\n";
            }
            $page++;
        }

        $page = 1;
        while (true) {
            echo $page . "\n";
            $order_lists = $api_service->getProductsToStatus('ENDED', $page, $limit);
            if (empty($order_lists)) {
                break;
            }

            foreach ($order_lists as $v) {
                /*$goods = GoodsChild::find()->where(['sku_no' => $v['external']['id']])->asArray()->one();
                if (empty($goods)) {
                    continue;
                }

                $goods_shop = GoodsShop::find()->where(['shop_id' => $shop_id, 'cgoods_no' => $goods['cgoods_no']])->one();*/
                $goods_shop = GoodsShop::find()->where(['shop_id' => $shop_id, 'platform_sku_no' => $v['external']['id']])->one();
                if (empty($goods_shop) || !empty($goods_shop->platform_goods_id)) {
                    continue;
                }

                $goods_shop->platform_goods_id = (string)$v['id'];
                $goods_shop->save();
                echo $v['id'] . ' ' . $goods_shop['cgoods_no'] . "\n";
            }
            $page++;
        }
    }

    /**
     * 删除allegro未成功商品
     * @param $shop_id
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\base\Exception
     * @throws \yii\db\StaleObjectException
     */
    public function actionDelAgGoods($shop_id)
    {
        $shop = Shop::findOne($shop_id);
        if ($shop['platform_type'] != Base::PLATFORM_ALLEGRO) {
            return;
        }

        $goods_nos = GoodsEvent::find()->where(['shop_id'=>$shop_id,'event_type'=>GoodsEvent::EVENT_TYPE_ADD_GOODS,'status'=>[0,10]])->select('goods_no')->column();

        $shop = Shop::find()->where(['id' => $shop_id])->asArray()->one();
        $platform_type = $shop['platform_type'];
        $goods_platform_class = FGoodsService::factory($platform_type);
        $platform_class = $goods_platform_class->model();

        while (true) {
            $goods_shop = GoodsShop::find()->where([
                'shop_id' => $shop_id,
                'platform_goods_id' => '',
            ])->limit(10000)->all();
            if (empty($goods_shop)) {
                break;
            }

            foreach ($goods_shop as $v) {

                if(in_array($v['goods_no'],$goods_nos)){
                    continue;
                }

                $goods_no = $v->goods_no;
                echo $goods_no."\n";
                if ($v->delete()) {
                    $goods_model = GoodsShop::findOne(['platform_type' => $platform_type, 'goods_no' => $goods_no]);
                    if (empty($goods_model)) {
                        $main_goods_model = $platform_class->findOne(['goods_no' => $goods_no]);
                        $main_goods_model->delete();
                    }
                }
            }

            if(count($goods_shop) < 10000){
                break;
            }
        }

    }

    /**
     * 获取失败 allegro商品
     */
    public function actionGetAgInactive($shop_id, $page = 1)
    {
        $shop = Shop::findOne($shop_id);
        if ($shop['platform_type'] != Base::PLATFORM_ALLEGRO) {
            return;
        }
        $limit = 1000;
        $api_service = FApiService::factory($shop);
        while (true) {
            echo $page . "\n";
            $order_lists = $api_service->getProductsToStatus('INACTIVE', $page, $limit);
            $page ++;
            if (empty($order_lists)) {
                break;
            }
            foreach ($order_lists as $v) {
                $goods = GoodsChild::find()->where(['sku_no' => $v['external']['id']])->asArray()->one();
                if (empty($goods)) {
                    continue;
                }

                $goods_shop = GoodsShop::find()->where(['shop_id' => $shop_id, 'cgoods_no' => $goods['cgoods_no']])->one();
                if (empty($goods_shop) || !empty($goods_shop['platform_goods_id'])) {
                    continue;
                }

                echo $v['external']['id'] . "\n";
            }
        }
    }

    /**
     * 获取类别
     */
    public function actionGetCategory()
    {
        $platform_type = Base::PLATFORM_ALLEGRO;
        $shop = Shop::find()->where(['platform_type' => $platform_type,'status'=>Shop::STATUS_VALID])->asArray()->one();
        $api_service = FApiService::factory($shop);
        $this->addCategory($api_service,'','');
    }

    public function addCategory($api_service,$pid,$p_name = '')
    {
        if(in_array($pid,[3,'1429','4bd97d96-f0ff-46cb-a52c-2992bd972bb1','a408e75a-cede-4587-8526-54e9be600d9f'
        ,'38d588fd-7e9c-4c42-a4ae-6831775eca45','42540aec-367a-4e5e-b411-17c09b08e41f'])){
            return ;
        }
        $sp = ' / ';
        $platform_type = Base::PLATFORM_ALLEGRO;
        try {
            $result = $api_service->getCategory($pid);
        } catch (\Exception $e) {
            $result = $api_service->getCategory($pid);
        }
        foreach ($result as $v1) {
            $name1 = empty($v1['name']) ? '' : $v1['name'];
            if (empty($p_name)) {
                $crumb = $name1;
            } else {
                $crumb = $p_name . $sp . $name1;
            }
            $code1 = $v1['id'];
            $pl_cate = PlatformCategory::find()->where(['id' => $code1, 'platform_type' => $platform_type])->one();
            if (empty($pl_cate)) {
                $pl_cate = new PlatformCategory();
                $pl_cate->platform_type = $platform_type;
                $pl_cate->id = (string)$code1;
                $pl_cate->parent_id = (string)$pid;
                $pl_cate->name = $name1;
                $pl_cate->crumb = $crumb;
                $pl_cate->status = 1;
                $pl_cate->save();
            }else{
                $pl_cate->status = 1;
                $pl_cate->save();
            }
            echo $code1. ',' .$crumb ."\n";
            if(!$v1['leaf']) {
                $this->addCategory($api_service, $code1, $crumb);
            }
        }
    }

    /**
     * 获取类别字段
     */
    public function actionGetCategoryField($limit = 1,$category_id = null)
    {
        $platform_type = Base::PLATFORM_ALLEGRO;
        $where = ['platform_type' => $platform_type];
        if(!is_null($category_id)){
            $where['id']  = $category_id;
        }else{
            $where['status'] = 1;
        }
        $platform_category = PlatformCategory::find()->where($where)
            ->offset(1000 * ($limit - 1))->limit(1000)->all();
        $shop = Shop::find()->where(['platform_type' => $platform_type,'status'=>Shop::STATUS_VALID])->asArray()->one();
        $api_service = FApiService::factory($shop);
        foreach ($platform_category as $category) {
            $category_id = (string)$category['id'];
            $par_category = PlatformCategory::find()->where(['parent_id' => $category_id])->exists();
            if (!empty($par_category)) {
                $category->status = 2;
                $category->save();
                continue;
            }
            try {
                $api_result = $api_service->getCategoryProductParameters($category_id);
                if (empty($api_result) || empty($api_result['parameters'])) {
                    $category->status = 3;
                    $category->save();
                    continue;
                }
                $attribute_ids = PlatformCategoryField::find()->where(['platform_type' => $platform_type, 'category_id' => $category_id])->select('attribute_id,id')->asArray()->all();
                $attribute_ids = ArrayHelper::map($attribute_ids,'attribute_id','id');
                foreach ($api_result['parameters'] as $api_v) {
                    $attribute_id = (string)$api_v['id'];
                    $required = ($api_v['required'] ? 1 : 0) + ($api_v['requiredForProduct'] ? 1 : 0);
                    $param = [
                        'options' => $api_v['options'],
                        'restrictions' => $api_v['restrictions'],
                    ];
                    if (empty($attribute_ids[$attribute_id])) {
                        $pl_cate = new PlatformCategoryField();
                    }else{
                        $pl_cate = PlatformCategoryField::find()->where(['id'=>$attribute_ids[$api_v['id']]])->one();
                    }
                    $pl_cate->platform_type = $platform_type;
                    $pl_cate->category_id = $category_id;
                    $pl_cate->attribute_id = $attribute_id;
                    $pl_cate->attribute_name = (string)$api_v['name'];
                    $pl_cate->attribute_type = (string)$api_v['type'];
                    $pl_cate->is_required = $required;
                    $pl_cate->is_multiple = !empty($api_v['restrictions']) && !empty($api_v['restrictions']['multipleChoices']) ? 1 : 0;
                    $pl_cate->dictionary_id = '';
                    $pl_cate->unit = empty($api_v['unit']) ? '' : $api_v['unit'];
                    $pl_cate->param = json_encode($param, JSON_UNESCAPED_UNICODE);
                    $pl_cate->status = 0;
                    $pl_cate->save();

                    /*
                    PlatformCategoryFieldValue::setPlatform(23);
                    $attribute_value_id = PlatformCategoryFieldValue::find()->where([
                        'platform_type' => $platform_type, 'category_id' => $category_id,'attribute_id'=>$attribute_id,'dictionary_id'=>''
                    ])->select('attribute_value_id,attribute_value_cn')->asArray()->all();
                    $attribute_value_id = ArrayHelper::map($attribute_value_id,'attribute_value_id','attribute_value_cn');

                    PlatformCategoryFieldValue::setPlatform(231);
                    if(!empty($api_v['dictionary'])) {
                        $data = [];
                        foreach ($api_v['dictionary'] as $api_val_v) {
                            $data[] = [
                                'platform_type' => $platform_type,
                                'category_id' => $category_id,
                                'dictionary_id' =>'',
                                'attribute_id' =>$attribute_id,
                                'attribute_value_id' =>(string)$api_val_v['id'],
                                'attribute_value' =>(string)$api_val_v['value'],
                                'attribute_value_cn' =>  empty($attribute_value_id[$api_val_v['id']])?'':$attribute_value_id[$api_val_v['id']],
                                'status'=>0,
                                'add_time' => time(),
                                'update_time' => time(),
                            ];
                        }
                        $add_columns = [
                            'platform_type',
                            'category_id',
                            'dictionary_id',
                            'attribute_id',
                            'attribute_value_id',
                            'attribute_value',
                            'attribute_value_cn',
                            'status',
                            'add_time',
                            'update_time'
                        ];
                        PlatformCategoryFieldValue::getDb()->createCommand()->batchIgnoreInsert(PlatformCategoryFieldValue::tableName(), $add_columns, $data)->execute();
                    }*/
                }

                $category->status = 2;
                $category->save();
            } catch (\Exception $e) {
                $category->status = 3;
                $category->save();
                echo  $e->getMessage(). "\n";
            }
            echo $category_id . "\n";
        }
    }

    public function actionUpdateAllegroGoods()
    {
        while (true) {
            $goods_shop_lists = GoodsShop::find()->where(['!=', 'platform_goods_id', ''])
                ->andWhere(['platform_goods_opc' => '', 'platform_type' => Base::PLATFORM_ALLEGRO])
                ->limit(50)->all();
            if (empty($goods_shop_lists)) {
                return;
            }

            foreach ($goods_shop_lists as $goods_v) {
                $shop = Shop::findOne($goods_v['shop_id']);

                $api_service = FApiService::factory($shop);
                $goods = Goods::find()->where(['goods_no'=>$goods_v['goods_no']])->one();
                $goods_allegro = GoodsAllegro::find()->where(['goods_no'=>$goods_v['goods_no']])->one();
                $category_id = $goods_allegro['o_category_name'];

                $result = $api_service->getOffer($goods_v['platform_goods_id']);
                if (empty($result['id'])) {
                    $goods_v['platform_goods_opc'] = '-1';
                    $goods_v->save();
                    echo $goods_v['shop_id'] . ',' . $goods_v['goods_no'] . ', error1' . "\n";
                    continue;
                }

                $product_id = $result['product']['id'];
                if (!empty($product_id)) {
                    $goods_v['platform_goods_opc'] = $product_id;
                    $goods_v->save();
                    echo $goods_v['shop_id'] . ',' . $goods_v['goods_no'] . ', error2' . "\n";
                    continue;
                }
                $category_id = $result['category']['id'];
                $status = $result['publication']['status'];

                $cate_result = $api_service->getCategoryInfo($category_id);
                if(!empty($cate_result) && !empty($cate_result['options']) && $cate_result['options']['productCreationEnabled'] == false) {
                    if ($status == 'ACTIVE') {
                        $goods_v['platform_goods_opc'] = '1';
                    } else {
                        $goods_v['platform_goods_opc'] = '-1';
                    }
                    $goods_v->save();
                    echo $goods_v['shop_id'] . ',' . $goods_v['goods_no'] . ', error2' . "\n";
                    continue;
                }

                $data = [];
                $name = str_replace(['（','）'],['(',')'],$result['name']);
                $name = CommonUtil::filterTrademark($name);
                $data['name'] = $name;
                $data['category']['id'] = $category_id;

                $images = [];
                foreach ($result['images'] as $v) {
                    $images[] = ['url' => $v];
                }
                $data['images'] = $images;
                $data['description'] = $result['description'];

                $params = [];

                $map = [
                    'Artist' => 'Unidentified',
                    'Author' => 'Other',
                    'Battery Manufacturer' => 'Tanks001',
                    'Battery Symbol' => 'Unidentified',
                    'Catalog number for part' => 'ROY001',
                    'Chipset' => 'Unidentified',
                    'Collection' => 'Unidentified',
                    'Compatible server' => 'other',
                    'Compatible with' => 'Unidentified',
                    'Country of Origin' => 'Unidentified',
                    'Dedicated Model' => 'Unidentified',
                    'Dimensions' => '1',
                    'Edition' => 'Unidentified',
                    'ISBN' => 'Undefined',
                    'ISSN' => '1',
                    'Item Name' => 'Other',
                    'Language' => 'Other',
                    'Line' => '1',
                    'Manufacturer' => 'Evacuators001',
                    'Manufacturer catalog number' => 'Airconditioning001',
                    'Manufacturer Color Name' => 'Other Color',
                    'Manufacturer\'s Color' => 'Other Color',
                    'Model' => 'Couches001',
                    'Name' => 'Other',
                    'Operator' => 'Other',
                    'Processor Model' => '1',
                    'Product Number' => 'Eau de toilette01',
                    'Publisher' => 'Unidentified',
                    'Purpose' => 'Other',
                    'Scent' => 'Unidentified',
                    'Series' => 'None',
                    'Service Name' => 'Undefined',
                    'Size' => '1',
                    'Symbol' => 'Unidentified',
                    'Theme' => '1',
                    'Title' => 'Other',
                    'Trade Name' => 'Undefined',
                    'Tytuł' => 'Other',
                    'Publication year' => 2020,

                    'Character'=> 'None',
                ];
                $category_product_params = $api_service->getCategoryProductParameters($category_id);
                $map_names = [
                    'Headphones Type'=>'in-ear-canal',
                    'Size'=>'Small (smaller than A4)',
                ];

                $brand_name = '';
                if (!empty($shop['brand_name'])) {
                    $brand_name = explode(',', $shop['brand_name']);
                    $brand_name = (array)$brand_name;
                    shuffle($brand_name);
                    $brand_name = current($brand_name);
                }

                //随机选择值
                $category_maps = [
                    259434 => [18163],
                    1491 => [203925],
                    49128 => [204737],
                    123449 => [129129],
                    112275 => [236386],
                    259813 => [24232],
                    259431 => [204829],
                    91482 => [75,223541],
                    258348 => [204737],
                    258163 => [211378],
                    257008 => [24234],
                    13421 => [236674],
                    257357 => [128551],
                    148085 => [243361],
                    301094 => [345],
                    10532 => [207786],
                    16430 => [130289],
                ];
                $category_map = !empty($category_maps[$category_id])?$category_maps[$category_id]:[];
                $no_exist_11323 = [261417];
                //var_dump($goods_allegro['o_category_name']);
                //var_dump($category_product_params);exit();
                foreach ($category_product_params['parameters'] as $v) {
                    $params_info = [
                        'id' => $v['id'],
                    ];
                    if (!empty($v['unit'])) {
                        //$params_info['unit'] = $v['unit'];
                    }
                    if ($v['type'] == 'dictionary') {
                        $params_info['valuesIds'] = [end($v['dictionary'])['id']];
                    }

                    //颜色匹配
                    if($v['type'] == 'Color'){
                        $exist = false;
                        foreach ($v['dictionary'] as $color_v){
                            if(ucfirst($color_v['value']) == $goods['colour']){
                                $params_info['valuesIds'] = [$color_v['id']];
                                $exist = true;
                                break;
                            }
                        }
                        if($exist){
                            $params[] = $params_info;
                            continue;
                        }
                        if(!empty($goods['colour'])){
                            $params_info['values'] = [$goods['colour']];
                            $params[] = $params_info;
                            continue;
                        }
                    }

                    $exist = false;
                    foreach ($map_names as $map_key => $map_name) {
                        if ($v['name'] == $map_key) {
                            $exist_id = false;
                            foreach ($v['dictionary'] as $dict_v) {
                                if ($dict_v['value'] == $map_name) {
                                    $params_info['valuesIds'] = [$dict_v['id']];
                                    $exist = true;
                                    $exist_id = true;
                                    break;
                                }
                            }
                            if(!$exist_id) {
                                $params_info['values'] = $map_name;
                                $exist = true;
                            }
                            break;
                        }
                    }
                    if($exist){
                        $params[] = $params_info;
                        continue;
                    }

                    $exist = false;
                    foreach ($category_map as $map_v) {
                        if ($v['id'] == $map_v) {
                            $cun = count($v['dictionary']);
                            if($cun > 2) {
                                $ran_i = rand(0, $cun - 2);
                                $params_info['valuesIds'] = [$v['dictionary'][$ran_i]['id']];
                                $exist = true;
                                break;
                            }
                        }
                    }
                    if($exist){
                        $params[] = $params_info;
                        continue;
                    }

                    //$restrictions = $v['restrictions'];
                    //品牌
                    if ($v['name'] == 'Brand') {
                        $params_info['values'] = [$brand_name];
                        $params[] = $params_info;
                        continue;
                    }

                    if ($v['name'] == 'EAN') {
                        $params_info['values'] = [$goods_v['ean']];
                        $params[] = $params_info;
                        continue;
                    }

                    if ($v['name'] == 'Product Code') {
                        $params_info['values'] = [$result['external']['id']];
                        $params[] = $params_info;
                        continue;
                    }

                    if ($v['name'] == 'Manufacturer Code') {
                        $params_info['values'] = [$result['external']['id']];
                        $params[] = $params_info;
                        continue;
                    }

                    if ($v['name'] == 'Product Weight') {
                        if ($v['unit'] == 'kg') {
                            $params_info['values'] = [
                                empty($goods['weight']) ? '0.1' : $goods['weight']
                            ];
                            $params[] = $params_info;
                            continue;
                        }
                    }

                    //其他不是必填跳过
                    if (empty($v['required'])) {
                        continue;
                    }

                    $exist = false;
                    foreach ($map as $map_k => $map_v) {
                        if ($v['name'] == $map_k) {
                            $exist = true;
                            $params_info['values'] = [$map_v];
                            break;
                        }
                    }
                    if ($exist) {
                        $params[] = $params_info;
                        continue;
                    }

                    $restrictions = $v['restrictions'];
                    switch ($v['type']) {
                        case 'dictionary':
                            $dictionary = end($v['dictionary']);
                            $params_info['valuesIds'] = [$dictionary['id']];
                            //$params_info['values'] = [$dictionary['value']];
                            $params[] = $params_info;
                            break;
                        case 'integer':
                            $val = 1;
                            if ($restrictions['range']) {
                                $val = $restrictions['min'];
                                //$desc = '在'.$restrictions['min'].'~'.$restrictions['max'].'之间';
                            }
                            $params_info['values'] = [$val];
                            $params[] = $params_info;
                            break;
                        case 'string':
                            //$desc = '最小长度：'.$restrictions['minLength'].' 最大长度：'.$restrictions['maxLength'].' 允许提供类型：'.$restrictions['allowedNumberOfValues'];
                            $params_info['values'] = ['1'];
                            $params[] = $params_info;
                            break;
                        case 'float':
                            $val = 0.1;
                            if ($restrictions['range']) {
                                //$desc = '在'.$restrictions['min'].'~'.$restrictions['max'].'之间 ';
                                $val = $restrictions['min'];
                            }
                            //$desc .= '小数位数:'.$restrictions['precision'];
                            $params_info['values'] = [$val];
                            $params[] = $params_info;
                            break;
                    }
                }


                if(!in_array($category_id,$no_exist_11323)) {
                    //status new
                    $params[] = [
                        'id' => '11323',
                        'valuesIds' => ['11323_1']
                    ];
                }

                $data['parameters'] = $params;

                //先发布产品
                $response = $api_service->getClient()->post('/sale/product-proposals', [
                    'json' => $data,
                    'http_errors' => false,
                    'curl' => [
                        CURLOPT_HTTPHEADER => [
                            'Content-Type:application/vnd.allegro.public.v1+json',
                            'Accept:application/vnd.allegro.public.v1+json',
                            'Authorization:Bearer ' . $api_service->refreshToken(),
                            'Accept-Language:en-US',
                        ]
                    ]
                ]);
                $product = $response->getBody()->getContents();
                $product = json_decode($product, true);
                if (!empty($product['errors']) || empty($product['id'])) {
                    CommonUtil::logs('allegro add_goods pro error id:' . $goods_v['shop_id'] . ',' . $goods_v['goods_no'] . ' data:' . json_encode($data) . ' result:' . json_encode($product), 'add_allegro');

                    if($status == 'ACTIVE'){
                        $goods_v['platform_goods_opc'] = '1';
                    }else {
                        $goods_v['platform_goods_opc'] = '-1';
                    }
                    $goods_v->save();
                    echo $goods_v['shop_id'] . ',' . $goods_v['goods_no'] . ', error4' . "\n";
                    continue;
                }

                $data = [];
                $data['product'] = [
                    'id' => (string)$product['id']
                ];
                $response = $api_service->getClient()->patch('sale/product-offers/' . $goods_v['platform_goods_id'], [
                    'json' => $data,
                    'http_errors' => false,
                    'curl' => [
                        CURLOPT_HTTPHEADER => [
                            'Content-Type:application/vnd.allegro.beta.v2+json',
                            'Accept:application/vnd.allegro.beta.v2+json',
                            'Authorization:Bearer ' . $api_service->refreshToken(),
                        ]
                    ]
                ]);
                $result = $response->getBody()->getContents();
                $result = json_decode($result, true);
                if (!empty($result['errors']) || empty($result['id'])) {
                    if($status == 'ACTIVE'){
                        $goods_v['platform_goods_opc'] = '1';
                    }else {
                        $goods_v['platform_goods_opc'] = '-1';
                    }
                    $goods_v->save();
                    echo $goods_v['shop_id'] . ',' . $goods_v['goods_no'] . ', error3' . "\n";
                    continue;
                }

                $goods_v->platform_goods_opc = (string)$product['id'];
                $goods_v->save();

                //上架商品
                $api_service->updateStockToId($goods_v['platform_goods_id'], true);

                echo $goods_v['shop_id'] . ',' . $goods_v['goods_no'] . ',' . $product['id'] . "\n";
            }
        }

    }

    /**
     * 添加海外仓商品
     * @return void
     */
    public function actionAddOverseasGoods($shop_id,$warehouse_id = 4)
    {
        $goods_shop_list = GoodsShop::find()->where(['shop_id' => $shop_id])->all();
        foreach ($goods_shop_list as $v) {
            $goods_shop_overseas = GoodsShopOverseasWarehouse::find()->where(['goods_shop_id' => $v['id']])->one();
            if (!empty($goods_shop_overseas)) {
                continue;
            }

            $goods_shop_overseas = new GoodsShopOverseasWarehouse();
            $goods_shop_overseas->goods_shop_id = $v['id'];
            $goods_shop_overseas->shop_id = $v['shop_id'];
            $goods_shop_overseas->platform_type = $v['platform_type'];
            $goods_shop_overseas->cgoods_no = $v['cgoods_no'];
            $goods_shop_overseas->warehouse_id = $warehouse_id;
            $bl_goods = BlContainerGoods::find()->where(['warehouse_id'=>$warehouse_id,'cgoods_no'=>$v['cgoods_no']])->orderBy('add_time desc')->one();
            $goods_shop_overseas->estimated_start_logistics_cost = !empty($bl_goods['price'])?$bl_goods['price']:0;
            $goods_shop_overseas->save();
            echo $v['cgoods_no'] ."\n";
        }
    }

}