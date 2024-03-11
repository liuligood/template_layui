<?php
namespace common\services\warehousing;

use common\models\goods\GoodsChild;
use common\models\goods_shop\GoodsShopOverseasWarehouse;
use common\models\warehousing\BlContainer;
use common\models\warehousing\BlContainerGoods;
use common\models\warehousing\BlContainerTransportation;
use common\models\warehousing\OverseasGoodsShipment;
use common\services\goods\GoodsService;
use common\services\goods\GoodsShopService;
use common\services\goods\GoodsStockService;
use yii\base\Exception;
use yii\helpers\ArrayHelper;

class BlContainerService
{

    /**
     * 更新商品
     * @param $bl_id
     * @param array $bl_goods
     * @param boolean $search_ovg
     * @param int $bl_transportation_id
     * @throws Exception
     */
    public static function updateGoods($bl_id, $bl_goods = [], $search_ovg = false,$bl_transportation_id = 0)
    {
        $bl = BlContainer::find()->where(['id'=>$bl_id])->one();
        $old_bl_goods = BlContainerGoods::find()->where(['bl_id' => $bl_id])->asArray()->all();
        $old_cgoods_nos = ArrayHelper::getColumn($old_bl_goods, 'cgoods_no');
        $old_bl_goods = ArrayHelper::index($old_bl_goods, 'cgoods_no');

        $new_cgoods_nos = ArrayHelper::getColumn($bl_goods, 'cgoods_no');
        $new_cgoods_nos = array_filter($new_cgoods_nos);

        //删除的商品id
        $del_cgoods_nos = array_diff($old_cgoods_nos, $new_cgoods_nos);
        if(!empty($del_cgoods_nos)) {
            BlContainerGoods::deleteAll(['cgoods_no' => $del_cgoods_nos,'bl_id' => $bl_id]);
        }

        //价格按重量计算商品价格
        $price = $bl['price'];
        $all_weight = 0;
        $bl_goods_id = '';
        //$cjz_weight = GoodsService::cjzWeight($bl['size'],$bl['cjz'],0);
        //$all_weight = max($cjz_weight, $bl['weight']);
        $goods_childes = GoodsChild::find()->where(['cgoods_no'=>$new_cgoods_nos])->indexBy('cgoods_no')->asArray()->all();
        foreach ($bl_goods as &$goods_v) {
            $goods_child = $goods_childes[$goods_v['cgoods_no']];
            $cjz_weight = GoodsService::cjzWeight($goods_child['package_size'], $bl['cjz'], 0);
            $weight = max($cjz_weight, $goods_child['real_weight'] > 0 ? $goods_child['real_weight'] : $goods_child['weight']);
            $weight = empty($weight)?0.2:$weight;
            $goods_v['weight'] = $weight;
            $all_weight += $weight * $goods_v['num'];
        }
        $avg_price = $price / $all_weight;

        $num = 0;
        $sku_num  = count($bl_goods);
        $index = 0;
        $all_price = 0;
        foreach ($bl_goods as &$goods_v) {
            $index++;
            if($index == $sku_num) {
                $goods_v['price'] = round(($price - $all_price)/$goods_v['num'],2);
            } else {
                $tmp_price = round($goods_v['weight'] * $avg_price,2);
                $goods_v['price'] = $tmp_price;
                $all_price += $tmp_price * $goods_v['num'];
            }
            unset($goods_v['weight']);
            $goods_v['bl_id'] = $bl_id;
            $goods_v['warehouse_id'] = $bl['warehouse_id'];
            $goods_v['status'] = BlContainer::STATUS_NOT_DELIVERED;
            $num += $goods_v['num'];
            if (empty($old_bl_goods[$goods_v['cgoods_no']])) {
                //添加商品
                $bl_goods_id = BlContainerGoods::add($goods_v);
            } else {
                if ($search_ovg) {
                    $bl_goods_id = $old_bl_goods[$goods_v['cgoods_no']]['id'];
                    $old_num = $old_bl_goods[$goods_v['cgoods_no']]['num'];
                    $new_num = $goods_v['num'];
                    BlContainerService::updateOverseasGoodsShipment($bl_goods_id,$old_num,$new_num,$bl['bl_transportation_id']);
                }
                //修改商品
                BlContainerGoods::updateOneByCond(['bl_id'=>$bl_id,'cgoods_no'=>$goods_v['cgoods_no']],$goods_v);
            }
        }

        if($bl['goods_count'] != $num){
            $bl->goods_count = $num;
            $bl->save();
        }

        if ($bl_transportation_id != 0) {
            $transportation = BlContainerTransportation::findOne($bl_transportation_id);
            BlContainerService::updateBlGoodsPrice($transportation);
        }
        return $bl_goods_id;
    }

