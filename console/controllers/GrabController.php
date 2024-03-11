<?php
namespace console\controllers;

use common\components\CommonUtil;
use common\components\HelperStamp;
use common\components\statics\Base;
use common\extensions\google\Translate;
use common\models\BuyGoods;
use common\models\Category;
use common\models\Goods;
use common\models\GoodsEvent;
use common\models\grab\Grab;
use common\models\grab\GrabGoods;
use common\models\grab\GrabGoodsCheck;
use common\models\Order;
use common\models\OrderEvent;
use common\models\OrderGoods;
use common\models\Shop;
use common\services\api\FruugoService;
use common\services\api\RealService;
use common\services\api\SelloService;
use common\services\FApiService;
use common\services\FGrabService;
use common\services\FTransportService;
use common\services\goods\GoodsService;
use common\services\grab\AmazonGrabService;
use common\services\GrabService;
use common\services\order\OrderService;
use common\services\ProxyService;
use common\services\transport\YanwenTransportService;
use yii\console\Controller;
use yii\helpers\ArrayHelper;

class GrabController extends Controller{

    /**
     * 采集
     */
    public function actionGrab()
    {
        ini_set("memory_limit","2048M");

        //检查已经完成的任务
        $going_grab = Grab::find()->where(['status'=>Grab::STATUS_GOING])->all();
        foreach ($going_grab as $v) {
            $exist = GrabGoods::find()->where(['gid' => $v['id'], 'status' => [GrabGoods::STATUS_WAIT, GrabGoods::STATUS_GOING]])->exists();
            if ($exist === false) {
                $exist = GrabGoods::find()->where(['gid' => $v['id']])->exists();
                if($exist) {
                    $v['status'] = Grab::STATUS_SUCCESS;
                    $v->save();
                }
            }
        }
        $retry_count = 5;//重试次数
        while (true) {
            $grab_info = Grab::find()->where(['status' => Grab::STATUS_WAIT])->one();
            if (empty($grab_info)) {
                return;
            }

            try {
                FGrabService::factory($grab_info['source'])->getLists($grab_info['id']);
            } catch (\Exception $e) {
                if($grab_info['retry_count'] >= $retry_count) {
                    $grab_info['status'] = Grab::STATUS_FAILURE;
                } else {
                    $grab_info['status'] = Grab::STATUS_WAIT;
                }
                $grab_info->save();
            }
        }

    }



    /**
     * 采集商品
     * @param int $limit
     */
    public function actionGrabGoods($limit = 1)
    {
        ini_set("memory_limit", "2048M");
        if ((date('G') % 8) == 5) {
            sleep(60);
            return;
        }

        $grab_goods_all = GrabGoods::find()->where(['status' => GrabGoods::STATUS_WAIT, 'source_method' => GoodsService::SOURCE_METHOD_AMAZON])->orderBy('gid asc,retry_count asc')->offset(($limit - 1) * 10)->limit(10)->all();
        if (empty($grab_goods_all)) {
            //CommonUtil::logs('error(11) '.$limit.':' . $grab_goods['id'] . ' ' .date('Y-m-d H:i:s'), 'grab_goods1');
            sleep(30);
            return;
        }

        foreach ($grab_goods_all as $grab_goods) {
            $retry_count = 3;//重试次数
            //CommonUtil::logs('error(10) '.$limit.':' . $grab_goods['id'] . ' ' .date('Y-m-d H:i:s'), 'grab_goods1');
            $grab_goods->retry_count = $grab_goods->retry_count + 1;
            $grab_goods->status = GrabGoods::STATUS_GOING;
            $grab_goods->save();

            $grab = Grab::find()->where(['id' => $grab_goods['gid']])->asArray()->one();
            $url = $grab_goods['url'];
            $url = urldecode($url);
            $asin = '';
            if (preg_match('@/dp/(.+)/@', $url, $arr)) {
                $asin = $arr[1];
            }
            $exist_grab_goods = null;
            if (!empty($asin)) {
                //CommonUtil::logs('error(12) '.$limit.':' . $grab_goods['id'] . ' ' .date('Y-m-d H:i:s'), 'grab_goods1');
                //$exist_grab_goods = GrabGoods::find()->where(['md5' => $grab_goods['md5'], 'status' => GrabGoods::STATUS_SUCCESS])->asArray()->one();
                //CommonUtil::logs('error(13) '.$limit.':' . $grab_goods['id'] . ' ' .date('Y-m-d H:i:s'), 'grab_goods1');
                $exist_grab_goods = GrabGoods::find()->where(['asin' => $asin, 'source' => $grab_goods['source']])->andWhere(['!=', 'id', $grab_goods['id']])->asArray()->one();
            }
            if ($exist_grab_goods) {
                try {
                    $info = [
                        'category' => $grab['title'],
                        'asin' => $exist_grab_goods['asin'],
                        'title' => $exist_grab_goods['title'],
                        'price' => $exist_grab_goods['price'],
                        'evaluate' => $exist_grab_goods['evaluate'],
                        'score' => $exist_grab_goods['score'],
                        'desc' => $exist_grab_goods['desc'],
                        'desc1' => $exist_grab_goods['desc1'],
                        'desc2' => $exist_grab_goods['desc2'],
                        'images1' => $exist_grab_goods['images1'],
                        'images2' => $exist_grab_goods['images2'],
                        'images3' => $exist_grab_goods['images3'],
                        'images4' => $exist_grab_goods['images4'],
                        'images5' => $exist_grab_goods['images5'],
                        'images6' => $exist_grab_goods['images6'],
                        'images7' => $exist_grab_goods['images7'],
                        'weight' => $exist_grab_goods['weight'],
                        'dimension' => $exist_grab_goods['dimension'],
                        'brand' => $exist_grab_goods['brand'],
                        'colour' => $exist_grab_goods['colour'],
                        'goods_status' => $exist_grab_goods['goods_status'],
                        'status' => GrabGoods::STATUS_REPEAT,
                    ];
                    GrabGoods::updateOneById($grab_goods['id'], $info);
                } catch (\Exception $e) {
                    CommonUtil::logs('error(10):' . $grab_goods['id'] . ' ' . $e->getMessage(), 'grab_goods');
                    if ($grab_goods->retry_count >= $retry_count) {
                        $grab_goods->status = GrabGoods::STATUS_FAILURE;
                    } else {
                        $grab_goods->status = GrabGoods::STATUS_WAIT;
                    }
                    $grab_goods->save();
                }
            } else {
                try {
                    //$info = (new AmazonDeGrabService())->getDetail($grab_goods['url']);
                    $info = FGrabService::factory($grab_goods['source'])->getDetail($grab_goods['url']);
                } catch (\Exception $e) {
                    CommonUtil::logs('error(30):' . $grab_goods['id'] . ' ' . $e->getMessage(), 'grab_goods');
                    $grab_goods->status = $e->getCode() == 8900 ? GrabGoods::STATUS_FAILURE : GrabGoods::STATUS_WAIT;
                    if ($grab_goods->retry_count >= $retry_count) {
                        $grab_goods->status = GrabGoods::STATUS_FAILURE;
                    }
                    $grab_goods->save();
                    continue;
                }
                //CommonUtil::logs('error(14) '.$limit.':' . $grab_goods['id'] . ' ' .date('Y-m-d H:i:s'), 'grab_goods1');

                if (empty($info)) {
                    if ($grab_goods->retry_count >= $retry_count) {
                        $grab_goods->status = GrabGoods::STATUS_FAILURE;
                    } else {
                        $grab_goods->status = GrabGoods::STATUS_WAIT;
                    }
                    $grab_goods->save();
                } else {
                    try {
                        $is_exist = GrabGoods::find()->where(['asin' => $info['asin'], 'source' => $grab_goods['source']])->andWhere(['!=', 'id', $grab_goods['id']])->select('id')->exists();
                        $info['category'] = $grab['title'];
                        $info['status'] = $is_exist ? GrabGoods::STATUS_REPEAT : GrabGoods::STATUS_SUCCESS;
                        GrabGoods::updateOneById($grab_goods['id'], $info);
                    } catch (\Exception $e) {
                        CommonUtil::logs('error(20):' . $grab_goods['id'] . ' ' . $e->getMessage() .$e->getTraceAsString(), 'grab_goods');
                        if ($grab_goods->retry_count >= $retry_count) {
                            $grab_goods->status = GrabGoods::STATUS_FAILURE;
                        } else {
                            $grab_goods->status = GrabGoods::STATUS_WAIT;
                        }
                        $grab_goods->save();
                    }
                }
            }
            //CommonUtil::logs('error(15) '.$limit.':' . $grab_goods['id'] . ' ' .date('Y-m-d H:i:s'), 'grab_goods1');
        }
    }

