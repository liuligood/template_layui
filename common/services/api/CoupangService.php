<?php

namespace common\services\api;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\Category;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsCoupang;
use common\models\GoodsProperty;
use common\models\GoodsShop;
use common\models\Order;
use common\models\PlatformCategoryProperty;
use common\services\buy_goods\BuyGoodsService;
use common\services\goods\GoodsService;
use common\services\goods\platform\CoupangPlatform;
use common\services\transport\TransportService;
use Psr\Http\Message\ResponseInterface;
use yii\helpers\ArrayHelper;

/**
 * Class CoupangService
 * @package common\services\api
 * https://developers.coupangcorp.com/hc/categories/360002105414
 */
class CoupangService extends BaseApiService
{

    public $frequency_limit_timer = [
        'add_goods' => [5000,86400,'d']
    ];

    /**
     * 获取供应商id
     * @return mixed
     */
    public function getVendorId()
    {
        $param = json_decode($this->param, true);
        return $param['vendorId'];
    }

    /**
     * 获取用户id
     * @return mixed
     */
    public function getUserId()
    {
        $param = json_decode($this->param, true);
        return $param['vendorUserId'];
    }

    /**
     * @param $method
     * @param $path
     * @param $param
     * @return array|false|\GuzzleHttp\Client
     */
    public function request($method,$path,$param)
    {
        date_default_timezone_set("GMT+0");
        $datetime = date("ymd") . 'T' . date("His") . 'Z';
        date_default_timezone_set('Asia/Shanghai');
        //$method = "GET";
        //$path = "/v2/providers/seller_api/apis/api/v1/marketplace/meta/category-related-metas/display-category-codes/77723";
        //$query = "";
        $query = [];
        if ($method == 'GET') {
            foreach ($param as $data_k => $data_v) {
                $query[] = $data_k . '=' . $data_v;
            }
        }
        $query = implode('&', $query);

        $message = $datetime . $method . $path . $query;

        $signature = hash_hmac('sha256', $message, $this->secret_key);

        $authorization = "CEA algorithm=HmacSHA256, access-key=" . $this->client_key . ", signed-date=" . $datetime . ", signature=" . $signature;

        $url = $path . '?' . $query;

        $client = new \GuzzleHttp\Client([
            'headers' => [
                'Content-Type' => 'application/json;charset=UTF-8',
                'Authorization' => $authorization,
                //'X-EXTENDED-TIMEOUT' => '90000'
            ],
            'base_uri' => 'https://api-gateway.coupang.com',
            'timeout' => 30,
        ]);
        if ($method == 'GET') {
            $response = $client->get($url);
        } else if ($method == 'POST') {
            $response = $client->post($url, [
                'body' => json_encode($param)
            ]);
        } else if ($method == 'PUT') {
            $response = $client->put($url, [
                'json' => $param,
            ]);
        } else if ($method == 'DELETE') {
            $response = $client->delete($url, [
                'json' => $param,
            ]);
        }
        return $this->returnBody($response);
    }


    /**
     * 获取类目产品参数
     * @param $category_id
     * @return string|array
     */
    public function getCategoryProductParameters($category_id)
    {
        $cache = \Yii::$app->redis;
        $cache_key = 'com::coupang::category_parameters:' . $category_id;
        $result = $cache->get($cache_key);
        if (empty($result)) {
            $path = '/v2/providers/seller_api/apis/api/v1/marketplace/meta/category-related-metas/display-category-codes/' . $category_id;
            $data = [];
            $lists = $this->request('GET', $path, $data);
            $result = empty($lists['data']) ? [] : $lists['data'];
            $cache->setex($cache_key, 60 * 60, json_encode($result));
        } else {
            $result = json_decode($result, true);
        }
        return $result;
    }

    /**
     * 获取ASIN获取商品信息
     * @param $asin
     * @return string|array
     */
    public function getProductsToAsin($asin)
    {
        $path = '/v2/providers/seller_api/apis/api/v1/marketplace/seller-products/external-vendor-sku-codes/' . $asin;
        $data = [];
        $lists = $this->request('GET', $path, $data);
        $product = empty($lists['data']) ? [] : $lists['data'];
        return current($product);
    }

    /**
     * 获取商品信息
     * @param $id
     * @return string|array
     */
    public function getProductsToId($id)
    {
        $path = '/v2/providers/seller_api/apis/api/v1/marketplace/seller-products/' . $id;
        $data = [];
        $lists = $this->request('GET', $path, $data);
        return empty($lists['data']) ? [] : $lists['data'];
    }

    /**
     * 获取商品信息
     * @param $id
     * @return string|array
     */
    public function getProductsToId1($id)
    {
        $path = '/v2/providers/seller_api/apis/api/v1/marketplace/vendor-items/'.$id.'/inventories';
        $data = [];
        $lists = $this->request('GET', $path, $data);
        return empty($lists['data']) ? [] : $lists['data'];
    }


    /**
     * 获取退货信息
     * @return array
     */
    public function getReturnShipping()
    {
        $cache = \Yii::$app->redis;
        $cache_key = 'com::coupang::return_shipping:' . $this->client_key;
        $result = $cache->get($cache_key);
        if (empty($result)) {
            $path = '/v2/providers/openapi/apis/api/v4/vendors/' . $this->getVendorId() . '/returnShippingCenters';
            $data = [];
            $lists = $this->request('GET', $path, $data);
            $content = empty($lists['data']) || empty($lists['data']['content']) ? [] : $lists['data']['content'];
            $result = current($content);
            $cache->setex($cache_key, 60 * 60, json_encode($result));
        } else {
            $result = json_decode($result, true);
        }
        return $result;
    }

    /**
     * 获取发货信息
     * @return array
     */
    public function getShippingPlace()
    {
        $cache = \Yii::$app->redis;
        $cache_key = 'com::coupang::shipping_place:' . $this->client_key;
        $result = $cache->get($cache_key);
        if (empty($result)) {
            $path = '/v2/providers/marketplace_openapi/apis/api/v1/vendor/shipping-place/outbound';
            $data = [
                'pageNum' =>1,
                'pageSize' =>10,
            ];
            $lists = $this->request('GET', $path, $data);
            $content = empty($lists['data']) || empty($lists['data']['content']) ? [] : $lists['data']['content'];
            $result = current($content);
            $cache->setex($cache_key, 60 * 60, json_encode($result));
        } else {
            $result = json_decode($result, true);
        }
        return $result;
    }

    /**
     * 获取订单列表
     * @param $add_time
     * @param $end_time
     * @return string|array
     */
    public function getOrderLists($add_time,$end_time = null)
    {
        if(!empty($add_time)){
            $add_time = strtotime($add_time) - 8*60*60;
            //$add_time = date("Y-m-d\TH:i",$add_time);
            $add_time = date("Y-m-d",$add_time);
            //$add_time = date("Y-m-d",$add_time);

        }
        if(empty($end_time)){
            //$end_time = date('Y-m-d\TH:i',time() + 2*60*60);
            $end_time = date('Y-m-d',time() + 24*60*60);
        }
        $path = '/v2/providers/openapi/apis/api/v4/vendors/'.$this->getVendorId().'/ordersheets';

        $data = [
            'createdAtFrom' => $add_time,
            'createdAtTo' => $end_time,
            'status' =>  'ACCEPT',//ACCEPT 已付款  DEPARTURE 已发货
            //'searchType' => 'timeFrame',
        ];
        $lists = $this->request('GET',$path,$data);
        return empty($lists['data'])?[]:$lists['data'];
    }

