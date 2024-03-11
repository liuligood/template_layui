<?php
namespace common\services\api;

use common\components\statics\Base;
use common\models\GoodsEvent;
use common\models\GoodsShop;

class GoodsEventService
{

    /**
     * @param $event_type
     * @param $platform
     * @return bool
     */
    public static function hasEvent($event_type,$platform)
    {
        $event_platform = [
            GoodsEvent::EVENT_TYPE_ADD_GOODS => [
                Base::PLATFORM_FRUUGO,
                Base::PLATFORM_ONBUY,
                Base::PLATFORM_ALLEGRO,
                Base::PLATFORM_FYNDIQ,
                Base::PLATFORM_MERCADO,
                Base::PLATFORM_OZON,
                Base::PLATFORM_JDID,
                Base::PLATFORM_CDISCOUNT,
                Base::PLATFORM_HEPSIGLOBAL,
                Base::PLATFORM_B2W,
                Base::PLATFORM_COUPANG,
                Base::PLATFORM_NOCNOC,
                Base::PLATFORM_LINIO,
                Base::PLATFORM_TIKTOK,
                //Base::PLATFORM_JUMIA,
                Base::PLATFORM_MICROSOFT,
                //Base::PLATFORM_EPRICE,
                Base::PLATFORM_WALMART,
                Base::PLATFORM_WOOCOMMERCE,
            ],
            GoodsEvent::EVENT_TYPE_UPDATE_GOODS => [//更新商品
                Base::PLATFORM_OZON,
                Base::PLATFORM_FYNDIQ,
                Base::PLATFORM_FRUUGO,
                Base::PLATFORM_HEPSIGLOBAL,
                Base::PLATFORM_ALLEGRO,
                Base::PLATFORM_COUPANG,
            ],
            GoodsEvent::EVENT_TYPE_UPDATE_STOCK => [
                Base::PLATFORM_REAL_DE,
                Base::PLATFORM_FRUUGO,
                Base::PLATFORM_ONBUY,
                Base::PLATFORM_ALLEGRO,
                Base::PLATFORM_FYNDIQ,
                Base::PLATFORM_MERCADO,
                Base::PLATFORM_OZON,
                Base::PLATFORM_JDID,
                Base::PLATFORM_CDISCOUNT,
                Base::PLATFORM_HEPSIGLOBAL,
                Base::PLATFORM_B2W,
                Base::PLATFORM_COUPANG,
                Base::PLATFORM_NOCNOC,
                Base::PLATFORM_LINIO,
                //Base::PLATFORM_JUMIA,
                Base::PLATFORM_TIKTOK,
                Base::PLATFORM_MICROSOFT,
                Base::PLATFORM_WALMART,
                //Base::PLATFORM_EPRICE,
                Base::PLATFORM_RDC,
                Base::PLATFORM_EPRICE,
                Base::PLATFORM_WORTEN,
                Base::PLATFORM_WILDBERRIES,
            ],
            GoodsEvent::EVENT_TYPE_UPDATE_PRICE => [
                Base::PLATFORM_REAL_DE,
                Base::PLATFORM_FRUUGO,
                Base::PLATFORM_ONBUY,
                Base::PLATFORM_ALLEGRO,
                Base::PLATFORM_FYNDIQ,
                Base::PLATFORM_MERCADO,
                Base::PLATFORM_OZON,
                Base::PLATFORM_JDID,
                Base::PLATFORM_CDISCOUNT,
                Base::PLATFORM_HEPSIGLOBAL,
                Base::PLATFORM_B2W,
                Base::PLATFORM_COUPANG,
                Base::PLATFORM_NOCNOC,
                Base::PLATFORM_LINIO,
                //Base::PLATFORM_JUMIA,
                Base::PLATFORM_TIKTOK,
                Base::PLATFORM_MICROSOFT,
                Base::PLATFORM_WALMART,
                //Base::PLATFORM_EPRICE,
                Base::PLATFORM_RDC,
                Base::PLATFORM_EPRICE,
                Base::PLATFORM_WORTEN,
                Base::PLATFORM_WILDBERRIES,
                Base::PLATFORM_WOOCOMMERCE,
            ],
            GoodsEvent::EVENT_TYPE_DEL_GOODS => [
                Base::PLATFORM_REAL_DE,
                Base::PLATFORM_FRUUGO,
                Base::PLATFORM_ONBUY,
                Base::PLATFORM_ALLEGRO,
                Base::PLATFORM_FYNDIQ,
                Base::PLATFORM_MERCADO,
                Base::PLATFORM_OZON,
                Base::PLATFORM_JDID,
                Base::PLATFORM_CDISCOUNT,
                Base::PLATFORM_HEPSIGLOBAL,
                Base::PLATFORM_COUPANG,
                Base::PLATFORM_LINIO,
                //Base::PLATFORM_JUMIA,
                //Base::PLATFORM_EPRICE,
                Base::PLATFORM_B2W,
                Base::PLATFORM_NOCNOC,
                Base::PLATFORM_TIKTOK,
                Base::PLATFORM_MICROSOFT,
                Base::PLATFORM_WALMART,
                Base::PLATFORM_RDC,
                Base::PLATFORM_EPRICE,
                Base::PLATFORM_WORTEN,
                Base::PLATFORM_WOOCOMMERCE,
            ],
            GoodsEvent::EVENT_TYPE_ADD_VARIANT => [
                Base::PLATFORM_ALLEGRO,
                Base::PLATFORM_FRUUGO
            ],
            GoodsEvent::EVENT_TYPE_UPLOAD_IMAGE => [
                Base::PLATFORM_FRUUGO,
                //Base::PLATFORM_EPRICE,
                Base::PLATFORM_JDID,
            ],
            GoodsEvent::EVENT_TYPE_GET_GOODS_ID => [
                Base::PLATFORM_OZON,
                Base::PLATFORM_MERCADO,
                Base::PLATFORM_JDID,
                Base::PLATFORM_ALLEGRO
            ],
            GoodsEvent::EVENT_TYPE_ADD_GOODS_CONTENT => [
                Base::PLATFORM_MERCADO,
            ],
            GoodsEvent::EVENT_TYPE_RESUME_GOODS => [
                Base::PLATFORM_OZON
            ]
        ];
        if (empty($event_platform[$event_type])) {
            return false;
        }
        return in_array($platform, $event_platform[$event_type]);
    }

