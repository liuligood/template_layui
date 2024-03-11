<?php

namespace common\services\api;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsFyndiq;
use common\models\GoodsEvent;
use common\models\GoodsShop;
use common\models\Order;
use common\models\Shop;
use common\services\buy_goods\BuyGoodsService;
use common\services\FApiService;
use common\services\goods\GoodsService;
use Psr\Http\Message\ResponseInterface;
use yii\base\Exception;
use yii\helpers\ArrayHelper;

/**
 * Class Wildberries
 * @package common\services\api
 * https://openapi.wb.ru/content/api/cn
 */
class WildberriesService extends BaseApiService
{

    const URL_TYPE_SUPPLIERS = 1;//内容
    const URL_TYPE_STATISTICS = 2;//统计

    /**
     * @return \GuzzleHttp\Client
     */
    public function getClient($url_type = self::URL_TYPE_SUPPLIERS)
    {
        switch ($url_type){
            case self::URL_TYPE_STATISTICS:
                $base_host = 'statistics-api';
                break;
            case self::URL_TYPE_SUPPLIERS:
            default:
                $base_host = 'suppliers-api';
                break;
        }
        $client = new \GuzzleHttp\Client([
            'headers' => [
                'Authorization' => $this->secret_key,
                'Content-Type' => 'application/json'
            ],
            'base_uri' => 'https://'.$base_host.'.wildberries.ru',
            'timeout' => 30,
        ]);
        return $client;
    }


    /**
     * 获取类目
     */
    public function getCategory()
    {
        $name = '';
        $response = $this->getClient()->get('/content/v1/object/all?name=' . $name . '&top=10000');
        $result = $this->returnBody($response);
        return !empty($result['data'])?$result['data']:[];
    }

    /**
     * 获取类目属性
     */
    public function getCategoryAttributes($name)
    {
        $cache = \Yii::$app->cache;
        $cache_key = 'com::wildberries::category::attr_' . $name;
        $attr = $cache->get($cache_key);
        $attr = empty($attr) ? [] : json_decode($attr, true);
        if (empty($attr)) {
            $response = $this->getClient()->get('/content/v1/object/characteristics/list/filter?name=' . urlencode($name));
            $result = $this->returnBody($response);
            $attr = !empty($result['data']) ? $result['data'] : [];
            $cache->set($cache_key, json_encode($attr), 24 * 60 * 60);
        }
        return $attr;
    }

    /**
     * 获取ASIN获取商品信息
     * @param $asin
     * @return string|array
     */
    public function getProductsToAsin($asin)
    {
        $response = $this->getClient()->post('/content/v1/cards/cursor/list', [
            'json' => [
                "sort" => [
                    'cursor' => ['limit'=>10],
                    'filter' => [
                        'textSearch' => $asin,
                        'withPhoto' => -1
                    ],
                ]
            ],
            'http_errors' => false
        ]);
        $result = $this->returnBody($response);
        return empty($result['data']) || empty($result['data']['cards'])? [] : current($result['data']['cards']);
    }

    /**
     * 获取商品列表
     * @return string|array
     */
    public function getProductsList()
    {
        $response = $this->getClient()->post('/content/v1/cards/cursor/list', [
            'json' => [
                "sort" => [
                    'cursor' => ['limit'=>1000],
                    'filter' => [
                        'withPhoto' => -1
                    ],
                ]
            ],
            'http_errors' => false
        ]);
        $result = $this->returnBody($response);
        return empty($result['data']) || empty($result['data']['cards'])? [] : $result['data']['cards'];
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
        if (empty($price)) {
            return 1;
        }
        return $this->updatePrice($goods, $price);
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
        if(empty($goods_shop['platform_goods_id'])){
            return -1;
        }

        $response = $this->getClient()->post('/public/api/v1/prices', ['json' => [
                [
                    'nmId' => (int)$goods_shop['platform_goods_id'],
                    'price' => (int)$price,
                ]
            ]
        ]);
        $result = $this->returnBody($response);
        if(!empty($result) && !empty($result['uploadId'])){
            return 1;
        }
        return 0;
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
            $add_time = strtotime($add_time) - 10 * 24 * 60 * 60;
            $add_time = date("Y-m-d", $add_time);
        }
        if (empty($end_time)) {
            $end_time = date("Y-m-d", time() + 2 * 60 * 60);
        }
        $response = $this->getClient(self::URL_TYPE_STATISTICS)->get('/api/v1/supplier/orders?dateFrom='.$add_time.'&flag=0');
        $lists = $this->returnBody($response);
        return empty($lists) ? [] : $lists;
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
        $add_time = strtotime($order['date']);

        $relation_no = $order['gNumber'];
        $exist = Order::find()->where(['shop_id'=>$shop_v['id'],'delivery_order_id' => $order['srid']])->one();
        if ($exist) {
            return false;
        }

