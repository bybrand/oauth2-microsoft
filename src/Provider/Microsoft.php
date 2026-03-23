<?php

namespace Bybrand\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class Microsoft extends AbstractProvider
{
    use BearerAuthorizationTrait;

    /**
     * Tenant used by Microsoft identity platform.
     * Can be common, organizations, consumers or a specific tenant id.
     */
    protected string $tenant = 'common';

    /**
     * Microsoft Graph API version.
     */
    protected string $graphApiVersion = 'v1.0';

    /**
     * Get authorization url to begin OAuth flow
     */
    public function getBaseAuthorizationUrl(): string
    {
        return sprintf(
            'https://login.microsoftonline.com/%s/oauth2/v2.0/authorize',
            $this->tenant
        );
    }

    /**
     * Get access token url to retrieve token
     */
    public function getBaseAccessTokenUrl(array $params): string
    {
        return sprintf(
            'https://login.microsoftonline.com/%s/oauth2/v2.0/token',
            $this->tenant
        );
    }

    /**
     * Get provider url to fetch user profile details.
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        return sprintf(
            'https://graph.microsoft.com/%s/me?$select=id,displayName,givenName,surname,mail,userPrincipalName',
            $this->graphApiVersion
        );
    }

    /**
     * Get the default scopes used by this provider.
     */
    protected function getDefaultScopes(): array
    {
        return [
            'openid',
            'profile',
            'email',
            'User.Read'
        ];
    }

    /**
     * Microsoft identity platform expects scopes separated by spaces.
     */
    protected function getScopeSeparator(): string
    {
        return ' ';
    }

    /**
     * Check a provider response for errors.
     *
     * @throws IdentityProviderException
     * @param ResponseInterface $response
     * @param string|array $data Parsed response data
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        $hasError = is_array($data) && !empty($data['error']);
        if ($response->getStatusCode() < 400 && !$hasError) {
            return;
        }

        if (!$hasError) {
            throw new IdentityProviderException(
                $response->getReasonPhrase(),
                $response->getStatusCode(),
                $data
            );
        }

        if (is_array($data['error'])) {
            $message = $data['error']['message'] ?? 'OAuth request failed.';
        } else {
            $message = $data['error_description'] ?? $data['error'];
        }

        throw new IdentityProviderException(
            $message,
            $response->getStatusCode(),
            $data
        );
    }

    /**
     * Generate a user object from a successful user details request.
     */
    protected function createResourceOwner(array $response, AccessToken $token): MicrosoftResourceOwner
    {
        return new MicrosoftResourceOwner($response);
    }
}
