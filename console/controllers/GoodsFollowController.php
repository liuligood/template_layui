<?php
namespace console\controllers;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\goods\GoodsChild;
use common\models\goods_shop\GoodsShopFollowSale;
use common\models\goods_shop\GoodsShopFollowSaleLog;
use common\models\GoodsEvent;
use common\models\GoodsShop;
use common\models\GoodsSource;
use common\services\api\GoodsEventService;
use common\services\FGrabService;
use common\services\goods\GoodsFollowService;
use common\services\goods\GoodsShopService;
use common\services\sys\ExchangeRateService;
use yii\console\Controller;

class GoodsFollowController extends Controller
{

    /**
     * 报价
     */
    public function actionListings($platform = null,$limit = 1,$goods_shop_id = null)
    {
        $where = [];
        $where['status'] = GoodsShopFollowSale::STATUS_VALID;
        $where['type'] = [GoodsShopFollowSale::TYPE_DEFAULT, GoodsShopFollowSale::TYPE_FOLLOW, GoodsShopFollowSale::TYPE_NON_CHINA, GoodsShopFollowSale::TYPE_LOW_PRICE_FOLLOW, GoodsShopFollowSale::TYPE_UNFOLLOW_FOLLOW];
        if (!empty($goods_shop_id)) {
            $where['goods_shop_id'] = $goods_shop_id;
        }
        if (!empty($platform)) {
            $where['platform_type'] = explode(',', $platform);
        }
        $goods_lists = GoodsShopFollowSale::find()->where($where);
        if (empty($goods_shop_id)) {
            $goods_lists = $goods_lists->andWhere(['<', 'plan_time', time()]);
        }
        $goods_lists = $goods_lists->orderBy('plan_time asc')->offset(50 * ($limit - 1))->limit(50)->all();
        if (empty($goods_lists)) {
            sleep(120);
        }
        $log = false;
        if(!empty($goods_shop_id)){
            $log = true;
        }
        foreach ($goods_lists as $v) {
            echo date('Y-m-d H:i:s') . ' ' . $v['platform_type'] . "," . $v['shop_id'] . "," . $v['cgoods_no'] . ',' . $v['goods_shop_id'] . "," . $v['type'] . "\n";
            /*if($v['platform_type'] != Base::PLATFORM_HEPSIGLOBAL) {
                continue;
            }*/
            $shop_name = GoodsFollowService::getShopName($v['shop_id']);
            $listings = FGrabService::factory($v['platform_type'])->getFollowListings($v['goods_url'], $v['goods_shop_id']);
            if($log) {
                echo 'listings:' . "\n";
                var_dump($listings);
            }
            if($listings == -2) {//-2禁用
                $v['status'] = GoodsShopFollowSale::STATUS_INVALID;
                $v->save();
                echo date('Y-m-d H:i:s') . ' end 禁用' . $v['platform_type'] . "," . $v['shop_id'] . "," . $v['cgoods_no'] . ',' . $v['goods_shop_id'] . "," . $v['type'] . "\n";
                continue;
            }
            if($listings == -3) {//-3找不到
                $v['type'] = GoodsShopFollowSale::TYPE_NOT_FIND;
                $v->save();
                echo date('Y-m-d H:i:s') . ' end ' . $v['platform_type'] . "," . $v['shop_id'] . "," . $v['cgoods_no'] . ',' . $v['goods_shop_id'] . "," . $v['type'] . "\n";
                continue;
            }
            if (!empty($listings['type'])) {
                if ($listings['type'] == 'change_url') {
                    if (empty($listings['url'])) {
                        $v['type'] = GoodsShopFollowSale::TYPE_NOT_FIND;
                    } else {
                        $v['goods_url'] = $listings['url'];
                    }
                    $v->save();
                    echo date('Y-m-d H:i:s') . ' ' . $v['goods_shop_id'] . "," . $listings['url'] . "\n";
                } else {
                    $plan_time = $this->planTime($v);

                    $listings_list = [];
                    if(!empty($listings['listings'])) {
                        foreach ($listings['listings'] as $listings_v) {
                            if (!empty($shop_name['shop_ids']) && in_array($listings_v['shopId'], $shop_name['shop_ids'])) {
                                continue;
                            }
                            $listings_list[] = $listings_v;
                        }
                    }
                    $listings['listings'] = $listings_list;

                    if (empty($listings['listings']) || count($listings['listings']) == 1) {
                        if($log) {
                            echo '没跟卖' . "\n";
                        }
                        $h_goods_shop_follow_sale_log = GoodsShopFollowSaleLog::find()->where(['goods_shop_id' => $v['goods_shop_id']])->orderBy('add_time desc')->limit(1)->one();
                        if (!empty($h_goods_shop_follow_sale_log)) {
                            $goods_shop = GoodsShop::find()->where(['id' => $v['goods_shop_id']])->one();
                            $follow_price = $goods_shop['fixed_price'] > 0 ? $goods_shop['fixed_price'] : $goods_shop['original_price'];

                            //不允许改价超过1.6倍
                            if ($follow_price < $goods_shop['price'] && $follow_price < $goods_shop['price'] / 1.6) {
                                $follow_price = $goods_shop['price'] / 1.6;
                                $plan_time = 2;
                            }

                            if ($follow_price > $goods_shop['price'] && $follow_price > $goods_shop['price'] * 1.6) {
                                $follow_price = $goods_shop['price'] * 1.6;
                                $plan_time = 2;
                            }

                            $follow_price = number_format($follow_price, 2, '.', '');
                            if (!CommonUtil::compareFloat($goods_shop['price'], $follow_price) && $follow_price > 0) {
                                $plan_time = min($plan_time, 3);
                            }
                            $v['price'] = $follow_price;
                            $v['plan_time'] = time() + $plan_time * 60 * 60;
                            $v['type'] = GoodsShopFollowSale::TYPE_UNFOLLOW_FOLLOW;
                            $v->save();
                            (new GoodsShopService())->updateFollowPrice($v['goods_shop_id']);
                            if($log) {
                                echo '没跟卖恢复原价' .$follow_price."\n";
                            }
                            continue;
                        }
                        $type = GoodsShopFollowSale::TYPE_UNFOLLOW;
                        if ($type != $v['type']) {
                            $v['plan_time'] = time() + $plan_time * 60 * 60;
                            $v['type'] = $type;
                            $v->save();
                            if($log) {
                                echo '没跟卖首次执行' ."\n";
                            }
                        }
                    } else {
                        $own_price = 0;
                        $type = GoodsShopFollowSale::TYPE_FOLLOW;
                        $min_listings = [];
                        $min_price = null;
                        $two_min_price = null;
                        foreach ($listings['listings'] as $listings_v) {
                            if (!$listings_v['isChina']) {
                                $type = GoodsShopFollowSale::TYPE_NON_CHINA;
                            }
                            if (is_null($min_price)) {
                                $min_listings = $listings_v;
                                $min_price = $listings_v['price'];
                            }

                            if ($min_price > $listings_v['price']) {
                                $min_listings = $listings_v;
                                $min_price = $listings_v['price'];
                            }

                            $own_shop = false;
                            if (!empty($shop_name['shop_id'])) {
                                if ($listings_v['shopId'] == $shop_name['shop_id']) {
                                    $own_shop = true;
                                }
                            } else {
                                if ($listings_v['shopName'] == $shop_name['shop_name']) {
                                    $own_shop = true;
                                }
                            }

                            if ($own_shop) {
                                $own_price = $listings_v['price'];
                            } else {
                                if (is_null($two_min_price)) {
                                    $two_min_price = $listings_v['price'];
                                } else {
                                    $two_min_price = min($two_min_price, $listings_v['price']);
                                }
                            }
                        }
                        $v['currency'] = $listings['currency'];
                        $v['number'] = count($listings['listings']) - 1;
                        $old_min_price = $v['min_price'];
                        $v['min_price'] = $min_listings['price'];
                        $v['own_price'] = $own_price;
                        $v['is_min_price'] = 0;
                        if (CommonUtil::compareFloat($own_price, $min_listings['price'])) {
                            $v['is_min_price'] = 1;
                        }

                        //被跟卖没出售
                        if ($own_price <= 0) {
                            $v['type'] = GoodsShopFollowSale::TYPE_FOLLOW_OFF;
                            $v['plan_time'] = time() + $plan_time * 60 * 60;
                            $v->save();
                            if($log) {
                                echo '被跟卖未出售' ."\n";
                            }
                            continue;
                        }

                        //非中国卖家跟卖
                        /*if ($type == GoodsShopFollowSale::TYPE_NON_CHINA) {
                            if ($type != $v['type']) {
                                $v['type'] = $type;
                                $v->save();
                            }
                            continue;
                        }*/
                        $v['type'] = $type;

                        $goods_shop = GoodsShop::find()->where(['id' => $v['goods_shop_id']])->one();
                        $goods = GoodsChild::find()->where(['cgoods_no' => $goods_shop['cgoods_no']])->one();
                        $weight = GoodsShopService::getGoodsWeight($goods, $goods_shop);
                        $min_cost_arr = GoodsFollowService::getMinCostPrice($goods, $goods_shop);
                        $min_adjustable_price = $min_cost_arr[0];//最低值
                        $warning_price = $min_cost_arr[1];//警戒值
                        if ($v['sale_min_price'] > 0) {
                            $min_adjustable_price = $v['sale_min_price'];
                        }

                        if($log) {
                            echo '系统最低价:'.$min_adjustable_price .' 系统警戒值:'. $warning_price."\n";
                        }

                        $h_goods_shop_follow_sale_log = GoodsShopFollowSaleLog::find()->where(['goods_shop_id' => $v['goods_shop_id']])->orderBy('add_time desc')->limit(1)->one();
                        //上次已经改过
                        if ($v['adjustment_times'] > 0 && CommonUtil::compareFloat($old_min_price, $v['min_price']) && $own_price > $min_adjustable_price) {
                            if (!empty($h_goods_shop_follow_sale_log) && CommonUtil::compareFloat($h_goods_shop_follow_sale_log['show_own_price'], $own_price) && $h_goods_shop_follow_sale_log['add_time'] + 24 * 60 * 60 > time()) {
                                $plan_time = $this->planTime($v);
                                $v['plan_time'] = time() + $plan_time * 60 * 60;
                                $v->save();
                                if($log) {
                                    echo '上次已执行' ."\n";
                                }
                                continue;
                            }
                        }

                        $follow_change = false;
                        //当前我们售卖的是最低价
                        if ($v['platform_type'] == Base::PLATFORM_OZON) {
                            $exchange_rate = ExchangeRateService::getRealConversion('USD', 'RUB') + 0.2;
                        }
                        if($log) {
                            echo '当前店铺价格：'.$own_price .' 当前最低价'. $min_listings['price']."\n";
                        }
                        if (CommonUtil::compareFloat($own_price, $min_listings['price'])) {
                            if ($v['platform_type'] == Base::PLATFORM_HEPSIGLOBAL) {
                                if ($two_min_price - $own_price < 20) {
                                    $plan_time = $this->planTime($v);
                                    $v['plan_time'] = time() + $plan_time * 60 * 60;
                                    $v->save();
                                    if($log) {
                                        echo '当前最低价不需要调整'."\n";
                                    }
                                    continue;
                                }

                                //我们的价格和第二价格相差20以上 调价
                                $adjustable_price = ($two_min_price - $own_price - 10) / 19 / 2.0053;
                                $follow_price = $goods_shop['price'] + $adjustable_price;
                                //$min_price_usd = ($two_min_price - 10) / 19;
                                $show_follow_price = $two_min_price - 10;
                                $plan_time = 3;
                            } else if ($v['platform_type'] == Base::PLATFORM_OZON) {
                                if ($two_min_price - $own_price < 30 && $two_min_price > $warning_price * $exchange_rate) {
                                    $plan_time = $this->planTime($v);
                                    $v['plan_time'] = time() + $plan_time * 60 * 60;
                                    $v->save();
                                    if($log) {
                                        echo '当前最低价不需要调整'."\n";
                                    }
                                    continue;
                                }
                                $adjustable_price = ($two_min_price - $own_price - 15) / $exchange_rate;
                                $follow_price = $goods_shop['price'] + $adjustable_price;
                                $show_follow_price = $two_min_price - 15;
                            } else {
                                if ($two_min_price - $own_price < 0.2 && $two_min_price > $warning_price) {
                                    $plan_time = $this->planTime($v);
                                    $v['plan_time'] = time() + $plan_time * 60 * 60;
                                    $v->save();
                                    if($log) {
                                        echo '当前最低价不需要调整'."\n";
                                    }
                                    continue;
                                }
                                $follow_price = $two_min_price - 0.01;
                            }
                        } else {
                            if ($v['platform_type'] == Base::PLATFORM_HEPSIGLOBAL) {
                                $fine_tuning = false;
                                if ($own_price - $min_listings['price'] > 300) {//300以上使用公式
                                    $show_follow_price = $min_listings['price'] - 10 - $min_listings['price'] * 0.004;
                                    //$min_price_usd = $show_follow_price / 19;
                                    $follow_price = ($show_follow_price / 19 - 1.603 - 11 * $weight - 0.3) / 2.0053;
                                    $follow_price = number_format($follow_price, 2, '.', '');
                                    if (CommonUtil::compareFloat($follow_price, $goods_shop['price']) || $follow_price > $goods_shop['original_price']) {
                                        $fine_tuning = true;
                                    }
                                } else {//100以内微调
                                    $fine_tuning = true;
                                }
                                if ($fine_tuning) {
                                    $adjustable_price = ($own_price - $min_listings['price'] + 10) / 19 / 2.0053;
                                    $follow_price = $goods_shop['price'] - $adjustable_price;
                                    //$min_price_usd = ($min_listings['price'] - 10) / 19;
                                    $show_follow_price = ($min_listings['price'] - 10);
                                }
                            } else if ($v['platform_type'] == Base::PLATFORM_OZON) {
                                $follow_price = ($two_min_price - 15) / $exchange_rate;
                                $show_follow_price = $two_min_price - 15;
                            } else {
                                $follow_price = $two_min_price - 0.01;
                            }
                        }

                        if($log) {
                            echo '跟卖金额：' . $follow_price . "\n";
                        }

                        if ($follow_price >= $min_adjustable_price && $follow_price < $warning_price && $min_adjustable_price != $warning_price) {//跟卖价小于警戒值
                            if($log) {
                                echo '跟卖小于警戒值 调整' . "\n";
                            }
                            $v['type'] = GoodsShopFollowSale::TYPE_LOW_PRICE_FOLLOW;
                            $plan_time = 2;
                            if ($v['platform_type'] == Base::PLATFORM_OZON) {
                                if ($own_price > $min_listings['price'] && $own_price - $min_listings['price'] < 40) {
                                    $v['plan_time'] = time() + $plan_time * 60 * 60;
                                    $v->save();
                                    if($log) {
                                        echo '当前值比最低价大 不调整' . "\n";
                                    }
                                    continue;
                                }
                                $adjustable_price = ($two_min_price - $own_price + 20) / $exchange_rate;
                                $follow_price = $goods_shop['price'] + $adjustable_price;
                                $show_follow_price = $two_min_price + 20;
                            } else { //rdc达到最低价后比对方要高一点
                                if ($own_price > $min_listings['price'] && $own_price - $min_listings['price'] < 0.2) {
                                    $v['plan_time'] = time() + $plan_time * 60 * 60;
                                    $v->save();
                                    continue;
                                }
                                $follow_price = $two_min_price + rand(1, 19) / 100;
                            }

                            if($log) {
                                echo '跟卖金额：' . $follow_price . "\n";
                            }
                        }

                        if ($follow_price < $min_adjustable_price) {//跟卖价小于最低价
                            if($log) {
                                echo '跟卖小于最低价 调整' . "\n";
                                echo '跟卖金额：' . $follow_price . "\n";
                            }
                            $v['type'] = GoodsShopFollowSale::TYPE_LOW_PRICE_FOLLOW;
                            $follow_price = $min_adjustable_price;
                            $follow_change = true;
                        }

                        $original_price = $goods_shop['fixed_price'] > 0 ? $goods_shop['fixed_price'] : $goods_shop['original_price'];
                        if ($follow_price > $original_price) {
                            $follow_price = $original_price;
                            $follow_change = true;
                            if($log) {
                                echo '跟卖大于系统价 调整' . "\n";
                                echo '跟卖金额：' . $follow_price . "\n";
                            }
                        }

                        //不允许改价超过1.6倍
                        if ($follow_price < $goods_shop['price'] && $follow_price < $goods_shop['price'] / 1.6) {
                            $follow_price = $goods_shop['price'] / 1.6;
                            $plan_time = 2;
                            $follow_change = true;
                            if($log) {
                                echo '跟卖改价小于1.6倍 调整' . "\n";
                                echo '跟卖金额：' . $follow_price . "\n";
                            }
                        }

                        if ($follow_price > $goods_shop['price'] && $follow_price > $goods_shop['price'] * 1.6) {
                            $follow_price = $goods_shop['price'] * 1.6;
                            $plan_time = 2;
                            $follow_change = true;
                            if($log) {
                                echo '跟卖改价超过1.6倍 调整' . "\n";
                                echo '跟卖金额：' . $follow_price . "\n";
                            }
                        }

                        if ($follow_change) {
                            if ($v['platform_type'] == Base::PLATFORM_HEPSIGLOBAL) {
                                $show_follow_price = ($follow_price * 2.0053 + 11 * $weight + 0.3 + 1.603) * 19;
                            }
                            if ($v['platform_type'] == Base::PLATFORM_OZON) {
                                $show_follow_price = $follow_price * $exchange_rate;
                            }
                        }

                        $follow_price = number_format($follow_price, 2, '.', '');
                        if (CommonUtil::compareFloat($goods_shop['price'], $follow_price)) {
                            $plan_time = $this->planTime($v);
                            $v['plan_time'] = time() + $plan_time * 60 * 60;
                            $v->save();
                            if (GoodsEventService::hasEvent(GoodsEvent::EVENT_TYPE_UPDATE_PRICE, $goods_shop['platform_type'])) {
                                GoodsEventService::addEvent($goods_shop, GoodsEvent::EVENT_TYPE_UPDATE_PRICE, 0);
                            }
                            if($log) {
                                echo '跟卖价和系统价一致不调整' . "\n";
                            }
                            continue;
                        }

                        $goods_shop_follow_sale_log = new GoodsShopFollowSaleLog();
                        $goods_shop_follow_sale_log->goods_shop_id = $v['goods_shop_id'];
                        $goods_shop_follow_sale_log->shop_id = $v['shop_id'];
                        $goods_shop_follow_sale_log->platform_type = $v['platform_type'];
                        $goods_shop_follow_sale_log->cgoods_no = $v['cgoods_no'];
                        $goods_shop_follow_sale_log->show_cur_price = $min_listings['price'];
                        $goods_shop_follow_sale_log->show_own_price = $own_price;
                        $goods_shop_follow_sale_log->show_currency = $listings['currency'];
                        $goods_shop_follow_sale_log->cur_price = $goods_shop['price'];
                        $goods_shop_follow_sale_log->currency = in_array($v['platform_type'], [Base::PLATFORM_HEPSIGLOBAL, Base::PLATFORM_OZON]) ? 'USD' : $listings['currency'];
                        $goods_shop_follow_sale_log->weight = $weight;
                        $goods_shop_follow_sale_log->follow_price = $follow_price;
                        $goods_shop_follow_sale_log->show_follow_price = in_array($v['platform_type'], [Base::PLATFORM_HEPSIGLOBAL, Base::PLATFORM_OZON]) ? $show_follow_price : $follow_price;
                        $goods_shop_follow_sale_log->save();

                        $v['price'] = $follow_price;
                        $v['adjustment_times'] = $v['adjustment_times'] + 1;
                        $v['last_time'] = time();
                        $plan_time = min($plan_time, 3);
                        $v['plan_time'] = time() + $plan_time * 60 * 60;
                        $v->save();
                        (new GoodsShopService())->updateFollowPrice($v['goods_shop_id']);
                        if($log) {
                            echo '调整跟卖价由'.$goods_shop['price'].'调整为'.$follow_price . "\n";
                        }
                    }
                }
            } else {
                $v['plan_time'] = time() + 60 * 60;
                $v->save();
                if($log) {
                    echo '执行错误'. "\n";
                }
            }
            echo date('Y-m-d H:i:s') . ' end ' . $v['platform_type'] . "," . $v['shop_id'] . "," . $v['cgoods_no'] . ',' . $v['goods_shop_id'] . "," . $v['type'] . "\n";
        }
        echo date('Y-m-d H:i:s') . ' 执行完毕' . "\n";
    }

