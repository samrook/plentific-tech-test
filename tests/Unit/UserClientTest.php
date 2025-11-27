<?php

declare(strict_types=1);

namespace SamRook\ReqResClient\Tests\Unit;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SamRook\ReqResClient\DTO\UserDTO;
use SamRook\ReqResClient\UserClient;

class UserClientTest extends TestCase
{
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
        $userId = 2;
        $mockResponse = new Response(body: file_get_contents('tests/fake-responses/get-user-2.json'));

        $this->guzzle
            ->shouldReceive('request')
            ->once()
            ->withArgs(function (string $method, string $uri) use ($userId): bool {
                $this->assertSame('GET', $method);
                $this->assertSame("users/{$userId}", $uri);
                // $this->assertSame([], $options);

                return true;
            })
            ->andReturn($mockResponse);

        $user = $this->service->getUser($userId);

        $this->assertInstanceOf(UserDTO::class, $user);
        $this->assertSame($userId, $user->id);
        $this->assertSame('Janet', $user->firstName);
        $this->assertSame('janet.weaver@reqres.in', $user->email);
    }
}