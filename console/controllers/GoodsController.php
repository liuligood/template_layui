<?php
namespace console\controllers;

use common\components\CommonUtil;
use common\components\HelperStamp;
use common\components\statics\Base;
use common\extensions\aliyun\AliCloudApi;
use common\extensions\google\PyTranslate;
use common\models\Category;
use common\models\CategoryMapping;
use common\models\Goods;
use common\models\goods\GoodsAllegro;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsImages;
use common\models\goods\GoodsLanguage;
use common\models\goods\GoodsStock;
use common\models\goods\GoodsStockDetails;
use common\models\goods\GoodsStockLog;
use common\models\goods\GoodsTranslate;
use common\models\goods\GoodsTranslateExec;
use common\models\goods\OriginalGoodsName;
use common\models\goods\WordTranslate;
use common\models\goods_shop\GoodsShopFollowSale;
use common\models\goods_shop\GoodsShopOverseasWarehouse;
use common\models\GoodsAttribute;
use common\models\goods\GoodsEprice;
use common\models\GoodsEvent;
use common\models\goods\GoodsFruugo;
use common\models\goods\GoodsOnbuy;
use common\models\goods\GoodsReal;
use common\models\GoodsShop;
use common\models\GoodsSource;
use common\models\GoodsStockCheck;
use common\models\GoodsStockCheckCycle;
use common\models\grab\GrabGoods;
use common\models\Order;
use common\models\OrderGoods;
use common\models\OrderStockOccupy;
use common\models\PlatformInformation;
use common\models\Shop;
use common\services\api\GoodsEventService;
use common\services\FGrabService;
use common\services\goods\FGoodsService;
use common\services\goods\GoodsService;
use common\services\goods\GoodsShopService;
use common\services\goods\GoodsStockDetailsService;
use common\services\goods\GoodsStockService;
use common\services\goods\GoodsTranslateService;
use common\services\goods\WordTranslateService;
use common\services\purchase\PurchaseProposalService;
use common\services\sys\ChatgptService;
use common\services\sys\CountryService;
use common\services\warehousing\WarehouseService;
use moonland\phpexcel\Excel;
use yii\console\Controller;
use yii\helpers\ArrayHelper;

class GoodsController extends Controller
{

    /**
     * 商品翻译
     * @param $platform_type
     * @param int $limit
     * @param $goods_no
     * @throws \yii\base\Exception
     */
    public function actionTranslateGoodsExec($limit = 1,$goods_no = null,$platform_type = null)
    {
        $cp_key = md5(gethostname());
        $goods_key = 'com::goods_translate::'.$cp_key.':'.$platform_type;
        $cache = \Yii::$app->redis;
        $lock_key = 'com::goods_translate::lock::'.$cp_key.':'.$platform_type;
        $lock = $cache->get($lock_key);
        if(!empty($lock)){
            sleep(100);
            return;
        }

        $where = ['status' => GoodsService::PLATFORM_GOODS_STATUS_UNCONFIRMED];
        if (!is_null($goods_no)) {
            $where['goods_no'] = $goods_no;
        }
        if (!is_null($platform_type)) {
            $where['platform_type'] = $platform_type;
        }
        $goods_translate = GoodsTranslateExec::find()->where($where)
            ->offset(10 * ($limit - 1))->limit(10)->all();
        if (empty($goods_translate)) {
            sleep(600);
            return;
        }

        foreach ($goods_translate as $translate_item) {
            $goods_no = $translate_item['goods_no'];
            $platform_type = $translate_item['platform_type'];
            $country_code = empty($translate_item['country_code']) ? '' : $translate_item['country_code'];

            $good_info = Goods::find()->where(['goods_no' => $goods_no])->asArray()->one();
            $goods_name = str_replace(['（', '）'], ['(', ')'], $good_info['goods_name']);
            $goods_name = CommonUtil::filterTrademark($goods_name);
            $goods_short_name = str_replace(['（', '）'], ['(', ')'], $good_info['goods_short_name']);
            $goods_short_name = CommonUtil::filterTrademark($goods_short_name);
            $is_same_title = false;
            if ($goods_name == $goods_short_name) {
                $is_same_title = true;
            }
            $goods_language = $good_info['language'];

            $good = [];
            $good['goods_name'] = $goods_name;
            $good['goods_short_name'] = $goods_short_name;
            $good['goods_desc'] = $good_info['goods_desc'];
            $good['goods_content'] = $good_info['goods_content'];

            $fy_language = [
                'es', 'fr', 'pl', 'ru', 'en', 'pt', 'it','de','ko','tr','nl','sv'
            ];
            $goods_platform_class = null;
            try {
                $bool = true;
                $language = $translate_item['language'];

                if(!empty($platform_type)) {
                    $goods_platform_class = FGoodsService::factory($platform_type);
                    if (empty($translate_item['language'])) {
                        $language = $goods_platform_class->getTranslateLanguage($country_code);
                    }
                }

                //关键字
                if (!empty($good_info['goods_short_name_cn']) && !empty($good_info['goods_keywords']) && $language != 'en') {
                    $good['goods_keywords'] = $good_info['goods_short_name_cn'] . ',' . $good_info['goods_keywords'];
                }

                //翻译失败的
                if (!in_array($language, $fy_language)) {
                    $translate_item->status = GoodsService::PLATFORM_GOODS_STATUS_TRANSLATE_FAIL;
                    $translate_item->save();
                    continue;
                }

                //成功执行的操作
                $success_fun = function ($data) use ($goods_no, $goods_platform_class, $country_code, $platform_type) {
                    if (!empty($platform_type)) {
                        $where = ['goods_no' => $goods_no];
                        if (!empty($country_code) && $platform_type != Base::PLATFORM_ALLEGRO) {
                            $where['country_code'] = $country_code;
                        }
                        $main_goods = $goods_platform_class->model()->findOne($where);
                        if (!empty($main_goods)) {
                            $goods_platform_class->model()->updateOneById($main_goods['id'], $data);
                            GoodsEvent::updateAll(['plan_time' => time()], ['goods_no' => $goods_no, 'status' => GoodsEvent::STATUS_WAIT_RUN, 'platform' => $platform_type]);
                            //更新默认标题
                            if (in_array($platform_type ,[Base::PLATFORM_OZON,Base::PLATFORM_ALLEGRO])) {
                                $goods_shop = GoodsShop::find()->where(['goods_no' => $goods_no,
                                    'status' => GoodsShop::STATUS_NOT_TRANSLATED, 'platform_type' => $platform_type])->all();
                                foreach ($goods_shop as $goods_shop_v) {
                                    (new GoodsShopService())->updateDefaultGoodsExpand($goods_shop_v, [3, 4], true);
                                }
                            }
                        }
                    }
                };

                //需要翻译的语言和当前商品语言相同不处理
                if ($language == $goods_language) {
                    $data = [];
                    $data['status'] = GoodsService::PLATFORM_GOODS_STATUS_VALID;
                    $success_fun($data);
                    $translate_item->status = GoodsService::PLATFORM_GOODS_STATUS_VALID;
                    $translate_item->save();
                    CommonUtil::logs('platform1:' . $platform_type . ' goods:' . $goods_no . ',' . $bool, 'translate_exec');
                    echo date('Y-m-d H:i:s') . ' platform1:' . $platform_type . ' goods:' . $goods_no . ',' . $bool . "\n";
                    continue;
                }

                /*if (in_array($language, $fy_language) && $language != 'en' && $good_info['status'] != Goods::GOODS_STATUS_WAIT_MATCH) {
                    if (!(new HelperStamp(Goods::$sync_status_map))->isExistStamp($good_info['sync_img'], Goods::SYNC_STATUS_TITLE_CN) && empty($good_info['goods_name_cn'])) {
                        throw new \Exception('未翻译标题', 1001);
                    }
                    $good['goods_short_name'] = GoodsService::filterGoodsNameCn($good_info['goods_name_cn']);
                }*/

                $goods_translate_service = new GoodsTranslateService($language);
                //已经翻译的数据
                $goods_translate_info = $goods_translate_service->getGoodsInfo($goods_no, null, GoodsTranslate::STATUS_CONFIRMED);
                foreach ($good as $key => &$val) {
                    if (!in_array($key, ['goods_name', 'goods_short_name', 'goods_keywords', 'goods_desc', 'goods_content'])) {
                        continue;
                    }
                    if ($is_same_title && $key == 'goods_short_name') {
                        continue;
                    }

                    if (!empty($val)) {
                        $line_val = PyTranslate::paginationNewline($val);
                        if (!empty($line_val)) {
                            $old_val = $val;
                            $md5_val = md5($old_val);
                            $ready = $goods_translate_service->checkGoodsInfo($goods_no, $key, $md5_val);
                            if (!$ready) {
                                $val = PyTranslate::exec($val, $language);
                                $val = trim($val);
                                if (empty($val)) {
                                    throw new \Exception($key . ' ' . $old_val . '翻译失败');
                                }
                                $goods_translate_service->updateGoodsInfo($goods_no, $key, $val, $md5_val);
                            } else {
                                $val = $ready;
                            }
                        }
                    }
                }
                unset($good['goods_keywords']);
                if ($is_same_title) {
                    $good['goods_short_name'] = $good['goods_name'];
                }
                //有AI生成的标题
                if (empty($goods_translate_info['goods_name_ai']) && in_array($platform_type, [Base::PLATFORM_ALLEGRO, Base::PLATFORM_OZON])) {
                    $has_ai_title = OriginalGoodsName::find()->where(['goods_no' => $goods_no])->limit(1)->exists();
                    if ($has_ai_title) {//ai 标题生成
                        $param = [
                            'title' => $good_info['goods_name']
                        ];
                        if ($platform_type == Base::PLATFORM_OZON) {
                            $code = 'ozon_claim_goods_name';
                        }
                        if ($platform_type == Base::PLATFORM_ALLEGRO) {
                            $code = 'allegro_claim_goods_name';
                        }
                        $md5_val = md5($good_info['goods_name']);
                        $ready = $goods_translate_service->checkGoodsInfo($goods_no, 'goods_name_ai', $md5_val);
                        if (!$ready) {
                            $ai_result = ChatgptService::templateExec($code, $param);
                            if (empty($ai_result)) {
                                throw new \Exception('AI翻译失败');
                            }
                            $goods_translate_service->updateGoodsInfo($goods_no, 'goods_name_ai', $ai_result, $md5_val);
                            $good['goods_name'] = $ai_result;
                            $good['goods_short_name'] = $ai_result;
                        } else {
                            $good['goods_name'] = $ready;
                            $good['goods_short_name'] = $ready;
                        }
                    }
                }

                $good['status'] = GoodsService::PLATFORM_GOODS_STATUS_VALID;
                $success_fun($good);
                $translate_item->status = GoodsService::PLATFORM_GOODS_STATUS_VALID;
                $translate_item->save();
            } catch (\Exception $e) {
                $translate_item['status'] = GoodsService::PLATFORM_GOODS_STATUS_TRANSLATE_FAIL;
                $translate_item->save();
                CommonUtil::logs('platform:' . $platform_type . ' goods:' . $goods_no . ' ' . $e->getMessage() . ' 翻译错误', 'translate_goods_error');
                $bool = false;
            }

            if(!$bool) {
                $request_num = $cache->incrby($goods_key, 1);
                $ttl_lock = $cache->ttl($goods_key);
                if ($request_num == 1 || $ttl_lock > 1000 || $ttl_lock == -1) {
                    $cache->expire($goods_key, 600);
                }
                if ($request_num > 3) {
                    $cache->setex($lock_key, 60 * 60, '1');
                    return;
                }
            }

            CommonUtil::logs('platform:' . $platform_type . ' goods:' . $goods_no . ',' . $bool, 'translate_exec');
            echo date('Y-m-d H:i:s') . ' platform:' . $platform_type . ' goods:' . $goods_no . ',' . $bool . "\n";
            sleep(2);
        }
    }

    /**
     * 翻译
     * @param $platform_type
     * @param $limit
     * @param $goods_no
     * @return void
     * @throws \yii\base\Exception
     */
    public function actionTranslateData($platform_type,$is_goods = 0,$goods_no = null)
    {
        if ($is_goods) {
            $where = null;
            if ($platform_type) {
                $where = ['source_platform_type' => $platform_type];
            }
            $goods = Goods::find()->where($where)->andWhere(['not in', 'language', ['en', '']])->all();
            foreach ($goods as $v) {
                $data = ['goods_no' => $v['goods_no'], 'language' => 'en', 'status' => [GoodsService::PLATFORM_GOODS_STATUS_UNCONFIRMED, GoodsService::PLATFORM_GOODS_STATUS_TRANSLATE_FAIL]];
                $exist = GoodsTranslateExec::find()->where($data)->one();
                if ($exist) {
                    continue;
                }
                $data['platform_type'] = 0;
                $data['country_code'] = '';
                $data['status'] = GoodsService::PLATFORM_GOODS_STATUS_UNCONFIRMED;
                GoodsTranslateExec::add($data);
            }
            return;
        }

        $goods_platform_class = FGoodsService::factory($platform_type);
        $where = ['status' => GoodsService::PLATFORM_GOODS_STATUS_UNCONFIRMED];
        if (!is_null($goods_no)) {
            $where['goods_no'] = $goods_no;
        }
        $goods_all = $goods_platform_class->model()->find()->where($where)->asArray()->all();
        foreach ($goods_all as $v) {
            $data = ['goods_no' => $v['goods_no'], 'country_code' => $v['country_code'], 'platform_type' => $v['platform_type'], 'status' => [GoodsService::PLATFORM_GOODS_STATUS_UNCONFIRMED, GoodsService::PLATFORM_GOODS_STATUS_TRANSLATE_FAIL]];
            $exist = GoodsTranslateExec::find()->where($data)->one();
            if ($exist) {
                continue;
            }
            $data['status'] = GoodsService::PLATFORM_GOODS_STATUS_UNCONFIRMED;
            $data['language'] = $goods_platform_class->getTranslateLanguage($v['country_code']);
            GoodsTranslateExec::add($data);
            echo date('Y-m-d H:i:s') . ' language:' . $data['language'] . ' goods:' . $v['goods_no'] . "\n";
        }
    }