    /**
     * 获取订单详情
     * @param $order_id
     * @return string|array
     */
    public function getOrderInfo($order_id)
    {
        $path = '/v2/providers/openapi/apis/api/v4/vendors/'.$this->getVendorId().'/'.$order_id.'/ordersheets';
        $data = [];
        $lists = $this->request('GET',$path,$data);
        return empty($lists['data'])?[]:$lists['data'];
    }

    /**
    /v2/providers/openapi/apis/api/v4/vendors/{vendorId}/ordersheets/{shipmentBoxId}
     * 获取订单详情
     * @param $order_id
     * @return string|array
     */
    public function getOrderInfo1($order_id)
    {
        $path = '/v2/providers/openapi/apis/api/v4/vendors/'.$this->getVendorId().'/ordersheets/'.$order_id.'/history';
        $data = [];
        $lists = $this->request('GET',$path,$data);
        var_dump($lists);
        return empty($lists['data'])?[]:current($lists['data']);
    }

    /**
     * 此处 datetime类型 转化 为iso8601 类型
     * @param $date
     * @return false|string
     */
    public static function toDate($date){
        $time = strtotime($date);
        return date("Y-m-d\TH:i:s\Z",$time);
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
        $add_time = strtotime($order['orderedAt']);

        $relation_no = $order['orderId'];
        $exist = Order::find()->where(['relation_no' => $relation_no])->one();
        if ($exist) {
            return false;
        }

        //多个是列表的方式拆分的
        $order_lists = $this->getOrderInfo($relation_no);

        $customer = $order['receiver'];
        $shipping_address = $customer['addr1'];
        $address_arr = explode(' ',$shipping_address);

        //$phone =  empty($customer['safeNumber'])?'0000':(string)$customer['safeNumber'];
        $phone = $order['overseaShippingInfoDto']['ordererPhoneNumber'];

        $integrated_logistics = Order::INTEGRATED_LOGISTICS_NO;

        $country = 'KR';
        $data = [
            'create_way' => Order::CREATE_WAY_SYSTEM,
            'shop_id' => $shop_v['id'],
            'source' => $shop_v['platform_type'],
            'relation_no' => (string)$relation_no,
            'date' => $add_time,
            'user_no' => (string)$order['overseaShippingInfoDto']['personalCustomsClearanceCode'],
            'country' => $country,
            'city' => empty($address_arr[1])?'':$address_arr[1],
            'area' => empty($address_arr[0])?'':$address_arr[0],
            'company_name' => '',
            'buyer_name' => $customer['name'],
            'buyer_phone' => $phone,
            'postcode' => (string)$customer['postCode'],
            'email' => '',
            'address' => $customer['addr1'] .' '. $customer['addr2'],
            'remarks' => '',
            'add_time' => $add_time,
        ];

        $goods = [];
        $logistics_channels_name = '';
        foreach ($order_lists as $order_v) {
            foreach ($order_v['orderItems'] as $v) {
                $goods_no = $v['externalVendorSkuCode'];
                $where = [
                    'shop_id' => $shop_v['id'],
                    'platform_sku_no' => $goods_no
                ];
                $platform_sku_no = GoodsShop::find()->where($where)->select('cgoods_no')->scalar();
                if (empty($platform_sku_no)) {
                    $goods_info = GoodsChild::find()->where(['cgoods_no' => $goods_no])->one();
                    $sku_no = $goods_no;
                    if (!empty($goods_info)) {
                        $sku_no = $goods_info['sku_no'];
                    }
                    $goods_map = (new BuyGoodsService())->getGoodsToSkuCountry($sku_no, $country, Base::PLATFORM_1688, 1);
                } else {
                    $goods_map = (new BuyGoodsService())->getGoodsToSkuCountry($platform_sku_no, $country, Base::PLATFORM_1688, 2);
                }
                $goods_data = $this->dealOrderGoods($goods_map);
                $goods_data = array_merge($goods_data, [
                    'goods_name' => $v['sellerProductName'],
                    'goods_num' => $v['shippingCount'],
                    'goods_income_price' => $v['salesPrice'],
                ]);
                $goods[] = $goods_data;
            }
            if (!empty($order_v['shipmentType']) && $order_v['shipmentType'] != 'THIRD_PARTY') {
                $integrated_logistics = Order::INTEGRATED_LOGISTICS_YES;
                $logistics_channels_name = $order_v['shipmentType'];
            }
        }
        
        $data['integrated_logistics'] = $integrated_logistics;
        $data['logistics_channels_name'] = $logistics_channels_name;
        return [
            'order' => $data,
            'goods' => $goods,
        ];
    }

    /**
     * 确认订单
     * @param $order_id
     * @return string
     */
    public function getConfirmOrder($order_id)
    {
        $order = $this->getOrderInfo($order_id);
        $shipment_box_id = ArrayHelper::getColumn($order,'shipmentBoxId');
        //$shipment_box_id = [$order['shipmentBoxId']];
        $vendor_id = $this->getVendorId();
        $path = '/v2/providers/openapi/apis/api/v4/vendors/' . $vendor_id . '/ordersheets/acknowledgement';
        $data = [
            "vendorId" => $vendor_id,
            'shipmentBoxIds' => $shipment_box_id
        ];
        $result = $this->request('PUT', $path, $data);
        return !empty($result['data']) && $result['data']['responseCode'] == 0 ? true : false;
    }

    /**
     * 打印
     * @param $order
     * @return string|array
     */
    public function doPrint($order, $is_show = false)
    {
        $vendor_id = $this->getVendorId();
        $path = '/v2/providers/openapi/apis/api/v1/vendors/' . $vendor_id . '/label/printOrderLabel';
        $data = [
            "templateName" => 'SIXTY_FORTY_MM',
            'orderIdList' => [$order['relation_no']],
            'quantity' => 1,
        ];
        $result = $this->request('POST', $path, $data);
        $response = base64_decode($result['data']);
        if ($is_show === 2) {
            return true;
        }

        if ($is_show) {
            header("Content-type: application/pdf");
            echo $response;
            exit();
        }
        $pdfUrl = CommonUtil::savePDF($response);
        return $pdfUrl['pdf_url'];
    }

    /**
     * 发送国内物流
     * @param string $order_id 订单号
     * @return string
     */
    public function sendFirstLogistics($order_id)
    {
        $order = Order::find()->where(['relation_no' => $order_id])->asArray()->one();
        if (empty($order['first_track_no'])) {
            return false;
        }

        $order = $this->getOrderInfo($order_id);
        $shipment_box_id = ArrayHelper::getColumn($order,'shipmentBoxId');

        $vendor_id = $this->getVendorId();
        $path = ' /v2/providers/openapi/apis/api/v4/vendors/' . $vendor_id . '/orders/global/invoices';
        $data = [
            'vendorId' => $vendor_id,
            "orderSheetInvoiceApplyDtos" => [
                [
                    'orderId' => $order['relation_no'],
                    'shipmentBoxId' => $shipment_box_id,
                    'deliveryCompanyCode' => 'uc_express',//DIRECT 直接配送
                    'invoiceNumber' => $order['first_track_no']
                ]
            ],
        ];
        $result = $this->request('POST', $path, $data);
        //var_dump(json_encode($result));
        CommonUtil::logs('nocnoc result order_id:' . $order['order_id'] . ' shop_id:' . $this->shop['id'] .' data:' . json_encode($data,JSON_UNESCAPED_UNICODE) . ' result:' . json_encode($result,JSON_UNESCAPED_UNICODE), 'order_nocnoc');
        return isset($result['data']) && isset($result['data']['responseCode']) && $result['data']['responseCode'] == 0;
    }

