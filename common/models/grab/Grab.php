<?php

namespace common\models\grab;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%grab}}".
 *
 * @property int $id
 * @property string $md5 md5
 * @property int $source_method 来源方式 1自建 2亚马逊
 * @property int $category_id 分类id
 * @property int $source 来源
 * @property string $title 标题
 * @property string $url 采集链接
 * @property int $admin_id 提交管理员
 * @property int $status 状态
 * @property int $page 采集页数
 * @property int $price_calculation 价格计算（乘以）
 * @property int $cur_lists_page 当前列表采集页数
 * @property int $retry_count 采集次数
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class Grab extends BaseAR
{

    const STATUS_WAIT = 0;//待采集
    const STATUS_GOING = 10;//采集中
    const STATUS_SUCCESS = 20;//采集成功
    const STATUS_FAILURE = 30;//采集失败
    const STATUS_CANCEL = 40;//已取消
    const STATUS_DELETE = 50;//删除

    public static $status_map = [
        self::STATUS_WAIT => '待采集',
        self::STATUS_GOING => '采集中',
        self::STATUS_SUCCESS => '采集成功',
        self::STATUS_FAILURE => '采集失败',
        self::STATUS_CANCEL => '已取消',
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%grab}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['admin_id', 'source_method', 'status', 'add_time', 'update_time', 'cur_lists_page', 'page', 'retry_count', 'source','category_id'], 'integer'],
            [['md5'], 'string', 'max' => 32],
            [['title'], 'string', 'max' => 256],
            [['url'], 'string', 'max' => 1000],
            [['price_calculation'], 'number'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'md5' => 'Md5',
            'title' => 'Title',
            'url' => 'Url',
            'admin_id' => 'Admin ID',
            'status' => 'Status',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}
