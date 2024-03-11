<?php

namespace backend\controllers;

use common\base\BaseController;
use common\components\statics\Base;
use common\models\Category;
use common\models\Goods;
use common\models\goods\OriginalGoodsName;
use common\models\GoodsSource;
use common\models\SupplierRelationship;
use common\models\User;
use common\services\goods\GoodsService;
use Yii;
use common\models\Supplier;
use backend\models\search\SupplierSearch;
use yii\data\Pagination;
use yii\web\NotFoundHttpException;
use yii\web\Response;


class SupplierController extends BaseController
{
    public function model()
    {
        return new Supplier();
    }


    /**
     * @routeName 供应商主页
     * @routeDescription 供应商主页
     */
    public function actionIndex()
    {
        $req = Yii::$app->request;
        $is_cooperate = $req->get('is_cooperate',Supplier::IS_NOT_COOPERATE);
        return $this->render('index',['is_cooperate' => $is_cooperate]);
    }


    /**
     * @routeName 供应商列表
     * @routeDescription 供应商列表
     */
    public function actionList()
    {
        $req = Yii::$app->request;
        $is_cooperate = $req->get('is_cooperate');
        Yii::$app->response->format=Response::FORMAT_JSON;
        $searchModel=new SupplierSearch();
        $where = $searchModel->search(Yii::$app->request->queryParams,$is_cooperate);
        $data = $this->lists($where);
        foreach ($data['list'] as &$info){
            $info['add_time'] = Yii::$app->formatter->asDatetime($info['add_time']);
            $info['update_time'] = Yii::$app->formatter->asDatetime($info['update_time']);
            $info['exists'] = GoodsSource::find()->where(['supplier_id' => $info['id'],'platform_type' => Base::PLATFORM_SUPPLIER])->exists();
            $info['offer_file'] = empty($info['offer_file']) ? '' : current(json_decode($info['offer_file'],true));
            $info['exists_goods'] = SupplierRelationship::find()->where(['supplier_id' => $info['id']])->exists();
        }
        return $this->FormatLayerTable(self::REQUEST_LAY_SUCCESS,"获取成功",$data['list'],$data['pages']->totalCount);
    }


    /**
     * @routeName 新增供应商
     * @routeDescription 创建供应商
     * @throws
     * @return string |Response |array
     */
    public function actionCreate()
    {
        $req = Yii::$app->request;
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new Supplier();
            if ($model->load($req->post(), '') && $model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, '添加失败', []);
            }
        }
        return $this->render('create');
    }

    /**
     * @routeName 更新供应商
     * @routeDescription 更新供应商
     * @throws
     */
    public function actionUpdate()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        if ($req->isPost) {
            $id = $req->post('id');
        }
        $model = $this->findModel($id);
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($model->load($req->post(), '') == false) {
                return $this->FormatArray(self::REQUEST_FAIL, "参数异常", []);
            }
            if ($model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, '更新失败', []);
            }
        } else {
            return $this->render('update', ['info' => $model->toArray()]);
        }
    }

    /**
     * @routeName 删除供应商
     * @routeDescription 删除供应商
     * @return array
     * @throws
     */
    public function actionDelete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = (int)$req->get('id');
        $model = $this->findModel($id);
        $exists = GoodsSource::find()->where(['supplier_id' => $id,'platform_type' => Base::PLATFORM_SUPPLIER])->exists();
        if ($exists) {
            return $this->FormatArray(self::REQUEST_FAIL, "删除失败,该供应商已被使用", []);
        }
        if ($model->delete()) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL, "删除失败", []);
        }
    }


    /**
     * @routeName 供应商详情
     * @routeDescription 供应商详情
     * @throws
     */
    public function actionView()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        $model = $this->findModel($id);
        return $this->render('view',['info' => $model->toArray()]);
    }


    /**
     * @routeName 报价上传
     * @routeDescription 报价上传
     */
    public function actionOffer()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        $model = $this->findModel($id);
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            $model['offer_file'] = $post['offer_file'];
            if ($model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "保存成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, "保存失败", []);
            }
        }
        return $this->render('offer',['model' => $model]);
    }


    /**
     * @routeName 查看商品
     * @routeDescription 查看商品
     */
    public function actionViewGoods()
    {
        $req = Yii::$app->request;
        $supplier_id = (int)$req->get('id');
        $page = Yii::$app->request->get('page', 1);
        $pageSize = Yii::$app->request->get('limit', 15);
        $model = $this->findModel($supplier_id);
        $where['s.supplier_id'] = $supplier_id;
        $query = Goods::find()->alias('g')
            ->select('g.*,s.purchase_amount')
            ->leftJoin(SupplierRelationship::tableName().' s','s.goods_no = g.goods_no')
            ->where(['s.supplier_id' => $supplier_id]);
        $count = Goods::getCountByCond($where,$query);
        $goods_list = Goods::getListByCond($where, $page, $pageSize, null,null,$query);
        $lists = [];
        foreach ($goods_list as $v) {
            $info = $v;
            $image = json_decode($v['goods_img'], true);
            $info['image'] = empty($image) || !is_array($image) ? '' : current($image)['img'];
            $info['add_time'] = date('Y-m-d H:i', $v['add_time']);
            $info['update_time'] = date('Y-m-d H:i', $v['update_time']);
            $info['status_desc'] = empty(Goods::$status_map[$v['status']]) ? '' : Goods::$status_map[$v['status']];
            $info['stock_desc'] = empty(Goods::$stock_map[$v['stock']]) ? '' : Goods::$stock_map[$v['stock']];
            $user = User::getInfo($info['admin_id']);
            $owner_user = User::getInfo($info['owner_id']);
            $info['owner_name'] = empty($owner_user['nickname']) ? '' : $owner_user['nickname'];
            $info['admin_name'] = empty($user['nickname']) ? '' : $user['nickname'];
            $info['category_name'] = Category::getCategoryName($v['category_id']).'('.$v['category_id'].')';
            $source_platforms = GoodsService::getGoodsSource($v['source_method']);
            $info['source_platform_type'] = empty($source_platforms[$v['source_platform_type']]) ? '' : $source_platforms[$v['source_platform_type']];
            $goods_tort_type_map = GoodsService::getGoodsTortTypeMap($v['source_method_sub']);
            $info['goods_tort_type_desc'] = empty($goods_tort_type_map[$v['goods_tort_type']])?'':$goods_tort_type_map[$v['goods_tort_type']];
            $lists[] = $info;
        }
        $pages = new Pagination(['totalCount' => $count, 'pageSize' => $pageSize]);
        return $this->render('view_goods',[
            'info' => $model,
            'goods_list' => $lists,
            'pages' => $pages
        ]);
    }

    /**
     * Finds the Supplier model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Supplier the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Supplier::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
