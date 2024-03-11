<?php

namespace backend\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\sys\ChatgptTemplate;

class ChatgptTemplateSearch extends ChatgptTemplate
{

    public function rules()
    {
        return [
            [['id', 'template_type', 'add_time', 'update_time','status'], 'integer'],
            [['template_name', 'template_code', 'template_content', 'template_param_desc'], 'string'],
        ];
    }


    public function search($params)
    {
        $where = [];

        $this->load($params);

        if (!empty($this->template_type)) {
            $where['template_type'] = $this->template_type;
        }

        if (!empty($this->template_name)) {
            $where['and'][] = ['like','template_name',$this->template_name];
        }

        if (!empty($this->status)) {
            $where['status'] = $this->status;
        }

        if (!empty($this->template_code)) {
            $where['template_code'] = $this->template_code;
        }

        return $where;
    }
}
