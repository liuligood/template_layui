<?php

namespace backend\controllers;

use common\components\statics\Base;
use common\models\goods\GoodsMicrosoft;
use common\services\goods\GoodsService;
use common\services\goods\platform\MicrosoftPlatform;
use Yii;

class GoodsMicrosoftController extends BaseGoodsController
{

    protected $render_view = '/goods/microsoft/';

    protected $platform_type = Base::PLATFORM_MICROSOFT;

    public function model(){
        return new GoodsMicrosoft();
    }

    protected $export_column = [
        'goods_no' => '商品编号',
        'cgoods_no' => '子商品编号',
        'shop_name' => '店铺名称',
        'category_name' => '平台类目',
        'o_category_name1' => '类目1',
        'o_category_name2' => '类目2',
        'o_category_name3' => '类目3',
        'o_category_name4' => '类目4',
        'o_category_name5' => '类目5',
        'o_category_name6' => '类目6',
        'platform_sku_no' => 'SKU',
        'ean' => 'EAN',
        'goods_name' => '标题',
        'goods_short_name' => '短标题',
        'image'=>'图片1',
        'image2'=>'图片2',
        'image3'=>'图片3',
        'image4'=>'图片4',
        'image5'=>'图片5',
        'price' => '价格',
        'freight' => '运费',
        'brand' => '品牌',
        'colour' => '颜色',
        'size_l' => '长',
        'size_w' => '宽',
        'size_h' => '高',
        'weight' => '重量',
        'goods_desc1' => '要素1',
        'goods_desc2' => '要素2',
        'goods_desc3' => '要素3',
        'goods_desc4' => '要素4',
        'goods_desc5' => '要素5',
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

        $size = GoodsService::getSizeArr($info['size']);
        $data['size_l'] = empty($size['size_l'])?10:$size['size_l'];
        $data['size_w'] = empty($size['size_w'])?10:$size['size_w'];
        $data['size_h'] = empty($size['size_h'])?10:$size['size_h'];

        $o_category_name = explode('/',$info['o_category_name']);
        $data['o_category_name1'] = empty($o_category_name[0])?'':$o_category_name[0];
        $data['o_category_name2'] = empty($o_category_name[1])?'':$o_category_name[1];
        $data['o_category_name3'] = empty($o_category_name[2])?'':$o_category_name[2];
        $data['o_category_name4'] = empty($o_category_name[3])?'':$o_category_name[3];
        $data['o_category_name5'] = empty($o_category_name[4])?'':$o_category_name[4];
        $data['o_category_name6'] = empty($o_category_name[5])?'':$o_category_name[5];
        $data['freight'] = (new MicrosoftPlatform())->getFreight($info);

        return $data;
    }


}