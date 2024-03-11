<?php
namespace common\services\api;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\Category;
use common\models\Goods;
use common\models\goods\GoodsAllegro;
use common\models\goods\GoodsJdid;
use common\models\GoodsEvent;
use common\models\GoodsShop;
use common\models\Order;
use common\models\Shop;
use common\services\buy_goods\BuyGoodsService;
use common\services\goods\GoodsService;
use common\services\goods\platform\JdidPlatform;
use Psr\Http\Message\ResponseInterface;
use yii\base\Exception;

/**
 * Class JdidService
 * @package common\services\api
 * https://jos.jd.id
 * https://jos.jd.id/home/home#/doc/api/16/96/jingdong.seller.order.sendGoodsOpenApi
 */
class JdidService extends BaseApiService
{

    public $auth_redirect_uri = 'http://yadmin.sanlinmail.site/auth.php';
    public $base_url = 'https://open-api.jd.id/routerjson';


    /**
     * @return \GuzzleHttp\Client
     */
    public function getClient()
    {
        $client = new \GuzzleHttp\Client([
            'headers' => [
                //'Authorization' => 'Bearer '.$this->refreshToken(),
                //'Content-Type' => 'application/json',
            ],
            'timeout' => 30
        ]);

        return $client;
    }

    /**
     * @param $method
     * @param $param
     * @return ResponseInterface
     * @throws Exception
     */
    public function request($method,$param = null)
    {
        $access_token = $this->refreshToken();
        $sysParams["app_key"] = $this->client_key;
        //$version = $request->getVersion();
        $version = '';
        $sysParams["v"] = empty($version) ? '2.0' : $version;
        $sysParams["method"] = $method;
        $sysParams["timestamp"] = $this->getCurrentTimeFormatted();
        $sysParams["access_token"] = $access_token;

        $apiParams = empty($param)?"{}":json_encode($param);
        $sysParams['360buy_param_json'] = $apiParams;

        $sysParams["sign"] = $this->generateSign($sysParams);

        $request_url = $this->base_url . "?";
        foreach ($sysParams as $sysParamKey => $sysParamValue) {
            $request_url .= "$sysParamKey=" . urlencode($sysParamValue) . "&";
        }

        $postBodyString = "";
        $postMultipart = false;
        if (is_array($param) && 0 < count($param)) {
            foreach ($param as $k => $v) {
                if ("@" != substr($v, 0, 1))
                {
                    $postBodyString .= "$k=" . urlencode($v) . "&";
                } else
                {
                    $postMultipart = true;
                }
            }
        }
        if($postMultipart) {
            $response = $this->getClient()->post($request_url, [
                'form_params' => $param
            ]);
        }else{
            $response = $this->getClient()->post($request_url, [
                'body' => substr($postBodyString, 0, -1)
            ]);
        }
        return $response;
    }

    public function request1($method,$param = null,$request_method = 'get')
    {
        $access_token = $this->refreshToken();
        $sysParams["app_key"] = $this->client_key;
        $sysParams["v"] = empty($version) ? '2.0' : $version;
        $sysParams["method"] = $method;
        $sysParams["timestamp"] = $this->getCurrentTimeFormatted();
        if (null != $access_token) {
            $sysParams["access_token"] = $access_token;
        }

        $apiParams = empty($param) ? "{}" : json_encode($param);
        $sysParams['360buy_param_json'] = $apiParams;
        $sysParams["sign"] = $this->generateSign($sysParams);

        if($request_method == 'get') {
            $requestUrl = $this->base_url . "?";
            foreach ($sysParams as $sysParamKey => $sysParamValue) {
                $requestUrl .= "$sysParamKey=" . urlencode($sysParamValue) . "&";
            }
            $curl_params = $apiParams;

        }else{
            $requestUrl = $this->base_url ;
            $curl_params = $sysParams;
        }
        $result = [];
        try {
            //echo urldecode($requestUrl);
            //echo PHP_EOL;
            $resp = $this->curl($requestUrl, $curl_params,$request_method);
        } catch (Exception $e) {
            $result['code'] = $e->getCode();
            $result['msg'] = $e->getMessage();
            return $result;
        }
        //if ("json" == $this->format) {
        $respObject = json_decode($resp,true);

        /*} else if ("xml" == $this->format) {
            $respObject = @simplexml_load_string($resp);
            if (false !== $respObject) {
                $respWellFormed = true;
            }
        }*/

        if (empty($respObject)) {
            $result['code'] = 0;
            $result['msg'] = "HTTP_RESPONSE_NOT_WELL_FORMED";
            return $result;
        }
        return $respObject;
    }

