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
namespace Mageplaza\SocialLogin\Controller\Popup;

use Magento\Customer\Controller\Account\CreatePost;
use Magento\Customer\Model\Account\Redirect as AccountRedirect;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Helper\Address;
use Magento\Framework\UrlFactory;
use Magento\Customer\Model\Metadata\FormFactory;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Customer\Model\Registration;
use Magento\Framework\Escaper;
use Magento\Customer\Model\CustomerExtractor;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Class Create
 * @package Mageplaza\SocialLogin\Controller\Popup
 */
class Create extends CreatePost
{
	/**
	 * @type \Magento\Framework\Controller\Result\JsonFactory
	 */
	protected $resultJsonFactory;

	/**
	 * @type \Magento\Captcha\Helper\Data
	 */
	protected $captchaHelper;

	/**
	 * @type \Mageplaza\SocialLogin\Helper\Data
	 */
	protected $socialHelper;

	/**
	 * @type
	 */
	private $cookieMetadataManager;

	/**
	 * @type
	 */
	private $cookieMetadataFactory;

	/**
	 * @param \Magento\Framework\App\Action\Context $context
	 * @param \Magento\Customer\Model\Session $customerSession
	 * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
	 * @param \Magento\Store\Model\StoreManagerInterface $storeManager
	 * @param \Magento\Customer\Api\AccountManagementInterface $accountManagement
	 * @param \Magento\Customer\Helper\Address $addressHelper
	 * @param \Magento\Framework\UrlFactory $urlFactory
	 * @param \Magento\Customer\Model\Metadata\FormFactory $formFactory
	 * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
	 * @param \Magento\Customer\Api\Data\RegionInterfaceFactory $regionDataFactory
	 * @param \Magento\Customer\Api\Data\AddressInterfaceFactory $addressDataFactory
	 * @param \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerDataFactory
	 * @param \Magento\Customer\Model\Url $customerUrl
	 * @param \Magento\Customer\Model\Registration $registration
	 * @param \Magento\Framework\Escaper $escaper
	 * @param \Magento\Customer\Model\CustomerExtractor $customerExtractor
	 * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
	 * @param \Magento\Customer\Model\Account\Redirect $accountRedirect
	 * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
	 * @param \Magento\Captcha\Helper\Data $captchaHelper
	 * @param \Mageplaza\SocialLogin\Helper\Data $socialHelper
	 */
	public function __construct(
		Context $context,
		Session $customerSession,
		ScopeConfigInterface $scopeConfig,
		StoreManagerInterface $storeManager,
		AccountManagementInterface $accountManagement,
		Address $addressHelper,
		UrlFactory $urlFactory,
		FormFactory $formFactory,
		SubscriberFactory $subscriberFactory,
		RegionInterfaceFactory $regionDataFactory,
		AddressInterfaceFactory $addressDataFactory,
		CustomerInterfaceFactory $customerDataFactory,
		CustomerUrl $customerUrl,
		Registration $registration,
		Escaper $escaper,
		CustomerExtractor $customerExtractor,
		DataObjectHelper $dataObjectHelper,
		AccountRedirect $accountRedirect,
		JsonFactory $resultJsonFactory,
		\Magento\Captcha\Helper\Data $captchaHelper,
		\Mageplaza\SocialLogin\Helper\Data $socialHelper
	)
	{
		$this->resultJsonFactory = $resultJsonFactory;
		$this->captchaHelper     = $captchaHelper;
		$this->socialHelper      = $socialHelper;

		parent::__construct(
			$context,
			$customerSession,
			$scopeConfig,
			$storeManager,
			$accountManagement,
			$addressHelper,
			$urlFactory,
			$formFactory,
			$subscriberFactory,
			$regionDataFactory,
			$addressDataFactory,
			$customerDataFactory,
			$customerUrl,
			$registration,
			$escaper,
			$customerExtractor,
			$dataObjectHelper,
			$accountRedirect
		);
	}

