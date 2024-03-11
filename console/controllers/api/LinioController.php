<?php
namespace console\controllers\api;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\GoodsShop;
use common\models\GoodsEvent;
use common\models\Shop;
use common\services\FApiService;
use common\services\goods\GoodsService;
use yii\console\Controller;
use yii\helpers\ArrayHelper;

class LinioController extends Controller
{

    /**
     * 批量处理更新库存
     */
    public function actionBatchTest($shop_id,$goods_no)
    {
        $shop_lists = Shop::find()->where(['platform_type' => [Base::PLATFORM_LINIO,Base::PLATFORM_JUMIA],'id'=>$shop_id])->asArray()->all();
        foreach ($shop_lists as $shop) {
            if (empty($shop['client_key'])) {
                continue;
            }

            $event_type = GoodsEvent::find()->where([
                'status' => GoodsEvent::STATUS_WAIT_RUN,
                'platform' => $shop['platform_type'],
                'shop_id' => $shop['id'],
                'goods_no' => $goods_no
            ])->groupBy('event_type')->select('event_type')->column();
            if (in_array(GoodsEvent::EVENT_TYPE_UPDATE_PRICE, $event_type)) {
                $this->batchUpdatePrice($shop);
            }
            continue;

            if (in_array(GoodsEvent::EVENT_TYPE_DEL_GOODS, $event_type)) {
                $this->batchDel($shop);
            }
            if (in_array(GoodsEvent::EVENT_TYPE_UPDATE_STOCK, $event_type)) {
                $this->batchUpdateStock($shop);
            }
            if (in_array(GoodsEvent::EVENT_TYPE_UPDATE_PRICE, $event_type)) {
                $this->batchUpdatePrice($shop);
            }
            if (in_array(GoodsEvent::EVENT_TYPE_DEL_GOODS, $event_type)) {
                $this->batchAddGoods($shop);
            }
        }
    }

    /**
     * 批量处理更新库存
     */
    public function actionBatchUpdateStock()
    {
        $shop_lists = Shop::find()->where(['platform_type' => [Base::PLATFORM_LINIO,Base::PLATFORM_JUMIA]])->asArray()->all();
        foreach ($shop_lists as $shop) {
            if (empty($shop['client_key'])) {
                continue;
            }

            $event_type = GoodsEvent::find()->where([
                'status' => GoodsEvent::STATUS_WAIT_RUN,
                'platform' => $shop['platform_type'],
                'shop_id' => $shop['id'],
            ])->groupBy('event_type')->select('event_type')->column();
            if (in_array(GoodsEvent::EVENT_TYPE_DEL_GOODS, $event_type)) {
                $this->batchDel($shop);
            }
            if (in_array(GoodsEvent::EVENT_TYPE_UPDATE_STOCK, $event_type)) {
                $this->batchUpdateStock($shop);
            }
            if (in_array(GoodsEvent::EVENT_TYPE_UPDATE_PRICE, $event_type)) {
                $this->batchUpdatePrice($shop);
            }
            if (in_array(GoodsEvent::EVENT_TYPE_ADD_GOODS, $event_type)) {
                $this->batchAddGoods($shop);
            }
            if (in_array(GoodsEvent::EVENT_TYPE_UPLOAD_IMAGE, $event_type)) {
                $this->batchUploadImage($shop);
            }
        }
    }

