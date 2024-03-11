<?php

namespace common\services\api;

use common\models\GoodsShop;
use yii\base\Exception;

/**
 * Class BaseSelloApiService
 * @package common\services\api
 * https://docs.sello.io/#introduction
 */
class BaseSelloApiService extends BaseApiService
{

    public $platform_name = '';

    /**
     * @return array|SelloService
     */
    public function getSelloService()
    {
        $param = json_decode($this->param, true);
        if (empty($param['sello_client_key']) || empty($param['sello_secret_key'])) {
            throw new Exception('未设置sello参数');
        }

        return (new SelloService($param['sello_client_key'], $param['sello_secret_key']));
    }

    /**
     * 上传图片
     * @param $goods
     * @return bool
     * @throws Exception
     */
    public function addGoodsImage($goods)
    {
        $shop = $this->shop;
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        if(empty($goods_shop['platform_goods_id'])){
            return false;
        }
        $sello_service = $this->getSelloService();

        $images = [];
        $image = json_decode($goods['goods_img'], true);
        $i = 0;
        foreach ($image as $v) {
            if ($i > 5) {
                break;
            }
            $v['img'] = str_replace('image.chenweihao.cn','img.chenweihao.cn',$v['img']);
            $images[] = $v['img'];
            $i++;
        }
        $sello_service->addProductImages($goods_shop['platform_goods_id'], $images);
        return true;
    }

}