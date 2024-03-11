<?php
namespace common\services\api;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\Category;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsHepsiglobal;
use common\models\goods\GoodsMercado;
use common\models\goods\GoodsTranslate;
use common\models\goods_shop\GoodsShopFollowSale;
use common\models\GoodsAttribute;
use common\models\GoodsEvent;
use common\models\GoodsShop;
use common\models\Order;
use common\models\OrderGoods;
use common\models\platform\PlatformCategory;
use common\models\Shop;
use common\services\buy_goods\BuyGoodsService;
use common\services\goods\GoodsService;
use common\services\goods\GoodsShopService;
use common\services\goods\GoodsTranslateService;
use common\services\goods\platform\HepsiglobalPlatform;
use common\services\goods\platform\MercadoPlatform;
use Psr\Http\Message\ResponseInterface;
use yii\base\Exception;
use yii\helpers\ArrayHelper;

/**
 * Class HepsiglobalService
 * @package common\services\api
 * https://developers.hepsiglobal.com
 */
class HepsiglobalService extends BaseApiService
{

    public $base_url = 'https://api.hepsiglobal.com';

    /**
     * @return \GuzzleHttp\Client
     */
    public function getClient($timeout = 30)
    {
        $client = new \GuzzleHttp\Client([
            'headers' => [
                'Authorization' => 'Bearer '.$this->param,
                'Content-Type' => 'application/json'
            ],
            'base_uri' => $this->base_url,
            'timeout' => $timeout,
            'verify' => false
        ]);

        return $client;
    }

    /**
     * 获取订单列表
     * @param $add_time
     * @param $end_time
     * @return string|array
     */
    public function searchOrderLists($add_time, $end_time = null)
    {
        if (!empty($add_time)) {
            $add_time = strtotime($add_time) - 12 * 60 * 60;
            $add_time = date("Y-m-d H:i:s", $add_time);
        }
        if (empty($end_time)) {
            $end_time = date('Y-m-d H:i:s', time() + 2 * 60 * 60);
        }
        $response = $this->getClient()->post('/api/v1/orders/search',[
            'body' => json_encode([
                'start'=>0,
                "length" => 100,
                "search" => [
                    "updated_from" => self::toDate($add_time)
                ]
            ]),
        ]);
        $lists = $this->returnBody($response);
        return empty($lists)?[]:$lists;
    }

    /**
     * 获取类目
     */
    public function getCategory($offset = 0 , $limit = 100){
        $response = $this->getClient()->post('/api/v1/marketplace-categories/search',[
            'json' => [
                'start'=> $offset,
                "length" => $limit,
                "search" => [
                    //"remote_id" => 60003486,
                    "marketplace_id" => 1,
                    "is_active" => true,
                    "is_leaf" => true,
                    //"attribute_list"=>true,
                ],
            ]
        ]);
        $lists = $this->returnBody($response);
        return empty($lists)||empty($lists['data'])||empty($lists['data']['data'])?[]:$lists['data']['data'];
    }

    /**
     * 获取属性
     * @param int $category_id
     * @return array|string
     */
    public function getCategoryAttributes($category_id = 0)
    {
        $cache = \Yii::$app->cache;
        $cache_key = 'com::hepsi::category::attr_' . $category_id;
        $attr = $cache->get($cache_key);
        $attr = empty($attr) ? [] : json_decode($attr, true);
        if (empty($attr)) {
            $response = $this->getClient()->post('/api/v1/marketplace-attributes/search', [
                'json' => [
                    'start' => 0,
                    "length" => 100,
                    "search" => [
                        "marketplace_category_id" => (int)$category_id,
                        "is_active" => true,
                        'marketplace_id' => 1,
                    ]
                ]
            ]);
            $lists = $this->returnBody($response);
            $attr = empty($lists) || empty($lists['data']) || empty($lists['data']['data']) ? [] : $lists['data']['data'];
            if (!empty($attr)) {
                $cache->set($cache_key, json_encode($attr), 24 * 60 * 60);
            }
        }
        return $attr;
    }

