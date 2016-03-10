<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2016 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/master/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Http;

use Slim\Http\Environment;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\RequestBody;
use Slim\Http\Stream;
use Slim\Http\UploadedFile;
use Slim\Http\Uri;

class UploadedFilesTest extends \PHPUnit_Framework_TestCase
{
    static private $filename = './phpUxcOty';
    /**
     * @beforeClass
     */
    public static function setUpBeforeClass()
    {
        $fh = fopen(self::$filename, "w");
        fwrite($fh, "12345678");
        fclose($fh);
    }

    /**
     * @afterClass
     */
    public static function tearDownAfterClass()
    {
        if (file_exists(self::$filename)) {
            unlink(self::$filename);
        }
    }

    /**
     * @param array $input The input array to parse.
     * @param array $expected The expected normalized output.
     *
     * @dataProvider providerCreateFromEnvironment
     */
    public function testCreateFromEnvironmentFromFilesSuperglobal(array $input, array $expected)
    {
        $_FILES = $input;

        $uploadedFile = UploadedFile::createFromEnvironment(Environment::mock());
        $this->assertEquals($expected, $uploadedFile);
    }

    /**
     * @return UploadedFile
     */
    public function testConstructor()
    {
        $attr = [
            'tmp_name' => self::$filename,
            'name'     => 'my-avatar.txt',
            'size'     => 8,
            'type'     => 'text/plain',
            'error'    => 0,
        ];

        $uploadedFile = new UploadedFile(
            $attr['tmp_name'],
            $attr['name'],
            $attr['type'],
            $attr['size'],
            $attr['error'],
            false
        );


        $this->assertEquals($attr['name'], $uploadedFile->getClientFilename());
        $this->assertEquals($attr['type'], $uploadedFile->getClientMediaType());
        $this->assertEquals($attr['size'], $uploadedFile->getSize());
        $this->assertEquals($attr['error'], $uploadedFile->getError());

        return $uploadedFile;
    }

    /**
     * @depends testConstructor
     * @param UploadedFile $uploadedFile
     * @return UploadedFile
     */
    public function testGetStream(UploadedFile $uploadedFile)
    {
        $stream = $uploadedFile->getStream();
        $this->assertEquals(true, $uploadedFile->getStream() instanceof Stream);
        $stream->close();

        return $uploadedFile;
    }

    /**
     * @depends testConstructor
     * @param UploadedFile $uploadedFile
     * @return UploadedFile
     */
    public function testMoveTo(UploadedFile $uploadedFile)
    {
        $tempName = uniqid('file-');
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $tempName;
        $uploadedFile->moveTo($path);

        $this->assertFileExists($path);

        unlink($path);

        return $uploadedFile;
    }

    public function providerCreateFromEnvironment()
    {
        return [
            [
                [
                    'files' => [
                        'tmp_name' => [
                            0 => __DIR__ . DIRECTORY_SEPARATOR . 'file0.txt',
                            1 => __DIR__ . DIRECTORY_SEPARATOR . 'file1.html',
                        ],
                        'name'     => [
                            0 => 'file0.txt',
                            1 => 'file1.html',
                        ],
                        'type'     => [
                            0 => 'text/plain',
                            1 => 'text/html',
                        ],
                        'error'    => [
                            0 => 0,
                            1 => 0
                        ]
                    ],
                ],
                [
                    'files' => [
                        0 => new UploadedFile(
                            __DIR__ . DIRECTORY_SEPARATOR . 'file0.txt',
                            'file0.txt',
                            'text/plain',
                            null,
                            UPLOAD_ERR_OK,
                            true
                        ),
                        1 => new UploadedFile(
                            __DIR__ . DIRECTORY_SEPARATOR . 'file1.html',
                            'file1.html',
                            'text/html',
                            null,
                            UPLOAD_ERR_OK,
                            true
                        ),
                    ],
                ]
            ],
            [
                [
                    'avatar' => [
                        'tmp_name' => 'phpUxcOty',
                        'name'     => 'my-avatar.png',
                        'size'     => 90996,
                        'type'     => 'image/png',
                        'error'    => 0,
                    ],
                ],
                [
                    'avatar' => new UploadedFile('phpUxcOty', 'my-avatar.png', 'image/png', 90996, UPLOAD_ERR_OK, true)
                ]
            ]
        ];
    }

    /**
     * @param array $mockEnv An array representing a mock environment.
     *
     * @return Request
     */
    public function requestFactory(array $mockEnv)
    {
        $env = Environment::mock();

        $uri = Uri::createFromString('https://example.com:443/foo/bar?abc=123');
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $uploadedFiles = UploadedFile::createFromEnvironment($env);
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body, $uploadedFiles);

        return $request;
    }

    public function testOneUploadedFile()
    {
        $env = Environment::mock([
            '_FILES' => [
                'image' => [
                    'name' => 'logo.png',
                    'type' => 'image/png',
                    'tmp_name' => __DIR__ . '/_files/logo.png',
                    'error' => 0,
                    'size' => 27671,
                ],
            ]
        ]);
        $uploadedFiles = UploadedFile::createFromEnvironment($env);

        $this->assertArrayHasKey('image', $uploadedFiles);
        $this->assertCount(1, $uploadedFiles);
        $this->assertInstanceOf(UploadedFile::class, $uploadedFiles['image']);
    }

    public function testTwoUploadedFiles()
    {
        $env = Environment::mock([
            '_FILES' => [
                'image' => [
                    'name' => [
                        'logo.png',
                        'logo.png',
                    ],
                    'type' => [
                        'image/png',
                        'image/png',
                    ],
                    'tmp_name' => [
                        __DIR__ . '/_files/logo.png',
                        __DIR__ . '/_files/logo.png',
                    ],
                    'error' => [
                        0,
                        0,
                    ],
                    'size' => [
                        27671,
                        27671,
                    ],
                ],
            ]
        ]);
        $uploadedFiles = UploadedFile::createFromEnvironment($env);

        $this->assertArrayHasKey('image', $uploadedFiles);
        $this->assertCount(1, $uploadedFiles);
        $this->assertCount(2, $uploadedFiles['image']);
        $this->assertInstanceOf(UploadedFile::class, $uploadedFiles['image'][0]);
        $this->assertInstanceOf(UploadedFile::class, $uploadedFiles['image'][1]);
    }
}
