<?php

namespace backend\controllers;

use common\base\BaseController;
use common\components\statics\Base;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsStock;
use common\models\GoodsSource;
use common\models\Order;
use common\models\OrderGoods;
use common\models\Supplier;
use common\models\SupplierRelationship;
use common\models\warehousing\BlContainer;
use common\models\warehousing\BlContainerGoods;
use common\models\warehousing\OverseasGoodsShipment;
use common\services\warehousing\OverseasGoodsShipmentService;
use common\services\warehousing\WarehouseService;
use Yii;
use common\models\warehousing\WarehouseProductSales;
use backend\models\search\WarehouseProductSalesSearch;
use yii\web\NotFoundHttpException;
use yii\web\Response;


class WarehouseProductSalesController extends BaseController
{
    public function model()
    {
        return new WarehouseProductSales();
    }

    public function query($type = 'select')
    {
        $query = GoodsStock::find()->alias('gs')
            ->select('wps.*,g.goods_no,gc.goods_img,g.goods_img as ggoods_img,gc.sku_no,g.goods_name,g.goods_name_cn,gc.sku_no,gs.cgoods_no as ccgoods_no,gs.num as inventory_quantity,gs.warehouse');
        $query->leftJoin(WarehouseProductSales::tableName() . ' wps','wps.cgoods_no = gs.cgoods_no and wps.warehouse_id = gs.warehouse');
        $query->leftJoin(GoodsChild::tableName() . ' gc', 'gc.cgoods_no= gs.cgoods_no');
        $query->leftJoin(Goods::tableName() . ' g', 'gc.goods_no = g.goods_no');
        return $query;
    }

    /**
     * @routeName 仓库商品销售情况主页
     * @routeDescription 仓库商品销售情况主页
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * @routeName 仓库商品销售情况列表
     * @routeDescription 仓库商品销售情况列表
     */
    public function actionList()
    {
        Yii::$app->response->format=Response::FORMAT_JSON;
        $searchModel=new WarehouseProductSalesSearch();
        $where = $searchModel->search(Yii::$app->request->queryParams);
        $data = $this->lists($where,'seven_day_sales desc,id desc');
        $warehouse_map = WarehouseService::getWarehouseMap();
        $warehouse_type_map = WarehouseService::getWarehouseProviderType();
        $goods_shipment_where = [OverseasGoodsShipment::STATUS_FINISH,OverseasGoodsShipment::STATUS_CANCELLED];
        foreach ($data['list'] as &$info){
            $image = $info['goods_img'];
            if(empty($info['goods_img'])){
                $image = json_decode($info['ggoods_img'], true);
                $image = empty($image) || !is_array($image) ? '' : current($image)['img'];
            }
            $info['one_day_sales'] = empty($info['one_day_sales']) ? 0 : $info['one_day_sales'];
            $info['seven_day_sales'] = empty($info['seven_day_sales']) ? 0 : $info['seven_day_sales'];
            $info['fifteen_day_sales'] = empty($info['fifteen_day_sales']) ? 0 : $info['fifteen_day_sales'];
            $info['thirty_day_sales'] = empty($info['thirty_day_sales']) ? 0 : $info['thirty_day_sales'];
            $info['ninety_day_sales'] = empty($info['ninety_day_sales']) ? 0 : $info['ninety_day_sales'];
            $info['total_sales'] = empty($info['total_sales']) ? 0 : $info['total_sales'];
            $info['image'] = $image;
            $info['transit_quantity'] = BlContainerGoods::find()->where(['warehouse_id'=>$info['warehouse'],'cgoods_no'=>$info['ccgoods_no'],'status'=>BlContainer::STATUS_NOT_DELIVERED])->select('sum(num) as num')->scalar();
            $info['warehouse_type'] = empty($warehouse_type_map[$info['warehouse']]) ? '' : $warehouse_type_map[$info['warehouse']];
            $info['purchasing'] = OverseasGoodsShipment::find()->where(['cgoods_no' => $info['ccgoods_no'],'warehouse_id' => $info['warehouse']])
                ->andWhere(['not in', 'status', $goods_shipment_where])
                ->select('sum(num) as purchase_num')->scalar();
            $info['order_frequency'] = $info['order_frequency'] == 0 ? '' : round($info['order_frequency'] / 86400,4);
            //$info['safe_stock_type'] = empty(WarehouseProductSales::$type_maps[$info['safe_stock_type']]) ? '未设置类型' : WarehouseProductSales::$type_maps[$info['safe_stock_type']];
            $info['warehouse_name'] = empty($warehouse_map[$info['warehouse']]) ? '' : $warehouse_map[$info['warehouse']];
            $info['construct'] = 'overseas';
            if (in_array($info['warehouse'],[WarehouseService::WAREHOUSE_OWN, WarehouseService::WAREHOUSE_ANJ])) {
                $info['construct'] = 'own';
            }
        }
        return $this->FormatLayerTable(self::REQUEST_LAY_SUCCESS,"获取成功",$data['list'],$data['pages']->totalCount);
    }

