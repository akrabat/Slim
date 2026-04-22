<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Tests\Handlers;

use Exception;
use PHPUnit\Framework\TestCase;
use Slim\Handlers\PhpError;
use Slim\Http\Request;
use Slim\Http\Response;

class PhpErrorTest extends TestCase
{
    public static function phpErrorProvider()
    {
        return [
            ['application/json', 'application/json', '{'],
            ['application/vnd.api+json', 'application/json', '{'],
            ['application/xml', 'application/xml', '<error>'],
            ['application/hal+xml', 'application/xml', '<error>'],
            ['text/xml', 'text/xml', '<error>'],
            ['text/html', 'text/html', '<html>'],
        ];
    }

    /**
     * Test invalid method returns the correct code and content type
     *
     * @dataProvider phpErrorProvider
     */
    public function testPhpError($acceptHeader, $contentType, $startOfBody)
    {
        $error = new PhpError();

        /** @var Response $res */
        $res = $error->__invoke($this->getRequest('GET', $acceptHeader), new Response(), new Exception());

        $this->assertSame(500, $res->getStatusCode());
        $this->assertSame($contentType, $res->getHeaderLine('Content-Type'));
        $this->assertEquals(0, strpos((string)$res->getBody(), $startOfBody));
    }

    /**
     * Test invalid method returns the correct code and content type
     *
     * @dataProvider phpErrorProvider
     */
    public function testPhpErrorDisplayDetails($acceptHeader, $contentType, $startOfBody)
    {
        $error = new PhpError(true);

        $exception = new Exception('Oops', 1, new Exception('Opps before'));

        /** @var Response $res */
        $res = $error->__invoke($this->getRequest('GET', $acceptHeader), new Response(), $exception);

        $this->assertSame(500, $res->getStatusCode());
        $this->assertSame($contentType, $res->getHeaderLine('Content-Type'));
        $this->assertEquals(0, strpos((string)$res->getBody(), $startOfBody));
    }

    public function testNotFoundContentType()
    {
        $errorMock = $this->getMockBuilder(PhpError::class)->onlyMethods(['determineContentType'])->getMock();
        $errorMock->method('determineContentType')
            ->will($this->returnValue('unknown/type'));

        $req = $this->getMockBuilder('Slim\Http\Request')->disableOriginalConstructor()->getMock();

        $this->expectException('\UnexpectedValueException');
        $errorMock->__invoke($req, new Response(), new Exception());
    }

    /**
     * @param string $method
     *
     * @return PHPUnit\Framework\MockObject\MockObject|Request
     */
    protected function getRequest($method, $acceptHeader)
    {
        $req = $this->getMockBuilder('Slim\Http\Request')->disableOriginalConstructor()->getMock();
        $req->expects($this->once())->method('getHeaderLine')->will($this->returnValue($acceptHeader));

        return $req;
    }
}
