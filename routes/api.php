<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::group(['domain' => 'api.' . env('APP_DOMAIN')],function () {
    Route::post('/auth/login', 'Auth\LoginController@login');

    Route::group(['middleware'=>'auth:sanctum'], function() {
//        Route::get('/dashboard/bytesSavedPerDay','DashboardController@bytesSavedPerDay');
//        Route::get('/dashboard/imagesServedPerDay','DashboardController@imagesServedPerDay');
        Route::get('/statistics/daily','Api\StatisticsController@daily');
        Route::post('/originals/{original_id}','Api\OriginalsController@update');
        Route::get('/originals','Api\OriginalsController@list');
        Route::get('/preview/{quality}/{image}','Api\PreviewController@preview')->where(['image' => '.*']);
    });
});
