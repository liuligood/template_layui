<?php
namespace common\services\order;

use common\components\statics\Base;
use common\models\ExchangeRate;
use common\models\goods\GoodsStock;
use common\models\Order;
use common\models\Shop;
use common\services\FApiService;
use common\services\goods\GoodsService;
use common\services\sys\CountryService;
use common\services\sys\ExchangeRateService;
use common\services\warehousing\WarehouseService;
use Yii;
use yii\base\Component;
use yii\base\Exception;

/**
 * 统一下单数据
 */
class DataService extends Component
{
    //基础数据
    public $goods = [];//订单商品(初始化解析前是原始数据，解析后写入格式化数据)
    public $order_info = [];//订单信息
    public $order_options = [];//订单扩展
    public $order_id = '';
    public $goods_income_total = 0;//商品销售价 总收入
    public $goods_cost_total = 0;//商品销售价 总成本
    public $op_user_role = '';//下单人角色
    public $op_user_id = '';//下单人ID


    /**
     * 初始化、解析下单数据
     * @return bool
     * @throws Exception
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
        $this->order_info['order_status'] = Order::ORDER_STATUS_UNCONFIRMED;//订单状态
        if(empty($this->order_info['source_method'])){
            if(!empty($this->goods)){
                $source_method = 0;
                foreach ($this->goods as $v) {
                    $source_method = $v['source_method'];
                    if($v['source_method'] == GoodsService::SOURCE_METHOD_OWN){
                        break;
                    }
                }
                $this->order_info['source_method'] = $source_method;
            }
        }

        if(empty($this->order_info['warehouse'])) {
            $this->order_info['warehouse'] = WarehouseService::WAREHOUSE_OWN;
        }
        /*
        //ozon使用自建
        $is_anj = false;//是否安骏有库存
        //if(!in_array($this->order_info['source'],[Base::PLATFORM_OZON,Base::PLATFORM_MERCADO,Base::PLATFORM_JDID,Base::PLATFORM_LINIO,Base::PLATFORM_HEPSIGLOBAL,Base::PLATFORM_NOCNOC])) {
            foreach ($this->goods as $good) {
                if (empty($good['cgoods_no'])) {
                    $is_anj = false;
                    break;
                }
                if (in_array($good['platform_asin'],['LQJ-Radio-228','LQJ-Radio-262','GO144869', 'LQJ-Radio-300'])){
                    //$is_anj = false;
                    //break;
                }
                $goods_stock = GoodsStock::find()->where(['cgoods_no' => $good['cgoods_no'], 'warehouse' => GoodsService::WAREHOUSE_ANJ])->one();
                if (!empty($goods_stock) && $goods_stock['num'] >= $good['goods_num']) {
                    $is_anj = true;
                    break;
                }
            }
        //}

        if ($is_anj) {
            $this->order_info['warehouse'] = GoodsService::WAREHOUSE_ANJ;
        }*/

        //平台自有物流
        if (in_array($this->order_info['source'], FApiService::$own_Logistics)) {
            $this->order_info['integrated_logistics'] = Order::INTEGRATED_LOGISTICS_YES;
        }

        //自建的allegro 补充ioss
        $is_union =  CountryService::isEuropeanUnion($this->order_info['country']);
        if($this->order_info['source_method'] == GoodsService::SOURCE_METHOD_OWN && empty($this->order_info['tax_number']) && $is_union) {
            //意大利ioss
            if($this->order_info['source'] == Base::PLATFORM_EPRICE){
                $this->order_info['tax_number'] = 'IM3800000116';
            }else {
                $order_info = Order::find()->where([
                    'source_method' => GoodsService::SOURCE_METHOD_AMAZON,
                    'source' => Base::PLATFORM_FRUUGO,
                    'order_status' => Order::ORDER_STATUS_SHIPPED,
                    'country' => 'DE',//德国
                    'tax_number_use' => Order::TAX_NUMBER_USE_NO,
                ])->andWhere(['!=', 'tax_number', ''])->one();
                if (!empty($order_info)) {
                    $order_info->tax_number_use = Order::TAX_NUMBER_USE_YES;
                    $order_info->save();
                    $this->order_info['tax_number'] = $order_info['tax_number'];
                    $this->order_info['tax_relation_no'] = $order_info['relation_no'];
                }else{
                    $ioss = Shop::find()->where(['id'=>$this->order_info['shop_id']])->select('ioss')->scalar();
                    if(!empty($ioss)){
                        $this->order_info['tax_number'] = $ioss;
                    }
                }
            }
        }

        //货币
        if(empty($this->order_info['currency'])) {
            $currency = Shop::find()->where(['id' => $this->order_info['shop_id']])->select('currency')->scalar();
            $this->order_info['currency'] = $currency;
        }

        //汇率
        $this->order_info['exchange_rate'] = ExchangeRateService::getValue($this->order_info['currency']);;

        return true;
    }

    /**
     * 添加订单
     * @return string 订单号
     */
    public function addOrder()
    {
        $this->order_id = Order::addOrder($this->order_info);
        return $this->order_id;
    }

    /**
     * 添加订单商品
     * @return bool
     * @throws Exception
     */
    public function addOrderGoods()
    {
        $goods_service = new OrderGoodsService();
        foreach($this->goods as $goods){
            $id = $goods_service->addGoods($this->order_id, $goods);
        }
        return true;
    }

    /**
     * 格式化邮编
     * @param $postcode
     * @param $country_code
     * @return string
     */
    public static function formatPostcode($postcode,$country_code)
    {
        $postcode = trim($postcode);
        switch ($country_code) {
            case 'GB':
                $postcode = strtoupper($postcode);
                //2-4位数字或字母+空格+3位数字或字母(空格可不填)
                /*if (strlen($postcode) == 7) {
                    $postcode = substr($postcode, 0, 4) . ' ' . substr($postcode, 4, 3);
                }*/
                break;
            case 'SE':
                $postcode = str_replace(' ', '', $postcode);
                break;
        }
        return $postcode;
    }

}
