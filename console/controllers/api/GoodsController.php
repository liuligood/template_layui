<?php
namespace console\controllers\api;

use common\components\CommonUtil;
use common\components\HelperStamp;
use common\components\statics\Base;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsOzon;
use common\models\goods_shop\GoodsShopOverseasWarehouse;
use common\models\GoodsShop;
use common\models\GoodsEvent;
use common\models\GoodsShopExpand;
use common\models\platform\PlatformCategory;
use common\models\platform\PlatformCategoryAttribute;
use common\models\Shop;
use common\services\api\GoodsEventService;
use common\services\FApiService;
use common\services\goods\FGoodsService;
use common\services\goods\GoodsErrorSolutionService;
use common\services\goods\GoodsService;
use yii\console\Controller;
use yii\helpers\ArrayHelper;

class GoodsController extends Controller
{

    /**
     * @param $shop_id
     * @param $sku_no
     * @return void
     * @throws \yii\base\Exception
     */
    public function actionGetOne($shop_id,$sku_no)
    {
        $shop = Shop::find()->where(['id'=>$shop_id])->asArray()->one();
        $api_service = FApiService::factory($shop);
        $result = $api_service->getProductsToAsin($sku_no);
        echo CommonUtil::jsonFormat($result);
        exit;
    }

    /**
     * @param $fun
     * @param $shop_id
     * @param $param
     * @return void
     * @throws \yii\base\Exception
     */
    public function actionTest($fun,$shop_id,$param)
    {
        $shop = Shop::find()->where(['id'=>$shop_id])->asArray()->one();
        $api_service = FApiService::factory($shop);
        $result = $api_service->$fun($param);
        echo CommonUtil::jsonFormat($result);
        exit;
    }

