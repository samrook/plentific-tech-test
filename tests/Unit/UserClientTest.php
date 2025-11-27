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
    private const int CREATED_USER_ID = 102;

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
        $mockResponse = $this->makeResponse('get-user-2');

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
        $mockResponse = $this->makeResponse('create-user');

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

    #[Test]
    public function canCreateUser(): void
    {
        $mockResponse = $this->makeResponse('create-user', 201);
        $payload = $this->loadFakeRequest('create-user');

        $this->guzzle
            ->shouldReceive('request')
            ->once()
            ->withArgs(function (string $method, string $uri, array $options) use ($payload): bool {
                $this->assertSame('POST', $method);
                $this->assertSame('users', $uri);
                $this->assertSame(['json' => $payload], $options);

                return true;
            })
            ->andReturn($mockResponse);

        $newId = $this->service->createUser($payload);

        $this->assertEquals(self::CREATED_USER_ID, $newId);
    }

    #[Test]
    public function throwsAnExceptionWhenCreatedUserIdIsMissing(): void
    {
        $payload = $this->loadFakeRequest('create-user');
        $mockResponse = $this->makeResponse('get-user-2', 201);

        $this->guzzle
            ->shouldReceive('request')
            ->once()
            ->withArgs(function (string $method, string $uri, array $options) use ($payload): bool {
                $this->assertSame('POST', $method);
                $this->assertSame('users', $uri);
                $this->assertSame(['json' => $payload], $options);

                return true;
            })
            ->andReturn($mockResponse);

        $this->expectException(ApiConnectionException::class);
        $this->expectExceptionMessage('Invalid response when creating user: ID missing or not numeric.');

        $this->service->createUser($payload);
    }

    #[Test]
    public function throwsAnApiConnectionExceptionWhenCreatingAUserFails(): void
    {
        $payload = $this->loadFakeRequest('create-user');
        $exception = new ConnectException(
            message: 'Connection timed out',
            request: $this->makeRequest('POST', 'users', $payload),
        );

        $this->guzzle
            ->shouldReceive('request')
            ->once()
            ->withArgs(function (string $method, string $uri, array $options) use ($payload): bool {
                $this->assertSame('POST', $method);
                $this->assertSame('users', $uri);
                $this->assertSame(['json' => $payload], $options);

                return true;
            })
            ->andThrow($exception);

        $this->expectException(ApiConnectionException::class);
        $this->expectExceptionMessage('Failed to create user on ReqRes API');

        $this->service->createUser($payload);
    }

    private function loadFakeResponse(string $name)
    {
        return file_get_contents("tests/fake-responses/{$name}.json");
    }

    private function loadFakeRequest(string $name, bool $asJson = false): array|string|false
    {
        $contents = file_get_contents("tests/fake-requests/{$name}.json");

        if ($asJson) {
            return $contents;
        }

        return json_decode($contents, true);
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