    /**
     * 添加事件
     * @param $goods_shop
     * @param $plan_time
     * @param $event_type
     * @return mixed
     * @throws \yii\base\Exception
     */
    public static function addEvent($goods_shop, $event_type,$plan_time = null)
    {
        $platform = $goods_shop['platform_type'];
        $shop_id = $goods_shop['shop_id'];
        $goods_no = $goods_shop['goods_no'];
        $cgoods_no = $goods_shop['cgoods_no'];
        $goods_shop_id = $goods_shop['id'];

        $where = ['goods_shop_id' => $goods_shop_id,'event_type'=>$event_type,'status' => GoodsEvent::STATUS_WAIT_RUN];
        //添加商品还未执行，不执行修改及更新价格库存等操作
        if(in_array($event_type,[GoodsEvent::EVENT_TYPE_UPDATE_GOODS,GoodsEvent::EVENT_TYPE_UPDATE_PRICE,GoodsEvent::EVENT_TYPE_UPDATE_STOCK])) {
            $where['event_type'] = [$event_type, GoodsEvent::EVENT_TYPE_ADD_GOODS];
        }
        $goods_event = GoodsEvent::find()->where($where)->limit(1)->one();
        if($goods_event) {
            return true;
        }
        $data = [
            'platform' => $platform,
            'shop_id' => $shop_id,
            'cgoods_no' => $cgoods_no,
            'goods_no' => $goods_no,
            'goods_shop_id' =>$goods_shop_id,
            'event_type' => $event_type,
            'plan_time' => is_null($plan_time)?time():$plan_time,
            'status' => GoodsEvent::STATUS_WAIT_RUN,
        ];
        return GoodsEvent::add($data);
    }

}