    /**
     * 获取属性值
     * @param int $category_id
     * @param int $attribute_id
     * @return array|string
     */
    public function getCategoryAttributesValue($category_id = 0,$attribute_id = 0)
    {
        $cache = \Yii::$app->cache;
        $cache_key = 'com::hepsi::category::attr_val_' . $category_id.'_'.$attribute_id;
        $attr = $cache->get($cache_key);
        $attr = empty($attr) ? null : json_decode($attr, true);
        if (is_null($attr)) {
            $attr_val = [];
            $limit = 100;
            $offer = 0;
            while (true) {
                $response = $this->getClient()->post('/api/v1/marketplace-attribute-values/search', [
                    'json' => [
                        'start' => $offer,
                        "length" => $limit,
                        "search" => [
                            "marketplace_category_id" => (int)$category_id,
                            "is_active" => true,
                            'marketplace_id' => 1,
                            'marketplace_attribute_id' => (int)$attribute_id
                        ]
                    ]
                ]);
                $lists = $this->returnBody($response);
                $result = empty($lists) || empty($lists['data']) || empty($lists['data']['data']) ? [] : $lists['data']['data'];
                $attr_val = array_merge($attr_val, $result);
                $offer += 100;
                break;//只一次多次经常超时
                if(count($attr_val) > 400){
                    break;
                }
                if (count($result) < $limit) {
                    break;
                }
            }
            $attr = $attr_val;
            $cache->set($cache_key, json_encode($attr_val), 24 * 60 * 60);
        }
        return $attr;
    }

    /**
     * 获取商品详情
     * @param int $id
     * @return array|string
     */
    public function getProductsInfo($id = 0){
        $response = $this->getClient()->get('/api/v1/products/get/'.$id);
        $lists = $this->returnBody($response);
        return empty($lists)?[]:$lists;
    }

    /**
     * 获取商品列表
     * @return array|string
     */
    public function getActiveProductsLists($offset = 0 , $limit = 100){
        $response = $this->getClient()->post('/api/v1/products/search',[
            'body' => json_encode([
                'start'=> $offset,
                "length" => $limit,
                "search" => [
                    "is_active" => true
                ]
            ]),
        ]);
        $lists = $this->returnBody($response);
        return empty($lists['data'])?[]:$lists['data'];
    }

    /**
     * 获取订单列表
     * @param $add_time
     * @param $end_time
     * @return string|array
     */
    public function getOrderLists($add_time, $end_time = null)
    {
        if (!empty($add_time)) {
            $add_time = strtotime($add_time) - 12 * 60 * 60;
            $add_time = date("Y-m-d H:i:s", $add_time);
        }
        if (empty($end_time)) {
            $end_time = date('Y-m-d H:i:s', time() + 2 * 60 * 60);
        }
        $response = $this->getClient()->post('/api/v1/orders/unfulfilled-list',[
            'body' => json_encode([
                'start'=>0,
                "length" => 100,
            ]),
        ]);
        /*$response = $this->getClient()->post('/api/v1/orders/search',[
            'body' => json_encode([
                'start'=>0,
                "length" => 100,
                "search" => [
                    "updated_from" => self::toDate($add_time)
                ]
            ]),
        ]);*/
        $lists = $this->returnBody($response);
        return empty($lists)||empty($lists['data'])||empty($lists['data']['data'])?[]:$lists['data']['data'];
    }

    /**
     * 获取更新订单列表
     * @param $update_time
     * @param $offset
     * @param int $limit
     * @return array
     */
    public function getUpdateOrderLists($update_time, $offset = 0 , $limit = 100)
    {
        $update_time = $update_time - 12 * 60 * 60;
        $update_time = date("Y-m-d H:i:s", $update_time);
        $response = $this->getClient()->post('/api/v1/orders/search',[
            'body' => json_encode([
                'start'=> $offset,
                "length" => $limit,
                "search" => [
                    "updated_from" => self::toDate($update_time)
                ]
            ]),
        ]);
        $lists = $this->returnBody($response);
        return empty($lists)||empty($lists['data'])||empty($lists['data']['data'])?[]:$lists['data']['data'];
    }

    /**
     * 处理取消订单
     * @param $order
     * @return array|bool
     */
    public function dealCancelOrder($order)
    {
        $goods = [];
        $cancel_goods = [];
        $cancel_time = 0;
        foreach ($order['order_items'] as $v) {
            if ($v['status'] == 'cancel') {
                $info = $v['variant'];
                $cancel_goods[] = [
                    //'id' => '432085',
                    'sku_no' => $info['sku'],
                    'num' => $v['quantity_cancel'],
                    'cancel_time' => strtotime($v['cancellation_date'])
                ];
                $cancel_time = strtotime($v['cancellation_date']);
                if ($v['quantity_cancel'] == $v['quantity']) {
                    continue;
                }
            }
            $goods[] = $v;
        }

        if (empty($goods)) {
            return [
                'relation_no' => $order['id'],
                'cancel_time' => $cancel_time
            ];
        } else {
            if(!empty($cancel_goods)){
                return [
                    'relation_no' => $order['id'],
                    'cancel_goods' => $cancel_goods,
                    'cancel_time' => $cancel_time
                ];
            }
        }
        return false;
    }

    /**
     * 获取订单详情
     * @param $order_id
     * @return string|array
     */
    public function getOrderInfo($order_id)
    {
        $response = $this->getClient()->get('/api/v1/orders/'.$order_id);
        $lists = $this->returnBody($response);
        return empty($lists)||empty($lists['data'])?[]:$lists['data'];
    }