    /**
     * 商品翻译
     * @param $platform_type
     * @param int $limit
     * @param $goods_no
     * @throws \yii\base\Exception
     */
    public function actionTranslateGoodsOld($platform_type,$limit = 1,$goods_no = null)
    {
        $cp_key = md5(gethostname());
        $goods_key = 'com::goods_translate::'.$cp_key.':'.$platform_type;
        $cache = \Yii::$app->redis;
        $lock_key = 'com::goods_translate::lock::'.$cp_key.':'.$platform_type;
        $lock = $cache->get($lock_key);
        if(!empty($lock)){
            sleep(100);
            return;
        }

        $goods_platform_class = FGoodsService::factory($platform_type);
        /*if (!$goods_platform_class->hasTranslate()) {
            sleep(60);
            return;
        }*/

        //$where = ['status' => GoodsService::PLATFORM_GOODS_STATUS_UNCONFIRMED, 'source_method' => GoodsService::SOURCE_METHOD_OWN];
        $where = ['status' => GoodsService::PLATFORM_GOODS_STATUS_UNCONFIRMED,];
        if(!is_null($goods_no)) {
            $where['goods_no'] = $goods_no;
        }
        $goods_all = $goods_platform_class->model()->find()->where($where)
            ->offset(10 * ($limit - 1))->limit(10)->asArray()->all();
        if (empty($goods_all)) {
            sleep(600);
            return;
        }

        foreach ($goods_all as $good) {
            if ($good['source_method'] == GoodsService::SOURCE_METHOD_OWN) {
                $good_info = Goods::find()->where(['goods_no' => $good['goods_no']])->asArray()->one();
                $goods_name = str_replace(['（', '）'], ['(', ')'], $good_info['goods_name']);
                $goods_name = CommonUtil::filterTrademark($goods_name);
                $goods_short_name = str_replace(['（', '）'], ['(', ')'], $good_info['goods_short_name']);
                $goods_short_name = CommonUtil::filterTrademark($goods_short_name);
                $good['goods_short_name'] = $goods_short_name;
                $good['goods_name'] = $goods_name;
                $good['goods_desc'] = $good_info['goods_desc'];
                $good['goods_content'] = $good_info['goods_content'];
                $good['colour'] = $good_info['colour'];
                $bool = false;
                try {
                    $language = $goods_platform_class->getTranslateLanguage(empty($good['country_code'])?'':$good['country_code']);

                    $fy_language = [
                        'es','fr','pl','ru','en'
                    ];
                    $fy_platform = [Base::PLATFORM_OZON,Base::PLATFORM_RDC,Base::PLATFORM_CDISCOUNT,Base::PLATFORM_LINIO,Base::PLATFORM_MERCADO,Base::PLATFORM_ALLEGRO];
                    //if(in_array($platform_type,$fy_platform)) {//ozon短标题由我们自己补充
                    $good_info = Goods::find()->where(['goods_no' => $good['goods_no']])->asArray()->one();
                    if(in_array($language,$fy_language) && $language != 'en' && $good_info['status'] != Goods::GOODS_STATUS_WAIT_MATCH) {
                        if(!(new HelperStamp(Goods::$sync_status_map))->isExistStamp($good_info['sync_img'],Goods::SYNC_STATUS_TITLE_CN) && empty($good_info['goods_name_cn'])) {
                            throw new \Exception('未翻译标题',1001);
                        }
                        $good['goods_short_name'] = GoodsService::filterGoodsNameCn($good_info['goods_name_cn']);
                    }

                    /*if(!empty($good['country_code']) && $platform_type != Base::PLATFORM_LINIO){
                        $language = CountryService::getLanguage($good['country_code']);
                    } else {
                        $language = $goods_platform_class->platform_language;
                    }*/

                    if(in_array($language,$fy_language)) {
                        $goods_translate_service = new GoodsTranslateService($language);
                        //已经翻译的数据
                        $goods_translate_info = $goods_translate_service->getGoodsInfo($good['goods_no'],null,GoodsTranslate::STATUS_CONFIRMED);

                        //关键字
                        if(!empty($good_info['goods_short_name_cn']) && !empty($good_info['goods_keywords'])) {
                            if (empty($goods_translate_info['goods_keywords'])) {
                                $goods_keywords = $good_info['goods_short_name_cn'] . ',' . $good_info['goods_keywords'];
                                $line_val = PyTranslate::paginationNewline($goods_keywords);
                                if(!empty($line_val)) {
                                    $val = PyTranslate::exec($goods_keywords, $language);
                                    $val = trim($val);
                                    if (empty($val)) {
                                        throw new \Exception('goods_keywords  ' . $goods_keywords . '翻译失败');
                                    }
                                    $goods_translate_service->updateGoodsInfo($good['goods_no'], 'goods_keywords', $val);
                                }
                            }
                        }
                    }

                    if($language == 'en') {
                        $bool = true;
                        $data = [];
                        $data['status'] = GoodsService::PLATFORM_GOODS_STATUS_VALID;
                        $goods_platform_class->model()->updateOneById($good['id'], $data);
                        GoodsEvent::updateAll(['plan_time' => time()], ['goods_no' => $good['goods_no'], 'status' => GoodsEvent::STATUS_WAIT_RUN,'platform' => $platform_type]);
                        CommonUtil::logs('platform:'.$platform_type.' goods:'.$good['goods_no'] .','.$bool,'translate_exec');
                        continue;
                    }

                    $is_same_title = false;
                    if($good['goods_short_name'] == $good['goods_name']){
                        $is_same_title = true;
                    }

                    foreach ($good as $key => &$val) {
                        /*if (in_array($key, ['id', 'source_method', 'country_code', 'o_category_name', 'ean', 'goods_no', 'platform_type', 'status', 'brand', 'price', 'weight','add_time','update_time'])) {
                            continue;
                        }*/
                        if (!in_array($key, ['goods_name', 'goods_short_name', 'goods_desc', 'goods_content'])) {
                            continue;
                        }

                        if($is_same_title && $key == 'goods_short_name') {
                            continue;
                        }

                        if(in_array($language,$fy_language)) {
                            if(empty($goods_translate_info[$key])) {
                                if (!empty($val) ) {
                                    $line_val = PyTranslate::paginationNewline($val);
                                    if(!empty($line_val)) {
                                        $old_val = $val;
                                        /*if($good_info['status'] != Goods::GOODS_STATUS_WAIT_MATCH && $key == 'goods_short_name' && in_array($language,$fy_language)) {
                                            $val = Translate::exec($val,$language,'cn');
                                        } else {
                                            $val = PyTranslate::exec($val, $language);
                                        }*/
                                        $val = PyTranslate::exec($val, $language);
                                        $val = trim($val);
                                        if (empty($val)) {
                                            throw new \Exception($key . ' ' . $old_val . '翻译失败');
                                        }
                                        $goods_translate_service->updateGoodsInfo($good['goods_no'], $key, $val);
                                    }
                                }
                            } else {
                                $val = $goods_translate_info[$key];
                            }
                        } else {
                            if (!empty($val)) {
                                $line_val = PyTranslate::paginationNewline($val);
                                if(!empty($line_val)) {
                                    $old_val = $val;
                                    $val = PyTranslate::exec($val, $language);
                                    $val = trim($val);
                                    if (empty($val)) {
                                        throw new \Exception($key . ' ' . $old_val . '翻译失败');
                                    }
                                }
                            }
                        }
                    }
                    if($is_same_title) {
                        $good['goods_short_name'] = $good['goods_name'];
                    }
                    //有AI生成的标题
                    if(empty($goods_translate_info['goods_name_ai']) && in_array($platform_type,[Base::PLATFORM_ALLEGRO,Base::PLATFORM_OZON])) {
                        $has_ai_title = OriginalGoodsName::find()->where(['goods_no' => $good['goods_no']])->limit(1)->exists();
                        if ($has_ai_title) {//ai 标题生成
                            $param = [
                                'title' => $good_info['goods_name']
                            ];
                            if ($platform_type == Base::PLATFORM_OZON) {
                                $code = 'ozon_claim_goods_name';
                            }
                            if ($platform_type == Base::PLATFORM_ALLEGRO) {
                                $code = 'allegro_claim_goods_name';
                            }
                            $ai_result = ChatgptService::templateExec($code, $param);
                            if (empty($ai_result)) {
                                throw new \Exception('AI翻译失败');
                            }
                            $goods_translate_service->updateGoodsInfo($good['goods_no'], 'goods_name_ai', $ai_result);
                            $good['goods_name'] = $ai_result;
                            $good['goods_short_name'] = $ai_result;
                        }
                    }
                    $good['status'] = GoodsService::PLATFORM_GOODS_STATUS_VALID;
                    $goods_platform_class->model()->updateOneById($good['id'], $good);
                    //更新默认标题
                    if($platform_type == Base::PLATFORM_OZON) {
                        $goods_shop = GoodsShop::find()->where(['goods_no' => $good['goods_no'],
                            'status' => GoodsShop::STATUS_NOT_TRANSLATED, 'platform_type' => $platform_type])->all();
                        foreach ($goods_shop as $goods_shop_v) {
                            (new GoodsShopService())->updateDefaultGoodsExpand($goods_shop_v,[3,4],true);
                        }
                    }
                    GoodsEvent::updateAll(['plan_time' => time()], ['goods_no' => $good['goods_no'], 'status' => GoodsEvent::STATUS_WAIT_RUN,'platform' => $platform_type]);
                    $bool = true;
                } catch (\Exception $e) {
                    $good_data = [];
                    $good_data['status'] = GoodsService::PLATFORM_GOODS_STATUS_TRANSLATE_FAIL;
                    $goods_platform_class->model()->updateOneById($good['id'], $good_data);
                    CommonUtil::logs('platform:'.$platform_type.' goods:'.$good['goods_no'].' '.$e->getMessage().' 翻译错误','translate_goods_error');
                    $bool = false;
                }

                if(!$bool) {
                    $request_num = $cache->incrby($goods_key, 1);
                    $ttl_lock = $cache->ttl($goods_key);
                    if ($request_num == 1 || $ttl_lock > 1000 || $ttl_lock == -1) {
                        $cache->expire($goods_key, 600);
                    }
                    if ($request_num > 3) {
                        $cache->setex($lock_key, 60 * 60, '1');
                        return;
                    }
                }

                CommonUtil::logs('platform:'.$platform_type.' goods:'.$good['goods_no'] .','.$bool,'translate_exec');
                echo date('Y-m-d H:i:s').' platform:'.$platform_type.' goods:'.$good['goods_no'] .','.$bool ."\n";
                sleep(2);
            }
        }
    }

    /**
     * 词组翻译
     * @param int $limit
     */
    public function actionWordTranslate($limit = 1)
    {
        $words_all = WordTranslate::find()->where(['status' => WordTranslate::STATUS_UNCONFIRMED])
            ->offset(50 * ($limit - 1))->limit(50)->all();
        if (empty($words_all)) {
            sleep(600);
            return;
        }

        foreach ($words_all as $word) {
            $language = $word['language'];
            $val = $word['name'];
            try {
                $val = PyTranslate::exec($val, $language);
                $val = trim($val);
                if (empty($val)) {
                    throw new \Exception('翻译失败');
                }
                $word['status'] = WordTranslate::STATUS_VALID;
                $word['lname'] = $val;
                $word->save();
            } catch (\Exception $e) {
                $word['status'] = WordTranslate::STATUS_TRANSLATE_FAIL;
                $word->save();
                CommonUtil::logs($language . ' ' . $val . ' ' . $e->getMessage() . ' 翻译错误', 'word_translate_goods_error');
            }
        }
    }

    /**
     * 亚马逊认领
     * @param $limit
     * @throws \Exception
     */
    public function actionAmazonClaim($source,$limit = 1)
    {
        if((date('G')%8) == 5){
            sleep(60);
            return;
        }
        
        $grab_goods_all = GrabGoods::find()->where(['status' => GrabGoods::STATUS_SUCCESS,'source'=>$source])->andWhere(['=', 'goods_no', ''])
            ->andWhere(['=','check_stock_time',0])->offset(50 * ($limit - 1))->limit(50)->orderBy('check_stock_time asc')->all();
        if (empty($grab_goods_all)) {
            sleep(60);
            return;
        }

        foreach ($grab_goods_all as $grab_goods) {
            $asin = $grab_goods['asin'];
            $is_exist = Goods::find()->where(['sku_no'=>$asin,'source_platform_type'=>$grab_goods['source']])->select('id')->exists();
            if($is_exist || empty($asin)){
                $grab_goods['status'] = GrabGoods::STATUS_REPEAT;
                $grab_goods->save();
                continue;
            }

            $id = $grab_goods['id'];
            CommonUtil::logs('time:' . date('Y-m-d H:i:s') . ' start:' . $id, 'amazon_claim_goods_check_stock');
            if (empty($asin)) {
                continue;
            }

            $has_check = false;
            try {
                $result = FGrabService::factory($grab_goods['source'])->checkStock($asin);
                if (!empty($result)) {
                    if ($result['stock'] != $grab_goods['goods_status']) {
                        $grab_goods['goods_status'] = $result['stock'];
                    }

                    if ($result['self_logistics'] != 2) {
                        if ($result['self_logistics'] != $grab_goods['self_logistics']) {
                            $grab_goods['self_logistics'] = $result['self_logistics'];
                        }
                    }
                    $grab_goods['check_stock_time'] = time();
                    $has_check = true;
                } else {
                    CommonUtil::logs('error:' . $id . ' 商品查找失败', 'amazon_claim_goods_check_stock_error');
                    $grab_goods['check_stock_time'] = 1999999999;
                }
            } catch (\Exception $e) {
                CommonUtil::logs('error:' . $id . ' ' . $e->getMessage(), 'amazon_claim_goods_check_stock_error');
                $grab_goods['check_stock_time'] = 1999999999;
            }
            $grab_goods->save();

            if ($has_check) {
                (new GoodsService())->claimAmazon($grab_goods['id']);
                CommonUtil::logs('time:' . date('Y-m-d H:i:s') . ' claim:' . $id, 'amazon_claim_goods_check_stock');
            }
        }
    }

    /**
     * 检测商品库存
     * @param int $stock
     * @param int $limit
     * @param null $source
     */
    public function actionCheckGoodsStock($stock = 1,$limit = 1,$source = null)
    {
        if ((date('G') % 8) == 5) {
            sleep(60);
            return;
        }

        $cycle_id = GoodsStockCheckCycle::find()->where(['status' => GoodsStockCheckCycle::STATUS_NONE])->orderBy('id desc')->select('id')->scalar();
        if (empty($cycle_id)) {
            sleep(60);
            return;
        }

        $where = [
            'status' => Goods::GOODS_STATUS_VALID,
            'source_method' => GoodsService::SOURCE_METHOD_AMAZON
        ];
        if (!is_null($source)) {
            $where['source_platform_type'] = $source;
        } else {
            $where['source_platform_type'] = [1, 2];
        }
        $goods_all = Goods::find()->where($where);
        if ($stock == 1) {
            $time = time() - 7 * 24 * 60 * 60;
            $goods_all = $goods_all->andWhere(['=', 'stock', Goods::STOCK_YES]);
        } else {
            $time = time() - 3 * 24 * 60 * 60;
            $goods_all = $goods_all->andWhere(['=', 'stock', Goods::STOCK_NO]);
        }
        $shop_ids = [];
        /*if($source == 1){
            $shop_ids = [6,10,29];
        }else{
            $shop_ids = [8,20,31];
        }
        $goods_all = $goods_all->andWhere(['in','goods_no',GoodsShop::find()->select('goods_no')->where(['shop_id'=>$shop_ids])]);*/
        $goods_all = $goods_all->andWhere(['<=', 'check_stock_time', $time])
            ->offset(50 * ($limit - 1))->limit(50)->orderBy('check_stock_time asc')->all();
        if (empty($goods_all)) {
            sleep(60);
            return;
        }

        foreach ($goods_all as $goods) {
            $source = $goods['source_platform_type'];
            $asin = $goods['sku_no'];
            CommonUtil::logs('time:' . date('Y-m-d H:i:s') . 'stock: ' . $stock . ' start:' . $goods['id'], 'goods_check_stock');
            if (empty($asin)) {
                continue;
            }

            $stock_change = false;
            $price_change = false;
            try {
                $result = FGrabService::factory($source)->checkStock($asin);
                if (!empty($result)) {
                    $new_stock = Goods::STOCK_NO;
                    if ($result['stock'] == GrabGoods::GOODS_STATUS_NORMAL && $result['self_logistics'] == GrabGoods::SELF_LOGISTICS_YES) {
                        $new_stock = Goods::STOCK_YES;
                    }

                    if ($goods['stock'] != $new_stock) {
                        $stock_change = true;
                        $grab_check_where = [
                            'cycle_id' => $cycle_id,
                            'goods_no' => $goods['goods_no']
                        ];
                        $grab_check = GoodsStockCheck::find()->where($grab_check_where)->one();
                        if (!empty($grab_check)) {
                            if ($grab_check['stock'] != $new_stock) {
                                $grab_check->delete();
                            }
                        } else {
                            $grab_check = new GoodsStockCheck();
                            $grab_check['cycle_id'] = $cycle_id;
                            $grab_check['source'] = $goods['source_platform_type'];
                            $grab_check['goods_no'] = $goods['goods_no'];
                            $grab_check['sku_no'] = $goods['sku_no'];
                            $grab_check['old_stock'] = $goods['stock'];
                            $grab_check['stock'] = $new_stock;
                            $grab_check->save();
                        }
                    }

                    //必须有库存才改价格
                    if ($new_stock == Goods::STOCK_YES && !empty($result['price'])) {
                        $price = CommonUtil::dealAmazonPrice($result['price']);
                        if (bccomp($goods['price'], $price, 2) != 0) {
                            $goods_source = GoodsSource::find()->where(['goods_no'=>$goods['goods_no']])->asArray()->one();
                            if($goods_source['price'] - $price < 3) {
                                $goods['price'] = $price;
                                $price_change = true;
                            }
                        }
                    }
                    $goods['stock'] = $new_stock;
                    $goods['check_stock_time'] = time();
                } else {
                    CommonUtil::logs('error:' . 'stock: ' . $stock . ' goods_id: ' . $goods['id'] . ' 商品查找失败', 'goods_check_stock_error');
                    $goods['check_stock_time'] = 1999999999;
                }
            } catch (\Exception $e) {
                $stock_change = false;
                $price_change = false;
                CommonUtil::logs('error:' . 'stock: ' . $stock . ' goods_id: ' . $goods['id'] . ' ' . $e->getMessage(), 'goods_check_stock_error');
                $goods['check_stock_time'] = 1999999999;
            }
            $goods->save();
            //价格变更
            if ($price_change) {
                (new GoodsService())->updatePlatformGoods($goods['goods_no'],false);
            }

            //目前没有 allegro可以这么处理
            if ($stock_change || $price_change) {
                (new GoodsService())->asyncPlatformStock($goods['goods_no'],false,$shop_ids);
            }
        }
    }

    public function actionMoveShop($shop,$file,$source = 2)
    {
        $file = fopen($file, "r") or exit("Unable to open file!");
        while (!feof($file)) {
            $line = trim(fgets($file));
            if (empty($line)) continue;

            list($asin) = explode(',', $line);
            if (empty($asin)) {
                continue;
            }

            $goods = Goods::find()->where(['sku_no' => $asin,'source_platform_type'=>$source])->asArray()->one();
            if (empty($goods)) {
                $grab = GrabGoods::find()->where(['asin'=>$asin,'source'=>$source])->one();
                if(!empty($grab)) {
                    (new GoodsService())->claimAmazon($grab['id'],false);
                    $goods = Goods::find()->where(['sku_no' => $asin,'source_platform_type'=>$source])->asArray()->one();
                }

                if (empty($goods)) {
                    echo "#{$asin}   商品为空\n";
                    continue;
                }
            }

            if ($goods['status'] == Goods::GOODS_STATUS_INVALID) {
                echo "#{$asin}   该商品被禁用\n";
                continue;
            }

            try {
                $resullt = (new GoodsService())->claim($goods['goods_no'], $shop, GoodsService::SOURCE_METHOD_AMAZON);
            } catch (\Exception $e) {
                //CommonUtil::logs($goods['goods_no'].' 认领失败 '.$e->getMessage(),'batch_claim');
                echo "#{$asin}  认领失败 ".$e->getMessage();
            }

        }
        fclose($file);

        echo "all done\n";
    }

