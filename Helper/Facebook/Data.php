<?php

namespace Mageplaza\SocialLogin\Helper\Facebook;

use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\SocialLogin\Helper\Data as HelperData;

class Data extends HelperData
{
    const XML_PATH_FACEBOOK_ENABLED = 'sociallogin/facebook/is_enabled';
    const XML_PATH_FACEBOOK_APP_ID = 'sociallogin/facebook/app_id';
    const XML_PATH_FACEBOOK_APP_SECRET = 'sociallogin/facebook/app_secret';
    const XML_PATH_FACEBOOK_REDIRECT_URL = 'sociallogin/facebook/redirect_url';
    const XML_PATH_FACEBOOK_BUTTON_IMAGE = 'sociallogin/facebook/button_image';
    const XML_PATH_FACEBOOK_BUTTON_IMAGE_LABEL = 'sociallogin/facebook/button_image_label';
    const XML_PATH_FACEBOOK_POSITION_NUMBER = 'sociallogin/facebook/position_number';
    const XML_PATH_FACEBOOK_SEND_PASSWORD = 'sociallogin/facebook/send_password';
    const XML_PATH_SECURE_IN_FRONTEND = 'web/secure/use_in_frontend';

    public function isEnabled($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_FACEBOOK_ENABLED, $storeId);
    }

    public function getAppId($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_FACEBOOK_APP_ID, $storeId);
    }

    public function sendPassword($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_FACEBOOK_SEND_PASSWORD, $storeId);
    }

    public function getAppSecret($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_FACEBOOK_APP_SECRET, $storeId);
    }

    public function getRedirectUrl($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_FACEBOOK_REDIRECT_URL, $storeId);
    }

    public function getButtonImage($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_FACEBOOK_BUTTON_IMAGE, $storeId);
    }

    public function getButtonImageLabel($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_FACEBOOK_BUTTON_IMAGE_LABEL, $storeId);
    }

    public function getPositionNumber($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_FACEBOOK_POSITION_NUMBER, $storeId);
    }

    public function getAuthUrl()
    {
        return $this->_getUrl('sociallogin/facebook/callback', array('_secure' => $this->isSecure(), 'auth' => 1));
    }

}