<?php
namespace Mageplaza\SocialLogin\Controller\Facebook;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Mageplaza\SocialLogin\Model\Facebook;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\SocialLogin\Helper\Facebook\Data as DataHelper;
use Mageplaza\SocialLogin\Helper\Data as SocialHelper;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Customer\Model\Session;
use Symfony\Component\Config\Definition\Exception\Exception;
use Mageplaza\SocialLogin\Model\SocialFactory as FacebookModelFactory;
use Mageplaza\SocialLogin\Model\ResourceModel\Social\CollectionFactory as FacebookCollectionFactory;
use Magento\Customer\Model\CustomerFactory;


class Callback extends Action
{
    const SOCIAL_TYPE = 'facebook';
    protected $resultPageFactory;
    protected $facebook;
    protected $dataHelper;
    protected $socialHelper;
    protected $accountManagement;
    protected $customerUrl;
    protected $session;
    protected $facebookCustomerCollectionFactory;
    protected $facebookCustomerModelFactory;
    protected $customerFactory;

    public function __construct(
        Context $context,
        Facebook $facebook,
        StoreManagerInterface $storeManager,
        DataHelper $dataHelper,
        SocialHelper $socialHelper,
        PageFactory $resultPageFactory,
        AccountManagementInterface $accountManagement,
        CustomerUrl $customerUrl,
        FacebookModelFactory $facebookCustomerModelFactory,
        FacebookCollectionFactory $facebookCustomerCollectionFactory,
        CustomerFactory $customerFactory,
        Session $customerSession
    ) {
        parent::__construct($context);
        $this->facebook                          = $facebook;
        $this->storeManager                      = $storeManager;
        $this->dataHelper                        = $dataHelper;
        $this->socialHelper                      = $socialHelper;
        $this->resultPageFactory                 = $resultPageFactory;
        $this->accountManagement                 = $accountManagement;
        $this->customerUrl                       = $customerUrl;
        $this->session                           = $customerSession;
        $this->facebookCustomerModelFactory      = $facebookCustomerModelFactory;
        $this->facebookCustomerCollectionFactory = $facebookCustomerCollectionFactory;
        $this->customerFactory                   = $customerFactory;
    }

    public function execute()
    {
        $isAuth   = $this->getRequest()->getParam('auth');
        $facebook = $this->facebook->newFacebook();
        $userId   = $facebook->getUser();
        if ($isAuth && !$userId && $this->getRequest()->getParam('error_reason') == 'user_denied') {
            $this->_appendJs("<script>window.close()</script>");
            exit;
        } elseif ($isAuth && !$userId) {
            $loginUrl = $facebook->getLoginUrl(array('scope' => 'email'));
            $this->_appendJs("<script type='text/javascript'>top.location.href = '$loginUrl';</script>");
            exit;
        }

        $user = $this->facebook->getFacebookUser();
        $customerId = $this->getCustomerIdByFacebookId($user['id']);
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
        }
        if ($isAuth && $user) {
            if (isset($user['email'])) {
                $store_id   = $this->storeManager->getStore()->getStoreId();
                $website_id = $this->storeManager->getStore()->getWebsiteId();
                $data       = array('firstname' => $user['first_name'], 'lastname' => $user['last_name'], 'email' => $user['email']);
                $customer   = $this->dataHelper->getCustomerByEmail($data['email'], $website_id); //add edition
                if (!$customer || !$customer->getId()) {
                    $customer = $this->dataHelper->createCustomerMultiWebsite($data, $website_id, $store_id);
                    if ($this->dataHelper->sendPassword()) {
                        try {
                            $customer->sendPasswordReminderEmail();
                        } catch (Exception $e) {
                        }
                    }
                }
                $this->setAuthorCustomer($userId, $customer->getId());
                $confirmationStatus = $this->accountManagement->getConfirmationStatus($customer->getId());
                if ($confirmationStatus === AccountManagementInterface::ACCOUNT_CONFIRMATION_REQUIRED) {
                    $this->customerUrl->getEmailConfirmationUrl($customer->getEmail());
                } else {
                    $this->session->setCustomerAsLoggedIn($customer);
                    $this->session->regenerateId();

                }
                $this->_appendJs("<script type=\"text/javascript\">try{window.opener.location.href=\"" . $this->_loginPostRedirect() . "\";}catch(e){window.opener.location.reload(true);} window.close();</script>");
                exit;
            } elseif (isset($user['id'])) {
                $facebookEmail = $user['id'] . '@facebook.com';
                $store_id      = $this->storeManager->getStore()->getId(); //add
                $website_id    = $this->storeManager->getWebsite()->getId(); //add
                $customer      = $this->socialHelper->getCustomerByEmail($facebookEmail, $website_id); //add edition
                if (!$customer) {
                    //Login multisite
                    $data              = array();
                    $data['email']     = $facebookEmail;
                    $data['firstname'] = $user['first_name'];
                    $data['lastname']  = $user['last_name'];
                    $customer          = $this->socialHelper->createCustomerMultiWebsite($data, $website_id, $store_id);
                }

                $this->setAuthorCustomer($user['id'], $customer->getId());
                $this->session->setCustomerIdSocialLogin($userId);
                $this->session->setCustomerAsLoggedIn($customer);
                $this->session->regenerateId();
                $this->messageManager->addNotice(__('Update your contact details'));
                $this->_appendJs("<script>window.close();window.opener.location = '" . $this->storeManager->getStore()->getUrl('customer/account/edit') . "';</script>");
            } else {
                $this->_appendJs("<script type=\"text/javascript\">try{window.opener.location.reload(true);}catch(e){window.opener.location.href=\"" . $this->storeManager->getStore()->getUrl() . "\"} window.close();</script>");
                exit;
            }
        }
    }

    protected function _appendJs($string)
    {
        echo $string;
    }

    protected function _loginPostRedirect()
    {
        $redirectPage = $this->dataHelper->getConfigValue(('general/select_redirect_page'), $this->storeManager->getStore()->getId());
        switch ($redirectPage) {
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
                return $this->dataHelper->getConfigValue(('general/custom_page'), $this->storeManager->getStore()->getId());
                break;
            default:
                return $this->storeManager->getStore()->getUrl('customer/account');
                break;
        }
    }

    public function setAuthorCustomer($facebookId, $customerId)
    {
        $facebookCustomer = $this->facebookCustomerModelFactory->create();
        $facebookCustomer->setData('social_id', $facebookId);
        $facebookCustomer->setData('customer_id', $customerId);
        $facebookCustomer->setData('type', self::SOCIAL_TYPE);
        $facebookCustomer->setData('is_send_password_email', $this->dataHelper->sendPassword());
        try {
            $facebookCustomer->save();
        } catch (Exception $e) {
        }

        return;
    }

    public function getCustomerIdByFacebookId($facebookId)
    {
        $customer = $this->facebookCustomerCollectionFactory->create();
        $user     = $customer
            ->addFieldToFilter('social_id', $facebookId)
            ->addFieldToFilter('type', self::SOCIAL_TYPE)
            ->getFirstItem();
        if ($user && $user->getId())
            return $user->getCustomerId();
        else
            return null;
    }

}
