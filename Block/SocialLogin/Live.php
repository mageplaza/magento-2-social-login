<?php
namespace Mageplaza\SocialLogin\Block\SocialLogin;
//them
use Magento\Framework\View\Element\Template;
use Mageplaza\SocialLogin\Block\SocialLogin;

class Live extends SocialLogin
{
	public function getLoginUrl()
	{
		return $this->getUrl('sociallogin/live/login');
		
	}

	public function isEnabled()
    {
		if ($this->liveHelper()->isEnabled() && $this->helperData()->isEnabled()) {
			return true;
		} else {
			return false;
		}
    }

    protected function liveHelper()
    {
        return $this->objectManager->create('Mageplaza\SocialLogin\Helper\Live\Data');
    }
	
	protected function helperData()
	{
		return $this->objectManager->get('Mageplaza\SocialLogin\Helper\Data');
	}
}