    /**
     * 获取发货信息
     * @param $shipment_id
     * @return array|string
     */
    public function getShipping($shipment_id)
    {
        $response = $this->getClient()->get('/marketplace/shipments/' . $shipment_id);
        return $this->returnBody($response);
    }

    /**
     * 此处 datetime类型 转化 为iso8601 类型
     * @param $date
     * @return false|string
     */
    public static function toDate($date)
    {
        $time = strtotime($date);
        return date("Y-m-d\TH:i:s\Z", $time);
    }

    /**
     * 处理订单
     * @param $order
     * @return array|bool
     */
    public function dealOrder($order)
    {
        $shop_v = $this->shop;
        if (empty($order)) {
            return false;
        }

        $relation_no = (string)$order['order_id'];
        $exist = Order::find()->where(['source'=> $shop_v['platform_type'],'relation_no' => $relation_no])->one();
        if ($exist) {
            return false;
        }
        $order = $this->getOrderInfo($relation_no);

        $add_time = strtotime($order['order_date']) - 8 * 60 * 60;;

        //$logistics_channels_name = empty($shipping['tracking_method']) ? '' : $shipping['tracking_method'];
        //$track_no = empty($shipping['tracking_number']) ? '' : $shipping['tracking_number'];

        $shipping_address = $order['customer_shipping_address'];
        $country = $shipping_address['country_code'];
        $buyer_phone = empty($shipping_address['phone']) ? '0000' : $shipping_address['phone'];

        $track_no = '';
        $logistics_channels_name = '';

        $data = [
            'create_way' => Order::CREATE_WAY_SYSTEM,
            'shop_id' => $shop_v['id'],
            'source' => $shop_v['platform_type'],
            'relation_no' => $relation_no,
            'date' => $add_time,
            'user_no' => (string)'',
            'country' => $country,
            'city' => empty($shipping_address['district']) ? '' : $shipping_address['district'],
            'area' => empty($shipping_address['city']) ? '' : $shipping_address['city'],
            'company_name' => '',
            'buyer_name' => $shipping_address['name'],
            'buyer_phone' => $buyer_phone,
            'postcode' => (string)empty($shipping_address['zipcode']) ? ' ' : $shipping_address['zipcode'],
            'email' => empty($shipping_address['email'])?'':$shipping_address['email'],
            'address' => $shipping_address['address'],
            'remarks' => '',
            'add_time' => $add_time,
            'logistics_channels_name' => $logistics_channels_name,
            'track_no' => $track_no,
        ];

        $platform_fee = 0;
        $freight_price = 0;
        $goods = [];
        $remaining_shipping_time = 0;
        foreach ($order['order_items'] as $v) {
            if ($v['status'] == 'cancel') {
                continue;
            }

            //剩余发货时间
            if (empty($remaining_shipping_time)) {
                $remaining_shipping_time = strtotime($v['due_date']) - 8 * 60 * 60;
            } else {
                $remaining_shipping_time = min($remaining_shipping_time, strtotime($v['due_date']) - 8 * 60 * 60);
            }


            $info = $v['variant'];
            /*$where = [
                'shop_id'=>$shop_v['id'],
                'platform_sku_no'=>$info['sku']
            ];
            $platform_sku_no = GoodsShop::find()->where($where)->select('goods_no')->scalar();*/
            $platform_sku_no = GoodsChild::find()->where(['cgoods_no' => $info['sku']])->select('cgoods_no')->scalar();
            if (empty($platform_sku_no)) {
                $goods_map = (new BuyGoodsService())->getGoodsToSkuCountry($info['sku'], $country, Base::PLATFORM_1688);
            } else {
                $goods_map = (new BuyGoodsService())->getGoodsToSkuCountry($platform_sku_no, $country, Base::PLATFORM_1688, 2);
            }
            $goods_data = $this->dealOrderGoods($goods_map);
            $goods_data = array_merge($goods_data, [
                'goods_name' => $info['name'],
                'goods_num' => $v['quantity'],
                //'goods_income_price' => $v['original_grand_total'] / $v['quantity'],
                'goods_income_price' => $v['unit_base_price'],
            ]);
            $goods[] = $goods_data;
            //$platform_fee += $v['total_commission_fee'] + $v['total_commission_vat_fee'] + $v['total_marketplace_fee'] + $v['total_customs_fee'] + $v['total_otv_fee'] + $v['total_tax_label_fee'] + $v['total_original_tax_label_fee'] + $v['total_service_fee'] + $v['total_buffer_fee'] + $v['total_marketplace_service_fee'] + $v['total_marketplace_service_vat_fee'];
            //$freight_price += $v['total_logistic_fee'];
            $freight_price = 0;
        }

        if(empty($goods)) {
            return false;
        }

        $data['remaining_shipping_time'] = $remaining_shipping_time;
        //$data['platform_fee'] = $platform_fee;
        //$data['freight_price'] = $freight_price * 6.4;

        return [
            'order' => $data,
            'goods' => $goods,
        ];
    }

