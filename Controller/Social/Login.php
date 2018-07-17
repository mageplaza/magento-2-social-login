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

/**
 * Class Login
 * @package Mageplaza\SocialLogin\Controller\Social
 */
class Login extends AbstractSocial
{
    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Raw|\Magento\Framework\Controller\ResultInterface|void
     * @throws \Exception
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    public function execute()
    {
        if ($this->checkCustomerLogin() && $this->session->isLoggedIn()) {
            $this->_redirect('customer/account');
            return;
        }

        $type = $this->apiHelper->setType($this->getRequest()->getParam('type', null));
        if (!$type) {
            $this->_forward('noroute');

            return;
        }

        try {
            $userProfile = $this->apiObject->getUserProfile($type);
            if (!$userProfile->identifier) {
                return $this->emailRedirect($type);
            }
        } catch (\Exception $e) {
            $this->setBodyResponse($e->getMessage());

            return;
        }

        $customer = $this->apiObject->getCustomerBySocial($userProfile->identifier, $type);
        if (!$customer->getId()) {
            if (!$userProfile->email && $this->apiHelper->requireRealEmail()) {
                $this->session->setUserProfile($userProfile);

                return $this->_appendJs(sprintf("<script>window.close();window.opener.fakeEmailCallback('%s');</script>", $type));
            }
            $customer = $this->createCustomerProcess($userProfile, $type);
        }
        $this->refresh($customer);

        return $this->_appendJs();
    }

    /**
     * @return bool
     */
    public function checkCustomerLogin()
    {
        return true;
    }

    /**
     * @param $message
     */
    protected function setBodyResponse($message)
    {
        $content = '<html><head></head><body>';
        $content .= '<div class="message message-error">' . __("Ooophs, we got an error: %1", $message) . '</div>';
        $content .= <<<Style
<style type="text/css">
    .message{
        background: #fffbbb;
        border: none;
        border-radius: 0;
        color: #333333;
        font-size: 1.4rem;
        margin: 0 0 10px;
        padding: 1.8rem 4rem 1.8rem 1.8rem;
        position: relative;
        text-shadow: none;
    }
    .message-error{
        background:#ffcccc;
    }
</style>
Style;
        $content .= '</body></html>';

        $this->getResponse()->setBody($content);
    }
}