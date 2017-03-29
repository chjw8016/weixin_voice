<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/4 0004
 * Time: 11:41
 */

namespace App\Http\Controllers;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class IndexController extends Controller
{

    public function token()
    {
        $token = "P4WFXqpjy3bDRshahO7myMZU81vPJhu28y4mLRPUNR1duNSUoXUKW_PZFybvXY7f99LDYLUNTGRosXGK7TDyGCvawAS15ksCD3AfWnI0BRmwjXUeFofc-tl-N4oo7nfwQOCcAHAYWK";
        $access_token = $this->send_post(env("AUTH_SERVER") . "/token", []);
        $app = app("wechat");
        $accessToken = $app->access_token; // EasyWeChat\Core\AccessToken 实例
        $accessToken->getCache()->save($accessToken->getCacheKey(), $access_token, time() + 5000); // token 字符串

        $userService = $app->user;
        var_dump($userService->lists($nextOpenId = null));
        exit();

    }

    public function  index()
    {
        $app = app("wechat");
        $accessToken = $app->access_token; // EasyWeChat\Core\AccessToken 实例
        $access_token = $accessToken->getToken(true); // token 字符串
        $ticket = $this->send_post("https://api.weixin.qq.com/cgi-bin/ticket/getticket", array("type" => "jsapi", "access_token" => $access_token));
        $ticket = \GuzzleHttp\json_decode($ticket, true);
        $ticket = $this->getSignPackage($ticket);
        $rows = DB::table("gm_audio")->orderBy("create_time", "asc")->skip(0)->take(10)->get()->toArray();
        $arr = array();
        foreach ($rows as $item) {
            $arr[] = (array)$item;
        }
        $ticket["list"] = $arr;
        return view("index", $ticket);
        /*  return view("index");*/
    }

    public function download()
    {
        $app = app("wechat");
        $accessToken = $app->access_token; // EasyWeChat\Core\AccessToken 实例
        $access_token = $accessToken->getToken(true); // token 字符串
        $rows = DB::table("gm_audio")->where("mp3", '=', 0)->get();
        foreach ($rows as $vo) {
            try {
                $rlt = $this->send_get("http://file.api.weixin.qq.com/cgi-bin/media/get", array("access_token" => $access_token, "media_id" => $vo->media_id));
                Storage::disk('public')->put($vo->media_id . ".amr", $rlt);
                $info = array();
                $rlt = 0;
                $command = "ffmpeg -i " . storage_path("app/public/") . $vo->media_id . ".amr " . public_path("mp3/") . $vo->media_id . ".mp3";
                exec($command, $info, $rlt);
                Log::info("command:" . $command);
                Log::info("convert:" . $rlt);
                if ($rlt) {
                    DB::table("gm_audio")->where("media_id", "=", $vo->media_id)->update(array("mp3" => 1));
                } else {
                    Log::info("error:" . json_encode($info));
                }
            } catch (\Exception $e) {
                Log::info($e);
            }
        }
        return Response::json(array("code" => 0, "message" => "转换完成"));
    }

    public function upload()
    {
        $local_id = Input::get("localId");
        $server_id = Input::get("serverId");
        DB::table("gm_audio")->insert(array("media_id" => $server_id, "create_time" => time()));
    }

    private function getSignPackage($ticket)
    {
        $url = "http://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
        $timestamp = time();
        $nonceStr = md5("gamma" . $timestamp);
        $jsapiTicket = $ticket['ticket'];
        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        $signature = sha1($string);

        $signPackage = array(
            "appId" => config("wechat.app_id"),
            "nonceStr" => $nonceStr,
            "timestamp" => $timestamp,
            "url" => $url,
            "signature" => $signature,
            "rawString" => $string
        );
        return $signPackage;
    }

    //发送Post 请求
    private function send_post($url, $data)
    {
        return $this->send_request($url, $data, '', 'POST');
    }

    //发送Post 请求
    private function send_get($url, $data)
    {
        return $this->send_request($url, $data, '', 'GET');
    }

    /**
     * 发送HTTP请求
     *
     * @param string $url 请求地址
     * @param string $method 请求方式 GET/POST
     * @param string $refererUrl 请求来源地址
     * @param array $data 发送数据
     * @param string $contentType
     * @param string $timeout
     * @param string $proxy
     * @return boolean
     */
    private function send_request($url, $data, $refererUrl = '', $method = 'GET', $contentType = 'application/json', $timeout = 30, $proxy = false)
    {
        $ch = null;
        if ('POST' === strtoupper($method)) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            if ($refererUrl) {
                curl_setopt($ch, CURLOPT_REFERER, $refererUrl);
            }
            if ($contentType) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:' . $contentType));
            }
            if (is_string($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        } else if ('GET' === strtoupper($method)) {
            if (is_string($data)) {
                $real_url = $url . (strpos($url, '?') === false ? '?' : '') . $data;
            } else {
                $real_url = $url . (strpos($url, '?') === false ? '?' : '') . http_build_query($data);
            }

            $ch = curl_init($real_url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:' . $contentType));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            if ($refererUrl) {
                curl_setopt($ch, CURLOPT_REFERER, $refererUrl);
            }
        } else {
            $args = func_get_args();
            return false;
        }

        if ($proxy) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy);
        }
        $ret = curl_exec($ch);
        $info = curl_getinfo($ch);
        $contents = array(
            'httpInfo' => array(
                'send' => $data,
                'url' => $url,
                'ret' => $ret,
                'http' => $info,
            ),
        );

        curl_close($ch);
        return $ret;
    }
}