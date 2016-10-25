<?php
namespace Mageplaza\SocialLogin\Controller;

use Magento\Framework\App\Action\Action;
//use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
//use Mageplaza\SocialLogin\Model\Foursquare;
use Magento\Store\Model\StoreManagerInterface;
//use Mageplaza\SocialLogin\Helper\Foursquare\Data as HelperFoursquare;
use Mageplaza\SocialLogin\Helper\Data as HelperData;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Customer\Model\Session;


class AbstractSocial extends Action
{
	//const SOCIAL_TYPE = 'foursquare';
	protected $resultPageFactory;
	//protected $foursquare;
	//protected $helperFoursquare;
	protected $accountManagement;
	protected $customerUrl;
	protected $session;
	protected $helperData;
	protected $storeManager;
	protected $resultFactory;


	public function __construct(
		//Context $context,
		//Foursquare $foursquare,
		StoreManagerInterface $storeManager,
		//HelperFoursquare $helperFoursquare,
		HelperData $helperData,
		PageFactory $resultPageFactory,
		AccountManagementInterface $accountManagement,
		CustomerUrl $customerUrl,
		Session $customerSession
	) {
		//parent::__construct($context);
		//$this->foursquare          = $foursquare;
		$this->storeManager      = $storeManager;
		//$this->helperFoursquare    = $helperFoursquare;
		$this->helperData        = $helperData;
		$this->resultPageFactory = $resultPageFactory;
		$this->accountManagement = $accountManagement;
		$this->customerUrl       = $customerUrl;
		$this->session           = $customerSession;
		$this->resultFactory     = $context->getResultFactory();
	}
	public function execute(){


	}

	protected function _appendJs($string)
	{
		echo $string;
	}

	public function getBaseUrl()
	{
		return $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')
			->getStore()
			->getBaseUrl();
	}

	protected function _loginPostRedirect()
	{
		$redirectUrl = $this->helperData->getConfigValue(('general/select_redirect_page'), $this->storeManager->getStore()->getId());
		switch ($redirectUrl) {
			case 0:
				return $this->storeManager->getStore()->getUrl('customer/account');
				break;
			case 1:
				return $this->storeManager->getStore()->getUrl('checkout/cart');
				break;
			case 2:
				return $this->storeManager->getStore()->getUrl();
				break;
			case 3:
				return $this->session->getCurrentPage();
				break;
			case 4:
				return $this->helperData->getConfigValue(('general/custom_page'), $this->storeManager->getStore()->getId());
				break;
			default:
				return $this->storeManager->getStore()->getUrl('customer/account');
				break;
		}
	}
}