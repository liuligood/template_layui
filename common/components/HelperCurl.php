<?php

namespace common\components;

/**
 * curl请求助手
 * @package helper
 */
class HelperCurl
{
    static $connect_timeout = 60;
    static $timeout = 500;
    static $last_post_info = null;
    static $last_error = null;

    /**
     * 发起请求
     *
     * @param string $url
     * @param string $requestBody
     * @param string $requestHeader
     * @param bool $justInit 是否只是初始化，用于并发请求
     * @param string $responseSaveToFileName 结果保存到文件，函数只返回true|false
     * @return bool|string
     */
    static function post($url, $requestBody = null, $requestHeader = null, $justInit = false, $responseSaveToFileName = null)
    {
        $connection = curl_init();

        curl_setopt($connection, CURLOPT_URL, $url);
        curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);
        if (!is_null($requestHeader)) {
            curl_setopt($connection, CURLOPT_HTTPHEADER, $requestHeader);
        }
        curl_setopt($connection, CURLOPT_POST, 1);
        curl_setopt($connection, CURLOPT_POSTFIELDS, $requestBody);
        if (!is_null($responseSaveToFileName)) {
            $fp = fopen($responseSaveToFileName, 'w');
            curl_setopt($connection, CURLOPT_FILE, $fp);
        } else {
            curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
        }
        curl_setopt($connection, CURLOPT_CONNECTTIMEOUT, self::$connect_timeout);
        curl_setopt($connection, CURLOPT_TIMEOUT, self::$timeout);
        if ($justInit) {
            return $connection;
        }

