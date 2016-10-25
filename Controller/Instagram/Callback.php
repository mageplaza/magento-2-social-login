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
use Magento\Customer\Model\CustomerFactory;
use Mageplaza\SocialLogin\Model\SocialFactory as InstagramModelFactory;
use Mageplaza\SocialLogin\Model\ResourceModel\Social\CollectionFactory as InstagramCollectionFactory;

class Callback extends Action
{
    const SOCIAL_TYPE = 'instagram';
    protected $resultPageFactory;
    protected $instagram;
    protected $helperInstagram;
    protected $accountManagement;
    protected $customerUrl;
    protected $session;
    protected $helperData;
    protected $storeManager;
    protected $messageManager;
    protected $instagramCustomerCollectionFactory;
    protected $instagramCustomerModelFactory;
    protected $customerFactory;

    public function __construct(
        Context $context,
        Instagram $instagram,
        StoreManagerInterface $storeManager,
        HelperInstagram $helperInstagram,
        HelperData $helperData,
        PageFactory $resultPageFactory,
        AccountManagementInterface $accountManagement,
        CustomerUrl $customerUrl,
        Session $customerSession,
        InstagramCollectionFactory $instagramCustomerCollectionFactory,
        InstagramModelFactory $instagramCustomerModelFactory,
        CustomerFactory $customerFactory
    ) {
        parent::__construct($context);
        $this->instagram                          = $instagram;
        $this->storeManager                       = $storeManager;
        $this->helperInstagram                    = $helperInstagram;
        $this->helperData                         = $helperData;
        $this->resultPageFactory                  = $resultPageFactory;
        $this->accountManagement                  = $accountManagement;
        $this->customerUrl                        = $customerUrl;
        $this->session                            = $customerSession;
        $this->messageManager                     = $context->getMessageManager();
        $this->instagramCustomerCollectionFactory = $instagramCustomerCollectionFactory;
        $this->instagramCustomerModelFactory      = $instagramCustomerModelFactory;
        $this->customerFactory                    = $customerFactory;

    }

    public function execute()
    {

        $instagram = $this->instagram->getInstagram();

        $code = $this->getRequest()->getParam('code');
        if (!$code) {
            $this->_appendJs("<script type=\"text/javascript\">try{window.opener.location.reload(true);}catch(e){window.opener.location.href=\"" . $this->getBaseUrl() . "\"} window.close();</script>");
        }
        $data       = $instagram->getOAuthToken($code);
        $customerId = $this->getCustomerIdByInstagramId($data->user->id);
        if ($customerId) {
            $customer = $this->customerFactory->create()->load($customerId);
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
            $user              = array();
            $name              = $data->user->full_name;
            $instagramUserName = $data->user->username;
            $arrName           = explode(' ', $name, 2);
            $email             = $instagramUserName . '@instagram.com';
            if (empty($arrName[0])) {
                $arrName[0] = $name;
            }
            if (!empty($arrName[1])) {
                $user['lastname'] = $arrName[1];
            } else {
                $user['lastname'] = 'yourLastName';
            }
            $user['firstname'] = $arrName[0];
            $user['email']     = $email;


            //get website_id and sote_id of each stores
            $store_id   = $this->storeManager->getStore()->getId(); //add
            $website_id = $this->storeManager->getWebsite()->getId(); //add
            $customer   = $this->helperData->getCustomerByEmail($user['email'], $website_id); //add edition
            if (!$customer) {
                //Login multisite
                $customer = $this->helperData->createCustomerMultiWebsite($user, $website_id, $store_id);
            }
            $this->setAuthorCustomer($data->user->id, $customer->getId());
            $this->session->setCustomerIdSocialLogin($data->user->id);
            $this->session->setCustomerAsLoggedIn($customer);
            $this->session->regenerateId();
            $this->messageManager->addNotice(__('Update your contact details'));
            $this->_appendJs("<script>window.close();window.opener.location = '" . $this->storeManager->getStore()->getUrl('customer/account/edit') . "';</script>");
        }
    }

    public function setAuthorCustomer($instagramId, $customerId)
    {
        $instagramCustomer = $this->instagramCustomerModelFactory->create();
        $instagramCustomer->setData('social_id', $instagramId);
        $instagramCustomer->setData('customer_id', $customerId);
        $instagramCustomer->setData('type', self::SOCIAL_TYPE);
        $instagramCustomer->setData('is_send_password_email',$this->helperInstagram->sendPassword());
        try {
            $instagramCustomer->save();
        } catch (Exception $e) {
        }

        return;
    }

    public function getCustomerIdByInstagramId($instagramId)
    {
        $customer = $this->instagramCustomerCollectionFactory->create();
        $user     = $customer
            ->addFieldToFilter('social_id', $instagramId)
            ->addFieldToFilter('type', self::SOCIAL_TYPE)
            ->getFirstItem();
        if ($user && $user->getId())
            return $user->getCustomerId();
        else
            return null;
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