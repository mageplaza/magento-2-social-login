<?php

/**
 * HybridAuth
 * http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
 * (c) 2009-2015, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
 */

namespace Mageplaza\SocialLogin\Hybrid;

/**
 * Endpoint class
 *
 * Provides a simple way to handle the OpenID and OAuth endpoint
 */
class Endpoint {

	protected $request = null;
	protected $initDone = false;

	/**
	 * Process the current request
	 *
	 * @param array $request The current request parameters. Leave as null to default to use $_REQUEST.
	 */
	public function __construct($request = null) {
		if (is_null($request)) {
			// Fix a strange behavior when some provider call back ha endpoint
			// with /index.php?hauth.done={provider}?{args}...
			// >here we need to parse $_SERVER[QUERY_STRING]
			$request = $_REQUEST;
			if (isset($_SERVER["QUERY_STRING"]) && strrpos($_SERVER["QUERY_STRING"], '?')) {
				$_SERVER["QUERY_STRING"] = str_replace("?", "&", $_SERVER["QUERY_STRING"]);
				parse_str($_SERVER["QUERY_STRING"], $request);
			}
		}

		// Setup request variable
		$this->request = $request;

		// If openid_policy requested, we return our policy document
		if (isset($this->request["get"]) && $this->request["get"] == "openid_policy") {
			$this->processOpenidPolicy();
		}

		// If openid_xrds requested, we return our XRDS document
		if (isset($this->request["get"]) && $this->request["get"] == "openid_xrds") {
			$this->processOpenidXRDS();
		}

		// If we get a hauth.start
		if (isset($this->request["hauth_start"]) && $this->request["hauth_start"]) {
			$this->processAuthStart();
		}
		// Else if hauth.done
		elseif (isset($this->request["hauth_done"]) && $this->request["hauth_done"]) {
			$this->processAuthDone();
		}
		// Else we advertise our XRDS document, something supposed to be done from the Realm URL page
		else {
			$this->processOpenidRealm();
		}
	}

	/**
	 * Process the current request
	 *
	 * @param array $request The current request parameters. Leave as null to default to use $_REQUEST.
	 * @return Endpoint
	 */
	public static function process($request = null) {
		// Trick for PHP 5.2, because it doesn't support late static binding
		$class = function_exists('get_called_class') ? get_called_class() : __CLASS__;
		new $class($request);
	}

	/**
	 * Process OpenID policy request
	 * @return void
	 */
	protected function processOpenidPolicy() {
		$output = file_get_contents(dirname(__FILE__) . "/resources/openid_policy.html");
		print $output;
		die();
	}

	/**
	 * Process OpenID XRDS request
	 * @return void
	 */
	protected function processOpenidXRDS() {
		header("Content-Type: application/xrds+xml");

		$output = str_replace("{RETURN_TO_URL}", str_replace(
						array("<", ">", "\"", "'", "&"), array("&lt;", "&gt;", "&quot;", "&apos;", "&amp;"), Auth::getCurrentUrl(false)
				), file_get_contents(dirname(__FILE__) . "/resources/openid_xrds.xml"));
		print $output;
		die();
	}

	/**
	 * Process OpenID realm request
	 * @return void
	 */
	protected function processOpenidRealm() {
		$output = str_replace("{X_XRDS_LOCATION}", htmlentities(Auth::getCurrentUrl(false), ENT_QUOTES, 'UTF-8')
				. "?get=openid_xrds&v="
				. Auth::$version, file_get_contents(dirname(__FILE__) . "/resources/openid_realm.html"));
		print $output;
		die();
	}

	/**
	 * Define: endpoint step 3
	 * @return void
	 * @throws Exception
	 */
	protected function processAuthStart() {
		$this->authInit();

		$provider_id = trim(strip_tags($this->request["hauth_start"]));

		// check if page accessed directly
		if (!Auth::storage()->get("hauth_session.$provider_id.hauth_endpoint")) {
			Logger::error("Endpoint: hauth_endpoint parameter is not defined on hauth_start, halt login process!");

			throw new Exception("You cannot access this page directly.");
		}

		// define:hybrid.endpoint.php step 2.
		$hauth = Auth::setup($provider_id);

		// if REQUESTed hauth_idprovider is wrong, session not created, etc.
		if (!$hauth) {
			Logger::error("Endpoint: Invalid parameter on hauth_start!");
			throw new Exception("Invalid parameter! Please return to the login page and try again.");
		}

		try {
			Logger::info("Endpoint: call adapter [{$provider_id}] loginBegin()");

			$hauth->adapter->loginBegin();
		} catch (Exception $e) {
			Logger::error("Exception:" . $e->getMessage(), $e);
			Error::setError($e->getMessage(), $e->getCode(), $e->getTraceAsString(), $e->getPrevious());

			$hauth->returnToCallbackUrl();
		}

		die();
	}

	/**
	 * Define: endpoint step 3.1 and 3.2
	 * @return void
	 * @throws Exception
	 */
	protected function processAuthDone() {
		$this->authInit();

		$provider_id = trim(strip_tags($this->request["hauth_done"]));

		$hauth = Auth::setup($provider_id);

		if (!$hauth) {
			Logger::error("Endpoint: Invalid parameter on hauth_done!");

			$hauth->adapter->setUserUnconnected();

			throw new Exception("Invalid parameter! Please return to the login page and try again.");
		}

		try {
			Logger::info("Endpoint: call adapter [{$provider_id}] loginFinish() ");
			$hauth->adapter->loginFinish();
		} catch (Exception $e) {
			Logger::error("Exception:" . $e->getMessage(), $e);
			Error::setError($e->getMessage(), $e->getCode(), $e->getTraceAsString(), $e->getPrevious());

			$hauth->adapter->setUserUnconnected();
		}

		Logger::info("Endpoint: job done. return to callback url.");

		$hauth->returnToCallbackUrl();
		die();
	}

	/**
	 * Initializes authentication
	 * @throws Exception
	 */
	protected function authInit() {
		if (!$this->initDone) {
			$this->initDone = true;

			// Init Auth
			try {
				if (!class_exists("Storage", false)) {
					require_once realpath(dirname(__FILE__)) . "/Storage.php";
				}
				if (!class_exists("Exception", false)) {
					require_once realpath(dirname(__FILE__)) . "/Exception.php";
				}
				if (!class_exists("Logger", false)) {
					require_once realpath(dirname(__FILE__)) . "/Logger.php";
				}

				$storage = new Storage();

				// Check if Auth session already exist
				if (!$storage->config("CONFIG")) {
					throw new Exception("You cannot access this page directly.");
				}

				Auth::initialize($storage->config("CONFIG"));
			} catch (Exception $e) {
				Logger::error("Endpoint: Error while trying to init Auth: " . $e->getMessage());
				throw new Exception( "Endpoint: Error while trying to init Auth: " . $e->getMessage(), $e->getCode(), $e );
			}
		}
	}

}
