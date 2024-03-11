<?php
namespace common\services\api;

use Automattic\WooCommerce\Client;
use common\components\CommonUtil;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsWoocommerce;
use common\models\GoodsShop;
use yii\base\Exception;
use yii\helpers\ArrayHelper;

/**
 * Class WoocommerceService
 * @package common\services\api
 * https://woocommerce.github.io/woocommerce-rest-api-docs
 * https://github.com/woocommerce/wc-api-php
 */
class WoocommerceService extends BaseApiService
{

    public $base_url = 'http://www.8sanlione.site/';

    /**
     * @return Client
     */
    public function getClient()
    {
        $options = [
            'debug' => true,
            'return_as_array' => false,
            'validate_url' => false,
            'timeout' => 30,
            'ssl_verify' => false,
        ];

        $param = json_decode($this->param, true);
        $base_url = $param['base_url'];
        if (empty($base_url)) {
            $base_url = $this->base_url;
        }
        return new Client($base_url, $this->client_key, $this->secret_key, $options);
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
        $goods_woo = GoodsWoocommerce::find()->where(['goods_no' => $goods['goods_no']])->one();
        if (!empty($goods_shop['platform_goods_id'])) {
            return true;
        }

        $data = $this->dealGoodsInfo($goods, $goods_shop, $goods_woo);
        if (empty($data)) {
            return false;
        }

        if (empty($goods_shop->platform_goods_opc)) {
            $product = $this->getClient()->post('products', $data);
            if (empty($product) || empty($product->id)) {
                CommonUtil::logs('woocommerce result goods_no:' . $goods['goods_no'] . ' shop_id:' . $shop['id'] . ' data:' . json_encode($data) . ' ' . json_encode($product), 'add_products_woocommerce');
                return false;
            }
            $goods_shop->platform_goods_opc = (string)$product->id;

            if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
                $goods_shop_multi = GoodsShop::find()->where(['goods_no' => $goods['goods_no'], 'shop_id' => $shop['id']])->andWhere(['!=','cgoods_no',$goods['cgoods_no']])->all();
                foreach ($goods_shop_multi as $cgoodss) {
                    $cgoodss->platform_goods_opc = (string)$product->id;
                    $cgoodss->save();
                }
            }
        }

