<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Tests\Http;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Slim\Http\Environment;
use Slim\Http\Uri;

class UriTest extends TestCase
{
    protected $uri;

    public function uriFactory()
    {
        $scheme = 'https';
        $host = 'example.com';
        $port = 443;
        $path = '/foo/bar';
        $query = 'abc=123';
        $fragment = 'section3';
        $user = 'josh';
        $password = 'sekrit';

        return new Uri($scheme, $host, $port, $path, $query, $fragment, $user, $password);
    }

    public function testGetScheme()
    {
        $this->assertEquals('https', $this->uriFactory()->getScheme());
    }

    public function testWithScheme()
    {
        $uri = $this->uriFactory()->withScheme('http');

        $this->assertSame('http', $uri->getScheme());
    }

    public function testWithSchemeRemovesSuffix()
    {
        $uri = $this->uriFactory()->withScheme('http://');

        $this->assertSame('http', $uri->getScheme());
    }

    public function testWithSchemeEmpty()
    {
        $uri = $this->uriFactory()->withScheme('');

        $this->assertSame('', $uri->getScheme());
    }

    public function testWithSchemeInvalid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Uri scheme must be one of: "", "https", "http"');
        $this->uriFactory()->withScheme('ftp');
    }

    public function testWithSchemeInvalidType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Uri scheme must be a string');
        $this->uriFactory()->withScheme([]);
    }

    public function testGetAuthorityWithUsernameAndPassword()
    {
        $this->assertEquals('josh:sekrit@example.com', $this->uriFactory()->getAuthority());
    }

    public function testGetAuthorityWithUsername()
    {
        $scheme = 'https';
        $user = 'josh';
        $password = '';
        $host = 'example.com';
        $path = '/foo/bar';
        $port = 443;
        $query = 'abc=123';
        $fragment = 'section3';
        $uri = new Uri($scheme, $host, $port, $path, $query, $fragment, $user, $password);

        $this->assertEquals('josh@example.com', $uri->getAuthority());
    }

    public function testGetAuthority()
    {
        $scheme = 'https';
        $user = '';
        $password = '';
        $host = 'example.com';
        $path = '/foo/bar';
        $port = 443;
        $query = 'abc=123';
        $fragment = 'section3';
        $uri = new Uri($scheme, $host, $port, $path, $query, $fragment, $user, $password);

        $this->assertEquals('example.com', $uri->getAuthority());
    }

    public function testGetAuthorityWithNonStandardPort()
    {
        $scheme = 'https';
        $user = '';
        $password = '';
        $host = 'example.com';
        $path = '/foo/bar';
        $port = 400;
        $query = 'abc=123';
        $fragment = 'section3';
        $uri = new Uri($scheme, $host, $port, $path, $query, $fragment, $user, $password);

        $this->assertEquals('example.com:400', $uri->getAuthority());
    }

    public function testGetUserInfoWithUsernameAndPassword()
    {
        $scheme = 'https';
        $user = 'josh';
        $password = 'sekrit';
        $host = 'example.com';
        $path = '/foo/bar';
        $port = 443;
        $query = 'abc=123';
        $fragment = 'section3';
        $uri = new Uri($scheme, $host, $port, $path, $query, $fragment, $user, $password);

        $this->assertEquals('josh:sekrit', $uri->getUserInfo());
    }

    public function testGetUserInfoWithUsername()
    {
        $scheme = 'https';
        $user = 'josh';
        $password = '';
        $host = 'example.com';
        $path = '/foo/bar';
        $port = 443;
        $query = 'abc=123';
        $fragment = 'section3';
        $uri = new Uri($scheme, $host, $port, $path, $query, $fragment, $user, $password);

        $this->assertEquals('josh', $uri->getUserInfo());
    }

    public function testGetUserInfoNone()
    {
        $scheme = 'https';
        $user = '';
        $password = '';
        $host = 'example.com';
        $path = '/foo/bar';
        $port = 443;
        $query = 'abc=123';
        $fragment = 'section3';
        $uri = new Uri($scheme, $host, $port, $path, $query, $fragment, $user, $password);

        $this->assertEquals('', $uri->getUserInfo());
    }

    public function testGetUserInfoWithUsernameAndPasswordEncodesCorrectly()
    {
        $uri = Uri::createFromString('https://bob%40example.com:pass%3Aword@example.com:443/foo/bar?abc=123#section3');

        $this->assertEquals('bob%40example.com:pass%3Aword', $uri->getUserInfo());
    }

    public function testWithUserInfo()
    {
        $uri = $this->uriFactory()->withUserInfo('bob', 'pass');

        $this->assertSame('bob:pass', $uri->getUserInfo());
    }

    public function testWithUserInfoEncodesCorrectly()
    {
        $uri = $this->uriFactory()->withUserInfo('bob@example.com', 'pass:word');

        $this->assertSame('bob%40example.com:pass%3Aword', $uri->getUserInfo());
    }

    public function testWithUserInfoRemovesPassword()
    {
        $uri = $this->uriFactory()->withUserInfo('bob');

        $this->assertSame('bob', $uri->getUserInfo());
    }

    public function testWithUserInfoRemovesInfo()
    {
        $uri = $this->uriFactory()->withUserInfo('bob', 'password');

        $uri = $uri->withUserInfo('');
        $this->assertSame('', $uri->getUserInfo());
    }


    public function testGetHost()
    {
        $this->assertEquals('example.com', $this->uriFactory()->getHost());
    }

    public function testWithHost()
    {
        $uri = $this->uriFactory()->withHost('slimframework.com');

        $this->assertSame('slimframework.com', $uri->getHost());
    }

    public function testGetPortWithSchemeAndNonDefaultPort()
    {
        $uri = new Uri('https', 'www.example.com', 4000);

        $this->assertEquals(4000, $uri->getPort());
    }

    public function testGetPortWithSchemeAndDefaultPort()
    {
        $uriHttp = new Uri('http', 'www.example.com', 80);
        $uriHttps = new Uri('https', 'www.example.com', 443);

        $this->assertNull($uriHttp->getPort());
        $this->assertNull($uriHttps->getPort());
    }

    public function testGetPortWithoutSchemeAndPort()
    {
        $uri = new Uri('', 'www.example.com');

        $this->assertNull($uri->getPort());
    }

    public function testGetPortWithSchemeWithoutPort()
    {
        $uri = new Uri('http', 'www.example.com');

        $this->assertNull($uri->getPort());
    }

    public function testWithPort()
    {
        $uri = $this->uriFactory()->withPort(8000);

        $this->assertSame(8000, $uri->getPort());
    }

    public function testWithPortNull()
    {
        $uri = $this->uriFactory()->withPort(null);

        $this->assertNull($uri->getPort());
    }

    public function testWithPortInvalidInt()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->uriFactory()->withPort(70000);
    }

    public function testWithPortInvalidString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->uriFactory()->withPort('Foo');
    }

    public function testGetBasePathNone()
    {
        $this->assertEquals('', $this->uriFactory()->getBasePath());
    }

    public function testWithBasePath()
    {
        $uri = $this->uriFactory()->withBasePath('/base');

        $this->assertSame('/base', $uri->getBasePath());
    }

    public function testWithBasePathInvalidType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Uri path must be a string');
        $this->uriFactory()->withBasePath(['foo']);
    }

    public function testWithBasePathAddsPrefix()
    {
        $uri = $this->uriFactory()->withBasePath('base');

        $this->assertSame('/base', $uri->getBasePath());
    }

    public function testWithBasePathIgnoresSlash()
    {
        $uri = $this->uriFactory()->withBasePath('/');

        $this->assertSame('', $uri->getBasePath());
    }

    public function testGetPath()
    {
        $this->assertEquals('/foo/bar', $this->uriFactory()->getPath());
    }

    public function testWithPath()
    {
        $uri = $this->uriFactory()->withPath('/new');

        $this->assertSame('/new', $uri->getPath());
    }

    public function testWithPathWithoutPrefix()
    {
        $uri = $this->uriFactory()->withPath('new');

        $this->assertSame('new', $uri->getPath());
    }

    public function testWithPathEmptyValue()
    {
        $uri = $this->uriFactory()->withPath('');

        $this->assertSame('', $uri->getPath());
    }

    public function testWithPathUrlEncodesInput()
    {
        $uri = $this->uriFactory()->withPath('/includes?/new');

        $this->assertSame('/includes%3F/new', $uri->getPath());
    }

    public function testWithPathDoesNotDoubleEncodeInput()
    {
        $uri = $this->uriFactory()->withPath('/include%25s/new');

        $this->assertSame('/include%25s/new', $uri->getPath());
    }

    public function testWithPathInvalidType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Uri path must be a string');
        $this->uriFactory()->withPath(['foo']);
    }

    public function testGetQuery()
    {
        $this->assertEquals('abc=123', $this->uriFactory()->getQuery());
    }

    public function testWithQuery()
    {
        $uri = $this->uriFactory()->withQuery('xyz=123');

        $this->assertSame('xyz=123', $uri->getQuery());
    }

    public function testWithQueryRemovesPrefix()
    {
        $uri = $this->uriFactory()->withQuery('?xyz=123');

        $this->assertSame('xyz=123', $uri->getQuery());
    }

    public function testWithQueryEmpty()
    {
        $uri = $this->uriFactory()->withQuery('');

        $this->assertSame('', $uri->getQuery());
    }

    public function testFilterQuery()
    {
        $uri = $this->uriFactory()->withQuery('?foobar=%match');

        $this->assertSame('foobar=%25match', $uri->getQuery());
    }

    public function testWithQueryInvalidType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Uri query must be a string');
        $this->uriFactory()->withQuery(['foo']);
    }

    public function testGetFragment()
    {
        $this->assertEquals('section3', $this->uriFactory()->getFragment());
    }

    public function testWithFragment()
    {
        $uri = $this->uriFactory()->withFragment('other-fragment');

        $this->assertSame('other-fragment', $uri->getFragment());
    }

    public function testWithFragmentRemovesPrefix()
    {
        $uri = $this->uriFactory()->withFragment('#other-fragment');

        $this->assertSame('other-fragment', $uri->getFragment());
    }

    public function testWithFragmentEmpty()
    {
        $uri = $this->uriFactory()->withFragment('');

        $this->assertSame('', $uri->getFragment());
    }

    public function testWithFragmentInvalidType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Uri fragment must be a string');
        $this->uriFactory()->withFragment(['foo']);
    }

    public function testToString()
    {
        $uri = $this->uriFactory();

        $this->assertEquals('https://josh:sekrit@example.com/foo/bar?abc=123#section3', (string) $uri);

        $uri = $uri->withPath('bar');
        $this->assertEquals('https://josh:sekrit@example.com/bar?abc=123#section3', (string) $uri);

        $uri = $uri->withBasePath('foo/');
        $this->assertEquals('https://josh:sekrit@example.com/foo/bar?abc=123#section3', (string) $uri);

        $uri = $uri->withPath('/bar');
        $this->assertEquals('https://josh:sekrit@example.com/bar?abc=123#section3', (string) $uri);

        // ensure that a Uri with just a base path correctly converts to a string
        // (This occurs via createFromEnvironment when index.php is in a subdirectory)
        $environment = Environment::mock([
            'SCRIPT_NAME' => '/foo/index.php',
            'REQUEST_URI' => '/foo/',
            'HTTP_HOST' => 'example.com',
        ]);
        $uri = Uri::createFromEnvironment($environment);
        $this->assertEquals('http://example.com/foo/', (string) $uri);
    }

    public function testCreateFromString()
    {
        $uri = Uri::createFromString('https://example.com:8080/foo/bar?abc=123');

        $this->assertEquals('https', $uri->getScheme());
        $this->assertEquals('example.com', $uri->getHost());
        $this->assertEquals('8080', $uri->getPort());
        $this->assertEquals('/foo/bar', $uri->getPath());
        $this->assertEquals('abc=123', $uri->getQuery());
    }

    public function testCreateFromStringWithInvalidType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Uri must be a string');
        Uri::createFromString(['https://example.com:8080/foo/bar?abc=123']);
    }

    public function testCreateEnvironment()
    {
        $environment = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/bar',
            'PHP_AUTH_USER' => 'josh',
            'PHP_AUTH_PW' => 'sekrit',
            'QUERY_STRING' => 'abc=123',
            'HTTP_HOST' => 'example.com:8080',
            'SERVER_PORT' => 8080,
        ]);

        $uri = Uri::createFromEnvironment($environment);

        $this->assertEquals('josh:sekrit', $uri->getUserInfo());
        $this->assertEquals('example.com', $uri->getHost());
        $this->assertEquals('8080', $uri->getPort());
        $this->assertEquals('/foo/bar', $uri->getPath());
        $this->assertEquals('abc=123', $uri->getQuery());
        $this->assertEquals('', $uri->getFragment());
    }

    public function testCreateFromEnvironmentSetsDefaultPortWhenHostHeaderDoesntHaveAPort()
    {
        $environment = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/bar',
            'QUERY_STRING' => 'abc=123',
            'HTTP_HOST' => 'example.com',
            'HTTPS' => 'on',
        ]);

        $uri = Uri::createFromEnvironment($environment);

        $this->assertEquals('example.com', $uri->getHost());
        $this->assertEquals(null, $uri->getPort());
        $this->assertEquals('/foo/bar', $uri->getPath());
        $this->assertEquals('abc=123', $uri->getQuery());
        $this->assertEquals('', $uri->getFragment());

        $this->assertEquals('https://example.com/foo/bar?abc=123', (string)$uri);
    }

    public function testCreateEnvironmentWithIPv6HostNoPort()
    {
        $environment = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/bar',
            'PHP_AUTH_USER' => 'josh',
            'PHP_AUTH_PW' => 'sekrit',
            'QUERY_STRING' => 'abc=123',
            'HTTP_HOST' => '[2001:db8::1]',
            'REMOTE_ADDR' => '2001:db8::1',
        ]);

        $uri = Uri::createFromEnvironment($environment);

        $this->assertEquals('josh:sekrit', $uri->getUserInfo());
        $this->assertEquals('[2001:db8::1]', $uri->getHost());
        $this->assertNull($uri->getPort());
        $this->assertEquals('/foo/bar', $uri->getPath());
        $this->assertEquals('abc=123', $uri->getQuery());
        $this->assertEquals('', $uri->getFragment());
    }

    public function testCreateEnvironmentWithIPv6HostWithPort()
    {
        $environment = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/bar',
            'PHP_AUTH_USER' => 'josh',
            'PHP_AUTH_PW' => 'sekrit',
            'QUERY_STRING' => 'abc=123',
            'HTTP_HOST' => '[2001:db8::1]:8080',
            'REMOTE_ADDR' => '2001:db8::1',
        ]);

        $uri = Uri::createFromEnvironment($environment);

        $this->assertEquals('josh:sekrit', $uri->getUserInfo());
        $this->assertEquals('[2001:db8::1]', $uri->getHost());
        $this->assertEquals('8080', $uri->getPort());
        $this->assertEquals('/foo/bar', $uri->getPath());
        $this->assertEquals('abc=123', $uri->getQuery());
        $this->assertEquals('', $uri->getFragment());
    }

    /**
     * @group one
     */
    public function testCreateEnvironmentWithNoHostHeader()
    {
        $environment = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/bar',
            'PHP_AUTH_USER' => 'josh',
            'PHP_AUTH_PW' => 'sekrit',
            'QUERY_STRING' => 'abc=123',
            'REMOTE_ADDR' => '2001:db8::1',
            'SERVER_NAME' => '[2001:db8::1]',
            'SERVER_PORT' => '8080',
        ]);
        $environment->remove('HTTP_HOST');

        $uri = Uri::createFromEnvironment($environment);

        $this->assertEquals('josh:sekrit', $uri->getUserInfo());
        $this->assertEquals('[2001:db8::1]', $uri->getHost());
        $this->assertEquals('8080', $uri->getPort());
        $this->assertEquals('/foo/bar', $uri->getPath());
        $this->assertEquals('abc=123', $uri->getQuery());
        $this->assertEquals('', $uri->getFragment());
    }

    public function testCreateEnvironmentWithBasePath()
    {
        $environment = Environment::mock([
            'SCRIPT_NAME' => '/foo/index.php',
            'REQUEST_URI' => '/foo/bar',
        ]);
        $uri = Uri::createFromEnvironment($environment);

        $this->assertEquals('/foo', $uri->getBasePath());
        $this->assertEquals('bar', $uri->getPath());

        $this->assertEquals('http://localhost/foo/bar', (string) $uri);
    }

    public function testGetBaseUrl()
    {
        $environment = Environment::mock([
            'SCRIPT_NAME' => '/foo/index.php',
            'REQUEST_URI' => '/foo/bar',
            'QUERY_STRING' => 'abc=123',
            'HTTP_HOST' => 'example.com:80',
            'SERVER_PORT' => 80
        ]);
        $uri = Uri::createFromEnvironment($environment);

        $this->assertEquals('http://example.com/foo', $uri->getBaseUrl());
    }

    public function testGetBaseUrlWithNoBasePath()
    {
        $environment = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/bar',
            'QUERY_STRING' => 'abc=123',
            'HTTP_HOST' => 'example.com:80',
            'SERVER_PORT' => 80
        ]);
        $uri = Uri::createFromEnvironment($environment);

        $this->assertEquals('http://example.com', $uri->getBaseUrl());
    }

    public function testGetBaseUrlWithAuthority()
    {
        $environment = Environment::mock([
            'SCRIPT_NAME' => '/foo/index.php',
            'REQUEST_URI' => '/foo/bar',
            'PHP_AUTH_USER' => 'josh',
            'PHP_AUTH_PW' => 'sekrit',
            'QUERY_STRING' => 'abc=123',
            'HTTP_HOST' => 'example.com:8080',
            'SERVER_PORT' => 8080
        ]);
        $uri = Uri::createFromEnvironment($environment);

        $this->assertEquals('http://josh:sekrit@example.com:8080/foo', $uri->getBaseUrl());
    }

    public function testWithPathWhenBaseRootIsEmpty()
    {
        $environment = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/bar',
        ]);
        $uri = Uri::createFromEnvironment($environment);

        $this->assertEquals('http://localhost/test', (string) $uri->withPath('test'));
    }

    public function testRequestURIContainsIndexDotPhp()
    {
        $uri = Uri::createFromEnvironment(
            Environment::mock(
                [
                    'SCRIPT_NAME' => '/foo/index.php',
                    'REQUEST_URI' => '/foo/index.php/bar/baz',
                ]
            )
        );
        $this->assertSame('/foo/index.php', $uri->getBasePath());
    }

    public function testRequestURICanContainParams()
    {
        $uri = Uri::createFromEnvironment(
            Environment::mock(
                [
                    'REQUEST_URI' => '/foo?abc=123',
                ]
            )
        );
        $this->assertEquals('abc=123', $uri->getQuery());
    }

    public function testUriDistinguishZeroFromEmptyString()
    {
        $expected = 'https://0:0@0:1/0?0#0';
        $this->assertSame($expected, (string) Uri::createFromString($expected));
    }

    public function testGetBaseUrlDistinguishZeroFromEmptyString()
    {
        $expected = 'https://0:0@0:1/0?0#0';
        $this->assertSame('https://0:0@0:1', (string) Uri::createFromString($expected)->getBaseUrl());
    }

    public function testConstructorWithEmptyPath()
    {
        $uri = new Uri('https', 'example.com', null, '');
        $this->assertSame('/', $uri->getPath());
    }

    public function testConstructorWithZeroAsPath()
    {
        $uri = new Uri('https', 'example.com', null, '0');
        $this->assertSame('0', (string) $uri->getPath());
    }
}
