<?php
namespace common\services\order;

use common\events\OrderGoodsEvent;
use common\extensions\google\PyTranslate;
use common\extensions\google\Translate;
use common\models\Category;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\Order;
use common\models\OrderDeclare;
use common\models\OrderGoods;
use common\services\goods\GoodsService;
use common\services\sys\CountryService;
use Yii;
use yii\base\Component;
use common\components\statics\Base;
use yii\helpers\ArrayHelper;

class OrderDeclareService extends Component
{

    /**
     * 默认报关信息
     * @param array|mixed $order 订单信息
     * @return array
     * @throws \yii\base\Exception
     */
    public function defaultOrderDeclare($order)
    {
        $order_id = $order['order_id'];
        $order_goods = OrderService::getOrderGoods($order_id);
        $declare_category = [];
        foreach ($order_goods as $v) {
            $goods_child = GoodsChild::find()->where(['cgoods_no'=>$v['cgoods_no']])->asArray()->one();
            $goods = Goods::find()->where(['goods_no'=>$v['goods_no']])->asArray()->one();
            $category_id = $goods['category_id'];
            $category_key = empty($category_id)?md5($goods['source_platform_category_name']):$category_id;
            /*if(isset($declare_category[$category_id])){
                $info = $declare_category[$category_id];
                $info['num'] += $v['goods_num'];
                $declare_category[$category_id] = $info;
            }else {*/
            $weight = $goods_child['real_weight'] >0 ? $goods_child['real_weight']:$goods_child['weight'];
            $weight = $weight <0.1 ? 0.1 :$weight;
            $weight = ceil($weight*10)/10;
                $declare_category[$category_key] = [
                    'category_id' => $category_id,
                    'num' => $v['goods_num'],
                    'weight' => $weight,
                    'order_goods_id' => $v['id'],
                    'goods_income_price' => $v['goods_income_price'],
                    'source_platform_category_name' => $goods['source_platform_category_name'],
                ];
            //}
        }

        if(empty($declare_category)){
            return [];
        }

        $price_1 = 10;
        $price_2 = 15;
        $is_union = CountryService::isEuropeanUnion($order['country']);
        if(!empty($order['tax_number']) && $is_union) {
            $price_1 = 18;
            $price_2 = 20;

            if(!empty($order['tax_relation_no'])) {
                $_order = Order::findOne(['relation_no'=>$order['tax_relation_no']]);
                if(!empty($_order) && $_order['order_income_price'] < 20) {
                    $price_1 = $_order['order_income_price'] - 3;
                    $price_2 = $_order['order_income_price'] - 2;
                }
            }
        }

        if(empty($order['tax_number']) && $is_union) {
            $price_1 = 5;
            $price_2 = 6;
        }

        //ozon申报金额
        if ($order['source'] == Base::PLATFORM_OZON) {
            $price_1 = 70;
            $price_2 = 75;
        }

        //b2w
        if ($order['source'] == Base::PLATFORM_B2W) {
            //$price_1 = 22;
            //$price_2 = 24;
            $price_1 = 40;
            $price_2 = 40;
        }

        if ($order['source'] == Base::PLATFORM_COUPANG && $order['order_income_price'] > 100000) {
            $price_1 = 50;
            $price_2 = 50;
        }

        $price = 0;
        if(count($declare_category) > 1) {
            $price = $price_2 / count($declare_category);
            $price = floor($price);
            $price = $price < 1?1:$price;
        } else {
            $price = current($declare_category)['num'] >1?$price_2:$price_1;
        }

        //是否使用原始申报金额
        $use_order_price = false;
        if(!empty($order['tax_number']) && empty($order['tax_relation_no']) && $is_union) {
            if($order['order_income_price'] > 50){
                if(count($declare_category) > 1) {
                    $price = 50 / count($declare_category);
                    $price = floor($price);
                    $price = $price < 1?1:$price;
                } else {
                    $price = 50;
                }
            } else {
                $use_order_price = true;
            }
        }

        $declare = [];
        foreach ($declare_category as $v) {
            $weight = empty($v['weight']) || $v['weight'] <= 0?0.1:$v['weight'];
            $info = [];
            $info['order_goods_id'] = $v['order_goods_id'];
            $category_name = '';
            if(!empty($v['category_id'])){
                $category = Category::find()->where(['id'=>$v['category_id']])->asArray()->one();
                $category_name = $category['name'];
                $info['declare_name_cn'] = $category_name;
                //$info['declare_name_en'] = Translate::exec($category_name,'en');
                $category_arr = explode('&',$category['name_en']);
                $category_arr = end($category_arr);
                $info['declare_name_en'] = $category_arr;
                if($order['source'] == Base::PLATFORM_B2W && !empty($category['hs_code'])) {
                    $info['declare_customs_code'] = $category['hs_code'];
                }
            }else{
                $category_arr = explode('>',$v['source_platform_category_name']);
                $category_arr = end($category_arr);
                $category_arr = explode('&',$category_arr);
                $category_arr = end($category_arr);
                $category_name = $category_arr;
                try {
                    $info['declare_name_cn'] = PyTranslate::exec($category_name, 'zh-CN');
                }catch (\Exception $e){
                    $info['declare_name_cn'] = '';
                }
                $info['declare_name_en'] = $category_name;
            }

            if($info['declare_name_cn'] == '手机') {
                $info['declare_name_cn'] = '联络设备';
                $info['declare_name_en'] = 'Contact equipment';
            }

            if($info['declare_name_cn'] == '电源逆变器') {
                $info['declare_name_cn'] = '逆变器';
                $info['declare_name_en'] = 'Inverters';
            }

            $info['declare_price'] = $use_order_price?($v['goods_income_price'] * $v['num']):$price;
            $info['declare_weight'] = (float)$weight * $v['num'];
            $info['declare_num'] = $v['num'];
            /**
            `declare_name_cn` varchar(255) NOT NULL DEFAULT '' COMMENT '报关中文名称',
            `declare_name_en` varchar(255) NOT NULL DEFAULT '' COMMENT '报关英文名称',
            `declare_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '申报金额',
            `declare_weight` int(11) NOT NULL DEFAULT '0' COMMENT '申报重量',
            `declare_num` int(11) NOT NULL DEFAULT '0' COMMENT '申报数量',
            `declare_material` varchar(100) NOT NULL DEFAULT '' COMMENT '材质',
            `declare_purpose` varchar(100) NOT NULL DEFAULT '' COMMENT '用途',
            `declare_customs_code` varchar(50) NOT NULL DEFAULT '' COMMENT '海关编码',
            `declare_attribute` varchar(200) NOT NULL DEFAULT '' COMMENT '报关属性',
             */
            $declare[] = $info;
        }
        return $declare;
    }