    /**
     * 生成ean码
     */
    public function actionGenEan()
    {
        while (true) {
            $shop_all = GoodsShop::find()->andWhere(['=', 'ean', ''])
                ->limit(50)->all();
            if (empty($shop_all)) {
                break;
            }
            foreach ($shop_all as $v) {
                $ean = '';
                while (true) {
                    $ean = CommonUtil::GenerateEan13();
                    $exist_ean = GoodsShop::find()->where(['ean' => $ean, 'platform_type' => $v['platform_type']])->exists();
                    if (!$exist_ean) {
                        break;
                    }
                }
                $v->ean = $ean;
                $v->save();
                echo $v['id'].' '.$ean."\n";
            }
        }
        echo "all done\n";
    }

    /**
     * 更新平台价格
     * @param $goods_no
     */
    public function actionUpdatePlatformGoods($goods_no)
    {
        (new GoodsService())->updatePlatformGoods($goods_no);
    }

    /**
     * 删除商品
     * @param $goods_no
     */
    public function actionDelGoods($goods_no)
    {
        Goods::deleteAll(['goods_no'=>$goods_no]);
        GoodsAttribute::deleteAll(['goods_no'=>$goods_no]);
        GoodsSource::deleteAll(['goods_no'=>$goods_no]);
        GoodsAllegro::deleteAll(['goods_no'=>$goods_no]);
        GoodsReal::deleteAll(['goods_no'=>$goods_no]);
        GoodsEprice::deleteAll(['goods_no'=>$goods_no]);
        GoodsFruugo::deleteAll(['goods_no'=>$goods_no]);
        GoodsOnbuy::deleteAll(['goods_no'=>$goods_no]);
        GoodsShop::deleteAll(['goods_no'=>$goods_no]);
    }

    /**
     * 更新平台商品价格
     * @param $platform_type
     * @param int $source_method
     * @throws \yii\base\Exception
     */
    public function actionUpdatePlatformPrice($platform_type,$source_method = GoodsService::SOURCE_METHOD_OWN)
    {
        $goods_platform_class = FGoodsService::factory($platform_type);
        $goods_platforms = $goods_platform_class->model()->find()->alias('mg')
            ->leftJoin(Goods::tableName() .' as g','g.goods_no = mg.goods_no')->select('mg.goods_no')
            ->where(['mg.source_method'=>$source_method])->asArray()->all();
        $i = 0;
        foreach ($goods_platforms as $v) {
            try {
                (new GoodsService())->updatePlatformGoods($v['goods_no'], false, [$platform_type]);
            }catch (\Exception $e){
                continue;
            }
            /*$shop_ids = [
                43,46,97,47,48,49,50,88,98,90,89,106,107,110,113,114,85,145,108,109,115,119,117,281
            ];*/
            if (GoodsEventService::hasEvent(GoodsEvent::EVENT_TYPE_UPDATE_PRICE,$platform_type)) {
                $where = ['goods_no' => $v['goods_no'], 'platform_type' => $platform_type];
                if ($platform_type == Base::PLATFORM_OZON) {
                    $where['status'] = [GoodsShop::STATUS_SUCCESS,GoodsShop::STATUS_NOT_UPLOADED,GoodsShop::STATUS_UPLOADING,GoodsShop::STATUS_UNDER_REVIEW,GoodsShop::STATUS_NOT_TRANSLATED];
                }
                $goods_shop = GoodsShop::find()->where($where)->asArray()->all();
                foreach ($goods_shop as $shop_v) {
                    /*if(in_array($shop_v['shop_id'],$shop_ids)) {
                        continue;
                    }*/
                    GoodsEventService::addEvent($shop_v,GoodsEvent::EVENT_TYPE_UPDATE_PRICE, -2);
                }
            }
            $i++;
            echo $i.','.$v['goods_no']."\n";
        }
    }


    /**
     * 更新平台商品店铺价格
     * @param $platform_type
     * @param int $shop_id
     * @throws \yii\base\Exception
     */
    public function actionUpdatePlatformShopPrice($platform_type,$shop_id)
    {
        $where = [
            'platform_type' => $platform_type
        ];
        if (!empty($shop_id)) {
            $where['id'] = $shop_id;
        }
        $shop_lists = Shop::find()->where($where)->asArray()->all();
        foreach ($shop_lists as $shop) {
            echo 'shop:' . $shop['id'] . ' ' . $shop['name'] . "\n";
            $shop_id = $shop['id'];
            $goods_shops = GoodsShop::find()->where(['platform_type' => $platform_type, 'shop_id' => $shop_id])->asArray()->all();
            $i = 0;
            foreach ($goods_shops as $shop_v) {
                if ($shop_v['platform_type'] == Base::PLATFORM_OZON && $shop_v['status'] == GoodsShop::STATUS_OFF_SHELF) {
                    continue;
                }

                if($shop_v['platform_type'] == Base::PLATFORM_HEPSIGLOBAL && empty($shop_v['platform_goods_id'])){
                    continue;
                }

                try {
                    (new GoodsService())->updatePlatformGoods($shop_v['goods_no'], false, $platform_type, $shop_id);
                }catch (\Exception $e){
                    continue;
                }
                //跟卖链接
                $goods_shop_follow = GoodsShopFollowSale::find()->where(['goods_shop_id'=>$shop_v['id']])->one();
                if(!empty($goods_shop_follow) && $goods_shop_follow['price'] > 0) {
                    $new_goods_shop = GoodsShop::find()->where(['id' => $shop_v['id']])->asArray()->one();
                    if ($goods_shop_follow['price'] > $new_goods_shop['price']) {
                        $goods_shop_follow['price'] = $new_goods_shop['price'];
                        $goods_shop_follow->save();
                    }
                }

                if (GoodsEventService::hasEvent(GoodsEvent::EVENT_TYPE_UPDATE_PRICE, $shop_v['platform_type'])) {
                    GoodsEventService::addEvent($shop_v, GoodsEvent::EVENT_TYPE_UPDATE_PRICE, -2);
                }
                $i++;
                echo $i . ',' . $shop_v['goods_no'] . "\n";
            }
        }
    }

    /**
     * 获取商品价格
     */
    public function actionGetPrice($source)
    {
        $limit = 0;
        while (true) {
            $limit ++;
            $goods = Goods::find()
                ->where(['source_method' => GoodsService::SOURCE_METHOD_AMAZON, 'source_platform_type' => $source])
                ->offset(1000*($limit-1))->limit(1000)->all();
            if(empty($goods)){
                break;
            }
            $goods_no = ArrayHelper::getColumn($goods, 'goods_no');
            $goods_source = GoodsSource::find()->where(['goods_no' => $goods_no])->indexBy('goods_no')->asArray()->all();
            foreach ($goods as $v) {
                $new_price = $v['price'];
                if (empty($goods_source[$v['goods_no']])) {
                    echo $v['goods_no'] . "," . $v['sku_no'] . "," . "来源为空" . "\n";
                }
                $old_price = $goods_source[$v['goods_no']]['price'];
                if ($new_price - $old_price > 3) {
                    echo $v['goods_no'] . "," . $v['sku_no'] . "," . $new_price . "," . $old_price . "\n";
                    //价格变更
                    $v->price = $old_price;
                    $v->save();
                    (new GoodsService())->updatePlatformGoods($v['goods_no']);

                    $goods_shop = GoodsShop::find()->where(['goods_no' => $v['goods_no']])->asArray()->all();
                    foreach ($goods_shop as $shop_v) {
                        //real 和 fruugo 有接口
                        if (in_array($shop_v['platform_type'], [Base::PLATFORM_REAL_DE, Base::PLATFORM_FRUUGO])) {
                            GoodsEventService::addEvent($shop_v ,GoodsEvent::EVENT_TYPE_UPDATE_STOCK);
                        }
                    }
                }

                /*if ($old_price < $new_price) {
                    //echo $v['goods_no'].",".$v['sku_no'].",".$new_price.",".$old_price.",1"."\n";
                }*/
            }
        }
    }

    /**
     * 下架店铺商品
     * @param $shop_id
     * @throws \yii\base\Exception
     */
    public function actionOffShelfShop()
    {
        $limit = 0;
        while (true) {
            $limit++;
            $goods_shop = GoodsShop::find()->where([
                'platform_type' => Base::PLATFORM_FRUUGO,
                'shop_id' => [6,10,29]
            ])->offset(1000 * ($limit - 1))->limit(1000)->orderBy('goods_no asc')->all();
            if(empty($goods_shop)){
                break;
            }
            foreach ($goods_shop as $v) {
                GoodsEventService::addEvent($v, GoodsEvent::EVENT_TYPE_UPDATE_PRICE);
            }
        }
    }

    /**
     * 删除店铺商品
     * @param $shop_id
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\base\Exception
     * @throws \yii\db\StaleObjectException
     */
    public function actionDelShopGoods($shop_id)
    {
        exit;
        $shop = Shop::find()->where(['id' => $shop_id])->asArray()->one();
        $platform_type = $shop['platform_type'];
        $goods_platform_class = FGoodsService::factory($platform_type);
        $platform_class = $goods_platform_class->model();

        while (true) {
            $goods_shop = GoodsShop::find()->where([
                'shop_id' => $shop_id
            ])->limit(1000)->orderBy('goods_no asc')->all();
            if (empty($goods_shop)) {
                break;
            }

            foreach ($goods_shop as $v) {
                $goods_no = $v->goods_no;
                echo $goods_no."\n";
                if ($v->delete()) {
                    $goods_model = GoodsShop::findOne(['platform_type' => $platform_type, 'goods_no' => $goods_no]);
                    if (empty($goods_model)) {
                        $main_goods_model = $platform_class->findOne(['goods_no' => $goods_no]);
                        $main_goods_model->delete();
                    }
                }
            }
        }
    }

    /**
     * 删除店铺商品
     * @param $shop_id
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\base\Exception
     * @throws \yii\db\StaleObjectException
     */
    public function actionDelShopGoods11()
    {
        $platform_type = 23;
        $goods_events = GoodsEvent::find()->where(['platform'=>$platform_type,'status'=>0,'event_type'=>GoodsEvent::EVENT_TYPE_ADD_GOODS])->all();
        $i = 0;
        foreach ($goods_events as $goods_event) {
            GoodsShop::deleteAll(['platform_type' => $platform_type, 'goods_no' => $goods_event['goods_no'],'shop_id'=>$goods_event['shop_id']]);
            $i ++;
            echo $i.",".$goods_event['goods_no'].",".$goods_event['shop_id']."\n";
        }
    }

    /**
     * 删除店铺商品
     * @param $shop_id
     * @throws \Exception
     * @throws \yii\base\Exception
     * @throws \yii\db\StaleObjectException
     * @throws \Throwable
     */
    public function actionDelShopGoods1($shop_id,$file)
    {
        $shop = Shop::find()->where(['id' => $shop_id])->asArray()->one();
        $platform_type = $shop['platform_type'];
        $goods_platform_class = FGoodsService::factory($platform_type);
        $platform_class = $goods_platform_class->model();

        $file = fopen($file, "r") or exit("Unable to open file!");
        while (!feof($file)) {
            $line = trim(fgets($file));
            if (empty($line)) continue;

            list($sku_no) = explode(',', $line);
            if (empty($sku_no)) {
                continue;
            }

            /*$goods = Goods::find()->where(['sku_no'=>$sku_no])->asArray()->one();
            if(empty($goods)){
                echo $sku_no.','.'不存在'."\n";
            }*/

            //$goods_no = $goods['goods_no'];
            $goods_shop = GoodsShop::find()->where([
                'shop_id' => $shop_id,
                'platform_sku_no' => $sku_no
            ])->one();
            if (empty($goods_shop)) {
                echo $sku_no.','.'店铺不存在'."\n";
                continue;
            }

            echo $sku_no."\n";
            if ($goods_shop->delete()) {
                /*$goods_model = GoodsShop::findOne(['platform_type' => $platform_type, 'goods_no' => $goods_no]);
                if (empty($goods_model)) {
                    $main_goods_model = $platform_class->findOne(['goods_no' => $goods_no]);
                    $main_goods_model->delete();
                }*/
            }
        }
        fclose($file);
        echo "all done\n";
    }

    /**
     * 同步上传图片
     */
    public function actionSyncImg($limit = 1,$goods_no = null){
        $sync_status =  (new HelperStamp(Goods::$sync_status_map))->getResidueStamps([Goods::SYNC_STATUS_IMG,Goods::SYNC_STATUS_IMG_FAILED]);
        $where = [
            'status' => [Goods::GOODS_STATUS_VALID,Goods::GOODS_STATUS_WAIT_MATCH],
            //'source_method'=>GoodsService::SOURCE_METHOD_OWN,
            'sync_img' => $sync_status
        ];
        if(!empty($goods_no)) {
            $where['goods_no'] = $goods_no;
        }
        $goods_lists = Goods::find()->where($where)->andWhere(['!=','source_platform_type',Base::PLATFORM_RDC])->offset(100*($limit-1))->limit(100)->all();
        if (empty($goods_lists)) {
            sleep(600);
            return;
        }

        foreach ($goods_lists as $goods) {
            echo ($goods['goods_no']) . "\n";
            $old_goods_img = $goods['goods_img'];
            $goods_img = json_decode($goods['goods_img'], true);
            $goods_img = empty($goods_img) || !is_array($goods_img) ? [] : $goods_img;
            CommonUtil::handleUrlProtocol($goods_img,['img'],true,'https');
            $imgs = [];
            $error = false;
            foreach ($goods_img as $v) {
                if(empty($v['img'])){
                    continue;
                }
                $img = $v['img'];
                $img = explode('"',$img);
                $img = trim($img[0]);
                if(empty($img)) {
                    $error = true;
                    continue;
                }
                if(strpos($img,\Yii::$app->oss->endpoint) === false && strpos($img,'gigab2b.cn') === false) {
                    $new_img = \Yii::$app->oss->uploadFileByPath($img);
                    if (empty($new_img)) {
                        $error = true;
                        //continue;
                    } else {
                        $img = $new_img;
                    }
                }
                $imgs[]['img'] = $img;
            }
            $goods->goods_img = json_encode($imgs);
            $stamp = Goods::SYNC_STATUS_IMG;
            if($error){
                CommonUtil::logs('Error：'.$goods['goods_no'],'goods_sync_img_error');
                CommonUtil::logs($old_goods_img,'goods_sync_img_error');
                CommonUtil::logs($goods['goods_img'],'goods_sync_img_error');
                $stamp += Goods::SYNC_STATUS_IMG_FAILED;
            }
            $goods->sync_img = HelperStamp::addStamp($goods['sync_img'],$stamp);
            CommonUtil::logs($goods['goods_no'],'goods_sync_img');
            CommonUtil::logs($old_goods_img,'goods_sync_img');
            CommonUtil::logs($goods['goods_img'],'goods_sync_img');

            if($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
                $goods_childs = GoodsChild::find()->where(['goods_no' => $goods['goods_no']])->all();
                foreach ($goods_childs as $child_v) {
                    if(empty($child_v['goods_img'])){
                        continue;
                    }
                    $img = trim($child_v['goods_img']);
                    if(strpos($img,\Yii::$app->oss->endpoint) === false && strpos($img,'gigab2b.cn') === false) {
                        $new_img = \Yii::$app->oss->uploadFileByPath($img);
                        if (empty($new_img)) {
                            continue;
                        } else {
                            $child_v['goods_img'] = $new_img;
                            $child_v->save();
                        }
                    }
                }
            }
            $goods->save();
        }
        echo date('Y-m-d H:i')."执行同步图片完成\n";
    }

