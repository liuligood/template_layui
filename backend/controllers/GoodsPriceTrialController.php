<?php

namespace backend\controllers;

use common\base\BaseController;
use common\components\statics\Base;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\services\goods_price_trial\GoodsPriceTrialService;
use common\services\sys\ExchangeRateService;
use Yii;
use common\models\GoodsPriceTrial;
use backend\models\search\GoodsPriceTrialSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;


class GoodsPriceTrialController extends BaseController
{
    public function model(){
        return new GoodsPriceTrial();
    }

    public function query($type = 'select')
    {
        $query = GoodsPriceTrial::find()
            ->alias('gpt')->select('gpt.id,gpt.price,gpt.cost_price,gpt.start_logistics_cost,gpt.platform_type,gpt.cgoods_no,gc.sku_no,gc.colour,gc.size,gc.goods_img,g.goods_img as ggoods_img,g.goods_no,g.goods_name_cn,g.goods_name,g.category_id,gc.weight,gc.package_size,gc.real_weight,g.size');
        $query->leftJoin(GoodsChild::tableName() . ' gc', 'gc.cgoods_no= gpt.cgoods_no');
        $query->leftJoin(Goods::tableName() . ' g', 'gc.goods_no = g.goods_no');
        return $query;
    }

    /**
     * @routeName 价格试算主页
     * @routeDescription 价格试算主页
     */
    public function actionIndex()
    {
        $req = Yii::$app->request;
        $platform_type = $req->get('platform_type',Base::PLATFORM_OZON);
        $title = GoodsPriceTrialService::getTitle($platform_type);
        return $this->render('index',['platform_type' => $platform_type,'title' => $title]);
    }

    /**
     * @routeName 价格试算列表
     * @routeDescription 价格试算列表
     */
    public function actionList()
    {
        $req = Yii::$app->request;
        Yii::$app->response->format = Response::FORMAT_JSON;
        $platform_type = $req->get('platform_type',Base::PLATFORM_OZON);
        $searchModel = new GoodsPriceTrialSearch();
        $where = $searchModel->search(Yii::$app->request->queryParams, $platform_type);
        $data = $this->lists($where);
        foreach ($data['list'] as &$info){
            $info = (new GoodsPriceTrialService())->dealListInfo($platform_type, $info);
        }
        return $this->FormatLayerTable(self::REQUEST_LAY_SUCCESS,"获取成功",$data['list'],$data['pages']->totalCount);
    }

    /**
     * @routeName 价格更新
     * @routeDescription 价格更新
     */
    public function actionUpdate()
    {
        $req = Yii::$app->request;
        Yii::$app->response->format = Response::FORMAT_JSON;
        $post = $req->post();
        $post['price'] = trim($post['price']);
        if (!is_numeric($post['price'])) {
            return $this->FormatArray(self::REQUEST_FAIL, '价格必须为数字', []);
        }
        $model = $this->findModel($post['id']);
        if (!empty($model)) {
            if ($post['field'] == 'price') {
                $model['price'] = $post['price'];
            }
            if ($post['field'] == 'cost_price') {
                $model['cost_price'] = $post['price'];
            }
            if ($post['field'] == 'start_logistics_cost') {
                $model['start_logistics_cost'] = $post['price'];
            }
            if ($model->save()) {
                $info = $this->query()->where(['gpt.id' => $post['id']])->asArray()->one();
                $info = (new GoodsPriceTrialService())->dealListInfo($post['platform_type'], $info);
                return $this->FormatArray(self::REQUEST_SUCCESS,'修改成功',[$info]);
            } else {
                return $this->FormatArray(self::REQUEST_SUCCESS,'修改失败',[]);
            }
        }
        return $this->FormatArray(self::REQUEST_FAIL,'该数据不存在，请刷新',[]);
    }

    /**
     * @routeName 添加商品价格试算
     * @routeDescription 添加商品价格试算
     * @throws
     * @return string |Response |array
     */
    public function actionAddGoods()
    {
        $req = Yii::$app->request;
        $platform_type = $req->get('platform_type');
        $cgoods_nos = $req->post('cgoods_no');
        Yii::$app->response->format = Response::FORMAT_JSON;

        $error = [];
        $exchange_rate = 1;
        if ($platform_type == Base::PLATFORM_OZON) {
            $exchange_rate = ExchangeRateService::getRealConversion('CNY','RUB');
        }
        foreach ($cgoods_nos as $cgoods_no) {
            $goods_price_trial = GoodsPriceTrial::find()->where(['cgoods_no' => $cgoods_no])->one();
            if (empty($goods_price_trial)) {
                $cost_price = GoodsChild::find()->where(['cgoods_no' => $cgoods_no])->select('price')->scalar();
                $price = round($cost_price * $exchange_rate,2);
                $goods_price_trial = new GoodsPriceTrial();
                $goods_price_trial->cgoods_no = $cgoods_no;
                $goods_price_trial->price = $price;
                $goods_price_trial->cost_price = $cost_price;
                $goods_price_trial->start_logistics_cost = 30;
                $goods_price_trial->platform_type = $platform_type;
                if (!$goods_price_trial->save()) {
                    $error[] = $cgoods_no;
                }
            }
        }
        $error = !empty($error) ? (',但同步商品有失败:' . $error) : '';
        return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功" . $error, []);
    }

    /**
     * @routeName 价格试算删除
     * @routeDescription 价格试算删除
     */
    public function actionDelete()
    {
        $req = Yii::$app->request;
        Yii::$app->response->format = Response::FORMAT_JSON;
        $id = (int)$req->get('id');
        $model = $this->findModel($id);
        if ($model->delete()) {
            return $this->FormatArray(self::REQUEST_SUCCESS,'删除成功',[]);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL,'删除失败',[]);
        }
    }


    /**
     * Finds the GoodsPriceTrialService model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return GoodsPriceTrial the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = GoodsPriceTrial::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
