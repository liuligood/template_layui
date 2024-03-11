<?php
namespace console\controllers\api;

use common\components\CommonUtil;
use common\components\HelperStamp;
use common\components\statics\Base;
use common\models\GoodsEvent;
use common\models\GoodsShop;
use common\models\Shop;
use common\services\FApiService;
use yii\console\Controller;
use yii\helpers\ArrayHelper;

/**
 * RDC Eprice worten
 */
class MiraklController extends Controller
{

    public function actionTest()
    {
        $shop = Shop::findOne(121);
        $api_service = FApiService::factory($shop);

        //$result = $api_service->getProducts('8395836216841');
        $result = $api_service->getOffers('8395836216841');
        //$result = $api_service->updateListings(0,true,11.1);
        echo CommonUtil::jsonFormat($result);
        exit();
    }

    /**
     * 批量处理更新库存
     */
    public function actionBatchUpdateStock()
    {
        $where = ['platform_type' => [Base::PLATFORM_RDC,Base::PLATFORM_WORTEN,Base::PLATFORM_EPRICE]];
        $where['api_assignment'] = (new HelperStamp(Shop::$api_assignment_maps))->getStamps(Shop::GOODS_ASSIGNMENT);
        $shop_lists = Shop::find()->where($where)->asArray()->all();
        foreach ($shop_lists as $shop) {
            if (empty($shop['client_key'])) {
                continue;
            }

            $event_type = GoodsEvent::find()->where([
                'status' => GoodsEvent::STATUS_WAIT_RUN,
                'platform' => $shop['platform_type'],
                'shop_id' => $shop['id'],
            ])->groupBy('event_type')->select('event_type')->column();
            if (in_array(GoodsEvent::EVENT_TYPE_UPDATE_STOCK, $event_type) || in_array(GoodsEvent::EVENT_TYPE_UPDATE_PRICE, $event_type)) {
                $this->batchUpdateStock($shop);
            }

            if (in_array(GoodsEvent::EVENT_TYPE_DEL_GOODS, $event_type)) {
                $this->batchDel($shop);
            }
        }
    }

    /**
     * 更新库存
     * @param $shop
     * @throws \yii\base\Exception
     */
    public function batchUpdateStock($shop)
    {
        while (true) {
            $order_event_lists = GoodsEvent::find()->where([
                'status' => GoodsEvent::STATUS_WAIT_RUN,
                'platform' => $shop['platform_type'],
                'shop_id' => $shop['id'],
                'event_type' => [GoodsEvent::EVENT_TYPE_UPDATE_STOCK, GoodsEvent::EVENT_TYPE_UPDATE_PRICE]
            ])->limit(1000)->all();
            if (empty($order_event_lists)) {
                break;
            }

            $ids = ArrayHelper::getColumn($order_event_lists, 'id');
            GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_RUNNING], ['id' => $ids]);
            $data = [];
            $api_service = FApiService::factory($shop);
            foreach ($order_event_lists as $v) {
                $goods_shop = GoodsShop::find()->where(['cgoods_no' => $v['cgoods_no'], 'shop_id' => $shop['id']])->one();
                $data[] = $api_service->getListingsData($goods_shop);
            }

            if (!empty($data)) {
                try {
                    $result = $api_service->updateListings($data);
                    if (empty($result)) {
                        GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE], ['id' => $ids]);
                    } else {
                        GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_SUCCESS], ['id' => $ids]);
                    }
                } catch (\Exception $e) {
                    CommonUtil::logs('error: shop_id:' . $shop['id'] . ' platform_type:' . $shop['platform_type'] . $e->getMessage(), 'mirakl_goods_api_error');
                    GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE, 'error_msg' => $e->getMessage()], ['id' => $ids]);
                }
            }
        }
    }

    /**
     * 删除商品
     * @param $shop
     * @throws \yii\base\Exception
     */
    public function batchDel($shop)
    {
        while (true) {
            $order_event_lists = GoodsEvent::find()->where([
                'status' => GoodsEvent::STATUS_WAIT_RUN,
                'platform' => $shop['platform_type'],
                'shop_id' => $shop['id'],
                'event_type' => [GoodsEvent::EVENT_TYPE_DEL_GOODS]
            ])->limit(1000)->all();
            if (empty($order_event_lists)) {
                break;
            }

            $ids = ArrayHelper::getColumn($order_event_lists, 'id');
            GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_RUNNING], ['id' => $ids]);
            $data = [];
            $api_service = FApiService::factory($shop);
            foreach ($order_event_lists as $v) {
                $goods_shop = GoodsShop::find()->where(['cgoods_no' => $v['cgoods_no'], 'shop_id' => $shop['id']])->one();
                $info = $api_service->getListingsData($goods_shop);
                $info['update_delete'] = 'delete';
                $data[] = $info;
            }

            if (!empty($data)) {
                try {
                    $result = $api_service->updateListings($data);
                    if (empty($result)) {
                        GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE], ['id' => $ids]);
                    } else {
                        GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_SUCCESS], ['id' => $ids]);
                    }
                } catch (\Exception $e) {
                    CommonUtil::logs('error: shop_id:' . $shop['id'] . ' platform_type:' . $shop['platform_type'] . $e->getMessage(), 'mirakl_goods_api_error');
                    GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE, 'error_msg' => $e->getMessage()], ['id' => $ids]);
                }
            }
        }
    }

}