    /**
     * 计划执行时间
     * @param $follow
     * @return float|int
     */
    public function planTime($follow)
    {
        $adjustment_times = $follow['adjustment_times'];
        $add_time = $follow['add_time'];
        $last_time = $follow['last_time'];

        if ($adjustment_times == 0) {
            return 5 * 24;
        }

        if($follow['type'] == GoodsShopFollowSale::TYPE_LOW_PRICE_FOLLOW) {
            return 12;
        }

        if ($last_time > 0 && $last_time < time() - 3 * 24 * 60 * 60) {
            return 4 * 24;
        }

        if ($last_time > 0 && $last_time < time() - 2 * 24 * 60 * 60) {
            return 2 * 24;
        }

        return 12;
    }

    /**
     * 添加跟卖
     * @param $shop_id
     * @return void
     */
    public function actionAddFollowSale($limit = 1,$platform_type = null,$shop_id = null)
    {
        $where = ['gs.platform_type' => GoodsFollowService::$follow_platform];
        if(!is_null($shop_id)) {
            $where['gs.shop_id'] = $shop_id;
            $where['gs.follow_claim'] = 1;
        }
        if(!is_null($platform_type)) {
            $where['gs.platform_type'] = $platform_type;
            if ($platform_type == Base::PLATFORM_OZON) {
                $where['gs.status'] = GoodsShop::STATUS_SUCCESS;
            }
        }
        $goods_shop = GoodsShop::find()->alias('gs')
            ->select('gs.*')
            ->leftJoin(GoodsShopFollowSale::tableName() . ' f', 'gs.id = f.goods_shop_id')
            ->where($where)
            ->andWhere(['not in','gs.shop_id',GoodsFollowService::$no_follow_shop])
            //->andWhere(['or',['=','f.type',GoodsShopFollowSale::TYPE_FAIL],['is','f.type',null]])
            ->andWhere(['is','f.type',null])
            ->offset(100 * ($limit - 1))->limit(100)->all();
        if (empty($goods_shop)) {
            sleep(600);
        }
        foreach ($goods_shop as $v) {
            $goods_shop_sale = GoodsShopFollowSale::find()->where(['goods_shop_id' => $v['id']])->limit(1)->one();
            if (!empty($goods_shop_sale)) {
                if ($goods_shop_sale['type'] != GoodsShopFollowSale::TYPE_FAIL) {
                    continue;
                }
            }

            if (empty($goods_shop_sale)) {
                $goods_shop_sale = new GoodsShopFollowSale();
                $goods_shop_sale->goods_shop_id = $v['id'];
                $goods_shop_sale->platform_type = $v['platform_type'];
                $goods_shop_sale->cgoods_no = $v['cgoods_no'];
                $goods_shop_sale->shop_id = $v['shop_id'];
            }

            $is_follow = false;//跟卖认领
            if(in_array($v['platform_type'],[Base::PLATFORM_RDC,Base::PLATFORM_HEPSIGLOBAL]) && $v['follow_claim'] == 1) {
                $platform_url = GoodsSource::find()
                    ->where(['platform_type' => $v['platform_type'], 'goods_no' => $v['goods_no']])->select('platform_url')->scalar();
                if(!empty($platform_url)) {
                    $url = $platform_url;
                    $goods_shop_sale->type = GoodsShopFollowSale::TYPE_DEFAULT;
                    $url = preg_replace('/\?.*/', '', $url);
                    $goods_shop_sale->goods_url = $url;
                    $is_follow = true;
                }
            }

            if(!$is_follow) {
                $url = FGrabService::factory($v['platform_type'])->getFollowLists($v);
                if (empty($url)) {
                    $goods_shop_sale->type = GoodsShopFollowSale::TYPE_NOT_FIND;
                } else if ($url == -1) {
                    $goods_shop_sale->type = GoodsShopFollowSale::TYPE_FAIL;
                } else {
                    $goods_shop_sale->type = GoodsShopFollowSale::TYPE_DEFAULT;
                    $goods_shop_sale->goods_url = $url;
                }
            }
            $goods_shop_sale->status = GoodsShopFollowSale::STATUS_VALID;
            $goods_shop_sale->plan_time = 0;
            $result = $goods_shop_sale->save();
            echo date('Y-m-d H:i:s').' '.$v['cgoods_no'] .','.$result. ','.$url."\n";
            if(!$is_follow) {
                sleep(1);
            }
        }
        echo date('Y-m-d H:i:s').' 执行完毕'."\n";
    }

