<?php

declare(strict_types=1);

use Bybrand\OAuth2\Client\Provider\Microsoft;
use Bybrand\OAuth2\Client\Provider\MicrosoftResourceOwner;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class MicrosoftTest extends TestCase
{
    public function testBaseAuthorizationUrlUsesTenant(): void
    {
        $provider = new TestableMicrosoft(['tenant' => 'organizations']);

        $this->assertEquals(
            'https://login.microsoftonline.com/organizations/oauth2/v2.0/authorize',
            $provider->getBaseAuthorizationUrl()
        );
    }

    public function testBaseAccessTokenUrlUsesTenant(): void
    {
        $provider = new TestableMicrosoft(['tenant' => 'consumers']);

        $this->assertEquals(
            'https://login.microsoftonline.com/consumers/oauth2/v2.0/token',
            $provider->getBaseAccessTokenUrl([])
        );
    }

    public function testResourceOwnerDetailsUrlUsesGraphApiVersion(): void
    {
        $provider = new TestableMicrosoft(['graphApiVersion' => 'beta']);

        $url = $provider->getResourceOwnerDetailsUrl(new AccessToken(['access_token' => 'abc123']));

        $this->assertEquals(
            'https://graph.microsoft.com/beta/me?$select=id,displayName,givenName,surname,mail,userPrincipalName',
            $url
        );
    }

    public function testDefaultScopes(): void
    {
        $provider = new TestableMicrosoft();

        $this->assertEquals(
            ['openid', 'profile', 'email', 'User.Read'],
            $provider->defaultScopes()
        );
    }

    public function testCheckResponseDoesNothingForSuccessfulResponseWithoutError(): void
    {
        $provider = new TestableMicrosoft();
        $response = $this->createResponse(200, 'OK');

        $provider->validateResponse($response, ['id' => 'user-id']);

        $this->assertTrue(true);
    }

    public function testCheckResponseThrowsWithReasonPhraseWhenHttpErrorHasNoOAuthError(): void
    {
        $this->expectException(IdentityProviderException::class);
        $this->expectExceptionMessage('Unauthorized');

        $provider = new TestableMicrosoft();
        $response = $this->createResponse(401, 'Unauthorized');

        $provider->validateResponse($response, ['message' => 'denied']);
    }

    public function testCheckResponseUsesErrorDescriptionWhenErrorIsString(): void
    {
        $this->expectException(IdentityProviderException::class);
        $this->expectExceptionMessage('Invalid client credentials.');

        $provider = new TestableMicrosoft();
        $response = $this->createResponse(400, 'Bad Request');

        $provider->validateResponse($response, [
            'error' => 'invalid_client',
            'error_description' => 'Invalid client credentials.',
        ]);
    }

    public function testCheckResponseUsesNestedErrorMessageWhenErrorIsArray(): void
    {
        $this->expectException(IdentityProviderException::class);
        $this->expectExceptionMessage('Token expired.');

        $provider = new TestableMicrosoft();
        $response = $this->createResponse(401, 'Unauthorized');

        $provider->validateResponse($response, [
            'error' => ['message' => 'Token expired.'],
        ]);
    }

    public function testCreateResourceOwner(): void
    {
        $provider = new TestableMicrosoft();
        $token = new AccessToken(['access_token' => 'abc123']);
        $payload = ['id' => '1', 'displayName' => 'Jane'];

        $resourceOwner = $provider->buildResourceOwner($payload, $token);

        $this->assertInstanceOf(MicrosoftResourceOwner::class, $resourceOwner);
        $this->assertSame($payload, $resourceOwner->toArray());
    }

    private function createResponse(int $statusCode, string $reasonPhrase): ResponseInterface
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($statusCode);
        $response->method('getReasonPhrase')->willReturn($reasonPhrase);

        return $response;
    }
}

final class TestableMicrosoft extends Microsoft
{
    public function defaultScopes(): array
    {
        return $this->getDefaultScopes();
    }

    /**
     * @param mixed $data
     */
    public function validateResponse(ResponseInterface $response, $data): void
    {
        $this->checkResponse($response, $data);
    }

    public function buildResourceOwner(array $response, AccessToken $token): MicrosoftResourceOwner
    {
        /** @var MicrosoftResourceOwner $owner */
        $owner = $this->createResourceOwner($response, $token);

        return $owner;
    }
}
