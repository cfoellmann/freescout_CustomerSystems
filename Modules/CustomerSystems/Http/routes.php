<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => ['web', 'auth'],
    'prefix' => \Helper::getSubdirectory(),
    'namespace' => 'Modules\\CustomerSystems\\Http\\Controllers',
], function () {
    Route::get('/customer-systems/conversation/{id}', 'SidebarController@show')
        ->where('id', '[0-9]+')->name('customersystems.show');
    Route::get('/customer-systems/conversation/{id}/orders/{customerId}', 'OrdersController@show')
        ->where('id', '[0-9]+')->where('customerId', '[a-fA-F0-9]{1,32}')->name('customersystems.orders');
    Route::get('/mailbox/customer-systems/{id}', 'SettingsController@show')
        ->where('id', '[0-9]+')->name('mailboxes.customersystems');
    Route::post('/mailbox/customer-systems/{id}', 'SettingsController@save')
        ->where('id', '[0-9]+')->name('mailboxes.customersystems.save');
    Route::post('/mailbox/customer-systems/{id}/test', 'SettingsController@test')
        ->middleware('throttle:10,1')->where('id', '[0-9]+')->name('mailboxes.customersystems.test');
});
