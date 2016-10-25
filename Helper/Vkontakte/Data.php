<?php
namespace Mageplaza\SocialLogin\Helper\Vkontakte;

use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\SocialLogin\Helper\Data as HelperData;
class Data extends HelperData
{
	const XML_PATH_VKONTAKTE_ENABLED = 'sociallogin/vkontakte/is_enabled';
	const XML_PATH_VKONTAKTE_CLIENT_ID = 'sociallogin/vkontakte/client_id';
	const XML_PATH_VKONTAKTE_CLIENT_SECRET = 'sociallogin/vkontakte/client_secret';
	//const XML_PATH_VKONTAKTE_REDIRECT_URL = 'sociallogin/vkontakte/redirect_url';
	const XML_PATH_VKONTAKTE_SEND_PASSWORD = 'sociallogin/vkontakte/send_password';

	public function isEnabled($storeId = null)
	{
		return $this->getConfigValue(self::XML_PATH_VKONTAKTE_ENABLED, $storeId);
	}

	public function getClientId($storeId = null)
	{
		return $this->getConfigValue(self::XML_PATH_VKONTAKTE_CLIENT_ID, $storeId);
	}

	public function sendPassword($storeId = null)
	{
		return $this->getConfigValue(self::XML_PATH_VKONTAKTE_SEND_PASSWORD, $storeId);
	}

	public function getClientSecret($storeId = null)
	{
		return $this->getConfigValue(self::XML_PATH_VKONTAKTE_CLIENT_SECRET, $storeId);
	}

//	public function getRedirectUrl($storeId = null)
//	{
//		return $this->getConfigValue(self::XML_PATH_VKONTAKTE_REDIRECT_URL, $storeId);
//	}

	public function getAuthUrl()
	{
		return $this->_getUrl('sociallogin/vkontakte/callback', array('_secure' => $this->isSecure(), 'auth' => 1));
	}

	public function getUrl($path)
	{
		return $this->_getUrl($path);
	}


}