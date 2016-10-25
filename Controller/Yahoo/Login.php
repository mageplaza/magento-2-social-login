<?php
namespace Mageplaza\SocialLogin\Controller\Yahoo;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Mageplaza\SocialLogin\Model\Yahoo;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\SocialLogin\Helper\Yahoo\Data as HelperYahoo;
use Mageplaza\SocialLogin\Helper\Data as HelperData;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Customer\Model\Session;


class Login extends Action
{
    const SOCIAL_TYPE = 'yahoo';
    protected $resultPageFactory;
    protected $yahoo;
    protected $helperYahoo;
    protected $accountManagement;
    protected $customerUrl;
    protected $session;
    protected $helperData;
    protected $storeManager;
    protected $resultFactory;
    

    public function __construct(
        Context $context,
        Yahoo $yahoo,
        StoreManagerInterface $storeManager,
        HelperYahoo $helperYahoo,
        HelperData $helperData,
        PageFactory $resultPageFactory,
        AccountManagementInterface $accountManagement,
        CustomerUrl $customerUrl,
        Session $customerSession
    ) {
        parent::__construct($context);
        $this->yahoo          = $yahoo;
        $this->storeManager      = $storeManager;
        $this->helperYahoo    = $helperYahoo;
        $this->helperData        = $helperData;
        $this->resultPageFactory = $resultPageFactory;
        $this->accountManagement = $accountManagement;
        $this->customerUrl       = $customerUrl;
        $this->session           = $customerSession;
        $this->resultFactory     = $context->getResultFactory();
    }
    public function execute(){
        $yahoo = $this->yahoo->newLive();
        $value = $yahoo->authenticate(self::SOCIAL_TYPE);
        $user_profile = $value->getUserProfile();
        $email = $user_profile->email;
        if($email==NULL){
           $this->messageManager->addError('Email is Null, Please enter email in your Yahoo profile');
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('customer/account/login');
            return $resultRedirect;
        }
        $customer = $this->helperData->getCustomerBySocialId($user_profile->identifier, self::SOCIAL_TYPE);
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
            $name = $user_profile->displayName;
            $user['firstname'] = $user_profile->firstName;
            $user['lastname']  = $user_profile->lastName;
            if($user_profile->firstName == NULL || $user_profile->lastName == NULL){
                if($user_profile->firstName == NULL){
                    if($user_profile->lastName == NULL){
                        $user['lastname'] = $name;
                    }
                    $user['firstname'] = $name;
                    $this->messageManager->addNotice('Please edit your name');
                }else{
                    $user['lastname'] = $name;
                    $this->messageManager->addNotice('Please edit your name');
                }
            }
            $user['email']     = $email;
            $store_id          = $this->storeManager->getStore()->getStoreId();
            $website_id        = $this->storeManager->getStore()->getWebsiteId();
            $customer          = $this->helperData->getCustomerByEmail($email, $website_id);
            if (!$customer || !$customer->getId()) {
                $customer = $this->helperData->createCustomerMultiWebsite($user, $website_id, $store_id);
            }
            $this->helperData->setAuthorCustomer($user_profile->identifier, $customer->getId(),self::SOCIAL_TYPE,$this->helperYahoo->sendPassword());
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