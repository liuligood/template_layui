<?php

namespace backend\models\search;

use common\services\sys\AccessService;
use Yii;
use common\models\ReportUserCount;



class ReportUserCountSearch extends ReportUserCount
{
    public $start_date;
    public $end_date;

    public function rules()
    {
        return [
            [['id', 'admin_id', 'date_time', 'shop_id', 'o_goods_success', 'o_goods_fail', 'o_goods_audit', 'o_goods_upload', 'order_count', 'add_time', 'update_time','start_date','end_date'], 'integer'],
        ];
    }

    public function search($params)
    {
        $where = [];

        $this->load($params);

        if (!empty($this->start_date)) {
            $where['and'][] = ['>=', 'date_time', strtotime($this->start_date)];
        }

        if (!empty($this->end_date)) {
            $where['and'][] = ['<', 'date_time', strtotime($this->end_date) + 86400];
        }

        if (!empty($this->admin_id)){
            $where['admin_id'] = $this->admin_id;
        }

        if (!empty($this->shop_id)){
            $where['shop_id'] = $this->shop_id;
        }

        //所有负责人
        if(!AccessService::hasAllUser()) {
            $where['admin_id'] = Yii::$app->user->identity->id;
        }

        return $where;
    }
}
