<?php
namespace Mageplaza\SocialLogin\Block\SocialLogin;
//them
use Magento\Framework\View\Element\Template;
use Mageplaza\SocialLogin\Block\SocialLogin;

class Github extends SocialLogin
{
	public function getLoginUrl()
	{
		return $this->getUrl('sociallogin/github/login');
		
	}

	public function isEnabled()
    {
		if ($this->githubHelper()->isEnabled() && $this->helperData()->isEnabled()) {
			return true;
		} else {
			return false;
		}
    }

    protected function githubHelper()
    {
        return $this->objectManager->create('Mageplaza\SocialLogin\Helper\Github\Data');
    }
	
	protected function helperData()
	{
		return $this->objectManager->get('Mageplaza\SocialLogin\Helper\Data');
	}
}