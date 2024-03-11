<?php

namespace backend\controllers;

use common\base\BaseController;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\warehousing\BlContainer;
use common\models\warehousing\BlContainerGoods;
use common\services\goods\GoodsService;
use common\services\sys\CountryService;
use common\services\warehousing\BlContainerService;
use common\services\warehousing\WarehouseService;
use Yii;
use common\models\warehousing\BlContainerTransportation;
use backend\models\search\BlContainerTransportationSearch;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use yii\web\Response;


class BlContainerTransportationController extends BaseController
{
    public function model(){
        return new BlContainerTransportation();
    }


    /**
     * @routeName 提单箱发货主页
     * @routeDescription 提单箱发货主页
     */
    public function actionIndex()
    {
        return $this->render('index');
    }


    /**
     * @routeName 提单箱发货列表
     * @routeDescription 提单箱发货列表
     */
    public function actionList()
    {
        Yii::$app->response->format=Response::FORMAT_JSON;
        $searchModel=new BlContainerTransportationSearch();
        $where = $searchModel->search(Yii::$app->request->queryParams);
        $data = $this->lists($where);
        $warehouse = WarehouseService::getWarehouseMap();
        $country_arr = CountryService::getSelectOption();
        foreach ($data['list'] as &$info){
            $info['warehouse_name'] = empty($warehouse[$info['warehouse_id']]) ? '' : $warehouse[$info['warehouse_id']];
            $info['country'] = empty($country_arr[$info['country']]) ? '' : $country_arr[$info['country']];
            $info['transport_type'] = empty(BlContainer::$transport_maps[$info['transport_type']]) ? '' : BlContainer::$transport_maps[$info['transport_type']];
            $info['delivery_time'] = $info['delivery_time'] == 0 ? '' : Yii::$app->formatter->asDate($info['delivery_time']);
            $info['arrival_time'] = $info['arrival_time'] == 0 ? '' : Yii::$app->formatter->asDate($info['arrival_time']);
            $info['status_desc'] = empty(BlContainerTransportationSearch::$status_maps[$info['status']]) ? '' : BlContainerTransportationSearch::$status_maps[$info['status']];
            $info['exists'] = BlContainer::find()->where(['bl_transportation_id' => $info['id']])->exists();
        }
        return $this->FormatLayerTable(self::REQUEST_LAY_SUCCESS,"获取成功",$data['list'],$data['pages']->totalCount);
    }


