<?php
namespace common\services\api;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\Category;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsHepsiglobal;
use common\models\goods\GoodsMicrosoft;
use common\models\goods\GoodsWalmart;
use common\models\GoodsShop;
use common\models\Order;
use common\models\platform\PlatformCategory;
use common\models\Shop;
use common\services\buy_goods\BuyGoodsService;
use common\services\goods\GoodsService;
use common\services\goods\platform\HepsiglobalPlatform;
use common\services\goods\platform\MicrosoftPlatform;
use common\services\goods\platform\WalmartPlatform;
use Psr\Http\Message\ResponseInterface;
use yii\base\Exception;
use yii\helpers\ArrayHelper;

/**
 * Class WalmartService
 * @package common\services\api
 * https://developer.walmart.com/api/us/mp/auth#operation/tokenAPI
 */
class WalmartService extends BaseApiService
{

    public $base_url = 'https://marketplace.walmartapis.com/';

    /**
     * @return \GuzzleHttp\Client
     */
    public function getClient($has_token = true)
    {
        $headers = [
            'WM_SVC.NAME' => 'Walmart Marketplace',
            'WM_QOS.CORRELATION_ID' => CommonUtil::uuid(),
            //'WM_CONSUMER.CHANNEL.TYPE' => '',
            'Authorization' => 'Basic ' . base64_encode($this->client_key .':'. $this->secret_key),
            'Accept' => 'application/json',
        ];
        if ($has_token) {
            $headers['WM_SEC.ACCESS_TOKEN'] = $this->getToken();
        }
        $client = new \GuzzleHttp\Client([
            'headers' => $headers,
            'timeout' => 30,
            'http_errors' => true,
            'base_uri' => $this->base_url,
        ]);
        return $client;
    }

