<?php
namespace Mageplaza\SocialLogin\Controller\Instagram;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Mageplaza\SocialLogin\Model\Instagram;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\SocialLogin\Helper\Instagram\Data as HelperInstagram;
use Mageplaza\SocialLogin\Helper\Data as HelperData;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Customer\Model\Session;

class Login extends Action
{
    const SOCIAL_TYPE = 'instagram';
    protected $resultPageFactory;
    protected $instagram;
    protected $helperData;
    protected $helperInstagram;
    protected $accountManagement;
    protected $customerUrl;
    protected $session;


    public function __construct(
        Context $context,
        Instagram $instagram,
        StoreManagerInterface $storeManager,
        HelperInstagram $helperInstagram,
        HelperData $helperData,
        PageFactory $resultPageFactory,
        AccountManagementInterface $accountManagement,
        CustomerUrl $customerUrl,
        Session $customerSession
    ) {

        parent::__construct($context);
        $this->instagram         = $instagram;
        $this->storeManager      = $storeManager;
        $this->helperData        = $helperData;
        $this->helperInstagram   = $helperInstagram;
        $this->resultPageFactory = $resultPageFactory;
        $this->accountManagement = $accountManagement;
        $this->customerUrl       = $customerUrl;
        $this->session           = $customerSession;
    }

    public function execute()
    {
        $instagram = $this->instagram->newLive();
        $value = $instagram->authenticate(self::SOCIAL_TYPE);
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
            $email = $name . '@instagram.com';
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
//            $user['firstname'] = $name;
//            $user['lastname']  = $name;
            $user['email']     = $email;
            $store_id          = $this->storeManager->getStore()->getStoreId();
            $website_id        = $this->storeManager->getStore()->getWebsiteId();
            $customer          = $this->helperData->getCustomerByEmail($email, $website_id);
            if (!$customer || !$customer->getId()) {
                $customer = $this->helperData->createCustomerMultiWebsite($user, $website_id, $store_id);
            }
            $this->helperData->setAuthorCustomer($user_profile->identifier, $customer->getId(),self::SOCIAL_TYPE,$this->helperInstagram->sendPassword());
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