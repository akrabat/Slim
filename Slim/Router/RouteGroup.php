<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim\Router;

class RouteGroup
{
    protected $name;
    protected $middleware = [];

    public function __construct($name, $middleware = null)
    {
        $this->name = $name;
        if ($middleware) {
            if (!is_array($middleware)) {
                $middleware = array($middleware);
            }
            foreach ($middleware as $m) {
                $this->middleware[] = $m;
            }
        }
    }

    public function add($callable)
    {
        $this->middleware[] = $callable;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getMiddleware()
    {
        return $this->middleware;
    }
}
