<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     2.3.5
 *
 * MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

use \Slim\App;
use \Slim\Http\Environment;
use \Slim\Http\Uri;
use \Slim\Http\Body;
use \Slim\Http\Headers;
use \Slim\Http\Request;
use \Slim\Http\Response;

class OutputBufferTest extends PHPUnit_Framework_TestCase
{
    /**
     * Ensure that anything echo'd in a route callable is added to the response
     * object that is returned by run().
     */
    public function testOutputBufferCollectsPrintedOutput()
    {
        $app = new App();
        $app->add(new \Slim\Middleware\OutputBuffer);
        $app->get('/', function ($request, $response) {
            print "foo";
            return $response->withStatus(201);
        });

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $response = new Response();

        // Run app's middleware stack
        $newResponse = $app->callMiddlewareStack($request, $response);

        $this->assertEquals('foo', (string)$newResponse->getBody());
        $this->assertEquals(201, $newResponse->getStatusCode());
    }

    /**
     * Ensure that if a string is returned by a route callable, then it is
     * added to the response object that is returned by run().
     */
    public function testOutputBufferConvertsAReturnedStringToAResponse()
    {
        $app = new App();
        $app->add(new \Slim\Middleware\OutputBuffer);
        $app->get('/', function ($request, $response) {
            return "foo";
        });

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $response = new Response();

        // Run app's middleware stack
        $newResponse = $app->callMiddlewareStack($request, $response);

        $this->assertEquals('foo', (string)$newResponse->getBody());
    }
}
