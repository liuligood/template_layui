<?php

namespace backend\controllers;

use AlibabaCloud\SDK\OSS\OSS\DeleteMultipleObjectsRequest\body\delete;
use common\base\BaseController;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\goods_shop\GoodsShopOverseasWarehouse;
use common\models\GoodsStockFreight;
use common\models\warehousing\BlContainerGoods;
use common\models\sys\Country;
use common\models\warehousing\BlContainerTransportation;
use common\models\warehousing\OverseasGoodsShipment;
use common\services\goods\GoodsService;
use common\services\sys\CountryService;
use common\services\sys\ExportService;
use common\services\warehousing\BlContainerService;
use common\services\warehousing\WarehouseService;
use Yii;
use common\models\warehousing\BlContainer;
use backend\models\search\BlContainerSearch;
use yii\db\Exception;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * BlContainerController implements the CRUD actions for BlContainer model.
 */
class BlContainerController extends BaseController
{
    public function model()
    {
        return new BlContainer();
    }

    public function query($type = 'select')
    {
        $query = BlContainer::find()->alias('bl');
        return $query;
    }

    /**
     * @routeName 提单箱表管理
     * @routeDescription 提单箱表管理
     */
    public function actionIndex()
    {
        $req = Yii::$app->request;
        $tag = $req->get('tag',10);
        $searchModel = new BlContainerSearch();
        $where = $searchModel->search(Yii::$app->request->queryParams,$tag);
        $sort = new Expression("CASE WHEN 1=1 THEN `status` END DESC,CASE WHEN `status` = 10 THEN `id` END DESC,CASE WHEN `status` = 20 THEN `update_time` END DESC");
        if ($tag != 10) {
            $sort = $tag == 1 ? 'id desc' : 'update_time desc';
        }
        $data = $this->lists($where,$sort);
        $map = CountryService::getSelectOption();
        $warehouse_name = WarehouseService::getOverseasWarehouse();
        $bl_container_transportation = BlContainerTransportation::find()->indexBy('id')->asArray()->all();
        foreach ($data['list'] as &$it) {
            $transportation = empty($bl_container_transportation[$it['bl_transportation_id']]) ? [] : $bl_container_transportation[$it['bl_transportation_id']];
            if (!empty($transportation)) {
                $price = $transportation['price'] / $transportation['estimate_weight'];
                $it['track_no'] = $transportation['track_no'];
                $it['tr_cjz'] = round(GoodsService::cjzWeight($it['size'],$transportation['cjz']),2);
                $bl_price = $it['tr_cjz'] > $it['weight'] ? $price * $it['tr_cjz'] : $price * $it['weight'];
                $it['bl_price'] = round($bl_price,2);
                $it['tr_delivery_time'] = $transportation['delivery_time'] == 0 ? '' : date('Y-m-d',$transportation['delivery_time']);
                $it['tr_arrival_time'] = $transportation['arrival_time'] == 0 ? '' : date('Y-m-d',$transportation['arrival_time']);
                $it['transport_name'] = empty(BlContainer::$transport_maps[$transportation['transport_type']]) ? '' : BlContainer::$transport_maps[$transportation['transport_type']];
            }
            $packing_time = BlContainer::find()->alias('bl')
                ->select('ogs.packing_time')
                ->leftJoin(BlContainerGoods::tableName().' blg','blg.bl_id = bl.id')
                ->leftJoin(OverseasGoodsShipment::tableName().' ogs','ogs.bl_container_goods_id = blg.id')
                ->where(['bl.id' => $it['id']])->orderBy('packing_time desc')->scalar();
            $it['packing_time'] = $packing_time != false ? $packing_time : '';
        }
        return $this->render('index', [
            'tag' => $tag,
            'searchModel' => $searchModel,
            'list' => $data['list'],
            'pages' => $data['pages'],
            'map' => $map,
            'warehouse_name' => $warehouse_name
        ]);
    }