    /**
     * 订单事件
     */
    public function actionGoodsEvent($limit = 1,$platform = null,$shop_id = null,$cgoods_no = null)
    {
        $where = [];
        if(!empty($cgoods_no)) {
            $where['cgoods_no'] = $cgoods_no;
        }
        $where['platform'] = [Base::PLATFORM_REAL_DE,Base::PLATFORM_FRUUGO,Base::PLATFORM_ALLEGRO,Base::PLATFORM_OZON,Base::PLATFORM_FYNDIQ,Base::PLATFORM_EPRICE,Base::PLATFORM_JDID,Base::PLATFORM_HEPSIGLOBAL,Base::PLATFORM_B2W,Base::PLATFORM_NOCNOC,Base::PLATFORM_TIKTOK,Base::PLATFORM_MICROSOFT,Base::PLATFORM_WALMART,Base::PLATFORM_WILDBERRIES];
        if(!empty($platform)){
            $where['platform'] = explode(',',$platform);
        }
        if(!empty($shop_id)){
            unset($where['platform']);
            $where['shop_id'] = explode(',',$shop_id);
        }
        $where['status'] = GoodsEvent::STATUS_WAIT_RUN;
        $order_event_lists = GoodsEvent::find()->where($where)->andWhere(['<','plan_time',time()]);
        $order_event_lists = $order_event_lists->orderBy('plan_time asc')->offset(100*($limit-1))->limit(100)->all();
        if(empty($order_event_lists)){
            sleep(200);
            return;
        }
        $ids = ArrayHelper::getColumn($order_event_lists, 'id');
        $goods_nos = ArrayHelper::getColumn($order_event_lists, 'goods_no');
        $goods_lists = Goods::find()->where(['goods_no' => $goods_nos])->indexBy('goods_no')->asArray()->all();
        $cgoods_nos = ArrayHelper::getColumn($order_event_lists, 'cgoods_no');
        $goods_childs = GoodsChild::find()->where(['cgoods_no'=>$cgoods_nos])->indexBy('cgoods_no')->asArray()->all();
        $good_child_lists = [];
        foreach ($goods_childs as $goods_child) {
            if (empty($goods_lists[$goods_child['goods_no']])) {
                continue;
            }
            $info = $goods_lists[$goods_child['goods_no']];
            $info = (new GoodsService())->dealGoodsInfo($info,$goods_child);
            /*$info['cgoods_no'] = $goods_child['cgoods_no'];
            $info['sku_no'] = $goods_child['sku_no'];
            $info['csize'] = $goods_child['size'];
            $info['ccolour'] = $goods_child['colour'];
            if (!empty($goods_child['goods_img'])) {
                $image = json_decode($info['goods_img'], true);
                $image = empty($image) || !is_array($image) ? [] : $image;
                $image[0]['img'] = $goods_child['goods_img'];
                $info['goods_img'] = json_encode($image);
            }*/
            $good_child_lists[$goods_child['cgoods_no']] = $info;
        }
        GoodsEvent::updateAll(['status' => GoodsEvent::STATUS_RUNNING], ['id' => $ids]);
        $shop_where = [];
        $shop_where['status'] = [Shop::STATUS_VALID,Shop::STATUS_PAUSE];
        $shop_where['api_assignment'] = (new HelperStamp(Shop::$api_assignment_maps))->getStamps(Shop::GOODS_ASSIGNMENT);
        $shop_lists = Shop::find()->where($shop_where)->indexBy('id')->asArray()->all();

        $error_result = function ($goods_event_v,$msg,$other_opt = true) {
            $goods_event_v->status = GoodsEvent::STATUS_FAILURE;
            $goods_event_v->error_msg = $msg;
            $goods_event_v->save();

            //添加修改商品 额外处理
            if($other_opt && in_array($goods_event_v['event_type'],[GoodsEvent::EVENT_TYPE_ADD_GOODS,GoodsEvent::EVENT_TYPE_UPDATE_GOODS])) {
                $goods_shop_id = $goods_event_v['goods_shop_id'];
                $goods_shop_expand = GoodsShopExpand::find()->where(['goods_shop_id'=>$goods_shop_id])->one();
                if(!empty($goods_shop_expand)) {
                    $goods_shop = GoodsShop::find()->where(['id'=>$goods_shop_id])->one();
                    $goods_shop->status = GoodsShop::STATUS_FAIL;
                    $goods_shop->save();

                    $error = '请求返回错误';
                    if (!empty($result['errors'])) {
                        $error = $result['errors'];
                    }
                    if (!empty($result['error']) && !empty($result['error_description'])) {
                        $error = $result['error_description'];
                    }
                    if(!empty($error)) {
                        $goods_shop_expand->error_msg = json_encode($error, JSON_UNESCAPED_UNICODE);
                        $goods_shop_expand->save();
                        (new GoodsErrorSolutionService())->addError($goods_shop['platform_type'], $goods_shop['id'], $error);
                    }
                }
            }
        };

        foreach ($order_event_lists as $v) {
            try {
                $error = '';
                if(!empty($v['cgoods_no'])) {
                    if (empty($good_child_lists[$v['cgoods_no']])) {
                        $error_result($v,'失败');
                        continue;
                    }
                    $goods = $good_child_lists[$v['cgoods_no']];
                    $cgoods_no = $goods['cgoods_no'];
                } else {
                    $goods = $goods_lists[$v['goods_no']];
                    $cgoods_no = '';
                }

                //real 下线
                if ($v['platform'] == Base::PLATFORM_REAL_DE) {
                    $error_result($v,'失败');
                    continue;
                }

                if(empty($shop_lists[$v['shop_id']])) {
                    $error_result($v,'失败');
                    continue;
                }
                
                $shop = $shop_lists[$v['shop_id']];
                echo date('Y-m-d H:i:s').' event_type:' . $v['event_type'] . ' shop_id:' . $shop['id'] . ' platform_type:' . $shop['platform_type'] . ' cgoods_no:' . $cgoods_no ."\n";
                CommonUtil::logs('event_type:' . $v['event_type'] . ' shop_id:' . $shop['id'] . ' platform_type:' . $shop['platform_type'] . ' cgoods_no:' . $cgoods_no, 'goods_api');

                if (empty($shop['client_key'])) {
                    $error_result($v,'client_key为空');
                    continue;
                }

                //店铺没有商品权限
                if (!((new HelperStamp(Shop::$api_assignment_maps))->isExistStamp($shop['api_assignment'],Shop::GOODS_ASSIGNMENT))) {
                    $error_result($v,'client_key为空');
                    continue;
                }

                try {
                    $api_service = FApiService::factory($shop)->setGoodsEvent($v);
                } catch (\Exception $e) {
                    CommonUtil::logs('error: shop_id:' . $shop['id'] . ' platform_type:' . $shop['platform_type'] . $e->getMessage(), 'goods_api_error');
                    $error_result($v,$e->getMessage());
                    continue;
                }

                //是否有对应事件
                if (!GoodsEventService::hasEvent($v['event_type'],$shop['platform_type'])) {
                    $v->status = GoodsEvent::STATUS_SUCCESS;
                    $v->error_msg = '不需要执行';
                    $v->save();
                    continue;
                }

                switch ($v['event_type']) {
                    case GoodsEvent::EVENT_TYPE_ADD_GOODS:
                        try {
                            if(in_array($shop['platform_type'],[Base::PLATFORM_MICROSOFT,Base::PLATFORM_WALMART])) {
                                GoodsEvent::updateAll([
                                    'plan_time' => time() + 7 * 24 * 60 * 60,
                                    'status' => GoodsEvent::STATUS_WAIT_RUN
                                ], ['id' => $v['id']]);
                                break;
                            }
                            if($goods['status'] == Goods::GOODS_STATUS_INVALID) {
                                $error_result($v,'该商品已经禁用');
                                break;
                            }

                            $goods_platform_class = FGoodsService::factory($shop['platform_type']);
                            $platform_goods = $goods_platform_class->model()->find()->where(['goods_no' => $goods['goods_no']])->one();
                            if(empty($platform_goods)){
                                $error_result($v,'该商品已经删除');
                                break;
                            }

                            if ($platform_goods['status'] != GoodsService::PLATFORM_GOODS_STATUS_VALID && $shop['platform_type'] != Base::PLATFORM_ALLEGRO) {//未翻译延迟处理
                                CommonUtil::logs('platform_type:'.$shop['platform_type'].' goods_no:' . $goods['goods_no'] .' cgoods_no:' . $goods['cgoods_no'] . ' shop_id:' . $shop['id'] ." 未翻译延迟",'goods_event_add_goods');
                                GoodsEvent::updateAll([
                                    'plan_time' => time() + 7 * 24 * 60 * 60,
                                    'status' => GoodsEvent::STATUS_WAIT_RUN
                                ], ['id' => $v['id']]);
                                break;
                            }

                            if (empty($platform_goods['o_category_name'])) {
                                if ($shop['platform_type'] == Base::PLATFORM_OZON) {
                                    $goods_shop_expand = GoodsShopExpand::find()->where(['goods_shop_id' => $v['goods_shop_id']])->one();
                                    if (empty($goods_shop_expand['o_category_id'])) {
                                        GoodsEvent::updateAll([
                                            'plan_time' => time() + 6 * 60 * 60,
                                            'status' => GoodsEvent::STATUS_WAIT_RUN
                                        ], ['id' => $v['id']]);
                                        CommonUtil::logs('platform_type:' . $shop['platform_type'] . ' goods_no:' . $goods['goods_no'] . ' cgoods_no:' . $goods['cgoods_no'] . ' shop_id:' . $shop['id'] . " 分类不能为空", 'goods_event_add_goods');
                                        break;
                                    }
                                } else {
                                    GoodsEvent::updateAll([
                                        'plan_time' => time() + 6 * 60 * 60,
                                        'status' => GoodsEvent::STATUS_WAIT_RUN
                                    ], ['id' => $v['id']]);
                                    CommonUtil::logs('platform_type:' . $shop['platform_type'] . ' goods_no:' . $goods['goods_no'] . ' cgoods_no:' . $goods['cgoods_no'] . ' shop_id:' . $shop['id'] . " 分类不能为空", 'goods_event_add_goods');
                                    break;
                                }
                            }

                            if ($goods['source_method'] == GoodsService::SOURCE_METHOD_OWN && !(new HelperStamp(Goods::$sync_status_map))->isExistStamp($goods['sync_img'],Goods::SYNC_STATUS_IMG) && !in_array($shop['platform_type'],[Base::PLATFORM_B2W,Base::PLATFORM_ALLEGRO])) {//未上传图片的延迟12个小时
                                GoodsEvent::updateAll([
                                    'plan_time' => time() + 12 * 60 * 60,
                                    'status' => GoodsEvent::STATUS_WAIT_RUN
                                ], ['id' => $v['id']]);
                                CommonUtil::logs('platform_type:'.$shop['platform_type'].' goods_no:' . $goods['goods_no'] .' cgoods_no:' . $goods['cgoods_no']. ' shop_id:' . $shop['id'] ." 未同步图片",'goods_event_add_goods');
                                break;
                            }

                            list ($check, $time) = $api_service->checkFrequencyLimit($v['event_type']);
                            if($check){
                                GoodsEvent::updateAll([
                                    'plan_time' => time() + $time,
                                    'status' => GoodsEvent::STATUS_WAIT_RUN
                                ], ['id' => $v['id']]);
                                break;
                            }
                            $result = $api_service->addGoods($goods);
                        } catch (\Exception $e) {
                            $result = false;
                            $error_result($v,$e->getMessage());
                            CommonUtil::logs('platform_type:'.$shop['platform_type'].' goods_no:' . $goods['goods_no'] .' cgoods_no:' . $goods['cgoods_no']. ' shop_id:' . $shop['id'] ." ".$e->getFile().$e->getLine().$e->getMessage(),'goods_event_add_goods');
                            break;
                        }

                        if ($result) {
                            if(is_array($result)) {
                                if (!empty($result['queue_id'])) {
                                    $v->queue_id = $result['queue_id'];
                                    $v->plan_time = time() + 30 * 60;
                                    $v->status = GoodsEvent::STATUS_RUNNING_RESULT;
                                }else if (!empty($result['plan_time'])) {
                                    $v->plan_time = $result['plan_time'];
                                    $v->status = GoodsEvent::STATUS_WAIT_RUN;
                                } else {
                                    $v->status = GoodsEvent::STATUS_SUCCESS;
                                }
                                $v->save();
                            } else {
                                $v->status = GoodsEvent::STATUS_SUCCESS;
                                $v->save();
                            }
                        } else {
                            $error = empty($error) ? '失败' : $error;
                            $error_result($v,$error,false);
                        }
                        break;
                    case GoodsEvent::EVENT_TYPE_UPDATE_GOODS://更新商品
                        try {
                            $goods_platform_class = FGoodsService::factory($shop['platform_type']);
                            $platform_goods = $goods_platform_class->model()->find()->where(['goods_no' => $goods['goods_no']])->one();
                            if(empty($platform_goods)){
                                $v->status = GoodsEvent::STATUS_FAILURE;
                                $v->error_msg = '该商品已经删除';
                                $v->save();
                                break;
                            }
                            
                            if ($platform_goods['status'] != GoodsService::PLATFORM_GOODS_STATUS_VALID && $shop['platform_type'] != Base::PLATFORM_OZON) {//未翻译延迟处理
                                GoodsEvent::updateAll([
                                    'plan_time' => time() + 7 * 24 * 60 * 60,
                                    'status' => GoodsEvent::STATUS_WAIT_RUN
                                ], ['id' => $v['id']]);
                                break;
                            }

                            /*if (empty($platform_goods['goods_short_name']) && $shop['platform_type'] == Base::PLATFORM_OZON) {//ozon未设置标题
                                GoodsEvent::updateAll([
                                    'plan_time' => time() + 7 * 24 * 60 * 60,
                                    'status' => GoodsEvent::STATUS_WAIT_RUN
                                ], ['id' => $v['id']]);
                                break;
                            }*/

                            /*if ($goods['source_method'] == GoodsService::SOURCE_METHOD_OWN && $goods['sync_img'] == 0) {//未上传图片的延迟12个小时
                                GoodsEvent::updateAll([
                                    'plan_time' => time() + 12 * 60 * 60,
                                    'status' => GoodsEvent::STATUS_WAIT_RUN
                                ], ['id' => $v['id']]);
                                break;
                            }*/
                            $event_type = $v['event_type'];
                            if($shop['platform_type'] == Base::PLATFORM_OZON) {
                                $event_type = GoodsEvent::EVENT_TYPE_ADD_GOODS;
                            }
                            list ($check, $time) = $api_service->checkFrequencyLimit($event_type);
                            if($check){
                                GoodsEvent::updateAll([
                                    'plan_time' => time() + $time,
                                    'status' => GoodsEvent::STATUS_WAIT_RUN
                                ], ['id' => $v['id']]);
                                break;
                            }
                            $result = $api_service->updateGoods($goods);
                        } catch (\Exception $e) {
                            $result = false;
                            $error = $e->getMessage();
                        }
                        if ($result) {
                            $v->status = GoodsEvent::STATUS_SUCCESS;
                            $v->save();
                        } else {
                            $v->status = GoodsEvent::STATUS_FAILURE;
                            $v->error_msg = empty($error) ? '失败' : $error;
                            $v->save();
                        }
                        break;
                    case GoodsEvent::EVENT_TYPE_DEL_GOODS://删除商品
                        try {
                            $goods_shop = GoodsShop::find()->where(['shop_id' => $v['shop_id'], 'id' => $v['goods_shop_id']])->one();
                            $exist = false;
                            if ($goods_shop['status'] == GoodsShop::STATUS_DELETE) {
                                $result = $api_service->delGoods($goods_shop);
                                if ($result && $result !== 2) {
                                    $platform_type = $goods_shop->platform_type;
                                    $goods_no = $goods_shop->goods_no;
                                    $country_code = $goods_shop->country_code;
                                    if ($goods_shop->delete()) {
                                        GoodsShopExpand::deleteAll(['goods_shop_id'=>$goods_shop['id']]);
                                        GoodsShopOverseasWarehouse::deleteAll(['goods_shop_id'=>$goods_shop['id']]);
                                        /*$where = ['platform_type' => $platform_type, 'goods_no' => $goods_no];
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
                                        }*/
                                    }
                                }
                                $exist = true;
                            }

                            if (!$exist) {
                                $result = false;
                            }
                        } catch (\Exception $e) {
                            $result = false;
                            $error = $e->getMessage();
                        }
                        if ($result) {
                            $v->status = GoodsEvent::STATUS_SUCCESS;
                            $v->save();
                        } else {
                            $v->status = GoodsEvent::STATUS_FAILURE;
                            $v->error_msg = empty($error) ? '删除失败' : $error;
                            $v->save();
                        }
                        break;
                    case GoodsEvent::EVENT_TYPE_ADD_VARIANT://变体
                        try {
                            $result = $api_service->addVariant($goods);
                        } catch (\Exception $e) {
                            $result = false;
                            $error = $e->getMessage();
                        }
                        if ($result) {
                            $v->status = GoodsEvent::STATUS_SUCCESS;
                            $v->save();
                        } else {
                            $v->status = GoodsEvent::STATUS_FAILURE;
                            $v->error_msg = empty($error) ? '失败' : $error;
                            $v->save();
                        }
                        break;
                    case GoodsEvent::EVENT_TYPE_UPLOAD_IMAGE://上传图片
                        try {
                            $result = $api_service->addGoodsImage($goods);
                        } catch (\Exception $e) {
                            $result = false;
                            $error = $e->getMessage();
                        }
                        if ($result) {
                            $v->status = GoodsEvent::STATUS_SUCCESS;
                            $v->save();
                        } else {
                            $v->status = GoodsEvent::STATUS_FAILURE;
                            $v->error_msg = empty($error) ? '失败' : $error;
                            $v->save();
                        }
                        break;
                    case GoodsEvent::EVENT_TYPE_GET_GOODS_ID:
                        try {
                            $result = $api_service->getGoodsId($goods);
                        } catch (\Exception $e) {
                            $result = false;
                            $error = $e->getMessage();
                        }
                        if ($result) {
                            $v->status = GoodsEvent::STATUS_SUCCESS;
                            $v->save();
                        } else {
                            $v->status = GoodsEvent::STATUS_FAILURE;
                            $v->error_msg = empty($error) ? '获取id失败' : $error;
                            $v->save();
                        }
                        break;
                    case GoodsEvent::EVENT_TYPE_ADD_GOODS_CONTENT://添加商品详情
                        try {
                            $result = $api_service->addGoodsContent($goods);
                        } catch (\Exception $e) {
                            $result = false;
                            $error = $e->getMessage();
                        }
                        if ($result) {
                            $v->status = GoodsEvent::STATUS_SUCCESS;
                            $v->save();
                        } else {
                            $v->status = GoodsEvent::STATUS_FAILURE;
                            $v->error_msg = empty($error) ? '失败' : $error;
                            $v->save();
                        }
                        break;
                    case GoodsEvent::EVENT_TYPE_UPDATE_PRICE:
                    case GoodsEvent::EVENT_TYPE_UPDATE_STOCK:
                        if(in_array($shop['platform_type'],[Base::PLATFORM_MICROSOFT]) && $v['event_type'] == GoodsEvent::EVENT_TYPE_UPDATE_PRICE) {
                            GoodsEvent::updateAll([
                                'plan_time' => time() + 7 * 24 * 60 * 60,
                                'status' => GoodsEvent::STATUS_WAIT_RUN
                            ], ['id' => $v['id']]);
                            break;
                        }
                        $stock = $goods['stock'] == Goods::STOCK_YES ? true : false;
                        //禁用的更新为下架
                        if ($goods['status'] == Goods::GOODS_STATUS_INVALID) {
                            $stock = false;
                        }

                        if ($goods['source_method'] == GoodsService::SOURCE_METHOD_OWN) {//自建的更新价格，禁用状态的更新为下架
                            $goods_shop = GoodsShop::find()->where(['id' => $v['goods_shop_id'], 'shop_id' => $v['shop_id']])->one();
                            $price = $goods_shop['price'];
                            if ($goods_shop['status'] == GoodsShop::STATUS_DELETE) {
                                $stock = false;
                            }

                        } else {
                            $price = $goods['price'];

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
                                break;
                            }

                            //英国亚马逊全下架
                            if ($goods['source_platform_type'] == Base::PLATFORM_AMAZON_CO_UK) {
                                $stock = false;
                            }
                        }

                        $error = '';
                        try {
                            CommonUtil::logs(' sku_no: ' . $goods['sku_no'] . ' shop_id:' . $shop['id'] . ' platform_type:' . $shop['platform_type'] . ' 数据：' . json_encode([
                                    'price' => $price,
                                    'stock' => $stock
                                ]), 'goods_api');
                            if (in_array($shop['platform_type'], [Base::PLATFORM_ALLEGRO, Base::PLATFORM_OZON, Base::PLATFORM_FYNDIQ,Base::PLATFORM_JDID,Base::PLATFORM_COUPANG,Base::PLATFORM_LINIO,Base::PLATFORM_JUMIA,Base::PLATFORM_TIKTOK,Base::PLATFORM_WALMART]) && $v['event_type'] == GoodsEvent::EVENT_TYPE_UPDATE_PRICE) {
                                $result = $api_service->updatePrice($goods, $price);
                            } else {
                                if($stock == false) {
                                    $price = null;
                                }
                                $result = $api_service->updateStock($goods, $stock, $price);
                            }
                        } catch (\Exception $e) {
                            $result = 0;
                            $error = $e->getMessage();
                        }
                        if ($result == 1) {
                            $v->status = GoodsEvent::STATUS_SUCCESS;
                            $v->save();
                        } else {
                            if ($result == -1) {
                                $v->status = GoodsEvent::STATUS_FAILURE;
                                $v->error_msg = '无报价数据';
                                $v->save();
                            } else {
                                $v->status = GoodsEvent::STATUS_FAILURE;
                                $v->error_msg = empty($error) ? '更新失败' : $error;
                                $v->save();
                            }
                        }
                        break;
                    case GoodsEvent::EVENT_TYPE_RESUME_GOODS://恢复商品
                        try {
                            $goods_shop = GoodsShop::find()->where(['shop_id' => $v['shop_id'], 'id' => $v['goods_shop_id']])->one();
                            $result = $api_service->resumeGoods($goods_shop);
                        } catch (\Exception $e) {
                            $result = false;
                            $error = $e->getMessage();
                        }
                        if ($result) {
                            $v->status = GoodsEvent::STATUS_SUCCESS;
                            $v->save();
                        } else {
                            $v->status = GoodsEvent::STATUS_FAILURE;
                            $v->error_msg = empty($error) ? '失败' : $error;
                            $v->save();
                        }
                        break;
                }
            } catch (\Exception $e) {
                $v->status = GoodsEvent::STATUS_FAILURE;
                $v->error_msg = $e->getMessage().$e->getFile().$e->getLine();
                $v->save();
            }
        }

