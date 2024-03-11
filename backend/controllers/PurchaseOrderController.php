<?php

namespace backend\controllers;

use backend\models\AdminUser;
use backend\models\search\PurchaseOrderSearch;
use common\components\statics\Base;
use common\extensions\albb\Job;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsStock;
use common\models\GoodsSource;
use common\models\Order;
use common\models\OrderGoods;
use common\models\purchase\PurchaseOrder;
use common\models\purchase\PurchaseOrderGoods;
use common\models\purchase\PurchaseProposal;
use common\models\Supplier;
use common\models\SupplierRelationship;
use common\models\User;
use common\models\warehousing\LogisticsSignLog;
use common\models\warehousing\OverseasGoodsShipment;
use common\services\goods\GoodsService;
use common\services\purchase\PurchaseOrderGoodsService;
use common\services\purchase\PurchaseOrderService;
use common\services\purchase\PurchaseProposalService;
use common\services\sys\AccessService;
use common\services\sys\CountryService;
use common\services\warehousing\ScanRecordService;
use Yii;
use common\base\BaseController;
use yii\base\Exception;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\web\Response;
use yii\web\NotFoundHttpException;

class PurchaseOrderController extends BaseController
{

    /**
     * 获取条件订单数
     */
    public static function getPurchaseOrderCount($tag = []){
        $searchModel = new PurchaseOrderSearch();
        $count = [];
        foreach ($tag as $v){
            $where = $searchModel->search(null,$v,0);
            $count[$v] = PurchaseOrder::getCountByCond($where);
        }
        return $count;
    }

    /**
     * @routeName 订单管理
     * @routeDescription 订单管理
     */
    public function actionIndex()
    {
        $req = Yii::$app->request;
        $tag = $req->get('tag', 10);
        $search = $req->get('search', 0);
        $searchModel = new PurchaseOrderSearch();
        $purchase_order_count = self::getPurchaseOrderCount([2,3,6]);
        $where = $searchModel->search(Yii::$app->request->queryParams, $tag,$search);
        $desc = $tag == 3 || $tag == 6?'ship_time asc':($tag == 4?'arrival_time desc':'add_time desc');
        if ($tag == 2){
            $desc = 'date asc';
        }
        $data = $this->lists($where, $desc, null, (in_array($tag, [1, 2, 3, 4, 5]) ? 200 : 200));

        $admin_lists = AdminUser::find()->where(['id' => AccessService::getPurchaseUserIds()])->andWhere(['=','status',AdminUser::STATUS_ACTIVE])->select(['id','nickname'])->asArray()->all();
        $admin_lists = ArrayHelper::map($admin_lists,'id','nickname');

        $sub_status_map = [];
        if($tag == 3 || $tag == 6) {
            $sub_status_map = PurchaseOrder::$order_sub_start_map[PurchaseOrder::ORDER_STATUS_SHIPPED];
        }
        if($tag == 4) {
            $sub_status_map = PurchaseOrder::$order_sub_start_map[PurchaseOrder::ORDER_STATUS_RECEIVED];
        }
        return $this->render('index', [
            'searchModel' => $searchModel,
            'list' => $data['list'],
            'pages' => $data['pages'],
            'tag' => $tag,
            'admin_arr' => $admin_lists,
            'sub_status_map' => $sub_status_map,
            'purchase_order_count' => $purchase_order_count,
        ]);
    }

