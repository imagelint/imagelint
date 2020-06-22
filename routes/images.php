<?php

Route::group(['domain' => 'a1.' . env('APP_DOMAIN')],function () {
    Route::get('/{query}','Images\ConvertController@image')->where('query','.*');
});
