<?php

namespace backend\controllers;

use common\base\BaseController;
use common\components\statics\Base;
use common\models\Goods;
use common\models\goods\GoodsLanguage;
use common\models\goods_shop\GoodsShopOverseasWarehouse;
use common\models\GoodsShop;
use common\services\goods\GoodsService;
use Yii;
use common\services\ShopService;
use common\models\FindGoods;
use backend\models\search\FindGoodsSearch;
use yii\web\Controller;
use yii\web\Response;
use common\models\Category;
use yii\web\NotFoundHttpException;
use yii\helpers\ArrayHelper;
use yii\filters\VerbFilter;

/**
 * FindGoodsController implements the CRUD actions for FindGoods model.
 */
class FindGoodsController extends BaseController
{
    protected $render_view = '/goods/find-goods/';
    /**
     * @routeName 精品商品
     * @routeDescription 精品商品
     */
    public function actionIndex()
    {
        $req = Yii::$app->request;
        $tag = $req->get('tag',0);
        return $this->_index($tag, GoodsService::SOURCE_METHOD_OWN);
    }

    /**
     * @routeName 精品ozon商品
     * @routeDescription 精品ozon商品
     */
    public function actionOzonIndex()
    {
        $req = Yii::$app->request;
        $tag = $req->get('tag',0);
        $platform_type = $req->get('platform_type', Base::PLATFORM_OZON);
        return $this->_index($tag, GoodsService::SOURCE_METHOD_OWN, 'platform_index', $platform_type);
    }

    /**
     * @routeName 精品allegro商品
     * @routeDescription 精品allegro商品
     */
    public function actionAllegroIndex()
    {
        $req = Yii::$app->request;
        $tag = $req->get('tag',0);
        $platform_type = $req->get('platform_type', Base::PLATFORM_ALLEGRO);
        return $this->_index($tag, GoodsService::SOURCE_METHOD_OWN, 'platform_index', $platform_type);
    }


    /**
     * 首页列表
     * @param $tag
     * @param $source_method
     * @param string $render_view
     * @param $platform_type
     * @return string
     */
    protected function _index($tag, $source_method, $render_view = 'index', $platform_type = null)
    {
        $req = Yii::$app->request;
        $source_method = $req->get('source_method', $source_method);
        if ($tag == 3) {
            $searchModel = new FindGoodsSearch();
            $platform_type = empty($platform_type) ? 0 : $platform_type;
            $where = $searchModel->search([],$tag ,$platform_type);
            $category_cuts = Goods::find()->where($where)->select('category_id,count(*) cut')->groupBy('category_id')->asArray()->all();
            $category_id = ArrayHelper::getColumn($category_cuts, 'category_id');
            $category_cuts = ArrayHelper::map($category_cuts, 'category_id', 'cut');
            $category_lists = Category::find()->select('name,id')->where(['source_method' => $source_method, 'id' => $category_id])->asArray()->all();
            $category_arr = [];
            foreach ($category_lists as $v) {
                $category_arr[$v['id']] = $v['name'] . '（' . $category_cuts[$v['id']] . '）';
            }
        } else {
            $category_arr = [];
        }
        $url_platform_name = '';
        if (!empty($platform_type)) {
            $platform_name = \common\components\statics\Base::$platform_maps[$platform_type];
            $url_platform_name = strtolower($platform_name);
        }
        $shop_arr = ShopService::getShopDropdown($platform_type);
        return $this->render($this->render_view . $render_view, [
            'tag' => $tag,
            'goods_stamp_tag' => $req->get('goods_stamp_tag'),
            'source_method' => $source_method,
            'category_arr' => $category_arr,
            'shop_arr' => $shop_arr,
            'url_platform_name' => $url_platform_name,
            'platform_type' => $platform_type
        ]);
    }

    public function query($type = 'select')
    {
        $query = FindGoods::find()
            ->alias('og')->select('g.*,og.id as ogid,og.add_time');
        $query->leftJoin(Goods::tableName() . ' g', 'og.goods_no = g.goods_no');
        return $query;
    }
    public function model()
    {
        return new Goods();
    }
    /**
     * @routeName 商品列表
     * @routeDescription 商品列表
     */
    public function actionList()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $source_method = $req->get('source_method');
        $goods_stamp_tag = $req->get('goods_stamp_tag');
        $platform_type = $req->get('platform_type', 0);
        $tag = $req->get('tag');
        $searchModel = new FindGoodsSearch();
        $searchModel->goods_stamp_tag = $goods_stamp_tag;
        $query_params = Yii::$app->request->post();
        $lists = [];
        $total_count = 0;
        $where = $searchModel->search($query_params, $tag, $platform_type);
        $data = $this->lists($where,'og.add_time asc');
        $total_count = $data['pages']->totalCount;
        $goods_no = ArrayHelper::getColumn($data['list'],'goods_no');
        $search_platform = '';
        if ($platform_type == Base::PLATFORM_OZON) {
            $search_platform = Base::PLATFORM_ALLEGRO;
        }
        $goods_shop = GoodsShop::find()->where(['goods_no' => $goods_no,'platform_type' => $search_platform])->select('cgoods_no')->groupBy('cgoods_no')->asArray()->all();
        $cgoods_nos = ArrayHelper::getColumn($goods_shop,'cgoods_no');
        $overseas_arr = GoodsShopOverseasWarehouse::find()->alias('gsow')
            ->select('gs.platform_goods_id,,gs.goods_no')
            ->leftJoin(GoodsShop::tableName().' gs','gs.id = gsow.goods_shop_id')
            ->where(['gsow.cgoods_no' => $cgoods_nos,'gsow.platform_type' => $search_platform])->andWhere(['!=','gs.platform_goods_id',''])
            ->indexBy('goods_no')->asArray()->all();
        foreach ($data['list'] as $v) {
            $info = $v;
            $image = json_decode($v['goods_img'], true);
            $info['image'] = empty($image) || !is_array($image) ? '' : current($image)['img'];
            $info['add_time'] = date('Y-m-d H:i', $v['add_time']);
            $info['status_desc'] = empty(Goods::$status_map[$v['status']]) ? '' : Goods::$status_map[$v['status']];
            $info['category_name'] = Category::getCategoryName($v['category_id']) . '(' . $v['category_id'] . ')';
            $info['url'] = '';
            if (in_array($platform_type,[Base::PLATFORM_OZON,Base::PLATFORM_ALLEGRO])) {
                $language = $platform_type == Base::PLATFORM_OZON ? 'ru' : 'pl';
                $info['language_id'] = GoodsLanguage::find()->where(['goods_no' => $info['goods_no'],'language' => $language])->select('id as language_id')->scalar();
                $overseas = empty($overseas_arr[$v['goods_no']]) ? [] : $overseas_arr[$v['goods_no']];
                if (!empty($overseas)) {
                    $info['url'] = 'https://allegro.pl/oferta/'.$overseas['platform_goods_id'];
                }
            }
            $lists[] = $info;
        }
        return $this->FormatLayerTable(
            self::REQUEST_LAY_SUCCESS, '获取成功',
            $lists, $total_count
        );
    }

    /**
     * @routeName 删除商品
     * @routeDescription 删除指定商品
     * @return array
     * @throws
     */
    public function actionDelete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = (int)$req->get('id');
        $result = FindGoods::deleteAll(['id'=>$id]);
        if ($result) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "移除成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_SUCCESS, "移除失败", []);
        }
    }
    /**
     * Finds the FindGoods model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return FindGoods the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = FindGoods::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
