<?php

namespace Mageplaza\SocialLogin\Helper\Instagram;

use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\SocialLogin\Helper\Data as HelperData;
class Data extends HelperData
{
    const XML_PATH_INSTAGRAM_ENABLED = 'sociallogin/instagram/is_enabled';
    const XML_PATH_INSTAGRAM_CLIENT_ID = 'sociallogin/instagram/client_id';
    const XML_PATH_INSTAGRAM_CLIENT_SECRET = 'sociallogin/instagram/client_secret';
    const XML_PATH_INSTAGRAM_REDIRECT_URL = 'sociallogin/instagram/redirect_url';
    const XML_PATH_INSTAGRAM_SEND_PASSWORD = 'sociallogin/instagram/send_password';

    public function isEnabled($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_INSTAGRAM_ENABLED, $storeId);
    }

    public function getClientId($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_INSTAGRAM_CLIENT_ID, $storeId);
    }

    public function sendPassword($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_INSTAGRAM_SEND_PASSWORD, $storeId);
    }

    public function getClientSecret($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_INSTAGRAM_CLIENT_SECRET, $storeId);
    }

    public function getRedirectUrl($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_INSTAGRAM_REDIRECT_URL, $storeId);
    }

    public function getAuthUrl()
    {
        return $this->_getUrl('sociallogin/instagram/callback', array('_secure' => $this->isSecure(), 'auth' => 1));
    }

    public function getUrl($path)
    {
        return $this->_getUrl($path);
    }


}