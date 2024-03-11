<?php

namespace backend\controllers;

use common\base\BaseController;
use common\components\statics\Base;
use common\models\FinancialPlatformSalesPeriod;
use common\models\Goods;
use common\models\goods\BaseGoods;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsOzon;
use common\models\GoodsShop;
use common\models\PromoteCampaign;
use common\models\Shop;
use common\services\financial\PlatformSalesPeriodService;
use common\services\goods\GoodsService;
use common\services\goods\GoodsShopService;
use common\services\ImportResultService;
use common\services\ShopService;
use moonland\phpexcel\Excel;
use Yii;
use common\models\PromoteCampaignDetails;
use backend\models\search\PromoteCampaignDetailsSearch;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;
use yii\web\UploadedFile;

/**
 * PromoteCampaignDetailsController implements the CRUD actions for PromoteCampaignDetails model.
 */
class PromoteCampaignDetailsController extends BaseController
{
    public function model(){
        return new PromoteCampaignDetails();
    }
    public function query($type = 'select'){
        return PromoteCampaignDetails::find()->select('sum(impressions) as impressions,
        sum(hits) as hits,
        sum(promotes) as promotes,
        sum(order_volume) as order_volume,
        sum(order_sales) as order_sales,
        sum(model_orders) as model_orders,
        sum(model_sales) as model_sales,platform_type,shop_id,platform_goods_opc,cgoods_no,id,promote_id')->groupBy('platform_goods_opc');
    }

    /**
     * @routeName 推广活动明细表主页
     * @routeDescription 推广活动明细表主页
     */
    public function actionIndex()
    {
        $req = Yii::$app->request;
        $types = $req->get('platform_type');
        $id = $req->get('id');
        $stime = $req->get('stime');
        $etime = $req->get('etime');
        $is_all = $req->get('is_all');
        $prompt = '每1000展现量(点击)平均价格';
        if (!empty($id)) {
            $type = PromoteCampaign::find()->where(['id' => $id])->select('type')->scalar();
            if ($type == PromoteCampaign::TYPE_SHOW) {
                $prompt = '每1000'.PromoteCampaign::$type_maps[$type];
            }
            if ($type == PromoteCampaign::TYPE_CLICK) {
                $prompt = '每次'.PromoteCampaign::$type_maps[$type];
            }
            $prompt = $prompt.'平均价格';
        }
        return $this->render('index',[
            'types' => $types,
            'id' => $id,
            'stime' => $stime,
            'etime' => $etime,
            'prompt' => $prompt,
            'is_all' => $is_all
        ]);
    }

    /**
     * @routeName 推广活动明细表列表
     * @routeDescription 推广活动明细表列表
     */
    public function actionList()
    {
        $req = Yii::$app->request;
        Yii::$app->response->format = Response::FORMAT_JSON;
        $id = $req->get('id');
        $stime = $req->get('stime');
        $etime = $req->get('etime');
        $field = $req->get('field');
        $order = $req->get('order');
        $searchModel = new PromoteCampaignDetailsSearch();
        if(!empty($stime)){$searchModel['start_date'] = $stime;}
        if(!empty($etime)){$searchModel['end_date'] = $etime;}
        $where = $searchModel->search(Yii::$app->request->queryParams);
        if (!empty($id)) {
            $where['promote_id'] = $id;
        }
        $data = $this->lists($where);
        $shop_list = ShopService::getShopMap();
        $promote_ids = ArrayHelper::getColumn($data['list'],'promote_id');
        $promote_campaign_arr = PromoteCampaign::find()->where(['id' => $promote_ids])->indexBy('id')->asArray()->all();
        foreach ($data['list'] as &$list) {
            $shops = GoodsShop::find()->where(['platform_goods_opc'=>$list['platform_goods_opc']])->asArray()->one();
            if(!empty($shops)) {$list['sid'] = $shops['id'];
            $ozon = GoodsChild::find()->where(['cgoods_no'=>$shops['cgoods_no']])->asArray()->one();
            if(!empty($ozon)){$list['sku_no']=$ozon['sku_no'];
            $goodo = Goods::find()->where(['goods_no'=>$shops['goods_no']])->asArray()->one();
            if(!empty($goodo)){
                if(empty($ozon['goods_img'])) {
                    $image = \Qiniu\json_decode($goodo['goods_img'], true);
                    $image = empty($image) || !is_array($image) ? '' : current($image)['img'];
                } else {
                    $image = $ozon['goods_img'];
                }
                $short_image = $image.'?imageView2/2/h/100';
                $image = GoodsShopService::getLogoImg($image,$list['shop_id']);
                $list['short_image'] = $short_image;
                $list['goods_img'] = $image;}
            }
            }
            $promote_campaign = empty($promote_campaign_arr[$list['promote_id']]) ? [] : $promote_campaign_arr[$list['promote_id']];
            $type = PromoteCampaign::TYPE_SHOW;
            if (!empty($promote_campaign)) {
                $type = $promote_campaign['type'];
            }
            $list['ctr'] = '-';
            if ($list['hits'] != 0 && $list['impressions'] != 0) {
                $list['ctr'] = $list['hits'] / $list['impressions'] <= 0 ? '-' : round((int)$list['hits'] / (int)$list['impressions'] * 100,2);
            }
            $list['average'] = '-';
            if ($type == PromoteCampaign::TYPE_SHOW) {
                if ($list['promotes'] != 0 && $list['impressions'] != 0) {
                    $list['average'] = $list['promotes'] / $list['impressions'] <= 0 ? '-' : round((int)$list['promotes'] / (int)$list['impressions'] * 1000,2);
                }
                $list['type_name'] = '展示';
            } else{
                if ($list['hits'] != 0 && $list['impressions'] != 0) {
                    $list['average'] = $list['hits'] / $list['impressions'] <= 0 ? '-' : round((int)$list['hits'] / (int)$list['impressions'], 2);
                }
                $list['type_name'] = '点击';
            }
            $list['promotes'] = round($list['promotes'],2);
            $list['acos'] = '-';
            if ($list['promotes'] != 0 && $list['order_sales'] != 0 && $list['model_sales']) {
                $list['acos'] = (float)$list['promotes']/((float)$list['order_sales']+(float)$list['model_sales']) <= 0 ? '-' : round((float)$list['promotes']/((float)$list['order_sales']+(float)$list['model_sales']), 2);
            }
            $list['impressions'] = $list['impressions'] <= 0 ? '-' : $list['impressions'];
            $list['hits'] = $list['hits'] <= 0 ? '-' : $list['hits'];
            $list['order_volume'] = $list['order_volume'] <= 0 ? '-' : $list['order_volume'];
            $list['order_sales'] = $list['order_sales'] <= 0 ? '-' : $list['order_sales'];
            $list['model_orders'] = $list['model_orders'] <= 0 ? '-' : $list['model_orders'];
            $list['model_sales'] = $list['model_sales'] <= 0 ? '-' : $list['model_sales'];
            $list['platform_type_name'] = empty(Base::$platform_maps[$list['platform_type']]) ? '' : Base::$platform_maps[$list['platform_type']];
            $list['shop'] = empty($shop_list[$list['shop_id']]) ? '' : $shop_list[$list['shop_id']];
        }
        if (!empty($field) && !empty($order)) {
            $last = [];
            $sort_arr = [];
            foreach ($data['list'] as $info) {
                if ($info[$field] == '-') {
                    $last[] = $info;
                    continue;
                }
                $sort_arr[] = $info;
            }
            $sort_arr = $this->bubbleSort($sort_arr,$field);
            if ($order == 'desc') {
                $sort_arr = array_reverse($sort_arr);
            }
            $data['list'] = $sort_arr + $last;
        }
        return $this->FormatLayerTable(self::REQUEST_LAY_SUCCESS,"获取成功",$data['list'],$data['pages']->totalCount);
    }


    /** 冒泡排序
     * @param $arr
     * @param $field
     */
    function bubbleSort($arr,$field) {
        $n = count($arr);
        for ($i = 0; $i < $n - 1; $i++) {
            for ($j = 0; $j < $n - $i - 1; $j++) {
                if ($arr[$j][$field] > $arr[$j + 1][$field]) {
                    // 交换元素
                    $temp = $arr[$j];
                    $arr[$j] = $arr[$j + 1];
                    $arr[$j + 1] = $temp;
                }
            }
        }
        return $arr;
    }

    /**
     * @routeName 导入区分
     * @routeDescription 导入区分
     */
    public function actionImport()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $file = UploadedFile::getInstanceByName('file');
        if (!in_array($file->extension, ['xlsx', 'xls', 'csv'])) {
            return $this->FormatArray(self::REQUEST_FAIL, "只允许使用以下文件扩展名的文件：xlsx, csv,xls。", []);
        }
//        $req = Yii::$app->request;
//        $promote_id = $req->get('id');
//        $promote_name = $req->get('promote_id');
//        $shop = $req->get('shop');
//        $type_id = Shop::find()->where(['id' => $shop])->select('platform_type')->asArray()->one();
        $data = file($file->tempName);
        $alltime = $data[0];
//        $it = PromoteCampaign::findOne($promote_id);
        $alltime = str_replace("，","",$alltime);
        $alltime = str_replace(" ","",$alltime);
        $alltime = str_replace(";","",$alltime);
        $alltime = str_replace("№","",$alltime);
//        $its = $it->promote_id;
        $code =(int)(substr("$alltime",-35,7));
        $time = substr("$alltime",-22,10);
        $date =substr("$alltime",-11,10);
        $date = $this->dealdata($date);
        $time = $this->dealdata($time);
        if ($date!=$time){
            return $this->FormatArray(self::REQUEST_FAIL, "不是同一天的数据", []);
        }
        $ite = PromoteCampaign::find()->where(['promote_id'=>$code])->asArray()->all();
        if(count($ite)==0){
            return $this->FormatArray(self::REQUEST_FAIL, "改活动编码还未创建", []);
        }
        $promote_id = $ite[0]['id'];
        $shop = $ite[0]['shop_id'];
        $promote_name = $code;
        $type_id = $ite[0]['platform_type'];
        $allone = PromoteCampaignDetails::find()->where(['promote_time'=>$date])->asArray()->all();
        if (count($allone)>0){
            $this->actionDelect($date,$promote_id);
        }
        $count = count($data);
        $success = 0;
        $errors = [];
        for ($i = 2; $i <= $count-2; $i++) {
            try {
                $all = str_getcsv($data[$i],';');
                if (empty($all[0])){
                    $errors[$i] = $date.'商品订单号为空';
                    continue;
                }
                foreach ($all as &$one){
                    $one =  str_replace(",",".",$one);
                }
                $item = GoodsShop::find()->where(['platform_goods_opc'=>$all[0]])->one();
                $order = new PromoteCampaignDetails();
                $order->promote_time = $date;
                $order->platform_type = (int)$type_id;
                $order->shop_id = (int)$shop;
                $order->promote_name = (int)$promote_name;
                $order->promote_id =(int) $promote_id;
                $order->platform_goods_opc = $all[0];
                $order->cgoods_no = empty($item) ? '' : $item->cgoods_no;
                $order->promotes = round((float)$all[7],2);
                $order->impressions =  (int)$all[3];
                $order->hits =  (int)$all[4];
                $order->order_volume = (int)$all[8];
                $order->order_sales =round((float)$all[9],2);
                $order->model_orders = (int)$all[10];
                $order->model_sales = round((float)$all[11],2);
                $order->save();
            }catch (\Exception $e) {
                $errors[$i] = $e->getMessage();
                continue;
            }

            $success++;
        }

        if(!empty($errors)) {
            $lists = [];
            foreach ($errors as $i => $error) {
                $row = $data[$i];
                $info = [];
                $info['index'] = $i;
                $info['reason'] = $error;
                $lists[] = $info;
            }
            $key = (new ImportResultService())->gen('导入明细', $lists);
            return $this->FormatArray(self::REQUEST_FAIL, "导入失败问题", [
                'key' => $key
            ]);
        }
        return $this->FormatArray(self::REQUEST_SUCCESS, "导入成功", []);
    }
    /**
     * @routeName 订单统计
     * @routeDescription 订单统计
     */
    public function actionOrderCount()
    {
        $req = Yii::$app->request;
        $start_date = $req->get('start_date');
        $end_date = $req->get('end_date' );
        $shop_id= $req->get('shop_id' );
        $time = time();
        if(empty($start_date)&&empty($end_date)){
            $start_date = date('Y-m-d',$time-86400);
            $end_date = date('Y-m-d',$time-86400);
        }
        $platform_type = $req->get('platform_type');
        if(empty($platform_type)){$platform_type = Base::PLATFORM_OZON;}
        $where = [];
        $where['platform_type'] = $platform_type;
        if(!empty($shop_id)){
            $where['shop_id'] = $shop_id;
        }
        $order_count = PromoteCampaignDetails::find()->select('id,shop_id,platform_type,sum(impressions) as impressions,sum(hits) as hits,sum(promotes) as promotes,sum(order_volume) as order_volume,sum(order_sales) as order_sales,sum(model_orders) as model_orders,sum(model_sales) as model_sales,')
            ->where($where)
            ->andWhere(['>=', 'promote_time', strtotime($start_date)])
            ->andWhere(['<', 'promote_time', strtotime($end_date) + 86400])
            ->groupBy('shop_id')->asArray()->all();
        $all_impressions = 0;
        $all_hits = 0;
        $all_promotes = 0;
        $all_order_volume = 0;
        $all_order_sales =0;
        $all_model_orders = 0;
        $all_model_sales =0;
        foreach ($order_count as $v) {
            $all_impressions += $v['impressions'];
            $all_hits += $v['hits'];
            $all_promotes += $v['promotes'];
            $all_order_volume += $v['order_volume'];
            $all_order_sales += $v['order_sales'];
            $all_model_orders += $v['model_orders'];
            $all_model_sales += $v['model_sales'];
        }
        $shop = Shop::find()->select('id,name')->asArray()->all();
        $shop = ArrayHelper::map($shop,'id','name');
        return $this->render('order_count', [
            'searchModel' => [
                'start_date' => $start_date,
                'end_date' => $end_date,
                'shop_id' => $shop_id
            ],
            'platform_type' => $platform_type,
            'all_impressions' => $all_impressions,
            'all_hits' => $all_hits,
            'all_promotes' => $all_promotes,
            'all_order_volume'=>$all_order_volume,
            'all_order_sales' => $all_order_sales,
            'all_model_orders' => $all_model_orders,
            'all_model_sales' => $all_model_sales,
            'order_count' => $order_count,
            'shop_map' => $shop,
        ]);
    }
    /**
     * @routeName 处理ozon的时间
     * @routeDescription 处理ozon的时间
     */

    public function dealdata($time){
        $item = explode('.',$time);
        $item = array_reverse($item);
        $item = implode("-",$item);
        return strtotime($item);
    }
    /**
     * @routeName 清空明细表单
     * @routeDescription 清空明细表单
     */
    public function actionDelect($time =null,$id=null)
    {
        $req = Yii::$app->request;
        if (empty($id)){
            $id = $req->post('id');
        }
        $where = [];
        if (!empty($time)){
            $where['promote_time'] = $time;
        }
        $where['promote_id'] = $id;
        $items = PromoteCampaignDetails::find()->where($where)->asArray()->all();
        foreach ($items as $item){
            $one =  PromoteCampaignDetails::findOne($item['id']);
            $one->delete();
        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        return $this->FormatArray(self::REQUEST_SUCCESS, "删除成功", []);
    }

    /**
     * Finds the PromoteCampaignDetails model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return PromoteCampaignDetails the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = PromoteCampaignDetails::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
