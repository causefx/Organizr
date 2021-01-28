<?php namespace Bogstag\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Trakt
 * @package Bogstag\OAuth2\Client\Provider
 */
class Trakt extends AbstractProvider
{
    use BearerAuthorizationTrait;

    /**
     * @var string
     */
    protected $baseUrlApi = 'https://api.trakt.tv';

    /**
     * @var string
     */
    protected $baseUrl = 'https://trakt.tv';

    /**
     * @var int
     */
    protected $traktApiVersion = 2;

    /**
     * Get authorization url to begin OAuth flow
     * As noted in api docs you should use the normal url not the api url.
     *
     * @return string
     */
    public function getBaseAuthorizationUrl()
    {
        return $this->baseUrl.'/oauth/authorize';
    }

    /**
     * @inheritDoc
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->baseUrlApi.'/oauth/token';
    }

    /**
     * @inheritDoc
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return $this->baseUrlApi.'/users/settings';
    }

    /**
     * @inheritDoc
     */
    public function getHeaders($token = null)
    {
        $headers = [];
        if ($token) {
            $headers = [
                'Content-Type'      => 'application/json',
                'trakt-api-version' => $this->traktApiVersion,
                'trakt-api-key'     => $this->clientId
            ];
        }

        return array_merge(parent::getHeaders($token), $headers);
    }

    /**
     * @inheritDoc
     */
    protected function getDefaultScopes()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (isset($data['error'])) {
            throw new IdentityProviderException(
                (isset($data['error']['message']) ? $data['error']['message'] : $response->getReasonPhrase()),
                $response->getStatusCode(),
                $data
            );
        }
    }

    /**
     * @inheritDoc
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new TraktResourceOwner($response);
    }
}
