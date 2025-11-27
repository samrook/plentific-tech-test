<?php

declare(strict_types=1);

namespace SamRook\ReqResClient;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use SamRook\ReqResClient\DTO\UserDTO;

class UserClient
{
    private ClientInterface $httpClient;

    public function __construct(
        ClientInterface|null $client = null,
        private readonly string $baseUri = 'https://reqres.in/api/',
        private readonly string $apiKey = 'reqres-free-v1',
        private int $maxRetries = 2,
    ) {
        $this->httpClient = $client ?? new Client([
            'base_uri' => $baseUri,
            'timeout'  => 5.0,
            'headers' => [
                'x-api-key' => $this->apiKey,
            ],
        ]);
    }

    public function getUser(int $id): UserDTO
    {
        try {
            $response = $this->httpClient->request('GET', "users/{$id}");
            $contents = $response->getBody()->getContents();

            // throw api connection exception if content empty

            $data = json_decode(
                json: $contents,
                associative: true,
                flags: JSON_THROW_ON_ERROR,
            );

            // throw api connection exception if $data not array
            // throw api connection exception if $data['data'] does not exist or is not array

            /** @var array{id: int, email: string, first_name: string, last_name: string, avatar: string} $userData */
            $userData = $data['data'];
            
            return UserDTO::fromArray($userData);
        } catch (ClientException $e) {
            if (404 === $e->getResponse()->getStatusCode()) {
                // user not found
                throw new Exception("User with ID {$id} was not found.");
            }
            // api connection
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        } catch (ConnectException | ServerException $e) {
            // api conncetion
            throw new Exception('Failed to connect to ReqRes API', 0, $e);
        }
    }
}