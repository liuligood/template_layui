<?php

namespace common\services\sys;

use common\components\statics\Base;
use common\models\Goods;
use common\models\GoodsEvent;
use common\models\GoodsShop;
use common\models\Order;
use common\models\sys\SystemOperlog;
use common\services\api\GoodsEventService;
use common\services\goods\FGoodsService;
use common\services\goods\GoodsService;
use common\services\goods\GoodsTranslateService;
use Yii;
use yii\helpers\ArrayHelper;
use phpDocumentor\Reflection\Types\Self_;
use phpDocumentor\Reflection\Types\This;

class SystemOperlogService
{

    const OBJECT_TYPE_ORDER = 1;//订单
    const OBJECT_TYPE_GOODS = 2;//商品

    public $op_user_info = [];

    public $op_name = '';

    public $type = SystemOperlog::TYPE_UPDATE;

    const ACTION_ORDER_CREATE = 100;//创建订单
    const ACTION_ORDER_CANCEL = 120;//取消订单
    const ACTION_ORDER_REFUND = 130;//订单退款
    const ACTION_ORDER_UPDATE = 135;//订单编辑
    const ACTION_ORDER_LOGISTICS = 140;//编辑运单号
    const ACTION_ORDER_RESET_LOGISTICS = 150;//打回待处理
    const ACTION_ORDER_SCAN_SHIP = 160;//扫描发货
    const ACTION_ORDER_MOVE_ABNORMAL = 161;//移入异常
    const ACTION_ORDER_ABNORMAL = 162;//恢复异常
    const ACTION_ORDER_VIRTUAL_SHIP = 163;//虚拟发货
    const ACTION_ORDER_SHIP = 164;//发货




    const ACTION_GOODS_CREATE = 200;//创建商品
    const ACTION_GOODS_GRAB_CREATE = 201;//采集商品
    const ACTION_GOODS_COPY_CREATE = 202;//复制商品
    const ACTION_GOODS_UPDATE = 210;//编辑商品
    const ACTION_GOODS_PRICE_UPDATE = 211;//编辑商品价格
    const ACTION_GOODS_EXAMINE = 212;//审查商品
    const ACTION_GOODS_UPDATE_STATUS = 213;//变更商品状态
    const ACTION_GOODS_UPDATE_CATEGORY = 214;//修改类目
    const ACTION_GOODS_COMPLETE = 215;//提交到商品库
    const ACTION_GOODS_CLOSR_STOCK =232;//暂停销售
    const ACTION_GOODS_OPEN_STOCK =231;//恢复销售

    const ACTION_GOODS_UPDATE_VARIANT = 220;//编辑商品变体
    const ACTION_GOODS_UPDATE_VARIANT_PRICE = 221;//编辑商品变体价格

    public $action_maps = [
        self::ACTION_ORDER_CANCEL => '取消订单',
        self::ACTION_ORDER_CREATE => '创建订单',
        self::ACTION_ORDER_REFUND => '订单退款',
        self::ACTION_ORDER_UPDATE => '订单编辑',
        self::ACTION_ORDER_LOGISTICS => '编辑运单号',
        self::ACTION_ORDER_RESET_LOGISTICS => '打回待处理',
        self::ACTION_ORDER_SCAN_SHIP => '扫描发货',
        self::ACTION_ORDER_MOVE_ABNORMAL => '移入异常',
        self::ACTION_ORDER_ABNORMAL => '恢复异常',
        self::ACTION_ORDER_VIRTUAL_SHIP => '虚拟发货',
        self::ACTION_ORDER_SHIP => '发货',



        self::ACTION_GOODS_CREATE => '创建商品',
        self::ACTION_GOODS_GRAB_CREATE => '采集创建',
        self::ACTION_GOODS_COPY_CREATE => '复制商品',
        self::ACTION_GOODS_UPDATE => '编辑商品',
        self::ACTION_GOODS_PRICE_UPDATE => '编辑商品价格',
        self::ACTION_GOODS_EXAMINE => '审查商品',
        self::ACTION_GOODS_UPDATE_STATUS => '商品状态变更',
        self::ACTION_GOODS_UPDATE_CATEGORY => '修改类目',
        self::ACTION_GOODS_COMPLETE => '提交到商品库',
    	self::ACTION_GOODS_CLOSR_STOCK=>'暂停销售',
    	self::ACTION_GOODS_OPEN_STOCK=>'恢复销售',
        self::ACTION_GOODS_UPDATE_VARIANT => '编辑商品变体',
        self::ACTION_GOODS_UPDATE_VARIANT_PRICE => '编辑变体价格',
    ];