    /**
     * @routeName 新增提单箱发货
     * @routeDescription 创建提单箱发货
     * @throws
     * @return string |Response |array
     */
    public function actionCreate()
    {
        $req = Yii::$app->request;
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new BlContainerTransportation();
            $model->load($req->post(), '');
            $model['delivery_time'] = strtotime($model['delivery_time']);
            $model['arrival_time'] = strtotime($model['arrival_time']);
            $model['status'] = BlContainerTransportation::STATUS_NOT_DELIVERED;
            if ($model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, '添加失败', []);
            }
        }
        return $this->render('create');
    }

    /**
     * @routeName 更新提单箱发货
     * @routeDescription 更新提单箱发货
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
            $model->load($req->post(), '');
            $model['delivery_time'] = strtotime($model['delivery_time']);
            $model['arrival_time'] = strtotime($model['arrival_time']);
            if ($model->save()) {
                BlContainerService::updateBlGoodsPrice($model);
                return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, '更新失败', []);
            }
        } else {
            return $this->render('update', ['info' => $model->toArray()]);
        }
    }

    /**
     * @routeName 删除提单箱发货
     * @routeDescription 删除提单箱发货
     * @return array
     * @throws
     */
    public function actionDelete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = (int)$req->get('id');
        $model = $this->findModel($id);
        $exists = BlContainer::find()->where(['bl_transportation_id' => $id])->exists();
        if ($exists) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除失败", []);
        }
        if ($model->delete()) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL, "删除失败", []);
        }
    }


    /**
     * @routeName 查看提单箱发货详情
     * @routeDescription 查看提单箱发货详情
     */
    public function actionView()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        $bl_container = BlContainer::find()->where(['bl_transportation_id' => $id])->asArray()->all();
        $model = $this->findModel($id);
        $country = CountryService::getSelectOption();
        $warehouse = WarehouseService::getWarehouseMap();
        $model['country'] = empty($country[$model['country']]) ? $model['country'] : $country[$model['country']];
        $model['warehouse_id'] = empty($warehouse[$model['warehouse_id']]) ? '' : $warehouse[$model['warehouse_id']];
        $model['delivery_time'] = $model['delivery_time'] == 0 ? '' : date('Y-m-d',$model['delivery_time']);
        $model['arrival_time'] = $model['arrival_time'] == 0 ? '' : date('Y-m-d',$model['arrival_time']);
        $model['transport_type'] = empty(BlContainer::$transport_maps[$model['transport_type']]) ? '' : BlContainer::$transport_maps[$model['transport_type']];
        $model['status'] = empty(BlContainerTransportation::$status_maps[$model['status']]) ? '' : BlContainerTransportation::$status_maps[$model['status']];
        foreach ($bl_container as &$v) {
            $v['cjz'] = round(GoodsService::cjzWeight($v['size'],$model['cjz']),2);
            $v['status'] = empty(BlContainer::$status_maps[$v['status']]) ? '' : BlContainer::$status_maps[$v['status']];
            $v['bl_goods'] = BlContainerController::getBlContainerGoodsList($v['id']);
        }
        return $this->render('view',['bl_container' => $bl_container,'model' => $model->toArray()]);
    }

    /**
     * Finds the BlContainerTransportation model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return BlContainerTransportation the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = BlContainerTransportation::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    /**
     * @routeName 提单箱到货
     * @routeDescription 提单箱到货
     */
    public function actionArrival()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            if (empty($post['finish_num'])) {
                return $this->FormatArray(self::REQUEST_FAIL, "到货数量不能为空", []);
            }
            foreach ($post['finish_num'] as $key=>$item) {
                $result = (new BlContainerService())->receivedGoods($key, $item);
            }
            $exist = BlContainer::find()->where(['bl_transportation_id'=>$id,'status'=>BlContainer::STATUS_NOT_DELIVERED])->one();
            if(!$exist) {
                $bl_transport = BlContainerTransportation::find()->where(['id' => $id])->one();
                if ($bl_transport['status'] != BlContainerTransportation::STATUS_DELIVERED) {
                    $bl_transport['status'] = BlContainerTransportation::STATUS_DELIVERED;
                    $bl_transport->save();
                }
            }
            return $this->FormatArray(self::REQUEST_SUCCESS, "提交成功", []);
        }
        $list = self::getBlContainerGoodsList($id);
        $warehouse_name = WarehouseService::getOverseasWarehouse();
        return $this->render('arrival', [
            'bl_container_goods' => $list,
            'warehouse_name' => $warehouse_name,
            'id'=>$id
        ]);
    }

    /**
     * 获取提单箱商品
     * @param $id
     * @return array
     */
    public static function getBlContainerGoodsList($id)
    {
        $bl_container_goods = BlContainer::find()->alias('bl')
            ->select(['bl.bl_transportation_id', 'bl.id as bid', 'bl.initial_number', 'bl.bl_no', 'bl.warehouse_id', 'blg.cgoods_no', 'blg.num', 'blg.price', 'blg.finish_num', 'blg.id'])
            ->leftJoin(BlContainerGoods::tableName() . ' as blg', 'blg.bl_id = bl.id')
            ->where(['bl.bl_transportation_id' => $id])
            ->asArray()->all();
        $cgoods_no = ArrayHelper::getColumn($bl_container_goods, 'cgoods_no');
        $goods_childes = GoodsChild::find()->where(['cgoods_no' => $cgoods_no])->indexBy('cgoods_no')->asArray()->all();
        $goods_nos = ArrayHelper::getColumn($goods_childes, 'goods_no');
        $goods_lists = Goods::find()->where(['goods_no' => $goods_nos])->indexBy('goods_no')->asArray()->all();
        $list = [];
        foreach ($bl_container_goods as $k => $v) {
            if (empty($goods_childes[$v['cgoods_no']])) {
                continue;
            }
            $goods_child = $goods_childes[$v['cgoods_no']];
            $info = $goods_lists[$goods_child['goods_no']];
            $info = (new GoodsService())->dealGoodsInfo($info, $goods_child);
            $image = json_decode($info['goods_img'], true);
            $tmp = [];
            $tmp['sku_no'] = $info['sku_no'];
            $tmp['finish_num'] = $v['finish_num'];
            $tmp['num'] = $v['num'];
            $tmp['price'] = $v['price'];
            $tmp['id'] = $v['id'];
            $tmp['goods_img'] = empty($image) || !is_array($image) ? '' : current($image)['img'];
            $tmp['goods_name'] = $info['goods_name'];
            $tmp['goods_no'] = $info['goods_no'];
            if (empty($list[$v['bid']])) {
                $list[$v['bid']] = [
                    'id' => $v['bid'],
                    'bl_no' => $v['bl_no'],
                    'warehouse_id' => $v['warehouse_id'],
                    'initial_number' => $v['initial_number'],
                ];
            }
            $list[$v['bid']]['goods'][$v['id']] = $tmp;
        }
        return $list;
    }

}