        //多变体
        if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
            $data = $this->dealGoodsVariationInfo($goods, $goods_shop, $goods_woo);
            $up_result = $this->getClient()->post('products/' . $goods_shop['platform_goods_opc'] . '/variations', $data);
            if (empty($up_result) || empty($up_result->id)) {
                CommonUtil::logs('woocommerce variation result goods_no:' . $goods['goods_no'] . ' shop_id:' . $shop['id'] . ' data:' . json_encode($data) . ' ' . json_encode($up_result), 'add_products_woocommerce');
                return false;
            }
            $platform_goods_id = $up_result->id;
        } else {
            $platform_goods_id = -1;
        }
        $goods_shop->platform_goods_id = (string)$platform_goods_id;
        $goods_shop->save();
        return true;
    }


    /**
     * 修改价格
     * @param $goods
     * @param $price
     * @return bool
     * @throws Exception
     */
    public function updatePrice($goods, $price)
    {
        $shop = $this->shop;
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        $platform_goods_opc = '';
        if (!empty($goods_shop['platform_goods_opc'])) {
            $platform_goods_opc = $goods_shop['platform_goods_opc'];
        }

        $data = [
            'regular_price' => (string)$price,
        ];

        if ($goods_shop['platform_goods_id'] != -1) {
            $product = $this->getClient()->put('products/'. $platform_goods_opc .'/variations/'. $goods_shop['platform_goods_id'] , $data);
            if (empty($product) || empty($product->id)) {
                CommonUtil::logs('woocommerce result goods_no:' . $goods['goods_no'] . ' shop_id:' . $shop['id'] . ' data:' . json_encode($data) . ' ' . json_encode($product), 'update_products_price_woocommerce');
                return false;
            }
            return true;
        }

        $product = $this->getClient()->put('products/' . $platform_goods_opc, $data);
        if (empty($product) || empty($product->id)) {
            CommonUtil::logs('woocommerce result goods_no:' . $goods['goods_no'] . ' shop_id:' . $shop['id'] . ' data:' . json_encode($data) . ' ' . json_encode($product), 'update_products_price_woocommerce');
            return false;
        }
        return true;
    }

    /**
     * 删除商品
     * @param $goods
     * @param $force
     * @return bool
     * @throws Exception
     */
    public function delGoods($goods, $force = false)
    {
        $shop = $this->shop;
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();

        $platform_goods_opc = '';
        if (!empty($goods_shop['platform_goods_opc'])) {
            $platform_goods_opc = $goods_shop['platform_goods_opc'];
        }

        $data = [];
        if ($force) {
            $data['force'] = $force;
        }

        if (!empty($goods_shop['platform_goods_id']) && $goods_shop['platform_goods_id'] != -1) {
            $product = $this->getClient()->delete('products/'. $platform_goods_opc .'/variations/'. $goods_shop['platform_goods_id'] , $data);
            if (empty($product) || empty($product->id)) {
                CommonUtil::logs('woocommerce result goods_no:' . $goods['goods_no'] . ' shop_id:' . $shop['id'] . ' data:' . json_encode($data) . ' ' . json_encode($product), 'delete_products_woocommerce');
                return false;
            }
            return true;
        }

        $product = $this->getClient()->delete('products/' . $platform_goods_opc, $data);
        if (empty($product) || empty($product->id)) {
            CommonUtil::logs('woocommerce result goods_no:' . $goods['goods_no'] . ' shop_id:' . $shop['id'] . ' data:' . json_encode($data) . ' ' . json_encode($product), 'delete_products_woocommerce');
            return false;
        }
        return true;
    }

    /**
     * 处理商品信息
     * @param $goods
     * @param $goods_woo
     * @param $goods_shop
     * @return array
     */
    public function dealGoodsInfo($goods, $goods_shop, $goods_woo)
    {
        $category_id = (int)$goods_woo['o_category_name'];
        $goods_name = $goods['goods_name'];
        $goods_short_description = $goods['goods_desc'];
        $goods_description = $goods['goods_content'];
        $goods_category = (int)$category_id;
        $price = $goods_shop['price'];
        $goods_sku = $goods_shop['platform_sku_no'];
        $images = [];
        $goods_images = json_decode($goods['goods_img'], true);
        $i = 0;
        foreach ($goods_images as $v) {
            if ($i > 5) {
                break;
            }
            $i++;
            $images[] = ['src' => $v['img']];
        }

        $weight = $goods['real_weight'] > 0 ? $goods['real_weight'] : $goods['weight'];
        $weight = $weight < 0.1 ? 0.1 : $weight;

        $data = [
            'name' => $goods_name,
            'regular_price' => $price,
            'description' => $goods_description,
            'short_description' => $goods_short_description,
            'categories' => [['id' => $goods_category]],
            'images' => $images,
            'weight' => $weight,
            'sku' => $goods['goods_type'] == Goods::GOODS_TYPE_MULTI ? '' : $goods_sku,
        ];
        $data['type'] = 'simple';
        //多变体
        if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
            $data['type'] = 'variable';
            $goods_childs = GoodsChild::find()->where(['goods_no' => $goods['goods_no']])->asArray()->all();
            $size = array_unique(ArrayHelper::getColumn($goods_childs, 'size'));
            $colour = array_unique(ArrayHelper::getColumn($goods_childs, 'colour'));
            $is_size = (count($size) == 1 && $size[0] == '') ? false : true;
            $is_colour = (count($colour) == 1 && $colour[0] == '') ? false : true;
            if ($is_colour) {
                $colour = [
                    'name' => 'Color',
                    'position' => 0,
                    'visible' => true,
                    'variation' => true,
                    'options' => $colour
                ];
                $data['attributes'][] = $colour;
            }
            if ($is_size) {
                $size = [
                    'name' => 'Size',
                    'position' => 0,
                    'visible' => true,
                    'variation' => true,
                    'options' => $size
                ];
                $data['attributes'][] = $size;
            }
        }
        return $data;
    }

    /**
     * 处理商品信息
     * @param $goods
     * @param $goods_woo
     * @param $goods_shop
     * @return array
     */
    public function dealGoodsVariationInfo($goods, $goods_shop, $goods_woo)
    {
        if ($goods['goods_type'] != Goods::GOODS_TYPE_MULTI) {
            return;
        }
        $price = $goods_shop['price'];
        $goods_sku = $goods_shop['platform_sku_no'];

        $images = [];
        $image = json_decode($goods['goods_img'], true);
        $main_image = $image[0]['img'];

        $weight = $goods['real_weight'] > 0 ? $goods['real_weight'] : $goods['weight'];
        $weight = $weight < 0.1 ? 0.1 : $weight;

        $attributes_color = [];
        if (!empty($goods['ccolour'])) {
            $attributes_color = [
                'name' => 'Color',
                'option' => $goods['ccolour']
            ];
        }
        $attributes_size = [];
        if (!empty($goods['csize'])) {
            $attributes_size = [
                'name' => 'Size',
                'option' => $goods['csize']
            ];
        }
        $data = [
            'regular_price' => $price,
            'image' => ['src' => $main_image],
            'sku' => $goods_sku,
            'weight' => $weight,
            'attributes' => [$attributes_color, $attributes_size]
        ];
        return $data;
    }

}