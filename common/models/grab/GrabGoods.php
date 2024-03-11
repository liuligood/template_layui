<?php

namespace common\models\grab;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%grab_goods}}".
 *
 * @property int $id
 * @property int $gid 采集id
 * @property string $md5 md5
 * @property int $source 来源
 * @property int $source_method 来源方式
 * @property string $category 类目
 * @property string $asin asin
 * @property string $title 标题
 * @property string $price 金额
 * @property string $evaluate 评价数
 * @property string $score 评分
 * @property string $desc 五要素+详情
 * @property string $desc1 五要素
 * @property string $desc2 详情
 * @property string $images1 图片1
 * @property string $images2 图片2
 * @property string $images3 图片3
 * @property string $images4 图片4
 * @property string $images5 图片5
 * @property string $images6 图片6
 * @property string $images7 图片7
 * @property string $url 链接
 * @property string $weight 产品重量
 * @property string $dimension 尺寸
 * @property string $brand 品牌
 * @property string $colour 颜色
 * @property int $self_logistics
 * @property int $retry_count 采集次数
 * @property int $goods_status 商品状态
 * @property int $use_status 使用状态
 * @property int $status 状态
 * @property int $admin_id 管理员
 * @property string $goods_no 商品编号
 * @property string $pgoods_no 父商品编号
 * @property int $use_time 使用时间
 * @property int $check_stock_time 检测时间
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class GrabGoods extends BaseAR
{
    const STATUS_WAIT = 0;//待采集
    const STATUS_GOING = 10;//采集中
    const STATUS_SUCCESS = 20;//采集成功
    const STATUS_FAILURE = 30;//采集失败
    const STATUS_REPEAT = 40;//采集重复
    const STATUS_CANCEL = 50;//已取消

    const SELF_LOGISTICS_YES = 1;//平台自营物流
    const SELF_LOGISTICS_NO = 0;//非平台自营物流

    const GOODS_STATUS_NORMAL = 0;//正常
    const GOODS_STATUS_OUT_STOCK = 10;//缺货

    const USE_STATUS_NONE = 0;
    const USE_STATUS_VALID = 10;
    const USE_STATUS_INVALID = 20;
    const USE_STATUS_DELETE = 30;//删除

    public static $use_status_map = [
        self::USE_STATUS_NONE => '未标记',
        self::USE_STATUS_VALID => '有效',
        self::USE_STATUS_INVALID => '作废',
    ];

    public static $goods_status_map = [
        self::GOODS_STATUS_NORMAL => '正常',
        self::GOODS_STATUS_OUT_STOCK => '缺货',
    ];


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%grab_goods}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['gid', 'status', 'admin_id', 'add_time', 'update_time', 'source_method', 'check_stock_time', 'self_logistics', 'retry_count', 'source', 'goods_status','use_status','use_time'], 'integer'],
            [['evaluate', 'score', 'desc', 'desc1', 'desc2'], 'string'],
            [['md5', 'goods_no','pgoods_no'], 'string', 'max' => 32],
            [['category','weight','dimension'], 'string', 'max' => 128],
            [['brand','colour'], 'string', 'max' => 64],
            [['asin'], 'string', 'max' => 30],
            [[ 'price', 'images1', 'images2', 'images3', 'images4', 'images5', 'images6', 'images7'], 'string', 'max' => 256],
            [['title','url'], 'string', 'max' => 1000],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'gid' => 'Gid',
            'md5' => 'Md5',
            'category' => 'Category',
            'asin' => 'Asin',
            'title' => 'Title',
            'price' => 'Price',
            'evaluate' => 'Evaluate',
            'score' => 'Score',
            'desc' => 'Desc',
            'desc1' => 'Desc1',
            'desc2' => 'Desc2',
            'images1' => 'Images1',
            'images2' => 'Images2',
            'images3' => 'Images3',
            'images4' => 'Images4',
            'images5' => 'Images5',
            'images6' => 'Images6',
            'images7' => 'Images7',
            'url' => 'Url',
            'status' => 'Status',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}
