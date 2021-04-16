<?php

use Illuminate\Support\Facades\Route;

/**
 * 'admin' middleware and 'offline-payments' prefix applied to all routes (including names)
 *
 * @see \App\Providers\Route::register
 */

Route::admin('offline-payments', function () {
    Route::group(['prefix' => 'settings', 'as' => 'settings.'], function () {
        Route::get('/', 'Settings@edit')->name('edit');
        Route::post('/', 'Settings@update')->name('update');
        Route::post('get', 'Settings@get')->name('get');
        Route::delete('delete', 'Settings@destroy')->name('delete');
    });
});
