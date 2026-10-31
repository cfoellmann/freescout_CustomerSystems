<?php

namespace Modules\CustomerSystems\Providers;

use Illuminate\Support\ServiceProvider;

class CustomerSystemsServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/config.php', 'customersystems');
    }

    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'customersystems');
        $this->loadRoutesFrom(__DIR__.'/../Http/routes.php');

        \Eventy::addFilter('javascripts', function ($scripts) {
            $scripts[] = \Module::getPublicPath('customersystems').'/js/sidebar.js';
            return $scripts;
        });

        \Eventy::addAction('conversation.after_customer_sidebar', function ($conversation) {
            if (auth()->check() && auth()->user()->can('view', $conversation)) {
                echo view('customersystems::sidebar', ['conversation' => $conversation])->render();
            }
        }, 20, 1);
    }
}
