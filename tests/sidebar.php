<?php

namespace { require __DIR__.'/controllers.php'; }

namespace Illuminate\Support {
    class ServiceProvider {
        protected function loadViewsFrom($path, $name) {}
        protected function loadRoutesFrom($path) {}
    }
}

namespace {
    class Eventy {
        public static $actions = [];
        public static function addFilter($name, $callback) {}
        public static function addAction($name, $callback, $priority = 20, $arguments = 1) {
            self::$actions[$name][] = compact('callback', 'priority', 'arguments');
        }
        public static function emit($name, array $arguments) {
            $listeners = self::$actions[$name] ?? [];
            usort($listeners, function ($left, $right) { return $left['priority'] <=> $right['priority']; });
            foreach ($listeners as $listener) { call_user_func_array($listener['callback'], array_slice($arguments, 0, $listener['arguments'])); }
        }
    }
    function view($name, $data) {
        return new class($name) {
            private $name;
            public function __construct($name) { $this->name = $name; }
            public function render() { return '<div data-rendered-view="'.$this->name.'"></div>'; }
        };
    }
    require __DIR__.'/../Modules/CustomerSystems/Providers/CustomerSystemsServiceProvider.php';
    (new \Modules\CustomerSystems\Providers\CustomerSystemsServiceProvider())->boot();
    Eventy::addAction('conversation.before_prev_convs', function () { echo '<div id="followers"></div>'; }, 0, 3);
    ob_start();
    // Native FreeScout profile markup: this is also a conv-sidebar-block and its avatar
    // extends upward into conv-customer-header. It must stay ahead of the module.
    echo '<div class="conv-customer-header"></div><div class="conv-customer-block conv-sidebar-block" id="native-profile"><div class="customer-photo-container"></div></div>';
    Eventy::emit('conversation.before_prev_convs', [null, \App\Conversation::$record, \App\Mailbox::$records[1]]);
    echo '<div id="previous-conversations"></div>';
    Eventy::emit('conversation.after_customer_sidebar', [\App\Conversation::$record]);
    $html = ob_get_clean();
    $module = strpos($html, 'data-rendered-view="customersystems::sidebar"');
    check($module !== false && $module > strpos($html, 'id="native-profile"'), 'Module must follow native customer profile');
    check($module < strpos($html, 'id="followers"') && $module < strpos($html, 'id="previous-conversations"'), 'Module must be the first box after the profile');
    check(substr_count($html, 'data-rendered-view="customersystems::sidebar"') === 1, 'Sidebar must not be inserted twice');
    $GLOBALS['test_user']->canView = false;
    ob_start(); Eventy::emit('conversation.before_prev_convs', [null, \App\Conversation::$record, \App\Mailbox::$records[1]]); $denied = ob_get_clean();
    check(strpos($denied, 'customersystems::sidebar') === false, 'Unauthorized ticket must not render module');
    echo 'OK: '.$passed." checks including native sidebar hook placement passed\n";
}
