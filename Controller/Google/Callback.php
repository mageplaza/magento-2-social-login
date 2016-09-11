<?php

namespace Mageplaza\SocialLogin\Controller\Google;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Mageplaza\SocialLogin\Model\Google;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\SocialLogin\Helper\Google\Data as HelperGoogle;
use Mageplaza\SocialLogin\Helper\Data as HelperData;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Customer\Model\Session;
use Symfony\Component\Config\Definition\Exception\Exception;

class Callback extends Action
{
    protected $resultPageFactory;
    protected $google;
    protected $helperGoogle;
    protected $accountManagement;
    protected $customerUrl;
    protected $session;
    protected $helperData;
    protected $storeManager;

    public function __construct(
        Context $context,
        Google $google,
        StoreManagerInterface $storeManager,
        HelperGoogle $helperGoogle,
        HelperData $helperData,
        PageFactory $resultPageFactory,
        AccountManagementInterface $accountManagement,
        CustomerUrl $customerUrl,
        Session $customerSession
    ) {
        parent::__construct($context);
        $this->google            = $google;
        $this->storeManager      = $storeManager;
        $this->helperGoogle      = $helperGoogle;
        $this->helperData        = $helperData;
        $this->resultPageFactory = $resultPageFactory;
        $this->accountManagement = $accountManagement;
        $this->customerUrl       = $customerUrl;
        $this->session           = $customerSession;

    }

    public function execute()
    {
        if($this->helperGoogle->isEnabled() && $this->helperData->isEnabled()){
            $oauth2 = new \Google_Oauth2Service($this->google);
            $code   = $this->getRequest()->getParam('code');
            if (!$code) {
                $this->_appendJs("<script>window.close()</script>");
                exit;
            }
            $this->google->authenticate($code);
            $client = $oauth2->userinfo->get();

            $user              = array();
            $email             = $client['email'];
            $name              = $client['name'];
            $arrName           = explode(' ', $name, 2);
            $user['firstname'] = $arrName[0];
            $user['lastname']  = $arrName[1];
            $user['email']     = $email;

            $storeId   = $this->storeManager->getStore()->getId(); //add
            $websiteId = $this->storeManager->getWebsite()->getId(); //add

            $customer = $this->helperData->getCustomerByEmail($user['email'], $websiteId); //add edition
            if (!$customer) {
                //Login multisite
                $customer = $this->helperData->createCustomerMultiWebsite($user, $websiteId, $storeId);
                if ($this->helperGoogle->sendPassword()) {
                    try {
                        $customer->sendPasswordReminderEmail();
                    } catch (Exception $e) {
                    }
                }
            }
            // fix confirmation
            if ($customer->getConfirmation()) {
                try {
                    $customer->setConfirmation(null);
                    $customer->save();
                } catch (Exception $e) {
                }
            }
            $this->session->setCustomerAsLoggedIn($customer);
            $this->session->regenerateId();
            $this->_appendJs("<script>window.close();window.opener.location = '" . $this->_loginPostRedirect() . "';</script>");
            //$this->_redirectUrl(Mage::helper('customer')->getDashboardUrl());
        }
    }

    protected function _appendJs($string)
    {
        echo $string;
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

    public function getBaseUrl()
    {
        return $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')
            ->getStore()
            ->getBaseUrl();
    }
}