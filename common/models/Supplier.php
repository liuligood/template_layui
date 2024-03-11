<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%supplier}}".
 *
 * @property int $id
 * @property string $name 名称
 * @property string $contacts 联系人
 * @property string $contacts_phone 联系电话
 * @property string $address 地址
 * @property string $url 链接
 * @property string $wx_code 微信号
 * @property string $desc 备注
 * @property string $offer_file 报价文件
 * @property int $is_cooperate 是否合作，1未合作，2已合作
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class Supplier extends BaseAR
{
    const IS_NOT_COOPERATE = 1;//未合作
    const IS_COLLABORATED = 2;//已合作

    public static $is_cooperate_maps = [
        self::IS_NOT_COOPERATE => '未合作',
        self::IS_COLLABORATED => '已合作'
    ];

    public static function tableName()
    {
        return '{{%supplier}}';
    }

    public function rules()
    {
        return [
            [['add_time', 'update_time','is_cooperate'], 'integer'],
            [['name', 'contacts'], 'string', 'max' => 120],
            [['contacts_phone'], 'string', 'max' => 20],
            [['address','desc'], 'string', 'max' => 500],
            [['url'], 'string', 'max' => 1000],
            [['wx_code'], 'string', 'max' => 32],
            [['offer_file'], 'string']
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'contacts' => 'Contacts',
            'contacts_phone' => 'Contacts Phone',
            'address' => 'Address',
            'url' => 'Url',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }


    /**
     * 获取供应商名称
     * @return array
     */
    public static function allSupplierName()
    {
        $model = Supplier::find()->select(['id','name'])->asArray()->all();
        $list = [];
        foreach ($model as $v) {
            $list[$v['id']] = $v['name'];
        }
        return $list;
    }

}
