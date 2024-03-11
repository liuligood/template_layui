<?php

namespace common\models;

use common\base\BaseActive;
use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%goods_shop}}".
 *
 * @property int $id
 * @property int $platform_type 所属平台
 * @property string $goods_no 商品编号
 * @property string $cgoods_no 子商品编号
 * @property string $platform_goods_id 平台对应商品id
 * @property string $platform_goods_opc 平台对应商品opc
 * @property string $platform_goods_url 平台对应商品链接
 * @property string $platform_sku_no 所属平台sku
 * @property string $platform_goods_exp_id 平台对应商品扩展id
 * @property string $country_code 国家代码
 * @property int $shop_id 店铺id
 * @property string $ean ean
 * @property int $status 状态
 * @property int $follow_claim 跟卖认领
 * @property string $original_price 原价
 * @property string $price 价格
 * @property string $discount 折扣
 * @property string $fixed_price 固定价格
 * @property int $admin_id 添加管理员
 * @property int $plan_check_price_time 计划执行价格时间
 * @property int $other_tag 额外标记
 * @property int $keywords_index 关键字顺序
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 * @property int $ad_status 是否进入广告
 */
class GoodsShop extends BaseARUnTime
{

    const ID_PREFIX = "P";
    const STATUS_NOT_UPLOADED = 0; //待上传
    const STATUS_UPLOADING = 1; //上传中
    const STATUS_UNDER_REVIEW = 2;//平台审核中
    const STATUS_NOT_TRANSLATED = 3;//未翻译
    const STATUS_SUCCESS = 10;//成功
    const STATUS_OFF_SHELF = 15;//下架
    const STATUS_FAIL = 20;//失败
    const STATUS_DELETE = 30;//删除

    public static $status_map = [
        self::STATUS_NOT_UPLOADED =>  '待上传',
        self::STATUS_NOT_TRANSLATED => '翻译中',
        self::STATUS_UPLOADING => '上传中',
        self::STATUS_UNDER_REVIEW => '审核中',
        self::STATUS_SUCCESS => '成功',
        self::STATUS_OFF_SHELF => '下架',
        self::STATUS_FAIL => '失败',
        self::STATUS_DELETE => '删除',
    ];

    const FOLLOW_CLAIM_YES = 1;//是跟卖认领
    const FOLLOW_CLAIM_NO = 0;//不是跟卖认领

    const OTHER_TAG_MEC_MULTI = -1;//美客多多变体标记
    const OTHER_TAG_OVERSEAS = 10;//官方海外仓
    const OTHER_TAG_COUPANG_CGFLIVE = 21;//库胖CGF LIVE

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%goods_shop}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['platform_type', 'shop_id', 'status', 'admin_id', 'add_time', 'update_time','plan_check_price_time','other_tag','ad_status','follow_claim'], 'integer'],
            [['price','discount','original_price','fixed_price'], 'number'],
            [['country_code'], 'string', 'max' => 20],
            [['ean'], 'string', 'max' => 64],
            [['goods_no','cgoods_no'], 'string', 'max' => 24],
            [['platform_goods_url','platform_goods_opc','platform_goods_id','platform_sku_no','keywords_index','platform_goods_exp_id'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'goods_no' => 'Goods No',
            'shop_id' => 'Shop ID',
            'status' => 'Status',
            'ean' => 'Ean',
            'price' => 'Price',
            'admin_id' => 'Admin ID',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'add_time',//根据数据库字段修改
                'updatedAtAttribute' => false,//根据数据库字段修改
                'value' => time(),
            ],
        ];
    }

    /**
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    public function fields()
    {
        $fields = parent::fields();
        $this->add_time= Yii::$app->formatter->asDatetime($this->add_time);
        $this->update_time= Yii::$app->formatter->asDatetime($this->update_time);
        return $fields;
    }

}