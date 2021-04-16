<?php

use Illuminate\Support\Facades\Route;

/**
 * 'signed' middleware and 'signed/offline-payments' prefix applied to all routes (including names)
 *
 * @see \App\Providers\Route::register
 */

Route::signed('offline-payments', function () {
    Route::get('invoices/{invoice}', 'Payment@show')->name('invoices.show');
    Route::post('invoices/{invoice}/confirm', 'Payment@confirm')->name('invoices.confirm');
});
