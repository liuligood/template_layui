<?php
namespace console\controllers\api;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\extensions\google\Translate;
use common\models\Category;
use common\models\CategoryMapping;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\GoodsShop;
use common\models\goods_shop\GoodsShopPriceChange;
use common\models\GoodsEvent;
use common\models\Shop;
use common\services\api\GoodsEventService;
use common\services\FApiService;
use common\services\goods\FGoodsService;
use common\services\goods\GoodsService;
use common\services\goods\platform\OnbuyPlatform;
use common\services\id\PlatformGoodsSkuIdService;
use yii\console\Controller;
use yii\helpers\ArrayHelper;

class OnbuyController extends Controller
{

    /**
     * 批量处理更新库存
     */
    public function actionBatchUpdateStock()
    {
        $shop_lists = Shop::find()->where(['platform_type' => Base::PLATFORM_ONBUY])->asArray()->all();
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

            if (in_array(GoodsEvent::EVENT_TYPE_ADD_LISTINGS, $event_type)) {
                $this->batchAddListings($shop);
            }
        }
    }

    /**
     * 更新库存
     * @param $shop
     */
    public function batchDel($shop)
    {
        $is_sell = in_array($shop['id'],OnbuyPlatform::$sell_shop)?true:false;
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
            ///$goods_nos = ArrayHelper::getColumn($order_event_lists, 'goods_no');
            //$good_lists = Goods::find()->where(['goods_no' => $goods_nos])->indexBy('goods_no')->asArray()->all();
            $cgoods_nos = ArrayHelper::getColumn($order_event_lists, 'cgoods_no');
            $good_child_lists = GoodsChild::find()->where(['cgoods_no' => $cgoods_nos])->indexBy('cgoods_no')->asArray()->all();
            GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_RUNNING], ['id' => $ids]);
            $data = [];
            $sku = [];
            foreach ($order_event_lists as $v) {
                //$goods = $good_lists[$v['goods_no']];
                $goods_child = $good_child_lists[$v['cgoods_no']];
                $goods_sku = $goods_child['sku_no'];
                if($is_sell){
                    $goods_shop = GoodsShop::find()->where(['cgoods_no' => $v['cgoods_no'], 'shop_id' => $shop['id']])->one();
                    $goods_sku = $goods_shop['platform_sku_no'];
                }
                $data[] = $goods_sku;
                $sku[$goods_sku] = ['id' => $v['id'], 'cgoods_no' => $v['cgoods_no']];
            }
            if (!empty($data)) {
                try {
                    $api_service = FApiService::factory($shop);
                    $result = $api_service->batchDelGoods($data);
                    if (empty($result)) {
                        GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE], ['id' => $ids]);
                    }
                    foreach ($result as $result_k => $result_v) {
                        if (empty($sku[$result_k])) {
                            continue;
                        }

                        $id = $sku[$result_k]['id'];
                        if (!empty($result_v['error'])) {
                            GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE, 'error_msg' => $result_v['error']], ['id' => $id]);
                        } else {
                            GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_SUCCESS], ['id' => $id]);
                        }

                        if (empty($result_v['error']) || $result_v['error'] == 'SKU not found') {
                            $goods_shop = GoodsShop::find()->where(['shop_id' => $shop['id'], 'cgoods_no' => $sku[$result_k]['cgoods_no']])->one();
                            $platform_type = $goods_shop->platform_type;
                            $goods_no = $goods_shop->goods_no;
                            $country_code = $goods_shop->country_code;
                            if ($goods_shop->delete()) {
                                $where = ['platform_type' => $platform_type, 'goods_no' => $goods_no];
                                if (!empty($country_code)) {
                                    $where['country_code'] = $country_code;
                                }
                                $goods_model = GoodsShop::findOne($where);
                                if (empty($goods_model)) {
                                    $where = ['goods_no' => $goods_no];
                                    if (!empty($country_code)) {
                                        $where['country_code'] = $country_code;
                                    }
                                    $main_goods_model = FGoodsService::factory($platform_type)->model()->findOne($where);
                                    $main_goods_model->delete();
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    CommonUtil::logs('error: shop_id:' . $shop['id'] . ' platform_type:' . $shop['platform_type'] . $e->getMessage(), 'onbuy_goods_api_error');
                    GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE, 'error_msg' => $e->getMessage()], ['id' => $ids]);
                    continue;
                }
            }
        }
    }

    /**
     * 添加报价
     * @param $shop
     */
    public function batchAddListings($shop)
    {
        $is_sell = in_array($shop['id'],OnbuyPlatform::$sell_shop)?true:false;
        while (true) {
            $order_event_lists = GoodsEvent::find()->where([
                'status' => GoodsEvent::STATUS_WAIT_RUN,
                'platform' => $shop['platform_type'],
                'shop_id' => $shop['id'],
                'event_type' => [GoodsEvent::EVENT_TYPE_ADD_LISTINGS]
            ])->limit(1000)->all();
            if(empty($order_event_lists)){
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
                //禁用的更新为下架
                if($goods['status'] == Goods::GOODS_STATUS_INVALID){
                    continue;
                }

                //不要亚马逊商品
                if($goods['source_method'] == GoodsService::SOURCE_METHOD_AMAZON) {
                    continue;
                }

                $goods_shop = GoodsShop::find()->where(['cgoods_no' => $v['cgoods_no'], 'shop_id' => $shop['id']])->one();
                if(empty($goods_shop) || empty($goods_shop['platform_goods_opc']) || empty($goods_shop['price'])){
                    continue;
                }
                $price = $goods_shop['price'];
                $goods_sku = $is_sell?$goods_shop['platform_sku_no']:$goods_child['sku_no'];
                $info = [
                    'sku' => $goods_sku,
                    'stock' => 100,
                    'price' => $price,
                    'opc' => $goods_shop['platform_goods_opc'],
                    'condition' => 'new'
                ];
                $data[] = $info;
                $sku[$goods_sku] = $v['id'];
            }

            if (!empty($data)){
                try {
                    $api_service = FApiService::factory($shop);
                    //CommonUtil::logs('error: shop_id:' . $shop['id']  . json_encode($data), 'onbuy_goods_api_error');

                    $result = $api_service->addListings($data);
                    if(empty($result)){
                        GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE], ['id' => $ids]);
                    }
                    foreach ($result as $result_v) {
                        if(empty($sku[$result_v['sku']])){
                            continue;
                        }

                        $sku_no = $sku[$result_v['sku']];
                        if (!empty($result_v['error'])) {
                            GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE, 'error_msg' => $result_v['error']], ['id' => $sku_no]);
                        } else {
                            GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_SUCCESS], ['id' => $sku_no]);
                        }
                    }
                } catch (\Exception $e) {
                    CommonUtil::logs('error: shop_id:' . $shop['id'] . ' platform_type:' . $shop['platform_type'] . $e->getMessage(), 'onbuy_goods_api_error');
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
     * 更新库存
     * @param $shop
     */
    public function batchUpdateStock($shop)
    {
        $is_sell = in_array($shop['id'],OnbuyPlatform::$sell_shop)?true:false;
        while (true) {
            $order_event_lists = GoodsEvent::find()->where([
                'status' => GoodsEvent::STATUS_WAIT_RUN,
                'platform' => $shop['platform_type'],
                'shop_id' => $shop['id'],
                'event_type' => [GoodsEvent::EVENT_TYPE_UPDATE_STOCK,GoodsEvent::EVENT_TYPE_UPDATE_PRICE]
            ])->limit(1000)->all();
            if(empty($order_event_lists)){
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
                if($goods['source_method'] == GoodsService::SOURCE_METHOD_OWN) {//自建的更新价格，禁用状态的更新为下架
                    $stock = true;
                    $price = null;
                    if($goods['status'] == Goods::GOODS_STATUS_INVALID) {
                        $stock = false;
                    } else {
                        $price = $goods_shop['price'];
                    }
                } else {
                    $price = $goods['price'];
                    $stock = $goods['stock'] == Goods::STOCK_YES ? true : false;

                    //禁用的更新为下架
                    if($goods['status'] == Goods::GOODS_STATUS_INVALID){
                        $stock = false;
                    }

                    //德国250  $price*1.35+2
                    //英国100  $pice*1.4+2
                    if ($goods['source_platform_type'] == Base::PLATFORM_AMAZON_DE) {
                        if ($price >= 250) {
                            $stock = false;
                        }
                        $price = ceil($price * 1.15 + 2) - 0.01;
                    } else if ($goods['source_platform_type'] == Base::PLATFORM_AMAZON_CO_UK) {
                        if ($price >= 100) {
                            $stock = false;
                        }
                        $price = ceil($price * 1.4 * 1.1 + 2) - 0.01;
                    } else {
                        continue;
                    }

                    //英国亚马逊全下架
                    if($goods['source_method'] == GoodsService::SOURCE_METHOD_AMAZON) {
                        $stock = false;
                    }
                }

                $goods_sku = $is_sell?$goods_shop['platform_sku_no']:$goods_child['sku_no'];
                if(array_key_exists($goods_sku,$sku)) {
                    GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_SUCCESS], ['id' => $v['id']]);
                    continue;
                }

                $info = [
                    'sku' => $goods_sku,
                    'stock' => $stock ? 100 : 0
                ];
                if ($stock && !empty($price)) {
                    $info['price'] = $price;
                }
                $data[] = $info;
                $sku[$goods_sku] = $v['id'];
            }

            if (!empty($data)){
                try {
                    $api_service = FApiService::factory($shop);
                    $result = $api_service->updateListings($data);
                    if(empty($result)){
                        GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE], ['id' => $ids]);
                    }
                    foreach ($result as $result_v) {
                        if(empty($sku[$result_v['sku']])){
                            continue;
                        }

                        $sku_no = $sku[$result_v['sku']];
                        if (!empty($result_v['error'])) {
                            GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE, 'error_msg' => $result_v['error']], ['id' => $sku_no]);
                        } else {
                            GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_SUCCESS], ['id' => $sku_no]);
                        }
                    }
                } catch (\Exception $e) {
                    CommonUtil::logs('error: shop_id:' . $shop['id'] . ' platform_type:' . $shop['platform_type'] . $e->getMessage(), 'onbuy_goods_api_error');
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
     * 批量添加商品
     */
    public function actionBatchAddGoods()
    {
        $shop_lists = Shop::find()->where(['platform_type' => Base::PLATFORM_ONBUY])->asArray()->all();
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
                ])->limit(100)->all();
                if (empty($order_event_lists)) {
                    break;
                }
                $goods_no_maps = ArrayHelper::map($order_event_lists, 'goods_no', 'id');
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
                    $info = $goods[$goods_child['goods_no']];
                    $info['cgoods_no'] = $goods_child['cgoods_no'];
                    $info['sku_no'] = $goods_child['sku_no'];
                    if (!empty($goods_child['colour'])) {
                        $info['colour'] = $goods_child['colour'];
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

                GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_RUNNING], ['id' => $ids]);

                try {
                    $api_service = FApiService::factory($shop);
                    $result = $api_service->batchAddGoods($good_lists);
                    if (empty($result)) {
                        GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE], ['id' => $ids]);
                    } else {
                        foreach ($result as $v) {
                            if (empty($v['uid'])) {
                                continue;
                            }
                            $event_id = $goods_no_maps[$v['uid']];
                            if ($v['success']) {
                                GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_RUNNING_RESULT, 'queue_id' => $v['queue_id']], ['id' => $event_id]);
                            } else {
                                GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE, 'error_msg' => empty($v['error'])?'':$v['error']], ['id' => $event_id]);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    CommonUtil::logs('error: shop_id:' . $shop['id'] . ' platform_type:' . $shop['platform_type'] . $e->getMessage(), 'onbuy_goods_api_error');
                    GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE, 'error_msg' => $e->getMessage()], ['id' => $ids]);
                    continue;
                }
                if(count($order_event_lists) < 100){
                    break;
                }
            }
        }
    }

    /**
     * 批量更新商品内容
     */
    public function actionBatchUpdateGoodsContent()
    {
        $shop_lists = Shop::find()->where(['platform_type' => Base::PLATFORM_ONBUY])->asArray()->all();
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
                    'event_type' => [GoodsEvent::EVENT_TYPE_UPDATE_GOODS_CONTENT]
                ])->limit(100)->all();
                if (empty($order_event_lists)) {
                    break;
                }
                $goods_no_maps = ArrayHelper::map($order_event_lists, 'goods_no', 'id');
                $ids = ArrayHelper::getColumn($order_event_lists, 'id');
                $goods_nos = ArrayHelper::getColumn($order_event_lists, 'goods_no');
                $good_lists = Goods::find()->where(['goods_no' => $goods_nos])->indexBy('goods_no')->asArray()->all();
                GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_RUNNING], ['id' => $ids]);

                try {
                    $api_service = FApiService::factory($shop);
                    $result = $api_service->batchUpdateGoodsContent($good_lists);
                    if (empty($result)) {
                        GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE], ['id' => $ids]);
                    } else {
                        foreach ($result as $v) {
                            if (empty($v['uid'])) {
                                continue;
                            }
                            $event_id = $goods_no_maps[$v['uid']];
                            if ($v['success']) {
                                GoodsEvent::updateAll(['status' => 16, 'queue_id' => $v['queue_id']], ['id' => $event_id]);
                            } else {
                                GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE, 'error_msg' => empty($v['message'])?'':$v['message']], ['id' => $event_id]);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    CommonUtil::logs('error: shop_id:' . $shop['id'] . ' platform_type:' . $shop['platform_type'] . $e->getMessage(), 'onbuy_goods_api_error');
                    GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE, 'error_msg' => $e->getMessage()], ['id' => $ids]);
                    continue;
                }

                if(count($order_event_lists) < 100){
                    break;
                }
            }
        }
    }

    /**
     * 批量查询队列
     */
    public function actionBatchQueue()
    {
        $shop_lists = Shop::find()->where(['platform_type'=>Base::PLATFORM_ONBUY])->asArray()->all();
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
                ])->andWhere(['<','plan_time',time()])->andWhere(['!=','queue_id',''])->indexBy('queue_id')->limit(10)->all();
                if(empty($order_event_lists)){
                    break;
                }
                $queue_ids = ArrayHelper::getColumn($order_event_lists, 'queue_id');
                $ids = ArrayHelper::getColumn($order_event_lists, 'id');
                GoodsEvent::updateAll(['plan_time' => time() + 30*60], ['id' => $ids]);

                try {
                    $api_service = FApiService::factory($shop);
                    $result = $api_service->getQueue(implode(',',$queue_ids));
                    if(empty($result)){
                        GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE], ['id' => $ids]);
                    }else {
                        foreach ($result as $v){
                            if(empty($v['queue_id'])){
                                continue;
                            }
                            if($v['status'] == 'pending'){
                                continue;
                            }
                            $event = $order_event_lists[$v['queue_id']];
                            $event_id = $event['id'];
                            if(!empty($v['error_message'])){
                                GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE,'error_msg'=>$v['error_message']], ['id' => $event_id]);
                            } else {
                                GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_SUCCESS], ['id' => $event_id]);
                                if(!empty($v['opc'])) {
                                    GoodsShop::updateAll(['platform_goods_opc' => $v['opc']], ['goods_no' => $event['goods_no'], 'shop_id' => $event['shop_id']]);
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    CommonUtil::logs('error: shop_id:' . $shop['id'] . ' platform_type:' . $shop['platform_type'] . $e->getMessage(), 'onbuy_goods_api_error');
                    GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE,'error_msg'=>$e->getMessage()], ['id' => $ids]);
                    continue;
                }

                if(count($order_event_lists) < 10){
                    break;
                }
            }
        }
    }

    /**
     * 跟卖价格
     */
    public function actionSellWithPrice($shop_ids)
    {
        $shop_ids = explode(',',$shop_ids);
        $shop_lists = Shop::find()->where(['platform_type'=>Base::PLATFORM_ONBUY,'id'=>$shop_ids])->asArray()->all();
        foreach ($shop_lists as $shop) {
            if (empty($shop['client_key'])){
                continue;
            }

            $goods_service = new GoodsService();
            $limit = 0;
            while (true) {
                $limit++;
                $is_sell = in_array($shop['id'],OnbuyPlatform::$sell_shop)?true:false;
                $goods_shop = GoodsShop::find()->where(['shop_id'=>$shop['id']])
                    ->andWhere(['<=','plan_check_price_time',time()])->limit(300)->all();
                if (empty($goods_shop)) {
                    break;
                }

                $sku_filed = $is_sell?'cgoods_no':'sku_no';
                $goods_nos = ArrayHelper::getColumn($goods_shop,'goods_no');
                $cgoods_nos = ArrayHelper::getColumn($goods_shop,'cgoods_no');
                //$goods_lists = GoodsChild::find()->alias('gc')->leftJoin(Goods::tableName() .' g','gc.goods_no=g.goods_no')->where(['cgoods_no'=>$cgoods_nos])->select('gc.goods_no,gc.cgoods_no,gc.sku_no,g.price,g.weight,g.size,g.gbp_price,g.source_method,g.source_method_sub,g.goods_stamp_tag')->indexBy($sku_filed)->asArray()->all();
                $goods_child_lists = GoodsChild::find()->where(['cgoods_no'=>$cgoods_nos])->asArray()->all();
                $goods = Goods::find()->where(['goods_no'=>$goods_nos])->indexBy('goods_no')->asArray()->all();
                $goods_lists = [];
                foreach ($goods_child_lists as $child_list) {
                    if(empty($goods[$child_list['goods_no']])) {
                        $goods_shop_info = GoodsShop::find()->where(['shop_id' => $shop['id'], 'goods_no' => $child_list['goods_no']])->one();
                        $goods_shop_info->plan_check_price_time = time()+100*24*60*60;
                        $goods_shop_info->save();
                        continue;
                    }
                    $goods_info = $goods_service->dealGoodsInfo($goods[$child_list['goods_no']],$child_list);
                    $goods_lists[$child_list[$sku_filed]] = $goods_info;
                }

                if($is_sell) {
                    $sku_nos = ArrayHelper::getColumn($goods_shop, 'platform_sku_no');
                }else{
                    $sku_nos = ArrayHelper::getColumn($goods_lists, $sku_filed);
                }
                try {
                    $api_service = FApiService::factory($shop);
                    $listings = $api_service->checkListings($sku_nos);
                }catch (\Exception $e){
                    $limit++;
                    CommonUtil::logs('shop_id:'.$shop['id'].' error'.$e->getMessage(),'onbuy_sell');
                    //每次执行50次 就是1.5万条
                    if($limit > 50){
                        break;
                    }
                    continue;
                }
                foreach ($listings as $list_v) {
                    if($is_sell){
                        $goods_shop_info = GoodsShop::find()->where(['shop_id' => $shop['id'], 'platform_sku_no' => $list_v['sku']])->one();
                        if(empty($goods_lists[$goods_shop_info['cgoods_no']])){
                            continue;
                        }
                        $goods = $goods_lists[$goods_shop_info['cgoods_no']];
                    }else {
                        if(empty($goods_lists[$list_v['sku']])){
                            continue;
                        }
                        $goods = $goods_lists[$list_v['sku']];
                        $goods_shop_info = GoodsShop::find()->where(['shop_id' => $shop['id'], 'cgoods_no' => $goods['cgoods_no']])->one();

                    }
                    if (!isset($list_v['winning']) || empty($list_v['lead_price'])) {
                        $plan_check_price_time = time() + 7 * 24 * 60 * 60;
                        $goods_shop_info->plan_check_price_time = $plan_check_price_time;
                        $goods_shop_info->save();
                        continue;
                    }

                    $lead_price = $list_v['lead_price'] + $list_v['lead_delivery_price'] - 0.01;

                    $goods_platform_class = FGoodsService::factory($shop['platform_type']);
                    /*if (!empty($country_code)) {
                        $goods_platform_class = $goods_platform_class->setCountryCode($country_code);
                    }*/
                    $max_price = $goods_platform_class->getPrice($goods,$shop['id']);
                    $min_price = $max_price * 0.8;

                    $has_update_price = true;
                    $plan_check_price_time = time();


                    if ($list_v['winning'] == true) {//已经是最低价的
                        $has_update_price = false;
                        $lead_price = $list_v['price'];
                        if ($list_v['price'] == $max_price) {//一般情况下没有跟卖
                            $plan_check_price_time = time() + 14 * 24 * 60 * 60;
                        } else {
                            $plan_check_price_time = time() + 1 * 60 * 60;
                            $change = GoodsShopPriceChange::find()->where(['goods_shop_id'=>$goods_shop_info['id']])->orderBy('add_time desc')->one();
                            if(empty($change) || $change['add_time'] < time() - 14 * 24 * 60 * 60) {//连续十四天保持最低价则改完最高价格重新改价
                                $plan_check_price_time = time() + 60 * 60;
                                $lead_price = $max_price;
                                $has_update_price = true;
                            }
                        }
                    } else {//不是最低价的 调整跟卖价格
                        if ($lead_price < $min_price) {
                            $lead_price = $max_price;
                            if ($list_v['price'] == $max_price) {
                                $has_update_price = false;
                            }
                        }
                        $plan_check_price_time = time() + 1 * 60 * 60;
                    }
                    $old_price = $goods_shop_info['price'];
                    $goods_shop_info->original_price = $max_price;
                    $goods_shop_info->price = $lead_price;
                    $goods_shop_info->plan_check_price_time = $plan_check_price_time;
                    $goods_shop_info->save();
                    //调整价格
                    if ($has_update_price) {
                        GoodsShopPriceChange::add([
                            'goods_shop_id' => $goods_shop_info['id'],
                            'old_price' => $old_price,
                            'new_price' => $lead_price,
                        ]);
                        GoodsEventService::addEvent($goods_shop_info, GoodsEvent::EVENT_TYPE_UPDATE_PRICE);
                    }
                    CommonUtil::logs($shop['id'].','. $goods['goods_no'].','. $goods['cgoods_no'].','. $old_price.','.$lead_price.','.$has_update_price,'onbuy_sell');
                }

                //每次执行50次 就是1.5万条
                if($limit > 200){
                    break;
                }
            }
        }
    }

    /**
     * 更新类目（onbuy）
     * @throws \yii\base\Exception
     */
    public function actionUpdateCategories()
    {
        $shop = Shop::findOne(20);
        $api_service = FApiService::factory($shop);
        $limit = 0;
        while (true) {
            $limit++;
            $result = $api_service->getCategories(100 * ($limit - 1), 100);
            if(empty($result)){
                break;
            }

            foreach ($result as $v) {
                $exist = Category::find()->where(['id'=>$v['category_id']+10000])->exists();
                if($exist){
                    continue;
                }

                $category = new Category();
                $category->source_method = GoodsService::SOURCE_METHOD_OWN;
                $category->name = Translate::exec($v['name']);
                $category->name_en = $v['name'];
                $category->sku_no = (string)$v['category_id'];
                $category->parent_id = empty($v['parent_id'])?0:($v['parent_id']+10000);
                $category->id = $v['category_id']+10000;
                $category->google_category_id = $v['google_category']['id'];
                $category->google_category_name = $v['google_category']['name'];
                $category->google_category_path = $v['google_category']['tree'];
                if(!$category->save()){
                    var_dump($category->getErrors());
                }

                $category_map = new CategoryMapping();
                $category_map->category_id = $category->id;
                $category_map->platform_type = Base::PLATFORM_ONBUY;
                $category_map->o_category_name = (string)$v['category_id'];
                $category_map->save();
                echo $v['category_id'].','.$v['name']."\n";
            }
        }
    }

    /**
     * 更新类目（onbuy）
     * @throws \yii\base\Exception
     */
    public function actionUpdateCategories1()
    {
        $shop = Shop::findOne(20);
        $api_service = FApiService::factory($shop);
        $limit = 0;
        while (true) {
            $limit++;
            $result = $api_service->getCategories(100 * ($limit - 1), 100);
            if(empty($result)){
                break;
            }

            foreach ($result as $v) {
                $category = Category::find()->where(['id'=>$v['category_id']+10000])->one();
                if(!$category){
                    echo 'error'.$v['category_id'].','.$v['name']."\n";
                    continue;
                }

                $category->google_category_id = $v['google_category']['id'];
                $category->google_category_name = $v['google_category']['name'];
                $category->google_category_path = $v['google_category']['tree'];
                $category->save();
                echo $v['category_id'].','.$v['name']."\n";
            }
        }
    }

    /**
     * 映射类目（google）
     * @throws \yii\base\Exception
     */
    public function actionMappingCategories()
    {
        $shop = Shop::findOne(51);
        $api_service = FApiService::factory($shop);
        $result = $api_service->getCategory();
        $p_result = [];
        foreach ($result as $v) {
            $path = explode('.',$v['path']);
            if(count($path) < 2){
                continue;
            }
            $p_result[$path[count($path) -2]] = $v;
        }

        $c_result = [];
        foreach ($result as $v){
            if(!empty($c_result[$v['google_taxonomy_id']])){
                continue;
            }

            if(empty($p_result[$v])){
                $c_result[$v['google_taxonomy_id']] = $v['id'];
            }
        }

        $limit = 0;
        while (true) {
            $limit++;
            $category = Category::find()->where([
                'source_method' => 1,
                'has_child' => 0,
            ])->offset(1000 * ($limit - 1))->limit(1000)->all();
            if (empty($category)) {
                continue;
            }

            foreach ($category as $category_v) {
                if(empty($category_v['google_category_id'])){
                    continue;
                }

                if(empty($c_result[$category_v['google_category_id']])) {
                    continue;
                }

                $category_map = new CategoryMapping();
                $category_map->category_id = $category_v['id'];
                $category_map->platform_type = Base::PLATFORM_FYNDIQ;
                $category_map->o_category_name = (string)$c_result[$category_v['google_category_id']];
                $category_map->save();
                echo $category_v['id'].','.$c_result[$category_v['google_category_id']]."\n";
            }
        }
    }


    /**
     * 添加更新商品事件
     * @throws \yii\base\Exception
     */
    public function actionAddEventUpdateGoodsContent($limit=0)
    {
        while (true) {
            $limit++;
            $goods_shop = GoodsShop::find()->where(['platform_type' => Base::PLATFORM_ONBUY])->andWhere(['!=', 'platform_goods_opc', ''])
                ->offset(10000 * ($limit - 1))->limit(10000)->all();
            foreach ($goods_shop as $shop_v) {
                GoodsEventService::addEvent($shop_v, GoodsEvent::EVENT_TYPE_UPDATE_GOODS_CONTENT);
                echo $shop_v['shop_id'] . ',' . $shop_v['goods_no'] . ',' . $shop_v['platform_goods_opc'] . "\n";
            }
            echo $limit . "\n";
        }
    }

    /**
     * 跟卖
     * @param int $gm_shop_id 跟卖店铺
     * @param int $shop_id 新店铺
     * @throws \yii\base\Exception
     */
    public function actionSellShop($gm_shop_id, $shop_id, $page = 1, $end_page = 100)
    {
        while (true) {
            echo $page . "\n";
            if($page > $end_page){
                break;
            }
            $goods_shop = GoodsShop::find()->where(['shop_id'=>$gm_shop_id])->offset(10000 * ($page - 1))->limit(10000)->all();
            if(empty($goods_shop)){
                break;
            }
            foreach ($goods_shop as $shop_v) {
                if(empty($shop_v['platform_goods_opc'])) {
                    continue;
                }

                $goods_shop = GoodsShop::find()->where(['goods_no'=>$shop_v['goods_no'],'shop_id'=>$shop_id])->one();
                if(!empty($goods_shop)){
                    continue;
                }

                $goods = Goods::find()->where(['goods_no'=>$shop_v['goods_no']])->one();
                if($goods['source_method'] != GoodsService::SOURCE_METHOD_OWN){
                    continue;
                }
                
                if($goods['status'] == Goods::GOODS_STATUS_WAIT_MATCH && $goods['source_platform_type'] == Base::PLATFORM_ONBUY) {
                    continue;
                }

                if($goods['status'] == Goods::GOODS_STATUS_INVALID) {
                    continue;
                }

                $id_server = new PlatformGoodsSkuIdService();
                $platform_sku_no =  GoodsShop::ID_PREFIX . $id_server->getNewId();
                $data = [
                    'goods_no' => $shop_v['goods_no'],
                    'cgoods_no' => $shop_v['cgoods_no'],
                    'platform_type' => $shop_v['platform_type'],
                    'shop_id' => $shop_id,
                    'country_code' => '',
                    'ean' => $shop_v['ean'],
                    'status' => 0,
                    'price' => $shop_v['price'],
                    'original_price' => $shop_v['price'],
                    'platform_goods_opc' => $shop_v['platform_goods_opc'],
                    'platform_sku_no' => $platform_sku_no,
                    'admin_id' => 0
                ];
                GoodsShop::add($data);

                GoodsEventService::addEvent($shop_v, GoodsEvent::EVENT_TYPE_ADD_LISTINGS);
                echo $shop_id . ',' . $shop_v['goods_no'] . ',' . $shop_v['platform_goods_opc'] . "\n";
            }
            $page++;
        }
    }

    /**
     * 修复平台sku_no
     * @param $shop_id
     */
    public function actionRePlatformSkuNo($shop_id)
    {
        $page = 0;
        while (true) {
            $page++;
            echo $page . "\n";
            $goods_shop = GoodsShop::find()->where(['shop_id'=>$shop_id])->offset(10000 * ($page - 1))->limit(10000)->all();
            if(empty($goods_shop)){
                break;
            }
            foreach ($goods_shop as $shop_v) {
                if(!empty($shop_v['platform_sku_no'])) {
                    continue;
                }

                $goods = Goods::find()->where(['goods_no'=>$shop_v['goods_no']])->one();
                $shop_v->platform_sku_no = $goods['sku_no'];
                $shop_v->save();
                echo $shop_id . ',' . $shop_v['goods_no'] . ',' . $shop_v['platform_sku_no'] . "\n";
            }
        }
    }

    /**
     * 更新商品id
     * @param $shop_id
     * @param int $offer
     * @throws \yii\base\Exception
     */
    public function actionUpdateGoodsNo($shop_id ,$offer = 0)
    {
        $shop = Shop::findOne($shop_id);
        if ($shop['platform_type'] != Base::PLATFORM_ONBUY) {
            return;
        }

        $limit = 1000;
        while (true) {
            echo $offer."\n";
            $api_service = FApiService::factory($shop);
            $result = $api_service->getListings($offer, $limit);
            if (empty($result)) {
                break;
            }

            foreach ($result as $v) {
                $goods_no = Goods::find()->where(['sku_no' => $v['sku']])->select('goods_no')->scalar();
                if (empty($goods_no)) {
                    CommonUtil::logs('商品不存在 shop_id:' . $shop_id . ' result:' . json_encode($v), 'onbuy_goods');
                    continue;
                }
                $goods_shop = GoodsShop::find()->where(['shop_id' => $shop_id, 'goods_no' => $goods_no])->one();
                if (empty($goods_shop)) {
                    CommonUtil::logs('店铺商品不存在 shop_id:' . $shop_id . ' result:' . json_encode($v), 'onbuy_goods');
                    continue;
                }
                $goods_shop->platform_goods_id = $v['product_listing_id'];
                $goods_shop->platform_goods_opc = $v['opc'];
                $goods_shop->platform_goods_url = $v['product_url'];
                $goods_shop->save();
            }
            $offer += $limit;
        }
    }

}