<?php

namespace Mageplaza\SocialLogin\Helper\Twitter;

use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\SocialLogin\Helper\Data as HelperData;

class Data extends HelperData
{
    const XML_PATH_TWITTER_ENABLED = 'sociallogin/twitter/is_enabled';
    const XML_PATH_TWITTER_CONSUMER_KEY = 'sociallogin/twitter/consumer_key';
    const XML_PATH_TWITTER_CONSUMER_SECRET = 'sociallogin/twitter/consumer_secret';
    const XML_PATH_TWITTER_SEND_PASSWORD = 'sociallogin/twitter/send_password';


    public function isEnabled($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_TWITTER_ENABLED, $storeId);
    }

    public function getConsumerKey($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_TWITTER_CONSUMER_KEY, $storeId);
    }

    public function getConsumerSecret($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_TWITTER_CONSUMER_SECRET, $storeId);
    }

    public function getAuthUrl()
    {
        return $this->_getUrl('sociallogin/twitter/callback', array('_secure' => $this->isSecure()));
    }

    public function sendPassword($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_TWITTER_SEND_PASSWORD, $storeId);
    }
}