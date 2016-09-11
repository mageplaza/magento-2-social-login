<?php
namespace Mageplaza\SocialLogin\Helper\Live;

use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\SocialLogin\Helper\Data as HelperData;
class Data extends HelperData
{
	const XML_PATH_LIVE_ENABLED = 'sociallogin/live/is_enabled';
	const XML_PATH_LIVE_CLIENT_ID = 'sociallogin/live/client_id';
	const XML_PATH_LIVE_CLIENT_SECRET = 'sociallogin/live/client_secret';
	//const XML_PATH_LIVE_REDIRECT_URL = 'sociallogin/live/redirect_url';
	const XML_PATH_LIVE_SEND_PASSWORD = 'sociallogin/live/send_password';

	public function isEnabled($storeId = null)
	{
		return $this->getConfigValue(self::XML_PATH_LIVE_ENABLED, $storeId);
	}

	public function getClientId($storeId = null)
	{
		return $this->getConfigValue(self::XML_PATH_LIVE_CLIENT_ID, $storeId);
	}

	public function sendPassword($storeId = null)
	{
		return $this->getConfigValue(self::XML_PATH_LIVE_SEND_PASSWORD, $storeId);
	}

	public function getClientSecret($storeId = null)
	{
		return $this->getConfigValue(self::XML_PATH_LIVE_CLIENT_SECRET, $storeId);
	}

//	public function getRedirectUrl($storeId = null)
//	{
//		return $this->getConfigValue(self::XML_PATH_LIVE_REDIRECT_URL, $storeId);
//	}

	public function getAuthUrl()
	{
		return $this->_getUrl('sociallogin/live/callback', array('_secure' => $this->isSecure(), 'auth' => 1));
	}

	public function getUrl($path)
	{
		return $this->_getUrl($path);
	}


}