<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2012 HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

namespace Mageplaza\SocialLogin\Model\Providers;

/**
 * Hybrid_Providers_Instagram (By Sebastian Lasse - https://github.com/sebilasse)
 */
class Instagram extends \Hybrid_Provider_Model_OAuth2
{
    // default permissions
    public $scope = "basic";

    /**
     * IDp wrappers initializer
     * @throws \Exception
     */
    function initialize()
    {
        parent::initialize();

        // Provider api end-points
        $this->api->api_base_url  = "https://api.instagram.com/v1/";
        $this->api->authorize_url = "https://api.instagram.com/oauth/authorize/";
        $this->api->token_url     = "https://api.instagram.com/oauth/access_token";
    }

    /**
     * security: Enforce signed requests
     */
    function generateSig($endpoint, $params, $secret)
    {
        $sig = $endpoint;
        ksort($params);
        foreach ($params as $key => $val) {
            $sig .= "|$key=$val";
        }

        return hash_hmac('sha256', $sig, $secret, false);
    }

    /**
     * load the user profile from the IDp api client
     * @return \Hybrid_User_Profile
     * @throws \Exception
     */
    function getUserProfile()
    {
        $endpoint         = '/users/self';
        $params           = [
            'access_token' => $this->api->access_token,
        ];
        $sig              = $this->generateSig($endpoint, $params, $this->api->client_secret);
        $params           = [
            "sig" => $sig
        ];
        $urlEncodedParams = http_build_query($params, '', '&');

        $url  = "users/self/" . (strpos("users/self/", '?') ? '&' : '?') . $urlEncodedParams;
        $data = $this->api->api($url);
        if ($data->meta->code != 200) {
            throw new \Exception("User profile request failed! {$this->providerId} returned an invalid response.", 6);
        }

        $this->user->profile->identifier  = $data->data->id;
        $this->user->profile->displayName = $data->data->full_name ? $data->data->full_name : $data->data->username;
        $this->user->profile->description = $data->data->bio;
        $this->user->profile->photoURL    = $data->data->profile_picture;

        $this->user->profile->webSiteURL = $data->data->website;

        $this->user->profile->username = $data->data->username;

        return $this->user->profile;
    }
}