    /**
     * @routeName 采购入库管理
     * @routeDescription 采购入库管理
     */
    public function actionWarehousingIndex()
    {
        $req = Yii::$app->request;
        $tag = $req->get('tag', 10);
        $last_track_no = $req->get('track_no');
        $searchModel = new PurchaseOrderSearch();
        $query = Yii::$app->request->queryParams;
        $track_no = null;
        if(empty($query['PurchaseOrderSearch']['track_no'])){
            $query['PurchaseOrderSearch']['track_no'] = -1;
        }else{
            if (strstr($last_track_no,$query['PurchaseOrderSearch']['track_no'])){
                $track_no = $last_track_no;
            }else{
                $track_no = $query['PurchaseOrderSearch']['track_no'].PHP_EOL.$last_track_no;
            }
            $tracks = explode(PHP_EOL,$track_no);
            if (in_array('',$tracks)){
                $i = array_search('',$tracks);
                unset($tracks[$i]);
            }
            if (count($tracks) > 20){
                array_pop($tracks);
                $track_no = implode(PHP_EOL,$tracks);
            }
            $query['PurchaseOrderSearch']['track_no'] = $track_no;
        }
        $where = $searchModel->search($query, $tag,1);
        $sort_data = [];
        $sort = $tag == 3?'ship_time desc':($tag == 4?'arrival_time desc':'add_time desc');
        //按物流单号指定排序
        if(!empty($searchModel->track_no)) {
            $sort_data = explode(PHP_EOL, $searchModel->track_no);
            if($sort_data > 1) {
                $sort_field = 'track_no';
            }
        }
        if(!empty($sort_field)) {
            foreach ($sort_data as &$sort_v) {
                $sort_v = "'" . trim($sort_v) . "'";
            }
            $sort = new Expression('field (' . $sort_field . ',' . implode(',', $sort_data) . ')');
        }
        $data = $this->lists($where, $sort, null, (in_array($tag, [1, 2, 3, 4, 5]) ? 20 : 20));
        $admin_lists = AdminUser::find()->where(['id' => AccessService::getPurchaseUserIds()])->andWhere(['=','status',AdminUser::STATUS_ACTIVE])->select(['id','nickname'])->asArray()->all();
        $admin_lists = ArrayHelper::map($admin_lists,'id','nickname');

        $sub_status_map = [];
        if($tag == 3 || $tag == 6) {
            $sub_status_map = PurchaseOrder::$order_sub_start_map[PurchaseOrder::ORDER_STATUS_SHIPPED];
        }
        if($tag == 4) {
            $sub_status_map = PurchaseOrder::$order_sub_start_map[PurchaseOrder::ORDER_STATUS_RECEIVED];
        }
        return $this->render('warehousing_index', [
            'searchModel' => $searchModel,
            'list' => $data['list'],
            'pages' => $data['pages'],
            'tag' => $tag,
            'admin_arr' => $admin_lists,
            'sub_status_map' => $sub_status_map,
            'track_no'=>$track_no,
        ]);
    }

    /**
     * 格式化列表
     * @param $list
     * @return array
     */
    protected function formatLists($list)
    {
        foreach ($list as &$v) {
            $order_goods = PurchaseOrderService::getOrderGoods($v['order_id']);
            $v['goods_count'] = empty($order_goods) ? 1 : count($order_goods);
            $v['goods'] = empty($order_goods) ? [[]] : $order_goods;
            $goods_num = 0;
            $goods_finish_num = 0;
            foreach ($order_goods as $goods_v) {
                $goods_num += $goods_v['goods_num'];
                $goods_finish_num += $goods_v['goods_finish_num'];
            }
            $v['goods_num'] = $goods_num;
            $v['goods_finish_num'] = $goods_finish_num;
            $channels = PurchaseOrderService::getLogisticsChannels();
            $v['logistics_channels_desc'] = empty($channels[$v['logistics_channels_id']])?'':$channels[$v['logistics_channels_id']];
            $user = User::getInfo($v['admin_id']);
            $v['admin_name'] = empty($user['nickname']) ? '' : $user['nickname'];
        }
        return $list;
    }

    public function model()
    {
        return new PurchaseOrder();
    }

    /**
     * @routeName 关联采购订单
     * @routeDescription 关联采购订单
     * @throws
     * @return string |Response |array
     */
    public function actionAssociateCreate()
    {
        $req = Yii::$app->request;
        $proposal_id = $req->get('proposal_id');
        $model = new PurchaseOrder();
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            try {
                $data = $this->associateDeal($post);
                if ((new PurchaseOrderService())->addOrder($data, $data['goods'])) {
                    return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
                } else {
                    return $this->FormatArray(self::REQUEST_FAIL, '添加失败', []);
                }
            } catch (\Exception $e) {
                return $this->FormatArray(self::REQUEST_FAIL, $e->getMessage().$e->getTraceAsString().$e->getFile().$e->getLine(), []);
            }
        }

