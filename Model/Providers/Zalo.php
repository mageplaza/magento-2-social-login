<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

namespace Mageplaza\SocialLogin\Model\Providers;

use Hybridauth\Adapter\OAuth2 as Hybrid_Provider_Model_OAuth2;
use Hybridauth\Data\Collection;
use Hybridauth\Exception\HttpClientFailureException;
use Hybridauth\Exception\HttpRequestFailedException;
use Hybridauth\Exception\InvalidAccessTokenException;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\User\Profile;
use Magento\Framework\App\ObjectManager;
use RuntimeException;

/**
 * Class Zalo
 * @package Mageplaza\SocialLogin\Model\Providers
 */
class Zalo extends Hybrid_Provider_Model_OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'email';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://oauth.zaloapp.com';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://oauth.zaloapp.com/v3/permission';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://oauth.zaloapp.com/v3/access_token';

    /**
     * {@inheritdoc}
     */
    protected function getAuthorizeUrl($parameters = [])
    {

        $this->AuthorizeUrlParameters           = array_merge($parameters, $this->AuthorizeUrlParameters);
        $this->AuthorizeUrlParameters['app_id'] = $this->AuthorizeUrlParameters['client_id'];
        unset($this->AuthorizeUrlParameters['client_id']);
        if ($this->supportRequestState) {
            if (!isset($this->AuthorizeUrlParameters['state'])) {
                $this->AuthorizeUrlParameters['state'] = 'HA-' . str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890');
            }
            $this->storeData('authorization_state', $this->AuthorizeUrlParameters['state']);
        }

        $queryParams = http_build_query($this->AuthorizeUrlParameters, '', '&', $this->AuthorizeUrlParametersEncType);

        return $this->authorizeUrl . '?' . $queryParams;
    }

    /**
     * {@inheritdoc}
     */
    protected function exchangeCodeForAccessToken($code)
    {
        $url              = $this->accessTokenUrl;
        $params           = [
            'app_id'     => $this->clientId,
            'app_secret' => $this->clientSecret,
            'code'       => $code
        ];
        $urlEncodedParams = http_build_query($params, '', '&');
        $url              = $url . (strpos($url, '?') ? '&' : '?') . $urlEncodedParams;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt(
            $ch,
            CURLOPT_USERAGENT,
            "OAuth/2 Simple PHP Client v0.1.1; HybridAuth http://hybridauth.sourceforge.net/"
        );
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, []);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

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

    /**
     * @return Profile
     * @throws UnexpectedApiResponseException
     * @throws HttpClientFailureException
     * @throws HttpRequestFailedException
     * @throws InvalidAccessTokenException
     */
    public function getUserProfile()
    {
        $fields           = '&fields=id,birthday,name,gender,picture';
        $accessProfileUrl = 'https://graph.zalo.me/v2.0/me?access_token='
            . $this->getStoredData('access_token') . $fields;
        $response         = $this->apiRequest($accessProfileUrl);

        if (!isset($response)) {
            throw new RuntimeException(
                "User profile request failed! 
                {$this->providerId} returned an invalid response: " . $this->setLogger($response),
                6
            );
        }
        $data = new Collection($response);
        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile              = new Profile();
        $userProfile->identifier  = $data->get('id');
        $userProfile->firstName   = $data->get('name');
        $userProfile->profileURL  = isset($data->get('picture')->data) ? $data->get('picture')->data->url : "";
        $userProfile->gender      = $data->get('gender');
        $userProfile->email       = $data->get('email');
        $userProfile->displayName = $data->get('name');

        return $userProfile;
    }
}