    /**
     * @routeName 更新仓库商品销售情况
     * @routeDescription 更新仓库商品销售情况
     * @throws
     */
    public function actionUpdate()
    {
        $req = Yii::$app->request;
        $cgoods_no = $req->get('cgoods_no');
        $warehouse_id = $req->get('warehouse_id');
        $model = WarehouseProductSales::find()->where(['warehouse_id' => $warehouse_id,'cgoods_no' => $cgoods_no])->one();
        if (empty($model)) {
            $model['id'] = '';
            $model['cgoods_no'] = $cgoods_no;
            $model['warehouse_id'] = $warehouse_id;
            $model['one_day_sales'] = 0;
            $model['seven_day_sales'] = 0;
            $model['fifteen_day_sales'] = 0;
            $model['thirty_day_sales'] = 0;
            $model['ninety_day_sales'] = 0;
            $model['total_sales'] = 0;
            $model['safe_stock_type'] = 0;
            $model['safe_stock_param'] = '';
            $model['stock_up_day'] = 0;
            $model['safe_stock_num'] = 0;
        }
        if ($req->isPost) {
            Yii::$app->response->format=Response::FORMAT_JSON;
            $post = $req->post();
            $info = WarehouseProductSales::findOne($post['id']);
            if (empty($info)) {
                $info = new WarehouseProductSales();
                $info['cgoods_no'] = $post['cgoods_no'];
                $info['warehouse_id'] = $post['warehouse_id'];
            }
            $info['safe_stock_num'] = $post['safe_stock_type'] == 3 ? $post['safe_stock_num'] : $post['safe_stock_num_val'];
            $info['safe_stock_type'] = $post['safe_stock_type'];
            $info['stock_up_day'] = isset($post['stock_up_day']) ? $post['stock_up_day'] : 0;
            $info['safe_stock_param'] = $post['safe_stock_param'];
            if ($info->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "修改成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, "删除失败", []);
            }
        } else {
            $days = WarehouseProductSalesController::dealDaysTotal($model);
            $goods = GoodsStock::find()->alias('wps')
                ->where(['wps.cgoods_no' => $model['cgoods_no']])
                ->select('g.goods_no,gc.goods_img,g.goods_img as ggoods_img,gc.sku_no,g.goods_name')
                ->leftJoin(GoodsChild::tableName() . ' gc', 'gc.cgoods_no= wps.cgoods_no')
                ->leftJoin(Goods::tableName() . ' g', 'gc.goods_no = g.goods_no')
                ->asArray()->one();
            $image = $goods['goods_img'];
            if(empty($goods['goods_img'])){
                $image = json_decode($goods['ggoods_img'], true);
                $image = empty($image) || !is_array($image) ? '' : current($image)['img'];
            }
            $model['safe_stock_type'] = $model['safe_stock_type'] == 0 ? 1 : $model['safe_stock_type'];
            $data = [
                'days' => $days,
                'info' => is_array($model) ? $model : $model->toArray(),
                'goods_no' => $goods['goods_no'],
                'image' => $image,
                'sku_no' => $goods['sku_no'],
                'goods_name' => $goods['goods_name'],
            ];
        }
        return $this->render('update',['data' => $data]);
    }

