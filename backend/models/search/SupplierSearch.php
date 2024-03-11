<?php

namespace backend\models\search;

use common\models\Supplier;


class SupplierSearch extends Supplier
{

    public function rules()
    {
        return [
            [['id', 'add_time', 'update_time', 'is_cooperate'], 'integer'],
            [['name', 'contacts', 'contacts_phone', 'address', 'url', 'wx_code'], 'string'],
        ];
    }


    public function search($params,$is_cooperate)
    {
        $where = [];

        $this->load($params);

        $where['is_cooperate'] = $is_cooperate;

        if (!empty($this->name)) {
            $where['and'][] = ['like', 'name', $this->name];
        }

        if (!empty($this->contacts)) {
            $where['and'][] = ['like', 'contacts', $this->contacts];
        }

        if (!empty($this->contacts_phone)) {
            $where['and'][] = ['like', 'contacts_phone', $this->contacts_phone];
        }

        if (!empty($this->wx_code)) {
            $where['wx_code'] = $this->wx_code;
        }

        return $where;
    }
}
