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
namespace Slim\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class OutputBuffer
{
    /**
     * Invoke output buffer middleware
     *
     * @param  RequestInterface  $request  A PSR7 request object
     * @param  ResponseInterface $response A PSR7 response object
     * @param  callable          $next     The next middleware callable
     *
     * @return ResponseInterface           A PSR7 response object
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next)
    {
        try {
            ob_start();
            $newReponse = $next($request, $response);
            $output = ob_get_clean();
        } catch (\Exception $e) {
            ob_end_clean();
            throw $e;
        }

        // if next middleware callback returns a ResponseInterface, then use it
        if ($newReponse instanceof ResponseInterface) {
            $response = $newReponse;
        }

        // if next middleware callback retuns a string, then append it to the response
        if (is_string($newReponse)) {
            $response->getBody()->write($newReponse);
        }

        // append output buffer content if there is any
        if ($output) {
            $response->getBody()->write($output);
        }
        return $response;
    }
}
