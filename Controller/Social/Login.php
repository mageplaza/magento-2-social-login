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
namespace Mageplaza\SocialLogin\Controller\Social;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Account\Redirect as AccountRedirect;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\SocialLogin\Helper\Social as SocialHelper;
use Mageplaza\SocialLogin\Model\Social;
use Magento\Customer\Model\Session;

/**
 * Class AbstractSocial
 * @package Mageplaza\SocialLogin\Controller
 */
class Login extends Action
{
	/**
	 * @type \Magento\Customer\Model\Session
	 */
	protected $session;

	/**
	 * @type \Magento\Store\Model\StoreManagerInterface
	 */
	protected $storeManager;

	/**
	 * @type \Mageplaza\SocialLogin\Helper\Social
	 */
	protected $apiHelper;

	/**
	 * @type \Mageplaza\SocialLogin\Model\Social
	 */
	protected $apiObject;

	/**
	 * @var AccountRedirect
	 */
	private $accountRedirect;

	/**
	 * @type
	 */
	private $cookieMetadataManager;

	/**
	 * @type
	 */
	private $cookieMetadataFactory;

	/**
	 * @var \Magento\Framework\Controller\Result\RawFactory
	 */
	protected $resultRawFactory;

	/**
	 * @param \Magento\Framework\App\Action\Context $context
	 * @param \Magento\Store\Model\StoreManagerInterface $storeManager
	 * @param \Mageplaza\SocialLogin\Helper\Social $apiHelper
	 * @param \Mageplaza\SocialLogin\Model\Social $apiObject
	 * @param \Magento\Customer\Model\Session $customerSession
	 * @param \Magento\Customer\Model\Account\Redirect $accountRedirect
	 */
	public function __construct(
		Context $context,
		StoreManagerInterface $storeManager,
		SocialHelper $apiHelper,
		Social $apiObject,
		Session $customerSession,
		AccountRedirect $accountRedirect,
		\Magento\Framework\Controller\Result\RawFactory $resultRawFactory
	)
	{
		parent::__construct($context);
		$this->storeManager     = $storeManager;
		$this->apiHelper        = $apiHelper;
		$this->apiObject        = $apiObject;
		$this->session          = $customerSession;
		$this->accountRedirect  = $accountRedirect;
		$this->urlBuilder       = $context->getUrl();
		$this->resultRawFactory = $resultRawFactory;
	}

	/**
	 * @return \Mageplaza\SocialLogin\Controller\AbstractSocial
	 */
	public function execute()
	{
		$type = $this->apiHelper->setType($this->getRequest()->getParam('type', null));
		if (!$type) {
			$this->_forward('noroute');

			return;
		}

		$userProfile = $this->apiObject->getUserProfile($type);
		if (!$userProfile->identifier) {
			return $this->emailRedirect($type);
		}

		$customer = $this->apiObject->getCustomerBySocial($userProfile->identifier, $type);
		if (!$customer->getId()) {
			$name = explode(' ', $userProfile->displayName ?: __('New User'));
			$user = array_merge([
				'email'      => $userProfile->email ?: $userProfile->identifier . '@' . strtolower($type) . '.com',
				'firstname'  => $userProfile->firstName ?: (array_shift($name) ?: $userProfile->identifier),
				'lastname'   => $userProfile->lastName ?: (array_shift($name) ?: $userProfile->identifier),
				'identifier' => $userProfile->identifier,
				'type'       => $type
			], $this->getUserData($userProfile));

			$customer = $this->createCustomer($user, $type);
		}

		return $this->_appendJs($customer);
	}

	/**
	 * @param $profile
	 * @return array
	 */
	protected function getUserData($profile)
	{
		return [];
	}

	/**
	 * Get Store object
	 *
	 * @return \Magento\Store\Api\Data\StoreInterface
	 */
	public function getStore()
	{
		return $this->storeManager->getStore();
	}

	/**
	 * Redirect to login page if social data is not contain email address
	 *
	 * @param $apiLabel
	 * @return $this
	 */
	public function emailRedirect($apiLabel, $needTranslate = true)
	{
		$message = $needTranslate ? __('Email is Null, Please enter email in your %1 profile', $apiLabel) : $apiLabel;
		$this->messageManager->addErrorMessage($message);
		$this->_redirect('customer/account/login');

		return $this;
	}

	/**
	 * Create customer from social data
	 *
	 * @param $user
	 * @return bool|\Magento\Customer\Model\Customer|mixed
	 */
	public function createCustomer($user, $type)
	{
		$customer = $this->apiObject->getCustomerByEmail($user['email'], $this->getStore()->getWebsiteId());
		if (!$customer->getId()) {
			try {
				$customer = $this->apiObject->createCustomerSocial($user, $this->getStore());
				if ($this->apiHelper->canSendPassword()) {
					$customer->sendPasswordReminderEmail();
				}
			} catch (\Exception $e) {
				$this->emailRedirect($e->getMessage(), false);

				return false;
			}
		}
		$this->apiObject->setAuthorCustomer($user['identifier'], $customer->getId(), $type);

		return $customer;
	}

	/**
	 * Return javascript to redirect when login success
	 *
	 * @param $customer
	 * @return $this
	 */
	public function _appendJs($customer)
	{
		if ($customer && $customer->getId()) {
			$this->session->setCustomerAsLoggedIn($customer);
			$this->session->regenerateId();

			if ($this->getCookieManager()->getCookie('mage-cache-sessid')) {
				$metadata = $this->getCookieMetadataFactory()->createCookieMetadata();
				$metadata->setPath('/');
				$this->getCookieManager()->deleteCookie('mage-cache-sessid', $metadata);
			}
		}

		/** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
		$resultRaw = $this->resultRawFactory->create();

		return $resultRaw->setContents(sprintf("<script>window.opener.socialCallback('%s', window);</script>", $this->_loginPostRedirect()));
	}

	/**
	 * Retrieve cookie manager
	 *
	 * @deprecated
	 * @return \Magento\Framework\Stdlib\Cookie\PhpCookieManager
	 */
	private function getCookieManager()
	{
		if (!$this->cookieMetadataManager) {
			$this->cookieMetadataManager = \Magento\Framework\App\ObjectManager::getInstance()->get(
				\Magento\Framework\Stdlib\Cookie\PhpCookieManager::class
			);
		}

		return $this->cookieMetadataManager;
	}

	/**
	 * Retrieve cookie metadata factory
	 *
	 * @deprecated
	 * @return \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
	 */
	private function getCookieMetadataFactory()
	{
		if (!$this->cookieMetadataFactory) {
			$this->cookieMetadataFactory = \Magento\Framework\App\ObjectManager::getInstance()->get(
				\Magento\Framework\Stdlib\Cookie\CookieMetadataFactory::class
			);
		}

		return $this->cookieMetadataFactory;
	}

	/**
	 * Return redirect url by config
	 *
	 * @return mixed
	 */
	protected function _loginPostRedirect()
	{
		$url = $this->urlBuilder->getUrl('customer/account');

		if ($this->_request->getParam('authen') == 'popup') {
			$url = $this->urlBuilder->getUrl('checkout');
		} else {
			$requestedRedirect = $this->accountRedirect->getRedirectCookie();
			if (!$this->apiHelper->getConfigValue('customer/startup/redirect_dashboard') && $requestedRedirect) {
				$url = $this->_redirect->success($requestedRedirect);
				$this->accountRedirect->clearRedirectCookie();
			}
		}

		return $url;
	}
}