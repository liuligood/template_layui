<?php

namespace backend\controllers;

use common\base\BaseController;
use common\components\statics\Base;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsExtend;
use common\models\goods\GoodsPackaging;
use common\models\goods\GoodsStock;
use common\models\GoodsShop;
use common\models\purchase\PurchaseOrder;
use common\models\purchase\PurchaseOrderGoods;
use common\models\Shop;
use common\models\Supplier;
use common\models\warehousing\BlContainer;
use common\models\warehousing\BlContainerGoods;
use common\models\warehousing\WarehouseProvider;
use common\services\goods\GoodsService;
use common\services\warehousing\BlContainerService;
use common\services\warehousing\WarehouseService;
use Yii;
use common\models\warehousing\OverseasGoodsShipment;
use backend\models\search\OverseasGoodsShipmentSearch;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use yii\web\Response;


class OverseasGoodsShipmentController extends BaseController
{
    public function model()
    {
        return new OverseasGoodsShipment();
    }

    public function query($type = 'select')
    {
        $select = 'ogs.*,g.goods_no,gc.goods_img,g.goods_img as ggoods_img,gc.sku_no,g.goods_name,g.goods_name_cn,gc.sku_no';
        $query = OverseasGoodsShipment::find()
            ->alias('ogs')->select($select);
        $query->leftJoin(GoodsChild::tableName() . ' gc', 'gc.cgoods_no= ogs.cgoods_no');
        $query->leftJoin(Goods::tableName() . ' g', 'gc.goods_no = g.goods_no');
        return $query;
    }

    /**
     * @routeName 海外仓发货主页
     * @routeDescription 海外仓发货主页
     */
    public function actionIndex()
    {
        $req = Yii::$app->request;
        $tag = $req->get('tag',1);
        $shop = WarehouseService::getPlatformOverseasWarehouseShop();
        $overseas_shop = [];
        foreach ($shop as $k=>$v) {
            $overseas_shop[$k] = $v;
        }
        return $this->render('index',['tag' => $tag,'overseas_shop' => $overseas_shop]);
    }

