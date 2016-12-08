<?php

/**
 * HybridAuth
 * http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
 * (c) 2009-2015, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
 */

namespace Mageplaza\SocialLogin\Hybrid;

/**
 * UserContact
 *
 * used to provider the connected user contacts list on a standardized structure across supported social apis.
 *
 * http://hybridauth.sourceforge.net/userguide/Profile_Data_UserContacts.html
 */
class UserContact {

	/**
	 * The Unique contact user ID
	 * @var mixed
	 */
	public $identifier = null;

	/**
	 * User website, blog, web page
	 * @var string
	 */
	public $webSiteURL = null;

	/**
	 * URL link to profile page on the IDp web site
	 * @var string
	 */
	public $profileURL = null;

	/**
	 * URL link to user photo or avatar
	 * @var string
	 */
	public $photoURL = null;

	/**
	 * User displayName provided by the IDp or a concatenation of first and last name
	 * @var string
	 */
	public $displayName = null;

	/**
	 * A short about_me
	 * @var string
	 */
	public $description = null;

	/**
	 * User email. Not all of IDp grant access to the user email
	 * @var string
	 */
	public $email = null;

}