    /**
     * 获取token
     */
    public function getToken()
    {
        $cache = \Yii::$app->redis;
        $cache_token_key = 'com::walmart::token::'.$this->client_key;
        $token = $cache->get($cache_token_key);
        if (empty($token)) {
            //加锁
           /*$lock = 'com::walmart::token::lock_key'.$this->client_key;
            $request_num = $cache->incrby($lock,1);
            if($request_num == 1) {
                $cache->expire($lock, 60);
            }

            //次数太多 可能已经造成死锁
            if($request_num > 500) {
                $cache->del($lock);
            }

            if($request_num > 1) {
                sleep(1);
                return $this->getToken();
            }*/
            $response = $this->getClient(false)->post('v3/token', [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                ]
            ]);
            $result = $this->returnBody($response);
            CommonUtil::logs('walmart_token result' . json_encode($result) . ' shop_id:' . $this->shop['id'], 'walmart_token');
            if (empty($result['access_token'])) {
                throw new Exception('token获取失败');
            }
            $token = $result['access_token'];
            $cache->setex($cache_token_key, $result['expires_in'] - 60, $token);
        }
        return $token;
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
        //last_updated
        $response = $this->getClient()->get('/v3/orders?limit=200&createdStartDate=' . self::toDate($add_time) . '&createdEndDate=' . self::toDate($end_time));
        $lists = $this->returnBody($response);
        return empty($lists['list']) || empty($lists['list']['elements']) || empty($lists['list']['elements']['order']) ? [] : $lists['list']['elements']['order'];
    }

    /**
     * 确认订单
     * @param $order_id
     * @return string
     */
    public function getConfirmOrder($order_id)
    {
        $response = $this->getClient()->post('/v3/orders/'.$order_id.'/acknowledge');
        return $this->returnBody($response);
    }

    /**
     * 获取订单详情
     * @param $order_id
     * @return string|array
     */
    public function getOrderInfo($order_id)
    {
        $response = $this->getClient()->get('/v3/orders/'.$order_id);
        $lists = $this->returnBody($response);
        return empty($lists)||empty($lists['order'])?[]:$lists['order'];
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
        if(empty($arrival_time)){
            $arrival_time = strtotime("+30 day",strtotime(date('Y-m-d')));
        } else {
            $arrival_time = $arrival_time + 2 * 60 * 60 * 24;
        }
        $arrival_time = date('Y-m-d',$arrival_time);
        $order = Order::find()->where(['relation_no'=>$order_id])->one();

        $data = [];
        $order_api = $this->getOrderInfo($order_id);
        foreach ($order_api['orderLines']['orderLine'] as $order_v){
            $trackingInfo = [
                'shipDateTime' => strtotime(date('Y-m-d')) * 1000,
                'carrierName' => [
                    'carrier' => $carrier_code
                ],
                'methodCode' => 'Standard',
                'trackingNumber' => $tracking_number
            ];
            if(!empty($tracking_url)) {
                $trackingInfo['trackingURL'] = $tracking_url;
            }
            $data[] = [
                'lineNumber' => $order_v['lineNumber'],
                'sellerOrderId' => $order['order_id'],
                'orderLineStatuses' => [
                    'orderLineStatus' => [
                        [
                            'status'=>'Shipped',
                            'statusQuantity' => [
                                'unitOfMeasurement' => $order_v['orderLineQuantity']['unitOfMeasurement'],
                                'amount' => $order_v['orderLineQuantity']['amount']
                            ],
                            'trackingInfo' => $trackingInfo
                        ]
                    ]
                ]
            ];
        }
        
        /*{
            "orderShipment": {
            "orderLines": {
                "orderLine": [
        {
            "lineNumber": "1",
          "intentToCancelOverride": false,
          "sellerOrderId": "92344",
          "orderLineStatuses": {
            "orderLineStatus": [
              {
                  "status": "Shipped",
                "statusQuantity": {
                  "unitOfMeasurement": "EACH",
                  "amount": "1"
                },
                "trackingInfo": {
                  "shipDateTime": 1580821866000,
                  "carrierName": {
                      "carrier": "UPS"
                  },
                  "methodCode": "Standard",
                  "trackingNumber": "22344",
                  "trackingURL": "http://walmart/tracking/ups?&type=MP&seller_id=12345&promise_date=03/02/2020&dzip=92840&tracking_numbers=92345"
                },
                "returnCenterAddress": {
                  "name": "walmart",
                  "address1": "walmart store 2",
                  "city": "Huntsville",
                  "state": "AL",
                  "postalCode": "35805",
                  "country": "USA",
                  "dayPhone": "12344",
                  "emailId": "walmart@walmart.com"
                }
              }
            ]
          }
        }
      ]
    }
  }
}*/
        $response = $this->getClient()->post('/v3/orders/'.$order_id.'/shipping',[
            'json'=> [
                'orderShipment' => [
                    'orderLines' => [
                        'orderLine' => $data
                    ]
                ]
            ]
        ]);
        $result = $this->returnBody($response);
        if(empty($result['order'])) {
            CommonUtil::logs('walmart getOrderSend error id:' . $order_id . ' data:' . json_encode($data) . ' result:' . json_encode($result), 'fapi');
            return '';
        }

        return $result;
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
        $add_time = intval($order['orderDate']/1000);

        $relation_no = $order['purchaseOrderId'];
        $exist = Order::find()->where(['relation_no' => $relation_no])->one();
        if ($exist) {
            return false;
        }

        $shipping_address = $order['shippingInfo']['postalAddress'];
        $buyer_phone = empty($order['shippingInfo']['phone'])?'0000':$order['shippingInfo']['phone'];
        $country = 'US';

        $data = [
            'create_way' => Order::CREATE_WAY_SYSTEM,
            'shop_id' => $shop_v['id'],
            'source' => $shop_v['platform_type'],
            'relation_no' => $relation_no,
            'date' => $add_time,
            'user_no' => (string)'',
            'country' => $country,
            'city' => empty($shipping_address['city'])?'':$shipping_address['city'],
            'area' => empty($shipping_address['state'])?'':$shipping_address['state'],
            'company_name' => '',
            'buyer_name' => $shipping_address['name'],
            'buyer_phone' => $buyer_phone,
            'postcode' => (string)$shipping_address['postalCode'],
            'email' =>'',
            'address' => $shipping_address['address1'].' '. $shipping_address['address2'],
            'remarks' => '',
            'add_time' => $add_time
        ];

        $goods = [];
        $currency_code = '';
        //$platform_fee = 0;
        $goods_data_lists = [];
        foreach ($order['orderLines']['orderLine'] as $v) {
            $order_status = current($v['orderLineStatuses']['orderLineStatus']);
            if (!empty($order_status) && $order_status['status'] == 'Cancelled') {
                continue;
            }

            if(!empty($goods_data_lists[$v['item']['sku']])) {
                $goods_data_lists[$v['item']['sku']]['goods_num'] += $v['orderLineQuantity']['amount'];
                continue;
            }

            $currency_code = 'USD';
            $charge = $v['charges']['charge'];
            $price = 0;
            foreach ($charge as $charge_v) {
                if ($charge_v['chargeType'] == 'PRODUCT') {
                    $price = $charge_v['chargeAmount']['amount'];
                    $currency_code = $charge_v['chargeAmount']['currency'];
                    /*if(!empty($charge_v['tax']) && !empty($charge_v['tax']['taxAmount']) && !empty($charge_v['tax']['taxAmount']['amount'])) {
                        $platform_fee += $charge_v['tax']['taxAmount']['amount'];
                    }*/
                }
            }

            $goods_data_lists[$v['item']['sku']] = [
                'platform_sku_no' =>$v['item']['sku'],
                'goods_name' => $v['item']['productName'],
                'goods_income_price' => $price/$v['orderLineQuantity']['amount'],
                'goods_num' => $v['orderLineQuantity']['amount']
            ];
        }

        foreach ($goods_data_lists as $v) {
            $where = [
                'shop_id'=>$shop_v['id'],
                'platform_sku_no'=>$v['platform_sku_no']
            ];
            $platform_sku_no = GoodsShop::find()->where($where)->select('cgoods_no')->scalar();
            if(empty($platform_sku_no)) {
                $goods_map = (new BuyGoodsService())->getGoodsToSkuCountry($v['platform_sku_no'], $country, Base::PLATFORM_1688, 1);
            } else {
                $goods_map = (new BuyGoodsService())->getGoodsToSkuCountry($platform_sku_no, $country, Base::PLATFORM_1688, 2);
            }
            $goods_data = $this->dealOrderGoods($goods_map);
            $goods_data = array_merge($goods_data,[
                'goods_name' => $v['goods_name'],
                'goods_num' => $v['goods_num'],
                'goods_income_price' => $v['goods_income_price']
            ]);
            $goods[] = $goods_data;
        }

        if(empty($goods)) {
            return false;
        }

        if(!empty($currency_code)) {
            $data['currency'] = $currency_code;
        }
        //$data['platform_fee'] = $platform_fee;

        return [
            'order' => $data,
            'goods' => $goods,
        ];
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
     * @return string|array
     */
    public function getProducts(){
        $response = $this->getClient()->get('/v3/items');
        $result = $this->returnBody($response);
        return $result;
    }


    /**
     * 添加商品
     * @param $goods_lists
     * @return bool
     * @throws Exception
     */
    public function batchAddGoods($goods_lists,$category_id)
    {
        $shop = $this->shop;
        $goods_data = [];
        foreach ($goods_lists as $goods) {
            $goods_walmart = GoodsWalmart::find()->where(['goods_no' => $goods['goods_no']])->one();
            $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
            if (!empty($goods_shop['platform_goods_id'])) {
                continue;
            }

            $info = $this->dealGoodsInfo($goods,$goods_walmart,$goods_shop);
            if(!$info) {
                continue;
            }
            $goods_data[] = $info;
        }

        $category_map = [
            "Animal Accessories"=>"animal_accessories",
            "Animal Food"=>"animal_food",
            "Animal Health & Grooming"=>"animal_health_and_grooming",
            "Animal Other"=>"animal_other",
            "Art & Craft"=>"art_and_craft_other",
            "Baby Diapering, Care, & Other"=>"baby_other",
            "Baby Food"=>"baby_food",
            "Baby Furniture"=>"baby_furniture",
            "Baby Toys"=>"baby_toys",
            "Baby Transport"=>"child_car_seats",
            "Beauty, Personal Care, & Hygiene"=>"personal_care",
            "Bedding"=>"bedding",
            "Books & Magazines"=>"books_and_magazines",
            "Building Supply"=>"building_supply",
            "Cameras & Lenses"=>"cameras_and_lenses",
            "Carriers & Accessories"=>"carriers_and_accessories_other",
            "Cases & Bags"=>"cases_and_bags",
            "Cell Phones"=>"cell_phones",
            "Ceremonial Clothing & Accessories"=>"ceremonial_clothing_and_accessories",
            "Clothing"=>"clothing_other",
            "Computer Components"=>"computer_components",
            "Computers"=>"computers",
            "Costumes"=>"costumes",
            "Cycling"=>"cycling",
            "Decorations & Favors"=>"decorations_and_favors",
            "Electrical"=>"electrical",
            "Electronics Accessories"=>"electronics_accessories",
            "Electronics Cables"=>"electronics_cables",
            "Electronics Other"=>"electronics_other",
            "Food & Beverage"=>"food_and_beverage_other",
            "Footwear"=>"footwear_other",
            "Fuels & Lubricants"=>"fuels_and_lubricants",
            "Funeral"=>"funeral",
            "Furniture"=>"furniture_other",
            "Garden & Patio"=>"garden_and_patio_other",
            "Gift Supply & Awards"=>"gift_supply_and_awards",
            "Grills & Outdoor Cooking"=>"grills_and_outdoor_cooking",
            "Hardware"=>"hardware",
            "Health & Beauty Electronics"=>"health_and_beauty_electronics",
            "Home Decor, Kitchen, & Other"=>"home_other",
            "Household Cleaning Products & Supplies"=>"cleaning_and_chemical",
            "Instrument Accessories"=>"instrument_accessories",
            "Jewelry"=>"jewelry_other",
            "Land Vehicles"=>"land_vehicles",
            "Large Appliances"=>"large_appliances",
            "Medical Aids & Equipment"=>"medical_aids",
            "Medicine & Supplements"=>"medicine_and_supplements",
            "Movies"=>"movies",
            "Music Cases & Bags"=>"music_cases_and_bags",
            "Music"=>"music",
            "Musical Instruments"=>"musical_instruments",
            "Office"=>"office_other",
            "Optical"=>"optical",
            "Optics"=>"optics",
            "Other"=>"other_other",
            "Photo Accessories"=>"photo_accessories",
            "Plumbing & HVAC"=>"plumbing_and_hvac",
            "Printers, Scanners, & Imaging"=>"printers_scanners_and_imaging",
            "Safety & Emergency"=>"safety_and_emergency",
            "Software"=>"software",
            "Sound & Recording"=>"sound_and_recording",
            "Sport & Recreation Other"=>"sport_and_recreation_other",
            "Storage"=>"storage",
            "TV Shows"=>"tv_shows",
            "TVs & Video Displays"=>"tvs_and_video_displays",
            "Tires"=>"tires",
            "Tools & Hardware Other"=>"tools_and_hardware_other",
            "Tools"=>"tools",
            "Toys"=>"toys_other",
            "Vehicle Other"=>"vehicle_other",
            "Vehicle Parts & Accessories"=>"vehicle_parts_and_accessories",
            "Video Games"=>"video_games",
            "Video Projectors"=>"video_projectors",
            "Watches"=>"watches_other",
            "Watercraft"=>"watercraft",
            "Wheels & Wheel Components"=>"wheels_and_wheel_components",
        ];

        /**
        "cases_and_bags",
        "building_supply",
        "tires",
        "computer_components",
        "health_and_beauty_electronics",
        "furniture_other",
        "decorations_and_favors",
        "hardware",
        "child_car_seats",
        "food_and_beverage_other",
        "electronics_other",
        "electronics_cables",
        "plumbing_and_hvac",
        "video_games",
        "other_other",
        "safety_and_emergency",
        "jewelry_other",
        "books_and_magazines",
        "tools",
        "sport_and_recreation_other",
        "carriers_and_accessories_other",
        "animal_food",
        "baby_toys",
        "cleaning_and_chemical",
        "ceremonial_clothing_and_accessories",
        "music_cases_and_bags",
        "computers",
        "grills_and_outdoor_cooking",
        "personal_care",
        "bedding",
        "storage",
        "animal_accessories",
        "baby_food",
        "electrical",
        "medical_aids",
        "music",
        "art_and_craft_other",
        "medicine_and_supplements",
        "toys_other",
        "wheels_and_wheel_components",
        "footwear_other",
        "tv_shows",
        "animal_health_and_grooming",
        "video_projectors",
        "cameras_and_lenses",
        "sound_and_recording",
        "watercraft",
        "funeral",
        "watches_other",
        "large_appliances",
        "baby_furniture",
        "costumes",
        "instrument_accessories",
        "optical",
        "home_other",
        "cycling",
        "gift_supply_and_awards",
        "fuels_and_lubricants",
        "baby_other",
        "vehicle_other",
        "animal_other",
        "optics",
        "garden_and_patio_other",
        "cell_phones",
        "musical_instruments",
        "printers_scanners_and_imaging",
        "movies",
        "office_other",
        "tvs_and_video_displays",
        "tools_and_hardware_other",
        "electronics_accessories",
        "vehicle_parts_and_accessories",
        "land_vehicles",
        "clothing_other",
        "photo_accessories",
        "software"
         */
        //$category_name = strtolower($category_id);
        //$category_name = str_replace([' ','&'],['_','and'],$category_name);
        $category_name = $category_map[$category_id];
        $data = [
            'MPItemFeedHeader' => [
                'sellingChannel'=>'marketplace',
                'processMode' => 'REPLACE',
                'subset' => 'EXTERNAL',
                'locale' => 'en',
                'version' => '4.6',
                'subCategory' => $category_name,
            ],
            'MPItem' => $goods_data
        ];

        /*$path = \Yii::$app->params['path']['file'];
        $file_dir = "walmart/" . date('Y-m');
        $wal_dir = $path . '/' . $file_dir;
        !is_dir($wal_dir) && @mkdir($wal_dir, 0777, true);
        $file_path = $wal_dir . '/'.date('nhis').'.json';
        file_put_contents($file_path, json_encode($data,JSON_UNESCAPED_UNICODE));*/
        $response = $this->getClient()->post('/v3/feeds?feedType=MP_ITEM', ['json' =>$data]);
        $up_result = $this->returnBody($response);
        $cgoods_no = ArrayHelper::getColumn($goods_lists, 'cgoods_no');
        CommonUtil::logs('walmart result goods_no:' . json_encode($cgoods_no) . ' shop_id:' . $shop['id'] . ' data:'. json_encode($data,JSON_UNESCAPED_UNICODE) .' result:' . json_encode($up_result), 'add_products');
        if (empty($up_result) || empty($up_result['feedId'])) {
            return false;
        }

        return $up_result['feedId'];
    }

    /**
     * 处理商品信息
     * @param $goods
     * @param $goods_walmart
     * @param $goods_shop
     * @param bool $is_parent
     * @return array|bool
     */
    public function dealGoodsInfo($goods, $goods_walmart, $goods_shop)
    {
        $walmart_ser = new WalmartPlatform();
        $goods_name = $walmart_ser->dealTitle($goods_walmart['goods_name']);
        $sku_no = !empty($goods_shop['platform_sku_no']) ? $goods_shop['platform_sku_no'] : $goods['sku_no'];
        $price = $goods_shop['price'];

        $category_id = trim($goods_walmart['o_category_name']);
        if (empty($category_id)) {
            return false;
        }

        $size = GoodsService::getSizeArr($goods['size']);
        $l = !empty($size['size_l']) && $size['size_l'] > 1 ? (int)$size['size_l'] : 0;
        $h = !empty($size['size_h']) && $size['size_h'] > 1 ? (int)$size['size_h'] : 0;
        $w = !empty($size['size_w']) && $size['size_w'] > 1 ? (int)$size['size_w'] : 0;

        $weight = $goods['real_weight'] > 0 ? $goods['real_weight'] : $goods['weight'];
        $weight = max($weight, 0.1);


        if (!in_array($goods['status'], [Goods::GOODS_STATUS_VALID, Goods::GOODS_STATUS_WAIT_MATCH])) {
            return false;
        }

        $content = $walmart_ser->dealContent($goods_walmart);
        $desc = $walmart_ser->dealDesc($goods_walmart);

        $main_image = '';
        $other_image = [];
        $i = 0;
        $images = json_decode($goods['goods_img'], true);
        foreach ($images as $v) {
            if ($i > 5) {
                break;
            }
            $v['img'] = str_replace('image.chenweihao.cn','img.chenweihao.cn',$v['img']);
            $i++;
            if ($i == 1) {
                $main_image = $v['img'].'?imageMogr2/thumbnail/!1000x1000r';//设置主图1000
                continue;
            }
            $other_image[] = $v['img'];;
        }

        $info = [];
        $info['Orderable'] = [
            'sku' => $sku_no,
            'productIdentifiers' => [
                'productIdType' => 'EAN',
                'productId' => $goods_shop['ean']
            ],
            'productName' => $goods_name,
            'brand' => 'Unbranded',
            'price' => (float)$price,
            'ShippingWeight' => (float)$weight,
            //'electronicsIndicator' => 'No',
            'chemicalAerosolPesticide' => 'No',
        ];

        $colour = empty($goods['ccolour']) ? $goods['colour'] : $goods['ccolour'];

        $params = [];
        if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
            $variational_properties = [];
            if (!empty($goods['csize'])) {
                $params['size'] = $goods['csize'];
                $variational_properties[] = 'size';
            }
            if (!empty($goods['ccolour'])) {
                $params['color'][] = $goods['ccolour'];
                $variational_properties[] = 'color';
            }
            $params['variantGroupId'] = $goods['goods_no'];
            $params['variantAttributeNames'] = $variational_properties;
            $content = 'This item is for sale:' . $goods['ccolour'] . ' ' . $goods['csize'] . PHP_EOL . $content;
        }

        $mp_attr_param_file = file_get_contents(\Yii::$app->params['path']['base'].'/json/MP_ITEM_SPEC_4.6.json');
        $mp_attr_param_file = json_decode($mp_attr_param_file,true);
        $attr_param = $mp_attr_param_file['properties']['MPItem']['items']['properties']['Visible']['properties'];
        if(empty($attr_param[$category_id])) {
            return false;
        }

        $attr_param = $attr_param[$category_id];
        foreach ($attr_param['properties'] as $param_k => $param_v) {
            if (in_array($param_v, ['size', 'color', 'variantGroupId', 'variantAttributeNames'])) {
                continue;
            }

            if ($param_k == 'shortDescription') {
                $params[$param_k] = $content;
                continue;
            }

            if ($param_k == 'mainImageUrl') {
                $params[$param_k] = $main_image;
                continue;
            }

            if ($param_k == 'productSecondaryImageURL') {
                $params[$param_k] = $other_image;
                continue;
            }

            if ($param_k == 'prop65WarningText') {
                $params[$param_k] = 'None';
                continue;
            }

            if ($param_k == 'smallPartsWarnings') {
                $params[$param_k][] = '0 - No warning applicable';
                continue;
            }

            //五要素
            if ($param_k == 'keyFeatures' && !empty($desc)) {
                $params[$param_k][] = $desc;
                continue;
            }

            //颜色
            if ($param_k == 'colorCategory') {
                $color_map = [
                    "Blue",
                    "Brown",
                    "Gold",
                    "Gray",
                    "Purple",
                    "Clear",
                    "Yellow",
                    "Multi-color",
                    "Black",
                    "Beige",
                    "Pink",
                    "Orange",
                    "Green",
                    "White",
                    "Red",
                    "Off-White",
                    "Silver",
                    "Bronze"
                ];
                foreach ($color_map as $c_v) {
                    if (CommonUtil::compareStrings($colour, $c_v)) {
                        $params[$param_k][] = $colour;
                    }
                }
                continue;
            }

            //长
            if ($param_k == 'assembledProductLength' && $l > 0) {
                $params[$param_k]['measure'] = (int)ceil($l * 0.3937);
                $params[$param_k]['unit'] = 'in';
                continue;
            }

            //宽
            if ($param_k == 'assembledProductWidth' && $w > 0) {
                $params[$param_k]['measure'] = (int)ceil($w * 0.3937);
                $params[$param_k]['unit'] = 'in';
                continue;
            }

            //高
            if ($param_k == 'assembledProductHeight' && $h > 0) {
                $params[$param_k]['measure'] = (int)ceil($h * 0.3937);
                $params[$param_k]['unit'] = 'in';
                continue;
            }

            //重量
            if ($param_k == 'assembledProductWeight' && $weight > 0.1) {
                $params[$param_k]['measure'] = (float)$weight;
                $params[$param_k]['unit'] = 'kg';
                continue;
            }

            //必填项目
            if (!in_array($param_k,$attr_param['required'])) {
                continue;
            }

            if ($param_k == 'stateRestrictions') {
                $params[$param_k][]['stateRestrictionsText'] = 'None';
                continue;
            }

            switch ($param_v['type']) {
                case 'number':
                case 'integer':
                    $params[$param_k] = 1;
                    break;
                case 'string':
                default:
                    if (!empty($param_v['enum'])) {
                        $params[$param_k] = current($param_v['enum']);
                    } else {
                        $params[$param_k] = '1';
                    }
            }
        }

        $info['Visible'] = [
            $category_id => $params
        ];

        return $info;
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
        $data = [];
        $data['sku'] = $goods_shop['platform_sku_no'];
        $data['quantity'] = [
            'amount' => $stock?1000:0,
            'unit' => 'EACH',
        ];
        $response = $this->getClient()->put('/v3/inventory', ['json' =>$data]);
        $result = $this->returnBody($response);
        if (empty($result['sku'])) {
            return false;
        }
        return 1;
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
        //$sku_no = $goods['sku_no'];
        $shop = $this->shop;
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        if(!empty($goods_shop['platform_sku_no'])){
            $sku_no = $goods_shop['platform_sku_no'];
        } else{
            $sku_no = $goods['sku_no'];
        }
        $response = $this->getClient()->put('/v3/price', ['json' => [
            'sku' => $sku_no,
            'pricing' => [
                [
                    'currentPriceType' => 'BASE',
                    "currentPrice" => [
                        "amount" => (float)$price,
                        'currency' => 'USD'
                    ],
                ]
            ]
        ]
        ]);
        $up_result = $this->returnBody($response);
        $up_result = empty($up_result['ItemPriceResponse']) ? [] : $up_result['ItemPriceResponse'];
        if (!empty($up_result)) {
            return 1;
        }
        return 0;
    }

    /**
     * 删除商品
     * @param $goods_shop
     * @return bool
     */
    public function delGoods($goods_shop)
    {
        $product_id = $goods_shop['platform_sku_no'];
        $response = $this->getClient()->delete('/v3/items/' . $product_id);
        $result = $this->returnBody($response);
        if (empty($result['sku'])) {
            return false;
        }
        return true;
    }

    /**
     * @param string $queue_ids
     * @return array|mixed
     */
    public function getQueue($queue_ids)
    {
        $response = $this->getClient()->get('/v3/feeds/'.$queue_ids.'?includeDetails=true');
        $result = $this->returnBody($response);
        return $result;
    }

    public function returnBody($response)
    {
        $body = '';
        if ($response->getStatusCode() == 200) {
            $body = $response->getBody();
        }
        return json_decode($body, true);
    }

}