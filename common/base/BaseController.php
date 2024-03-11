<?php
/**
 * Created by PhpStorm.
 * User: ahanfeng
 * Date: 18-11-25
 * Time: 上午10:31
 */
namespace common\base;
use Codeception\Util\HttpCode;
use common\models\BaseAR;
use yii\data\Pagination;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\Controller;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use yii\web\Response;

class BaseController extends Controller
{

    const REQUEST_SUCCESS=1;
    const REQUEST_FAIL=0;
    const REQUEST_LAY_SUCCESS=0;
    const REQUEST_LAY_FAIL=1;
    const REQUEST_UN_AUTH=-1;
    public static $page_config = ['pageSizeParam' =>'limit','pageSizeLimit'=>[1,1000]];

    public $enableCsrfValidation=false;
    public $query;
    protected $cache_count = false;
    protected $substep_query = false;//分步查询

    /**
     * @param $action
     * @return bool
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        if ($this->module->id=="app-backend") {
            $route=$this->getRoute();
            $ing_list=['site/login','site/captcha','site/error','order/printed-pdf'];
            if (Yii::$app->user->isGuest){
                if (!in_array($route,$ing_list)){
                    if (Yii::$app->request->isAjax){
                        Yii::$app->response->format=Response::FORMAT_JSON;
                        Yii::$app->response->statusCode=HttpCode::UNAUTHORIZED;
                        Yii::$app->response->data=$this->FormatArray(self::REQUEST_UN_AUTH,'请重新登陆',"");
                        return false;
                    }else{
                        $this->redirect(['site/login']);
                        return false;
                    }
                }
            }else{//验证权限
                array_push($ing_list,'site/left-nav');

                if (in_array($route,$ing_list)){
                    return true;
                }
                if (Yii::$app->user->can('/'.$route)==false && !empty($ing_list)){
                    if (Yii::$app->request->isAjax){
                        Yii::$app->response->format=Response::FORMAT_JSON;
                        //Yii::$app->response->statusCode=HttpCode::FORBIDDEN;
                        Yii::$app->response->data=$this->FormatArray(self::REQUEST_FAIL,'无权限操作',"");
                        return false;
                    }else{
                        throw new ForbiddenHttpException("无权限操作");
                    }
                }
                return true;
            }
            return true;
        }
        return true;
    }

    /**
     * @param int $code 状态码
     * @param string $msg 错误消息
     * @param array $data 数据
     * @return array
     */
    public function FormatArray($code,$msg,$data = []){

        return ['status'=>$code,'msg'=>$msg,'data'=>$data];
    }

    /**
     * @param int $code 状态码
     * @param string $msg 消息内容
     * @param array $data 数据 请按照layer数组格式
     * @param int $count 列表长度
     * @param array $param
     * @return array
     */

    public function FormatLayerTable(int $code , string $msg, array $data,int $count,$param = []){
        $result = ['code'=>$code,'msg'=>$msg,'data'=>$data,'count'=>$count];
        if (!empty($param)) {
            $result['param'] = $param;
        }
        return $result;
    }

    /**
     * 获取model
     * @return \common\models\BaseAR
     */
    public function model(){

    }

    public function query($type = 'select'){
        return $this->query;
    }

    /**
     * 列表
     * @param $where
     * @param string $sort
     * @param array $select
     * @return array
     */
    protected function lists($where, $sort = 'id DESC',$select = null,$pageSize = null,$model = null)
    {
        $page = Yii::$app->request->get('page');
        if(empty($page)) {
            $page = Yii::$app->request->post('page',1);
        }
        if (is_null($pageSize)) {
            $pageSize = Yii::$app->request->get('limit');
            if(empty($pageSize)) {
                $pageSize = Yii::$app->request->post('limit',20);
            }
        }
        if(is_null($model)) {
            $model = $this->model();
            if (!($model instanceof BaseAR)) {
                return [];
            }
        }
        $query = $this->query();
        if($this->substep_query === false) {
            $list = $model::getListByCond($where, $page, $pageSize, $sort, $select, $query);
        } else {
            $list = $model::getListByCond($where, $page, $pageSize, $sort, 'id', $query);
            $ids = ArrayHelper::getColumn($list,'id');
            $new_model = $model::find()->where(['id'=>$ids]);
            if($select){
                $new_model->select($select);
            }
            if(!empty($sort)){
                $new_model = $new_model->orderBy($sort);
            }
            $list = $new_model->asArray()->all();
        }

        if(count($list) < $pageSize && $page == 1) {
            $count = count($list);
        } else {
            if($this->cache_count) {
                $count = $model::getCacheCountByCond($where, $query, __CLASS__ . __FUNCTION__);
            } else {
                $count = $model::getCountByCond($where, $this->query('count'));
            }
        }
        $pages = new Pagination(['totalCount' => $count, 'pageSize' => $pageSize]);
        $list = $this->formatLists($list);

        return [
            'list' => $list,
            'pages' => $pages,
        ];
    }

    /**
     * 格式化列表
     * @param $list
     * @return array
     */
    protected function formatLists($list)
    {
        $model = $this->model();
        return $model::formatLists($list);
    }
}