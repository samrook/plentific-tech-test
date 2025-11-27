<?php

declare(strict_types=1);

namespace SamRook\ReqResClient\DTO;

use JsonSerializable;

readonly class UserDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $email,
        public string $firstName,
        public string $lastName,
        public string $avatar,
    ) {
    }

    /**
     * @param array{
     *     id: int,
     *     email: string,
     *     first_name: string,
     *     last_name: string,
     *     avatar: string
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            email: $data['email'],
            firstName: $data['first_name'],
            lastName: $data['last_name'],
            avatar: $data['avatar'],
        );
    }

    /**
     * @return array{
     *      id: int,
     *      email: string,
     *      first_name: string,
     *      last_name: string,
     *      avatar: string
     *  }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'avatar' => $this->avatar,
        ];
    }

    /**
     * @return array{
     *      id: int,
     *      email: string,
     *      first_name: string,
     *      last_name: string,
     *      avatar: string
     *  }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
