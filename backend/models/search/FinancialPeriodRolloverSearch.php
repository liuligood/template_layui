<?php

namespace backend\models\search;

use common\models\Order;
use common\models\OrderGoods;
use common\services\financial\PlatformSalesPeriodService;
use common\services\sys\AccessService;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\FinancialPeriodRollover;
use yii\helpers\ArrayHelper;

/**
 * FinancialPeriodRolloverSearch represents the model behind the search form of `common\models\FinancialPeriodRollover`.
 */
class FinancialPeriodRolloverSearch extends FinancialPeriodRollover
{
    public $start_date;
    public $end_date;
    public $start_collection_time;
    public $end_collection_time;
    public $sku_no;
    public $operation_value;
    public function rules()
    {
        return [
            [['platform_type', 'shop_id','amount'], 'integer'],
            [['operation'], 'string', 'max' => 256],
            [['start_date','end_date','start_collection_time','end_collection_time','sku_no','operation_value'], 'string'],
            [['relation_no'],'string','max' => 64],
            [['id'],'integer']
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function search($params)
    {
        $where = [];
        $this->load($params);
        if (!empty($this->platform_type)) {
            $where['platform_type'] = $this->platform_type;
        }
        if (!empty($this->shop_id)) {
            $where['shop_id'] = $this->shop_id;
        }
        if (!empty($this->operation)) {
            $where['operation'] = PlatformSalesPeriodService::$OPREATION_ALL_MAP[$this->operation];
        }
        if (!empty($this->relation_no)) {
            $where['relation_no'] = $this->relation_no;
        }
        if (!empty($this->start_date)) {
            $where['and'][] = ['>=', 'date', strtotime($this->start_date)];
        }
        if (!empty($this->end_date)) {
            $where['and'][] = ['<', 'date', strtotime($this->end_date) + 86400];
        }
        if (!empty($this->start_collection_time)) {
            $where['and'][] = ['>=', 'collection_time', strtotime($this->start_collection_time)];
        }
        if (!empty($this->end_collection_time)) {
            $where['and'][] = ['<', 'collection_time', strtotime($this->end_collection_time) + 86400];
        }
        if(!AccessService::hasAllShop()) {
            $shop_id = Yii::$app->user->identity->shop_id;
            $shop_id = explode(',', $shop_id);
            $where['and'][] = ['in', 'shop_id', $shop_id];
        }
        if (!empty($this->amount)) {
            $amount = trim($this->amount);
            $where['amount'] = $amount;
        }
        if (!empty($this->sku_no)) {
            $order = OrderGoods::find()->alias('og')->select('o.relation_no')
                ->leftJoin(Order::tableName().' o','o.order_id = og.order_id')
                ->where(['og.platform_asin' => $this->sku_no])
                ->asArray()->all();
            $relation_no = ArrayHelper::getColumn($order,'relation_no');
            $where['relation_no'] = $relation_no;
        }
        if (!empty($this->operation_value)) {
            $where['and'][] = ['like','operation',$this->operation_value];
        }
        return $where;
    }
}
