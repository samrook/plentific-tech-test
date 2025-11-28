<?php

declare(strict_types=1);

namespace SamRook\ReqResClient\DTO;

use JsonSerializable;
use ArrayIterator;
use IteratorAggregate;
use InvalidArgumentException;

/**
 * @template-implements IteratorAggregate<int, UserDTO>
 */
readonly class UserCollectionDTO implements JsonSerializable, IteratorAggregate
{
    /**
     * @param array<UserDTO> $users
     */
    public function __construct(
        public int $page,
        public int $perPage,
        public int $total,
        public int $totalPages,
        public array $users,
    ) {}

    /**
     * @return ArrayIterator<int, UserDTO>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->users);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        if (
            !isset($data['page'], $data['per_page'], $data['total'], $data['total_pages'], $data['data'])
            || !is_int($data['page'])
            || !is_int($data['per_page'])
            || !is_int($data['total'])
            || !is_int($data['total_pages'])
            || !is_array($data['data'])
        ) {
            throw new InvalidArgumentException('Failed to parse user list from API response.');
        }

        /**
         * @var array<int, array{
         *   id: int,
         *   email: string,
         *   first_name: string,
         *   last_name: string,
         *   avatar: string
         * }> $usersData
         */
        $usersData = $data['data'];

        $users = array_map(
            static fn(array $user): UserDTO => UserDTO::fromArray($user),
            $usersData,
        );

        return new self(
            page: $data['page'],
            perPage: $data['per_page'],
            total: $data['total'],
            totalPages: $data['total_pages'],
            users: $users,
        );
    }

    /**
     * @return array{
     *      page: int,
     *      per_page: int,
     *      total: int,
     *      total_pages: int,
     *      users: array<int, array{
     *          id: int,
     *          email: string,
     *          first_name: string,
     *          last_name: string,
     *          avatar: string
     *      }>
     *  }
     */
    public function toArray(): array
    {
        return [
            'page' => $this->page,
            'per_page' => $this->perPage,
            'total' => $this->total,
            'total_pages' => $this->totalPages,
            'users' => array_values(array_map(static fn(UserDTO $user) => $user->toArray(), $this->users)),
        ];
    }

    /**
     * @return array{
     *      page: int,
     *      per_page: int,
     *      total: int,
     *      total_pages: int,
     *      users: array<int, array{
     *          id: int,
     *          email: string,
     *          first_name: string,
     *          last_name: string,
     *          avatar: string
     *      }>
     *  }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
