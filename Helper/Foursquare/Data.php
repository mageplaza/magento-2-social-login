<?php
namespace Mageplaza\SocialLogin\Helper\Foursquare;

use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\SocialLogin\Helper\Data as HelperData;
class Data extends HelperData
{
	const XML_PATH_FOURSQUARE_ENABLED = 'sociallogin/foursquare/is_enabled';
	const XML_PATH_FOURSQUARE_CLIENT_ID = 'sociallogin/foursquare/client_id';
	const XML_PATH_FOURSQUARE_CLIENT_SECRET = 'sociallogin/foursquare/client_secret';
	//const XML_PATH_FOURSQUARE_REDIRECT_URL = 'sociallogin/foursquare/redirect_url';
	const XML_PATH_FOURSQUARE_SEND_PASSWORD = 'sociallogin/foursquare/send_password';

	public function isEnabled($storeId = null)
	{
		return $this->getConfigValue(self::XML_PATH_FOURSQUARE_ENABLED, $storeId);
	}

	public function getClientId($storeId = null)
	{
		return $this->getConfigValue(self::XML_PATH_FOURSQUARE_CLIENT_ID, $storeId);
	}

	public function sendPassword($storeId = null)
	{
		return $this->getConfigValue(self::XML_PATH_FOURSQUARE_SEND_PASSWORD, $storeId);
	}

	public function getClientSecret($storeId = null)
	{
		return $this->getConfigValue(self::XML_PATH_FOURSQUARE_CLIENT_SECRET, $storeId);
	}

//	public function getRedirectUrl($storeId = null)
//	{
//		return $this->getConfigValue(self::XML_PATH_FOURSQUARE_REDIRECT_URL, $storeId);
//	}

	public function getAuthUrl()
	{
		return $this->_getUrl('sociallogin/foursquare/callback', array('_secure' => $this->isSecure(), 'auth' => 1));
	}

	public function getUrl($path)
	{
		return $this->_getUrl($path);
	}


}