    /**
     * 同步商品关键词
     */
    public function actionGoodsKeywords($limit = 1)
    {
        $cp_key = md5(gethostname());
        $goods_key = 'com::goods_keywords_translate::'.$cp_key;
        $cache = \Yii::$app->redis;
        $lock_key = 'com::goods_keywords_translate::lock::'.$cp_key;
        $lock = $cache->get($lock_key);
        if(!empty($lock)){
            sleep(100);
            return;
        }

        $goods_lists = Goods::find()->where([
            //'source_method' => GoodsService::SOURCE_METHOD_OWN,
            'sync_img' => [0, 1]])->offset(100 * ($limit - 1))->limit(100)->all();
        if (empty($goods_lists)) {
            sleep(600);
            return;
        }

        foreach ($goods_lists as $goods) {
            //echo ($goods['goods_no']) . "\n";
            $goods_name_cn = $goods['goods_name_cn'];
            try {
                /*if(!empty($goods['language']) && $goods['language'] != 'en') {
                    $old_goods_name = $goods['goods_name'];
                    $goods_name = str_replace(['&'], [' '], $goods['goods_name']);
                    $goods_name = PyTranslate::exec($goods_name, 'en');
                    $goods['goods_name'] = $goods_name;
                    if (empty($goods_name)) {
                        CommonUtil::logs($goods['goods_no'] . ' ' . $goods['goods_name'] . ' 翻译失败', 'goods_keywords_error');
                        $goods->sync_img = HelperStamp::addStamp($goods['sync_img'], Goods::SYNC_STATUS_TRANSLATION_FAILED);
                        $goods->save();
                        continue;
                    }

                    if ($old_goods_name == $goods['goods_short_name']) {
                        $goods['goods_short_name'] = $goods_name;
                    } else {
                        $goods_short_name = PyTranslate::exec($goods['goods_short_name'], 'en');
                        $goods['goods_short_name'] = $goods_short_name;
                        if (empty($goods_short_name)) {
                            $goods->sync_img = HelperStamp::addStamp($goods['sync_img'], Goods::SYNC_STATUS_TRANSLATION_FAILED);
                            $goods->save();
                            CommonUtil::logs($goods['goods_no'] . ' ' . $goods['goods_short_name'] . ' 翻译失败', 'goods_keywords_error');
                            continue;
                        }
                    }

                    if (!empty($goods['goods_desc'])) {
                        $goods_desc = PyTranslate::exec($goods['goods_desc'], 'en');
                        $goods['goods_desc'] = $goods_desc;
                        if (empty($goods_desc)) {
                            $goods->sync_img = HelperStamp::addStamp($goods['sync_img'], Goods::SYNC_STATUS_TRANSLATION_FAILED);
                            $goods->save();
                            CommonUtil::logs($goods['goods_no'] . ' ' . $goods['goods_desc'] . ' 翻译失败', 'goods_keywords_error');
                            continue;
                        }
                    }

                    $goods_content = PyTranslate::exec($goods['goods_content'], 'en');
                    $goods['goods_content'] = $goods_content;
                    if (empty($goods_content)) {
                        $goods->sync_img = HelperStamp::addStamp($goods['sync_img'], Goods::SYNC_STATUS_TRANSLATION_FAILED);
                        $goods->save();
                        CommonUtil::logs($goods['goods_no'] . ' ' . $goods['goods_desc'] . ' 翻译失败', 'goods_keywords_error');
                        continue;
                    }
                    $goods['language'] = '';
                }*/

                if (empty($goods_name_cn)) {
                    //$goods_name = str_replace(['&'],[' '],$goods['goods_name']);
                    $goods_name = str_replace(['（', '）'], ['(', ')'], $goods['goods_name']);
                    $goods_name = CommonUtil::filterTrademark($goods_name);
                    $goods_name_cn = PyTranslate::exec($goods_name);
                    $goods_name_cn = str_replace(['amp；'], [''], $goods_name_cn);
                    if (empty($goods_name_cn)) {
                        CommonUtil::logs($goods['goods_no'] . ' ' . $goods['goods_name'] . ' 翻译失败', 'goods_keywords_error');
                        continue;
                    }
                    $goods->goods_name_cn = CommonUtil::usubstr($goods_name_cn,140);
                }

                /*if(empty($goods['goods_keywords'])) {
                    $goods_keywords = CommonUtil::getKeywordsCN($goods_name_cn);
                    $goods->goods_keywords = $goods_keywords;
                }*/

                //CommonUtil::logs($goods['goods_img'],'goods_sync_img');
                $goods->sync_img = HelperStamp::addStamp($goods['sync_img'], Goods::SYNC_STATUS_KEYWORDS);
                $goods->save();
                echo date('Y-m-d H:i:s').' '. $goods['goods_no'] . ' 翻译成功'."\n";
                CommonUtil::logs($goods['goods_no'] . ' 翻译成功', 'goods_keywords');
            }catch (\Exception $e){
                $request_num = $cache->incrby($goods_key, 1);
                $ttl_lock = $cache->ttl($goods_key);
                if ($request_num == 1 || $ttl_lock > 1000 || $ttl_lock == -1) {
                    $cache->expire($goods_key, 600);
                }
                if ($request_num > 3) {
                    $cache->setex($lock_key, 60 * 60, '1');
                    return;
                }
                echo date('Y-m-d H:i:s').' '. $goods['goods_no'] . ' 翻译失败'."\n";
                CommonUtil::logs($goods['goods_no'] . ' 翻译失败', 'goods_keywords_error');
            }
            sleep(2);
        }
        echo date('Y-m-d H:i') . "执行同步商品关键词完成\n";
    }

    /**
     * 同步商品关键词
     */
    public function actionGoodsTitle($limit = 1)
    {
        $sync_status_1 =  (new HelperStamp(Goods::$sync_status_map))->getStamps(Goods::SYNC_STATUS_KEYWORDS);
        $sync_status_2 =  (new HelperStamp(Goods::$sync_status_map))->getStamps(Goods::SYNC_STATUS_TITLE_CN);
        $sync_img = array_diff($sync_status_1,$sync_status_2);
        $goods_lists = Goods::find()->where([
            //'source_method' => GoodsService::SOURCE_METHOD_OWN,
            'sync_img' => $sync_img])->offset(100 * ($limit - 1))->limit(100)->all();
        if (empty($goods_lists)) {
            sleep(600);
            return;
        }

        foreach ($goods_lists as $goods) {
            $goods_no = $goods['goods_no'];
            echo ($goods['goods_no']) . "\n";
            $goods_name_cn = $goods['goods_name_cn'];
            /*if (preg_match('/[a-zA-Z]/',$goods_name_cn)) {//有字母的重新翻译
                $goods_name_cn = str_replace(['（', '）','|'], ['(', ')',''], $goods_name_cn);
                $goods_name_cn = CommonUtil::filterTrademark($goods_name_cn);
                $goods_name_cn = Translate::exec($goods_name_cn,'cn','en');
                if (empty($goods_name_cn)) {
                    CommonUtil::logs($goods['goods_no'] . ' ' . $goods_name_cn . ' 翻译失败' , 'goods_keywords_error');
                    continue;
                }
                if(strpos($goods_name_cn, 'UNK1') === false) {
                    $goods->goods_name_cn = $goods_name_cn;
                }
            }*/
            $goods->sync_img = HelperStamp::addStamp($goods['sync_img'], Goods::SYNC_STATUS_TITLE_CN);
            $goods->save();
            /*GoodsOzon::updateAll(['status'=>0],['goods_no'=>$goods_no]);
            $goods_shop = GoodsShop::find()->where(['goods_no'=>$goods_no,'platform_type'=>Base::PLATFORM_OZON])->asArray()->all();
            foreach ($goods_shop as $goods_shop_v) {
                GoodsEventService::addEvent($goods_shop_v['platform_type'], $goods_shop_v['shop_id'], $goods_shop_v['goods_no'], $goods_shop_v['cgoods_no'], GoodsEvent::EVENT_TYPE_UPDATE_GOODS);
            }*/
        }
        echo date('Y-m-d H:i') . "执行同步商品标题完成\n";
    }

    /**
     * 同步商品执行多标题
     */
    public function actionMoreGoodsTitle($limit = 1)
    {
        $sync_status =  (new HelperStamp(Goods::$sync_status_map))->getResidueStamps(Goods::SYNC_STATUS_MORE_TITLE);
        $goods_lists = Goods::find()->where(['sync_img'=>$sync_status, 'status'=>Goods::GOODS_STATUS_VALID])
            ->offset(100 * ($limit - 1))->limit(100)->all();
        if (empty($goods_lists)) {
            sleep(600);
            return;
        }

        foreach ($goods_lists as $goods) {
            $goods_no = $goods['goods_no'];
            echo ($goods['goods_no']) . "\n";
            $param = [
                'title' => $goods['goods_name']
            ];
            $code = 'goods_name_more';
            $ai_result = ChatgptService::templateExec($code, $param);
            if (empty($ai_result)) {
                $etr = $goods['goods_no'] . ',' . $goods['goods_name'] . ",AI翻译失败";
                CommonUtil::logs($etr, 'goods_name_more');
                echo $etr . "\n";
                continue;
            }

            $goods_translate_service = new GoodsTranslateService('en');
            $i = 0;
            foreach ($ai_result as $v) {
                $i ++;
                $goods_translate_service->updateGoodsInfo($goods_no,'goods_name'.$i, $v);
            }
            $goods->sync_img = HelperStamp::addStamp($goods['sync_img'], Goods::SYNC_STATUS_MORE_TITLE);
            $goods->save();
        }
        echo date('Y-m-d H:i') . "执行多商品标题完成\n";
    }

    public function actionSize()
    {
        exit;
        $category_id = 1695;
        $category = Category::collectionChildrenId($category_id);
        $category[] = $category_id;
        $goods = Goods::find()->where(['source_method' => 1])->andWhere(['not in', 'category_id', $category])->andWhere(['!=', 'size', ''])->all();

        foreach ($goods as $v) {
            $v['size'] = '';
            $v->save();
            (new GoodsService())->updatePlatformGoods($v['goods_no'], true);
            echo $v['goods_no']."\n";
        }
    }

    /**
     * 更新重量
     * @throws \yii\base\Exception
     */
    public function actionUpdateWeight()
    {
        $category_id = 2021;
        $category = Category::collectionChildrenId($category_id);
        $category[] = $category_id;
        $goods = Goods::find()->where(['source_method' => 1])->andWhere(['in', 'category_id', $category])->all();
        foreach ($goods as $v) {
            $v['weight'] = $v['weight'] + 2;
            $v->save();
            (new GoodsService())->updatePlatformGoods($v['goods_no'], true);
            echo $v['goods_no']."\n";
        }
    }

    /**
     * 导入商品
     * @param $file
     * @throws \yii\base\Exception
     */
    public function actionExcel($file){
        $excel_data = Excel::import($file, [
            'setFirstRecordAsKeys' => false,
        ]);

        // 多Sheet
        if (isset($excel_data[0])) {
            $excel_data = $excel_data[0];
        }

        $i = 0;
        $goods_lists = [];
        foreach ($excel_data as $data) {
            $i++;
            if ($i == 1) {
                continue;
            }
            $url = $data['V'];
            if (empty($data['A']) || empty($url)) {
                continue;
            }
            preg_match_all('/offer\/(.+?)\.html/',$url,$r);
            if(empty($r[1]) || empty($r[1][0])){
                echo ($url);
                continue;
            }
            $source_platform_id = $r[1][0];

            $price = $data['T'];
            $weight = $data['U'];

            $goods_img = [];
            $goods_img[] = $data['A'];
            $goods_img[] = $data['B'];
            $goods_img[] = $data['C'];
            $goods_img[] = $data['D'];
            $goods_img[] = $data['E'];

            $goods_lists[] = [
                'source_platform_id' => $source_platform_id,
                'source_platform_type' => Base::PLATFORM_1688,
                'source_platform_url' => $url,
                'price' => empty($price)?0:$price,
                'weight'=>$weight,
                'sku_no'=>$data['S'],
                'goods_name' => $data['K'],
                'goods_img' => $goods_img,
                'goods_attribute' => [
                    [
                        'attribute_name' => 'Brand Name',
                        'attribute_value' => 'SANBEANS',
                    ]
                ],
                'goods_content' => $data['J'],
            ];
        }


        /*foreach ($goods_lists as $data) {
            $goods_img = [];
            $img_count = 1;
            foreach ($data['goods_img'] as $v) {
                $v = trim($v);
                if(empty($v) || $img_count > 10) {//只采集10张图片
                    continue;
                }
                $goods_img[] = ['img' => $v];
                $img_count ++;
            }

            $sku_no = $data['sku_no'];
            $goods = Goods::find()->where(['sku_no'=>$sku_no])->one();
            if(empty($goods)){
                continue;
            }
            $goods->goods_img = json_encode($goods_img);
            $goods->save();
            echo $goods['goods_no']."\n";
        }
        exit;*/

        $category_id = 1987;
        foreach ($goods_lists as $data) {
            $goods_img = [];
            $img_count = 1;
            foreach ($data['goods_img'] as $v) {
                $v = trim($v);
                if(empty($v) || $img_count > 10) {//只采集10张图片
                    continue;
                }
                $goods_img[] = ['img' => $v];
                $img_count ++;
            }
            $data['goods_img'] = json_encode($goods_img);

            $goods_content = $data['goods_content'];
            $data['goods_content'] = $goods_content;
            $data['goods_short_name'] = $data['goods_name'];

            $data['status'] = Goods::GOODS_STATUS_VALID;
            $data['admin_id'] = 11;

            $data['source_method'] = GoodsService::SOURCE_METHOD_OWN;
            if(!empty($category_id)){
                $data['category_id'] = $category_id;
            }
            $data['stock'] = Goods::STOCK_YES;
            $goods_no = Goods::addGoods($data);

            if (empty($goods_no)) {
                continue;
            }

            GoodsSource::add([
                'goods_no' => $goods_no,
                'platform_type' => $data['source_platform_type'],
                'platform_url' => $data['source_platform_url'],
                'price' => $data['price'],
                'is_main' => 1,
                'status' => 1,
            ]);

            foreach ($data['goods_attribute'] as $attribute_v) {
                GoodsAttribute::add([
                    'goods_no' => $goods_no,
                    'attribute_name' => $attribute_v['attribute_name'],
                    'attribute_value' => $attribute_v['attribute_value'],
                ]);
            }
            echo $goods_no."\n";
        }

    }

    /**
     * 更新商品属性标签
     */
    public function actionUpdateGoodsStampTag()
    {
        $goods = Goods::find()->where(['source_method' => 1,'source_method_sub'=>Goods::GOODS_SOURCE_METHOD_SUB_GRAB])->all();
        foreach ($goods as $v) {
            if($v['price'] <= 10){
                $v->goods_tort_type = Goods::GOODS_TORT_TYPE_LOW_PRICE;
            } else {
                $img = $v['goods_img'];
                $img = json_decode($img,true);
                if(count($img) < 3){
                    $v->goods_tort_type = Goods::GOODS_TORT_TYPE_IMPERFECT;
                }else{
                    continue;
                }
            }
            $v->save();
        }
    }

    /**
     * 清除相同产品
     * @param int $shop_id
     * @param int $limit
     * @throws \yii\base\Exception
     */
    public function actionClearSameGoodsShop($shop_id,$limit = 0)
    {
        $shop = Shop::find()->where(['id' => $shop_id])->asArray()->one();
        $platform_type = $shop['platform_type'];
        while (true) {
            $limit++;
            $goods_shop = GoodsShop::find()->where([
                'shop_id' => $shop_id
            ])->offset(10000 * ($limit - 1))->limit(10000)->orderBy('goods_no asc')->all();
            if (empty($goods_shop)) {
                break;
            }

            $order_goods_nos = Order::find()->alias('o')
                ->leftJoin(OrderGoods::tableName() . ' as og', 'og.order_id=o.order_id')->where(['shop_id'=>$shop_id])->select('og.goods_no')->column();

            foreach ($goods_shop as $v) {
                $country_code = $v->country_code;
                $goods_no = $v->goods_no;
                if(in_array($goods_no,$order_goods_nos)) {
                    continue;
                }
                $exist = GoodsShop::find()->where(['goods_no' => $goods_no, 'country_code'=> $country_code,'platform_type' => $platform_type])->andWhere(['!=', 'shop_id', $shop_id])->exists();
                if($exist){
                    echo $goods_no . "\n";
                    $v->status = GoodsShop::STATUS_DELETE;
                    $v->save();
                    GoodsEventService::addEvent($v,GoodsEvent::EVENT_TYPE_DEL_GOODS);
                }
                /*$goods = Goods::find()->where(['goods_no' => $goods_no])->one();
                if ($goods['source_method'] == GoodsService::SOURCE_METHOD_AMAZON || $goods['status'] != Goods::GOODS_STATUS_VALID) {
                    echo $goods_no . "\n";
                    $v->status = GoodsShop::STATUS_DELETE;
                    $v->save();
                    GoodsEventService::addEvent($platform_type, $v['shop_id'], $goods_no, GoodsEvent::EVENT_TYPE_DEL_GOODS);
                }*/
            }
            echo $limit . "\n";
        }
    }


    /**
     * 清除不出单产品
     * @param int $shop_id
     * @param int $all_limit
     * @throws \yii\base\Exception
     */
    public function actionClearNoOrderGoodsShop($shop_id,$all_limit = 10)
    {
        $shop = Shop::find()->where(['id' => $shop_id])->asArray()->one();
        $platform_type = $shop['platform_type'];

        //$sku = OrderGoods::find()->where(['source_method'=>GoodsService::SOURCE_METHOD_OWN])->distinct(true)->select('platform_asin')->column();
        //$sku = array_filter($sku);
        $limit = 0;
        $goods_nos = OrderGoods::find()->alias('og')->leftJoin(Order::tableName().' o','o.order_id=og.order_id')->where([
            'og.source_method'=>GoodsService::SOURCE_METHOD_OWN,
            'o.shop_id'=>$shop_id,
            //'o.source' => $platform_type
        ])->distinct(true)->select('og.goods_no')->column();
        while (true) {
            $limit++;
            $goods_shop = GoodsShop::find()->alias('gs')->where([
                'shop_id' => $shop_id,
            ])->andWhere(['!=','gs.status',GoodsShop::STATUS_DELETE])
                ->andWhere(['not in','gs.goods_no',$goods_nos])
                ->limit(10000)->all();
            if (empty($goods_shop)) {
                break;
            }
            foreach ($goods_shop as $v) {
                $goods_no = $v->goods_no;
                //$goods = Goods::find()->where(['goods_no' => $goods_no])->one();
                //if (in_array($goods['sku_no'], $sku)) {
                echo $goods_no . "\n";
                (new GoodsShopService())->delGoods($v);
                //}
            }
            echo $limit . "\n";
            if($limit >= $all_limit){
                break;
            }
        }
    }

