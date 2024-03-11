<?php
namespace console\controllers\api;

use common\components\CommonUtil;
use common\components\HelperStamp;
use common\components\statics\Base;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\goods_shop\GoodsShopOverseasWarehouse;
use common\models\GoodsEvent;
use common\models\GoodsShop;
use common\models\platform\PlatformCategory;
use common\models\Shop;
use common\models\warehousing\WarehouseProvider;
use common\services\api\GoodsEventService;
use common\services\FApiService;
use common\services\goods\FGoodsService;
use common\services\goods\GoodsService;
use common\services\warehousing\WarehouseService;
use yii\console\Controller;
use yii\helpers\ArrayHelper;

class CdiscountController extends Controller
{

    /**
     * 批量处理更新库存
     */
    public function actionBatchUpdateStock()
    {
        $shop_lists = Shop::find()->where(['platform_type' => Base::PLATFORM_CDISCOUNT])->asArray()->all();
        foreach ($shop_lists as $shop) {
            if (empty($shop['client_key'])) {
                continue;
            }

            $event_type = GoodsEvent::find()->where([
                'status' => GoodsEvent::STATUS_WAIT_RUN,
                'platform' => $shop['platform_type'],
                'shop_id' => $shop['id'],
            ])->groupBy('event_type')->select('event_type')->column();
            if (in_array(GoodsEvent::EVENT_TYPE_UPDATE_STOCK, $event_type) || in_array(GoodsEvent::EVENT_TYPE_UPDATE_PRICE, $event_type) || in_array(GoodsEvent::EVENT_TYPE_DEL_GOODS, $event_type)) {
                $this->batchUpdateStock($shop);
            }

            /*if (in_array(GoodsEvent::EVENT_TYPE_DEL_GOODS, $event_type)) {
                //$this->batchDel($shop);
            }*/

            if (in_array(GoodsEvent::EVENT_TYPE_ADD_LISTINGS, $event_type)) {
                $this->batchAddListings($shop);
            }
        }
    }

    /**
     * 批量修改库存
     * @param $shop
     */
    public function batchUpdateStock($shop)
    {
        $limit  = 200;
        while (true) {
            $order_event_lists = GoodsEvent::find()->where([
                'status' => GoodsEvent::STATUS_WAIT_RUN,
                'platform' => $shop['platform_type'],
                'shop_id' => $shop['id'],
                'event_type' => [GoodsEvent::EVENT_TYPE_UPDATE_STOCK,GoodsEvent::EVENT_TYPE_UPDATE_PRICE,GoodsEvent::EVENT_TYPE_DEL_GOODS]
            ])->limit($limit)->all();
            if(empty($order_event_lists)){
                break;
            }
            $ids = ArrayHelper::getColumn($order_event_lists, 'id');
            $goods_nos = ArrayHelper::getColumn($order_event_lists, 'goods_no');
            $cgoods_nos = ArrayHelper::getColumn($order_event_lists, 'cgoods_no');
            $good_lists = Goods::find()->where(['goods_no' => $goods_nos])->indexBy('goods_no')->asArray()->all();
            $good_child_lists = GoodsChild::find()->where(['cgoods_no' => $cgoods_nos])->indexBy('cgoods_no')->asArray()->all();
            GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_RUNNING], ['id' => $ids]);

            $data = [];
            foreach ($order_event_lists as $v) {
                $goods = $good_lists[$v['goods_no']];
                $goods_child = $good_child_lists[$v['cgoods_no']];
                $goods_shop = GoodsShop::find()->where(['cgoods_no' => $v['cgoods_no'], 'shop_id' => $shop['id']])->one();
                if(empty($goods_shop)){
                    continue;
                }

                $stock = true;
                $price = null;
                if($goods['status'] == Goods::GOODS_STATUS_INVALID) {
                    $stock = false;
                } else {
                    $price = $goods_shop['price'];
                }

                if($goods_shop['status'] == GoodsShop::STATUS_DELETE) {
                    $stock = false;
                }
                $stock = $stock?1000:0;

                if($goods_shop['other_tag'] == GoodsShop::OTHER_TAG_OVERSEAS) {//第三方海外仓的要实时库存
                    $goods_shop_ov = GoodsShopOverseasWarehouse::find()->where(['goods_shop_id'=>$goods_shop['id']])->one();
                    if(!empty($goods_shop_ov)) {
                        $warehouse_type = WarehouseService::getWarehouseProviderType($goods_shop_ov['warehouse_id']);
                        if ($warehouse_type == WarehouseProvider::TYPE_THIRD_PARTY) {
                            $stock = $goods_shop_ov['goods_stock'];
                        }
                    }
                }

                $goods_sku = $goods_child['sku_no'];
                $info = [
                    'sku' => $goods_sku,
                    'stock' => $stock,
                    'price' => $price,
                    //'opc' => $goods_shop['platform_goods_opc'],
                    'ean' => $goods_shop['ean']
                ];
                $data[] = $info;
            }

