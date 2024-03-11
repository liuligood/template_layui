<?php
/**
 * 货架
 * User: chenweihao
 * Date: 2022/1/22
 * Time: 11:38
 */
namespace common\services\warehousing;

use common\components\CommonUtil;
use common\models\goods\GoodsStock;
use common\models\warehousing\Shelves;
use common\services\goods\GoodsService;

class ShelvesService
{

    /**
     * 获取闲置货架
     * @return mixed
     */
    public function getIdleShelves()
    {
        return false;
        $shelves = Shelves::find()->where(['status' => Shelves::STATUS_DEFAULT])->asArray()->one();
        return empty($shelves['shelves_no'])?false:$shelves['shelves_no'];
    }

    /**
     * 关联商品
     * @param $cgoods_no
     */
    public function relatedGoods($cgoods_no)
    {
        $goods_stock = GoodsStock::find()->where(['warehouse' => WarehouseService::WAREHOUSE_OWN, 'cgoods_no' => $cgoods_no])->one();
        if ($goods_stock['num'] > 0) {
            if (empty($goods_stock['shelves_no'])) {
                $shelves_no = $this->getIdleShelves();
                if(empty($shelves_no)) {//没有多余货架
                    return ;
                }
                $goods_stock['shelves_no'] = $shelves_no;
                $goods_stock->save();
                $status = Shelves::STATUS_OCCUPY;
            } else {
                $shelves_no = $goods_stock['shelves_no'];
                $status = Shelves::STATUS_OCCUPY;
            }
        } else {
            if (!empty($goods_stock['shelves_no'])) {
                CommonUtil::logs('清除:' . $cgoods_no .', 货架号：'.$goods_stock['shelves_no'], 'goods_stock_shelves');
                $shelves_no = $goods_stock['shelves_no'];
                $goods_stock['shelves_no'] = '';
                $goods_stock->save();
                $exist = GoodsStock::find()->where(['shelves_no' => $shelves_no])->exists();
                $status = Shelves::STATUS_DEFAULT;
                if ($exist) {
                    $status = Shelves::STATUS_OCCUPY;
                }
            }
        }

        //更新货架状态
        if (!empty($shelves_no) && !empty($status)) {
            $shelves = Shelves::find()->where(['shelves_no' => $shelves_no])->one();
            if ($shelves['status'] != $status) {
                $shelves['status'] = $status;
                $shelves->save();
            }
        }
    }

    /**
     * 更新状态
     * @param $shelves_no
     * @param null $status
     */
    public function updateStatus($shelves_no,$status = null)
    {
        if(empty($shelves_no)){
            return;
        }
        if(is_null($status)) {
            $exist = GoodsStock::find()->where(['shelves_no' => $shelves_no])->exists();
            $status = Shelves::STATUS_DEFAULT;
            if ($exist) {
                $status = Shelves::STATUS_OCCUPY;
            }
        }
        $shelves = Shelves::find()->where(['shelves_no' => $shelves_no])->one();
        if ($shelves['status'] != $status) {
            $shelves['status'] = $status;
            $shelves->save();
        }
    }

}