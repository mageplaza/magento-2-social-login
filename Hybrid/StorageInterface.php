<?php

/**
 * HybridAuth
 * http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
 * (c) 2009-2015, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
 */

namespace Mageplaza\SocialLogin\Hybrid;

/**
 * HybridAuth storage manager interface
 */
interface StorageInterface {

	public function config($key, $value = null);

	public function get($key);

	public function set($key, $value);

	function clear();

	function delete($key);

	function deleteMatch($key);

	function getSessionData();

	function restoreSessionData($sessiondata = null);
}
