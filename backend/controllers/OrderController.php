<?php

namespace backend\controllers;

use backend\models\search\OrderSearch;
use common\components\CommonUtil;
use common\components\HelperStamp;
use common\components\statics\Base;
use common\extensions\google\PyTranslate;
use common\models\Category;
use common\models\FinancialPeriodRollover;
use common\models\FreightPriceLog;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsStock;
use common\models\GoodsSource;
use common\models\Order;
use common\models\order\OrderTransport;
use common\models\order\OrderTransportFeeDetail;
use common\models\OrderEvent;
use common\models\OrderGoods;
use common\models\OrderRecommended;
use common\models\order\OrderRefund;
use common\models\OrderSettlement;
use common\models\OrderStockOccupy;
use common\models\purchase\PurchaseOrderGoods;
use common\models\Shop;
use common\models\sys\Country;
use common\models\sys\ShippingMethod;
use common\models\TransportProviders;
use common\models\warehousing\WarehouseProvider;
use common\services\api\OrderEventService;
use common\services\FApiService;
use common\services\FTransportService;
use common\services\goods\GoodsService;
use common\services\ImportResultService;
use common\services\order\OrderAbnormalService;
use common\services\order\OrderDeclareService;
use common\services\order\OrderGoodsService;
use common\services\order\OrderService;
use common\services\purchase\PurchaseProposalService;
use common\services\sys\CountryService;
use common\services\sys\ExchangeRateService;
use common\services\sys\SystemOperlogService;
use common\services\transport\BaseTransportService;
use common\services\transport\TransportService;
use common\services\warehousing\WarehouseService;
use moonland\phpexcel\Excel;
use Yii;
use common\base\BaseController;
use yii\data\Pagination;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use common\models\sys\SystemOperlog;

class OrderController extends BaseController
{

    private $list_type = GoodsService::SOURCE_METHOD_OWN;
    private $list_tag = 10;
    private $list_warehouse = GoodsService::OWN_WAREHOUSE;

    /**
     * 获取条件订单数
     */
    public static function getOrderCount($tag = [],$warehouse_list = []){
        $searchModel = new OrderSearch();
        $count = [];
        foreach ($tag as $v){
            $where = $searchModel->ownSearch(null,$v,0);
            if (!empty($warehouse_list)) {
                $where['warehouse'] = $warehouse_list;
            }
            $count[$v] = Order::getCountByCond($where);
        }
        return $count;
    }

    /**
     * @routeName 亚马逊订单管理
     * @routeDescription 亚马逊订单管理
     */
    public function actionIndex()
    {
        $this->list_type = GoodsService::SOURCE_METHOD_AMAZON;
        $req = Yii::$app->request;
        $tag = $req->get('tag',10);
        $this->list_tag = $tag;
        $searchModel=new OrderSearch();
        $where=$searchModel->search(Yii::$app->request->queryParams,$tag);
        $data = $this->lists($where,'date desc');

        return $this->render('index', [
            'searchModel' => $searchModel,
            'list' => $data['list'],
            'pages' => $data['pages'],
            'tag'=>$tag,
        ]);
    }

    /**
     * @routeName 订单管理
     * @routeDescription 订单管理
     */
    public function actionOwnIndex()
    {
        $this->list_type = GoodsService::SOURCE_METHOD_OWN;
        $req = Yii::$app->request;
        $warehouse = array_keys(WarehouseService::$warehouse_map);
        $order_count = $this->getOrderCount([1,2,3,4,5,6,9,15],$warehouse);
        $tag = $req->get('tag',10);
        $search = $req->get('search', 0);
        $this->list_tag = $tag;
        $searchModel=new OrderSearch();
        $query_params = Yii::$app->request->queryParams;
        $country_arr = [];
        $logistics_channels_ids = [];
        $recommended_logistics_channels_ids = [];
        $data = [
            'list' => [],
            'pages' => new Pagination(['totalCount' => 0, 'pageSize' => 20])
        ];
        if(!empty($query_params['OrderSearch']) || $tag != 10) {
            $query_params['OrderSearch']['warehouse_list'] = $warehouse;
            $where = $searchModel->ownSearch($query_params, $tag, $search);
            $sort = $tag == 2 ? 'order_income_price desc' : 'date desc';
            if (in_array($tag, [6, 11])) {
                $sort = 'delivery_time desc';
            }
            if (in_array($tag, [7, 12])) {
                $sort = 'cancel_time desc';
            }
            if ($tag == 15) {
                $sort = 'remaining_shipping_time asc';
            }
            //按销售单号或物流单号指定排序
            $sort_data = [];
            if(!empty($searchModel->relation_no)) {
                $sort_data = explode(PHP_EOL, $searchModel->relation_no);
                if($sort_data > 1){
                    $sort_field = 'relation_no';
                }
            } else {
                if(!empty($searchModel->track_no)) {
                    $sort_data = explode(PHP_EOL, $searchModel->track_no);
                    if($sort_data > 1) {
                        $sort_field = 'track_no';
                    }
                }
            }
            if(!empty($sort_field)) {
                foreach ($sort_data as &$sort_v) {
                    $sort_v = "'" . trim($sort_v) . "'";
                }
                $sort = new Expression('field (' . $sort_field . ',' . implode(',', $sort_data) . ')');
            }

            $data = $this->lists($where, $sort, null, (in_array($tag, [1, 2, 3, 4, 5, 9]) ? 100 : 20));

            $logistics_channels_ids = TransportService::getShippingMethodOptions();
            $recommended_logistics_channels_ids = TransportService::getShippingMethodOptions(true);

            foreach ($data['list'] as &$value){
                if ($value['order_status'] == Order::ORDER_STATUS_REFUND){
                    $refund = OrderRefund::find()->where(['order_id'=>$value['order_id']])->asArray()->one();
                    $value['order_refund_reason'] = $refund['refund_reason'];
                    $value['order_refund_remarks'] = $refund['refund_remarks'];
                    $value['order_refund_type'] = $refund['refund_type'];
                    $value['order_refund_num'] = $refund['refund_num'];
                }
            }
            unset($where['country']);
            if (empty($searchModel->relation_no)) {
                $country_cut = Order::dealWhere($where, 'country,count(*) as cut')->cache(7200)->groupBy('country')->asArray()->all();
                $country_code = ArrayHelper::getColumn($country_cut, 'country');
                $country_cut = ArrayHelper::map($country_cut, 'country', 'cut');
                $country_lists = Country::find()->where(['country_code' => $country_code])->cache(86400)->asArray()->all();
                foreach ($country_lists as $v) {
                    $cut = empty($country_cut[$v['country_code']]) ? 0 : $country_cut[$v['country_code']];
                    $country_arr[$v['country_code']] = $v['country_zh'] . '「' . $v['country_code'] . '」（' . $cut . '）';
                }
            }
        }

        return $this->render('own/index', [
            'searchModel' => $searchModel,
            'list' => $data['list'],
            'pages' => $data['pages'],
            'tag'=>$tag,
            'logistics_channels_ids'=>$logistics_channels_ids,
            'recommended_logistics_channels_ids'=>$recommended_logistics_channels_ids,
            'country_arr' => $country_arr,
            'order_count' => $order_count,
        ]);
    }

    /**
     * @routeName 订单未结算列表
     * @routeDescription 订单未结算列表
     */
    public function actionNotSettledIndex()
    {
        $this->list_type = GoodsService::SOURCE_METHOD_OWN;
        $req = Yii::$app->request;
        $tag = $req->get('tag',14);
        $this->list_tag = $tag;
        $searchModel=new OrderSearch();
        $query_params = Yii::$app->request->queryParams;
        $country_arr = [];
        $query_params['OrderSearch']['settlement_status'] = [0,1];
        $where = $searchModel->ownSearch($query_params, $tag);
        $data = $this->lists($where);
        $order_statistics = [];
        if(!empty($searchModel->source) || !empty($searchModel->shop_id)) {
            $order_statistics = Order::dealWhere($where,'currency,sum(order_income_price - platform_fee) as order_income_price,sum(freight_price) as freight_price,sum(order_cost_price) as order_cost_price')->groupBy('currency')->asArray()->all();
        }
        $logistics_channels_ids = TransportService::getShippingMethodOptions();
        $recommended_logistics_channels_ids = TransportService::getShippingMethodOptions(true);
        unset($where['country']);
        if (empty($searchModel->relation_no)) {
            $country_cut = Order::dealWhere($where, 'country,count(*) as cut')->cache(7200)->groupBy('country')->asArray()->all();
            $country_code = ArrayHelper::getColumn($country_cut, 'country');
            $country_cut = ArrayHelper::map($country_cut, 'country', 'cut');
            $country_lists = Country::find()->where(['country_code' => $country_code])->cache(86400)->asArray()->all();
            foreach ($country_lists as $v) {
                $cut = empty($country_cut[$v['country_code']]) ? 0 : $country_cut[$v['country_code']];
                $country_arr[$v['country_code']] = $v['country_zh'] . '「' . $v['country_code'] . '」（' . $cut . '）';
            }
        }

        return $this->render('not_settled_index', [
            'searchModel' => $searchModel,
            'list' => $data['list'],
            'pages' => $data['pages'],
            'tag'=>$tag,
            'logistics_channels_ids'=>$logistics_channels_ids,
            'recommended_logistics_channels_ids'=>$recommended_logistics_channels_ids,
            'country_arr' => $country_arr,
            'order_statistics' =>$order_statistics
        ]);
    }

    /**
     * @routeName 海外仓订单
     * @routeDescription 海外仓订单
     */
    public function actionOverseasIndex()
    {
        $this->list_type = GoodsService::SOURCE_METHOD_OWN;
        $this->list_warehouse = GoodsService::OVERSEA_WAREHOUSE;//海外仓
        $req = Yii::$app->request;
        $tag = $req->get('tag',10);
        $search = $req->get('search', 0);
        $this->list_tag = $tag;
        $searchModel=new OrderSearch();
        $query_params = Yii::$app->request->queryParams;
        $warehouse_map = WarehouseService::getOverseasWarehouse();
        $warehouse_overseas_list = array_keys($warehouse_map);
        $order_count = $this->getOrderCount([1,2,5,6],$warehouse_overseas_list);
        $country_arr = [];
        $logistics_channels_ids = [];
        $recommended_logistics_channels_ids = [];
        $data = [
            'list' => [],
            'pages' => new Pagination(['totalCount' => 0, 'pageSize' => 20])
        ];
        if(!empty($query_params['OrderSearch']) || $tag != 10) {
            $query_params['OrderSearch']['warehouse_list'] = $warehouse_overseas_list;
            $where = $searchModel->ownSearch($query_params, $tag, $search);
            $sort = $tag == 2 ? 'order_income_price desc' : 'date desc';
            if (in_array($tag, [6, 11])) {
                $sort = 'delivery_time desc';
            }
            if (in_array($tag, [7, 12])) {
                $sort = 'cancel_time desc';
            }
            if ($tag == 15) {
                $sort = 'remaining_shipping_time asc';
            }
            //按销售单号或物流单号指定排序
            $sort_data = [];
            if(!empty($searchModel->relation_no)) {
                $sort_data = explode(PHP_EOL, $searchModel->relation_no);
                if($sort_data > 1){
                    $sort_field = 'relation_no';
                }
            } else {
                if(!empty($searchModel->track_no)) {
                    $sort_data = explode(PHP_EOL, $searchModel->track_no);
                    if($sort_data > 1) {
                        $sort_field = 'track_no';
                    }
                }
            }
            if(!empty($sort_field)) {
                foreach ($sort_data as &$sort_v) {
                    $sort_v = "'" . trim($sort_v) . "'";
                }
                $sort = new Expression('field (' . $sort_field . ',' . implode(',', $sort_data) . ')');
            }

            $data = $this->lists($where, $sort, null, (in_array($tag, [1, 2, 4, 5, 9]) ? 100 : 20));

            $logistics_channels_ids = TransportService::getShippingMethodOptions(false,true);
            $recommended_logistics_channels_ids = TransportService::getShippingMethodOptions(true,true);

            foreach ($data['list'] as &$value){
                if ($value['order_status'] == Order::ORDER_STATUS_REFUND){
                    $refund = OrderRefund::find()->where(['order_id'=>$value['order_id']])->asArray()->one();
                    $value['order_refund_reason'] = $refund['refund_reason'];
                    $value['order_refund_remarks'] = $refund['refund_remarks'];
                    $value['order_refund_type'] = $refund['refund_type'];
                    $value['order_refund_num'] = $refund['refund_num'];
                }
            }
            unset($where['country']);
            if (empty($searchModel->relation_no)) {
                $country_cut = Order::dealWhere($where, 'country,count(*) as cut')->cache(7200)->groupBy('country')->asArray()->all();
                $country_code = ArrayHelper::getColumn($country_cut, 'country');
                $country_cut = ArrayHelper::map($country_cut, 'country', 'cut');
                $country_lists = Country::find()->where(['country_code' => $country_code])->cache(86400)->asArray()->all();
                foreach ($country_lists as $v) {
                    $cut = empty($country_cut[$v['country_code']]) ? 0 : $country_cut[$v['country_code']];
                    $country_arr[$v['country_code']] = $v['country_zh'] . '「' . $v['country_code'] . '」（' . $cut . '）';
                }
            }
        }

        return $this->render('overseas_index', [
            'searchModel' => $searchModel,
            'list' => $data['list'],
            'pages' => $data['pages'],
            'tag'=>$tag,
            'logistics_channels_ids'=>$logistics_channels_ids,
            'recommended_logistics_channels_ids'=>$recommended_logistics_channels_ids,
            'country_arr' => $country_arr,
            'order_count' => $order_count,
            'overseas_list' => $warehouse_map
        ]);
    }


