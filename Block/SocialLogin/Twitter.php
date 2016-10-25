<?php
namespace Mageplaza\SocialLogin\Block\SocialLogin;

use Magento\Framework\View\Element\Template;
use Mageplaza\SocialLogin\Block\SocialLogin;

class Twitter extends SocialLogin
{
    public function getLoginUrl()
    {
        //return $this->getUrl('sociallogin/twitter/login', $this->isSecure());
        return $this->getUrl('sociallogin/twitter/login');
    }

    public function isEnabled()
    {
        if ($this->twitterHelper()->isEnabled() && $this->helperData()->isEnabled()) {
            return true;
        } else {
            return false;
        }
    }

    protected function twitterHelper()
    {
        return $this->objectManager->create('Mageplaza\SocialLogin\Helper\Twitter\Data');
    }
    
    protected function helperData()
    {
        return $this->objectManager->create('Mageplaza\SocialLogin\Helper\Data');
    }
}
