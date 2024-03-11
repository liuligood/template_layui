<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%category_count}}".
 *
 * @property int $id
 * @property int $category_id 类目id
 * @property int $type 类型
 * @property int $count 数量
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class CategoryCount extends BaseAR
{

    const TYPE_GOODS = 1;//商品数
    const TYPE_ORDER = 2;//订单数
    const TYPE_OZON_MAPPING = 11;//ozon映射
    const TYPE_ALLEGRO_MAPPING = 12;//allegro映射
    public static $type_map = [
        self::TYPE_GOODS => '商品数',
        self::TYPE_ORDER => '订单数',
        self::TYPE_OZON_MAPPING => 'Ozon映射',
        self::TYPE_ALLEGRO_MAPPING => 'Allegro映射',
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%category_count}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['category_id', 'type', 'count', 'add_time', 'update_time'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'category_id' => 'Category ID',
            'type' => 'Type',
            'count' => 'Count',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}