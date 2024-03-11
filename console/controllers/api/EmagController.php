<?php
namespace console\controllers\api;

use common\components\statics\Base;
use common\models\Order;
use common\models\Shop;
use common\services\FApiService;
use common\services\order\OrderService;
use yii\console\Controller;

class EmagController extends Controller
{


    /**
     * 订单状态
     * @return void
     */
    public function actionOrderStatus()
    {
        $where = [
            'source' => Base::PLATFORM_EMAG,
            'order_status' => [Order::ORDER_STATUS_FINISH]
        ];
        $order_lists = Order::find()->where($where)
            ->andWhere(['<', 'exec_status_time', time() - 15 * 24 * 60 * 60])
            ->andWhere(['>', 'add_time', time() - 6 * 30 * 24 * 60 * 60])
            ->all();
        foreach ($order_lists as $order) {
            $shop = Shop::findOne($order['shop_id']);
            $api_service = FApiService::factory($shop);
            try {
                $result = $api_service->getOrderInfo($order['relation_no']);
            } catch (\Exception $e) {
                echo '###' . $order['shop_id'] . "," . $order['order_id'] . ',' . $e->getMessage() . "\n";
                continue;
            }

            if (empty($result)) {
                continue;
            }

            $status = $api_service->getOrderStatus($result);
            if ($status === false) {
                continue;
            }

            if ($status == $order['order_status']) {
                continue;
            }

            //取消订单
            if ($status == Order::ORDER_STATUS_CANCELLED) {
                (new OrderService())->cancel($order['order_id'], 9, '系统自动取消');
                echo $order['order_id'] .',cancel'. "\n";
                continue;
            }

            //退款
            if ($status == Order::ORDER_STATUS_REFUND) {
                (new OrderService())->refund($order['order_id'], 109, '系统自动退款');
                echo $order['order_id'] .',refund'. "\n";
                continue;
            }

            echo $order['order_id'] ."\n";
        }
    }

}