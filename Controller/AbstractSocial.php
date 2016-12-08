<?php
namespace Mageplaza\SocialLogin\Controller;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\SocialLogin\Helper\Social as SocialHelper;
use Mageplaza\SocialLogin\Model\Social;
use Magento\Customer\Model\Session;

/**
 * Class AbstractSocial
 * @package Mageplaza\SocialLogin\Controller
 */
abstract class AbstractSocial extends Action
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
	 * @type \Mageplaza\SocialLogin\Controller\AbstractSocial|mixed
	 */
	protected $apiHelper;

	/**
	 * @type \Mageplaza\SocialLogin\Controller\AbstractSocial|mixed
	 */
	protected $apiObject;

	/**
	 * Type of social network
	 *
	 * @type string
	 */
	protected $socialType;

	/**
	 * Redirect Url flag
	 *
	 * @type null
	 */
	protected $redirectEdit = false;

	private $cookieMetadataManager;
	private $cookieMetadataFactory;

	/**
	 * Constructor
	 *
	 * @param \Magento\Framework\App\Action\Context $context
	 * @param \Magento\Store\Model\StoreManagerInterface $storeManager
	 * @param \Mageplaza\SocialLogin\Helper\Data $apiHelper
	 * @param \Magento\Customer\Model\Session $customerSession
	 */
	public function __construct(
		Context $context,
		StoreManagerInterface $storeManager,
		SocialHelper $apiHelper,
		Social $apiObject,
		Session $customerSession
	)
	{
		parent::__construct($context);
		$this->storeManager = $storeManager;
		$this->apiHelper    = $apiHelper;
		$this->apiObject    = $apiObject;
		$this->session      = $customerSession;

		$this->apiHelper->correctXmlPath($this->socialType);
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
	 * Get social data
	 *
	 * @return mixed
	 */
	public function getUserProfile()
	{
		$auth  = $this->apiObject->getAuth($this->socialType);
		$value = $auth->authenticate($this->socialType);

		return $value->getUserProfile();
	}

	/**
	 * Redirect to login page if social data is not contain email address
	 *
	 * @param $apiLabel
	 * @return $this
	 */
	public function emailRedirect($apiLabel)
	{
		$this->messageManager->addErrorMessage(__('Email is Null, Please enter email in your %1 profile', $apiLabel));
		$this->_redirect('customer/account/login');

		return $this;
	}

	/**
	 * Get customer if it already exist
	 *
	 * @param $identifier
	 * @return null
	 */
	public function checkCustomer($identifier)
	{
		$customer = $this->apiHelper->getCustomerBySocialId($identifier, $this->socialType);
		if ($customer) {
			if ($customer->getConfirmation()) {
				try {
					$customer->setConfirmation(null);
					$customer->save();
				} catch (\Exception $e) {
				}
			}
		}

		return $customer;
	}

	/**
	 * Create customer from social data
	 *
	 * @param $user
	 * @return bool|\Magento\Customer\Model\Customer|mixed
	 */
	public function createCustomer($user)
	{
		$customer = $this->apiHelper->getCustomerByEmail($user['email'], $this->getStore()->getWebsiteId());
		if (!$customer || !$customer->getId()) {
			$customer = $this->apiHelper->createCustomerMultiWebsite($user, $this->getStore()->getWebsiteId(), $this->getStore()->getId());
			if ($this->apiHelper->sendPassword()) {
				$customer->sendPasswordReminderEmail();
			}
		}
		$this->apiHelper->setAuthorCustomer($user['identifier'], $customer->getId(), $this->socialType, $this->apiHelper->sendPassword());
		$this->redirectEdit = true;

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

		echo "<script type=\"text/javascript\">
				try{
					window.opener.location.href=\"" . $this->_loginPostRedirect() . "\";
				} catch(e){
					window.opener.location.reload(true);
				}
				window.close();
			</script>";
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
		$store = $this->storeManager->getStore();
		if ($this->redirectEdit) {
			return $store->getUrl('customer/account/edit');
		}

		$redirectUrl = $this->apiHelper->getConfigValue(('general/select_redirect_page'), $store->getId());
		switch ($redirectUrl) {
			case 1:
				$url = $store->getUrl('checkout/cart');
				break;
			case 2:
				$url = $store->getUrl();
				break;
			case 3:
				$url = $this->session->getCurrentPage();
				break;
			case 4:
				$url = $this->apiHelper->getConfigValue(('general/custom_page'), $store->getId());
				break;
			default:
				$url = $store->getUrl('customer/account');
				break;
		}

		return $url;
	}
}