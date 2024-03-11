<?php
namespace backend\models\search;

use common\components\statics\Base;
use common\models\grab\GrabGoods;
use common\services\FGrabService;
use common\services\sys\AccessService;
use Yii;
use yii\data\ActiveDataProvider;

class GrabGoodsSearch extends GrabGoods
{

    public $start_use_time;
    public $end_use_time;
    public $start_check_time;
    public $end_check_time;
    public $use_status = GrabGoods::USE_STATUS_VALID;

    public function rules()
    {
        return [
            [['id','gid','use_status','source','admin_id'], 'integer'],
            [['category','asin','title'], 'string'],
            [['start_use_time', 'end_use_time' ,'start_check_time' ,'end_check_time'], 'date', 'format' => 'php:Y-m-d'],
        ];
    }

    public function search($params)
    {
        $where = [];
        if(!empty($params['gid'])){
            $where['gid'] = (int)$params['gid'];
            $use_status = [GrabGoods::USE_STATUS_NONE,GrabGoods::USE_STATUS_INVALID,GrabGoods::USE_STATUS_INVALID];
        }else{
            $use_status = [GrabGoods::USE_STATUS_VALID,GrabGoods::USE_STATUS_INVALID];
        }
        $this->load($params);

        if (!empty($this->category)) {
            $where['category'] = $this->category;
        }

        if (!empty($this->source)) {
            $where['source'] = $this->source;
        }

        if(!empty($this->asin)){
            $where['asin'] = $this->asin;
        }

        if(!empty($this->title)){
            $where['and'][] = ['like','title',$this->title];
        }

        if (!is_null($this->use_status) && $this->use_status !== '' && in_array($this->use_status,$use_status)) {
            $where['use_status'] = (int)$this->use_status;
        }else{
            $where['use_status'] = $use_status;
        }

        //时间
        if (!empty($this->start_use_time)) {
            $where['and'][] = ['>=', 'use_time', strtotime($this->start_use_time)];
        }
        if (!empty($this->end_use_time)) {
            $where['and'][] = ['<', 'use_time', strtotime($this->end_use_time) + 86400];
        }

        //采集时间
        if (!empty($this->start_check_time)) {
            $where['and'][] = ['>=', 'check_stock_time', strtotime($this->start_check_time)];
        }
        if (!empty($this->end_check_time)) {
            //$where['and'][] = ['<', 'check_stock_time', strtotime($this->end_check_time) + 86400];
            $where['and'][] = ['<=', 'check_stock_time', strtotime($this->end_check_time)];
        }

        $where['status'] = [GrabGoods::STATUS_SUCCESS];
        $where['self_logistics'] = GrabGoods::SELF_LOGISTICS_YES;
        $where['goods_status'] = GrabGoods::GOODS_STATUS_NORMAL;
        if(!AccessService::hasAllGoods()) {
            $where['admin_id'] = Yii::$app->user->identity->id;
        }

        return $where;
    }

    protected function dealExportContent($goods_content)
    {
        $str_arr = explode(PHP_EOL, $goods_content);
        $result = '';
        foreach ($str_arr as $v) {
            $v = trim($v);
            if (empty($v)) {
                continue;
            }

            $result .= '<p>' . $v . '</p>';
        }
        return $result;
    }

    /**
     * 导出
     * @param $list
     * @return array
     */
    public function export($list)
    {
        $data = [];
        foreach ($list as $k => $v) {
            $data[$k]['category'] = $v['category'];
            $data[$k]['asin'] = $v['asin'];
            $data[$k]['title'] = $v['title'];
            $data[$k]['price'] = $v['price'];
            $data[$k]['evaluate'] = $v['evaluate'];
            $data[$k]['score'] = $v['score'];
            $data[$k]['desc1'] = $v['desc1'];
            $data[$k]['desc3'] = $this->dealExportContent($v['desc1']);
            $data[$k]['desc2'] = $v['desc2'];
            $data[$k]['desc'] = $v['desc'];
            $data[$k]['images1'] = $v['images1'];
            $data[$k]['images2'] = $v['images2'];
            $data[$k]['images3'] = $v['images3'];
            $data[$k]['images4'] = $v['images4'];
            $data[$k]['images5'] = $v['images5'];
            $data[$k]['images6'] = $v['images6'];
            $data[$k]['images7'] = $v['images7'];
            $data[$k]['url'] = $v['url'];
            $data[$k]['brand'] = $v['brand'];
            //$data[$k]['desc2'] = $v['desc2'];


            //if($v['source'] != Base::PLATFORM_AMAZON_DE){
                $data[$k]['weight'] = $v['weight'];
                $data[$k]['dimension'] = $v['dimension'];
                $data[$k]['colour'] = $v['colour'];
            //}
        }

        $column = [
            'category' => '类目',
            'asin' => 'asin',
            'title' => '标题',
            'price' => '金额',
            'evaluate' => '评价数',
            'score' => '评分',
            'desc1' => '详情',
            'desc3' => '详情html',
            //'desc1' => '五要素',
            //'desc2' => '详情',
            //'desc' => '五要素+详情',

        ];
        $column['brand'] = '品牌';
        //$column['desc2'] = '内容';

        //if($v['source'] != Base::PLATFORM_AMAZON_DE){
            $column['weight'] = '重量';
            $column['dimension'] = '尺寸';
            $column['colour'] = '颜色';
        //}

        $column = array_merge($column,[
            'images1' => '图片1',
            'images2' => '图片2',
            'images3' => '图片3',
            'images4' => '图片4',
            'images5' => '图片5',
            'images6' => '图片6',
            'images7' => '图片7',
            'url' => '链接'
        ]);

        return [
            'key' => array_keys($column),
            'header' => array_values($column),
            'list' => $data,
            'fileName' => '采集导出' . date('ymdhis')
        ];
    }

}