            if (!empty($data)){
                try {
                    $api_service = FApiService::factory($shop);
                    //CommonUtil::logs('error: shop_id:' . $shop['id']  . json_encode($data), 'onbuy_goods_api_error');
                    $result = $api_service->updateListings($data);
                    if(empty($result)){
                        GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE], ['id' => $ids]);
                    }
                    GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_SUCCESS], ['id' => $ids]);
                } catch (\Exception $e) {
                    CommonUtil::logs('offers error: shop_id:' . $shop['id'] . ' platform_type:' . $shop['platform_type'] . $e->getMessage(), 'add_cd_products_error');
                    GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE,'error_msg'=>$e->getMessage()], ['id' => $ids]);
                    continue;
                }
            }

            if(count($order_event_lists) < $limit){
                break;
            }
        }
    }

    /**
     * 添加报价
     * @param $shop
     */
    public function batchAddListings($shop)
    {
        $limit  = 200;
        while (true) {
            $order_event_lists = GoodsEvent::find()->where([
                'status' => GoodsEvent::STATUS_WAIT_RUN,
                'platform' => $shop['platform_type'],
                'shop_id' => $shop['id'],
                'event_type' => [GoodsEvent::EVENT_TYPE_ADD_LISTINGS]
            ])->limit($limit)->all();
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
                $goods_sku = $goods_child['sku_no'];
                $info = [
                    'sku' => $goods_sku,
                    'stock' => 1000,
                    'price' => $price,
                    //'opc' => $goods_shop['platform_goods_opc'],
                    'ean' => $goods_shop['ean']
                ];
                $data[] = $info;
            }

            if (!empty($data)){
                try {
                    $api_service = FApiService::factory($shop);
                    //CommonUtil::logs('error: shop_id:' . $shop['id']  . json_encode($data), 'onbuy_goods_api_error');
                    $result = $api_service->addListings($data);
                    if(empty($result)){
                        GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE], ['id' => $ids]);
                    }
                    GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_SUCCESS], ['id' => $ids]);
                } catch (\Exception $e) {
                    CommonUtil::logs('offers error: shop_id:' . $shop['id'] . ' platform_type:' . $shop['platform_type'] . $e->getMessage(), 'add_cd_products_error');
                    GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE,'error_msg'=>$e->getMessage()], ['id' => $ids]);
                    continue;
                }
            }

            if(count($order_event_lists) < $limit){
                break;
            }
        }
    }

    /**
     * 分类
     */
    public function actionCategoryTree()
    {
        $sp = ' / ';
        $shop = Shop::find()->where(['platform_type'=>Base::PLATFORM_CDISCOUNT])->andWhere(['!=','client_key',''])->one();
        $api_service = FApiService::factory($shop);
        $tree = $api_service->getAllowedCategoryTree();

        foreach ($tree['ChildrenCategoryList']['CategoryTree'] as $v1){
            $name1 = empty($v1['Name'])?'':$v1['Name'];
            foreach ($this->childCate($v1) as $v2){
                $name2 = $v2['Name'];
                $child2 = $this->childCate($v2);
                if(empty($child2)){
                    $code2 = $v2['Code'];
                    echo $code2.'::'.$name1.$sp.$name2."\n";
                    $pl_cate =PlatformCategory::find()->where(['id'=>$code2,'platform_type'=>Base::PLATFORM_CDISCOUNT])->one();
                    if(empty($pl_cate)){
                        $pl_cate = new PlatformCategory();
                        $pl_cate->platform_type = Base::PLATFORM_CDISCOUNT;
                        $pl_cate->id = $code2;
                        $pl_cate->name = $name2;
                        $pl_cate->crumb = $name1.$sp.$name2;
                        $pl_cate->status = 1;
                        $pl_cate->save();
                    }
                }else {
                    foreach ($child2 as $v3) {
                        $name3 = $v3['Name'];
                        $child3 = $this->childCate($v3);
                        if (empty($child3)) {
                            $code3 = $v3['Code'];
                            echo $code3 . '::' . $name1 . $sp . $name2 . $sp . $name3 . "\n";
                            $pl_cate =PlatformCategory::find()->where(['id'=>$code3,'platform_type'=>Base::PLATFORM_CDISCOUNT])->one();
                            if(empty($pl_cate)){
                                $pl_cate = new PlatformCategory();
                                $pl_cate->platform_type = Base::PLATFORM_CDISCOUNT;
                                $pl_cate->id = $code3;
                                $pl_cate->name = $name3;
                                $pl_cate->crumb = $name1 . $sp . $name2 . $sp . $name3;
                                $pl_cate->status = 1;
                                $pl_cate->save();
                            }
                        } else {
                            foreach ($child3 as $v4) {
                                $name4 = $v4['Name'];
                                $code4 = $v4['Code'];
                                echo $code4 . '::' . $name1 . $sp . $name2 . $sp . $name3 . $sp . $name4 . "\n";
                                $pl_cate =PlatformCategory::find()->where(['id'=>$code4,'platform_type'=>Base::PLATFORM_CDISCOUNT])->one();
                                if(empty($pl_cate)){
                                    $pl_cate = new PlatformCategory();
                                    $pl_cate->platform_type = Base::PLATFORM_CDISCOUNT;
                                    $pl_cate->id = $code4;
                                    $pl_cate->name = $name4;
                                    $pl_cate->crumb = $name1 . $sp . $name2 . $sp . $name3 . $sp . $name4;
                                    $pl_cate->status = 1;
                                    $pl_cate->save();
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public function childCate($category_tree){
        if(empty($category_tree['ChildrenCategoryList']) || empty($category_tree['ChildrenCategoryList']['CategoryTree'])){
            return [];
        }
        if(!empty($category_tree['ChildrenCategoryList']['CategoryTree']['Name'])){
            return [$category_tree['ChildrenCategoryList']['CategoryTree']];
        }
        return $category_tree['ChildrenCategoryList']['CategoryTree'];
    }

    /**
     * 批量添加商品
     */
    public function actionBatchAddGoods()
    {
        $shop_lists = Shop::find()->where(['platform_type' => Base::PLATFORM_CDISCOUNT])->asArray()->all();
        foreach ($shop_lists as $shop) {
            if (empty($shop['client_key'])) {
                continue;
            }

            $goods_event_lists = GoodsEvent::find()->where([
                'status' => GoodsEvent::STATUS_WAIT_RUN,
                'platform' => $shop['platform_type'],
                'shop_id' => $shop['id'],
                'event_type' => [GoodsEvent::EVENT_TYPE_ADD_GOODS]
            ])->andWhere(['<', 'plan_time', time()])->limit(500)->orderBy('plan_time asc')->all();
            if (empty($goods_event_lists)) {
                continue;
            }
            $cgoods_no_maps = ArrayHelper::map($goods_event_lists, 'cgoods_no', 'id');
            $goods_nos = ArrayHelper::getColumn($goods_event_lists, 'goods_no');
            $cgoods_nos = ArrayHelper::getColumn($goods_event_lists, 'cgoods_no');
            $goods = Goods::find()->where(['goods_no' => $goods_nos])->indexBy('goods_no')->asArray()->all();
            $good_child_lists = GoodsChild::find()->where(['cgoods_no' => $cgoods_nos])->asArray()->all();
            $good_lists = [];
            foreach ($good_child_lists as $goods_child) {
                if (empty($goods[$goods_child['goods_no']])) {
                    continue;
                }
                $info = $goods[$goods_child['goods_no']];
                $info['cgoods_no'] = $goods_child['cgoods_no'];
                $info['sku_no'] = $goods_child['sku_no'];
                $info['ccolour'] = $goods_child['colour'];
                $info['csize'] = $goods_child['size'];
                if (!empty($goods_child['goods_img'])) {
                    $image = json_decode($info['goods_img'], true);
                    $image = empty($image) || !is_array($image) ? [] : $image;
                    $image[0]['img'] = $goods_child['goods_img'];
                    $info['goods_img'] = json_encode($image);
                }
                $good_lists[$goods_child['cgoods_no']] = $info;
            }

            try {
                $j = 1;
                $good_lists_cur = [];
                $ids = [];
                foreach ($good_lists as $goods_v) {
                    if ($j > 100) {
                        continue;
                    }
                    if (empty($cgoods_no_maps[$goods_v['cgoods_no']])) {
                        continue;
                    }
                    $id = $cgoods_no_maps[$goods_v['cgoods_no']];
                    if ($goods_v['source_method'] == GoodsService::SOURCE_METHOD_OWN && !(new HelperStamp(Goods::$sync_status_map))->isExistStamp($goods_v['sync_img'], Goods::SYNC_STATUS_IMG)) {//未上传图片的延迟12个小时
                        GoodsEvent::updateAll([
                            'plan_time' => time() + 12 * 60 * 60,
                            'status' => GoodsEvent::STATUS_WAIT_RUN
                        ], ['id' => $id]);
                        break;
                    }

                    $goods_platform_class = FGoodsService::factory($shop['platform_type']);
                    $platform_goods = $goods_platform_class->model()->find()->where(['goods_no' => $goods_v['goods_no']])->one();
                    if ($platform_goods['status'] != GoodsService::PLATFORM_GOODS_STATUS_VALID) {//未翻译延迟处理
                        GoodsEvent::updateAll([
                            'plan_time' => time() + 7 * 24 * 60 * 60,
                            'status' => GoodsEvent::STATUS_WAIT_RUN
                        ], ['id' => $id]);
                        continue;
                    }

                    $j++;
                    $good_lists_cur[] = $goods_v;
                    $ids[] = $id;
                }

                if(empty($ids)) {
                    continue;
                }

                $api_service = FApiService::factory($shop);
                list ($check, $time) = $api_service->checkFrequencyLimit(GoodsEvent::EVENT_TYPE_ADD_GOODS);
                if ($check) {
                    continue;
                }

                GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_RUNNING], ['id' => $ids]);
                $result = $api_service->batchAddGoods($good_lists_cur);
                if (empty($result)) {
                    GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE], ['id' => $ids]);
                } else {
                    GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_RUNNING_RESULT, 'queue_id' => $result, 'plan_time' => time() + 30 * 60], ['id' => $ids]);
                }
            } catch (\Exception $e) {
                CommonUtil::logs('error: shop_id:' . $shop['id'] . ' platform_type:' . $shop['platform_type'] . $e->getMessage(), 'add_cd_products');
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
        $shop_lists = Shop::find()->where(['platform_type'=>Base::PLATFORM_CDISCOUNT])->asArray()->all();
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
                            foreach ($result as $result_v) {
                                if (empty($result_v['PropertyList'])) {
                                    continue;
                                }
                                $sku = $result_v['SKU'];
                                $goods_child = GoodsChild::find()->where(['sku_no' => $sku])->one();
                                $cgoods_no = $goods_child['cgoods_no'];
                                if(empty($cgoods_nos[$cgoods_no])){
                                    continue;
                                }
                                if (isset($result_v['PropertyList']['ProductReportPropertyLog']['LogMessage'])) {
                                    $log_message = $result_v['PropertyList']['ProductReportPropertyLog']['LogMessage'];
                                    $log_message_arr = explode('|', $log_message);
                                    if ($log_message_arr[2] == 'OK') {
                                        GoodsShop::updateAll(['platform_goods_opc'=>$log_message_arr[3]],['cgoods_no'=>$cgoods_no,'shop_id'=>$shop['id']]);
                                        GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_SUCCESS],
                                            ['id' => $cgoods_nos[$cgoods_no]]);
                                        $goods_shop = GoodsShop::find()->where(['cgoods_no'=>$cgoods_no,'shop_id'=>$shop['id']])->all();
                                        foreach ($goods_shop as $gods_shop_v) {
                                            GoodsEventService::addEvent($gods_shop_v, GoodsEvent::EVENT_TYPE_ADD_LISTINGS, time() + 5 * 60);
                                        }
                                    } else {
                                        GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE, 'error_msg' => 'queue:'.$log_message],
                                            ['id' => $cgoods_nos[$cgoods_no]]);
                                    }
                                }

                                $log_message = [];
                                foreach ($result_v['PropertyList']['ProductReportPropertyLog'] as $logs) {
                                    if (empty($logs['LogMessage'])) {
                                        continue;
                                    }
                                    $log_message[] = $logs['LogMessage'];
                                }
                                if (!empty($log_message)) {
                                    GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE, 'error_msg' => 'queue:'.implode('#', $log_message)], ['id' => $cgoods_nos[$cgoods_no]]);
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        CommonUtil::logs('queue error: shop_id:' . $shop['id'] . ' platform_type:' . $shop['platform_type'] . $e->getMessage(), 'add_cd_products_error');
                        GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_FAILURE, 'error_msg' => 'queue:'.$e->getMessage()], ['id' => $ids]);
                        continue;
                    }
                }
            }
        }
    }

    /**
     * 跟卖
     * @param $file
     * @param int $gm_shop_id 跟卖店铺
     * @param int $shop_id 新店铺
     * @throws \yii\base\Exception
     */
    public function actionSellShop($file,$gm_shop_id, $shop_id)
    {
        $file = fopen($file, "r") or exit("Unable to open file!");
        while (!feof($file)) {
            $line = trim(fgets($file));
            if (empty($line)) continue;

            list($sku_no,$ean) = explode(',', $line);
            if (empty($sku_no)) {
                continue;
            }

            $goods_child = GoodsChild::find()->where(['sku_no'=>$sku_no])->one();
            $goods = Goods::find()->where(['goods_no'=>$goods_child['goods_no']])->one();
            if($goods['source_method'] != GoodsService::SOURCE_METHOD_OWN){
                echo $shop_id . ',' . $sku_no . ',' . $ean . ",商品失败\n";
                continue;
            }

            /*if($goods['status'] == Goods::GOODS_STATUS_WAIT_MATCH && $goods['source_platform_type'] == Base::PLATFORM_ONBUY) {
                continue;
            }

            if($goods['status'] == Goods::GOODS_STATUS_INVALID) {
                continue;
            }*/

            $goods_shop = GoodsShop::find()->where([
                //'shop_id'=>$gm_shop_id,
                'platform_type'=>Base::PLATFORM_CDISCOUNT,'cgoods_no'=>$goods_child['cgoods_no']])->one();
            if(empty($goods_shop)){
                echo $shop_id . ',' . $sku_no . ',' . $ean . ",未认领\n";
                continue;
            }

            $goods_shop_new = GoodsShop::find()->where(['shop_id'=>$shop_id,'cgoods_no'=>$goods_child['cgoods_no']])->one();
            if(!empty($goods_shop_new)){
                echo $shop_id . ',' . $sku_no . ',' . $ean . ",已认领\n";
                continue;
            }

            $data = [
                'goods_no' => $goods_shop['goods_no'],
                'cgoods_no' => $goods_shop['cgoods_no'],
                'platform_type' => $goods_shop['platform_type'],
                'shop_id' => $shop_id,
                'country_code' => '',
                'ean' => $ean,
                'status' => 0,
                'price' => $goods_shop['price'],
                'original_price' => $goods_shop['price'],
                'discount' => $goods_shop['discount'],
                'platform_goods_opc' => '',
                'platform_sku_no' => $sku_no,
                'keywords_index' => $goods_shop['keywords_index'],
                'admin_id' => 0
            ];
            GoodsShop::add($data);

            //GoodsEventService::addEvent($goods_shop['platform_type'], $shop_id, $goods_shop['goods_no'], GoodsEvent::EVENT_TYPE_ADD_LISTINGS);
            echo $shop_id . ',' . $sku_no . ',' . $ean . "\n";
        }
        fclose($file);
        echo "all done\n";
    }


    /**
     * 批量查询队列
     */
    public function actionGetQueueResult($shop_id,$queue_id)
    {
        $shop = Shop::find()->where(['platform_type'=>Base::PLATFORM_CDISCOUNT,'id'=>$shop_id])->asArray()->one();
        $api_service = FApiService::factory($shop);
        $result = $api_service->getQueue($queue_id);
        foreach ($result as $result_v) {
            if (empty($result_v['PropertyList'])) {
                continue;
            }
            $sku = $result_v['SKU'];
            $goods_child = GoodsChild::find()->where(['sku_no' => $sku])->one();
            $cgoods_no = $goods_child['cgoods_no'];
            if(empty($cgoods_nos[$cgoods_no])){
                continue;
            }
            if (isset($result_v['PropertyList']['ProductReportPropertyLog']['LogMessage'])) {
                $log_message = $result_v['PropertyList']['ProductReportPropertyLog']['LogMessage'];
                $log_message_arr = explode('|', $log_message);
                if ($log_message_arr[2] == 'OK') {
                    GoodsShop::updateAll(['platform_goods_opc'=>$log_message_arr[3]],['cgoods_no'=>$cgoods_no,'shop_id'=>$shop['id']]);
                }
            }
        }
    }

}