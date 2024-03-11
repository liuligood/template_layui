<?php

namespace common\services\id;

use common\models\GoodsShop;
use Yii;

class PlatformGoodsSkuIdService extends IdService
{

    public function checkIdExist($id){
        $platform_sku_no = GoodsShop::ID_PREFIX.$id;
        if(GoodsShop::findOne(['platform_sku_no'=>$platform_sku_no])){
            return true;
        }
        return false;
    }

} 