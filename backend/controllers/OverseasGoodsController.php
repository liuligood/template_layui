<?php

namespace backend\controllers;

use backend\models\search\OverseasGoodsSearch;
use common\models\Category;
use common\models\Goods;
use common\services\goods\GoodsService;
use common\services\ShopService;
use Yii;
use common\base\BaseController;
use yii\helpers\ArrayHelper;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use common\models\goods\OverseasGoods;

class OverseasGoodsController extends BaseController
{
    protected $render_view = '/goods/overseas/';

    /**
     * @routeName 海外仓商品
     * @routeDescription 海外仓商品
     */
    public function actionIndex()
    {
        $req = Yii::$app->request;
        $tag = $req->get('tag',0);
        return $this->_index($tag, GoodsService::SOURCE_METHOD_OWN);
    }

    /**
     * 首页列表
     * @param $tag
     * @param $source_method
     * @param string $render_view
     * @return string
     */
    protected function _index($tag, $source_method, $render_view = 'index')
    {
        $req = Yii::$app->request;
        $source_method = $req->get('source_method', $source_method);
        if ($tag == 3) {
            $searchModel = new OverseasGoodsSearch();
            $where = $searchModel->search([],$tag);
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
        $shop_arr = ShopService::getShopDropdown();
        return $this->render($this->render_view . $render_view, [
            'tag' => $tag,
            'goods_stamp_tag' => $req->get('goods_stamp_tag'),
            'source_method' => $source_method,
            'category_arr' => $category_arr,
            'shop_arr' => $shop_arr,
        ]);
    }

    public function query($type = 'select')
    {
        $query = OverseasGoods::find()
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
        $tag = $req->get('tag');
        $searchModel = new OverseasGoodsSearch();
        $searchModel->goods_stamp_tag = $goods_stamp_tag;
        $query_params = Yii::$app->request->post();
        $lists = [];
        $total_count = 0;
        $where = $searchModel->search($query_params, $tag);
        $data = $this->lists($where,'og.add_time asc');
        $total_count = $data['pages']->totalCount;
        foreach ($data['list'] as $v) {
            $info = $v;
            $image = json_decode($v['goods_img'], true);
            $info['image'] = empty($image) || !is_array($image) ? '' : current($image)['img'];
            $info['add_time'] = date('Y-m-d H:i', $v['add_time']);
            $info['status_desc'] = empty(Goods::$status_map[$v['status']]) ? '' : Goods::$status_map[$v['status']];
            $info['category_name'] = Category::getCategoryName($v['category_id']) . '(' . $v['category_id'] . ')';
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
        $result = OverseasGoods::deleteAll(['id'=>$id]);
        if ($result) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "移除成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_SUCCESS, "移除失败", []);
        }
    }

    /**
     * @param $id
     * @return null|Goods
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        if (($model = $this->model()->findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

}