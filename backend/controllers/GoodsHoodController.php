<?php


namespace backend\controllers;


use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\goods\GoodsHood;
use common\models\Shop;
use yii\helpers\ArrayHelper;

class GoodsHoodController extends BaseGoodsController
{
    protected $render_view = '/goods/hood/';

    protected $platform_type = Base::PLATFORM_HOOD;

    public function model(){
        return new GoodsHood();
    }

    protected $export_column = [
        'format' => 'Format',
        'action' => 'Action',
        'o_category_name' => 'Kategorie Nr',
        'platform_sku_no' => 'Artikel Nr',
        'zustand' => 'Zustand',
        'ean' => 'EAN',
        'isbn' => 'ISBN',
        'mpn' => 'MPN',
        'brand' => 'Hersteller',
        'goods_name' => 'Titel',
        'goods_short_name' => 'Untertitel',
        'goods_content' => 'Beschreibung',
        'price' => 'Preis',
        'ek' => 'EK',
        'uvp' => 'UVP',
        'menge' => 'Menge',
        'mindestbestellmenge' => 'Mindestbestellmenge',
        'weight' => 'Gewicht',
        'einheit' => 'Einheit',
        'mwst' => 'MwSt',
        'laufzeit' => 'Laufzeit',
        'zahlungsarten' => 'Zahlungsarten',
        'express_nat' => 'express_nat',
        'all_image' => 'Bild URL',
        'ausverkauft' => 'ausverkauft',
    ];

    /**
     * 导出
     * @param $info
     * @return array
     */
    public function dealExport($info)
    {
        $data = [];
        $shop = Shop::find()->cache(300)->where(['id'=>$info['shop_id']])->limit(1)->asArray()->one();
        $data['brand'] = $shop['brand_name'];
        $goods_img = json_decode($info['goods_img'],true);
        $data['goods_name'] = CommonUtil::usubstr($info['goods_name'], 85);
        $data['goods_short_name'] = CommonUtil::usubstr($info['goods_short_name'], 80);
        $data['goods_content'] = CommonUtil::usubstr($info['goods_content'], 65000);
        $image_arr = ArrayHelper::getColumn($goods_img,'img');
        $images = implode('*',$image_arr);
        $data['all_image'] = str_ireplace(' ','',$images);
        return $data;
    }


    public function afterDealExport($data)
    {
        $data['format'] = 7;
        $data['action'] = 'Add';
        $data['zustand'] = 'neu';
        $data['isbn'] = $data['ean'];
        $data['mpn'] = $data['platform_sku_no'];
        $data['ek'] = $data['price'];
        $data['uvp'] = round($data['price'] * 2,2);
        $data['menge'] = 1000;
        $data['mindestbestellmenge'] = 1;
        $data['einheit'] = 'Stck';
        $data['mwst'] = 19;
        $data['laufzeit'] = 14;
        $data['zahlungsarten'] = '1,3,21';
        $data['express_nat'] = 0;
        $data['ausverkauft'] = 'hide';
        return $data;
    }
}