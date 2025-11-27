<?php

declare(strict_types=1);

namespace SamRook\ReqResClient\Tests\Unit;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SamRook\ReqResClient\DTO\UserDTO;
use SamRook\ReqResClient\Exceptions\ApiConnectionException;
use SamRook\ReqResClient\Exceptions\UserNotFoundException;
use SamRook\ReqResClient\UserClient;

class UserClientTest extends TestCase
{
    private const int USER_ID_JANET_WEAVER = 2;
    private const int USER_ID_DOESNT_EXIST = 23;

    private ClientInterface&MockInterface $guzzle;
    private UserClient $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guzzle = Mockery::mock(ClientInterface::class);
        $this->service = new UserClient(client: $this->guzzle);
    }

    #[Test]
    public function canGetSingleUser(): void
    {
        $userId = self::USER_ID_JANET_WEAVER;
        $mockResponse = $this->makeResponse('tests/fake-responses/get-user-2.json');

        $this->guzzle
            ->shouldReceive('request')
            ->once()
            ->withArgs(function (string $method, string $uri) use ($userId): bool {
                $this->assertSame('GET', $method);
                $this->assertSame("users/{$userId}", $uri);

                return true;
            })
            ->andReturn($mockResponse);

        $user = $this->service->getUser($userId);

        $this->assertInstanceOf(UserDTO::class, $user);
        $this->assertSame($userId, $user->id);
        $this->assertSame('Janet', $user->firstName);
        $this->assertSame('janet.weaver@reqres.in', $user->email);
    }

    #[Test]
    public function throwsUserNotFoundExceptionOn404(): void
    {
        $userId = self::USER_ID_DOESNT_EXIST;
        $request = $this->makeRequest('GET', "users/{$userId}");
        $exception = new ClientException(
            message: 'Not Found',
            request: $request,
            response: $this->makeResponse(code: 404),
        );

        $this->guzzle
            ->shouldReceive('request')
            ->once()
            ->withArgs(function (string $method, string $uri) use ($userId): bool {
                $this->assertSame('GET', $method);
                $this->assertSame("users/{$userId}", $uri);

                return true;
            })
            ->andThrow($exception);

        $this->expectException(UserNotFoundException::class);
        $this->expectExceptionMessage("User with ID {$userId} not found.");

        $this->service->getUser($userId);
    }

    #[Test]
    public function throwsApiConnectionExceptionWhenNot404(): void
    {
        $userId = self::USER_ID_JANET_WEAVER;
        $request = $this->makeRequest('GET', "users/{$userId}");
        $exception = new ClientException(
            message: 'Bad Request',
            request: $request,
            response: $this->makeResponse(code: 400),
        );

        $this->guzzle
            ->shouldReceive('request')
            ->once()
            ->withArgs(function (string $method, string $uri) use ($userId): bool {
                $this->assertSame('GET', $method);
                $this->assertSame("users/{$userId}", $uri);

                return true;
            })
            ->andThrow($exception);

        $this->expectException(ApiConnectionException::class);
        $this->expectExceptionMessage('Bad Request');

        $this->service->getUser($userId);
    }

    #[Test]
    public function throwsAnExceptionWhenUserDataIsNotFoundInResponse(): void
    {
        $userId = self::USER_ID_JANET_WEAVER;
        $mockResponse = new Response(body: json_encode([
            'name' => 'Sam Rook',
            'job' => 'Software Engineer',
            'id' => '102',
            'createdAt' => '2025-11-27T16:32:32.952Z'
        ]));

        $this->guzzle
            ->shouldReceive('request')
            ->once()
            ->withArgs(function (string $method, string $uri) use ($userId): bool {
                $this->assertSame('GET', $method);
                $this->assertSame("users/{$userId}", $uri);

                return true;
            })
            ->andReturn($mockResponse);

        $this->expectException(ApiConnectionException::class);
        $this->expectExceptionMessage('User data not found in API response.');

        $this->service->getUser($userId);
    }

    #[Test]
    public function throwsConnectionExceptionOnTimeout(): void
    {
        $userId = self::USER_ID_JANET_WEAVER;
        $exception = new ConnectException(
            message: 'Connection timed out',
            request: $this->makeRequest('GET', "users/{$userId}"),
        );

        $this->guzzle
            ->shouldReceive('request')
            ->once()
            ->andThrow($exception);

        $this->expectException(ApiConnectionException::class);
        $this->expectExceptionMessage('Failed to connect to ReqRes API');

        $this->service->getUser($userId);
    }

    #[Test]
    public function throwsConnectionExceptionOn500(): void
    {
        $userId = self::USER_ID_JANET_WEAVER;
        $request = $this->makeRequest('GET', "users/{$userId}");
        $exception = new ServerException(
            message: 'Internal Server Error',
            request: $request,
            response: $this->makeResponse(code: 500),
        );

        $this->guzzle
            ->shouldReceive('request')
            ->once()
            ->andThrow($exception);

        $this->expectException(ApiConnectionException::class);
        $this->expectExceptionMessage('Failed to connect to ReqRes API');

        $this->service->getUser($userId);
    }

    private function loadFakeResponse(string $filename): string
    {
        return file_get_contents($filename);
    }

    private function makeResponse(string|null $filename = null, int $code = 200): Response
    {
        return new Response(
            status: $code,
            body: $filename ? $this->loadFakeResponse($filename) : null,
        );
    }

    private function makeRequest(string $method, string $uri, array|string|null $body = null): Request
    {
        if (is_array($body)) {
            $body = json_encode($body);
        }

        return new Request($method, $uri, body: $body);
    }
}