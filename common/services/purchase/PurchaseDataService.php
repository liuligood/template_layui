<?php
namespace common\services\purchase;

use common\models\purchase\PurchaseOrder;
use Yii;
use yii\base\Component;
use yii\base\Exception;

/**
 * 统一下单数据
 */
class PurchaseDataService extends Component
{
    //基础数据
    public $goods = [];//订单商品(初始化解析前是原始数据，解析后写入格式化数据)
    public $order_info = [];//订单信息
    public $order_options = [];//订单扩展
    public $order_id = '';
    public $op_user_role = '';//下单人角色
    public $op_user_id = '';//下单人ID


    /**
     * 初始化、解析下单数据
     * @return bool
     */
    public function initData()
    {
        //初始化 解析订单商品(ps:如果有秒杀则在解析时减库存)
        $this->initOrderGoods();
        //初始化 解析订单扩展
        $this->initOrderOptions();
        //初始化 订单信息
        $this->initOrderData();

        return true;
    }

    /**
     * 初始化订单商品
     * @return bool
     */
    public function initOrderGoods()
    {
        if(!empty($this->goods)){
            foreach ($this->goods as &$item) {
                $item['source'] = $this->order_info['source'];
            }
        }
        return true;
    }

    /**
     * 初始化订单扩展信息
     * @return bool
     */
    public function initOrderOptions()
    {
        return true;
    }

    /**
     * 初始化订单信息
     * @return bool
     */
    public function initOrderData()
    {
        $this->order_info['order_status'] = PurchaseOrder::ORDER_STATUS_WAIT_SHIP;//订单状态
        if(!empty($this->order_info['track_no'])){
            $this->order_info['order_status'] = PurchaseOrder::ORDER_STATUS_SHIPPED;//订单状态
            if(empty($this->order_info['ship_time'])) {
                $this->order_info['ship_time'] = time();
            }
        }
        if(empty($this->order_info['date'])){
            $this->order_info['date'] = time();
        }
        return true;
    }

    /**
     * 添加订单
     * @return string 订单号
     * @throws Exception
     */
    public function addOrder()
    {
        $this->order_id = PurchaseOrder::addOrder($this->order_info);
        return $this->order_id;
    }

    /**
     * 添加订单商品
     * @return bool
     * @throws Exception
     */
    public function addOrderGoods()
    {
        $goods_service = new PurchaseOrderGoodsService();
        foreach($this->goods as $goods){
            $id = $goods_service->addGoods($this->order_id, $goods);
        }
        return true;
    }

}
