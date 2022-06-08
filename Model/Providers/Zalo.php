<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

namespace Mageplaza\SocialLogin\Model\Providers;

use Exception;
use Hybrid_Provider_Model_OAuth2;
use Hybrid_User_Profile;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Serialize\Serializer\Serialize;
use stdClass;
use Hybrid_Auth;
use Hybrid_Logger;

/**
 * Hybrid_Providers_Zalo
 */
class Zalo extends Hybrid_Provider_Model_OAuth2
{
    /**
     * IDp wrappers initializer
     *
     * @throws Exception
     */
    function initialize()
    {
        parent::initialize();

        // Provider api end-points
        $this->api->api_base_url  = 'https://oauth.zaloapp.com';
        $this->api->authorize_url = 'https://oauth.zaloapp.com/v3/permission';
        $this->api->token_url     = 'https://oauth.zaloapp.com/v3/access_token';
    }

    function loginBegin()
    {
        $parameters = [
            'app_id'       => $this->api->client_id,
            'redirect_uri' => $this->api->redirect_uri,
            'state'        => time()
        ];

        Hybrid_Auth::redirect($this->api->authorizeUrl($parameters));
    }

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

    /**
     * @param $code
     *
     * @return StdClass|mixed
     * @throws Exception
     */
    function authenticate($code)
    {
        $params = [
            'app_id'     => $this->api->client_id,
            'app_secret' => $this->api->client_secret,
            'code'       => $code
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
    function loginFinish()
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
     * load the user profile from the IDp api client
     *
     * @return Hybrid_User_Profile
     * @throws Exception
     */
    function getUserProfile()
    {
        $fields   = '&fields=id,birthday,name,gender,picture';
        $response = $this->api->get('https://graph.zalo.me/v2.0/me?access_token=' . $this->api->access_token . $fields);

        if (!isset($response)) {
            throw new Exception("User profile request failed! {$this->providerId} returned an invalid response: " . Hybrid_Logger::dumpData($response),
                6);
        }

        $data = $response;

        $this->user->profile->identifier  = isset($data->id) ? $data->id : '';
        $this->user->profile->firstName   = isset($data->name) ? $data->name : '';
        $this->user->profile->lastName    = isset($data->name) ? $data->name : '';
        $this->user->profile->displayName = isset($data->name) ? trim($data->name) : '';
        $this->user->profile->gender      = isset($data->gender) ? $data->gender : '';

        return $this->user->profile;
    }
}