        $proposal = PurchaseProposal::find()->where(['id'=>$proposal_id])->asArray()->one();
        if (empty($proposal)) {
            return'采购建议不存在，请刷新界面';
        }
        $goods = Goods::find()->where(['goods_no'=>$proposal['goods_no']])->asArray()->one();
        $size_info = (new GoodsService())->getSizeArr($goods['size']);
        return $this->render('associate', ['proposal_id' => $proposal_id,'goods'=>$goods,'size'=>$size_info]);
    }

    /**
     * 关联处理
     * @param $info
     * @return array|bool
     */
    public function associateDeal($info)
    {
        $proposal_id = $info['proposal_id'];
        $relation_no = trim($info['relation_no']);
        $goods_num = $info['goods_num'];

        $size = '';
        if(!empty($info['size_l']) && !empty($info['size_w']) && !empty($info['size_h'])) {
            $size = $info['size_l'] . 'x' . $info['size_w'] . 'x' . $info['size_h'];
        }

        if($info['weight'] <= 0) {
            throw new \Exception('重量需大于0');
        }

        if($goods_num <= 0) {
            throw new \Exception('商品数量需大于0');
        }

        $job = new Job();
        $order_info = $job->getOrderInfo($relation_no);
        if(empty($order_info)){
            throw new \Exception('采购单号无效');
        }

        $data = [
            'source' => Base::PLATFORM_1688,
            'relation_no' => $relation_no,
        ];

        //下单时间
        if(!empty($order_info['baseInfo']['createTime'])) {
            $create_time = $order_info['baseInfo']['createTime'];
            $create_time = substr($create_time, 0, 4) . '-' .
                substr($create_time, 4, 2) . '-' .
                substr($create_time, 6, 2) . ' ' .
                substr($create_time, 8, 2) . ':' .
                substr($create_time, 10, 2) . ':' .
                substr($create_time, 12, 2);
            $data['date'] = strtotime($create_time);
        }

        //交易状态，waitbuyerpay:等待买家付款;waitsellersend:等待卖家发货;waitbuyerreceive:等待买家收货;confirm_goods:已收货;success:交易成功;cancel:交易取消;terminated:交易终止;未枚举:其他状态
        $track_no = '';
        $logistics_channels_id = '';
        $delivered_time = 0;
        if (!empty($order_info['nativeLogistics']) && !empty($order_info['nativeLogistics']['logisticsItems'])) {
            $logistics = current($order_info['nativeLogistics']['logisticsItems']);
            $track_no = empty($logistics['logisticsBillNo']) ? '' : $logistics['logisticsBillNo'];
            $logistics_channels_id = empty($logistics['logisticsCompanyNo']) ? '' : $logistics['logisticsCompanyNo'];
            $delivered_time = empty($logistics['deliveredTime']) ? '' : $logistics['deliveredTime'];
            if(!empty($delivered_time)) {
                $delivered_time = substr($delivered_time, 0, 4) . '-' .
                    substr($delivered_time, 4, 2) . '-' .
                    substr($delivered_time, 6, 2) . ' ' .
                    substr($delivered_time, 8, 2) . ':' .
                    substr($delivered_time, 10, 2) . ':' .
                    substr($delivered_time, 12, 2);
                $delivered_time = strtotime($delivered_time);
            }
        }
        $data['track_no'] = $track_no;
        $data['logistics_channels_id'] = $logistics_channels_id;
        if (!empty($logistics_channels_id)) {
            $data['ship_time'] = empty($delivered_time)?0:$delivered_time;
        }

        $goods_data = [];
        if (count($order_info['productItems']) > 1) {
            throw new \Exception('存在多件商品');
        }
        $product_items = current($order_info['productItems']);

        $proposal = PurchaseProposal::find()->where(['id' => $proposal_id])->asArray()->one();
        //$goods = Goods::find()->where(['goods_no' => $proposal['goods_no']])->asArray()->one();
        $goods_pic = $product_items['productImgUrl'];
        $product_price = $product_items['price'];//单价
        if($product_items['entryDiscount'] < 0) {//存在优惠的情况下单价不准确
            $product_price = $product_items['itemAmount'] / $goods_num;
        }
        $goods_pic = empty($goods_pic[1]) ? '' : $goods_pic[1];
        $goods_data['sku_no'] = $proposal['sku_no'];
        $goods_data['goods_no'] = $proposal['goods_no'];
        $goods_data['cgoods_no'] = $proposal['cgoods_no'];
        $goods_data['goods_name'] = $product_items['name'];
        $goods_data['goods_pic'] = $goods_pic;
        $goods_data['goods_num'] = $goods_num;
        $goods_data['goods_price'] = $product_price;
        $goods_data['goods_weight'] = $info['weight'];
        $goods_data['goods_colour'] = empty($info['colour'])?'':$info['colour'];
        $goods_data['goods_url'] = 'https://detail.1688.com/offer/'.$product_items['productID'].'.html';
        $goods_data['specification'] = $info['specification'];
        $goods_data['size'] = $size;
        $electric = $info['electric'];
        if(empty($info['electric'])){
            $electric = Base::ELECTRIC_ORDINARY;
        }
        $goods_data['electric'] = $electric;

        $data['goods'][] = $goods_data;

        $data['freight_price'] = $order_info['baseInfo']['shippingFee'];
        $data['remarks'] = '';
        $data['admin_id'] = Yii::$app->user->identity->id;
        $data['create_way'] = PurchaseOrder::CREATE_WAY_ASSOCIATE;

        //是否安骏地址
        $warehouse = 2;
        $to_area = $order_info['baseInfo']['receiverInfo']['toArea'];
        if (strpos($to_area, '安骏') !== false) {
            $warehouse = 1;
        }
        $data['warehouse'] = $warehouse;
        return $data;
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
        $proposal_id = $req->get('proposal_id');
        $ovg_id = $req->get('ovg_id');
        $model = new PurchaseOrder();
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $data = $req->post();
            try {
                $data = $this->dataDeal($data);
                $data['admin_id'] = Yii::$app->user->identity->id;
                $data['create_way'] = PurchaseOrder::CREATE_WAY_BACKSTAGE;
                if ((new PurchaseOrderService())->addOrder($data, $data['goods'])) {
                    return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
                } else {
                    return $this->FormatArray(self::REQUEST_FAIL, '添加失败', []);
                }
            } catch (\Exception $e) {
                return $this->FormatArray(self::REQUEST_FAIL, $e->getMessage(), []);
            }
        }
        $order_goods = [];
        $cgoods_nos = [];
        $cgoods_lists = [];
        if(!empty($proposal_id)) {
            $proposal = PurchaseProposal::find()->where(['id'=>$proposal_id])->asArray()->one();
            if (empty($proposal)) {
                return'采购建议不存在，请刷新界面';
            }
            $cgoods_nos = (array)$proposal['cgoods_no'];
            $cgoods_lists[$proposal['cgoods_no']] = [
                'cgoods_nos' => $proposal['cgoods_no'],
                'goods_num' => $proposal['proposal_stock'],
            ];
        }
        $lock_goods = false;
        if(!empty($ovg_id)) {
            $ovg_id = explode(',',$ovg_id);
            $overseas_goods_shipment = OverseasGoodsShipment::find()->where(['id'=>$ovg_id,'status'=>OverseasGoodsShipment::STATUS_WAIT_PURCHASE])->asArray()->all();
            if (empty($overseas_goods_shipment)) {
                return'采购商品不存在，请刷新界面';
            }
            $cgoods_nos = ArrayHelper::getColumn($overseas_goods_shipment, 'cgoods_no');
            $supplier_id = null;
            $warehouse_id = null;
            foreach ($overseas_goods_shipment as $item) {
                if(is_null($supplier_id)) {
                    $supplier_id = $item['supplier_id'];
                }
                if($supplier_id != $item['supplier_id']) {
                    return '必须为同一供应商,存在不同供应商';
                }

                if(is_null($warehouse_id)) {
                    $warehouse_id = $item['warehouse_id'];
                }
                if($warehouse_id != $item['warehouse_id']) {
                    return '必须为同一仓库,存在不同仓库商品';
                }

                $cgoods_lists[$item['cgoods_no']] = [
                    'cgoods_nos' => $item['cgoods_no'],
                    'goods_num' => $item['num'],
                    'ovg_id' => $item['id']
                ];
            }

            if(!empty($supplier_id)) {
                $model->supplier_id = $supplier_id;
                $model->source = Base::PLATFORM_SUPPLIER;
            }
            $model->warehouse = $warehouse_id;
            $lock_goods = true;
        }

        if(!empty($cgoods_nos)) {
            $goods_lists = GoodsChild::find()->where(['cgoods_no' => $cgoods_nos])->asArray()->all();
            $goods_nos = ArrayHelper::getColumn($goods_lists, 'goods_no');
            $goods_sources = GoodsSource::find()
                ->where(['goods_no' => $goods_nos, 'platform_type' => [Base::PLATFORM_1688], 'is_main' => 2])->indexBy('goods_no')->all();
            foreach ($goods_lists as $goods) {
                $goods_source = empty($goods_sources[$goods['goods_no']]) ? [] : $goods_sources[$goods['goods_no']];
                if (empty($cgoods_lists[$goods['cgoods_no']])) {
                    continue;
                }
                $order_good = $cgoods_lists[$goods['cgoods_no']];
                $goods_name = '';
                $goods_url = '';
                if (!empty($goods_source)) {
                    $goods_name = $goods_source['platform_title'];
                    $goods_url = $goods_source['platform_url'];
                }

                $image = json_decode($goods['goods_img'], true);
                $image = empty($image) || !is_array($image) ? '' : current($image)['img'];
                $weight = $goods['real_weight'] > 0 ? $goods['real_weight'] : $goods['weight'];
                if (!empty($model->supplier_id)) {
                    $purchase_amount = SupplierRelationship::find()->where(['goods_no' => $goods['goods_no'],'supplier_id' => $model->supplier_id])->select('purchase_amount')->scalar();
                }
                $order_goods[] = [
                    'ovg_id' => empty($order_good['ovg_id'])?0:$order_good['ovg_id'],
                    'sku_no' => $goods['sku_no'],
                    'goods_no' => $goods['goods_no'],
                    'goods_name' => $goods_name,
                    'goods_pic' => $image,
                    'goods_num' => $order_good['goods_num'],
                    'goods_price' => empty($purchase_amount) ? $goods['price'] : $purchase_amount,
                    'goods_url' => $goods_url,
                    'goods_weight' => empty($weight) ? '' : $weight,
                ];
            }
        }
        return $this->render('update', [
            'model' => $model,
            'order_goods' => json_encode($order_goods),
            'lock_goods' =>$lock_goods,
            'again' => null
        ]);
    }

    /**
     *
     * @param $data
     * @return mixed
     * @throws Exception
     */
    private function dataDeal($data)
    {
        $data['relation_no'] = trim($data['relation_no']);
        $data['date'] = empty($data['date'])?0:strtotime($data['date']);
        $goods = [];
        if (!empty($data['goods'])) {
            foreach ($data['goods']['goods_id'] as $k => $v) {
                $sku_no = empty($data['goods']['sku_no'][$k]) ? '' : trim($data['goods']['sku_no'][$k]);
                $goods_child_info = GoodsChild::find()->where(['sku_no' => $sku_no])->asArray()->one();
                if (empty($goods_child_info)) {
                    throw new Exception('商品' . $sku_no . '不存在');
                }
                if(!empty($data['goods']['goods_pic'][$k])){
                    $image = $data['goods']['goods_pic'][$k];
                }else {
                    if(!empty($goods_child_info['goods_img'])){
                        $image = $goods_child_info['goods_img'];
                    } else {
                        $goods_info = Goods::find()->where(['sku_no' => $sku_no])->asArray()->one();
                        $image = json_decode($goods_info['goods_img'], true);
                        $image = empty($image) || !is_array($image) ? '' : current($image)['img'];
                    }
                }
                $goods[] = [
                    'id' => empty($data['goods']['goods_id'][$k]) ? '' : $data['goods']['goods_id'][$k],
                    'sku_no' => $sku_no,
                    'goods_no' => $goods_child_info['goods_no'],
                    'cgoods_no' => $goods_child_info['cgoods_no'],
                    'goods_name' => empty($data['goods']['goods_name'][$k]) ? '' : $data['goods']['goods_name'][$k],
                    'goods_pic' => $image,
                    'goods_num' => empty($data['goods']['goods_num'][$k]) ? 0 : $data['goods']['goods_num'][$k],
                    'goods_price' => empty($data['goods']['goods_price'][$k]) ? 0 : $data['goods']['goods_price'][$k],
                    'goods_url'  => empty($data['goods']['goods_url'][$k]) ? '' : $data['goods']['goods_url'][$k],
                    'goods_weight'  => empty($data['goods']['goods_weight'][$k]) ? 0 : $data['goods']['goods_weight'][$k],
                    'ovg_id'  => empty($data['goods']['ovg_id'][$k]) ? 0 : $data['goods']['ovg_id'][$k],
                ];
            }
        }
        $data['goods'] = $goods;
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
            try{
                $data = $this->dataDeal($data);
                if($model->order_status == PurchaseOrder::ORDER_STATUS_WAIT_SHIP && !empty($data['track_no'])) {
                    $data['order_status'] = PurchaseOrder::ORDER_STATUS_SHIPPED;
                    if (empty($model['ship_time'])) {
                        $data['ship_time'] = time();
                    }
                }
                $goods = empty($data['goods']) ? [] : $data['goods'];
                if ($model->load($data, '') == false) {
                    return $this->FormatArray(self::REQUEST_FAIL, "参数异常", []);
                }
                $model['supplier_id'] = isset($data['supplier_id']) ? $data['supplier_id'] : 0;
                if ($model->save()) {
                    (new PurchaseOrderGoodsService())->updateOrderGoods($model['order_id'], $goods);
                    return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
                } else {
                    return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
                }
            } catch (\Exception $e) {
                return $this->FormatArray(self::REQUEST_FAIL, $e->getMessage(), []);
            }
        } else {
            $lock_goods = false;
            $order_goods = PurchaseOrderService::getOrderGoods($model['order_id']);
            foreach ($order_goods as $order_good) {
                if($order_good['ovg_id'] > 0){
                    $lock_goods = true;
                }
            }
            $logistics_channels_ids = PurchaseOrderService::getLogisticsChannels();
            $model['date'] = empty($model['date']) ? '' : date('Y-m-d H:i:s', $model['date']);
            return $this->render('update', [
                'model' => $model,
                'order_goods' => json_encode($order_goods),
                'logistics_channels_id' => $logistics_channels_ids,
                'lock_goods' =>$lock_goods,
                'again' => null
            ]);
        }
    }

    /**
     * @routeName 到货
     * @routeDescription 到货
     * @throws
     */
    public function actionArrival()
    {
        $req = Yii::$app->request;
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $data = $req->post();
            try {
                if (empty($data['order_id'])) {
                    return $this->FormatArray(self::REQUEST_FAIL, "订单不能为空", []);
                }
                $order_ids = $data['order_id'];
                $logistics_no = $data['logistics_no'];
                $result = true;
                foreach ($order_ids as $order_id) {
                    if (empty($data['finish_num'][$order_id])) {
                        continue;
                    }
                    $arrival_goods = $data['finish_num'][$order_id];
                    $order_result = (new PurchaseOrderService())->receivedGoods($order_id, $arrival_goods);
                    $result = $result === false ? false : $order_result;
                }

                //修改商品
                foreach ($data['weight'] as $weight_k => $weight_v) {
                    $cgoods_no = $weight_k;
                    if (empty($cgoods_no)) {
                        continue;
                    }

                    if (empty($weight_v)) {
                        continue;
                    }
                    $child_data = [];
                    //重量变更
                    $child_data['real_weight'] = $weight_v;
                    $size = '';
                    if (!empty($data['size_l']) && !empty($data['size_w']) && !empty($data['size_h']) && isset($data['size_l'][$cgoods_no]) && isset($data['size_w'][$cgoods_no]) && isset($data['size_h'][$cgoods_no])) {
                        $size = $data['size_l'][$cgoods_no] . 'x' . $data['size_w'][$cgoods_no] . 'x' . $data['size_h'][$cgoods_no];
                    }
                    $child_data['package_size'] = $size;
                    (new GoodsService())->updateChildPrice($cgoods_no,$child_data,'采购入库');
                }

                //物流入库
                if (!empty($logistics_no)) {
                    (new ScanRecordService())->logisticsStorage($logistics_no);
                }
                if ($result) {
                    return $this->FormatArray(self::REQUEST_SUCCESS, "提交成功", []);
                } else {
                    return $this->FormatArray(self::REQUEST_FAIL, '提交失败', []);
                }
            } catch (\Exception $e) {
                return $this->FormatArray(self::REQUEST_FAIL, $e->getMessage(), []);
            }
        } else {
            $where = [];
            $logistics_no = $req->get('logistics_no');
            if (!empty($logistics_no)) {
                $where = ['track_no' => $logistics_no];
            }
            $order_id = $req->get('order_id');
            if (!empty($order_id)) {
                $where = ['order_id' => $order_id];
            }
            if (empty($where)) {
                return '参数异常';
            }
            //$where['order_status'] = PurchaseOrder::ORDER_STATUS_SHIPPED;
            $purchase_order = PurchaseOrder::find()->where($where)
                ->andWhere(['!=','order_status',PurchaseOrder::ORDER_STATUS_CANCELLED])->asArray()->all();
            foreach ($purchase_order as &$order_v) {
                $order_goods = PurchaseOrderService::getOrderGoods($order_v['order_id']);
                $order_goods_lists = [];
                foreach ($order_goods as $order_good_v){
                    if($order_good_v['ovg_id'] > 0) {
                        return '不支持海外仓采购到货';
                    }
                    $goods = Goods::find()->where(['goods_no'=>$order_good_v['goods_no']])->one();
                    $goods_child = GoodsChild::find()->where(['cgoods_no'=>$order_good_v['cgoods_no']])->one();
                    if(!empty($goods_child['goods_img'])) {
                        $image = $goods_child['goods_img'];
                    }else{
                        $image = json_decode($goods['goods_img'], true);
                        $image = empty($image) || !is_array($image) ? '' : current($image)['img'];
                    }
                    $order_good_v['goods_id'] = $goods['id'];
                    $order_good_v['goods_pic'] = $image;
                    $order_good_v['real_weight'] = $goods_child['real_weight'] == 0 ?$goods_child['weight']:$goods_child['real_weight'];
                    $order_good_v['ccolour'] = $goods_child['colour'];
                    $order_good_v['csize'] = $goods_child['size'];
                    $order_good_v['specification'] = $goods['specification'];
                    $order_good_v['size'] = (new GoodsService())->getSizeArr($goods_child['package_size']);

                    $all_order_lists = (new PurchaseProposalService())->getOrderQuery('og.id as id,o.id as oid,o.source,o.integrated_logistics,o.track_no,o.order_id,o.remarks,o.country,o.order_status,goods_num as num',$goods_child['sku_no'],$order_v['warehouse']);

                    $order_good_v['shelves_no'] = GoodsStock::find()->where(['cgoods_no'=>$order_good_v['cgoods_no'],'warehouse'=>$order_v['warehouse']])->select('shelves_no')->scalar();

                    $order_good_v['order'] = [];
                    foreach ($all_order_lists as $v) {
                        $count = OrderGoods::find()->where(['order_id' => $v['order_id'], 'goods_status' => [OrderGoods::GOODS_STATUS_UNCONFIRMED, OrderGoods::GOODS_STATUS_NORMAL]])->count();
                        $track_no_o = $v['track_no'];
                        if($v['integrated_logistics'] == Order::INTEGRATED_LOGISTICS_YES){
                            $track_no_o = $v['order_id'];
                        }
                        $order_good_v['order'][$track_no_o][] = [
                            'order_id' => $v['order_id'],
                            'num' => $v['num'],
                            'has_move' => $count > 1,
                            'track_no' => $v['track_no'],
                            'platform' => Base::$platform_maps[$v['source']],
                            'country' => CountryService::getName($v['country'], false, true),
                            'remarks' => $v['remarks'],
                            'order_status' => $v['order_status'],
                        ];
                    }
                    $order_goods_lists[] = $order_good_v;
                }
                $order_v['order_goods'] = $order_goods_lists;
            }
            return $this->render('arrival', [
                'order' => $purchase_order,
                'logistics_no' => $logistics_no,
                'order_goods' => null,
            ]);
        }
    }

    /**
     * @param $id
     * @return null|PurchaseOrder
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        if (($model = PurchaseOrder::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
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
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $order_id = $req->get('order_id');
        if ((new PurchaseOrderService())->cancel($order_id)) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "取消成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_SUCCESS, "取消失败", []);
        }
    }

    /**
     * @routeName 结束剩余采购
     * @routeDescription 结束剩余采购
     * @return array
     * @throws
     */
    public function actionFinish()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $order_id = $req->get('order_id');
        if ((new PurchaseOrderService())->finish($order_id)) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "取消成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_SUCCESS, "取消失败", []);
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
        $model = new PurchaseOrder();
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $data = $req->post();
            try {
                $data = $this->dataDeal($data);
                $data['admin_id'] = Yii::$app->user->identity->id;
                $data['create_way'] = PurchaseOrder::CREATE_WAY_BACKSTAGE;
                if ((new PurchaseOrderService())->addOrder($data,$data['goods'])) {
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
        $order_goods = PurchaseOrderService::getOrderGoods($model['order_id']);
        $model['id'] = '';
        $lock_goods = false;
        return $this->render('update', ['model' => $model, 'order_goods' => json_encode($order_goods),'lock_goods'=>$lock_goods,'again'=>1]);
    }

    /**
     * @routeName 批量到货
     * @routeDescription 批量到货
     * @return array
     * @throws
     */
    public function actionBatchReceived()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;

        $is_all = $req->get('all');
        if (!empty($is_all)) {
            $tag = $req->get('tag');
            $searchModel = new PurchaseOrderSearch();
            $where = $searchModel->search([], $tag);
            $where['order_status'] = PurchaseOrder::ORDER_STATUS_SHIPPED;
            $order_ids = PurchaseOrder::getAllByCond($where,['id' => SORT_DESC],'order_id');
            $order_id = ArrayHelper::getColumn($order_ids,'order_id');
            if (empty($order_id)) {
                return $this->FormatArray(self::REQUEST_FAIL, "无可到货的订单", []);
            }
        } else {
            $order_id = $req->post('order_id');
            if (empty($order_id)) {
                return $this->FormatArray(self::REQUEST_FAIL, "请选择订单", []);
            }
        }

        $purchase_order_service = new PurchaseOrderService();
        foreach ($order_id as $v) {
            $purchase_order_service->received($v);
        }
        return $this->FormatArray(self::REQUEST_SUCCESS, "操作成功", []);
    }

    /**
     * @routeName 标记已出发
     * @routeDescription 标记已出发
     * @return array
     * @throws
     */
    public function actionBatchOnWay()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;

        $order_id = $req->post('order_id');
        if (empty($order_id)) {
            return $this->FormatArray(self::REQUEST_FAIL, "请选择订单", []);
        }

        PurchaseOrder::updateAll(['logistics_status' => PurchaseOrder::LOGISTICS_STATUS_ON_WAY], ['order_id' => $order_id]);
        return $this->FormatArray(self::REQUEST_SUCCESS, "操作成功", []);
    }

    /**
     * @routeName 物流跟踪信息
     * @routeDescription 物流跟踪信息
     * @return array
     * @throws
     */
    public function actionLogisticsTrace()
    {
        $req = Yii::$app->request;
        $order_id = $req->get('order_id');
        $order = PurchaseOrder::findOne(['order_id'=>$order_id]);
        $job = new Job();
        $logistics = $job->getOrderLogisticsTrace($order['relation_no']);
        $logistics = current($logistics);
        $logistics_steps = [];
        if(!empty($logistics) && !empty($logistics['logisticsSteps'])){
            $logistics_steps = $logistics['logisticsSteps'];
            $logistics_steps = array_reverse($logistics_steps);
        }
        return $this->render('logistics_trace', ['logistics' => $logistics_steps]);
    }

    /**
     * @routeName 物流单号导出
     * @routeDescription 物流单号导出
     * @return array |Response|string
     */
    public function actionExport()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $tag = $req->get('tag',10);
        $searchModel = new PurchaseOrderSearch();
        $where = $searchModel->search(Yii::$app->request->queryParams,$tag);
        $where['and'][] = ['order_status'=>[PurchaseOrder::ORDER_STATUS_WAIT_SHIP,PurchaseOrder::ORDER_STATUS_SHIPPED,PurchaseOrder::ORDER_STATUS_RECEIVED]];
        $list = PurchaseOrder::getAllByCond($where,'ship_time desc');
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
        $order_goods = PurchaseOrderGoods::find()->where(['order_id'=>$order_ids])->asArray()->all();
        $order_lists = ArrayHelper::index($list, 'order_id');

        $channels = PurchaseOrderService::getLogisticsChannels();
        $data = [];
        foreach ($order_goods as $k => $v) {
            if (empty($order_lists[$v['order_id']])) {
                continue;
            }
            $order_info = $order_lists[$v['order_id']];
            $data[$k]['logistics_channels_name'] = empty($channels[$order_info['logistics_channels_id']])?'':$channels[$order_info['logistics_channels_id']];
            $data[$k]['track_no'] = empty($order_info['track_no'])?'':$order_info['track_no'];
            $data[$k]['sku_no'] = $v['sku_no'];
            $data[$k]['goods_num'] = $v['goods_num'];
            $data[$k]['freight_price'] = $order_info['freight_price'];
            $data[$k]['goods_price'] = $v['goods_price'];
        }

        $column = [
            'logistics_channels_name' => '物流公司名称',
            'track_no' => '物流单号',
            'sku_no' => '店铺sku',
            'goods_num' => '入仓数量',
            'freight_price' => '运费',
            'goods_price'=>'单价',
        ];
        return [
            'key' => array_keys($column),
            'header' => array_values($column),
            'list' => $data,
            'fileName' => '订单导出' . date('ymdhis')
        ];
    }


}