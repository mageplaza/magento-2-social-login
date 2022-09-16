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

use Hybridauth\Endpoint as Hybrid_Endpoint;

/**
 * Class Callback
 *
 * @package Mageplaza\SocialLogin\Controller\Social
 */
class Callback extends AbstractSocial
{
    /**
     * @inheritdoc
     */
    public function execute()
    {
        $param = $this->getRequest()->getParams();
        $type  = $this->getRequest()->getParam('hauth_done');
        if (isset($param['live.php'])) {
            $request = array_merge($param, ['hauth_done' => 'Live']);
        }
        if ($this->checkRequest('hauth_start', false)
            && (($this->checkRequest('error_reason', 'user_denied')
                    && $this->checkRequest('error', 'access_denied')
                    && $this->checkRequest('error_code', '200')
                    && $this->checkRequest('hauth_done', 'Facebook'))
                || ($this->checkRequest('hauth_done', 'Twitter') && $this->checkRequest('denied')))
        ) {
            return $this->_appendJs(sprintf('<script>window.close();</script>'));
        }

        $userProfile = $this->apiObject->getUserProfile($type, null, true, $param);
        
        try {
            $userProfile = $this->apiObject->getUserProfile($type);

            if (!$userProfile->identifier) {
                return $this->emailRedirect($type);
            }
        } catch (Exception $e) {
            $this->setBodyResponse($e->getMessage());

            return;
        }

        $customer     = $this->apiObject->getCustomerBySocial($userProfile->identifier, $type);
        $customerData = $this->customerModel->load($customer->getId());

        if (!$customer->getId()) {
            $requiredMoreInfo = (int)$this->apiHelper->requiredMoreInfo();

            if ((!$userProfile->email && $requiredMoreInfo === 2) || $requiredMoreInfo === 1) {
                $this->session->setUserProfile($userProfile);

                return $this->_appendJs(
                    sprintf(
                        "<script>window.close();window.opener.fakeEmailCallback('%s','%s','%s');</script>",
                        $type,
                        $userProfile->firstName,
                        $userProfile->lastName
                    )
                );
            }

            $customer = $this->createCustomerProcess($userProfile, $type);
        }
        if ($this->apiHelper->isCheckMode()) {
            if ($customerData->getData('password_hash') === null) {
                $userProfile->hash = '';
                $this->session->setUserProfile($userProfile);

                return $this->_appendJs(
                    sprintf(
                        "<script>window.close();window.opener.fakeEmailCallback('%s','%s','%s');</script>",
                        $type,
                        $userProfile->firstName,
                        $userProfile->lastName
                    )
                );
            }
        }
        $this->refresh($customer);

        return $this->_appendJs();
    }

    /**
     * @param $key
     * @param null $value
     *
     * @return bool|mixed
     */
    public function checkRequest($key, $value = null)
    {
        $param = $this->getRequest()->getParam($key, false);

        if ($value) {
            return $param === $value;
        }

        return $param;
    }
}