    public function curl($url, $postFields = null,$request_method = 'get')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        if (strlen($url) > 5 && strtolower(substr($url, 0, 5)) == "https") {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        if($request_method == 'get') {
            if (is_array($postFields) && 0 < count($postFields)) {
                $postBodyString = "";
                $postMultipart = false;
                foreach ($postFields as $k => $v) {
                    if ("@" != substr($v, 0, 1)) {
                        $postBodyString .= "$k=" . urlencode($v) . "&";
                    } else {
                        $postMultipart = true;
                    }
                }
                unset($k, $v);
                curl_setopt($ch, CURLOPT_POST, true);
                if ($postMultipart) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
                } else {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, substr($postBodyString, 0, -1));
                }
            }
        }else{
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        }
        $reponse = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch), 0);
        } else {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (200 !== $httpStatusCode) {
                throw new Exception($reponse, $httpStatusCode);
            }
        }
        curl_close($ch);
        return $reponse;
    }

    private function getCurrentTimeFormatted()
    {
        return  date("Y-m-d H:i:s").'.000'.$this->getStandardOffsetUTC(date_default_timezone_get());
    }

    private function getStandardOffsetUTC($timezone)
    {
        if($timezone == 'UTC') {
            return '+0000';
        } else {
            $timezone = new \DateTimeZone($timezone);
            $transitions = array_slice($timezone->getTransitions(), -3, null, true);

            foreach (array_reverse($transitions, true) as $transition)
            {
                if ($transition['isdst'] == 1)
                {
                    continue;
                }

                return sprintf('%+03d%02u', $transition['offset'] / 3600, abs($transition['offset']) % 3600 / 60);
            }

            return false;
        }
    }

    /**
     * 生成签名
     * @param $params
     * @return string
     */
    protected function generateSign($params)
    {
        ksort($params);
        $stringToBeSigned = $this->secret_key;
        foreach ($params as $k => $v)
        {
            if("@" != substr($v, 0, 1))
            {
                $stringToBeSigned .= "$k$v";
            }
        }
        unset($k, $v);
        $stringToBeSigned .= $this->secret_key;
        return strtoupper(md5($stringToBeSigned));
    }

    /**
     * 获取token
     * @return mixed
     * @throws Exception
     */
    public function refreshToken()
    {
        $cache = \Yii::$app->redis;
        $cache_token_key = 'com::jdid::token::' . $this->client_key;
        $token = $cache->get($cache_token_key);
        if (empty($token)) {
            $param = json_decode($this->param, true);
            if (empty($param) || empty($param['refresh_token'])) {
                throw new Exception('refresh_token不能为空');
            }

            $client = new \GuzzleHttp\Client([
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'base_uri' => 'https://oauth.jd.id/oauth2/refresh_token',
                'timeout' => 30,
            ]);
            $response = $client->get('?app_key='.$this->client_key.'&app_secret='.$this->secret_key
                .'&grant_type=refresh_token&refresh_token='.$param['refresh_token']);
            $result = $this->returnBody($response);
            if (empty($result['access_token'])) {
                throw new Exception('token获取失败');
            }

            if (!empty($this->shop['id'])) {
                $param['refresh_token'] = $result['refresh_token'];
                Shop::updateOneById($this->shop['id'], ['param' => json_encode($param)]);
            }

            $token = $result['access_token'];
            $cache->setex($cache_token_key, $result['expires_in'] - 60 * 60, $token);
            CommonUtil::logs('jdid token client_key:' . $this->client_key . ' param:' . json_encode($param) . ' result:' . json_encode($result), 'fapi');
        }
        return $token;
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
        $response = $this->request('jingdong.seller.order.getOrderIdListByCondition', [
            'updateTimeBegin' => $update_time,
            'pageNo' => intval($offset / $limit) + 1,
            'pageSize' => $limit
        ]);
        $result = $this->returnBody($response);
        return empty($result['jingdong_seller_order_getOrderIdListByCondition_response']) ? [] : $result['jingdong_seller_order_getOrderIdListByCondition_response']['result']['model'];
    }

    /**
     * 处理取消订单
     * @param $order
     * @return array|bool
     */
    public function dealCancelOrder($order)
    {
        $relation_no = (string)$order;
        $info = $this->getOrderInfo($relation_no);

        if ($info['orderState'] != 5) {
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
        $cancel_time = $info['modifyTime'] / 1000;

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
     * 获取订单列表
     * @param $add_time
     * @param $end_time
     * @return string|array
     * @throws Exception
     */
    public function getOrderLists($add_time,$end_time = null)
    {
        if(!empty($add_time)){
            $add_time = strtotime($add_time) - 12*60*60;
            $add_time = date("Y-m-d H:i:s",$add_time);
        }
        if(empty($end_time)){
            $end_time = date('Y-m-d H:i:s',time() + 2*60*60);
        }
        $response = $this->request('jingdong.seller.order.getOrderIdListByCondition',['bookTimeBegin'=>$add_time]);
        $result = $this->returnBody($response);
        return empty($result['jingdong_seller_order_getOrderIdListByCondition_response'])?[]:$result['jingdong_seller_order_getOrderIdListByCondition_response']['result']['model'];
    }

    /**
     * 获取订单详情
     * @param $order_id
     * @return string|array
     * @throws Exception
     */
    public function getOrderInfo($order_id)
    {
        $response = $this->request('jingdong.seller.order.getOrderInfoByOrderId',['orderId'=>$order_id]);
        $result = $this->returnBody($response);
        return empty($result['jingdong_seller_order_getOrderInfoByOrderId_response'])?[]:$result['jingdong_seller_order_getOrderInfoByOrderId_response']['result']['model'];
    }

    /**
     * 处理订单
     * @param $order
     * @return array|bool
     * @throws Exception
     */
    public function dealOrder($order)
    {
        $shop_v = $this->shop;
        if (empty($order)) {
            return false;
        }

        $relation_no = (string)$order;
        $exist = Order::find()->where(['relation_no' => $relation_no])->one();
        if ($exist) {
            return false;
        }

        $info = $this->getOrderInfo($relation_no);

        //取消状态
        if($info['orderState'] == 5){
            return false;
        }

        $add_time = $info['bookTime']/1000 - 60 * 60;

        $country = 'ID';
        $buyer_phone = empty($info['phone'])?'0000':$info['phone'];
        $data = [
            'create_way' => Order::CREATE_WAY_SYSTEM,
            'shop_id' => $shop_v['id'],
            'source' => $shop_v['platform_type'],
            'relation_no' => $relation_no,
            'date' => $add_time,
            'user_no' => (string)'',
            'country' => $country,
            'city' => empty($info['city'])?'':$info['city'],
            'area' => empty($info['area'])?'':$info['area'],
            'company_name' => '',
            'buyer_name' => $info['customerName'],
            'buyer_phone' => $buyer_phone,
            'postcode' => (string)$info['postCode'],
            'email' => empty($info['email'])?'':$info['email'],
            'address' => $info['address'],
            'remarks' => '',
            'logistics_channels_name' => empty($info['carrierCompany'])?'':$info['carrierCompany'],
            'track_no' => empty($info['expressNo'])?'':$info['expressNo'],
            'add_time' => $add_time,
        ];

        $goods = [];
        $freight = 0;
        foreach ($info['orderSkuinfos'] as $v) {
            $sku_no = $v['popSkuId'];
            $goods_map = (new BuyGoodsService())->getGoodsToSkuCountry($sku_no,$country,Base::PLATFORM_1688);

            $goods_data = $this->dealOrderGoods($goods_map);
            $goods_data = array_merge($goods_data,[
                'goods_name' => $v['skuName'],
                'goods_pic' => empty($v['skuImage'])?'':$v['skuImage'],
                'goods_num' => $v['skuNumber'],
                'goods_income_price' => $v['costPrice']/$v['skuNumber'],
                'platform_asin' => $sku_no,
            ]);
            $goods[] = $goods_data;

            $goods_info = Goods::find()->where(['goods_no'=>$goods_data['goods_no']])->one();
            if(!empty($goods_data['goods_no']) && !empty($goods_info)) {
                $weight = $goods_info['real_weight'] > 0 ? $goods_info['real_weight'] : $goods_info['weight'];
                if ($weight > 0) {
                    $weight = (new JdidPlatform())->getWeight($weight, $goods_info['size'], 6000);
                    $freight += ($weight * 50 * $v['skuNumber'] + 20) * 1.08;
                }
            }
        }

        if($freight > 0) {
            $data['freight_price'] = $freight;
        }

        return [
            'order' => $data,
            'goods' => $goods,
        ];
    }

    /**
     * 打印
     * @param $order
     * @return string|array
     */
    public function doPrint($order,$is_show = false)
    {
        $response = $this->request('jingdong.seller.order.printOrder',['printType'=>1,'printNum'=>1,'orderId'=>$order['relation_no']]);
        $result = $this->returnBody($response);
        if(empty($result['jingdong_seller_order_printOrder_response']) || empty($result['jingdong_seller_order_printOrder_response']['result']) || empty($result['jingdong_seller_order_printOrder_response']['result']['model'])){
            return '';
        }

        if (empty($order['track_no'])) {
            $order_info = $this->getOrderInfo($order['relation_no']);
            $order = Order::find()->where(['relation_no' => $order['relation_no']])->one();
            $order['logistics_channels_name'] = $order_info['carrierCompany'];
            $order['track_no'] = $order_info['expressNo'];
            $order->save();
        }
        $content = $result['jingdong_seller_order_printOrder_response']['result']['model']['content'];
        $content = base64_decode($content);
        if($is_show === 2){
            return true;
        }

        if($is_show) {
            header("Content-type: application/pdf");
            echo
            exit();
        }
        $pdfUrl = CommonUtil::savePDF($content);
        return $pdfUrl['pdf_url'];
    }

    /**
     * 发货
     * @param string $order_id 订单号
     * @param string $carrier_code 发货物流公司
     * @param string $tracking_number 物流单号
     * @param string $arrival_time 预计到货时间
     * @param string $tracking_url 物流跟踪链接
     * @return string
     * @throws Exception
     */
    public function getOrderSend($order_id, $carrier_code, $tracking_number, $arrival_time = null, $tracking_url = null)
    {
        $order = Order::find()->where(['relation_no'=>$order_id])->one();
        $order_info = $this->getOrderInfo($order_id);
        if(empty($order_info['expressNo'])){
            $this->doPrint($order,2);
            $order_info = $this->getOrderInfo($order_id);
        }

        if (empty($order['track_no'])) {
            $order['logistics_channels_name'] = $order_info['carrierCompany'];
            $order['track_no'] = $order_info['expressNo'];
            $order->save();
        }

        $response = $this->request('jingdong.seller.order.sendGoodsOpenApi',['orderId'=>$order_id]);
        $result = $this->returnBody($response);
        return $result;
    }

    /**
     * @param ResponseInterface $response
     * @return array|string
     */
    public function returnBody($response)
    {
        $body = '';
        if (in_array($response->getStatusCode(),[200,201])) {
            $body = $response->getBody()->getContents();
        }
        return json_decode($body, true);
    }

    /**
     * @param int $page
     * @param int $limit
     * @return array|mixed
     * @throws Exception
     */
    public function getProducts($page = 1,$limit = 100)
    {
        $response = $this->request('jingdong.seller.product.getWareInfoListByVendorId',['size'=>$limit,'page'=>$page]);
        $up_result = $this->returnBody($response);var_dump($up_result);
        if (empty($up_result['jingdong_seller_product_getWareInfoListByVendorId_response']) || empty($up_result['jingdong_seller_product_getWareInfoListByVendorId_response']['returnType']['model']['spuInfoVoList'])) {
            return [];
        }
        return $up_result['jingdong_seller_product_getWareInfoListByVendorId_response']['returnType']['model']['spuInfoVoList'];
    }

    /**
     * @param int $spuId
     * @return array|mixed
     * @throws Exception
     */
    public function getProductsToSpuId($spuId)
    {
        $up_result = $this->request1('jingdong.seller.product.getSkuInfoBySpuIdAndVenderId',['spuId'=>$spuId]);
        if (empty($up_result['jingdong_seller_product_getSkuInfoBySpuIdAndVenderId_response'])) {
            return [];
        }
        return $up_result['jingdong_seller_product_getSkuInfoBySpuIdAndVenderId_response']['returnType']['model'];
    }

    /**
     * @param int $page
     * @param int $limit
     * @return array|mixed
     * @throws Exception
     */
    public function getProductsImg($page = 1,$limit = 100)
    {
        $response = $this->request('jingdong.seller.product.api.read.getSkuImgsBySpuId',['spuId'=>'635692163','pageSize'=>$limit,'currentPage'=>$page]);
        $up_result = $this->returnBody($response);
        if (empty($up_result['com.jd.eptid.common.domain.EptRemoteResult'])) {
            return [];
        }
        return $up_result['com.jd.eptid.common.domain.EptRemoteResult']['returnType']['model']['spuInfoVoList'];
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
        $goods_jdid = GoodsJdid::find()->where(['goods_no' => $goods['goods_no']])->one();
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        if (!empty($goods_shop['platform_goods_id'])) {
            return true;
        }

        $data = $this->dealGoodsInfo($goods, $goods_jdid, $goods_shop);
        if (!$data) {
            return false;
        }
        /*CommonUtil::logs('jdid request goods_no:' . $goods['goods_no'] . ' shop_id:' . $shop['id'] . ' data:' . json_encode([
                'items' => $data
            ], JSON_UNESCAPED_UNICODE), 'jd_add_products');*/
        $up_result = $this->request1('jingdong.seller.product.api.write.addProduct', $data,'post');
        if (empty($up_result) || empty($up_result['jingdong_seller_product_api_write_addProduct_response']) || empty($up_result['jingdong_seller_product_api_write_addProduct_response']['returnType']) || empty($up_result['jingdong_seller_product_api_write_addProduct_response']['returnType']['model']) || empty($up_result['jingdong_seller_product_api_write_addProduct_response']['returnType']['model']['spuId'])) {
            CommonUtil::logs('jdid result goods_no:' . $goods['goods_no'] . ' shop_id:' . $shop['id'] . ' data:' . json_encode([
                    'items' => $data
                ], JSON_UNESCAPED_UNICODE) . ' result:' . json_encode($up_result), 'jd_add_products');
            return false;
        }

        $platform_goods_id = $up_result['jingdong_seller_product_api_write_addProduct_response']['returnType']['model']['skuIdList'][0]['skuId'];
        $platform_goods_opc = $up_result['jingdong_seller_product_api_write_addProduct_response']['returnType']['model']['spuId'];

        GoodsShop::updateAll(['platform_goods_id' => $platform_goods_id,'platform_goods_opc' => $platform_goods_opc], ['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']]);

        GoodsEventService::addEvent($goods_shop, GoodsEvent::EVENT_TYPE_UPLOAD_IMAGE,-1);
        return true;
    }

    /**
     * 上传图片
     * @param $goods
     * @return bool
     * @throws Exception
     */
    public function addGoodsImage($goods)
    {
        $shop = $this->shop;
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        $image = json_decode($goods['goods_img'], true);
        $i = 1;
        $is_success = true;
        $success_i = 1;
        foreach ($image as $v) {
            if ($i > 6) {
                break;
            }
            $images =  $v['img'].'?imageView2/2/w/500/h/500';
            //$images = 'http://image.chenweihao.cn/202111/c4bb358ef22a25b50c86a972a49f115c.jpg?imageView2/2/w/800/h/800';

            $data = ['imageApiVo'=>[
                    'colorId' => '0000000000',
                    'order' => $success_i,
                    'productId' => $goods_shop['platform_goods_opc'],
                    'imageByteBase64' => chunk_split(base64_encode(file_get_contents($images))),
                ]
            ];
            $response = $this->request1('jingdong.seller.product.sku.write.updateProductImages',$data,'post');

            if(empty($response) || empty($response['jingdong_seller_product_sku_write_updateProductImages_response']) || empty($response['jingdong_seller_product_sku_write_updateProductImages_response']['returnType']) || empty($response['jingdong_seller_product_sku_write_updateProductImages_response']['returnType']['success'])) {
                CommonUtil::logs('jdid result goods_no:' . $goods['goods_no'] . ' shop_id:' . $shop['id'] . ' data:' . json_encode($data, JSON_UNESCAPED_UNICODE) . ' result:' . json_encode($response), 'add_image');
                if($i == 1){//主图失败直接失败
                    $is_success = false;
                }
            }else{
                $success_i ++;
            }
            $i++;
        }

        if(!$is_success){
            return false;
        }

        GoodsEventService::addEvent($goods_shop, GoodsEvent::EVENT_TYPE_GET_GOODS_ID, -1);
        return true;
    }

    public function imgtobase64($img='')
    {
        $imageInfo = getimagesize($img);
        return 'data:' . $imageInfo['mime'] . ';base64,' . chunk_split(base64_encode(file_get_contents($img)));
    }

    /**
     * 获取商品id(审核商品)
     * @param $goods
     * @return bool
     */
    public function getGoodsId($goods)
    {
        $shop = $this->shop;
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        $platform_goods_opc = $goods_shop['platform_goods_opc'];
        if (empty($platform_goods_opc)) {
            return false;
        }

        $response = $this->request1('jingdong.seller.product.api.write.submitAudit' , ['spuId' => [(int)$platform_goods_opc]]);
        if(empty($response) || empty($response['jingdong_seller_product_api_write_submitAudit_response']) || empty($response['jingdong_seller_product_api_write_submitAudit_response']['returnType']) || empty($response['jingdong_seller_product_api_write_submitAudit_response']['returnType']['success'])) {
            CommonUtil::logs('jdid result goods_no:' . $goods['goods_no'] . ' shop_id:' . $shop['id'] . ' data:' . $platform_goods_opc . ' result:' . json_encode($response), 'jd_get_goods_id');
            return false;
        }
        return true;
    }

    /**
     * 处理商品信息
     * @param $goods
     * @param $goods_jdid
     * @param $goods_shop
     * @return array
     */
    public function dealGoodsInfo($goods, $goods_jdid, $goods_shop)
    {
        $goods_name = empty($goods_jdid['goods_short_name'])?$goods_jdid['goods_name']:$goods_jdid['goods_short_name'];

        //$colour = empty($colour_map[$goods['colour']]) ? '' : $colour_map[$goods['colour']];
        //$colour = empty($colour) ? $colour_map['Black'] : $colour;

        $stock = true;
        $price = $goods_shop['price'];
        if ($goods['source_method'] == GoodsService::SOURCE_METHOD_AMAZON) {//自建的更新价格，禁用状态的更新为下架
            $price = $goods['price'];
            //德国250  $price*1.35+2
            //英国100  $pice*1.4+2
            if ($goods['source_platform_type'] == Base::PLATFORM_AMAZON_DE) {
                if ($price >= 250) {
                    $stock = false;
                }
                $price = ceil($price * 1.15 + 2) - 0.01;
            } else if ($goods['source_platform_type'] == Base::PLATFORM_AMAZON_CO_UK) {
                if ($price >= 100) {
                    $stock = false;
                }
                $price = ceil($price * 1.4 * 1.1 + 2) - 0.01;
            } else {
                return false;
            }
        }

        if (!in_array($goods['status'], [Goods::GOODS_STATUS_VALID, Goods::GOODS_STATUS_WAIT_MATCH])) {
            $stock = false;
            return false;
        }

        $category_id = trim($goods_jdid['o_category_name']);
        if (empty($category_id)) {
            return false;
        }

        $size = GoodsService::getSizeArr($goods['size']);

        $l = !empty($size['size_l']) && $size['size_l'] > 1 ? (int)$size['size_l'] : 50;
        $h = !empty($size['size_h']) && $size['size_h'] > 1 ? (int)$size['size_h'] : 10;
        $w = !empty($size['size_w']) && $size['size_w'] > 1 ? (int)$size['size_w'] : 20;

        $goods['weight'] = $goods['weight'] <= 0 ? 0.1 : $goods['weight'];

        $name_en = Category::find()->where(['id'=>$goods['category_id']])->select('name_en')->scalar();
        $name_en = str_replace('&','',$name_en);

        $content = (new JdidPlatform())->dealContent($goods_jdid);
        $info = [];
        $info['packLong'] = (string)$l;
        $info['spuName'] = $goods_name;
        $info['commonAttributeIds'] = '';
        $info['keywords'] = $name_en;//
        $info['description'] = $content;
        $info['countryId'] = 156;
        $info['warrantyPeriod'] = 5;//6月官方保修
        $info['productArea'] = '';
        $info['minQuantity'] = 1;
        $info['crossProductType'] = 2;
        $info['packHeight'] = (string)$h;
        $info['taxesType'] = 2;
        $info['appDescription'] = $content;
        $info['weight'] = (string)$goods['weight'];
        $info['packWide'] = (string)$w;
        $info['catId'] = (int)$category_id;
        $info['whetherCod'] = 0;
        $info['piece'] = 0;
        $info['brandId'] = 36489;//无品牌
        $info['subtitle'] = '';
        $info['isQuality'] = 1;
        $info['packageInfo'] = (string)1;
        $info['afterSale'] = 1;
        $info['clearanceType'] = 2;
//{"packLong":"1","spuName":"test Product 1","keywords":"key,word","description":"Test product 1","countryId":"10000000","warantyPeriod":1,"productArea":"","minQuantity":1,"packHeight":"1","appDescription":"Test product 1","weight":"1","packWide":"1","catId":75060984,"whetherCod":0,"piece":0,"brandId":"0","packageInfo":""}
        $lists = [];
        $lists['saleAttributeIds'] = '';
        $lists['costPrice'] = intval($price) * 2;
        $lists['upc'] = '';//$goods_shop['ean'];
        $lists['sellerSkuId'] = $goods['sku_no'];
        $lists['saleAttrValueAlias'] = '';
        $lists['skuName'] = $goods_name;
        $lists['jdPrice'] =intval($price);
        $lists['stock'] = $stock ? 500 : 0;
        return ['spuInfo' => ($info), 'skuList' => [$lists]];
    }

    /**
     * 更新价格
     * @param $goods
     * @param $price
     * @return int
     * @throws Exception
     */
    public function updatePrice($goods, $price)
    {
        $shop = $this->shop;
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        $product_id = $goods_shop['platform_goods_id'];
        $response = $this->request1('jingdong.seller.price.updatePriceBySkuIds', [
            'salePrice' => intval($price),
            'skuId' => intval($product_id),
        ]);
        if(empty($response) || empty($response['jingdong_seller_price_updatePriceBySkuIds_response']) || empty($response['jingdong_seller_price_updatePriceBySkuIds_response']['returnType']) || empty($response['jingdong_seller_price_updatePriceBySkuIds_response']['returnType']['success'])) {
            return false;
        }
        return 1;
    }

    /**
     * 更新库存
     * @param $goods
     * @param $stock
     * @param null $price
     * @return bool
     * @throws Exception
     */
    public function updateStock($goods, $stock, $price = null)
    {
        $shop = $this->shop;
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        if (empty($goods_shop['platform_goods_opc'])) {
            return false;
        }

        $product_id = $goods_shop['platform_goods_opc'];
        $method = $stock?'jingdong.seller.product.api.write.onShelf':'jingdong.seller.product.api.write.offShelf';
        $response = $this->request1($method, [
            'spuId' => intval($product_id),
        ]);

        /*$re_string = $stock?'jingdong_seller_product_api_write_onShelf_response':'jingdong_seller_product_api_write_offShelf_response';
        if(empty($response) || empty($response[$re_string]) || empty($response[$re_string]['returnType']) || empty($response[$re_string]['returnType']['success'])) {
            return false;
        }*/
        return 1;
    }

    /**
     * 删除商品
     * @param $goods_shop
     * @return array|string
     * @throws Exception
     */
    public function delGoods($goods_shop)
    {
        $product_id = $goods_shop['platform_goods_opc'];
        if(empty($product_id)){
            return true;
        }
        $response = $this->request1('jingdong.seller.product.api.write.delSpu', [
            'spuId' => $product_id,
        ]);

        if(empty($response) || empty($response['jingdong_seller_product_api_write_delSpu_response']) || empty($response['jingdong_seller_product_api_write_delSpu_response']['returnType']) || empty($response['jingdong_seller_product_api_write_delSpu_response']['returnType']['success'])) {
            return false;
        }
        return true;
    }

}