    /**
     * 获取包裹
     * @param $order_id
     * @return array|string
     */
    public function getPackages($order_id)
    {
        $response = $this->getClient()->post('/api/v1/packages/search',[
            'body' => json_encode([
                'start'=>0,
                "length" => 10,
                "search" => [
                    //"id" => $order_id,
                    'order_id'=>(int)$order_id
                ]
            ]),
        ]);
        $lists = $this->returnBody($response);
        return empty($lists)||empty($lists['data'])||empty($lists['data']['data'])?[]:$lists['data']['data'];
    }

    /**
     * 打印
     * @param $order
     * @return string|array
     */
    public function doPrint($order, $is_show = false)
    {
        $items = $this->getPackages($order['relation_no']);
        $items = end($items);
        $tracking = [];
        foreach ($items['tracking'] as $v) {
            if(empty($v['tracking_pdf']) && empty($v['tracking_barcode'])) {
                continue;
            }
            $tracking = $v;
        }
        if (empty($order['track_no']) || $order['track_no'] != $tracking['tracking_barcode']) {
            $order = Order::find()->where(['relation_no' => $order['relation_no']])->one();
            $order['logistics_channels_name'] = $tracking['tracking_provider'];
            $order['track_no'] = $tracking['tracking_barcode'];
            $order->save();
        }

        if(empty($tracking['tracking_pdf'])) {
            return false;
        }

        $tracking_pdf = $tracking['tracking_pdf'];
        if ($is_show === 2) {
            return true;
        }

        if ($is_show) {
            header("Content-type: application/pdf");
            echo file_get_contents($tracking_pdf);
            exit();
        }

        return $tracking_pdf;
    }

    /**
     * 打印发票
     * @param $order
     * @return string|array
     */
    public function doPrintInvoice($order, $is_show = false)
    {
        $items = $this->getPackages($order['relation_no']);
        $items = current($items);
        $tracking = [];
        foreach ($items['tracking'] as $v) {
            if(empty($v['tracking_pdf']) && empty($v['tracking_barcode'])) {
                continue;
            }
            $tracking = $v;
        }
        if (empty($order['track_no']) || $order['track_no'] != $tracking['tracking_barcode']) {
            $order = Order::find()->where(['relation_no' => $order['relation_no']])->one();
            $order['logistics_channels_name'] = $tracking['tracking_provider'];
            $order['track_no'] = $tracking['tracking_barcode'];
            $order->save();
        }

        if(empty($items['proforma_url'])) {
            return false;
        }

        $tracking_pdf = $items['proforma_url'];
        if ($is_show === 2) {
            return true;
        }

        if ($is_show) {
            header("Content-type: application/pdf");
            echo file_get_contents($tracking_pdf);
            exit();
        }

        return $tracking_pdf;
    }

    /**
     * 设置物流单号
     * @param $order_id
     * @param $tracking_number
     * @return bool
     */
    public function setTrackingNumber($order_id, $tracking_number, $carrier_code = null, $tracking_url = null)
    {
        $order = Order::find()->where(['relation_no' => $order_id])->one();
        $order_api_info = $this->getOrderInfo($order_id);
        $items = [];
        foreach ($order_api_info['order_items'] as $v){
            $items[] = [
                'order_item_id' => (int)$v['id'],
                'quantity' => (int)$v['quantity']
            ];
        }
        $response = $this->getClient(60)->post('/api/v1/packages',[
            'body' => json_encode([
                'order_id'=>(int)$order_id,
                "items" => $items
            ]),
        ]);
        $result = $this->returnBody($response);
        if(empty($result) || empty($result['data'])){
            return false;
        }
        $result = $result['data'];
        $tracking = [];
        foreach ($result['tracking'] as $v) {
            if(empty($v['tracking_pdf']) && empty($v['tracking_barcode'])){
                continue;
            }
            $tracking = $v;
        }

        if (empty($order['track_no']) || $order['track_no'] != $tracking['tracking_barcode']) {
            $order['logistics_channels_name'] = $tracking['tracking_provider'];
            $order['track_no'] = $tracking['tracking_barcode'];
            $order->save();
        }
        return true;
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
        /*$order = Order::find()->where(['relation_no' => $order_id]);
        $items = $this->getOrderInfo($order['delivery_order_id']);
        foreach ($items as $v) {
            $OrderItemId = $v['OrderItemId'];
            $result = $this->request('GET', 'SetStatusToDelivered', ['OrderItemId' => $OrderItemId]);
        }*/
        return true;
    }

