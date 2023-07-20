<?php
require_once '../server/vendor/autoload.php';

ini_set('display_errors', 0);
$logger = new Monolog\Logger('admin_panel', [
    (new Monolog\Handler\StreamHandler('../server/adminLogError.log', Monolog\Logger::ERROR))->setFormatter(new Monolog\Formatter\LineFormatter(null, null, true)),
    (new Monolog\Handler\StreamHandler('php://output', Monolog\Logger::ERROR))->setFormatter(new Monolog\Formatter\JsonFormatter()),
]);
Monolog\ErrorHandler::register($logger);

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();
$dotenv->required(['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD', 'DB_CHARSET', 'KEY']);

$factory = new GuzzleHttp\Psr7\HttpFactory();
$request = GuzzleHttp\Psr7\ServerRequest::fromGlobals();

$routes = new FastRoute\RouteCollector(new FastRoute\RouteParser\Std(), new FastRoute\DataGenerator\GroupCountBased());

$routes->get('/', [AdminPanel\Endpoints\HomePageController::class, 'getHtmlTemplate', false]);
$routes->get('/welcome', [AdminPanel\Endpoints\HomePageController::class, 'getHtmlTemplate', false]);
$routes->get('/auth/{secret:[^/]+}', [AdminPanel\Endpoints\HomePageController::class, 'getHtmlTemplate', false]);
$routes->get('/goods/new', [AdminPanel\Endpoints\HomePageController::class, 'getHtmlTemplate']);

$routes->post('/auth', [AdminPanel\Endpoints\AuthController::class, 'auth', false]);

$routes->post('/refresh', [AdminPanel\Endpoints\AuthController::class, 'refresh', false]);

$routes->get('/firststart/{step:\d+}', [AdminPanel\Endpoints\FirstStartController::class, 'getState', false]);
$routes->post('/firststart', [AdminPanel\Endpoints\FirstStartController::class, 'setState', false]);

$routes->post('/files/upload', [AdminPanel\Endpoints\FilesController::class, 'upload']);

$routes->get('/dashboard', [AdminPanel\Endpoints\DashboardController::class, 'getInfo']);

$routes->get('/categories', [AdminPanel\Endpoints\CategoriesController::class, 'get']);
$routes->post('/categories', [AdminPanel\Endpoints\CategoriesController::class, 'add']);
$routes->put('/categories/edit', [AdminPanel\Endpoints\CategoriesController::class, 'edit']);
$routes->put('/categories/order', [AdminPanel\Endpoints\CategoriesController::class, 'order']);
$routes->delete('/categories', [AdminPanel\Endpoints\CategoriesController::class, 'delete']);

$routes->get('/goods', [AdminPanel\Endpoints\GoodsController::class, 'get']);
$routes->get('/goods/{id:\d+}', [AdminPanel\Endpoints\GoodsController::class, 'getProduct']);
$routes->post('/goods', [AdminPanel\Endpoints\GoodsController::class, 'add']);
$routes->put('/goods', [AdminPanel\Endpoints\GoodsController::class, 'edit']);
$routes->delete('/goods', [AdminPanel\Endpoints\GoodsController::class, 'delete']);

$routes->get('/purchases', [AdminPanel\Endpoints\PurchasesController::class, 'get']);
$routes->get('/purchases/{id:\d+}', [AdminPanel\Endpoints\PurchasesController::class, 'getPurchase']);
$routes->get('/purchases/payment/{id:\d+}', [AdminPanel\Endpoints\PurchasesController::class, 'getPayment']);

$routes->get('/users', [AdminPanel\Endpoints\UsersController::class, 'get']);
$routes->post('/users/mailing', [AdminPanel\Endpoints\UsersController::class, 'mailing']);

$routes->get('/settings', [AdminPanel\Endpoints\SettingsController::class, 'get']);
$routes->post('/settings', [AdminPanel\Endpoints\SettingsController::class, 'save']);
$routes->get('/settings/admins', [AdminPanel\Endpoints\SettingsController::class, 'getAdmins']);
$routes->post('/settings/admins', [AdminPanel\Endpoints\SettingsController::class, 'addAdmin']);
$routes->delete('/settings/admins', [AdminPanel\Endpoints\SettingsController::class, 'delAdmin']);

$dispatcherMiddleware = new mindplay\middleman\Dispatcher(
    [
        new AdminPanel\Middleware\RouteMiddleware($routes, $factory),
        new AdminPanel\Middleware\FirstStartMiddleware($factory),
        new AdminPanel\Middleware\OverrideHandlerMiddleware(),
        new AdminPanel\Middleware\AuthMiddleware($factory),
        new AdminPanel\Middleware\RequestHandlerMiddleware($factory),
    ]
);

$response = $dispatcherMiddleware->handle($request);

(new Laminas\HttpHandlerRunner\Emitter\SapiEmitter())->emit($response);