    /**
     * 采集自建商品
     * @param int $limit
     */
    public function actionGrabOwnGoods($limit = 1,$mode = 1,$gid = null)
    {
        ini_set("memory_limit", "2048M");
        if ((date('G') % 8) == 5) {
            //sleep(60);
            //return;
        }

        //1白天 2晚上
        if(date('G') >= 19 || date('G') < 9) {//晚上
            if ($mode == 1) {
                sleep(100);
                return;
            }
        } else {
            if ($mode == 2) {
                sleep(100);
                return;
            }
        }

        $grab_goods_key = 'com::grab_goods::val::';
        $cache = \Yii::$app->redis;
        /*$cp_key = md5(gethostname());
        $goods_key = 'com::grab_goods::'.$cp_key;
        $lock_key = 'com::grab_goods::lock::'.$cp_key;
        $lock = $cache->get($lock_key);
        if(!empty($lock)){
            sleep(100);
            return;
        }*/

        $grab_goods_all = GrabGoods::find()->where(['status' => GrabGoods::STATUS_WAIT, 'source_method' => GoodsService::SOURCE_METHOD_OWN]);
        if(!is_null($gid)){
            $grab_goods_all = $grab_goods_all->andWhere(['>=','gid',$gid]);
        }
        $grab_goods_all = $grab_goods_all->orderBy('gid asc,retry_count asc,id desc')->offset(($limit - 1) * 10)->limit(10)->all();
        if (empty($grab_goods_all)) {
            //CommonUtil::logs('error(11) '.$limit.':' . $grab_goods['id'] . ' ' .date('Y-m-d H:i:s'), 'grab_goods1');
            sleep(30);
            return;
        }

        foreach ($grab_goods_all as $grab_goods) {
            $grab_goods_cache_key = $grab_goods_key.$grab_goods['id'];
            $exist_lock = $cache->get($grab_goods_cache_key);
            if(!empty($exist_lock)){
                continue;
            }
            $cache->setex($grab_goods_cache_key, 60 * 15, '1');

            $retry_count = 3;//重试次数
            //CommonUtil::logs('error(10) '.$limit.':' . $grab_goods['id'] . ' ' .date('Y-m-d H:i:s'), 'grab_goods1');
            $grab_goods->retry_count = $grab_goods->retry_count + 1;
            $grab_goods->status = GrabGoods::STATUS_GOING;
            $grab_goods->save();

            $grab = Grab::find()->where(['id' => $grab_goods['gid']])->asArray()->one();
            $url = $grab_goods['url'];
            $url = urldecode($url);
            $asin = $grab_goods['asin'];
            if (empty($asin) && preg_match('@/dp/(.+)/@', $url, $arr)) {
                $asin = $arr[1];
            }
            $exist_grab_goods = null;
            if (!empty($asin)) {
                $exist_grab_goods = Goods::find()->where(['source_platform_type' => $grab_goods['source'], 'sku_no' => $asin, 'source_method' => GoodsService::SOURCE_METHOD_OWN])
                    ->select('id')->asArray()->exists();
            }

            //重复标题
            /*if (empty($exist_grab_goods) && !empty($grab_goods['title'])) {
                $work_arr = explode(' ', $grab_goods['title']);
                $i = 0;
                $work = [];
                foreach ($work_arr as $arr_v) {
                    if (!in_array(strtolower($arr_v), ['in', 'on', 'form', 'or', 'and']) && preg_match("/^[a-zA-Z\s]+$/", $arr_v)) {
                        $i++;
                        $work[] = '+' . $arr_v;
                    }
                    if ($i > 5) {
                        break;
                    }
                }
                //采集标题
                $goods_lists = GrabGoods::find()->where("MATCH (title) AGAINST (:word IN BOOLEAN MODE)", [':word' => implode(' ', $work)])
                    ->andWhere(['!=', 'id', $grab_goods['id']])->andWhere(['status'=>GrabGoods::STATUS_SUCCESS])->asArray()->all();
                foreach ($goods_lists as $goods_v) {
                    similar_text($goods_v['title'], $grab_goods['title'], $percent);
                    if ($percent > 99.9) {
                        $exist_grab_goods = true;
                        break;
                    }
                }
            }*/

            if ($exist_grab_goods) {
                $grab_goods->status = GrabGoods::STATUS_REPEAT;
                $grab_goods->save();
            } else {
                if (!empty($grab_goods['title']) && !in_array($grab_goods['source'],[Base::PLATFORM_RDC])) {
                    $grab_goods['title'] = str_replace(['Hmwy-', 'Lbq-'], '', $grab_goods['title']);
                    $grab_goods['title'] = ucwords(trim($grab_goods['title']));
                    //自建查重
                    if ($grab['source_method'] == GoodsService::SOURCE_METHOD_OWN) {
                        $re_goods_no = GoodsService::existRepeatGoodsName($grab_goods['title']);
                        if (!empty($re_goods_no)) {
                            $grab_goods->goods_no = $re_goods_no;
                            $grab_goods->status = GrabGoods::STATUS_REPEAT;
                            $grab_goods->save();
                            $goods = Goods::find()->where(['goods_no'=>$re_goods_no])->one();
                            if($goods['status'] == Goods::GOODS_STATUS_WAIT_MATCH) {
                                Goods::updateAll(['add_time' => time()], ['goods_no' => $re_goods_no]);
                            }
                            CommonUtil::logs('error(20):' . $grab_goods['id'] . '  重复标题:' . $grab_goods['title'], 'grab_goods');
                        }
                        continue;
                    }
                }
                try {
                    $goods_nos = (new GoodsService())->grab($grab_goods['url'],null,[
                        'category_id' =>$grab['category_id'],
                        'pgoods_no' => $grab_goods['pgoods_no'],
                        'grab' => $grab,
                    ]);
                    if(!empty($goods_nos)) {
                        $grab_goods->goods_no = current($goods_nos);
                    }
                    $grab_goods->status = GrabGoods::STATUS_SUCCESS;
                    $grab_goods->save();
                } catch (\Exception $e) {
                    if( $e->getCode() == 9998) {//不需要采集
                        $grab_goods->status = GrabGoods::STATUS_SUCCESS;
                        $grab_goods->goods_no = '-2';
                        $grab_goods->save();
                        continue;
                    }
                    CommonUtil::logs('error(20):' . $grab_goods['id'] . ' ' . $e->getMessage(), 'grab_goods');
                    $grab_goods->status = $e->getCode() == 8900 ? GrabGoods::STATUS_FAILURE : GrabGoods::STATUS_WAIT;
                    if ($grab_goods->retry_count >= $retry_count) {
                        $grab_goods->status = GrabGoods::STATUS_FAILURE;
                    }
                    $grab_goods->save();
                }
            }

            /*if($grab_goods['status'] == 0) {
                $request_num = $cache->incrby($goods_key, 1);
                $ttl_lock = $cache->ttl($goods_key);
                if ($request_num == 1 || $ttl_lock > 1000 || $ttl_lock == -1) {
                    $cache->expire($goods_key, 600);
                }
                if ($request_num > 10) {
                    $cache->setex($lock_key, 60 * 30, '1');
                    return;
                }
            }*/

            echo date('Y-m-d H:i:s').' id:'.$grab_goods['id'].' goods_no:' .$grab_goods->goods_no.' success:'.$grab_goods['status'] ."\n";
        }
    }

