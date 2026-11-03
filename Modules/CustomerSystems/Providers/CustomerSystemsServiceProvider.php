<?php

namespace Modules\CustomerSystems\Providers;

use Illuminate\Support\ServiceProvider;

class CustomerSystemsServiceProvider extends ServiceProvider
{
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

        // This hook is after the native profile (including its overlapping avatar),
        // before previous conversations and the other sidebar boxes.
        \Eventy::addAction('conversation.before_prev_convs', function ($customer, $conversation, $mailbox) {
            if (auth()->check() && auth()->user()->can('view', $conversation)) {
                echo view('customersystems::sidebar', ['conversation' => $conversation])->render();
            }
        }, -100, 3);
    }
}