    /**
     * 商品到货
     * @param $bl_container_id
     * @param $arrival_goods
     * @return bool
     * @throws Exception
     * @throws \yii\db\Exception
     */
    public function receivedGoods($bl_container_id,$arrival_goods)
    {
        $bl_container = BlContainer::findOne($bl_container_id);
        if (!in_array($bl_container['status'], [BlContainer::STATUS_NOT_DELIVERED, BlContainer::STATUS_PARTIAL_DELIVERED])) {
            return false;
        }

        $transaction = \Yii::$app->db->beginTransaction();
        try {
            $bl_container_goods = BlContainerGoods::find()->where(['bl_id' => $bl_container_id])->all();
            $all_arrival = true;
            foreach ($bl_container_goods as $goods_v) {
                $arrival_num = empty($arrival_goods[$goods_v['id']]) ? 0 : (int)$arrival_goods[$goods_v['id']];
                $goods_num = $goods_v['num'];
                $goods_finish_num = $goods_v['finish_num'];
                if ($goods_num == $goods_finish_num) {
                    continue;
                }

                if ($arrival_num <= 0) {
                    $all_arrival = false;
                    continue;
                }

                if (empty($goods_v['cgoods_no'])) {
                    throw new \Exception('商品数据异常');
                }

                if ($goods_num - $goods_finish_num < $arrival_num) {
                    throw new \Exception('到货数量不正确');
                }

                //部分到货
                if ($goods_num - $goods_finish_num > $arrival_num) {
                    $all_arrival = false;
                }
                $goods_v['finish_num'] = $goods_finish_num + $arrival_num;
                $goods_v['status'] = BlContainer::STATUS_DELIVERED;
                $goods_v->save();

                //先入库
                GoodsStockService::changeStock($goods_v['cgoods_no'], $bl_container['warehouse_id'], GoodsStockService::TYPE_Bl_WAREHOUSING, $arrival_num, $bl_container_id, '提单箱到货');
                GoodsShopService::updateGoodsStock($bl_container['warehouse_id'], $goods_v['cgoods_no']);//更新库存
            }
            $bl_container['status'] = BlContainer::STATUS_DELIVERED;
            if (!$all_arrival) {
                $bl_container['status'] = BlContainer::STATUS_PARTIAL_DELIVERED;//部分到货
            }
            if ($bl_container->save()) {
                //这里需要做库存变动操作
                $transaction->commit();
                return true;
            } else {
                $transaction->rollBack();
                return false;
            }
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new Exception($e->getMessage(), $e->getCode());
        }

        return false;
    }