    /**
     * 添加商品
     * @param $goods
     * @return bool
     * @throws \Exception
     */
    public function addGoods($goods)
    {
        //商品是否已经添加过，针对多变体
        $shop_id = $this->shop['id'];
        static $goods_no_arr = [];
        if (!empty($goods_no_arr[$shop_id]) && in_array($goods['goods_no'], $goods_no_arr[$shop_id])) {
            return true;
        }
        $goods_no_arr[$shop_id][] = $goods['goods_no'];

        $shop = $this->shop;
        $goods_coupang = GoodsCoupang::find()->where(['goods_no' => $goods['goods_no']])->one();
        $goods_shop = GoodsShop::find()->where(['id' => $this->goods_event['goods_shop_id'], 'shop_id' => $shop['id']])->one();
        if(empty($goods_shop) || $goods_shop['status'] == GoodsShop::STATUS_DELETE) {
            return false;
        }

        if (!empty($goods_shop['platform_goods_opc'])) {
            return true;
        }

        $data = $this->dealGoodsInfo($goods,$goods_coupang,$goods_shop);
        if(!$data){
            return false;
        }
        CommonUtil::logs('coupang request goods_no:' . $goods['goods_no'] . ' shop_id:' . $shop['id'] . ' data:' . json_encode($data,JSON_UNESCAPED_UNICODE) , 'add_products_coupang');
        //echo  json_encode($data,JSON_UNESCAPED_UNICODE);

        $path = '/v2/providers/seller_api/apis/api/v1/marketplace/seller-products';
        $result = $this->request('POST', $path, $data);

        CommonUtil::logs('coupang result goods_no:' . $goods['goods_no'] . ' shop_id:' . $shop['id'] . ' data:' . json_encode($data,JSON_UNESCAPED_UNICODE) . ' result:' . json_encode($result,JSON_UNESCAPED_UNICODE), 'add_products_coupang');
        if (empty($result) || empty($result['code']) || $result['code'] != 'SUCCESS' || empty($result['data'])) {
            return false;
        }

        $platform_goods_opc = (string)$result['data'];
        if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
            GoodsShop::updateAll(['platform_goods_opc' => $platform_goods_opc], ['goods_no' => $goods['goods_no'], 'shop_id' => $shop['id'],'platform_goods_opc' =>'','other_tag'=>$goods_shop['other_tag']]);
        } else {
            GoodsShop::updateAll(['platform_goods_opc' => $platform_goods_opc], [$this->goods_event['goods_shop_id'], 'shop_id' => $shop['id']]);
        }

