<?php

namespace Pixelant\PxaSocialFeed\Domain\Model;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2015
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use Facebook\Authentication\AccessTokenMetadata;
use Facebook\Facebook;
use Pixelant\PxaSocialFeed\GraphSdk\FacebookGraphSdkFactory;
use Pixelant\PxaSocialFeed\SignalSlot\EmitSignalTrait;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Tokens
 */
class Token extends AbstractEntity
{
    use EmitSignalTrait;

    /**
     * facebook token
     */
    const FACEBOOK = 1;

    /**
     * instagram_oauth2
     */
    const INSTAGRAM = 2;

    /**
     * twitter token
     */
    const TWITTER = 3;

    /**
     * youtube token
     */
    const YOUTUBE = 4;

    /**
     * Default PID
     *
     * @var int
     */
    protected $pid = 0;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\BackendUserGroup>
     * @lazy
     */
    protected $beGroup= null;

    /**
     * @var string
     */
    protected $name = '';

    /**
     * type
     *
     * @var integer
     */
    protected $type = 0;

    /**
     * @var string
     */
    protected $appId = '';

    /**
     * @var string
     */
    protected $appSecret = '';

    /**
     * @var string
     */
    protected $accessToken = '';

    /**
     * @var string
     */
    protected $apiKey = '';

    /**
     * @var string
     */
    protected $apiSecretKey = '';

    /**
     * @var string
     */
    protected $accessTokenSecret = '';

    /**
     * @var Facebook
     */
    protected $fb = null;

    /**
     * @var AccessTokenMetadata
     */
    protected $fbTokenMetaData = null;

    /**
     * Initialize
     */
    public function __construct()
    {
        $this->beGroup = new ObjectStorage();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * @param string $appId
     */
    public function setAppId($appId)
    {
        $this->appId = $appId;
    }

    /**
     * @return string
     */
    public function getAppSecret()
    {
        return $this->appSecret;
    }

    /**
     * @param string $appSecret
     */
    public function setAppSecret($appSecret)
    {
        $this->appSecret = $appSecret;
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * @param string $accessToken
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @param string $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @return string
     */
    public function getApiSecretKey()
    {
        return $this->apiSecretKey;
    }

    /**
     * @param string $apiSecretKey
     */
    public function setApiSecretKey($apiSecretKey)
    {
        $this->apiSecretKey = $apiSecretKey;
    }

    /**
     * @return string
     */
    public function getAccessTokenSecret()
    {
        return $this->accessTokenSecret;
    }

    /**
     * @param string $accessTokenSecret
     */
    public function setAccessTokenSecret($accessTokenSecret)
    {
        $this->accessTokenSecret = $accessTokenSecret;
    }

    /**
     * @return ObjectStorage
     */
    public function getBeGroup(): ObjectStorage
    {
        return $this->beGroup;
    }

    /**
     * @param ObjectStorage $beGroup
     */
    public function setBeGroup(ObjectStorage $beGroup): void
    {
        $this->beGroup = $beGroup;
    }

    /**
     * Check if facebook token is valid
     *
     * @return bool
     */
    public function isValidFacebookAccessToken()
    {
        if (empty($this->accessToken)) {
            return false;
        }

        try {
            $tokenMetadata = $this->getFacebookAccessTokenMetadata();
            $tokenMetadata->validateExpiration();
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Check how much it left for facebook access token
     *
     * @param string $format
     * @return string
     * @throws \Exception
     */
    public function getFacebookAccessTokenValidPeriod($format = '%R%a')
    {
        $expireAt = $this->getFacebookAccessTokenMetadataExpirationDate();
        if ($expireAt !== null) {
            $today = new \DateTime();
            $interval = $today->diff($expireAt);

            return $interval->format($format);
        }

        return 'Could not get expire date of token';
    }

    /**
     * Get date when facebook token expire
     *
     * @return \DateTime|null
     */
    public function getFacebookAccessTokenMetadataExpirationDate()
    {
        $expireAt = $this->getFacebookAccessTokenMetadata()->getExpiresAt();

        if ($expireAt === 0) {
            $dataAccessExpiresAt = (int)$this->getFacebookAccessTokenMetadata()->getField('data_access_expires_at');
            if ($dataAccessExpiresAt > 0) {
                try {
                    return (new \DateTime())->setTimestamp($dataAccessExpiresAt);
                } catch (\Exception $exception) {
                    return null;
                }
            }
        }

        return $expireAt;
    }

    /**
     * Facebook login url
     *
     * @param string $redirectUrl
     * @param array $permissions
     * @return string
     */
    public function getFacebookLoginUrl($redirectUrl, $permissions)
    {
        // required by SDK login
        session_start();

        $fb = $this->getFb();

        $loginHelper = $fb->getRedirectLoginHelper();

        return $loginHelper->getLoginUrl($redirectUrl, $permissions);
    }

    /**
     * Fetch all available pages from facebook
     *
     * @return array
     */
    public function getFacebookPagesIds()
    {
        try {
            $body = $this->getFb()->get('me/accounts')->getDecodedBody();
        } catch (\Exception $exception) {
            $body = null;
        }

        if (isset($body['data'])) {
            $accounts = [
                'me' => LocalizationUtility::translate('module.source_id_me', 'PxaSocialFeed')
            ];
            foreach ($body['data'] as $page) {
                $accounts[$page['id']] = "{$page['name']} (ID: {$page['id']})";
            }
        } else {
            $accounts = ['0' => 'Invalid data. Could not fetch accounts(pages) list from facebook'];
        }

        return $accounts;
    }

    /**
     * Get value for select box
     *
     * @return string
     */
    public function getTitle()
    {
        $type = LocalizationUtility::translate('module.type.' . $this->getType(), 'PxaSocialFeed') ?: '';
        if ($this->getName()) {
            return sprintf('%s (%s)', $type, $this->getName());
        }

        return $type;
    }

    /**
     * Check if is facebook token type
     *
     * @return bool
     */
    public function isFacebookType()
    {
        return $this->type === static::FACEBOOK;
    }

    /**
     * Check if it's of type instagram
     *
     * @return bool
     */
    public function isInstagramType()
    {
        return $this->type === static::INSTAGRAM;
    }

    /**
     * Check if it's of type twitter
     *
     * @return bool
     */
    public function isTwitterType()
    {
        return $this->type === static::TWITTER;
    }

    /**
     * Check if it's of type youtube
     *
     * @return bool
     */
    public function isYoutubeType()
    {
        return $this->type === static::YOUTUBE;
    }

    /**
     * Get FB
     *
     * @return Facebook
     */
    public function getFb()
    {
        if ($this->fb === null) {
            $this->fb = FacebookGraphSdkFactory::getUsingToken($this);
        }

        return $this->fb;
    }

    /**
     * Get facebook token meta data
     *
     * @return AccessTokenMetadata
     */
    protected function getFacebookAccessTokenMetadata()
    {
        $this->initFacebookAccessTokenMetadata();
        return $this->fbTokenMetaData;
    }

    /**
     * Load access token metadata
     */
    protected function initFacebookAccessTokenMetadata()
    {
        if ($this->fbTokenMetaData === null) {
            $fb = $this->getFb();
            $this->fbTokenMetaData = $fb->getOAuth2Client()->debugToken($fb->getDefaultAccessToken());
        }
    }

    /**
     * Return all available types
     *
     * @return array
     */
    public static function getAvailableTokensTypes()
    {
        return [
            static::FACEBOOK,
            static::INSTAGRAM,
            static::TWITTER,
            static::YOUTUBE,
        ];
    }
}
