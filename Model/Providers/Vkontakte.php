<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2015 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

namespace Mageplaza\SocialLogin\Model\Providers;

use Hybridauth\Adapter\OAuth2 as Hybrid_Provider_Model_OAuth2;
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

    public $version = '5.107';

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
        $params['v']       = $this->version;
        $params['fields']  = implode(',', $this->fields);
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
            throw new Exception(
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

        $response          = json_decode(json_encode($response), true)['response'][0];
        $user->identifier  = $response['id'];
        $user->firstName   = $response['first_name'];
        $user->lastName    = $response['last_name'];
        $user->region      = $response['home_town'];
        $user->profileURL  = $response['photo_big'];
        $user->gender      = $response['sex'];
        $user->birthDay    = $response['bdate'];
        $user->city        = $response['city']['title'];
        $user->country     = $response['country']['title'];
        $user->displayName = $response['screen_name'];
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
