<?php

namespace Mageplaza\SocialLogin\Model;
//require_once 'Linkedin/Author/OAuth.php';

require_once("Lib/Hybrid/Auth.php");


class Linkedin
{
    public function newLive()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $object        = $objectManager->get('Mageplaza\SocialLogin\Helper\Linkedin\Data');
        $url           = $objectManager->get('Magento\Store\Model\StoreManagerInterface')
            ->getStore()
            ->getBaseUrl();
        $config = array(
            "base_url"  => $url . 'sociallogin/social/callback',
            "providers" => array(
                "LinkedIn" => array(
                    "enabled" => $object->isEnabled(),
                    "keys"    => array("key" => $object->getApiKey(), "secret" => $object->getClientKey()),
                )));
        try {
            $hybridauth = new \Hybrid_Auth($config);
            return $hybridauth;
        } catch (Exception $e) {
            echo "Ooophs, we got an error: " . $e->getMessage();
        }
    }



}