    /**
     * 添加商品
     * @param $goods
     * @return bool
     * @throws Exception
     */
    public function addGoods($goods)
    {
        //商品是否已经添加过，针对多变体
        static $goods_no_arr = [];
        if(in_array($goods['goods_no'],$goods_no_arr)){
            return true;
        }
        $goods_no_arr[] = $goods['goods_no'];

        $shop = $this->shop;
        $goods_hepsi = GoodsHepsiglobal::find()->where(['goods_no' => $goods['goods_no']])->one();
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        if(empty($goods_shop) || $goods_shop['status'] == GoodsShop::STATUS_DELETE) {
            return false;
        }

        if (!empty($goods_shop['platform_goods_id'])) {
            //return true;
        }

        $data = $this->dealGoodsInfo($goods,$goods_hepsi,$goods_shop);
        if(!$data){
            return false;
        }
        CommonUtil::logs('hepsi request goods_no:' . $goods['goods_no'] . ' shop_id:' . $shop['id'] . ' data:' . json_encode($data,JSON_UNESCAPED_UNICODE) , 'add_products_hepsi');
        //echo  json_encode($data,JSON_UNESCAPED_UNICODE);
        $response = $this->getClient()->post('/api/v1/products/upsert', ['json' => $data]);
        $up_result = $this->returnBody($response);
        CommonUtil::logs('hepsi result goods_no:' . $goods['goods_no'] . ' shop_id:' . $shop['id'] . ' data:' . json_encode($data,JSON_UNESCAPED_UNICODE) . ' result:' . json_encode($up_result), 'add_products_hepsi');
        if (empty($up_result) || empty($up_result['data'])) {
            return false;
        }
        return true;
    }

    /**
     * 修改商品
     * @param $goods
     * @return bool
     * @throws Exception
     */
    public function updateGoods($goods)
    {
        return $this->addGoods($goods);
    }