    /**
     * @routeName 获取平均日销量
     * @routeDescription 获取平均日销量
     * @return array
     * @throws
     */
    public function actionGetAverageDay()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $safe_stock_day = (int)$req->get('safe_stock_day');
        $cgoods_no = $req->get('cgoods_no');
        $warehouse_id = $req->get('warehouse_id');
        $where = [];
        $where['og.cgoods_no'] = $cgoods_no;
        $where['o.warehouse'] = $warehouse_id;
        $safe_stock_day = WarehouseProductSalesController::getStockUpNum($where,$safe_stock_day);
        return $this->FormatArray(self::REQUEST_SUCCESS,'获取成功',['safe_stock_day' => $safe_stock_day]);
    }

    /**
     * 处理天数
     * @param $data
     * @return array
     */
    public static function dealDaysTotal($data)
    {
        $list = [];
        $day_list[1] = $data['one_day_sales'];
        $day_list[7] = $data['seven_day_sales'];
        $day_list[15] = $data['fifteen_day_sales'];
        $day_list[30] = $data['thirty_day_sales'];
        $day_list[90] = $data['ninety_day_sales'];
        foreach ($day_list as $k => $v) {
            $list[$k]['average_day'] = round($v / $k,2);
            $list[$k]['total_sales'] = $v;
        }
        return $list;
    }

    /**
     * 获取日均销量
     * @param $where
     * @param $safe_stock_day
     * @return int
     */
    public static function getStockUpNum($where,$safe_stock_day)
    {
        $old_day_time = strtotime(date('Y-m-d',strtotime("-".$safe_stock_day." day")));
        $now_time = strtotime(date('Y-m-d',time()));
        $sum = OrderGoods::find()->alias('og')
            ->select('sum(og.goods_num) as num')
            ->leftJoin(Order::tableName().' o','o.order_id = og.order_id')
            ->where('o.date >='.$old_day_time)
            ->andWhere('o.date <='.$now_time)
            ->andWhere($where)
            ->andWhere(['!=','o.order_status',Order::ORDER_STATUS_CANCELLED])
            ->scalar();
        $safe_stock_day = $sum != 0 ? round($sum / $safe_stock_day,2) : 0;
        return $safe_stock_day;
    }

    /**
     * Finds the WarehouseProductSales model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return WarehouseProductSales the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = WarehouseProductSales::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    /**
     * @routeName 加入采购计划
     * @routeDescription 加入采购计划
     * @throws
     */
    public function actionAddPurchase()
    {
        $req = Yii::$app->request;
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            try {
                $warehouse_id = $post['warehouse_id'];
                $cgoods_no = $post['cgoods_no'];
                $data = [
                    'num' => $post['num'],
                    'supplier_id' => empty($post['supplier_id'])?0:$post['supplier_id'],
                    'warehouse_id' => $warehouse_id,
                    'cgoods_no' => $cgoods_no,
                ];
                if ((new OverseasGoodsShipmentService())->addPurchasePlanning($data)) {
                    return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
                } else {
                    return $this->FormatArray(self::REQUEST_FAIL, '添加失败', []);
                }
            } catch (\Exception $e) {
                return $this->FormatArray(self::REQUEST_FAIL, $e->getMessage() . $e->getTraceAsString() . $e->getFile() . $e->getLine(), []);
            }
        }

        $warehouse_id = $req->get('warehouse_id');
        $cgoods_no = $req->get('cgoods_no');
        $warehouse_map = WarehouseService::getWarehouseMap();
        $goods = GoodsChild::find()->alias('gc')
            ->where(['gc.cgoods_no' => $cgoods_no])
            ->select('g.goods_no,gc.goods_img,g.goods_img as ggoods_img,gc.sku_no,g.goods_name,gs.price as 1688_price,gc.price')
            ->leftJoin(Goods::tableName() . ' g', 'gc.goods_no = g.goods_no')
            ->leftJoin(GoodsSource::tableName().' gs','gs.goods_no = gc.goods_no and gs.platform_type = '.Base::PLATFORM_1688)
            ->asArray()->one();
        $image = $goods['goods_img'];
        if (empty($goods['goods_img'])) {
            $image = json_decode($goods['ggoods_img'], true);
            $image = empty($image) || !is_array($image) ? '' : current($image)['img'];
        }

        $supplier = SupplierRelationship::find()->alias('sr')
            ->select('sr.supplier_id,s.name,sr.is_prior,sr.purchase_amount')
            ->leftJoin(Supplier::tableName() . ' s', 's.id = sr.supplier_id')
            ->where(['goods_no' => $goods['goods_no']])->asArray()->all();
        
        $data = [
            'cgoods_no' => $cgoods_no,
            'warehouse_id' => $warehouse_id,
            'goods_no' => $goods['goods_no'],
            'image' => $image,
            'sku_no' => $goods['sku_no'],
            'goods_name' => $goods['goods_name'],
            'warehouse_name' => empty($warehouse_map[$warehouse_id]) ? '' : $warehouse_map[$warehouse_id],
            'supplier' => $supplier,
            'price' => empty($goods['1688_price']) ? $goods['price'] : $goods['1688_price']
        ];
        return $this->render('add-purchase', ['data' => $data]);
    }

}
