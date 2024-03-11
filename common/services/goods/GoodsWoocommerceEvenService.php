<?php


namespace common\services\goods;



use common\components\CommonUtil;
use common\extensions\wordpress\Woocommerce;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsWoocommerce;
use common\models\GoodsShop;
use yii\helpers\ArrayHelper;
use yii\base\Exception;


class GoodsWoocommerceEvenService
{

    /**
     * 添加woocommerce商品
     * @param $goods_no
     * @param $cgoods_no
     * @return Boolean
     */
    public static function addEvent($goods_no,$cgoods_no,$platform_type)
    {
        $woocommerce = Woocommerce::Client();
        $goods = GoodsWoocommerce::find()->alias('gw')
            ->select('gw.goods_name,gw.goods_desc,gw.goods_content,gw.o_category_name,gw.weight,g.goods_img,g.goods_type,g.sku_no')
            ->leftJoin(Goods::tableName().' g','gw.goods_no = g.goods_no')
            ->where(['gw.goods_no' => $goods_no,'platform_type' => $platform_type])
            ->asArray()->one();
        $goods_shop = GoodsShop::find()->alias('gs')
            ->select('gs.discount,gs.price,gs.platform_sku_no,gc.goods_img,gc.colour,gc.size')
            ->leftJoin(GoodsChild::tableName().' gc','gs.cgoods_no = gc.cgoods_no')
            ->where(['gs.cgoods_no' => $cgoods_no,'platform_type' => $platform_type])->asArray()->one();
        $goods_name = $goods['goods_name'];
        $goods_short_description = $goods['goods_desc'];
        $goods_sale_price = $goods_shop['price'] - $goods_shop['discount'];
        $goods_description = $goods['goods_content'];
        $goods_category = (int)$goods['o_category_name'];
        $goods_weight = $goods['weight'];
        $goods_regular_price = $goods_shop['price'];
        $goods_sku = $goods['sku_no'];
        $goods_img = [];
        $goods_images = json_decode($goods['goods_img'],true);
        foreach ($goods_images as $k => $v) {
            $goods_img[$k]['src'] = $v['img'];
        }
        $data = [
            'name' => $goods_name,
            'regular_price' => $goods_regular_price,
            'description' => $goods_description,
            'short_description' => $goods_short_description,
            'categories' => [['id'=> $goods_category]],
            'images' => $goods_img,
            'weight' => $goods_weight,
            'sku' => $goods_sku
        ];
        $data['type'] = $goods['goods_type'] == Goods::GOODS_TYPE_SINGLE ? 'simple' : 'variable';
        if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
            $goods_childs = GoodsChild::find()->where(['goods_no' => $goods_no])->asArray()->all();
            $size = array_unique(ArrayHelper::getColumn($goods_childs,'size'));
            $colour = array_unique(ArrayHelper::getColumn($goods_childs,'colour'));
            if (!empty($colour)) {
                $colour = [
                    'name' => 'Color',
                    'position' => 0,
                    'visible' => true,
                    'variation' => true,
                    'options' => $colour
                ];
                $data['attributes'][] = $colour;
            }
            if (!empty($size)) {
                $size = [
                    'name' => 'Size',
                    'position' => 0,
                    'visible' => true,
                    'variation' => true,
                    'options' => $size
                ];
                $data['attributes'][] = $size;
            }
            $cgoods_price = $goods_regular_price;
            $cgoods_image = $goods_shop['goods_img'];
            $cgoods_sku = $goods_shop['platform_sku_no'];
            $cgoods_weight = $goods['weight'];
            $attributes_color = [
                'name' => 'Color',
                'option' => $goods_shop['colour']
            ];
            $attributes_size = [
                'name' => 'Size',
                'option' => $goods_shop['size']
            ];
            $cgoods_data = [
                'regular_price' => $cgoods_price,
                'image' => ['src' => $cgoods_image],
                'sku' => $cgoods_sku,
                'weight' => $cgoods_weight,
                'attributes' => [$attributes_color,$attributes_size]
            ];
            try {
                $product = $woocommerce->get('products',['sku' => $goods['sku_no']]);
                $id = $product[0]->id;
                $product_id = 'products/'.$id.'/variations';
                try {
                    $woocommerce->post($product_id,$cgoods_data);
                    return true;
                } catch (\Exception $e) {
                    CommonUtil::logs($goods_no . ' 多变体认领失败 '. $cgoods_no . $e->getMessage(), 'claim');
                }
            } catch (\Exception $e) {
                $product = $woocommerce->post('products', $data);
                $product_id = $product->id;
                $product_id = 'products/'.$product_id.'/variations';
                $woocommerce->post($product_id,$cgoods_data);
                return true;
            }
        }
        try {
            $woocommerce->post('products', $data);
            return true;
        } catch (\Exception $e) {
            CommonUtil::logs($goods_no . ' 认领失败 ' . $e->getMessage(), 'claim');
        }
    }

    /**
     * 修改woocommerce商品价格
     * @param $goods_no
     * @param $cgoods_no
     * @return Boolean
     */
    public static function updateEvent($sku_no,$platform_sku_no,$price)
    {
        $woocommerce = Woocommerce::Client();
        try {
            $goods = $woocommerce->get('products',['sku' => $sku_no]);
            $id = $goods[0]->id;
            $cgoods = $woocommerce->get('products/'.$id.'/variations',['sku' => $platform_sku_no]);
            if (!empty($cgoods)) {
                $cgoods_id = $cgoods[0]->id;
                $woocommerce->put('products/'. $id .'/variations/'. $cgoods_id , ['regular_price' => $price]);
                return true;
            }
            $woocommerce->put('products/'.$id, ['regular_price' => $price]);
        } catch (\Exception $e) {
            throw new \Exception('没找到该商品');
        }
    }

    /**
     * 删除woocommerce商品
     * @param $goods_no
     * @param $cgoods_no
     * @return Boolean
     */
    public static function deleteEvent($sku_no,$platform_sku_no)
    {
        $woocommerce = Woocommerce::Client();
        try {
            $goods = $woocommerce->get('products',['sku' => $sku_no]);
            $id = $goods[0]->id;
            $cgoods = $woocommerce->get('products/'.$id.'/variations',['sku' => $platform_sku_no]);
            if (!empty($cgoods)) {
                $cgoods_id = $cgoods[0]->id;
                $woocommerce->delete('products/'. $id .'/variations/'. $cgoods_id , ['force' => true]);
                return true;
            }
            $woocommerce->delete('products/'.$id, ['force' => true]);
        } catch (\Exception $e) {
            throw new \Exception('没找到该商品');
        }
    }

    /**
     * 添加woocommerce商品
     * @param $goods_no
     * @param $cgoods_no
     * @return Boolean
     */
    public static function createComment($data,$sku_no)
    {
        $woocommerce = Woocommerce::Client();
        try {
            $goods = $woocommerce->get('products',['sku' => $sku_no]);
            $goods_id = $goods[0]->id;
            $data['product_id'] = $goods_id;
            $woocommerce->post('products/reviews', $data);
            return true;
        } catch (\Exception $e) {
            return '该商品不存在';
        }
    }
}