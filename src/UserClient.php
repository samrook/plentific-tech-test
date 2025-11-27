<?php

declare(strict_types=1);

namespace SamRook\ReqResClient;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use InvalidArgumentException;
use SamRook\ReqResClient\DTO\UserCollectionDTO;
use SamRook\ReqResClient\DTO\UserDTO;
use SamRook\ReqResClient\Exceptions\ApiConnectionException;
use SamRook\ReqResClient\Exceptions\UserNotFoundException;
use Throwable;

class UserClient
{
    private ClientInterface $httpClient;

    public function __construct(
        ClientInterface|null $client = null,
        private readonly string $baseUri = 'https://reqres.in/api/',
        private readonly string $apiKey = 'reqres-free-v1',
    ) {
        $this->httpClient = $client ?? new Client([
            'base_uri' => $this->baseUri,
            'timeout'  => 5.0,
            'headers' => [
                'x-api-key' => $this->apiKey,
            ],
        ]);
    }

    public function getUser(int $id): UserDTO
    {
        try {
            $data = $this->makeRequest('GET', "users/{$id}");

            $userData = $data['data'] ?? null;
            if (!is_array($userData)) {
                throw new ApiConnectionException('User data not found in API response.');
            }

            /** @var array{id: int, email: string, first_name: string, last_name: string, avatar: string} $userData */
            $userData = $data['data'];

            return UserDTO::fromArray($userData);
        } catch (ClientException $e) {
            if (404 === $e->getResponse()->getStatusCode()) {
                throw new UserNotFoundException("User with ID {$id} not found.");
            }
            throw new ApiConnectionException($e->getMessage(), $e->getCode(), $e);
        } catch (ConnectException | ServerException $e) {
            throw new ApiConnectionException('Failed to connect to ReqRes API', 0, $e);
        }
    }

    /**
     * @param array{"name": string, "job": string} $data
     */
    public function createUser(array $data): int
    {
        try {
            $responseData = $this->makeRequest('POST', 'users', [
                'json' => $data
            ]);

            if (!isset($responseData['id']) || !is_numeric($responseData['id'])) {
                throw new ApiConnectionException('Invalid response when creating user: ID missing or not numeric.');
            }
            return (int) $responseData['id'];
        } catch (ConnectException | ServerException $e) {
            throw new ApiConnectionException('Failed to create user on ReqRes API', 0, $e);
        }
    }

    public function listUsers(int $page): UserCollectionDTO
    {
        try {
            $data = $this->makeRequest('GET', "users?page={$page}");

            return UserCollectionDTO::fromArray($data);
        } catch (ConnectException | ServerException $e) {
            throw new ApiConnectionException('Failed to connect to ReqRes API', 0, $e);
        } catch (InvalidArgumentException $e) {
            throw new ApiConnectionException($e->getMessage(), 0, $e);
        } catch (Throwable $e) {
            throw new ApiConnectionException('Failed to parse user list from API response.', 0, $e);
        }
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function makeRequest(string $method, string $uri, array $options = []): array
    {
        $response = $this->httpClient->request($method, $uri, $options);
        $contents = $response->getBody()->getContents();
        if ($contents === '') {
            throw new ApiConnectionException('API response was empty.');
        }

        try {
            $data = json_decode(
                json: $contents,
                associative: true,
                flags: JSON_THROW_ON_ERROR,
            );
        } catch (\JsonException $e) {
            throw new ApiConnectionException('API response is not valid JSON.', 0, $e);
        }

        if (!is_array($data)) {
            throw new ApiConnectionException('API response did not contain a JSON object.');
        }

        /** @var array<string, mixed> $data */
        return $data;
    }
}
