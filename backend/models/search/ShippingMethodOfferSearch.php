<?php
namespace backend\models\search;

use common\models\sys\ShippingMethodOffer;
use Yii;

class ShippingMethodOfferSearch extends ShippingMethodOffer
{

    public function rules()
    {
        return [
            [['status','shipping_method_id'], 'integer'],
            [['shipping_method_code','shipping_method_name','transport_code','country_code'], 'string'],
        ];
    }

    public function search($params)
    {
        $where = [];

        $this->load($params);

        if (!empty($this->shipping_method_id)) {
            $where['shipping_method_id'] = $this->shipping_method_id;
        }

        if (!empty($this->country_code)) {
            $where['country_code'] = $this->country_code;
        }

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