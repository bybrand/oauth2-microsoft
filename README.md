# Microsoft Provider for PHP OAuth 2.0 Client

This package provides Microsoft OAuth 2.0 support (v2.0 endpoints) for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

## Installation

```bash
composer require bybrand/oauth2-microsoft
```

## Usage

```php
use Bybrand\OAuth2\Client\Provider\Microsoft;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

$provider = new Microsoft([
    'clientId'     => 'your-client-id',
    'clientSecret' => 'your-client-secret',
    'redirectUri'  => 'your-redirect-uri',
    'tenant'       => 'common', // common | organizations | consumers | tenant-id
]);

$params = $_GET;

if (!isset($params['code']) || empty($params['code'])) {
    $authorizationUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->getState();

    header('Location: ' . $authorizationUrl);
    exit;
}

if (empty($params['state']) || ($params['state'] !== $_SESSION['oauth2state'])) {
    unset($_SESSION['oauth2state']);
    exit('Invalid state');
}

try {
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $params['code']
    ]);

    $user = $provider->getResourceOwner($token);

    $id = $user->getId();
    $name = $user->getName();
    $email = $user->getEmail();
} catch (IdentityProviderException $e) {
    // Handle token/user request errors.
}
```

## License

MIT.
