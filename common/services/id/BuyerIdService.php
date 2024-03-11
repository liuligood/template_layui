<?php

namespace common\services\id;

use common\models\BuyerAccount;
use Yii;

class BuyerIdService extends IdService
{

    public function checkIdExist($id){
        $buyer_id = BuyerAccount::ID_PREFIX.$id;
        if(BuyerAccount::findOne(['buyer_id'=>$buyer_id])){
            return true;
        }
        return false;
    }

} 