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

namespace Mageplaza\SocialLogin\Block;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Mageplaza\SocialLogin\Helper\Data as HelperData;

/**
 * Class Popup
 * @package Mageplaza\SocialLogin\Block
 */
class Popup extends Template
{
	/**
	 * @type \Magento\Store\Model\StoreManagerInterface
	 */
	protected $storeManager;

	/**
	 * @type \Mageplaza\SocialLogin\Helper\Data
	 */
	protected $helperData;

	/**
	 * @type \Magento\Customer\Model\Session
	 */
	protected $customerSession;

	/**
	 * @param \Magento\Framework\View\Element\Template\Context $context
	 * @param \Mageplaza\SocialLogin\Helper\Data $helperData
	 * @param \Magento\Customer\Model\Session $customerSession
	 * @param array $data
	 */
	public function __construct(
		Context $context,
		HelperData $helperData,
		CustomerSession $customerSession,
		array $data = []
	)
	{
		$this->helperData      = $helperData;
		$this->customerSession = $customerSession;
		$this->storeManager    = $context->getStoreManager();
		parent::__construct($context, $data);
	}

	/**
	 * Is enable popup
	 *
	 * @return bool
	 */
	public function isEnabled()
	{
		return $this->helperData->isEnabled() && !$this->customerSession->isLoggedIn() && $this->helperData->getGeneralConfig('popup_login');
	}

	/**
	 * Js params
	 *
	 * @return string
	 */
	public function getFormParams()
	{
		$params = [
			'headerLink'    => $this->getHeaderLink(),
			'popupEffect'   => $this->getPopupEffect(),
			'formLoginUrl'  => $this->getFormLoginUrl(),
			'forgotFormUrl' => $this->getForgotFormUrl(),
			'createFormUrl' => $this->getCreateFormUrl()
		];

		return json_encode($params);
	}

	public function getHeaderLink()
	{
		$links = $this->helperData->getGeneralConfig('link_trigger');

		return $links ?: '.header .links, .section-item-content .header.links';
	}

	/**
	 * @return mixed
	 */
	public function getPopupEffect()
	{
		return $this->helperData->getPopupEffect();
	}

	/**
	 * get Social Login Form Url
	 *
	 * @return string
	 */
	public function getFormLoginUrl()
	{
		return $this->getUrl('customer/ajax/login', ['_secure' => $this->isSecure()]);
	}

	/**
	 * get is secure url
	 *
	 * @return mixed
	 */
	public function isSecure()
	{
		return $this->helperData->isSecure();
	}

	/**
	 * @return string
	 */
	public function getForgotFormUrl()
	{
		return $this->getUrl('sociallogin/popup/forgot', ['_secure' => $this->isSecure()]);
	}

	/**
	 *  get Social Login Form Create Url
	 *
	 * @return string
	 */
	public function getCreateFormUrl()
	{
		return $this->getUrl('sociallogin/popup/create', ['_secure' => $this->isSecure()]);
	}

	/**
	 * @return mixed
	 */
	public function getStyleColor()
	{
		return $this->helperData->getStyleManagement();
	}

	/**
	 * @return mixed
	 */
	public function getCustomCss()
	{
		$storeId = $this->storeManager->getStore()->getId(); //add

		return $this->helperData->getCustomCss($storeId);
	}
}
