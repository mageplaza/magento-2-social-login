<?php
namespace Mageplaza\SocialLogin\Controller\Popup;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Mageplaza\SocialLogin\Helper\Data as HelperData;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Json\Helper\Data as JsonHelper;

class Login extends Action
{
	protected $helperData;
	protected $accountManagement;
	protected $customerSession;
	protected $jsonHelper;
	private $cookieMetadataManager;
	private $cookieMetadataFactory;


	public function __construct(
		Context $context,
		HelperData $helperData,
		AccountManagementInterface $accountManagement,
		CustomerSession $customerSession,
		JsonHelper $jsonHelper
	)
	{
		parent::__construct($context);
		$this->helperData        = $helperData;
		$this->accountManagement = $accountManagement;
		$this->customerSession   = $customerSession;
		$this->jsonHelper        = $jsonHelper;
	}

	public function execute()
	{
		$username      = $this->getRequest()->getParam('username', false);
		$password      = $this->getRequest()->getParam('password', false);
		$captchaStatus = $this->customerSession->getResultCaptcha();
		if ($captchaStatus) {
			if (isset($captchaStatus['error'])) {
				$this->customerSession->setResultCaptcha(null);
				$this->getResponse()->setBody($this->jsonHelper->jsonEncode($captchaStatus));


			}
		} else {
			$result = array(
				'success' => false,
				'message' => array()
			);
			if ($username && $password) {
				try {
					$accountManage = $this->accountManagement;
					$customer      = $accountManage->authenticate(
						$username,
						$password
					);
					$this->customerSession->setCustomerDataAsLoggedIn($customer);
					$this->customerSession->regenerateId();

					if ($this->getCookieManager()->getCookie('mage-cache-sessid')) {
						$metadata = $this->getCookieMetadataFactory()->createCookieMetadata();
						$metadata->setPath('/');
						$this->getCookieManager()->deleteCookie('mage-cache-sessid', $metadata);
					}
				} catch (\Exception $e) {
					$result['error']   = true;
					$result['message'] = $e->getMessage();
				}
				if (!isset($result['error'])) {
					$result['success'] = true;
					$result['message'] = __('Login successfully. Please wait ...');
				}
			} else {
				$result['error']   = true;
				$result['message'] = __(
					'Please enter a username and password.');
			}
			$this->getResponse()->setBody($this->jsonHelper->jsonEncode($result));
		}
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
}
