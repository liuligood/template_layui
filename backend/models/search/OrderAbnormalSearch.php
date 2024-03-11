<?php
namespace backend\models\search;

use common\models\order\OrderAbnormal;
use common\models\OrderGoods;
use common\services\goods\GoodsService;
use Faker\Provider\Base;
use Yii;

class OrderAbnormalSearch extends OrderAbnormal
{

    public $start_date;
    public $end_date;
    public $start_arrival_time;
    public $end_arrival_time;
    public $start_delivery_time;
    public $end_delivery_time;
    public $platform_asin;
    public $abnormal_start_date;
    public $abnormal_end_date;
    public $relation_no;
    public $track_no;
    public $shop_id;
    public $source;
    public $my_abnormal;


    public function rules()
    {
        return [
            [['id','shop_id','start_date','end_date','abnormal_start_date','abnormal_end_date','start_arrival_time','end_arrival_time','start_delivery_time','end_delivery_time','abnormal_type','abnormal_status','source','follow_admin_id','my_abnormal'], 'integer'],
            [['order_id','relation_no','platform_asin','track_no'], 'string'],
        ];
    }

    public function search($params,$tag)
    {
        $this->load($params);

        $where = [];

        switch ($tag) {
            case 1://未发货
                $where['and'][] = ['!=', 'abnormal_status', self::ORDER_ABNORMAL_STATUS_CLOSE];
                $where['and'][] = ['not in', 'abnormal_type', [7, 9]];
                break;
            case 3://待重派
                $where['and'][] = ['!=', 'abnormal_status', self::ORDER_ABNORMAL_STATUS_CLOSE];
                $where['and'][] = ['=', 'abnormal_type', 7];
                break;
            case 4://物流商退件
                $where['and'][] = ['!=', 'abnormal_status', self::ORDER_ABNORMAL_STATUS_CLOSE];
                $where['and'][] = ['=', 'abnormal_type', 9];
                break;
            case 2://已发货
                $where['and'][] = ['abnormal_status' => self::ORDER_ABNORMAL_STATUS_CLOSE];
                break;
        }

        $where['o.source_method'] = GoodsService::SOURCE_METHOD_OWN;

        if (!empty($this->platform_asin)) {
            $order_ids = OrderGoods::find()->where(['platform_asin' => $this->platform_asin])->select('order_id')->column();
            $where['and'][] = ['o.order_id' => $order_ids];
        }

        if (!empty($this->track_no)) {
            $track_no = explode(PHP_EOL,$this->track_no);
            foreach ($track_no as &$v){
                $v = trim($v);
                if (strlen($v) < 2) {
                    continue;
                }
            }
            $track_no = array_filter($track_no);
            if(!empty($track_no)) {
                $track_no = count($track_no) == 1 ? current($track_no) : $track_no;
                $where['o.track_no'] = $track_no;
            }
        }

        if (!empty($this->order_id)) {
            $where['o.order_id'] = $this->order_id;
        }

        if (!empty($this->abnormal_type)) {
            $where['oa.abnormal_type'] = $this->abnormal_type;
        }
        if (isset($this->abnormal_status) && $this->abnormal_status != '') {
            $where['oa.abnormal_status'] = $this->abnormal_status;
        }

        if (!empty($this->source)) {
            $where['o.source'] = $this->source;
        }

        if (!empty($this->shop_id)) {
            $where['and'][] = ['=', 'shop_id', $this->shop_id];
        }

        if (!empty($this->relation_no)) {
            $relation_no = explode(PHP_EOL,$this->relation_no);
            foreach ($relation_no as &$v){
                $v = trim($v);
                if (strlen($v) < 2) {
                    continue;
                }
            }
            $relation_no = array_filter($relation_no);
            if(!empty($relation_no)) {
                if (count($relation_no) == 1) {
                    $where['and'][] = ['like', 'o.relation_no', current($relation_no)];
                } else {
                    $where['o.relation_no'] = $relation_no;
                }
            }
        }

        //时间
        if (!empty($this->start_date)) {
            $where['and'][] = ['>=', 'date', strtotime($this->start_date)];
        }
        if (!empty($this->end_date)) {
            $where['and'][] = ['<', 'date', strtotime($this->end_date) + 86400];
        }

        //时间
        if (!empty($this->abnormal_start_date)) {
            $where['and'][] = ['>=', 'abnormal_time', strtotime($this->abnormal_start_date)];
        }
        if (!empty($this->abnormal_end_date)) {
            $where['and'][] = ['<', 'abnormal_time', strtotime($this->abnormal_end_date) + 86400];
        }

        //发货时间
        if (!empty($this->start_delivery_time)) {
            $where['and'][] = ['>=', 'delivery_time', strtotime($this->start_delivery_time)];
        }
        if (!empty($this->end_delivery_time)) {
            $where['and'][] = ['<', 'delivery_time', strtotime($this->end_delivery_time) + 86400];
        }

        if (!empty($this->follow_admin_id)) {
            $where['and'][] = ['=', 'oa.follow_admin_id', $this->follow_admin_id];
        }

        //店铺数据
        if (!Yii::$app->authManager->checkAccess(Yii::$app->user->id, '所有店铺数据')) {
            $shop_id = Yii::$app->user->identity->shop_id;
            $shop_id = explode(',', $shop_id);
            $where['shop_id'] = $shop_id;
        }
        /*if (\Yii::$app->user->id == 88) {//李凯颂 暂时开放所有ozon店铺异常件
            $shop_id = Yii::$app->user->identity->shop_id;
            $shop_id = explode(',', $shop_id);
            $where['shop_id'] = $shop_id;
        } else {
            if (!Yii::$app->authManager->checkAccess(Yii::$app->user->id, '所有异常数据')) {
                $where['oa.follow_admin_id'] = \Yii::$app->user->id;
            }
        }*/

        if(isset($this->my_abnormal)) {
            $where['oa.follow_admin_id'] = \Yii::$app->user->id;
        }

        return $where;
    }

}