<?php


namespace Mageplaza\SocialLogin\Model;

require_once("Lib/Hybrid/Auth.php");


class Twitter
{
    public function newLive()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $object        = $objectManager->get('Mageplaza\SocialLogin\Helper\Twitter\Data');
        $url           = $objectManager->get('Magento\Store\Model\StoreManagerInterface')
            ->getStore()
            ->getBaseUrl();
        $config = array(
            "base_url"  => $url . 'sociallogin/social/callback',
            "providers" => array(
                "Twitter" => array(
                    "enabled" => $object->isEnabled(),
                    "keys"    => array("key" => $object->getConsumerKey(), "secret" => $object->getConsumerSecret()),
                )));
        try {
            $hybridauth = new \Hybrid_Auth($config);
            return $hybridauth;
        } catch (Exception $e) {
            echo "Ooophs, we got an error: " . $e->getMessage();
        }
    }


}