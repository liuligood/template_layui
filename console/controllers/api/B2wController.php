<?php
namespace console\controllers\api;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\goods\ExchangeGoodsTmp;
use common\models\GoodsShop;
use common\models\Shop;
use common\services\FApiService;
use common\services\goods\GoodsService;
use yii\console\Controller;

class B2wController extends Controller
{

    public function actionTest()
    {
        $shop = Shop::findOne(130);
        $api_service = FApiService::factory($shop);

        $result = $api_service->getQueuesOrder();

        echo CommonUtil::jsonFormat($result);
        exit();
    }

    /**
     * 执行订单
     * @return void
     */
    public function actionOrder($shop_id = null)
    {
        $where = [
            'platform_type' => [Base::PLATFORM_B2W],
            'status' => Shop::STATUS_VALID
        ];
        if (!empty($shop_id)) {
            $where['id'] = $shop_id;
        }
        $shop = Shop::find()->where($where)->andWhere(['<>', 'client_key', ''])->all();
        foreach ($shop as $shop_v) {
            echo $shop_v['id'] . " " . $shop_v['name'] . "\n";

            if (empty($shop_v['client_key']) || empty($shop_v['secret_key'])) {
                continue;
            }
            try {
                $api_service = FApiService::factory($shop_v);
            } catch (\Exception $e) {
                CommonUtil::logs('add_order error: shop_id:' . $shop_v['id'] . ' platform_type:' . $shop_v['platform_type'] . ' ' . $e->getMessage() . ' ' . $e->getFile() . $e->getLine(), 'order_api_error');
                continue;
            }

            if (empty($api_service)) {
                continue;
            }

            $api_service->runOrder();
        }
    }

    /**
     * 重复商品第一步
     */
    public function actionRepeatGoods0($limit = 1)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $shop_ids = [
            144,
            202,
            233,
            248,
            249,
            250,
            259,
            260,
            261,
            262,
            263,
            282,
            285,
            286,
            301,
            302,
            305,
            306,
            307,
            308,
            309,
            310,
            311,
            337,
            338,
            339,
            342,
            343,
            344,
            346,
            351,
            352,
            353,
            354,
            357,
            358,
            359,
            355];
        $goods_shop_ids = [];
        while (true) {
            echo '第' .$limit. "页\n";
            $goods_nos = GoodsShop::find()
                ->select('goods_no,count(*) as num,GROUP_CONCAT(shop_id) as old_shop_ids')
                ->where(['platform_type' => Base::PLATFORM_B2W])->groupBy('goods_no')->having('num = 1')
                ->offset(100000 * ($limit - 1))->limit(100000)->asArray()->all();
            $limit ++;
            $i = 0;
            $all_num = count($goods_nos);
            if (!empty($goods_nos)) {
                echo '总数:' . $all_num . "\n";
                do {
                    $top_1000 = array_slice($goods_nos, 0, 1000);
                    $goods_nos = array_slice($goods_nos, 1000);
                    $data = [];
                    foreach ($top_1000 as $goods_v) {
                        $i++;
                        //if ($goods_v['num'] > 1 || (strlen($goods_v['cgoods_no']) == 15 && strpos($goods_v['cgoods_no'], 'C06') === 0)) {
                        if ($goods_v['num'] > 1) {
                            continue;
                        }
                        $old_shop_ids = $goods_v['old_shop_ids'];

                        $new_shop_ids = $shop_ids;
                        $tmp_key = array_search($old_shop_ids, $new_shop_ids);
                        array_splice($new_shop_ids, $tmp_key, 1);
                        shuffle($new_shop_ids);
                        $tmp_new_shop_ids = current($new_shop_ids);
                        if (empty($goods_shop_ids[$tmp_new_shop_ids])) {
                            $goods_shop_ids[$tmp_new_shop_ids] = 0;
                        }
                        $goods_shop_ids[$tmp_new_shop_ids] += 1;
                        if ($goods_shop_ids[$tmp_new_shop_ids] >= 80000) {
                            $tmp_key = array_search($tmp_new_shop_ids, $shop_ids);
                            array_splice($shop_ids, $tmp_key, 1);
                        }

                        $data[] = [
                            'goods_no' => $goods_v['goods_no'],
                            'num' => $goods_v['num'],
                            'old_shop_ids' => $old_shop_ids,
                            'order_shop_ids' => '',
                            'new_shop_ids' => $tmp_new_shop_ids,
                            'del_shop_ids' => '',
                            'status' => 0,
                            'add_time' => time(),
                            'update_time' => time(),
                        ];
                        echo $i . '/' . $all_num . ',' . $goods_v['goods_no'] . ',' . $goods_v['num'] .','. $old_shop_ids . "\n";
                    }
                    $add_columns = [
                        'goods_no',
                        'num',
                        'old_shop_ids',
                        'order_shop_ids',
                        'new_shop_ids',
                        'del_shop_ids',
                        'status',
                        'add_time',
                        'update_time',
                    ];
                    ExchangeGoodsTmp::getDb()->createCommand()->batchIgnoreInsert(ExchangeGoodsTmp::tableName(), $add_columns, $data)->execute();
                } while (count($goods_nos) > 0);
                var_dump($goods_shop_ids);
            }
            if($all_num < 100000) {
                break;
            }
        }
    }

    /**
     * 交换商品第一步 添加店铺商品
     */
    public function actionRepeatGoods1()
    {
        $exch_goods = ExchangeGoodsTmp::find()->where(['status' => 0])->limit(100000)->all();
        $i = 0;
        foreach ($exch_goods as $v) {
            $i++;
            if (empty($v['new_shop_ids']) || empty($v['goods_no'])) {
                $v->status = 101;
                $v->save();
                continue;
            }
            $goods_no = $v['goods_no'];
            $new_shop_ids = explode(',', $v['new_shop_ids']);

            $exist = GoodsShop::find()->where(['goods_no' => $goods_no, 'shop_id' => $new_shop_ids])->all();
            if ($exist) {
                $v->status = 5;
                $v->save();
                continue;
            }

            (new GoodsService())->claim($goods_no, $new_shop_ids, GoodsService::SOURCE_METHOD_OWN);
            $v->status = 2;
            $v->save();
            echo $i . ',' . $goods_no . "\n";
        }
    }

}