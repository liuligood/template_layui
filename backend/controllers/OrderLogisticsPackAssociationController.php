<?php

namespace backend\controllers;

use common\models\goods\GoodsChild;
use common\services\warehousing\WarehouseService;
use Yii;
use common\models\OrderLogisticsPackAssociation;
use backend\models\search\OrderLogisticsPackAssociationSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use common\base\BaseController;
use yii\web\Response;
use yii\helpers\ArrayHelper;
use common\models\User;
use common\models\OrderLogisticsPack;
use common\models\Order;
use yii\data\Pagination;
use backend\models\search\OrderSearch;
use common\models\Shop;
use common\models\OrderGoods;
use common\services\sys\CountryService;
use backend\models\AuthItem;
use yii\rbac\Item;
use common\services\transport\TransportService;
use common\services\goods\GoodsService;
use common\models\OrderStockOccupy;
use common\models\Goods;
use const vierbergenlars\SemVer\Internal\caretTrimReplace;


class OrderLogisticsPackAssociationController extends BaseController
{
    private $list_type = GoodsService::SOURCE_METHOD_OWN;
    private $list_tag = 10;
    
   
    /**
     * @routeName 查看物流包裹订单
     * @routeDescription 查看物流包裹订单
     * @return array
     * @throws
     */
    public function actionCreate()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');       
        $tag = $req->get('tag',13);
        $page = Yii::$app->request->get('page', 1);
        $pageSize = Yii::$app->request->get('limit', 20); 
        $query_params = Yii::$app->request->queryParams;
        $logistics_channels_ids = [];
        $logistics_channels_ids = TransportService::getShippingMethodOptions();
        $searchModel=new OrderSearch();
            //$searchModel->order_id =$orders;
            $where=$searchModel->ownSearch($query_params,$tag);
            $sort = 'date desc';
            $count = Order::getCountByCond($where);
            $list = Order::getListByCond($where, $page, $pageSize, $sort);
            $pages = new Pagination(['totalCount' => $count, 'pageSize' => $pageSize]);
            $list = $this->orderFormatLists($list);
            $info= OrderLogisticsPack::find()->where(['id'=>$id])->asArray()->one(); 
        return $this->render('create',[ 
            'info'=>$info,
            'searchModel' => $searchModel,
            'logistics_channels_ids'=>$logistics_channels_ids,
            'list' => $list,
            'pages' => $pages,
            'tag'=>$tag,
        ]);    
    }
    
    /**
     * @routeName 删除物流包裹订单
     * @routeDescription 删除物流包裹订单
     * @return array
     * @throws
     */
    public function actionDelete(){
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = $req->get('id');
        $items = $req->post('logistics', []);
        $weight = 0;
        if(empty($items)){
            return $this->FormatArray(self::REQUEST_FAIL, "请选择订单", []);
        }
        $logistics_pack = OrderLogisticsPack::findOne($id);
        $number = sizeof($items);
        foreach ($items as $v){
            $model = OrderLogisticsPackAssociation::find()->where(['order_id'=>$v])->select('id')->one();
            $order = Order::find()->where(['order_id'=>$v])->select('weight')->asArray()->one();
            if ($order['weight'] <= 0) {
                $order['weight'] = OrderLogisticsPackController::getGoodsWeight($v);
            }
            $weight = (float)$weight + (float)$order['weight'];
            if ($model->delete()) {
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, "删除失败", []);
            }
        }
        $logistics_pack['quantity'] = $logistics_pack['quantity'] - $number;
        $logistics_pack['weight'] = $logistics_pack['weight'] - $weight;
        $logistics_pack->save();
        return $this->FormatArray(self::REQUEST_SUCCESS, "删除成功", []);
    }
    
    
    /**
     * @routeName 添加物流包裹订单
     * @routeDescription 添加物流包裹订单
     * @return array
     * @throws
     */
    public function actionAssign()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = $req->get('id');
        $user_id = Yii::$app->user->identity->id;
        $weight = 0;
        if($req->isPost){
            $id=$req->post('id');
        }                  
        $items = $req->post('logistics', []);
        $logistics_pack = OrderLogisticsPack::find()->where(['id'=>$id])->one();
        $order_list= OrderLogisticsPackAssociation::find()->where('id')->select('order_id')->asArray()->all();
        $orders = [];
        foreach ($order_list as $order_v){
            $orders[] = $order_v['order_id'];
        }
        if(empty($items)){
            return $this->FormatArray(self::REQUEST_FAIL, "请选择订单", []);
        }
        $intersect = array_intersect($items,$orders);
        if (empty($intersect)){
        $numbmer = sizeof($items);
        foreach ($items as $item){
                $order = Order::find()->where(['order_id'=>$item])->select('weight')->asArray()->one();
                if ($order['weight'] <= 0) {
                    $order['weight'] = OrderLogisticsPackController::getGoodsWeight($item);
                }
                $weight = (float)$weight + (float)$order['weight'];
                $model = new OrderLogisticsPackAssociation();
                $model->logistics_pack_id=$id??'';
                $model->order_id = $item??'';
                $model->admin_id = $user_id;
                if ($model->save()) {
                } else {
                    return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
                }
        }
        $logistics_pack['quantity'] = $logistics_pack['quantity'] + $numbmer;
        $logistics_pack['weight'] = $weight + $logistics_pack['weight'];
        $logistics_pack->save();
        return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []); 
        }else {
            return $this->FormatArray(self::REQUEST_FAIL, "添加失败,其他包裹已经拥有该订单", []);
        }
        
    }
    
    /**
     * 格式化列表
     * @param $list
     * @return array
     */
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
    
    protected function findModel($id)
    {
        if (($model = OrderLogisticsPackAssociation::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
