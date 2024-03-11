<?php

namespace backend\models\search;

use common\services\sys\AccessService;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\PromoteCampaign;

/**
 * PromoteCampaignSearch represents the model behind the search form of `common\models\PromoteCampaign`.
 */
class PromoteCampaignSearch extends PromoteCampaign
{
    public $start_date;
    public $end_date;
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'platform_type', 'shop_id', 'promote_id', 'status', 'add_time', 'update_time', 'type'], 'integer'],
            [['promote_name'], 'safe'],
            [['start_date','end_date'], 'integer'],
        ];
    }
    public function search($params)
    {
        $where = [];
        $this->load($params);
        if (!empty($this->id)){
            $where['pc.id'] = $this->id;
        }
        if (!empty($this->platform_type)){
            $where['pc.platform_type'] = $this->platform_type;
        }
        if (!empty($this->shop_id)){
            $where['pc.shop_id'] = $this->shop_id;
        }

        $type = $this->type;
        if ($type != '') {
            $where['pc.type'] = $type;
        }

        if(!AccessService::hasAllShop()) {
            $shop_id = Yii::$app->user->identity->shop_id;
            $shop_id = explode(',', $shop_id);
            $where['and'][] = ['in', 'pc.shop_id', $shop_id];
        }
        return $where;
    }
}
