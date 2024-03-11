<?php

namespace backend\models\search;

use common\models\grab\GrabCookies;


class GrabCookiesSearch extends GrabCookies
{

    public function rules()
    {
        return [
            [['id', 'platform_type', 'cookie', 'status', 'exec_num', 'add_time', 'update_time'], 'integer'],
        ];
    }

    public function search($params)
    {
        $where = [];

        $this->load($params);

        if (!empty($this->platform_type)) {
            $where['platform_type'] = $this->platform_type;
        }

        if (!empty($this->status)) {
            $where['status'] = $this->status;
        }

        if (!empty($this->cookie)) {
            $where['cookie'] = $this->cookie;
        }

        return $where;
    }
}
