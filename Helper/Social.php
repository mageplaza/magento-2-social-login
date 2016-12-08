<?php
namespace Mageplaza\SocialLogin\Helper;

use Mageplaza\SocialLogin\Helper\Data as HelperData;

class Social extends HelperData
{
	const XML_PATH = [
		'api_enabled'       => 'sociallogin/{social_type}/is_enabled',
		'api_app_id'        => 'sociallogin/{social_type}/app_id',
		'api_app_secret'    => 'sociallogin/{social_type}/app_secret',
		'api_redirect_url'  => 'sociallogin/{social_type}/redirect_url',
		'api_send_password' => 'sociallogin/{social_type}/send_password'
	];
	protected $socialType = '';
	protected $xmlPath = [];

	public function correctXmlPath($socialType)
	{
		$this->socialType = strtolower($socialType);

		foreach (self::XML_PATH as $key => $value) {
			$this->xmlPath[$key] = str_replace('{social_type}', $this->socialType, $value);
		}

		return $this;
	}

	public function isEnabled($storeId = null)
	{
		return $this->getConfigValue($this->xmlPath['api_enabled'], $storeId);
	}

	public function getAppId($storeId = null)
	{
		return $this->getConfigValue($this->xmlPath['api_app_id'], $storeId);
	}

	public function getAppSecret($storeId = null)
	{
		return $this->getConfigValue($this->xmlPath['api_app_secret'], $storeId);
	}

	public function getRedirectUrl($storeId = null)
	{
		return $this->getConfigValue($this->xmlPath['api_redirect_url'], $storeId);
	}

	public function sendPassword($storeId = null)
	{
		return $this->getConfigValue($this->xmlPath['api_send_password'], $storeId);
	}

	public function getAuthUrl($type)
	{
		$authUrl = $this->getBaseAuthUrl();

		return $authUrl . (strpos($authUrl, '?') ? '&' : '?') . "hauth.done={$type}";
	}

	public function getBaseAuthUrl()
	{
		return $this->_getUrl('sociallogin/social/callback', array('_nosid' => true));
	}
}