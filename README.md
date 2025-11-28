# ReqRes API Client

A modern, framework-agnostic PHP package for interacting with the [reqres.in](https://reqres.in/) API.

This client provides a simple and robust way to retrieve and create users, with built-in features to handle unreliable network conditions.

## Installation

Install the package via Composer:

```bash
composer require samrook/reqres-client
```

## Usage

Instantiate the client and start making requests.

```php
use SamRook\ReqResClient\UserClient;

$client = new UserClient();
```

### Get a single user

The `getUser()` method returns an immutable `UserDTO` object.

```php
try {
    $user = $client->getUser(2);

    echo "Hello, {$user->firstName} {$user->lastName}!";
    // Hello, Janet Weaver!

    print_r($user->toArray());
    /*
    Array
    (
        [id] => 2
        [email] => janet.weaver@reqres.in
        [first_name] => Janet
        [last_name] => Weaver
        [avatar] => https://reqres.in/img/faces/2-image.jpg
    )
    */

} catch (\SamRook\ReqResClient\Exceptions\UserNotFoundException $e) {
    // Handle the case where the user does not exist
    echo $e->getMessage();
}
```

### List users

The `listUsers()` method returns an immutable `UserCollectionDTO` object, which is iterable and contains an array of `UserDTO` objects.

```php
$users = $client->listUsers(1);

foreach ($users as $user) {
    echo $user->email . "\n";
}

// The DTO also contains pagination data
echo "Page {$users->page} of {$users->totalPages}";
```

### Create a user

The `createUser()` method accepts an array of data and returns the ID of the new user.

```php
$newUserId = $client->createUser([
    'name' => 'Sam Rook',
    'job' => 'Software Engineer'
]);

echo "Created user with ID: {$newUserId}";
```

## Features

### Resilient by Design

This client is built to be resilient against unreliable network conditions and API failures.

*   **Automatic Retries**: The client uses Guzzle Middleware to automatically retry requests that fail due to connection timeouts or 5xx-level server errors. It uses an exponential backoff strategy to avoid overwhelming the API. By default, it will retry a failed request twice.

*   **Custom Exceptions**: Generic HTTP exceptions are caught and re-thrown as specific, domain-level exceptions. This allows your application to handle different failure scenarios gracefully. See the `Error Handling` section for more details.

### Immutable, Type-Safe DTOs

The client uses `readonly` Data Transfer Objects (`UserDTO` and `UserCollectionDTO`) to represent API data. This provides a clear, immutable, and type-safe data structure, decoupling your application's logic from the raw API response.

### Dependency Injection

The underlying `GuzzleHttp\ClientInterface` is injected into the `UserClient`. While the client creates a default Guzzle client for you, this allows you to pass in your own pre-configured Guzzle instance for advanced use cases or for mocking during tests.

## Error Handling

The client will throw the following custom exceptions:

*   `UserNotFoundException`: Thrown for a `404 Not Found` response when fetching a single user.
*   `ApiConnectionException`: Thrown for connection errors (e.g., DNS, timeouts) or 5xx server errors after all retries have failed.

You should catch these exceptions in your application and handle them accordingly.

```php
use SamRook\ReqResClient\UserClient;
use SamRook\ReqResClient\Exceptions\UserNotFoundException;
use SamRook\ReqResClient\Exceptions\ApiConnectionException;

$client = new UserClient();

try {
    $user = $client->getUser(999);
} catch (UserNotFoundException $e) {
    // User doesn't exist
    echo "Could not find the user.";
} catch (ApiConnectionException $e) {
    // API is down or there was a connection issue
    echo "The API is currently unavailable.";
}
```

## Testing

The package includes a comprehensive test suite.

```bash
# Run the main test suite (Unit and Integration tests)
composer test

# Run static analysis
composer analyse

# Run all checks (tests, static analysis, and code style)
composer check
```

### API Tests

The suite also includes a set of tests that make requests against the live `reqres.in` API. These are excluded by default to keep the main test suite fast and reliable.

To run them, use the `@external` group with PHPUnit:

```bash
composer test -- --group external
# or
vendor/bin/phpunit --group external
```
