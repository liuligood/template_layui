<?php
namespace console\controllers;

use common\components\statics\Base;
use common\models\Goods;
use common\models\GoodsShop;
use common\models\GoodsEvent;
use common\models\GoodsShopExpand;
use common\services\goods\GoodsService;
use yii\console\Controller;
use yii\db\Query;

class HistoryController extends Controller
{
    public $count = 0;

    /**
     * 亚马逊商品迁移
     */
    public function actionMigrateAmazonGoods()
    {
        $where = [
            'and',
            ['source_method' => GoodsService::SOURCE_METHOD_AMAZON],
        ];
        $date_time = Goods::find()->select('add_time')->where($where)->limit(1)->orderBy('add_time asc')->scalar();
        $exec_time = time();
        for ($i = 0; $date_time < $exec_time; $i++) {
            $where = [
                'and',
                ['source_method' => GoodsService::SOURCE_METHOD_AMAZON],
                ['<=', 'add_time', $date_time]
            ];
            $count = 0;
            $goods_exist = Goods::find()->select('id')->where($where)->limit(1)->scalar();
            if($goods_exist) {
                $query = (new Query())->from(Goods::getDbName() . '.' . Goods::tableName())->select((new Goods())->attributes())->where($where);
                $count = \Yii::$app->db->createCommand()->ignoreInsert('ys_goods_2', $query)->execute();
                Goods::deleteAll($where);
            }
            echo date('Y-m-d H:i:s', $date_time) . '执行' . $count . "条\n";
            $date_time += 24* 60* 60;
        }

    }

    /**
     * 店铺商品迁移
     */
    public function actionMigrateGoodsShop()
    {
        $count = 0;
        while (true) {
            $where = [
                'platform_type' => Base::PLATFORM_OZON,
                'status' => GoodsShop::STATUS_OFF_SHELF
            ];
            $ids = GoodsShop::find()->where($where)->select('id')->limit(2000)->column();
            if (empty($ids)) {
                break;
            }

            $this->delGoodsShop($ids);
        }
    }


    /**
     * 只保留重复两条的下架数据
     */
    public function actionMigrateGoodsShop1()
    {
        $where = [
            'platform_type' => Base::PLATFORM_OZON,
            'status' => GoodsShop::STATUS_OFF_SHELF
        ];
        $cgoods_nos = GoodsShop::find()->where($where)->select('cgoods_no')->groupBy('cgoods_no')->column();
        if (empty($cgoods_nos)) {
            return ;
        }

        $ids = [];
        foreach ($cgoods_nos as $cgoods_no) {
            $where['cgoods_no'] = $cgoods_no;
            $goods_ids = GoodsShop::find()->where($where)->select('id')->orderBy('add_time asc')->column();
            $i = 0;
            foreach ($goods_ids as $v) {
                $i++;
                if ($i <= 2) {
                    continue;
                }
                $ids[] = $v;
                if (count($ids) >= 1000) {
                    $this->delGoodsShop($ids);
                    $ids = [];
                }
            }
        }

        if (!empty($ids)) {
            $this->delGoodsShop($ids);
        }
        exit;
    }

    public function delGoodsShop($ids)
    {
        $where = ['id' => $ids, 'platform_type' => Base::PLATFORM_OZON];
        $query = (new Query())->from(GoodsShop::getDbName() . '.' . GoodsShop::tableName())->select((new GoodsShop())->attributes())->where($where);
        $this->count += \Yii::$app->db_history->createCommand()->ignoreInsert('ys_goods_shop', $query)->execute();
        GoodsShop::deleteAll($where);

        $where = ['goods_shop_id' => $ids];
        $query = (new Query())->from(GoodsShopExpand::getDbName() . '.' . GoodsShopExpand::tableName())->select((new GoodsShopExpand())->attributes())->where($where);
        \Yii::$app->db_history->createCommand()->ignoreInsert('ys_goods_shop_expand', $query)->execute();
        GoodsShopExpand::deleteAll($where);
        echo '执行' . $this->count . "条\n";
    }

    /**
     * 清除商品事件
     */
    public function actionGoodsEvent()
    {
        $date_time = GoodsEvent::find()->select('update_time')->where(['status' => 20])->limit(1)->orderBy('update_time asc')->scalar();
        $exec_time = strtotime('-1 day');
        for ($i = 0; $date_time < $exec_time; $i++) {
            $date_time += 24* 60* 60;
            $where = [
                'and',
                ['status' => 20],
                ['<=', 'update_time', $date_time]
            ];
            $query = (new Query)->from(GoodsEvent::getDbName().'.'.GoodsEvent::tableName())->select((new GoodsEvent)->attributes())->where($where);
            $count = \Yii::$app->db_history->createCommand()->ignoreInsert(GoodsEvent::tableName(), $query)->execute();
            GoodsEvent::deleteAll($where);
            echo date('Y-m-d H:i:s',$date_time).'执行'.$count."条\n";
        }
    }

    /**
     * 清除未认领商品
     * @throws \yii\base\Exception
     */
    public function actionClearUnclaimedGoods()
    {
        $where = [
            'and',
            ['source_method' => 1],
            ['in', 'status', [Goods::GOODS_STATUS_WAIT_MATCH]],
            ['not in', 'goods_no', GoodsShop::find()->select('goods_no')],
        ];
        $date_time = Goods::find()->select('add_time')->where($where)->limit(1)->orderBy('add_time asc')->scalar();
        $exec_time = time();
        for ($i = 0; $date_time < $exec_time; $i++) {
            $where = [
                'and',
                ['source_method' => 1],
                ['in', 'status', [Goods::GOODS_STATUS_WAIT_MATCH]],
                ['not in', 'goods_no', (new Query())->from(GoodsShop::getDbName().'.'.GoodsShop::tableName())->select('goods_no')],
                ['<=', 'add_time', $date_time]
            ];
            $query = (new Query())->from(Goods::getDbName().'.'.Goods::tableName())->select((new Goods())->attributes())->where($where);
            $count = \Yii::$app->db_history->createCommand()->ignoreInsert(Goods::tableName(), $query)->execute();
            Goods::deleteAll($where);
            echo date('Y-m-d H:i:s',$date_time).'执行'.$count."条\n";
            $date_time += 24* 60* 60;
        }
    }
}