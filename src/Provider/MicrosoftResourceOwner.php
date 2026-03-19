<?php

namespace Bybrand\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Tool\ArrayAccessorTrait;

class MicrosoftResourceOwner implements ResourceOwnerInterface
{
    use ArrayAccessorTrait;

    /**
     * Raw response
     */
    protected array $response;

    /**
     * Creates new resource owner.
     *
     * @param array $response
     */
    public function __construct(array $response = [])
    {
        $this->response = $response;
    }

    /**
     * Get resource owner id.
     */
    public function getId()
    {
        return $this->getValueByKey($this->response, 'id');
    }

    /**
     * Get resource owner display name.
     */
    public function getName(): ?string
    {
        return $this->getValueByKey($this->response, 'displayName');
    }

    /**
     * Get resource owner first name.
     */
    public function getFirstName(): ?string
    {
        return $this->getValueByKey($this->response, 'givenName');
    }

    /**
     * Get resource owner last name.
     */
    public function getLastName(): ?string
    {
        return $this->getValueByKey($this->response, 'surname');
    }

    /**
     * Get resource owner email.
     */
    public function getEmail(): ?string
    {
        $email = $this->getValueByKey($this->response, 'mail');
        if (!empty($email)) {
            return $email;
        }

        return $this->getValueByKey($this->response, 'userPrincipalName');
    }

    /**
     * Return all of the owner details available as an array.
     */
    public function toArray(): array
    {
        return $this->response;
    }
}
