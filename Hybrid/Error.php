<?php

/**
 * HybridAuth
 * http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
 * (c) 2009-2015, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
 */

namespace Mageplaza\SocialLogin\Hybrid;

/**
 * Errors manager
 *
 * HybridAuth errors are stored in Hybrid::storage() and not displayed directly to the end user
 */
class Error {

	/**
	 * Store error in session
	 *
	 * @param string $message  Error message
	 * @param int    $code     Error code
	 * @param string $trace    Back trace
	 * @param string $previous Previous exception
	 */
	public static function setError($message, $code = null, $trace = null, $previous = null) {
		Logger::info("Enter Error::setError( $message )");

		Auth::storage()->set("hauth_session.error.status", 1);
		Auth::storage()->set("hauth_session.error.message", $message);
		Auth::storage()->set("hauth_session.error.code", $code);
		Auth::storage()->set("hauth_session.error.trace", $trace);
		Auth::storage()->set("hauth_session.error.previous", $previous);
	}

	/**
	 * Clear the last error
	 * @return void
	 */
	public static function clearError() {
		Logger::info("Enter Error::clearError()");

		Auth::storage()->delete("hauth_session.error.status");
		Auth::storage()->delete("hauth_session.error.message");
		Auth::storage()->delete("hauth_session.error.code");
		Auth::storage()->delete("hauth_session.error.trace");
		Auth::storage()->delete("hauth_session.error.previous");
	}

	/**
	 * Checks to see if there is a an error.
	 * @return boolean true if there is an error.
	 */
	public static function hasError() {
		return (bool) Auth::storage()->get("hauth_session.error.status");
	}

	/**
	 * Return error message
	 * @return string
	 */
	public static function getErrorMessage() {
		return Auth::storage()->get("hauth_session.error.message");
	}

	/**
	 * Return error code
	 * @return int
	 */
	public static function getErrorCode() {
		return Auth::storage()->get("hauth_session.error.code");
	}

	/**
	 * Return string detailed error backtrace as string
	 * @return string
	 */
	public static function getErrorTrace() {
		return Auth::storage()->get("hauth_session.error.trace");
	}

	/**
	 * Detailed error backtrace as string
	 * @return string
	 */
	public static function getErrorPrevious() {
		return Auth::storage()->get("hauth_session.error.previous");
	}

}
