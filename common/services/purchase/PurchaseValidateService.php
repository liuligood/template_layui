<?php
namespace common\services\purchase;

use Yii;
use yii\base\Exception;

/**
 * 统一下单 数据验证类
 */
class PurchaseValidateService extends PurchaseDataService
{

    /**
     * 验证下单数据是否合法
     * @param array $goods
     * @param array $order_info
     * @param array $order_options
     * @return bool
     * @throws Exception
     */
    public function validateData($goods=[], $order_info=[], $order_options=[])
    {
        /**
         * 下单通用验证
         */
        //验证订单信息
        $this->validOrderInfo($order_info);
        //验证订单商品
        $this->validGoods($goods,$order_options);
        //验证订单扩展信息
        $this->validOrderOptions($order_options);


        //下单验证通过
        return true;
    }

    /**
     * 验证订单信息
     * @param $order_info
     * @return bool
     * @throws Exception
     */
    public function validOrderInfo($order_info)
    {

        if(empty($order_info) || !is_array($order_info)){
            throw new Exception('订单信息为空，下单失败！请核实');
        }
        $check_keys = [
            'source', 'create_way', 'relation_no', 'remarks',
        ];
        foreach($check_keys as $item_key){
            if(!isset($order_info[$item_key])) {
                throw new Exception('订单信息必须字段「' . $item_key . '」不存在！请核实');
            }
        }

        $this->order_info = $order_info;

        return true;
    }

    /**
     * 验证订单商品
     * @return bool
     * @throws Exception
     */
    public function validGoods($goods,$order_options)
    {
        if(empty($goods) || !is_array($goods)){
            throw new Exception('订单商品不能为空，请核实');
        }

        foreach ($goods as &$item) {
            $item_check = ['goods_name', 'goods_num', 'goods_no', 'sku_no'];
            foreach ($item_check as $item_key) {
                if (!isset($item[$item_key])) {
                    throw new Exception('商品必须字段「' . $item_key . '」不存在！请核实');
                } elseif ($item_key == 'goods_num' && intval($item[$item_key]) < 1) {
                    throw new Exception('商品数量不能小于1！请核实');
                }
            }
        }

        $this->goods = $goods;
        return true;
    }

    /**
     * 验证订单扩展信息
     * @param $order_options
     * @return bool
     */
    public function validOrderOptions($order_options)
    {
        $this->order_options = $order_options;
        if(!empty($order_options['op_user_role'])){
            $this->op_user_role = $order_options['op_user_role'];
        }
        if(!empty($order_options['op_user_id'])){
            $this->op_user_id = $order_options['op_user_id'];
        }

        return true;
    }


}
