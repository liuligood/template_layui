<?php

namespace common\models\sys;

use common\models\BaseAR;
use common\services\goods\GoodsService;
use Yii;

/**
 * This is the model class for table "{{%sys_shipping_method}}".
 *
 * @property int $id
 * @property string $transport_code 物流商代码
 * @property string $shipping_method_code
 * @property string $shipping_method_name 物流商运输服务名
 * @property int $electric_status 是否带电 0:否 1:是
 * @property int $status 0:未启用 10:已启用
 * @property int $recommended 推荐物流 0:否 10:是
 * @property int $cjz 材积重
 * @property string $currency 货币
 * @property string $formula 公式
 * @property int $warehouse_id 仓库id
 * @property int $add_time 创建时间
 * @property int $update_time 更新时间
 */
class ShippingMethod extends BaseAR
{

    const STATUS_VALID = 10;
    const STATUS_INVALID = 20;

    public static $status_map = [
        self::STATUS_VALID => '正常',
        self::STATUS_INVALID => '禁用',
    ];

    const RECOMMENDED_YES = 1;
    const RECOMMENDED_NO = 0;

    public static $recommended_map = [
        self::RECOMMENDED_YES => '是',
        self::RECOMMENDED_NO => '否',
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%sys_shipping_method}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['transport_code', 'shipping_method_code'], 'required'],
            [['status', 'add_time', 'update_time','electric_status','recommended','cjz','warehouse_id'], 'integer'],
            [['transport_code'], 'string', 'max' => 50],
            [['shipping_method_code', 'shipping_method_name'], 'string', 'max' => 100],
            [['transport_code', 'shipping_method_code'], 'unique', 'targetAttribute' => ['transport_code', 'shipping_method_code']],
            [['formula','currency'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'transport_code' => 'Transport Code',
            'shipping_method_code' => 'Shipping Method Code',
            'shipping_method_name' => 'Shipping Method Name',
            'status' => 'Status',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }

    /**
     * 执行公式 符合该公式的为true 不符合为false
     * @param $goods
     * @param $formula
     * @return bool|mixed
     */
    public static function execFormula($goods,$formula)
    {
        if (empty($formula)) {
            return true;
        }

        if (empty($goods['size'])) {
            return true;
        }

        $size = GoodsService::getSizeArr($goods['size']);
        if (empty($size)) {
            return true;
        }
        $size_l = (float)$size['size_l'];
        $size_w = (float)$size['size_w'];
        $size_h = (float)$size['size_h'];
        $max_l = (float)max($size_l, $size_h, $size_w);
        $weight = (float)(!empty($goods['real_weight']) && $goods['real_weight'] > 0?$goods['real_weight']:(empty($goods['weight'])?0:$goods['weight']));
        $formula = str_replace(['最长', '长', '宽', '高', '重量', PHP_EOL], ['$max_l', '$size_l', '$size_w', '$size_h', '$weight', '&&'], $formula);
        $bool = eval("return " . $formula . ';');
        return $bool ? true : false;
    }

    /**
     * 验证公式
     * @param $formula
     * @return bool
     */
    public static function verifyFormula($formula)
    {
        if(empty($formula)) {
            return true;
        }
        $ch_formula = str_replace(['最长', '长', '宽', '高', PHP_EOL, ' '], [''], $formula);
        if (preg_match("/^[0-9+-><=]+$/", $ch_formula)) {
            $a = 20;
            $formula = str_replace(['最长', '长', '宽', '高', PHP_EOL], ['$a', '$a', '$a', '$a', '&&'], $formula);
            eval("return " . $formula . ';');
            return true;
        }
        return false;
    }

}