<?php

namespace Mageplaza\SocialLogin\Model;
require_once( "Lib/Hybrid/Auth.php" );
require_once ("Lib/Hybrid/Endpoint.php");
use Magento\Framework\Model\AbstractModel;
class Social extends AbstractModel
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('Mageplaza\SocialLogin\Model\ResourceModel\Social');
    }
    public function getEndpoint(){
      $endPoint = new \Hybrid_Endpoint();
      return $endPoint;
    }
    
}