<?php
namespace console\controllers\api;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsWalmart;
use common\models\GoodsEvent;
use common\models\GoodsShop;
use common\models\Shop;
use common\services\api\GoodsEventService;
use common\services\FApiService;
use common\services\goods\GoodsService;
use yii\console\Controller;
use yii\helpers\ArrayHelper;

class WalmartController extends Controller
{

    public function actionTest()
    {
        $shop = Shop::findOne(366);
        $api_service = FApiService::factory($shop);
        $result = $api_service->getQueue('D6CC52B098B14A2FBC7436FECD7F9AFF@AXkBCgA');
        echo CommonUtil::jsonFormat($result);
        exit();
    }

    /**
     * 批量添加商品
     */
    public function actionBatchAddGoods()
    {
        $shop_lists = Shop::find()->where(['platform_type' => Base::PLATFORM_WALMART])->asArray()->all();
        foreach ($shop_lists as $shop) {
            if (empty($shop['client_key'])) {
                continue;
            }

            $order_event_lists = GoodsEvent::find()->where([
                'status' => GoodsEvent::STATUS_WAIT_RUN,
                'platform' => $shop['platform_type'],
                'shop_id' => $shop['id'],
                'event_type' => [GoodsEvent::EVENT_TYPE_ADD_GOODS]
            ])->limit(10000)->asArray()->all();
            if (empty($order_event_lists)) {
                continue;
            }
            $goods_nos = ArrayHelper::getColumn($order_event_lists, 'goods_no');
            $category_lists = GoodsWalmart::find()->where(['goods_no'=>$goods_nos])->select('goods_no,o_category_name')->asArray()->all();
            $o_category_name = '';
            $run_goods_no = [];
            $i = 0;
            foreach ($category_lists as $cate_v) {
                if($i > 1000){
                    break;
                }
                if (empty($o_category_name)) {
                    $run_goods_no[] = $cate_v['goods_no'];
                    $o_category_name = $cate_v['o_category_name'];
                    $i ++;
                    continue;
                }
                if (trim($cate_v['o_category_name']) == trim($o_category_name)) {
                    $run_goods_no[] = $cate_v['goods_no'];
                    $i ++;
                }
            }

            $run_order_event_lists = [];
            foreach ($order_event_lists as $order_event_v){
                if(in_array($order_event_v['goods_no'],$run_goods_no)){
                    $run_order_event_lists[] = $order_event_v;
                }
            }
            unset($order_event_lists);

            $ids = ArrayHelper::getColumn($run_order_event_lists, 'id');
            $goods = Goods::find()->where(['goods_no' => $run_goods_no])->indexBy('goods_no')->asArray()->all();
            $cgoods_nos = ArrayHelper::getColumn($run_order_event_lists, 'cgoods_no');
            $good_child_lists = GoodsChild::find()->where(['cgoods_no' => $cgoods_nos])->indexBy('cgoods_no')->asArray()->all();
            $good_lists = [];
            foreach ($good_child_lists as $goods_child) {
                if (empty($goods[$goods_child['goods_no']])) {
                    continue;
                }
                $good_lists[] = (new GoodsService())->dealGoodsInfo($goods[$goods_child['goods_no']], $goods_child);
            }
            GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_RUNNING], ['id' => $ids]);

