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
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Raw|\Magento\Framework\Controller\ResultInterface|Login|void
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    public function execute()
    {
        $type = $this->apiHelper->setType($this->getRequest()->getParam('type', null));
        if (!$type) {
            $this->_forward('noroute');

            return;
        }
        $userProfile = $this->apiObject->getUserProfile($type);
        if (!$userProfile->identifier) {
            return $this->emailRedirect($type);
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
}