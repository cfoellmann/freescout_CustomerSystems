<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => ['web', 'auth'],
    'prefix' => \Helper::getSubdirectory(),
    'namespace' => 'Modules\\CustomerSystems\\Http\\Controllers',
], function () {
    Route::get('/customer-systems/conversation/{id}', 'SidebarController@show')
        ->where('id', '[0-9]+')->name('customersystems.show');
});