        return true;
    }

    /**
     * 更新商品
     * @param $goods
     * @return bool
     * @throws \Exception
     */
    public function updateGoods($goods)
    {
        //商品是否已经添加过，针对多变体
        $shop_id = $this->shop['id'];
        static $goods_no_arr = [];
        if (!empty($goods_no_arr[$shop_id]) && in_array($goods['goods_no'], $goods_no_arr[$shop_id])) {
            return true;
        }
        $goods_no_arr[$shop_id][] = $goods['goods_no'];

        $shop = $this->shop;
        $goods_coupang = GoodsCoupang::find()->where(['goods_no' => $goods['goods_no']])->one();
        $goods_shop_all = GoodsShop::find()->where(['goods_no' => $goods['goods_no'], 'shop_id' => $this->shop['id']])->all();
        $platform_goods_opc = '';
        $platform_goods_exp_id = '';
        foreach ($goods_shop_all as $goods_shop_v) {
            if (empty($goods_shop_v['platform_goods_url'])) {
                if (!empty($goods_shop_v['platform_sku_no'])) {
                    $sku_no = $goods_shop_v['platform_sku_no'];
                } else {
                    $goods_child = GoodsChild::find()->where(['cgoods_no' => $goods_shop_v['cgoods_no']])->one();
                    $sku_no = $goods_child['sku_no'];
                }

                if (empty($platform_goods_opc) || empty($platform_goods_exp_id)) {
                    $platform_goods_opc = $goods_shop_v['platform_goods_opc'];
                    $platform_goods_exp_id = $goods_shop_v['platform_goods_exp_id'];
                    if (empty($platform_goods_opc) || empty($platform_goods_exp_id)) {
                        $product = $this->getProductsToAsin($sku_no);
                        if (empty($product) || empty($product['sellerProductId'])) {
                            return -1;
                        }
                        $platform_goods_opc = (string)$product['sellerProductId'];
                        $platform_goods_exp_id = (string)$product['productId'];
                        $goods_shop_v['platform_goods_opc'] = $platform_goods_opc;
                        $goods_shop_v['platform_goods_exp_id'] = $platform_goods_exp_id;
                    }
                } else {
                    if(empty($goods_shop_v['platform_goods_opc'])){
                        $goods_shop_v['platform_goods_opc'] = $platform_goods_opc;
                    }
                    if(empty($goods_shop_v['platform_goods_exp_id'])) {
                        $goods_shop_v['platform_goods_exp_id'] = $platform_goods_exp_id;
                    }
                }

                $product = $this->getProductsToId($platform_goods_opc);
                $platform_goods_id = $goods_shop_v['platform_goods_id'];
                $platform_goods_url = '';
                foreach ($product['items'] as $v) {
                    if ($v['externalVendorSku'] == $sku_no) {
                        $platform_goods_id = $v['vendorItemId'];
                        $platform_goods_url = $v['sellerProductItemId'];
                    }
                }

                if (empty($platform_goods_id)) {
                    return -1;
                }

                $goods_shop_v['platform_goods_id'] = (string)$platform_goods_id;
                $goods_shop_v['platform_goods_url'] = (string)$platform_goods_url;
                if(empty($goods_shop_v['platform_goods_exp_id'])) {
                    $goods_shop_v['platform_goods_exp_id'] = (string)$product['productId'];
                }
                $goods_shop_v->save();
            }
        }

        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        if(empty($goods_shop) || $goods_shop['status'] == GoodsShop::STATUS_DELETE) {
            return false;
        }

        $data = $this->dealGoodsInfo($goods,$goods_coupang,$goods_shop,true);
        if(!$data){
            return false;
        }
        CommonUtil::logs('coupang request goods_no:' . $goods['goods_no'] . ' shop_id:' . $shop['id'] . ' data:' . json_encode($data,JSON_UNESCAPED_UNICODE) , 'update_products_coupang');
        //echo  json_encode($data,JSON_UNESCAPED_UNICODE);

        $path = '/v2/providers/seller_api/apis/api/v1/marketplace/seller-products';
        $result = $this->request('PUT', $path, $data);

        CommonUtil::logs('coupang result goods_no:' . $goods['goods_no'] . ' shop_id:' . $shop['id'] . ' data:' . json_encode($data,JSON_UNESCAPED_UNICODE) . ' result:' . json_encode($result,JSON_UNESCAPED_UNICODE), 'update_products_coupang');
        if (empty($result) || empty($result['code']) || $result['code'] != 'SUCCESS' || empty($result['data'])) {
            return false;
        }

        /*$platform_goods_opc = (string)$result['data'];
        if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
            GoodsShop::updateAll(['platform_goods_opc' => $platform_goods_opc], ['goods_no' => $goods['goods_no'], 'shop_id' => $shop['id']]);
        } else {
            GoodsShop::updateAll(['platform_goods_opc' => $platform_goods_opc], ['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']]);
        }*/

        return true;
    }

    /**
     * 获取商品属性
     * @param $goods_no
     * @return array|void
     */
    public function getGoodsProperty($goods_no)
    {
        $goods_property = GoodsProperty::find()->where(['goods_no' => $goods_no])->all();
        if (empty($goods_property)) {
            return [];
        }
        $property_ids = ArrayHelper::getColumn($goods_property, 'property_id');
        $property_ids = array_unique($property_ids);
        $property_value_ids = ArrayHelper::getColumn($goods_property, 'property_value_id');
        $property_value_ids = array_unique($property_value_ids);
        $property_ids_lists = PlatformCategoryProperty::find()->where(['platform_type' => Base::PLATFORM_COUPANG, 'property_type' => 1, 'property_id' => $property_ids])->indexBy('property_id')->asArray()->all();
        $property_value_ids_lists = PlatformCategoryProperty::find()->where(['platform_type' => Base::PLATFORM_COUPANG, 'property_type' => 2, 'property_id' => $property_value_ids])->indexBy('property_id')->asArray()->all();
        $property = [];
        foreach ($goods_property as $property_v) {
            if (empty($property_ids_lists[$property_v['property_id']])) {
                continue;
            }
            $p_value = null;
            $p_name = trim($property_ids_lists[$property_v['property_id']]['name']);
            if ($p_name == '사이즈') {//尺寸
                $p_value = $property_v['property_value'] . ' cm';
            }
            if ($p_name == '수량') {//尺寸
                $p_value = $property_v['property_value'];
            }
            if (empty($p_value) && !empty($property_value_ids_lists[$property_v['property_value_id']])) {
                $p_value = $property_value_ids_lists[$property_v['property_value_id']]['name'];
            }
            if (empty($p_value)) {
                continue;
            }
            $property[$p_name][] = $p_value;
        }
        return $property;
    }

    /**
     * 处理商品信息
     * @param $goods
     * @param $goods_coupang
     * @param $goods_shop
     * @return array
     * @throws \Exception
     */
    public function dealGoodsInfo($goods, $goods_coupang, $goods_shop,$is_update = false)
    {
        $goods_short_name = '';
        /*if (!empty($goods_shop['keywords_index'])) {
            $goods_short_name = (new GoodsShopService())->getKeywordsTitle($this->platform_type, $goods['goods_no'], $goods_shop['keywords_index'], 100);
        }*/
        $brand = !empty($this->shop['brand_name']) ? $this->shop['brand_name'] : 'Generic';
        //$delivery_type = $this->shop['id'] == 484?2:0;//配送类型 0:卖家配送 1:CGF 2:CGF lite
        $delivery_type = $goods_shop['other_tag'];//配送类型 0:卖家配送 10:CGF 21:CGF lite

        if (empty($goods_short_name)) {
            $goods_short_name = empty($goods_coupang['goods_short_name']) ? $goods_coupang['goods_name'] : $goods_coupang['goods_short_name'];
            $goods_short_name = str_replace(['（', '）'], ['(', ')'], $goods_short_name);
            $goods_short_name = $brand . ' ' . $goods_short_name;
            $goods_short_name = CommonUtil::filterTrademark($goods_short_name);
            $goods_short_name = CommonUtil::usubstr($goods_short_name, 100);
        }

        $sku_no = !empty($goods_shop['platform_sku_no']) ? $goods_shop['platform_sku_no'] : $goods['sku_no'];
        $base_goods = $goods;

        $images = [];
        $image = json_decode($goods['goods_img'], true);
        $i = 0;
        foreach ($image as $v) {
            if ($i > 5) {
                break;
            }
            $i++;
            $images[] = $v['img'] . '?imageMogr2/thumbnail/!700x700r';//图片不小于500;
        }
        /*$category_id = trim($goods_coupang['o_category_name']);
        if (empty($category_id)){
            return false;
        }
        $category = Category::find()->where(['id'=>$goods['category_id']])->one();*/

        if (!in_array($goods['status'], [Goods::GOODS_STATUS_VALID, Goods::GOODS_STATUS_WAIT_MATCH])) {
            return false;
        }
        $goods_content = (new CoupangPlatform())->dealContent([
            'goods_name' => $goods_coupang['goods_name'],
            'goods_img' => $goods['goods_img'],
            'goods_content' => $goods_coupang['goods_content'],
        ]);

        $all_goods = [];
        if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {//多变体
            $goods_childs = GoodsChild::find()->where(['goods_no' => $goods['goods_no']])->asArray()->all();
            $goods_shops = GoodsShop::find()->where(['goods_no' => $goods['goods_no'], 'shop_id' => $this->shop['id']])->indexBy('cgoods_no')->asArray()->all();
            foreach ($goods_childs as $goods_child_v) {
                if (empty($goods_shops[$goods_child_v['cgoods_no']])) {
                    continue;
                }
                $goods_shop_v = $goods_shops[$goods_child_v['cgoods_no']];
                $goods['price'] = $goods_shop_v['price'];
                $goods['ean'] = $goods_shop_v['ean'];
                $goods['colour'] = empty($goods_child_v['colour']) ? $goods['colour'] : $goods_child_v['colour'];
                $goods['csize'] = $goods_child_v['size'];
                $goods['size'] = $goods_child_v['package_size'];
                $goods['real_weight'] = $goods_child_v['real_weight'];
                $goods['weight'] = $goods_child_v['weight'];
                $goods['sku_no'] = !empty($goods_shop_v['platform_sku_no']) ? $goods_shop_v['platform_sku_no'] : $goods_child_v['sku_no'];
                $main_image = $goods_child_v['goods_img'] . '?imageMogr2/thumbnail/!700x700r';
                $images[0] = $main_image;
                $goods['images'] = $images;
                if ($is_update) {
                    $goods['platform_goods_id'] = $goods_shop_v['platform_goods_id'];
                    $goods['platform_goods_opc'] = $goods_shop_v['platform_goods_opc'];
                    $goods['platform_goods_url'] = $goods_shop_v['platform_goods_url'];
                }
                $all_goods[] = $goods;
            }
            CommonUtil::logs('coupang request goods_no:' . $goods['goods_no'] . ' data:' . json_encode($all_goods, JSON_UNESCAPED_UNICODE), 'add_products_coupang');
        } else {
            $goods['sku_no'] = $sku_no;
            $goods['price'] = $goods_shop['price'];
            $goods['ean'] = $goods_shop['ean'];
            $goods['images'] = $images;
            if ($is_update) {
                $goods['platform_goods_id'] = $goods_shop['platform_goods_id'];
                $goods['platform_goods_opc'] = $goods_shop['platform_goods_opc'];
                $goods['platform_goods_url'] = $goods_shop['platform_goods_url'];
            }
            $all_goods = [$goods];
        }

        $category_id = trim($goods_coupang['o_category_name']);
        $category_parameters = $this->getCategoryProductParameters($category_id);
        $category_attr = $category_parameters['attributes'];

        $category_type = 0;
        if ($this->shop['id'] == 484) {
            $category_type = 1;//家具类目
        }


        $category_notice = [];
        foreach ($category_parameters['noticeCategories'] as $category_notice_v) {
            if ($category_notice_v['noticeCategoryName'] == '기타 재화') {
                $category_notice = $category_notice_v;
            }

            if ($category_type == 1) {//家具类目
                if ($category_notice_v['noticeCategoryName'] == '가구(침대 / 소파 / 싱크대 / DIY제품 등)') {
                    $category_notice = $category_notice_v;
                    break;
                }
            }
        }
        if (empty($category_notice)) {
            $category_notice = current($category_parameters['noticeCategories']);
        }

        $search_tags = [];
        if ($category_type == 1) {//关键字
            $search_tags = ['벽 램프', '거실 벽 램프', '식당 벽 램프', '복고풍 벽 램프', '복도 램프', '샹들리에',
                '천장 조명', '거실 조명', '다이닝 룸 조명', '빈티지 샹들리에',
                '테이블 램프', '탁상용 램프', '팬 램프', '실외 램프', '타이논 램프'];

        }
        $colour_map = CoupangPlatform::$colour_map;

        $items = [];
        foreach ($all_goods as $goods_v) {
            $price = $goods_v['price'];

            $item_name = '';
            $params = [];
            $notices = [];

            $goods_property = $this->getGoodsProperty($goods_v['goods_no']);

            $exist_param = false;
            foreach ($category_attr as $attr_v) {
                //必填属性
                if ($attr_v['required'] == 'OPTIONAL' && !in_array($attr_v['attributeTypeName'], ['색상', '사이즈', 'size'])) {
                    continue;
                }
                if (!empty($goods_property[$attr_v['attributeTypeName']])) {
                    continue;
                }
                $exist_param = true;
            }

            $i = 0;
            $has_size = false;
            $colour_val = $goods_v['colour'];
            $colour = empty($colour_map[$goods_v['colour']]) ? $colour_val : $colour_map[$goods_v['colour']];

            //属性处理
            foreach ($category_attr as $attr_v) {
                $i++;
                if ($exist_param || $i != 1) {//当没有必填时候 取第一个
                    //必填属性
                    if ($attr_v['required'] == 'OPTIONAL' && !in_array($attr_v['attributeTypeName'], ['색상', '사이즈', 'size']) && empty($goods_property[$attr_v['attributeTypeName']])) {
                        continue;
                    }
                }

                $params_info = [];
                $params_info['attributeTypeName'] = $attr_v['attributeTypeName'];
                $params_info['attributeValueName'] = '1';

                //属性值
                if (!empty($goods_property[$attr_v['attributeTypeName']])) {
                    $params_info['attributeValueName'] = implode(',', $goods_property[$attr_v['attributeTypeName']]);
                }

                //颜色处理
                if ($attr_v['attributeTypeName'] == '색상') {
                    $params_info['attributeValueName'] = $colour;
                }

                //尺寸
                if ($attr_v['attributeTypeName'] == '사이즈' && !$has_size) {
                    if (empty($goods_v['csize'])) {
                        continue;
                    }
                    $has_size = true;
                    $params_info['attributeValueName'] = $goods_v['csize'];
                }

                //尺寸
                if ($attr_v['attributeTypeName'] == 'size' && !$has_size) {
                    if (empty($goods_v['csize'])) {
                        continue;
                    }
                    $has_size = true;
                    $params_info['attributeValueName'] = $goods_v['csize'];
                }
                $params[] = $params_info;
            }

            if (!empty($category_notice['noticeCategoryDetailNames'])) {
                foreach ($category_notice['noticeCategoryDetailNames'] as $notice_v) {
                    $content = '상품 상세페이지 참조';
                    if ($category_type == 1) {
                        if ($notice_v['noticeCategoryDetailName'] == '품명') { // 品名类目最后一级
                            continue;
                        }
                        if ($notice_v['noticeCategoryDetailName'] == 'KC 인증정보') {
                            $content = '가지고 있지 않다';
                        }
                        if ($notice_v['noticeCategoryDetailName'] == '색상') { // 颜色
                            $content = $colour;
                        }
                        if (in_array($notice_v['noticeCategoryDetailName'], ['구성품', '주요 소재'])) {//材质
                            $content = '철(금속)';
                        }
                        if ($notice_v['noticeCategoryDetailName'] == '제조자(수입자)') {//进口商
                            $content = '산린두';
                        }
                        if ($notice_v['noticeCategoryDetailName'] == '제조국') {//制造国
                            $content = '중국';
                        }
                        if ($notice_v['noticeCategoryDetailName'] == '크기') {//包装尺寸
                            if (!empty($goods_v['size'])) {
                                $content = $goods_v['size'] . ' cm';
                            }
                        }
                        if ($notice_v['noticeCategoryDetailName'] == '배송/설치비용') {//配送费
                            $content = '가지고 있지 않다';
                        }
                        if ($notice_v['noticeCategoryDetailName'] == '재공급(리퍼브) 가구의 경우 재공급 사유 및 하자 부위에 관한 정보') {
                            continue;//예시 : 견본주택 전시상품으로 식탁 상판에 미세한 흠집 있음 등
                        }
                        if ($notice_v['noticeCategoryDetailName'] == '품질보증기준') {
                            continue;
                        }
                        if ($notice_v['noticeCategoryDetailName'] == 'A/S 책임자와 전화번호') {
                            continue;
                        }
                    }
                    $notices[] = [
                        'noticeCategoryName' => $category_notice['noticeCategoryName'],
                        'noticeCategoryDetailName' => $notice_v['noticeCategoryDetailName'],
                        'content' => $content,
                    ];
                }
            }

            if ($goods_v['goods_type'] == Goods::GOODS_TYPE_MULTI) {
                if (!empty($goods_v['colour'])) {
                    $item_name .= $goods_v['colour'] . ' ';
                }

                if (!empty($goods_v['csize'])) {
                    $item_name .= $goods_v['csize'];
                }
                $item_name = trim($item_name);
            } else {
                $item_name = $goods_v['colour'];
            }

            //图片
            $images = [];
            $img_i = 0;
            foreach ($goods_v['images'] as $img_v) {
                $i_type = $img_i == 0 ? 'REPRESENTATION' : 'DETAIL';
                $images[] = [
                    'imageOrder' => $img_i,
                    'imageType' => $i_type,
                    'vendorPath' => $img_v,
                ];
                $img_i++;
            }
            $price = ceil($price / 10) * 10;
            $item = [
                'itemName' => $item_name,
                'originalPrice' => (int)$price * 2,
                'salePrice' => (int)$price,
                'maximumBuyForPerson' => 0,
                'maximumBuyForPersonPeriod' => 1,
                'unitCount' => 0,
                'adultOnly' => "EVERYONE",
                'parallelImported' => 'NOT_PARALLEL_IMPORTED',
                'overseasPurchased' => 'OVERSEAS_PURCHASED',
                'taxType' => 'TAX',
                'pccNeeded' => true,
                'externalVendorSku' => $goods_v['sku_no'],
                'barcode' => '',
                'modelNo' => $goods_v['ean'],
                'searchTags' => $search_tags,
                'images' => $images,
                'notices' => $notices,
                'attributes' => $params,
                'contents' => [
                    [
                        'contentsType' => 'HTML',
                        'contentDetails' => [
                            [
                                'content' => $goods_content,
                                'detailType' => 'TEXT',
                            ]
                        ],
                    ]
                ],
                'offerCondition' => 'NEW',
                'offerDescription' => '',
            ];

            $item['maximumBuyCount'] = 1000; //库存

            $sku_info = [
                'fragile' => false,
                'hazardous' => false,
                'heatSensitive' => false,
                'quantityPerBox' => 1,
            ];
            $size = GoodsService::getSizeArr($goods['size']);
            $l = !empty($size['size_l']) && $size['size_l'] > 1 ? (int)$size['size_l'] : 0;
            $h = !empty($size['size_h']) && $size['size_h'] > 1 ? (int)$size['size_h'] : 0;
            $w = !empty($size['size_w']) && $size['size_w'] > 1 ? (int)$size['size_w'] : 0;
            $weight = $goods['real_weight'] > 0 ? $goods['real_weight'] : $goods['weight'];
            if (!empty($l)) {
                $sku_info['length'] = $l * 10;
            }
            if (!empty($w)) {
                $sku_info['width'] = $w * 10;
            }
            if (!empty($h)) {
                $sku_info['height'] = $h * 10;
            }
            if (!empty($weight)) {
                $weight = ceil($weight * 1000);
                $weight = $weight < 100 ? 100 : $weight;
                $sku_info['weight'] = $weight;
                $sku_info['netWeight'] = ceil($weight * 0.95);
            }
            $category = Category::find()->where(['id' => $base_goods['category_id']])->one();
            if ($delivery_type == 0) {
                $item['taxType'] = 'FREE';
                $item['pccNeeded'] = false;
                $item['emptyBarcode'] = true;
                $item['emptyBarcodeReason'] = "SMALL_PRODUCT";
                $item['outboundShippingTimeDay'] = 10;//发货时间
            } else if ($delivery_type == GoodsShop::OTHER_TAG_OVERSEAS) {
                $item['barcode'] = $goods_v['ean'];
                $item['emptyBarcode'] = false;
                $item['emptyBarcodeReason'] = "";
                $item['globalInfo'] = [
                    'manufacturedCountry' => 'China',
                    'battery' => false,
                    'productRegulationInfo' => false,
                    /*'invoiceName' => $category['name'],
                    'productNameEng' => $category['name_en'],
                    'localProductName' => $goods['goods_name_cn'],*/
                ];
                $item['productRegulationInfo'] = false;
                $item['material'] = [
                    'ACETONE_AEROSOLS_WEAPONS' => false,
                    'PERFUMES_OVER_60' => false,
                    'INCLUDE_SYMBOL_FLAMMABLE' => false,
                    'INCLUDE_WORD_FLAMMABLE' => false,
                    'MAGNETS_INCLUDED' => false,
                ];
                $item['skuInfo'] = $sku_info;
                $item['maximumBuyCount'] = 0;
            } else if ($delivery_type == GoodsShop::OTHER_TAG_COUPANG_CGFLIVE) {
                $item['barcode'] = $goods_v['ean'];
                $item['emptyBarcode'] = false;
                $item['emptyBarcodeReason'] = "";
                $item['outboundShippingTimeDay'] = 5;//发货时间
                $item['globalInfo'] = [
                    'manufacturedCountry' => 'China',
                    'battery' => false,
                    'productRegulationInfo' => false,
                    /*'invoiceName' => $category['name'],
                    'productNameEng' => $category['name_en'],
                    'localProductName' => $goods['goods_name_cn'],*/
                ];
                $item['skuInfo'] = $sku_info;
            }

            if ($is_update) {
                $item['sellerProductItemId'] = $goods['platform_goods_url'];
                $item['vendorItemId'] = $goods['platform_goods_id'];
            }
            $items[] = $item;
        }

        $info = [];
        if ($is_update) {
            $info['sellerProductId'] = $goods['platform_goods_opc'];
        }
        $info['displayCategoryCode'] = $category_id;
        $info['sellerProductName'] = $goods_short_name;
        $info['vendorId'] = $this->getVendorId();
        $info['saleStartedAt'] = date("Y-m-d\TH:i:s", time());
        $info['saleEndedAt'] = "2090-01-01T00:00:00";
        $info['displayProductName'] = $brand . ' ' . $goods_short_name;
        $info['brand'] = $brand;
        //$info['generalProductName'] = '';
        //$info['productGroup'] = '';
        $return_shipping = $this->getReturnShipping();
        $info['returnCenterCode'] = $return_shipping['returnCenterCode'];
        $info['returnChargeName'] = $return_shipping['shippingPlaceName'];
        $place_addresses = current($return_shipping['placeAddresses']);
        $info['companyContactNumber'] = $place_addresses['companyContactNumber'];
        $info['returnZipCode'] = $place_addresses['returnZipCode'];
        $info['returnAddress'] = $place_addresses['returnAddress'];
        $info['returnAddressDetail'] = $place_addresses['returnAddressDetail'];
        $info['returnCharge'] = 3500;
        $info['manufacture'] = $brand;
        if ($delivery_type == 0) {
            $info['deliveryMethod'] = 'AGENT_BUY';
            $info['deliveryCompanyCode'] = 'DIRECT';
            $info['deliveryChargeType'] = 'FREE';
            $info['deliveryCharge'] = 0;
            $info['freeShipOverAmount'] = 0;
            $info['deliveryChargeOnReturn'] = 0;
            $info['remoteAreaDeliverable'] = 'N';
            $info['unionDeliveryType'] = 'NOT_UNION_DELIVERY';
            $shipping_place = $this->getShippingPlace();
            $info['outboundShippingPlaceCode'] = $shipping_place['outboundShippingPlaceCode'];
        } else if ($delivery_type == GoodsShop::OTHER_TAG_OVERSEAS) {
            $info['registrationType'] = 'FUJI';
            $info['metaData'] = [
                'RETURN_SHIPPING_OPTION' => 'COUPANG'
            ];
        } else if ($delivery_type == GoodsShop::OTHER_TAG_COUPANG_CGFLIVE) {
            $info['registrationType'] = 'FUJI_CROSSDOCK';
            $info['metaData'] = [
                'RETURN_SHIPPING_OPTION' => 'COUPANG'
            ];
        }
        $info['vendorUserId'] = $this->getUserId();
        $info['requested'] = true;
        $info['items'] = $items;
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
        $goods_shop = $this->repairGoodsId($goods);
        if(empty($goods_shop) || empty($goods_shop['platform_goods_id'])){
            return -1;
        }

        $path = '/v2/providers/seller_api/apis/api/v1/marketplace/vendor-items/'.$goods_shop['platform_goods_id'].'/quantities/'. ($stock?1000:0);
        $data = [];
        $result = $this->request('PUT', $path, $data);
        if(!empty($result['code']) && $result['code'] == 'SUCCESS'){
            return 1;
        }
        return 0;
    }

    /**
     * 修复商品id
     * @param $goods
     * @return int
     */
    public function repairGoodsId($goods){
        $shop = $this->shop;
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        if(empty($goods_shop['platform_goods_id'])) {
            $sku_no = !empty($goods_shop['platform_sku_no']) ? $goods_shop['platform_sku_no'] : $goods['sku_no'];
            $platform_goods_opc = $goods_shop['platform_goods_opc'];
            if(empty($platform_goods_opc) || empty($goods_shop['platform_goods_exp_id'])) {
                $product = $this->getProductsToAsin($sku_no);
                if (empty($product) || empty($product['sellerProductId'])) {
                    return -1;
                }
                $platform_goods_opc =  $product['sellerProductId'];
                $goods_shop->platform_goods_opc = (string)$product['sellerProductId'];
                $goods_shop->platform_goods_exp_id = (string)$product['productId'];
            }

            $product = $this->getProductsToId($platform_goods_opc);
            $platform_goods_id = '';
            $platform_goods_url = '';
            foreach ($product['items'] as $v) {
                if($v['externalVendorSku'] == $sku_no) {
                    $platform_goods_id = $v['vendorItemId'];
                    $platform_goods_url = $v['sellerProductItemId'];
                }
            }

            if(empty($platform_goods_id)) {
                return false;
            }
            $goods_shop->platform_goods_url = (string)$platform_goods_url;
            $goods_shop->platform_goods_id = (string)$platform_goods_id;
            if(empty($goods_shop['platform_goods_exp_id'])) {
                $goods_shop->platform_goods_exp_id = (string)$product['productId'];
            }
            $goods_shop->save();
        }
        return $goods_shop;
    }

    /**
     * 更新价格
     * @param $goods
     * @param $price
     * @return int
     */
    public function updatePrice($goods,$price)
    {
        $goods_shop = $this->repairGoodsId($goods);
        if(empty($goods_shop) || empty($goods_shop['platform_goods_id'])){
            return -1;
        }

        $price = ceil($price / 10) * 10;
        $path = '/v2/providers/seller_api/apis/api/v1/marketplace/vendor-items/'.$goods_shop['platform_goods_id'].'/prices/'. (int)$price;
        $data = [
            'forceSalePriceUpdate' => true
        ];
        $result = $this->request('PUT', $path, $data);
        if(!empty($result['code']) && $result['code'] == 'SUCCESS'){
            return 1;
        }
        return 0;
    }

    /**
     * 删除商品
     * @param $goods_shop
     * @return bool|int
     * @throws \Exception
     * @throws \Throwable
     */
    public function delGoods($goods_shop)
    {
        $shop_id = $this->shop['id'];
        static $goods_no_arr = [];
        if (!empty($goods_no_arr[$shop_id]) && in_array($goods_shop['goods_no'], $goods_no_arr[$shop_id])) {
            return true;
        }
        $goods_no_arr[$shop_id][] = $goods_shop['goods_no'];

        $goods_shop_all = GoodsShop::find()->where(['goods_no' => $goods_shop['goods_no'], 'shop_id' => $this->shop['id']])->all();
        $platform_goods_opc = '';
        $all_del = true;
        foreach ($goods_shop_all as $goods_shop_v) {
            if ($goods_shop_v['status'] != GoodsShop::STATUS_DELETE) {
                $all_del = false;
            }

            $platform_goods_id = $goods_shop_v['platform_goods_id'];
            if (!empty($goods_shop_v['platform_sku_no'])) {
                $sku_no = $goods_shop_v['platform_sku_no'];
            } else {
                $goods = GoodsChild::find()->where(['cgoods_no' => $goods_shop_v['cgoods_no']])->one();
                $sku_no = $goods['sku_no'];
            }

            if (empty($platform_goods_opc)) {
                $platform_goods_opc = $goods_shop_v['platform_goods_opc'];
                if (empty($platform_goods_opc)) {
                    $product = $this->getProductsToAsin($sku_no);
                    if (empty($product) || empty($product['sellerProductId'])) {
                        return -1;
                    }
                    $platform_goods_opc = $product['sellerProductId'];
                }
            }

            if (empty($goods_shop_v['platform_goods_id'])) {
                $product = $this->getProductsToId($platform_goods_opc);
                foreach ($product['items'] as $v) {
                    if ($v['externalVendorSku'] == $sku_no) {
                        $platform_goods_id = $v['vendorItemId'];
                    }
                }

                if (empty($platform_goods_id)) {
                    return -1;
                }
            }

            $path = '/v2/providers/seller_api/apis/api/v1/marketplace/vendor-items/' . $platform_goods_id . '/sales/stop';
            $data = [];
            $result = $this->request('PUT', $path, $data);
            CommonUtil::logs('coupang 停止销售 result goods_no:' . $goods_shop['goods_no'] . ' shop_id:' . $this->shop['id'] . ' path:'.$path. ' data:' . json_encode($data,JSON_UNESCAPED_UNICODE) . ' result:' . json_encode($result,JSON_UNESCAPED_UNICODE), 'del_coupang');
            if (!empty($result['code']) && $result['code'] == 'SUCCESS') {
                $goods_shop_v->delete();
            } else {
                return false;
            }
        }

        if ($all_del) {
            $path = '/v2/providers/seller_api/apis/api/v1/marketplace/seller-products/' . $platform_goods_opc;
            $data = [];
            $result = $this->request('DELETE', $path, $data);
            CommonUtil::logs('coupang 删除 result goods_no:' . $goods_shop['goods_no'] . ' shop_id:' . $this->shop['id'] . ' path:'.$path.' data:' . json_encode($data,JSON_UNESCAPED_UNICODE) . ' result:' . json_encode($result,JSON_UNESCAPED_UNICODE), 'del_coupang');
            if (!empty($result['code']) && $result['code'] == 'SUCCESS') {
                return true;
            }
            return false;
        }
        return 2;
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
        if (empty($arrival_time)) {
            $arrival_time = strtotime("+30 day", strtotime(date('Y-m-d')));
        } else {
            $arrival_time = $arrival_time + 2 * 60 * 60 * 24;
        }
        $arrival_time = date('Y-m-d', $arrival_time);

        $order_lists = $this->getOrderInfo($order_id);
        //$shipment_box_id = $order['shipmentBoxId'];
        $vendor_id = $this->getVendorId();
        $path = '/v2/providers/openapi/apis/api/v4/vendors/'.$vendor_id.'/orders/invoices';

        $delivery_company_code = $carrier_code;
        if($carrier_code == 'Yanwen') {
            $order_info = Order::find()->where(['relation_no' => $order_id])->asArray()->one();
            if ($order_info['logistics_channels_id']) {
                $delivery_company_code = 'EMS';
                $shipping_method = TransportService::getShippingMethodInfo($order_info['logistics_channels_id']);
                if (in_array($shipping_method['shipping_method_code'], [1047, 1048])) { //Coupang燕文专线追踪
                    $delivery_company_code = 'EPOST';
                }
                if (in_array($shipping_method['shipping_method_code'], [481, 484,363, 812, 437, 456])) { //中邮深圳线下E邮宝
                    $delivery_company_code = 'EMS';
                }
                if (in_array($shipping_method['shipping_method_code'], [140])) { //燕文专线追踪普货/特货、燕邮宝特货
                    $delivery_company_code = 'CJGLS';
                }
            }
        }

        $order_data = [];
        foreach ($order_lists as $order) {
            foreach ($order['orderItems'] as $o_v) {
                $order_data[] = [
                    'shipmentBoxId' => $order['shipmentBoxId'],
                    'orderId' => $order['orderId'],
                    'vendorItemId' => $o_v['vendorItemId'],
                    'deliveryCompanyCode' => $delivery_company_code,//EPOST EMS
                    'invoiceNumber' => $tracking_number,
                    'splitShipping' => false,
                    'preSplitShipped' => false,
                    'estimatedShippingDate' => ""
                ];
                /*[
                    'shipmentBoxId' => $shipment_box_id,
                    'invoiceNumber' => $tracking_number,
                    'deliveryCompanyCode' => 'YANWEN',
                ],*/
            }
        }

        $data = [
            "vendorId" => $vendor_id,
            "orderSheetInvoiceApplyDtos" => $order_data
        ];

        $result = $this->request('POST', $path, $data);
        if(!empty($result['data']) && $result['data']['responseMessage'] == 'SUCCESS') {
            return true;
        }
        CommonUtil::logs('coupang request order_no:' . $order_id . ' shop_id:' . $this->shop['id'] . ' data:' . json_encode($data,JSON_UNESCAPED_UNICODE) .' result：'.json_encode($result,JSON_UNESCAPED_UNICODE) , 'order_send');
        return false;

        /**
        </option><option data-v-27358c14="" value="TWOFASTEXP">
        2FastsExpress
        </option><option data-v-27358c14="" value="ACEEXP">
        ACE Express
        </option><option data-v-27358c14="" value="ACIEXPRESS">
        ACI Express
        </option><option data-v-27358c14="" value="BGF">
        BGF포스트
        </option><option data-v-27358c14="" value="CJGLS">
        CJ 대한통운
        </option><option data-v-27358c14="" value="KOREXG">
        CJ대한통운특송
        </option><option data-v-27358c14="" value="CJWATER">
        CJ생수
        </option><option data-v-27358c14="" value="WINION">
        CLS
        </option><option data-v-27358c14="" value="WINION2">
        CLS
        </option><option data-v-27358c14="" value="CVS">
        CVS택배
        </option><option data-v-27358c14="" value="EFS">
        EFS
        </option><option data-v-27358c14="" value="GSIEXPRESS">
        GSI익스프레스
        </option><option data-v-27358c14="" value="GSMNTON">
        GSM NtoN
        </option><option data-v-27358c14="" value="GTSLOGIS">
        GTS로지스
        </option><option data-v-27358c14="" value="INNOGIS">
        GTX로지스
        </option><option data-v-27358c14="" value="JNET">
        J-NET
        </option><option data-v-27358c14="" value="KGLNET">
        KGL네트웍스
        </option><option data-v-27358c14="" value="LGE">
        LG전자
        </option><option data-v-27358c14="" value="LTL">
        LTL
        </option><option data-v-27358c14="" value="LINEEXPRESS">
        LineExpress
        </option><option data-v-27358c14="" value="NEXEN">
        NEXEN직배송
        </option><option data-v-27358c14="" value="NKLS">
        NK로지솔루션
        </option><option data-v-27358c14="" value="OVERSEA">
        OVERSEA
        </option><option data-v-27358c14="" value="SBGLS">
        SBGLS
        </option><option data-v-27358c14="" value="CSLOGIS">
        SC로지스
        </option><option data-v-27358c14="" value="SFC">
        SFC(Santai)
        </option><option data-v-27358c14="" value="SLX">
        SLX택배
        </option><option data-v-27358c14="" value="SAMSUNGSDS">
        Samsung SDS
        </option><option data-v-27358c14="" value="YDH">
        YDH
        </option><option data-v-27358c14="" value="ELIAN">
        elianpost
        </option><option data-v-27358c14="" value="IPARCEL">
        i-parcel
        </option><option data-v-27358c14="" value="YUNDA">
        yunda express
        </option><option data-v-27358c14="" value="KUNYOUNG">
        건영택배
        </option><option data-v-27358c14="" value="KDEXP">
        경동택배
        </option><option data-v-27358c14="" value="KOKUSAI">
        국제익스프레스
        </option><option data-v-27358c14="" value="GOODSTOLUCK">
        굿투럭
        </option><option data-v-27358c14="" value="NHLOGIS">
        농협택배
        </option><option data-v-27358c14="" value="DAESIN">
        대신택배
        </option><option data-v-27358c14="" value="DAEWOON">
        대운글로벌
        </option><option data-v-27358c14="" value="KOREX">
        대한통운
        </option><option data-v-27358c14="" value="DODOFLEX">
        도도플렉스
        </option><option data-v-27358c14="" value="CHAINLOGIS">
        두발히어로(4시간당일택배)
        </option><option data-v-27358c14="" value="KGB">
        로젠택배
        </option><option data-v-27358c14="" value="LOGISPOT">
        로지스팟
        </option><option data-v-27358c14="" value="INTRAS">
        로토스
        </option><option data-v-27358c14="" value="LOTTEGLOBAL">
        롯데글로벌
        </option><option data-v-27358c14="" value="LOTTECHILSUNG">
        롯데칠성
        </option><option data-v-27358c14="" value="HYUNDAI">
        롯데택배
        </option><option data-v-27358c14="" value="BABABA">
        바바바로지스
        </option><option data-v-27358c14="" value="VALEX">
        발렉스
        </option><option data-v-27358c14="" value="SHIPNERGY">
        배송하기좋은날
        </option><option data-v-27358c14="" value="PANTOS">
        범한판토스
        </option><option data-v-27358c14="" value="VROONG">
        부릉
        </option><option data-v-27358c14="" value="BRIDGE">
        브리지로지스
        </option><option data-v-27358c14="" value="SAMSUNGAC">
        삼성에어컨직배송
        </option><option data-v-27358c14="" value="SWGEXP">
        성원글로벌
        </option><option data-v-27358c14="" value="SEBANG">
        세방택배
        </option><option data-v-27358c14="" value="SMARTLOGIS">
        스마트로지스
        </option><option data-v-27358c14="" value="CRLX">
        시알로지텍
        </option><option data-v-27358c14="" value="CWAY">
        씨웨이
        </option><option data-v-27358c14="" value="AJOU">
        아주택배
        </option><option data-v-27358c14="" value="ANYTRACK">
        애니트랙
        </option><option data-v-27358c14="" value="ESTHER">
        에스더쉬핑
        </option><option data-v-27358c14="" value="AIRBOY">
        에어보이익스프레스
        </option><option data-v-27358c14="" value="VENDORPIA">
        에이스물류
        </option><option data-v-27358c14="" value="LSERVICE">
        엘서비스
        </option><option data-v-27358c14="" value="YELLOW">
        옐로우캡
        </option><option data-v-27358c14="" value="ALLTAKOREA">
        올타코리아
        </option><option data-v-27358c14="" value="TPMLOGIS">
        용달이특송
        </option><option data-v-27358c14="" value="YONGMA">
        용마로지스
        </option><option data-v-27358c14="" value="WEVILL">
        우리동네택배
        </option><option data-v-27358c14="" value="EPOST">
        우체국
        </option><option data-v-27358c14="" value="EMS">
        우체국 EMS
        </option><option data-v-27358c14="" value="REGISTPOST">
        우편등기
        </option><option data-v-27358c14="" value="WOONGJI">
        웅지익스프레스
        </option><option data-v-27358c14="" value="WARPEX">
        워펙스
        </option><option data-v-27358c14="" value="WONDERS">
        원더스퀵
        </option><option data-v-27358c14="" value="XINPATEK">
        윈핸드해운상공
        </option><option data-v-27358c14="" value="EUGWATER">
        유진로지스틱스
        </option><option data-v-27358c14="" value="UFREIGHT">
        유프레이트 코리아
        </option><option data-v-27358c14="" value="EUNHA">
        은하쉬핑
        </option><option data-v-27358c14="" value="ETOMARS">
        이투마스
        </option><option data-v-27358c14="" value="ILYANG">
        일양택배
        </option><option data-v-27358c14="" value="GNETWORK">
        자이언트
        </option><option data-v-27358c14="" value="ZENIELSYSTEM">
        제니엘시스템
        </option><option data-v-27358c14="" value="JLOGIST">
        제이로지스트
        </option><option data-v-27358c14="" value="GENIEGO">
        지니고
        </option><option data-v-27358c14="" value="GDAKOREA">
        지디에이코리아
        </option><option data-v-27358c14="" value="CHUNIL">
        천일특송
        </option><option data-v-27358c14="" value="COSHIP">
        캐나다쉬핑
        </option><option data-v-27358c14="" value="QRUN">
        큐런
        </option><option data-v-27358c14="" value="QXPRESS">
        큐익스프레스
        </option><option data-v-27358c14="" value="HEREWEGO">
        탱고앤고
        </option><option data-v-27358c14="" value="TEAMFRESH">
        팀프레시
        </option><option data-v-27358c14="" value="PANASIA">
        판아시아
        </option><option data-v-27358c14="" value="FOREVERPS">
        퍼레버택배
        </option><option data-v-27358c14="" value="FRESHMATES">
        프레시메이트
        </option><option data-v-27358c14="" value="FRESHSOLUTIONS">
        프레시솔루션
        </option><option data-v-27358c14="" value="PINGPONG">
        핑퐁
        </option><option data-v-27358c14="" value="HOWSER">
        하우저
        </option><option data-v-27358c14="" value="HIVECITY">
        하이브시티
        </option><option data-v-27358c14="" value="HANDEX">
        한덱스
        </option><option data-v-27358c14="" value="HANSSEM">
        한샘
        </option><option data-v-27358c14="" value="HPL">
        한의사랑택배
        </option><option data-v-27358c14="" value="HANJIN">
        한진택배
        </option><option data-v-27358c14="" value="HDEXP">
        합동택배
        </option><option data-v-27358c14="" value="HONAM">
        호남택배
        </option><option data-v-27358c14="" value="HOMEINNOV">
        홈이노베이션로지스
        </option><option data-v-27358c14="" value="HOMEPICK">
        홈픽택배
        </option><option data-v-27358c14="" value="CARGOPLEASE">
        화물부탁해
        </option></select>
         */
    }

    /**
     * @param ResponseInterface $response
     * @return array|string
     */
    public function returnBody($response)
    {
        $body = '';
        if ($response->getStatusCode() == 200) {
            $body = $response->getBody();
        }
        return json_decode($body, true);
    }

}