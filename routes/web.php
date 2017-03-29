<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are handled
| by your application. Just tell Laravel the URIs it should respond
| to using a Closure or controller method. Build something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::any('/wechat', 'WechatController@serve');
Route::get('/clear', function () {
    session(['wechat.oauth_user' => null]);
    echo "success";
});
Route::get("/download", "IndexController@download");
Route::post("/upload", "IndexController@upload");
Route::group(['middleware' => ['web', 'wechat:snsapi_userinfo']], function () {
    Route::get('/index', "IndexController@index");
});
Route::get('/token', "IndexController@token");