<?php
namespace console\controllers\api;

use common\components\CommonUtil;
use common\models\Shop;
use common\services\FApiService;
use yii\console\Controller;

class QueueController extends Controller
{

    public function actionToken()
    {
        $key = 'com::api::queue::refresh_token';
        $start_time = time();
        $redis = \Yii::$app->redis;
        while(true) {
            if ($start_time - time() > 60 * 60 && date('H') == '05') {
                exit;
            }

            $result = $redis->blpop($key,30);
            if (empty($result) || empty($result[1])) {
                sleep(2);
                continue;
            }
            $shop_id = $result[1];

            try {
                CommonUtil::logs('run: shop_id:' . $shop_id, 'api_queue_success');
                echo date('Y-m-d H:i:s').' run: shop_id:' . $shop_id."\n";
                $shop = Shop::find()->where(['id' => $shop_id])->asArray()->limit(1)->one();
                if (empty($shop)) {
                    sleep(1);
                    continue;
                }

                //加锁
                $lock = 'com::api::refresh_token::lock:' . $shop_id;
                $request_num = $redis->incrby($lock, 1);
                $ttl_lock = $redis->ttl($lock);
                if ($request_num == 1 || $ttl_lock > 700 || $ttl_lock == -1) {
                    $redis->expire($lock, 600);
                }

                if ($request_num > 1) {
                    continue;
                }

                $api_ser = FApiService::factory($shop);
                $api_ser->refreshToken();
                echo date('Y-m-d H:i:s').' success: shop_id:' . $shop_id."\n";
                CommonUtil::logs('success: shop_id:' . $shop_id, 'api_queue_success');
            } catch (\Exception $e) {
                echo date('Y-m-d H:i:s').' error: shop_id:' . $shop_id .' '. $e->getMessage()."\n";
                CommonUtil::logs('error: shop_id:' . $shop_id .' '. $e->getMessage(), 'api_queue_error');
            }
        }
    }

}