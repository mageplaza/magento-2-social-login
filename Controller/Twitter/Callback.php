<?php

namespace Mageplaza\SocialLogin\Controller\Twitter;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Mageplaza\SocialLogin\Model\Twitter;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\SocialLogin\Helper\Twitter\Data as TwitterHelper;
use Mageplaza\SocialLogin\Helper\Data as HelperData;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Session\SessionManagerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Magento\Customer\Model\CustomerFactory;
use Mageplaza\SocialLogin\Model\SocialFactory as TwitterModelFactory;
use Mageplaza\SocialLogin\Model\ResourceModel\Social\CollectionFactory as TwitterCollectionFactory;


class Callback extends Action
{
    const SOCIAL_TYPE = 'twitter';
    protected $resultPageFactory;
    protected $twitter;
    protected $twitterHelper;
    protected $accountManagement;
    protected $customerUrl;
    protected $customerSession;
    protected $session;
    protected $helperData;
    protected $storeManager;
    protected $messageManager;
    protected $twitterCustomerCollectionFactory;
    protected $twitterCustomerModelFactory;
    protected $customerFactory;

    public function __construct(
        Context $context,
        Twitter $twitter,
        StoreManagerInterface $storeManager,
        TwitterHelper $twitterHelper,
        HelperData $helperData,
        PageFactory $resultPageFactory,
        AccountManagementInterface $accountManagement,
        CustomerUrl $customerUrl,
        CustomerSession $customerSession,
        SessionManagerInterface $session,
        TwitterCollectionFactory $twitterCustomerCollectionFactory,
        TwitterModelFactory $twitterCustomerModelFactory,
        CustomerFactory $customerFactory
    ) {
        parent::__construct($context);
        $this->twitter                          = $twitter;
        $this->storeManager                     = $storeManager;
        $this->twitterHelper                    = $twitterHelper;
        $this->helperData                       = $helperData;
        $this->resultPageFactory                = $resultPageFactory;
        $this->accountManagement                = $accountManagement;
        $this->customerUrl                      = $customerUrl;
        $this->customerSession                  = $customerSession;
        $this->session                          = $session;
        $this->messageManager                   = $context->getMessageManager();
        $this->twitterCustomerCollectionFactory = $twitterCustomerCollectionFactory;
        $this->twitterCustomerModelFactory      = $twitterCustomerModelFactory;
        $this->customerFactory                  = $customerFactory;

    }

    public function execute()
    {
        $twitter      = $this->twitter;
        $requestToken = $this->session->getRequestToken();

        try {
            $token = $twitter->getAccessToken(
                array(
                    'oauth_token'    => $this->getRequest()->getParam('oauth_token'),
                    'oauth_verifier' => $this->getRequest()->getParam('oauth_verifier')
                ),
                unserialize($requestToken)
            );
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
            $this->_appendJs("<script type=\"text/javascript\">try{window.opener.location.reload(true);}catch(e){window.opener.location.href=\"" . $this->storeManager->getStore()->getUrl() . "\"} window.close();</script>");
            exit;
        }
        $customerId = $this->getCustomerIdByTwitterId($token->user_id);

        if ($customerId) {
            $customer = $this->customerFactory->create()->load($customerId);
            if ($customer->getConfirmation()) {
                try {
                    $customer->setConfirmation(null);
                    $customer->save();
                } catch (\Exception $e) {
                }
            }
            $this->customerSession->setCustomerAsLoggedIn($customer);
            $this->_appendJs("<script type=\"text/javascript\">try{window.opener.location.href=\"" . $this->_loginPostRedirect() . "\";}catch(e){window.opener.location.reload(true);} window.close();</script>");
            exit;
        } else {
            $screenName        = (string)$token->screen_name;
            $email             = $screenName . '@twitter.com';
            $user['firstname'] = $screenName;
            $user['lastname']  = $screenName;
            $user['email']     = $email;
            $store_id          = $this->storeManager->getStore()->getStoreId();
            $website_id        = $this->storeManager->getStore()->getWebsiteId();
            $customer          = $this->helperData->getCustomerByEmail($user['email'], $website_id);
            if (!$customer || !$customer->getId()) {
                $customer = $this->helperData->createCustomerMultiWebsite($user, $website_id, $store_id);
            }
            $confirmationStatus = $this->accountManagement->getConfirmationStatus($customer->getId());
            if ($confirmationStatus === AccountManagementInterface::ACCOUNT_CONFIRMATION_REQUIRED) {
                $this->customerUrl->getEmailConfirmationUrl($customer->getEmail());
            } else {
                $this->customerSession->setCustomerAsLoggedIn($customer);
            }
            $this->setAuthorCustomer($token->user_id, $customer->getId());
            $this->session->setCustomerIdSocialLogin($token->user_id);
            $this->messageManager->addNotice(__('Update your contact details'));
            $this->_appendJs("<script>window.close();window.opener.location = '" . $this->storeManager->getStore()->getUrl('customer/account/edit') . "';</script>");
            exit;
        }
    }

    public function setAuthorCustomer($twId, $customerId)
    {
        $twitterCustomer = $this->twitterCustomerModelFactory->create();
        $twitterCustomer->setData('social_id', $twId);
        $twitterCustomer->setData('customer_id', $customerId);
        $twitterCustomer->setData('type', self::SOCIAL_TYPE);
        $twitterCustomer->setData('is_send_password_email',$this->twitterHelper->sendPassword());
        try {
            $twitterCustomer->save();
        } catch (Exception $e) {
        }

        return;
    }

    public function getCustomerIdByTwitterId($twitterId)
    {
        $customer = $this->twitterCustomerCollectionFactory->create();
        $user     = $customer->addFieldToFilter('social_id', $twitterId)
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