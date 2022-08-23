<?php

Route::group(['namespace' => 'Botble\Ecommerce\Http\Controllers', 'middleware' => ['web', 'core']], function () {
    Route::group(['prefix' => BaseHelper::getAdminPrefix() . '/ecommerce', 'middleware' => 'auth'], function () {
        Route::group(['prefix' => 'cjdropping', 'as' => 'cjdropping.'], function () {
            Route::resource('', 'CjdroppingController')
                ->parameters(['' => 'cjdropping']);

           
        });
    });
});
