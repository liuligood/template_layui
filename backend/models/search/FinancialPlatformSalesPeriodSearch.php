<?php

namespace backend\models\search;

use common\services\sys\AccessService;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\FinancialPlatformSalesPeriod;

/**
 * FinancialPlatformSalesPeriodSearch represents the model behind the search form of `common\models\FinancialPlatformSalesPeriod`.
 */
class FinancialPlatformSalesPeriodSearch extends FinancialPlatformSalesPeriod
{
    public $start_date;
    public $end_date;
    public $shop_collecton;
    public function rules()
    {
        return [
            [['platform_type', 'shop_id','payment_back','id','shop_collecton','objection'], 'integer'],
            [['start_date','end_date'], 'integer'],
            [['currency'], 'string', 'max' => 32],
        ];
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return
     */
    public function search($params)
    {
        $where = [];
        $this->load($params);
        if (!empty($this->id)){
            $where['id'] = $this->id;
        }
        //回款表跳转过来时
        if (!empty($this->shop_collecton)){
            $where['shop_id'] = $this->shop_collecton;
        }
        if (!empty($this->platform_type)) {
            $where['platform_type'] = $this->platform_type;
        }
        if (!empty($this->shop_id)) {
            $where['shop_id'] = $this->shop_id;
        }
        if (!empty($this->payment_back)) {
            $where['payment_back'] = $this->payment_back;
        }
        if(!empty($this->objection)){
            $where['objection']=$this->objection;
        }
        if (!empty($this->currency)) {
            $where['currency'] = $this->currency;
        }
        if (!empty($this->start_date)) {
            $where['and'][] = ['>=', 'data', strtotime($this->start_date)];
        }
        if (!empty($this->end_date)) {
            $where['and'][] = ['<', 'data', strtotime($this->end_date) + 86400];
        }

        if(!AccessService::hasAllShop()) {
            $shop_id = Yii::$app->user->identity->shop_id;
            $shop_id = explode(',', $shop_id);
            $where['and'][] = ['in', 'shop_id', $shop_id];
        }
        return $where;
    }
}
