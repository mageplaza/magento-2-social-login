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
     *  string
     */
    protected $scope = 'email';

    /**
     * string
     */
    protected $apiBaseUrl = 'https://oauth.zaloapp.com';

    /**
     * string
     */
    protected $authorizeUrl = 'https://oauth.zaloapp.com/v4/permission';

    /**
     * string
     */
    protected $accessTokenUrl = 'https://oauth.zaloapp.com/v4/access_token';

    /**
     * {@inheritdoc}
     */
    protected function getAuthorizeUrl($parameters = [])
    {
        $parameters                             = [
            'code_challenge' => $this->generatePkceCodes(),
        ];
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
            'secret_key' => $this->clientSecret,
            'grant_type' => 'authorization_code',
            'code'       => $code,
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
        $header = [
            "secret_key: {$this->clientSecret}",
            "Content-Type: application/x-www-form-urlencoded",
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, true);
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

    /**
     * @param $text
     *
     * @return string
     */
    public function base64UrlEncode($text)
    {
        $base64    = base64_encode($text);
        $base64    = trim($base64, "=");
        $base64url = strtr($base64, "+/", "-_");

        return $base64url;
    }

    /**
     * @return array
     */
    public function generatePkceCodes()
    {
        $random         = bin2hex(openssl_random_pseudo_bytes(32));
        $code_verifier  = $this->base64UrlEncode(pack('H*', $random));
        $code_challenge = $this->base64UrlEncode(pack('H*', hash('sha256', $code_verifier)));

        return [
            "verifier"  => $code_verifier,
            "challenge" => $code_challenge,
        ];
    }
}
