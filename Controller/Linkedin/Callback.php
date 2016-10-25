<?php

namespace Mageplaza\SocialLogin\Controller\Linkedin;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Mageplaza\SocialLogin\Model\Linkedin;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\SocialLogin\Helper\Linkedin\Data as HelperLinkedin;
use Mageplaza\SocialLogin\Helper\Data as HelperData;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Customer\Model\Session;
use Magento\Framework\Controller\ResultFactory;
use Symfony\Component\Config\Definition\Exception\Exception;

class Callback extends Action
{
    protected $resultPageFactory;
    protected $linkedin;
    protected $helperLinkedin;
    protected $accountManagement;
    protected $customerUrl;
    protected $session;
    protected $helperData;
    protected $storeManager;
    protected $resultFactory;

    public function __construct(
        Context $context,
        Linkedin $linkedin,
        StoreManagerInterface $storeManager,
        HelperLinkedin $helperLinkedin,
        HelperData $helperData,
        PageFactory $resultPageFactory,
        AccountManagementInterface $accountManagement,
        CustomerUrl $customerUrl,
        Session $customerSession
    ) {
        parent::__construct($context);
        $this->linkedin          = $linkedin;
        $this->storeManager      = $storeManager;
        $this->helperLinkedin    = $helperLinkedin;
        $this->helperData        = $helperData;
        $this->resultPageFactory = $resultPageFactory;
        $this->accountManagement = $accountManagement;
        $this->customerUrl       = $customerUrl;
        $this->session           = $customerSession;
        $this->resultFactory     = $context->getResultFactory();

    }

    public function execute()
    {
        $param = $this->getRequest()->getParams();
        if (isset($param['oauth_problem'])) {
            if ($param['oauth_problem'] == 'user_refused') {
                $this->_appendJs("<script>window.close()</script>");
                exit;
            }
        }
        $config['base_url']        = $this->getBaseUrl() . 'sociallogin/linkedin/login';
        $config['callback_url']    = $this->getBaseUrl() . 'sociallogin/linkedin/callback';
        $config['linkedin_access'] = $this->helperLinkedin->getApiKey();
        $config['linkedin_secret'] = $this->helperLinkedin->getClientKey();

        //        $linkedin = Mage::helper('linkedinlogin/linkedin');
        $this->linkedin->setParams($config['linkedin_access'], $config['linkedin_secret'], $config['callback_url']);

        if ($this->getRequest()->getParam('oauth_verifier')) {
            $oauthVerifier = $this->getRequest()->getParam('oauth_verifier');
            $this->session->setOauthVerifier($oauthVerifier);

            $this->linkedin->request_token  = unserialize($this->session->getRequestToken());
            $this->linkedin->oauth_verifier = $this->session->getOauthVerifier();
            $this->linkedin->getAccessToken($oauthVerifier);
            $this->session->setOauthAccessToken(serialize($this->linkedin->access_token));
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setPath($config['callback_url']);
        } else {
            $this->linkedin->request_token  = unserialize($this->session->getRequestToken());
            $this->linkedin->oauth_verifier = $this->session->getOauthVerifier();
            $this->linkedin->access_token   = unserialize($this->session->getOauthAccessToken());
        }
        $xml_response      = $this->linkedin->getProfile("~:(id,first-name,last-name)");
        $xmlToObject       = simplexml_load_string($xml_response);
        $json              = json_encode($xmlToObject);
        $data              = json_decode($json, true);
        $xml_responseEmail = $this->linkedin->getProfile("~:(email-address)");
        $xmlToObjectEmail  = simplexml_load_string($xml_responseEmail);
        $jsonEmail         = json_encode($xmlToObjectEmail);
        $dataEmail         = json_decode($jsonEmail, true);
        $store_id          = $this->storeManager->getStore()->getId(); //add
        $website_id        = $this->storeManager->getWebsite()->getId(); //add
        $user              = array();
        $user['firstname'] = $data['first-name'];
        $user['lastname']  = $data['last-name'];
        $user['email']     = $dataEmail['email-address'];
        $customer          = $this->helperData->getCustomerByEmail($dataEmail['email-address'], $website_id); //add edition
        if (!$customer) {
            //Login multisite
            $customer = $this->helperData->createCustomerMultiWebsite($user, $website_id, $store_id);
            if ($this->helperLinkedin->sendPassword()) {
                $customer->sendPasswordReminderEmail();
            }
        }
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

        return;
    }

    protected function _appendJs($string)
    {
        echo $string;
    }

    public function getBaseUrl()
    {
        return $this->storeManager->getStore()->getUrl();
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