    /**
     * 按类目清除产品
     * @param int $shop_id
     * @param int $all_limit
     * @throws \yii\base\Exception
     */
    public function actionClearGoodsShopByCategory($shop_id,$all_limit = 10)
    {
        $category_ids = [
            12164,
            27408,
            27407,
            26151,
            13381,
            13380,
        ];
        if(!is_array($category_ids)) {
            $category_ids = explode(',', $category_ids);
        }
        $category = Category::find()->all();
        $parent_cate = ArrayHelper::index($category,null,'parent_id');
        $del_category = $category_ids;
        foreach ($category_ids as $category_id) {
            $category_lists = Category::collectionChildrenId($category_id,$parent_cate);
            $del_category = array_merge($category_lists,$del_category);
        }

        foreach ($del_category as &$cate_v) {
            $cate_v = (int)$cate_v;
        }

        $shop = Shop::find()->where(['id' => $shop_id])->asArray()->one();
        $platform_type = $shop['platform_type'];

        $limit = 0;
        while (true) {
            $limit++;
            $goods_shop = GoodsShop::find()->alias('gs')->leftJoin(Goods::tableName().' g','g.goods_no=gs.goods_no')->where([
                'shop_id' => $shop_id,
            ])->andWhere(['!=','gs.status',GoodsShop::STATUS_DELETE])
                ->andWhere(['g.category_id'=>$del_category])
                //->andWhere(['!=','g.goods_tort_type',1])
                //->andWhere(['!=','g.goods_stamp_tag',Goods::GOODS_STAMP_TAG_FINE])
                ->limit(10000)->all();
            if (empty($goods_shop)) {
                break;
            }
            foreach ($goods_shop as $v) {
                $goods_no = $v->goods_no;
                //$goods = Goods::find()->where(['goods_no' => $goods_no])->one();
                //if (in_array($goods['sku_no'], $sku)) {
                echo $goods_no . "\n";
                $v->status = GoodsShop::STATUS_DELETE;
                $v->save();
                GoodsEventService::addEvent($v,GoodsEvent::EVENT_TYPE_DEL_GOODS);
                //}
            }
            echo $limit . "\n";
            if($limit >= $all_limit){
                break;
            }
        }
    }

    /**
     * 根据物流渠道更新带电属性
     * @param $file
     * @param int $is_electric
     */
    public function actionUpdateElectricToTrackNo($file,$is_electric = 0)
    {
        $file = fopen($file, "r") or exit("Unable to open file!");
        while (!feof($file)) {
            $line = trim(fgets($file));
            if (empty($line)) continue;

            list($track_no) = explode(',', $line);
            if (empty($track_no)) {
                continue;
            }

            $order = Order::find()->where(['track_no' => $track_no])->one();
            if (empty($order)) {
                echo $track_no . ',' . '不存在' . "\n";
                continue;
            }

            $order_goods_list = OrderGoods::find()->where(['order_id' => $order['order_id']])->asArray()->all();
            foreach ($order_goods_list as $order_goods) {
                $goods = Goods::find()->where(['sku_no' => $order_goods['platform_asin']])->one();
                if (empty($goods)) {
                    echo $track_no . ',' . '该产品不存在' . "\n";
                    continue;
                }
                $goods->electric = $is_electric;
                $goods->save();
                echo $track_no . ',' . $goods['goods_no'] . "\n";
            }
        }
        fclose($file);
        echo "all done\n";
    }

    /**
     * 删除店铺商品
     * @param int $shop_id
     * @throws \yii\base\Exception
     */
    public function actionDelShopGoodsApi($shop_id)
    {
        $shop = Shop::find()->where(['id' => $shop_id])->asArray()->one();
        $platform_type = $shop['platform_type'];
        $limit = 0;
        while (true) {
            $limit++;
            $goods_shop = GoodsShop::find()->where([
                'shop_id' => $shop_id
            ])->andWhere(['!=','status',GoodsShop::STATUS_DELETE])->limit(10000)->orderBy('goods_no asc')->all();
            if (empty($goods_shop)) {
                break;
            }

            foreach ($goods_shop as $v) {
                $goods_no = $v->goods_no;
                /*
                //$exist = GoodsShop::find()->where(['goods_no' => $goods_no, 'platform_type' => $platform_type])->andWhere(['!=', 'shop_id', $shop_id])->exists();
                $goods = Goods::find()->where(['goods_no' => $goods_no])->one();
                if ($goods['source_method'] == GoodsService::SOURCE_METHOD_AMAZON || $goods['status'] != Goods::GOODS_STATUS_VALID) {
                    echo $goods_no . "\n";
                    $v->status = GoodsShop::STATUS_DELETE;
                    $v->save();
                    GoodsEventService::addEvent($platform_type, $v['shop_id'], $goods_no, GoodsEvent::EVENT_TYPE_DEL_GOODS,time()-25*60);
                }*/
                (new GoodsShopService())->delGoods($v);
                echo $goods_no . "\n";
            }
            echo $limit . "\n";
        }
    }

    /**
     * 删除平台异常产品
     * @param $platform_type
     * @param int $shop_id
     * @throws \yii\base\Exception
     */
    public function actionDelAbnormalGoodsApi($platform_type,$shop_id = null)
    {
        $limit = 0;
        while (true) {
            $limit++;
            $fgoods = FGoodsService::factory($platform_type);
            $abnormal_goods = $fgoods->model()->find()->select('goods_no')->where(['audit_status'=>GoodsService::PLATFORM_GOODS_AUDIT_STATUS_ABNORMAL])->offset(1000 * ($limit - 1))->limit(1000)->column();
            //$abnormal_goods = $fgoods->model()->find()->select('goods_no')->where(['o_category_name'=>'-1'])->offset(1000 * ($limit - 1))->limit(1000)->column();
            if (empty($abnormal_goods)) {
                break;
            }
            $where = ['platform_type'=>$platform_type];
            if(!is_null($shop_id)){
                $where['shop_id'] = $shop_id;
            }
            $goods_shop = GoodsShop::find()->where($where)->andWhere(['!=','status',GoodsShop::STATUS_DELETE])
                ->andWhere(['in','goods_no',$abnormal_goods])->orderBy('goods_no asc')->all();

            foreach ($goods_shop as $v) {
                $goods_no = $v->goods_no;
                (new GoodsShopService())->delGoods($v);
                echo $v->shop_id.','.$goods_no . "\n";
            }
            echo $limit . "\n";
        }
    }


    public function actionMoveCategory($category_id,$move_category_id)
    {
        CategoryMapping::updateAll(['category_id'=>$move_category_id],[
            'and',
            ['category_id'=>$category_id],
            ['!=','platform_type',Base::PLATFORM_ONBUY]
        ]);
        Goods::updateAll(['category_id'=>$move_category_id],['category_id'=>$category_id]);
        //Category::deleteAll(['id'=>$category_id]);
        //CategoryMapping::deleteAll(['category_id'=>$category_id]);
    }


    /**
     * 更新颜色
     * @param int $limit
     */
    public function actionUpdateColor($limit = 0)
    {
        while (true) {
            $limit++;
            $goods = Goods::find()->where([
                'source_method' => GoodsService::SOURCE_METHOD_OWN
            ])->offset(10000 * ($limit - 1))->limit(10000)->all();
            if (empty($goods)) {
                break;
            }

            foreach ($goods as $v) {
                if (empty($v['colour'])) {
                    preg_match('@(?<=Color:|Colour:)[^\n]+@i', $v['goods_content'], $match);
                    if (!empty($match[0])) {
                        $colour = trim($match[0]);
                        $colour = ucfirst($colour);
                        $exist = array_key_exists($colour,GoodsService::$colour_map);
                        if($exist) {
                            $v['colour'] = (string)$colour;
                            $v->save();
                            echo $v['goods_no'].' 1colour:' .$colour. "\n";
                        }
                    }
                    continue;
                }

                $colour = ucfirst($v['colour']);
                $exist = array_key_exists($colour,GoodsService::$colour_map);
                if(!$exist) {
                    preg_match('@(?<=Color:|Colour:)[^\n]+@i', $v['goods_content'], $match);
                    if (!empty($match[0])) {
                        $colour = trim($match[0]);
                        $colour = ucfirst($colour);
                        $exist = array_key_exists($colour,GoodsService::$colour_map);
                        if($exist) {
                            $v['colour'] = (string)$colour;
                            $v->save();
                            echo $v['goods_no'].' colour:' .$colour. "\n";
                            continue;
                        }
                    }
                    $v['colour'] = '';
                    $v->save();
                    echo $v['goods_no'].' colour:'. $colour .' 无'. "\n";
                }else{
                    if($colour != $v['colour']) {
                        $v['colour'] = (string)$colour;
                        $v->save();
                        echo $v['goods_no'] . ' colour:' . $colour . "\n";
                    }
                }

                /*preg_match('@(?<=\(|（)[^\)|）]+@', $v['goods_name'], $match);
                if (!empty($match[0])) {
                    $v['colour'] = (string)$match[0];
                    $v->save();
                    echo $v['goods_no'].' colour:' .$match[0]. "\n";
                }*/
            }
            echo $limit . "\n";
        }
    }

    /**
     * 更新颜色
     * @param int $limit
     */
    public function actionUpdatePlatformColor($platform_type,$limit = 0)
    {
        $goods_platform_class = FGoodsService::factory($platform_type);
        $platform_class = $goods_platform_class->model();

        $colour_deal = function($colour,$language){
            static $_arr;
            if(empty($_arr[$colour])) {
                $val = PYTranslate::exec($colour, $language);
                $_arr[$colour] = $val;
            }
            return $_arr[$colour];
        };

        while (true) {
            $limit++;
            $m_goods = $platform_class->find()->offset(10000 * ($limit - 1))->limit(10000)->all();
            if (empty($m_goods)) {
                break;
            }

            foreach ($m_goods as $v) {
                if (!empty($v['colour'])) {
                    continue;
                }

                $goods = Goods::find()->where(['goods_no'=>$v['goods_no']])->one();
                if(empty($goods['colour'])){
                    continue;
                }

                $colour = $goods['colour'];

                $colour = $colour_deal($colour,$goods_platform_class->platform_language);

                $v->colour = $colour;
                $v->save();
                echo $v['goods_no'] .' '.$goods['colour'].' '.$colour ."\n";

            }
            echo $limit . "\n";
        }
    }

    /**
     * sku_no
     */
    public function actionUpdateSkuNo()
    {
        $goods_sku = Goods::find()->select('sku_no')
            ->where(['status'=>Goods::GOODS_STATUS_WAIT_MATCH,'source_method' => GoodsService::SOURCE_METHOD_OWN, 'source_method_sub' => 1])
            ->groupBy('sku_no')->having('count(*)>1')->column();
        foreach ($goods_sku as $v) {
            $goods = Goods::find()->where(['sku_no' => $v])->asArray()->all();
            $last_gno = '';
            $goods_nos = [];
            $source_platform_type = '';
            foreach ($goods as $goods_v) {
                $goods_nos[] = $goods_v['goods_no'];
                $last_gno = $goods_v['goods_no'];
                $source_platform_type = $goods_v['source_platform_type'];
            }
            $pre = 'GF';
            if ($source_platform_type == Base::PLATFORM_ONBUY) {
                $pre = 'GO';
            }
            $sku_no = GoodsService::genSkuNo($pre);
            Goods::updateOneByCond(['goods_no' => $last_gno], ['sku_no' => $sku_no]);

            $goods_shop = GoodsShop::find()->where(['goods_no' => $goods_nos, 'platform_type' => [Base::PLATFORM_ONBUY, Base::PLATFORM_FRUUGO, Base::PLATFORM_ALLEGRO]])->all();
            foreach ($goods_shop as $shop_v) {
                $shop_v->status = GoodsShop::STATUS_DELETE;
                $shop_v->save();
                GoodsEventService::addEvent($shop_v, GoodsEvent::EVENT_TYPE_DEL_GOODS);
            }
            echo $last_gno . "," . $v . "," . $sku_no . "\n";
        }
    }

    /**
     * 更新平台商品价格
     * @throws \yii\base\Exception
     */
    public function actionUpdatePlatformSizePrice()
    {
        $goods = Goods::find()->where(['source_method'=>GoodsService::SOURCE_METHOD_OWN,'status'=>[Goods::GOODS_STATUS_VALID]])
            ->andWhere(['!=','size',''])->asArray()->all();
        $i = 0;
        foreach ($goods as $v) {
            (new GoodsService())->updatePlatformGoods($v['goods_no']);
            $i++;
            echo $i.','.$v['goods_no'].' '.$v['size']."\n";
        }
    }

    /**
     * 更新平台商品价格
     * @throws \yii\base\Exception
     */
    public function actionUpdateOnbuyPlatformPrice($limit=0)
    {
        while (true) {
            $limit++;
            $goods = Goods::find()->where(['source_method' => 1, 'source_method_sub' => 1, 'status' => 8, 'source_platform_type' => Base::PLATFORM_ONBUY])
                ->andWhere(['>','add_time',strtotime('2021-08-09')])->offset(10000 * ($limit - 1))->limit(10000)->all();
            foreach ($goods as $v) {
                //$goods_source = GoodsSource::find()->where(['goods_no' => $v['goods_no'], 'platform_type' => Base::PLATFORM_ONBUY])->asArray()->one();

                $pice_i = 1.4;
                $old_price = $v['gbp_price'];
                /*if ($old_price <= 5) {
                    $pice_i = 2;
                } else if ($old_price > 5 && $old_price <= 10) {
                    $pice_i = 1.5;
                } else if ($old_price > 10 && $old_price <= 15) {
                    $pice_i = 1.2;
                } else if ($old_price > 15 && $old_price <= 20) {
                    $pice_i = 1.1;
                } else {
                    continue;
                }*/
                $exist = GoodsShop::find()->where(['goods_no' => $v['goods_no']])->exists();
                /*if ($exist) {
                    if ($old_price > 20 && $old_price <= 30) {
                        $pice_i = 1.2;
                    } else if ($old_price > 30) {
                        $pice_i = 1.1;
                    } else {
                        continue;
                    }
                } else {
                    if ($old_price > 10 && $old_price <= 20) {
                        $pice_i = 1.3;
                    } else if ($old_price > 20 && $old_price <= 30) {
                        $pice_i = 1.2;
                    } else if ($old_price > 30) {
                        $pice_i = 1.3;
                    } else {
                        continue;
                    }
                }*/
                if($v['price'] <= 20){
                    continue;
                }
                $v->gbp_price = $old_price * $pice_i;
                $v->save();
                if($exist) {
                    (new GoodsService())->updatePlatformGoods($v['goods_no'], true);
                }
                echo $v['goods_no'] . ',' . $pice_i . ',' . $old_price . ',' . $v['gbp_price'] . "\n";
            }
            echo $limit . "\n";
        }
    }

    public function actionAddGoods($shop_id)
    {
        $goods_shop_list = GoodsShop::find()->where(['shop_id' => $shop_id, 'platform_goods_id' => ''])->asArray()->all();
        foreach ($goods_shop_list as $goods_shop) {
            $platform_type = $goods_shop['platform_type'];
            $goods_no = $goods_shop['goods_no'];
            if(empty($goods_shop['platform_goods_id'])) {
                GoodsEventService::addEvent($goods_shop,GoodsEvent::EVENT_TYPE_ADD_GOODS);
            }
            echo $goods_no."\n";
        }
    }

    public function actionUpdateGoodsStock($platform_type,$shop_id = null)
    {
        $where = ['platform_type' => $platform_type];
        if(!empty($shop_id)) {
            $where['shop_id'] = $shop_id;
        }
        $goods_shop_list = GoodsShop::find()->where($where)->asArray()->all();
        foreach ($goods_shop_list as $goods_shop) {
            $platform_type = $goods_shop['platform_type'];
            $goods_no = $goods_shop['goods_no'];
            if($platform_type == Base::PLATFORM_OZON && in_array($goods_shop['status'],[GoodsShop::STATUS_NOT_UPLOADED,GoodsShop::STATUS_OFF_SHELF])) {
                continue;
            }
            if($platform_type == Base::PLATFORM_MERCADO && $goods_shop['status'] == GoodsShop::STATUS_DELETE){
                continue;
            }
            if(in_array($goods_shop['shop_id'],[280,487,491,496])) {
                continue;
            }
            GoodsEventService::addEvent($goods_shop,GoodsEvent::EVENT_TYPE_UPDATE_STOCK);
            echo $goods_no."\n";
        }
    }

