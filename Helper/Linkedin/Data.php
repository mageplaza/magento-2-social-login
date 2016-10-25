<?php

namespace Mageplaza\SocialLogin\Helper\Linkedin;

use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\SocialLogin\Helper\Data as HelperData;

class Data extends HelperData
{
    const XML_PATH_LINKEDIN_ENABLED = 'sociallogin/linkedin/is_enabled';
    const XML_PATH_LINKEDIN_API_KEY = 'sociallogin/linkedin/api_key';
    const XML_PATH_LINKEDIN_CLIENT_KEY = 'sociallogin/linkedin/client_key';
    const XML_PATH_LINKEDIN_SEND_PASSWORD = 'sociallogin/linkedin/send_password';

    public function isEnabled($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_LINKEDIN_ENABLED, $storeId);
    }

    public function getApiKey($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_LINKEDIN_API_KEY, $storeId);
    }

    public function getClientKey($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_LINKEDIN_CLIENT_KEY, $storeId);
    }
    public function sendPassword($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_LINKEDIN_SEND_PASSWORD, $storeId);
    }

    public function generateRandomString($length = 3)
    {
        $characters       = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString     = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    public function getModelCustomer()
    {
        return $this->objectManager->create(
            'Magento\Customer\Model\Customer'
        );
    }
    public function getAuthUrl()
    {
        return $this->_getUrl('sociallogin/linkedin/callback', array('_secure' => $this->isSecure(), 'auth' => 1));
    }


}