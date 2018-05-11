<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_SocialLogin
 * @copyright   Copyright (c) Mageplaza (http://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\SocialLogin\Controller\Social;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Account\Redirect as AccountRedirect;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PhpCookieManager;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\SocialLogin\Helper\Social as SocialHelper;
use Mageplaza\SocialLogin\Model\Social;

/**
 * Class AbstractSocial
 *
 * @package Mageplaza\SocialLogin\Controller
 */
abstract class AbstractSocial extends Action
{
    /**
     * @type \Magento\Customer\Model\Session
     */
    protected $session;

    /**
     * @type \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @type \Magento\Customer\Api\AccountManagementInterface
     */
    protected $accountManager;

    /**
     * @type \Mageplaza\SocialLogin\Helper\Social
     */
    protected $apiHelper;

    /**
     * @type \Mageplaza\SocialLogin\Model\Social
     */
    protected $apiObject;

    /**
     * @var AccountRedirect
     */
    protected $accountRedirect;

    /**
     * @type
     */
    protected $cookieMetadataManager;

    /**
     * @type
     */
    protected $cookieMetadataFactory;

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
     * Login constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param AccountManagementInterface $accountManager
     * @param SocialHelper $apiHelper
     * @param Social $apiObject
     * @param Session $customerSession
     * @param AccountRedirect $accountRedirect
     * @param RawFactory $resultRawFactory
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        AccountManagementInterface $accountManager,
        SocialHelper $apiHelper,
        Social $apiObject,
        Session $customerSession,
        AccountRedirect $accountRedirect,
        RawFactory $resultRawFactory
    )
    {
        parent::__construct($context);

        $this->storeManager     = $storeManager;
        $this->accountManager   = $accountManager;
        $this->apiHelper        = $apiHelper;
        $this->apiObject        = $apiObject;
        $this->session          = $customerSession;
        $this->accountRedirect  = $accountRedirect;
        $this->resultRawFactory = $resultRawFactory;
    }

    /**
     * Get Store object
     *
     * @return \Magento\Store\Api\Data\StoreInterface
     */
    public function getStore()
    {
        return $this->storeManager->getStore();
    }

    /**
     * @param $userProfile
     * @param $type
     * @return bool|\Magento\Customer\Model\Customer|mixed
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createCustomerProcess($userProfile, $type)
    {
        $name = explode(' ', $userProfile->displayName ?: __('New User'));
        $user = array_merge([
            'email'      => $userProfile->email ?: $userProfile->identifier . '@' . strtolower($type) . '.com',
            'firstname'  => $userProfile->firstName ?: (array_shift($name) ?: $userProfile->identifier),
            'lastname'   => $userProfile->lastName ?: (array_shift($name) ?: $userProfile->identifier),
            'identifier' => $userProfile->identifier,
            'type'       => $type
        ], $this->getUserData($userProfile));

        return $this->createCustomer($user, $type);
    }

    /**
     * Create customer from social data
     *
     * @param $user
     * @param $type
     * @return bool|\Magento\Customer\Model\Customer|mixed
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createCustomer($user, $type)
    {
        $customer = $this->apiObject->getCustomerByEmail($user['email'], $this->getStore()->getWebsiteId());
        if (!$customer->getId()) {
            try {
                $customer = $this->apiObject->createCustomerSocial($user, $this->getStore());
            } catch (\Exception $e) {
                $this->emailRedirect($e->getMessage(), false);

                return false;
            }
        } else {
            $this->apiObject->setAuthorCustomer($user['identifier'], $customer->getId(), $type);
        }

        return $customer;
    }

    /**
     * @param $profile
     * @return array
     */
    protected function getUserData($profile)
    {
        return [];
    }

    /**
     * Redirect to login page if social data is not contain email address
     *
     * @param $apiLabel
     * @param bool $needTranslate
     * @return $this
     */
    public function emailRedirect($apiLabel, $needTranslate = true)
    {
        $message = $needTranslate ? __('Email is Null, Please enter email in your %1 profile', $apiLabel) : $apiLabel;
        $this->messageManager->addErrorMessage($message);
        $this->_redirect('customer/account/login');

        return $this;
    }

    /**
     * Return redirect url by config
     *
     * @return mixed
     */
    protected function _loginPostRedirect()
    {
        $url = $this->_url->getUrl('customer/account');

        if ($this->_request->getParam('authen') == 'popup') {
            $url = $this->_url->getUrl('checkout');
        } else {
            $requestedRedirect = $this->accountRedirect->getRedirectCookie();
            if (!$this->apiHelper->getConfigValue('customer/startup/redirect_dashboard') && $requestedRedirect) {
                $url = $this->_redirect->success($requestedRedirect);
                $this->accountRedirect->clearRedirectCookie();
            }
        }

        $object = ObjectManager::getInstance()->create(DataObject::class, ['url' => $url]);
        $this->_eventManager->dispatch('social_manager_get_login_redirect', [
            'object'  => $object,
            'request' => $this->_request
        ]);
        $url = $object->getUrl();

        return $url;
    }

    /**
     * Return javascript to redirect when login success
     *
     * @param null $content
     * @return \Magento\Framework\Controller\Result\Raw
     */
    public function _appendJs($content = null)
    {
        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $resultRaw = $this->resultRawFactory->create();

        return $resultRaw->setContents($content ?: sprintf("<script>window.opener.socialCallback('%s', window);</script>", $this->_loginPostRedirect()));
    }

    /**
     * @param $customer
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    public function refresh($customer)
    {
        if ($customer && $customer->getId()) {
            $this->session->setCustomerAsLoggedIn($customer);
            $this->session->regenerateId();

            if ($this->getCookieManager()->getCookie('mage-cache-sessid')) {
                $metadata = $this->getCookieMetadataFactory()->createCookieMetadata();
                $metadata->setPath('/');
                $this->getCookieManager()->deleteCookie('mage-cache-sessid', $metadata);
            }
        }
    }

    /**
     * Retrieve cookie manager
     *
     * @deprecated
     * @return \Magento\Framework\Stdlib\Cookie\PhpCookieManager
     */
    private function getCookieManager()
    {
        if (!$this->cookieMetadataManager) {
            $this->cookieMetadataManager = ObjectManager::getInstance()->get(
                PhpCookieManager::class
            );
        }

        return $this->cookieMetadataManager;
    }

    /**
     * Retrieve cookie metadata factory
     *
     * @deprecated
     * @return \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    private function getCookieMetadataFactory()
    {
        if (!$this->cookieMetadataFactory) {
            $this->cookieMetadataFactory = ObjectManager::getInstance()->get(
                CookieMetadataFactory::class
            );
        }

        return $this->cookieMetadataFactory;
    }
}