    public function actionUpdateGoods()
    {
        $i = 0;
        $goods_shop_list = GoodsShop::find()->where(['platform_type'=>Base::PLATFORM_HEPSIGLOBAL,'shop_id'=>234,'goods_no'=>[
            'G06216946534007',
            'G06264165058601',
           
        ]])->asArray()->all();
        echo count($goods_shop_list)."\n";
        foreach ($goods_shop_list as $goods_shop) {
            $i ++;
            $platform_type = $goods_shop['platform_type'];
            $goods_no = $goods_shop['goods_no'];
            $goods = Goods::find()->where(['goods_no'=>$goods_no])->one();
            if($goods['status'] == Goods::GOODS_STATUS_INVALID){
                continue;
            }
            GoodsEventService::addEvent($goods_shop, GoodsEvent::EVENT_TYPE_UPDATE_GOODS,0);

            echo $i.','.$goods_shop['shop_id'].','.$goods_no."\n";
        }
    }

    /**
     * 更新店铺商品
     */
    public function actionUpdateGoodsFile($file,$shop_id)
    {
        $file = fopen($file, "r") or exit("Unable to open file!");
        $i = 0;
        while (!feof($file)) {
            $line = trim(fgets($file));
            if (empty($line)) continue;

            list($goods_no) = explode(',', $line);
            if (empty($goods_no)) {
                continue;
            }

            $goods_shop = GoodsShop::find()->where(['shop_id' => $shop_id, 'goods_no' => $goods_no])->asArray()->one();
            if(empty($goods_shop)){
                continue;
            }
            $platform_type = $goods_shop['platform_type'];
            $goods = Goods::find()->where(['goods_no' => $goods_no])->one();
            if (empty($goods) || $goods['status'] == Goods::GOODS_STATUS_INVALID) {
                continue;
            }

            GoodsEventService::addEvent($goods_shop, GoodsEvent::EVENT_TYPE_UPDATE_GOODS, 0);
            $i++;
            echo $i.','.$goods_shop['shop_id'] . ',' . $goods_no . "\n";
        }
        fclose($file);
        echo "all done\n";
    }

    /**
     * 禁用指定类目商品
     * @param $category_id
     * @throws \yii\base\Exception
     */
    public function actionOutCategoryGoods($category_id)
    {
        while (true) {
            $goods_lists = Goods::find()->where(['category_id' => $category_id])
                ->andWhere(['!=', 'status', Goods::GOODS_STATUS_INVALID])->limit(10000)->all();
            if(empty($goods_lists)){
                break;
            }
            foreach ($goods_lists as $goods_v) {
                (new GoodsService())->asyncPlatformStock($goods_v['goods_no'],true);
                $goods_v->status = Goods::GOODS_STATUS_INVALID;
                $goods_v->save();
                echo $goods_v['goods_no']."\n";
            }
        }
    }

    /**
     * 禁用指定类目品牌
     * @param $category_id
     * @throws \yii\base\Exception
     */
    public function actionOutCategoryGoodsBrand()
    {
        while (true) {
            $goods_lists = Goods::find()->where(['source_platform_title' =>[
                'Lock-&-Lock',
                'Armitage',
                'Newcastle United FC',
                'House of Paws',
                'CRUSHGRIND',
                'Beckasin',
                'Marvo',
                'Janod',
                'Mystic Moments',
                'Relaxdays',
                'Kandy Toys',
                'The-Leonardo-Collection',
                'IMC Toys',
                'Pukka Pad',
                'Front Porch Classics',
                'onepre',
                'Loftus International',
                'Pot of Dreams',
                'Cousins',
                'Mattel-UK-Ltd',
                'Pet Safe',
                'Dell Computers',
                'Interpet',
                'Spura Home',
                'Friends',
                'Case-Wonder',
                'FEPITO',
                'V-TAC',]])
                ->andWhere(['not in', 'status', [Goods::GOODS_STATUS_VALID ,Goods::GOODS_STATUS_INVALID]])->limit(10000)->all();
            /**
             * $goods_lists = Goods::find()->where(['source_method'=>1])->andWhere(['like','goods_name','Original'])
            ->andWhere(['in', 'status', [8,10]])->limit(10000)->all();
             */
            if(empty($goods_lists)){
                break;
            }
            foreach ($goods_lists as $goods_v) {
                (new GoodsService())->asyncPlatformStock($goods_v['goods_no'],true);
                $goods_v->status = Goods::GOODS_STATUS_INVALID;
                $goods_v->save();
                echo $goods_v['goods_no']."\n";
            }
        }
    }


    /**
     * 按文件禁用指定商品
     * @param $file
     * @throws \yii\base\Exception
     */
    public function actionOutGoodsToFile($file)
    {
        $file = fopen($file, "r") or exit("Unable to open file!");
        while (!feof($file)) {
            $line = trim(fgets($file));
            if (empty($line)) continue;

            list($goods_no) = explode(',', $line);
            if (empty($goods_no)) {
                continue;
            }

            $goods = Goods::find()->where(['goods_no'=>$goods_no])->one();
            if(empty($goods)){
                echo $goods_no.','.'不存在'."\n";
            }

            (new GoodsService())->asyncPlatformStock($goods_no,true);
            $goods->status = Goods::GOODS_STATUS_INVALID;
            $goods->save();
            echo $goods_no."\n";
        }
        fclose($file);
        echo "all done\n";
    }

    /**
     * 删除黑名单名单
     * @throws \yii\base\Exception
     */
    public function actionDelBlackGoods($limit=0)
    {
        while (true) {
            $limit++;
            $goods_lists = Goods::find()->where(['source_method' => 1])
                ->andWhere(['not in', 'status', [Goods::GOODS_STATUS_INVALID]])->limit(10000)->all();
            if(empty($goods_lists)){
                break;
            }
            foreach ($goods_lists as $goods_v) {
                if((new GoodsService())->existBlacklist($goods_v)) {
                    (new GoodsService())->asyncPlatformStock($goods_v['goods_no'], true);
                    $goods_v->status = Goods::GOODS_STATUS_INVALID;
                    $goods_v->save();
                    echo $goods_v['goods_no'] .' '.$goods_v['sku_no'].' '.$goods_v['goods_name']."\n";
                }
            }
            echo $limit . "\n";
        }
    }

    /**
     * 更新平台商品价格
     * @throws \yii\base\Exception
     */
    public function actionUpdateFruugoPlatformPrice($limit=0)
    {
        while (true) {
            $limit++;
            $goods_shop = GoodsShop::find()->where(['platform_type' => Base::PLATFORM_FRUUGO, 'shop_id' => [8, 11, 12, 30, 37]])->andWhere(['<', 'price', 12.99])
                ->limit(10000)->all();
            if(empty($goods_shop)){
                break;
            }
            foreach ($goods_shop as $shop_v) {
                $old_price = $shop_v->price;
                $shop_v->price = 12.99;
                $shop_v->save();
                GoodsEventService::addEvent($shop_v, GoodsEvent::EVENT_TYPE_UPDATE_PRICE);
                echo $shop_v['goods_no'] . ',' . $old_price . "\n";
            }
            echo $limit . "\n";
        }
    }

    /**
     * 导入店铺商品
     */
    public function actionExpShopPlatformId($file,$shop_id)
    {
        $file = fopen($file, "r") or exit("Unable to open file!");
        while (!feof($file)) {
            $line = trim(fgets($file));
            if (empty($line)) continue;

            list($sku_no) = explode(',', $line);
            if (empty($sku_no)) {
                continue;
            }

            $goods = Goods::find()
                ->where(['sku_no' => $sku_no])
                ->all();
            if (empty($goods)) {
                CommonUtil::logs($shop_id  . ',' . $sku_no . ',空sku', 'shop_platform');
                continue;
            }

            $goods_info = current($goods);
            $params = [
                'is_sync' => false,
            ];
            (new GoodsService())->claim($goods_info['goods_no'], [$shop_id], $goods_info['source_method'],$params);
            echo $sku_no ."\n";
        }
        fclose($file);
        echo "all done\n";
    }

    /**
     * 更新未出单商品折扣价
     */
    public function actionUpdateShopPriceDiscount($page = 1,$shdp_id = null)
    {
        $where = ['source' => Base::PLATFORM_ALLEGRO];
        if (!empty($shdp_id)) {
            $where['shop_id'] = $shdp_id;
        }
        $order_sku_no = OrderGoods::find()->alias('og')
            ->leftJoin(Order::tableName() . ' o', 'o.order_id=og.order_id')
            ->where($where)
            ///->andWhere(['>','date',strtolower('2021-10-01')])
            ->select('goods_no')
            ->distinct()->asArray()->column();
        $order_sku_no = array_filter($order_sku_no);

        $limit = 1000;
        while (true) {
            echo $page . "\n";
            $where = ['platform_type' => Base::PLATFORM_ALLEGRO];
            if (!empty($shdp_id)) {
                $where['shop_id'] = $shdp_id;
            }
            $goods_shop = GoodsShop::find()
                ->where($where)->offset($limit * ($page - 1))->limit($limit)
                ->asArray()->all();
            if (empty($goods_shop)) {
                break;
            }
            foreach ($goods_shop as $v) {
                //排除10月以后出单产品
                if (in_array($v['goods_no'], $order_sku_no)) {
                    continue;
                }
                (new GoodsShopService())->updateGoodsDiscount($v['id'], 9);
                echo $v['shop_id'] . ',' . $v['goods_no'] . "\n";
            }
            $page += 1;
        }
    }

    /**
     * 商品翻译数据
     * @param $platform_type
     * @param null $goods_no
     * @param int $limit
     * @throws \yii\base\Exception
     */
    public function actionGoodsTranslateData($platform_type,$goods_no = null,$limit = 1)
    {
        $goods_platform_class = FGoodsService::factory($platform_type);
        $where = ['status' => GoodsService::PLATFORM_GOODS_STATUS_VALID];
        if(!is_null($goods_no)) {
            $where['goods_no'] = $goods_no;
        }

        while (true) {
            echo $limit ."\n";
            $goods_all = $goods_platform_class->model()->find()->where($where)
                ->offset(1000 * ($limit - 1))->limit(1000)->asArray()->all();
            if (empty($goods_all)) {
                break;
            }

            foreach ($goods_all as $good) {
                $goods_no = $good['goods_no'];
                $goods = Goods::find()->where(['goods_no'=>$goods_no])->one();
                if(empty($goods)) {
                    continue;
                }
                if (!empty($good['country_code']) && $platform_type != Base::PLATFORM_LINIO) {
                    $language = CountryService::getLanguage($good['country_code']);
                } else {
                    $language = $goods_platform_class->platform_language;
                }

                $goods_translate_service = new GoodsTranslateService($language);
                //$goods_translate_info = $goods_translate_service->getGoodsInfo($good['goods_no']);

                foreach ($good as $key => $val) {
                    if (!in_array($key, ['goods_name', 'goods_short_name', 'goods_desc', 'goods_content'])) {
                        continue;
                    }
                    /*if(!empty($goods_translate_info[$key])){
                        continue;
                    }*/
                    if (empty($val)) {
                        continue;
                    }
                    $val = trim($val);
                    $goods_translate_service->updateGoodsInfo($goods_no, $key, $val);
                }
                echo $goods_no ."\n";
            }
            $limit ++;
        }

    }


    /**
     * 删除店铺商品
     * @throws \Exception
     * @throws \yii\base\Exception
     * @throws \yii\db\StaleObjectException
     */
    public function actionDelShopGoods2()
    {
        exit;
        $goods_event = GoodsEvent::find()
            ->where(['platform' => Base::PLATFORM_OZON, 'status' => 0, 'event_type' => 'add_goods'])->limit(5000)->all();
        $platform_type = Base::PLATFORM_OZON;
        foreach ($goods_event as $v) {
            $goods_no = $v['goods_no'];
            $shop_id = $v['shop_id'];
            GoodsShop::deleteAll(['platform_type' => Base::PLATFORM_OZON, 'shop_id' => $shop_id, 'goods_no' => $goods_no]);
            $goods_model = GoodsShop::findOne(['platform_type' => $platform_type, 'goods_no' => $goods_no]);
            if (empty($goods_model)) {
                $shop = Shop::find()->where(['id' => $shop_id])->asArray()->one();
                $platform_type = $shop['platform_type'];
                $goods_platform_class = FGoodsService::factory($platform_type);
                $platform_class = $goods_platform_class->model();
                $main_goods_model = $platform_class->findOne(['goods_no' => $goods_no]);
                if ($main_goods_model) {
                    $main_goods_model->delete();
                }
            }
            $v->delete();
            echo $shop_id . '_' . $goods_no . "\n";
        }
    }

    /**
     * 修复旧数据子商品
     * @param $limit
     * @return bool
     * @throws \yii\base\Exception
     */
    public function actionGenGoodsChild($limit)
    {
        return false;
        $i = 0;
        while (true) {
            $limit++;
            $goods = Goods::find()->offset(10000 * ($limit - 1))->limit(10000)->all();
            if (empty($goods)) {
                break;
            }

            foreach ($goods as $v) {
                $i ++;
                /*if($v['goods_stamp_tag'] == Goods::GOODS_STAMP_TAG_FINE || $v['goods_type'] == Goods::GOODS_TYPE_MULTI){
                    continue;
                }*/


                (new GoodsService())->updateProperty($v['goods_no']);
                echo $limit.','.$v['goods_no'].','.$i. "\n";
            }
            echo $limit . "\n";
        }
    }

    /**
     *
     * @param $goods_no
     */
    public function actionGenGoodsWordTranslate($goods_no = '')
    {
        $where = [
            //'source_method' => GoodsService::SOURCE_METHOD_OWN,
            'goods_type' => Goods::GOODS_TYPE_MULTI];
        if (!empty($goods_no)) {
            $where['goods_no'] = $goods_no;
        }else{
            (new WordTranslateService())->addWord(array_keys(GoodsService::$colour_map));
        }
        $goods_nos = Goods::find()->where($where)->select('goods_no')->column();
        foreach ($goods_nos as $v) {
            (new WordTranslateService())->addGoodsTranslate($v);
            echo $v . "\n";
        }
    }


    /**
     * 更新安骏库存
     * @param $goods_no
     */
    public function actionUpdateAnjunStock($file)
    {
        $file = fopen($file, "r") or exit("Unable to open file!");
        $cgoods_nos = [];
        $warehouse = WarehouseService::WAREHOUSE_ANJ;

        while (!feof($file)) {
            $line = trim(fgets($file));
            if (empty($line)) continue;

            list($sku_no, $num) = explode(',', $line);
            if (empty($sku_no) || empty($num)) {
                continue;
            }

            $goods = GoodsChild::find()->where(['sku_no' => $sku_no])->asArray()->one();
            if (empty($goods)) {
                echo $sku_no . ',' . '不存在' . "\n";
                continue;
            }
            $cgoods_nos[] = $goods['cgoods_no'];
            $goods_stock = GoodsStock::find()->where(['cgoods_no' => $goods['cgoods_no'], 'warehouse' => $warehouse])->one();
            if ($goods_stock['num'] != $num) {
                $change_num = $num - $goods_stock['num'];
                GoodsStockService::changeStock($goods['cgoods_no'], $warehouse, GoodsStockService::TYPE_ADMIN_CHANGE, $change_num, '','安骏库存同步');
                OrderStockOccupy::deleteAll(['sku_no' => $sku_no, 'type' => OrderStockOccupy::TYPE_STOCK, 'warehouse' => $warehouse]);
                (new PurchaseProposalService())->updatePurchaseProposal($warehouse, $sku_no);
                echo $sku_no . ',' . $num . ',同步库存' . "\n";
            }
        }
        fclose($file);

        $goods_stock = GoodsStock::find()->where(['warehouse' => $warehouse])->andWhere(['not in', 'cgoods_no', $cgoods_nos])
            ->andWhere(['>','num',0])->all();
        foreach ($goods_stock as $v) {
            GoodsStockService::changeStock($v['cgoods_no'], $warehouse, GoodsStockService::TYPE_ADMIN_CHANGE, -$v['num'], '','安骏库存同步');
            $goods = GoodsChild::find()->where(['cgoods_no' => $v['cgoods_no']])->asArray()->one();
            OrderStockOccupy::deleteAll(['sku_no' => $goods['sku_no'], 'type' => OrderStockOccupy::TYPE_STOCK, 'warehouse' => $warehouse]);
            (new PurchaseProposalService())->updatePurchaseProposal($warehouse, $goods['sku_no']);
            echo $goods['sku_no'] . ',删除库存' . "\n";
        }
    }

    /**
     * 修复白底
     * @param int $page
     */
    public function actionWhiteImage($page = 1)
    {
        $goods = Goods::find()->where(['goods_tort_type' => 9, 'sync_img' => 7])->offset(100 * ($page - 1))->limit(100)->all();
        $coun_i = 0;
        foreach ($goods as $v) {
            $coun_i ++;
            $image = json_decode($v['goods_img'], true);
            $success = true;
            $images = [];
            $i = 0;
            $main_img = '';
            foreach ($image as $img_v) {
                $i++;
                if ($i == 1) {
                    $main_img = $img_v['img'];
                    $result = (new AliCloudApi())->whiteImage($main_img);
                    if (empty($result)) {
                        $success = false;
                        break;
                    }
                    $images[] = ['img' => $result];
                } else {
                    $images[] = ['img' => $img_v['img']];
                }
            }

            if ($success) {
                $images[] = ['img' => $main_img];
                $v->goods_img = json_encode($images);
                $v->sync_img = 32 + 7;//39
            } else {
                $v->sync_img = 64 + 7;//71
            }
            $v->save();
            echo $coun_i .','.$v['goods_no'] .','.$success. "\n";
        }
    }

