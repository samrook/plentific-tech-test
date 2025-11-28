<?php

declare(strict_types=1);

namespace SamRook\ReqResClient\Tests\API;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SamRook\ReqResClient\DTO\UserDTO;
use SamRook\ReqResClient\UserClient;

#[Group('external')]
class UserApiTest extends TestCase
{
    private const int USER_ID = 1;

    #[Test]
    public function canFetchARealUserFromApi(): void
    {
        $service = new UserClient();

        $user = $service->getUser(self::USER_ID);

        $this->assertInstanceOf(UserDTO::class, $user);
        $this->assertEquals(self::USER_ID, $user->id);
        $this->assertNotEmpty($user->email);
        $this->assertSame('george.bluth@reqres.in', $user->email);
        $this->assertNotEmpty($user->firstName);
        $this->assertSame('George', $user->firstName);
    }
}
