<?php

namespace backend\models\search;

use common\models\PromoteCampaign;
use common\services\sys\AccessService;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\PromoteCampaignDetails;

/**
 * PromoteCampaignDetailsSearch represents the model behind the search form of `common\models\PromoteCampaignDetails`.
 */
class PromoteCampaignDetailsSearch extends PromoteCampaignDetails
{
    public $start_date;
    public $end_date;
    public $type;
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['platform_type', 'shop_id', 'promote_id', 'impressions', 'hits',  'order_volume', 'model_orders',  'promote_time', 'add_time', 'update_time'], 'integer'],
            [[ 'cgoods_no','platform_goods_opc'], 'string', 'max' => 256],
            [[ 'promotes','model_sales', 'order_sales'], 'number'],
            [['promote_name'], 'safe'],
            [['start_date','end_date','type'], 'integer'],
        ];
    }

    public function search($params)
    {
        $where = [];
        $this->load($params);
        if(!empty($this->cgoods_no)){
            $where['cgoods_no'] = $this->cgoods_no;
        }
        if(!empty($this->platform_goods_opc)){
            $where['platform_goods_opc'] = $this->platform_goods_opc;
        }
        if (!empty($this->start_date)) {
            $where['and'][] = ['>=', 'promote_time', strtotime($this->start_date)];
        }
        if (!empty($this->end_date)) {
            $where['and'][] = ['<', 'promote_time', strtotime($this->end_date) + 86400];
        }
        if (!empty($this->platform_type)) {
            $where['platform_type'] = $this->platform_type;
        }
        if (!empty($this->shop_id)) {
            $where['shop_id'] = $this->shop_id;
        }
        $type = $this->type;
        if ($type != '') {
            $details_id = PromoteCampaignDetails::find()->alias('pcd')
                ->select('pcd.id as id')
                ->leftJoin(PromoteCampaign::tableName().' pc','pc.id = pcd.promote_id')
                ->where(['type' => $type]);
            $where['and'][] = ['in', 'id', $details_id];
        }
        if(!AccessService::hasAllShop()) {
            $shop_id = Yii::$app->user->identity->shop_id;
            $shop_id = explode(',', $shop_id);
            $where['and'][] = ['in', 'shop_id', $shop_id];
        }
        return $where;
    }
}
