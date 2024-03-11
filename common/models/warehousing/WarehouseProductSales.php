<?php

namespace common\models\warehousing;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%warehouse_product_sales}}".
 *
 * @property int $id
 * @property string $cgoods_no 子商品编号
 * @property int $warehouse_id 仓库id
 * @property int $one_day_sales 1日销量
 * @property int $seven_day_sales 7日销量
 * @property int $fifteen_day_sales 15日销量
 * @property int $thirty_day_sales 30日销量
 * @property int $ninety_day_sales 90日销量
 * @property int $total_sales 总销量
 * @property int $safe_stock_type 安全库存类型
 * @property string $safe_stock_param 安全库存参数
 * @property int $stock_up_day 备货天数
 * @property int $order_frequency 出单频次
 * @property int $safe_stock_num 安全库存数
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class WarehouseProductSales extends BaseAR
{
    const TYPE_STOCK_UP = 1;//按备货天数计算
    const TYPE_SALES_WEIGHT = 2;//按销量权重计算
    const TYPE_FIXED = 3;//设置固定值

    public static $type_maps = [
        self::TYPE_STOCK_UP => '按备货天数计算',
        self::TYPE_SALES_WEIGHT => '按销量权重计算',
        self::TYPE_FIXED => '设置固定值'
    ];

    const BALANCED_ALGORITHM = 1;//均衡算法
    const RECENT_WEIGHTING_ALGORITHM = 2;//近期加权算法
    const PERIOD_WEIGHTING_ALGORITHM = 3;//长期加权算法

    public static $algorithm_maps = [
        self::BALANCED_ALGORITHM => '均衡算法',
        self::RECENT_WEIGHTING_ALGORITHM => '近期加权算法',
        self::PERIOD_WEIGHTING_ALGORITHM => '长期加权算法'
    ];

    public static function tableName()
    {
        return '{{%warehouse_product_sales}}';
    }

    public function rules()
    {
        return [
            [['warehouse_id', 'one_day_sales', 'seven_day_sales', 'fifteen_day_sales', 'thirty_day_sales', 'ninety_day_sales', 'total_sales', 'safe_stock_type', 'stock_up_day', 'safe_stock_num', 'add_time', 'update_time', 'order_frequency'], 'integer'],
            [['safe_stock_param'], 'string'],
            [['cgoods_no'], 'string', 'max' => 24],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'cgoods_no' => 'Cgoods No',
            'warehouse_id' => 'Warehouse ID',
            'one_day_sales' => 'One Day Sales',
            'seven_day_sales' => 'Seven Day Sales',
            'fifteen_day_sales' => 'Fifteen Day Sales',
            'thirty_day_sales' => 'Thirty Day Sales',
            'ninety_day_sales' => 'Ninety Day Sales',
            'total_sales' => 'Total Sales',
            'safe_stock_type' => 'Safe Stock Type',
            'safe_stock_param' => 'Safe Stock Param',
            'stock_up_day' => 'Stock Up Day',
            'safe_stock_num' => 'Safe Stock Num',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}
