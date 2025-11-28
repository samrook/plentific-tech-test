<?php

declare(strict_types=1);

namespace SamRook\ReqResClient\Tests\Integration;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SamRook\ReqResClient\DTO\UserDTO;
use SamRook\ReqResClient\Exceptions\ApiConnectionException;
use SamRook\ReqResClient\UserClient;
use Throwable;

class UserClientRetryTest extends TestCase
{
    private const int USER_ID = 2;

    #[Test]
    public function retriesAFailedRequest(): void
    {
        $mockHandler = new MockHandler([
            new Response(500),
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                $this->getFakeUserResponse(),
            ),
        ]);

        $service = new UserClient(
            client: null,
            handler: $mockHandler,
            retryDelay: fn(int $retries) => 0,
        );

        $user = $service->getUser(self::USER_ID);

        $this->assertInstanceOf(UserDTO::class, $user);
        $this->assertSame(self::USER_ID, $user->id);
        $this->assertSame(0, $mockHandler->count());
    }

    #[Test]
    public function retriesMultipleFailedRequests(): void
    {
        $mockHandler = new MockHandler([
            new Response(500),
            new Response(500),
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                $this->getFakeUserResponse(),
            ),
        ]);

        $service = new UserClient(
            client: null,
            handler: $mockHandler,
            retryDelay: fn(int $retries) => 0,
        );

        $user = $service->getUser(self::USER_ID);

        $this->assertInstanceOf(UserDTO::class, $user);
        $this->assertSame(self::USER_ID, $user->id);
        $this->assertSame(0, $mockHandler->count());
    }

    #[Test]
    public function stopsRetryingAfterMaxRetries(): void
    {
        $mockHandler = new MockHandler([
            new Response(500),
            new Response(500),
            new Response(500),
        ]);

        $service = new UserClient(
            client: null,
            handler: $mockHandler,
            maxRetries: 2,
            retryDelay: fn(int $retries) => 0,
        );

        $this->expectException(ApiConnectionException::class);

        try {
            $service->getUser(self::USER_ID);
        } catch(Throwable $e) {
            $this->assertSame(0, $mockHandler->count());
            throw $e;
        }
    }

    private function getFakeUserResponse(): string
    {
        return file_get_contents('tests/fake-responses/get-user-2.json');
    }
}
