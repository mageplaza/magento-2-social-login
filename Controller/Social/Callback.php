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
        if ($this->checkRequest('hauth_start', false) && (
                $this->checkRequest('error_reason', 'user_denied')
                && $this->checkRequest('error', 'access_denied')
                && $this->checkRequest('error_code', '200')
                && $this->checkRequest('hauth_done', 'Facebook')
                || ($this->checkRequest('hauth_done', 'Twitter') && $this->checkRequest('denied'))
            )) {
            return $this->_appendJs(sprintf("<script>window.close();</script>"));
        }

        \Hybrid_Endpoint::process();
    }

    /**
     * @param $key
     * @param null $value
     * @return bool|mixed
     */
    public function checkRequest($key, $value = null)
    {
        $param = $this->getRequest()->getParam($key, false);

        if ($value) {
            return $param == $value;
        }

        return $param;
    }
}