            try {
                $api_service = FApiService::factory($shop);
                $result = $api_service->batchAddGoods($good_lists,$o_category_name);
                if (empty($result)) {
                    GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE], ['id' => $ids]);
                } else {
                    GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_RUNNING_RESULT, 'queue_id' => $result,'plan_time'=>time() + 30*60], ['id' => $ids]);
                }
            } catch (\Exception $e) {
                CommonUtil::logs('error: shop_id:' . $shop['id'] . ' platform_type:' . $shop['platform_type'] . $e->getMessage(), 'walmart_goods_api_error');
                GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE, 'error_msg' => $e->getMessage()], ['id' => $ids]);
                continue;
            }
        }

    }

    /**
     * 批量查询队列
     */
    public function actionBatchQueue()
    {
        $shop_lists = Shop::find()->where(['platform_type'=>Base::PLATFORM_WALMART])->asArray()->all();
        foreach ($shop_lists as $shop) {
            if (empty($shop['client_key'])){
                continue;
            }

            $i = 0;
            while (true) {
                $i++;
                if ($i > 50) {
                    break;
                }

                $order_event_lists = GoodsEvent::find()->where([
                    'status' => GoodsEvent::STATUS_RUNNING_RESULT,
                    'platform' => $shop['platform_type'],
                    'shop_id' => $shop['id'],
                ])->andWhere(['<', 'plan_time', time()])->andWhere(['!=', 'queue_id', ''])->limit(1000)->all();
                if (empty($order_event_lists)) {
                    break;
                }
                $queue_ids = ArrayHelper::index($order_event_lists, null, 'queue_id');

                foreach ($queue_ids as $queue_id => $event) {
                    $ids = ArrayHelper::getColumn($event, 'id');
                    $cgoods_nos = ArrayHelper::map($event, 'cgoods_no', 'id');
                    GoodsEvent::updateAll(['plan_time' => time() + 30 * 60], ['id' => $ids]);
                    try {
                        $api_service = FApiService::factory($shop);
                        $result = $api_service->getQueue($queue_id);
                        if ($result == -1) {
                            continue;
                        }

                        if (empty($result)) {
                            GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE], ['id' => $ids]);
                        } else {
                            //成功
                            if($result['itemsSucceeded'] > 0 && $result['itemsReceived'] == $result['itemsSucceeded']) {
                                foreach ($event as $ev_v) {
                                    GoodsEventService::addEvent($ev_v, GoodsEvent::EVENT_TYPE_UPDATE_STOCK, time() + 60 * 60);
                                }
                                GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_SUCCESS],['id' => $ids]);
                                continue;
                            }

                            foreach ($result['itemDetails']['itemIngestionStatus'] as $result_v) {
                                if (empty($result_v)) {
                                    continue;
                                }

                                $sku = $result_v['sku'];
                                $goods_child = GoodsChild::find()->where(['sku_no' => $sku])->one();
                                $cgoods_no = $goods_child['cgoods_no'];
                                if(empty($cgoods_nos[$cgoods_no])){
                                    continue;
                                }

                                if($result_v['ingestionStatus']=='SUCCESS') {
                                    foreach ($event as $ev_v) {
                                        GoodsEventService::addEvent($ev_v, GoodsEvent::EVENT_TYPE_UPDATE_STOCK, time() + 60 * 60);
                                    }
                                    GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_SUCCESS],
                                        ['id' => $cgoods_nos[$cgoods_no]]);
                                } else if($result_v['ingestionStatus']=='DATA_ERROR') {
                                    $log_message = '';
                                    if(!empty($result_v['ingestionErrors']['ingestionError'])) {
                                        $re_run = false;
                                        foreach ($result_v['ingestionErrors']['ingestionError'] as $error) {
                                            if ($error['code'] == 'ERR_EXT_DATA_0101149' && $error['field'] == 'mainImageUrl') {//图片错误重新执行
                                                $re_run = true;
                                                break;
                                            }
                                            $log_message .= $error['description'] .' ';
                                        }
                                        if ($re_run) {
                                            GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_WAIT_RUN], ['id' => $cgoods_nos[$cgoods_no]]);
                                            continue;
                                        }
                                    }
                                    GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE, 'error_msg' => 'queue:'.$log_message],
                                        ['id' => $cgoods_nos[$cgoods_no]]);
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        CommonUtil::logs('queue error: shop_id:' . $shop['id'] . ' platform_type:' . $shop['platform_type'] . $e->getMessage(), 'add_wa_products_error');
                        GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE, 'error_msg' => 'queue:'.$e->getMessage()], ['id' => $ids]);
                        continue;
                    }
                }
            }
        }
    }

}