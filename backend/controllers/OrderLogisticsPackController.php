<?php

namespace backend\controllers;

use common\components\statics\Base;
use common\models\goods\GoodsChild;
use common\models\sys\ShippingMethod;
use common\models\TransportProviders;
use common\services\sys\ExportService;
use common\services\warehousing\WarehouseService;
use Yii;
use common\models\OrderLogisticsPack;
use backend\models\search\OrderLogisticsPackSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;
use yii\helpers\ArrayHelper;
use common\base\BaseController;
use common\models\User;
use common\models\Order;
use common\services\transport\TransportService;
use common\services\purchase\PurchaseOrderService;
use backend\models\search\OrderLogisticsPackAssociationSearch;
use common\models\OrderLogisticsPackAssociation;
use backend\models\search\OrderSearch;
use yii\data\Pagination;
use common\models\Shop;
use common\models\OrderGoods;
use common\services\sys\CountryService;
use common\services\goods\GoodsService;
use common\models\sys\Country;
use common\models\OrderStockOccupy;
use const vierbergenlars\SemVer\Internal\caretTrimReplace;

class OrderLogisticsPackController extends BaseController
{
    private $list_type = GoodsService::SOURCE_METHOD_OWN;
    private $list_tag = 10;


     protected function orderFormatLists($list)
    {
        $shop_ids = ArrayHelper::getColumn($list,'shop_id');
        $shop = Shop::find()->select(['id','name','currency'])->where(['id'=>$shop_ids])->indexBy('id')->asArray()->all();
        $order_ids = [];
        foreach ($list as $v) {
            $order_ids[] = $v['order_id'];
        }
        $order_goods_lists = OrderGoods::find()->where(['order_id' => $order_ids, 'goods_status' => [OrderGoods::GOODS_STATUS_UNCONFIRMED, OrderGoods::GOODS_STATUS_NORMAL]])->asArray()->all();
        $order_goods_lists = ArrayHelper::index($order_goods_lists,null,'order_id');
        $order_stock_occupy = OrderStockOccupy::find()->where(['order_id'=>$order_ids])->select('order_id,sku_no,count(*) cut,sum(case when type = 2 then 1 else 0 end) stock_cut')->groupBy('order_id,sku_no')->asArray()->all();
        $order_stock_occupy = ArrayHelper::index($order_stock_occupy, 'sku_no', 'order_id');        
        $sku_num_lists = [];
        
        foreach ($list as &$v){
            $order_goods = $order_goods_lists[$v['order_id']];
            if($this->list_type == GoodsService::SOURCE_METHOD_OWN){
                $v['logistics_channels_desc'] = empty($v['logistics_channels_id'])?$v['logistics_channels_name']:TransportService::getShippingMethodName($v['logistics_channels_id']);
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
                    $goods_info = empty($goods_lists[$goods_v['goods_no']])?'':$goods_lists[$goods_v['goods_no']];
                    $goods_v['weight'] = empty($goods_info)?0:($goods_info['real_weight'] > 0?$goods_info['real_weight']:0);
                    $goods_v['size'] = empty($goods_info)?'':$goods_info['size'];
                    //是否库存占用
                    if(in_array($v['order_status'],[Order::ORDER_STATUS_WAIT_PRINTED_OUT_STOCK,Order::ORDER_STATUS_WAIT_PRINTED]) ||
                        ($v['order_status'] == Order::ORDER_STATUS_WAIT_SHIP && $v['warehouse'] == WarehouseService::WAREHOUSE_OWN)) {
                            $goods_v['stock_occupy'] = !empty($order_stock_occupy[$v['order_id']])
                            && !empty($order_stock_occupy[$v['order_id']][$goods_v['platform_asin']]) &&
                            $order_stock_occupy[$v['order_id']][$goods_v['platform_asin']]['stock_cut'] > 0 ? 1 : 0;
                        }else {
                            $goods_v['stock_occupy'] = !empty($order_stock_occupy[$v['order_id']]) && !empty($order_stock_occupy[$v['order_id']][$goods_v['platform_asin']]) && $order_stock_occupy[$v['order_id']][$goods_v['platform_asin']]['cut'] > 0 ? 1 : 0;
                        }
                }
            }
            $v['shop_name'] = empty($shop[$v['shop_id']])?'':$shop[$v['shop_id']]['name'];
            $order_goods = $order_goods_lists[$v['order_id']];
            $v['goods_count'] = empty($order_goods)?1:count($order_goods);
            
            $v['goods'] = empty($order_goods)?[[]]:$order_goods;
            $v['country'] = CountryService::getName($v['country']);
            $v['currency'] = empty($shop[$v['shop_id']])?'':$shop[$v['shop_id']]['currency'];
        }
        return $list;
    }
    
    
    /**
     * @routeName OrderLogisticsPack主页
     * @routeDescription OrderLogisticsPack主页
     */
    public function actionIndex()
    {
        return $this->render('index');
    }
 
    /**
     * @routeName OrderLogisticsPack列表
     * @routeDescription OrderLogisticsPack列表
     */
    public function actionList()
    {
        $req = Yii::$app->request;
        Yii::$app->response->format=Response::FORMAT_JSON;
        $tag = $req->get('tag',10);
        $order_logistics_pack = new OrderLogisticsPack();
        $searchModel=new OrderLogisticsPackSearch();
        $dataProvider=$searchModel->search(Yii::$app->request->queryParams);
        $data=array_values($dataProvider->getModels()); 
        $data= ArrayHelper::toArray($data); 
        //获取orderlogtsiticpack表的所有id
        $orderSearch=new OrderSearch();
        $channels = PurchaseOrderService::getLogisticsChannels();
        $transport = TransportProviders::getTransportName();
        foreach ($data as $key=>$value){
            $data[$key]['ship_dates'] = Yii::$app->formatter->asDate($value['ship_date']);
            $data[$key]['channels_type'] = empty($transport[$value['channels_type']])?'':$transport[$value['channels_type']];
            $data[$key]['admin_id'] = User::getInfoNickname($value['admin_id']); 
            $data[$key]['courier'] = empty($channels[$value['courier']])?'':$channels[$value['courier']];
            $data[$key]['start_time'] = strtotime(date('y-m-d',$value['ship_date']));
            $data[$key]['end_time'] = strtotime(date('y-m-d',$value['ship_date'])) + 172800;
            $data[$key]['now_time'] = time();
            $data[$key]['day'] = date('d',$value['ship_date']);
            $data[$key]['month'] = date('m',$value['ship_date']);
        }
        return $this->FormatLayerTable(self::REQUEST_LAY_SUCCESS,"获取成功",$data,$dataProvider->totalCount);
    }
    
    /**
     * @routeName 查看订单包裹
     * @routeDescription 查看订单包裹
     * @throws
     */
    public function actionView()
    {
        $req = Yii::$app->request;
        $this->list_type = GoodsService::SOURCE_METHOD_OWN;
        $id = $req->get('id');
        $info = OrderLogisticsPack::find()->where(['id'=>$id])->asArray()->one();
        
        $tag = $req->get('tag',10);
        $page = Yii::$app->request->get('page', 1);
        $pageSize = Yii::$app->request->get('limit', 20); 
        $order_list= OrderLogisticsPackAssociation::find()->where(['logistics_pack_id'=>$id])->select(['logistics_pack_id','order_id'])->asArray()->all();    
        $orders = [];
        foreach ($order_list as $order_v){
            $orders[] = $order_v['order_id'];
        }
        $searchModel=new OrderSearch();
        if (!empty($orders)){          
        $searchModel->order_id =$orders;
        $where=$searchModel->ownSearch([],$tag);
        $sort = 'date desc';
        $count = Order::getCountByCond($where);
        $list = Order::getListByCond($where, $page, $pageSize, $sort);
        $pages = new Pagination(['totalCount' => $count, 'pageSize' => $pageSize]);
        $list = $this->orderFormatLists($list);
        }else {
            $where=$searchModel->ownSearch($orders,$orders);
            $sort = 'date desc';
            $count = null;
            $list = null;
            $pages = new Pagination(['totalCount' => $count, 'pageSize' => $pageSize]);
            $list = null;
        }
       
        $tag = $req->get('tag',10);
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
            $where = $searchModel->ownSearch($query_params, $tag);
            $sort = $tag == 2 ? 'order_income_price desc' : 'date desc';
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
        return $this->render('view', [
            'info' => $info,
            'searchModel' => $searchModel,
            'list' => $list,
            'pages' => $pages,
            'tag'=>$tag,
            'logistics_channels_ids'=>$logistics_channels_ids,
            'recommended_logistics_channels_ids'=>$recommended_logistics_channels_ids,
            'country_arr' => $country_arr
        ]);
        
    }

    public function actionCreate(){
        $req = Yii::$app->request;
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $admin_id = Yii::$app->user->identity->id;
            $model = new OrderLogisticsPack();
            $model->load($req->post(), '');
            $model['ship_date'] = strtotime($model['ship_date']);
            $model->admin_id = $admin_id??'';
            if ($model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
            }
        }
        return $this->render('create');
    }
    
    
    /**
     * @routeName 更新订单包裹
     * @routeDescription 更新订单包裹 
     * @throws
     */
    public function actionUpdate()
    {
        $req = Yii::$app->request;
        $admin_id = Yii::$app->user->identity->id;
        $id = $req->get('id');
        if ($req->isPost) {
            $id = $req->post('id');
        }
        $model = $this->findModel($id);
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($model->load($req->post(), '') == false) {
                return $this->FormatArray(self::REQUEST_FAIL, "参数异常", []);
            }
            $model->admin_id = $admin_id??'';
            if ($model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
            }
        } else {
            return $this->render('update', ['info' => $model->toArray()]);
        }
    }

    /**
     * @routeName 删除订单包裹
     * @routeDescription 删除订单包裹
     * @return array
     * @throws
     */
    public function actionDelete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = (int)$req->get('id');
        $model = $this->findModel($id);
        if ($model->delete()) {
            OrderLogisticsPackAssociation::deleteAll(['logistics_pack_id'=>$id]);
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除失败", []);
        }
    }


    /**
     * @routeName 生成订单包裹
     * @routeDescription 生成订单包裹
     * @return array
     * @throws
     */
    public function actionCreateLogistics(){
        $req = Yii::$app->request;
        $user_id = Yii::$app->user->identity->id;
        $list = [];
        $start_time = strtotime(date('y-m-d',time()));
        $end_time = $start_time + 86400;
        $order = Order::find()->alias('o')
            ->leftJoin(OrderLogisticsPackAssociation::tableName().' olpa','olpa.order_id = o.order_id')
            ->where(['order_status'=>Order::ORDER_STATUS_FINISH,'warehouse'=>WarehouseService::WAREHOUSE_OWN,'source_method'=>1])
            ->andWhere('delivery_time >='.$start_time)
            ->andWhere('delivery_time <'.$end_time)
            ->andWhere(['is','olpa.id',null])
            ->asArray()->all();
        $transport = TransportProviders::getTransportName();
        foreach ($order as $v) {
            $code = TransportProviders::getTransportCode($v['logistics_channels_id'],$v['order_id']);
            $channels = TransportProviders::find()->where(['transport_code'=>$code])->select('id')->one();
            if ($v['weight'] <= 0) {
                $v['weight'] = OrderLogisticsPackController::getGoodsWeight($v);
            }
            if (isset($list[$code])){
                $quantity = $list[$code]['quantity'];
                $order = $list[$code]['order_id'];
                $weight = $list[$code]['weight'];
                $list[$code]['quantity'] = $quantity + 1;
                $list[$code]['order_id'] = $order . ',' . $v['order_id'];
                $list[$code]['weight'] = (float)$weight + (float)$v['weight'];
            }else{
                $list[$code] = [
                    'ship_date' => time(),
                    'quantity' => 1,
                    'channels_type' => $channels['id'],
                    'channels_name' => empty($transport[$channels['id']]) ? '' : $transport[$channels['id']],
                    'order_id' => $v['order_id'],
                    'weight' => (float)$v['weight'],
                ];
            }
        }
        $logistics_pack = OrderLogisticsPack::find()->where('ship_date >= '.$start_time)
            ->andWhere('ship_date <'.$end_time)
            ->asArray()->all();
        foreach ($logistics_pack as $pack){
            $transports = TransportProviders::find()->where(['id'=>$pack['channels_type']])->select('transport_code')->asArray()->one();
            if (isset($list[$transports['transport_code']])){
                unset($list[$transports['transport_code']]);
            }
        }
        if ($req->isPost){
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            $count = count($post['ship_date']);
            for ($i=0;$i<$count;$i++){
                $model = new OrderLogisticsPack();
                $model['ship_date'] = $post['ship_date'][$i];
                $model['courier'] = $post['courier'][$i];
                $model['channels_type'] = $post['channels_type'][$i];
                $model['quantity'] = $post['quantity'][$i];
                $model['weight'] = $post['weight'][$i];
                $model['admin_id'] = $user_id;
                if ($model->save()){
                    $order_id = explode(',',$post['order_id'][$i]);
                    foreach ($order_id as $v){
                        $info = new OrderLogisticsPackAssociation();
                        $info['logistics_pack_id'] = $model['id'];
                        $info['order_id'] = $v;
                        $info['admin_id'] = $user_id;
                        $info->save();
                    }
                }else{
                    return $this->FormatArray(self::REQUEST_FAIL, "生成包裹失败", []);
                }
            }
            return $this->FormatArray(self::REQUEST_SUCCESS, "生成包裹成功", []);
        }
        return $this->render('create_logistics',['list'=>$list]);
    }


    /**
     * @routeName 更新包裹件数
     * @routeDescription 更新包裹件数
     * @return array
     * @throws
     */
    public function actionUpdateLogistics(){
        $req = Yii::$app->request;
        Yii::$app->response->format = Response::FORMAT_JSON;
        $id = $req->get('id');
        $info = $this->findModel($id);
        $user_id = Yii::$app->user->identity->id;
        $start_time = strtotime(date('y-m-d',$info['ship_date']));
        $end_time = $start_time + 172800;
        $transport = TransportProviders::find()->where(['id'=>$info['channels_type']])->select('transport_code')->asArray()->one();
        $order = Order::find()->alias('o')
            ->leftJoin(OrderLogisticsPackAssociation::tableName().' olpa','olpa.order_id = o.order_id')
            ->where(['order_status'=>Order::ORDER_STATUS_FINISH,'warehouse'=>WarehouseService::WAREHOUSE_OWN,'source_method'=>1])
            ->andWhere('delivery_time >='.$start_time)
            ->andWhere('delivery_time <'.$end_time)
            ->andWhere(['is','olpa.id',null])
            ->asArray()->all();
        $weight = 0;
        $count = 0;
        foreach ($order as $v){
            $code = TransportProviders::getTransportCode($v['logistics_channels_id'],$v['order_id']);
            if ($v['weight'] <= 0) {
                $v['weight'] = OrderLogisticsPackController::getGoodsWeight($v);
            }
            if ($transport['transport_code'] == $code){
                $model = new OrderLogisticsPackAssociation();
                $model['logistics_pack_id'] = $id;
                $model['order_id'] = $v['order_id'];
                $model['admin_id'] = $user_id;
                if ($model->save()){
                    $count = $count + 1;
                    $weight = (float)$weight + (float)$v['weight'];
                }else{
                    return $this->FormatArray(self::REQUEST_FAIL, "更新包裹失败", []);
                }
            }
        }
        $info['quantity'] = $info['quantity'] + $count;
        $info['weight'] = (float)$weight + (float)$info['weight'];
        $info->save();
        return $this->FormatArray(self::REQUEST_SUCCESS, "更新包裹成功", []);
    }


    /**
     * @routeName 订单包裹导出
     * @routeDescription 订单包裹导出
     * @return array
     * @throws
     */
    public function actionExports(){
        \Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = $req->get('id');
        $export_ser = new ExportService();

        $logistics_pack = OrderLogisticsPackAssociation::find()->where(['logistics_pack_id'=>$id])->select('order_id')->asArray()->all();

        $list = [];
        foreach ($logistics_pack as $v){
            $order = Order::find()->where(['order_id'=>$v['order_id']])->select(['order_id','track_no','track_logistics_no'])->asArray()->one();
            $list[] = [
                    'order_id' => $order['order_id'],
                    'track_no' => $order['track_no'],
                    'track_logistics_no' => $order['track_logistics_no'],
                ];
        }
        $data = [];
        foreach ($list as $k => $v) {
            $data[$k]['order_id'] = $v['order_id'];
            $data[$k]['track_no'] = $v['track_no'];
            $data[$k]['track_logistics_no'] = $v['track_logistics_no'];
        }

        $column = [
            'order_id' => '订单号',
            'track_no' => '物流单号',
            'track_logistics_no' => '物流转单号',
        ];

        $result = $export_ser->forData($column,$data,'导出物流包裹' . date('ymdhis'));
        return $this->FormatArray(self::REQUEST_SUCCESS, "", $result);
    }


    /**
     * 获取商品体重
     * @param $order_id
     */
    public static function getGoodsWeight($order_id)
    {
        $goods_child = OrderGoods::find()->alias('og')
            ->leftJoin(GoodsChild::tableName().' gc','gc.cgoods_no = og.cgoods_no')
            ->select('gc.real_weight,gc.weight')
            ->where(['og.order_id' => $order_id])
            ->asArray()->all();
        $weight = [];
        foreach ($goods_child as $g_v) {
            $weight[] = $g_v['real_weight'] <= 0 ? $g_v['weight'] : $g_v['real_weight'];
        }
        $weight = array_sum($weight);
        return $weight;
    }


    protected function findModel($id)
    {
        if (($model = OrderLogisticsPack::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
