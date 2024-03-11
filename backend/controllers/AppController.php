<?php

namespace backend\controllers;

use common\services\ImportResultService;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use Yii;
use common\base\BaseController;
use yii\helpers\FileHelper;
use yii\web\Response;
use yii\web\UploadedFile;

class AppController extends BaseController
{

    /**
     * @routeName 编辑图片
     * @routeDescription 编辑图片
     * @inheritdoc
     * @todo
     */
    public function actionEditImg()
    {
        return $this->render('edit_img');
    }

    /**
     * @routeName 上传图片
     * @routeDescription 上传图片
     * @inheritdoc
     * @todo
     */
    public function actionUploadImg()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $images = UploadedFile::getInstancesByName("file");
        if (count($images) <= 0) {
            return $this->FormatArray(self::REQUEST_FAIL, "上传文件不能为空");
        }

        $image = current($images);

        if ($image->size > 5 * 1024 * 1024) {
            return $this->FormatArray(self::REQUEST_FAIL, "图片最大不可超过5M");
        }
        if (!in_array(strtolower($image->extension), array('gif', 'jpg', 'jpeg', 'png'))) {
            return $this->FormatArray(self::REQUEST_FAIL, "请上传标准图片文件, 支持gif,jpg,png和jpeg.");
        }

        $result = \Yii::$app->oss->upload($image);
        if(!$result){
            return $this->FormatArray(self::REQUEST_FAIL, '上传失败');
        }

        $data = [];
        $data['img'] = $result;
        return $this->FormatArray(self::REQUEST_SUCCESS, "上传成功", $data);
    }

    /**
     * @routeName 上传图片
     * @routeDescription 上传图片
     * @inheritdoc
     * @todo
     */
    public function actionTinymceUploadImg()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $images = UploadedFile::getInstancesByName("edit");
        if (count($images) <= 0) {
            return $this->FormatArray(self::REQUEST_FAIL, "上传文件不能为空");
        }

        $image = current($images);

        if ($image->size > 2048 * 1024) {
            return ['code' => self::REQUEST_LAY_FAIL, 'msg' => "图片最大不可超过2M", 'data' => ''];
        }
        if (!in_array(strtolower($image->extension), array('gif', 'jpg', 'jpeg', 'png'))) {
            return ['code' => self::REQUEST_LAY_FAIL, 'msg' => "请上传标准图片文件, 支持gif,jpg,png和jpeg.", 'data' => ''];
        }

        $result = \Yii::$app->oss->upload($image);
        if(!$result){
            return $this->FormatArray(self::REQUEST_FAIL, '上传失败');
        }

        return ['code' => self::REQUEST_LAY_SUCCESS, 'msg' => "上传成功", 'data' => $result];
    }

    /*public function actionUploadImg()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $images = UploadedFile::getInstancesByName("file");
        if (count($images) <= 0) {
            return $this->FormatArray(self::REQUEST_FAIL, "上传文件不能为空");
        }

        $image = current($images);

        if ($image->size > 2048 * 1024) {
            return $this->FormatArray(self::REQUEST_FAIL, "图片最大不可超过2M");
        }
        if (!in_array(strtolower($image->extension), array('gif', 'jpg', 'jpeg', 'png'))) {
            return $this->FormatArray(self::REQUEST_FAIL, "请上传标准图片文件, 支持gif,jpg,png和jpeg.");

        }
        $dir = '/uploads/';
        //生成唯一uuid用来保存到服务器上图片名称
        $pickey = md5(time());
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

        $data['img'] = Yii::$app->params['site']['url'] . $imgpath;
        return $this->FormatArray(self::REQUEST_SUCCESS, "上传成功", $data);
    }*/

    /**
     * @routeName 导出错误excel文件
     * @routeDescription 导出上传的错误excel文件
     * @inheritdoc
     * @todo
     */
    public function actionGetImportResult()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $key = $req->get('key');
        return (new ImportResultService())->getExcel($key);
    }

    /**
     * @routeName 上传文件
     * @routeDescription 上传文件
     * @inheritdoc
     * @todo
     */
    public function actionUploadFile()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $files = UploadedFile::getInstancesByName("file");
        if (count($files) <= 0) {
            return $this->FormatArray(self::REQUEST_FAIL, "上传文件不能为空");
        }

        $file = current($files);

        if ($file->size > 5 * 1024 * 1024) {
            return $this->FormatArray(self::REQUEST_FAIL, "文件最大不可超过5M");
        }
        /*if (!in_array(strtolower($image->extension), array('gif', 'jpg', 'jpeg', 'png'))) {
            return $this->FormatArray(self::REQUEST_FAIL, "请上传标准图片文件, 支持gif,jpg,png和jpeg.");
        }*/

        $result = \Yii::$app->oss->upload($file);
        if(!$result){
            return $this->FormatArray(self::REQUEST_FAIL, '上传失败');
        }

        $data = [];
        $data['file'] = $result;
        $data['file_name'] = $file->name;
        return $this->FormatArray(self::REQUEST_SUCCESS, "上传成功", $data);
    }


    /**
     * @routeName 上传视频
     * @routeDescription 上传视频
     * @inheritdoc
     * @todo
     */
    public function actionUploadVideo()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $type = $req->get('type');
        $videos = UploadedFile::getInstancesByName("file");
        if (count($videos) <= 0) {
            return $this->FormatArray(self::REQUEST_FAIL, "上传文件不能为空");
        }

        $video = current($videos);
        if (!in_array(strtolower($video->extension), array('mp4','ogg','webm','swf'))) {
            return $this->FormatArray(self::REQUEST_FAIL, "请上传标准视频文件, 支持mp4,ogg,webm和swf.");
        }

        $result = \Yii::$app->oss->upload($video);
        if(!$result){
            return $this->FormatArray(self::REQUEST_FAIL, '上传失败');
        }

        $data = [];
        $data['video'] = $result;
        $data['type'] = $type;
        return $this->FormatArray(self::REQUEST_SUCCESS, "上传成功", $data);
    }

}