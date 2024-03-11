<?php
namespace common\services\goods;

use common\models\goods\GoodsLock;

class GoodsLockService
{

    /**
     * 是否存在锁
     * @param $goods_no
     * @return bool
     */
    public static function existLockPrice($goods_no)
    {
        $goods_lock = GoodsLock::find()->where(['goods_no' => $goods_no, 'lock_type' => GoodsLock::LOCK_TYPE_PRICE])->limit(1)->one();
        return !empty($goods_lock);
    }

    /**
     * 锁定价格
     * @param $goods_no
     * @return bool
     */
    public static function lockPrice($goods_no)
    {
        if(self::existLockPrice($goods_no)){
            return true;
        }
        $goods_lock = new GoodsLock();
        $goods_lock['goods_no'] = $goods_no;
        $goods_lock['lock_type'] = GoodsLock::LOCK_TYPE_PRICE;
        return $goods_lock->save();
    }

    /**
     * 解锁价格
     * @param $goods_no
     * @return bool
     */
    public static function unlockPrice($goods_no)
    {
        if (!self::existLockPrice($goods_no)) {
            return true;
        }
        return GoodsLock::deleteAll(['goods_no' => $goods_no, 'lock_type' => GoodsLock::LOCK_TYPE_PRICE]);
    }

}