    /**
     * 更新提单箱商品价格
     * @param $transportation
     * @param int $bl_id
     * @return boolean
     */
    public static function updateBlGoodsPrice($transportation,$bl_id = 0)
    {
        if ($bl_id != 0) {
            $bl_container_goods = BlContainerGoods::find()->where(['bl_id' => $bl_id])->all();
            foreach ($bl_container_goods as $v) {
                $v['price'] = 0;
                $v->save();
            }
            return true;
        }
        $bl_container = BlContainer::find()->where(['bl_transportation_id' => $transportation['id']])->asArray()->all();
        foreach ($bl_container as $model) {
            $average_price = $transportation['price'] / $transportation['estimate_weight'];
            $cjz = GoodsService::cjzWeight($model['size'],$transportation['cjz']);
            $price = $cjz > $model['weight'] ? $average_price * $cjz : $average_price * $model['weight'];

            $bl_goods = BlContainerGoods::find()->where(['bl_id' => $model['id']])->asArray()->all();
            $cgoods_no = ArrayHelper::getColumn($bl_goods, 'cgoods_no');
            $all_weight = 0;
            $goods_childes = GoodsChild::find()->where(['cgoods_no'=>$cgoods_no])->indexBy('cgoods_no')->asArray()->all();
            foreach ($bl_goods as &$goods_v) {
                $goods_child = $goods_childes[$goods_v['cgoods_no']];
                $cjz_weight = GoodsService::cjzWeight($goods_child['package_size'], $transportation['cjz']);
                $weight = max($cjz_weight, $goods_child['real_weight'] > 0 ? $goods_child['real_weight'] : $goods_child['weight']);
                $weight = empty($weight)?0.2:$weight;
                $goods_v['weight'] = $weight;
                $all_weight += $weight * $goods_v['num'];
            }
            $avg_price = $price / $all_weight;
            $sku_num  = count($bl_goods);
            $index = 0;
            $all_price = 0;
            foreach ($bl_goods as &$goods_v) {
                $index++;
                if($index == $sku_num) {
                    $goods_v['price'] = round(($price - $all_price) / $goods_v['num'],2);
                } else {
                    $tmp_price = round($goods_v['weight'] * $avg_price,2);
                    $goods_v['price'] = $tmp_price;
                    $all_price += $tmp_price * $goods_v['num'];
                }
                unset($goods_v['weight']);
                BlContainerGoods::updateOneByCond(['bl_id'=>$model['id'],'id'=>$goods_v['id']],$goods_v);
            }
        }
    }

    /**
     * 更新海外发货列表数量
     * @param $bl_goods_id
     * @param $old_num
     * @param $new_num
     * @param $bl_transportation_id
     * @return boolean
     */
    public static function updateOverseasGoodsShipment($bl_goods_id,$old_num,$new_num,$bl_transportation_id = 0)
    {
        $nums = 0;
        $cgoods_arr = [];
        if ($old_num != $new_num) {
            $nums = $old_num - $new_num;
        }
        if ($nums != 0) {
            $transportation = BlContainerTransportation::findOne($bl_transportation_id);
            if (!empty($transportation)) {
                $transportation['goods_count'] = $transportation['goods_count'] - $nums;
                $transportation->save();
            }
        }
        $residue_num = $nums;
        $goods_shipment = OverseasGoodsShipment::find()->where(['bl_container_goods_id' => $bl_goods_id,'status' => OverseasGoodsShipment::STATUS_FINISH])->all();
        if (!empty($goods_shipment)) {
            foreach ($goods_shipment as $value) {
                if ($value['num'] - $residue_num < 0 || $value['num'] - $residue_num == 0) {
                    $residue_num = $residue_num - $value['num'];
                    $value->delete();
                } else {
                    $value['num'] = $value['num'] - $residue_num;
                    $value->save();
                }
                if ($nums != 0) {
                    if (in_array($value['cgoods_no'],$cgoods_arr)) {
                        continue;
                    }
                    $wait_packed = OverseasGoodsShipment::find()
                        ->where(['porder_id' => $value['porder_id'],'cgoods_no' => $value['cgoods_no'],'status' => OverseasGoodsShipment::STATUS_WAIT_PACKED])
                        ->one();
                    if (empty($wait_packed)) {
                        $wait_packed = new OverseasGoodsShipment();
                        $wait_packed['num'] = $nums;
                        $wait_packed['status'] = OverseasGoodsShipment::STATUS_WAIT_PACKED;
                        $wait_packed['supplier_id'] = $value['supplier_id'];
                        $wait_packed['cgoods_no'] = $value['cgoods_no'];
                        $wait_packed['warehouse_id'] = $value['warehouse_id'];
                        $wait_packed['porder_id'] = $value['porder_id'];
                        $wait_packed['purchase_time'] = $value['purchase_time'];
                        $wait_packed['arrival_time'] = $value['arrival_time'];
                    } else {
                        $wait_packed['num'] = $wait_packed['num'] + $nums;
                    }
                    $wait_packed->save();
                    $cgoods_arr[] = $value['cgoods_no'];
                }
            }
        }
        return true;
    }

}