<?php
namespace Mageplaza\SocialLogin\Controller\Live;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Mageplaza\SocialLogin\Model\Live;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\SocialLogin\Helper\Live\Data as HelperLive;
use Mageplaza\SocialLogin\Helper\Data as HelperData;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Customer\Model\Session;

class Login extends Action
{
	const SOCIAL_TYPE = 'live';
	protected $resultPageFactory;
	protected $live;
	protected $helperData;
	protected $helperLive;
	protected $accountManagement;
	protected $customerUrl;
	protected $session;

	public function __construct(
		Context $context,
		Live $live,
		StoreManagerInterface $storeManager,
		HelperLive $helperLive,
		HelperData $helperData,
		PageFactory $resultPageFactory,
		AccountManagementInterface $accountManagement,
		CustomerUrl $customerUrl,
		Session $customerSession
	) {

		parent::__construct($context);
		$this->live         = $live;
		$this->storeManager      = $storeManager;
		$this->helperData        = $helperData;
		$this->helperLive   = $helperLive;
		$this->resultPageFactory = $resultPageFactory;
		$this->accountManagement = $accountManagement;
		$this->customerUrl       = $customerUrl;
		$this->session           = $customerSession;
	}

	public function execute()
	{
		$user_profile = $this->live->newLive();
		$name = $user_profile->name;
		$email = $user_profile->preferred_username;
		if($email==NULL){
             $this->messageManager->addError('Email is Null, Please enter email in your Microsoft Live profile');
             $resultRedirect = $this->resultRedirectFactory->create();
             $resultRedirect->setPath('customer/account/login');
             return $resultRedirect;          
		}
		$customer = $this->helperData->getCustomerBySocialId($user_profile->sub,self::SOCIAL_TYPE);
		
		if ($customer) {
             if ($customer->getConfirmation()) {
                 try {
                    $customer->setConfirmation(null);
                    $customer->save();
                } catch (\Exception $e) {
                }
            }
            $this->session->setCustomerAsLoggedIn($customer);
			$this->_appendJs("<script type=\"text/javascript\">try{window.opener.location.href=\"" . $this->_loginPostRedirect() . "\";}catch(e){window.opener.location.reload(true);} window.close();</script>");
             exit;
        } else {
			$arrName           = explode(' ', $name, 2);
			if (empty($arrName[0])) {
				$arrName[0] = $name;
			}
			if (!empty($arrName[1])) {
				$user['lastname'] = $arrName[1];
			} else {
				$user['lastname'] = $name;
				$this->messageManager->addNotice('Please edit your name');
			}
			$user['firstname'] = $arrName[0];
            $user['email']     = $email;
            $store_id          = $this->storeManager->getStore()->getStoreId();
            $website_id        = $this->storeManager->getStore()->getWebsiteId();
            $customer          = $this->helperData->getCustomerByEmail($email, $website_id);
            if (!$customer || !$customer->getId()) {
                $customer = $this->helperData->createCustomerMultiWebsite($user, $website_id, $store_id);
                if ($this->helperLive->sendPassword()) {
                    try {
                        $customer->sendPasswordReminderEmail();
                    } catch (Exception $e) {
                    }
                }
            }
            $this->helperData->setAuthorCustomer($user_profile->sub, $customer->getId(),self::SOCIAL_TYPE,$this->helperLive->sendPassword());
            $this->session->setCustomerAsLoggedIn($customer);
            $this->_appendJs("<script>window.close();window.opener.location = '" . $this->storeManager->getStore()->getUrl('customer/account/edit') . "';</script>");
            exit;
          }
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