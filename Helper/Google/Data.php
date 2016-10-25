<?php
namespace Mageplaza\SocialLogin\Helper\Google;

use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\SocialLogin\Helper\Data as HelperData;
class Data extends HelperData
{
    const XML_PATH_GOOGLE_ENABLED = 'sociallogin/google/is_enabled';
    const XML_PATH_GOOGLE_CLIENT_ID = 'sociallogin/google/client_id';
    const XML_PATH_GOOGLE_CLIENT_SECRET = 'sociallogin/google/client_secret';
    const XML_PATH_GOOGLE_REDIRECT_URL = 'sociallogin/google/redirect_url';
    const XML_PATH_GOOGLE_SEND_PASSWORD = 'sociallogin/google/send_password';

    public function isEnabled($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_GOOGLE_ENABLED, $storeId);
    }

    public function getClientId($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_GOOGLE_CLIENT_ID, $storeId);
    }

    public function sendPassword($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_GOOGLE_SEND_PASSWORD, $storeId);
    }

    public function getClientSecret($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_GOOGLE_CLIENT_SECRET, $storeId);
    }

    public function getRedirectUrl($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_GOOGLE_REDIRECT_URL, $storeId);
    }

    public function getAuthUrl()
    {
        return $this->_getUrl('sociallogin/google/callback', array('_secure' => $this->isSecure(), 'auth' => 1));
    }

    public function getUrl($path)
    {
        return $this->_getUrl($path);
    }


}