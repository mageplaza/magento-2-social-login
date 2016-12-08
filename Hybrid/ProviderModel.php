<?php

/**
 * HybridAuth
 * http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
 * (c) 2009-2015, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
 */

namespace Mageplaza\SocialLogin\Hybrid;

/**
 * ProviderModel provide a common interface for supported IDps on HybridAuth.
 *
 * Basically, each provider adapter has to define at least 4 methods:
 *   Providers_{provider_name}::initialize()
 *   Providers_{provider_name}::loginBegin()
 *   Providers_{provider_name}::loginFinish()
 *   Providers_{provider_name}::getUserProfile()
 *
 * HybridAuth also come with three others models
 *   Class ProviderModelOpenID for providers that uses the OpenID 1 and 2 protocol.
 *   Class ProviderModelOAuth1 for providers that uses the OAuth 1 protocol.
 *   Class ProviderModelOAuth2 for providers that uses the OAuth 2 protocol.
 */
abstract class ProviderModel {

  /**
   * IDp ID (or unique name)
   * @var mixed
   */
  public $providerId = null;

  /**
   * Specific provider adapter config
   * @var array
   */
  public $config = null;

  /**
   * Provider extra parameters
   * @var array
   */
  public $params = null;

  /**
   * Endpoint URL for that provider
   * @var string
   */
  public $endpoint = null;

  /**
   * User obj, represents the current loggedin user
   * @var User
   */
  public $user = null;

  /**
   * The provider api client (optional)
   * @var \stdClass
   */
  public $api = null;

  /**
   * Model should use "gzip,deflate" for CURLOPT_ENCODING
   * @var \stdClass
   */
  public $compressed = false;

  /**
   * Common providers adapter constructor
   *
   * @param mixed $providerId Provider ID
   * @param array $config     Provider adapter config
   * @param array $params     Provider extra params
   */
  function __construct($providerId, $config, $params = null) {
    # init the IDp adapter parameters, get them from the cache if possible
    if (!$params) {
      $this->params = Auth::storage()->get("hauth_session.$providerId.id_provider_params");
    } else {
      $this->params = $params;
    }

    // idp id
    $this->providerId = $providerId;

    // set HybridAuth endpoint for this provider
    $this->endpoint = Auth::storage()->get("hauth_session.$providerId.hauth_endpoint");

    // idp config
    $this->config = $config;

    // new user instance
    $this->user = new User();
    $this->user->providerId = $providerId;

    // initialize the current provider adapter
    $this->initialize();

    Logger::debug("ProviderModel::__construct( $providerId ) initialized. dump current adapter instance: ", serialize($this));
  }

  /**
   * IDp wrappers initializer
   *
   * The main job of wrappers initializer is to performs (depend on the IDp api client it self):
   *     - include some libs needed by this provider,
   *     - check IDp key and secret,
   *     - set some needed parameters (stored in $this->params) by this IDp api client
   *     - create and setup an instance of the IDp api client on $this->api
   *
   * @return void
   * @throws Exception
   */
  abstract public function initialize();

  /**
   * Begin login
   *
   * @return void
   * @throws Exception
   */
  abstract public function loginBegin();

  /**
   * Finish login
   * @return void
   * @throws Exception
   */
  abstract public function loginFinish();

  /**
   * Generic logout, just erase current provider adapter stored data to let Auth all forget about it
   * @return bool
   */
  function logout() {
    Logger::info("Enter [{$this->providerId}]::logout()");
    $this->clearTokens();
    return true;
  }

  /**
   * Grab the user profile from the IDp api client
   * @return UserProfile
   * @throw Exception
   */
  function getUserProfile() {
    Logger::error("HybridAuth do not provide users contacts list for {$this->providerId} yet.");
    throw new Exception("Provider does not support this feature.", 8);
  }

  /**
   * Load the current logged in user contacts list from the IDp api client
   * @return UserContact[]
   * @throws Exception
   */
  function getUserContacts() {
    Logger::error("HybridAuth do not provide users contacts list for {$this->providerId} yet.");
    throw new Exception("Provider does not support this feature.", 8);
  }

  /**
   * Return the user activity stream
   * @return UserActivity[]
   * @throws Exception
   */
  function getUserActivity($stream) {
    Logger::error("HybridAuth do not provide user's activity stream for {$this->providerId} yet.");
    throw new Exception("Provider does not support this feature.", 8);
  }

  /**
   * Set user status
   * @return mixed Provider response
   * @throws Exception
   */
  function setUserStatus($status) {
    Logger::error("HybridAuth do not provide user's activity stream for {$this->providerId} yet.");
    throw new Exception("Provider does not support this feature.", 8);
  }

  /**
   * Return the user status
   * @return mixed Provider response
   * @throws Exception
   */
  function getUserStatus($statusid) {
    Logger::error("HybridAuth do not provide user's status for {$this->providerId} yet.");
    throw new Exception("Provider does not support this feature.", 8);
  }

  /**
   * Return true if the user is connected to the current provider
   * @return bool
   */
  public function isUserConnected() {
    return (bool) Auth::storage()->get("hauth_session.{$this->providerId}.is_logged_in");
  }

  /**
   * Set user to connected
   * @return void
   */
  public function setUserConnected() {
    Logger::info("Enter [{$this->providerId}]::setUserConnected()");
    Auth::storage()->set("hauth_session.{$this->providerId}.is_logged_in", 1);
  }

  /**
   * Set user to unconnected
   * @return void
   */
  public function setUserUnconnected() {
    Logger::info("Enter [{$this->providerId}]::setUserUnconnected()");
    Auth::storage()->set("hauth_session.{$this->providerId}.is_logged_in", 0);
  }

  /**
   * Get or set a token
   * @return string
   */
  public function token($token, $value = null) {
    if ($value === null) {
      return Auth::storage()->get("hauth_session.{$this->providerId}.token.$token");
    } else {
      Auth::storage()->set("hauth_session.{$this->providerId}.token.$token", $value);
    }
  }

  /**
   * Delete a stored token
   * @return void
   */
  public function deleteToken($token) {
    Auth::storage()->delete("hauth_session.{$this->providerId}.token.$token");
  }

  /**
   * Clear all existent tokens for this provider
   * @return void
   */
  public function clearTokens() {
    Auth::storage()->deleteMatch("hauth_session.{$this->providerId}.");
  }

}
