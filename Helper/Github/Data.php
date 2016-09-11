<?php
namespace Mageplaza\SocialLogin\Helper\Github;

use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\SocialLogin\Helper\Data as HelperData;
class Data extends HelperData
{
	const XML_PATH_GITHUB_ENABLED = 'sociallogin/github/is_enabled';
	const XML_PATH_GITHUB_CLIENT_ID = 'sociallogin/github/client_id';
	const XML_PATH_GITHUB_CLIENT_SECRET = 'sociallogin/github/client_secret';
	//const XML_PATH_GITHUB_REDIRECT_URL = 'sociallogin/github/redirect_url';
	const XML_PATH_GITHUB_SEND_PASSWORD = 'sociallogin/github/send_password';

	public function isEnabled($storeId = null)
	{
		return $this->getConfigValue(self::XML_PATH_GITHUB_ENABLED, $storeId);
	}

	public function getClientId($storeId = null)
	{
		return $this->getConfigValue(self::XML_PATH_GITHUB_CLIENT_ID, $storeId);
	}

	public function sendPassword($storeId = null)
	{
		return $this->getConfigValue(self::XML_PATH_GITHUB_SEND_PASSWORD, $storeId);
	}

	public function getClientSecret($storeId = null)
	{
		return $this->getConfigValue(self::XML_PATH_GITHUB_CLIENT_SECRET, $storeId);
	}

//	public function getRedirectUrl($storeId = null)
//	{
//		return $this->getConfigValue(self::XML_PATH_GITHUB_REDIRECT_URL, $storeId);
//	}

	public function getAuthUrl()
	{
		return $this->_getUrl('sociallogin/github/callback', array('_secure' => $this->isSecure(), 'auth' => 1));
	}

	public function getUrl($path)
	{
		return $this->_getUrl($path);
	}


}