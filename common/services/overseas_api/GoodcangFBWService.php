<?php
namespace common\services\overseas_api;

use common\components\statics\Base;
use common\models\Category;
use common\models\Order;
use common\models\OrderGoods;
use common\models\sys\ShippingMethod;
use common\services\goods\GoodsService;
use yii\base\Exception;
use yii\helpers\ArrayHelper;

/**
 * 谷仓接口
 * https://open.goodcang.com/
 */
class GoodcangFBWService extends BaseFBWService
{
    /**
     * TOKEN
     * @var string
     */
    public $app_token;
    /**
     * 用户 KEY
     * @var string
     */
    public $app_key;

    /**
     * 基础请求地址
     */
    public $base_url = 'https://oms.goodcang.net/public_open/';

    public function __construct($param)
    {
        if(!empty($param['base_url'])) {
            $this->base_url = $param['base_url'];
        }
        $this->app_token = $param['app_token'];
        $this->app_key = $param['app_key'];
    }

    /**
     * @return \GuzzleHttp\Client
     */
    public function getClient()
    {
        $client = new \GuzzleHttp\Client([
            'headers'=>[
                'Accept'=>'application/json',
                'Content-Type' => 'application/json',
                'app-token'=>$this->app_token,
                'app-key' => $this->app_key
            ],
            'verify' => false,
            'base_uri' => $this->base_url,
            'timeout' => 20,
        ]);
        return $client;
    }

    /**
     * 添加商品
     * @param $cgoods_no
     * @return string
     */
    public function addGoods($cgoods_no)
    {
        $goods = GoodsService::getChildOne($cgoods_no);
        $goods_name = !empty($goods['goods_short_name'])?$goods['goods_short_name']:$goods['goods_name'];
        $goods_name_cn = !empty($goods['goods_short_name_cn'])?$goods['goods_short_name_cn']:$goods['goods_name_cn'];
        $image = json_decode($goods['goods_img'], true);
        $image = empty($image) ? '' : current($image)['img'];
        $size = GoodsService::getSizeArr($goods['size']);
        $category = Category::find()->where(['id' => $goods['category_id']])->asArray()->one();

        $data = [
            'product_sku' => $goods['cgoods_no'],
            'product_name_cn' => $goods_name_cn,
            'product_name_en' => $goods_name,
            'product_weight' => $goods['real_weight'] > 0 ? $goods['real_weight'] : $goods['weight'],
            'product_length' => !isset($size['size_l']) ? 10 : $size['size_l'],
            'product_width' => !isset($size['size_w']) ? 10 : $size['size_w'],
            'product_height' => !isset($size['size_h']) ? 10 : $size['size_h'],
            'contain_battery' => 4,
            'type_of_goods' => 0,
            'cat_id_level2' => 603197,
            'product_declared_name_cn' => empty($category) ? '' : $category['name'],
            'product_declared_name' => empty($category) ? '' : $category['name_en'],
            'product_material' => '金属',
            'branded' => 0,
            'product_link' => $image,
            'export_country' => [['country_code' => 'CN', 'declared_value' => 5]],
            'import_country' => [['country_code' => 'CZ', 'declared_value' => 5]],
            'sku_wrapper_type' => 1,
            'return_auth' => 1,
            'verify' => 1
        ];
        
        $response = $this->getClient()->post('product/add_product', ['json' => $data]);
        $response = (string)$response->getBody();
        $result = json_decode($response, true);

        if (empty($result)) {
            throw new Exception('谷仓接口异常');
        }

        if ($result['ask'] == 'Failure') {
            $error = $result['message'];
            throw new Exception('谷仓请求失败：'.$error);
        }
        
        return $result['product_barcode'];
    }

    /**
     * 打印商品标签
     * @param $cgoods_no
     * @param $is_show 1直接显示，2链接
     * @return string
     * @throws Exception
     */
    public function printGoods($cgoods_no,$is_show = 1)
    {
        $data = [
            'product_sku_arr' => (array)$cgoods_no,
            'print_size' => 2,
            'print_code' => 7,
        ];
        $response = $this->getClient()->post('product/print_sku', [
            'json' => $data
        ]);
        $response = (string)$response->getBody();
        $result = json_decode($response, true);
        if (empty($result) || empty($result['ask']) || $result['ask'] != 'Success') {
            throw new Exception('谷仓接口异常');
        }

        return $result['data'];
    }

    /**
     * 获取商品标签编号
     * @param $cgoods_no
     * @return mixed
     */
    public function getGoodsLabelNo($cgoods_no)
    {
        return $cgoods_no;
    }

