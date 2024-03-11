<?php

namespace backend\controllers;

use backend\models\search\GrabGoodsSearch;
use backend\models\search\GrabSearch;
use common\components\CommonUtil;
use common\models\Category;
use common\models\Goods;
use common\models\grab\Grab;
use common\models\grab\GrabGoods;
use common\models\User;
use common\services\FGrabService;
use common\services\goods\GoodsService;
use Yii;
use common\base\BaseController;
use yii\helpers\FileHelper;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

class GrabController extends BaseController
{

    /**
     * @routeName 采集html
     * @routeDescription 采集html
     * @return array |Response|string
     * @throws NotFoundHttpException
     */
    public function actionHtml()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $url = $req->post('url');
        $html = $req->post('html');
         if(!empty($url) && !empty($html)) {
             $html = '<html>'.$html.'</html>';
             try {
                 (new GoodsService())->grab($url, \Yii::$app->user->identity->id, [], $html);
             } catch (\Exception $e){
                 return $this->FormatArray(self::REQUEST_FAIL, '采集失败：'.$e->getMessage(), []);
             }
             /*$path = \Yii::$app->params['path']['file'];
             $file_dir = "walmart/" . date('Y-m');
             $wal_dir = $path . '/' . $file_dir;
             !is_dir($wal_dir) && @mkdir($wal_dir, 0777, true);
             $file_path = $wal_dir . '/'.date('nhis').'.json';
             file_put_contents($file_path, $html);
             CommonUtil::logs($url . ' ' . $file_path, 'grab_html');*/
             return $this->FormatArray(self::REQUEST_SUCCESS, "采集成功", []);
         } else {
             return $this->FormatArray(self::REQUEST_FAIL, "采集失败", []);
        }
    }

    /**
     * @routeName 采集管理
     * @routeDescription 采集管理
     */
    public function actionIndex()
    {
        $queryParams = Yii::$app->request->queryParams;
        $source_method = empty($queryParams['source_method'])?GoodsService::SOURCE_METHOD_OWN:$queryParams['source_method'];
        return $this->render('index',['source_method'=>$source_method]);
    }

    /**
     * @routeName 采集列表
     * @routeDescription 采集列表
     */
    public function actionList()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $searchModel = new GrabSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams,Yii::$app->request->queryParams['source_method']);

        $data = $dataProvider->getModels();
        $lists = [];
        foreach ($data as $v) {
            $info = $v->toArray();
            $user = User::getInfo($info['admin_id']);
            $info['category_name'] = Category::getCategoryName($v['category_id']);
            $info['admin_name'] = empty($user['nickname']) ? '' : $user['nickname'];
            $info['status_desc'] = empty(Grab::$status_map[$v['status']]) ? '' : Grab::$status_map[$v['status']];
            $info['source_desc'] = empty(FGrabService::$source_map[$v['source']]) ? '' : FGrabService::$source_map[$v['source']]['name'];
            $lists[] = $info;
        }

        return $this->FormatLayerTable(
            self::REQUEST_LAY_SUCCESS, '获取成功',
            $lists, $dataProvider->getTotalCount()
        );
    }

    /**
     * @routeName 新增采集
     * @routeDescription 创建新的采集
     * @throws
     * @return string |Response |array
     */
    public function actionCreate()
    {
        $req = Yii::$app->request;
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $grab_model = new Grab();
            $data = $req->post();
            $source = FGrabService::getSource($data['url']);
            if(!in_array($source,FGrabService::$source_method[$data['source_method']])){
                return $this->FormatArray(self::REQUEST_FAIL, '暂不支持该采集', []);
            }

            if(!empty($data['category_id'])) {
                $category_id = $data['category_id'];
                $category_arr = explode(',', $category_id);
                $data['category_id'] = (int)end($category_arr);
            }

            if ($grab_model->load($data, '')) {
                $grab_model->admin_id = Yii::$app->user->identity->id;
                $grab_model->md5 = md5($grab_model->url);
                $grab_model->status = 0;
                $grab_model->cur_lists_page = 0;
                $grab_model->source = $source;
                $grab_model->save();
                return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $grab_model->getErrorSummary(false)[0], []);
            }
        }
        $source_method = $req->get('source_method');
        return $this->render('create',['source_method'=>$source_method]);
    }

    /**
     * @routeName 取消采集
     * @routeDescription 取消采集
     * @return array |Response|string
     * @throws NotFoundHttpException
     */
    public function actionCancel()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = (int)$req->get('id');

        $grab_model = $this->findModel($id);
        $grab_model->status = Grab::STATUS_CANCEL;
        if ($grab_model->save()) {
            GrabGoods::updateAll(['status' => GrabGoods::STATUS_CANCEL], ['gid' => $id, 'status' => GrabGoods::STATUS_WAIT]);
            return $this->FormatArray(self::REQUEST_SUCCESS, "取消成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL, "取消失败", []);
        }
    }

    /**
     * @routeName 删除采集
     * @routeDescription 删除采集
     * @return array |Response|string
     * @throws NotFoundHttpException
     */
    public function actionDelete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = (int)$req->get('id');

        $grab_model = $this->findModel($id);
        if($grab_model->status == Grab::STATUS_SUCCESS && GrabGoods::find()->where([
                'gid'=>$id,
                'goods_status' => GrabGoods::GOODS_STATUS_NORMAL,
                'self_logistics' => GrabGoods::SELF_LOGISTICS_YES,
                'status' => GrabGoods::STATUS_SUCCESS,
                'use_status'=>GrabGoods::USE_STATUS_NONE,
            ])->exists()){
            return $this->FormatArray(self::REQUEST_FAIL, "删除失败，存在未处理的商品数据", []);
        }

        $grab_model->status = Grab::STATUS_DELETE;
        if ($grab_model->save()) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL, "删除失败", []);
        }
    }

    /**
     * @param $id
     * @return null|Goods
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        if (($model = Grab::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * @routeName 采集导出
     * @routeDescription 采集导出
     * @return array |Response|string
     */
    public function actionExport()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = (int)$req->get('id');

        //$grab = Grab::find()->where(['id'=>$id])->one();
        $grab_goods_lists = GrabGoods::find()->where([
            'gid' => $id,
            'status' => [GrabGoods::STATUS_SUCCESS],
            'self_logistics' => GrabGoods::SELF_LOGISTICS_YES,
            'goods_status' => GrabGoods::GOODS_STATUS_NORMAL,
            //'use_status' => GrabGoods::USE_STATUS_VALID,
        ])->asArray()->all();

        $data = (new GrabGoodsSearch())->export($grab_goods_lists);

        return $this->FormatArray(self::REQUEST_SUCCESS, "", $data);
    }
}