    /**
     * @routeName 海外仓发货列表
     * @routeDescription 海外仓发货列表
     */
    public function actionList()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $tag = $req->get('tag',1);
        $sort = 'id desc';
        if ($tag == 40) {
            $sort = 'update_time desc';
        }
        $searchModel = new OverseasGoodsShipmentSearch();
        $where = $searchModel->search(Yii::$app->request->queryParams,$tag);
        $data = $this->lists($where,$sort);
        $supplier = [0 => '无供应商'] + Supplier::allSupplierName();
        $warehouse = WarehouseService::getWarehouseMap();
        $shop = WarehouseService::getPlatformOverseasWarehouseShop();
        foreach ($data['list'] as &$info){
            $image = $info['goods_img'];
            if(empty($info['goods_img'])){
                $image = json_decode($info['ggoods_img'], true);
                $image = empty($image) || !is_array($image) ? '' : current($image)['img'];
            }
            $info['image'] = $image;
            $goods_packing = GoodsPackaging::find()
                ->where(['goods_no' => $info['goods_no'],'warehouse_id' => $info['warehouse_id']])
                ->orWhere(['warehouse_id' => 9999,'goods_no' => $info['goods_no']])->asArray()->all();
            $shop_name = '';

            $warehouse_info = WarehouseService::getInfo($info['warehouse_id']);
            if($warehouse_info['warehouse_provider']['warehouse_provider_type'] == WarehouseProvider::TYPE_PLATFORM) {
                $goods_shop = GoodsShop::find()->where(['platform_type'=>$warehouse_info['platform_type'],'cgoods_no'=>$info['cgoods_no'],'shop_id'=>array_keys($shop)])->one();
                $shop_id = !empty($goods_shop['shop_id'])?$goods_shop['shop_id']:0;
                $shop_name = !empty($shop[$shop_id])?$shop[$shop_id]:'';
            }
            $info['purchase_desc'] = GoodsExtend::find()->where(['goods_no' => $info['goods_no']])->select('purchase_desc')->scalar();
            $info['supplier_name'] = empty($supplier[$info['supplier_id']]) ? '' : $supplier[$info['supplier_id']];
            $info['warehouse_name'] = empty($warehouse[$info['warehouse_id']]) ? '' : $warehouse[$info['warehouse_id']];
            $info['shop_name'] = $shop_name;
            $info['add_time'] = $info['add_time'] == 0 ? '' : Yii::$app->formatter->asDatetime($info['add_time']);
            $info['update_time'] = $info['update_time'] == 0 ? '' : Yii::$app->formatter->asDatetime($info['update_time']);
            $info['purchase_time'] = empty($info['purchase_time']) ? '' : Yii::$app->formatter->asDatetime($info['purchase_time']);
            $info['arrival_time'] = empty($info['arrival_time']) ? '' : Yii::$app->formatter->asDatetime($info['arrival_time']);
            $info['packing_time'] = empty($info['packing_time']) ? '' : Yii::$app->formatter->asDatetime($info['packing_time']);
            $info['goods_packing'] = $goods_packing;
            $info['inventory_quantity'] = GoodsStock::find()->where(['warehouse'=>$info['warehouse_id'],'cgoods_no'=>$info['cgoods_no']])->select('real_num')->scalar();
            $info['transit_quantity'] = BlContainerGoods::find()->where(['warehouse_id'=>$info['warehouse_id'],'cgoods_no'=>$info['cgoods_no'],'status'=>BlContainer::STATUS_NOT_DELIVERED])->select('sum(num) as num')->scalar();
            $info['status_name'] = empty(OverseasGoodsShipment::$status_maps[$info['status']]) ? '' : OverseasGoodsShipment::$status_maps[$info['status']];
        }
        return $this->FormatLayerTable(self::REQUEST_LAY_SUCCESS,"获取成功",$data['list'],$data['pages']->totalCount);
    }

    /**
     * @routeName 更新海外仓发货
     * @routeDescription 更新海外仓发货
     */
    public function actionUpdate()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        $model = $this->findModel($id);
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($model->load($req->post(), '') == false) {
                return $this->FormatArray(self::REQUEST_FAIL, "参数异常", []);
            }
            if ($model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, '更新失败', []);
            }
        }
        return $this->render('update',['info' => $model->toArray()]);
    }


    /**
     * 装箱
     * @param $goods_shipment
     * @param $number
     * @param $bl_goods_id
     * @return void
     */
    public static function packedFinish($goods_shipment,$number,$bl_goods_id)
    {
        if ($goods_shipment['num'] == (int)$number) {
            $goods_shipment['status'] = OverseasGoodsShipment::STATUS_FINISH;
            $goods_shipment['packing_time'] = time();
            $goods_shipment['bl_container_goods_id'] = (string)$bl_goods_id;
            $goods_shipment->save();
        }
        if ($goods_shipment['num'] != (int)$number) {
            $goods_shipment_model = new OverseasGoodsShipment();
            $goods_shipment_model['num'] = (int)$number;
            $goods_shipment_model['status'] = OverseasGoodsShipment::STATUS_FINISH;
            $goods_shipment_model['supplier_id'] = $goods_shipment['supplier_id'];
            $goods_shipment_model['cgoods_no'] = $goods_shipment['cgoods_no'];
            $goods_shipment_model['warehouse_id'] = $goods_shipment['warehouse_id'];
            $goods_shipment_model['porder_id'] = $goods_shipment['porder_id'];
            $goods_shipment_model['purchase_time'] = $goods_shipment['purchase_time'];
            $goods_shipment_model['arrival_time'] = $goods_shipment['arrival_time'];
            $goods_shipment_model['bl_container_goods_id'] = $bl_goods_id;
            $goods_shipment_model['packing_time'] = time();
            $goods_shipment['num'] = $goods_shipment['num'] - (int)$number;
            $goods_shipment->save();
            $goods_shipment_model->save();
        }
    }

    /**
     * @routeName 到货
     * @routeDescription 到货
     */
    public function actionArrival()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        $id = explode(',',$id);
        $list = [];
        $goods_shipment = OverseasGoodsShipment::find()->alias('ogs')
            ->leftJoin(PurchaseOrder::tableName() . 'po', 'po.order_id = ogs.porder_id')
            ->leftJoin(PurchaseOrderGoods::tableName() . 'pog', 'po.order_id = ogs.porder_id and pog.cgoods_no = ogs.cgoods_no')
            ->select('ogs.*,po.relation_no,pog.goods_no,pog.sku_no,pog.goods_pic,pog.goods_name,pog.goods_price')
            ->where(['ogs.id' => $id,'pog.goods_status' => [PurchaseOrderGoods::GOODS_STATUS_UNCONFIRMED, PurchaseOrderGoods::GOODS_STATUS_NORMAL]])
            ->andWhere(['!=','po.order_status',PurchaseOrder::ORDER_STATUS_CANCELLED])
            ->asArray()->all();
        $supplier_name = [0 => '无供应商'] + Supplier::allSupplierName();
        $warehouse = WarehouseService::getWarehouseMap();
        foreach ($goods_shipment as $k => &$v) {
            $v['supplier_name'] = empty($supplier_name[$v['supplier_id']]) ? '' : $supplier_name[$v['supplier_id']];
            $v['warehouse_name'] = empty($warehouse[$v['warehouse_id']]) ? '' : $warehouse[$v['warehouse_id']];
            $list[$v['porder_id']][] = $v;
        }
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            $finish = $post['finish_num'];
            foreach ($finish as $id => $num) {
                if ($num == 0) {
                    continue;
                }
                $goods_shipment = OverseasGoodsShipment::find()->where(['id' => $id,'status' => OverseasGoodsShipment::STATUS_WAIT_SHIP])->one();
                if (empty($goods_shipment)) {
                    return $this->FormatArray(self::REQUEST_FAIL,'采购商品不存在，请刷新页面',[]);
                }
                $purchase_order_goods = PurchaseOrderGoods::find()->where(['order_id' => $goods_shipment['porder_id'],'cgoods_no' => $goods_shipment['cgoods_no']])->one();

                $wait_packed = OverseasGoodsShipment::find()
                    ->where(['porder_id' => $goods_shipment['porder_id'],'cgoods_no' => $goods_shipment['cgoods_no'],'status' => OverseasGoodsShipment::STATUS_WAIT_PACKED])
                    ->one();

                if (empty($wait_packed)) {
                    $model = new OverseasGoodsShipment();
                    $model['num'] = $num;
                    $model['cgoods_no'] = $goods_shipment['cgoods_no'];
                    $model['supplier_id'] = $goods_shipment['supplier_id'];
                    $model['warehouse_id'] = $goods_shipment['warehouse_id'];
                    $model['status'] = OverseasGoodsShipment::STATUS_WAIT_PACKED;
                    $model['porder_id'] = $goods_shipment['porder_id'];
                    $model['purchase_time'] = $goods_shipment['purchase_time'];
                    $model['arrival_time'] = time();
                    $model->save();
                    if ($goods_shipment['num'] - $num == 0) {
                        $goods_shipment->delete();
                    } else {
                        $goods_shipment['num'] = $goods_shipment['num'] - $num;
                        $goods_shipment->save();
                    }
                } else {
                    $wait_packed['num'] = $wait_packed['num'] + $num;
                    $wait_packed['arrival_time'] = time();
                    $wait_packed->save();
                    if ($goods_shipment['num'] - $num == 0) {
                        $goods_shipment->delete();
                    } else {
                        $goods_shipment['num'] = $goods_shipment['num'] - $num;
                        $goods_shipment->save();
                    }
                }

                $purchase_order_goods['goods_finish_num'] = $purchase_order_goods['goods_finish_num'] + $num;

                $purchase_order_goods->save();

                $purchase_order = PurchaseOrder::find()->where(['order_id' => $goods_shipment['porder_id']])->one();
                $purchase_order_goods_num = PurchaseOrderGoods::find()->where(['order_id' => $goods_shipment['porder_id']])
                    ->select('sum(goods_finish_num) as finish_num,sum(goods_num) as num')->asArray()->one();
                if ($purchase_order_goods_num['finish_num'] == $purchase_order_goods_num['num']) {
                    $purchase_order['order_status'] = PurchaseOrder::ORDER_STATUS_RECEIVED;
                    $purchase_order->save();
                }
            }
            return $this->FormatArray(self::REQUEST_SUCCESS,'操作成功',[]);
        }
        return $this->render('arrival',['list' => $list]);
    }


    /**
     * @routeName 设置空运
     * @routeDescription 设置空运
     */
    public function actionSetAirLogistics()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $ids = $req->post('id');
        $overseas_goods_shipment = OverseasGoodsShipment::find()->where(['id' => $ids])->all();
        $error = [];
        foreach ($overseas_goods_shipment as $v) {
            $v['air_logistics'] = 1;
            if (!$v->save()) {
                $error[] = $v['id'];
            }
        }
        if (!empty($error)) {
            return $this->FormatArray(self::REQUEST_SUCCESS,'操作成功,有'.implode(',',$error).'操作失败',[]);
        } else {
            return $this->FormatArray(self::REQUEST_SUCCESS,'操作成功',[]);
        }
    }

    /**
     * @routeName 装箱
     * @routeDescription 装箱
     */
    public function actionCrating()
    {
        $req = Yii::$app->request;
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $transaction = \Yii::$app->db->beginTransaction();
            $initial_number_lists = $req->post('initial_number');
            $pack_count = count($initial_number_lists);
            $ovg_num = $req->post('ovg');
            if(empty($ovg_num)) {
                return $this->FormatArray(self::REQUEST_FAIL, "商品不能为空", []);
            }

            //均分箱子
            $ave_num = 0;
            if($pack_count > 1) {
                if(count($ovg_num) > 1) {
                    $transaction->rollBack();
                    return $this->FormatArray(self::REQUEST_FAIL, "均分箱子商品只能为同一个", []);
                }

                $goods_lists = [];
                $shipment = current($ovg_num);
                $cgoods_no = key($ovg_num);
                $num = array_sum($shipment);
                if ($pack_count > $num) {
                    $transaction->rollBack();
                    return $this->FormatArray(self::REQUEST_FAIL, "箱子数不可大于箱子数量", []);
                }
                if ($num % $pack_count > 0) {
                    $transaction->rollBack();
                    return $this->FormatArray(self::REQUEST_FAIL, "每箱数量不一致", []);
                }
                $ave_num = $num / $pack_count;
                for($i = 0;$i < $pack_count; $i++) {
                    $new_shipment = [];
                    foreach ($shipment as $goods_shipment_id => &$numbers) {
                        $tmp_numbers = array_sum($new_shipment);
                        $tmp_ave_num = $ave_num - $tmp_numbers;
                        if ($numbers > $tmp_ave_num) {
                            $numbers = $numbers - $tmp_ave_num;
                            $new_shipment[$goods_shipment_id] = $tmp_ave_num;
                            break;
                        } else {
                            $new_shipment[$goods_shipment_id] = $numbers;
                            unset($shipment[$goods_shipment_id]);
                        }
                    }
                    $goods_lists[$i][$cgoods_no] = $new_shipment;
                }
            } else {
                $goods_lists[0] = $ovg_num;
            }

            $i = 0;
            foreach ($initial_number_lists as $initial_number) {
                $tmp_goods_lists = $goods_lists[$i];
                $i ++;
                $model = new BlContainer();
                $model->load($req->post(), '');
                $model->initial_number = $initial_number;
                $model->size = (new GoodsService())->genSize($req->post());
                $model->status = BlContainer::STATUS_WAIT_SHIP;
                if (!$model->save()) {
                    $transaction->rollBack();
                    return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
                }
                $goods = [];
                $error = [];
                foreach ($tmp_goods_lists as $k => $shipment) {
                    $num = array_sum($shipment);
                    $goods[] = ['cgoods_no' => $k, 'num' => $num];
                    $bl_goods_id = BlContainerService::updateGoods($model->id, $goods);
                    foreach ($shipment as $goods_shipment_id => $numbers) {
                        $goods_shipment = OverseasGoodsShipment::findOne($goods_shipment_id);
                        if ($goods_shipment['num'] < $numbers) {
                            $error[] = $goods_shipment_id;
                            continue;
                        }
                        self::packedFinish($goods_shipment, $numbers, $bl_goods_id);
                    }
                }
                if (!empty($error)) {
                    $transaction->rollBack();
                    return $this->FormatArray(self::REQUEST_FAIL, "装箱数量不能超过采购数量", []);
                }

            }
            $transaction->commit();
            return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
        }

        $ovg_id = $req->get('id');
        $ovg_id_arr = explode(',', $ovg_id);
        $ovg = OverseasGoodsShipment::find()->where(['id' => $ovg_id_arr])
            ->select('warehouse_id,cgoods_no,num,id as ovg_id')->asArray()->all();
        $cgoods_no = ArrayHelper::getColumn($ovg, 'cgoods_no');
        $cgoods_no = array_unique($cgoods_no);
        $goods = $this->dealGoods($ovg);
        $goods_packaging = [];
        if (count($cgoods_no) == 1) {//只有一件开启多个装箱
            $goods_info = current($goods);
            $goods_packaging = GoodsPackaging::find()->where(['goods_no' => $goods_info['goods_no']])->asArray()->all();
        }
        $warehouse_id = null;
        foreach ($ovg as $v) {
            if (is_null($warehouse_id)) {
                $warehouse_id = $v['warehouse_id'];
            }
            if ($warehouse_id != $v['warehouse_id']) {
                return '必须为同一仓库,存在不同仓库商品';
            }
        }
        return $this->render('crating', ['warehouse_id' => $warehouse_id, 'goods' => $goods, 'ovg_id' => $ovg_id, 'goods_packaging' => $goods_packaging]);
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

    /**
     * @routeName 批量操作
     * @routeDescription 批量操作
     */
    public function actionBatchConfirm()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $status = $req->get('status');
        $is_del = $req->get('is_del');
        $ids = $req->post('id');
        $overseas_arr = OverseasGoodsShipment::find()->where(['id' => $ids])->all();
        $status_arr = [OverseasGoodsShipment::STATUS_UNCONFIRMED,OverseasGoodsShipment::STATUS_WAIT_PURCHASE,OverseasGoodsShipment::STATUS_WAIT_SHIP,OverseasGoodsShipment::STATUS_WAIT_PACKED];
        $error = [];
        foreach ($overseas_arr as $v) {
            if (!in_array($v['status'],$status_arr)) {
                $error[] = $v['id'];
                continue;
            }
            if ($v['status'] == OverseasGoodsShipment::STATUS_WAIT_PACKED) {
                $exists = OverseasGoodsShipment::find()->where(['cgoods_no' => $v['cgoods_no'],'supplier_id' => $v['supplier_id'],'warehouse_id' => $v['warehouse_id'],'porder_id' => $v['porder_id'],'status' => OverseasGoodsShipment::STATUS_WAIT_SHIP])->exists();
                if ($exists) {
                    $error[] = $v['id'];
                    continue;
                }
            }

            $v['status'] = $status;
            if (!$v->save()) {
                $error[] = $v['id'];
            }
            if ($status == OverseasGoodsShipment::STATUS_WAIT_PURCHASE && $is_del == 1) {
                $porder_id = $v['porder_id'];
                PurchaseOrderGoods::deleteAll(['order_id' => $porder_id]);
                PurchaseOrder::deleteAll(['order_id' => $porder_id]);
            }
        }
        if (!empty($error)) {
            $error = implode(',',$error);
            return $this->FormatArray(self::REQUEST_SUCCESS,'操作成功,有id为'.$error.'操作失败',[]);
        } else {
            return $this->FormatArray(self::REQUEST_SUCCESS,'操作成功',[]);
        }
    }

    /**
     * Finds the OverseasGoodsShipment model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return OverseasGoodsShipment the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = OverseasGoodsShipment::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
