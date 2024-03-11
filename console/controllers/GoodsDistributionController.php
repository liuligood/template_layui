<?php
namespace console\controllers;

use common\components\CommonUtil;
use common\models\Goods;
use common\models\goods\GoodsStock;
use common\models\sys\Exectime;
use common\models\warehousing\Warehouse;
use common\services\goods\GoodsStockService;
use common\services\overseas_api\GIGAB2BService;
use yii\console\Controller;
use yii\helpers\ArrayHelper;

class GoodsDistributionController extends Controller
{

    /**
     * 添加分销商品
     * @param null $warehouse_id
     * @return void
     */
    public function actionAddGoods($warehouse_id = null)
    {
        $where = ['warehouse_provider_id' => 5];
        if (!empty($warehouse_id)) {
            $where['id'] = $warehouse_id;
        }
        $warehouse_lists = Warehouse::find()->where($where)->all();
        foreach ($warehouse_lists as $warehouse_v) {
            if (empty($warehouse_v['api_params'])) {
                continue;
            }
            $warehouse_id = $warehouse_v['id'];
            echo date('Y-m-d H:i:s').' warehouse' . $warehouse_id . "\n";
            $object_type = Exectime::TYPE_GOODS_DISTRIBUTION_ADD;
            $exec_time = Exectime::getTime($object_type, $warehouse_id);
            $date = 0;
            if (!empty($exec_time)) {
                $date = strtotime(date('Y-m-d', $exec_time));
            }
            $exec_time = time();//记录当前执行时间
            $giga = (new GIGAB2BService($warehouse_id));
            $giga->addGoods($date);
            Exectime::setTime($exec_time, $object_type, $warehouse_id);
        }
    }

    /**
     * 更新分销库存
     * @param null $warehouse_id
     * @return void
     */
    public function actionUpdateStock($warehouse_id = null)
    {
        $where = ['warehouse_provider_id' => 5];
        if (!empty($warehouse_id)) {
            $where['id'] = $warehouse_id;
        }
        $warehouse_lists = Warehouse::find()->where($where)->all();
        foreach ($warehouse_lists as $warehouse_v) {
            if (empty($warehouse_v['api_params'])) {
                continue;
            }
            $warehouse_id = $warehouse_v['id'];
            echo 'warehouse' . $warehouse_id . "\n";
            $giga = (new GIGAB2BService($warehouse_id));
            $giga->updateStock();
        }
    }



    /**
     * 修复商品详情
     * @return void
     */
    public function actionUpdateContent()
    {
        $goods_lists = Goods::find()->where(['source_method_sub' => Goods::GOODS_SOURCE_METHOD_SUB_DISTRIBUTION,'goods_content'=>''])->all();
        $sku_goods = ArrayHelper::map($goods_lists, 'source_platform_id', 'goods_no');
        $skus = ArrayHelper::getColumn($goods_lists, 'source_platform_id');
        $page_num = 200;
        $page = 1;
        $giga = (new GIGAB2BService(5));
        do {
            echo $page . "\n";
            $page++;
            $sku = array_slice($skus, 0, $page_num);
            $skus = array_slice($skus, $page_num);
            $detail_lists = $giga->getApi()->getGoodsDetail($sku);
            foreach ($detail_lists as $v) {
                $goods_no = $sku_goods[$v['sku']];
                $goods = Goods::find()->where(['goods_no'=>$goods_no])->one();
                $goods['goods_content'] = CommonUtil::dealContent($v['description']);
                $goods->save();
                echo $goods_no . "\n";
            }
        } while (count($skus) > 0);
    }

}