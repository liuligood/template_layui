<?php


namespace console\controllers;

use common\models\Order;
use common\models\Shop;
use yii\console\Controller;
use Yii;
use yii\helpers\ArrayHelper;

class ShopController extends Controller
{
    /**
     * 店铺出单数量
     */
    public function actionOrderNumber()
    {
        $shops = Shop::find()->all();
        $shops_id = ArrayHelper::getColumn($shops,'id');
        $order_list = Order::find()->where(['shop_id' => $shops_id])
            ->select('count(*) as count,shop_id,max(add_time) as last_order_time')
            ->groupBy('shop_id')
            ->indexBy('shop_id')->asArray()->all();
        foreach ($shops as $v) {
            $order = empty($order_list[$v['id']]) ? '' : $order_list[$v['id']];
            if (empty($order)) {
                $v['sale_status'] = Shop::SALE_STATUS_ABNORMAL;
                $v->save();
                echo '店铺'.$v['name'].'执行成功！'."\n";
                continue;
            }
            $v['sale_status'] = Shop::SALE_STATUS_NORMAL;
            $v['order_num'] = $order['count'];
            $v['last_order_time'] = $order['last_order_time'];
            $last_sunday = strtotime('-1 sunday', time()) + 86399;
            $last_monday = strtotime(date('Y-m-d',$last_sunday).'-6 day',time());
            $last_seven_monday = strtotime(date('Y-m-d',time()).'-6 day',time());
            $last_week_order_count = Order::find()->where(['shop_id' => $v['id']])
                ->andWhere('add_time >='.$last_monday)
                ->andWhere('add_time <'.$last_sunday)
                ->count();
            $now_week_order_count = Order::find()->where(['shop_id' => $v['id']])
                ->andWhere('add_time >='.$last_seven_monday)
                ->andWhere('add_time <'.time())
                ->count();
            if ($now_week_order_count == 0 || ($last_week_order_count / 2) > $now_week_order_count) {
                $v['sale_status'] = Shop::SALE_STATUS_ABNORMAL;
            }
            $v->save();
            echo '店铺' . $v['name'] . '执行成功！' . "\n";
        }
        echo "执行完毕！";
    }

}