    /**
     * 更新订单报价
     * @param $order_id
     * @param array $order_declare
     * @return bool
     * @throws \yii\base\Exception
     */
    public function updateOrderDeclare($order_id, $order_declare = [])
    {
        $order = OrderService::getOneByOrderId($order_id);
        //未确认和不是自建的单不需要更新报价
        if($order['source_method'] == GoodsService::SOURCE_METHOD_AMAZON) {
            return true;
        }

        $old_order_declare = OrderService::getOrderDeclare($order_id);
        //初始化
        if(empty($old_order_declare) && empty($order_declare)) {
            $default_declare = self::defaultOrderDeclare($order);
            foreach ($default_declare as $v) {
                $v['order_id'] = $order_id;
                OrderDeclare::add($v);
            }
            return true;
        }

        if(empty($order_declare)){
            return false;
        }

        $old_ids = ArrayHelper::getColumn($old_order_declare, 'id');
        //$old_order_declare = ArrayHelper::index($old_order_declare, 'id');

        $new_ids = ArrayHelper::getColumn($order_declare, 'id');
        $new_ids = array_filter($new_ids);

        $del_ids = array_diff($old_ids, $new_ids);
        if(!empty($del_ids)) {
            OrderDeclare::deleteAll(['id' => $del_ids]);
        }

        foreach ($order_declare as $declare_v) {
            $declare_id = $declare_v['id'];
            $declare_v['order_id'] = $order_id;
            if (empty($declare_id)) {
                //添加
                $id = OrderDeclare::add($declare_v);
            } else {
                //修改
                OrderDeclare::updateOneById(['id'=>$declare_id],$declare_v);
            }
        }
    }

}
