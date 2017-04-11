<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_SocialLogin
 * @copyright   Copyright (c) 2016 Mageplaza (http://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */
namespace Mageplaza\SocialLogin\Helper;

use Mageplaza\SocialLogin\Helper\Data as HelperData;

/**
 * Class Social
 * @package Mageplaza\SocialLogin\Helper
 */
class Social extends HelperData
{
	const XML_PATH = [
		'api_enabled'       => 'sociallogin/{social_type}/is_enabled',
		'api_app_id'        => 'sociallogin/{social_type}/app_id',
		'api_app_secret'    => 'sociallogin/{social_type}/app_secret'
	];

	/**
	 * @type string
	 */
	protected $socialType = '';

	/**
	 * @type array
	 */
	protected $xmlPath = [];

	/**
	 * @param $socialType
	 * @return $this
	 */
	public function correctXmlPath($socialType)
	{
		$this->socialType = strtolower($socialType);
		foreach (self::XML_PATH as $key => $value) {
			$this->xmlPath[$key] = str_replace('{social_type}', $this->socialType, $value);
		}

		return $this;
	}

	/**
	 * @param null $storeId
	 * @return mixed
	 */
	public function isEnabled($storeId = null)
	{
		return $this->getConfigValue($this->xmlPath['api_enabled'], $storeId);
	}

	/**
	 * @param null $storeId
	 * @return mixed
	 */
	public function getAppId($storeId = null)
	{
		return $this->getConfigValue($this->xmlPath['api_app_id'], $storeId);
	}

	/**
	 * @param null $storeId
	 * @return mixed
	 */
	public function getAppSecret($storeId = null)
	{
		return $this->getConfigValue($this->xmlPath['api_app_secret'], $storeId);
	}

	/**
	 * @param null $storeId
	 * @return mixed
	 */
	public function getOpenIdIdentifier($storeId = null)
	{
		return $this->getConfigValue('sociallogin/openid/identifier', $storeId);
	}

	/**
	 * @param $type
	 * @return string
	 */
	public function getAuthUrl($type)
	{
		$authUrl = $this->getBaseAuthUrl($type);

		switch ($type) {
			case 'Facebook':
				$param = 'hauth_done=' . $type;
				break;
			case 'Live':
				$param = null;
				break;
			default:
				$param = 'hauth.done=' . $type;
		}

		return $authUrl . ($param ? (strpos($authUrl, '?') ? '&' : '?') . $param : '');
	}

	/**
	 * @param null $type
	 * @return string
	 */
	public function getBaseAuthUrl($type = null)
	{
		$type = $type ?: $this->socialType;
		if (strtolower($type) == 'live') {
			return $this->_getUrl('sociallogin/social_callback/live', array('_nosid' => true));
		}

		return $this->_getUrl('sociallogin/social/callback', array('_nosid' => true));
	}
}