    /**
     * 获取用户操作描述
     * @param $op_user_role
     * @param string $op_user_name
     * @return mixed|string
     */
    public static function getOpUserDesc($op_user_role, $op_user_name = '')
    {
        if (empty($op_user_role)) {
            return '';
        }
        $op = [
            Base::ROLE_ADMIN => '后台',
            Base::ROLE_SYSTEM => '系统',
        ];
        $name = $op[$op_user_role];

        if ($op_user_role != Base::ROLE_SYSTEM && !empty($op_user_name)) {
            $name .= '(' . $op_user_name . ')';
        }
        return $name;
    }

    /**
     * 获取操作用户
     * @return array
     */
    public function getOpUserInfo()
    {
        $op_user_info = $this->op_user_info;
        if (empty($op_user_info)) {
            if (!empty(\Yii::$app->user)) {
                $op_user_info = [
                    'op_user_id' => strval(Yii::$app->user->getId()), 'op_user_name' => Yii::$app->user->identity->getName(), 'op_user_role' => Base::ROLE_ADMIN,
                ];
            } else {
                $op_user_info = [
                    'op_user_id' => '', 'op_user_name' => '', 'op_user_role' => Base::ROLE_SYSTEM,
                ];
            }
        }
        if ($op_user_info['op_user_role'] == Base::ROLE_SYSTEM) {
            $op_user_info['op_user_id'] = '';
            $op_user_info['op_user_name'] = '';
        }
        return $op_user_info;
    }

    /**
     * 设置操作用户
     * @param $op_user_info
     * @return $this
     */
    public function setOpUserInfo($op_user_info)
    {
        $this->op_user_info = $op_user_info;
        return $this;
    }

    /**
     * 设置类型
     * @param $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * 设置操作名称
     * @param $op_name
     * @return $this
     */
    public function setOpName($op_name)
    {
        $this->op_name = $op_name;
        return $this;
    }

    /**
     * 获取操作名称
     * @param $action
     * @return string
     */
    public function getOpName($action)
    {
        if (!empty($this->op_name)) {
            return $this->op_name;
        }
        return empty($this->action_maps[$action]) ? '' : $this->action_maps[$action];
    }

    /**
     * 增加订单日志
     * @param $order_id
     * @param $data
     * @param $action
     * @param $op_desc
     * @throws \yii\base\Exception
     */
    public function addOrderLog($order_id, $data, $action, $op_desc)
    {
        $object_data = [
            'object_type' => self::OBJECT_TYPE_ORDER, 'object_id' => 0, 'object_no' => $order_id,
        ];

        SystemOperlogService::addBackendLog($this->type, Order::getTableSchema()->name, 0, $data, $this->getOpName($action), $object_data, $this->getOpUserInfo(), $op_desc, $action);
    }

