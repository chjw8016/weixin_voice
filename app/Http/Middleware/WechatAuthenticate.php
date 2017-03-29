<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/4 0004
 * Time: 11:09
 */

namespace App\Http\Middleware;

use Closure;
use Event;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;

class WechatAuthenticate
{

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param string|null $guard
     *
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (true) {
            if (!empty(env("AUTH_SERVER"))) {
                if ($request->has('gm_oauth_user')) {
                    $user = \GuzzleHttp\json_decode(urldecode(Input::get("gm_oauth_user")), true);
                    dd($user);
                    if (count($user) > 0) {
                        session(['wechat.oauth_user' => $user]);
                    } else {
                        Log::info("没有拿到用户信息");
                    }
                } else {
                    return redirect()->to(env("AUTH_SERVER") . "?gm_from=" . $request->fullUrl());
                }
            } else {
                $wechat = app('wechat');
                if ($request->has('state') && $request->has('code')) {
                    session(['wechat.oauth_user' => $wechat->oauth->user()]);
                    return redirect()->to($this->getTargetUrl($request));
                }
                $scopes = config('wechat.oauth.scopes', ['snsapi_userinfo']);
                if (is_string($scopes)) {
                    $scopes = array_map('trim', explode(',', $scopes));
                }
                return $wechat->oauth->scopes($scopes)->redirect($request->fullUrl());
            }
        }
        return $next($request);
    }

    /**
     * Build the target business url.
     *
     * @param Request $request
     *
     * @return string
     */
    public function getTargetUrl($request)
    {
        $queries = array_except($request->query(), ['code', 'state', "gm_oauth_user"]);

        return $request->url() . (empty($queries) ? '' : '?' . http_build_query($queries));
    }

}