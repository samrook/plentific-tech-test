<?php

declare(strict_types=1);

namespace SamRook\ReqResClient\Tests\Unit\DTO;

use PHPUnit\Framework\TestCase;
use SamRook\ReqResClient\DTO\UserDTO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

class UserDTOTest extends TestCase
{
    #[Test]
    #[DataProvider('userDataProvider')]
    public function createsFromArray(array $data): void
    {
        $userDto = UserDTO::fromArray($data);

        $this->assertSame((int) $data['id'], $userDto->id);
        $this->assertSame($data['email'], $userDto->email);
        $this->assertSame($data['first_name'], $userDto->firstName);
        $this->assertSame($data['last_name'], $userDto->lastName);
        $this->assertSame($data['avatar'], $userDto->avatar);
    }

    #[Test]
    #[DataProvider('userDataProvider')]
    public function convertsToArray(array $data): void
    {
        $userDto = UserDTO::fromArray($data);

        $this->assertEquals($data, $userDto->toArray());
    }

    #[Test]
    #[DataProvider('userDataProvider')]
    public function jsonSerializes(array $data): void
    {
        $userDto = UserDTO::fromArray($data);

        $this->assertEquals($data, $userDto->jsonSerialize());
    }

    public static function userDataProvider(): array
    {
        return [
            'with string id' => [
                'data' => [
                    'id' => '1',
                    'email' => 'george.bluth@reqres.in',
                    'first_name' => 'George',
                    'last_name' => 'Bluth',
                    'avatar' => 'https://reqres.in/img/faces/1-image.jpg',
                ],
            ],
            'with int id' => [
                'data' => [
                    'id' => 2,
                    'email' => 'janet.weaver@reqres.in',
                    'first_name' => 'Janet',
                    'last_name' => 'Weaver',
                    'avatar' => 'https://reqres.in/img/faces/2-image.jpg',
                ],
            ]
        ];
    }
}
