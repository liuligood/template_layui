<?php

namespace common\services\goods;

use common\models\FindGoods;

class FindGoodsService
{

    /**
     * 加入精品
     * @param $lists
     */
    public function addOverseas($lists)
    {
        $data = [];
        foreach ($lists as $platform_type => $goods_nos) {
            foreach ($goods_nos as $goods_no) {
                $normal = false;
                $platform_type = $platform_type == 'find' ? 0 : $platform_type;
                $exists = FindGoods::find()->where(['goods_no' => $goods_no,'platform_type' => $platform_type])->exists();
                if ($exists) {
                    continue;
                }
                if ($platform_type != 0) {
                    $normal = FindGoods::find()->where(['goods_no' => $goods_no,'overseas_goods_status' => FindGoods::FIND_GOODS_STATUS_NORMAL])->exists();
                }
                $data[] = [
                    'platform_type' => $platform_type,
                    'goods_no' => $goods_no,
                    'overseas_goods_status' => $normal == false ? FindGoods::FIND_GOODS_STATUS_UNTREATED : FindGoods::FIND_GOODS_STATUS_NORMAL,
                    'admin_id' => (int)\Yii::$app->user->id,
                    'add_time' => time(),
                    'update_time' => time(),
                ];
            }
        }
        $add_columns = [
            'platform_type',
            'goods_no',
            'overseas_goods_status',
            'admin_id',
            'add_time',
            'update_time',
        ];
        FindGoods::getDb()->createCommand()->batchIgnoreInsert(FindGoods::tableName(), $add_columns, $data)->execute();
    }
    /**
     * 海外直接加入精品
     * @param $goods_nos
     */
    public function addOverseass($goods_nos)
    {
        $data = [];
        foreach ($goods_nos as $goods_no) {
            $data[] = [
                'goods_no' => $goods_no,
                'overseas_goods_status' => FindGoods::FIND_GOODS_STATUS_NORMAL,
                'admin_id' => (int)\Yii::$app->user->id,
                'add_time' => time(),
                'update_time' => time(),
            ];
        }
        $add_columns = [
            'goods_no',
            'overseas_goods_status',
            'admin_id',
            'add_time',
            'update_time',
        ];
        FindGoods::getDb()->createCommand()->batchIgnoreInsert(FindGoods::tableName(), $add_columns, $data)->execute();
    }

}