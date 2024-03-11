<?php
namespace common\services\api;
use common\components\CommonUtil;

/**
 * Class BaseApiService
 * @package common\services\api
 */
class BaseApiService
{

    //客户端
    protected $client = null;
    public $client_key = '';
    public $secret_key = '';
    public $param = '';
    public $shop = [];
    const FREQUENCY_LIMIT_KEY = 'com::api::frequency::';
    public $frequency_limit_timer = [];
    public $platform_type;
    public $goods_event;

    public function __construct($shop)
    {
        $client_key = $shop['client_key'];
        $secret_key = $shop['secret_key'];
        $param = $shop['param'];

        $this->client_key = $client_key;
        $this->secret_key = $secret_key;
        $this->param = $param;
        $this->shop = $shop;
        $this->platform_type = $shop['platform_type'];
    }

    /**
     * 设置商品事件
     * @param $goods_event
     * @return void
     */
    public function setGoodsEvent($goods_event)
    {
        $this->goods_event = $goods_event;
        return $this;
    }

    /**
     * 获取客户端
     * @return array|false
     */
    public function getClient(){
        return false;
    }

    /**
     * 获取订单
     * @param $add_time
     * @param $end_time
     * @return array|false
     */
    public function getOrderLists($add_time,$end_time = null)
    {
        return false;
    }

    /**
     * 处理订单
     * @param $order
     * @return array|false
     */
    public function dealOrder($order)
    {
        return false;
    }

    /**
     * 处理订单商品
     * @param $goods_map
     * @return array
     */
    public function dealOrderGoods($goods_map){
        return [
            'source_method' => $goods_map['source_method'],
            'platform_type' => $goods_map['platform_type'],
            'goods_pic' => empty($goods_map['buy_goods_pic'])?'':$goods_map['buy_goods_pic'],
            'goods_name' => empty($goods_map['goods_name'])?'':$goods_map['goods_name'],
            'goods_specification' => '',
            'goods_cost_price' => 0,
            'has_buy_goods' => 0,
            'platform_asin' => $goods_map['platform_sku_no'],
            'goods_no' => $goods_map['goods_no'],
            'cgoods_no' => $goods_map['cgoods_no'],
        ];
    }

    public function frequencyLimitKey($type)
    {
        $shop = $this->shop;
        return self::FREQUENCY_LIMIT_KEY.'_'.$type.'_'.$shop['id'];
    }

    /**
     * 获取当前限制
     * @param $action
     * @return false|mixed
     */
    public function getFrequencyLimitTimer($action)
    {
        if (!empty($this->frequency_limit_timer['shop']) && !empty($this->frequency_limit_timer['shop'][$this->shop['id']]) && !empty($this->frequency_limit_timer['shop'][$this->shop['id']][$action])) {
            return $this->frequency_limit_timer['shop'][$this->shop['id']][$action];
        }

        if (!empty($this->frequency_limit_timer[$action])) {
            return $this->frequency_limit_timer[$action];
        }

        return false;
    }

    /**
     * 验证限制
     * @param $action
     * @return array
     */
    public function checkFrequencyLimit($action)
    {
        $frequency_limit_timer = $this->getFrequencyLimitTimer($action);
        if (empty($frequency_limit_timer)) {
            return [false, 0];
        }

        list ($limit, $window, $date) = $frequency_limit_timer;
        $key = $this->frequencyLimitKey($action);
        $redis = \Yii::$app->redis;
        $key_res = $redis->exists($key);
        if ($key_res) {
            $redis->incrby($key, 1);
            $nums = $redis->get($key);
            CommonUtil::logs('shop_id:' . $this->shop['id'] .' num:'.$nums, 'frequency_limit');
            //key的值超过了请求次数
            if ($nums > $limit) {
                $expire = $this->getExpireTime($date);
                $expire = $expire > 0 ? $expire : $window;
                $ttl = $redis->ttl($key);
                if ($ttl > $expire + 10 || $ttl < 0) {//修复过期时间异常
                    $redis->expire($key, $expire);
                }
                CommonUtil::logs('limit shop_id:' . $this->shop['id'] .' ex:'. $ttl, 'frequency_limit');
                return [true, $window];
            }
        } else {
            $expire = $this->getExpireTime($date);
            $expire = $expire > 0 ? $expire : $window;
            $redis->setex($key, $expire, 1);
        }
        return [false, 0];
    }

    /**
     * 限制减一
     * @param $action
     * @return array|bool
     */
    public function decrFrequencyLimit($action){
        $frequency_limit_timer = $this->getFrequencyLimitTimer($action);
        if (empty($frequency_limit_timer)) {
            return [false, 0];
        }
        $key = $this->frequencyLimitKey($action);
        $redis = \Yii::$app->redis;
        $key_res = $redis->exists($key);
        if ($key_res) {
            $redis->decrby($key,1);
            $nums = $redis->get($key);
            CommonUtil::logs('decr shop_id:' . $this->shop['id'] .' num:'.$nums, 'frequency_limit');
        }
        return true;
    }

    public function getExpireTime($type){
        $expire = 0;
        if($type == 'd'){
            $expire = 86400 - time() - strtotime(date('Y-m-d'));
        }
        if($type == 'H'){
            $expire = 60*60 - time() - strtotime(date('Y-m-d H:00'));
        }
        return $expire;
    }

    /**
     * 更新库存
     * @param $goods
     * @param $stock
     * @param null $price
     * @return bool
     */
    public function updateStock($goods,$stock,$price = null)
    {
        return false;
    }

    /**
     * 发货
     * @param string $order_id 订单号
     * @param string $carrier_code 发货物流公司
     * @param string $tracking_number 物流单号
     * @param string $arrival_time 预计到货时间
     * @param string $tracking_url 物流跟踪链接
     * @return string
     */
    public function getOrderSend($order_id, $carrier_code, $tracking_number, $arrival_time = null, $tracking_url = null)
    {
        return false;
    }

    /**
     * 获取更新订单列表
     * @param $update_time
     * @return string|array
     */
    public function getCancelOrderLists($update_time)
    {
        $cancel_order = [];
        $offset = 0;
        $limit = 100;
        while (true) {
            $order_lists = $this->getUpdateOrderLists($update_time, $offset, $limit);
            if(empty($order_lists)) {
                break;
            }

            $count = 0;
            foreach ($order_lists as $order) {
                if(empty($order)){
                    continue;
                }
                $count ++;

                $result = $this->dealCancelOrder($order);
                if($result) {
                    $cancel_order[] = $result;
                }
            }

            $offset += $limit;
            if($count < $limit){
                break;
            }
        }
        return $cancel_order;
    }

    /**
     * 获取更新订单列表
     * @param $update_time
     * @param $offset
     * @param int $limit
     * @return array
     * @throws Exception
     */
    public function getUpdateOrderLists($update_time, $offset = 0 , $limit = 100)
    {
        return [];
    }

    /**
     * 处理取消订单
     * @param $order
     * @return array|bool
     */
    public function dealCancelOrder($order)
    {
        return false;
    }

    /**
     * 处理商品内容
     * @param $info
     * @param bool $html
     * @return string
     */
    protected function dealGoodsContent($info,$html = false)
    {
        $goods_content = $info['goods_name'].PHP_EOL.$info['goods_content'];
        if($html) {
            $goods_content = CommonUtil::dealP($goods_content);
        }
        return $goods_content;
    }

}