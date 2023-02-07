<?php

// Client View Groups
Route::group(['middleware' => ['web'], 'namespace' => '\Acelle\Vnpay\Controllers'], function () {
    Route::match(['get', 'post'], 'plugins/acelle/vnpay/{invoice_uid}/checkout/check', 'VnpayController@checkoutCheck');
    Route::match(['get', 'post'], 'plugins/acelle/vnpay/{invoice_uid}/checkout', 'VnpayController@checkout');
});
