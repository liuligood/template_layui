<?php
namespace backend\models\search;

use common\models\sys\ShippingMethod;
use Yii;

class ShippingMethodSearch extends ShippingMethod
{

    public function rules()
    {
        return [
            [['status'], 'integer'],
            [['shipping_method_code','shipping_method_name','transport_code'], 'string'],
        ];
    }

    public function search($params)
    {
        $where = [];

        $this->load($params);

        if (!empty($this->transport_code)) {
            $where['transport_code'] = $this->transport_code;
        }

        if (!empty($this->shipping_method_code)) {
            $where['shipping_method_code'] = $this->shipping_method_code;
        }

        if (!empty($this->shipping_method_name)) {
            $where['and'][] = ['like', 'shipping_method_name', $this->shipping_method_name];
        }

        if (!empty($this->status)) {
            $where['status'] = $this->status;
        }

        return $where;
    }

}