<?php

namespace backend\controllers;

use backend\models\search\BaseGoodsSearch;
use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\Category;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsLanguage;
use common\models\goods\GoodsLogisticsPrice;
use common\models\goods\GoodsOzon;
use common\models\goods\GoodsStock;
use common\models\goods\GoodsStockDetails;
use common\models\goods_shop\GoodsShopFollowSale;
use common\models\goods_shop\GoodsShopFollowSaleLog;
use common\models\goods_shop\GoodsShopOverseasWarehouse;
use common\models\goods_shop\GoodsShopPriceChangeLog;
use common\models\goods_shop\GoodsShopSalesTotal;
use common\models\GoodsEvent;
use common\models\GoodsShop;
use common\models\GoodsShopExpand;
use common\models\GoodsSource;
use common\models\Order;
use common\models\OrderGoods;
use common\models\platform\PlatformShopConfig;
use common\models\PlatformInformation;
use common\models\Shop;
use common\models\SupplierRelationship;
use common\models\sys\ShippingMethod;
use common\models\User;
use common\models\warehousing\BlContainer;
use common\models\warehousing\BlContainerGoods;
use common\models\warehousing\OverseasGoodsShipment;
use common\models\warehousing\Warehouse;
use common\services\api\GoodsEventService;
use common\services\goods\FGoodsService;
use common\services\goods\GoodsFollowService;
use common\services\goods\GoodsService;
use common\services\goods\GoodsShopService;
use common\services\goods\OverseasGoodsService;
use common\services\goods_price_trial\GoodsPriceTrialService;
use common\services\order\OrderService;
use common\services\ShopService;
use common\services\sys\CountryService;
use common\services\sys\ExchangeRateService;
use common\services\sys\ExportService;
use common\services\warehousing\WarehouseService;
use Yii;
use common\base\BaseController;
use yii\base\ViewNotFoundException;
use yii\data\Pagination;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use backend\models\AdminUser;
use common\models\BaseAR;
use common\services\sys\AccessService;

abstract class BaseGoodsController extends BaseController
{

    protected $render_view = '';

    protected $platform_type = '';

    protected $join = [];

    protected $has_country = false;

    protected $cache_count = true;

    protected $export_column = [];

    protected $max_num = 0;

    /**
     * @routeName 商品管理
     * @routeDescription 商品管理
     */
    public function actionIndex()
    {
        //$category_arr = Category::getCategoryOptCache(0);
        $category_arr = [];
        $country_arr = [];
        $shop_arr = [];
        if ($this->has_country) {
            $shop = Shop::find()->where(['platform_type' => $this->platform_type])->asArray()->all();
            foreach ($shop as $v) {
                if (empty($v['country_site'])) {
                    continue;
                }
                $country = explode(',', $v['country_site']);
                $country_arr = array_merge($country_arr, $country);
                $country_arr = array_unique($country_arr);
                $country_arr = CountryService::getSelectOption(['country_code' => $country_arr]);
            }
        }
        if ($this->platform_type == Base::PLATFORM_OZON) {
            $shop_arr = ShopService::getShopDropdown($this->platform_type);
        }
        $data = [
            'category_arr' => $category_arr,
            'country_arr' => $country_arr,
            'platform_type' => $this->platform_type,
            'shop_arr' => $shop_arr
        ];
        try {
            return $this->render($this->render_view . 'index', $data);
        } catch (ViewNotFoundException $e) {
            return $this->render('/goods/base/index', $data);
        }
    }

    public function query($type = 'select')
    {
        return $this->join_query('mg.*,mg.id as mg_id,g.category_id,g.sku_no,g.goods_img,g.electric,gs.country_code,gs.id,gs.shop_id,gs.original_price,gs.discount,gs.price,gs.admin_id,gs.add_time,mg.goods_content,mg.status,gs.status as gs_status,gs.ean,g.size,g.weight,g.real_weight,g.colour as gcolour,g.goods_type,gs.platform_sku_no,gs.platform_goods_opc,gs.cgoods_no,gs.keywords_index,gs.platform_goods_id,gs.follow_claim,gs.ad_status',$type);
    }

    /**
     * join查询
     * @param $type
     * @param $column
     * @return \yii\db\ActiveQuery
     */
    public function join_query($column,$type = 'select'){
        $query = GoodsShop::find()
            ->alias('gs')->select($column);
        if ($type != 'count' || in_array('mg', $this->join)) {
            $has_country = $this->model()->hasCountry();
            $query->leftJoin($this->model()->tableName() . ' mg', $has_country?'gs.goods_no= mg.goods_no and gs.country_code = mg.country_code':'gs.goods_no= mg.goods_no');
        }
        if ($type != 'count' || in_array('g', $this->join)) {
            $query->leftJoin(Goods::tableName() . ' g', 'gs.goods_no = g.goods_no');
        }
        return $query;
    }

    public function shop_follow_sale_query($type = 'select')
    {
        $column = 'mg.*,mg.id as mg_id,g.category_id,g.sku_no,g.goods_img,gs.id,gs.shop_id,gs.original_price,gs.discount,gs.price,gs.admin_id,gs.add_time,mg.goods_content,mg.status,gs.status as gs_status,gs.ean,g.size,g.weight,g.real_weight,g.colour as gcolour,gs.platform_sku_no,gs.platform_goods_opc,gs.cgoods_no,gs.keywords_index,gs.platform_goods_id,gsf.type,gsf.min_price,gsf.is_min_price,gsf.number,gsf.currency,gsf.currency,gsf.plan_time,gsf.goods_url,gsf.adjustment_times,gsf.update_time as follow_update_time,gsf.goods_shop_id,gsf.own_price,st.total_sales';
        $query = GoodsShopFollowSale::find()
            ->alias('gsf')->select($column);
        if ($type != 'count' || in_array('gs', $this->join)) {
            $query->leftJoin(GoodsShop::tableName() . ' gs', 'gs.id = gsf.goods_shop_id');
        }
        $query->leftJoin(GoodsShopSalesTotal::tableName() . ' st', 'gs.id = st.goods_shop_id');
        if ($type != 'count' || in_array('mg', $this->join)) {
            $query->leftJoin($this->model()->tableName() . ' mg', $this->has_country?'gs.goods_no= mg.goods_no and gs.country_code = mg.country_code':'gs.goods_no= mg.goods_no');
        }
        if ($type != 'count' || in_array('g', $this->join)) {
            $query->leftJoin(Goods::tableName() . ' g', 'gs.goods_no = g.goods_no');
        }
        return $query;
    }

    public function shop_follow_sale_log_query($type = 'select')
    {
        $column = 'gsfsl.show_cur_price,gsfsl.show_follow_price,gsfsl.show_currency,gsfsl.cur_price,gsfsl.follow_price,gsfsl.currency,gsfsl.weight,gsfsl.add_time,gsfsl.update_time,gsfsl.show_own_price';
        $query = GoodsShopFollowSaleLog::find()
            ->alias('gsfsl')->select($column);
        if ($type != 'count' || in_array('gsfsl', $this->join)) {
            $query->leftJoin(GoodsShopFollowSale::tableName() . ' gsf', 'gsf.goods_shop_id = gsfsl.goods_shop_id');
        }
        return $query;
    }

    /**
     * @routeName 商品列表
     * @routeDescription 商品列表
     */
    public function actionList()
    {
        Yii::$app->response->format=Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $ad_status = $req->get('ad_status');
        $searchModel=new BaseGoodsSearch();
        $where = $searchModel->search(Yii::$app->request->post(),$this->platform_type);
        if(!empty($ad_status)){
            $where['gs.ad_status'] = $ad_status;
        }
        $this->join = $where['_join'];
        unset($where['_join']);
        $data = $this->lists($where,'gs.id desc');

        $lists = [];
        $shop_map = \common\services\ShopService::getShopMap();
        $cgoods_nos = ArrayHelper::getColumn($data['list'],'cgoods_no');
        $goods_childs = GoodsChild::find()->where(['cgoods_no'=>$cgoods_nos])->indexBy('cgoods_no')->asArray()->all();
        foreach ($data['list'] as $v) {
            $goods_child = empty($goods_childs[$v['cgoods_no']])?[]:$goods_childs[$v['cgoods_no']];
            $info = $v;
            if(empty($goods_child['goods_img'])) {
                $image = json_decode($v['goods_img'], true);
                $image = empty($image) || !is_array($image) ? '' : current($image)['img'];
            } else {
                $image = $goods_child['goods_img'];
            }
            if(!empty($goods_child['sku_no'])) {
                $info['sku_no'] = $goods_child['sku_no'];
            }
            if(!empty($goods_child['colour'])) {
                $info['colour'] = $goods_child['colour'];
            }
            $info['shop_name'] = empty($v['shop_id']) ? '' : $shop_map[$v['shop_id']];
            $info['image'] = $image;
            $info['add_time'] = date('Y-m-d H:i', $v['add_time']);
            $info['update_time'] = date('Y-m-d H:i', $v['update_time']);
            $user = User::getInfo($info['admin_id']);
            $info['admin_name'] = empty($user['nickname']) ? '' : $user['nickname'];
            $info['category_name'] = Category::getCategoryName($v['category_id']);
            if(!empty($v['status'])){
                $info['status_desc'] = GoodsService::$platform_goods_status_map[$v['status']];
            }
            if($this->has_country){
                $info['country'] = CountryService::getName($v['country_code']);
            }
            $lists[] = $info;
        }

        $lists = $this->dealList($lists);
        return $this->FormatLayerTable(
            self::REQUEST_LAY_SUCCESS,'获取成功',
            $lists,$data['pages']->totalCount
        );
    }

    /**
     * 跟卖商品明细列表
     * @param $where
     * @param string $sort
     * @return array
     */
    protected function shop_follow_sale_log_lists($where, $sort = 'id DESC')
    {
        $page = Yii::$app->request->get('page');
        if(empty($page)) {
            $page = Yii::$app->request->post('page',1);
        }
        $pageSize = Yii::$app->request->get('limit');
        if(empty($pageSize)) {
            $pageSize = Yii::$app->request->post('limit',20);
        }
        $model = $this->model();
        if (!($model instanceof BaseAR)) {
            return [];
        }

        $query = $this->shop_follow_sale_log_query();
        $list = $model::getListByCond($where, $page, $pageSize, $sort,null,$query);
        if(count($list) < $pageSize && $page == 1) {
            $count = count($list);
        } else {
            if($this->cache_count) {
                $count = $model::getCacheCountByCond($where, $this->shop_follow_sale_log_query('count'), __CLASS__ . __FUNCTION__);
            } else {
                $count = $model::getCountByCond($where, $this->shop_follow_sale_log_query('count'));
            }
        }
        $pages = new Pagination(['totalCount' => $count, 'pageSize' => $pageSize]);
        $list = $this->formatLists($list);

        return [
            'list' => $list,
            'pages' => $pages,
        ];
    }

    /**
     * 跟卖商品列表
     * @param $where
     * @param string $sort
     * @return array
     */
    protected function shop_follow_sale_lists($where, $sort = 'id DESC')
    {
        $page = Yii::$app->request->get('page');
        if(empty($page)) {
            $page = Yii::$app->request->post('page',1);
        }
        $pageSize = Yii::$app->request->get('limit');
        if(empty($pageSize)) {
            $pageSize = Yii::$app->request->post('limit',20);
        }
        $model = $this->model();
        if (!($model instanceof BaseAR)) {
            return [];
        }

        $query = $this->shop_follow_sale_query();
        $list = $model::getListByCond($where, $page, $pageSize, $sort,null,$query);
        if(count($list) < $pageSize && $page == 1) {
            $count = count($list);
        } else {
            if($this->cache_count) {
                $count = $model::getCacheCountByCond($where, $this->shop_follow_sale_query('count'), __CLASS__ . __FUNCTION__);
            } else {
                $count = $model::getCountByCond($where, $this->shop_follow_sale_query('count'));
            }
        }
        $pages = new Pagination(['totalCount' => $count, 'pageSize' => $pageSize]);
        $list = $this->formatLists($list);

        return [
            'list' => $list,
            'pages' => $pages,
        ];
    }

