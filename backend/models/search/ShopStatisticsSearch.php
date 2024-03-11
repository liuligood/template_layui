<?php

namespace backend\models\search;

use common\components\statics\Base;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\ShopStatistics;


class ShopStatisticsSearch extends ShopStatistics
{

    public function rules()
    {
        return [
            [['id', 'platform_type', 'shop_id', 'online_products', 'add_time', 'update_time'], 'integer'],
        ];
    }



    public function search($params,$tag)
    {
        $this->load($params);

        $where = [];

        switch ($tag){
            case 1://Ozon
                $where['platform_type'] =  Base::PLATFORM_OZON;
                break;
        }

        return $where;
    }
}