    /**
     * @routeName 增加提单箱
     * @routeDescription 增加提单箱
     */
    public function actionCreate()
    {
        $req = Yii::$app->request;
        $ovg_id = $req->get('id');
        $goods = [];
        $model = new BlContainer();
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $ovg_num = $req->post('ovg');
            $num = $req->post('num');
            $model->load($req->post(), '');
            $model->size = (new GoodsService())->genSize($req->post());
            if (!empty($model->arrival_time)) {
                $model->arrival_time = strtotime($model->arrival_time);
            }
            if (!empty($model->delivery_time)) {
                $model->delivery_time = strtotime($model->delivery_time);
            }
            $model->status = BlContainer::STATUS_NOT_DELIVERED;
            if (!empty($ovg_num)) {
                $model->status = BlContainer::STATUS_WAIT_SHIP;
            }
            if ($model->save()) {
                $goods = [];
                if (empty($ovg_num)) {
                    foreach ($num as $k=>$v){
                        $goods[] = ['cgoods_no'=>$k,'num'=>$v];
                    }
                    BlContainerService::updateGoods($model->id,$goods);
                } else {
                    $error = [];
                    foreach ($ovg_num as $k => $shipment) {
                        $goods[] = ['cgoods_no' => $k,'num' => array_sum($shipment)];
                        $bl_goods_id = BlContainerService::updateGoods($model->id,$goods);
                        foreach ($shipment as $goods_shipment_id => $numbers) {
                            $goods_shipment = OverseasGoodsShipment::findOne($goods_shipment_id);
                            if ($goods_shipment['num'] < $numbers) {
                                $error[] = $goods_shipment_id;
                                continue;
                            }
                            OverseasGoodsShipmentController::packedFinish($goods_shipment,$numbers,$bl_goods_id);
                        }
                    }
                    if (!empty($error)) {
                        return $this->FormatArray(self::REQUEST_FAIL, "装箱数量不能超过采购数量", []);
                    }
                }
                return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
            }
        } else {
            if (!empty($ovg_id)) {
                $ovg_id_arr = explode(',',$ovg_id);
                $ovg = OverseasGoodsShipment::find()->where(['id' => $ovg_id_arr])
                    ->select('cgoods_no,num,id as ovg_id,warehouse_id')->asArray()->all();
                $goods = $this->dealGoods($ovg);
                $warehouse_id = ArrayHelper::getColumn($ovg,'warehouse_id');
                $count = count(array_unique($warehouse_id));
                if ($count != 1) {
                    return '仓库必须统一';
                }
                $model['warehouse_id'] = current($warehouse_id);
            }
        }