    /**
     * 处理列表
     * @param $lists
     * @return mixed
     */
    public function dealList($lists)
    {
        return $lists;
    }

    /**
     * @routeName 更新商品
     * @routeDescription 更新商品信息
     * @throws
     */
    public function actionUpdate()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        if ($req->isPost) {
            $id = $req->post('id');
        }
        $shop_goods_model = GoodsShop::find()->where(['id'=>$id])->one();
        $main_goods_model = $this->findModel(['goods_no'=>$shop_goods_model['goods_no']]);
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            $data = $this->dataDeal($post);
            if (!$main_goods_model->load($data, '')) {
                return $this->FormatArray(self::REQUEST_FAIL, "参数异常", []);
            }
            if ($main_goods_model->save()) {
                //折扣发生变更
                /*$discount = empty($post['discount'])?10:$post['discount'];
                if (abs($shop_goods_model['discount'] - $discount) > 0.00001) {
                    (new GoodsShopService())->updateGoodsDiscount($id, $discount);
                }*/
                return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $main_goods_model->getErrorSummary(false)[0], []);
            }
        } else {
            $goods_model = Goods::findOne(['goods_no'=>$main_goods_model['goods_no']]);
            $data =['platform_type'=>$this->platform_type,'main_goods' => $main_goods_model,'goods'=>$goods_model,'shop_goods_model' => $shop_goods_model];
            try {
                return $this->render($this->render_view.'update',$data);
            } catch (ViewNotFoundException $e) {
                return $this->render('/goods/base/update',$data);
            }
        }
    }

    /**
     * @routeName 更新价格
     * @routeDescription 更新更新价格
     * @throws
     */
    public function actionUpdatePrice()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        if ($req->isPost) {
            $id = $req->post('id');
        }
        $shop_goods_model = GoodsShop::find()->where(['id' => $id])->one();
        $main_goods_model = $this->findModel(['goods_no' => $shop_goods_model['goods_no']]);
        $good = GoodsShopExpand::find()->where(['goods_shop_id' => $shop_goods_model['id']])->one();
        $shop_follow_sale = GoodsShopFollowSale::findOne(['goods_shop_id' => $id]);
        if($shop_follow_sale) {
            if (!in_array($shop_follow_sale['type'], [GoodsShopFollowSale::TYPE_FOLLOW, GoodsShopFollowSale::TYPE_NON_CHINA, GoodsShopFollowSale::TYPE_UNFOLLOW_FOLLOW, GoodsShopFollowSale::TYPE_LOW_PRICE_FOLLOW, GoodsShopFollowSale::TYPE_FOLLOW_OFF])
                && $shop_follow_sale['price'] <= 0) {
                $shop_follow_sale = null;
            }
        }
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            if (isset($post['lock_weight'])) {
                if (isset($post['weight']) && $post['weight'] <= 0) {
                    return $this->FormatArray(self::REQUEST_FAIL, "重量为0无法锁定", []);
                }
            }
            if (empty($post['discount']) || $post['discount'] < 5) {
                return $this->FormatArray(self::REQUEST_FAIL, "折扣有异常，请重新检查", []);
            }
            if (!empty($post['selling_price'])) {
                $main_goods_model->selling_price = $post['selling_price'];
                $main_goods_model->save();
            }
            //推荐库存
            if (!empty($post['warehouse_id']) && $good['real_logistics_id'] != $post['warehouse_id']) {
                $good->real_logistics_id = $post['warehouse_id'];
                $good->save();
                if (GoodsEventService::hasEvent(GoodsEvent::EVENT_TYPE_UPDATE_STOCK, $shop_goods_model['platform_type'])) {
                    GoodsEventService::addEvent($shop_goods_model, GoodsEvent::EVENT_TYPE_UPDATE_STOCK);
                }
            }
            if (isset($post['sale_min_price']) || isset($post['follow_price'])) {
                $goods_shop_follow_sale = GoodsShopFollowSale::findOne(['goods_shop_id' => $post['id']]);
                if (isset($post['sale_min_price'])) {
                    $goods_shop_follow_sale['sale_min_price'] = (float)$post['sale_min_price'];
                }
                if (isset($post['follow_price'])) {
                    $goods_shop_follow_sale['price'] = (float)$post['follow_price'];
                }
                $goods_shop_follow_sale['plan_time'] = time() + 30 * 60;
                $goods_shop_follow_sale->save();
            }
            if (isset($post['start_logistics_cost'])) {
                $goods_shop_overseas_warehouse = GoodsShopOverseasWarehouse::find()->where(['goods_shop_id' => $post['id']])->one();
                $goods_shop_overseas_warehouse->start_logistics_cost = $post['start_logistics_cost'];
                $goods_shop_overseas_warehouse->end_logistics_cost = $post['end_logistics_cost'];
                $goods_shop_overseas_warehouse->save();
            }
            if (isset($post['weight'])) {
                $where = [];
                $where['goods_shop_id'] = $post['id'];
                $goods_shop_expand = GoodsShopExpand::find()->where($where)->one();
                if (!empty($goods_shop_expand) || $post['weight'] > 0) {
                    if (empty($goods_shop_expand)) {
                        $goods_shop_expand = new GoodsShopExpand();
                        $goods_shop_expand['goods_shop_id'] = $post['id'];
                        $goods_shop_expand['shop_id'] = $shop_goods_model->shop_id;
                        $goods_shop_expand['cgoods_no'] = $shop_goods_model->cgoods_no;
                        $goods_shop_expand['platform_type'] = $shop_goods_model->platform_type;
                    }
                    $goods_shop_expand['weight_g'] = intval($post['weight'] * 1000);
                    $goods_shop_expand['lock_weight'] = isset($post['lock_weight']) ? 1 : 0;
                    $goods_shop_expand->save();
                    if ($shop_goods_model['platform_type'] == Base::PLATFORM_HEPSIGLOBAL) {
                        if (GoodsEventService::hasEvent(GoodsEvent::EVENT_TYPE_UPDATE_GOODS, $shop_goods_model['platform_type'])) {
                            GoodsEventService::addEvent($shop_goods_model, GoodsEvent::EVENT_TYPE_UPDATE_GOODS);
                        }
                    }
                }
            }

            //折扣发生变更
            $discount = empty($post['discount']) ? 10 : $post['discount'];
            $fixed_price = empty($post['fixed_price']) ? 0 : $post['fixed_price'];
            $result = (new GoodsShopService())->updateGoodsDiscount($id, $discount, $fixed_price);
            if ($result) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $main_goods_model->getErrorSummary(false)[0], []);
            }
        } else {
            $shop_model = Shop::find()->where(['id' => $shop_goods_model['shop_id']])->one();
            $base_currency = $shop_model['currency'];
            if(empty($base_currency) && !empty($shop_goods_model['country_code'])) {
                $base_currency = CountryService::getCurrency($shop_goods_model['country_code']);
            }
            $exchange_rate = 0;
            $allegro_currency = [];
            if ($shop_goods_model['platform_type'] == Base::PLATFORM_OZON && $shop_goods_model['other_tag'] != GoodsShop::OTHER_TAG_OVERSEAS) {
                $target_currency = 'RUB';
            } else {
                $target_currency = 'CNY';
            }
            if ($shop_goods_model['platform_type'] == Base::PLATFORM_ALLEGRO) {
                $allegro_target_currency = 'PLN';
                if ($base_currency == $allegro_target_currency) {
                    $allegro_target_currency = 'CZK';
                }
                $country_code = substr($allegro_target_currency,0,2);
                $allegro_other_shop = GoodsShop::find()->where(['cgoods_no' => $shop_goods_model['cgoods_no'],'country_code' => $country_code,'shop_id' => $shop_goods_model['shop_id']])->asArray()->one();
                if (!empty($allegro_other_shop)) {
                    $allegro_exchange_rate = ExchangeRateService::getRealConversion($allegro_target_currency, $base_currency);
                    $allegro_exchange_rate = round($allegro_exchange_rate, 4);
                    $other_currency_price = round($allegro_other_shop['price'] * $allegro_exchange_rate * 0.8,2);
                    $allegro_currency = [
                        'other_currency_price' => $other_currency_price
                    ];
                }
            }
            $exchange_rate = ExchangeRateService::getRealConversion($base_currency, $target_currency);
            $exchange_rate = round($exchange_rate, 4);
            $currency = [
                'base_currency' => $base_currency,
                'target_currency' => $target_currency,
                'exchange_rate' => $exchange_rate,
            ];
            $goods_model = Goods::findOne(['goods_no' => $main_goods_model['goods_no']]);
            $goods_chlid = GoodsChild::findOne(['cgoods_no' => $shop_goods_model['cgoods_no']]);
            $warehouse_lists = [];
            $logistics_price = [];
            $purchase_price = $goods_chlid['price'];
            $supplier_price = SupplierRelationship::find()->where(['goods_no'=>$main_goods_model['goods_no'],'is_prior'=>1])->select('purchase_amount')->scalar();
            if($supplier_price > 0) {
                $purchase_price = $supplier_price;
            }
            $purchase_price_usd = round(($purchase_price + 4)
                * ExchangeRateService::getRealConversion('CNY', 'USD'), 2);
            if ($shop_goods_model['platform_type'] == Base::PLATFORM_OZON && $shop_goods_model['other_tag'] != GoodsShop::OTHER_TAG_OVERSEAS) {
                $method_logistics_map = [
                    2011,//兴远陆运到门
                    2015,//兴远陆空到门
                    2146,//e邮宝特惠
                ];
                $platform_warehouse = PlatformShopConfig::find()->where(['shop_id' => $shop_goods_model['shop_id'], 'type' => PlatformShopConfig::TYPE_WAREHOUSE])->asArray()->all();
                $warehouse_lists = ArrayHelper::map($platform_warehouse, 'type_id', 'type_val');
                $logistics_price = GoodsLogisticsPrice::find()
                    ->select('logistics_channels_id,price,currency')
                    ->where(['platform_type' => Base::PLATFORM_OZON, 'cgoods_no' => $shop_goods_model['cgoods_no']])->indexBy('logistics_channels_id')
                    ->asArray()->all();
                $weight = $goods_model['real_weight'] > 0 ? $goods_model['real_weight'] : $goods_model['weight'];
                foreach ($method_logistics_map as $map_v) {
                    $freight_coefficient = 1.1;
                    if ($map_v == 2146 || in_array($shop_goods_model['shop_id'],[216,220])) {//e邮宝特惠不加系数 两个新店铺不加
                        $freight_coefficient = 1;
                    }
                    if (empty($logistics_price[$map_v])) {
                        $freight_price = OrderService::getMethodLogisticsFreightPrice($map_v, 'RU', $weight, $goods_model['size']);
                        $logistics_info = [
                            'logistics_channels_id' => $map_v,
                            'price' => $freight_price * $freight_coefficient,
                            'currency' => 'CNY',
                            'estimate' => true,
                        ];
                        if ($freight_price > 0) {
                            $logistics_price[] = $logistics_info;
                        }
                    }
                }
                foreach ($logistics_price as &$v) {
                    $v['logistics_name'] = ShippingMethod::find()->where(['id' => $v['logistics_channels_id']])->select('shipping_method_code')->scalar();
                    $v['estimate'] = empty($v['estimate']) ? false : $v['estimate'];
                    $v['cost'] = +round(($purchase_price_usd +
                            $v['price'] * ExchangeRateService::getRealConversion($v['currency'], 'USD')) * 1.18, 2);
                    $v['cost_rub'] = round($v['cost'] * ExchangeRateService::getRealConversion('USD', 'RUB'), 2);
                }
            }

            $goods_shop_overseas_warehouse = [];
            if ($shop_goods_model['platform_type'] == Base::PLATFORM_ALLEGRO) {
                $goods_shop_overseas_warehouse = GoodsShopOverseasWarehouse::find()->where(['goods_shop_id' => $id])->asArray()->one();
                if(!empty($goods_shop_overseas_warehouse)) {
                    $goods_shop_overseas_warehouse['inventory_quantity'] = GoodsStock::find()->where(['warehouse' => $goods_shop_overseas_warehouse['warehouse_id'], 'cgoods_no' => $goods_shop_overseas_warehouse['cgoods_no']])->select('num')->scalar();
                    $goods_shop_overseas_warehouse['transit_quantity'] = BlContainerGoods::find()->where(['warehouse_id' => $goods_shop_overseas_warehouse['warehouse_id'], 'cgoods_no' => $goods_shop_overseas_warehouse['cgoods_no'], 'status' => BlContainer::STATUS_NOT_DELIVERED])->select('sum(num) as num')->scalar();
                    $warehouse_name = Warehouse::find()->where(['id' => $goods_shop_overseas_warehouse['warehouse_id']])->select('warehouse_name')->scalar();
                    $inventory_quantity = GoodsStock::find()->where(['warehouse' => $goods_shop_overseas_warehouse['warehouse_id'], 'cgoods_no' => $goods_shop_overseas_warehouse['cgoods_no']])->select('num')->scalar();
                    $transit_quantity = BlContainerGoods::find()->where(['warehouse_id' => $goods_shop_overseas_warehouse['warehouse_id'], 'cgoods_no' => $goods_shop_overseas_warehouse['cgoods_no'], 'status' => BlContainer::STATUS_NOT_DELIVERED])->select('sum(num) as num')->scalar();
                    $goods_shop_overseas_warehouse['warehouse'] = [
                        'warehouse_name' => $warehouse_name,
                        'inventory_quantity' => $inventory_quantity ? $inventory_quantity : 0,
                        'transit_quantity' => $transit_quantity ? $transit_quantity : 0,
                    ];
                }
            }
            $item = GoodsShopPriceChangeLog::find()->where(['goods_shop_id' => $id])->orderBy(['add_time' => SORT_DESC])->limit(10)->asArray()->all();
            foreach ($item as &$it) {
                $it['add_time'] = date("Y-m-d H:i:s", $it['add_time']);
                $it['type'] = GoodsShopPriceChangeLog::$price_map[$it['type']];
                if (!empty($it['user_id'])) {
                    $items = \backend\models\AdminUser::findOne($it['user_id']);
                    $it['user_id'] = $items['username'];
                }
            }
            $good_ware = $good['real_logistics_id'];
            $warhou = PlatformShopConfig::find()->where(['type_id' => $good['logistics_id']])->asArray()->one();
            $warehouse_id = $warhou['type_val'];

            $selling_price = [
                'original' => [
                    'price' => 0,
                    'currency' => 'RUB'
                ],
                'target' => [
                    'price' => 0,
                    'currency' => 'USD'
                ],
            ];
            if (!empty($main_goods_model['selling_price']) && $main_goods_model['selling_price'] > 0) {
                $selling_price['original']['price'] = $main_goods_model['selling_price'];
                $selling_price['target']['price'] = round($main_goods_model['selling_price']
                    * ExchangeRateService::getRealConversion($selling_price['original']['currency'], $selling_price['target']['currency']), 2);
                $selling_price['RealConversion'] = ExchangeRateService::getRealConversion($selling_price['original']['currency'], $selling_price['target']['currency']);
            }
            $min_price_arr = GoodsFollowService::getMinCostPrice($goods_chlid,$shop_goods_model);
            $data = [
                'platform_type' => $this->platform_type,
                'main_goods' => $main_goods_model,
                'goods' => $goods_model,
                'shop_goods_model' => $shop_goods_model,
                'price' => [
                    'min_price' => $min_price_arr[0],
                    'warning_price' => $min_price_arr[1],
                ],
                'selling_price' => $selling_price,
                'warehouse_lists' => $warehouse_lists,
                'warehouse_id' => $warehouse_id,
                'good_ware' => $good_ware,
                'logistics_price' => $logistics_price,
                'currency' => $currency,
                'list' => $item,
                'shop_follow_sale' => $shop_follow_sale,
                'goods_shop_overseas_warehouse' => $goods_shop_overseas_warehouse,
                'allegro_currency' => $allegro_currency,
            ];
            try {
                return $this->render($this->render_view . 'update_price', $data);
            } catch (ViewNotFoundException $e) {
                return $this->render('/goods/base/update_price', $data);
            }
        }
    }

    /**
     * 预算价格
     * @return array
     */
    public function actionBudgetPrice()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = (int)$req->post('id');
        $fixed_price = $req->post('fixed_price');
        $follow_price = $req->post('follow_price');
        $discount = $req->post('discount');
        $exchange_rate = $req->post('exchange_rate');

        $shop_goods_model = GoodsShop::find()->where(['id' => $id])->one();
        $shop_model = Shop::find()->where(['id' => $shop_goods_model['shop_id']])->one();
        $good = GoodsShopExpand::find()->where(['goods_shop_id' => $shop_goods_model['id']])->one();
        $shop_follow_sale = GoodsShopFollowSale::findOne(['goods_shop_id' => $id]);
        if ($shop_follow_sale) {
            if (!in_array($shop_follow_sale['type'], [GoodsShopFollowSale::TYPE_FOLLOW, GoodsShopFollowSale::TYPE_NON_CHINA, GoodsShopFollowSale::TYPE_UNFOLLOW_FOLLOW, GoodsShopFollowSale::TYPE_LOW_PRICE_FOLLOW, GoodsShopFollowSale::TYPE_FOLLOW_OFF])
                && $shop_follow_sale['price'] <= 0) {
                $shop_follow_sale = null;
            }
        }
        $platform_type = $shop_goods_model['platform_type'];

        $base_currency = $shop_model['currency'];
        if (empty($base_currency) && !empty($shop_goods_model['country_code'])) {
            $base_currency = CountryService::getCurrency($shop_goods_model['country_code']);
        }

        $goods_model = Goods::findOne(['goods_no' => $shop_goods_model['goods_no']]);
        $goods_child = GoodsChild::findOne(['cgoods_no' => $shop_goods_model['cgoods_no']]);
        $size = $goods_child['package_size'];
        $warehouse_lists = [];
        $purchase_price = $goods_child['price'];
        $supplier_price = SupplierRelationship::find()->where(['goods_no' => $shop_goods_model['goods_no'], 'is_prior' => 1])->select('purchase_amount')->scalar();
        if ($supplier_price > 0) {
            $purchase_price = $supplier_price;
        }
        $purchase_price_usd = round(($purchase_price + 4)
            * ExchangeRateService::getRealConversion('CNY', 'USD'), 2);

        $goods_shop_overseas_warehouse = [];
        $has_platform_fee = 0;
        $freight_price = 0;
        if ($shop_goods_model['other_tag'] == GoodsShop::OTHER_TAG_OVERSEAS) {
            $goods_shop_overseas_warehouse = GoodsShopOverseasWarehouse::find()->where(['goods_shop_id' => $id])->asArray()->one();
            if (!empty($goods_shop_overseas_warehouse)) {
                $has_platform_fee = true;
            }
        }

        if ($platform_type == Base::PLATFORM_OZON && !$has_platform_fee) {
            $target_currency = 'RUB';
        } else {
            $target_currency = 'CNY';
        }
        //汇率
        if(empty($exchange_rate)) {
            $exchange_rate = 1;
            if (!empty($base_currency)) {
                $exchange_rate = ExchangeRateService::getRealConversion($base_currency, $target_currency);
                $exchange_rate = round($exchange_rate, 4);
            }
        }

        if ($platform_type == Base::PLATFORM_WORTEN) {
            $has_platform_fee = true;
            $weight_tmp = $goods_child['real_weight'] > 0 ? $goods_child['real_weight'] : $goods_child['weight'];
            $weight_cjz = GoodsService::cjzWeight($goods_child['package_size'], 8000, 0);
            $weight_tmp = max($weight_cjz, $weight_tmp);
            $freight_price = ($weight_tmp * 58 + 20) * 1.05;
        }

        $price_range = [
            'start' => round($shop_goods_model['original_price'] * 0.9, 2),
            'end' => round($shop_goods_model['original_price'] * 1.1, 2),
        ];
        $expand_weight = empty($good['weight_g']) ? 0 : $good['weight_g'] / 1000;
        $estimate_weight = GoodsShopService::getGoodsWeight($goods_model, $shop_goods_model, false);
        $lock_weight = empty($good['lock_weight']) ? 0 : $good['lock_weight'];

        $price = $shop_goods_model['price'];//售价
        if (!empty($discount) && $discount > 0) {
            $price = $shop_goods_model['original_price'] * $discount / 10;
        }
        if (!empty($fixed_price) && $fixed_price > 0) {
            $price = $fixed_price;
        }
        if (!empty($follow_price) && $follow_price > 0) {
            $price = $follow_price;
        }

        $row_left = [];
        $size_arr = GoodsService::getSizeArr($size);
        $litre = empty($size)?0:($size_arr['size_l'] * $size_arr['size_w'] * $size_arr['size_h'] / 1000);
        $litre = round($litre,2);
        $cube = round($litre / 1000,4);

        if($has_platform_fee) {
            $fgoods_service = FGoodsService::factory($platform_type)->setCountryCode($shop_goods_model['country_code']);
            $post_start_logistics_cost = $start_logistics_cost = $req->post('start_logistics_cost');
            $post_end_logistics_cost = $end_logistics_cost = $req->post('end_logistics_cost');
            $platform_discount = $req->post('platform_discount',0);//平台活动折扣
            if(!empty($platform_discount)) {
                $price = round(((100 - $platform_discount) * $price) / 100, 2);
            }
            if (!empty($goods_shop_overseas_warehouse)) {
                $freight_price = 0;
                $end_logistics_currency = $base_currency;
                if($platform_type == Base::PLATFORM_OZON) {//ozon尾程
                    //fbo物流费
                    $fbo_logistics_fee = (new GoodsPriceTrialService())->LogisticsPrice('FBO', $litre);
                    $fbo_logistics_fee = $fbo_logistics_fee + $price * 0.055;
                    $goods_shop_overseas_warehouse['estimated_end_logistics_cost'] = $fbo_logistics_fee;
                }

                if(in_array($platform_type,[Base::PLATFORM_EMAG,Base::PLATFORM_WILDBERRIES])) {
                    $end_price = $fgoods_service->getFboFweight($estimate_weight,$size);
                    $goods_shop_overseas_warehouse['estimated_end_logistics_cost'] = $end_price;
                }

                if($goods_shop_overseas_warehouse['warehouse_id'] == 8) {//谷仓捷克海外仓
                    $shipping_methods_where = ['warehouse_id'=>$goods_shop_overseas_warehouse['warehouse_id']];
                    switch ($platform_type) {
                        case Base::PLATFORM_CDISCOUNT:
                        case Base::PLATFORM_RDC:
                            $country = 'FR';
                            break;
                        case Base::PLATFORM_FYNDIQ:
                            $country = 'SE';
                            break;
                        case Base::PLATFORM_WORTEN:
                            $country = 'PT';
                            break;
                        case Base::PLATFORM_MIRAVIA:
                            $country = 'ES';
                            break;
                        case Base::PLATFORM_ALLEGRO:
                            $shipping_methods_where['id'] = 2272;
                        default:
                            $country = 'PL';
                    }
                    $shipping_methods = ShippingMethod::find()->where($shipping_methods_where)->asArray()->all();
                    $end_price = 0;
                    foreach ($shipping_methods as $sh_v) {
                        $tmp_price = OrderService::getMethodLogisticsFreightPrice($sh_v['id'], $country, $estimate_weight, $size);
                        if (empty($tmp_price)) {
                            continue;
                        }
                        $end_price = $end_price>0?min($end_price, $tmp_price):$tmp_price;
                    }
                    //谷仓操作费
                    $end_price += (new OverseasGoodsService())->goodcangOutboundOperationFee($estimate_weight);
                    $goods_shop_overseas_warehouse['estimated_end_logistics_cost'] = $end_price;
                    $end_logistics_currency = 'CNY';
                }
                $post_start_logistics_cost = $start_logistics_cost = empty($start_logistics_cost) ? $goods_shop_overseas_warehouse['start_logistics_cost'] : $start_logistics_cost;
                $post_end_logistics_cost = $end_logistics_cost = empty($end_logistics_cost) ? $goods_shop_overseas_warehouse['end_logistics_cost'] : $end_logistics_cost;
                $start_logistics_cost = $start_logistics_cost > 0 ? $start_logistics_cost : $goods_shop_overseas_warehouse['estimated_start_logistics_cost'];
                $end_logistics_cost = $end_logistics_cost > 0 ? $end_logistics_cost : $goods_shop_overseas_warehouse['estimated_end_logistics_cost'];
            }
            $platform_fee = $fgoods_service->platformFee($price,$shop_goods_model['shop_id']);

            $cost_price = $start_logistics_cost + $purchase_price + $freight_price + 4;
            $tax = 0;//税务
            if(in_array($platform_type , [Base::PLATFORM_OZON,Base::PLATFORM_WILDBERRIES])) {
                $tax = - round(($price - $platform_fee - $end_logistics_cost) * 0.08,2);
                if($platform_type == Base::PLATFORM_OZON) {
                    $min_price_arr = GoodsFollowService::getMinCostPrice($goods_child, $shop_goods_model, ['start_logistics_cost' => $start_logistics_cost]);
                    $price_range['start'] = $min_price_arr[0];
                }
            }
            if($platform_type == Base::PLATFORM_EMAG) {
                $tax = round($price * 0.2,2);
            }

            //尾程汇率
            $end_logistics_exchange_rate = $end_logistics_currency=='CNY'?1:ExchangeRateService::getRealConversion($end_logistics_currency,'CNY');
            $profit = ($price - $platform_fee + $tax) * $exchange_rate - $end_logistics_cost * $end_logistics_exchange_rate - $cost_price;//人民币利润

            //尾程是人民币加入成本
            if($end_logistics_currency == 'CNY') {
                $cost_price += $end_logistics_cost;
            }

            $row_left[] = [
                'label' => '系统推荐价',
                'value' => $shop_goods_model['original_price'] . '【' . $price_range['start'] . ' - ' . $price_range['end'] . '】' . $base_currency
            ];

            $row_left[] = [
                'label' => '佣金',
                'value' => $this->getCurrencyPrice($platform_fee, $base_currency) . ' ( ' . $this->getCurrencyPrice($platform_fee * $exchange_rate) . ')',
            ];
            $row_left[] = [
                'label' => '成本',
                'value' => $this->getCurrencyPrice($cost_price / $exchange_rate, $base_currency) . ' ( ' . $this->getCurrencyPrice($cost_price) . ')',
                'color' => '#ff0000',
            ];
            if($tax != 0) {
                $row_left[] = [
                    'label' => '税务',
                    'value' => $this->getCurrencyPrice($tax,$base_currency) . ' ( ' . $this->getCurrencyPrice($tax * $exchange_rate) . ')',
                    'color' => '#00aa00',
                ];
            }
            $row_left[] = [
                'label' => '利润',
                'value' => $this->getCurrencyPrice($profit / $exchange_rate, $base_currency) . ' ( ' . $this->getCurrencyPrice($profit) . ')',
            ];
        }else {
            $row_left[] = [
                'label' => '系统推荐价',
                'value' => $shop_goods_model['original_price'] . '【' . $price_range['start'] . ' - ' . $price_range['end'] . '】' . $base_currency
            ];
        }

        $row_left[] = [
            'label' => '实际售价',
            'value' => $this->getCurrencyPrice($price, $base_currency) . ' ( ' . $this->getCurrencyPrice($price * $exchange_rate, $target_currency) . ')',
            'color' => '#ff0000',
        ];

        $data['left'] = $row_left;

        $row_right = [];
        $row_right[] = [
            'label' => '采购价',
            'value' => '¥ ' . $purchase_price
        ];

        if ($freight_price > 0) {
            $row_right[] = [
                'label' => '物流费用',
                'value' => $this->getCurrencyPrice($freight_price),
            ];
        }

        if(!empty($goods_shop_overseas_warehouse)) {
            if($platform_type == Base::PLATFORM_OZON) {
                $row_right[] = [
                    'name' => 'platform_discount',
                    'type' => 'input',
                    'label' => '活动折扣',
                    'currency' => '%',
                    'value' => $platform_discount,
                    'auto_ajax' => true
                ];
            }
            $row_right[] = [
                'name' => 'start_logistics_cost',
                'type' => 'input',
                'label' => '头程费用',
                'currency' => '元',
                'value' => $post_start_logistics_cost,
                'estimate_value' => $goods_shop_overseas_warehouse['estimated_start_logistics_cost'],
                'auto_ajax' => true
            ];
            $row_right[] = [
                'name' => 'end_logistics_cost',
                'type' => 'input',
                'label' => '尾程费用',
                'currency' => $end_logistics_currency =='CNY'?'元':$end_logistics_currency,
                'value' => $post_end_logistics_cost,
                'estimate_value' => $goods_shop_overseas_warehouse['estimated_end_logistics_cost'],
                'auto_ajax' => true
            ];
        }

        if ($platform_type == Base::PLATFORM_HEPSIGLOBAL) {
            $row_right[] = [
                'name' => 'weight',
                'type' => 'input',
                'label' => '重量',
                'value' => $expand_weight,
                'estimate_value' => $estimate_weight,
                'lock_weight' => $lock_weight == 1,
                'auto_ajax' => false
            ];
        } else {
            $row_right[] = [
                'label' => '重量',
                'value' => $expand_weight > 0 ? $expand_weight : $estimate_weight,
            ];
        }

        $row_right[] = [
            'label' => '尺寸',
            'value' => $size .($litre>0?(' <span style="color: #00aa00">【 '.$litre.'升 '.$cube .' m³'.' 】</span>'):''),
        ];
        $data['right'] = $row_right;
        return $this->FormatArray(self::REQUEST_SUCCESS, "", $data);
    }

    /**
     * 货币金额
     * @param $price
     * @param $currency
     * @return string
     */
    public function  getCurrencyPrice($price,$currency = 'CNY')
    {
        $price = round($price , 2);
        if ($currency != 'CNY') {
            $price_cur = $price . " " . $currency ;
        } else {
            $price_cur = "¥ " . $price;
        }
        return $price_cur;
    }

    /**
     *
     * @param $data
     * @return mixed
     */
    public function dataDeal($data){
        return $data;
    }

    /**
     * @routeName 删除商品
     * @routeDescription 删除指定商品
     * @return array
     * @throws
     */
    public function actionDelete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = (int)$req->get('id');
        $result = (new GoodsShopService())->delGoodsToId($id);
        if ($result) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除失败", []);
        }
    }


    /**
     * @routeName 批量锁定价格
     * @routeDescription 批量锁定价格
     * @return array
     * @throws
     */
    public function actionBatchUpdatePrice()
    {
        $req = Yii::$app->request;
        $key = $req->get('key');
        $params = GoodsService::urlParam($key);
        $id = isset($params['id']) ? $params['id'] : '';
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            $fixed_price = $post['fixed_price'];
            if (empty($id)) {
                return $this->FormatArray(self::REQUEST_FAIL, "商品不能为空", []);
            }
            $ids = explode(',',$id);
            $goods_shop = GoodsShop::find()->where(['id' => $ids])->select(['id','discount'])->asArray()->all();
            foreach ($goods_shop as $v) {
                $discount = $v['discount'];
                $result = (new GoodsShopService())->updateGoodsDiscount($v['id'], $discount, $fixed_price);
                if (!$result) {
                    return $this->FormatArray(self::REQUEST_SUCCESS, "修改失败", []);
                }
            }
            return $this->FormatArray(self::REQUEST_SUCCESS, "修改成功", []);
        }
        $data = ['id'=>$id,'platform_type'=>$this->platform_type];
        try {
            return $this->render($this->render_view . 'batch_update_price',$data);
        } catch (ViewNotFoundException $e) {
            return $this->render('/goods/base/batch_update_price',$data);
        }
    }

    /**
     * @routeName 批量删除
     * @routeDescription 批量删除
     * @return array
     * @throws
     */
    public function actionBatchDel()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = $req->post('id');
        $goods_shop = GoodsShop::find()->where(['id'=>$id])->all();
        if(empty($goods_shop)) {
            return $this->FormatArray(self::REQUEST_FAIL, "商品不能为空", []);
        }
        $error = 0;
        foreach ($goods_shop as $goods_model) {
            try {
                if ((new GoodsShopService())->delGoods($goods_model) == false) {
                    $error ++;
                }
            }catch (\Exception $e){
                $error ++;
            }
        }
        if($error > 0){
            return $this->FormatArray(self::REQUEST_FAIL, "删除失败，失败".$error.'条', []);

        }else {
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除成功", []);
        }
    }


    /**
     * @routeName 批量设置各自平台类目
     * @routeDescription 批量设置外部各自平台类目
     * @return array
     * @throws
     */
    public function actionBatchCategory()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            if(empty($post['o_category_name'])) {
                return $this->FormatArray(self::REQUEST_FAIL, "类目不能为空", []);
            }

            $goods_no = GoodsShop::find()->where(['id'=>explode(',',$id)])->select('goods_no')->column();
            if(empty($goods_no)) {
                return $this->FormatArray(self::REQUEST_FAIL, "商品不能为空", []);
            }

            $this->model()->updateAll(['o_category_name'=>$post['o_category_name']],['goods_no'=>$goods_no]);

            return $this->FormatArray(self::REQUEST_SUCCESS, "设置类目成功", []);
        } else {
            $data = ['id'=>$id,'platform_type'=>$this->platform_type];
            try {
                return $this->render($this->render_view.'batch_category',$data);
            } catch (ViewNotFoundException $e) {
                return $this->render('/goods/base/batch_category',$data);
            }
        }

    }

    /**
     * @routeName 批量重新翻译
     * @routeDescription 批量重新翻译
     * @return array
     * @throws
     */
    /*public function actionBatchRetranslate()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = $req->post('id');
        $goods_no = GoodsShop::find()->where(['id'=>$id])->select('goods_no')->column();
        if(empty($goods_no)) {
            return $this->FormatArray(self::REQUEST_FAIL, "商品不能为空", []);
        }

        $lists = $this->model()->find()->where(['goods_no'=>$goods_no])->asArray()->all();
        foreach ($lists as $v){
            (new GoodsService())->reTranslate($v['goods_no'],$v['platform_type'],$v['source_method']);
        }
        return $this->FormatArray(self::REQUEST_SUCCESS, "批量重新翻译成功", []);
    }*/

    /**
     * @param $id
     * @return null|Goods
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        if (($model = $this->model()->findOne($id)) !== null) {
           return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * @routeName 商品导出
     * @routeDescription 商品导出
     * @return array |Response|string
     */
    public function actionExport()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $page = $req->get('page',1);
        $config = $req->get('config') ? true : false;
        $page_size = 500;
        $export_ser = new ExportService($page_size);

        $searchModel=new BaseGoodsSearch();
        $where = $searchModel->search(Yii::$app->request->post(),$this->platform_type);
        $model = $this->model();
        $query = $this->query();
        $this->join = $where['_join'];
        unset($where['_join']);
        //$where['g.status'] = [Goods::GOODS_STATUS_VALID,Goods::GOODS_STATUS_WAIT_MATCH];
        if ($config) {
            $count = $model::getCountByCond($where,$query);
            $max_num = in_array(Yii::$app->user->identity->id,[4,6])?100000:$this->max_num;
            $result = $export_ser->forHeadConfig($count,0,$max_num);
            return $this->FormatArray(self::REQUEST_SUCCESS, "", $result);
        }
        $list = $model::getListByCond($where, $page, $page_size, null,null,$query);
        $data = $this->export($list);
        return $this->FormatArray(self::REQUEST_SUCCESS, "", $data);
    }


    /**
     * 导出
     * @param $list
     * @return array
     */
    public function export($list)
    {
        $column = $this->export_column;
        $cgoods_nos = ArrayHelper::getColumn($list,'cgoods_no');
        $goods_childs = GoodsChild::find()->where(['cgoods_no'=>$cgoods_nos])->indexBy('cgoods_no')->asArray()->all();

        $data = [];
        $shop_map = \common\services\ShopService::getShopMap();
        foreach ($list as $k => $v) {
            $goods_child = empty($goods_childs[$v['cgoods_no']])?[]:$goods_childs[$v['cgoods_no']];
            $price = $v['price'];
            $v = (new GoodsService())->dealGoodsInfo($v,$goods_child);
            $v['price'] = $price;
            $image = json_decode($v['goods_img'], true);
            $image = empty($image) || !is_array($image) ? [] : $image;
            CommonUtil::handleUrlProtocol($image,['img'],true,'https');
            $v['goods_img'] = json_encode($image);
            $info = $this->dealExport($v);

            if(!isset($info['goods_no']) && array_key_exists('goods_no',$column)) {
                $info['goods_no'] = $v['goods_no'];
            }

            if(!isset($info['cgoods_no']) && array_key_exists('cgoods_no',$column)) {
                $info['cgoods_no'] = $v['cgoods_no'];
            }
            if(!isset($info['country']) && array_key_exists('country',$column)) {
                if($this->has_country){
                    $info['country'] = CountryService::getName($v['country_code']);
                }
            }

            if(!isset($info['shop_name']) && array_key_exists('shop_name',$column)) {
                $info['shop_name'] = empty($v['shop_id']) ? '' : $shop_map[$v['shop_id']];
            }

            if(!isset($info['category_name']) && array_key_exists('category_name',$column)) {
                $info['category_name'] =  Category::getCategoryName($v['category_id']);
            }

            if(!isset($info['category_name_en']) && array_key_exists('category_name_en',$column)) {
                $info['category_name_en'] =  Category::getCategoryNameEn($v['category_id']);
            }

            if(!isset($info['o_category_name']) && array_key_exists('o_category_name',$column)) {
                $info['o_category_name'] = $v['o_category_name'];
            }

            $sku_no = !empty($goods_child['sku_no'])?$goods_child['sku_no']:$v['sku_no'];
            if(!isset($info['sku_no']) && array_key_exists('sku_no',$column)) {
                $info['sku_no'] = $sku_no;
            }

            if(!isset($info['platform_sku_no']) && array_key_exists('platform_sku_no',$column)) {
                $info['platform_sku_no'] = !empty($v['platform_sku_no'])?$v['platform_sku_no']:$sku_no;
            }

            if(!isset($info['platform_goods_id']) && array_key_exists('platform_goods_id',$column)) {
                $info['platform_goods_id'] = !empty($v['platform_goods_id'])?$v['platform_goods_id']:'';
            }

            if(!isset($info['ean']) && array_key_exists('ean',$column)) {
                $info['ean'] = $v['ean'];
            }

            if(!isset($info['price']) && array_key_exists('price',$column)) {
                $info['price'] = $v['price'];
            }

            if(!isset($info['brand']) && array_key_exists('brand',$column)) {
                $info['brand'] = $v['brand'];
            }

            //所有图片
            if(!isset($info['image_all']) && array_key_exists('image_all',$column)) {
                $image_arr = [];
                $i = 0;
                foreach ($image as $img_v){
                    if(empty($img_v['img']) || $i > 6){
                        continue;
                    }
                    $i++;
                    $image_arr[] = $img_v['img'];
                }
                $info['image_all'] = implode(',',$image_arr);
            }

            if(!isset($info['image']) && array_key_exists('image',$column)) {
                $info['image'] = !empty($image[0])?$image[0]['img']:'';
            }

            if(!isset($info['image2']) && array_key_exists('image2',$column)) {
                $info['image2'] = !empty($image[1])?$image[1]['img']:'';
            }

            if(!isset($info['image3']) && array_key_exists('image3',$column)) {
                $info['image3'] = !empty($image[2])?$image[2]['img']:'';
            }

            if(!isset($info['image4']) && array_key_exists('image4',$column)) {
                $info['image4'] = !empty($image[3])?$image[3]['img']:'';
            }

            if(!isset($info['image5']) && array_key_exists('image5',$column)) {
                $info['image5'] = !empty($image[4])?$image[4]['img']:'';
            }

            if(!isset($info['image6']) && array_key_exists('image6',$column)) {
                $info['image6'] = !empty($image[5])?$image[5]['img']:'';
            }

            if(!isset($info['image7']) && array_key_exists('image7',$column)) {
                $info['image7'] = !empty($image[6])?$image[6]['img']:'';
            }

            if(!isset($info['image8']) && array_key_exists('image8',$column)) {
                $info['image8'] = !empty($image[7])?$image[7]['img']:'';
            }

            if(!isset($info['image9']) && array_key_exists('image9',$column)) {
                $info['image9'] = !empty($image[8])?$image[8]['img']:'';
            }

            if(!isset($info['goods_name']) && array_key_exists('goods_name',$column)) {
                $info['goods_name'] = $v['goods_name'];
            }

            $p_class = FGoodsService::factory($this->platform_type);
            if(!isset($info['goods_short_name']) && array_key_exists('goods_short_name',$column)) {
                $info['goods_short_name'] = $p_class->dealTitle($v['goods_short_name']);
            }

            if(!isset($info['goods_content']) && array_key_exists('goods_content',$column)) {
                $info['goods_content'] = $p_class->dealContent($v);
            }

            $goods_desc_result = empty($v['goods_desc'])?[]:explode(PHP_EOL, $v['goods_desc']);
            $goods_desc_arr = [];
            foreach ($goods_desc_result as $re_v) {
                $re_v = trim($re_v);
                if (empty($re_v)) {
                    continue;
                }
                $goods_desc_arr[] = $re_v;
            }
            if(!isset($info['goods_desc1']) && array_key_exists('goods_desc1',$column)) {
                $info['goods_desc1'] = !empty($goods_desc_arr[0])?$goods_desc_arr[0]:'';
            }
            if(!isset($info['goods_desc2']) && array_key_exists('goods_desc2',$column)) {
                $info['goods_desc2'] = !empty($goods_desc_arr[1])?$goods_desc_arr[1]:'';
            }
            if(!isset($info['goods_desc3']) && array_key_exists('goods_desc3',$column)) {
                $info['goods_desc3'] = !empty($goods_desc_arr[2])?$goods_desc_arr[2]:'';
            }
            if(!isset($info['goods_desc4']) && array_key_exists('goods_desc4',$column)) {
                $info['goods_desc4'] = !empty($goods_desc_arr[3])?$goods_desc_arr[3]:'';
            }
            if(!isset($info['goods_desc5']) && array_key_exists('goods_desc5',$column)) {
                $info['goods_desc5'] = !empty($goods_desc_arr[4])?$goods_desc_arr[4]:'';
            }

            if(!isset($info['goods_desc']) && array_key_exists('goods_desc',$column)) {
                $info['goods_desc'] = $p_class->dealP($v['goods_desc']);
            }

            //颜色
            if(!isset($info['colour']) && array_key_exists('colour',$column)) {
                $info['colour'] = empty($goods_child['colour'])?$v['gcolour']:$goods_child['colour'];
            }
            $info['psize'] = empty($goods_child['size'])?'':$goods_child['size'];

            //尺寸
            if(!isset($info['size']) && array_key_exists('size',$column)) {
                $info['size'] = $v['size'];
            }
            $size = GoodsService::getSizeArr($v['size']);
            if(!isset($info['size_l']) && array_key_exists('size_l',$column)) {
                $info['size_l'] = !empty($size['size_l'])?$size['size_l']:'';
            }
            if(!isset($info['size_w']) && array_key_exists('size_w',$column)) {
                $info['size_w'] = !empty($size['size_w'])?$size['size_w']:'';
            }
            if(!isset($info['size_h']) && array_key_exists('size_h',$column)) {
                $info['size_h'] = !empty($size['size_h'])?$size['size_h']:'';
            }

            //重量
            if(!isset($info['weight']) && array_key_exists('weight',$column)) {
                $info['weight'] = $v['weight'];
            }

            if(!isset($info['real_weight']) && array_key_exists('real_weight',$column)) {
                $info['real_weight'] = $v['real_weight'];
            }

            if(!isset($info['cjz_weight']) && array_key_exists('cjz_weight',$column)) {
                $info['cjz_weight'] = GoodsService::cjzWeight($v['size'],8000,0.5);
            }

            if(!isset($info['add_time']) && array_key_exists('add_time',$column)) {
                $info['add_time'] = date('Y-m-d H:i', $v['add_time']);
            }

            $info = $this->afterDealExport($info);

            $data[$k] = $info;
        }

        return [
            'key' => array_keys($column),
            'header' => array_values($column),
            'list' => $data,
            'fileName' => Base::$platform_maps[$this->platform_type].'商品导出' . date('ymdhis')
        ];
    }

    public function dealExport($data)
    {
        return [];
    }

    public function afterDealExport($data)
    {
        return $data;
    }

    protected function dealExportContent($info,$html = false)
    {
        $goods_content = $info['goods_name'].PHP_EOL.$info['goods_content'];
        $goods_content = $this->dealP($goods_content,$html);
        return $goods_content;
    }

    /**
     * 处理换行
     * @param $goods_content
     * @param bool $html
     * @return string
     */
    protected function dealP($goods_content,$html = false)
    {
        if ($html) {
            return CommonUtil::dealP($goods_content);
        }
        return $goods_content;
    }

    /**
     * @routeName 商品库管理
     * @routeDescription 商品库管理
     */
    public function actionPlatformIndex()
    {
        $req = Yii::$app->request;
        $tag = $req->get('tag', 0);
        $admin_lists = AdminUser::find()->where(['id' => AccessService::getShopOperationUserIds()])->andWhere(['=', 'status', AdminUser::STATUS_ACTIVE])->select(['id', 'nickname'])->asArray()->all();
        $admin_lists = ArrayHelper::map($admin_lists, 'id', 'nickname');
        $shop_arr = ShopService::getShopDropdown($this->platform_type);
        $data = [
            'platform_type' => $this->platform_type,
            'tag' => $tag,
            'admin_arr' => $admin_lists,
            'all_goods_access' => AccessService::hasAllGoods(),
            'shop_arr' => $shop_arr,
        ];
        try {
            return $this->render($this->render_view . 'platform_index', $data);
        } catch (ViewNotFoundException $e) {
            return $this->render('/goods/base/platform_index', $data);
        }
    }

    /**
     * @routeName 跟卖商品主页
     * @routeDescription 跟卖商品主页
     */
    public function actionShopFollowSaleIndex()
    {
        $category_arr = [];
        $country_arr = [];
        if ($this->has_country) {
            $shop = Shop::find()->where(['platform_type' => $this->platform_type])->asArray()->all();
            foreach ($shop as $v) {
                if (empty($v['country_site'])) {
                    continue;
                }
                $country = explode(',', $v['country_site']);
                $country_arr = array_merge($country_arr, $country);
                $country_arr = array_unique($country_arr);
                $country_arr = CountryService::getSelectOption(['country_code' => $country_arr]);
            }
        }
        $data = [
            'category_arr' => $category_arr,
            'country_arr' => $country_arr,
            'platform_type' => $this->platform_type
        ];
        try {
            return $this->render($this->render_view . 'shop_follow_sale_index', $data);
        } catch (ViewNotFoundException $e) {
            return $this->render('/goods/base/shop_follow_sale_index', $data);
        }
    }

    /**
     * @routeName 跟卖商品日志主页
     * @routeDescription 跟卖商品日志主页
     */
    public function actionShopFollowSaleLogIndex()
    {
        $req = Yii::$app->request;
        $goods_shop_id = $req->get('goods_shop_id');
        $data = [
            'platform_type' => $this->platform_type,
            'goods_shop_id' => $goods_shop_id,
        ];
        try {
            return $this->render($this->render_view . 'shop_follow_sale_log_index',$data);
        } catch (ViewNotFoundException $e) {
            return $this->render('/goods/base/shop_follow_sale_log_index',$data);
        }
    }

    /**
     * @return array
     */
    public function actionShopFollowRestore()
    {
        Yii::$app->response->format=Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = $req->get('id');
        $goods_shop_follow_sale = GoodsShopFollowSale::find()->where(['goods_shop_id'=>$id])->one();
        $goods_shop_follow_sale->type = GoodsShopFollowSale::TYPE_FOLLOW;
        $goods_shop_follow_sale->plan_time = time() + 30*60;
        if ($goods_shop_follow_sale->save()) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "恢复成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL, "恢复失败", []);
        }
    }

    /**
     * @routeName 审查商品
     * @routeDescription 审查商品
     * @throws
     */
    public function actionExamine()
    {
        $cache_token_key = 'com::goods_'.$this->platform_type.'::examine::prev::' . \Yii::$app->user->identity->id;
        $cache_token_next_key = 'com::goods_'.$this->platform_type.'::examine::next::' . \Yii::$app->user->identity->id;
        $req = Yii::$app->request;
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $tag = $req->get('tag');
            $post = $req->post();
            if (!isset($post['audit_status'])) {
                return $this->FormatArray(self::REQUEST_FAIL, "归类必须选择", []);
            }
            $goods_no = $post['goods_no'];
            try {
                if (!(new GoodsService())->examineGoods($goods_no, $post,false)) {
                    return $this->FormatArray(self::REQUEST_FAIL, "归类失败", []);
                }
                $base_goods = $this->model()->find()->where(['goods_no' => $goods_no])->one();
                $base_goods['audit_status'] = $post['audit_status'];
                $base_goods['admin_id'] = \Yii::$app->user->identity->id;
                $base_goods->save();
                //成功删除缓存
                \Yii::$app->redis->setex($cache_token_key, 60 * 60, $goods_no);
                $next_goods_no = \Yii::$app->redis->get($cache_token_next_key);
                if (!empty($next_goods_no)) {//删除商品
                    $next_goods_no = json_decode($next_goods_no);
                    $key = array_search($next_goods_no, $goods_no);
                    array_splice($next_goods_no, $key, 1);
                    \Yii::$app->redis->setex($cache_token_next_key, 60 * 60, json_encode($next_goods_no));
                    //$next_goods_no = array_diff([$goods_no],$next_goods_no);
                }
            } catch (\Exception $e) {
                return $this->FormatArray(self::REQUEST_FAIL, $e->getMessage(), []);
            }
            $uri = 'tag=' . $tag;
            $platform_name = \common\components\statics\Base::$platform_maps[$this->platform_type];
            $url_platform_name = strtolower($platform_name);
            $uri = Url::to(['goods-'.$url_platform_name.'/examine?' . $uri]);
            return $this->FormatArray(self::REQUEST_SUCCESS, "归类成功", ['url' => $uri]);
        } else {
            $prev_goods_no = '';
            $tag = $req->get('tag', 0);
            $goods_no = $req->get('goods_no');
            if (empty($goods_no)) {
                $next_goods_no = \Yii::$app->redis->get($cache_token_next_key);
                $next_goods_no = json_decode($next_goods_no);
                if (!empty($next_goods_no)) {
                    $prev_goods_no = \Yii::$app->redis->get($cache_token_key);
                    $goods_no = current($next_goods_no);
                }
            }
            $has_all_goods = AccessService::hasAllGoods();
            if (empty($goods_no)) {
                $prev_goods_no = \Yii::$app->redis->get($cache_token_key);
                $searchModel = new BaseGoodsSearch();
                $query_params = Yii::$app->request->post();
                $where = $searchModel->platform_search($query_params, $tag);
                $this->join = $where['_join'];
                unset($where['_join']);
                $where['mg.admin_id'] = Yii::$app->user->id;
                $query = $this->platform_query()->select('mg.*');
                $goods_lists = $this->model()->getListByCond($where, 1, $has_all_goods ? 1 : 20, 'mg.id asc', null, $query);
                $base_goods = current($goods_lists);
                $goods_nos = ArrayHelper::getColumn($goods_lists, 'goods_no');
                $goods_no = $base_goods['goods_no'];
                if (!$has_all_goods) {
                    \Yii::$app->redis->setex($cache_token_next_key, 60 * 60, json_encode($goods_nos));
                }
            } else {
                $base_goods = $this->model()->find()->where(['goods_no' => $goods_no])->one();
            }
            $goods_model = Goods::find()->where(['goods_no' => $goods_no])->one();
            if (empty($goods_model)) {
                throw new NotFoundHttpException('已经是最后一条数据');
            }
            $goods_source = GoodsSource::find()->where(['goods_no' => $goods_model['goods_no']])->asArray()->all();
            $goods_child = [];
            if ($goods_model['goods_type'] == Goods::GOODS_TYPE_MULTI) {
                $goods_child = GoodsChild::find()->where(['goods_no' => $goods_model['goods_no']])->asArray()->all();
            }
            return $this->render('/goods/base/examine', [
                'base_goods'=>$base_goods,
                'goods' => $goods_model,
                'source' => $goods_source,
                'prev_goods_no' => $prev_goods_no,
                'goods_child' => $goods_child,
                'platform_type'=>$this->platform_type,
                'uri' => 'tag=' . $tag
            ]);
        }
    }

    /**
     * @routeName 商品库列表
     * @routeDescription 商品库列表
     */
    public function actionPlatformList()
    {
        Yii::$app->response->format=Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $tag = $req->get('tag');
        $searchModel=new BaseGoodsSearch();
        $where=$searchModel->platform_search(Yii::$app->request->post(),$tag);
        $this->join = $where['_join'];
        unset($where['_join']);
        $data = $this->platform_lists($where,null);

        $lists = [];
        foreach ($data['list'] as $v) {
            $info = $v;
            $image = json_decode($v['goods_img'], true);
            $image = empty($image) || !is_array($image) ? '' : current($image)['img'];
            $info['image'] = $image;
            $info['add_time'] = date('Y-m-d H:i:s', $v['add_time']);
            $info['update_time'] = date('Y-m-d H:i:s', $v['update_time']);
            $user = User::getInfo($info['admin_id']);
            $info['admin_name'] = empty($user['nickname']) ? '' : $user['nickname'];
            $info['category_name'] = Category::getCategoryName($v['category_id']);
            $lists[] = $info;
        }

        return $this->FormatLayerTable(
            self::REQUEST_LAY_SUCCESS,'获取成功',
            $lists,$data['pages']->totalCount
        );
    }

    /**
     * @routeName 跟卖商品日志列表
     * @routeDescription 跟卖商品日志列表
     */
    public function actionShopFollowSaleLogList()
    {
        $req = Yii::$app->request;
        $goods_shop_id = $req->get('goods_shop_id');
        Yii::$app->response->format=Response::FORMAT_JSON;
        $searchModel=new BaseGoodsSearch();
        $where=$searchModel->shop_follow_sale_log_search(Yii::$app->request->post(),$this->platform_type,$goods_shop_id);
        $this->join = $where['_join'];
        unset($where['_join']);
        $data = $this->shop_follow_sale_log_lists($where,'gsfsl.id desc');
        $lists = [];
        foreach ($data['list'] as $key => $v) {
            $info = $v;
            $info['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
            $info['update_time'] = date('Y-m-d H:i:s',$v['update_time']);
            $lists[] = $info;
        }
        return $this->FormatLayerTable(
            self::REQUEST_LAY_SUCCESS,'获取成功',
            $lists,$data['pages']->totalCount
        );
    }

    /**
     * @routeName 跟卖商品列表
     * @routeDescription 跟卖商品列表
     */
    public function actionShopFollowSaleList()
    {
        Yii::$app->response->format=Response::FORMAT_JSON;
        $searchModel=new BaseGoodsSearch();
        $where=$searchModel->shop_follow_sale_search(Yii::$app->request->post(),$this->platform_type);
        $this->join = $where['_join'];
        unset($where['_join']);
        $data = $this->shop_follow_sale_lists($where,'gsf.id desc');
        $lists = [];
        $shop_map = \common\services\ShopService::getShopMap();
        $cgoods_nos = ArrayHelper::getColumn($data['list'],'cgoods_no');
        $goods_childs = GoodsChild::find()->where(['cgoods_no'=>$cgoods_nos])->indexBy('cgoods_no')->asArray()->all();
        foreach ($data['list'] as $key => $v) {
            $goods_child = empty($goods_childs[$v['cgoods_no']])?[]:$goods_childs[$v['cgoods_no']];
            $info = $v;;
            if(empty($goods_child['goods_img'])) {
                $image = json_decode($v['goods_img'], true);
                $image = empty($image) || !is_array($image) ? '' : current($image)['img'];
            } else {
                $image = $goods_child['goods_img'];
            }
            if(!empty($goods_child['sku_no'])) {
                $info['sku_no'] = $goods_child['sku_no'];
            }
            if(!empty($goods_child['colour'])) {
                $info['colour'] = $goods_child['colour'];
            }
            $info['shop_name'] = empty($v['shop_id']) ? '' : $shop_map[$v['shop_id']];
            $info['image'] = $image;
            $info['add_time'] = date('Y-m-d H:i', $v['add_time']);
            $user = User::getInfo($info['admin_id']);
            $info['admin_name'] = empty($user['nickname']) ? '' : $user['nickname'];
            $info['category_name'] = Category::getCategoryName($v['category_id']);
            $info['follow_update_time'] = date('Y-m-d H:i', $v['follow_update_time']);
            $info['plan_time'] = date('Y-m-d H:i', $v['plan_time']);
            $info['type_desc'] = !empty(GoodsShopFollowSale::$type_show_map[$v['type']])?GoodsShopFollowSale::$type_show_map[$v['type']]:$v['type'];
            if(!empty($v['status'])){
                $info['status_desc'] = GoodsService::$platform_goods_status_map[$v['status']];
            }
            if($this->has_country){
                $info['country'] = CountryService::getName($v['country_code']);
            }
            $lists[] = $info;
        }
        return $this->FormatLayerTable(
            self::REQUEST_LAY_SUCCESS,'获取成功',
            $lists,$data['pages']->totalCount
        );
    }

    /**
     * 列表
     * @param $where
     * @param string $sort
     * @return array
     */
    protected function platform_lists($where, $sort = 'id DESC')
    {
        $page = Yii::$app->request->get('page');
        if(empty($page)) {
            $page = Yii::$app->request->post('page',1);
        }
        $pageSize = Yii::$app->request->get('limit');
        if(empty($pageSize)) {
            $pageSize = Yii::$app->request->post('limit',20);
        }
        $model = $this->model();
        if (!($model instanceof BaseAR)) {
            return [];
        }

        $query = $this->platform_query();
        $list = $model::getListByCond($where, $page, $pageSize, $sort,null,$query);
        if(count($list) < $pageSize && $page == 1) {
            $count = count($list);
        } else {
            if($this->cache_count) {
                $count = $model::getCacheCountByCond($where, $this->platform_query('count'), __CLASS__ . __FUNCTION__);
            } else {
                $count = $model::getCountByCond($where, $this->platform_query('count'));
            }
        }
        $pages = new Pagination(['totalCount' => $count, 'pageSize' => $pageSize]);
        $list = $this->formatLists($list);

        return [
            'list' => $list,
            'pages' => $pages,
        ];
    }

    public function platform_query($type = 'select')
    {
        $select = 'mg.id as id,mg.goods_no,mg.audit_status,mg.o_category_name,g.id as g_id,g.status as goods_status,g.sku_no,g.goods_name,g.goods_name_cn,g.category_id,g.goods_img,mg.add_time,mg.update_time,mg.admin_id';
        if ($type == 'count') {
            $select = 'mg.id';
        }
        $query = $this->model()->find()
            ->alias('mg')->select($select);
        if ($type != 'count' || in_array('g', $this->join)) {
            $query->leftJoin(Goods::tableName() . ' g', 'mg.goods_no = g.goods_no');
        }
        $fgoods = FGoodsService::factory($this->platform_type);
        if($fgoods->has_country){
            $query->groupBy('mg.goods_no');
        }
        return $query;
    }

    /**
     * @routeName 批量审核
     * @routeDescription 批量审核
     * @return array |Response|string
     */
    public function actionBatchUpdateAuditStatus()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $status = $req->get('status');
        if ($req->isPost) {
            $id = $req->post('id');
        } else {
            $id = $req->get('id');
        }
        $goods_no = $this->model()->find()->select('goods_no')->where(['id' => $id])->column();
        $result = false;
        if (!empty($goods_no)) {
            $result = $this->model()->updateAll(['audit_status' => $status,'admin_id' => \Yii::$app->user->identity->id], ['goods_no' => $goods_no]);
        }
        if ($result) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL, "更新失败", []);
        }
    }

    /**
     * @routeName 批量分配商品
     * @routeDescription 批量分配商品
     * @return array
     * @throws
     */
    public function actionBatchAllo()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            if(empty($post['admin_id'])) {
                return $this->FormatArray(self::REQUEST_FAIL, "平台商品数据处理员不能为空", []);
            }

            if(!empty($id)) {
                $goods = $this->model()->find()->where(['id' => explode(',', $id)])->asArray()->all();
            }else{
                if(empty($post['limit']) || $post['limit'] <= 0) {
                    return $this->FormatArray(self::REQUEST_FAIL, "分配数量错误", []);
                }
                $tag = $req->get('tag');
                $searchModel = new BaseGoodsSearch();
                $where = $searchModel->platform_search(Yii::$app->request->get(),$tag);
                $this->join = $where['_join'];
                unset($where['_join']);
                $goods = $this->model()->dealWhere($where,[],null,$this->platform_query())->limit($post['limit'])->asArray()->all();
                if(count($goods) > 10000) {
                    return $this->FormatArray(self::REQUEST_FAIL, "分配数量不超过10000", []);
                }
            }
            if(empty($goods)) {
                return $this->FormatArray(self::REQUEST_FAIL, "分配商品不能为空", []);
            }
            $ids = ArrayHelper::getColumn($goods,'id');
            $result = $this->model()->updateAll(['admin_id' => $post['admin_id']], ['id' => $ids]);
            /*foreach ($goods as $v) {
                try {
                    $v['admin_id'] = (int)$post['admin_id'];
                    $v->save();
                } catch (\Exception $e) {
                    CommonUtil::logs($v['goods_no'].' 分配失败 '.$e->getMessage(),'batch_allo');
                }
            }*/
            return $this->FormatArray(self::REQUEST_SUCCESS, "分配成功", []);
        } else {
            $admin_ids = AccessService::getShopOperationUserIds();
            $admin_lists = AdminUser::find()->where(['id' => $admin_ids])->andWhere(['=','status',AdminUser::STATUS_ACTIVE])->select(['id','nickname'])->asArray()->all();
            $admin_lists = ArrayHelper::map($admin_lists,'id','nickname');
            return $this->render('/goods/base/allo', ['admin_lists' => $admin_lists,'platform_type'=>$this->platform_type,'id'=>$id]);
        }
    }


    /**
     * @routeName 价格趋势数表
     * @routeDescription 价格趋势数表
     * @return array
     * @throws
     */
    public function actionTable()
    {
        $req = Yii::$app->request;
        $shop_id = $req->get('shop_id');
        $platform_type = $req->get('platform_type');
        $cgoods_no = $req->get('cgoods_no');
        $where = [];
        $where['o.source'] = $platform_type;
        $where['og.cgoods_no'] = $cgoods_no;
        $shop_where = $where;
        $shop_where['shop_id'] = $shop_id;
        $data_shop = $this->getPriceTime($shop_where);
        $data_platform = $this->getPriceTime($where);
        try {
            return $this->render($this->render_view . 'table',['data_shop'=>$data_shop,'data_platform'=>$data_platform]);
        } catch (ViewNotFoundException $e) {
            return $this->render('/goods/base/table',['data_shop'=>$data_shop,'data_platform'=>$data_platform]);
        }
    }


    /**
     * 获取平台或者店铺时间价格
     * @return array
     * @throws
     */
    public function getPriceTime($where = [])
    {
        $query = Order::find()->alias('o')
            ->select('og.goods_income_price as price,o.date as date,o.currency as currency')
            ->leftJoin(OrderGoods::tableName().' og','o.order_id = og.order_id')
            ->where($where)
            ->limit(20)
            ->orderBy('o.date desc')
            ->asArray()->all();
        $data = [];
        if (!empty($query)){
            foreach ($query as $v){
                $data['time'][] = date('y-m-d H:i',$v['date']);
                $data['price'][] = $v['price'];
                $data['currency'][] = $v['currency'];
            }
            $data['time'] = array_reverse($data['time']);
            $data['price'] = array_reverse($data['price']);
            $data['currency'] = array_reverse($data['currency']);
        }
        return $data;
    }


    /**
     * @routeName 批量认领商品
     * @routeDescription 批量领指定商品
     * @return array
     * @throws
     */
    public function actionBatchClaim()
    {
        $req = Yii::$app->request;
        $key = $req->get('key');
        $params = GoodsService::urlParam($key);
        $id = isset($params['id']) ? $params['id'] : '';
        $type = $req->get('type',0);//1为店铺商品认领
        if ($req->isPost) {
            set_time_limit(0);
            ini_set('memory_limit', '512M');
            ob_end_clean();
            ob_implicit_flush();
            header('X-Accel-Buffering: no');
            echo "开始准备认领<br/>";
            Yii::$app->response->format = Response::FORMAT_RAW;

            $post = $req->post();
            if (empty($post['shop'])) {
                return $this->FormatArray(self::REQUEST_FAIL, "认领店铺不能为空", []);
            }

            if ($type == 1) {
                if (!empty($id)) {
                    $goods = GoodsShop::find()->where(['id' => explode(',', $id)])->all();
                }
            } else {
                if (!empty($id)) {
                    $goods = $this->model()->find()->where(['id' => explode(',', $id)])->select('goods_no')->asArray()->all();
                } else {
                    if (empty($post['limit']) || $post['limit'] <= 0) {
                        return $this->FormatArray(self::REQUEST_FAIL, "认领数量错误", []);
                    }
                    $tag = $req->get('tag');
                    $searchModel = new BaseGoodsSearch();
                    $where = $searchModel->platform_search(Yii::$app->request->queryParams, $tag);
                    $this->join = $where['_join'];
                    unset($where['_join']);
                    $goods = $this->model()->dealWhere($where, [], null, $this->platform_query())->select('mg.goods_no,g.status')->limit($post['limit'])->asArray()->all();
                }
            }
            if (empty($goods)) {
                return $this->FormatArray(self::REQUEST_FAIL, "认领商品不能为空", []);
            }
            $success_i = 0;
            $index_i = 0;
            foreach ($goods as $v) {
                try {
                    $index_i ++;
                    echo $index_i.','.$v['goods_no'].',';
                    $goods_shop_id = null;
                    if ($type == 1) {
                        if (!in_array($v['status'], [GoodsShop::STATUS_OFF_SHELF, GoodsShop::STATUS_SUCCESS])) {
                            continue;
                        }
                        $goods_shop_id = $v['id'];
                    }
                    if (!empty($v['status']) && $v['status'] == Goods::GOODS_STATUS_INVALID) {
                        continue;
                    }


                    $shop_ids = [];
                    foreach ($post['shop'] as $shop_v) {
                        $shop_ids[] = $shop_v;
                    }
                    //$shop_lists[$post['shop']]
                    $params = [
                        'old_goods_shop_id' => $goods_shop_id,
                        'show_log' => true,
                    ];
                    $result = (new GoodsService())->claim($v['goods_no'], $shop_ids, GoodsService::SOURCE_METHOD_OWN, $params);
                    if ($result) {
                        $success_i++;
                    }
                } catch (\Exception $e) {
                    echo "---,认领失败," . $e->getMessage() . "<br/>";
                    CommonUtil::logs($v['goods_no'] . ' 认领失败 ' . $e->getMessage(), 'batch_claim');
                }
            }
            echo '执行商品认领完成！本次成功商品数' . $success_i . "<br/>";
            exit;
        } else {
            $platform = [];
            $goods_platform = GoodsService::$own_platform_type;

            $where = [];
            $where['platform_type'] = $this->platform_type;
            if (!AccessService::hasAllShop()) {
                $shop_id = Yii::$app->user->identity->shop_id;
                $shop_id = explode(',', $shop_id);
                $where['id'] = $shop_id;
            }
            $where['status'] = [Shop::STATUS_VALID,Shop::STATUS_PAUSE];
            $shop = Shop::find()->where($where)->select(['id', 'platform_type', 'name', 'country_site'])
                ->asArray()->all();

            foreach ($goods_platform as $k => $v) {
                //$shop = ArrayHelper::map($shop, 'id', 'name');
                $shop_lists = [];
                foreach ($shop as $shop_v) {
                    if ($shop_v['platform_type'] != $k) {
                        continue;
                    }

                    if (!empty($shop_v['country_site'])) {
                        $country_site = explode(',', $shop_v['country_site']);
                        foreach ($country_site as $size) {
                            $shop_lists[$shop_v['id'] . '_' . $size] = $shop_v['name'] . CountryService::getName($size, false);
                        }
                    } else {
                        $shop_lists[$shop_v['id']] = $shop_v['name'];
                    }
                }

                if (empty($shop_lists)) {
                    continue;
                }
                $platform[$k] = [
                    'name' => $v,
                    'shop' => $shop_lists
                ];
            }
            return $this->render('/goods/base/batch_claim', ['platform' => $platform, 'id' => $id, 'platform_type' => $this->platform_type]);
        }
    }


    public function overseas_query($type = 'select')
    {
        $column = 'gsow.*,mg.*,mg.id as mg_id,g.category_id,g.sku_no,g.goods_img,gs.id,gs.country_code,gs.shop_id,gs.original_price,gs.discount,gs.price,gs.admin_id,gs.add_time,mg.goods_content,mg.status,gs.status as gs_status,gs.ean,g.size,g.weight,g.real_weight,g.colour as gcolour,gs.platform_sku_no,gs.platform_goods_opc,gs.cgoods_no,gs.keywords_index,gs.platform_goods_id,st.total_sales,gs.platform_goods_opc';
        $query = GoodsShopOverseasWarehouse::find()
            ->alias('gsow')->select($column);
        if ($type != 'count' || in_array('gs', $this->join)) {
            $query->leftJoin(GoodsShop::tableName() . ' gs', 'gs.id = gsow.goods_shop_id');
        }
        $query->leftJoin(GoodsShopSalesTotal::tableName() . ' st', 'gs.id = st.goods_shop_id');
        if ($type != 'count' || in_array('mg', $this->join)) {
            $query->leftJoin($this->model()->tableName() . ' mg', 'gs.goods_no= mg.goods_no');
        }
        if ($type != 'count' || in_array('g', $this->join)) {
            $query->leftJoin(Goods::tableName() . ' g', 'gs.goods_no = g.goods_no');
        }
        return $query;
    }

    /**
     * 海外仓商品列表
     * @param $where
     * @param string $sort
     * @return array
     */
    protected function overseas_lists($where, $sort = 'id DESC')
    {
        $page = Yii::$app->request->get('page');
        if (empty($page)) {
            $page = Yii::$app->request->post('page', 1);
        }
        $pageSize = Yii::$app->request->get('limit');
        if (empty($pageSize)) {
            $pageSize = Yii::$app->request->post('limit', 20);
        }
        $model = $this->model();
        if (!($model instanceof BaseAR)) {
            return [];
        }

        $query = $this->overseas_query();
        $list = $model::getListByCond($where, $page, $pageSize, $sort, null, $query);
        if (count($list) < $pageSize && $page == 1) {
            $count = count($list);
        } else {
            if ($this->cache_count) {
                $count = $model::getCacheCountByCond($where, $this->overseas_query('count'), __CLASS__ . __FUNCTION__);
            } else {
                $count = $model::getCountByCond($where, $this->overseas_query('count'));
            }
        }
        $pages = new Pagination(['totalCount' => $count, 'pageSize' => $pageSize]);
        $list = $this->formatLists($list);

        return [
            'list' => $list,
            'pages' => $pages,
        ];
    }

    /**
     * @routeName 海外仓商品主页
     * @routeDescription 海外仓商品主页
     */
    public function actionOverseasIndex()
    {
        $req = Yii::$app->request;
        $sku_no = $req->get('sku_no','');
        $category_arr = [];
        $country_arr = [];
        if ($this->has_country) {
            $shop = Shop::find()->where(['platform_type' => $this->platform_type])->asArray()->all();
            foreach ($shop as $v) {
                if (empty($v['country_site'])) {
                    continue;
                }
                $country = explode(',', $v['country_site']);
                $country_arr = array_merge($country_arr, $country);
                $country_arr = array_unique($country_arr);
                $country_arr = CountryService::getSelectOption(['country_code' => $country_arr]);
            }
        }
        $data = [
            'category_arr' => $category_arr,
            'country_arr' => $country_arr,
            'platform_type' => $this->platform_type,
            'sku_no' => $sku_no
        ];
        try {
            return $this->render($this->render_view . 'overseas_index', $data);
        } catch (ViewNotFoundException $e) {
            return $this->render('/goods/base/overseas_index', $data);
        }
    }

    /**
     * @routeName 海外仓商品列表
     * @routeDescription 海外仓商品列表
     */
    public function actionOverseasList()
    {
        $req = Yii::$app->request;
        $post = Yii::$app->request->post();
        $sku_no = $req->get('sku_no','');
        if (!empty($sku_no)) {
            $post['BaseGoodsSearch']['sku_no'] = $sku_no;
        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        $searchModel = new BaseGoodsSearch();
        $where = $searchModel->overseas_search($post, $this->platform_type);
        $this->join = $where['_join'];
        unset($where['_join']);
        $has_stock = '';
        if (isset($where['has_stock'])) {
            $has_stock = $where['has_stock'];
            unset($where['has_stock']);
        }
        $data = $this->overseas_lists($where, 'gsow.id desc');
        $lists = [];
        $shop_map = \common\services\ShopService::getShopMap();
        $cgoods_nos = ArrayHelper::getColumn($data['list'], 'cgoods_no');
        $goods_childs = GoodsChild::find()->where(['cgoods_no' => $cgoods_nos])->indexBy('cgoods_no')->asArray()->all();

        $goods_nos = '';
        $goods_languages = [];
        $goods_informations = [];
        if (in_array($this->platform_type,[Base::PLATFORM_ALLEGRO,Base::PLATFORM_OZON])) {
            $goods_nos = ArrayHelper::getColumn($data['list'],'goods_no');
            $language = $this->platform_type == Base::PLATFORM_ALLEGRO ? 'pl' : 'ru';
            $goods_languages = GoodsLanguage::find()->where(['goods_no' => $goods_nos, 'language' => $language])->indexBy('goods_no')->asArray()->all();
            $goods_informations = PlatformInformation::find()->where(['goods_no' => $goods_nos, 'platform_type' => $this->platform_type])->indexBy('goods_no')->asArray()->all();
        }
        $warehouse_map = WarehouseService::getWarehouseMap();

        foreach ($data['list'] as $key => $v) {
            $goods_child = empty($goods_childs[$v['cgoods_no']]) ? [] : $goods_childs[$v['cgoods_no']];
            $info = $v;;
            if (empty($goods_child['goods_img'])) {
                $image = json_decode($v['goods_img'], true);
                $image = empty($image) || !is_array($image) ? '' : current($image)['img'];
            } else {
                $image = $goods_child['goods_img'];
            }
            if (!empty($goods_child['sku_no'])) {
                $info['sku_no'] = $goods_child['sku_no'];
            }
            if (!empty($goods_child['colour'])) {
                $info['colour'] = $goods_child['colour'];
            }
            $info['shop_name'] = empty($v['shop_id']) ? '' : $shop_map[$v['shop_id']];
            $info['image'] = $image;
            $info['add_time'] = date('Y-m-d H:i', $v['add_time']);
            $user = User::getInfo($info['admin_id']);
            $info['admin_name'] = empty($user['nickname']) ? '' : $user['nickname'];
            $info['category_name'] = Category::getCategoryName($v['category_id']);
            if (!empty($v['status'])) {
                $info['status_desc'] = GoodsService::$platform_goods_status_map[$v['status']];
            }
            $info['country'] = '';
            if ($this->has_country) {
                $info['country'] = CountryService::getName($v['country_code']);
            }
            $info['start_logistics_cost'] = $v['start_logistics_cost'] > 0? $v['start_logistics_cost']: $v['estimated_start_logistics_cost'];
            $info['end_logistics_cost'] = $v['end_logistics_cost'] > 0? $v['end_logistics_cost']: $v['estimated_end_logistics_cost'];
            $info['inventory_quantity'] = $info['goods_stock'];
            $info['transit_quantity'] = BlContainerGoods::find()->where(['warehouse_id'=>$v['warehouse_id'],'cgoods_no'=>$v['cgoods_no'],'status'=>BlContainer::STATUS_NOT_DELIVERED])->select('sum(num) as num')->scalar();
            if (!empty($has_stock)) {
                $info['inventory_quantity'] = GoodsStockDetails::find()->alias('gsd')->where(['warehouse'=>$v['warehouse_id'],'cgoods_no'=>$v['cgoods_no']])
                    ->andWhere($has_stock)->andWhere(['gsd.status' => 2])->andWhere(['=','gsd.outgoing_time',0])->count();
            }
            $info['weight'] = $info['real_weight'] == 0 ? $info['weight'] : $info['real_weight'];
            $size = GoodsService::getSizeArr($info['size']);
            $square = empty($info['size']) ? '' : round($size['size_l'] * $size['size_w'] * $size['size_h'],2);
            $info['square_l'] = empty($square) ? '' : round($square / 1000,2);
            $info['square_m'] = empty($square) ? '' : round($square / 1000000,4);
            $info['classify'] = empty($square) ? '' : $this->getClassify($info['square_l']);
            $goods_shipment_where = [OverseasGoodsShipment::STATUS_FINISH,OverseasGoodsShipment::STATUS_CANCELLED];
            $info['purchasing'] = OverseasGoodsShipment::find()->where(['cgoods_no' => $v['cgoods_no'],'warehouse_id' => $v['warehouse_id']])
                ->andWhere(['not in', 'status', $goods_shipment_where])
                ->select('sum(num) as purchase_num')->scalar();
            if (in_array($info['platform_type'],[Base::PLATFORM_ALLEGRO,Base::PLATFORM_OZON])) {
                $goods_language = empty($goods_languages[$v['goods_no']])?[]:$goods_languages[$v['goods_no']];
                $info['language_id'] = false;
                if (!empty($goods_language)) {
                    $info['language_id'] = $goods_language['id'];
                    $info['is_editor'] = '2';
                    $goods_information = empty($goods_informations[$v['goods_no']])?[]:$goods_informations[$v['goods_no']];
                    if (!empty($goods_information)) {
                        $info['is_editor'] = empty($goods_information['editor_value']) || $goods_information['editor_value'] == '[]' ? '2' : '1';
                    }
                }
            }
            $info['warehouse_name'] = empty($warehouse_map[$info['warehouse_id']]) ? '' : $warehouse_map[$info['warehouse_id']];
            $lists[] = $info;
        }
        return $this->FormatLayerTable(
            self::REQUEST_LAY_SUCCESS, '获取成功',
            $lists, $data['pages']->totalCount
        );
    }

    /**
     * 获取分类
     * @param $square_l
     * @return string
     */
    public function getClassify($square_l) {
        if ($square_l <= 0.3) {
            return 'A';
        } elseif ($square_l > 0.3 && $square_l <= 1) {
            return 'B';
        } elseif ($square_l > 1 && $square_l <= 3) {
            return 'C';
        } elseif ($square_l > 3 && $square_l <= 15) {
            return 'D';
        } elseif ($square_l > 15 && $square_l <= 31) {
            return 'E';
        } elseif ($square_l > 31 && $square_l <= 76.8) {
            return 'F';
        } else {
            return '';
        }
    }

    /**
     * @routeName 已移入广告列表
     * @routeDescription 已移入广告列表
     */
    public function actionAdIndex()
    {
        $category_arr = [];
        $country_arr = [];
        if ($this->has_country) {
            $shop = Shop::find()->where(['platform_type' => $this->platform_type])->asArray()->all();
            foreach ($shop as $v) {
                if (empty($v['country_site'])) {
                    continue;
                }
                $country = explode(',', $v['country_site']);
                $country_arr = array_merge($country_arr, $country);
                $country_arr = array_unique($country_arr);
                $country_arr = CountryService::getSelectOption(['country_code' => $country_arr]);
            }
        }
        $data = [
            'category_arr' => $category_arr,
            'country_arr' => $country_arr,
            'platform_type' => $this->platform_type
        ];
        try {
            return $this->render($this->render_view . 'ad_index', $data);
        } catch (ViewNotFoundException $e) {
            return $this->render('/goods/base/ad_index', $data);
        }
    }
    /**
     * @routeName 移入广告列表
     * @routeDescription 移入广告列表
     */
    public function actionAddAd(){
        $req = Yii::$app->request;
        $id = $req->get('id');
        $item = GoodsShop::findOne($id);
        $item->ad_status = 2;
        if ($item->save()) {
            Yii::$app->response->format=Response::FORMAT_JSON;
            return $this->FormatArray(self::REQUEST_SUCCESS, "移入成功", []);
        } else {
            Yii::$app->response->format=Response::FORMAT_JSON;
            return $this->FormatArray(self::REQUEST_FAIL, "移入失败", []);
        }
    }
    /**
     * @routeName 批量移入广告列表
     * @routeDescription 批量移入广告列表
     */
    public function actionAddAds(){
        $req = Yii::$app->request;
        $ids = $req->post('id');
        foreach ($ids as $id){
            $item = GoodsShop::findOne($id);
            $item->ad_status = 2;
            $item->save();
        }
        Yii::$app->response->format=Response::FORMAT_JSON;
        return $this->FormatArray(self::REQUEST_SUCCESS, "移入成功", []);
    }

    /**
     * @routeName 移出广告列表
     * @routeDescription 移出广告列表
     */
    public function actionDeleteAd(){
        $req = Yii::$app->request;
        $id = $req->get('id');
        $item = GoodsShop::findOne($id);
        $item->ad_status = 1;
        if ($item->save()) {
            Yii::$app->response->format=Response::FORMAT_JSON;
            return $this->FormatArray(self::REQUEST_SUCCESS, "移除成功", []);
        } else {
            Yii::$app->response->format=Response::FORMAT_JSON;
            return $this->FormatArray(self::REQUEST_FAIL, "移除失败", []);
        }
    }

}