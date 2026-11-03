<?php

namespace { require __DIR__.'/connections.php'; }

namespace Illuminate\Routing { class Controller {} }
namespace Illuminate\Http {
    class Request {
        private $input;
        public function __construct(array $input) { $this->input = $input; }
        public function all() { return $this->input; }
        public function only(array $keys) { return array_intersect_key($this->input, array_flip($keys)); }
    }
}
namespace App {
    class Mailbox extends \TestMailbox {
        public static $records = [];
        public static function findOrFail($id) { return self::$records[$id]; }
    }
    class Conversation {
        public static $record;
        public static function findOrFail($id) { return self::$record; }
    }
}
namespace {
    use Modules\CustomerSystems\Http\Controllers\SettingsController;
    use Modules\CustomerSystems\Http\Controllers\SidebarController;
    use Modules\CustomerSystems\Services\ConnectionSettings;
    use Illuminate\Http\Request;

    require __DIR__.'/../Modules/CustomerSystems/Http/Controllers/SettingsController.php';
    require __DIR__.'/../Modules/CustomerSystems/Http/Controllers/SidebarController.php';
    require __DIR__.'/../Modules/CustomerSystems/Http/Controllers/OrdersController.php';

    class TestUser {
        public $admin = false;
        public $canView = false;
        public function isAdmin() { return $this->admin; }
        public function can($ability, $record) { return $this->canView; }
    }
    class TestResponse {
        public $view;
        public $data = [];
        public $old = [];
        public $errors = [];
        public function header($key, $value) { return $this; }
        public function route($name, $params) { return $this; }
        public function withErrors($errors) { $this->errors = $errors; return $this; }
        public function withInput($input) { $this->old = $input; return $this; }
        public function with($key, $value) { return $this; }
        public function view($name, $data) { $this->view = $name; $this->data = $data; return $this; }
        public function json($data, $status) { $this->data = $data; return $this; }
    }
    function auth() { return new class { public function check() { return true; } public function user() { return $GLOBALS['test_user']; } }; }
    function abort_unless($condition, $status) { if (!$condition) { throw new RuntimeException('http_'.$status); } }
    function response() { return new TestResponse(); }
    function redirect() { return new TestResponse(); }
    $GLOBALS['test_user'] = new TestUser();
    $controller = new SettingsController();
    foreach (['show', 'save', 'test'] as $method) {
        failsWith(function () use ($controller, $method) { $controller->$method(1, new Request([])); }, 'http_403');
    }
    $GLOBALS['test_user']->admin = true;
    \App\Mailbox::$records[1] = new \App\Mailbox();
    \App\Mailbox::$records[1]->id = 1;
    $settings['enabled'] = true;
    (new ConnectionSettings())->save(\App\Mailbox::$records[1], $settings);
    $response = $controller->show(1);
    check($response->data['hasSecret'] && !isset($response->data['settings']['api_key']), 'Settings GET leaked key');
    $invalid = array_merge($settings, ['url' => 'http://bad.example.com', 'api_key' => 'new-secret']);
    $response = $controller->save(1, new Request($invalid));
    check(count($response->errors) === 1 && !array_key_exists('api_key', $response->old), 'Validation flashed key');
    $before = \App\Mailbox::$records[1]->meta;
    $response = $controller->test(1, new Request($invalid));
    check(!$response->data['ok'] && \App\Mailbox::$records[1]->meta === $before, 'Connection test changed persisted settings');
    $controller->save(1, new Request(array_merge($settings, ['enabled' => '0', 'api_key' => ''])));
    check(!(new ConnectionSettings())->load(\App\Mailbox::$records[1])['enabled'], 'Settings POST failed to disable');

    \App\Conversation::$record = (object) ['id' => 101, 'mailbox_id' => 1, 'customer_email' => 'test@example.com', 'mailbox' => \App\Mailbox::$records[1]];
    $sidebar = new SidebarController();
    failsWith(function () use ($sidebar) { $sidebar->show(1); }, 'http_403');
    $orderController = new \Modules\CustomerSystems\Http\Controllers\OrdersController();
    failsWith(function () use ($orderController) { $orderController->show(1, '0123456789abcdef0123456789abcdef'); }, 'http_403');
    $GLOBALS['test_user']->canView = true;
    // Disabled mailboxes must not contact either API.
    // Supply a log adapter because FreeScout logs the controlled error code.
    $response = $sidebar->show(1);
    check($response->data['error'] === 'disabled' && $response->data['customers'] === [], 'Disabled mailbox performed lookup');
    $orderResponse = $orderController->show(1, '0123456789abcdef0123456789abcdef');
    check($orderResponse->data['error'] === 'disabled' && $orderResponse->data['orders'] === [], 'Disabled mailbox performed order lookup');
    check(isset($controller->test(1, new Request($invalid))->data['reference']), 'Failed test must expose a log reference');
    echo 'OK: '.$passed." total checks including controller authorization passed\n";
}

namespace {
    class Log { public static function warning($message, $context) {} }
}
