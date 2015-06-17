<?php
namespace Slim\Router;

use Aura\Router\Route as AuraRoute;
use Slim\MiddlewareAwareTrait;
use Slim\CallableResolverAwareTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class Route extends AuraRoute
{
    use CallableResolverAwareTrait;
    use MiddlewareAwareTrait {
        add as addMiddleware;
    }

    /**
     * Add middleware
     *
     * This method prepends new middleware to the route's middleware stack.
     *
     * @param  mixed $callable The callback routine
     *
     * @return RouteInterface
     */
    public function add($callable)
    {
        $callable = $this->resolveCallable($callable);
        if ($callable instanceof Closure) {
            $callable = $callable->bindTo($this->container);
        }

        return $this->addMiddleware($callable);
    }


    /********************************************************************************
    * Route Runner
    *******************************************************************************/

    /**
     * Run route
     *
     * This method traverses the middleware stack, including the route's callable
     * and captures the resultant HTTP response object. It then sends the response
     * back to the Application.
     */
    public function run(ServerRequestInterface $request, ResponseInterface $response)
    {
        // Traverse middleware stack and fetch updated response
        return $this->callMiddlewareStack($request, $response);
    }

    /**
     * Dispatch route callable against current Request and Response objects
     *
     * This method invokes the route object's callable. If middleware is
     * registered for the route, each callable middleware is invoked in
     * the order specified.
     *
     * @param ServerRequestInterface $request  The current Request object
     * @param ResponseInterface      $response The current Response object
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Exception  if the route callable throws an exception
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        // invoke route callable
        try {
            ob_start();
            $function = $this->params['action'];
            $newResponse = $function($request, $response, $request->getAttributes());
            $output = ob_get_clean();
        } catch (Exception $e) {
            ob_end_clean();
            throw $e;
        }

        // if route callback returns a ResponseInterface, then use it
        if ($newResponse instanceof ResponseInterface) {
            $response = $newResponse;
        }

        // if route callback retuns a string, then append it to the response
        if (is_string($newResponse)) {
            $response->getBody()->write($newResponse);
        }

        // append output buffer content if there is any
        if ($output) {
            $response->getBody()->write($output);
        }

        return $response;
    }
}