    /**
     * 修复跟卖价
     * @param $platform
     * @param $goods_shop_id
     * @return void
     */
    public function actionReFollowPrice($platform = null,$goods_shop_id = null)
    {
        $where = [];
        $where['status'] = GoodsShopFollowSale::STATUS_VALID;
        $where['type'] = [GoodsShopFollowSale::TYPE_FOLLOW, GoodsShopFollowSale::TYPE_NON_CHINA, GoodsShopFollowSale::TYPE_LOW_PRICE_FOLLOW, GoodsShopFollowSale::TYPE_UNFOLLOW_FOLLOW];
        if (!empty($goods_shop_id)) {
            $where['goods_shop_id'] = $goods_shop_id;
        }
        if (!empty($platform)) {
            $where['platform_type'] = explode(',', $platform);
        }
        $goods_lists = GoodsShopFollowSale::find()->where($where)->all();
        foreach ($goods_lists as $v) {
            $goods_shop = GoodsShop::find()->where(['id' => $v['goods_shop_id']])->one();
            if($goods_shop['fixed_price'] > 0){
                $v['price'] = $goods_shop['fixed_price'];
                $v->save();
                $goods_shop->fixed_price = 0;
                $goods_shop->save();
            }
            echo $v['goods_shop_id'].','.$v['shop_id'].','.$v['cgoods_no']."\n";
        }
    }

}