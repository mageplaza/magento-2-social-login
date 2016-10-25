<?php
namespace Mageplaza\SocialLogin\Block\SocialLogin;
//them
use Magento\Framework\View\Element\Template;
use Mageplaza\SocialLogin\Block\SocialLogin;

class Yahoo extends SocialLogin
{
    public function getLoginUrl()
    {
        return $this->getUrl('sociallogin/yahoo/login');
    }
    public function isEnabled()
    {
        if ($this->helperYahoo()->isEnabled() && $this->helperData()->isEnabled()) {
            return true;
        } else {
            return false;
        }
    }

    protected function helperYahoo()
    {
        return $this->objectManager->get('Mageplaza\SocialLogin\Helper\Yahoo\Data');
    }

     protected function helperData()
    {
       return $this->objectManager->get('Mageplaza\SocialLogin\Helper\Data');
    }
}