        echo date('Y-m-d H:i:s') .' end'."\n";
    }

    /**
     * 修复库胖商品id
     * @param $shop_id
     * @throws \yii\base\Exception
     */
    public function actionRepairCoupangGoodsId($shop_id)
    {
        $shop = Shop::findOne($shop_id);
        $api_service = FApiService::factory($shop);
        $limit = 0;
        $i = 0;
        while (true) {
            $limit++;
            $goods_shops = GoodsShop::find()->where(['platform_type' => Base::PLATFORM_COUPANG, 'shop_id' => $shop_id])->andWhere(['=','platform_goods_exp_id',''])
                ->limit(1000)->all();
            if (empty($goods_shops)) {
                break;
            }
            foreach ($goods_shops as $goods_shop) {
                if (!empty($goods_shop['platform_goods_exp_id'])) {
                    continue;
                }
                $platform_goods_id = '';
                $platform_goods_opc = $goods_shop['platform_goods_opc'];
                try {
                    $sku_no = $goods_shop['platform_sku_no'];
                    if (empty($sku_no)) {
                        $goods_child = GoodsChild::find()->where(['cgoods_no' => $goods_shop['cgoods_no']])->one();
                        $sku_no = $goods_child['sku_no'];
                    }

                    $platform_goods_opc = $goods_shop['platform_goods_opc'];
                    if (empty($platform_goods_opc)) {
                        $product = $api_service->getProductsToAsin($sku_no);
                        if (empty($product) || empty($product['sellerProductId'])) {
                            $goods_shop->platform_goods_exp_id = '-1';
                        } else {
                            $platform_goods_opc = $product['sellerProductId'];
                            $goods_shop->platform_goods_opc = (string)$product['sellerProductId'];
                            $goods_shop->platform_goods_exp_id = empty($product['productId'])?'-3':(string)$product['productId'];
                        }
                    }

                    /*if($goods_shop->platform_goods_exp_id == '-1') {
                        $goods_child = GoodsChild::find()->where(['cgoods_no' => $goods_shop['cgoods_no']])->one();
                        $sku_no = $goods_child['sku_no'];
                        $product = $api_service->getProductsToAsin($sku_no);
                        if (empty($product) || empty($product['sellerProductId'])) {
                            $goods_shop->platform_goods_exp_id = '-1';
                        } else {
                            $platform_goods_opc = $product['sellerProductId'];
                            $goods_shop->platform_sku_no = $sku_no;
                            $goods_shop->platform_goods_opc = (string)$product['sellerProductId'];
                            $goods_shop->platform_goods_exp_id = (string)$product['productId'];
                        }
                    }*/

                    if (!empty($platform_goods_opc) && (empty($goods_shop['platform_goods_url']) || empty($goods_shop['platform_goods_exp_id']))) {
                        $product = $api_service->getProductsToId($platform_goods_opc);
                        $platform_goods_url = '';
                        foreach ($product['items'] as $v) {
                            if ($v['externalVendorSku'] == $sku_no) {
                                $platform_goods_id = $v['vendorItemId'];
                                $platform_goods_url = $v['sellerProductItemId'];
                            }
                        }
                        if (!empty($platform_goods_id)) {
                            $goods_shop->platform_goods_url = (string)$platform_goods_url;
                            $goods_shop->platform_goods_id = (string)$platform_goods_id;
                        }
                        if(empty($goods_shop->platform_goods_exp_id)) {
                            $goods_shop->platform_goods_exp_id = empty($product['productId']) ? '-3' : (string)$product['productId'];
                        }
                    }
                }catch (\Exception $e){
                    $goods_shop->platform_goods_exp_id = '-2';
                }
                $goods_shop->save();
                $i ++;
                echo $i.','.$shop_id.','.$goods_shop['cgoods_no'].','.$platform_goods_opc.','.$platform_goods_id."\n";
            }
        }
    }

    /**
     * 更新京东商品id
     */
    public function actionUpdateJdGoodsId($shop_id, $page = 1)
    {
        $shop = Shop::findOne($shop_id);
        if ($shop['platform_type'] != Base::PLATFORM_JDID) {
            return;
        }

        $limit = 50;
        $api_service = FApiService::factory($shop);

        while (true) {
            echo $page . "\n";
            $page++;
            $order_lists = $api_service->getProducts($page,$limit);
            if(empty($order_lists)){
                break;
            }

            $spu_id = ArrayHelper::getColumn($order_lists,'spuId');
            $spu_id = implode(',',$spu_id);
            $lists = $api_service->getProductsToSpuId($spu_id);

            foreach ($lists as $v) {
                $goods = Goods::find()->where(['sku_no' =>$v['sellerSkuId']])->asArray()->one();
                if(empty($goods)){
                    continue;
                }

                $goods_shop = GoodsShop::find()->where(['shop_id'=>$shop_id,'goods_no'=>$goods['goods_no']])->one();
                if(empty($goods_shop)){
                    continue;
                }

                $goods_shop->platform_goods_id = (string)$v['skuId'];
                $goods_shop->platform_goods_opc = (string)$v['spuId'];
                $goods_shop->save();

                echo $v['spuId'] . ' '. $goods['goods_no'] ."\n";
            }
        }
    }

}