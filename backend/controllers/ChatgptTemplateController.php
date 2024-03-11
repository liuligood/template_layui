<?php

namespace backend\controllers;

use AlibabaCloud\Credentials\Helper;
use common\base\BaseController;
use common\services\sys\ChatgptService;
use Yii;
use common\models\sys\ChatgptTemplate;
use backend\models\search\ChatgptTemplateSearch;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;


class ChatgptTemplateController extends BaseController
{
    public function model()
    {
        return new ChatgptTemplate();
    }

    /**
     * @routeName chatgpt模板主页
     * @routeDescription chatgpt模板主页
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * @routeName chatgpt模板列表
     * @routeDescription chatgpt模板列表
     */
    public function actionList()
    {
        Yii::$app->response->format=Response::FORMAT_JSON;
        $searchModel=new ChatgptTemplateSearch();
        $where = $searchModel->search(Yii::$app->request->queryParams);
        $data = $this->lists($where);
        foreach ($data['list'] as &$info){
            $info['add_time'] = Yii::$app->formatter->asDatetime($info['add_time']);
            $info['update_time'] = Yii::$app->formatter->asDatetime($info['update_time']);
            $info['template_type'] = ChatgptTemplate::$template_maps[$info['template_type']];
            $info['status'] = empty(ChatgptTemplate::$status_maps[$info['status']]) ? $info['status'] : ChatgptTemplate::$status_maps[$info['status']];
        }
        return $this->FormatLayerTable(self::REQUEST_LAY_SUCCESS,"获取成功",$data['list'],$data['pages']->totalCount);
    }


    /**
     * @routeName 新增chatgpt模板
     * @routeDescription 新增chatgpt模板
     */
    public function actionCreate()
    {
        $req = Yii::$app->request;
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            $exists = ChatgptTemplate::find()->where(['template_code'=>$post['template_code'],'status' => ChatgptTemplate::STATUS_NORMAL])->exists();
            if ($exists) {
                return $this->FormatArray(self::REQUEST_FAIL,'添加失败,已有该模板编号', []);
            }
            $model = new ChatgptTemplate();
            $model['template_name'] = $post['template_name'];
            $model['template_code'] = $post['template_code'];
            $model['template_type'] = $post['template_type'];
            $model['template_content'] = $post['template_content'];
            if ($post['template_type'] == ChatgptTemplate::TEMPLATE_TYPE_CHAT) {
                $list = [];
                foreach ($post['role'] as $k => $v) {
                    if (empty($post['role'])) {
                        return $this->FormatArray(self::REQUEST_FAIL,'必填项不能为空', []);
                    }
                    $list[$k]['role'] = $v;
                    $list[$k]['content'] = $post['template_content'][$k];
                }
                $model['template_content'] = json_encode($list,JSON_UNESCAPED_UNICODE);
            }
            $model['param'] = $this->dealParam($post);
            $model['template_param_desc'] = $post['template_param_desc'];
            $model['status'] = $post['status'];
            if ($model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL,'添加失败', []);
            }
        }
        return $this->render('create');
    }

    /**
     * @routeName 修改chatgpt模板
     * @routeDescription 修改chatgpt模板
     */
    public function actionUpdate()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        $model = $this->findModel($id);
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            if (($model['template_code'] != $post['template_code'] || $model['status'] != $post['status']) && $post['status'] == ChatgptTemplate::STATUS_NORMAL) {
                $exists = ChatgptTemplate::find()->where(['template_code' => $post['template_code'],'status' => ChatgptTemplate::STATUS_NORMAL])->exists();
                if ($exists) {
                    return $this->FormatArray(self::REQUEST_FAIL,'修改失败,该编号已有其他模板启动', []);
                }
            }
            $info = ChatgptTemplate::findOne($post['id']);
            $info['template_name'] = $post['template_name'];
            $info['template_code'] = $post['template_code'];
            $info['template_type'] = $post['template_type'];
            $info['template_content'] = $post['template_content'];
            if ($post['template_type'] == ChatgptTemplate::TEMPLATE_TYPE_CHAT) {
                $list = [];
                foreach ($post['role'] as $k => $v) {
                    if (empty($post['role'])) {
                        return $this->FormatArray(self::REQUEST_FAIL,'必填项不能为空', []);
                    }
                    $list[$k]['role'] = $v;
                    $list[$k]['content'] = $post['template_content'][$k];
                }
                $info['template_content'] = json_encode($list,JSON_UNESCAPED_UNICODE);
            }
            $info['param'] = $this->dealParam($post);
            $info['template_param_desc'] = $post['template_param_desc'];
            $info['status'] = $post['status'];
            if ($info->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "修改成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL,'修改失败', []);
            }
        }
        return $this->render('update',['model'=>$model->toArray()]);
    }


    /**
     * @routeName 测试chatgpt模板
     * @routeDescription 测试chatgpt模板
     */
    public function actionTestTemplate()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        $model = ChatgptTemplate::findOne($id);
        $list = [];
        if ($model['template_type'] == ChatgptTemplate::TEMPLATE_TYPE_COMPLETIONS) {
            $list = $this->getPram($model['template_content']);
        }
        if ($model['template_type'] == ChatgptTemplate::TEMPLATE_TYPE_CHAT) {
            $model['template_content'] = json_decode($model['template_content'],true);
            $content = ArrayHelper::getColumn($model['template_content'],'content');
            $content = implode(',',$content);
            $list = $this->getPram($content);
        }
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            $data = ChatgptService::templateExec($post['code'],$post['data'],$post['id']);
            if (!empty($data)) {
                return $this->FormatArray(self::REQUEST_SUCCESS,'获取成功',$data);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL,'服务器出错',[]);
            }
        }
        return $this->render('test_template',[
            'list' => $list,
            'template_code' => $model['template_code'],
            'id' => $model['id']
        ]);
    }

    /**
     * @pram $template_content
     * 提取参数
     */
    public function getPram($template_content)
    {
        $list = [];
        preg_match_all("/\{(.*?)\}/", $template_content,$content);
        foreach ($content[1] as $c_v) {
            $list[] = $c_v;
        }
        return $list;
    }

    /**
     * @routeName 删除chatgpt模板
     * @routeDescription 删除chatgpt模板
     */
    public function actionDelete()
    {
        $req = Yii::$app->request;
        Yii::$app->response->format = Response::FORMAT_JSON;
        $id = (int)$req->get('id');
        $model = $this->findModel($id);
        if ($model->delete()) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除失败", []);
        }
    }

    /**
     * 处理参数
     * @param $data
     * @return string
     */
    public function dealParam($data)
    {
        $list = [];
        foreach ($data['param_name'] as $k => $v) {
            if (empty($v)) {
                continue;
            }
            $list[$k]['name'] = $v;
            $list[$k]['content'] = $data['param_content'][$k];
        }
        return empty($list) ? '' : json_encode($list,JSON_UNESCAPED_UNICODE);
    }

    /**
     * Finds the ChatgptTemplate model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return ChatgptTemplate the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = ChatgptTemplate::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
