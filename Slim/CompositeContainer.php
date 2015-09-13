<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\NotFoundException;
use Slim\Handlers\Error;
use Slim\Handlers\NotFound;
use Slim\Handlers\NotAllowed;
use Slim\Handlers\Strategies\RequestResponse;
use Slim\Http\Environment;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\Http\EnvironmentInterface;
use Slim\Interfaces\InvocationStrategyInterface;
use Slim\Interfaces\RouterInterface;

/**
 * Compose a ContainerInterop container and supply the default services required by
 * Slim if they are not in the container.
 *
 * Slim\App expects these service keys configured and ready for use, so they are instantiated
 * in this class if they are not in the container:
 *
 *  - settings: an array or instance of \ArrayAccess
 *  - environment: an instance of \Slim\Interfaces\Http\EnvironmentInterface
 *  - request: an instance of \Psr\Http\Message\ServerRequestInterface
 *  - response: an instance of \Psr\Http\Message\ResponseInterface
 *  - router: an instance of \Slim\Interfaces\RouterInterface
 *  - foundHandler: an instance of \Slim\Interfaces\InvocationStrategyInterface
 *  - errorHandler: a callable with the signature: function($request, $response, $exception)
 *  - notFoundHandler: a callable with the signature: function($request, $response)
 *  - notAllowedHandler: a callable with the signature: function($request, $response, $allowedHttpMethods)
 *  - callableResolver: an instance of \Slim\Interfaces\CallableResolverInterface
 */
final class CompositeContainer implements ContainerInterface
{
    /**
     * @var ContainerInterface
     */
    private $originalContainer;

    /**
     * Default settings
     *
     * @var array
     */
    private $defaultSettings = [
        'httpVersion' => '1.1',
        'responseChunkSize' => 4096,
        'outputBuffering' => 'append',
        'determineRouteBeforeAppMiddleware' => false,
    ];

    /**
     * @var EnvironmentInterface
     */
    private $environment;

    /**
     * @var ServerRequestInterface
     */

    private $request;
    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var Error
     */
    private $foundHandler;

    /**
     * @var RouterInterface
     */
    private $errorHandler;

    /**
     * @var NotFound
     */
    private $notFoundHandler;

    /**
     * @var NotAllowed
     */
    private $notAllowedHandler;

    /**
     * @var CallableResolverInterface
     */
    private $callableResolver;

    /**
     * Create new container
     *
     * @param array $values The parameters or objects.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->originalContainer = $container;
    }

    /**
     * Forward any unknown calls to the container.
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        return call_user_func_array([$this->originalContainer, $method], $arguments);
    }

    /********************************************************************************
     * Methods to create a required service
     *******************************************************************************/

    /**
     * Is this service required by Slim?
     *
     * @param  string  $id service name
     * @return boolean
     */
    protected function isRequiredService($id)
    {
        $services = [
            'environment',
            'request',
            'response',
            'router',
            'foundHandler',
            'errorHandler',
            'notFoundHandler',
            'notAllowedHandler',
            'callableResolver',
        ];

        return in_array($id, $services);
    }

    /**
     * Settings is an array. Always re-merge from the container as we don't know
     * if it's been changed.
     *
     * @return array
     */
    protected function getSettings()
    {
        // never store settings to a local variable
        $userSettings = $this->originalContainer->has('settings') ? $this->originalContainer->get('settings') : [];
        return array_merge($this->defaultSettings, $userSettings);
    }

    /**
     * Create a required service. All services are shared, so they are only instantiated
     * once and then that instance is returned.
     *
     * @param  string $id Service name

     * @throws NotFoundException  No entry was found for this identifier.
     * @throws ContainerException Error while retrieving the entry.

     * @return mixed
     */
    protected function createRequiredService($id)
    {
        switch ($id) {
            case 'environment':
                return new Environment($_SERVER);

            case 'request':
                return Request::createFromEnvironment($this->get('environment'));

            case 'response':
                $settings = $this->get('settings');

                $headers = new Headers(['Content-Type' => 'text/html']);
                $response = new Response(200, $headers);
                return $response->withProtocolVersion($settings['httpVersion']);

            case 'router':
                return new Router();

            case 'foundHandler':
                return new RequestResponse();

            case 'errorHandler':
                return new Error();

            case 'notFoundHandler':
                return new NotFound();

            case 'notAllowedHandler':
                return new NotAllowed();

            case 'callableResolver':
                return new CallableResolver($this);
        }

        throw new NotFoundException(sprintf('Identifier "%s" is not defined.', $id));
    }

    /********************************************************************************
     * Methods to satisfy Interop\Container\ContainerInterface
     *******************************************************************************/

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws NotFoundException  No entry was found for this identifier.
     * @throws ContainerException Error while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get($id)
    {
        // settings are not a shared service, so are handled separately
        if ($id === 'settings') {
            return $this->getSettings();
        }

        // If we've created a required service, then re-use it, so we are consistent
        if ($this->isRequiredService($id) && null !== $this->$id) {
            return $this->$id;
        }

        // originalContainer takes precedence for creating a a new service
        if ($this->originalContainer->has($id)) {
            return $this->originalContainer->get($id);
        }

        // create a required service
        $this->$id = $this->createRequiredService($id);
        return $this->$id;
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return boolean
     */
    public function has($id)
    {
        if ($this->isRequiredService($id)) {
            return true;
        }

        return $this->originalContainer->has($id);
    }

    /********************************************************************************
     * Getters and setters
     *******************************************************************************/

    /**
     * Getter for originalContainer
     *
     * @return mixed
     */
    public function getOriginalContainer()
    {
        return $this->originalContainer;
    }
    
    /**
     * Setter for originalContainer
     *
     * @param mixed $originalContainer Value to set
     */
    public function setOriginalContainer($originalContainer)
    {
        $this->originalContainer = $originalContainer;
    }
}
