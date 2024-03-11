<?php

namespace backend\controllers;

use backend\models\AdminUser;
use backend\models\search\OrderAbnormalSearch;
use common\components\statics\Base;
use common\models\Goods;
use common\models\Order;
use common\models\order\OrderAbnormal;
use common\models\order\OrderAbnormalFollow;
use common\models\OrderGoods;
use common\models\OrderRecommended;
use common\models\Shop;
use common\services\goods\GoodsService;
use common\services\order\OrderAbnormalService;
use common\services\sys\AccessService;
use common\services\sys\CountryService;
use common\services\sys\ExportService;
use common\services\transport\TransportService;
use Yii;
use common\base\BaseController;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\web\Response;

class OrderAbnormalController extends BaseController
{

    private $list_type = GoodsService::SOURCE_METHOD_OWN;

    public function query($type = 'select')
    {
        $query = OrderAbnormal::find()
            ->alias('oa')->select('oa.*,o.*,oa.add_time as ab_time,oa.id as abnormal_id');
        $query->leftJoin(Order::tableName() . ' o', 'oa.order_id= o.order_id');
        return $query;
    }

    /**
     * 获取条件订单数
     */
    public function getOrderAbnormalCount($tag = []){
        $searchModel = new OrderAbnormalSearch();
        $count = [];
        foreach ($tag as $v){
            $where = $searchModel->search(null,$v);
            $data = $this->lists($where,null,null);
            $count[$v] = $data['pages']->totalCount;
        }
        return $count;
    }