        /*if($order['isCancel']) {
            return false;
        }*/

        /*{
            "date": "2024-02-13T19:57:47",
            "lastChangeDate": "2024-02-13T23:40:11",
            "warehouseName": "Коледино",
            "countryName": "россия",
            "oblastOkrugName": "южный федеральный округ",
            "regionName": "Республика Адыгея",
            "supplierArticle": "G06293236533866",
            "nmId": 198628845,
            "barcode": "G06293236533866",
            "category": "Дом",
            "subject": "Светильники",
            "brand": "SLD-LIGHT",
            "techSize": "0",
            "incomeID": 16681062,
            "isSupply": false,
            "isRealization": true,
            "totalPrice": 3449,
            "discountPercent": 0,
            "spp": 19,
            "finishedPrice": 2793,
            "priceWithDisc": 3449,
            "isCancel": false,
            "cancelDate": "0001-01-01T00:00:00",
            "orderType": "Клиентский",
            "sticker": "16642624176",
            "gNumber": "91206723447706489510",
            "srid": "5872081921843387608.0.0"
          }*/

        $phone = '';

        $country = 'RU';
        $integrated_logistics = Order::INTEGRATED_LOGISTICS_NO;
        $logistics_channels_name = '';

        $data = [
            'create_way' => Order::CREATE_WAY_SYSTEM,
            'shop_id' => $shop_v['id'],
            'source' => $shop_v['platform_type'],
            'relation_no' => $relation_no,
            'delivery_order_id' => $order['srid'],
            'date' => $add_time,
            'user_no' => (string)'',
            'country' => $country,
            'city' => empty($order['oblastOkrugName'])?'':$order['oblastOkrugName'],
            'area' => empty($order['regionName'])?'':$order['regionName'],
            'company_name' => '',
            'buyer_name' => '',
            'buyer_phone' => $phone,
            'postcode' => (string)'',
            'email' => '',
            'address' => '',
            'remarks' => '',
            'add_time' => $add_time,
            'integrated_logistics' => $integrated_logistics,
            'logistics_channels_name' => $logistics_channels_name,
        ];

        $goods = [];

        $where = [
            'shop_id'=>$shop_v['id'],
            'platform_sku_no'=> $order['supplierArticle']
        ];
        $platform_sku_no = GoodsShop::find()->where($where)->select('cgoods_no')->scalar();
        if(empty($platform_sku_no)) {
            $goods_map = (new BuyGoodsService())->getGoodsToSkuCountry($order['supplierArticle'], $country, Base::PLATFORM_1688, 1);
        } else {
            $goods_map = (new BuyGoodsService())->getGoodsToSkuCountry($platform_sku_no, $country, Base::PLATFORM_1688, 2);
        }
        $goods_data = $this->dealOrderGoods($goods_map);
        $goods_data = array_merge($goods_data,[
            'goods_num' => 1,
            'goods_income_price' => $order['priceWithDisc'],
        ]);
        $goods[] = $goods_data;
        $data['platform_fee'] = $order['priceWithDisc'] - $order['finishedPrice'];
        $data['currency'] = 'RUB';

        //海外仓
        if(!empty($shop_v['warehouse_id'])) {
            $data['warehouse'] = $shop_v['warehouse_id'];
        }

        return [
            'order' => $data,
            'goods' => $goods,
        ];
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
        $update_time = $update_time - 12 * 24 * 60 * 60;
        $update_time = date("Y-m-d", $update_time);
        $response = $this->getClient(self::URL_TYPE_STATISTICS)->get('/api/v1/supplier/orders?dateFrom='.$update_time.'&flag=0');
        $lists = $this->returnBody($response);
        return empty($lists) ? [] : $lists;
    }

    /**
     * 处理取消订单
     * @param $order
     * @return array|bool
     */
    public function dealCancelOrder($order)
    {
        if (!$order['isCancel']) {
            return false;
        }

        $goods = [];
        $cancel_goods = [];

        $relation_no = $order['gNumber'];
        $cancel_time = strtotime($order['cancelDate']);

        if (empty($goods)) {
            return [
                'relation_no' => $relation_no,
                'cancel_time' => $cancel_time,
                'delivery_order_id' => $order['srid'],
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
     * 获取库存列表
     * @param $time
     * @return string|array
     */
    public function getStocksLists($time)
    {
        $time = date("Y-m-d", $time);
        $response = $this->getClient(self::URL_TYPE_STATISTICS)->get('/api/v1/supplier/stocks?dateFrom='.$time);
        $lists = $this->returnBody($response);
        return empty($lists) ? [] : $lists;
    }

    /**
     * @param ResponseInterface $response
     * @return array|string
     */
    public function returnBody($response)
    {
        return json_decode($response->getBody(), true);
    }

}