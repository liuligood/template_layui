<?php
namespace common\components;

use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use yii\base\Component;

class Oss extends Component
{
    public $accessKey = 'eGxa1ZwVUE0zmkJtRfl1k6XpUbuqwPJNtsIl2LbZ';
    public $secretKey = 'zvedpHgAFD0MInotJ2VLKg0_A36w0PTdjlQq9xMl';

    public $endpoint = 'image.chenweihao.cn';

    public $bucket = 'byshop';

    public static $ossClient = null;

    public $errors = '';

    public function init()
    {
        parent::init();
    }

    private function getClient()
    {
        try {
            if (self::$ossClient === null) {
                self::$ossClient = new Auth($this->accessKey, $this->secretKey);// 构建 鉴权对象
            }
            return self::$ossClient;
        } catch (\Exception $e) {
            $this->errors = $e->getMessage();
            return null;
        }
    }

    /**
     * 创建token
     * @param $bucket
     * @return string
     */
    public function createToken($bucket = null)
    {
        if (empty($bucket)) {
            $bucket = $this->bucket;
        }
        return $this->getClient()->uploadToken($bucket);
    }

    /**
     * 上传
     * @param \yii\web\UploadedFile $file
     * @param $bucket
     * @return bool
     */
    public function upload($file, $bucket = null)
    {
        try {
            $token = $this->createToken($bucket);

            $filePath = $file->tempName;
            $filename = md5($file->baseName . time() . uniqid()) . '.' . $file->getExtension();
            $key = date('Ym') . '/' . $filename;

            $uploadMgr = new UploadManager();
            list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
            if ($err !== null) {
                $this->errors = $err;
                return false;
            }

            return 'http://' . $this->endpoint . '/' . $ret['key'];
        } catch (\Exception $e) {
            $this->errors = $e->getMessage();
            return false;
        }
    }

    /**
     * 本地图片上传到七牛
     * @param string $file_path 路径
     * @param $bucket
     * @return bool
     * @throws \Exception
     */
    public function uploadFileByLocal($file_path, $bucket = null)
    {
        try {
            $token = $this->createToken($bucket);
            $image_info = pathinfo($file_path);
            $extension = $image_info['extension'];
            $filename = md5($image_info['basename'] . time() . uniqid()) . '.' . $extension;
            $key = date('Ym') . '/' . $filename;
            $content = @fread(fopen($file_path, 'r'), filesize($file_path));

            $uploadMgr = new UploadManager();
            list($ret, $err) = $uploadMgr->put($token, $key, $content);
            if ($err !== null) {
                $this->errors = $err;
                return false;
            }
            return 'http://' . $this->endpoint . '/' . $ret['key'];
        } catch (\Exception $e) {
            $this->errors = $e->getMessage();
            return false;
        }
    }

    /**
     * 远程图片上传到七牛
     * @param string $file_path 路径
     * @param $bucket
     * @return bool
     * @throws \Exception
     */
    public function uploadFileByPath($file_path, $bucket = null)
    {
        try {
            $file_path = trim($file_path);
            $token = $this->createToken($bucket);

            $path = explode('?',$file_path);
            $image_info = pathinfo($path[0]);

            $extension = $image_info['extension'];
            $filename = md5($image_info['basename'] . time() . uniqid()) . '.' . $extension;
            $key = date('Ym') . '/' . $filename;

            $content = file_get_contents($file_path);
            if(empty($content)) {
                return false;
            }
            //$content = self::getImage($file_path);

            $uploadMgr = new UploadManager();
            list($ret, $err) = $uploadMgr->put($token, $key, $content);
            if ($err !== null) {
                $this->errors = $err;
                return false;
            }

            return 'http://' . $this->endpoint . '/' . $ret['key'];

        } catch (\Exception $e) {
            $this->errors = $e->getMessage();
            return false;
        }
    }

    public static function getImage($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, ""); //加速 这个地方留空就可以了
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

}