    /**
     * @routeName 订单异常管理
     * @routeDescription 订单异常管理
     */
    public function actionIndex()
    {
        $this->list_type = GoodsService::SOURCE_METHOD_OWN;
        $req = Yii::$app->request;
        $tag = $req->get('tag',1);
        $searchModel=new OrderAbnormalSearch();
        $order_abnormal_count = $this->getOrderAbnormalCount([1,3,4]);
        $where=$searchModel->search(Yii::$app->request->queryParams,$tag);
        $sort = 'next_follow_time asc';
        if($tag == 2){
            $sort = 'last_follow_time desc';
        }
        //按物流单号或销售单号指定排序
        $sort_data = [];
        if(!empty($searchModel->relation_no)) {
            $sort_data = explode(PHP_EOL, $searchModel->relation_no);
            if($sort_data > 1){
                $sort_field = 'o.relation_no';
            }
        } else {
            if(!empty($searchModel->track_no)) {
                $sort_data = explode(PHP_EOL, $searchModel->track_no);
                if($sort_data > 1) {
                    $sort_field = 'o.track_no';
                }
            }
        }
        if(!empty($sort_field)) {
            foreach ($sort_data as &$sort_v) {
                $sort_v = "'" . trim($sort_v) . "'";
            }
            $sort = new Expression('field (' . $sort_field . ',' . implode(',', $sort_data) . ')');
        }
        $data = $this->lists($where,$sort,null);
        $oa_user_ids = AccessService::getOrderAbnormalUserIds();
        $so_user_ids = AccessService::getShopOperationUserIds();
        $admin_lists = AdminUser::find()->where(['id' => array_merge($so_user_ids,$oa_user_ids)])->andWhere(['=','status',AdminUser::STATUS_ACTIVE])->select(['id','nickname'])->asArray()->all();
        $admin_lists = ArrayHelper::map($admin_lists,'id','nickname');
        return $this->render('index', [
            'searchModel' => $searchModel,
            'list' => $data['list'],
            'pages' => $data['pages'],
            'tag'=>$tag,
            'admin_arr' => $admin_lists,
            'order_abnormal_count' => $order_abnormal_count,
        ]);
    }
    /**
     * @routeName 导出订单异常
     * @routeDescription 导出订单异常
     * @return array
     * @throws
     */
    public function actionExport()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $tag = $req->get('tag');
        $order_id = $req->get('ids');
        $searchModel = new OrderAbnormalSearch();
        $params = Yii::$app->request->queryParams;
        if(!empty($order_id)){
            $order_id = explode(',',$order_id);
            $params['OrderAbnormalSearch']['order_id'] = $order_id;
        }
        $where = $searchModel->search($params, $tag);
        $sort = 'next_follow_time asc';
        if($tag == 2){
            $sort = 'last_follow_time desc';
        }
        $query = $this->query();
        $list = Order::getAllByCond($where,$sort,null,$query);
        $list = $this->formatLists($list);
        $result = $this->export($list);
        return $this->FormatArray(self::REQUEST_SUCCESS, "", $result);
    }
    /**
     * 导出方法
     */
    public function export($list){
        $page_size = 1000;
        $export_ser = new ExportService($page_size);
        foreach ($list as $k => $v) {
            $data[$k]['order_id'] = $v['order_id'];
            $data[$k]['relation_no'] = $v['relation_no'];
            $data[$k]['shop_name'] = $v['shop_name'];
            $data[$k]['logistics_channels_desc'] = $v['logistics_channels_desc'];
            $data[$k]['track_no'] = $v['track_no'];
            $data[$k]['sku_no'] = $v['goods'][0]['platform_asin'];
            $data[$k]['price'] = $v['goods'][0]['goods_cost_price'];
            $data[$k]['abnormal_type'] = OrderAbnormalService::$abnormal_type_maps[$v['abnormal_type']];
            $data[$k]['abnormal_remarks'] = $v['abnormal_remarks'];

        }

        $column = [
            'order_id' => '订单号',
            'relation_no' => '销售单号',
            'shop_name' => '店铺',
            'logistics_channels_desc' => '物流方式',
            'track_no' => '物流单号',
            'sku_no' => 'sku',
            'price'=>'价格',
            'abnormal_type'=>'异常类型',
            'abnormal_remarks'=>'异常备注',
        ];

        $result = $export_ser->forData($column,$data,'商品标题导出' . date('ymdhis'));
        return $result;
    }
    /**
     * @routeName 订单异常跟进
     * @routeDescription 订单异常跟进
     */
    public function actionFollow()
    {
        $req = Yii::$app->request;
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $data = $req->post();
            if(empty($data['follow_remarks'])) {
                return $this->FormatArray(self::REQUEST_FAIL, "跟进内容不能为空", []);
            }
            if(isset($data['remarks_status'])){
                $follow = OrderAbnormal::findOne($data['abnormal_id']);
                $order = Order::find()->where(['order_id'=>$follow['order_id']])->one();
                $remarks = $order['remarks']."\n".$data['follow_remarks'];
                $order['remarks'] = $remarks;
                $order->save();
            }
            (new OrderAbnormalService())->followAbnormal($data['abnormal_id'],$data['abnormal_status'],$data['follow_remarks'],$data['next_follow_time'],$data['follow_admin_id']);
            return $this->FormatArray(self::REQUEST_SUCCESS, "提交成功", []);
        } else {
            $order_id = $req->get('order_id');
            $is_view = $req->get('is_view',0);
            $where = [];
            if($order_id){
                $where['order_id'] = $order_id;
            }else{
                $id = $req->get('id');
                $where['id'] = $id;
            }
            $model = OrderAbnormal::find()->where($where)->orderBy('add_time desc')->asArray()->one();
            $order_abnormal_follow = OrderAbnormalFollow::find()->where(['abnormal_id'=>$model['id']])->asArray()->all();
            $oa_user_ids = AccessService::getOrderAbnormalUserIds();
            $so_user_ids = AccessService::getShopOperationUserIds();
            $admin_lists = AdminUser::find()->where(['id' => array_merge($so_user_ids,$oa_user_ids)])->andWhere(['=','status',AdminUser::STATUS_ACTIVE])->select(['id','nickname'])->asArray()->all();
            $admin_lists = ArrayHelper::map($admin_lists,'id','nickname');
            return $this->render('follow', [
                'model' => $model,
                'order_abnormal_follow' => $order_abnormal_follow,
                'is_view' => $is_view,
                'admin_arr' => $admin_lists
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
            if (in_array($v['order_status'], [Order::ORDER_STATUS_UNCONFIRMED, Order::ORDER_STATUS_WAIT_PURCHASE])) {
                $order_recommended_id[] = $v['order_id'];
            }
            $order_ids[] = $v['order_id'];
        }
        $order_recommended = OrderRecommended::find()->where(['order_id'=>$order_recommended_id])->indexBy('order_id')->asArray()->all();

        $order_goods_lists = OrderGoods::find()->where(['order_id' => $order_ids, 'goods_status' => [OrderGoods::GOODS_STATUS_UNCONFIRMED, OrderGoods::GOODS_STATUS_NORMAL]])->asArray()->all();
        $order_goods_lists = ArrayHelper::index($order_goods_lists,null,'order_id');

        foreach ($list as &$v){
            $v['shop_name'] = empty($shop[$v['shop_id']])?'':$shop[$v['shop_id']]['name'];
            $order_goods = $order_goods_lists[$v['order_id']];
            $v['goods_count'] = empty($order_goods)?1:count($order_goods);

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

    /**
     * @routeName 批量关闭异常
     * @routeDescription 批量关闭异常
     * @return array
     * @throws
     */
    public function actionBatchClose()
    {
        $req = Yii::$app->request;
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            $follow_remarks = $post['follow_remarks'];
            $order_id = $req->get('order_id');
            if (empty($order_id)) {
                return $this->FormatArray(self::REQUEST_FAIL, "请选择订单", []);
            }
            if (empty($follow_remarks)) {
                return $this->FormatArray(self::REQUEST_FAIL, "异常备注不能为空", []);
            }
            $order_id = explode(',', $order_id);
            foreach ($order_id as $id_v) {
                (new OrderAbnormalService())->closeAbnormalToOrderId($id_v, $follow_remarks);
            }
            return $this->FormatArray(self::REQUEST_SUCCESS, "操作成功", []);
        } else {
            return $this->render('batch-close');
        }
    }

    public function model(){
        return new Order();
    }

}