    /**
     * 检测库存
     */
    public function actionCheckStock()
    {
        if(date('H') >= 1 && date('H') < 7){
            sleep(60);
            return;
        }

        $time = time()-2*60*60;
        $buy_goods_all = BuyGoods::find()->where(['buy_goods_status' => BuyGoods::BUY_GOODS_STATUS_OUT_STOCK,'source_method'=>GoodsService::SOURCE_METHOD_AMAZON])
            ->andWhere(['<=','check_stock_time',$time])->limit(20)->all();
        if(empty($buy_goods_all)){
            sleep(60);
            return;
        }

        foreach ($buy_goods_all as $buy_goods) {
            CommonUtil::logs('time:'.date('Y-m-d H:i:s').' start:' . $buy_goods['id'] , 'order_check_stock');
            if(empty($buy_goods['buy_goods_url'])){
                continue;
            }

            try {
                $info = FGrabService::factory($buy_goods['platform_type'])->getDetail($buy_goods['buy_goods_url']);
                if ($info['goods_status'] == GrabGoods::GOODS_STATUS_NORMAL && $info['self_logistics'] == GrabGoods::SELF_LOGISTICS_YES) {
                    $buy_goods->buy_goods_status = BuyGoods::BUY_GOODS_STATUS_IN_STOCK;
                }
                $buy_goods->check_stock_time = time();
                $buy_goods->save();
            } catch (\Exception $e) {
                $buy_goods->check_stock_time = time();
                $buy_goods->save();
                CommonUtil::logs('error:' . $buy_goods['id'] . ' ' . $e->getMessage(), 'order_check_stock_error');
                return;
            }
        }
    }


