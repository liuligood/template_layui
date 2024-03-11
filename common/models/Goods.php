<?php

namespace common\models;

use common\services\id\GoodsIdService;
use Yii;
use yii\base\Exception;

/**
 * This is the model class for table "{{%goods}}".
 *
 * @property int $id
 * @property int $category_id 分类id
 * @property int $source_method 来源方式
 * @property int $source_method_sub 来源方式子类型
 * @property int $goods_tort_type 商品侵权类型
 * @property string $goods_no 商品编号
 * @property string $sku_no sku编号
 * @property string $goods_name 名称
 * @property string $goods_keywords 关键字
 * @property string $goods_name_cn 中文标题
 * @property string $goods_short_name_cn 中文短标题
 * @property string $brand 品牌
 * @property string $goods_short_name 短标题
 * @property string $goods_img 商品图片
 * @property string $goods_desc 商品简要描述
 * @property string $goods_content 商品详细说明
 * @property string $colour 颜色
 * @property string $price 价格
 * @property string $gbp_price 英镑价格
 * @property string $weight 重量
 * @property string $real_weight 实际重量
 * @property string $size 尺寸
 * @property string $specification 规格型号
 * @property int $electric 是否带电
 * @property int $status 状态
 * @property int $stock 库存
 * @property int $goods_stamp_tag 属性标签
 * @property int $source_platform_type 来源平台
 * @property string $source_platform_title 来源标题
 * @property string $source_platform_url 来源平台链接
 * @property string $source_platform_id 来源平台商品id
 * @property string $source_platform_category_id 来源平台分类id
 * @property string $source_platform_category_name 来源平台分类名称
 * @property int $source_grab_id 来源采集id
 * @property int $check_stock_time 检测时间
 * @property int $sync_img 同步图片
 * @property int $admin_id 管理员
 * @property int $owner_id 所属者
 * @property int $goods_type 商品类型
 * @property int $size_type 尺寸类型
 * @property string $language 语言
 * @property string $property 变体属性
 * @property string $fgoods_no 精品商品编号
 * @property string $currency 货币
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class Goods extends BaseAR
{
    const ID_PREFIX = "G";

    const GOODS_SOURCE_METHOD_SUB_GRAB = 1;//采集
    const GOODS_SOURCE_METHOD_SUB_FINE = 2;//精品
    const GOODS_SOURCE_METHOD_SUB_DISTRIBUTION = 4;//分销
    public static $source_method_sub_map = [
        self::GOODS_SOURCE_METHOD_SUB_GRAB => '采集',
        self::GOODS_SOURCE_METHOD_SUB_FINE => '精品',
        self::GOODS_SOURCE_METHOD_SUB_DISTRIBUTION => '分销',
    ];

    const GOODS_STATUS_UNCONFIRMED = 0;//未确认
    const GOODS_STATUS_UNALLOCATED = 1;//未分配
    const GOODS_STATUS_WAIT_ADDED = 5;//待完善
    const GOODS_STATUS_WAIT_MATCH = 8;//待匹配
    const GOODS_STATUS_VALID = 10;//正常
    const GOODS_STATUS_INVALID = 20;//禁用

    public static $status_map = [
        self::GOODS_STATUS_UNCONFIRMED => '未确认',
        self::GOODS_STATUS_UNALLOCATED => '待分配',
        self::GOODS_STATUS_WAIT_ADDED => '待完善',
        self::GOODS_STATUS_WAIT_MATCH => '待匹配',
        self::GOODS_STATUS_VALID => '正常',
        self::GOODS_STATUS_INVALID => '禁用',

    ];

    const GOODS_REASON_ONE = 11;//侵权
    const GOODS_REASON_TWO = 3;//假冒
    const GOODS_REASON_THREE = 4;//无法寄送
    const GOODS_REASON_FOURTH = 7;//找不到商品
    const GOODS_REASON_FIRTH = 6;//其他
    public static $reason_map=[
    		self::GOODS_REASON_ONE =>'侵权',
    		self::GOODS_REASON_TWO =>'假冒',
    		self::GOODS_REASON_THREE =>'无法寄送',
    		self::GOODS_REASON_FOURTH =>'找不到商品',
    		self::GOODS_REASON_FIRTH =>'其他',
    ];

    const STOCK_NO = 0;
    const STOCK_YES = 1;

    public static $stock_map = [
        self::STOCK_YES => '销售',
        self::STOCK_NO => '停止销售',
    ];

    /**
     * 同步图片
     */
    const SYNC_STATUS_IMG = 1;

    /**
     * 同步标签
     */
    const SYNC_STATUS_KEYWORDS = 2;

    /**
     * 同步修改中文标题
     */
    const SYNC_STATUS_TITLE_CN = 4;

    /**
     * 同步执行多标题
     */
    const SYNC_STATUS_MORE_TITLE = 8;

    /**
     * 同步翻译失败
     */
    const SYNC_STATUS_TRANSLATION_FAILED = 16;

    /**
     * 同步图片失败
     */
    const SYNC_STATUS_IMG_FAILED = 32;

    public static $sync_status_map = [
        self::SYNC_STATUS_IMG => '同步图片',
        self::SYNC_STATUS_KEYWORDS => '同步标签',
        self::SYNC_STATUS_TITLE_CN => '同步修改标题',
        self::SYNC_STATUS_TRANSLATION_FAILED => '同步翻译失败',
        self::SYNC_STATUS_IMG_FAILED => '同步图片失败',
        self::SYNC_STATUS_MORE_TITLE => '同步多标题',
    ];

    /**
     * 开店产品
     */
    const GOODS_STAMP_TAG_OPEN_SHOP = 10;

    /**
     * 精细产品
     */
    //const GOODS_STAMP_TAG_FINE = 20;

    public static $goods_stamp_tag_map = [
        //self::GOODS_TORT_TYPE_IMPERFECT => '待完善',
        //self::GOODS_TORT_TYPE_LOW_PRICE => '低价',
        //self::GOODS_TORT_TYPE_CATEGORY_ERROR => '类目错误',
        self::GOODS_STAMP_TAG_OPEN_SHOP => '开店商品',
    ];

    const GOODS_TYPE_SINGLE = 1; //单品
    const GOODS_TYPE_MULTI = 2; //多变体

    public static $goods_type_map = [
        self::GOODS_TYPE_SINGLE => '单品',
        self::GOODS_TYPE_MULTI => '多变体',
    ];

    const GOODS_SIZE_TYPE_SMALL_MICRO = 1;//微
    const GOODS_SIZE_TYPE_SMALL = 2; //小
    const GOODS_SIZE_TYPE_MIDDLE = 3; //中
    const GOODS_SIZE_TYPE_BIG = 4; //大
    public static $goods_size_type = [
        0 => '未知',
        self::GOODS_SIZE_TYPE_SMALL_MICRO => '微件',
        self::GOODS_SIZE_TYPE_SMALL => '小件',
        self::GOODS_SIZE_TYPE_MIDDLE => '适中',
        self::GOODS_SIZE_TYPE_BIG => '大件',
    ];

    /**
     * 待完善商品
     */
    const GOODS_TORT_TYPE_IMPERFECT = 21;

    /**
     * 低价商品
     */
    const GOODS_TORT_TYPE_LOW_PRICE = 22;

    /**
     * 类目错误
     */
    const GOODS_TORT_TYPE_CATEGORY_ERROR = 7;

    public static $grab_goods_tort_type_map = [
        0 => '待配对',
        self::GOODS_TORT_TYPE_IMPERFECT => '待完善',
        self::GOODS_TORT_TYPE_LOW_PRICE => '低价商品',
        self::GOODS_TORT_TYPE_CATEGORY_ERROR => '类目错误',
    ];

    public static $goods_tort_type_map = [
        0 => '未归类',
        1 => '正常',
        2 => '图片带Logo',
        3 => '带品牌名',
        4 => '可能侵权',
        5 => '侵权',
        6 => '假冒商品',
        8 => '违禁品',
        7 => '类目错误',
        9 => '主图不好',
        10 => '图片存在多色|多款',
        11 => '待二审',
    ];

    /**
     * 汇率
     */
    const GOODS_CURRENCY_CNY = 'CNY';
    const GOODS_CURRENCY_USD = 'USD';
    const GOODS_CURRENCY_GBP = 'GBP';
    const GOODS_CURRENCY_EUR = 'EUR';

    public static $goods_currency_maps=[
        self::GOODS_CURRENCY_CNY => '人民币(CNY)',
        self::GOODS_CURRENCY_USD => '美元(USD)',
        self::GOODS_CURRENCY_GBP => '英镑(GBP)',
        self::GOODS_CURRENCY_EUR => '欧元(EUR)',
    ];


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%goods}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['goods_keywords','goods_desc', 'goods_content','brand','property'], 'string'],
            [['source_method','source_method_sub' , 'status', 'category_id', 'source_platform_type', 'admin_id', 'add_time', 'update_time', 'stock', 'check_stock_time','sync_img', 'electric', 'goods_stamp_tag','owner_id','source_grab_id','goods_type','size_type','goods_tort_type'], 'integer'],
            [['goods_no','fgoods_no'], 'string', 'max' => 24],
            [['sku_no','size','source_platform_category_id','colour'], 'string', 'max' => 32],
            [['source_platform_id'], 'string', 'max' => 64],
            [['goods_name','goods_short_name','goods_name_cn','goods_short_name_cn','specification','source_platform_title'], 'string', 'max' => 256],
            [['price','weight','real_weight','gbp_price'], 'number'],
            [['goods_img','source_platform_url','source_platform_category_name','language'], 'string'],
            [['currency'],'string','max'=> 3],
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
            'goods_name' => 'Goods Name',
            'goods_img' => 'Goods Img',
            'goods_desc' => 'Goods Desc',
            'goods_content' => 'Goods Content',
            'status' => 'Status',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }

    //保存前处理
    public function beforeSave($insert)
    {
        if ($insert) {
            if (empty($this->goods_no)) {
                $id_server = new GoodsIdService();
                $this->goods_no =  self::ID_PREFIX . $id_server->getNewId();
            }
        }

        if(empty($this->sku_no)) {
            $id_server = new GoodsIdService();
            $this->sku_no = 'S' . $id_server->getNewId();
        }
        if(empty($this->currency)) {
            $this->currency = 'CNY';
        }
        return parent::beforeSave($insert);
    }

    /**
     * 根据条件 获取订单总数
     * @param array $where
     * @return int
     */
    public static function getCountByCond($where = [],$query = null)
    {
//        $query = self::dealWhere($where);
//        return $query->count();
        return parent::getCacheCountByCond($where,$query,__CLASS__.__FUNCTION__);
    }

    /**
     * 添加记录
     * @param $data
     * @return mixed
     * @throws Exception
     */
    public static function addGoods($data)
    {
        $model = new static();
        $model->load($data, '');
        if($model->validate() && $model->save()){
            return $model->goods_no;
        }else{
            throw new Exception(current($model->getFirstErrors()));
        }
    }

}
