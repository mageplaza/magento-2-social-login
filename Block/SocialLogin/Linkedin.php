<?php
namespace Mageplaza\SocialLogin\Block\SocialLogin;

use Magento\Framework\View\Element\Template;
use Mageplaza\SocialLogin\Block\SocialLogin;

class Linkedin extends SocialLogin
{
    public function getLoginUrl()
    {
        return $this->getUrl('sociallogin/linkedin/login');
    }
    /**
 	 * @return mixed
 	 */
 	public function isEnabled()
 	{
		if ($this->linkedinHelper()->isEnabled() && $this->helperData()->isEnabled()) {
			return true;
		} else {
			return false;
		}
 	}
 
 	/**
 	 * @return mixed
 	 */
 	protected function linkedinHelper()
 	{
 		return $this->objectManager->create('Mageplaza\SocialLogin\Helper\Linkedin\Data');
 	}
	
	protected function helperData()
	{
		return $this->objectManager->create('Mageplaza\SocialLogin\Helper\Data');
	}

}