    /**
     * 检测商品库存
     * @param $source
     * @param int $limit
     */
    public function actionCheckGoodsStock($source,$limit = 1,$is_qh = 1)
    {
        if((date('G')%8) == 5){
            sleep(60);
            return;
        }

        $grab_goods_all = GrabGoods::find()->where(['use_status'=>GrabGoods::USE_STATUS_VALID,'source'=>$source,'source_method'=>GoodsService::SOURCE_METHOD_AMAZON]);
        if ($is_qh == 1){
            $time = time()-5*24*60*60;
            $grab_goods_all = $grab_goods_all->andWhere(['and',['=','goods_status',GrabGoods::GOODS_STATUS_NORMAL],['=','self_logistics',GrabGoods::SELF_LOGISTICS_YES]]);
        }else{
            $time = time()-3*24*60*60;
            $grab_goods_all = $grab_goods_all->andWhere(['or',['=','goods_status',GrabGoods::GOODS_STATUS_OUT_STOCK],['=','self_logistics',GrabGoods::SELF_LOGISTICS_NO]]);
        }
        $grab_goods_all = $grab_goods_all->andWhere(['<=','check_stock_time',$time])
            ->offset(50*($limit-1))->limit(50)->orderBy('check_stock_time asc,id asc')->all();
        if(empty($grab_goods_all)){
            sleep(60);
            return;
        }

        foreach ($grab_goods_all as $grab_goods) {
            $asin = $grab_goods['asin'];
            CommonUtil::logs('time:'.date('Y-m-d H:i:s').' start:' . $grab_goods['id'] , 'goods_check_stock');
            if(empty($asin)){
                continue;
            }

            try {
                $grab_check = new GrabGoodsCheck();
                $grab_check['source'] = $grab_goods['source'];
                $grab_check['asin'] = $grab_goods['asin'];
                $result = FGrabService::factory($source)->checkStock($asin);
                if (!empty($result)) {
                    $grab_check['old_goods_status'] = $grab_goods['goods_status'];
                    $grab_check['old_self_logistics'] = $grab_goods['self_logistics'];
                    $grab_check['self_logistics'] = $grab_goods['self_logistics'];

                    $has_change = false;
                    if($result['stock'] != $grab_goods['goods_status']){
                        $grab_goods['goods_status'] = $result['stock'];
                        $has_change = true;
                    }
                    $grab_check['goods_status'] = $result['stock'];

                    if($result['self_logistics'] != 2) {
                        if ($result['self_logistics'] != $grab_goods['self_logistics']) {
                            $grab_goods['self_logistics'] = $result['self_logistics'];
                            $has_change = true;
                        }
                        $grab_check['self_logistics'] = $result['self_logistics'];
                    }

                    if($has_change) {
                        $grab_check->save();
                    }
                    $grab_goods['check_stock_time'] = time();
                }else{
                    CommonUtil::logs('error:' . $grab_goods['id'] . ' 商品查找失败', 'goods_check_stock_error');
                    $grab_goods['check_stock_time'] = 1999999999;
                }
            } catch (\Exception $e) {
                CommonUtil::logs('error:' . $grab_goods['id'] . ' ' . $e->getMessage(), 'goods_check_stock_error');
                $grab_goods['check_stock_time'] = 1999999999;
            }
            $grab_goods->save();
        }
    }

