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
use Magento\Customer\Model\AccountManagement;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\SecurityViolationException;
use Magento\Framework\Validator\ValidateException;
use Magento\Framework\Validator\ValidatorChain;
use Magento\Framework\Validator\EmailAddress;
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
     * @param Context $context
     * @param Session $customerSession
     * @param AccountManagementInterface $customerAccountManagement
     * @param Escaper $escaper
     * @param JsonFactory $resultJsonFactory
     * @param CaptchaData $captchaHelper
     * @param Data $socialHelper
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        AccountManagementInterface $customerAccountManagement,
        Escaper $escaper,
        JsonFactory $resultJsonFactory,
        CaptchaData $captchaHelper,
        Data $socialHelper
    ) {
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
        $resolve      = $this->socialHelper->captchaResolve($this->getRequest(), $formId);

        return !($captchaModel->isRequired() && !$captchaModel->isCorrect($resolve));
    }

    /**
     * @return ResponseInterface|Json|ResultInterface
     * @throws ValidateException
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();

        $result = [
            'success' => false,
            'message' => []
        ];

        if (!$this->socialHelper->isEnabledGGRecaptcha() && !$this->checkCaptcha()) {
            $result['message'] = __('Incorrect CAPTCHA.');

            return $resultJson->setData($result);
        }

        /**
         * @var Redirect $resultRedirect
         */
        $email = (string) $this->getRequest()->getPost('email');

        if ($email) {
            if (!ValidatorChain::is($email, EmailAddress::class)) {
                $this->session->setForgottenEmail($email);
                $result['message'][] = __('Please correct the email address.');
            }

            try {
                $this->customerAccountManagement->initiatePasswordReset(
                    $email,
                    AccountManagement::EMAIL_RESET
                );
                $result['success']   = true;
                $result['message'][] = __(
                    'If there is an account associated with %1 you will receive an email with a link to reset your password.',
                    $this->escaper->escapeHtml($email)
                );
            } catch (NoSuchEntityException $e) {
                $result['success']   = true;
                $result['message'][] = __(
                    'If there is an account associated with %1 you will receive an email with a link to reset your password.',
                    $this->escaper->escapeHtml($email)
                );
                // Do nothing, we don't want anyone to use this action to determine which email accounts are registered.
            } catch (SecurityViolationException $exception) {
                $result['error']     = true;
                $result['message'][] = $exception->getMessage();
            } catch (Exception $exception) {
                $result['error']     = true;
                $result['message'][] = __('We\'re unable to send the password reset email.');
            }
        }

        return $resultJson->setData($result);
    }
}
