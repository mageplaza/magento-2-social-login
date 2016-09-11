<?php

namespace Mageplaza\SocialLogin\Block\SocialLogin;

use Mageplaza\SocialLogin\Block\SocialLogin;

class Instagram extends SocialLogin
{

    public function isEnabled()
    {
        if ($this->helperInstagram()->isEnabled() && $this->helperData()->isEnabled()) {
            return true;
        } else {
            return false;
        }
    }
    protected function helperInstagram()
    {
        return $this->objectManager->create('Mageplaza\SocialLogin\Helper\Instagram\Data');
    }

    protected function helperData()
    {
        return $this->objectManager->create('Mageplaza\SocialLogin\Helper\Data');
    }
    public function getLoginUrl()
    {
        return $this->getUrl('sociallogin/instagram/login');
    }
}