    /**
     * @routeName 批量打印拣货单
     * @routeDescription 批量打印拣货单
     * @return array |Response|string
     * @throws \yii\base\Exception
     */
    public function actionBatchPickingPrinted()
    {
        $req = Yii::$app->request;
        $order_id = $req->get('order_id');
        if(empty($order_id)){
            Yii::$app->response->format = Response::FORMAT_JSON;
            return $this->FormatArray(self::REQUEST_FAIL, "请选择订单", []);
        }

        $order_id = explode(',',$order_id);
        $order_ids = array_filter(array_unique($order_id));

        $order_goods = OrderGoods::find()
            ->where(['order_id' => $order_id])->andWhere(['!=', 'goods_status', 20])
            ->select('cgoods_no,order_id,goods_num')
            ->asArray()->all();

        $cgoods_nos = ArrayHelper::getColumn($order_goods,'cgoods_no');
        $gooods_stock = GoodsStock::find()->where(['cgoods_no'=>$cgoods_nos,'warehouse'=> WarehouseService::WAREHOUSE_OWN])->orderBy('shelves_no asc')->all();
        $gooods_stock = ArrayHelper::index($gooods_stock,'cgoods_no');

        $cgoods_order = [];
        $cgoods_nums = [];
        foreach ($order_goods as $v) {
            if (empty($cgoods_nums[$v['cgoods_no']])) {
                $cgoods_nums[$v['cgoods_no']] = $v['goods_num'];
            } else {
                $cgoods_nums[$v['cgoods_no']] += $v['goods_num'];
            }
            $cgoods_order[$v['cgoods_no']][] = $v['order_id'];
        }

        $order_ids = [];
        /*arsort($cgoods_nums);
        foreach ($cgoods_nums as $cgoods_nums_k=>$cgoods_nums_v) {
            foreach ($cgoods_order[$cgoods_nums_k] as $order_v) {
                $order_ids[$order_v] = $order_v;
            }
        }*/
        foreach ($gooods_stock as $cgoods_nums_k=>$cgoods_nums_v) {
            foreach ($cgoods_order[$cgoods_nums_k] as $order_v) {
                $order_ids[$order_v] = $order_v;
            }
        }
        $order_ids = array_values($order_ids);

        $page_num = 10;
        $lists = [];
        do {
            $order_id = array_slice($order_ids, 0, $page_num);
            $order_ids = array_slice($order_ids, $page_num);

            $sort_field = 'og.order_id';
            $sort_data = [];
            foreach ($order_id as $sort_v) {
                $sort_data[] = "'" . trim($sort_v) . "'";
            }
            $sort = new Expression('field (' . $sort_field . ',' . implode(',', $sort_data) . ')');
            $order_lists = Order::find()->where(['order_id' => $order_id])->indexBy('order_id')->asArray()->all();
            $i = 0;
            foreach ($order_lists as $k=>$v) {
                $i++;
                $order_lists[$k]['index'] = $i;
            }

            $order_goods = OrderGoods::find()->alias('og')
                ->where(['order_id' => $order_id])->andWhere(['!=', 'og.goods_status', 20])
                ->select('og.cgoods_no,og.order_id,og.platform_asin,og.goods_num')->orderBy($sort)
                ->asArray()->all();
            $cgoods_nos = ArrayHelper::getColumn($order_goods, 'cgoods_no');

            //货架
            $goods_stocks = GoodsStock::find()
                ->where(['cgoods_no'=>$cgoods_nos,'warehouse'=> WarehouseService::WAREHOUSE_OWN])
                ->indexBy('cgoods_no')->asArray()->all();

            //颜色 图片
            $goods_childs = GoodsChild::find()->alias('gc')
                ->leftJoin(Goods::tableName() . ' g', 'g.goods_no=gc.goods_no')
                ->select('gc.cgoods_no,g.goods_type,g.goods_img,g.colour,gc.colour as ccolour,gc.goods_img as cgoods_img')
                ->where(['gc.cgoods_no' => $cgoods_nos])->asArray()->all();
            $goods_childs_lists = [];
            foreach ($goods_childs as $v) {
                if ($v['goods_type'] == Goods::GOODS_TYPE_MULTI) {
                    $goods_img = $v['cgoods_img'];
                    $colour = $v['ccolour'];
                } else {
                    $image = json_decode($v['goods_img'], true);
                    $goods_img = empty($image) || !is_array($image) ? '' : current($image)['img'];
                    $colour = $v['colour'];
                }
                $goods_childs_lists[$v['cgoods_no']] = [
                    'goods_img' => $goods_img,
                    'colour' => $colour,
                ];
            }

            $order_goods_lists = [];
            foreach ($order_goods as $v) {
                $index = $order_lists[$v['order_id']]['index'];
                $track_no = $order_lists[$v['order_id']]['track_no'];
                if (empty($order_goods_lists[$v['platform_asin']])) {
                    $goods_img = '';
                    $colour = '';
                    $shelves_no = empty($goods_stocks[$v['cgoods_no']])?'':$goods_stocks[$v['cgoods_no']]['shelves_no'];
                    if(!empty($v['cgoods_no']) && !empty($goods_childs_lists[$v['cgoods_no']])){
                        $goods_img = $goods_childs_lists[$v['cgoods_no']]['goods_img'];
                        $colour = $goods_childs_lists[$v['cgoods_no']]['colour'];
                    }
                    $order_goods_lists[$v['platform_asin']] = [
                        'cgoods_no' => $v['cgoods_no'],
                        'sku_no' => $v['platform_asin'],
                        'shelves_no' => $shelves_no,
                        'goods_num' => $v['goods_num'],
                        'goods_img' => $goods_img,
                        'colour' => empty(GoodsService::$colour_map[$colour])?$colour:GoodsService::$colour_map[$colour],
                        'index' => [['track_no' => $track_no ,'index' => $index ,'num'=> $v['goods_num']]],
                    ];
                } else {
                    $order_goods_lists[$v['platform_asin']]['goods_num'] += $v['goods_num'];
                    $order_goods_lists[$v['platform_asin']]['index'][] = ['track_no' => $track_no ,'index' => $index ,'num'=> $v['goods_num']];
                }
            }
            $lists[] = [
                'order_goods' => $order_goods_lists,
                'order' => $order_lists,
            ];
        } while (count($order_ids) > 0);

        return $this->render('picking_printed', [
            'lists' => $lists,
        ]);
    }

    /**
     * @routeName 订单出库管理
     * @routeDescription 订单出库管理
     */
    public function actionWarehousingIndex()
    {
        $this->list_type = GoodsService::SOURCE_METHOD_OWN;
        $req = Yii::$app->request;
        $tag = $req->get('tag',10);
        $last_relation_track_no = $req->get('relation_track_no');
        $relation_track_no = null;
        $this->list_tag = $tag;
        $searchModel=new OrderSearch();
        $query_params = Yii::$app->request->queryParams;
        if(empty($query_params['OrderSearch']['relation_track_no'])){
            $query_params['OrderSearch']['relation_track_no'] = '-1';
        }else{
            $relation_track_no = $query_params['OrderSearch']['relation_track_no'].PHP_EOL.$last_relation_track_no;
            $tracks = explode(PHP_EOL,$relation_track_no);
            if (in_array('',$tracks)){
                $i = array_search('',$tracks);
                unset($tracks[$i]);
            }
            if (count($tracks) > 20){
                array_pop($tracks);
                $relation_track_no = implode(PHP_EOL,$tracks);
            }
            $query_params['OrderSearch']['relation_track_no'] = $relation_track_no;
        }
        $country_arr = [];
        $logistics_channels_ids = [];
        $recommended_logistics_channels_ids = [];
        $data = [
            'list' => [],
            'pages' => new Pagination(['totalCount' => 0, 'pageSize' => 20])
        ];
        if(!empty($query_params['OrderSearch']) || $tag != 10) {
            $where = $searchModel->ownSearch($query_params, $tag);
            $sort = $tag == 2 ? 'order_income_price desc' : 'delivery_time desc';
            if (in_array($tag, [6, 11])) {
                $sort = 'delivery_time desc';
            }
            if (in_array($tag, [7, 12])) {
                $sort = 'cancel_time desc';
            }
            
            $data = $this->lists($where, $sort, null, (in_array($tag, [1, 2, 3, 4, 5, 9]) ? 100 : 20));

            $logistics_channels_ids = TransportService::getShippingMethodOptions();
            $recommended_logistics_channels_ids = TransportService::getShippingMethodOptions(true);

            unset($where['country']);
            if (empty($searchModel->relation_no)) {
                $country_cut = Order::dealWhere($where, 'country,count(*) as cut')->cache(7200)->groupBy('country')->asArray()->all();
                $country_code = ArrayHelper::getColumn($country_cut, 'country');
                $country_cut = ArrayHelper::map($country_cut, 'country', 'cut');
                $country_lists = Country::find()->where(['country_code' => $country_code])->cache(86400)->asArray()->all();
                foreach ($country_lists as $v) {
                    $cut = empty($country_cut[$v['country_code']]) ? 0 : $country_cut[$v['country_code']];
                    $country_arr[$v['country_code']] = $v['country_zh'] . '「' . $v['country_code'] . '」（' . $cut . '）';
                }
            }
        }

        return $this->render('own/warehousing_index', [
            'searchModel' => $searchModel,
            'list' => $data['list'],
            'pages' => $data['pages'],
            'tag'=>$tag,
            'logistics_channels_ids'=>$logistics_channels_ids,
            'recommended_logistics_channels_ids'=>$recommended_logistics_channels_ids,
            'country_arr' => $country_arr,
            'relation_track_no' => $relation_track_no,
        ]);
    }

