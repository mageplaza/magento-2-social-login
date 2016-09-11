<?php
namespace Mageplaza\SocialLogin\Controller\Amazon;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
//use Mageplaza\SocialLogin\Model\Amazon;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\SocialLogin\Helper\Amazon\Data as HelperAmazon;
use Mageplaza\SocialLogin\Helper\Data as HelperData;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Customer\Model\Session;

class Login extends Action
{
	const SOCIAL_TYPE = 'amazon';
	protected $resultPageFactory;
	protected $amazon;
	protected $helperData;
	protected $helperAmazon;
	protected $accountManagement;
	protected $customerUrl;
	protected $session;

	public function __construct(
		Context $context,
		//Amazon $amazon,
		StoreManagerInterface $storeManager,
		HelperAmazon $helperAmazon,
		HelperData $helperData,
		PageFactory $resultPageFactory,
		AccountManagementInterface $accountManagement,
		CustomerUrl $customerUrl,
		Session $customerSession
	) {

		parent::__construct($context);
		//$this->amazon         = $amazon;
		$this->storeManager      = $storeManager;
		$this->helperData        = $helperData;
		$this->helperAmazon   = $helperAmazon;
		$this->resultPageFactory = $resultPageFactory;
		$this->accountManagement = $accountManagement;
		$this->customerUrl       = $customerUrl;
		$this->session           = $customerSession;
	}

	public function execute()
	{
		
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_URL,'https://api.amazon.com/auth/o2/tokeninfo?access_token=' . urlencode($_REQUEST['access_token']));
	$result=curl_exec($ch);
	curl_close($ch);
	$d = json_decode($result, true);

	$c = curl_init('https://api.amazon.com/user/profile');
	curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($c, CURLOPT_HTTPHEADER, array('Authorization: bearer ' . $_REQUEST['access_token']));
	curl_setopt($c, CURLOPT_RETURNTRANSFER, true);

	$r = curl_exec($c);
	curl_close($c);
	$d = json_decode($r);

	//echo sprintf('%s %s %s', $d->name, $d->email, $d->user_id);
		//$user_profile = d;
		$name = $d->name;
		$email = $d->email;
		if($email==NULL){
			$this->messageManager->addError('Email is Null, Please enter email in your Amazon profile');
			$resultRedirect = $this->resultRedirectFactory->create();
			$resultRedirect->setPath('customer/account/login');
			return $resultRedirect;
		}
		$customer = $this->helperData->getCustomerBySocialId($d->user_id,self::SOCIAL_TYPE);
		if ($customer) {
			if ($customer->getConfirmation()) {
				try {
					$customer->setConfirmation(null);
					$customer->save();
				} catch (\Exception $e) {
				}
			}
			$this->session->setCustomerAsLoggedIn($customer);
			$resultRedirect = $this->resultRedirectFactory->create();
			$resultRedirect->setPath('customer/account/create');
			return $resultRedirect;
			//$this->_appendJs("<script type=\"text/javascript\">try{window.opener.location.href=\"" . $this->_loginPostRedirect() . "\";}catch(e){window.opener.location.reload(true);} window.close();</script>");
			exit;
		} else {
			$arrName = explode(' ', $name, 2);
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
				if ($this->helperAmazon->sendPassword()) {
					try {
						$customer->sendPasswordReminderEmail();
					} catch (Exception $e) {
					}
				}
			}
			$this->helperData->setAuthorCustomer($d->user_id, $customer->getId(),self::SOCIAL_TYPE,$this->helperAmazon->sendPassword());
			$this->session->setCustomerAsLoggedIn($customer);
			$resultRedirect = $this->resultRedirectFactory->create();
			$resultRedirect->setPath('customer/account/create');
			return $resultRedirect;
			// $this->_appendJs("<script>window.close();window.opener.location = '" . $this->storeManager->getStore()->getUrl('customer/account/edit') . "';</script>");
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