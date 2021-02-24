<?php namespace Bogstag\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Tool\ArrayAccessorTrait;

class TraktResourceOwner implements ResourceOwnerInterface
{
    use ArrayAccessorTrait;

    /**
     * Raw response
     *
     * @var array
     */
    protected $response;

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
     * Get username
     *
     * @return string|null
     */
    public function getUsername()
    {
        return $this->getValueByKey($this->response, 'user.username');
    }

    /**
     * Get user name
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->getValueByKey($this->response, 'user.name');
    }

    /**
     * Get user avatar url
     *
     * @return string|null
     */
    public function getAvatarUrl()
    {
        return $this->getValueByKey($this->response, 'user.images.avatar.full');
    }

    /**
     * Get user slug
     *
     * @return string|null
     */
    public function getId()
    {
        return $this->getValueByKey($this->response, 'user.ids.slug');
    }

    /**
     * Return all of the owner details available as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->response;
    }
}
