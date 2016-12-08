<?php

namespace Mageplaza\SocialLogin\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Customer\Model\CustomerFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Mageplaza\Core\Helper\AbstractData as CoreHelper;
use Mageplaza\SocialLogin\Model\SocialFactory;
use Mageplaza\SocialLogin\Model\ResourceModel\Social\CollectionFactory;

class Data extends CoreHelper
{
	const XML_PATH_GENERAL_ENABLED = 'sociallogin/general/is_enabled';
	const XML_PATH_GENERAL = 'sociallogin/general/';
	const XML_PATH_GENERAL_POPUP_LEFT = 'sociallogin/general/left';
	const XML_PATH_GENERAL_STYLE_MANAGEMENT = 'sociallogin/general/style_management';
	const XML_PATH_CAPTCHA_ENABLE = 'sociallogin/captcha/is_enabled';
	const XML_PATH_SECURE_IN_FRONTEND = 'web/secure/use_in_frontend';
	protected $customerFactory;
	protected $socialFactory;//them
	protected $collectionFactory;


	public function __construct(
		Context $context,
		ObjectManagerInterface $objectManager,
		StoreManagerInterface $storeManager,
		CustomerFactory $customerFactory,
		SocialFactory $socialFactory,
		CollectionFactory $collectionFactory
	)
	{
		$this->customerFactory   = $customerFactory;
		$this->socialFactory     = $socialFactory;
		$this->collectionFactory = $collectionFactory;

		parent::__construct($context, $objectManager, $storeManager);
	}

	public function isEnabled($storeId = null)
	{
		return $this->getConfigValue(self::XML_PATH_GENERAL_ENABLED, $storeId);
	}

	public function isCaptchaEnabled($storeId = null)
	{
		return $this->getConfigValue(self::XML_PATH_CAPTCHA_ENABLE, $storeId);
	}

	/**
	 * @param      $code
	 * @param null $storeId
	 * @return mixed
	 */
	public function getGeneralConfig($code, $storeId = null)
	{
		return $this->getConfigValue(self::XML_PATH_GENERAL . $code, $storeId);
	}

	/**
	 * @param null $storeId
	 * @return mixed
	 */
	public function getPopupEffect($storeId = null)
	{
		return $this->getGeneralConfig('popup_effect', $storeId);
	}

	/**
	 * @param null $storeId
	 * @return mixed
	 */
	public function getStyleManagement($storeId = null)
	{
		$style = $this->getGeneralConfig('style_management', $storeId);
		if ($style == 'custom') {
			return $this->getCustomColor($storeId);
		}

		return $style;
	}

	public function getCustomColor($storeId = null)
	{
		return $this->getGeneralConfig('custom_color', $storeId);
	}

	public function getCustomCss($storeId = null)
	{
		return $this->getGeneralConfig('custom_css', $storeId);
	}

	/**
	 * @param string $email
	 * @return bool|\Magento\Customer\Model\Customer
	 */
	public function getCustomerByEmail($email, $websiteId = null)
	{
		/** @var \Magento\Customer\Model\Customer $customer */
		$customer = $this->objectManager->create(
			'Magento\Customer\Model\Customer'
		);
		if (!$websiteId) {
			$customer->setWebsiteId($this->storeManager->getWebsite()->getId());
		} else {
			$customer->setWebsiteId($websiteId);
		}
		$customer->loadByEmail($email);

		if ($customer->getId()) {
			return $customer;
		}

		return false;
	}

	public function getCustomerById($id, $websiteId = null)
	{
		/** @var \Magento\Customer\Model\Customer $customer */
		$customer = $this->objectManager->create(
			'Magento\Customer\Model\Customer'
		);
		if (!$websiteId) {
			$customer->setWebsiteId($this->storeManager->getWebsite()->getId());
		} else {
			$customer->setWebsiteId($websiteId);
		}
		$customer->load($id);

		if ($customer->getId()) {
			return $customer;
		}

		return false;
	}

	/**
	 * @param $data
	 * @param $website_id
	 * @param $store_id
	 * @return mixed
	 */
	public function createCustomerMultiWebsite($data, $website_id, $store_id)
	{
		$customer = $this->customerFactory->create();
		$customer->setFirstname($data['firstname'])
			->setLastname($data['lastname'])
			->setEmail($data['email'])
			->setWebsiteId($website_id)
			->setStoreId($store_id)
			->save();

		try {
			$customer->save();
		} catch (\Exception $e) {
		}

		return $customer;
	}

	public function setAuthorCustomer($tokenId, $customerId, $type, $pass)
	{
		$customer = $this->socialFactory->create();
		$customer->setData('social_id', $tokenId);
		$customer->setData('customer_id', $customerId);
		$customer->setData('type', $type);
		$customer->setData('is_send_password_email', $pass);
		try {
			$customer->save();
		} catch (\Exception $e) {
		}

		return $customer;
	}

	public function getCustomerBySocialId($Id, $type)
	{
		$customer = $this->collectionFactory->create();
		$user     = $customer->addFieldToFilter('social_id', $Id)
			->addFieldToFilter('type', $type)
			->getFirstItem();
		if ($user && $user->getId()) {
			$newcustomer = $this->customerFactory->create()->load($user->getCustomerId());

			return $newcustomer;
		} else
			return null;
	}

	public function isSecure()
	{
		$isSecure = $this->getConfigValue(self::XML_PATH_SECURE_IN_FRONTEND);

		return $isSecure;
	}

	public function getEditUrl()
	{
		$isSecure = $this->isSecure();

		return $this->_getUrl('customer/account/edit', array('_secure' => $isSecure));
	}

}