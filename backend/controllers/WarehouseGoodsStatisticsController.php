<?php


namespace backend\controllers;


use common\base\BaseController;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsStock;
use common\models\goods\GoodsStockDetails;
use common\models\GoodsStockFreight;
use common\models\purchase\PurchaseOrder;
use common\models\purchase\PurchaseOrderGoods;
use common\models\warehousing\BlContainer;
use common\models\warehousing\BlContainerGoods;
use common\models\warehousing\OverseasGoodsShipment;
use common\models\warehousing\Warehouse;
use common\models\warehousing\WarehouseProvider;
use common\services\goods\GoodsService;
use common\services\warehousing\WarehouseService;

class WarehouseGoodsStatisticsController extends BaseController
{
    /**
     * @routeName 仓库商品统计
     * @routeDescription 仓库商品统计
     */
    public function actionIndex()
    {
        $req = \Yii::$app->request;
        $warehouse_lists = WarehouseService::getOverseasWarehouse();
        $warehouse_id = $req->get('warehouse_id',WarehouseService::WAREHOUSE_ALLEGRO);
        $own_warehouse = Warehouse::find()->alias('w')
            ->leftJoin(WarehouseProvider::tableName().' wp','wp.id = w.warehouse_provider_id')
            ->select('w.*')->where(['wp.warehouse_provider_type' => WarehouseProvider::TYPE_LOCAL])->asArray()->all();
        $own_warehouse_lists = [];
        foreach ($own_warehouse as $v) {
            $own_warehouse_lists[$v['id']] = $v['warehouse_name'];
        }
        $warehouse_lists = $warehouse_lists + $own_warehouse_lists;
        $warehouse_type = WarehouseService::getWarehouseProviderType($warehouse_id);


        $where['warehouse'] = $warehouse_id;
        $where['status'] = 2;

        $now_time = strtotime(date('Y-m-d',time()));
        $third_time = GoodsService::getBeforeTime('-30');
        $sixty_time = GoodsService::getBeforeTime('-60');
        $ninety_time = GoodsService::getBeforeTime('-90');
        $hundred_eighty_time = GoodsService::getBeforeTime('-180');
        $three_hundred_sixty_time = GoodsService::getBeforeTime('-360');

        //获取时间区间库存统计
        $select[] = GoodsService::combinationSql($third_time,$now_time,'less_thirty','inbound_time');
        $select[] = GoodsService::combinationSql($sixty_time,$third_time,'greater_thirty','inbound_time');
        $select[] = GoodsService::combinationSql($ninety_time,$sixty_time,'greater_sixty','inbound_time');
        $select[] = GoodsService::combinationSql($hundred_eighty_time,$ninety_time,'greater_ninety','inbound_time');
        $select[] = GoodsService::combinationSql($three_hundred_sixty_time,$hundred_eighty_time,'greater_hundred_eighty','inbound_time');
        $select[] = 'sum(case when inbound_time < '.$three_hundred_sixty_time.' then 1 else 0 end) as greater_three_hundred_sixty';

        //获取时间区间商品价格统计
        $select[] = GoodsService::combinationSql($third_time,$now_time,'less_thirty_price','inbound_time','goods_price');
        $select[] = GoodsService::combinationSql($sixty_time,$third_time,'greater_thirty_price','inbound_time','goods_price');
        $select[] = GoodsService::combinationSql($ninety_time,$sixty_time,'greater_sixty_price','inbound_time','goods_price');
        $select[] = GoodsService::combinationSql($hundred_eighty_time,$ninety_time,'greater_ninety_price','inbound_time','goods_price');
        $select[] = GoodsService::combinationSql($three_hundred_sixty_time,$hundred_eighty_time,'greater_hundred_eighty_price','inbound_time','goods_price');
        $select[] = 'sum(case when inbound_time < '.$three_hundred_sixty_time.' then goods_price else 0 end) as greater_three_hundred_sixty_price';

        $stock_freight = [];
        $total_freight = 0;
        if (in_array($warehouse_type,[WarehouseProvider::TYPE_PLATFORM,WarehouseProvider::TYPE_THIRD_PARTY])){
            //获取时间区间运费统计
            $select_freight[] = GoodsService::combinationSql($third_time,$now_time,'less_thirty_freight','gsd.inbound_time','gsf.freight_price');
            $select_freight[] = GoodsService::combinationSql($sixty_time,$third_time,'greater_thirty_freight','gsd.inbound_time','gsf.freight_price');
            $select_freight[] = GoodsService::combinationSql($ninety_time,$sixty_time,'greater_sixty_freight','gsd.inbound_time','gsf.freight_price');
            $select_freight[] = GoodsService::combinationSql($hundred_eighty_time,$ninety_time,'greater_ninety_freight','gsd.inbound_time','gsf.freight_price');
            $select_freight[] = GoodsService::combinationSql($three_hundred_sixty_time,$hundred_eighty_time,'greater_hundred_eighty_freight','gsd.inbound_time','gsf.freight_price');
            $select_freight[] = 'sum(case when gsd.inbound_time < '.$three_hundred_sixty_time.' then gsf.freight_price else 0 end) as greater_three_hundred_sixty_freight';

            $select_freight = implode(',',$select_freight);
            $stock_freight = GoodsStockDetails::find()->alias('gsd')
                ->leftJoin(GoodsStockFreight::tableName().' gsf','gsf.cgoods_no = gsd.cgoods_no and gsf.warehouse_id = gsd.warehouse')
                ->where($where)->andWhere(['=','outgoing_time',0])->select($select_freight)->asArray()->one();
            $total_freight = $stock_freight['less_thirty_freight'] + $stock_freight['greater_thirty_freight'] + $stock_freight['greater_sixty_freight'] + $stock_freight['greater_ninety_freight']
                + $stock_freight['greater_hundred_eighty_freight'] + $stock_freight['greater_three_hundred_sixty_freight'];
        }
        $select = implode(',',$select);

        $stock_details = GoodsStockDetails::find()->where($where)->andWhere(['=','outgoing_time',0])->select($select)->asArray()->one();
        unset($where['status']);

        $stock = GoodsStock::find()->where($where)->select('sum(real_num) as real_num,sum(num) as num')->asArray()->one();

        $transit = BlContainerGoods::find()->alias('blg')
            ->leftJoin(GoodsChild::tableName().' gc','gc.cgoods_no = blg.cgoods_no')
            ->where(['blg.warehouse_id' => $warehouse_id,'blg.status' => BlContainer::STATUS_NOT_DELIVERED])
            ->select('sum(blg.num) as num,sum(gc.price * blg.num) as goods_price,sum(blg.price * blg.num) as freight_price')->asArray()->one();

        $goods_shipment_where = [OverseasGoodsShipment::STATUS_FINISH,OverseasGoodsShipment::STATUS_CANCELLED];
        $purchasing = OverseasGoodsShipment::find()->alias('ogs')
            ->leftJoin(PurchaseOrderGoods::tableName().' pog','pog.order_id = ogs.porder_id and pog.cgoods_no = ogs.cgoods_no')
            ->where(['ogs.warehouse_id' => $warehouse_id])
            ->andWhere(['not in', 'ogs.status', $goods_shipment_where])
            ->select('sum(ogs.num) as purchase_num,sum(pog.goods_price * ogs.num) as goods_price')->asArray()->one();

        $total_price = $stock_details['less_thirty_price'] + $stock_details['greater_thirty_price'] + $stock_details['greater_sixty_price'] + $stock_details['greater_ninety_price']
            + $stock_details['greater_hundred_eighty_price'] + $stock_details['greater_three_hundred_sixty_price'];


        $total['stock'] = $transit['num'] + $purchasing['purchase_num'] + $stock['num'];
        $total['purchasing'] = $transit['goods_price'] + $purchasing['goods_price'] + $total_price;
        $total['freight'] = $transit['freight_price'] + $total_freight;

        return $this->render('index',[
            'warehouse_lists' => $warehouse_lists,
            'stock_details' => $stock_details,
            'stock' => $stock,
            'warehouse_id' => $warehouse_id,
            'transit' => $transit,
            'purchasing' => $purchasing,
            'total_price' => $total_price,
            'warehouse_type' => $warehouse_type,
            'stock_freight' => $stock_freight,
            'total_freight' => $total_freight,
            'total' => $total
        ]);
    }
}