<?php

/* !
 * HybridAuth
 * http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
 * (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
 */

namespace Mageplaza\SocialLogin\Hybrid\Providers;

use Mageplaza\SocialLogin\Hybrid\ProviderModelOpenID;

/**
 * AOL provider adapter based on OpenID protocol
 * 
 * http://hybridauth.sourceforge.net/userguide/IDProvider_info_AOL.html
 */
class AOL extends ProviderModelOpenID {

	var $openidIdentifier = "http://openid.aol.com/";

}
