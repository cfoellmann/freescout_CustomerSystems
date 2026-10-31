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
            $scripts[] = \Module::getPublicPath('customersystems').'/js/settings.js';
            return $scripts;
        });

        \Eventy::addAction('mailboxes.settings.menu', function ($mailbox) {
            if (auth()->check() && auth()->user()->isAdmin()) {
                echo view('customersystems::settings_menu', ['mailbox' => $mailbox])->render();
            }
        }, 35, 1);

        \Eventy::addAction('conversation.after_customer_sidebar', function ($conversation) {
            if (auth()->check() && auth()->user()->can('view', $conversation)) {
                echo view('customersystems::sidebar', ['conversation' => $conversation])->render();
            }
        }, 20, 1);
    }
}
