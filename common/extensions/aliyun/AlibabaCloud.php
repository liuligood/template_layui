<?php
namespace common\extensions\aliyun;

use AlibabaCloud\SDK\Alimt\V20181012\Alimt;
use Darabonba\OpenApi\Models\Config;
use AlibabaCloud\SDK\Alimt\V20181012\Models\TranslateImageRequest;
use yii\helpers\ArrayHelper;

class AlibabaCloud
{

    public $accessKeyId = 'NfLmhaq3gZQJSQoV';
    public $accessKeySecret = 'IZoo642BYRW3poPMQCBjLpKL5Jsvgv';

    /**
     * 使用AK&SK初始化账号Client
     * @return Alimt Client
     */
    public function createClient()
    {
        $config = new Config([
            // 您的AccessKey ID
            "accessKeyId" => $this->accessKeyId,
            // 您的AccessKey Secret
            "accessKeySecret" => $this->accessKeySecret
        ]);
        // 访问的域名
        $config->endpoint = "mt.aliyuncs.com";
        return new Alimt($config);
    }

    /**
     * 翻译图片
     * @param array $args
     * @return array
     */
    public function translateImage($args)
    {
        $params = ArrayHelper::merge([
            'sourceLanguage' => 'zh',
            'targetLanguage' => 'en',
            'field' => 'e-commerce',
            //'ext' => '{"needEditorData": "true"}',
        ],$args);
        $client = $this->createClient();
        $translateImageRequest = new TranslateImageRequest($params);
        $result = $client->translateImage($translateImageRequest);
        return $result->toMap();
    }

}