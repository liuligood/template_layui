<?php

namespace backend\controllers;

use backend\models\AdminUser;
use common\base\BaseController;
use common\components\statics\Base;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\Order;
use common\models\OrderGoods;
use common\services\order\OrderService;
use Yii;
use common\models\OrderOverseasStock;
use backend\models\search\OrderOverseasStockSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * OrderOverseasStockController implements the CRUD actions for OrderOverseasStock model.
 */
class OrderOverseasStockController extends BaseController
{
    /**
     * {@inheritdoc}
     */

    public function model(){
        return new OrderOverseasStock();
    }
    public function query($type = 'select')
    {
        $query = OrderOverseasStock::find()
            ->alias('gs')->select('gs.return_data,ga.country,ga.source,gs.order_id,gs.id,gs.ware_house,gs.expire_time,gs.rewire_id,gs.rewire_data,gs.desc,gs.add_time,gs.update_time,gs.status,gs.user_id,gs.goods_shelves,gs.cgoods_no,gs.number,gc.goods_img,g.goods_img as ggoods_img,g.goods_no,g.goods_name_cn,g.goods_name,ga.relation_no,ga.track_no,g.source_platform_type,gc.sku_no');
        $query->leftJoin(GoodsChild::tableName() . ' gc', 'gc.cgoods_no= gs.cgoods_no');
        $query->leftJoin(Goods::tableName() . ' g', 'gc.goods_no = g.goods_no');
        $query->leftJoin(Order::tableName().'ga','gs.order_id = ga.order_id');
        return $query;
    }
    /**
     *  @routeName 退回海外仓表的主页界面
     * @routeDescription退回海外列表的主页界面
     */
        public function actionIndex()
    {
        return $this->render('index');
    }

        /**
         * @routeName 退回海外仓列表
         * @routeDescription 退回海外仓列表
         */
        public function actionList()
    {
        Yii::$app->response->format=Response::FORMAT_JSON;
        $searchModel=new OrderOverseasStockSearch();
        $where = $searchModel->search(Yii::$app->request->queryParams);
        $data = $this->lists($where);
        $datetime = time();

        foreach ($data['list'] as &$list){
            if($list['expire_time']<$datetime && $list['status'] == OrderOverseasStock::ORDER_UODATE_NEVER){
                $model = $this->findModel($list['id']);
                $model['status'] = OrderOverseasStock::ORDER_UODATE_RESTORM;
                $list['status'] = OrderOverseasStock::ORDER_UODATE_RESTORM;
                $model->save();
            }
            $list['add_time'] = date('Y-m-d H:i:s', $list['add_time']);
            $list['update_time'] = date('Y-m-d H:i:s', $list['update_time']);
            $list['return_data'] = date('Y-m-d', $list['return_data']);
            $list['expire_time'] = date('Y-m-d', $list['expire_time']);
            $list['user_id'] = AdminUser::findOne($list['user_id'])['username'];
            if($list['rewire_data']){ $list['rewire_data'] = date('Y-m-d', $list['rewire_data']);}
            $list['status'] = OrderOverseasStock::$stutas_map[$list['status']];
            $list['source_platform_type'] = Base::$order_source_maps[$list['source']];
            $list['country'] =\common\services\sys\CountryService::getName($list['country']);
        }
        $lists = array_map(function ($info) {
            $image = $info['goods_img'];
            if(empty($info['goods_img'])){
                $image = json_decode($info['ggoods_img'], true);
                $image = empty($image) || !is_array($image) ? '' : current($image)['img'];
            }
            $info['image'] = $image;
            //$info['status_desc'] = Shelves::$status_map[$info['status']];
            //$info['update_time_desc'] = empty($info['update_time'])?'':date('Y-m-d H:i',$info['update_time']);
            return $info;
        }, $data['list']);


        return $this->FormatLayerTable(
            self::REQUEST_LAY_SUCCESS,'获取成功',
            $lists, $data['pages']->totalCount
        );
    }

