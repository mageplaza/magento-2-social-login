<?php
namespace Mageplaza\SocialLogin\Block\SocialLogin;
//them
use Magento\Framework\View\Element\Template;
use Mageplaza\SocialLogin\Block\SocialLogin;

class Amazon extends SocialLogin
{
	public function getLoginUrl()
	{
		return $this->getUrl('sociallogin/amazon/login');
		
	}

	public function isEnabled()
    {
		if ($this->amazonHelper()->isEnabled() && $this->helperData()->isEnabled()) {
			return true;
		} else {
			return false;
		}
    }

    public function getClientId(){
    	return $this->helperData()->getClientId();
    }

    protected function amazonHelper()
    {
        return $this->objectManager->create('Mageplaza\SocialLogin\Helper\Amazon\Data');
    }
	
	protected function helperData()
	{
		return $this->objectManager->get('Mageplaza\SocialLogin\Helper\Data');
	}
}