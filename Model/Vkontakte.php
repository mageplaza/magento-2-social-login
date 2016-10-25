<?php
namespace Mageplaza\SocialLogin\Model;

require_once("Lib/Hybrid/Auth.php");


class Vkontakte
{
	public function newLive()
	{
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$object        = $objectManager->get('Mageplaza\SocialLogin\Helper\Vkontakte\Data');
		$url           = $objectManager->get('Magento\Store\Model\StoreManagerInterface')
			->getStore()
			->getBaseUrl();
		$config = array(
			"base_url"  => $url . 'sociallogin/social/callback',
			"providers" => array(
				"Vkontakte" => array(
					"enabled" => $object->isEnabled(),
					"keys"    => array("id" => $object->getClientId(), "secret" => $object->getClientSecret()),
				)));
		try {
			$hybridauth = new \Hybrid_Auth($config);
			return $hybridauth;
		} catch (Exception $e) {
			echo "Ooophs, we got an error: " . $e->getMessage();
		}
	}

	
}