<?php

namespace common\services\id;

use common\models\Goods;
use Yii;

class GoodsIdService extends IdService
{

    public function checkIdExist($id){
        $goods_id = Goods::ID_PREFIX.$id;
        if(Goods::findOne(['goods_no'=>$goods_id])){
            return true;
        }
        return false;
    }

} 