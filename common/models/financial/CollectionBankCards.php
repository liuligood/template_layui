<?php

namespace common\models\financial;

use common\models\BaseAR;
use common\models\Shop;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "{{%collection_bank_cards}}".
 *
 * @property int $id
 * @property int $collection_account_id 收款账号id
 * @property string $collection_bank_cards 收款银行卡
 * @property string $collection_currency 收款币种
 * @property int $add_time 添加时间
 * @property int $update_time 更新时间
 */
class CollectionBankCards extends BaseAR
{

    public static function tableName()
    {
        return '{{%collection_bank_cards}}';
    }


    public function rules()
    {
        return [
            [['collection_account_id', 'add_time', 'update_time'], 'integer'],
            [['collection_bank_cards'], 'string', 'max' => 32],
            [['collection_currency'], 'string', 'max' => 3],
        ];
    }


    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'collection_account_id' => 'Collection Account ID',
            'collection_bank_cards' => 'Collection Bank Cards',
            'collection_currency' => 'Collection Currency',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }

    //获取账号列表
    public static function getListBank(){
        $model = CollectionBankCards::find()->asArray()->all();
        $list = [];
        foreach ($model as $v){
            $list[$v['id']] = $v['collection_bank_cards'];
        }
        return $list;
    }


    //获取银行卡的店铺
    public static function getShopName($bank_id){
        $shop = Shop::find()->where(['collection_bank_cards_id'=>$bank_id])->select('name')->asArray()->all();
        $shop_name = ArrayHelper::getColumn($shop,'name');
        $shop_name = implode(',',$shop_name);
        return empty($shop_name) ? "" : $shop_name;
    }

}
