<?php
/**
 * MageGiant
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magegiant.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magegiant.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @copyright   Copyright (c) 2014 Magegiant (http://magegiant.com/)
 * @license     http://magegiant.com/license-agreement.html
 */
namespace Mageplaza\SocialLogin\Block\SocialLogin;

use Mageplaza\SocialLogin\Block\SocialLogin;

class Google extends SocialLogin
{
    public function isEnabled()
    {
        if ($this->helperGoogle()->isEnabled() && $this->helperData()->isEnabled()) {
            return true;
        } else {
            return false;
        }
    }

    protected function helperGoogle()
    {
        return $this->objectManager->create('Mageplaza\SocialLogin\Helper\Google\Data');
    }

    protected function helperData()
    {
        return $this->objectManager->create('Mageplaza\SocialLogin\Helper\Data');
    }

    public function getLoginUrl()
    {
        return $this->getUrl('sociallogin/google/login');
    }

}