    /**
     * 增加商品日志
     * @param $goods_no
     * @param $data
     * @param $action
     * @param $op_desc
     * @throws \yii\base\Exception
     */
    public function addGoodsLog($goods_no, $data, $action, $op_desc)
    {
        $has_log = true;
        //更新商品
        if ($this->whetherUpdateGoodsEvent($action, $data)) {
            $goods_shops = GoodsShop::find()->where(['goods_no' => $goods_no])->all();
            $goods_shops = ArrayHelper::index($goods_shops, null, 'platform_type');
            $fy_platform = [
                Base::PLATFORM_OZON => 'ru',
                Base::PLATFORM_CDISCOUNT => 'fr',
            ];

            $con_update_key = [
                'goods_name' => 'goods_name',
                'goods_short_name' => 'goods_short_name',
                'goods_keywords' => 'goods_keywords',
                'goods_short_name_cn' => 'goods_keywords',
                'goods_desc' => 'goods_desc',
                'goods_content' => 'goods_content'
            ];
            $change_goods_field = [];
            foreach ($data as $k => $v) {
                if(!empty($con_update_key[$k])){
                    $change_goods_field[$con_update_key[$k]] = $con_update_key[$k];
                }
            }
            $change_goods_field = array_values($change_goods_field);
            foreach ($goods_shops as $platform_k => $platform_v) {
                //翻译 先这么写后续优化
                if(!empty($fy_platform[$platform_k]) && !empty($change_goods_field)) {
                    $fy_language = $fy_platform[$platform_k];
                    $goods_platform_class = FGoodsService::factory($platform_k);
                    $platform_goods = $goods_platform_class->model()->find()->where(['goods_no' => $goods_no])->one();
                    $platform_goods->status = 0;
                    $platform_goods->save();
                    (new GoodsTranslateService($fy_language))->readyToRetranslate($goods_no,$change_goods_field);
                }

                if (in_array($platform_k, [Base::PLATFORM_FRUUGO,Base::PLATFORM_ALLEGRO])) {
                    foreach ($platform_v as $goods_shop_v) {
                        //商品发生变更
                        GoodsEventService::addEvent($goods_shop_v, GoodsEvent::EVENT_TYPE_UPDATE_GOODS);
                        //图片发生变更
                        if (isset($data['goods_img']) && in_array($platform_k, [Base::PLATFORM_FRUUGO])) {
                            GoodsEventService::addEvent($goods_shop_v, GoodsEvent::EVENT_TYPE_UPLOAD_IMAGE);
                        }
                    }
                }
            }
        }

        //更新价格
        if(in_array($action,[self::ACTION_GOODS_UPDATE_VARIANT,self::ACTION_GOODS_UPDATE_VARIANT_PRICE])) {
            $has_log = false;
            if (!empty($data['update'])) {
                $has_log = true;
                $cgoods_nos = [];
                foreach ($data['update'] as $data_v) {
                    $cgoods_no = $data_v['cgoods_no'];
                    $data_v = $data_v['data'];
                    if (!empty($data_v['price']) || !empty($data_v['gbp_price']) || !empty($data_v['package_size']) || !empty($data_v['real_weight']) || !empty($data_v['weight'])) {
                        $cgoods_nos[] = $cgoods_no;
                    }

                    //重量发生变更
                    if (!empty($data_v['real_weight']) || !empty($data_v['weight'])) {
                        $goods_shops = GoodsShop::find()->where(['cgoods_no' => $cgoods_no])->all();
                        $goods_shops = ArrayHelper::index($goods_shops, null, 'platform_type');
                        foreach ($goods_shops as $platform_k => $platform_v) {
                            if (in_array($platform_k, [Base::PLATFORM_HEPSIGLOBAL])) {
                                foreach ($platform_v as $goods_shop_v) {
                                    GoodsEventService::addEvent($goods_shop_v, GoodsEvent::EVENT_TYPE_UPDATE_GOODS);
                                }
                            }
                        }
                    }
                }
                (new GoodsService())->updatePlatformCGoods($cgoods_nos,true);
            }
        }

        if($has_log) {
            $object_data = [
                'object_type' => self::OBJECT_TYPE_GOODS, 'object_id' => 0, 'object_no' => $goods_no,
            ];

            SystemOperlogService::addBackendLog($this->type, Goods::getTableSchema()->name, 0, $data, $this->getOpName($action), $object_data, $this->getOpUserInfo(), $op_desc, $action);
        }
    }

    /**
     * 是否更新商品实际
     * @param $action
     * @param $data
     * @return bool
     */
    public function whetherUpdateGoodsEvent($action, $data)
    {
        //更新数据
        if ($this->type != SystemOperlog::TYPE_UPDATE) {
            return false;
        }

        if (!in_array($action, [self::ACTION_GOODS_UPDATE, self::ACTION_GOODS_EXAMINE, self::ACTION_GOODS_UPDATE_CATEGORY, self::ACTION_GOODS_COMPLETE])) {
            return false;
        }

        //内容发生变化的key
        $con_update_key = ['goods_name', 'goods_keywords', 'goods_name_cn', 'goods_short_name_cn', 'goods_short_name', 'goods_img', 'goods_desc', 'goods_content'];
        $exist = false;
        foreach ($data as $k => $v) {
            if (in_array($k, $con_update_key)) {
                $exist = true;
            }
        }
        if ($exist) {
            return true;
        }
        return false;
    }