    /**
     * 获取库存
     * @param $cgoods_no
     * @return array
     * @throws Exception
     */
    public function getInventory($cgoods_no)
    {
        $data = [
            'product_sku_arr' => (array)$cgoods_no,
            'page' => 1,
            'pageSize' => 200,
        ];
        $response = $this->getClient()->post('inventory/get_product_inventory', [
            'json' => $data
        ]);
        $response = (string)$response->getBody();
        $result = json_decode($response, true);
        if (empty($result) || empty($result['ask']) && $result['ask'] != 'Success') {
            throw new Exception('谷仓接口异常');
        }
        $lists = $result['data'];
        return ArrayHelper::map($lists, 'product_sku', 'sellable');
    }


    /**
     * 获取谷仓所有Sku
     * @param $page
     * @return array
     * @throws Exception
     */
    public function getProductSku($page,$cgoods_no = '')
    {
        $data = [
            'page' => $page,
            'pageSize' => 200,
        ];

        if (!empty($cgoods_no)) {
            $data['product_sku'] = $cgoods_no;
        }

        $response = $this->getClient()->post('product/get_product_sku_list', [
            'json' => $data
        ]);

        $response = (string)$response->getBody();
        $result = json_decode($response, true);

        if (empty($result) || empty($result['ask']) && $result['ask'] != 'Success') {
            throw new Exception('谷仓接口异常');
        }

        return $result['data'];
    }

    /**
     * 添加订单
     * @param $order_id
     * @return mixed
     */
    public function addOrder($order_id)
    {
        $order = Order::find()->where(['order_id'=>$order_id])->one();
        if($order['warehouse'] != 8) {
            return false;
        }

        $platform = 'OTHER';
        if($order['source'] == Base::PLATFORM_AMAZON){
            $platform = 'AMAZON';
        }

        $shipping_method = ShippingMethod::findOne($order['logistics_channels_id']);
        $shipping_method_code = $shipping_method['shipping_method_code'];
        $shipping_method_code = explode('-',$shipping_method_code);
        if(empty($shipping_method_code) || count($shipping_method_code) != 2) {
            throw new Exception('渠道出错');
        }

        $order_goods = OrderGoods::find()->where(['order_id'=>$order_id])->all();
        $shipping_method = $shipping_method_code[1];
        $warehouse_code = $shipping_method_code[0];
        $items = [];
        foreach ($order_goods as $v) {
            $info = [];
            $info['product_sku'] = $v['cgoods_no'];
            $info['quantity'] = $v['goods_num'];
            $items[] = $info;
        }
        $data = [
            'reference_no' => $order['relation_no'],
            'platform' => $platform,
            'shipping_method' => $shipping_method,
            'warehouse_code' => $warehouse_code,
            //'wp_code' => 'CZ-8',
            'country_code' => $order['country'],
            'province' => $order['area'],
            'city' =>  $order['city'],
            'address1' => $order['address'],
            'address2' => '',
            'zipcode' => $order['postcode'],
            'doorplate' => '',
            'name' => $order['buyer_name'],
            'last_name' => '',
            'phone' => $order['buyer_phone'],
            'email' => $order['email'],
            'verify' => 1,
            'items' => $items,
        ];
        $response = $this->getClient()->post('order/create_order', [
            'json' => $data
        ]);
        $response = (string)$response->getBody();
        $result = json_decode($response, true);
        return $result;

        /**
         * {
        "ask": "Success",
        "message": "Success",
        "order_code": "G8797-231213-0002",
        "data": {
        "order_code": "G8797-231213-0002"
        }
        }
         */
    }

    /**
     * 获取物流轨迹
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getTracking($order_id)
    {
        $order = Order::find()->where(['order_id'=>$order_id])->one();
        $response = $this->getClient()->post('order/query_tracking_status', [
            'json' => [
                'refrence_no' => $order['relation_no']
            ]
        ]);
        $response = (string)$response->getBody();
        return json_decode($response, true);
    }


    /**
     * 获取物流轨迹
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getOrder($order_id)
    {
        $order = Order::find()->where(['order_id'=>$order_id])->one();
        $response = $this->getClient()->post('order/get_order_by_ref_code', [
            'json' => [
                'reference_no' => $order['relation_no']
            ]
        ]);
        $response = (string)$response->getBody();
        return json_decode($response, true);
    }


    /**
     * 获取仓库
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getWarehouse()
    {
        $response = $this->getClient()->get('base_data/get_warehouse');
        $response = (string)$response->getBody();
        return json_decode($response, true);
    }

    /**
     * 获取物流方式
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getShippingMethod($warehouse_code)
    {
        $data = [
            'warehouseCode' => $warehouse_code
        ];
        $response = $this->getClient()->post('base_data/get_shipping_method', [
            'json' => $data
        ]);
        $response = (string)$response->getBody();
        return json_decode($response, true);
    }

}