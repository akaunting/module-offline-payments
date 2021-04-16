<?php

use Illuminate\Support\Facades\Route;

/**
 * 'portal' middleware and 'portal/offline-payments' prefix applied to all routes (including names)
 *
 * @see \App\Providers\Route::register
 */

Route::portal('offline-payments', function () {
    Route::get('invoices/{invoice}', 'Payment@show')->name('invoices.show');
    Route::post('invoices/{invoice}/confirm', 'Payment@confirm')->name('invoices.confirm');
});
