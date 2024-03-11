<?php
namespace common\services\api;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsLinio;
use common\models\GoodsEvent;
use common\models\GoodsShop;
use common\models\Order;
use common\services\buy_goods\BuyGoodsService;
use common\services\goods\FGoodsService;
use common\services\goods\GoodsService;
use common\services\goods\platform\LinioPlatform;
use common\services\goods\WordTranslateService;
use Psr\Http\Message\ResponseInterface;
use yii\base\Exception;

/**
 * Class BaseSellerCenterApiService
 * @package common\services\api
 * https://sellerapi.sellercenter.net/docs/signing-requests
 */
class BaseSellerCenterApiService extends BaseApiService
{

    /**
     * 频次 时间 类型
     * @var array
     */
    public $frequency_limit_timer = [
        'add_goods' => [30,3600,''],
        'update_stock' => [50000,86400,'']
    ];

    public function getBaseUri()
    {
        $shop = $this->shop;
        return '';
    }

    /**
     * @param $method
     * @param $path
     * @param $param
     * @return array|false|\GuzzleHttp\Client
     */
    public function request($method,$path,$param,$xml = null)
    {

        date_default_timezone_set("UTC");

        // The current time. Needed to create the Timestamp parameter below.
        $now = new \DateTime();
        $type = !empty($xml)?'XML':'JSON';
        // The parameters for our GET request. These will get signed.
        $parameters = [
            // The user ID for which we are making the call.
            'UserID' => $this->client_key,

            // The API version. Currently must be 1.0
            'Version' => '1.0',

            // The API method to call.
            'Action' => $path,

            // The format of the result.
            'Format' => $type,

            // The current time formatted as ISO8601
            'Timestamp' => $now->format(\DateTime::ISO8601)
        ];

        if ($method == 'GET') {
            $parameters = array_merge($parameters, $param);
        }

        // Sort parameters by name.
        ksort($parameters);

        // URL encode the parameters.
        $encoded = array();
        foreach ($parameters as $name => $value) {
            $encoded[] = rawurlencode($name) . '=' . rawurlencode($value);
        }

        // Concatenate the sorted and URL encoded parameters into a string.
        $concatenated = implode('&', $encoded);

        // The API key for the user as generated in the Seller Center GUI.
        // Must be an API key associated with the UserID parameter.
        $api_key = $this->secret_key;

        // Compute signature and add it to the parameters.
        $parameters['Signature'] =
            rawurlencode(hash_hmac('sha256', $concatenated, $api_key, false));

        date_default_timezone_set('Asia/Shanghai');

        $query_string = http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);

        $url = '?'.$query_string;

        if(!empty($xml)) {
            $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>" .$xml;
        }

