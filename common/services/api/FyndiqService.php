<?php

namespace common\services\api;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsFyndiq;
use common\models\GoodsShop;
use common\models\Order;
use common\services\buy_goods\BuyGoodsService;
use common\services\goods\GoodsService;
use Psr\Http\Message\ResponseInterface;
use yii\base\Exception;
use yii\helpers\ArrayHelper;

/**
 * Class FyndiqService
 * @package common\services\api
 * https://merchantapi.fyndiq.com
 */
class FyndiqService extends BaseApiService
{

    /**
     * @return \GuzzleHttp\Client
     */
    public function getClient()
    {
        $client = new \GuzzleHttp\Client([
            'headers' => [
                'Authorization' => 'Basic '.base64_encode($this->client_key.':'.$this->secret_key),
                //'Api-Key' => $this->secret_key,
                'Content-Type' => 'application/json'
            ],
            'base_uri' => 'https://merchants-api.fyndiq.se',
            'timeout' => 30,
        ]);

        return $client;
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
            $add_time = date("Y-m-d H:i:s",$add_time);
        }
        if(empty($end_time)){
            $end_time = date('Y-m-d H:i:s',time() + 2*60*60);
        }
        $response = $this->getClient()->get('/api/v1/orders?state=CREATED&limit=1000&page=1');
        $lists = $this->returnBody($response);
        return empty($lists)?[]:$lists;
    }

    /**
     * 获取订单详情
     * @param $order_id
     * @return string|array
     */
    public function getOrderInfo($order_id)
    {
        $response = $this->getClient()->post('/api/v1/orders/'.$order_id);
        $lists = $this->returnBody($response);
        return empty($lists)?[]:$lists;
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
        $add_time = strtotime($order['created_at']) + 8*60*60;

        $relation_no = $order['id'];
        $exist = Order::find()->where(['relation_no' => $relation_no])->one();
        if ($exist) {
            return false;
        }

        $shipping_address = $order['shipping_address'];
        $phone =  empty($shipping_address['phone_number'])?'0000':(string)$shipping_address['phone_number'];
        //$country = $shipping_address['country'];
        $country = $order['market'];
        $data = [
            'create_way' => Order::CREATE_WAY_SYSTEM,
            'shop_id' => $shop_v['id'],
            'source' => $shop_v['platform_type'],
            'relation_no' => $relation_no,
            'date' => $add_time,
            'user_no' => (string)'',
            'country' => $country,
            'city' => empty($shipping_address['city'])?'':$shipping_address['city'],
            'area' => empty($shipping_address['city'])?'':$shipping_address['city'],
            'company_name' => '',
            'buyer_name' => $shipping_address['first_name'].' ' .$shipping_address['last_name'],
            'buyer_phone' => $phone,
            'postcode' => (string)$shipping_address['postal_code'],
            'email' => '',
            'address' => $shipping_address['street_address'],
            'remarks' => '',
            'add_time' => $add_time
        ];

        $goods = [];
        //foreach ($order_info['products'] as $v) {
        $goods_map = (new BuyGoodsService())->getGoodsToSkuCountry($order['article_sku'],$country,Base::PLATFORM_1688);
        $goods_data = $this->dealOrderGoods($goods_map);
        $goods_data = array_merge($goods_data,[
            'goods_name' => $order['title'],
            'goods_num' => $order['quantity'],
            'goods_income_price' => $order['price']['amount'] + $order['price']['vat_amount'],
            'platform_asin' => $order['article_sku'],
        ]);
        $goods[] = $goods_data;
        //}

        return [
            'order' => $data,
            'goods' => $goods,
        ];
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
        $data = [
            'tracking_information' => [
                [
                    'carrier_name'=>$carrier_code,
                    'tracking_number'=>$tracking_number
                ]
            ],
        ];
        $response = $this->getClient()->put('/api/v1/orders/'.$order_id.'/fulfill?order_id='.$order_id, ['json' => $data]);
        return $this->returnBody($response);
    }

    /**
     * 获取类目
     */
    public function getCategory(){
        $response = $this->getClient()->get('/api/v1/categories/SE/en-US');
        $result = $this->returnBody($response);
        return $result;
    }

    /**
     * 获取ASIN获取商品信息
     * @param $asin
     * @return string|array
     */
    public function getProductsToAsin($asin){
        $response = $this->getClient()->get('/api/v1/articles/sku/'.$asin);
        $result = $this->returnBody($response);
        return empty($result['content']) || empty($result['content']['article'])?[]:$result['content']['article'];
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
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        $goods_fyndiq = GoodsFyndiq::find()->where(['goods_no' => $goods['goods_no']])->one();
        if (!empty($goods_shop['platform_goods_id'])) {
            return true;
        }

        $data = $this->dealGoods($goods,$goods_shop,$goods_fyndiq);
        if($data === false){
            return false;
        }
        $response = $this->getClient()->post('/api/v1/articles', ['json' => $data]);
        $up_result = $this->returnBody($response);
        if (empty($up_result) || empty($up_result['id'])) {
            CommonUtil::logs('fyndiq result goods_no:' . $goods['goods_no'] . ' shop_id:' . $shop['id'] . ' ' . json_encode($up_result), 'add_products');
            return false;
        }


        $goods_shop->platform_goods_id = (string)$up_result['id'];
        $goods_shop->save();
        return true;
    }

    /**
     * 更新商品
     * @param $goods
     * @return bool
     * @throws Exception
     */
    public function updateGoods($goods)
    {
        $shop = $this->shop;
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        $goods_fyndiq = GoodsFyndiq::find()->where(['goods_no' => $goods['goods_no']])->one();
        if (empty($goods_shop['platform_goods_id'])) {
            return false;
        }

        $data = $this->dealGoods($goods,$goods_shop,$goods_fyndiq,true);
        if($data === false){
            return false;
        }
        $response = $this->getClient()->put('/api/v1/articles/'.$goods_shop['platform_goods_id'], ['json' => $data]);
        if ($response->getStatusCode() == 204) {
            return true;
        }
        $body = $response->getBody();
        CommonUtil::logs('fyndiq result goods_no:' . $goods['goods_no'] . ' shop_id:' . $shop['id'] . ' ' . json_encode($body), 'update_products');
        return false;
    }

    public function dealGoods($goods,$goods_shop,$goods_fyndiq,$is_update = false){
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
        }

        $category_id = $goods_fyndiq['o_category_name'];

        //$colour_map = OzonPlatform::$colour_map;
        //$colour = empty($colour_map[$goods['colour']])?'':$colour_map[$goods['colour']];

        $images = [];
        $image = json_decode($goods['goods_img'], true);
        $main_image = '';
        $i = 0;
        foreach ($image as $v) {
            if ($i > 5) {
                break;
            }
            $i++;
            if($i == 1){
                $main_image = $v['img'];
                continue;
            }
            $images[] = str_replace('http://','https://',$v['img']);
        }

        $goods_name = CommonUtil::usubstr($goods['goods_name'], 60);
        $data = [];
        $data['sku'] = $goods['sku_no'];
        $data['categories'] = [$category_id];
        $properties = [];
        //多变体
        if($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
            $variational_properties = [];
            if(!empty($goods['csize'])) {
                $properties[] = [
                    'name' => 'size',
                    "value" => $goods['csize'],
                    "language" => "en-US",
                ];
                $variational_properties[] = 'size';
            }
            if(!empty($goods['ccolour'])) {
                $properties[] = [
                    'name' => 'color',
                    "value" => $goods['ccolour'],
                    "language" => "en-US",
                ];
                $variational_properties[] = 'color';
            }
            $data['parent_sku'] = $goods['goods_no'];
            $data['variational_properties'] = $variational_properties;
        }
        if($goods_shop['shop_id'] == 482) {//品牌
            $data['brand'] = 'Sanlindou';
        }
        $data['properties'] = $properties;
        $data['status'] = $stock?'for sale':'paused';
        //$data['status'] = 'paused';
        if(!$is_update) {
            $data['quantity'] = $stock?1000:0;
        }
        $data['main_image'] = $main_image;
        $data['images'] = $images;
        $data['markets'] = ["SE"];
        $data['title'][] = ["language"=>'en-US',"value"=>$goods_name];
        $data['description'][] = ["language"=>'en-US',"value"=>$this->dealGoodsContent($goods_fyndiq, false)];
        if(!$is_update) {
            $data['price'][] = ["market"=>'SE',"value"=>["amount"=>$price*1,'currency'=>"SEK"]];
            $data['original_price'][] = ["market"=>'SE',"value"=>["amount"=>$price*2,'currency'=>"SEK"]];
        }
        $data['shipping_time'][] = ["market"=>'SE',"min"=>13,'max'=>21];
        return $data;
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
        $shop = $this->shop;
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        if(empty($goods_shop['platform_goods_id'])) {
            $sku_no = $goods['sku_no'];
            $product = $this->getProductsToAsin($sku_no);
            if(empty($product) || empty($product['id'])){
                return -1;
            }
            $goods_shop->platform_goods_id = $product['id'];
            $goods_shop->save();
        }

        if(empty($goods_shop['platform_goods_id'])){
            return -1;
        }

        $response = $this->getClient()->put('/api/v1/articles/'.$goods_shop['platform_goods_id'].'/quantity', ['json' => [
            'quantity' => $stock?100:0
        ]]);
        if($response->getStatusCode() == 204){
            return 1;
        }
        return 0;
    }

    /**
     * 更新价格
     * @param $goods
     * @param $price
     * @return int
     */
    public function updatePrice($goods,$price)
    {
        $shop = $this->shop;
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        if(empty($goods_shop['platform_goods_id'])) {
            $sku_no = $goods['sku_no'];
            $product = $this->getProductsToAsin($sku_no);
            if(empty($product) || empty($product['id'])){
                return -1;
            }
            $goods_shop->platform_goods_id = $product['id'];
            $goods_shop->save();
        }

        if(empty($goods_shop['platform_goods_id'])){
            return -1;
        }

        $response = $this->getClient()->put('/api/v1/articles/'.$goods_shop['platform_goods_id'].'/price', ['json' => [
            'price' => [
                ["market"=>'SE',"value"=>["amount"=>$price*1,'currency'=>"SEK"]]
            ],
            'original_price' => [
                ["market"=>'SE',"value"=>["amount"=>$price*2,'currency'=>"SEK"]]
            ]
        ]]);
        if($response->getStatusCode() == 204){
            return 1;
        }
        return 0;
    }

    /**
     * 删除商品
     */
    public function delGoods($goods_shop)
    {
        $id = '';
        if (!empty($goods_shop['platform_goods_id'])) {
            $id = $goods_shop['platform_goods_id'];
        } else {
            $goods = GoodsChild::find()->where(['cgoods_no' => $goods_shop['cgoods_no']])->one();
            $sku_no = $goods['sku_no'];
            $product = $this->getProductsToAsin($sku_no);
            if (!empty($product) && !empty($product['id'])) {
                $id = $product['id'];
            }
        }

        if (!empty($id)) {
            $response = $this->getClient()->delete('/api/v1/articles/' . $id);
            if ($response->getStatusCode() == 204) {
                return true;
            }
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
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            $body = $response->getBody();
        }
        return json_decode($body, true);
    }

}