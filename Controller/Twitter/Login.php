<?php

namespace Mageplaza\SocialLogin\Controller\Twitter;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\SocialLogin\Helper\Twitter\Data as TwitterHelper;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Customer\Model\Session;
use Magento\Framework\Session\SessionManagerInterface;
use Mageplaza\SocialLogin\Model\Twitter;
use Mageplaza\SocialLogin\Helper\Data as HelperData;

class Login extends Action
{
    const SOCIAL_TYPE = 'twitter';
    protected $resultPageFactory;
    protected $twitterHelper;
    protected $accountManagement;
    protected $customerUrl;
    protected $customerSession;
    protected $session;
    protected $twitter;
    protected $helperData;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        TwitterHelper $twitterHelper,
        PageFactory $resultPageFactory,
        HelperData $helperData,
        AccountManagementInterface $accountManagement,
        CustomerUrl $customerUrl,
        Session $customerSession,
        SessionManagerInterface $session,
        Twitter $twitter
    ) {

        parent::__construct($context);
        $this->storeManager      = $storeManager;
        $this->twitterHelper     = $twitterHelper;
        $this->resultPageFactory = $resultPageFactory;
        $this->accountManagement = $accountManagement;
        $this->customerUrl       = $customerUrl;
        $this->customerSession   = $customerSession;
        $this->session           = $session;
        $this->twitter           = $twitter;
        $this->helperData        = $helperData;
    }

    public function execute()
    {
        $twitter = $this->twitter->newLive();
        $value = $twitter->authenticate(self::SOCIAL_TYPE);
        $user_profile = $value->getUserProfile();
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
            $email = $name . '@twitter.com';
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
             $this->helperData->setAuthorCustomer($user_profile->identifier, $customer->getId(),self::SOCIAL_TYPE,$this->twitterHelper->sendPassword());
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