        return $this->render('create', ['info' => $model, 'goods' => $goods, 'size' => [], 'ovg_id' => $ovg_id]);
    }

    /**
     * @routeName 修改提单箱
     * @routeDescription 修改提单箱
     */
    public function actionUpdate()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        if ($req->isPost) {
            $id = $req->post('id');
        }
        $model = $this->findModel($id);
        $bl_container_goods = BlContainerGoods::find()->where(['bl_id' => $model['id']])->asArray()->all();
        $ovg_id = '';
        if (!empty($bl_container_goods)) {
            $ovg_id = OverseasGoodsShipment::find()->where(['bl_container_goods_id' => $bl_container_goods[0]['id']])->one();
        }
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $now_weight = $req->post('weight');
            $post_size = (new GoodsService())->genSize($req->post());
            if ($now_weight != $model['weight'] || $model['size'] != $post_size) {
                if ($model['bl_transportation_id'] != 0) {
                    $bl_transportation = BlContainerTransportation::findOne($model['bl_transportation_id']);
                    $old_cjz = GoodsService::cjzWeight($model['size'],$bl_transportation['cjz']);
                    $old_weight = $old_cjz > $model['weight'] ? $old_cjz : $model['weight'];
                    $now_cjz = GoodsService::cjzWeight($post_size,$bl_transportation['cjz']);
                    $now_weight = $now_cjz > $now_weight ? $now_cjz : $now_weight;
                    $weight = $old_weight - $now_weight;
                    $bl_transportation['estimate_weight'] = $bl_transportation['estimate_weight'] - $weight;
                    $bl_transportation->save();
                }
            }
            $ovg_num = $req->post('ovg');
            $num = $req->post('num');
            $model->load($req->post(), '');
            $model->size = $post_size;
            if ($model->save()) {
                $goods = [];
                foreach ($num as $k=>$v) {
                    $goods[] = ['cgoods_no' => $k, 'num' => $v];
                }
                BlContainerService::updateGoods($model->id,$goods,true,$model->bl_transportation_id);
                return $this->FormatArray(self::REQUEST_SUCCESS, "修改成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, "修改失败", []);
            }
        } else {
            $size = (new GoodsService())->getSizeArr($model['size']);
            $goods = $this->dealGoods($bl_container_goods);
            $model['arrival_time'] = !empty($model['arrival_time'])?date('Y-m-d', $model['arrival_time']):'';
            $model['delivery_time'] =!empty($model['delivery_time'])? date('Y-m-d', $model['delivery_time']):'';
            return $this->render('create', ['info' => $model, 'size' => $size, 'goods' => $goods, 'ovg_id' => $ovg_id]);
        }
    }

    /**
     * @routeName 删除提单箱
     * @routeDescription 删除提单箱
     */
    public function actionDelete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = (int)$req->get('id');
        $model = $this->findModel($id);
        if ($model['bl_transportation_id'] != 0) {
            $transportation = BlContainerTransportation::findOne($model['bl_transportation_id']);
            $cjz = GoodsService::cjzWeight($model['size'],$transportation['cjz']);
            $weight = $cjz > $model['weight'] ? $cjz : $model['weight'];
            $transportation['estimate_weight'] = $transportation['estimate_weight'] - $weight;
            $transportation['goods_count'] = $transportation['goods_count'] - $model['goods_count'];
            $transportation['bl_container_count'] = $transportation['bl_container_count'] - 1;
            $transportation->save();
        }
        if ($model->delete()) {
            $bl_goods = BlContainerGoods::find()->where(['bl_id' => $id])->asArray()->all();
            foreach ($bl_goods as $v) {
                BlContainerService::updateOverseasGoodsShipment($v['id'],$v['num'],0);
            }
            BlContainerGoods::deleteAll(['bl_id' => $id]);
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除失败", []);
        }
    }

    /**
     * @routeName 查看提单箱详情
     * @routeDescription 查看提单箱详情
     */
    public function actionView()
    {
        $req = Yii::$app->request;
        $id = (int)$req->get('id');
        $warehouse_name = WarehouseService::getOverseasWarehouse();
        $country_name = CountryService::getSelectOption();
        $model = $this->findModel($id);
        $transportation = BlContainerTransportation::find()->where(['id' => $model['bl_transportation_id']])->asArray()->one();
        if (!empty($transportation)) {
            $price = $transportation['price'] / $transportation['estimate_weight'];
            $transportation['cjz'] = GoodsService::cjzWeight($model['size'],$transportation['cjz']);
            $bl_price = $transportation['cjz'] > $model['weight'] ? $price * $transportation['cjz'] : $price * $model['weight'];
            $transportation['bl_price'] = round($bl_price,2);
            $transportation['transport_type'] = empty(BlContainer::$transport_maps[$transportation['transport_type']]) ? '' : BlContainer::$transport_maps[$transportation['transport_type']];
        }
        $list = BlContainerController::getBlContainerGoodsList($id);
        return $this->render('view', [
            'model' => $model,
            'warehouse_name' => $warehouse_name,
            'country_name' => $country_name,
            'bl_container_goods' => $list,
            'transportation' => $transportation
        ]);
    }

    /**
     * @routeName 提单箱到货
     * @routeDescription 提单箱到货
     */
    public function actionArrival()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        $bl_container = $this->findModel($id);
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            if (empty($post['finish_num'])) {
                return $this->FormatArray(self::REQUEST_FAIL, "到货数量不能为空", []);
            }
            $result = (new BlContainerService())->receivedGoods($id, $post['finish_num']);
            if (!$result) {
                return $this->FormatArray(self::REQUEST_FAIL, "提交失败", []);
            }
            $exist = BlContainer::find()->where(['bl_transportation_id'=>$bl_container['bl_transportation_id'],'status'=>BlContainer::STATUS_NOT_DELIVERED])->one();
            if(!$exist) {
                $bl_transport = BlContainerTransportation::find()->where(['id' => $bl_container['bl_transportation_id']])->one();
                if ($bl_transport['status'] != BlContainerTransportation::STATUS_DELIVERED) {
                    $bl_transport['status'] = BlContainerTransportation::STATUS_DELIVERED;
                    $bl_transport->save();
                }
            }
            return $this->FormatArray(self::REQUEST_SUCCESS, "提交成功", []);
        }
        $transportation = BlContainerTransportation::find()->where(['id' => $bl_container['bl_transportation_id']])->asArray()->one();
        $cjz = 0;
        $bl_price = 0;
        if (!empty($transportation)) {
            $price = $transportation['price'] / $transportation['estimate_weight'];
            $transportation['cjz'] = GoodsService::cjzWeight($bl_container['size'],$transportation['cjz']);
            $bl_price = $transportation['cjz'] > $bl_container['weight'] ? $price * $transportation['cjz'] : $price * $bl_container['weight'];
            $cjz = $transportation['cjz'];
        }
        $list = BlContainerController::getBlContainerGoodsList($id,$cjz,$bl_price);
        $warehouse_name = WarehouseService::getOverseasWarehouse();
        $country_name = CountryService::getSelectOption();
        return $this->render('arrival', [
            'model' => $bl_container,
            'bl_container_goods' => $list,
            'warehouse_name' => $warehouse_name,
        ]);
    }


    /**
     * @routeName 提单箱发货
     * @routeDescription 提单箱发货
     */
    public function actionDelivery()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        $is_batch = $req->get('is_batch');
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            $bl_nos = $post['bl_nos'];
            $bl_transportation_id = $post['track_no'];
            $bl_ids = array_keys($bl_nos);
            $bl_ids = BlContainer::find()->where(['id' => $bl_ids])->all();
            $result = $this->updateTransportation($bl_transportation_id,$bl_ids,$bl_nos);
            if ($result) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "提交成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, "提交失败", []);
            }
        } else {
            $id = explode(',',$id);
            $bl_container = BlContainer::find()->where(['id' => $id])->asArray()->all();
            $warehouse_id = ArrayHelper::getColumn($bl_container,'warehouse_id');
            $count = count(array_unique($warehouse_id));
            if ($count != 1) {
                return '提单箱仓库必须统一';
            }
            $warehouse_id = current($warehouse_id);
            $bl_container = $is_batch == 2 ? current($bl_container) : $bl_container;
            $warehouse = WarehouseService::getWarehouseMap();
        }
        return $this->render('delivery',['bl_container' => $bl_container,
            'is_batch' => $is_batch,
            'warehouse_id' => $warehouse_id,
            'warehouse' => $warehouse
        ]);
    }

    /**
     * @routeName 打回待发货
     * @routeDescription 打回待发货
     */
    public function actionResetWaitShip()
    {
        $req = Yii::$app->request;
        Yii::$app->response->format = Response::FORMAT_JSON;
        $id = $req->post('id');
        $bl_ids = BlContainer::find()->where(['id' => $id])->all();
        $result = $this->updateTransportation('',$bl_ids,[],true);
        if ($result) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "提交成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL, "提交失败", []);
        }
    }

    /**
     * @routeName 同步运费
     * @routeDescription 同步运费
     */
    public function actionUpdateLogisticsCost()
    {
        $req = Yii::$app->request;
        Yii::$app->response->format = Response::FORMAT_JSON;
        $id = $req->get('id');
        $bl_container_goods = BlContainerTransportation::find()->alias('blt')
            ->leftJoin(BlContainer::tableName().' bl','bl.bl_transportation_id = blt.id')
            ->leftJoin(BlContainerGoods::tableName(). ' blg','blg.bl_id = bl.id')
            ->select('blg.cgoods_no,blg.price,blg.warehouse_id')->where(['blt.id' => $id])->asArray()->all();
        $error = [];
        foreach ($bl_container_goods as $v) {
            $overseas_warehouse = GoodsShopOverseasWarehouse::find()->where(['cgoods_no' => $v['cgoods_no'],'warehouse_id' => $v['warehouse_id']])->all();
            $goods_stock_freight = GoodsStockFreight::find()->where(['cgoods_no' => $v['cgoods_no'], 'warehouse_id' => $v['warehouse_id']])->one();
            foreach ($overseas_warehouse as $overseas) {
                /*if ($overseas['estimated_start_logistics_cost'] > 0) {
                    continue;
                }*/
                $overseas['estimated_start_logistics_cost'] = $v['price'];
                if (!$overseas->save()) {
                    $error[] = $v['cgoods_no'];
                }
            }
            if (empty($goods_stock_freight)) {
                $goods_stock_freight = new GoodsStockFreight();
                $goods_stock_freight['cgoods_no'] = $v['cgoods_no'];
                $goods_stock_freight['warehouse_id'] = $v['warehouse_id'];
            }
            $goods_stock_freight['freight_price'] = $v['price'];
            $goods_stock_freight->save();
        }
        if (!empty($error)) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "同步成功，有".implode(',',$error).'同步失败', []);
        } else {
            return $this->FormatArray(self::REQUEST_SUCCESS, "同步成功", []);
        }
    }

    /**
     * @routeName 导出
     * @routeDescription 导出
     */
    public function actionExport()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $tag = $req->get('tag');
        $searchModel = new BlContainerSearch();
        $params = Yii::$app->request->queryParams;
        $where = $searchModel->search($params, $tag);

        $sort = new Expression("CASE WHEN 1=1 THEN bl.status END DESC,CASE WHEN bl.status = 10 THEN bl.id END DESC,CASE WHEN bl.status = 20 THEN bl.update_time END DESC");
        if ($tag != 10) {
            $sort = $tag == 1 ? 'id desc' : 'update_time desc';
        }

        $blg_query = BlContainerGoods::find()->alias('blg')->select('bl.weight,bl.bl_no,bl.size,bl.initial_number,bl.bl_transportation_id,blg.bl_id,blg.cgoods_no,blg.num,gc.goods_img,g.goods_img as ggoods_img')
            ->leftJoin(BlContainer::tableName().' bl','bl.id = blg.bl_id')
            ->leftJoin(GoodsChild::tableName() . ' gc', 'gc.cgoods_no= blg.cgoods_no')
            ->leftJoin(Goods::tableName() . ' g', 'gc.goods_no = g.goods_no');
        $blg_list = BlContainerGoods::getAllByCond($where,null,null,$blg_query);

        $bl_query = BlContainer::find()->alias('bl')->leftJoin(BlContainerGoods::tableName().' blg','blg.bl_id = bl.id');
        $where['and'][] = ['is','blg.id',null];
        $bl_list = BlContainer::getAllByCond($where,$sort,null,$bl_query);

        $list = array_merge($bl_list,$blg_list);

        $page_size = 1000;
        $export_ser = new ExportService($page_size);
        $bl_transportation_id = ArrayHelper::getColumn($list,'bl_transportation_id');
        $bl_transportation_arr = BlContainerTransportation::find()->where(['id' => $bl_transportation_id])->select(['track_no','cjz','id'])->indexBy('id')->asArray()->all();
        $cgoods_sum = BlContainerGoods::find()->select('sum(num) as num,bl_id')->groupBy('bl_id')->indexBy('bl_id')->asArray()->all();
        foreach ($list as $k => $v) {
            $size = GoodsService::getSizeArr($v['size']);
            $bl_transportation = empty($bl_transportation_arr[$v['bl_transportation_id']]) ? [] : $bl_transportation_arr[$v['bl_transportation_id']];
            $data[$k]['initial_number'] = $v['initial_number'];
            $data[$k]['size_l'] = isset($size['size_l']) ? $size['size_l'] : '';
            $data[$k]['size_w'] = isset($size['size_w']) ? $size['size_w'] : '';
            $data[$k]['size_h'] = isset($size['size_h']) ? $size['size_h'] : '';
            $data[$k]['bl_no'] = $v['bl_no'];
            $data[$k]['track_no'] = '';
            if (!empty($bl_transportation)) {
                $data[$k]['track_no'] = $bl_transportation['track_no'];
                $cjz = GoodsService::cjzWeight($v['size'],$bl_transportation['cjz'],0);
            } else {
                $cjz = GoodsService::cjzWeight($v['size'],6000,0);
            }
            $data[$k]['cjz'] = round($cjz,2);
            $data[$k]['weight'] = $v['weight'];
            $data[$k]['cgoods_no'] = !isset($v['cgoods_no']) ? '' : $v['cgoods_no'];
            $data[$k]['num'] = !isset($v['num']) ? '' : $v['num'];
            $image = !isset($v['goods_img']) ? '' : $v['goods_img'];
            if(empty($image)){
                if (isset($v['ggoods_img'])) {
                    $image = json_decode($v['ggoods_img'], true);
                    $image = empty($image) || !is_array($image) ? '' : current($image)['img'];
                }
            }
            $data[$k]['image'] = empty($image) ? '' : $image;
            $bl_id = '';
            if (isset($v['bl_id'])) {
                $bl_id = $v['bl_id'];
            }
            $cgoods_num = empty($cgoods_sum[$bl_id]) ? '' : $cgoods_sum[$bl_id];
            $data[$k]['cgoods_num'] = empty($cgoods_num) ? '' : $cgoods_num['num'];
        }

        $column = [
            'initial_number' => '序号',
            'bl_no' => '提单箱编号',
            'track_no' => '物流编号',
            'size_l' => '长',
            'size_w' => '宽',
            'size_h' => '高',
            'weight' => '重量',
            'cjz' => '材积重',
            'cgoods_no' => '商品编号',
            'num' => '数量',
            'cgoods_num' => '商品总数',
            'image' => '商品主图',
        ];

        $result = $export_ser->forData($column,$data,'提单箱导出' . date('ymdhis'));
        return $this->FormatArray(self::REQUEST_SUCCESS, "", $result);
    }

    /**
     * @routeName 重置估算重量
     * @routeDescription 重置估算重量
     */
    public function actionResetEstimateWeight()
    {
        $req = Yii::$app->request;
        \Yii::$app->response->format = Response::FORMAT_JSON;
        $bl_transportation_id = $req->get('id');
        $bl_transportation = BlContainerTransportation::findOne($bl_transportation_id);
        $bl_transportation->estimate_weight = 0;
        $bl_transportation->goods_count = 0;
        $bl_transportation->bl_container_count = 0;
        $bl_ids = BlContainer::find()->where(['bl_transportation_id' => $bl_transportation_id])->all();
        if ($bl_transportation->save()) {
            $this->updateTransportation($bl_transportation_id,$bl_ids);
            return $this->FormatArray(self::REQUEST_SUCCESS, '同步成功', []);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL, '同步失败', []);
        }
    }

    /**
     * @routeName 终止部分到货
     * @routeDescription 终止部分到货
     */
    public function actionFinishArrival()
    {
        $req = Yii::$app->request;
        \Yii::$app->response->format = Response::FORMAT_JSON;
        $bl_container_id = $req->get('id');
        $bl_container = BlContainer::findOne($bl_container_id);
        $bl_container->status = BlContainer::STATUS_DELIVERED;
        if ($bl_container->save()) {
            return $this->FormatArray(self::REQUEST_SUCCESS, '操作成功', []);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL, '操作失败', []);
        }
    }

    /**
     * 处理商品
     * @param $data
     * @return array
     */
    public function dealGoods($data)
    {
        $goods = [];
        $cgoods_nos = ArrayHelper::getColumn($data, 'cgoods_no');
        $goods_childes = GoodsChild::find()->where(['cgoods_no' => $cgoods_nos])->indexBy('cgoods_no')->asArray()->all();
        $goods_nos = ArrayHelper::getColumn($goods_childes, 'goods_no');
        $goods_lists = Goods::find()->where(['goods_no' => $goods_nos])->indexBy('goods_no')->asArray()->all();
        foreach ($data as $bl_container_good) {
            if (empty($goods_childes[$bl_container_good['cgoods_no']])) {
                continue;
            }
            $goods_child = $goods_childes[$bl_container_good['cgoods_no']];
            $info = $goods_lists[$goods_child['goods_no']];
            $info = (new GoodsService())->dealGoodsInfo($info, $goods_child);
            $image = json_decode($info['goods_img'], true);
            $info['goods_img'] = empty($image) || !is_array($image) ? '' : current($image)['img'];
            $goods[] = array_merge($info, $bl_container_good);
        }
        return $goods;
    }

    /*
     * 获取提单箱商品
     * @param $id
     */
    public static function getBlContainerGoodsList($id)
    {
        $bl_container_goods = BlContainerGoods::find()->where(['bl_id' => $id])->select(['cgoods_no', 'num', 'price', 'finish_num', 'id'])->asArray()->all();
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
            $list[$k]['sku_no'] = $info['sku_no'];
            $list[$k]['finish_num'] = $v['finish_num'];
            $list[$k]['num'] = $v['num'];
            $list[$k]['price'] = $v['price'];
            $list[$k]['id'] = $v['id'];
            $list[$k]['goods_img'] = empty($image) || !is_array($image) ? '' : current($image)['img'];
            $list[$k]['goods_name'] = $info['goods_name'];
            $list[$k]['goods_no'] = $info['goods_no'];
        }
        return $list;
    }

    /**
     * 更新提单箱物流
     * @param $bl_transportation_id
     * @param $bl_ids
     * @param $bl_nos
     * @param bool $delete
     * @return true
     */
    public function updateTransportation($bl_transportation_id,$bl_ids,$bl_nos = [],$delete = false)
    {
        if (!empty($bl_transportation_id)) {
            $transportation = BlContainerTransportation::findOne($bl_transportation_id);
        }
        $bl_id = 0;
        foreach ($bl_ids as $v) {
            $count = 1;
            $goods_count = $v['goods_count'];
            $weight = $v['weight'];
            $bl_status = $v['status'];
            if (!empty($bl_nos)) {
                $v['bl_no'] = $bl_nos[$v['id']];
            }
            if (!empty($transportation)) {
                $cjz = GoodsService::cjzWeight($v['size'],$transportation['cjz']);
                if ($cjz > $weight) {
                    $weight = $cjz;
                }
            }
            if (!in_array($v['status'], [BlContainer::STATUS_DELIVERED, BlContainer::STATUS_PARTIAL_DELIVERED])) {
                $v['status'] = BlContainer::STATUS_NOT_DELIVERED;
            }
            if ($delete) {
                if ($bl_status == BlContainer::STATUS_PARTIAL_DELIVERED) {
                    continue;
                }
                $transportation = BlContainerTransportation::findOne($v['bl_transportation_id']);
                $v['status'] = BlContainer::STATUS_WAIT_SHIP;
                $v['bl_no'] = '';
                if (empty($transportation)) {
                    $v['bl_transportation_id'] = 0;
                    $v->save();
                    continue;
                }
                $cjz = GoodsService::cjzWeight($v['size'],$transportation['cjz']);
                if ($cjz > $weight) {
                    $weight = $cjz;
                }
                $goods_count = -$v['goods_count'];
                $weight = -$weight;
                $count = -$count;
                $bl_id = $v['id'];
            }
            $v['bl_transportation_id'] = $delete == true ? 0 : $transportation['id'];
            $transportation['goods_count'] = $transportation['goods_count'] + $goods_count;
            $transportation['estimate_weight'] = $transportation['estimate_weight'] + $weight;
            $transportation['bl_container_count'] = $transportation['bl_container_count'] + $count;
            $transportation->save();
            $v->save();
            BlContainerService::updateBlGoodsPrice($transportation,$bl_id);
        }
        return true;
    }


    /**
     * Finds the BlContainer model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return BlContainer the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = BlContainer::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
