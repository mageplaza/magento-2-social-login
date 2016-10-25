<?php

namespace Mageplaza\SocialLogin\Helper\Yahoo;

use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\SocialLogin\Helper\Data as HelperData;
class Data extends HelperData
{
    const XML_PATH_YAHOO_ENABLED = 'sociallogin/yahoo/is_enabled';
    const XML_PATH_YAHOO_APPLICATION_ID = 'sociallogin/yahoo/application_id';
    const XML_PATH_YAHOO_CONSUMER_SECRET = 'sociallogin/yahoo/consumer_secret';
    const XML_PATH_YAHOO_CONSUMER_KEY = 'sociallogin/yahoo/consumer_key';
    //const XML_PATH_YAHOO_REDIRECT_URL = 'sociallogin/yahoo/redirect_url';
    const XML_PATH_YAHOO_SEND_PASSWORD = 'sociallogin/yahoo/send_password';

    public function isEnabled($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_YAHOO_ENABLED, $storeId);
    }

    public function getApplicationId($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_YAHOO_APPLICATION_ID, $storeId);
    }

    public function sendPassword($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_YAHOO_SEND_PASSWORD, $storeId);
    }

    public function getConsumerSecret($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_YAHOO_CONSUMER_SECRET, $storeId);
    }

     public function getConsumerKey($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_YAHOO_CONSUMER_KEY, $storeId);
    }

    // public function getRedirectUrl($storeId = null)
    // {
    //     return $this->getConfigValue(self::XML_PATH_YAHOO_REDIRECT_URL, $storeId);
    // }

    public function getAuthUrl()
    {
        return $this->_getUrl('sociallogin/yahoo/callback', array('_secure' => $this->isSecure(), 'auth' => 1));
    }

    public function getUrl($path)
    {
        return $this->_getUrl($path);
    }


}