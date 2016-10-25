<?php
namespace Mageplaza\SocialLogin\Model;
require_once( "Lib/Hybrid/Auth.php" );
use Magento\Framework\Model\AbstractModel;
class Yahoo extends AbstractModel
{
    protected function _construct()
    {
        $this->_init('Mageplaza\SocialLogin\Model\ResourceModel\Yahoo');
    }

    public function newLive(){
        
      $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
      $object = $objectManager->get('Mageplaza\SocialLogin\Helper\Yahoo\Data');
      $url =  $objectManager->get('Magento\Store\Model\StoreManagerInterface')
            ->getStore()
            ->getBaseUrl();
       $config = array(
                  "base_url" => $url . "sociallogin/social/callback",
                  "providers" => array (
                  "Yahoo" => array (
                  "enabled" => $object->isEnabled(),
                  "keys"    => array ( "key" => $object->getConsumerKey(), "secret" => $object->getConsumerSecret() ),
                      
      )));

      try{
        $hybridauth = new \Hybrid_Auth( $config );
        return $hybridauth;
      }
      catch( Exception $e ){
        echo "Ooophs, we got an error: " . $e->getMessage();
      }
    }

    
}