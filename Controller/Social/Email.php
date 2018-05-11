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
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\SocialLogin\Helper\Social as SocialHelper;
use Mageplaza\SocialLogin\Model\Social;

/**
 * Class AbstractSocial
 *
 * @package Mageplaza\SocialLogin\Controller
 */
class Email extends Login
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
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
     * @type \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Registry
     */

    protected $_registry;

    /**
     * @var
     */
    protected $customerModel;

    /**
     * Email constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param AccountManagementInterface $accountManager
     * @param SocialHelper $apiHelper
     * @param Social $apiObject
     * @param Session $customerSession
     * @param AccountRedirect $accountRedirect
     * @param RawFactory $resultRawFactory
     * @param JsonFactory $resultJsonFactory
     * @param Customer $customerModel
     * @param Registry $registry
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        AccountManagementInterface $accountManager,
        SocialHelper $apiHelper,
        Social $apiObject,
        Session $customerSession,
        AccountRedirect $accountRedirect,
        RawFactory $resultRawFactory,
        JsonFactory $resultJsonFactory,
        Customer $customerModel,
        Registry $registry
    )
    {
        parent::__construct($context, $storeManager, $accountManager, $apiHelper, $apiObject, $customerSession, $accountRedirect, $resultRawFactory, $registry);

        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerModel     = $customerModel;
    }

    /**
     * @return $this|array|void
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();

        $type = $this->apiHelper->setType($this->getRequest()->getParam('type', null));

        $result = [
            'success' => false,
            'message' => [],
            'url'     => ''
        ];
        if (!$type) {
            $this->_forward('noroute');

            return;
        }
        $realEmail = $this->getRequest()->getParam('realEmail', null);
        if (!$realEmail) {
            $result['message'] = __('Email is Null');

            return $resultJson->setData($result);
        }
        $userProfile        = $this->session->getUserProfile();
        $userProfile->email = $realEmail;

        /** @var \Magento\Store\Model\StoreManagerInterface $storeManager */
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        $store   = $this->storeManager->getStore($storeId);
        $this->customerModel->setWebsiteId($store->getWebsiteId());
        if ($this->customerModel->loadByEmail($userProfile->email)->getId()) {
            $result['message'] = __('Email already exists');

            return $resultJson->setData($result);
        }

        $customer = $this->createCustomerProcess($userProfile, $type);
        $this->refresh($customer);

        $result['success'] = true;
        $result['message'] = __('Success!');
        $result['url']     = $this->_loginPostRedirect();

        return $resultJson->setData($result);
    }
}