    /**
     * @param int $page
     */
    public function actionInfringementGoods($page = 1)
    {
        while (true) {
            echo $page . "\n";
            $goods = Goods::find()->where(['source_method' => 1,'status'=>8])->offset(10000 * ($page - 1))->limit(10000)->all();
            if(empty($goods)){
                return;
            }
            foreach ($goods as $v) {
                $content = $v['goods_name'] . ' ' . $v['goods_desc'] . ' ' . $v['goods_content'];
                $map = [
                    'Booba',
                    '3D Sparrow',
                    'abercrombie fitch',
                    'Acushnet',
                    'AirTamer',
                    'Alice Cooper',
                    'ALLMAN BROTHERS',
                    'American Expedition Vehicles ',
                    'AMERICAN GIRL ',
                    'AMERICAN PSYCHO ',
                    'Angle izer ',
                    'Angry Birds',
                    'AQUABEADS',
                    'Arc teryx',
                    'ARCTIC AIR',
                    'ASSC',
                    'B-52s',
                    'Bacon Bin',
                    'BAGILAANOE',
                    'Bala Bangles',
                    'BALLCAPBUDDY',
                    'BANG & OLUFSEN',
                    'Beanie Boos',
                    'Bear Paws',
                    'Beth Bender Beauty',
                    'Betty Boop',
                    'BIDI',
                    'Big Green Egg',
                    'Blackhawk',
                    'BLIND GUARDIAN',
                    'Block Of Gear',
                    'Bluey ',
                    'BORESNAKE ',
                    'BottleLoft',
                    'Bring Me the Horizon',
                    'BRISTLY',
                    'Bunch O Ballons',
                    'Burger Master ',
                    'Butterfly Craze',
                    'BUZZFEED',
                    'CANNONDALE ',
                    //'CAP',
                    'CECI TATTOOS',
                    'Cheap Trick',
                    'Chenyan Sun',
                    'Christina Menzel Works',
                    'Chrome Cherry',
                    'Clever Cutter',
                    'Cocomelon',
                    'Costa Del Mar',
                    'Counting Crows ',
                    'CWC',
                    'DAVID BOWIE',
                    'David Gilmour',
                    //'DEF',
                    'Derek Deyoung',
                    'Dewalt',
                    'Diamond Painting Pen',
                    'DOUBLE ENDED HAND TOOL',
                    'Draft Top',
                    'DRAIN WEASEL',
                    'Egg Sitter',
                    'Emoji',
                    'ESS',
                    'EVEL KNIEVEL',
                    'EverRatchet',
                    'Fear of God',
                    'FIDGET CUBE',
                    'FinGears',
                    'FlagWix',
                    'FLYNOVA',
                    'Form-A-Funnel',
                    'Foxmind',
                    'Frog Work',
                    'FrogLog',
                    'FSU',
                    'GARDEN GENIE GLOVES',
                    'Gator Grip',
                    'Gebra',
                    'GEEKEY',
                    'GLIDER CAPO',
                    'GODZILLA',
                    'Gold’s Gym',
                    'Goo Jit Zu',
                    'Gorilla Gripper',
                    'Gpen',
                    'Green Day',
                    'HAMANN',
                    'Happy Nappers',
                    'HAVANA MAMBO ',
                    'Hello Neighbor',
                    //'Hole Saw',
                    'Holly Denise Simental works',
                    'Hollywood',
                    'HSL WORKS',
                    'HYGIENE HAND',
                    'ILUSTRATA WORKS',
                    'Itop',
                    'Jamiroquai',
                    'Jawzrsize',
                    'Jimi Hendrix',
                    'Kaxionage',
                    'Kendra Scott',
                    'Keyboard Cat',
                    'KeySmart',
                    'KTM',
                    'Lil Bub',
                    'Little ELF',
                    'LOCK-JAW',
                    'Loewe',
                    'Luke Combs',
                    'LUMINAID',
                    'Lynyrd Skynyrd',
                    'Mac Miller',
                    'Magentic Fingers',
                    'MAGIC-SAW ',
                    'MAGNA TILEES',
                    'MASHA AND THE BEAR',
                    'MCM',
                    //'Mini Keyboard',
                    'Mon Cheri',
                    'MOOMIN',
                    'MÖTLEY CRÜE ',
                    'Motorhead',
                    'NakeFit ',
                    'Nanoblock',
                    'NARS',
                    'Naughty Santa',
                    'NEON GENESIS EVANGELION',
                    'Nimuno Loops',
                    'NIPSEY HUSSLE',
                    'Nirvana',
                    'Novitec',
                    //'OFF-WHITE',
                    'ONE SECOND NEEDLE',
                    'OnMyWhey',
                    'Original Two-dimensional Artwork',
                    'OtterBox',
                    //'Palace',
                    'PatPat',
                    //'PEANUTS',
                    'Peropon Papa',
                    'Perry’s Music',
                    'Personal Floatation Device',
                    //'Pet Carrier',
                    'PET-AG',
                    'PETS ROCK',
                    'POCOYO',
                    //'POLO',
                    'Popdarts',
                    'Portable Door Lock',
                    'POWER FLOSS ',
                    //'pregnancy pillow',
                    'ProExtender',
                    'Qenla',
                    'Razorbacks',
                    'Recreational tray',
                    'RING SNUGGIES',
                    'Robert Farkas Works',
                    //'Roku',
                    'Roller Shoe',
                    'ROYAL ENFIELD',
                    'RUBY SLIDERS',
                    'RUMMIKUB',
                    'Sabaton',
                    //'SADDLE',
                    'Safety Nailer',
                    'Sahbabii ',
                    'Santoro',
                    'Scarlxrd ',
                    'SCHITT’S CREEK',
                    'Scrape-A-Round',
                    //'Seat Back Organizer',
                    'Secret Xpress Control',
                    'Sexy Dance',
                    'Shaquille O’Neal',
                    'Shaun the sheep',
                    'SHERLOCK HOLMES',
                    'SHRINKY DINKS',
                    'Slap Chop',
                    'SLIDEAWAY',
                    'SLIP \'N SLIDE',
                    //'Smart Lock',
                    'Snactiv',
                    'Sneaker Match',
                    'SoClean',
                    'Solo Stove',
                    'SOLTI WORKS',
                    'Sons of Arthritis',
                    'Soulfly',
                    //'SPECIALIZED',
                    'Spidercapo',
                    'SpillNot',
                    'Spy Optic',
                    'Squishmallows',
                    'Starla Michelle work',
                    'Steve McQueen',
                    'Stone Island',
                    'STUFZ',
                    'Supercalla',
                    'SuperSpeed Golf',
                    //'Supreme',
                    'Suvivalist Kermantle',
                    'Syd Barrett',
                    'TANGLE ',
                    'Tee Turtle',
                    'TELFAR',
                    'Terry O\'Neill',
                    'THE ELF ON THE SHELF',
                    //'The Mountain',
                    'The Mug With A Hoop',
                    'TOMS',
                    'Triumph',
                    'TrxTraining ',
                    'UAA',
                    'UFC',
                    'UGG',
                    'ULOVEIDO',
                    'UNO',
                    'USPC',
                    'Vineyard Vines',
                    'Vogue ',
                    'VON KOWEN Works ',
                    'WAHL',
                    //'Watch strap clasp',
                    'Weed Snatcher',
                    'Wick Centering Device',
                    'Wig Grip Apparatus',
                    'Wireless Sports Headband',
                    'WUBBANUB',
                    'Wu-Tang Clan',
                    'XYZ Corporation',
                    'YETI',
                    'Yonanas',
                    'Adidas',
                    'ELLA FITZGERALD ',
                    'Audermars Piguet',
                    'Care Bears',
                    //'Einstein',
                    'Brabus',
                    'Patagonia',
                    'Barbie',
                    'BESTWAY',
                    'Bulgari',
                    'PAUL MCCARTNEY ',
                    'Fortnite',
                    'The North Face',
                    'Betty Boop',
                    //'Benefit',
                    'Iced Earth',
                    'Poppy Playtime',
                    'Burberry',
                    'Bose',
                    'Grumpy Cat',
                    'BOOGIE',
                    'Romero Britto',
                    'Blippi',
                    'Bluey',
                    'NYAN CAT',
                    'Hatsune Miku',
                    'Magnetic Suspension Device',
                    'David Yurman',
                    'Volkswagen',
                    'Dyson',
                    'Pie Shield ',
                    'Dior ',
                    'Tiffany ',
                    'V-COMB',
                    'KIDS RIDE SHOTGUN BIKE SEAT',
                    'Versace',
                    //'Frisbee',
                    'Fendi',
                    'The Expendables',
                    'Kenzo',
                    'Goyard',
                    'SLOW TREATER ',
                    'Monster Energy',
                    'Harley Davidson',
                    'Hexbug',
                    'Herschel',
                    'THE BLACK CROWES',
                    'Hula Hoop',
                    'Fox Racing',
                    'FACAL HAIR SHAPING TOOL',
                    'THE WOLF OF WALL STREET',
                    'SLIP \'N SLIDE',
                    'Pacific Rim',
                    'ROYAL ENFIELD',
                    'Crayola',
                    //'Whirlpool',
                    'Mori Lee',
                    'Robo Fish',
                    'Givenchy',
                    'Canada Goose',
                    'Canon',
                    'The Beatles',
                    'Snapperz',
                    'JIANGWANG',
                    'Capsule Letters',
                    'Jack Daniel',
                    'Ring Sizer Adjuster',
                    'Metallica',
                    'Anne Stokes',
                    'SPIN THE SHOT',
                    'Cartier',
                    'Kareem Abdul-Jabbar ',
                    'Casio',
                    'Brochette Express',
                    'Chrome Hearts',
                    //'Converse',
                    'Lamborghini',
                    'The Smurfs',
                    'EAGLES',
                    'RayBan & Oakley',
                    'Levis',
                    'Lilly Pulitzer',
                    'Lil Pump',
                    'Frida Kahlo',
                    'RUBIK\'S Cube',
                    'Louis Vuitton',
                    'Lululemon',
                    'AS Roma',
                    'Marc Jacobs',
                    'POTTY PUTTER',
                    //'Marshall',
                    'Marilyn Monroe',
                    'MASHA AND THE BEAR ',
                    'MUFC',
                    'Elvis Preley',
                    'NBA',
                    'PJ Masks',
                    'Monchhichi',
                    'Moncler',
                    'MIFFY',
                    'Motorhead',
                    'MOOMIN',
                    'Muhammad Ali',
                    'Nike',
                    'JIMMY THE BULL ',
                    'Oakley',
                    //'MIRACULOUS',
                    'Pink Floyd',
                    'Led Zeppelin',
                    'Flag Holder',
                    'TMNT',
                    'RIMOWA',
                    'Mastodon',
                    'Celine ',
                    'Baby Shark',
                    'PINKFONG',
                    'Sandisk',
                    //'Life Tree',
                    'Swarovski',
                    'Bright Bugz',
                    'XXXTentacion',
                    'Stan Lee',
                    'Stussy',
                    'Tag Heuer',
                    'Taylor Made Golf',
                    'Tory Burch',
                    'Tommy Hilfiger',
                    'Iron Maiden',
                    'Cadillac',
                    'Chevrolet',
                    'CAMELBAK',
                    'Valentino Rossi',
                    'Paw Patrol',
                    'Def Leppard',
                    'J Mark',
                    'VIKING ARM',
                    'The Crow',
                    'Borderlands ',
                    'Chanel',
                    'Peppa Pig',
                    'Brain Flakes',
                    'MATCH MADNESS',
                    'Instantly Ageless',
                    'FinalStraw',
                    'Zippo',
                    'Tekonsha Brake Controller',
                    'Abercrombie & Fitch',
                    'Arc\'teryx ',
                    'Form A Funnel',
                    'Terry O Neill',
                    'Michael Kors ',
                    'Montblanc',
                    'Van Cleef ',
                    'MLB',
                    'NHL',
                    'NFL',
                    'Cleveland Golf',


                    '60 SECOND SALAD',
                    'RAINBOW LOOM',
                    'BRAIN FLAKES',
                    'LV',
                    'BERLUTI',
                    'PXG',
                    'THE NORTH FACE',
                    'TOMS SHOES',
                    'ABERCROMBIE&FITCH',
                    'ABERCROMBIE',
                    'HOLLISTER CO.',
                    'HOLLISTER',
                    'GILLY HICKS',
                    'YETI',
                    'ADIDAS',
                    'THE BEATLES',
                    'GOYARD',
                    'TIFFANY',
                    'FENDI',
                    'GUCCI',
                    'HARRINGTON',
                    'BROCHETTE EXPRESS',
                    'DAVID YURMAN',
                    'HAPPY BEE',
                    //'RAW',
                    'LED ZEPPELIN',
                    'RUBIK\'S CUBE',
                    'D\'ADDARIO',
                    'MIRACULOUS LADYBUG',
                    'MONCLER',
                    'POW ENTERTAINMENT',
                    'ULTIMATE GROUND ANCHOR',
                    'ST.PAULI',
                    'ROBO FISH',
                    'ANNE STOKES',
                    'KANAHEI',
                    'GRUMPY CAT',
                    'GOLDEN GOOSE',
                    'KENZO',
                    'BABY SHARK',
                    'PINKFONG',
                    'FLUTTERBYE FAIRY',
                    'COPPER FIT',
                    'ZIPPO',
                    'BLACKBERRY SMOKE',
                    'SHOTLOC',
                    'ANGLE-IZER',
                    'CAMELBAK',
                    'KEY NINJIA',
                    'WALLET NINJA',
                    'SIR PERKY',
                    'MR.BANANA',
                    'POTTY PUTTER',
                    'ROMERO BRITTO',
                    'JIMMY THE BULL',
                    'POWER FLOSS',
                    'TORQBAR',
                    'DEWALT',
                    'RAZORBACKS',
                    'PETS ROCK',
                    'THE EXPENDABLES',
                    'HULA HOOP',
                    'MISTER TWISTER',
                    'MONCHHICHI',
                    'MIFFY',
                    'IRON MAIDEN',
                    'MOTORHEAD',
                    'PINK FLOYD',
                    'FRIDA KAHLO',
                    'GIVENCHY',
                    'FINASTRAW',
                    'SNAPPI',
                    'EAGLES',
                    'MARC JACOBS',
                    //'NECTAR',
                    'NIKE',
                    'MICHAEL KORS',
                    'CANADA GOOSE',
                    'PATAGONIA',
                    'BOSE',
                    'UGG',
                    'CALVIN KLEIN',
                    'CHROME HEARTS',
                    'SWAROVSKI',
                    'PAW PATROL',
                    'RICHEMONT',
                    'HERSCHEL',
                    'GOLD\'S GYM',
                    'RIMOWA',
                    'LEVI\'S',
                    'MCM',
                    'PJ MASKS',
                    'GRID IT',
                    'ESS',
                    'MONSTER ENERGY',
                    'LULULEMON',
                    'BURBERRY',
                    'WARHAMMER',
                    'POPSOCKETS',
                    'KENDRA SCOTT',
                    'VOLKSWAGEN',
                    'TAG HEUER',
                    'NBA',
                    'MLB',
                    'NHL',
                    'NFL',
                    'TORY BURCH',
                    'SANDISK',
                    //'MAC',
                    'THE SMURFS',
                    'TOMMY HILFIGER',
                    'DIOR',
                    'HARLEY DAVIDSON',
                    'VERSACE',
                    'LILLY PULIZER',
                    'RAYBAN&OAKLEY',
                    'COZYPHONES',
                    '3 BEES & ME',
                    'PAUL MCCARTNEY',
                    'BRABUS',
                    'CARE BEARS',
                    'SNAP CIRCUITS',
                    'ROLEX',
                    'EMOJI',
                    'SUSHEZI',
                    'RANDY SAVAGE',
                    'HUGO BOSS',
                    'DOMINIQUE WILKINS',
                    //'HD VISION',
                    'CELINE',
                    'COSTA DEL MAR',
                    'HEXBUG',
                    'PRO-WAX100',
                    'BACON BIN',
                    'L.O.L SURPRISE!',
                    'TMNT',
                    'VOLBEAT',
                    'VALENTINO ROSSI',
                    'MORALE PATCH',
                    'BRIGHT BUGZ',
                    'ED HARDY',
                    'FROGLOG',
                    'TWINS SPECIAL',
                    'MAGIC TWISTY',
                    'AUDERMARS PIGUET',
                    'DEF LEPPARD',
                    'SLAP CHOP',
                    'DYSON',
                    'SELF-BALANCING VEHICLE',
                    'HATSUNE MIKU',
                    'PARKER BABY CO',
                    'MAGIC TRACKS',
                    'SOCKET SHELF',
                    'CROCS',
                    'BEANIE BOOS',
                    'SNAP-ON SMILE',
                    'SOCLEAN',
                    'NIMUNO LOOPS',
                    //'GRANDE',
                    'NENDOROID',
                    'BASEBOARD BUDDY',
                    'WHAT DO YOU MEME?',
                    'HAVANA MAMBO',
                    'CWC',
                    'EGG SITTER',
                    'LOEWE',
                    'MASHA AND THE BEAR',
                    'MANCHESTER ',
                    'STONE ISLAND',
                    'CLEVER CUTTER',
                    'UFC',
                    'GATOR GRIP',
                    'PIE SHIELD',
                    'MASTODON',
                    'BANG&OLUFSEN',
                    'BIDI',
                    'PEROPON PAPA',
                    'LIL PUMP',
                    //'UNHAPPY',
                    'SLOW TREATER',
                    'FOX RACING',
                    'J MARK',
                    'ICED EARTH',
                    'STUFZ',
                    'ELVIS PRELEY',
                    'BLACKHAWK',
                    'JACK DANIEL\'S',
                    'SQUISHMALLOWS',
                    'EVERRATCHET',
                    'SNEAKER MATCH',
                    'NIRVANA',
                    'CHROME CHERRY',
                    'NYAN CAT',
                    'THE BLACK CROWES',
                    'BESTWAY',
                    'RING SNUGGIES',
                    'BLIPPI',
                    'LYNYRD SKYNYRD',
                    'TERRY O\'NEILL',
                    'JAWZRSIZE',
                    'NARS',
                    'FIDGET CUBE',
                    'FOXMIND',
                    'MAGENTIC FINGERS',
                    'SOLO STOVE',
                    'SHAQUILLE O\'NEAL',
                    'OAKLEY',
                    'PEPPA PIG',
                    'KTM',
                    'CHANEL',
                    'PACIFIC RIM',
                    'TRXTRAINING',
                    'LUKE COMBS',
                    'SYD BARRETT',
                    'BULGARI',
                    'BORESNAKE',
                    'Pandora',
                    'Omega',
                    'iwc',
                    'Rolex',
                    'omega',
                    'Pandora',
                    //'vein',
                ];
                $map = implode('|', $map);
                preg_match_all('/\b(' . $map . ')\b/i', $content, $a);
                if (!empty($a[0])) {
                    echo $v['goods_no'] . ',' . $v['sku_no'] . ',' . implode('|', array_unique($a[0])) . "\n";
                }
            }
            $page++;
        }
    }

