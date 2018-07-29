<?

use \App\Controllers\WorldLocationController;
use \FastRoute\RouteCollector;

require_once './vendor/autoload.php';

$dispatcher = \FastRoute\simpleDispatcher(function (RouteCollector $r) {

    $r->addGroup('/post', function (RouteCollector $r) {
        $postController = new WorldLocationController();

        $r->addRoute('GET', '/list', function ($params) use ($postController) {
            return $postController->getList($params);
        });

        $r->addRoute('GET', '/addOne', function ($params) use ($postController) {
            return $postController->getOne($params);
        });

    });

});

// Fetch method and URI from somewhere
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        // ... 404 Not Found
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        // ... 405 Method Not Allowed
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];

        echo json_encode($handler($routeInfo[2]));
        // ... call $handler with $vars
        break;
}