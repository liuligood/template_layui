<?php

namespace common\models\financial;

use common\models\BaseAR;
use common\models\Shop;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "{{%collection_account}}".
 *
 * @property int $id
 * @property string $collection_account 收款账号
 * @property int $collecton_platform 收款平台
 * @property string $collection_owner 收款归属者
 * @property int $add_time 添加时间
 * @property int $update_time 更新时间
 */
class CollectionAccount extends BaseAR
{

    public static function tableName()
    {
        return '{{%collection_account}}';
    }


    public function rules()
    {
        return [
            [['collecton_platform', 'add_time', 'update_time'], 'integer'],
            [['collection_account', 'collection_owner'], 'string', 'max' => 32],
        ];
    }


    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'collection_account' => 'Collection Account',
            'collecton_platform' => 'Collecton Platform',
            'collection_owner' => 'Collection Owner',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }

    //获取账号列表
    public static function getListAccount(){
        $model = CollectionAccount::find()->asArray()->all();
        $list = [];
        foreach ($model as $v){
            $list[$v['id']] = $v['collection_account'].'('.Shop::$collection_maps[$v['collecton_platform']].')';
        }
        return $list;
    }

    //获取收款平台列表信息
    public static function getPlatformAccount(){
        $platform = Shop::$collection_maps;
        $list = [];
        foreach ($platform as $key => $v){
            $collection = CollectionAccount::find()->where(['collecton_platform'=>$key])->select('collection_account')->asArray()->all();
            $account = ArrayHelper::getColumn($collection,'collection_account');
            $account = implode(',',$account);
            $list[$key] = $v.'('.$account.')';
        }
        return $list;
    }


    //获取关联信息
    public static function getRelevancyAccount(){
        $model = CollectionAccount::find()->asArray()->all();
        $account = ArrayHelper::getColumn($model,'id');
        $list = [];
        foreach ($account as $v){
            $bank = CollectionBankCards::find()->where(['collection_account_id'=>$v])->asArray()->all();
            if (empty($bank)){
                $list[$v] = [];
            }
            $bank = ArrayHelper::map($bank,'id','collection_bank_cards');
            $list[$v] = $bank;
        }
        $list = json_encode($list);
        return $list;
    }

}
