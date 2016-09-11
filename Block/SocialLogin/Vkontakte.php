<?php
namespace Mageplaza\SocialLogin\Block\SocialLogin;
//them
use Magento\Framework\View\Element\Template;
use Mageplaza\SocialLogin\Block\SocialLogin;

class Vkontakte extends SocialLogin
{
	public function getLoginUrl()
    {
        return $this->getUrl('sociallogin/vkontakte/login');	 
    }

    public function isEnabled()
    {
        if ($this->helperVkontakte()->isEnabled() && $this->helperData()->isEnabled()) {
            return true;
        } else {
            return false;
        }
    }

    protected function helperVkontakte()
    {
        return $this->objectManager->get('Mageplaza\SocialLogin\Helper\Vkontakte\Data');
    }

     protected function helperData()
    {
       return $this->objectManager->get('Mageplaza\SocialLogin\Helper\Data');
    }

}