    /**
     * @param int $page
     */
    public function actionReplaceKeywordsGoods($page = 1)
    {
        $replace_keywords = function ($content) {
            $map = [
                'Homemiyn',
                'original',
                'authentic',
                'airsoft',
                'wholesale',
                'Amazon',
                'Drop Shipping',
                'Fat Burner',
                'free shipping',
                'weight loss',
                'YouTube',
            ];
            $map = implode('|', $map);
            $content = preg_replace('/\b(' . $map . ')\b/i', '', $content);

            $map = [
                'Hemp'
            ];
            $map = implode('|', $map);
            $content = preg_replace('/\b(' . $map . ')\b/i', 'hessian', $content);
            $content = str_replace(['()', '  '], ' ', $content);
            $content = trim($content);
            return $content;
        };

        while (true) {
            echo $page . "\n";
            $goods = Goods::find()->where(['source_method' => 1, 'status' => 10])->offset(10000 * ($page - 1))->limit(10000)->all();
            if (empty($goods)) {
                return;
            }
            foreach ($goods as $v) {
                $content = $v['goods_name'] . ' ' . $v['goods_desc'] . ' ' . $v['goods_content'];
                $map = [
                    'Homemiyn',
                    'original',
                    'authentic',
                    'airsoft',
                    'wholesale',
                    'Amazon',
                    'Drop Shipping',
                    'Fat Burner',
                    'free shipping',
                    'weight loss',
                    'YouTube',
                ];
                $map = implode('|', $map);
                preg_match_all('/\b(' . $map . ')\b/i', $content, $a);
                if (!empty($a[0])) {
                    $exist = false;
                    $tmp_name = $replace_keywords($v['goods_name']);
                    if ($tmp_name != $v['goods_name']) {
                        $v['goods_name'] = $tmp_name;
                        $exist = true;
                    }

                    $tmp_name = $replace_keywords($v['goods_desc']);
                    if ($tmp_name != $v['goods_desc']) {
                        $v['goods_desc'] = $tmp_name;
                        $exist = true;
                    }

                    $tmp_name = $replace_keywords($v['goods_content']);
                    if ($tmp_name != $v['goods_content']) {
                        $v['goods_content'] = $tmp_name;
                        $exist = true;
                    }

                    if ($exist) {
                        //$v->save();
                        echo $v['goods_no'] . ',' . $v['sku_no'] . ',' . implode('|', array_unique($a[0])) . "\n";
                        exit;
                    }
                }
            }
            $page++;
        }
    }

    /**
     * 固定订单价格
     * @param $shop_id
     * @return void
     * @throws \yii\base\Exception
     */
    public function actionFixedOrderPrice($shop_id)
    {
        $order_goods_price = OrderGoods::find()->alias('og')
            ->leftJoin(Order::tableName() . ' as o', 'o.order_id=og.order_id')
            ->select('cgoods_no,max(og.goods_income_price) price')
            ->where(['o.shop_id' => $shop_id, 'o.currency' => 'USD', 'abnormal_time' => 0, 'o.order_status' => [
                Order::ORDER_STATUS_UNCONFIRMED,
                Order::ORDER_STATUS_WAIT_PURCHASE,
                Order::ORDER_STATUS_APPLY_WAYBILL,
                Order::ORDER_STATUS_WAIT_PRINTED_OUT_STOCK,
                Order::ORDER_STATUS_WAIT_PRINTED,
                Order::ORDER_STATUS_WAIT_SHIP,
                Order::ORDER_STATUS_SHIPPED,
                Order::ORDER_STATUS_FINISH,
            ]])->groupBy('cgoods_no')->asArray()->all();
        foreach ($order_goods_price as $v) {
            $goods_shop = GoodsShop::find()->where(['shop_id' => $shop_id, 'cgoods_no' => $v['cgoods_no']])->one();
            if (abs($goods_shop['price'] - $v['price']) > 0.00001) {
                (new GoodsShopService())->updateGoodsDiscount($goods_shop['id'], 10,  $v['price']);
                echo $v['cgoods_no'] .','.$v['price']."\n";
            }
        }
    }


    /**
     * 修复商品库存详情
     * @return void
     */
    public function actionRepairGoodsStockDetails()
    {
        $limit = 0;
        while (true) {
            $limit++;
            echo $limit . "\n";
            $goods_stock_logs = GoodsStockLog::find()->offset(10000 * ($limit - 1))->limit(10000)->all();
            if (empty($goods_stock_logs)) {
                break;
            }

            foreach ($goods_stock_logs as $log_v) {
                if ($log_v['type'] == GoodsStockService::TYPE_ADMIN_CHANGE) {
                    continue;
                }
                $num = $log_v['num'];
                $warehouse = $log_v['warehouse'];
                $cgoods_no = $log_v['goods_no'];
                $admin_id = $log_v['op_user_id'];
                $add_time = $log_v['add_time'];

                if($num > 0) {
                    $purchase_order_id = $log_v['type_id'];
                    $goods_price = (new GoodsStockDetailsService())->getPrice($cgoods_no,$purchase_order_id);
                    for ($i = 1; $i <= $num; $i++) {
                        $data = [
                            'cgoods_no' => strval($cgoods_no),
                            'warehouse' => $warehouse,
                            'purchase_order_id' => empty($purchase_order_id)?'':$purchase_order_id,
                            //'order_id' => (string)$order_id,
                            'type' => empty($purchase_order_id)?GoodsStockDetails::TYPE_ADMIN:GoodsStockDetails::TYPE_PURCHASE,
                            'status' => GoodsStockDetails::STATUS_INBOUND,
                            'admin_id' => $admin_id,
                            'inbound_time' => $add_time
                        ];
                        $data['goods_price'] = empty($goods_price)?0:$goods_price;
                        //$data['outgoing_time'] = time();
                        //$data['cancel_time'] = time();
                        GoodsStockDetails::add($data);
                    }
                } else {
                    $num = abs($num);
                    $goods_stock_details = GoodsStockDetails::find()
                        ->where(['cgoods_no' => $cgoods_no, 'warehouse' => $warehouse, 'status' => GoodsStockDetails::STATUS_INBOUND])
                        ->limit($num)->all();
                    $ids = ArrayHelper::getColumn($goods_stock_details, 'id');
                    if (count($ids) < $num) {
                        $goods_price = (new GoodsStockDetailsService())->getPrice($cgoods_no);
                        $new_num = $num - count($ids);
                        for ($i = 1; $i <= $new_num; $i++) {
                            $data = [
                                'cgoods_no' => strval($cgoods_no),
                                'warehouse' => $warehouse,
                                'purchase_order_id' => '',
                                'type' => GoodsStockDetails::TYPE_ADMIN,
                                'status' => GoodsStockDetails::STATUS_INBOUND,
                                'admin_id' => $admin_id,
                                'inbound_time' => $add_time
                            ];
                            $data['goods_price'] = empty($goods_price)?0:$goods_price;
                            $ids[] = GoodsStockDetails::add($data);
                        }
                    }
                    GoodsStockDetails::updateAll([
                        'status' => GoodsStockDetails::STATUS_OUTGOING,
                        'order_id' => empty($log_v['type_id']) ? '' : $log_v['type_id'],
                        'outgoing_time' => $add_time
                    ], [
                        'status' => GoodsStockDetails::STATUS_INBOUND, 'id' => $ids
                    ]);
                }
                echo $log_v['id']."\n";
            }
        }
    }

    /**
     * 修复商品库存数量
     * @return void
     */
    public function actionRepairGoodsStock()
    {
        $goods_stock = GoodsStock::find()->all();
        foreach ($goods_stock as $v) {
            if ($v['num'] < 0) {
                continue;
            }
            $num = $v['num'];
            $cgoods_no = $v['cgoods_no'];
            $warehouse = $v['warehouse'];
            $goods_stock_details = GoodsStockDetails::find()
                ->where(['cgoods_no' => $cgoods_no, 'warehouse' => $warehouse, 'status' => GoodsStockDetails::STATUS_INBOUND])
                ->all();
            $ids = ArrayHelper::getColumn($goods_stock_details, 'id');
            if (count($ids) < $num) {
                $new_num = $num - count($ids);
                (new GoodsStockDetailsService())->inbound($cgoods_no, $warehouse, $new_num);
            }

            if (count($ids) > $num) {
                $new_num = count($ids) - $num;
                (new GoodsStockDetailsService())->outgoing($cgoods_no, $warehouse, $new_num);
            }
            echo $v['id'].' '.$v['cgoods_no'] . "\n";
        }
    }


    public function actionCleanStock()
    {
        $warehouse_id = WarehouseService::WAREHOUSE_OWN;
        /*$order_ids = Order::find()->where(['warehouse'=>$warehouse_id])->andWhere(['>','add_time',strtotime('2023-10-01')])->select('order_id')->column();
        $cgoods_no = OrderGoods::find()->where(['order_id'=>$order_ids])->select('cgoods_no')->distinct()->column();*/
        $goods_stocks = GoodsStock::find()
            ->where(['warehouse'=>$warehouse_id])->andWhere(['>','num',0])
            //->andWhere(['not in','cgoods_no',$cgoods_no])
            ->asArray()->all();
        //$shop_id = 237;
        foreach ($goods_stocks as $v) {
            $where = [
                //'shop_id'=>$shop_id,
                'cgoods_no'=>$v['cgoods_no'],
                'platform_type' => Base::PLATFORM_OZON,
                'status' => GoodsShop::STATUS_SUCCESS
            ];
            $goods_shop_lists = GoodsShop::find()->where($where)->all();
            if(empty($goods_shop_lists)){
                continue;
            }
            foreach ($goods_shop_lists as $goods_shop) {
                $goods_shop->other_tag = GoodsShop::OTHER_TAG_OVERSEAS;
                $goods_shop->save();
                $goods_shop_overseas = GoodsShopOverseasWarehouse::find()->where(['goods_shop_id' => $goods_shop['id']])->one();
                if (empty($goods_shop_overseas)) {
                    $goods_shop_overseas = new GoodsShopOverseasWarehouse();
                }
                $goods_shop_overseas->goods_shop_id = $goods_shop['id'];
                $goods_shop_overseas->shop_id = $goods_shop['shop_id'];
                $goods_shop_overseas->platform_type = $goods_shop['platform_type'];
                $goods_shop_overseas->cgoods_no = $goods_shop['cgoods_no'];
                $goods_shop_overseas->warehouse_id = $warehouse_id;
                $goods_shop_overseas->save();
                GoodsShopService::updateGoodsStock($warehouse_id, $goods_shop['cgoods_no'], true);
            }
            echo $v['cgoods_no']."\n";
        }
    }

    /**
     * 商品标题导入
     * @param $file
     * @return void
     * @throws \yii\base\Exception
     */
    public function actionGoodsNameImport($file)
    {
        $data = Excel::import($file, [
            'setFirstRecordAsKeys' => false,
        ]);

        // 多Sheet
        if (isset($data[0])) {
            $data = $data[0];
        }

        $rowKeyTitles = [
            'goods_no' => '商品编号',
            'language' => '语言',
            'goods_name' => '商品标题',
        ];
        $rowTitles = $data[1];
        $keyMap = [];
        foreach ($rowKeyTitles as $k => $v) {
            $excelKey = array_search($v, $rowTitles);
            $keyMap[$k] = $excelKey;
        }

        if(empty($keyMap['goods_no']) || empty($keyMap['language']) || empty($keyMap['goods_name'])) {
            exit('格式错误');
        }

        $count = count($data);
        $success = 0;
        for ($i = 2; $i <= $count; $i++) {
            $row = $data[$i];
            foreach ($row as &$rowValue) {
                $rowValue = !empty($rowValue) ? str_replace(' ', ' ', $rowValue) : '';
                $rowValue = !empty($rowValue) ? trim($rowValue) : '';
            }

            foreach (array_keys($rowKeyTitles) as $rowMapKey) {
                $rowKey = isset($keyMap[$rowMapKey]) ? $keyMap[$rowMapKey] : '';
                $$rowMapKey = isset($row[$rowKey]) ? trim($row[$rowKey]) : '';
            }

            if ((empty($goods_no) && empty($language)) || empty($goods_name)) {
                echo $i.',商品编号或商品标题或语言不能为空';
                continue;
            }

            if($language == 'en'){
                $goods_name = ucwords(trim($goods_name));
            }

            $goods = Goods::find()->where(['goods_no'=>$goods_no])->one();
            $goods_language = empty($goods['language'])?'en':$goods['language'];
            if($goods_language == $language) {
                $goods['goods_name'] = $goods_name;
                $goods->save();
                $success ++;
                echo $goods_no.','.$language."\n";
                continue;
            }
            $goods_translate_service = new GoodsTranslateService($language);
            $md5_content = md5($goods_name);
            $goods_translate_service->updateGoodsInfo($goods_no, 'goods_name' , $goods_name , $md5_content, GoodsTranslate::STATUS_MULTILINGUAL);
            $model = GoodsLanguage::find()->where(['goods_no' => $goods_no, 'language' => $language])->one();
            if (empty($model)) {
                $model = new GoodsLanguage();
                $model['goods_no'] = $goods_no;
            }
            $model['language'] = $language;
            $model->save();
            $success ++;
            echo $goods_no.','.$language."\n";
        }
        echo '成功:'.$success."\n";
    }

    /**
     * 复制商品
     * @param string $goods_no 旧商品
     * @param string $new_goods_no 新商品
     * @return void
     * @throws \yii\base\Exception
     */
    public function actionCop($goods_no,$new_goods_no)
    {
        $platform_information = PlatformInformation::find()->where(['goods_no' => $goods_no])->asArray()->all();
        foreach ($platform_information as $v) {
            unset($v['id']);
            $v['goods_no'] = $new_goods_no;
            PlatformInformation::add($v);
        }

        $goods_language = GoodsLanguage::find()->where(['goods_no' => $goods_no])->asArray()->all();
        foreach ($goods_language as $v) {
            unset($v['id']);
            $v['goods_no'] = $new_goods_no;
            GoodsLanguage::add($v);

            $goods_translate_service = new GoodsTranslateService($v['language']);
            $goods_language_model = $goods_translate_service->getModel();

            $goods_translate = $goods_language_model::find()->where(['goods_no' => $goods_no])->asArray()->all();
            foreach ($goods_translate as $t_v) {
                unset($t_v['id']);
                $t_v['goods_no'] = $new_goods_no;
                ((new GoodsTranslateService($v['language']))->getModel())->add($t_v);
            }
        }

        $goods_images = GoodsImages::find()->where(['goods_no' => $goods_no])->asArray()->all();
        foreach ($goods_images as $v) {
            unset($v['id']);
            $v['goods_no'] = $new_goods_no;
            GoodsImages::add($v);
        }
    }

}