    /**
     * 处理商品信息
     * @param $goods
     * @param $goods_hepis
     * @param $goods_shop
     * @return array
     */
    public function dealGoodsInfo($goods, $goods_hepis, $goods_shop) {

        if($goods['language'] != 'en') {
            $goods_translate_service = new GoodsTranslateService('en');
            //已经翻译的数据
            $goods_translate_info = $goods_translate_service->getGoodsInfo($goods['goods_no']);
            if (!empty($goods_translate_info['goods_name'])) {
                $goods['goods_name'] = $goods_translate_info['goods_name'];
            }
            if (!empty($goods_translate_info['goods_content'])) {
                $goods['goods_content'] = $goods_translate_info['goods_content'];
            }
        }

        //hepsi采集
        $ean = '';
        $goods_attr_item = [];
        if($goods['source_platform_type'] == Base::PLATFORM_HEPSIGLOBAL) {
            $goods_attr = GoodsAttribute::find()->where(['goods_no' => $goods['goods_no']])->asArray()->all();
            foreach ($goods_attr as $g_attr_v) {
                if ($g_attr_v['attribute_name'] == 'EAN') {
                    /*if (in_array($this->shop['id'], [471, 472])) {
                        $ean = $g_attr_v['attribute_value'];
                        $ean = preg_replace('/-y$/i', '', $ean);
                    }*/
                    continue;
                }
                $goods_attr_item[$g_attr_v['attribute_name']] = $g_attr_v['attribute_value'];
            }
        }

        $hepsi_ser = new HepsiglobalPlatform();
        $goods_name = $hepsi_ser->dealTitle($goods_hepis['goods_name']);
        $sku_no = !empty($goods_shop['platform_sku_no'])?$goods_shop['platform_sku_no']:$goods['sku_no'];
        $spu = $sku_no;
        if($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
            $spu = $goods['goods_no'];
        }

        $images = [];
        $image = json_decode($goods['goods_img'], true);
        $main_image = '';
        $i = 0;
        foreach ($image as $v) {
            if ($i > 5) {
                break;
            }
            $i++;
            if ($i == 1) {
                $main_image = $v['img']. '?imageMogr2/thumbnail/!500x500r';//图片不小于500;
                //continue;
            }
            $images[] = $v['img']. '?imageMogr2/thumbnail/!500x500r';//图片不小于500;
        }
        $category_id = trim($goods_hepis['o_category_name']);
        if (empty($category_id)){
            return false;
        }
        $category = Category::find()->where(['id'=>$goods['category_id']])->one();
        $category_data = [
            "parent_id" => null,
            "remote_id" => (string)$category['id'],
            "remote_parent_id" => null,
            "name" => $category['name_en'],
            "is_active" => true,
            "cross" => [
                [
                    "hepsiburada"=> (string)$category_id
                ]
            ]
        ];

        $pl_category = PlatformCategory::find()->where(['platform_type'=>Base::PLATFORM_HEPSIGLOBAL,'extra1'=>$category_id])->one();
        if(empty($pl_category)){
            return false;
        }

        $category_attr = $this->getCategoryAttributes($pl_category['id']);
        //$category_attr_value = ArrayHelper::index($category_attr_value,null,'marketplace_attribute_id');

        $size = GoodsService::getSizeArr($goods['size']);

        $l = !empty($size['size_l']) && $size['size_l'] > 1 ? (int)$size['size_l'] : 30;
        $h = !empty($size['size_h']) && $size['size_h'] > 1  ? (int)$size['size_h'] : 10;
        $w = !empty($size['size_w']) && $size['size_w'] > 1 ? (int)$size['size_w'] : 20;

        $weight = GoodsShopService::getGoodsWeight($goods,$goods_shop);
        $weight = intval((max($weight, 0.1)) * 1000);
        $stock = true;
        if (!in_array($goods['status'], [Goods::GOODS_STATUS_VALID, Goods::GOODS_STATUS_WAIT_MATCH])) {
            return false;
        }

        //停止销售
        if ($goods['stock'] == Goods::STOCK_NO) {
            $stock = false;
        }

        $content_en = $hepsi_ser->dealContent($goods);
        $content_tr = $hepsi_ser->dealContent($goods_hepis);
        $variants = [];

        $all_goods = [];
        if($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {//多变体
            $goods_childs = GoodsChild::find()->where(['goods_no'=>$goods['goods_no']])->asArray()->all();
            $goods_shops = GoodsShop::find()->where(['goods_no' => $goods['goods_no'], 'shop_id' => $this->shop['id']])->indexBy('cgoods_no')->asArray()->all();
            foreach ($goods_childs as $goods_child_v) {
                if (empty($goods_shops[$goods_child_v['cgoods_no']])) {
                    continue;
                }
                $goods_shop_v = $goods_shops[$goods_child_v['cgoods_no']];
                $goods['price'] = $goods_shop_v['price'];
                $goods['ean'] = !empty($ean)?$ean:$goods_shop_v['ean'];
                $goods['colour'] = $goods_child_v['colour'];
                $goods['csize'] = $goods_child_v['size'];
                $goods['sku_no'] = !empty($goods_shop_v['platform_sku_no'])?$goods_shop_v['platform_sku_no']:$goods_child_v['sku_no'];
                $main_image = $goods_child_v['goods_img']. '?imageMogr2/thumbnail/!500x500r';//图片不小于300
                $images[0] = $main_image;
                $goods['main_image'] = $main_image;
                $goods['extra_images'] = $images;
                $goods['size'] = $goods_child_v['package_size'];
                $goods['real_weight'] = $goods_child_v['real_weight'];
                $goods['weight'] = $goods_child_v['weight'];
                $all_goods[] = $goods;
            }
            CommonUtil::logs('hepsi request goods_no:' . $goods['goods_no'] . ' data:' . json_encode($all_goods,JSON_UNESCAPED_UNICODE) , 'add_products_hepsi');
        } else {
            $goods['sku_no'] = $sku_no;
            $goods['price'] = $goods_shop['price'];
            $goods['ean'] = !empty($ean)?$ean:$goods_shop['ean'];
            $goods['main_image'] = $main_image;
            $goods['extra_images'] = $images;
            $all_goods = [$goods];
        }

        foreach ($all_goods as $goods_v) {
            $colour_map = HepsiglobalPlatform::$colour_map;

            $colour_val = $goods_v['colour'];
            $colour = empty($colour_map[$goods_v['colour']]) ? $colour_val : $colour_map[$goods_v['colour']];
            $colour_val = str_replace(' ', '_', $colour_val);
            $pattern = '/[^a-zA-Z0-9_]/';
            $colour_val = preg_replace($pattern, '', $colour_val);
            //$colour = empty($colour) ? $colour_map['Black'] : $colour;

            $price = $goods_v['price'];


            $params = [];
            $exist_param = false;
            foreach ($category_attr as $attr_v) {
                $attr_configs = [];
                foreach ($attr_v['marketplace_attribute_configs'] as $tmp_v) {
                    if ($tmp_v['marketplace_id'] == 1) {
                        $attr_configs = $tmp_v;
                    }
                }

                //必填属性
                if ($attr_configs['is_required'] != true && $attr_v['name_en'] != 'Color') {
                    continue;
                }
                $exist_param = true;
            }

            $has_color = false;
            $has_size = false;
            $i = 0;
            foreach ($category_attr as $attr_v) {
                $i++;
                $attr_configs = [];
                foreach ($attr_v['marketplace_attribute_configs'] as $tmp_v) {
                    if ($tmp_v['marketplace_id'] == 1) {
                        $attr_configs = $tmp_v;
                    }
                }

                if ($exist_param || $i != 1) {//当没有必填时候 取第一个
                    //必填属性
                    if ($attr_configs['is_required'] != true && $attr_v['name_en'] != 'Color') {
                        continue;
                    }
                }

                $attr_value = $this->getCategoryAttributesValue($pl_category['id'], $attr_v['id']);
                //$attr_value = empty($category_attr_value[$attr_v['id']])?[]:$category_attr_value[$attr_v['id']];

                $params_info = [
                    'name' => $attr_v['name_en'],
                    'remote_attribute_id' => 'hg_' . $attr_v['remote_id'],
                    'cross_attr' => [[
                        'hepsiburada' => (string)$attr_v['remote_id'],
                    ]],
                    'value' => "",
                    /*'remote_attribute_value_id' => 'hg_'.$attr_v['remote_id'],
                    'cross' => [[
                        'hepsiburada'=>'',
                    ]],
                    */
                ];

                if(!empty($goods_attr_item[$attr_v['name']])) {
                    foreach ($attr_value as $attr_val_v) {
                        if (CommonUtil::compareStrings($attr_val_v['value'], $goods_attr_item[$attr_v['name']])) {
                            $params_info['value'] = $attr_val_v['value'];
                            $params_info['remote_attribute_value_id'] = 'hg_' . $attr_val_v['remote_id'];
                            $params_info['cross'] = [[
                                'hepsiburada' => (string)$attr_val_v['remote_id'],
                            ]];
                            break;
                        }
                    }
                    if (empty($params_info['value'])) {
                        $params_info['value'] = $goods_attr_item[$attr_v['name']];
                        $att_val = str_replace(' ', '_', $goods_attr_item[$attr_v['name']]);
                        $att_val = preg_replace('/[^a-zA-Z0-9_]/', '', $att_val);
                        $params_info['remote_attribute_value_id'] = 'm_' . $att_val;
                    }
                }else {
                    //颜色处理
                    if ($attr_v['name_en'] == 'Color') {
                        $has_color = true;
                        foreach ($attr_value as $attr_val_v) {
                            if (CommonUtil::compareStrings($attr_val_v['value'], $colour)) {
                                $params_info['value'] = $attr_val_v['value'];
                                $params_info['remote_attribute_value_id'] = 'hg_' . $attr_val_v['remote_id'];
                                $params_info['cross'] = [[
                                    'hepsiburada' => (string)$attr_val_v['remote_id'],
                                ]];
                                break;
                            }
                        }
                        if (empty($params_info['value'])) {
                            $params_info['value'] = $colour;
                            $params_info['remote_attribute_value_id'] = 'm_' . $colour_val;
                        }
                    }

                    if ($attr_v['name_en'] == 'Size' && $goods_v['goods_type'] == Goods::GOODS_TYPE_MULTI) {
                        $has_size = true;
                        $size_val = str_replace(' ', '_', $goods_v['csize']);
                        $params_info['value'] = $goods_v['csize'];
                        $params_info['remote_attribute_value_id'] = 'm_' . $size_val;
                    }

                    if (empty($params_info['value'])) {
                        if (!empty($attr_value)) {
                            $cut = count($attr_value);
                            $ran_i = rand(0, $cut - 1);
                            $attr_value = $attr_value[$ran_i];
                            $params_info['value'] = $attr_value['value'];
                            $params_info['remote_attribute_value_id'] = 'hg_' . $attr_value['remote_id'];
                            $params_info['cross'] = [[
                                'hepsiburada' => (string)$attr_value['remote_id'],
                            ]];
                        } else {
                            $params_info['value'] = '1';
                            $params_info['remote_attribute_value_id'] = 'm_1';
                        }
                    }
                }
                $params[] = $params_info;
            }

            if($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
                if ($has_color === false && !empty($colour)) {
                    $params_info = [
                        'name' => 'Color',
                        'remote_attribute_id' => 'm_color',
                        'value' => $colour,
                        'remote_attribute_value_id' => 'm_' . $colour_val
                    ];
                    $params[] = $params_info;
                }

                if ($has_size === false && !empty($goods_v['csize'])) {
                    $size_val = str_replace(' ', '_', $goods_v['csize']);
                    $pattern = '/[^a-zA-Z0-9_]/';
                    $size_val = preg_replace($pattern, '', $size_val);
                    $params_info = [
                        'name' => 'Size',
                        'remote_attribute_id' => 'm_size',
                        'value' => $goods_v['csize'],
                        'remote_attribute_value_id' => 'm_' . $size_val
                    ];
                    $params[] = $params_info;
                }
            }

            $variants[] = [
                'name' => $hepsi_ser->dealTitle($goods_v['goods_name']),
                'price' => (float)$price,
                'retail_price' => (float)$price*2,
                'currency' => 'USD',
                'stock' => $stock ? 1000 : 0,
                'sku' => $goods_v['sku_no'],
                'barcode' => $goods_v['ean'],
                'hs_code' => (string)$pl_category['extra2'],
                'gtin' => '',
                'default_image_url' => $goods_v['main_image'],
                'extra_images' => $goods_v['extra_images'],
                'description' => $content_en,
                'weight' => $weight,
                'height' => $h,
                'width' => $w,
                'length' => $l,
                'freight' => 0,
                'is_active' => true,
                'translations' => [
                    [
                        'language' => 'TR',
                        'name' => $goods_name,
                        'description' => $content_tr,
                    ],
                    [
                        'language' => 'ZH',
                        'name' => $hepsi_ser->dealTitle($goods_v['goods_name_cn']),
                        'description' => $goods_v['goods_name_cn'],
                    ],
                ],
                'attributes' => empty($params)?null:$params,
            ];
        }

        $brand = !empty($this->shop['brand_name'])?$this->shop['brand_name']:'Generic';
        $info = [];
        $info['spu'] = $spu;
        $info['name'] = $hepsi_ser->dealTitle($goods['goods_name']);
        $info['category'] = $category_data;
        $info['brand'] = [
            "name" => $brand,
            "remote_id" => 'm_'.$brand,
            /*"cross" => [
                [
                    //"hepsiburada"=> "pg1"
                ]
            ]*/
        ];
        $info['default_image_url'] = $main_image;
        $info['tax'] = 0;
        $info['is_active'] = true;
        if(!empty($goods_shop['follow_claim'])) {
            $fulfillment_day = 4;
        } else {
            $order = OrderGoods::find()->where(['goods_no' => $goods['goods_no']])->limit(1)->one();
            $fulfillment_day = !empty($order) ? 4 : 5;
        }
        if($this->shop['id'] == 488) {
            $fulfillment_day = 5;
        }
        $info['fulfillment_day'] = $fulfillment_day;
        //$info['guarantee'] = 0;

        $info['translations'] = [
            [
                'language' => 'TR',
                'name' => $goods_name
            ],
            [
                'language' => 'ZH',
                'name' => $hepsi_ser->dealTitle($goods['goods_name_cn'])
            ],
        ];
        $info['variants'] = $variants;
        return $info;
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
        //$sku_no = $goods['sku_no'];
        $shop = $this->shop;
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        if (!empty($goods_shop['platform_sku_no'])) {
            $sku_no = $goods_shop['platform_sku_no'];
        } else {
            $sku_no = $goods['sku_no'];
        }

        $data = [
            'sku' => $sku_no,
            'stock' => $stock ? 1000 : 0,
        ];
        if (empty($price)) {
            $price = $goods_shop['price'];
        }
        $data['price'] = (float)$price;
        $data['retail_price'] = (float)($price * 2);
        $response = $this->getClient()->post('/api/v1/variants/stock-and-price', ['json' => [
            'items' => [
                $data
            ]
        ]]);
        $up_result = $this->returnBody($response);
        CommonUtil::logs('hepsi request goods_no:' . $goods['goods_no'] . ' data:' . json_encode($data,JSON_UNESCAPED_UNICODE). ' result:' . json_encode($up_result,JSON_UNESCAPED_UNICODE) , 'update_price_hepsi');
        $up_result = empty($up_result['data']) ? [] : $up_result['data'];
        if (!empty($up_result)) {
            return 1;
        }
        return 0;
    }

    public function delGoodsVariants($sku_no)
    {
        $response = $this->getClient()->get('/api/v1/variants/disabled/'.$sku_no);
        $up_result = $this->returnBody($response);
        $up_result = empty($up_result['data']) ? [] : $up_result['data'];
        if (empty($up_result)) {
            return false;
        }
        return true;
    }


    /**
     * 删除商品
     */
    public function delGoods($goods_shop)
    {
        if(!empty($goods_shop['platform_sku_no'])){
            $sku_no = $goods_shop['platform_sku_no'];
        } else {
            $goods = GoodsChild::find()->where(['cgoods_no' => $goods_shop['cgoods_no']])->one();
            $sku_no = $goods['sku_no'];
        }
        $response = $this->getClient()->get('/api/v1/products/disabled/'.$sku_no);
        $up_result = $this->returnBody($response);
        $up_result = empty($up_result['data']) ? [] : $up_result['data'];
        if (empty($up_result)) {
            return false;
        }
        return true;
    }

    /**
     * @param ResponseInterface $response
     * @return array|string
     */
    public function returnBody($response)
    {
        $body = '';
        if (in_array($response->getStatusCode(), [200, 201])) {
            $body = $response->getBody()->getContents();
        }
        return json_decode($body, true);
    }
}