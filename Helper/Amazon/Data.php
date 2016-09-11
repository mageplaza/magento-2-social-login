<?php
namespace Mageplaza\SocialLogin\Helper\Amazon;

use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\SocialLogin\Helper\Data as HelperData;
class Data extends HelperData
{
	const XML_PATH_AMAZON_ENABLED = 'sociallogin/amazon/is_enabled';
	const XML_PATH_AMAZON_CLIENT_ID = 'sociallogin/amazon/client_id';
	//const XML_PATH_AMAZON_CLIENT_SECRET = 'sociallogin/amazon/client_secret';
	//const XML_PATH_AMAZON_REDIRECT_URL = 'sociallogin/amazon/redirect_url';
	const XML_PATH_AMAZON_SEND_PASSWORD = 'sociallogin/amazon/send_password';

	public function isEnabled($storeId = null)
	{
		return $this->getConfigValue(self::XML_PATH_AMAZON_ENABLED, $storeId);
	}

	public function getClientId($storeId = null)
	{
		return $this->getConfigValue(self::XML_PATH_AMAZON_CLIENT_ID, $storeId);
	}

	public function sendPassword($storeId = null)
	{
		return $this->getConfigValue(self::XML_PATH_AMAZON_SEND_PASSWORD, $storeId);
	}


	public function getAuthUrl()
	{
		return $this->_getUrl('sociallogin/amazon/callback', array('_secure' => $this->isSecure(), 'auth' => 1));
	}

	public function getUrl($path)
	{
		return $this->_getUrl($path);
	}


}