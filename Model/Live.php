<?php
namespace Mageplaza\SocialLogin\Model;
require_once("Live/OpenIDConnectClient.php");
class Live
{
	public function newLive()
	{
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$object        = $objectManager->get('Mageplaza\SocialLogin\Helper\Live\Data');
		$url           = $objectManager->get('Magento\Store\Model\StoreManagerInterface')
			->getStore()
			->getBaseUrl();
		$liveurl = $url.'sociallogin/live/login';
		$oidc = new \OpenIDConnectClient('https://login.microsoftonline.com/common/v2.0',
		$object->getClientId(),$object->getClientSecret(),$liveurl);
		$profile = $oidc->authenticate();
		return $profile;
	}

}
