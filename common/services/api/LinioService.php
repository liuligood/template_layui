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
use common\services\goods\GoodsService;
use common\services\goods\platform\LinioPlatform;
use common\services\goods\WordTranslateService;
use Psr\Http\Message\ResponseInterface;
use yii\base\Exception;

/**
 * Class LinioService
 * @package common\services\api
 * https://sellerapi.sellercenter.net/docs/signing-requests
 */
class LinioService extends BaseSellerCenterApiService
{

    public function getBaseUri()
    {
        $shop = $this->shop;
        switch ($shop['country_site']) {
            case 'PE':
                return 'https://sellercenter-api.linio.com.pe';
                break;
            case 'CL':
                return 'https://sellercenter-api.linio.cl';
                break;
            case 'MX':
                return 'https://sellercenter-api.linio.com.mx';
                break;
            case 'CO':
                return 'https://sellercenter-api.linio.com.co';
                break;
        }
        return '';
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
        $country = $this->shop['country_site'];
        $country_sort = ['CL', 'PE', 'MX', 'CO'];
        $country_index = array_search($country,$country_sort);

        $colour_map = LinioPlatform::$colour_map;
        //限制150
        /*$goods_name = $goods_linio['goods_name'];
        if (strlen($goods_linio['goods_name']) > 140) {
            $goods_name = CommonUtil::usubstr($goods_linio['goods_short_name'], 140);
        }*/

        $colour = empty($colour_map[$goods['ccolour']]) ? '' : $colour_map[$goods['ccolour']];
        if (empty($colour)) {
            $colour = empty($colour_map[$goods['colour']]) ? '' : $colour_map[$goods['colour']];
            $colour = empty($colour) ? $colour_map['Black'] : $colour;
        }
        $base_goods['gcolour'] = $colour;

        $goods_name = '';
        /*if(!empty($goods_shop['keywords_index'])) {
            $goods_name = (new GoodsShopService())->getKeywordsTitle($this->platform_type, $goods['goods_no'], $goods_shop['keywords_index'], 100);
        }*/

        if (empty($goods_name)) {
            $goods_name = !empty($base_goods['goods_short_name']) ? $base_goods['goods_short_name'] : $base_goods['goods_name'];
            $goods_name = CommonUtil::filterTrademark($goods_name);
            $goods_name = str_replace(['（', '）'], '', $goods_name);
            $goods_name = (new LinioPlatform())->filterContent(CommonUtil::usubstr($goods_name, 59));
        }
        /*if(strpos($goods_linio['goods_short_name'],'(') === false) {
            $goods_name = $goods_name . ' ' . $colour;
        }*/

        /*$images = [];
        $image = json_decode($goods['goods_img'], true);
        $main_image = '';
        $i = 0;
        foreach ($image as $v) {
            if ($i > 5) {
                break;
            }
            $i++;
            if ($i == 1) {
                $main_image = $v['img'];
                //continue;
            }
            $images[] = $v['img'];
        }*/

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

        $params = [];
        $category = trim($base_goods['o_category_name']);
        $category = explode(',',$category);
        $category_id = empty($category[$country_index])?'':$category[$country_index];
        //$category_id = 10231;
        if (empty($category_id)) {
            return false;
        }
        $sku_no = !empty($goods_shop['platform_sku_no']) ? $goods_shop['platform_sku_no'] : $goods['sku_no'];

        $category_attr = $this->getCategoryAttributes($category_id);

        if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
            $translate_name = [];
            if (!empty($goods['ccolour'])) {
                $translate_name[] = $goods['ccolour'];
            }
            if (!empty($goods['csize'])) {
                $translate_name[] = $goods['csize'];
            }
            $words = (new WordTranslateService())->getTranslateName($translate_name, (new LinioPlatform())->platform_language);
            $ccolour = empty($words[$goods['ccolour']]) ? $goods['ccolour'] : $words[$goods['ccolour']];
            $cszie = empty($words[$goods['csize']]) ? $goods['csize'] : $words[$goods['csize']];
            $base_goods['goods_content'] = 'Este artículo vende:' . $ccolour . ' ' . $cszie . PHP_EOL . $base_goods['goods_content'];
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
        $linio_platform = (new LinioPlatform())->setCountryCode($country);
        foreach ($category_attr as $attr_v) {
            if (in_array($attr_v['FeedName'], [
                'TicketNumber',
                'ConditionTypeNote',
                'TaxClass',
                'Name',
                'Brand',
                'Description',
                //'ProductWeight',
                'PrimaryCategory',
                'Categories',
                'BrowseNodes',
                'Price',
                'SalePrice',
                'SaleStartDate',
                'SaleEndDate',
                'SellerSku',
                'ParentSku',
                'ProductId',
                'Variation',
                'Quantity',
                'ShipmentType',
            ])) {
                continue;
            }

            if ($attr_v['FeedName'] == 'SellerWarranty') {
                $params[$attr_v['FeedName']] = '3 meses';
                continue;
            }

            if ($attr_v['FeedName'] == 'ConditionType') {
                $params[$attr_v['FeedName']] = 'Nuevo';
                continue;
            }

            if ($attr_v['FeedName'] == 'PackageLength') {
                $params[$attr_v['FeedName']] = $l;
                continue;
            }

            if ($attr_v['FeedName'] == 'PackageWidth') {
                $params[$attr_v['FeedName']] = $w;
                continue;
            }

            if ($attr_v['FeedName'] == 'PackageHeight') {
                $params[$attr_v['FeedName']] = $h;
                continue;
            }

            if ($attr_v['FeedName'] == 'NameEn') {
                $params[$attr_v['FeedName']] = CommonUtil::filterTrademark($goods['goods_name']);
                continue;
            }

            if ($attr_v['FeedName'] == 'Model') {
                //添加编号
                $ean_no = substr($goods_shop['ean'], -2);
                $ean_no .= substr($goods_shop['ean'], -5, 2);
                $ean_no .= substr($goods_shop['ean'], -3, 1);
                $params[$attr_v['FeedName']] = 'M' . $ean_no;
                continue;
            }

            if ($attr_v['FeedName'] == 'ShortDescription') {
                $params[$attr_v['FeedName']] = $linio_platform->descDeal($base_goods);
                continue;
            }

            if ($attr_v['FeedName'] == 'Color') {
                $params[$attr_v['FeedName']] = $colour;
                continue;
            }

            //主要材料
            if ($attr_v['FeedName'] == 'MainMaterial') {
                $params[$attr_v['FeedName']] = 'Metal';
                continue;
            }

            if ($attr_v['FeedName'] == 'FilterColor') {
                $params[$attr_v['FeedName']] = $colour;
                continue;
            }

            if ($attr_v['FeedName'] == 'ProductionCountry') {
                $params[$attr_v['FeedName']] = 'China';
                continue;
            }

            if ($attr_v['FeedName'] == 'ProductMeasures') {
                $params[$attr_v['FeedName']] = $l . ' x ' . $w . ' x ' . $h;
                continue;
            }

            if ($attr_v['FeedName'] == 'ProductWeight') {
                $params[$attr_v['FeedName']] = $weight;
                continue;
            }

            if ($attr_v['FeedName'] == 'PackageWeight') {
                $params[$attr_v['FeedName']] = $weight;
                continue;
            }

            if ($attr_v['isMandatory'] == 1) {
                if ($attr_v['AttributeType'] == 'option') {
                    $attr_val = current($attr_v['Options']['Option']);
                    $tmp_val = $attr_val['Name'];
                } else {
                    $tmp_val = '1';
                }
                $params[$attr_v['FeedName']] = $tmp_val;
                continue;
            }
        }


        $info = [];
        $info['SellerSku'] = $sku_no;

        if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
            $info['ParentSku'] = $goods['goods_no'];
            $vl = $ccolour . ' ' . $cszie;
            $info['Variation'] = trim($vl);
        }

        $info['Status'] = 'active';
        $info['Name'] = $goods_name;
        $info['PrimaryCategory'] = (int)$category_id;
        //$info['Categories'] = '';//可选子类别
        //$info['BrowseNodes'] = '';//相关类目可选
        $info['Description'] = $linio_platform->dealContent($base_goods);
        $info['Brand'] = 'Generico';
        $info['Price'] = (string)($price * 2);
        $info['SalePrice'] = (string)$price;
        $info['SaleStartDate'] = $this->toDate(date('Y-m-d', time() - 24 * 60 * 60));
        $info['SaleEndDate'] = $this->toDate('2026-12-01');
        $tax_class = '0%';
        switch ($country){
            case 'MX':
                $tax_class = 'IVA 0%';
                break;
            case 'CO':
                $tax_class = 'IVA exento 0%';
                break;
        }
        $info['TaxClass'] = $tax_class;
        //$info['ShipmentType'] = 'dropshipping';//crossdocking
        $info['ProductId'] = $goods_shop['ean'];
        $info['ProductData'] = $params;
        $info['Quantity'] = $stock ? 1000 : 0;
        //$info['ProductWeight'] = $weight;
        //$info['ProductGroup'] = '';
        return $info;
    }
}