    /**
     * 检测ip
     */
    public function actionCheckIp()
    {
        $url = 'http://yadmin.sanlinmail.site/1.php';
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL,$url);
        //curl_setopt($curl, CURLOPT_PROXY, "209.205.216.2212");
        //curl_setopt($curl, CURLOPT_PROXYPORT, "10002");
        //curl_setopt($curl, CURLOPT_PROXYUSERPWD, "astoip3305-country-GB:0bc529-6e136d-1dd494-70d7f3-68442");
        echo date('Y-m-d H:i:s') .' ';
        $result=curl_exec($curl);
        echo "\n";
        sleep(10);
        exit();
    }

    public function actionTest()
    {
        for ($i = 1; $i <= 261; $i++) {
            $url = 'https://www.fruugo.co.uk/dzk/b-233?page=' . $i;
            $grab_service = (new GrabService(1))->useProxy(false);
            $ql = $grab_service->getHtml($url);
            $rt = $ql->range('.products-list .product-item')->rules([
                'url' => ['.justify-content-between', 'href'],
            ])->query()->getData()->all();
            foreach ($rt as $v) {
                $url_d = 'https://www.fruugo.co.uk' . $v['url'];
                $ql = $grab_service->getHtml($url_d);

                $field_list1 = $ql->range('.product-description-spec-list .mr-16')->rules([
                    'left' => ['strong', 'text'],
                    'right' => ['span', 'text'],
                ])->query()->getData();
                $ean = '';
                foreach ($field_list1 as $item) {
                    if ($item['left'] == 'EAN:') {
                        $ean = $item['right'];
                    }
                }
                echo $i.','.$ean . "\n";
            }
        }
    }


    /**
     * 更新品牌
     * @param $source
     * @param int $limit
     */
    public function actionUpdateBrand($source,$limit = 1)
    {
        $grab_goods_all = GrabGoods::find()->where(['brand'=>'','source'=>$source,'use_status'=>GrabGoods::USE_STATUS_VALID])
            ->offset(10*($limit-1))->limit(10)->orderBy( 'id asc')->all();
        if(empty($grab_goods_all)){
            sleep(60);
            return;
        }
        foreach ($grab_goods_all as $grab_goods) {
            CommonUtil::logs('time:'.date('Y-m-d H:i:s').' start:' . $grab_goods['id'] , 'update_goods_brand');
            echo  ' start:' . $grab_goods['id'] ."\n";
            try {
                try{
                    $result = FGrabService::factory($source)->getDetail($grab_goods['url']);
                } catch (\Exception $e) {
                    if($e->getCode() == 404){
                        $grab_goods->check_stock_time = 1999999998;
                        $grab_goods->use_status = 30;
                        $grab_goods->save();
                        echo  ' end:' . $grab_goods['id'] . ' '.$grab_goods['asin'] ."  404\n";
                        continue;
                    }
                }
                if (!empty($result)) {
                    if(!empty($result['price'])) {
                        $grab_goods['price'] = $result['price'];
                    }
                    $grab_goods['brand'] = empty($result['brand'])?'-':$result['brand'];
                    $grab_goods->save();
                    echo  ' end:' . $grab_goods['id'] ."\n";
                }else{
                    echo  ' end:' . $grab_goods['id'] ." 商品查找失败\n";
                    CommonUtil::logs('error:' . $grab_goods['id'] . ' 商品查找失败', 'update_goods_brand_error');
                }
            } catch (\Exception $e) {
                CommonUtil::logs('error:' . $grab_goods['id'] . ' ' . $e->getMessage(), 'update_goods_brand_error');
            }
        }
    }

    /**
     * 重新执行失败任务
     */
    public function actionReFail()
    {
        //商品同步执行超出频次限制
        GoodsEvent::updateAll(['status'=>0],['and',[
                'status' => 30,
                'platform' => 11,
            ],
            ['not in','error_msg',['无报价数据','更新失败','失败','删除失败','client_key为空','该商品已经删除']]
        ]);

        GoodsEvent::updateAll(['status'=>0],['and',[
            'status' => 30,
            //'platform' => 37,
        ],
            ['like','error_msg','Connection timed out after']
        ]);

        GoodsEvent::updateAll(['status'=>0],['and',[
            'status' => 30,
            'event_type' => GoodsEvent::EVENT_TYPE_GET_GOODS_ID,
            //'platform' => 37,
        ],
            ['like','error_msg','Operation timed out after']
        ]);

        OrderEvent::updateAll(['status'=>0],['and',[
            'status' => 30,
            //'platform' => 37,
        ],
            ['like','error_msg','Connection timed out after']
        ]);

        OrderEvent::updateAll(['status'=>0],['and',[
            'status' => 30,
            'platform' => 37,
        ],
            ['like','error_msg','Operation timed out after']
        ]);

        OrderEvent::updateAll(['status'=>0],['and',[
            'status' => 30,
            //'platform' => 37,
        ],
            ['like','error_msg','429 Too Many Requests']
        ]);

        OrderEvent::updateAll(['status'=>0],['and',[
            'status' => 30,
            'platform' => 37,
        ],
            ['like','error_msg','oms-external.hepsiburada.com']
        ]);

        GoodsEvent::updateAll(['status'=>0],['and',[
            'status' => 30,
            //'platform' => 37,
        ],
            ['like','error_msg','Redis command was']
        ]);

        //采集失败
        /*GrabGoods::updateAll(['status'=>0,'retry_count'=>0],['and',[
                'status' => 30,
            ],
            ['>=','gid',3200
        ]]);*/
        //采集认领失败
        /*GrabGoods::updateAll(['check_stock_time'=>0],[
            'status' => 20,
            'check_stock_time'=>1999999999
        ]);
        //库存检测执行失败
        Goods::updateAll(['check_stock_time'=>0],[
            'check_stock_time'=>1999999999
        ]);*/
        echo date('Y-m-d H:i')." all done\n";
        /*update ys_goods_event set status =0 where status =30  and platform =11 and error_msg != '无报价数据' and error_msg != '更新失败';
        update ys_grab_goods set status =0 ,retry_count =0 where status =30 and gid >= 3100;
        update ys_grab_goods set  check_stock_time = 0 where status =20 and check_stock_time = 1999999999;
        update ys_goods set  check_stock_time = 0 where  check_stock_time = 1999999999;*/
    }


    /**
     * 更新代理
     */
    public function actionReProxy()
    {
        ProxyService::getOneProxy(true);
    }

    public function actionTest2($file,$shop_id)
    {
        $file = fopen($file, "r") or exit("Unable to open file!");
        $shop = Shop::find()->where(['id' => $shop_id])->asArray()->one();
        while (!feof($file)) {
            $line = trim(fgets($file));
            if (empty($line)) continue;

            list($asin) = explode(',', $line);

            if (empty($asin)) {
                continue;
            }

            $goods = Goods::find()->where(['sku_no' => $asin, 'source_platform_type' => Base::PLATFORM_AMAZON_DE])->asArray()->one();
            try {
                $api_service = FApiService::factory($shop);
            } catch (\Exception $e) {
                echo 'error: shop_id:' . $shop['id'] . ' platform_type:' . $shop['platform_type'] . $e->getMessage() . "\n";
                continue;
            }

            if (!in_array($shop['platform_type'], [Base::PLATFORM_REAL_DE, Base::PLATFORM_FRUUGO])) {
                continue;
            }

            $price = $goods['price'];
            $stock = $goods['stock'] == Goods::STOCK_YES ? true : false;

            //德国250  $price*1.35+2
            //英国100  $pice*1.4+2
            if ($goods['source_platform_type'] == Base::PLATFORM_AMAZON_DE) {
                if ($price >= 250) {
                    $stock = false;
                }
                $price = ceil($price * 1.35 + 2) - 0.01;
            } else if ($goods['source_platform_type'] == Base::PLATFORM_AMAZON_CO_UK) {
                if ($price >= 100) {
                    $stock = false;
                }
                $price = ceil($price * 1.4 + 2) - 0.01;
            } else {
                continue;
            }

            $error = '';
            try {
                $result = $api_service->updateStock($goods['sku_no'], $stock, null);
            } catch (\Exception $e) {
                $result = 0;
                $error = $e->getMessage();
            }

            if ($result == 1) {
                echo $asin . "\n";
            } else {
                if ($result == -1) {
                    echo $asin . ' #无报价数据' . "\n";
                } else {
                    echo $asin . ' #' . (empty($error) ? '更新失败' : $error) . "\n";
                }
            }
        }
        fclose($file);
        echo "all done\n";
        exit;
    }

    public function actionTest1()
    {
        $shop = Shop::findOne(43);
        $api_service = FApiService::factory($shop);
        $result = $api_service->getProductsAttributesToAsin('GF29413');
        var_dump($result);
        exit();

        $param = '{"refresh_token":"eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX25hbWUiOiI5ODMzMzQ5NiIsInNjb3BlIjpbImFsbGVncm86YXBpOm9yZGVyczpyZWFkIiwiYWxsZWdybzphcGk6cHJvZmlsZTp3cml0ZSIsImFsbGVncm86YXBpOnNhbGU6b2ZmZXJzOndyaXRlIiwiYWxsZWdybzphcGk6YmlsbGluZzpyZWFkIiwiYWxsZWdybzphcGk6Y2FtcGFpZ25zIiwiYWxsZWdybzphcGk6ZGlzcHV0ZXMiLCJhbGxlZ3JvOmFwaTpzYWxlOm9mZmVyczpyZWFkIiwiYWxsZWdybzphcGk6YmlkcyIsImFsbGVncm86YXBpOm9yZGVyczp3cml0ZSIsImFsbGVncm86YXBpOmFkcyIsImFsbGVncm86YXBpOnBheW1lbnRzOndyaXRlIiwiYWxsZWdybzphcGk6c2FsZTpzZXR0aW5nczp3cml0ZSIsImFsbGVncm86YXBpOnByb2ZpbGU6cmVhZCIsImFsbGVncm86YXBpOnJhdGluZ3MiLCJhbGxlZ3JvOmFwaTpzYWxlOnNldHRpbmdzOnJlYWQiLCJhbGxlZ3JvOmFwaTpwYXltZW50czpyZWFkIl0sImFsbGVncm9fYXBpIjp0cnVlLCJhdGkiOiJlYjk5NjAyZC1jZWNkLTRmY2QtOTUyMy1hOTQ5Y2MyMTVmM2EiLCJleHAiOjE2MzA5NzczNjIsImp0aSI6IjQ5ZWUwNGIzLTE5M2MtNGEwOC04YjVlLTZkMDEwMTA3ZTg4MyIsImNsaWVudF9pZCI6Ijg1YmFjMGM4MDdmMDQxMGNhYWRhNzUwOTk2Mzk1NGQxIn0.2mHpstvjZu_yOGiI7Fhay-39qRpu7k5x0CVpw6A0DyT8zzevqFg56-5aP5bHTYDllCF4o7N1fcTRZwEv_CHDUAayHaHyHQdiR-NevlwC17R3ZmLPzfGc03lbLmRtYxzf-vkn6VuTkiINNRYi1sjbtDWBAebCbtdq8HgdZnidDNxTPydPqxigyRMMz5SiHCPhKndAzN6OMoavpGkn4wShT6AQUc5ms7T_BYl-tODElY_Ybn3vchW5Al3AWXgkVRqz1N7B0vW7NEJsQ0yQ92ZWyHRxfupKEUbJ12MZbhl5n2PuNY2h-97Gp8rhT_vHHc3i6GT8Rj-GPl_qLrsRiraPEA"}';
        $api_service = FApiService::factory(Base::PLATFORM_ALLEGRO, '85bac0c807f0410caada7509963954d1', 'FkwiVuqAgM67PRkueR06mwFotOcgpQ4WN5HbOZtqXjHCNgpurraGBvwmOAkZ6RqU',$param);
        //var_dump($api_service->getToken());
        //$order_lists = $api_service->getProductsToAsin('DigitalWatch133');

        $order_lists = $api_service->updateStock('DigitalWatch133',1,75.99);
        var_dump($order_lists);
        exit();
        /*$param = '{"refresh_token":"eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX25hbWUiOiI5ODMzMzQ5NiIsInNjb3BlIjpbImFsbGVncm86YXBpOm9yZGVyczpyZWFkIiwiYWxsZWdybzphcGk6cHJvZmlsZTp3cml0ZSIsImFsbGVncm86YXBpOnNhbGU6b2ZmZXJzOndyaXRlIiwiYWxsZWdybzphcGk6YmlsbGluZzpyZWFkIiwiYWxsZWdybzphcGk6Y2FtcGFpZ25zIiwiYWxsZWdybzphcGk6ZGlzcHV0ZXMiLCJhbGxlZ3JvOmFwaTpzYWxlOm9mZmVyczpyZWFkIiwiYWxsZWdybzphcGk6YmlkcyIsImFsbGVncm86YXBpOm9yZGVyczp3cml0ZSIsImFsbGVncm86YXBpOmFkcyIsImFsbGVncm86YXBpOnBheW1lbnRzOndyaXRlIiwiYWxsZWdybzphcGk6c2FsZTpzZXR0aW5nczp3cml0ZSIsImFsbGVncm86YXBpOnByb2ZpbGU6cmVhZCIsImFsbGVncm86YXBpOnJhdGluZ3MiLCJhbGxlZ3JvOmFwaTpzYWxlOnNldHRpbmdzOnJlYWQiLCJhbGxlZ3JvOmFwaTpwYXltZW50czpyZWFkIl0sImFsbGVncm9fYXBpIjp0cnVlLCJhdGkiOiI3NjMwNTE5ZS03M2RkLTQ2OTctOGY2YS04YmYxZTU2MzViNzAiLCJleHAiOjE2MzA5ODM0OTIsImp0aSI6ImYxNTlhZWNjLTY5MWUtNDVlYi05MzM3LTA3MWYyZTMzNzgzMiIsImNsaWVudF9pZCI6Ijg1YmFjMGM4MDdmMDQxMGNhYWRhNzUwOTk2Mzk1NGQxIn0.FWxYdCPy6aaAsJwjTKIwpygASiGrzJX0byvS5e2ukkGncIpmS7r8naFZBJcHe5HWnMH2kkkr9vfB4b-osToehVXhbWrLUmwW-U4WwAF3prrPsGi3qhHXdZYDtP_ltGBXmPFttLDTKig2cGgTIYjLDyVAaBHN-Gq9ffoLHUCi1DCo8lQjWVJ2nD41X-Lx_5bmW4mDthGx_kfC57DuaI9JflluirqovGB0CH97yYCnEEEGZVtQ9Hgxf7TgBJeBT4CM0_E0QNd2vmCfW2jcK1_mJqDEML7kwnNubouktRsxWwh2LSyN10k4J18DCukm5JLMHo-3sxYTudemb1jX4MxFvQ","shop_id":5}';
        $api_service = FApiService::factory(Base::PLATFORM_ALLEGRO, '85bac0c807f0410caada7509963954d1', 'FkwiVuqAgM67PRkueR06mwFotOcgpQ4WN5HbOZtqXjHCNgpurraGBvwmOAkZ6RqU',$param);*/
        //var_dump($api_service->getToken());
        $order_lists = $api_service->getOrderLists('2021-06-05');
        foreach ($order_lists as $order) {
            $order = $api_service->dealOrder($order);
            var_dump($order);
        }
        exit();
        $content=file_get_contents('https://ae01.alicdn.com/kf/H9418ea7b486941dca3a95eb9159299f34/Puzzle-Wooden-Toys-Magnetic-Fruit-Tree-Montessori-Toys-Educational-Toys-Match-Children-Montessori-Materials-Magnetic-Apple.jpg_640x640.jpg');
        $file_content=chunk_split(base64_encode($content));//base64编码
        /*switch($type[2]){//判读图片类型
            case 1:$img_type="gif";break;
            case 2:$img_type="jpg";break;
            case 3:$img_type="png";break;
        }*/
        $img='data:image/jpg'.';base64,'.$file_content;//合成图片的base64编码

        echo ($img);
        exit();

        var_dump((new HelperStamp(Order::$printed_status_map))->getStamps(2));exit;
        //$channels = (new YanwenShipmentService())->getChannels(100000);
        //var_dump($channels);
        $order = Order::findAll(['order_id'=>'O06214918088007']);

        $ser = FTransportService::factory('yanwen');
        $re = $ser->doPrint($order);
        var_dump($re);
        exit;
        $api_service = FApiService::factory(Base::PLATFORM_ONBUY, 'ck_live_8efa5d02c20c4fb1abf54a6a33a32fe2', 'sk_live_17aa12f67fcd4d1b8e9a5b67ca70aa22');
        //$token = $api_service->getOrderLists('2021-05-15');
        //$token = $api_service->updateStock('B075L1W18R',1,130.99);
        $token = $api_service->getOrderSend('TDM6TT','Amazon Logistics UK','204-1792300-2160355','1621407411');
        var_dump($token);
        exit();

        //57635312

        //hf
        //$products = (new SelloService('54cada2cadfa86d01970d17239bfa850','9153e8e9637a1916f02659d76b16fa609fa092a4ffac56f4188ba779f328993c'));
        //wx
        //$products = (new SelloService('9ff2490b306f1d881450890ae9de81cd','0839b918f66b252f8e757a73bcd2f92be241d0814da37a054291ab318987d0bc'));
        //gu
        //$products = (new SelloService('c3f3e0dd18f5c32653333a5e3a5ffcb8','12f6c02fa617fee6ae1748514ef83a68f01bdee25f3348f38207b5edcb94ba7e'));
        //xgf
        $products = (new SelloService('497504866646f6b3f9281f00d9712b28','c79ec8d027b40ae156dd119931e559dc9bacfff5150c8d814657220fdb7ada89'));

        //$products = $products->getProductsToAsin('B081SWMH22');
        $products_arr = $products->getProductsToAsin('B00DNPCZVG');
        $products_arr = $products_arr['products'][0];
        $prices = $products_arr['prices'];
        $price = [];
        foreach ($prices as $k=>$v){
            if(empty($v['store']) || $v['store'] <= 0){
                continue;
            }
            $v['store'] = 84.99;
            $v['regular'] = 125.99;
            $price[$k]=$v;
        }
        $products = $products->updateProducts($products_arr['id'],[
            'quantity'=>100,
            'prices'=>$price
        ]);


        //['quantity'=>1,''=>]
        try {
            //$products = (new SelloService('54cada2cadfa86d01970d17239bfa850', '9153e8e9637a1916f02659d76b16fa609fa092a4ffac56f4188ba779f328993c'))->getProducts('57635312');
            var_dump($products);
        }catch (\Exception $e){
            echo $e->getMessage();
        }
        exit;
        $asin = 'B08JY8Q2ZT';
        $result = FGrabService::factory(Base::PLATFORM_AMAZON_DE)->checkStock($asin);
        var_export($result);
        var_dump(CommonUtil::dealAmazonPrice($result['price']));
        exit();

        $arr = explode(',','1,099.38');
        $lists = [];
        foreach ($arr as $v){
            $lists = array_merge($lists,explode('.',$v));
        }
        $cut = count($lists);
        $price = '';
        $i = 1;
        foreach ($lists as $v){
            if($i == $cut){
                $price .= '.'.$v;
            } else {
                $price .= $v;
            }
            $i ++;
        }
        var_dump((double)$price);

        var_dump(CommonUtil::dealAmazonPrice('199,38'));
        exit;
        $url = 'https://www.wish.com/search/Portable%20Speakers';
            $result = FGrabService::factory(Base::PLATFORM_WISH)->lists($url, 2);
       var_dump($result);
        //$result = FGrabService::factory(Base::PLATFORM_WISH)->lists($url,2);
        //var_dump($result);
        exit;
        $url = 'https://www.amazon.co.uk/Samsung-Galaxy-Wireless-Earphones-Version-Mystic-Black/dp/B08C5HYHYB/ref=sr_1_17?dchild=1&keywords=Echo+Buds&sr=8-17';
        //$url = 'https://www.wish.com/feed/tag_53dc186421a86318bdc87f22/product/5e93fbec4496382b805354e2?source=tag_53dc186421a86318bdc87f22&position=44&share=web';
        //$url = 'https://www.wish.com/product/fashion-motorcycle-anti-fall-leg-bag-waterproof-motorcycle-purse-outdoor-leisure-purse-motorcycle-bike-bag-5e62071b9e84951613489a3f';
        $other_params = [
            'headers' => [
                //'Accept-Language' => 'en-US,en',
                //'Cookie' => 'bsid=bd3cbb0f4bc31b9558b8bfca890de4b1; expires=Tue, 27 Apr 2021 10:53:44 GMT; httponly; Path=/'
            ]
        ];
        $result = FGrabService::factory(Base::PLATFORM_AMAZON_CO_UK)->getGoods($url);
        var_dump($result);
        exit();
        $url = 'https://www.amazon.de/DMC-TZ1EG-S-DMC-TZ1EF-S-DMC-TZ2EF-S-DMC-TZ3EG-K-DMC-TZ3EF-S/dp/B00CTMIRCU/ref=sr_1_3696?__mk_de_DE=%C3%85M%C3%85%C5%BD%C3%95%C3%91&dchild=1&keywords=Kamera-Akkus&qid=1620111922&refinements=p_72%3A419117031%2Cp_36%3A1000-20000&rnid=389294011&s=ce-de&sr=1-3696';
        $url = urldecode($url);
        $asin = '';
        if (preg_match('@/dp/(.+)/@', $url, $arr)) {
            $asin = $arr[1];
        }
        var_dump($arr);
        exit;
        $u  = 'Maitresse by Agent Provocateur Eau de Parfum Purse Spray 25ml';
        $u = CommonUtil::usubstr($u,33);
        var_dump($u);
        exit();
        $url = 'https://www.wish.com/merchant/5df34685b89cf4116c37c89f/product/5e62071b9e84951613489a3f?source=merchant&position=1&share=web';
        //$url = 'https://www.wish.com/feed/tag_53dc186421a86318bdc87f22/product/5e93fbec4496382b805354e2?source=tag_53dc186421a86318bdc87f22&position=44&share=web';
        //$url = 'https://www.wish.com/product/fashion-motorcycle-anti-fall-leg-bag-waterproof-motorcycle-purse-outdoor-leisure-purse-motorcycle-bike-bag-5e62071b9e84951613489a3f';
        $other_params = [
            'headers' => [
                //'Accept-Language' => 'en-US,en',
                //'Cookie' => 'bsid=bd3cbb0f4bc31b9558b8bfca890de4b1; expires=Tue, 27 Apr 2021 10:53:44 GMT; httponly; Path=/'
            ]
        ];
        $result = FGrabService::factory(Base::PLATFORM_WISH)->getGoods($url);
        var_dump($result);
        exit();
        $ql = (new GrabService(1))->useProxy(true)->getHtml($url,$other_params);
        //$evaluate = $ql->find('.ehaKtq')->html();
        echo ($ql->getHtml()) ;

        exit();
        /*$asin = 'https://www.amazon.co.uk/Fleur-De-P%C3%AAcher-EDP-100-ml/dp/B000C1UBPG/ref=sr_1_1?dchild=1&keywords=B000C1UBPG&qid=1619170378&sr=8-1';
        $result = FGrabService::factory(Base::PLATFORM_AMAZON_CO_UK)->getDetail($asin);
        var_export($result);
        exit();*/


        $url = 'http://yadmin.sanlinmail.site/1.php';
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL,$url);
        //curl_setopt($curl, CURLOPT_PROXY, "209.205.216.2212");
        //curl_setopt($curl, CURLOPT_PROXYPORT, "10002");
        //curl_setopt($curl, CURLOPT_PROXYUSERPWD, "astoip3305-country-GB:0bc529-6e136d-1dd494-70d7f3-68442");
        $result=curl_exec($curl);
        var_dump($result);
        var_dump(curl_error($curl));
        exit();

        /*$login = 'wokexun201901@163.com';
       $password = '2gs8YgtS';
       $url = 'https://www.fruugo.com/orders/download/v2?from='.$this->toDate($from).'&to='.$this->toDate($to);
       $ch = curl_init();
       curl_setopt($ch, CURLOPT_URL,$url);
       curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type:text/xml; charset=utf-8"));
       curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
       curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
       curl_setopt($ch, CURLOPT_USERPWD, "$login:$password");
       $result = curl_exec($ch);
       curl_close($ch);
       echo ($result);*/


        //$lists = Category::find()->select('name,id,parent_id,id as value')->asArray()->all();
        //var_dump(Category::tree($lists,0,5));

        //$ss =  exec('python /data/wwwroot/yshop/useragent.py');

        //var_dump($this->EAN13('801439256902'));
        //var_dump(CommonUtil::GenerateEan13());
