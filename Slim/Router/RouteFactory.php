<?php
namespace Slim\Router;

use Aura\Router\RouteFactory as AuraRouteFactory;

class RouteFactory extends AuraRouteFactory
{
    public function __construct($class = 'Slim\Router\Route')
    {
        parent::__construct($class);
    }
}
