<?php
namespace common\extensions\openai;

use common\components\CommonUtil;
use yii\base\Component;

class Chatgpt extends Component
{

    public $api_key = "vtRzd2REhSRmDXNAx7GbT3BlbkFJTle4pNXebyE1caq5Y3n8";
    public $original_return = false;

    /**
     * @return \GuzzleHttp\Client
     */
    public function getClient()
    {
        $client = new \GuzzleHttp\Client([
            'headers' => [
                'Authorization' => base64_encode($this->api_key),
                'Content-Type' => 'application/json'
            ],
            'base_uri' => 'http://oapi.sanlinmail.site/openai/',
            'timeout' => 120,
            'http_errors' => false,
        ]);
        return $client;
    }

    /**
     * @return array|string
     */
    public function models()
    {
        $response = $this->getClient()->get('models');
        return $this->returnBody($response);
    }

    /**
     * 提问
     *
     * 使用方法：
     * $chatgpt = new Chatgpt();
     * $chatgpt->completions(['prompt'=>"你好","n"=>1]);
     * $chatgpt->completions("你好");
     * @return array|string
     */
    public function completions($data,$other_params = [])
    {
        $param = [];
        if (is_array($data)) {
            $param = $data;
        } else {
            $param['prompt'] = $data;
        }
        if (empty($param['prompt'])) {
            throw new \Exception('内容不能为空');
        }
        $param = array_merge($param, [
            "model" => "text-davinci-003",
            "max_tokens" => 3000,
            "temperature" => 0.2,
            "presence_penalty" => 0.6,
            "frequency_penalty" => 0
        ]);
        $param = array_merge($param, $other_params);
        $param = $this->dealParam($param);
        $response = $this->getClient()->post('completions', ['json' => $param]);
        $result = $this->returnBody($response);
        if ($this->original_return) {
            return $result;
        }
        if (!empty($result['status']) && $result['status'] == 200 && $result['data'] && empty($result['data']['error'])) {
            $arr = [];
            foreach ($result['data']['choices'] as $choice) {
                $content = trim($choice['text']);
                $content = trim($content,'"\'');
                $arr[] = $content;
            }
            return count($arr) > 1 ? $arr : (string)current($arr);
        }
        CommonUtil::logs('completions data:'.json_encode($param,JSON_UNESCAPED_UNICODE).' result:'.json_encode($result,JSON_UNESCAPED_UNICODE),'chatgpt');
        $error = !empty($result['error']) && !empty($result['error']['message']) ? $result['error']['message'] : (!empty($result['data']) && !empty($result['data']['error']) ? $result['data']['error']['message']:'');
        throw new \Exception('返回失败：' . $error);
    }

    /**
     * 处理参数类型
     * @param $param
     * @return array
     */
    public function dealParam($param)
    {
        $data = [];
        foreach ($param as $k => $v) {
            if (in_array($k, ['n', 'max_tokens', 'logprobs', 'best_of'])) {
                $v = (int)$v;
            }
            if (in_array($k, ['temperature', 'top_p', 'presence_penalty', 'frequency_penalty'])) {
                $v = (float)$v;
            }
            if (in_array($k, ['stream', 'echo'])) {
                $v = $v == 'true'?true:false;
            }
            $data[$k] = $v;
        }
        return $data;
    }

    /**
     * 聊天
     *
     * 使用方法：
     * $chatgpt = new Chatgpt();
     * $result = $chatgpt->chat([
     *      ['role' => 'user', 'content' => '你好'],
     *      ['role' => 'assistant', 'content' => '你好！有什么可以帮助您的吗？'],
     *      ['role' => 'user', 'content' => '请将[셜록 홈즈 탐정 베레모 모자 유니섹스 코스프레 액세서리 (카키)]翻译成英语'],
     * ]);
     *
     * $result = $chatgpt->chat(['messages' => [
     *      ['role' => 'system', 'content' => '你是个翻译助手',],
     *      ['role' => 'user', 'content' => '请将[셜록 홈즈 탐정 베레모 모자 유니섹스 코스프레 액세서리 (카키)]翻译成英语',],
     * ]]);
     * @return array|string
     */
    public function chat($data,$other_params = [])
    {
        $param = [];
        if (!empty($data['messages'])) {
            $param = $data;
        } else {
            $param['messages'] = $data;
        }
        $param = array_merge($param, [
            "model" => "gpt-3.5-turbo"
        ]);
        $param = array_merge($param, $other_params);
        $param = $this->dealParam($param);
        $response = $this->getClient()->post('chat', ['json' => $param]);
        $result = $this->returnBody($response);
        if ($this->original_return) {
            return $result;
        }
        if (!empty($result['status']) && $result['status'] == 200 && $result['data'] && empty($result['data']['error'])) {
            $arr = [];
            foreach ($result['data']['choices'] as $choice) {
                if ($choice['message']['role'] == 'assistant') {
                    $content = trim($choice['message']['content']);
                    $content = trim($content,'"\'');
                    $arr[] = $content;
                }
            }
            return count($arr) > 1 ? $arr : (string)current($arr);
        }
        CommonUtil::logs('chat data:'.json_encode($param,JSON_UNESCAPED_UNICODE).' result:'.json_encode($result,JSON_UNESCAPED_UNICODE),'chatgpt');
        $error = !empty($result['error']) && !empty($result['error']['message']) ? $result['error']['message'] : (!empty($result['data']) && !empty($result['data']['error']) ? $result['data']['error']['message']:'');
        throw new \Exception('返回失败：' . $error);
    }

    /**
     * @return array|string
     */
    public function returnBody($response)
    {
        $body = $response->getBody();
        return json_decode($body, true);
    }

}