    /**
     * 更新库存
     * @param $shop
     */
    public function batchDel($shop)
    {
        $index_i = 0;
        while (true) {
            $index_i ++;
            if($index_i > 10) {
                break;
            }
            $order_event_lists = GoodsEvent::find()->where([
                'status' => GoodsEvent::STATUS_WAIT_RUN,
                'platform' => $shop['platform_type'],
                'shop_id' => $shop['id'],
                'event_type' => [GoodsEvent::EVENT_TYPE_DEL_GOODS]
            ])->limit(500)->all();
            if(empty($order_event_lists)){
                break;
            }

            $api_service = FApiService::factory($shop);
            list ($check, $time) = $api_service->checkFrequencyLimit(GoodsEvent::EVENT_TYPE_ADD_GOODS);
            if ($check) {
                break;
            }

            $ids = ArrayHelper::getColumn($order_event_lists, 'id');
            $cgoods_nos = ArrayHelper::getColumn($order_event_lists, 'cgoods_no');
            $good_child_lists = GoodsChild::find()->where(['cgoods_no' => $cgoods_nos])->indexBy('cgoods_no')->asArray()->all();
            GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_RUNNING], ['id' => $ids]);
            $data = [];
            $sku = [];
            foreach ($order_event_lists as $v) {
                $goods_child = $good_child_lists[$v['cgoods_no']];
                $goods_shop = GoodsShop::find()->where(['cgoods_no' => $v['cgoods_no'], 'shop_id' => $shop['id']])->one();

                $goods_sku = !empty($goods_shop['platform_sku_no'])?$goods_shop['platform_sku_no']:$goods_child['sku_no'];
                if(array_key_exists($goods_sku,$sku)) {
                    GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_SUCCESS], ['id' => $v['id']]);
                    continue;
                }

                $info = [
                    'SellerSku' => $goods_sku,
                ];
                /*if ($stock && !empty($price)) {
                    //$info['price'] = $price;
                }*/
                $data[] = $info;
                $sku[$goods_sku] = $v['id'];
            }

            if (!empty($data)){
                try {
                    $result = $api_service->batchDelGoods($data);
                    if(empty($result)){
                        GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE], ['id' => $ids]);
                        continue;
                    }
                    GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_RUNNING_RESULT, 'plan_time' => time() + 30 * 60, 'queue_id' => $result], ['id' => $ids]);
                    //GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_SUCCESS], ['id' => $ids]);
                } catch (\Exception $e) {
                    CommonUtil::logs('error: shop_id:' . $shop['id'] . ' platform_type:' . $shop['platform_type'] . $e->getMessage(), 'linio_goods_api_error');
                    GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE,'error_msg'=>$e->getMessage()], ['id' => $ids]);
                    continue;
                }
            }

            if(count($order_event_lists) < 500){
                break;
            }
        }
    }

    /**
     * 更新库存
     * @param $shop
     */
    public function batchUpdateStock($shop)
    {
        $api_service = FApiService::factory($shop);
        $index_i = 0;
        while (true) {
            $index_i ++;
            if($index_i > 10) {
                break;
            }
            $order_event_lists = GoodsEvent::find()->where([
                'status' => GoodsEvent::STATUS_WAIT_RUN,
                'platform' => $shop['platform_type'],
                'shop_id' => $shop['id'],
                'event_type' => [GoodsEvent::EVENT_TYPE_UPDATE_STOCK]
            ])->limit(1000)->all();
            if(empty($order_event_lists)){
                break;
            }

            list ($check, $time) = $api_service->checkFrequencyLimit(GoodsEvent::EVENT_TYPE_UPDATE_STOCK);
            if($check){
                break;
            }

            $ids = ArrayHelper::getColumn($order_event_lists, 'id');
            $goods_nos = ArrayHelper::getColumn($order_event_lists, 'goods_no');
            $good_lists = Goods::find()->where(['goods_no' => $goods_nos])->indexBy('goods_no')->asArray()->all();
            $cgoods_nos = ArrayHelper::getColumn($order_event_lists, 'cgoods_no');
            $good_child_lists = GoodsChild::find()->where(['cgoods_no' => $cgoods_nos])->indexBy('cgoods_no')->asArray()->all();
            GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_RUNNING], ['id' => $ids]);
            $data = [];
            $sku = [];
            foreach ($order_event_lists as $v) {
                $goods = $good_lists[$v['goods_no']];
                $goods_child = $good_child_lists[$v['cgoods_no']];
                $goods_shop = GoodsShop::find()->where(['cgoods_no' => $v['cgoods_no'], 'shop_id' => $shop['id']])->one();
                $stock = true;
                $price = null;
                if($goods['status'] == Goods::GOODS_STATUS_INVALID) {
                    $stock = false;
                } else {
                    $price = $goods_shop['price'];
                }

                $goods_sku = !empty($goods_shop['platform_sku_no'])?$goods_shop['platform_sku_no']:$goods_child['sku_no'];
                if(array_key_exists($goods_sku,$sku)) {
                    GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_SUCCESS], ['id' => $v['id']]);
                    continue;
                }

                $info = [
                    'SellerSku' => $goods_sku,
                    'Quantity' => $stock ? 100 : 0
                ];
                /*if ($stock && !empty($price)) {
                    //$info['price'] = $price;
                }*/
                $data[] = $info;
                $sku[$goods_sku] = $v['id'];
            }

            if (!empty($data)){
                try {
                    $result = $api_service->updateListings($data);
                    if(empty($result)){
                        GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE], ['id' => $ids]);
                        continue;
                    }
                    GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_RUNNING_RESULT, 'plan_time' => time() + 30 * 60, 'queue_id' => $result], ['id' => $ids]);
                    //GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_SUCCESS], ['id' => $ids]);
                } catch (\Exception $e) {
                    CommonUtil::logs('error: shop_id:' . $shop['id'] . ' platform_type:' . $shop['platform_type'] . $e->getMessage(), 'linio_goods_api_error');
                    GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE,'error_msg'=>$e->getMessage()], ['id' => $ids]);
                    continue;
                }
            }

            if(count($order_event_lists) < 1000){
                break;
            }
        }
    }

    /**
     * 更新价格
     * @param $shop
     */
    public function batchUpdatePrice($shop)
    {
        $index_i = 0;
        while (true) {
            $index_i ++;
            if($index_i > 10) {
                break;
            }
            $order_event_lists = GoodsEvent::find()->where([
                'status' => GoodsEvent::STATUS_WAIT_RUN,
                'platform' => $shop['platform_type'],
                'shop_id' => $shop['id'],
                'event_type' => [GoodsEvent::EVENT_TYPE_UPDATE_PRICE]
            ])->limit(500)->all();
            if(empty($order_event_lists)){
                break;
            }

            $api_service = FApiService::factory($shop);
            list ($check, $time) = $api_service->checkFrequencyLimit(GoodsEvent::EVENT_TYPE_ADD_GOODS);
            if ($check) {
                break;
            }

            $ids = ArrayHelper::getColumn($order_event_lists, 'id');
            $goods_nos = ArrayHelper::getColumn($order_event_lists, 'goods_no');
            $good_lists = Goods::find()->where(['goods_no' => $goods_nos])->indexBy('goods_no')->asArray()->all();
            $cgoods_nos = ArrayHelper::getColumn($order_event_lists, 'cgoods_no');
            $good_child_lists = GoodsChild::find()->where(['cgoods_no' => $cgoods_nos])->indexBy('cgoods_no')->asArray()->all();
            GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_RUNNING], ['id' => $ids]);
            $data = [];
            $sku = [];
            foreach ($order_event_lists as $v) {
                $goods = $good_lists[$v['goods_no']];
                $goods_child = $good_child_lists[$v['cgoods_no']];
                $goods_shop = GoodsShop::find()->where(['cgoods_no' => $v['cgoods_no'], 'shop_id' => $shop['id']])->one();
                if (empty($goods_shop)) {
                    continue;
                }

                $price = $goods_shop['price'];
                $goods_sku = !empty($goods_shop['platform_sku_no'])?$goods_shop['platform_sku_no']:$goods_child['sku_no'];
                if(array_key_exists($goods_sku,$sku)) {
                    GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_SUCCESS], ['id' => $v['id']]);
                    continue;
                }

                $size = GoodsService::getSizeArr($goods['size']);
                $exist_size = true;
                if($goods['real_weight'] > 0) {
                    $weight = $goods['real_weight'];
                    if(!empty($size)) {
                        if(!empty($size['size_l']) && $size['size_l'] > 3) {
                            $l = (int)$size['size_l'] - 2;
                        } else {
                            $exist_size = false;
                        }

                        if(!empty($size['size_w']) && $size['size_w'] > 3) {
                            $w = (int)$size['size_w'] - 2;
                        } else {
                            $exist_size = false;
                        }

                        if(!empty($size['size_h']) && $size['size_h'] > 3) {
                            $h = (int)$size['size_h'] - 2;
                        } else {
                            $exist_size = false;
                        }
                    } else {
                        $exist_size = false;
                    }
                } else {
                    $weight = $goods['weight'] < 0.02 ? 0.02 : $goods['weight']/2;
                    $exist_size = false;
                }
                $weight = round($weight,2);

                //生成长宽高
                if(!$exist_size) {
                    $tmp_weight = $weight > 4 ? 4 : $weight;
                    $tmp_cjz = $tmp_weight / 2 * 5000;
                    $pow_i = pow($tmp_cjz, 1 / 3);
                    $pow_i = $pow_i > 30 ? 30 : (int)$pow_i;
                    $min_pow_i = $pow_i > 6 ? ($pow_i - 5) : 1;
                    $max_pow_i = $pow_i > 5 ? ($pow_i + 5) : ($pow_i > 2 ? ($pow_i + 2) : $pow_i);
                    $arr = [];
                    $arr[] = rand($min_pow_i,$max_pow_i);
                    $arr[] = rand($min_pow_i,$max_pow_i);
                    $arr[] = (int)(($tmp_cjz/$arr[0])/$arr[1]);
                    rsort($arr);
                    list($l,$w,$h) = $arr;
                }

                $info = [
                    'SellerSku' => $goods_sku,
                    'Price' => $price * 2,
                    'SalePrice' => $price,
                    'ProductData' => [
                        'PackageLength' => $l,
                        'PackageWidth' => $w,
                        'PackageHeight' => $h,
                        'PackageWeight' => $weight,
                        'ProductWeight' => $weight,
                        'ProductMeasures' => $l . ' x ' . $w . ' x ' . $h
                    ],
                ];
                $data[] = $info;
                $sku[$goods_sku] = $v['id'];
            }

            if (!empty($data)){
                try {
                    $api_service = FApiService::factory($shop);
                    $result = $api_service->updateGoods($data);
                    if(empty($result)){
                        GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE], ['id' => $ids]);
                        continue;
                    }
                    GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_RUNNING_RESULT, 'plan_time' => time() + 30 * 60, 'queue_id' => $result], ['id' => $ids]);
                    //GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_SUCCESS], ['id' => $ids]);
                } catch (\Exception $e) {
                    CommonUtil::logs('error: shop_id:' . $shop['id'] . ' platform_type:' . $shop['platform_type'] . $e->getMessage(), 'linio_goods_api_error');
                    GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE,'error_msg'=>$e->getMessage()], ['id' => $ids]);
                    continue;
                }
            }

            if(count($order_event_lists) < 500){
                break;
            }
        }
    }

    /**
     * 添加商品
     * @param $shop
     */
    public function batchAddGoods($shop)
    {
        $index_i = 0;
        while (true) {
            $index_i ++;
            if($index_i > 10) {
                break;
            }
            $order_event_lists = GoodsEvent::find()->where([
                'status' => GoodsEvent::STATUS_WAIT_RUN,
                'platform' => $shop['platform_type'],
                'shop_id' => $shop['id'],
                'event_type' => [GoodsEvent::EVENT_TYPE_ADD_GOODS]
            ])->limit(90)->all();
            if(empty($order_event_lists)){
                break;
            }

            $api_service = FApiService::factory($shop);
            list ($check, $time) = $api_service->checkFrequencyLimit(GoodsEvent::EVENT_TYPE_ADD_GOODS);
            if ($check) {
                break;
            }

            $cgoods_no_maps = ArrayHelper::map($order_event_lists, 'cgoods_no', 'id');
            $ids = ArrayHelper::getColumn($order_event_lists, 'id');
            $goods_nos = ArrayHelper::getColumn($order_event_lists, 'goods_no');
            $goods = Goods::find()->where(['goods_no' => $goods_nos])->indexBy('goods_no')->asArray()->all();
            $cgoods_nos = ArrayHelper::getColumn($order_event_lists, 'cgoods_no');
            $good_child_lists = GoodsChild::find()->where(['cgoods_no' => $cgoods_nos])->indexBy('cgoods_no')->asArray()->all();
            $good_lists = [];
            $sku_nos = [];
            foreach ($good_child_lists as $goods_child) {
                if (empty($goods[$goods_child['goods_no']]) || empty($cgoods_no_maps[$goods_child['cgoods_no']])) {
                    continue;
                }

                $tmp_id = $cgoods_no_maps[$goods_child['cgoods_no']];
                $sku_nos[$goods_child['sku_no']] = $tmp_id;
                $info = $goods[$goods_child['goods_no']];
                $info['cgoods_no'] = $goods_child['cgoods_no'];
                $info['sku_no'] = $goods_child['sku_no'];
                $info['ccolour'] = '';
                if (!empty($goods_child['colour'])) {
                    $info['ccolour'] = $goods_child['colour'];
                }
                $info['csize'] = $goods_child['size'];
                if (!empty($goods_child['goods_img'])) {
                    $image = json_decode($info['goods_img'], true);
                    $image = empty($image) || !is_array($image) ? [] : $image;
                    $image[0]['img'] = $goods_child['goods_img'];
                    $info['goods_img'] = json_encode($image);
                }
                $good_lists[] = $info;
            }

            if (!empty($good_lists)){
                GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_RUNNING], ['id' => $ids]);
                try {
                    $result = $api_service->batchAddGoods($good_lists);
                    if(empty($result)){
                        GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE], ['id' => $ids]);
                        continue;
                    }

                    if(!empty($result['error'])) {
                        foreach ($result['error'] as $error_v) {
                            if (empty($sku_nos[$error_v['sku_no']])) {
                                continue;
                            }
                            $tmp_id = $sku_nos[$error_v['sku_no']];
                            GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE], ['id' => $tmp_id, 'error_msg' => $error_v['error']]);
                            if (!empty($ids[$tmp_id])) {
                                unset($ids[$tmp_id]);
                            }
                        }
                    }
                    GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_RUNNING_RESULT, 'plan_time' => time() + 30 * 60, 'queue_id' => $result['id']], ['id' => $ids]);
                    //GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_SUCCESS], ['id' => $ids]);
                } catch (\Exception $e) {
                    CommonUtil::logs('error: shop_id:' . $shop['id'] . ' platform_type:' . $shop['platform_type'] .' ' . $e->getMessage(), 'linio_goods_api_error');
                    GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE,'error_msg'=>$e->getMessage()], ['id' => $ids]);
                    continue;
                }
            }

            if(count($order_event_lists) < 90){
                break;
            }
        }
    }

    /**
     * 上传图片
     * @param $shop
     */
    public function batchUploadImage($shop)
    {
        $index_i = 0;
        while (true) {
            $index_i ++;
            if($index_i > 10) {
                break;
            }
            $order_event_lists = GoodsEvent::find()->where([
                'status' => GoodsEvent::STATUS_WAIT_RUN,
                'platform' => $shop['platform_type'],
                'shop_id' => $shop['id'],
                'event_type' => [GoodsEvent::EVENT_TYPE_UPLOAD_IMAGE]
            ])->limit(200)->all();
            if(empty($order_event_lists)){
                break;
            }

            $api_service = FApiService::factory($shop);
            /*list ($check, $time) = $api_service->checkFrequencyLimit(GoodsEvent::EVENT_TYPE_ADD_GOODS);
            if ($check) {
                break;
            }*/

            $cgoods_no_maps = ArrayHelper::map($order_event_lists, 'cgoods_no', 'id');
            $ids = ArrayHelper::getColumn($order_event_lists, 'id');
            $goods_nos = ArrayHelper::getColumn($order_event_lists, 'goods_no');
            $goods = Goods::find()->where(['goods_no' => $goods_nos])->indexBy('goods_no')->asArray()->all();
            $cgoods_nos = ArrayHelper::getColumn($order_event_lists, 'cgoods_no');
            $good_child_lists = GoodsChild::find()->where(['cgoods_no' => $cgoods_nos])->indexBy('cgoods_no')->asArray()->all();
            $good_lists = [];
            $sku_nos = [];
            foreach ($good_child_lists as $goods_child) {
                if (empty($goods[$goods_child['goods_no']]) || empty($cgoods_no_maps[$goods_child['cgoods_no']])) {
                    continue;
                }

                $tmp_id = $cgoods_no_maps[$goods_child['cgoods_no']];
                $sku_nos[$goods_child['sku_no']] = $tmp_id;
                $info = $goods[$goods_child['goods_no']];
                $info['cgoods_no'] = $goods_child['cgoods_no'];
                $info['sku_no'] = $goods_child['sku_no'];
                $info['ccolour'] = '';
                if (!empty($goods_child['colour'])) {
                    $info['ccolour'] = $goods_child['colour'];
                }
                $info['csize'] = $goods_child['size'];
                if (!empty($goods_child['goods_img'])) {
                    $image = json_decode($info['goods_img'], true);
                    $image = empty($image) || !is_array($image) ? [] : $image;
                    $image[0]['img'] = $goods_child['goods_img'];
                    $info['goods_img'] = json_encode($image);
                }
                $good_lists[] = $info;
            }

            if (!empty($good_lists)){
                GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_RUNNING], ['id' => $ids]);
                try {
                    $result = $api_service->batchUploadImage($good_lists);
                    if(empty($result)){
                        GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE], ['id' => $ids]);
                        continue;
                    }

                    if(!empty($result['error'])) {
                        foreach ($result['error'] as $error_v) {
                            if (empty($sku_nos[$error_v['sku_no']])) {
                                continue;
                            }
                            $tmp_id = $sku_nos[$error_v['sku_no']];
                            GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE], ['id' => $tmp_id, 'error_msg' => $error_v['error']]);
                            if (!empty($ids[$tmp_id])) {
                                unset($ids[$tmp_id]);
                            }
                        }
                    }
                    GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_RUNNING_RESULT, 'plan_time' => time() + 30 * 60, 'queue_id' => $result['id']], ['id' => $ids]);
                    //GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_SUCCESS], ['id' => $ids]);
                } catch (\Exception $e) {
                    CommonUtil::logs('error: shop_id:' . $shop['id'] . ' platform_type:' . $shop['platform_type'] .' ' . $e->getMessage(), 'linio_goods_api_error');
                    GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE,'error_msg'=>$e->getMessage()], ['id' => $ids]);
                    continue;
                }
            }

            if(count($order_event_lists) < 90){
                break;
            }
        }
    }


    /**
     * 批量查询队列
     */
    public function actionBatchQueue()
    {
        $shop_lists = Shop::find()->where(['platform_type'=>[Base::PLATFORM_LINIO,Base::PLATFORM_JUMIA]])->asArray()->all();
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

                        if (empty($result) || empty($result['Status'])) {
                            GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE], ['id' => $ids]);
                        } else {
                            if($result['Status'] !== 'Finished') {
                                continue;
                            }

                            if(!empty($result['FeedErrors']) && !empty($result['FeedErrors']['Error'])) {
                                foreach ($result['FeedErrors']['Error'] as $result_v){
                                    $sku = $result_v['SellerSku'];
                                    $goods_shop = GoodsShop::find()->where(['platform_sku_no'=>$sku,'shop_id' => $shop['id']])->one();
                                    if(empty($goods_shop)) {
                                        $goods_child = GoodsChild::find()->where(['sku_no' => $sku])->one();
                                        $cgoods_no = $goods_child['cgoods_no'];
                                    }else{
                                        $cgoods_no = $goods_shop['cgoods_no'];
                                    }
                                    if(empty($cgoods_nos[$cgoods_no])){
                                        continue;
                                    }
                                    GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE, 'error_msg' => 'queue:'.$result_v['Message']],
                                        ['id' => $cgoods_nos[$cgoods_no]]);
                                    unset($cgoods_nos[$cgoods_no]);
                                }
                            }

                            if(!empty($cgoods_nos)) {
                                GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_SUCCESS], ['id' => array_values($cgoods_nos)]);
                                if ($result['Action'] == 'ProductRemove') {
                                    GoodsShop::deleteAll(['shop_id' => $shop['id'], 'cgoods_no' => array_keys($cgoods_nos), 'status' => GoodsShop::STATUS_DELETE]);
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        CommonUtil::logs('queue error: shop_id:' . $shop['id'] . ' platform_type:' . $shop['platform_type'] . $e->getMessage(), 'linio_goods_api_error');
                        GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE, 'error_msg' => 'queue:'.$e->getMessage()], ['id' => $ids]);
                        continue;
                    }
                }
            }
        }
    }

    public function actionGetStatus($shop_id,$query_id){
        $shop = Shop::findOne($shop_id);
        //$sku_no ='GO167630';
        $api_service = FApiService::factory($shop);
        $product = $api_service->getQueue($query_id);
        var_dump($product);
    }

}