    /**
     * @routeName 扫描发货
     * @routeDescription 扫描发货
     * @throws
     */
    public function actionScanShip()
    {
        $req = Yii::$app->request;
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $data = $req->post();
            try {
                $force = $data['force'] == 1?true:false;
                if (empty($data['order_id'])) {
                    return $this->FormatArray(self::REQUEST_FAIL, "订单不能为空", []);
                }
                $result = false;
                $order_ids = $data['order_id'];
                $error_msg = '';
                foreach ($order_ids as $order_id) {
                    if (empty($order_id)) {
                        continue;
                    }

                    $weight = 0;
                    $size = '';
                    if (!empty($data['size_l']) && !empty($data['size_w']) && !empty($data['size_h']) && isset($data['size_l'][$order_id]) && isset($data['size_w'][$order_id]) && isset($data['size_h'][$order_id])) {
                        $size = $data['size_l'][$order_id] . 'x' . $data['size_w'][$order_id] . 'x' . $data['size_h'][$order_id];
                    }
                    if (!empty($data['weight']) && $data['weight'][$order_id] > 0) {
                        $weight = $data['weight'][$order_id];
                    }
                    try {
                        $result = (new OrderService())->ship($order_id, $weight, $size, $force);
                        (new SystemOperlogService())->setType(SystemOperlog::TYPE_UPDATE)
                            ->addOrderLog($order_id, time(), SystemOperlogService::ACTION_ORDER_SCAN_SHIP, null);
                    } catch (\Exception $e) {
                        if($e->getCode() == 3004) {
                            return $this->FormatArray(2, '提交失败' . $error_msg, []);
                        }
                        $error_msg = $e->getMessage() . "\n";
                    }
                }
                if ($result && empty($error_msg)) {
                    return $this->FormatArray(self::REQUEST_SUCCESS, "提交成功", []);
                } else {
                    return $this->FormatArray(self::REQUEST_FAIL, '提交失败' . $error_msg, []);
                }
            } catch (\Exception $e) {
                return $this->FormatArray(self::REQUEST_FAIL, $e->getMessage() . $e->getFile() . $e->getLine(), []);
            }
        } else {
            $where = [];
            $logistics_no = $req->get('logistics_no');
            if (!empty($logistics_no)) {
                $where = ['or',['track_no' => $logistics_no],['relation_no'=>$logistics_no]];
            }
            $order_id = $req->get('order_id');
            if (!empty($order_id)) {
                $where = ['order_id' => $order_id];
            }
            if (empty($where)) {
                return '参数异常';
            }
            $order = Order::find()->where($where)
                //->andWhere(['=', 'order_status', Order::ORDER_STATUS_WAIT_SHIP])
                ->asArray()->all();
            $order_result = [];
            foreach ($order as &$order_v) {
                /*if(in_array($order_v['shop_id'],[8, 10, 12, 13, 29, 30, 37])) {
                    return '暂时不出库';
                }*/

                if($order_v['order_status'] != Order::ORDER_STATUS_WAIT_SHIP){
                    continue;
                }

                $order_goods = OrderService::getOrderGoods($order_v['order_id']);
                $order_goods_lists = [];
                $is_other = true;
                if (count($order_goods) > 1) {
                    $is_other = false;
                }
                $size = [];
                $real_weight = 0;
                foreach ($order_goods as $order_good_v) {
                    $goods = Goods::find()->where(['goods_no' => $order_good_v['goods_no']])->one();
                    $num = GoodsStock::find()->where(['cgoods_no' => $order_good_v['cgoods_no'], 'warehouse' => $order_v['warehouse']])->select('num')->scalar();
                    $goods_child = GoodsChild::find()->where(['cgoods_no'=>$order_good_v['cgoods_no']])->one();
                    $order_good_v['goods_id'] = $goods['id'];
                    if(!empty($goods_child['goods_img'])) {
                        $image = $goods_child['goods_img'];
                    }else{
                        $image = json_decode($goods['goods_img'], true);
                        $image = empty($image) || !is_array($image) ? '' : current($image)['img'];
                    }
                    $order_good_v['goods_pic'] = $image;
                    $order_good_v['specification'] = $goods['specification'];
                    $order_good_v['ccolour'] = $goods_child['colour'];
                    $order_good_v['csize'] = $goods_child['size'];
                    $size = (new GoodsService())->getSizeArr($goods_child['package_size']);
                    $real_weight += ($goods_child['real_weight'] == 0 ? $goods_child['weight'] : $goods_child['real_weight']) * $order_good_v['goods_num'];
                    $order_good_v['goods_stock_num'] = $num;
                    $order_goods_lists[] = $order_good_v;
                    if ($order_good_v['goods_num'] > 1) {
                        $is_other = false;
                    }
                }
                $order_v['weight'] = $real_weight;
                if ($is_other) {
                    $order_v['size'] = $size;
                }
                $order_v['order_goods'] = $order_goods_lists;
                $order_result[] = $order_v;
            }
            $orders = current($order_result);
            $code = TransportProviders::getTransportCode($orders['logistics_channels_id'],$orders['order_id']);
            $transport = TransportProviders::find()->where(['transport_code'=>$code])->asArray()->one();
            return $this->render('scan_ship', [
                'order' => $order_result,
                'logistics_no' => $logistics_no,
                'transport' => $transport,
            ]);
        }
    }

    /**
     * 格式化列表
     * @param $list
     * @return array
     */
    protected function formatLists($list)
    {
        $shop_ids = ArrayHelper::getColumn($list,'shop_id');
        $shop = Shop::find()->select(['id','name','currency'])->where(['id'=>$shop_ids])->indexBy('id')->asArray()->all();

        $order_recommended_id = [];
        $order_ids = [];
        foreach ($list as $v) {
            if ($v['source'] != Base::PLATFORM_OZON && in_array($v['order_status'], [Order::ORDER_STATUS_UNCONFIRMED, Order::ORDER_STATUS_WAIT_PURCHASE,Order::ORDER_STATUS_APPLY_WAYBILL])) {
                $order_recommended_id[] = $v['order_id'];
            }
            $order_ids[] = $v['order_id'];
        }
        $order_recommended = OrderRecommended::find()->where(['order_id'=>$order_recommended_id])->indexBy('order_id')->asArray()->all();

        //$order_stock_occupy = OrderStockOccupy::find()->where(['order_id'=>$order_ids])->asArray()->all();
        $order_stock_occupy = OrderStockOccupy::find()->where(['order_id'=>$order_ids])->select('order_id,sku_no,count(*) cut,sum(case when type = 2 then 1 else 0 end) stock_cut')->groupBy('order_id,sku_no')->asArray()->all();
        $order_stock_occupy = ArrayHelper::index($order_stock_occupy, 'sku_no', 'order_id');


        $order_goods_lists = OrderGoods::find()->where(['order_id' => $order_ids, 'goods_status' => [OrderGoods::GOODS_STATUS_UNCONFIRMED, OrderGoods::GOODS_STATUS_NORMAL]])->asArray()->all();
        $cgoods_nos = ArrayHelper::getColumn($order_goods_lists,'cgoods_no');
        $cgoods_nos = array_filter($cgoods_nos);
        $sku_nos = ArrayHelper::getColumn($order_goods_lists,'platform_asin');
        $sku_nos = array_filter($sku_nos);
        $order_goods_lists = ArrayHelper::index($order_goods_lists,null,'order_id');
        $sku_num_lists = [];

        if(!in_array($this->list_tag,[1,2,3,9,4,5])) {
            if ($this->list_warehouse == GoodsService::OVERSEA_WAREHOUSE) {
                $warehouse_new = WarehouseService::getWarehouseMap();
                $warehouse_old = WarehouseService::$warehouse_map;
                $warehouse_map = array_diff($warehouse_new,$warehouse_old);
                $warehouse = array_keys($warehouse_map);

                $sku_num_lists = OrderGoods::find()->alias('og')
                    ->leftJoin(Order::tableName().' o','o.order_id = og.order_id')
                    ->where(['og.platform_asin' => $sku_nos,'o.warehouse' => $warehouse])->select('og.platform_asin,count(og.goods_num) cut')
                    ->groupBy('platform_asin')->indexBy('platform_asin')->asArray()->all();
            } else {
                $sku_num_lists = OrderGoods::find()->where(['platform_asin' => $sku_nos])->select('platform_asin,count(goods_num) cut')
                    ->groupBy('platform_asin')->indexBy('platform_asin')->asArray()->all();
            }
        }

        $cgoods_lists = GoodsChild::find()->where(['cgoods_no'=>$cgoods_nos])->indexBy('cgoods_no')->asArray()->all();

        foreach ($list as &$v){
            $v['shop_name'] = empty($shop[$v['shop_id']])?'':$shop[$v['shop_id']]['name'];
            $order_goods = $order_goods_lists[$v['order_id']];
            $v['goods_count'] = empty($order_goods)?1:count($order_goods);

            /*foreach ($order_goods as &$goods_v) {
                $buy_goods = BuyGoods::find()->where(['order_goods_id'=>$goods_v['id']])->one();
                $goods_v['buy_relation_no'] = $buy_goods['buy_relation_no'];
            }*/

            if($this->list_type == GoodsService::SOURCE_METHOD_OWN){
                if($v['integrated_logistics'] == Order::INTEGRATED_LOGISTICS_YES) {
                    $v['logistics_channels_desc'] = empty($v['logistics_channels_id']) ? $v['logistics_channels_name'] : TransportService::getShippingMethodName($v['logistics_channels_id']);
                } else {
                    $v['logistics_channels_desc'] = empty($v['logistics_channels_id']) ? '' : TransportService::getShippingMethodName($v['logistics_channels_id']);
                }
                $track_no_url = '';
                if(!empty($v['track_no'])) {
                    $shipping_method = TransportService::getShippingMethodInfo($v['logistics_channels_id']);
                    //云途跟踪信息链接
                    if ($shipping_method['transport_code'] == 'yuntu') {
                        $track_no_url = 'https://www.yuntrack.com/track/detail?id='.$v['track_no'];
                    }
                    //燕文跟踪信息链接
                    if ($shipping_method['transport_code'] == 'yanwen') {
                        $track_no_url = 'https://www.ship24.com/tracking?p='.$v['track_no'];
                    }
                    if($shipping_method['transport_code'] == 'hualei'){
                        $track_no_url = 'https://t.17track.net/zh-cn#nums='.$v['track_no'];
                    }
                    if($shipping_method['transport_code'] == 'fpx') {
                        $track_no_url = 'http://track.4px.com/#/result/0/' . $v['track_no'];
                    }
                }
                $v['track_no_url'] = $track_no_url;
                foreach ($order_goods as &$goods_v){
                    $goods_v['asin_count'] = empty($sku_num_lists[$goods_v['platform_asin']])?0:$sku_num_lists[$goods_v['platform_asin']]['cut'];//销量
                    $cgoods_info = empty($cgoods_lists[$goods_v['cgoods_no']])?'':$cgoods_lists[$goods_v['cgoods_no']];
                    $goods_v['weight'] = empty($cgoods_info)?0:($cgoods_info['real_weight'] > 0?$cgoods_info['real_weight']:0);
                    $goods_v['size'] = empty($cgoods_info)?'':$cgoods_info['package_size'];
                    $foam_weight = '';
                    $size_arr = GoodsService::getSizeArr($goods_v['size']);
                    if(!empty($size_arr)) {
                        $foam_weight = $size_arr['size_l'] * $size_arr['size_w'] * $size_arr['size_h'] / 6000 / $goods_v['weight'];
                        $foam_weight = round($foam_weight,1);
                    }
                    $goods_v['foam_weight'] = $foam_weight;
                    $goods_v['ccolour'] = empty($cgoods_info)?'':$cgoods_info['colour'];
                    $goods_v['csize'] = empty($cgoods_info)?'':$cgoods_info['size'];
                    //是否库存占用
                    if(in_array($v['order_status'],[Order::ORDER_STATUS_WAIT_PRINTED_OUT_STOCK,Order::ORDER_STATUS_WAIT_PRINTED]) ||
                        ($v['order_status'] == Order::ORDER_STATUS_WAIT_SHIP && $v['warehouse'] == WarehouseService::WAREHOUSE_OWN)) {
                        $goods_v['stock_occupy'] = !empty($order_stock_occupy[$v['order_id']])
                        && !empty($order_stock_occupy[$v['order_id']][$goods_v['platform_asin']]) &&
                        $order_stock_occupy[$v['order_id']][$goods_v['platform_asin']]['stock_cut'] > 0 ? 1 : 0;
                    }else {
                        $goods_v['stock_occupy'] = !empty($order_stock_occupy[$v['order_id']]) && !empty($order_stock_occupy[$v['order_id']][$goods_v['platform_asin']]) && $order_stock_occupy[$v['order_id']][$goods_v['platform_asin']]['cut'] > 0 ? 1 : 0;
                    }

                    //判断有没有第三方海外仓库存
                    $goods_v['has_ov_stock'] = false;
                    if($this->list_warehouse == GoodsService::OWN_WAREHOUSE) {
                        if(CountryService::isEuropeanUnion($v['country'])) {
                            $goods_stocks = GoodsStock::find()->where(['warehouse' => 8, 'cgoods_no' => $goods_v['cgoods_no']])->asArray()->one();
                            if(!empty($goods_stocks) && $goods_stocks['num'] > $goods_v['goods_num']) {
                                $goods_v['has_ov_stock'] = true;
                            }
                        }
                    }
                }
            }

            $v['goods'] = empty($order_goods)?[[]]:$order_goods;
            $v['country'] = CountryService::getName($v['country']);
            //$v['currency'] = empty($shop[$v['shop_id']])?'':$shop[$v['shop_id']]['currency'];

            if(!empty($order_recommended[$v['order_id']])) {
                $order_recommended_info = $order_recommended[$v['order_id']];
                $order_recommended_info['logistics_channels_desc'] = TransportService::getShippingMethodName($order_recommended_info['logistics_channels_id']);
                $v['order_recommended'] = $order_recommended_info;
            }
        }
        return $list;
    }

    public function model(){
        return new Order();
    }

    /**
     * @routeName 自建订单发货处理
     * @routeDescription 自建订单发货处理
     */
    public function actionOwnShipIndex()
    {
        $this->list_type = GoodsService::SOURCE_METHOD_OWN;
        $req = Yii::$app->request;
        $tag = $req->get('tag',10);

        $searchModel=new OrderSearch();
        $where=$searchModel->ownShipSearch(Yii::$app->request->queryParams,$tag);
        $data = $this->lists($where);

        $logistics_channels_ids = TransportService::getShippingMethodOptions();
        return $this->render('own/ship_index', [
            'searchModel' => $searchModel,
            'list' => $data['list'],
            'pages' => $data['pages'],
            'tag'=>$tag,
            'logistics_channels_ids'=>$logistics_channels_ids
        ]);
    }

    /**
     * @routeName 订单发货处理
     * @routeDescription 订单发货处理
     */
    public function actionShipIndex()
    {
        $this->list_type = GoodsService::SOURCE_METHOD_AMAZON;
        $req = Yii::$app->request;
        $tag = $req->get('tag',10);

        $searchModel=new OrderSearch();
        $where=$searchModel->shipSearch(Yii::$app->request->queryParams,$tag);
        $data = $this->lists($where);

        return $this->render('ship_index', [
            'searchModel' => $searchModel,
            'list' => $data['list'],
            'pages' => $data['pages'],
            'tag'=>$tag,
        ]);
    }

    /**
     * @routeName 新增订单
     * @routeDescription 创建新的订单
     * @throws
     * @return string |Response |array
     */
    public function actionCreate()
    {
        $req = Yii::$app->request;
        $overseas = $req->get('overseas');
        $model = new Order();
        $warehouse = WarehouseService::$warehouse_map + WarehouseService::getOverseasWarehouse(WarehouseProvider::TYPE_THIRD_PARTY);
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $data = $req->post();
            $data = $this->dataDeal($data);
            $data['admin_id'] = Yii::$app->user->identity->id;
            $data['create_way'] = Order::CREATE_WAY_BACKSTAGE;
            try {
                if ((new OrderService())->addOrder($data,$data['goods'])) {
                    return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
                } else {
                    return $this->FormatArray(self::REQUEST_FAIL, '添加失败', []);
                }
            } catch (\Exception $e) {
                return $this->FormatArray(self::REQUEST_FAIL, $e->getMessage(), []);
            }
        }
        $model->buyer_phone = '0000';
        $model->country = 'Deutschland';
        $warehouse_new = WarehouseService::getWarehouseMap();
        $warehouse_old = WarehouseService::$warehouse_map;
        if (!empty($overseas)) {
            $warehouse = array_diff($warehouse_new,$warehouse_old);
        }
        return $this->render('update', ['model' => $model,
            'order_goods'=>'',
            'order_declare' => '',
            'own_third_warehouse_list' => $warehouse,
        ]);
    }

    /**
     *
     * @param $data
     * @return mixed
     */
    private function dataDeal($data){
        $data['date'] = empty($data['date'])?0:strtotime($data['date']);
        $goods = [];
        if(!empty($data['goods'])) {
            foreach ($data['goods']['goods_id'] as $k => $v) {
                $sku_no = empty($data['goods']['platform_asin'][$k]) ? '' : trim($data['goods']['platform_asin'][$k]);
                $goods_child = GoodsChild::find()->where(['sku_no'=>$sku_no])->select('goods_no,cgoods_no,sku_no')->one();
                $platform_type = empty($data['goods']['platform_type'][$k]) ? 0 : $data['goods']['platform_type'][$k];
                $goods[] = [
                    'id' => empty($data['goods']['goods_id'][$k]) ? '' : $data['goods']['goods_id'][$k],
                    'goods_name' => empty($data['goods']['goods_name'][$k]) ? '' : $data['goods']['goods_name'][$k],
                    'goods_pic' => empty($data['goods']['goods_pic'][$k]) ? '' : $data['goods']['goods_pic'][$k],
                    'goods_num' => empty($data['goods']['goods_num'][$k]) ? 0 : $data['goods']['goods_num'][$k],
                    'goods_specification' => empty($data['goods']['goods_specification'][$k]) ? '' : $data['goods']['goods_specification'][$k],
                    'goods_income_price' => empty($data['goods']['goods_income_price'][$k]) ? 0 : $data['goods']['goods_income_price'][$k],
                    'goods_cost_price' => empty($data['goods']['goods_cost_price'][$k]) ? 0 : $data['goods']['goods_cost_price'][$k],
                    'source_method' => $platform_type == Base::PLATFORM_1688?GoodsService::SOURCE_METHOD_OWN:GoodsService::SOURCE_METHOD_AMAZON,
                    'platform_type' => empty($data['goods']['platform_type'][$k]) ? 0 : $data['goods']['platform_type'][$k],
                    'platform_asin' => $sku_no,
                    'goods_no' => empty($goods_child['goods_no'])?'':$goods_child['goods_no'],
                    'cgoods_no' => empty($goods_child['cgoods_no'])?'':$goods_child['cgoods_no'],
                    'out_stock'=> empty($data['goods']['out_stock'][$k]) ? 0 : 1,
                    'error_con'=> empty($data['goods']['error_con'][$k]) ? 0 : 1,
                    'has_buy_goods' => 1,
                ];
            }
        }
        $data['goods'] = $goods;

        $declare = [];
        if(!empty($data['declare'])) {
            foreach ($data['declare']['declare_id'] as $k => $v) {
                $declare[] = [
                    'id' => empty($data['declare']['declare_id'][$k]) ? '' : $data['declare']['declare_id'][$k],
                    'order_goods_id' => empty($data['declare']['order_goods_id'][$k]) ? 0 : $data['declare']['order_goods_id'][$k],
                    'declare_name_cn' => empty($data['declare']['declare_name_cn'][$k]) ? '' : $data['declare']['declare_name_cn'][$k],
                    'declare_name_en' => empty($data['declare']['declare_name_en'][$k]) ? '' : $data['declare']['declare_name_en'][$k],
                    'declare_price' => empty($data['declare']['declare_price'][$k]) ? 0 : $data['declare']['declare_price'][$k],
                    'declare_weight' => empty($data['declare']['declare_weight'][$k]) ? 0 : $data['declare']['declare_weight'][$k],
                    'declare_num' => empty($data['declare']['declare_num'][$k]) ? 0 : $data['declare']['declare_num'][$k],
                    'declare_material' => empty($data['declare']['declare_material'][$k]) ? '' : $data['declare']['declare_material'][$k],
                    'declare_purpose' => empty($data['declare']['declare_purpose'][$k]) ? '' : $data['declare']['declare_purpose'][$k],
                    'declare_customs_code' => empty($data['declare']['declare_customs_code'][$k]) ? '' : $data['declare']['declare_customs_code'][$k],
                ];
            }
        }
        $data['declare'] = $declare;
        return $data;
    }

    /**
     * @routeName 更新订单
     * @routeDescription 更新订单信息
     * @throws
     */
    public function actionUpdate()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        if ($req->isPost) {
            $id = $req->post('id');
        }

        $model = $this->findModel($id);
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $data = $req->post();
            if(empty($data['country'])) {
                return $this->FormatArray(self::REQUEST_FAIL, "国家不能为空", []);
            }
            $data = $this->dataDeal($data);
            $goods = empty($data['goods'])?[]:$data['goods'];
            $declare = empty($data['declare'])?[]:$data['declare'];

            $change_warehouse = false;
            if(!empty($data['warehouse']) && $model->warehouse != $data['warehouse']) {
                $change_warehouse = true;
            }

            if(!empty($data['currency']) && $model['currency'] != $data['currency']) {
                $data['exchange_rate'] = ExchangeRateService::getValue($data['currency']);
            }
            if ($model->load($data, '') == false) {
                return $this->FormatArray(self::REQUEST_FAIL, "参数异常", []);
            }

            if ($model->save()) {
                if($change_warehouse) {
                    OrderStockOccupy::deleteAll(['order_id' => $model['order_id']]);
                }
                (new OrderGoodsService())->updateOrderGoods($model['order_id'],$goods);
                (new OrderDeclareService())->updateOrderDeclare($model['order_id'],$declare);
                (new SystemOperlogService())->setType(SystemOperlog::TYPE_UPDATE)
                    ->addOrderLog($model['order_id'], time(), SystemOperlogService::ACTION_ORDER_UPDATE, null);
                return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
            }
        } else {
            $model['date'] = empty($model['date']) ? '' : date('Y-m-d H:i:s', $model['date']);
            $order_goods = OrderService::getOrderGoods($model['order_id']);
            foreach ($order_goods as &$v) {
                $v['goods_name'] = htmlentities($v['goods_name'], ENT_COMPAT);
            }
            $order_declare = OrderService::getOrderDeclare($model['order_id']);
            $logistics_channels_ids = TransportService::getShippingMethodOptions();
            $warehouse_list = WarehouseService::getOverseasWarehouse(WarehouseProvider::TYPE_PLATFORM);
            $platform_warehouse = array_keys($warehouse_list);
            $own_third_warehouse_list = WarehouseService::$warehouse_map + WarehouseService::getOverseasWarehouse(WarehouseProvider::TYPE_THIRD_PARTY);
            $own_third_warehouse = array_keys($own_third_warehouse_list);
            return $this->render('update', [
                'model' => $model,
                'order_goods' => json_encode($order_goods),
                'order_declare' => json_encode($order_declare),
                'logistics_channels_id' => $logistics_channels_ids,
                'warehouse_list' => $warehouse_list,
                'platform_warehouse' => $platform_warehouse,
                'own_third_warehouse_list' => $own_third_warehouse_list,
                'own_third_warehouse' => $own_third_warehouse
            ]);
        }
    }

    /**
     * @routeName 查看订单
     * @routeDescription 查看订单信息
     * @throws
     */
    public function actionView()
    {
        $req = Yii::$app->request;
        $order_id = $req->get('order_id');
        $model = Order::find()->where(['order_id'=>$order_id])->asArray()->one();
        $settlement = OrderSettlement::find()->where(['order_id'=>$order_id])->asArray()->one();
        $relation_no = $settlement['relation_no'];
        $financial_period_rollover = [];
        if (isset($relation_no)){
            $financial_period_rollover = FinancialPeriodRollover::find()->where(['relation_no'=>$relation_no])->asArray()->all();
        }
        $model['date'] = empty($model['date']) ? '' : date('Y-m-d H:i:s', $model['date']);
        $shop = Shop::find()->where(['id'=>$model['shop_id']])->asArray()->one();
        $model['shop_name'] = $shop['name'];
        //$model['currency'] = $shop['currency'];
        $order_goods = OrderService::getOrderGoods($model['order_id']);
        $per_info= SystemOperlog::find()->where(['object_no' => $order_id])->orderBy('id desc')->asArray()->all();
        $order_declare = OrderService::getOrderDeclare($model['order_id']);
        $order_recommended = [];
        if (in_array($model['order_status'], [Order::ORDER_STATUS_UNCONFIRMED, Order::ORDER_STATUS_WAIT_PURCHASE])) {
            $order_recommended = OrderRecommended::find()->where(['order_id' => $order_id])->indexBy('order_id')->asArray()->one();
        }
        $order_refund = [];
        if ($model['order_status'] == Order::ORDER_STATUS_REFUND){
            $order_refund = OrderRefund::find()->where(['order_id'=>$order_id])->asArray()->one();
        }
        return $this->render('view', [
            'financial' => $financial_period_rollover,
            'settlement' => $settlement,
            'model' => $model,
            'order_goods' => $order_goods,
            'order_declare'=> $order_declare,
            'order_recommended' => $order_recommended,
        	'per_info'=>$per_info,
            'refund'=>$order_refund,
        ]);
    }

    /**
     * @routeName 批量申请运单号
     * @routeDescription 批量申请运单号
     * @return array |Response|string
     */
    public function actionBatchTransportNo()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $order_id = $req->post('order_id');
        if(empty($order_id)){
            return $this->FormatArray(self::REQUEST_FAIL, "请选择订单", []);
        }

        $order_lists = Order::find()->where(['order_id'=>$order_id])->asArray()->all();
        $logistics_channels_ids = ArrayHelper::getColumn($order_lists,'logistics_channels_id');
        $shipping_method_lists = ShippingMethod::find()->where(['id'=>$logistics_channels_ids])->indexBy('id')->asArray()->all();
        $error = '';
        $i = 0;
        foreach ($order_lists as $v) {
            $warehouse_id = $v['warehouse'];
            $warehouse_type = WarehouseService::getWarehouseProviderType($warehouse_id);
            if ($warehouse_type == WarehouseProvider::TYPE_PLATFORM) {
                Order::updateAll(['order_status' => Order::ORDER_STATUS_WAIT_SHIP], ['order_id' => $v['order_id']]);
                continue;
            }

            //美客多直接进去待打包
            if (in_array($v['source'] ,FApiService::$own_Logistics) || $v['integrated_logistics'] == Order::INTEGRATED_LOGISTICS_YES) {
                Order::updateAll(['order_status'=>Order::ORDER_STATUS_WAIT_PRINTED_OUT_STOCK], ['order_id' => $v['order_id']]);
                if(in_array($v['source'],[Base::PLATFORM_LINIO,Base::PLATFORM_JUMIA,Base::PLATFORM_HEPSIGLOBAL,Base::PLATFORM_OZON])){
                    OrderEventService::addEvent($v['source'], $v['shop_id'], $v['order_id'], OrderEvent::EVENT_TYPE_TRACKING_NUMBER);
                }
                continue;
            }
            if(empty($v['logistics_channels_id'])){
                $error .= '订单号：'.$v['order_id'].' 未选择物流方式'."<br/>";
                $i++;
                continue;
            }
            if(!empty($v['track_no'])){
                $error .= '订单号：'.$v['order_id'].' 已申请运单号'."<br/>";
                $i++;
                continue;
            }

            $shipping_method = $shipping_method_lists[$v['logistics_channels_id']];
            $ser = FTransportService::factory($shipping_method['transport_code']);
            $re = $ser->getOrderNO($v);
            if($re['error'] == BaseTransportService::RESULT_FAIL) {
                $error .= '订单号：'.$v['order_id'].' '.$re['msg']."<br/>";
                $i++;
                continue;
            }
            $data = $re['data'];
            $up_data = [
                'track_no'=> $data['track_no'],
            ];
            if(!empty($data['delivery_order_id'])){
                $up_data['delivery_order_id'] = $data['delivery_order_id'];
            }
            if(!empty($data['track_logistics_no'])){
                $up_data['track_logistics_no'] = $data['track_logistics_no'];
            }

            if(in_array($v['order_status'],[Order::ORDER_STATUS_UNCONFIRMED,Order::ORDER_STATUS_WAIT_PURCHASE])) {
                if ($warehouse_type == WarehouseProvider::TYPE_THIRD_PARTY) {
                    $up_data['order_status'] = Order::ORDER_STATUS_WAIT_SHIP;
                } else {
                    $up_data['order_status'] = Order::ORDER_STATUS_APPLY_WAYBILL;
                }
            }
            Order::updateAll($up_data, ['order_id' => $v['order_id']]);
            if(in_array($v['source'] ,[Base::PLATFORM_OZON,Base::PLATFORM_EPRICE,Base::PLATFORM_RDC,Base::PLATFORM_WORTEN])) {
                OrderEventService::addEvent($v['source'], $v['shop_id'], $v['order_id'], OrderEvent::EVENT_TYPE_TRACKING_NUMBER);
            }
            (new SystemOperlogService())->setType(SystemOperlog::TYPE_UPDATE)
                ->addOrderLog($v['order_id'], time(), SystemOperlogService::ACTION_ORDER_LOGISTICS, null);
        }
        if(!empty($error)){
            return $this->FormatArray(self::REQUEST_FAIL, "申请".$i."条失败 "."<br/>" .$error, []);
        } else {
            return $this->FormatArray(self::REQUEST_SUCCESS, "申请成功", []);
        }
    }

    /**
     * @routeName 批量移入异常
     * @routeDescription 批量移入异常
     * @return array
     * @throws
     */
    public function actionBatchMoveAbnormal()
    {
        $req = Yii::$app->request;
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();

            $abnormal_remarks = $post['abnormal_remarks'];
            $abnormal_type = $post['abnormal_type'];
            if(empty($abnormal_type)){
                return $this->FormatArray(self::REQUEST_FAIL, "请选择异常类型", []);
            }
            $next_follow_time = $post['next_follow_time'];

            $order_id = $req->get('order_id');
            if(empty($order_id)){
                return $this->FormatArray(self::REQUEST_FAIL, "请选择订单", []);
            }

            if($abnormal_type == 99 && empty($abnormal_remarks)){
                return $this->FormatArray(self::REQUEST_FAIL, "异常备注不能为空", []);
            }
            $order_id = explode(',', $order_id);
            $next_follow_time = empty($next_follow_time)?0:strtotime($next_follow_time);
            (new OrderAbnormalService())->batchMoveAbnormal($order_id,$abnormal_type,$abnormal_remarks,$next_follow_time);
            foreach ($order_id as $v){
                (new SystemOperlogService())->setType(SystemOperlog::TYPE_UPDATE)
                    ->addOrderLog($v, time(), SystemOperlogService::ACTION_ORDER_MOVE_ABNORMAL, null);
            }
            return $this->FormatArray(self::REQUEST_SUCCESS, "操作成功", []);
        } else {
            return $this->render('own/batch-move-abnormal');
        }
    }

    /**
     * @routeName 恢复异常
     * @routeDescription 恢复异常
     * @return array
     * @throws
     */
    public function actionBatchAbnormalRecovery()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $order_id = $req->post('order_id');
        if(empty($order_id)){
            return $this->FormatArray(self::REQUEST_FAIL, "请选择订单", []);
        }

        $order = Order::find()->where(['order_id'=>$order_id])->all();
        foreach ($order as $v){
            if($v['abnormal_time'] == 0){
                continue;
            }
            $v->abnormal_time = 0;
            $v->save();
            (new PurchaseProposalService())->updatePurchaseProposalToOrderId($order_id);
            (new SystemOperlogService())->setType(SystemOperlog::TYPE_UPDATE)
                ->addOrderLog($v['order_id'], time(), SystemOperlogService::ACTION_ORDER_ABNORMAL, null);
        }
        return $this->FormatArray(self::REQUEST_SUCCESS, "操作成功", []);
    }

    /**
     * @routeName 批量选择物流方式
     * @routeDescription 批量选择物流方式
     * @return array
     * @throws
     */
    public function actionBatchLogisticsChannels()
    {
        $req = Yii::$app->request;
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();

            $logistics_channels_id = $post['logistics_channels_id'];
            if(empty($logistics_channels_id)) {
                return $this->FormatArray(self::REQUEST_FAIL, "物流方式不能为空", []);
            }

            $order_id = $req->get('order_id');
            if(empty($order_id)){
                return $this->FormatArray(self::REQUEST_FAIL, "请选择订单", []);
            }
            //$order_lists = Order::find()->where(['order_id'=>$order_id])->asArray()->all();
            Order::updateAll(['logistics_channels_id'=>$logistics_channels_id],['order_id'=> explode(',', $order_id),'integrated_logistics'=>Order::INTEGRATED_LOGISTICS_NO]);
            return $this->FormatArray(self::REQUEST_SUCCESS, "操作成功", []);
        } else {
            $type = $req->get('type',1);
            $logistics_channels_ids = TransportService::getShippingMethodOptions(false,$type==2?true:false);
            return $this->render('own/batch-logistics-channels', ['logistics_channels_id' => $logistics_channels_ids]);
        }
    }

    /**
     * @routeName 录入运单号
     * @routeDescription 录入运单号
     * @return array
     * @throws
     */
    public function actionInputLogistics()
    {
        $req = Yii::$app->request;
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            $order_id = $post['order_id'];
            $gen_logistics = !empty($post['gen_logistics'])?true:false;//重新生成物流单号
            $order = Order::findOne(['order_id'=>$order_id]);
            $logistics_channels_id = $post['logistics_channels_id'];
            $track_no = $post['track_no'];
            if((!$gen_logistics && empty($track_no)) || empty($logistics_channels_id)) {
                return $this->FormatArray(self::REQUEST_FAIL, "物流方式或物流单号不能为空", []);
            }
            $old_track_no = $order['track_no'];
            if($gen_logistics) {
                $order->logistics_channels_id = $logistics_channels_id;
                if(!empty($old_track_no)) {
                    $order->track_no = '';
                    $order->logistics_reset_times = $order['logistics_reset_times'] + 1;
                }
                $order->save();

                $shipping_method = ShippingMethod::find()->where(['id' => $logistics_channels_id])->indexBy('id')->asArray()->one();
                $ser = FTransportService::factory($shipping_method['transport_code']);
                $re = $ser->getOrderNO($order);
                if ($re['error'] == BaseTransportService::RESULT_FAIL) {
                    return $this->FormatArray(self::REQUEST_FAIL, '重新生成运单号失败：' . $re['msg'], []);
                }

                $data = $re['data'];
                $track_no = $data['track_no'];
                $order->track_no = $track_no;
                if(!empty($data['delivery_order_id'])){
                    $order->delivery_order_id = $data['delivery_order_id'];
                }
                if(!empty($data['track_logistics_no'])){
                    $order->track_logistics_no = $data['track_logistics_no'];
                }
            } else {
                $order->logistics_channels_id = $logistics_channels_id;
                $order->track_no = $track_no;
            }
            if(!in_array($order['order_status'],[Order::ORDER_STATUS_WAIT_PRINTED,Order::ORDER_STATUS_WAIT_SHIP,Order::ORDER_STATUS_SHIPPED,Order::ORDER_STATUS_FINISH])) {
                $order->order_status = Order::ORDER_STATUS_APPLY_WAYBILL;
            }
            $order->logistics_pdf = '';
            if ($order->save()) {
                if(in_array($order['source'] ,[Base::PLATFORM_OZON,Base::PLATFORM_EPRICE,Base::PLATFORM_RDC,Base::PLATFORM_WORTEN])) {
                    OrderEventService::addEvent($order['source'], $order['shop_id'], $order['order_id'], OrderEvent::EVENT_TYPE_TRACKING_NUMBER);
                }
                CommonUtil::logs('InputLogistics:'.$order_id.' old_track_no:'.$old_track_no.' track_no:'.$track_no,'order_log');
                (new SystemOperlogService())->setType(SystemOperlog::TYPE_UPDATE)
                    ->addOrderLog($order_id, time(), SystemOperlogService::ACTION_ORDER_LOGISTICS, null);
                return $this->FormatArray(self::REQUEST_SUCCESS, "操作成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $order->getErrorSummary(false)[0], []);
            }
        } else {
            $order_id = $req->get('order_id');
            $gen_logistics = $req->get('gen_logistics',0);
            $order = Order::findOne(['order_id'=>$order_id]);
            $shipping = ShippingMethod::find()->where(['status'=>ShippingMethod::STATUS_VALID])->asArray()->all();
            $logistics_channels_ids = TransportService::getShippingMethodOptions();
            return $this->render('own/input-logistics', ['model'=>$order,'logistics_channels_id' => $logistics_channels_ids,'gen_logistics'=>$gen_logistics]);
        }
    }

    /**
     * @routeName 打回待处理
     * @routeDescription 打回待处理
     * @return array
     * @throws
     */
    public function actionResetLogistics()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $order_id = $req->post('order_id');
        if(empty($order_id)){
            return $this->FormatArray(self::REQUEST_FAIL, "请选择订单", []);
        }

        $order = Order::find()->where(['order_id'=>$order_id])->all();
        foreach ($order as $v){
            $old_status = $v['order_status'];
            if(!in_array($old_status,[Order::ORDER_STATUS_APPLY_WAYBILL,Order::ORDER_STATUS_WAIT_PRINTED_OUT_STOCK,Order::ORDER_STATUS_WAIT_PRINTED,Order::ORDER_STATUS_WAIT_SHIP,Order::ORDER_STATUS_SHIPPED])) {
                continue;
            }
            $v->delivery_order_id = '';
            $v->order_status = Order::ORDER_STATUS_WAIT_PURCHASE;
            $v->logistics_reset_times = $v['logistics_reset_times'] + 1;
            $v->track_no = '';
            $v->logistics_pdf = '';
            $v->save();
            OrderTransport::updateAll(['status'=>OrderTransport::STATUS_CANCELLED],['order_id'=>$v['order_id']]);
            OrderTransportFeeDetail::updateAll(['status'=>OrderTransport::STATUS_CANCELLED],['order_id'=>$v['order_id']]);
            (new SystemOperlogService())->setType(SystemOperlog::TYPE_UPDATE)
                ->addOrderLog($v['order_id'], time(), SystemOperlogService::ACTION_ORDER_RESET_LOGISTICS, null);
        }
        return $this->FormatArray(self::REQUEST_SUCCESS, "操作成功", []);
    }

    /**
     * @routeName 批量确认订单
     * @routeDescription 批量确认订单
     * @return array |Response|string
     * @throws \yii\base\Exception
     */
    public function actionBatchConfirm()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $order_id = $req->post('order_id');
        if(empty($order_id)){
            return $this->FormatArray(self::REQUEST_FAIL, "请选择订单", []);
        }
        if (Order::updateAll(['order_status' => Order::ORDER_STATUS_WAIT_PURCHASE], ['order_id' => $order_id])) {
            GoodsService::updateDeclare($order_id);
            //OrderGoods::updateAll(['goods_status' => OrderGoods::GOODS_STATUS_NORMAL], ['order_id' => $order_id]);
            return $this->FormatArray(self::REQUEST_SUCCESS, "确认成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL, "确认失败", []);
        }
    }

    /**
     * @routeName 批量设置商品状态
     * @routeDescription 批量设置商品状态
     * @return array |Response|string
     * @throws \yii\base\Exception
     */
    public function actionBatchUpdateGoodsStatus()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $order_id = $req->post('order_id');
        $status = $req->get('status');

        if(empty($order_id)){
            return $this->FormatArray(self::REQUEST_FAIL, "请选择订单", []);
        }

        $sku_nos = OrderGoods::find()
            ->where(['order_id'=>$order_id,'goods_status'=>[OrderGoods::GOODS_STATUS_UNCONFIRMED,OrderGoods::GOODS_STATUS_NORMAL]])
            ->select('platform_asin')->column();
        $sku_nos = array_filter(array_unique($sku_nos));

        if(count($sku_nos) > count($order_id) * 3){
            return $this->FormatArray(self::REQUEST_FAIL, "商品数量过多,可能出现异常", []);
        }

        $goods = Goods::find()->where(['sku_no'=>$sku_nos])->asArray()->all();
        if (Goods::updateAll(['status'=>$status],['sku_no'=>$sku_nos])) {
            foreach ($goods as $v) {
                $old_status = $v['status'];
                //未匹配的情况 恢复为未匹配状态
                if($status == Goods::GOODS_STATUS_VALID){
                    if($v['weight'] <= 0){
                        $v['status'] = Goods::GOODS_STATUS_WAIT_MATCH;
                        $v->save();
                    }
                }

                if($old_status != $status) {
                    (new GoodsService())->asyncPlatformStock($v['goods_no']);
                }
            }
            return $this->FormatArray(self::REQUEST_SUCCESS, "状态更新成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL, "状态更新失败", []);
        }
    }

    /**
     * @routeName 批量设置状态
     * @routeDescription 批量设置状态
     * @return array |Response|string
     */
    public function actionBatchUpdateStatus()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $order_id = $req->post('order_id');
        $order_status = $req->get('order_status');
        $go_back = $req->get('go_back',0);

        if(empty($order_id)){
            return $this->FormatArray(self::REQUEST_FAIL, "请选择订单", []);
        }
        //待打包 安骏的直接到待发货
        $result = false;
        $update_purchase = false;
        if($order_status == Order::ORDER_STATUS_WAIT_PRINTED_OUT_STOCK){
            $result1 = Order::updateAll(['order_status' => Order::ORDER_STATUS_WAIT_SHIP], ['order_id' => $order_id,'warehouse'=> WarehouseService::WAREHOUSE_ANJ]);
            $result2 = Order::updateAll(['order_status' => $order_status], ['order_id' => $order_id,'warehouse'=> WarehouseService::WAREHOUSE_OWN]);
            $result = $result1 || $result2;
        }else if($order_status == Order::ORDER_STATUS_WAIT_PRINTED){//待打单
            if($go_back == 1) {//回退
                $result1 = Order::updateAll(['order_status' => Order::ORDER_STATUS_APPLY_WAYBILL], ['order_id' => $order_id, 'warehouse' => WarehouseService::WAREHOUSE_ANJ]);
                $result2 = Order::updateAll(['order_status' => $order_status], ['order_id' => $order_id, 'warehouse' => WarehouseService::WAREHOUSE_OWN]);
                $result = $result1 || $result2;
            } else{
                $result = Order::updateAll(['order_status' => $order_status], ['order_id' => $order_id,'order_status'=>Order::ORDER_STATUS_WAIT_PRINTED_OUT_STOCK]);
            }
        }else if($order_status == Order::ORDER_STATUS_SHIPPED && $go_back == 1) {//已完成打回已发货
            $result = Order::updateAll(['order_status' => $order_status], ['order_id' => $order_id,'order_status'=>Order::ORDER_STATUS_FINISH]);
        }else if($order_status == Order::ORDER_STATUS_WAIT_SHIP && $go_back == 1) {//打回待发货
            $result = Order::updateAll(['order_status' => $order_status], ['order_id' => $order_id,'order_status'=>[Order::ORDER_STATUS_FINISH,Order::ORDER_STATUS_SHIPPED]]);
            $update_purchase = true;
        }else{
            $result = Order::updateAll(['order_status' => $order_status], ['order_id' => $order_id]);
        }
        if($order_status == Order::ORDER_STATUS_SHIPPED) {
            $update_purchase = true;
        }
        if ($result) {
            if($update_purchase) {
                (new PurchaseProposalService())->updatePurchaseProposalToOrderId($order_id);
            }
            //OrderGoods::updateAll(['goods_status' => OrderGoods::GOODS_STATUS_NORMAL], ['order_id' => $order_id]);
            return $this->FormatArray(self::REQUEST_SUCCESS, "操作成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL, "操作失败", []);
        }
    }

    /**
     * @routeName 刷新库存
     * @routeDescription 刷新库存
     * @return array |Response|string
     */
    public function actionRefreshOutStockStatus()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $result = OrderService::refreshOutStockStatus();
        if ($result) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "操作成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL, "操作失败", []);
        }
    }

    /**
     * @routeName 批量虚拟发货
     * @routeDescription 批量虚拟发货
     * @return array |Response|string
     * @throws \yii\base\Exception
     */
    public function actionBatchVirtualShip()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $all = $req->get('all',0);
        if(empty($all)) {
            $order_id = $req->post('order_id');
            if (empty($order_id)) {
                return $this->FormatArray(self::REQUEST_FAIL, "请选择订单", []);
            }
            $order = Order::find()->where(['order_id'=>$order_id])->all();
        }else{
            $order = Order::find()->where(['order_status'=>Order::ORDER_STATUS_WAIT_PRINTED_OUT_STOCK,
                'integrated_logistics'=>Order::INTEGRATED_LOGISTICS_NO,'abnormal_time'=>0])->all();
        }
        foreach ($order as $v){
            if(!in_array($v['order_status'],[Order::ORDER_STATUS_APPLY_WAYBILL,Order::ORDER_STATUS_WAIT_PRINTED_OUT_STOCK,Order::ORDER_STATUS_WAIT_PRINTED,Order::ORDER_STATUS_WAIT_SHIP])) {
                continue;
            }
            if($v['pdelivery_status'] == Order::PDELIVERY_SHIPPED || empty($v['track_no'])){
                continue;
            }

            $v['pdelivery_status'] = Order::PDELIVERY_SHIPPED;
            $v->save();
            OrderEventService::addEvent($v['source'],$v['shop_id'],$v['order_id'],OrderEvent::EVENT_TYPE_SHIPPING);
            (new SystemOperlogService())->setType(SystemOperlog::TYPE_UPDATE)
                ->addOrderLog($v['order_id'], time(), SystemOperlogService::ACTION_ORDER_VIRTUAL_SHIP, null);
        }
        return $this->FormatArray(self::REQUEST_SUCCESS, "虚拟发货成功", []);
    }

    /**
     * @routeName 批量打印面单
     * @routeDescription 批量打印面单
     * @return array |Response|string
     * @throws \yii\base\Exception
     */
    public function actionBatchPrinted()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $order_id = $req->post('order_id');
        if(empty($order_id)){
            return $this->FormatArray(self::REQUEST_FAIL, "请选择订单", []);
        }

        $order_lists = Order::find()->where(['order_id'=>$order_id])->asArray()->all();
        $logistics_channels_ids = ArrayHelper::getColumn($order_lists,'logistics_channels_id');
        $logistics_channels_ids = array_unique($logistics_channels_ids);
        if(count($logistics_channels_ids) > 1){
            return $this->FormatArray(self::REQUEST_FAIL, "只能批量打印同一种物流方式", []);
        }

        $mck = 0;
        foreach ($order_lists as $v) {
            if (in_array($v['source'] ,FApiService::$own_Logistics) || $v['integrated_logistics'] == Order::INTEGRATED_LOGISTICS_YES) {
                $mck++;
            }
        }
        if($mck > 1){
            return $this->FormatArray(self::REQUEST_FAIL, "平台自有物流订单不支持批量打印", []);
        }

        if($mck == 1){
            if(count($order_lists) > 1){
                return $this->FormatArray(self::REQUEST_FAIL, "台自有物流订单不支持批量打印", []);
            }

            $order_info = current($order_lists);
            $shop = Shop::find()->where(['id'=>$order_info['shop_id']])->asArray()->one();
            $url = FApiService::factory($shop)->doPrint($order_info);
            return $this->FormatArray(self::REQUEST_SUCCESS, "打印成功", $url);
        }


        $shipping_method = ShippingMethod::find()->where(['id'=>$logistics_channels_ids])->indexBy('id')->asArray()->one();

        $ser = FTransportService::factory($shipping_method['transport_code']);
        $re = $ser->doPrint($order_lists);
        if($re['error'] == BaseTransportService::RESULT_FAIL) {
            return $this->FormatArray(self::REQUEST_FAIL, "打印失败:".$re['msg'], []);
        }

        return $this->FormatArray(self::REQUEST_SUCCESS, "打印成功", $re['data']['pdf_url']);
    }

    /**
     * @routeName 打印发票
     * @routeDescription 打印发票
     * @return array |Response|string
     * @throws \yii\base\Exception
     */
    public function actionPrintedInvoice()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $order_id = $req->post('order_id');
        if(empty($order_id)){
            return $this->FormatArray(self::REQUEST_FAIL, "请选择订单", []);
        }

        $order_lists = Order::find()->where(['order_id'=>$order_id])->asArray()->all();
        if(count($order_lists) > 1){
            return $this->FormatArray(self::REQUEST_FAIL, "只能打印一个订单", []);
        }

        $order_info = current($order_lists);
        $shop = Shop::find()->where(['id'=>$order_info['shop_id']])->asArray()->one();
        $url = FApiService::factory($shop)->doPrintInvoice($order_info);
        return $this->FormatArray(self::REQUEST_SUCCESS, "打印成功", $url);
    }

    /**
     * @routeName 直接打印面单
     * @routeDescription 直接打印面单
     * @return array |Response|string
     * @throws \yii\base\Exception
     */
    public function actionDirectPrinted()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $order_id = $req->get('order_id');
        if (empty($order_id)) {
            return $this->FormatArray(self::REQUEST_FAIL, "请选择订单", []);
        }

        try {
            $result = OrderService::genLogisticsPdf($order_id);
            if ($result == false) {
                return $this->FormatArray(self::REQUEST_FAIL, "打印失败", []);
            }
        }catch (\Exception $e){
            return $this->FormatArray(self::REQUEST_FAIL, "打印失败", []);
        }

        $order = OrderService::getOneByOrderId($order_id);
        if(!empty($order['track_no']) && $order['integrated_logistics'] == Order::INTEGRATED_LOGISTICS_NO) {
            $order_lists = Order::find()->where(['track_no' => $order['track_no']])->all();
        } else {
            $order_lists = [$order];
        }
        foreach ($order_lists as $order_v) {
            if (in_array($order_v['order_status'], [Order::ORDER_STATUS_WAIT_PRINTED, Order::ORDER_STATUS_WAIT_PRINTED_OUT_STOCK])) {
                $order_v['order_status'] = Order::ORDER_STATUS_WAIT_SHIP;
                $order_v->save();
            }
        }
        return $this->FormatArray(self::REQUEST_SUCCESS, "打印成功", $result);
    }

    /**
     * @routeName 批量设置打印状态
     * @routeDescription 批量设置打印状态
     * @return array |Response|string
     */
    public function actionBatchPrintedStatus()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $order_id = $req->post('order_id');
        if(empty($order_id)) {
            return $this->FormatArray(self::REQUEST_FAIL, "请选择订单", []);
        }
        if (Order::updateAll(['printed_status' => HelperStamp::addStampSql('printed_status',Order::PRINTED_STATUS_L)], ['order_id' => $order_id])) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "确认成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL, "确认失败", []);
        }
    }


    /**
     * @routeName 打印PDF
     * @routeDescription 打印PDF
     * @return array |Response|string
     */
    public function actionPrintedPdf()
    {
        $req = Yii::$app->request;
        $order_id = $req->get('order_id');

        $order = Order::find()->where(['order_id' => $order_id])->asArray()->one();
        if (empty($order) || (empty($order['logistics_channels_id']) && empty($order['logistics_channels_name']))) {
            return '订单不存在';
        }
        try {
            if(in_array($order['source'], FApiService::$own_Logistics) || $order['integrated_logistics'] == Order::INTEGRATED_LOGISTICS_YES) {
                $shop = Shop::find()->where(['id' => $order['shop_id']])->asArray()->one();
                FApiService::factory($shop)->doPrint($order,true);
            }else {
                $shipping_method = ShippingMethod::find()->where(['id' => $order['logistics_channels_id']])->asArray()->one();
                $ser = FTransportService::factory($shipping_method['transport_code']);
                $ser->doPrint([$order], true);
            }
        } catch (\Exception $e) {
            return '打印失败';
        }
    }

    /**
     * @routeName 批量发货
     * @routeDescription 批量发货
     * @return array |Response|string
     */
    public function actionBatchShip()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $order_id = $req->post('order_id');
        $type = $req->get('type', 0);
        if (empty($order_id)) {
            return $this->FormatArray(self::REQUEST_FAIL, "请选择订单", []);
        }

        $order_lists = Order::find()->where(['order_id' => $order_id])->all();
        $error = '';
        foreach ($order_lists as $v) {
            if ($v['warehouse'] == WarehouseService::WAREHOUSE_OWN && $type != 1) {
                continue;
            }
            try {
                OrderService::ship($v['order_id'], 0, '', true);
            } catch (\Exception $e) {
                $error .= $v['order_id'] . ':' . $e->getMessage() . "\n";
            }
        }
        if (empty($error)) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "批量发货成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_SUCCESS, "存在失败：" . $error, []);
        }
    }

    /**
     * @routeName 重新下单
     * @routeDescription 重新下单
     * @throws
     */
    public function actionAgain()
    {
        $req = Yii::$app->request;
        $model = new Order();
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $data = $req->post();
            $data = $this->dataDeal($data);
            $data['admin_id'] = Yii::$app->user->identity->id;
            $data['create_way'] = Order::CREATE_WAY_BACKSTAGE;
            try {
                if ((new OrderService())->addOrder($data,$data['goods'])) {
                    return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
                } else {
                    return $this->FormatArray(self::REQUEST_FAIL, '添加失败', []);
                }
            } catch (\Exception $e) {
                return $this->FormatArray(self::REQUEST_FAIL, $e->getMessage(), []);
            }
        }
        $re_id = $req->get('re_id');
        $model = $this->findModel($re_id);
        $model['date'] = empty($model['date']) ? '' : date('Y-m-d H:i:s', $model['date']);
        $order_goods = OrderService::getOrderGoods($model['order_id']);
        $model['id'] = '';
        $model['relation_no'] = $model['relation_no'].'0';
        //$model['tax_number'] = '';
        //$model['tax_relation_no'] = '';
        return $this->render('update', ['model' => $model, 'order_goods' => json_encode($order_goods),'again'=>1]);
    }

    /**
     * @param $id
     * @return null|Order
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        if (($model = Order::findOne($id)) !== null) {
           return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }


    /**
     * @routeName 订单发货
     * @routeDescription 订单发货
     * @throws
     */
    public function actionShip()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        if ($req->isPost) {
            $id = $req->post('id');
        }

        $model = $this->findModel($id);
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            try {
                OrderService::ship($model['order_id'],0,'',true);
                return $this->FormatArray(self::REQUEST_SUCCESS, "发货成功", []);
            } catch (\Exception $e) {
                return $this->FormatArray(self::REQUEST_FAIL, "发货失败:".$e->getMessage(), []);
            }
        } else {
            $model['date'] = empty($model['date']) ? '' : date('Y-m-d', $model['date']);
            $order_goods = OrderService::getOrderGoods($model['order_id']);
            return $this->render('ship', ['model' => $model, 'order_goods' => $order_goods]);
        }
    }

    /**
     * @routeName 取消订单
     * @routeDescription 取消指定订单
     * @return array
     * @throws
     */
    public function actionCancel()
    {
        /*Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $order_id = $req->get('order_id');
        if ((new OrderService())->cancel($order_id)) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "取消成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_SUCCESS, "取消失败", []);
        }*/
        $req = Yii::$app->request;
        $type = $req->get('type',1);
        if ($req->isPost || $type == 2) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();

            $cancel_remarks = empty($post['cancel_remarks'])?'':$post['cancel_remarks'];
            $cancel_reason = empty($post['cancel_reason'])?0:$post['cancel_reason'];

            $order_id = $req->get('order_id');
            if(empty($order_id)){
                return $this->FormatArray(self::REQUEST_FAIL, "请选择订单", []);
            }

            $order = OrderService::getOneByOrderId($order_id);
            if($order['source_method'] == GoodsService::SOURCE_METHOD_OWN) {
                if (empty($cancel_reason)) {
                    return $this->FormatArray(self::REQUEST_FAIL, "取消原因不能为空", []);
                }

                if ($cancel_reason == 9 && empty($cancel_remarks)) {
                    return $this->FormatArray(self::REQUEST_FAIL, "取消备注不能为空", []);
                }
            }

            if ((new OrderService())->cancel($order_id,$cancel_reason,$cancel_remarks)) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "取消成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_SUCCESS, "取消失败", []);
            }
        } else {
            return $this->render('own/cancel');
        }
    }

    /**
     * @routeName 订单退款
     * @routeDescription 订单退款
     * @return array
     * @throws
     */
    public function actionRefund()
    {
        $req = Yii::$app->request;
        $order_id = $req->get('order_id');
        $user_id = Yii::$app->user->identity->id;
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            $datatime = time();
            if (empty($post['cancel_reason'])){
                return $this->FormatArray(self::REQUEST_FAIL, "退款原因不能为空", []);
            }
            $order_id = $req->post('order_id');
            $refund_remarks = empty($post['cancel_remarks'])?'':$post['cancel_remarks'];
            $refund_reason = $post['cancel_reason'];
            $refund_type =$post['cancel_type'];
            $refund_num = $post['num'];
            $model = new OrderRefund();
            $model['order_id'] = $order_id;
            $model['refund_remarks'] = $refund_remarks;
            $model['refund_reason'] = $refund_reason;
            $model['refund_type'] = $refund_type;
            $model['add_time'] = $datatime;
            $model['admin_id'] = $user_id;
            $order_refund_reason = empty(Order::$refund_reason_map[$refund_reason])?'':Order::$refund_reason_map[$refund_reason];
            $orders = Order::find()->where(['order_id'=>$order_id])->asArray()->all();
            foreach ($orders as $or){
                $order = Order::findOne($or['id']);
                $order['order_status'] = Order::ORDER_STATUS_REFUND;
                $order['cancel_time'] = $datatime;
                if($refund_type == OrderRefund::REFUND_TWO){
                if($refund_num > $order['order_income_price']){
                    Yii::$app->response->format = Response::FORMAT_JSON;
                    return $this->FormatArray(self::REQUEST_FAIL, "退款金额大于本金", []);
                }else{$model['refund_num'] = $refund_num;}
                }else{$model['refund_num']=(float)$order['order_income_price'];}
                $order->save();
                (new SystemOperlogService())->setType(SystemOperlogService::ACTION_ORDER_REFUND)
                    ->addOrderLog($order_id, $datatime, SystemOperlogService::ACTION_ORDER_REFUND, $order_refund_reason);
            }
            if($model->save()){
                return $this->FormatArray(self::REQUEST_SUCCESS, "退款成功", []);
            }else{
                return $this->FormatArray(self::REQUEST_FAIL, "退款失败", []);
            }
        } else {
            return $this->render('own/refund',['id' => $order_id]);
        }
    }

    /**
     * @routeName 订单导出
     * @routeDescription 订单导出
     * @return array |Response|string
     */
    public function actionOwnExport()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $tag = $req->get('tag',10);
        $searchModel=new OrderSearch();
        $where = $searchModel->ownSearch(Yii::$app->request->queryParams,$tag);
        $list = Order::getAllByCond($where);
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
        $order_ids = ArrayHelper::getColumn($list,'order_id');
        $order_goods = OrderGoods::find()->where(['order_id'=>$order_ids])->andWhere(['!=','goods_status',OrderGoods::GOODS_STATUS_CANCEL])->asArray()->all();

        $order_lists = ArrayHelper::index($list, 'order_id');

        $sku_nos = ArrayHelper::getColumn($order_goods,'platform_asin');
        $goods_lists = Goods::find()->where(['sku_no' => $sku_nos])->indexBy('sku_no')->asArray()->all();
        $goods_child_lists = GoodsChild::find()->where(['sku_no' => $sku_nos])->indexBy('sku_no')->asArray()->all();
        $shop_lists = Shop::find()->indexBy('id')->asArray()->all();
        $p_goods_lists = PurchaseOrderGoods::find()->where(['sku_no' => $sku_nos])->groupBy('sku_no')->indexBy('sku_no')->asArray()->all();
        $data = [];
        foreach ($order_goods as $k => $v) {
            if (empty($order_lists[$v['order_id']])) {
                continue;
            }
            $order_info = $order_lists[$v['order_id']];
            $data[$k]['date'] = empty($order_info['date']) ? '' : date('Y/m/d', $order_info['date']);
            $data[$k]['order_id'] = $order_info['order_id'];
            $data[$k]['relation_no'] = $order_info['relation_no'];
            $data[$k]['shop_name'] = empty($shop_lists[$order_info['shop_id']])?'':$shop_lists[$order_info['shop_id']]['name'];
            $data[$k]['asin'] = $v['platform_asin'];
            $data[$k]['goods_name_en'] = $v['goods_name'];

            $goods = empty($goods_lists[$v['platform_asin']])?[]:$goods_lists[$v['platform_asin']];
            $goods_child = empty($goods_child_lists[$v['platform_asin']])?[]:$goods_child_lists[$v['platform_asin']];
            /*$category = empty($goods) ? [] : Category::find()->where(['id' => $goods['category_id']])->asArray()->one();
            //$order_declare = OrderDeclare::find()->where(['order_id'=>$v['order_id'],'declare_name_cn'=>$category['name']])->asArray()->one();
            try {
                $p_goods = empty($p_goods_lists[$v['platform_asin']])?[]:$p_goods_lists[$v['platform_asin']];
                $goods_name_cn = empty($p_goods['goods_name'])?'':$p_goods['goods_name'];
                if(empty($goods_name_cn)) {
                    $goods_name_cn = empty($v['goods_name']) ? '' : PyTranslate::exec($v['goods_name'], 'zh-CN');
                }
                $data[$k]['goods_name'] = $goods_name_cn;
            } catch (\Exception $e) {
                $data[$k]['goods_name'] = '';
            }
            $data[$k]['goods_short_name'] = empty($category) ? '' : $category['name'];*/
            $weight = empty($goods_child) ? '' : ((float)$goods_child['real_weight'] * 1000);
            if (empty($weight) || $weight <= 0) {
                $weight = empty($goods) ? '' : ((float)$goods['weight'] * 1000);
            }
            $data[$k]['weight'] = $weight;
            $data[$k]['goods_pic'] = $v['goods_pic'];

            //货物类别
            /*$parent_category = '';
            if (!empty($goods['category_id'])) {
                $parent_name = [];
                Category::getParentNames($goods['category_id'], $parent_name);
                $parent_category = current($parent_name);
            }
            $data[$k]['parent_category'] = $parent_category;*/
            $data[$k]['count'] = $v['goods_num'];
            $data[$k]['buyer_name'] = $order_info['buyer_name'];
            $data[$k]['buyer_phone'] = $order_info['buyer_phone'];
            //$data[$k]['address'] = $order_info['address'].','.$order_info['postcode'].','.$order_info['city'].','.$order_info['area'].','.$order_info['country'];
            $data[$k]['address'] = $order_info['address'];
            $data[$k]['city'] = $order_info['city'];
            $data[$k]['area'] = $order_info['area'];
            $data[$k]['postcode'] = $order_info['postcode'];
            $data[$k]['country'] = $order_info['country'];
            $data[$k]['track_no'] = $order_info['track_no'];
            $data[$k]['printed_url'] = in_array($order_info['order_status'], [Order::ORDER_STATUS_UNCONFIRMED, Order::ORDER_STATUS_WAIT_PURCHASE, Order::ORDER_STATUS_CANCELLED]) ? '' : (Yii::$app->request->hostinfo . '/order/printed-pdf?order_id=' . $v['order_id']);
            //$data[$k]['asin_count'] = OrderGoods::find()->where(['platform_asin'=>$v['platform_asin']])->sum('goods_num');
            $data[$k]['order_income_price'] = $order_info['order_income_price'];
        }

        $column = [
            'date' => '日期',
            'order_id' => '订单号',
            'relation_no' => '销售单号',
            'shop_name' => '店铺名',
            'asin' => '产品ASIN',
            'goods_name' => '产品名称',
            'goods_short_name'=>'简要产品名称',
            'goods_name_en' => '英文名称',
            'weight' => '重量',
            'goods_pic' => '商品图片',
            'parent_category'=>'货物类别',
            'count' => '购买数量',
            'buyer_name' => '买家名称',
            'buyer_phone' => '电话',
            'address' => '地址',
            'city' => '城市',
            'area' => '区',
            'postcode' => '邮编',
            'country' => '国家',
            'track_no' => '物流订单号',
            'printed_url' => '打印面单PDF',
            'order_income_price' => '订单金额',
            //'asin_count' => '销量'
        ];

        return [
            'key' => array_keys($column),
            'header' => array_values($column),
            'list' => $data,
            'fileName' => '订单导出' . date('ymdhis')
        ];
    }

    /**
     * @routeName 下载发票
     * @routeDescription 下载Real订单发票
     * @throws
     */
    public function actionInvoice()
    {
        $req = Yii::$app->request;
        $order_id = $req->get('order_id');
        /*$model = Order::find()->where(['order_id'=>$order_id])->asArray()->one();
        $shop = Shop::findOne(['id'=>$model['shop_id']]);
        $pdf = new \TCPDF('P', 'mm',  'S12R', true, 'UTF-8', false);

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetFont('helvetica', '', 9);

        $pdf->AddPage();

        //发票号：R+月份+年份+-8位随机数字（如R1120-12345678）
        $invoice_no = 'R' . date('Ym', $model['date']) . '-' . CommonUtil::randString(8, 1);

        $model['invoice_no'] = $invoice_no;

        $order_goods = OrderGoods::find()->where(['order_id'=>$order_id,'goods_status'=>OrderGoods::GOODS_STATUS_NORMAL])->asArray()->all();
        $goods = [];

        foreach ($order_goods as $v){
            $price3 = $v['goods_income_price'] * $v['goods_num'];//售价
            $price1 = number_format($price3 / 1.16, 2);//税前价格 售价除以1.16
            $price2 = number_format($price1 * 0.16, 2);//增值税金额 售价乘以0.16
            $goods[] = [
                'goods_name' => $v['goods_name'],
                'goods_num' => $v['goods_num'],
                'price1' => $price1,
                'price2' => $price2,
                'price3' => $price3
            ];
        }

        $html = $this->render('/order/invoice/'.$shop['invoice_template'],[
            'model' => $model,
            'goods' => $goods
        ]);

        $pdf->writeHTML($html, true, false, true, false, '');*/
        $pdf = (new OrderService())->invoice($order_id);
        $pdf->Output('Rechnungen'.$order_id.'.pdf', 'D');
    }

    /**
     * @routeName 导入订单运费
     * @routeDescription 导入订单运费
     * @return array
     * @throws
     */
    public function actionImportFreight()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $file = UploadedFile::getInstanceByName('file');
        if (!in_array($file->extension, ['xlsx', 'xls'])) {
            return $this->FormatArray(self::REQUEST_FAIL, "只允许使用以下文件扩展名的文件：xlsx, xls。", []);
        }

        // 读取excel文件
        $data = Excel::import($file->tempName, [
            'setFirstRecordAsKeys' => false,
        ]);

        // 多Sheet
        if (isset($data[0])) {
            $data = $data[0];
        }

        $rowKeyTitles = [
            'track_no' => '物流单号',
            'freight_price' => '运费',
            'weight' => '重量',
            'track_logistics_no' => '物流转单号',
            'length' => '长',
            'width' => '宽',
            'height' => '高',
            'billed_time' => '计费时间',
            'transport_name' => '物流商',
        ];
        $rowTitles = $data[1];
        $keyMap = [];
        foreach ($rowKeyTitles as $k => $v) {
            $excelKey = array_search($v, $rowTitles);
            $keyMap[$k] = $excelKey;
        }
        if(empty($keyMap['track_no']) || empty($keyMap['freight_price']) || empty($keyMap['weight']) || empty($keyMap['billed_time']) || empty($keyMap['transport_name'])) {
            return $this->FormatArray(self::REQUEST_FAIL, "excel表格式错误", []);
        }

        $count = count($data);
        $success = 0;
        $errors = [];
        for ($i = 2; $i <= $count; $i++) {
            $row = $data[$i];
            foreach ($row as &$rowValue) {
                $rowValue = !empty($rowValue) ? str_replace(' ', '', $rowValue) : '';
            }

            foreach (array_keys($rowKeyTitles) as $rowMapKey) {
                $rowKey = isset($keyMap[$rowMapKey]) ? $keyMap[$rowMapKey] : '';
                $$rowMapKey = isset($row[$rowKey]) ? $row[$rowKey] : '';
            }

            if (empty($track_no) || empty($freight_price) || empty($billed_time)) {
                $errors[$i] = '物流单号、运费、计费时间';
                continue;
            }

            try {
                $model = new FreightPriceLog();
                if($weight > 0) {
                    $weight = ceil($weight * 100) / 100;
                }
                $size = '';
                if(!empty($length) && !empty($width) && !empty($height) && $length > 0 && $width > 0 && $height > 0) {
                    $size = $length . 'x' . $width . 'x' . $height;
                    $model['length'] = $length;
                    $model['width'] = $width;
                    $model['height'] = $height;
                }
                $order = Order::find()->where(['track_no'=>$track_no])->one();
                if(empty($order)){
                    $errors[$i] = '该订单不存在';
                    continue;
                }
                $transport = TransportProviders::find()->where(['transport_name'=>$transport_name])->one();
                if (empty($transport)){
                    $errors[$i] = '物流商不存在';
                    continue;
                }
                $order->track_logistics_no = empty($track_logistics_no)?'':$track_logistics_no;
                if($weight >0) {
                    $order->weight = $weight;
                    $model['weight'] = $weight;
                }
                $order->size = $size;
                $order->freight_price = $freight_price;
                $order->settlement_status = HelperStamp::addStamp($order['settlement_status'], Order::SETTLEMENT_STATUS_COST);
                $order_profit = OrderService::calculateProfitPrice($order);
                $order->order_profit = $order_profit;
                $order->save();
                $billed_time = str_replace([',','/'],['','-'],$billed_time);
                if(strlen($billed_time) > 17){
                    $billed_time = substr($billed_time , 0 , 17);
                }
                $billed_time = strtotime($billed_time);
                $freight_log = FreightPriceLog::find()->where(['order_id'=>$order['order_id'],'billed_time'=>$billed_time])->one();
                if (!empty($freight_log)){
                    $errors[$i] = '该订单号的计费时间已存在';
                    continue;
                }
                $model['freight_price'] = $freight_price;
                $model['billed_time'] = $billed_time;
                $model['logistics_channels_id'] = $order['logistics_channels_id'];
                $model['track_no'] = $track_no;
                $model['track_logistics_no'] = empty($track_logistics_no) ? "" : $track_logistics_no;
                $model['order_id'] = $order['order_id'];
                $model['country'] = $order['country'];
                $model['transport_code'] = $transport['transport_code'];
                $model->save();


                $order_goods = OrderGoods::find()->where(['order_id'=>$order['order_id']])->asArray()->all();
                if(count($order_goods) > 1){
                    continue;
                }

                $order_goods = current($order_goods);
                if($order_goods['goods_num'] > 1){
                    continue;
                }


                /*$goods_child = GoodsChild::find()->where(['sku_no'=>$order_goods['platform_asin']])->one();
                if(empty($goods_child)){
                    $errors[$i] = '该商品不存在';
                    continue;
                }*/
                /*if(abs($goods['weight'] - $weight) <= 0.01) { //相差0.01内不更新
                    continue;
                }*/

                $goods_data = [];
                if($order['source'] == Base::PLATFORM_FRUUGO && $order_profit < 0) {
                    $goods_data['gbp_price'] = 0;
                }
                if($weight > 0) {
                    $goods_data['weight'] = $weight;
                    $goods_data['real_weight'] = $weight;
                }
                if(!empty($size)) {
                    $goods_data['package_size'] = $size;
                }
                (new GoodsService())->updateChildPrice($order_goods['cgoods_no'],$goods_data,'导入订单运费');
            }catch (\Exception $e) {
                $errors[$i] = $e->getMessage();
                continue;
            }

            $success++;
        }

        if(!empty($errors)) {
            $lists = [];
            foreach ($errors as $i => $error) {
                $row = $data[$i];
                $info = [];
                $info['index'] = $i;
                $info['rvalue1'] = $row[$keyMap['track_no']];
                $info['rvalue2'] = $row[$keyMap['freight_price']];
                $info['rvalue3'] = $row[$keyMap['weight']];
                $info['rvalue4'] = $row[$keyMap['billed_time']];
                $info['rvalue5'] = $row[$keyMap['transport_name']];
                $info['reason'] = $error;
                $lists[] = $info;
            }
            $key = (new ImportResultService())->gen('导入订单运费', $lists);
            return $this->FormatArray(self::REQUEST_FAIL, "导入失败问题", [
                'key' => $key
            ]);
        }

        return $this->FormatArray(self::REQUEST_SUCCESS, "导入成功", []);
    }

    /**
     * @routeName 导入签收订单
     * @routeDescription 导入签收订单
     * @return array
     * @throws
     */
    public function actionImportDelivered()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $file = UploadedFile::getInstanceByName('file');
        if (!in_array($file->extension, ['xlsx', 'xls'])) {
            return $this->FormatArray(self::REQUEST_FAIL, "只允许使用以下文件扩展名的文件：xlsx, xls。", []);
        }

        // 读取excel文件
        $data = Excel::import($file->tempName, [
            'setFirstRecordAsKeys' => false,
        ]);

        // 多Sheet
        if (isset($data[0])) {
            $data = $data[0];
        }

        $rowKeyTitles = [
            'track_no' => '物流单号',
            'delivered_time' => '签收时间',
        ];
        $rowTitles = $data[1];
        $keyMap = [];
        foreach ($rowKeyTitles as $k => $v) {
            $excelKey = array_search($v, $rowTitles);
            $keyMap[$k] = $excelKey;
        }
        if(empty($keyMap['track_no'])) {
            return $this->FormatArray(self::REQUEST_FAIL, "excel表格式错误", []);
        }

        $count = count($data);
        $success = 0;
        $errors = [];
        for ($i = 2; $i <= $count; $i++) {
            $row = $data[$i];
            foreach ($row as &$rowValue) {
                $rowValue = !empty($rowValue) ? str_replace(' ', '', $rowValue) : '';
            }

            foreach (array_keys($rowKeyTitles) as $rowMapKey) {
                $rowKey = isset($keyMap[$rowMapKey]) ? $keyMap[$rowMapKey] : '';
                $$rowMapKey = isset($row[$rowKey]) ? $row[$rowKey] : '';
            }

            if (empty($track_no)) {
                $errors[$i] = '物流单号';
                continue;
            }

            try {
                $order_lists = Order::find()->where(['track_no' => $track_no,'order_status'=>Order::ORDER_STATUS_FINISH])->all();
                if (empty($order_lists)) {
                    $errors[$i] = '该订单不存在';
                    continue;
                }
                foreach($order_lists as $order){
                    if ($order['order_status'] != Order::ORDER_STATUS_FINISH) {
                        $errors[$i] = '该订单不是完成状态';
                        continue;
                    }
                    if(!empty($delivered_time)){
                        if(strlen($delivered_time) > 16){
                            $delivered_time = substr($delivered_time , 0 , 16);
                        }
                        $delivered_time = strtotime($delivered_time);
                        if($delivered_time <= $order['delivery_time']){
                            $delivered_time = time();
                        }
                    } else {
                        $delivered_time = time();
                    }
                    $order->delivered_time = $delivered_time;
                    $order->delivery_status = Order::DELIVERY_ARRIVAL;
                    $order->save();
                    OrderEventService::addEvent($order['source'], $order['shop_id'], $order['order_id'], OrderEvent::EVENT_TYPE_DELIVERED);
                }
            } catch (\Exception $e) {
                $errors[$i] = $e->getMessage();
                continue;
            }

            $success++;
        }

        if(!empty($errors)) {
            $lists = [];
            foreach ($errors as $i => $error) {
                $row = $data[$i];
                $info = [];
                $info['index'] = $i;
                $info['rvalue1'] = $row[$keyMap['track_no']];
                $info['rvalue2'] = '';
                $info['rvalue3'] = '';
                $info['reason'] = $error;
                $lists[] = $info;
            }
            $key = (new ImportResultService())->gen('导入签收订单', $lists);
            return $this->FormatArray(self::REQUEST_FAIL, "导入签收订单", [
                'key' => $key
            ]);
        }

        return $this->FormatArray(self::REQUEST_SUCCESS, "导入成功", []);
    }

    /**
     * @routeName 国内快递导入
     * @routeDescription 国内快递导入
     * @return array
     * @throws
     */
    public function actionImportFirstLogistics()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $file = UploadedFile::getInstanceByName('file');
        if (!in_array($file->extension, ['xlsx', 'xls'])) {
            return $this->FormatArray(self::REQUEST_FAIL, "只允许使用以下文件扩展名的文件：xlsx, xls。", []);
        }

        // 读取excel文件
        $data = Excel::import($file->tempName, [
            'setFirstRecordAsKeys' => false,
        ]);

        // 多Sheet
        if (isset($data[0])) {
            $data = $data[0];
        }

        $rowKeyTitles = [
            'order_id' => '订单号',
            'first_track_no' => '国内物流单号',
        ];
        $rowTitles = $data[1];
        $keyMap = [];
        foreach ($rowKeyTitles as $k => $v) {
            $excelKey = array_search($v, $rowTitles);
            $keyMap[$k] = $excelKey;
        }
        if(empty($keyMap['order_id']) || empty($keyMap['first_track_no'])) {
            return $this->FormatArray(self::REQUEST_FAIL, "excel表格式错误", []);
        }

        $count = count($data);
        $success = 0;
        $errors = [];
        for ($i = 2; $i <= $count; $i++) {
            $row = $data[$i];
            foreach ($row as &$rowValue) {
                $rowValue = !empty($rowValue) ? str_replace(' ', '', $rowValue) : '';
            }

            foreach (array_keys($rowKeyTitles) as $rowMapKey) {
                $rowKey = isset($keyMap[$rowMapKey]) ? $keyMap[$rowMapKey] : '';
                $$rowMapKey = isset($row[$rowKey]) ? $row[$rowKey] : '';
            }

            if (empty($order_id)) {
                $errors[$i] = '订单号';
                continue;
            }

            try {
                $order = Order::find()->where(['order_id' => $order_id])->one();
                if (empty($order)) {
                    $errors[$i] = '该订单不存在';
                    continue;
                }
                if ($order['order_status'] != Order::ORDER_STATUS_FINISH) {
                    $errors[$i] = '该订单不是完成状态';
                    continue;
                }
                $order->first_track_no = $first_track_no;
                //$order->delivery_status = Order::DELIVERY_ARRIVAL;
                $order->save();
                OrderEventService::addEvent($order['source'], $order['shop_id'], $order['order_id'], OrderEvent::EVENT_TYPE_FIRST_LOGISTICS);

            } catch (\Exception $e) {
                $errors[$i] = $e->getMessage();
                continue;
            }

            $success++;
        }

        if(!empty($errors)) {
            $lists = [];
            foreach ($errors as $i => $error) {
                $row = $data[$i];
                $info = [];
                $info['index'] = $i;
                $info['rvalue1'] = $row[$keyMap['order_id']];
                $info['rvalue2'] = '';
                $info['rvalue3'] = '';
                $info['reason'] = $error;
                $lists[] = $info;
            }
            $key = (new ImportResultService())->gen('导入国内快递订单', $lists);
            return $this->FormatArray(self::REQUEST_FAIL, "导入国内快递订单", [
                'key' => $key
            ]);
        }

        return $this->FormatArray(self::REQUEST_SUCCESS, "导入成功", []);
    }

    /**
     * @routeName 设置商品带电状态
     * @routeDescription 设置商品带电状态
     * @return array |Response|string
     */
    public function actionGoodsElectric()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $order_ids = $req->post('order_id');
        $electric = $req->get('electric');

        if (empty($order_ids)) {
            return $this->FormatArray(self::REQUEST_FAIL, "请选择订单", []);
        }

        $goods_nos = OrderGoods::find()
            ->where(['order_id' => $order_ids, 'goods_status' => [OrderGoods::GOODS_STATUS_UNCONFIRMED, OrderGoods::GOODS_STATUS_NORMAL]])
            ->select('goods_no')->column();
        $goods_nos = array_filter(array_unique($goods_nos));

        if (count($goods_nos) > count($order_ids) * 3) {
            return $this->FormatArray(self::REQUEST_FAIL, "商品数量过多,可能出现异常", []);
        }

        //$goods = Goods::find()->where(['sku_no' => $sku_nos])->asArray()->all();
        if (Goods::updateAll(['electric' => $electric], ['goods_no' => $goods_nos])) {
            foreach ($order_ids as $order_id) {
                (new OrderService())->recommendedLogistics($order_id,true);
            }
            return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL, "更新失败", []);
        }
    }

    /**
     * @routeName 预估物流费用
     * @routeDescription 预估物流费用
     * @throws
     */
    public function actionRecommendedLogistics()
    {
        $req = Yii::$app->request;
        $order_id = $req->get('order_id');

        $weight = 0;
        $size = '';
        $order_goods = OrderGoods::find()->where(['order_id' => $order_id])->asArray()->all();
        $purchase_price = 0;
        foreach ($order_goods as &$order_good_v) {
            $goods_child = GoodsChild::find()->where(['cgoods_no' => $order_good_v['cgoods_no']])->asArray()->one();
            if ($goods_child['real_weight'] <= 0) {
                $real_weight = $goods_child['weight'];
            }else{
                $real_weight = $goods_child['real_weight'];
            }
            $real_weight = abs($real_weight) < 0.00001?0.5:$real_weight;
            $weight += $real_weight * $order_good_v['goods_num'];
            //$order_good_v['goods'] = $goods;
            if(!empty($goods_child['package_size'])) {
                $size = $goods_child['package_size'];
            }

            $goods_source = GoodsSource::find()->where(['goods_no'=>$goods_child['goods_no'],'platform_type'=>Base::PLATFORM_1688,'is_main'=>2])->asArray()->one();
            if(!empty($goods_source)){
                $purchase_price += $goods_source['price'] * $order_good_v['goods_num'];
            }else {
                $goods_source_reference = GoodsSource::find()->where(['goods_no' => $goods_child['goods_no'], 'platform_type' => Base::PLATFORM_1688, 'is_main' => [0, 1]])->asArray()->one();
                if(!empty($goods_source_reference)) {
                    $purchase_price += $goods_source_reference['price'] * $order_good_v['goods_num'];
                }
            }
        }

        $order = Order::find()->where(['order_id'=>$order_id])->asArray()->one();
        $order_profit = ($order['order_income_price'] - $order['platform_fee']) * $order['exchange_rate'];

        $logistics = (new OrderService())->getLogisticsPrice($order_id);
        $logistics_lists = !empty($logistics['logistics'])?$logistics['logistics']:[];


        $tmp_weight = $req->get('weight');
        $tmp_logistics_lists = [];
        if($tmp_weight > 0) {
            $size_l = $req->get('size_l');
            $size_w = $req->get('size_w');
            $size_h = $req->get('size_h');
            $tmp_size = compact('size_l','size_w','size_h');
            $tmp_size = GoodsService::genSize($tmp_size);

            foreach($logistics_lists as $logistics_v) {
                $tmp_logistics_lists[] = [
                    'transport_code' => $logistics_v['transport_code'],//渠道
                    'shipping_method_id' => $logistics_v['shipping_method_id'],
                    'price' => (new OrderService())->getMethodLogisticsFreightPrice($logistics_v['shipping_method_id'],$order['country'],$tmp_weight,$tmp_size)
                ];
            }
        }
        $foam_weight = '';
        $size_arr = GoodsService::getSizeArr($size);
        if(!empty($size_arr)) {
            $foam_weight = $size_arr['size_l'] * $size_arr['size_w'] * $size_arr['size_h'] / 6000 / $weight;
            $foam_weight = round($foam_weight,1);
        }
        return $this->render('recommended_logistics', [
            'weight' => $weight,
            'size' => $size,
            'foam_weight' => $foam_weight,
            'purchase_price' => $purchase_price,
            'order_profit' => $order_profit,
            'logistics_lists' => $logistics_lists,
            'tmp_logistics_lists' => $tmp_logistics_lists
        ]);
    }


    /**
     * @routeName 订单备注
     * @routeDescription 订单备注
     * @throws
     */
    public function actionRemarks(){
        $req = Yii::$app->request;
        $order_id = $req->get('order_id');
        $info = Order::find()->where(['order_id'=>$order_id])->one();
        if ($req->isPost){
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            $model = Order::find()->where(['order_id'=>$post['order_id']])->one();
            $model['remarks'] = $post['remarks'];
            if ($model->save()){
                return $this->FormatArray(self::REQUEST_SUCCESS, "备注成功", []);
            }else{
                return $this->FormatArray(self::REQUEST_FAIL, "备注失败", []);
            }
        }
        return $this->render('remarks',['info'=>$info]);
    }

}