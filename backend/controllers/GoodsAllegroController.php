<?php
namespace backend\controllers;

use backend\models\search\BaseGoodsSearch;
use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\BaseAR;
use common\models\Category;
use common\models\Goods;
use common\models\goods\GoodsAllegro;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsStock;
use common\models\goods\GoodsStockDetails;
use common\models\goods_shop\GoodsShopOverseasWarehouse;
use common\models\goods_shop\GoodsShopSalesTotal;
use common\models\GoodsShop;
use common\models\Shop;
use common\models\User;
use common\models\warehousing\BlContainer;
use common\models\warehousing\BlContainerGoods;
use common\models\warehousing\OverseasGoodsShipment;
use common\services\goods\FGoodsService;
use common\services\goods\GoodsService;
use common\services\sys\CountryService;
use Yii;
use yii\base\ViewNotFoundException;
use yii\data\Pagination;
use yii\helpers\ArrayHelper;
use yii\web\Response;

class GoodsAllegroController extends BasePlatformGoodsController
{

    protected $render_view = '/goods/allegro/';

    protected $platform_type = Base::PLATFORM_ALLEGRO;

    protected $has_country = true;

    public function model()
    {
        return new GoodsAllegro();
    }

    protected $export_column = [
        'goods_no' => '商品编号',
        'cgoods_no' => '子商品编号',
        'shop_name' => '店铺名称',
        'category_name' => '平台类目',
        'o_category_name' => 'Allegro类目',
        'platform_sku_no' => 'SKU',
        'ean' => 'EAN',
        'goods_name' => '标题',
        'goods_short_name' => '短标题',
        'image_all' => '图片(总)',
        'price' => '价格',
        'brand' => '品牌',
        'colour' => '颜色',
        'size' => '尺寸',
        'weight' => '重量',
        'goods_content' => '详细描述',
        'goods_desc' => '简要描述',
        'add_time' => '创建时间',
    ];


    /**
     * 导出
     * @param $info
     * @return array
     */
    public function dealExport($info)
    {
        $data = [];
        $image = json_decode($info['goods_img'], true);
        $image = empty($image) || !is_array($image) ? [] : $image;

        //所有图片
        $image_arr = [];
        $i = 0;
        foreach ($image as $img_v) {
            if (empty($img_v['img']) || $i > 6) {
                continue;
            }
            $i++;
            $img_v['img'] = str_replace('http://image.chenweihao.cn', 'https://image.chenweihao.cn', $img_v['img']);
            $image_arr[] = $img_v['img'];
        }
        $data['image_all'] = implode('|', $image_arr);
        $goods_short_name = empty($info['goods_short_name']) ? $info['goods_name'] : $info['goods_short_name'];
        $goods_short_name = str_replace(['（', '）'], ['(', ')'], $goods_short_name);
        $goods_short_name = CommonUtil::filterTrademark($goods_short_name);
        $goods_short_name = CommonUtil::usubstr($goods_short_name, 50);
        $data['goods_short_name'] = htmlspecialchars($goods_short_name);
        $p_class = FGoodsService::factory($this->platform_type);
        $info['goods_name'] = htmlspecialchars($info['goods_name']);
        $info['goods_content'] = htmlspecialchars($info['goods_content']);
        $data['goods_content'] = $p_class->dealContent($info);
        return $data;
    }


}