    /**
     * Displays a single OrderOverseasStock model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     * @routeName 退回海外仓表的重发
     * @routeDescription 退回海外仓表的重发
     */
    public function actionView()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        if($req->isPost){
            $ids = $req->post('id');
            $model = OrderOverseasStock::findOne($ids);
            $time = $req->post('rewire_data');
            $order = $req->post('rewire_id');
            $model['rewire_data']=strtotime($time);
            $model['rewire_id'] =$order;
            $datatime =time();
            $model['status'] = OrderOverseasStock::ORDER_UODATE_RESTAR;
            $model['update_time'] =$datatime;
            if($model->save()){
                Yii::$app->response->format=Response::FORMAT_JSON;
                return $this->FormatArray(self::REQUEST_SUCCESS, "重发成功", []);
            }else{
                Yii::$app->response->format=Response::FORMAT_JSON;
                return $this->FormatArray(self::REQUEST_FAIL, "重发失败", []);
            }
        }
        return $this->render('view', [
            'info' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new OrderOverseasStock model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @routeName 退回海外仓表的创建
     * @routeDescription 退回海外仓表的创建
     * @return mixed
     */
    public function actionCreate()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');

        if ($req->isPost) {
            $i = 0;$result = '';
            $user_id = Yii::$app->user->identity->id;
            $cgoods = $req->post('cgoods_no');
            $numbers= $req->post('number');
            $descs = $req->post('desc');
            $expire_times =$req->post('expire_time');;
            $return_data = $req->post('return_time');
            foreach($cgoods as $cgood){
                $datetime = time();
                $a = new OrderOverseasStock();
                $a['order_id'] = $req->post('order_id');
                $order_goods = OrderService::getOrderGoods($a['order_id']);
                $a['number'] = $numbers[$i];
                if($order_goods[$i]['goods_num']<$a['number']){
                    $result = 'num'; break;}
                $order = OrderGoods::findOne($order_goods[$i]['id']);
                $order['goods_num'] = $order_goods[$i]['goods_num']-$a['number'];
                $a['cgoods_no'] = $cgood;
                $a['expire_time'] = strtotime($expire_times[$i]);
                $a['desc'] = $descs[$i];
                $a['add_time']=$datetime;
                $a['update_time']=$datetime;
                $a['return_data']= strtotime($return_data[$i]);
                $a['rewire_data'] = null;
                $a['status'] = 3;
                $a['user_id'] = $user_id;
                if($a->save()&&$order->save()){
                    $result = 'ok';
                }
                $i+=1;
            }
            Yii::$app->response->format=Response::FORMAT_JSON;
            if($result == 'ok'){
            return $this->FormatArray(self::REQUEST_SUCCESS, "退订成功", []);}elseif($result == 'num'){
                return $this->FormatArray(self::REQUEST_FAIL, "数字超出原有数量", []);}else{
                return $this->FormatArray(self::REQUEST_FAIL, "退订失败，数据错误", []);
            }

        }
        $model = Order::findOne($id);
        if($req->isGet){
            $datetime = date('Y-m-d',time());
            $model['date'] = empty($model['date']) ? '' : date('Y-m-d H:i:s', $model['date']);
            $order_goods = OrderService::getOrderGoods($model['order_id']);
            foreach ($order_goods as &$v) {
                $v['goods_name'] = htmlentities($v['goods_name'], ENT_COMPAT);
            }
            return $this->render('create', [
                'model' => $model,
                'datatime' =>$datetime,
                'order_goods' => $order_goods
            ]);
        }
    }

    /**
     * Updates an existing OrderOverseasStock model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     * @routeName 退回海外仓表的编辑
     * @routeDescription 退回海外仓表的编辑
     */
    public function actionUpdate()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        if ($req->isPost) {
            $id = $req->post('id');
        }
        $model = $this->findModel($id);
        $model['return_data'] = date('Y-m-d', $model['return_data']);
        $model['expire_time'] = date('Y-m-d', $model['expire_time']);
        if ($req->isPost) {
            $good_no = $req->post('goods_no');
            $order_id = $req->post('order_id');
            $num = $req->post('number');
            $good = OrderGoods::find()->where(['cgoods_no' => $good_no,'order_id' => $order_id])->asArray()->all();
            foreach ($good as $item){
                $new_goode = OrderGoods::findOne($item['id']);
               if($item['goods_num']<($num-$model['number'])){
                   Yii::$app->response->format = Response::FORMAT_JSON;
                   return $this->FormatArray(self::REQUEST_FAIL, "数量超过已有数量", []);
               }else{
                   $new_goode['goods_num'] =$new_goode['goods_num']-($num-$model['number']);
                   $new_goode->save();
               }
            }
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model->load($req->post(), '');
            $datetime = time();
            $model['return_data'] = strtotime($model['return_data']);
            $model['expire_time'] = strtotime($model['expire_time']);
            $model['update_time'] = $datetime;
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
     * Deletes an existing OrderOverseasStock model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    /**
     * Finds the OrderOverseasStock model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return OrderOverseasStock the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = OrderOverseasStock::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