	/**
	 * Create customer account action
	 *
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 */
	public function execute()
	{
		/** @var \Magento\Framework\Controller\Result\Json $resultJson */
		$resultJson = $this->resultJsonFactory->create();

		$result = array(
			'success' => false,
			'message' => array()
		);

		$formId       = 'user_create';
		$captchaModel = $this->captchaHelper->getCaptcha($formId);
		if ($captchaModel->isRequired()) {
			if (!$captchaModel->isCorrect($this->socialHelper->captchaResolve($this->getRequest(), $formId))) {
				$result['message'] = __('Incorrect CAPTCHA.');

				return $resultJson->setData($result);
			}
			$captchaModel->generate();
			$result['imgSrc'] = $captchaModel->getImgSrc();
		}

		if ($this->session->isLoggedIn() || !$this->registration->isAllowed()) {
			$result['redirect'] = $this->urlModel->getUrl('customer/account');

			return $resultJson->setData($result);
		}

		if (!$this->getRequest()->isPost()) {
			$result['message'] = __('Data error. Please try again.');

			return $resultJson->setData($result);
		}

		$this->session->regenerateId();

		try {
			$address   = $this->extractAddress();
			$addresses = $address === null ? [] : [$address];

			$customer = $this->customerExtractor->extract('customer_account_create', $this->_request);
			$customer->setAddresses($addresses);

			$password     = $this->getRequest()->getParam('password');
			$confirmation = $this->getRequest()->getParam('password_confirmation');
			if (!$this->checkPasswordConfirmation($password, $confirmation)) {
				$result['message'][] = __('Please make sure your passwords match.');
			} else {
				$customer = $this->accountManagement
					->createAccount($customer, $password);

				if ($this->getRequest()->getParam('is_subscribed', false)) {
					$this->subscriberFactory->create()->subscribeCustomerById($customer->getId());
				}

				$this->_eventManager->dispatch(
					'customer_register_success',
					['account_controller' => $this, 'customer' => $customer]
				);

				$confirmationStatus = $this->accountManagement->getConfirmationStatus($customer->getId());
				if ($confirmationStatus === AccountManagementInterface::ACCOUNT_CONFIRMATION_REQUIRED) {
					$email = $this->customerUrl->getEmailConfirmationUrl($customer->getEmail());
					// @codingStandardsIgnoreStart
					$result['success'] = true;
					$this->messageManager->addSuccess(
						__(
							'You must confirm your account. Please check your email for the confirmation link or <a href="%1">click here</a> for a new link.',
							$email
						)
					);
				} else {
					$result['success']   = true;
					$result['message'][] = __('Create an account successfully. Please wait...');
					$this->session->setCustomerDataAsLoggedIn($customer);
				}
				if ($this->getCookieManager()->getCookie('mage-cache-sessid')) {
					$metadata = $this->getCookieMetadataFactory()->createCookieMetadata();
					$metadata->setPath('/');
					$this->getCookieManager()->deleteCookie('mage-cache-sessid', $metadata);
				}
			}
		} catch (StateException $e) {
			$url = $this->urlModel->getUrl('customer/account/forgotpassword');
			// @codingStandardsIgnoreStart
			$result['message'][] = __(
				'There is already an account with this email address. If you are sure that it is your email address, <a href="%1">click here</a> to get your password and access your account.',
				$url
			);
		} catch (InputException $e) {
			$result['message'][] = $this->escaper->escapeHtml($e->getMessage());
			foreach ($e->getErrors() as $error) {
				$result['message'][] = $this->escaper->escapeHtml($error->getMessage());
			}
		} catch (LocalizedException $e) {
			$result['message'][] = $this->escaper->escapeHtml($e->getMessage());
		} catch (\Exception $e) {
			$result['message'][] = __('We can\'t save the customer.');
		}

		$this->session->setCustomerFormData($this->getRequest()->getPostValue());

		return $resultJson->setData($result);
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
	 * Make sure that password and password confirmation matched
	 *
	 * @param string $password
	 * @param string $confirmation
	 * @return boolean
	 * @throws InputException
	 */
	protected function checkPasswordConfirmation($password, $confirmation)
	{
		return $password == $confirmation;
	}

}
