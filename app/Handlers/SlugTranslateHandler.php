<?php

namespace App\Handlers;

use GuzzleHttp\Client;
use Overtrue\Pinyin\Pinyin;

class SlugTranslateHandler
{
    private $api = 'http://api.fanyi.baidu.com/api/trans/vip/translate?';
    private $appid;
    private $key;
    private $text;

    public function __construct()
    {
        $this->appid = config('services.baidu_translate.appid');
        $this->key = config('services.baidu_translate.key');
    }

    public function translate($text)
    {
        if (empty($this->appid) || empty($this->key)) {
            return $this->pinyin($text);
        }

        $http = new Client();
        $response = $http->get($this->str_query());
        $result = json_decode($response->getBody(), true);

        /**
         * 获取结果，如果请求成功，dd($result) 结果如下：
         *
         * array:3 [▼
         * "from" => "zh"
         * "to" => "en"
         * "trans_result" => array:1 [▼
         * 0 => array:2 [▼
         * "src" => "XSS 安全漏洞"
         * "dst" => "XSS security vulnerability"
         * ]
         * ]
         * ]
         **/

        // 尝试获取获取翻译结果
        if (isset($result['trans_result'][0]['dst'])) {
            return str_slug($result['trans_result'][0]['dst']);
        } else {
            // 如果百度翻译没有结果，使用拼音作为后备计划。
            return $this->pinyin($text);
        }
    }

    public function pinyin($text)
    {
        return str_slug(app(Pinyin::class)->permalink($text));
    }

    private function str_query()
    {

        $salt = time();
        // 根据文档，生成 sign
        // http://api.fanyi.baidu.com/api/trans/product/apidoc
        // appid+q+salt+密钥 的MD5值
        $sign = md5($this->appid . $this->text . $salt . $this->key);

        // 构建请求参数
        $query = http_build_query([
            "q"     => $this->text,
            "from"  => "zh",
            "to"    => "en",
            "appid" => $this->appid,
            "salt"  => $salt,
            "sign"  => $sign,
        ]);

        return $this->api . $query;
    }
}