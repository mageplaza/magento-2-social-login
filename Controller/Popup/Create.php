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

namespace Mageplaza\SocialLogin\Controller\Popup;

use Exception;
use Magento\Captcha\Helper\Data as CaptchaData;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Controller\Account\CreatePost;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PhpCookieManager;
use Mageplaza\SocialLogin\Helper\Data;

/**
 * Class Create
 *
 * @package Mageplaza\SocialLogin\Controller\Popup
 */
class Create extends CreatePost
{
    /**
     * @type JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @type CaptchaData
     */
    protected $captchaHelper;

    /**
     * @type Data
     */
    protected $socialHelper;

    /**
     * @var PhpCookieManager
     */
    private $cookieMetadataManager;

    /**
     * @var CookieMetadataFactory
     */
    private $cookieMetadataFactory;

    /**
     * @return JsonFactory|mixed
     */
    protected function getJsonFactory()
    {
        if (!$this->resultJsonFactory) {
            $this->resultJsonFactory = ObjectManager::getInstance()->get(JsonFactory::class);
        }

        return $this->resultJsonFactory;
    }

    /**
     * @return CaptchaData|mixed
     */
    protected function getCaptchaHelper()
    {
        if (!$this->captchaHelper) {
            $this->captchaHelper = ObjectManager::getInstance()->get(CaptchaData::class);
        }

        return $this->captchaHelper;
    }

    /**
     * @return Data|mixed
     */
    protected function getSocialHelper()
    {
        if (!$this->socialHelper) {
            $this->socialHelper = ObjectManager::getInstance()->get(Data::class);
        }

        return $this->socialHelper;
    }

    /**
     * Check default captcha
     *
     * @return bool
     */
    public function checkCaptcha()
    {
        $formId       = 'user_create';
        $captchaModel = $this->getCaptchaHelper()->getCaptcha($formId);
        $resolve      = $this->getSocialHelper()->captchaResolve($this->getRequest(), $formId);

        return !($captchaModel->isRequired() && !$captchaModel->isCorrect($resolve));
    }

    /**
     * Create customer account action
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        /**
         * @var Json $resultJson
         */
        $resultJson = $this->getJsonFactory()->create();
        $result     = [
            'success' => false,
            'message' => []
        ];

        if (!$this->checkCaptcha()) {
            $result['message'] = __('Incorrect CAPTCHA.');

            return $resultJson->setData($result);
        }

        if ($this->session->isLoggedIn() || !$this->registration->isAllowed()) {
            $result['redirect'] = $this->urlModel->getUrl('customer/account');

            return $resultJson->setData($result);
        }

        if (!$this->getRequest()->isPost()) {
            $result['message'] = __('Data error. Please try again.');

            return $resultJson->setData($result);
        }

        $this->session->regenerateId();

        try {
            $address   = $this->extractAddress();
            $addresses = $address === null ? [] : [$address];

            $customer = $this->customerExtractor->extract('customer_account_create', $this->_request);
            $customer->setAddresses($addresses);

            $password     = $this->getRequest()->getParam('password');
            $confirmation = $this->getRequest()->getParam('password_confirmation');

            if (!$this->checkPasswordConfirmation($password, $confirmation)) {
                $result['message'][] = __('Please make sure your passwords match.');
            } else {
                $customer = $this->accountManagement
                    ->createAccount($customer, $password);

                if ($this->getRequest()->getParam('is_subscribed', false)) {
                    $this->subscriberFactory->create()->subscribeCustomerById($customer->getId());
                }

                $this->_eventManager->dispatch(
                    'customer_register_success',
                    [
                        'account_controller' => $this,
                        'customer'           => $customer
                    ]
                );

                $confirmationStatus = $this->accountManagement->getConfirmationStatus($customer->getId());

                if ($confirmationStatus === AccountManagementInterface::ACCOUNT_CONFIRMATION_REQUIRED) {
                    $email = $this->customerUrl->getEmailConfirmationUrl($customer->getEmail());
                    // @codingStandardsIgnoreStart
                    $result['success'] = true;
                    $this->messageManager->addSuccess(
                        __(
                            'You must confirm your account. Please check your email for the confirmation link or <a href="%1">click here</a> for a new link.',
                            $email
                        )
                    );
                } else {
                    $result['success']   = true;
                    $result['message'][] = __('Create an account successfully. Please wait...');
                    $this->session->setCustomerDataAsLoggedIn($customer);
                }

                if ($this->getCookieManager()->getCookie('mage-cache-sessid')) {
                    $metadata = $this->getCookieMetadataFactory()->createCookieMetadata();
                    $metadata->setPath('/');
                    $this->getCookieManager()->deleteCookie('mage-cache-sessid', $metadata);
                }
            }
        } catch (StateException $e) {
            $url = $this->urlModel->getUrl('customer/account/forgotpassword');
            // @codingStandardsIgnoreStart
            $result['message'][] = __(
                'There is already an account with this email address. If you are sure that it is your email address, <a href="%1">click here</a> to get your password and access your account.',
                $url
            );
        } catch (InputException $e) {
            $result['message'][] = $this->escaper->escapeHtml($e->getMessage());
            foreach ($e->getErrors() as $error) {
                $result['message'][] = $this->escaper->escapeHtml($error->getMessage());
            }
        } catch (LocalizedException $e) {
            $result['message'][] = $this->escaper->escapeHtml($e->getMessage());
        } catch (Exception $e) {
            $result['message'][] = __('We can\'t save the customer.');
        }

        $result['url'] = $this->_loginPostRedirect();
        $this->session->setCustomerFormData($this->getRequest()->getPostValue());

        return $resultJson->setData($result);
    }

    /**
     * Retrieve cookie manager
     *
     * @return PhpCookieManager
     * @deprecated
     */
    protected function getCookieManager()
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
     * @return CookieMetadataFactory
     * @deprecated
     */
    protected function getCookieMetadataFactory()
    {
        if (!$this->cookieMetadataFactory) {
            $this->cookieMetadataFactory = ObjectManager::getInstance()->get(
                CookieMetadataFactory::class
            );
        }

        return $this->cookieMetadataFactory;
    }

    /**
     * Make sure that password and password confirmation matched
     *
     * @param string $password
     * @param string $confirmation
     *
     * @return boolean
     */
    protected function checkPasswordConfirmation($password, $confirmation)
    {
        return $password === $confirmation;
    }

    /**
     * Return redirect url by config
     *
     * @return mixed
     */
    protected function _loginPostRedirect()
    {
        $url = $this->_url->getUrl('customer/account');

        $object = ObjectManager::getInstance()->create(DataObject::class, ['url' => $url]);
        $this->_eventManager->dispatch('social_manager_get_login_redirect', [
            'object'  => $object,
            'request' => $this->_request
        ]);
        $url = $object->getUrl();

        return $url;
    }
}
