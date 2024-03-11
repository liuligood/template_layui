<?php
namespace console\controllers\api;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\GoodsEvent;
use common\models\Shop;
use common\services\FApiService;
use common\services\goods\GoodsService;
use yii\console\Controller;
use yii\helpers\ArrayHelper;

class MicrosoftController extends Controller
{

    public function actionTest()
    {
        $shop = Shop::findOne(375);
        $api_service = FApiService::factory($shop);
        $result = $api_service->getProductsToStatus();
        echo CommonUtil::jsonFormat($result);
        exit();
    }

    public function actionGetQueue($shop_id,$queue_id)
    {
        $shop = Shop::findOne($shop_id);
        $api_service = FApiService::factory($shop);
        $result = $api_service->getQueue($queue_id);
        echo CommonUtil::jsonFormat($result);
        exit();
    }

    /**
     * 批量处理更新库存
     */
    public function actionBatchGoods()
    {
        $shop_lists = Shop::find()->where(['platform_type' => Base::PLATFORM_MICROSOFT])->asArray()->all();
        foreach ($shop_lists as $shop) {
            if (empty($shop['client_key'])) {
                continue;
            }
            $event_type = GoodsEvent::find()->where([
                'status' => GoodsEvent::STATUS_WAIT_RUN,
                'platform' => $shop['platform_type'],
                'shop_id' => $shop['id'],
            ])->groupBy('event_type')->select('event_type')->column();
            if (in_array(GoodsEvent::EVENT_TYPE_UPDATE_PRICE, $event_type)) {
                $this->batchDealGoods($shop, GoodsEvent::EVENT_TYPE_UPDATE_PRICE);
            }
            if (in_array(GoodsEvent::EVENT_TYPE_ADD_GOODS, $event_type)) {
                $this->batchDealGoods($shop, GoodsEvent::EVENT_TYPE_ADD_GOODS);
            }
        }
    }

    /**
     * 批量处理商品
     * @param $shop
     * @param $event_type
     * @return void
     */
    public function batchDealGoods($shop,$event_type)
    {
        $i = 0;
        while (true) {
            $i++;
            if ($i > 30) {
                break;
            }

            $order_event_lists = GoodsEvent::find()->where([
                'status' => GoodsEvent::STATUS_WAIT_RUN,
                'platform' => $shop['platform_type'],
                'shop_id' => $shop['id'],
                'event_type' => [$event_type]
            ])->limit(500)->all();
            if (empty($order_event_lists)) {
                break;
            }
            $ids = ArrayHelper::getColumn($order_event_lists, 'id');
            $goods_nos = ArrayHelper::getColumn($order_event_lists, 'goods_no');
            $goods = Goods::find()->where(['goods_no' => $goods_nos])->indexBy('goods_no')->asArray()->all();
            $cgoods_nos = ArrayHelper::getColumn($order_event_lists, 'cgoods_no');
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
                if ($event_type == GoodsEvent::EVENT_TYPE_ADD_GOODS) {
                    $result = $api_service->batchAddGoods($good_lists);
                }
                if ($event_type == GoodsEvent::EVENT_TYPE_UPDATE_PRICE) {
                    $result = $api_service->batchUpdateGoodsPrice($good_lists);
                }
                if (empty($result)) {
                    GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE], ['id' => $ids]);
                } else {
                    GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_RUNNING_RESULT, 'queue_id' => $result], ['id' => $ids]);
                }
            } catch (\Exception $e) {
                CommonUtil::logs('error: shop_id:' . $shop['id'] . ' platform_type:' . $shop['platform_type'] . $e->getMessage(), 'microsoft_goods_api_error');
                GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE, 'error_msg' => $e->getMessage()], ['id' => $ids]);
                continue;
            }
            if (count($order_event_lists) < 100) {
                break;
            }
        }
    }

    /**
     * 批量添加商品
     */
    public function actionBatchAddGoods()
    {
        $shop_lists = Shop::find()->where(['platform_type' => Base::PLATFORM_MICROSOFT])->asArray()->all();
        foreach ($shop_lists as $shop) {
            if (empty($shop['client_key'])) {
                continue;
            }

            $i = 0;
            while (true) {
                $i++;
                if ($i > 30) {
                    break;
                }

                $order_event_lists = GoodsEvent::find()->where([
                    'status' => GoodsEvent::STATUS_WAIT_RUN,
                    'platform' => $shop['platform_type'],
                    'shop_id' => $shop['id'],
                    'event_type' => [GoodsEvent::EVENT_TYPE_ADD_GOODS]
                ])->limit(500)->all();
                if (empty($order_event_lists)) {
                    break;
                }
                $ids = ArrayHelper::getColumn($order_event_lists, 'id');
                $goods_nos = ArrayHelper::getColumn($order_event_lists, 'goods_no');
                $goods = Goods::find()->where(['goods_no' => $goods_nos])->indexBy('goods_no')->asArray()->all();
                $cgoods_nos = ArrayHelper::getColumn($order_event_lists, 'cgoods_no');
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
                    $result = $api_service->batchAddGoods($good_lists);
                    if (empty($result)) {
                        GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE], ['id' => $ids]);
                    } else {
                        GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_RUNNING_RESULT, 'queue_id' => $result], ['id' => $ids]);
                    }
                } catch (\Exception $e) {
                    CommonUtil::logs('error: shop_id:' . $shop['id'] . ' platform_type:' . $shop['platform_type'] . $e->getMessage(), 'microsoft_goods_api_error');
                    GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE, 'error_msg' => $e->getMessage()], ['id' => $ids]);
                    continue;
                }
                if(count($order_event_lists) < 100){
                    break;
                }
            }
        }
    }

}