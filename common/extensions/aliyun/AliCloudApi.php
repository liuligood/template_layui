<?php
namespace common\extensions\aliyun;


use yii\helpers\ArrayHelper;

class AliCloudApi
{


    /**
     * @param $url
     * @param $appcode
     * @param $params
     * @return mixed
     */
    public function createClient($appcode,$url,$params)
    {
        $method = "POST";
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        //根据API的要求，定义相对应的Content-Type
        array_push($headers, "Content-Type".":"."application/json; charset=UTF-8");
        $bodys = json_encode($params);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($curl, CURLOPT_HEADER, true);
        if (1 == strpos("$".$url, "https://"))
        {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        curl_setopt($curl, CURLOPT_POSTFIELDS, $bodys);
        $result = curl_exec($curl);
        return json_decode($result,true);
    }

    /**
     * 图片白底
     * @param string $img
     * @return bool|string
     */
    public function whiteImage($img)
    {
        $content = file_get_contents($img);
        $photo = base64_encode($content);
        $url = "https://objseg.market.alicloudapi.com/commonseg/rgba";
        $app_code = 'eb3ec0a2695340dd9a7a0f57c45fa78f';
        $params = [
            'photo' => $photo
        ];
        $result = $this->createClient($app_code,$url,$params);
        if(!empty($result['data']) && !empty($result['data']['result'])){
            $url =  $result['data']['result'];

            $image = imagecreatefrompng($url);

            $w = $result['data']['size'][0];
            $h = $result['data']['size'][1];
            //宽 高
            $new_image = imagecreatetruecolor($w, $h);
            //白色背景
            $white = imagecolorallocate($new_image, 255, 255, 255);
            //填充背景
            imagefill($new_image, 0, 0, $white);

            imagecopyresampled($new_image, $image, 0, 0, 0, 0, $w, $h, $w, $h);

            $output_file = '/tmp/'.md5(time().rand(1, 9999)).'.png';
            //生成图片
            imagepng($new_image,$output_file);
            //销毁释放
            imagedestroy($image);

            return \Yii::$app->oss->uploadFileByLocal($output_file);
        }
        return false;
    }

}