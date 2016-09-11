<?php
namespace Mageplaza\SocialLogin\Block\SocialLogin;
//them
use Magento\Framework\View\Element\Template;
use Mageplaza\SocialLogin\Block\SocialLogin;
    
class Foursquare extends SocialLogin
{
	public function getLoginUrl()
	{
		return $this->getUrl('sociallogin/foursquare/login');
		
	}

	public function isEnabled()
    {
		if ($this->foursquareHelper()->isEnabled() && $this->helperData()->isEnabled()) {
			return true;
		} else {
			return false;
		}
    }

    protected function foursquareHelper()
    {
        return $this->objectManager->create('Mageplaza\SocialLogin\Helper\Foursquare\Data');
    }

	protected function helperData()
	{
		return $this->objectManager->get('Mageplaza\SocialLogin\Helper\Data');
	}
}