    /**
     * @param $table_name [表名]
     * @param $type [操作1修改,2增减,3删除]
     * @param $id [主键id，被操作的id]
     * @param $data [日志内容,无须json_encode]
     * @param $op_name [操作明细/修改、新增]
     * @param string $op_desc [订单编号等]
     * @param int $action [二级动作，和接口有关]
     * @param array $object_data object_type object_id object_no
     * @param array $op_user_info op_user_id op_user_name  op_user_role
     * @throws \yii\base\Exception
     */
    public static function addBackendLog($type, $table_name, $id, $data, $op_name, $object_data = [], $op_user_info = [], $op_desc = '', $action = 0)
    {
        //加日志
        $log = [
            'type' => $type,
            'table_name' => $table_name,
            'add_time' => time(),
            'op_data' => json_encode($data),
            'object_id' => $id,
            'op_action' => $action,
            'op_desc' => strval($op_desc),
            'op_name' => strval($op_name),
            'op_ip' => empty(Yii::$app->request->userIP) ? '' : Yii::$app->request->userIP,
        ];

        $log = array_merge($log, $op_user_info, $object_data);
        SystemOperlog::add($log);

    }

    /**
     * @param $model
     * @return mixed
     */
    public static function getModelChangeData($model)
    {
        $log_extra = [];
        //$model->getDirtyAttributes();
        foreach ($model->attributes() as $field) {
            if (!in_array($field, ['update_time', 'add_time'])) {
                if ($model->isAttributeChanged($field, false)) {
                    $log_extra[$field] = ['new' => $model->getAttribute($field), 'old' => $model->getOldAttribute($field)];
                }
            }
        }
        return $log_extra;
    }

    /**
     * 获取显示日志详情
     * @param $data
     * @return string
     */
    public static function getShowLogDesc($data)
    {
        $op_date = !empty($data['op_data']) ? json_decode($data['op_data'], true) : '';
        if (empty($op_date)) {
            return $data['op_desc'];
        }

        $text = '';
        //状态变更  {"status":{"new":20,"old":10}}
        if ($data['op_action'] == self::ACTION_GOODS_UPDATE_STATUS) {
            if (!empty($op_date['status']) && isset($op_date['status']['new'])) {
                $text = '状态由【' . (empty($op_date['status']['old']) ? '' : Goods::$status_map[$op_date['status']['old']]) . '】变更为【' . Goods::$status_map[$op_date['status']['new']] . '】';
            }
        }elseif(in_array($data['op_action'],[self::ACTION_GOODS_OPEN_STOCK,self::ACTION_GOODS_CLOSR_STOCK])){
            $text = '状态由【' .  Goods::$stock_map[$op_date['stock']['old']] . '】变更为【' . Goods::$stock_map[$op_date['stock']['new']] . '】';
        }elseif (in_array($data['op_action'],[self::ACTION_GOODS_UPDATE_VARIANT,self::ACTION_GOODS_UPDATE_VARIANT_PRICE]) && array_key_exists('update',$op_date)) {
            $a = $op_date['update'];
            $g = 0;
            $cgoods = [];
            $price = [];
            $weight = [];
            $package = [];
            $gdb = [];
            foreach ($a as $b) {
                if (empty($b['cgoods_no'])) {
                    continue;
                }
                $cgoods[$g] = $b['cgoods_no'];
                if (array_key_exists('price', $b['data'])) {
                    $price = $b['data']['price'];
                }
                if (array_key_exists('weight', $b['data'])) {
                    $weight = $b['data']['weight'];
                }
                if (array_key_exists('package_size', $b['data'])) {
                    $package = $b['data']['package_size'];
                }
                if (array_key_exists('gbp_grice', $b['data'])) {
                    $gdb = $b['data']['gbp_grice'];
                }
                $g += 1;
            }
            foreach ($cgoods as $c) {
                if (!(strstr($c, ','))) {
                    $text = $text . '【' . $c . '】';
                }
                if (!empty($price)) {
                    $text = $text . '价格由' . $price['old'] . '变为' . $price['new'] . ';';
                }
                if (!empty($weight)) {
                    $text = $text . '重量由' . $weight['old'] . '变为' . $weight['new'] . ';';
                }
                if (!empty($package)) {
                    $text = $text . '尺寸' . $package['old'] . '变为' . $package['new'] . ';';
                }
                if (!empty($gdb)) {
                    $text = $text . 'GBP价格由' . $gdb['old'] . '变为' . $gdb['new'] . ';';
                }
            }
        }
        if(!empty($data['op_desc'])) {
            $text .= ' 备注:'. $data['op_desc'];
        }
       return $text;
    }

}