//exit();
         /*$service = (new RealService('b963adce2b94b23b6bcc8019e4c81623','981b6f53567d15bb6e8c36fb694766b93e9ec33c37a4fce146a22b6e7a7aae62'));
         $i = 0;
         while (true) {
             $ss = $service->getUnitsSeller(null, null, 'item', 50, $i);
             $total = $ss->total();
             if($i > $total){
                 break;
             }
             foreach ($ss as $s) {
                 $i ++;
                 $info = ($s->toArray());
                 echo $info['id_offer'] . ',' . current($info['item']['eans']) . ',' .$info['amount']. ',' . $info['listing_price'] . "\n";
             }
         }
         exit();*/
         $url = 'https://www.amazon.de/gp/slredirect/picassoRedirect.html/ref=pa_sp_btf_lighting_sr_pg74_1?ie=UTF8&adId=A062779321AWFD9QOIVLC&url=%2FLED-Profil-Aluminium-Abdeckung-Profilhalterung%2Fdp%2FB06XNTY2PY%2Fref%3Dsr_1_1782_sspa%3F__mk_de_DE%3D%25C3%2585M%25C3%2585%25C5%25BD%25C3%2595%25C3%2591%26dchild%3D1%26keywords%3DLED%2BStreifen%26qid%3D1615775290%26refinements%3Dp_72%253A225442031%252Cp_36%253A1000-10000%26rnid%3D225422031%26s%3Dlighting%26sr%3D1-1782-spons%26psc%3D1&qualifier=1615775290&id=652357509725392&widgetName=sp_btf';
         $result = FGrabService::factory(Base::PLATFORM_AMAZON_DE)->getDetail($url);
         var_export($result);
         exit;

        //$url = 'https://www.amazon.co.uk/Soundcore-Bluetooth-Earphones-Reduction-Personalized-Black/dp/B07ZHDYH6P/ref=sr_1_18?dchild=1&keywords=Echo+Buds&qid=1602907787&sr=8-18';
        /*$url = 'https://www.aliexpress.com/item/1005001895353708.html?spm=a2g0o.productlist.0.0.6dfb8a037iBFdy&algo_pvid=c4b28afa-6bdd-4638-bd07-b87dceceff73&algo_expid=c4b28afa-6bdd-4638-bd07-b87dceceff73-1&btsid=0b86d81616104655994764757e52cc&ws_ab_test=searchweb0_0,searchweb201602_,searchweb201603_';
        $url = 'https://www.aliexpress.com/item/1005001280693920.html?spm=a2g0o.productlist.0.0.6dfb8a037iBFdy&algo_pvid=c4b28afa-6bdd-4638-bd07-b87dceceff73&algo_expid=c4b28afa-6bdd-4638-bd07-b87dceceff73-3&btsid=0b86d81616104655994764757e52cc&ws_ab_test=searchweb0_0,searchweb201602_,searchweb201603_';
        //$de = FGrabService::factory($url)->getDetail($url,false);
        //$url = 'http://baidu.com';
        //$ql = GrabService::getHtml($url,false);
        //var_dump($ql->getHtml());

        //GrabService::getCurl(1,1);

        //echo GrabService::getProxy();
        $result = FGrabService::factory(Base::PLATFORM_ALIEXPRESS)->getGoods($url);
        var_dump($result);*/
    }

    /**
     * 重新采集商品
     */
    public function actionReAliGoods()
    {
        $goods = Goods::find()->where(['source_method'=>1])->limit(1000)->orderBy('id asc')->all();
        foreach ($goods as $v) {
            $url = $v['source_platform_url'];
            $source = FGrabService::getSource($url);
            if (empty($source) || $source != Base::PLATFORM_ALIEXPRESS) {
                throw new \Exception('暂不支持该采集');
            }
            $grab_data = FGrabService::factory($source)->getGoods($url);

            $v['goods_content'] = $grab_data['goods_content'];
            $v->save();
            echo $v['id'] ."\n";
        }
    }



}