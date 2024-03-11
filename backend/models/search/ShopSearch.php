<?php

namespace backend\models\search;

use common\services\sys\AccessService;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;


use common\models\Shop;
use backend\controllers\ShopController;

/**
 * ShopSearch represents the model behind the search form of `common\models\Shop`.
 */
class ShopSearch extends Shop
{
    
    public $adminid; 
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','status','platform_type','admin_id','platform_type','sale_status'], 'integer'],
            [['name','currency','brand_name'], 'string'],
        ];
    }


    public function search($params)
    {
        $where = [];

        $this->load($params);

        if (!empty($this->name)) {
            $where['and'][] = ['like', 'name', $this->name];
        }

        if (!empty($this->brand_name)) {
            $where['and'][] = ['like', 'brand_name', $this->brand_name];
        }

        if (!empty($this->id)) {
            $where['and'][] = ['like', 'id', $this->id];
        }

        if (!empty($this->status)) {
            $where['status'] = $this->status;
        }

        if (!empty($this->platform_type)) {
            $where['platform_type'] = $this->platform_type;
        }

        if (!empty($this->admin_id)) {
            $where['admin_id'] = $this->admin_id;
        }

        if (!empty($this->sale_status)) {
            $where['sale_status'] = $this->sale_status;
        }

        //店铺数据
        if(!AccessService::hasAllShop()) {
            $shop_id = Yii::$app->user->identity->shop_id;
            $shop_id = explode(',', $shop_id);
            $where['id'] = $shop_id;
        }

        return $where;
    }
}
