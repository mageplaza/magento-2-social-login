<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category  Mageplaza
 * @package   Mageplaza_SocialLogin
 * @copyright Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license   https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\SocialLogin\Model\Providers;

use Hybridauth\Adapter\OAuth2 as Hybrid_Provider_Model_OAuth2;
use Hybridauth\Data\Collection;
use Hybridauth\Exception\Exception;
use Hybridauth\Exception\HttpClientFailureException;
use Hybridauth\Exception\HttpRequestFailedException;
use Hybridauth\Exception\InvalidAccessTokenException;
use Hybridauth\User\Profile;
use Magento\Framework\App\ObjectManager;
use RuntimeException;

/**
 * Class Vkontakte
 * @package Mageplaza\SocialLogin\Model\Providers
 */
class Vkontakte extends Hybrid_Provider_Model_OAuth2
{
    public $scope = 'email';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.vk.com/method/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://oauth.vk.com/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://oauth.vk.com/token';

    /**
     * @var string
     */
    public $version = '5.107';

    /**
     * @var array
     */
    public $fields = [
        'identifier'  => 'id',
        'firstName'   => 'first_name',
        'lastName'    => 'last_name',
        'displayName' => 'screen_name',
        'gender'      => 'sex',
        'photoURL'    => 'photo_big',
        'home_town'   => 'home_town',
        'profileURL'  => 'domain',
        'nickname'    => 'nickname',
        'bdate'       => 'bdate',
        'timezone'    => 'timezone',
        'photo_rec'   => 'photo_rec',
        'domain'      => 'domain',
        'photo_max'   => 'photo_max',
        'home_phone'  => 'home_phone',
        'city'        => 'city',
        'country'     => 'country',
    ];

    /**
     * @param $class
     *
     * @return mixed
     */
    public function getDataObject($class)
    {
        $objectManager = ObjectManager::getInstance();

        return $objectManager->create($class);
    }

    public function initialize()
    {
        parent::initialize();

        if (property_exists($this->config, 'version')) {
            $this->version = $this->config['version'];
        }
        if (property_exists($this->config, 'v')) {
            $this->version = $this->config['v'];
        }
    }

    /**
     * @return Profile|void
     *
     * @throws HttpClientFailureException
     * @throws HttpRequestFailedException
     * @throws InvalidAccessTokenException
     * @throws Exception
     */
    public function getUserProfile()
    {
        $params['v']        = $this->version;
        $params['fields']   = implode(',', $this->fields);
        $params['user_ids'] = $this->getStoredData('user_id');

        $response = $this->apiRequest('users.get', 'GET', $params);

        if (isset($response->error)) {
            throw new RuntimeException(
                "User profile request failed!
                 {$this->providerId} returned an error #{$response->error->error_code}:
                  {$response->error->error_msg}",
                6
            );
        }

        if (!isset($response->response[0], $response->response[0]->id)) {
            throw new RuntimeException(
                "User profile request failed! {$this->providerId} returned an invalid response.",
                6
            );
        }

        $userProfile = $this->getUserByResponse($response);

        return $userProfile;
    }

    /**
     * @param $response
     *
     * @return Profile
     */
    public function getUserByResponse($response)
    {
        $user = new Profile();

        $userData = new Collection($response->response[0]);

        $user->identifier  = $userData->get('id');
        $user->firstName   = $userData->get('first_name');
        $user->lastName    = $userData->get('last_name');
        $user->region      = $userData->get('home_town');
        $user->profileURL  = $userData->get('photo_big');
        $user->gender      = $userData->get('sex');
        $user->birthDay    = $userData->get('bdate');
        $user->city        = $userData->get('city') ? $userData->get('city')->title : "";
        $user->country     = $userData->get('country') ? $userData->get('country')->title : "";
        $user->displayName = $userData->get('screen_name');
        $user->email       = $this->getStoredData('email');

        if (isset($user->gender)) {
            switch ($user->gender) {
                case 1:
                    $user->gender = 'female';
                    break;

                case 2:
                    $user->gender = 'male';
                    break;

                default:
                    $user->gender = null;
                    break;
            }
        }

        if (!empty($user->birthDay)) {
            $birthday = explode('.', $user->birthDay);
            switch (count($birthday)) {
                case 3:
                    $user->birthDay   = (int) $birthday[0];
                    $user->birthMonth = (int) $birthday[1];
                    $user->birthYear  = (int) $birthday[2];
                    break;

                case 2:
                    $user->birthDay   = (int) $birthday[0];
                    $user->birthMonth = (int) $birthday[1];
                    break;
            }
        }

        return $user;
    }

    /**
     * @inheritdoc
     */
    protected function validateAccessTokenExchange($response)
    {
        $collection = parent::validateAccessTokenExchange($response);
        $this->storeData('user_id', $collection->get('user_id'));
        $this->storeData('email', $collection->get('email'));

        return $collection;
    }
}
