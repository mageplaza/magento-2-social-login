<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2012 HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

namespace Mageplaza\SocialLogin\Model\Providers;

use Exception;
use Hybrid_Logger;
use Hybrid_Provider_Model_OAuth2;

/**
 * Hybrid_Providers_Yahoo (By Satish Gumudavelly - https://github.com/mageseller)
 */
class Yahoo extends Hybrid_Provider_Model_OAuth2
{
    // default permissions
    public $scope = ["profile", "email"];

    /**
     * IDp wrappers initializer
     *
     * @throws Exception
     */
    public function initialize()
    {
        parent::initialize();

        // Provider api end-points
        $this->api->api_base_url = "https://api.login.yahoo.com/openid/v1/";
        $this->api->authorize_url = "https://api.login.yahoo.com/oauth2/request_auth";
        $this->api->token_url = "https://api.login.yahoo.com/oauth2/get_token";
        // Set token headers.
        $this->setAuthorizationHeaders("basic");
    }

    /**
     * Set correct Authorization headers.
     *
     * @param string $token_type
     *   Specify token type.
     *
     * @return void
     */
    private function setAuthorizationHeaders($token_type)
    {
        switch ($token_type) {
            case "basic":
                // The /get_token requires authorization header.
                $token = base64_encode("{$this->config["keys"]["id"]}:{$this->config["keys"]["secret"]}");
                $this->api->curl_header = [
                    "Authorization: Basic {$token}"
                ];
                break;

            case "bearer":
                // Yahoo API requires the token to be passed as a Bearer within the authorization header.
                $this->api->curl_header = [
                    "Authorization: Bearer {$this->api->access_token}",
                ];
                break;
        }
    }

    public function loginBegin()
    {
        if (is_array($this->scope)) {
            $this->scope = implode(",", $this->scope);
        }
        parent::loginBegin();
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $this->setAuthorizationHeaders("bearer");
        $response = $this->api->api('userinfo');
        if (!isset($response->sub)) {
            throw new Exception("User profile request failed! {$this->providerId} returned an invalid response: " . Hybrid_Logger::dumpData($response), 6);
        }
        $data = $response;
        $this->user->profile->identifier = $data->sub ?? "";
        $this->user->profile->firstName = $data->given_name ?? "";
        $this->user->profile->lastName = $data->family_name ?? "";
        $this->user->profile->displayName = $data->name ?? "";
        $this->user->profile->gender = $data->gender ?? "";
        $this->user->profile->language = $data->locale ?? "";
        $this->user->profile->email = $data->email ?? "";

        $this->user->profile->emailVerified = $data->email_verified ?? false ? $this->user->profile->email : '';

        $profileImages = $data->profile_images;
        $prop = 'image192';
        $this->user->profile->photoURL = $profileImages->$prop ?? "";


        return $this->user->profile;
    }
}
