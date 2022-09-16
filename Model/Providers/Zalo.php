<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

namespace Mageplaza\SocialLogin\Model\Providers;

use Exception;
use Hybridauth\Adapter\OAuth2 as Hybrid_Provider_Model_OAuth2;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Serialize\Serializer\Serialize;
use stdClass;

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
    protected $authorizeUrl = 'https://oauth.zaloapp.com/v4/permission';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://oauth.zaloapp.com/v3/access_token';

    //    /**
    //     * IDp wrappers initializer
    //     *
    //     * @throws Exception
    //     */
    //    public function initialize()
    //    {
    //        parent::initialize();
    //
    //        // Provider api end-points
    //        $this->api_base_url  = 'https://oauth.zaloapp.com';
    //        $this->authorize_url = 'https://oauth.zaloapp.com/v3/permission';
    //        $this->token_url     = 'https://oauth.zaloapp.com/v3/access_token';
    //    }

    //    public function authenticateBegin()
    //    {
    //        $parameters = [
    //            'app_id'       => $this->clientId,
    //            'redirect_uri' => $this->redirect_uri,
    //            'state'        => time()
    //        ];
    //
    //        Hybrid_Auth::redirect($this->authorizeUrl);
    //    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthorizeUrl($parameters = [])
    {
        $parameters                             = [
            'code_challenge' => $this->generate_pkce_codes(),
        ];
        $this->AuthorizeUrlParameters           = array_merge($parameters, $this->AuthorizeUrlParameters);
        $this->AuthorizeUrlParameters['app_id'] = $this->AuthorizeUrlParameters['client_id'];
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
     * @param $url
     * @param false $params
     * @param string $type
     *
     * @return bool|string
     */
    private function request($url, $params = false, $type = "GET")
    {
        /** @var Serialize $serialize */
        $serialize = $this->getDataObject(Serialize::class);
        Hybrid_Logger::info("Enter OAuth2Client::request($url)");
        Hybrid_Logger::debug("OAuth2Client::request(). dump request params: ", $serialize->serialize($params));

        $urlEncodedParams = http_build_query($params, '', '&');

        if ($type === "GET") {
            $url = $url . (strpos($url, '?') ? '&' : '?') . $urlEncodedParams;
        }
        $this->http_info = [];
        $ch              = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->api->curl_time_out);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->api->curl_useragent);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->api->curl_connect_time_out);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->api->curl_ssl_verifypeer);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->api->curl_header);
        if ($this->api->curl_proxy) {
            curl_setopt($ch, CURLOPT_PROXY, $this->api->curl_proxy);
        }
        if ($type === "POST") {
            curl_setopt($ch, CURLOPT_POST, 1);
            if ($params) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            }
        }
        $response = curl_exec($ch);
        Hybrid_Logger::debug("OAuth2Client::request(). dump request info: ", $serialize->serialize(curl_getinfo($ch)));
        Hybrid_Logger::debug("OAuth2Client::request(). dump request result: ", $serialize->serialize($response));
        $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->http_info = array_merge($this->http_info, curl_getinfo($ch));
        curl_close($ch);

        return $response;
    }

    /***
     * @return StdClass|mixed
     * @throws Exception
     */
    public function authenticatec()
    {
        $params = [
            'app_id'     => $this->apiRequest()->client_id,
            'app_secret' => $this->apiRequest()->client_secret,
        ];

        $response = $this->request($this->api->token_url, $params);

        $response = $this->parseRequestResult($response);

        if (!$response || !isset($response->access_token)) {
            throw new Exception("The Authorization Service has return: " . $response->error);
        }

        if (isset($response->access_token)) {
            $this->api->access_token = $response->access_token;
        }

        if (isset($response->expires_in)) {
            $this->api->access_token_expires_in = $response->expires_in;
        }

        // Calculate when the access token expire.
        if (isset($response->expires_in)) {
            $this->api->access_token_expires_at = time() + $response->expires_in;
        } else {
            $this->api->access_token_expires_at = time() + 3600;
        }

        return $response;
    }

    /**
     * @throws Exception
     */
    public function loginFinish()
    {
        /** @var RequestInterface $request */
        $request = $this->getDataObject(RequestInterface::class);
        $params  = $request->getParams();
        $error   = (array_key_exists('error', $params)) ? $params['error'] : "";

        // Check for errors
        if ($error) {
            throw new Exception("Authentication failed! {$this->providerId} returned an error: $error", 5);
        }
        // Try to authenticate user
        $code = (array_key_exists('code', $params)) ? $params['code'] : "";

        try {
            $this->authenticate($code);
        } catch (Exception $e) {
            throw new Exception(
                "User profile request failed! {$this->providerId} returned an error: {$e->getMessage()} ",
                6
            );
        }
        // Check if authenticated
        if (!$this->api->access_token) {
            throw new Exception("Authentication failed! {$this->providerId} returned an invalid access token.", 5);
        }
        // Store tokens
        $this->token("access_token", $this->api->access_token);
        $this->token("expires_in", $this->api->access_token_expires_in);
        // Set user connected locally
        $this->setUserConnected();
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
     * @param $result
     *
     * @return StdClass|mixed
     */
    private function parseRequestResult($result)
    {
        if (json_decode($result)) {
            return json_decode($result);
        }
        parse_str($result, $output);
        $result = new StdClass();
        foreach ($output as $k => $v) {
            $result->$k = $v;
        }

        return $result;
    }

    /**
     * @throws \Hybridauth\Exception\HttpRequestFailedException
     * @throws \Hybridauth\Exception\HttpClientFailureException
     * @throws \Hybridauth\Exception\InvalidAccessTokenException
     */
    public function getUserProfiled()
    {
        $fields = '&fields=id,birthday,name,gender,picture';

        $fields = [
            'id',
            'name',
            'first_name',
            'last_name',
            'email'
        ];

        // Note that en_US is needed for gender fields to match convention.
        $locale   = $this->config->get('locale') ?: 'en_US';
        $response = $this->apiRequest('me', 'GET', [
            'fields' => implode(',', $fields),
            'locale' => $locale,
        ]);

        $data = new \Data\Collection($response);

        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier  = $data->get('id');
        $userProfile->displayName = $data->get('name');
        $userProfile->firstName   = $data->get('first_name');
        $userProfile->lastName    = $data->get('last_name');
        $userProfile->profileURL  = $data->get('link');
        $userProfile->webSiteURL  = $data->get('website');
        $userProfile->gender      = $data->get('gender');
        $userProfile->language    = $data->get('locale');
        $userProfile->description = $data->get('about');
        $userProfile->email       = $data->get('email');

        // Fallback for profile URL in case Facebook does not provide "pretty" link with username (if user set it).
        if (empty($userProfile->profileURL)) {
            $userProfile->profileURL = $this->getProfileUrl($userProfile->identifier);
        }

        $userProfile->region = $data->filter('hometown')->get('name');

        $photoSize = $this->config->get('photo_size') ?: '150';

        $userProfile->photoURL = $this->apiBaseUrl . $userProfile->identifier;
        $userProfile->photoURL .= '/picture?width=' . $photoSize . '&height=' . $photoSize;

        $userProfile->emailVerified = $userProfile->email;

        $userProfile = $this->fetchUserRegion($userProfile);

        $userProfile = $this->fetchBirthday($userProfile, $data->get('birthday'));

        return $userProfile;
    }

    public function base64url_encode($text)
    {
        $base64    = base64_encode($text);
        $base64    = trim($base64, "=");
        $base64url = strtr($base64, "+/", "-_");

        return $base64url;
    }

    public function generate_state_param()
    {
        return bin2hex(openssl_random_pseudo_bytes(4));
    }

    public function generate_pkce_codes()
    {
        $random         = bin2hex(openssl_random_pseudo_bytes(32)); // a random 64-digit hex
        $code_verifier  = $this->base64url_encode(pack('H*', $random));
        $code_challenge = $this->base64url_encode(pack('H*', hash('sha256', $code_verifier)));

        return [
            "verifier"  => $code_verifier,
            "challenge" => $code_challenge
        ];
    }
}