        $response = curl_exec($connection);
        self::$last_post_info = curl_getinfo($connection);
        $error = curl_error($connection);
        curl_close($connection);
        if (!is_null($responseSaveToFileName)) {
            fclose($fp);
        }
        if ($error) {
            $e = 'curl_error:' . (print_r($error, true)) . 'URL:' . $url . 'DATA:' . $requestBody;
            \Yii::error($e, "file");
            return false;
        }
        return $response;
    }

    /**
     * 发起请求
     *
     * @param string $url
     * @param string $requestBody
     * @param string $requestHeader
     * @param bool $justInit 是否只是初始化，用于并发请求
     * @param string $responseSaveToFileName 结果保存到文件，函数只返回true|false
     * @return bool|string
     */
    static function patch($url, $requestBody = null, $requestHeader = null, $justInit = false, $responseSaveToFileName = null)
    {
        $requestBody = json_encode($requestBody);
        $connection = curl_init();

        curl_setopt($connection, CURLOPT_URL, $url);
        curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);
        if (!is_null($requestHeader)) {
            curl_setopt($connection, CURLOPT_HTTPHEADER, $requestHeader);
        }
        curl_setopt ($connection, CURLOPT_CUSTOMREQUEST, "PATCH");
        curl_setopt($connection, CURLOPT_POSTFIELDS, $requestBody);
        if (!is_null($responseSaveToFileName)) {
            $fp = fopen($responseSaveToFileName, 'w');
            curl_setopt($connection, CURLOPT_FILE, $fp);
        } else {
            curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
        }
        curl_setopt($connection, CURLOPT_CONNECTTIMEOUT, self::$connect_timeout);
        curl_setopt($connection, CURLOPT_TIMEOUT, self::$timeout);
        if ($justInit) {
            return $connection;
        }

        $response = curl_exec($connection);
        self::$last_post_info = curl_getinfo($connection);
        $error = curl_error($connection);
        curl_close($connection);
        if (!is_null($responseSaveToFileName)) {
            fclose($fp);
        }
        if ($error) {
            $e = 'curl_error:' . (print_r($error, true)) . 'URL:' . $url . 'DATA:' . $requestBody;
            \Yii::error($e, "file");
            return false;
        }
        return $response;
    }

    /**
     * 发起请求
     *
     * @param string $url
     * @param string $requestBody
     * @param string $requestHeader
     * @param bool $justInit 是否只是初始化，用于并发请求
     * @param string $responseSaveToFileName 结果保存到文件，函数只返回true|false
     * @return bool|string
     */
    static function get($url, $requestBody = null, $requestHeader = null, $justInit = false, $responseSaveToFileName = null, $http_version = null)
    {
        $requestBody = $requestBody ? '?' . http_build_query ( $requestBody ) : '';
        $url = $url . $requestBody;

        $connection = curl_init();

        curl_setopt($connection, CURLOPT_URL, $url);
        curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($connection, CURLOPT_FOLLOWLOCATION, true);// 出现图片短链接需要302跳转才能获取
        if (!is_null($requestHeader)) {
            curl_setopt($connection, CURLOPT_HTTPHEADER, $requestHeader);
        }
        if (!is_null($http_version)) {
            curl_setopt($connection, CURLOPT_HTTP_VERSION, $http_version);
        }
        if (!is_null($responseSaveToFileName)) {
            $fp = fopen($responseSaveToFileName, 'w');
            curl_setopt($connection, CURLOPT_FILE, $fp);
        } else {
            curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
        }
        curl_setopt($connection, CURLOPT_CONNECTTIMEOUT, self::$connect_timeout);
        curl_setopt($connection, CURLOPT_TIMEOUT, self::$timeout);
        if ($justInit) {
            return $connection;
        }

        $response = curl_exec($connection);
        self::$last_post_info = curl_getinfo($connection);
        $error = curl_error($connection);
        curl_close($connection);
        if (!is_null($responseSaveToFileName)) {
            fclose($fp);
        }
        if ($error) {
            $e = 'curl_error:' . (print_r($error, true)) . 'URL:' . $url . 'DATA:' . print_r($requestBody, true);
            \Yii::error($e, "file");
            return false;
        }
        return $response;
    }

    /**
     * 发起请求
     *
     * @param string $url
     * @param string $requestBody
     * @param string $requestHeader
     * @param bool $justInit 是否只是初始化，用于并发请求
     * @param string $responseSaveToFileName 结果保存到文件，函数只返回true|false
     * @param bool $isurl_encode 是否对参数$requestBody使用http_build_query 生成 URL-encode 之后的请求字符串
     *
     * @author hqw    20160331
     * @return bool|string
     */
    static function post2($url, $requestBody = null, $requestHeader = null, $justInit = false, $responseSaveToFileName = null, $isurl_encode = false)
    {
        $connection = curl_init();

        curl_setopt($connection, CURLOPT_URL, $url);
        curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);
        if (!is_null($requestHeader)) {
            curl_setopt($connection, CURLOPT_HTTPHEADER, $requestHeader);
        }
        curl_setopt($connection, CURLOPT_POST, 1);

        if ($isurl_encode == true) {
            curl_setopt($connection, CURLOPT_POSTFIELDS, http_build_query($requestBody));
        } else {
            curl_setopt($connection, CURLOPT_POSTFIELDS, $requestBody);
        }

        if (!is_null($responseSaveToFileName)) {
            $fp = fopen($responseSaveToFileName, 'w');
            curl_setopt($connection, CURLOPT_FILE, $fp);
        } else {
            curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
        }
        curl_setopt($connection, CURLOPT_CONNECTTIMEOUT, self::$connect_timeout);
        curl_setopt($connection, CURLOPT_TIMEOUT, self::$timeout);
        if ($justInit) {
            return $connection;
        }

        $response = curl_exec($connection);
        self::$last_post_info = curl_getinfo($connection);
        $error = curl_error($connection);
        curl_close($connection);
        if (!is_null($responseSaveToFileName)) {
            fclose($fp);
        }
        if ($error) {
            $e = 'curl_error:' . (print_r($error, true)) . 'URL:' . $url . 'DATA:' . (print_r($requestBody, true));
            \Yii::error($e, "file");
            return false;
        }
        return $response;
    }

    static function delete($url, $requestBody = null, $requestHeader = null)
    {
        $curl_handle = curl_init();
// 		var_dump($url);die;
        // Set default options.
        curl_setopt($curl_handle, CURLOPT_URL, $url);
        curl_setopt($curl_handle, CURLOPT_FILETIME, true);
        curl_setopt($curl_handle, CURLOPT_FRESH_CONNECT, false);
        if (!is_null($requestHeader)) {
            curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $requestHeader);
        }
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, self::$connect_timeout);
        curl_setopt($curl_handle, CURLOPT_TIMEOUT, self::$timeout);
        curl_setopt($curl_handle, CURLOPT_NOSIGNAL, true);
        curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'DELETE');

        $response = curl_exec($curl_handle);
        $error = curl_error($curl_handle);
        curl_close($curl_handle);
        if ($error) {
            $e = 'curl_error:' . (print_r($error, true)) . 'URL:' . $url . 'DATA:' . $requestBody;
            \Yii::error($e, "file");
            return false;
        }
        return $response;
    }

    static function multiPost($curlHandles)
    {
        self::$last_error = array();
        self::$last_post_info = array();
        $mh = curl_multi_init();
        foreach ($curlHandles as $ch) {
            curl_multi_add_handle($mh, $ch);
        }
        $still_running = 1;
        do {
            usleep(500);
            curl_multi_exec($mh, $still_running);
        } while ($still_running > 0);
        $results = array();
        foreach ($curlHandles as $id => $ch) {
            $results[$id] = curl_multi_getcontent($ch);
            self::$last_post_info[$id] = curl_getinfo($ch);
            self::$last_error[$id] = curl_error($ch);
            curl_multi_remove_handle($mh, $ch);
        }
        curl_multi_close($mh);
        return $results;
    }

    static function downloadFile($remote, $local, $timeout = 10)
    {
        $cp = curl_init($remote);
        $fp = fopen($local, "w");

        curl_setopt($cp, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($cp, CURLOPT_TIMEOUT, 3600);
        curl_setopt($cp, CURLOPT_FILE, $fp);
        curl_setopt($cp, CURLOPT_HEADER, 0);

        $r = curl_exec($cp);
        curl_close($cp);
        fclose($fp);
        return $r;
    }
}