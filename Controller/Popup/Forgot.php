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

namespace Mageplaza\SocialLogin\Controller\Popup;

use Magento\Captcha\Helper\Data as CaptchaData;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\AccountManagement;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\SecurityViolationException;
use Mageplaza\SocialLogin\Helper\Data;

/**
 * Class Forgot
 *
 * @package Mageplaza\SocialLogin\Controller\Popup
 */
class Forgot extends Action
{
    /**
     * @var AccountManagementInterface
     */
    protected $customerAccountManagement;

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @type \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @type \Magento\Captcha\Helper\Data
     */
    protected $captchaHelper;

    /**
     * @type \Mageplaza\SocialLogin\Helper\Data
     */
    protected $socialHelper;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Api\AccountManagementInterface $customerAccountManagement
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Captcha\Helper\Data $captchaHelper
     * @param \Mageplaza\SocialLogin\Helper\Data $socialHelper
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        AccountManagementInterface $customerAccountManagement,
        Escaper $escaper,
        JsonFactory $resultJsonFactory,
        CaptchaData $captchaHelper,
        Data $socialHelper
    )
    {
        $this->session                   = $customerSession;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->escaper                   = $escaper;
        $this->resultJsonFactory         = $resultJsonFactory;
        $this->captchaHelper             = $captchaHelper;
        $this->socialHelper              = $socialHelper;

        parent::__construct($context);
    }

    /**
     * @return bool
     */
    public function checkCaptcha()
    {
        $formId       = 'user_forgotpassword';
        $captchaModel = $this->captchaHelper->getCaptcha($formId);
        if ($captchaModel->isRequired() && !$captchaModel->isCorrect($this->socialHelper->captchaResolve($this->getRequest(), $formId))) {
            return false;
        }

        return true;
    }

    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Zend_Validate_Exception
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();

        $result = [
            'success' => false,
            'message' => []
        ];

        if (!$this->checkCaptcha()) {
            $result['message'] = __('Incorrect CAPTCHA.');

            return $resultJson->setData($result);
        }

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $email = (string)$this->getRequest()->getPost('email');
        if ($email) {
            if (!\Zend_Validate::is($email, 'EmailAddress')) {
                $this->session->setForgottenEmail($email);
                $result['message'][] = __('Please correct the email address.');
            }

            try {
                $this->customerAccountManagement->initiatePasswordReset(
                    $email,
                    AccountManagement::EMAIL_RESET
                );
                $result['success']   = true;
                $result['message'][] = __('If there is an account associated with %1 you will receive an email with a link to reset your password.', $this->escaper->escapeHtml($email));
            } catch (NoSuchEntityException $e) {
                $result['success']   = true;
                $result['message'][] = __('If there is an account associated with %1 you will receive an email with a link to reset your password.', $this->escaper->escapeHtml($email));
                // Do nothing, we don't want anyone to use this action to determine which email accounts are registered.
            } catch (SecurityViolationException $exception) {
                $result['error']     = true;
                $result['message'][] = $exception->getMessage();
            } catch (\Exception $exception) {
                $result['error']     = true;
                $result['message'][] = __('We\'re unable to send the password reset email.');
            }
        }

        return $resultJson->setData($result);
    }
}
