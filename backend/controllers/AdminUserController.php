<?php
/**
 * Created by PhpStorm.
 * User: ahanfeng
 * Date: 18-11-25
 * Time: 下午11:13
 * 后台用户管理控制器
 */

namespace backend\controllers;

use backend\models\AdminUser;
use backend\models\Assignment;
use backend\models\SignUser;
use common\components\statics\Base;
use common\models\Shop;
use Yii;
use backend\models\search\AdminUserSearch;
use common\base\BaseController;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;

class AdminUserController extends BaseController
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['get'],
                ],
            ],
        ];
    }
    /**
     * @routeName 管理员管理
     * @routeDescription 管理员管理
     */
    public function actionIndex()
    {

        return $this->render('index');

    }

    /**
     * @routeName 管理员列表
     * @routeDescription 管理员列表
     */
    public function actionList()
    {
        Yii::$app->response->format=Response::FORMAT_JSON;

        $searchModel=new AdminUserSearch();
        $dataProvider=$searchModel->search(Yii::$app->request->queryParams);
    
        return $this->FormatLayerTable(
            self::REQUEST_LAY_SUCCESS,'获取成功',
            $dataProvider->getModels(),$dataProvider->getTotalCount()
        );
    }
    /**
     * @routeName 创建管理员
     * @routeDescription 创建新的管理员
     * @throws
     * @return string |Response |array
     */
    public function actionCreate()
    {
        $req=Yii::$app->request;
        if ($req->isPost){
            Yii::$app->response->format=Response::FORMAT_JSON;
            $signUser=new SignUser();
            $signUser->last_login_at=time();
            $items=$req->post('items',[]);
            $post = $req->post();
            //检查shop_id是否为空
            $shop_id = empty($post['shop_id'])?[]:$post['shop_id']; 
            //返回字符串
            $shop_id = implode(',',$shop_id);
            //变为请求post
            $post['shop_id'] = $shop_id;
            if ($signUser->load($post,'') && $signUser->save()){
                $assignModel=$this->findAssignModel($signUser->id);
                $success= $assignModel->assign($items);
                if ($success==count($items) && $success>0){
                    return $this->FormatArray(self::REQUEST_SUCCESS,"添加成功",[]);
                }else{
                    return $this->FormatArray(self::REQUEST_FAIL,"管理员添加成功,但权限分配有误,请到更新处确认",[]);
                }
            }else{
                return $this->FormatArray(self::REQUEST_FAIL,$signUser->getErrorSummary(false)[0],[]);
            }
        }
        $assignData=Assignment::getAllItems(); //获取所有
        $shop = Shop::find()->select('id,name,platform_type')->asArray()->all();
        $shop = ArrayHelper::index($shop,'id','platform_type');
        $shop_lists = [];
        $shop_ids = [];
        foreach ($shop as $key=>$value){
            $children = [];
            foreach ($value as $v){
                $info = [
                    'name' => $v['name'],
                    'value' => $v['id']
                ];
                if(in_array($v['id'],$shop_ids)){
                    $info['selected'] = true;
                }
                $children[] = $info;
            }
            $shop_lists[] = [
                'name' => Base::$platform_maps[$key],
                'children' => $children,
            ];
        }
        return $this->render('create',['assignData'=>$assignData,'shop'=>json_encode($shop_lists)]);
    }

    /**
     * @routeName 更新管理员
     * @routeDescription 更新管理员信息
     * @throws
     */
    public function actionUpdate()
    {
        $req=Yii::$app->request;
        $admin_user_id=$req->get('admin_user_id');
        if ($req->isPost){
            $admin_user_id=$req->post('admin_user_id');
        }
        $adminModel=$this->findModel($admin_user_id);

        $assignModel=$this->findAssignModel($admin_user_id);
        //获取已有权限和角色
        $assignData = $assignModel->getAssignItems();
        if ($req->isPost){
            Yii::$app->response->format=Response::FORMAT_JSON;
            $items=$req->post('items',[]);
            $post = $req->post();
            //检查$post['shop_id']是否为空,空的话赋值[]
            $shop_id = empty($post['shop_id'])?[]:$post['shop_id'];
            //将shop_id转换为字符串
            $shop_id = implode(',',$shop_id);
            $post['shop_id'] = $shop_id;
            if ($adminModel->load($post,'')==false){
               return $this->FormatArray(self::REQUEST_FAIL,"参数异常",[]);
            }
            if ($adminModel->role==AdminUser::ROLE_ROOT){
                $adminModel->status=$adminModel->getOldAttribute("status");
                $adminModel->role=$adminModel->getOldAttribute("role");
            }

            if ( $adminModel->save()){
                $assigned= array_reduce($assignData, 'array_merge', []);;//已经拥有的
                $removeAss=array_diff($assigned,$items);//需要移除的权限或者角色
                $assignAss=array_diff($items,$assigned);//新增的权限或者角色
                $rm_success = $assignModel->revoke($removeAss);
                $as_success= $assignModel->assign($assignAss);
                if ($rm_success+$as_success !=$removeAss+$assignAss || $rm_success!=$removeAss){
                    return $this->FormatArray(self::REQUEST_SUCCESS,"更新成功",[]);
                }else{
                    return $this->FormatArray(self::REQUEST_FAIL,"管理员更新成功,但权限分配有误,请到更新处确认",[]);
                }
            }else{
                return $this->FormatArray(self::REQUEST_FAIL,$adminModel->getErrorSummary(false)[0],[]);
            }
        }else{

            $all_role = Assignment::getAllItems();
            $shop = Shop::find()->select('id,name,platform_type')->asArray()->all();
            $shop = ArrayHelper::index($shop,'id','platform_type');
            $shop_lists = [];
            $shop_ids = explode(',',$adminModel['shop_id']);
            foreach ($shop as $key=>$value){
                $children = [];
                foreach ($value as $v){
                    $info = [
                        'name' => $v['name'],
                        'value' => $v['id']
                    ];
                    if(in_array($v['id'],$shop_ids)){
                        $info['selected'] = true;
                    }
                    $children[] = $info;
                }
                $shop_lists[] = [
                    'name' => Base::$platform_maps[$key],
                    'children' => $children,
                ];
            }
            return $this->render('update',['admin_user'=>$adminModel->toArray(),'assignData'=>$assignData,'all_role'=>$all_role,'shop'=>$shop_lists]);
        }
    }

    /**
     * @routeName 删除管理员
     * @routeDescription 删除指定管理员
     * @return array
     * @throws
     */
    public function actionDelete()
    {
        Yii::$app->response->format=Response::FORMAT_JSON;
        $req=Yii::$app->request;
        $admin_user_id=(int)$req->get('admin_user_id');
        $adminUser=$this->findModel($admin_user_id);
        if ($adminUser->role==AdminUser::ROLE_ROOT){
            return $this->FormatArray(self::REQUEST_FAIL,"禁止删除超级管理员",[]);
        }
        if ($adminUser->delete()){
            return $this->FormatArray(self::REQUEST_SUCCESS,"删除成功",[]);
        }else{
            return $this->FormatArray(self::REQUEST_SUCCESS,"删除失败",[]);
        }


    }

    /**
     * @routeName 上传头像
     * @routeDescription 上传头像
     * @inheritdoc
     * @todo
     */
    public function actionUploadHeadImg()
    {
        Yii::$app->response->format=Response::FORMAT_JSON;
        
        $images = UploadedFile::getInstancesByName("file");
        if(count($images) <= 0){
            return $this->FormatArray(self::REQUEST_FAIL,"上传文件不能为空");
        }
        
        $image = current($images);

        if ($image->size > 2048 * 1024) {
            return $this->FormatArray(self::REQUEST_FAIL,"图片最大不可超过2M");
        }
        if (!in_array(strtolower($image->extension), array('gif', 'jpg', 'jpeg', 'png'))) {
            return $this->FormatArray(self::REQUEST_FAIL,"请上传标准图片文件, 支持gif,jpg,png和jpeg.");

        }
        $dir = '/uploads/';
        //生成唯一uuid用来保存到服务器上图片名称
        $pickey = md5(time().rand(1, 9999));
        $filename = $pickey . '.' . $image->getExtension();

        //如果文件夹不存在，则新建文件夹
        if (!file_exists(Yii::getAlias('@frontend') . '/web' . $dir)) {
            FileHelper::createDirectory(Yii::getAlias('@frontend') . '/web' . $dir, 777);
        }
        $filepath = realpath(Yii::getAlias('@frontend') . '/web' . $dir) . '/';
        $file = $filepath . $filename;

        if ($image->saveAs($file)) {
            $imgpath = $dir . $filename;
        }
        
        $data['head_img'] = $imgpath;
        return $this->FormatArray(self::REQUEST_SUCCESS,"上传成功",$data);
    }

    /**
     * @routeName 更新状态
     * @routeDescription 更新状态
     * @return array |Response|string
     * @throws NotFoundHttpException
     */
    public function actionUpdateStatus(){

        Yii::$app->response->format=Response::FORMAT_JSON;
        $req=Yii::$app->request;
        $admin_user_id=(int)$req->get('admin_user_id');

        $adminUser=$this->findModel($admin_user_id);
        if ($adminUser->role==AdminUser::ROLE_ROOT){
            return $this->FormatArray(self::REQUEST_FAIL,"禁止更新超级管理员状态",[]);
        }
        $adminUser->status = $adminUser->status==10?0:10;
        if ($adminUser->save()){
            return $this->FormatArray(self::REQUEST_SUCCESS,"更新成功",[]);
        }else{
            return $this->FormatArray(self::REQUEST_FAIL,"更新失败",[]);
        }
    }

    /**
     * @param int $id 用户ID
     * @return Assignment
     * @throws NotFoundHttpException
     */
    protected function findAssignModel($id)
    {

        if (($user = AdminUser::findOne($id)) !== null) {
            return new Assignment($id, $user);
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * @param $id
     * @return null|AdminUser
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        if (($model = SignUser::findOne($id)) !== null) {
           return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}