        //echo  $url;
        $client = new \GuzzleHttp\Client([
            /*'headers' => [
                'Content-Type' => 'application/json;charset=UTF-8',
            ],*/
            'base_uri' => $this->getBaseUri(),
            'timeout' => 30,
            'verify' => false
        ]);
        if ($method == 'GET') {
            $response = $client->get($url);
        } else if ($method == 'POST') {
            $body = empty($xml)?json_encode($param):$xml;
            $response = $client->post($url, [
                'body' => $body
            ]);
        } else if ($method == 'PUT') {
            $response = $client->put($url, [
                'json' => $param,
            ]);
        }
        return $this->returnBody($response,$type);
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
        $response = $this->request('GET','GetOrders',['CreatedAfter'=>$add_time]);
        $result = !empty($response['SuccessResponse']) && !empty($response['SuccessResponse']['Body']) && !empty($response['SuccessResponse']['Body']['Orders']) && !empty($response['SuccessResponse']['Body']['Orders']['Order'])?$response['SuccessResponse']['Body']['Orders']['Order']:[];
        return empty($result['OrderId'])?$result:[$result];
    }

    /**
     * 获取订单详情
     * @param $order_id
     * @return string|array
     */
    public function getOrderInfo($order_id)
    {
        $response = $this->request('GET','GetOrderItems',['OrderId'=>$order_id]);
        $result = !empty($response['SuccessResponse']) && !empty($response['SuccessResponse']['Body']) && !empty($response['SuccessResponse']['Body']['OrderItems']) && !empty($response['SuccessResponse']['Body']['OrderItems']['OrderItem'])?$response['SuccessResponse']['Body']['OrderItems']['OrderItem']:[];
        return empty($result['OrderItemId'])?$result:[$result];
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
        return date(\DateTime::ISO8601, $time);
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
        $update_time = $update_time - 12 * 60 * 60;
        $update_time = date("Y-m-d H:i:s", $update_time);
        $response = $this->request('GET','GetOrders',['UpdatedAfter'=>$update_time,'Offset'=>$offset,'Limit'=>$limit]);
        $result = !empty($response['SuccessResponse']) && !empty($response['SuccessResponse']['Body']) && !empty($response['SuccessResponse']['Body']['Orders']) && !empty($response['SuccessResponse']['Body']['Orders']['Order'])?$response['SuccessResponse']['Body']['Orders']['Order']:[];
        return empty($result['OrderId'])?$result:[$result];
    }

    /**
     * 处理取消订单
     * @param $order
     * @return array|bool
     */
    public function dealCancelOrder($order)
    {
        $relation_no = (string)$order['OrderNumber'];
        if($order['Statuses']['Status'] != 'canceled'){
            return false;
        }

        $goods = [];
        $cancel_goods = [];
        $cancel_time = 0;
        /*foreach ($order['order_items'] as $v) {
            if ($v['status'] == 'cancel') {
                $info = $v['variant'];
                $cancel_goods[] = [
                    'id' => '432085',
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
        }*/
        $cancel_time = strtotime($order['UpdatedAt']);

        if (empty($goods)) {
            return [
                'relation_no' => $relation_no,
                'cancel_time' => $cancel_time
            ];
        } else {
            if (!empty($cancel_goods)) {
                return [
                    'relation_no' => $relation_no,
                    'cancel_goods' => $cancel_goods,
                    'cancel_time' => $cancel_time
                ];
            }
        }
        return false;
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

        $relation_no = (string)$order['OrderNumber'];
        $exist = Order::find()->where(['relation_no' => $relation_no])->one();
        if ($exist) {
            return false;
        }

        $add_time = strtotime($order['CreatedAt']);

        if($order['Statuses']['Status'] == 'canceled'){
            return false;
        }

        $items = $this->getOrderInfo($order['OrderId']);

        //$logistics_channels_name = empty($shipping['tracking_method']) ? '' : $shipping['tracking_method'];
        //$track_no = empty($shipping['tracking_number']) ? '' : $shipping['tracking_number'];

        $shipping_address = $order['AddressShipping'];
        $country_map = ['Peru'=>'PE'];
        $country = !empty($country_map[$shipping_address['Country']]) ? $country_map[$shipping_address['Country']] : $shipping_address['Country'];
        $country = $shop_v['country_site'];
        $buyer_phone = empty($shipping_address['Phone2']) ? '0000' : $shipping_address['Phone2'];

        $track_no = '';
        $logistics_channels_name = '';
        $goods_lists = [];
        foreach ($items as $v) {
            $sku = $v['Sku'];
            if(strlen($sku) > 36 && strpos($sku,'_DELETED_') !== false){
                $arr  = explode('_DELETED_',$sku);
                $sku = $arr[0];
            }
            if (empty($goods_lists[$sku])) {
                $v['num'] = 1;
                $goods_lists[$sku] = $v;
            } else {
                $goods_lists[$sku]['num'] += 1;
            }
            $track_no = $v['TrackingCode'];
            $logistics_channels_name = $v['ShippingProviderType'];
        }

        $data = [
            'create_way' => Order::CREATE_WAY_SYSTEM,
            'shop_id' => $shop_v['id'],
            'source' => $shop_v['platform_type'],
            'relation_no' => $relation_no,
            'date' => $add_time,
            'user_no' => (string)'',
            'country' => $country,
            'city' => empty($shipping_address['City']) ? '' : $shipping_address['City'],
            'area' => empty($shipping_address['City']) ? '' : $shipping_address['City'],
            'company_name' => '',
            'buyer_name' => $shipping_address['FirstName'] .' ' .$shipping_address['LastName'],
            'buyer_phone' => $buyer_phone,
            'postcode' => (string)empty($shipping_address['PostCode']) ? ' ' : $shipping_address['PostCode'],
            'email' => '',
            'address' => $shipping_address['Address1'],
            'remarks' => '',
            'add_time' => $add_time,
            'logistics_channels_name' => $logistics_channels_name,
            'track_no' => $track_no,
            'delivery_order_id' => (string)$order['OrderId'],
            'integrated_logistics' => Order::INTEGRATED_LOGISTICS_YES
        ];

        //$platform_fee = 0;
        $goods = [];
        foreach ($goods_lists as $v) {
            $sku = $v['Sku'];
            if(strlen($sku) > 36 && strpos($sku,'_DELETED_') !== false){
                $arr  = explode('_DELETED_',$sku);
                $sku = $arr[0];
            }
            $where = [
                'shop_id'=>$shop_v['id'],
                'platform_sku_no'=>$sku
            ];
            $platform_sku_no = GoodsShop::find()->where($where)->select('cgoods_no')->scalar();
            if(empty($platform_sku_no)){
                $goods_map = (new BuyGoodsService())->getGoodsToSkuCountry($sku, $country, Base::PLATFORM_1688);
            }else {
                $goods_map = (new BuyGoodsService())->getGoodsToSkuCountry($platform_sku_no, $country, Base::PLATFORM_1688, 2);
            }
            $goods_data = $this->dealOrderGoods($goods_map);
            $goods_data = array_merge($goods_data,[
                'goods_name' => $v['Name'],
                'goods_num' => $v['num'],
                'goods_income_price' => $v['ItemPrice'],
            ]);
            $goods[] = $goods_data;
            //$platform_fee += $v['sale_fee'];
        }
        //$data['platform_fee'] = $platform_fee;

        return [
            'order' => $data,
            'goods' => $goods,
        ];
    }

    public function getDocumentType()
    {
        return 'shippingParcel';
    }

    /**
     * 打印
     * @param $order
     * @return string|array
     */
    public function doPrint($order, $is_show = false)
    {
        $items = $this->getOrderInfo($order['delivery_order_id']);
        $ids = [];
        foreach ($items as $v) {
            $ids[] = $v['OrderItemId'];
        }
        $response = $this->request('GET', 'GetDocument', ['OrderItemIds' => json_encode($ids), 'DocumentType' => $this->getDocumentType()]);

        $response_con = !empty($response['SuccessResponse']) && !empty($response['SuccessResponse']['Body'])  && !empty($response['SuccessResponse']['Body']['Documents']) && !empty($response['SuccessResponse']['Body']['Documents']['Document']) && !empty($response['SuccessResponse']['Body']['Documents']['Document']['File']) ? $response['SuccessResponse']['Body']['Documents']['Document']['File'] : '';
        if ($is_show === 2) {
            return true;
        }
        $mime_type = $response['SuccessResponse']['Body']['Documents']['Document']['MimeType'];
        $response_con = base64_decode($response_con);

        if ($is_show) {
            header("Content-type: ".$mime_type);
            echo $response_con;
            exit();
        }

        $file_type = 'pdf';
        if($mime_type == 'text/html') {
            $file_type = 'html';
        }
        $pdfUrl = CommonUtil::savePDF($response_con,$file_type);
        return $pdfUrl['pdf_url'];
    }

    public function getShipmentProviders()
    {
        $result = $this->request('GET', 'GetShipmentProviders', []);
        var_dump($result);
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
        $items = $this->getOrderInfo($order['delivery_order_id']);
        //$items = $this->getOrderInfo($order_id);
        $ids = [];
        $track_no = '';
        $logistics_channels_name = '';
        foreach ($items as $v) {
            $ids[] = $v['OrderItemId'];
            $track_no = $v['TrackingCode'];
            $logistics_channels_name = $v['ShippingProviderType'];
        }
        $result = $this->request('GET', 'SetStatusToReadyToShip', ['OrderItemIds' => json_encode($ids), 'DeliveryType' => 'dropship']);

        if (empty($result['SuccessResponse'])) {
            CommonUtil::logs('lino set-tracking-number error order_id:' . $order_id . ' result:' . json_encode($result), 'fapi');
        }

        if (empty($order['track_no'])) {
            if (empty($track_no)) {
                $items = $this->getOrderInfo($order['delivery_order_id']);
                foreach ($items as $v) {
                    $track_no = $v['TrackingCode'];
                    $logistics_channels_name = $v['ShippingProviderType'];
                }
            }
            if (empty($order['logistics_channels_name'])) {
                $order['logistics_channels_name'] = $logistics_channels_name;
            }
            $order['track_no'] = $track_no;
            $order->save();
        }
        return $result;
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
    }

    /**
     * 添加商品
     * @param $goods_lists
     * @return bool|array
     * @throws Exception
     */
    public function batchAddGoods($goods_lists)
    {
        $shop = $this->shop;
        $data = [];
        $goods_images = [];
        $error_sku = [];
        $base_platform = FGoodsService::factory($this->platform_type);
        foreach ($goods_lists as $goods) {
            $base_goods = $base_platform->model()->find()->where(['goods_no' => $goods['goods_no']])->asArray()->one();
            $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
            if (empty($goods_shop)) {
                $error_sku[] = [
                    'sku_no'=>$goods['sku_no'],
                    'error' => '不存在商品',
                ];
                continue;
            }

            try {
                $info = $this->dealGoodsInfo($goods, $base_goods, $goods_shop);
            } catch (\Exception $e) {
                $error_sku[] = [
                    'sku_no' => $goods['sku_no'],
                    'error' => $e->getMessage(),
                ];
                continue;
            }

            if(!$info){
                $error_sku[] = [
                    'sku_no'=>$goods['sku_no'],
                    'error' => '信息出错',
                ];
                continue;
            }
            $data[] = $info;

            $goods_images[] = $goods;
        }

        $xml = $this->genXML($data,'Product');
        $up_result = $this->request('POST', 'ProductCreate', null,$xml);
        CommonUtil::logs($this->platform_type.' result shop_id:' . $shop['id'] . ' data:' . $xml . ' result:' . json_encode($up_result,JSON_UNESCAPED_UNICODE), 'add_products_'.$this->platform_type);
        if (empty($up_result) || !empty($up_result['Body']) || empty($up_result['Head']) || !empty($up_result['Head']['ErrorCode'])) {
            return false;
        }

        foreach ($goods_images as $goods_v) {
            GoodsEventService::addEvent($goods_shop, GoodsEvent::EVENT_TYPE_UPLOAD_IMAGE, -1);
        }
        return ['id'=>$up_result['Head']['RequestId'],'error'=>$error_sku];
    }


    /**
     * 上传图片
     * @param $goods_lists
     * @return bool|array
     * @throws Exception
     */
    public function batchUploadImage($goods_lists)
    {
        $shop = $this->shop;
        $goods_images = [];
        $error_sku = [];
        foreach ($goods_lists as $goods) {
            $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
            if (empty($goods_shop)) {
                $error_sku[] = [
                    'sku_no'=>$goods['sku_no'],
                    'error' => '不存在商品',
                ];
                continue;
            }
            $sku_no = !empty($goods_shop['platform_sku_no']) ? $goods_shop['platform_sku_no'] : $goods['sku_no'];

            $images = [];
            $image = json_decode($goods['goods_img'], true);
            $i = 0;
            foreach ($image as $v) {
                if ($i > 5) {
                    break;
                }
                $i++;
                $images[] = $v['img'].'?imageMogr2/thumbnail/!500x500r';
            }

            $goods_images[] = [
                'SellerSku' => $sku_no,
                'Images' => $images
            ];
        }

        $xml = $this->genXML($goods_images, 'ProductImage');
        $up_result = $this->request('POST', 'Image', null,$xml);
        CommonUtil::logs($this->platform_type.' image result shop_id:' . $shop['id'] . ' data:' . $xml . ' result:' . json_encode($up_result,JSON_UNESCAPED_UNICODE), 'add_products_'.$this->platform_type);
        return ['id'=>$up_result['Head']['RequestId'],'error'=>$error_sku];
    }

    /**
     * 添加商品
     * @param $goods
     * @return bool
     * @throws Exception
     */
    public function addGoods($goods)
    {
        $shop = $this->shop;
        $base_platform = FGoodsService::factory($this->platform_type);
        $base_goods = $base_platform->model()->find()->where(['goods_no' => $goods['goods_no']])->asArray()->one();
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        if (!empty($goods_shop['platform_goods_id'])) {
            //return true;
        }

        /*if(!empty($goods['weight']) && $goods['weight'] > 0){
            $product_data[] = [
                'label' => 'Weight',
                'value' => $goods['weight'] .'kg'
            ];
        }
        if(!empty($goods['colour'])){
            $product_data[] = [
                'label' => 'Colour',
                'value' => $goods['colour']
            ];
        }*/
        $data = $this->dealGoodsInfo($goods,$base_goods,$goods_shop);
        if(!$data){
            return false;
        }
        CommonUtil::logs($this->platform_type.' request goods_no:' . $goods['goods_no'] . ' shop_id:' . $shop['id'] . ' data:' . json_encode($data,JSON_UNESCAPED_UNICODE) , 'add_products_'.$this->platform_type);
        /*echo  json_encode([
            'items' => $data
        ],JSON_UNESCAPED_UNICODE);//exit();*/

        $xml = $this->genXML([$data],'Product');
        $up_result = $this->request('POST', 'ProductCreate', null,$xml);

        CommonUtil::logs($this->platform_type.' result goods_no:' . $goods['goods_no'] . ' shop_id:' . $shop['id'] . ' data:' . $xml . ' result:' . json_encode($up_result,JSON_UNESCAPED_UNICODE), 'add_products_'.$this->platform_type);
        if (empty($up_result) || !empty($up_result['Body']) || empty($up_result['Head']) || !empty($up_result['Head']['ErrorCode'])) {
            return false;
        }

        GoodsEventService::addEvent($goods_shop, GoodsEvent::EVENT_TYPE_UPLOAD_IMAGE, -1);

        /*$images = [];
        $image = json_decode($goods['goods_img'], true);
        $i = 0;
        foreach ($image as $v) {
            if ($i > 5) {
                break;
            }
            $i++;
            $images[] = $v['img'].'?imageMogr2/thumbnail/!800x800r';
        }

        $xml = $this->genXML([[
                'SellerSku' => $data['SellerSku'],
                'Images' => $images
            ]], 'ProductImage');
        $iup_result = $this->request('POST', 'Image', null,$xml);
        CommonUtil::logs($this->platform_type.' image result goods_no:' . $goods['goods_no'] . ' shop_id:' . $shop['id'] . ' data:' . $xml . ' result:' . json_encode($iup_result,JSON_UNESCAPED_UNICODE), 'add_products_'.$this->platform_type);*/

        //上传图片

        //GoodsEventService::addEvent($shop['platform_type'], $shop['id'], $goods['goods_no'], $goods['cgoods_no'], GoodsEvent::EVENT_TYPE_GET_GOODS_ID,time() + 30 * 60);
        return true;
    }

    /**
     * 处理商品信息
     * @param $goods
     * @param $base_goods
     * @param $goods_shop
     * @return array
     */
    public function dealGoodsInfo($goods, $base_goods, $goods_shop)
    {

    }

    /**
     * 获取类目
     */
    public function getCategoryAttributes($id)
    {
        $cache = \Yii::$app->cache;
        $country = $this->shop['country_site'];
        $cache_key = 'com::platform_'.$this->platform_type.'::category::'.$country.'_attr_' . $id;
        $attr = $cache->get($cache_key);
        $attr = empty($attr) ? [] : json_decode($attr, true);
        if (empty($attr)) {
            $result = $this->request('GET', 'GetCategoryAttributes', ['PrimaryCategory' => $id]);
            $attr = empty($result['SuccessResponse']) || empty($result['SuccessResponse']['Body']) ? [] : $result['SuccessResponse']['Body']['Attribute'];
            $cache->set($cache_key, json_encode($attr), 24 * 60 * 60);
        }
        return $attr;
    }

    /**
     * 获取ASIN获取商品信息
     * @param $asin
     * @return string|array
     */
    public function getProductsAttributesToAsin($asin)
    {
        $result = $this->request('GET', 'GetProducts', [
            'Search' => $asin
        ]);
        return empty($result['SuccessResponse']) ? [] : $result['SuccessResponse']['Body'];
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
        $result = $this->updateListings([[
            'SellerSku' => $sku_no,
            'Quantity' => $stock
        ]]);
        return $result ? 1 : 0;
    }

    /**
     * @param $data
     * @return bool
     */
    public function updateListings($data)
    {
        /*[
            [
                'SellerSku' => $sku_no,
                'Quantity' => $stock
            ]
        ]*/
        $xml = $this->genXML($data, 'Product');
        $up_result = $this->request('POST', 'ProductStockUpdate', null, $xml);
        CommonUtil::logs($this->platform_type.' updateListings result shop_id:' . $this->shop['id'] . ' data:' . $xml . ' result:' . json_encode($up_result,JSON_UNESCAPED_UNICODE), 'products_'.$this->platform_type);
        if (empty($up_result) || !empty($up_result['Body']) || empty($up_result['Head']) || !empty($up_result['Head']['ErrorCode'])) {
            return false;
        }
        return $up_result['Head']['RequestId'];
    }

    /**
     * 批量更新商品
     * @param $lists
     * @return bool
     */
    public function updateGoods($lists)
    {
        foreach ($lists as &$v) {
            if (!empty($v['Price'])) {
                $v['SaleStartDate'] = $this->toDate(date('Y-m-d', time() - 24 * 60 * 60));
                $v['SaleEndDate'] = $this->toDate('2026-12-01');
            }
        }
        $xml = $this->genXML($lists, 'Product');
        $up_result = $this->request('POST', 'ProductUpdate', null, $xml);
        CommonUtil::logs($this->platform_type.' batchUpdateGoods result shop_id:' . $this->shop['id'] . ' data:' . $xml . ' result:' . json_encode($up_result,JSON_UNESCAPED_UNICODE), 'products_'.$this->platform_type);
        if (empty($up_result) || !empty($up_result['Body']) || empty($up_result['Head']) || !empty($up_result['Head']['ErrorCode'])) {
            return false;
        }
        return $up_result['Head']['RequestId'];
    }

    /**
     * 更新价格
     * @param $goods
     * @param $price
     * @return int
     * @throws Exception
     */
    public function updatePrice($goods,$price)
    {
        $shop = $this->shop;
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        if (!empty($goods_shop['platform_sku_no'])) {
            $sku_no = $goods_shop['platform_sku_no'];
        } else {
            $sku_no = $goods['sku_no'];
        }

        $size = GoodsService::getSizeArr($goods['size']);
        $exist_size = true;
        if($goods['real_weight'] > 0) {
            $weight = $goods['real_weight'];
            if(!empty($size)) {
                if(!empty($size['size_l']) && $size['size_l'] > 3) {
                    $l = (int)$size['size_l'] - 2;
                } else {
                    $exist_size = false;
                }

                if(!empty($size['size_w']) && $size['size_w'] > 3) {
                    $w = (int)$size['size_w'] - 2;
                } else {
                    $exist_size = false;
                }

                if(!empty($size['size_h']) && $size['size_h'] > 3) {
                    $h = (int)$size['size_h'] - 2;
                } else {
                    $exist_size = false;
                }
            } else {
                $exist_size = false;
            }
        } else {
            $weight = $goods['weight'] < 0.02 ? 0.02 : ($goods['weight']/2);
            $exist_size = false;
        }
        $weight = round($weight,2);

        //生成长宽高
        if(!$exist_size) {
            $tmp_weight = $weight > 4 ? 4 : $weight;
            $tmp_cjz = $tmp_weight / 2 * 5000;
            $pow_i = pow($tmp_cjz, 1 / 3);
            $pow_i = $pow_i > 30 ? 30 : (int)$pow_i;
            $min_pow_i = $pow_i > 6 ? ($pow_i - 5) : 1;
            $max_pow_i = $pow_i > 5 ? ($pow_i + 5) : ($pow_i > 2 ? ($pow_i + 2) : $pow_i);
            $arr = [];
            $arr[] = rand($min_pow_i,$max_pow_i);
            $arr[] = rand($min_pow_i,$max_pow_i);
            $arr[] = (int)(($tmp_cjz/$arr[0])/$arr[1]);
            rsort($arr);
            list($l,$w,$h) = $arr;
        }

        $result = $this->updateGoods([[
            'SellerSku' => $sku_no,
            'Price' => $price * 2,
            'SalePrice' => $price,
            'ProductData' => [
                'PackageLength' => $l,
                'PackageWidth' => $w,
                'PackageHeight' => $h,
                'PackageWeight' => $weight,
                'ProductWeight' => $weight,
                'ProductMeasures' => $l . ' x ' . $w . ' x ' . $h
            ],
            //'SaleStartDate' => $this->toDate(date('Y-m-d', time() - 24 * 60 * 60)),
            //'SaleEndDate' =>  $this->toDate('2026-12-01')
        ]]);
        return $result ? 1 : 0;
    }

    /**
     * 批量更新商品
     * @param $lists
     * @return bool
     */
    public function batchDelGoods($lists)
    {
        $xml = $this->genXML($lists, 'Product');
        $up_result = $this->request('POST', 'ProductRemove', null, $xml);
        CommonUtil::logs($this->platform_type.' batchDelGoods result shop_id:' . $this->shop['id'] . ' data:' . $xml . ' result:' . json_encode($up_result,JSON_UNESCAPED_UNICODE), 'products_'.$this->platform_type);
        if (empty($up_result) || !empty($up_result['Body']) || empty($up_result['Head']) || !empty($up_result['Head']['ErrorCode'])) {
            return false;
        }
        return $up_result['Head']['RequestId'];
    }

    /**
     * 删除商品
     */
    public function delGoods($goods_shop)
    {
        if (!empty($goods_shop['platform_sku_no'])) {
            $sku_no = $goods_shop['platform_sku_no'];
        } else {
            $goods = GoodsChild::find()->where(['cgoods_no' => $goods_shop['cgoods_no']])->one();
            $sku_no = $goods['sku_no'];
        }

        $result = $this->batchDelGoods([[
            'SellerSku' => $sku_no
        ]]);
        return $result;
    }

    /**
     * 生成xml
     * @param $lists
     * @param $model
     * @return string
     */
    public function genXML($lists,$model)
    {
        $xml = "<Request>";
        foreach ($lists as $data) {
            $xml .= "<{$model}>";
            foreach ($data as $key => $val) {
                if (in_array($key, ['ProductData', 'Images'])) {
                    $xml .= "<{$key}>";
                    if ($key == 'Images') {
                        foreach ($val as $val_1) {
                            $xml .= "<Image>{$val_1}</Image>";
                        }
                    } else {
                        foreach ($val as $key_1 => $val_1) {
                            if (in_array($key_1, ['ShortDescription'])) {
                                $xml .= "<{$key_1}><![CDATA[ {$val_1} ]]></{$key_1}>";
                            } else {
                                $xml .= "<{$key_1}>{$val_1}</{$key_1}>";
                            }
                        }
                    }
                    $xml .= "</{$key}>";
                } else {
                    if (in_array($key, ['Description'])) {
                        $xml .= "<{$key}><![CDATA[ {$val} ]]></{$key}>";
                    } else {
                        $xml .= "<{$key}>{$val}</{$key}>";
                    }
                }
            }
            $xml .= "</{$model}>";
        }
        $xml .= "</Request>";
        $xml = str_replace(['&'], '', $xml);
        return $xml;
    }

    public function getQueue($id){
        $result = $this->request('GET', 'FeedStatus', ['FeedID' => $id]);
        if(empty($result['SuccessResponse']) || empty($result['SuccessResponse']['Body']) || empty($result['SuccessResponse']['Body']['FeedDetail'])){
            return -1;
        }

        /*if($result['SuccessResponse']['Body']['FeedDetail']['Status'] !== 'Finished'){
            return -1;
        }*/
        return $result['SuccessResponse']['Body']['FeedDetail'];
    }

    /**
     * @param ResponseInterface $response
     * @return array|string
     */
    public function returnBody($response,$type)
    {
        $body = '';
        if (in_array($response->getStatusCode(), [200, 201])) {
            $body = $response->getBody()->getContents();
        }
        if($type == 'XML'){
            return json_decode(json_encode(simplexml_load_string($body)), true);
        }else {
            return json_decode($body, true);
        }
    }
}