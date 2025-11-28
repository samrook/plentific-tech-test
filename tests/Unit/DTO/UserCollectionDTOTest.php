<?php

declare(strict_types=1);

namespace SamRook\ReqResClient\Tests\Unit\DTO;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use SamRook\ReqResClient\DTO\UserCollectionDTO;
use SamRook\ReqResClient\DTO\UserDTO;

class UserCollectionDTOTest extends TestCase
{
    #[Test]
    #[DataProvider('userCollectionDataProvider')]
    public function createsFromArray(array $data): void
    {
        $userCollectionDto = UserCollectionDTO::fromArray($data);

        $this->assertSame($data['page'], $userCollectionDto->page);
        $this->assertSame($data['per_page'], $userCollectionDto->perPage);
        $this->assertSame($data['total'], $userCollectionDto->total);
        $this->assertSame($data['total_pages'], $userCollectionDto->totalPages);
        $this->assertCount(count($data['data']), $userCollectionDto->users);
        $this->assertContainsOnlyInstancesOf(UserDTO::class, $userCollectionDto->users);
    }

    #[Test]
    #[DataProvider('userCollectionDataProvider')]
    public function convertsToArray(array $data): void
    {
        $userCollectionDto = UserCollectionDTO::fromArray($data);

        $expected = [
            'page' => $data['page'],
            'per_page' => $data['per_page'],
            'total' => $data['total'],
            'total_pages' => $data['total_pages'],
            'users' => $data['data'],
        ];

        $this->assertSame($expected, $userCollectionDto->toArray());
    }

    #[Test]
    #[DataProvider('userCollectionDataProvider')]
    public function jsonSerializes(array $data): void
    {
        $userCollectionDto = UserCollectionDTO::fromArray($data);

        $expected = [
            'page' => $data['page'],
            'per_page' => $data['per_page'],
            'total' => $data['total'],
            'total_pages' => $data['total_pages'],
            'users' => $data['data'],
        ];

        $this->assertSame($expected, $userCollectionDto->jsonSerialize());
    }

    #[Test]
    public function throwsExceptionWhenMissingKeys(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Failed to parse user list from API response.');

        UserCollectionDTO::fromArray(['data' => []]);
    }

    public static function userCollectionDataProvider(): array
    {
        return [
            [
                'data' => [
                    'page' => 1,
                    'per_page' => 6,
                    'total' => 12,
                    'total_pages' => 2,
                    'data' => [
                        [
                            'id' => 1,
                            'email' => 'george.bluth@reqres.in',
                            'first_name' => 'George',
                            'last_name' => 'Bluth',
                            'avatar' => 'https://reqres.in/img/faces/1-image.jpg',
                        ],
                        [
                            'id' => 2,
                            'email' => 'janet.weaver@reqres.in',
                            'first_name' => 'Janet',
                            'last_name' => 'Weaver',
                            'avatar' => 'https://reqres.in/img/faces/2-image.jpg',
                        ],
                    ],
                ],
            ],
        ];
    }
}
