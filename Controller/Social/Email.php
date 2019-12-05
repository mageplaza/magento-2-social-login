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
 * @category  Mageplaza
 * @package   Mageplaza_SocialLogin
 * @copyright Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license   https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\SocialLogin\Controller\Social;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Account\Redirect as AccountRedirect;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\SocialLogin\Helper\Social as SocialHelper;
use Mageplaza\SocialLogin\Model\Social;

/**
 * Class AbstractSocial
 *
 * @package Mageplaza\SocialLogin\Controller
 */
class Email extends AbstractSocial
{
    /**
     * @type JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var EncryptorInterface
     */
    protected $_encrypt;

    /**
     * Email constructor.
     *
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param AccountManagementInterface $accountManager
     * @param SocialHelper $apiHelper
     * @param Social $apiObject
     * @param Session $customerSession
     * @param AccountRedirect $accountRedirect
     * @param RawFactory $resultRawFactory
     * @param JsonFactory $resultJsonFactory
     * @param CustomerFactory $customerFactory
     * @param EncryptorInterface $encrypt
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
        CustomerFactory $customerFactory,
        EncryptorInterface $encrypt
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerFactory   = $customerFactory;
        $this->_encrypt          = $encrypt;

        parent::__construct(
            $context,
            $storeManager,
            $accountManager,
            $apiHelper,
            $apiObject,
            $customerSession,
            $accountRedirect,
            $resultRawFactory
        );
    }

    /**
     * @return ResponseInterface|Json|ResultInterface|void
     * @throws FailureToSendException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        /**
         * @var Json $resultJson
         */
        $resultJson = $this->resultJsonFactory->create();
        $params     = $this->getRequest()->getParams();
        $type       = $this->apiHelper->setType($params['type']);

        if (!$type) {
            $this->_forward('noroute');

            return;
        }

        $result    = ['success' => false];
        $realEmail = isset($params['realEmail']) ? $params['realEmail'] : null;
        $firstname = isset($params['firstname']) ? $params['firstname'] : null;
        $lastname  = isset($params['lastname']) ? $params['lastname'] : null;
        $password  = isset($params['password']) ? $this->_encrypt->getHash($params['password'], true) : null;

        $customer = $this->customerFactory->create()
            ->setWebsiteId($this->getStore()->getWebsiteId())
            ->loadByEmail($realEmail);
        if ($customer->getId()) {
            $result['message'] = __('Email already exists');

            return $resultJson->setData($result);
        }

        $userProfile            = $this->session->getUserProfile();
        $userProfile->email     = $realEmail ?: $userProfile->email;
        $userProfile->firstName = $firstname ?: $userProfile->firstName;
        $userProfile->lastName  = $lastname ?: $userProfile->lastName;
        $userProfile->password  = $password ?: null;

        $customer = $this->createCustomerProcess($userProfile, $type);
        $this->refresh($customer);

        $result['success'] = true;
        $result['message'] = __('Success!');
        $result['url']     = $this->_loginPostRedirect();

        return $resultJson->setData($result);
    }
}
