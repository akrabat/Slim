<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim\Router;

use InvalidArgumentException;
use RuntimeException;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\RouterInterface;
use Aura\Router\Router as AuraRouter;
use Aura\Router\RouteCollection as AuraRouteCollection;
use Aura\Router\Generator as AuraGenerator;

/**
 * Router
 *
 * This class organizes Slim application route objects. It is responsible
 * for registering route objects, assigning names to route objects,
 * finding routes that match the current HTTP request, and creating
 * URLs for a named route.
 */
class Router implements RouterInterface
{
    const MATCH_FOUND              = 'MATCH_FOUND';
    const MATCH_METHOD_NOT_ALLOWED = 'MATCH_METHOD_NOT_ALLOWED';
    const MATCH_NOT_ACCEPTABLE     = 'MATCH_NOT_ACCEPTABLE';
    const MATCH_UNKNOWN            = 'MATCH_UNKNOWN';

    /**
     * Router
     * @var \Aura\Router\Router
     */
    protected $router;

    /**
     * Route groups
     *
     * @var array
     */
    protected $routeGroups = [];

    /**
     * Create new router
     */
    public function __construct()
    {
        $this->router = new AuraRouter(
            new AuraRouteCollection(new RouteFactory),
            new AuraGenerator
        );
    }

    /**
     * Add route
     *
     * @param  string[] $methods Array of HTTP methods
     * @param  string   $pattern The route pattern
     * @param  callable $handler The route callable
     * @param  string   $name    Name of route (set to null for anonymous)
     *
     * @return \Slim\Interfaces\RouteInterface
     * @throws InvalidArgumentException if the route pattern isn't a string
     */
    public function map($methods, $pattern, $handler, $name = null)
    {

        if (!is_string($pattern)) {
            throw new InvalidArgumentException('Route pattern must be a string');
        }

        // Prepend group pattern
        $groupMiddleware = [];
        if ($this->routeGroups) {
            list($groupPattern, $groupMiddleware) = $this->processGroups();
            $pattern = $groupPattern . $pattern;
        }

        // Add route
        $route = $this->router->add($name, $pattern, $handler);
        foreach ($methods as $method) {
            $route->addMethod($method);
        }

        foreach ($groupMiddleware as $middleware) {
            $route->add($middleware);
        }

        return $route;
    }

    /**
     * Dispatch router for HTTP request
     *
     * @param  ServerRequestInterface $request The current HTTP request object
     *
     * @return array [result, route, parameters]
     * @link   https://github.com/nikic/FastRoute/blob/master/src/Dispatcher.php
     */
    public function dispatch(ServerRequestInterface $request)
    {
        $path = $request->getUri()->getPath();
        $server = $request->getServerParams();
        
        $route = $this->router->match($path, $server);
        if (!$route) {
            $reason = self::MATCH_UNKNOWN;
            $failure = $this->router->getFailedRoute();
            if ($failure) {
                $params = $failure->params;
                unset($params['action']);

                if ($failure->failedMethod()) {
                    $reason = self::MATCH_METHOD_NOT_ALLOWED;
                }
                if ($failure->failedAccept()) {
                    $reason = self::MATCH_NOT_ACCEPTABLE;
                }
            }
            return [$reason, null, $params];
        }

        $params = $route->params;
        unset($params['action']);

        return [self::MATCH_FOUND, $route, $params];
    }

    /**
     * Process route groups
     *
     * @return array An array with two elements: pattern, middlewareArr
     */
    protected function processGroups()
    {
        $pattern = "";
        $middleware = [];
        foreach ($this->routeGroups as $group) {
            $k = key($group);
            $pattern .= $k;
            if (is_array($group[$k])) {
                $middleware = array_merge($middleware, $group[$k]);
            }
        }
        return [$pattern, $middleware];
    }

    /**
     * Add a route group to the array
     *
     * @param string     $group      The group pattern prefix
     * @param array|null $middleware Optional middleware
     *
     * @return int The index of the new group
     */
    public function pushGroup($group, $middleware = [])
    {
        return array_push($this->routeGroups, [$group => $middleware]);
    }

    /**
     * Removes the last route group from the array
     *
     * @return bool True if successful, else False
     */
    public function popGroup()
    {
        return (array_pop($this->routeGroups) !== null);
    }

    /**
     * Build URL for named route
     *
     * @param  string $name        Route name
     * @param  array  $data        Route URI segments replacement data
     * @param  array  $queryParams Optional query string parameters
     *
     * @return string
     * @throws \RuntimeException         If named route does not exist
     * @throws \InvalidArgumentException If required data not provided
     */
    public function urlFor($name, array $data = [], array $queryParams = [])
    {
        $url = $this->router->generate($name, $data);

        if ($queryParams) {
            $url .= '?' . http_build_query($queryParams);
        }

        return $url;
    }
}
