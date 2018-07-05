<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_SocialLogin
 * @copyright   Copyright (c) Mageplaza (http://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\SocialLogin\Helper;

use Magento\Store\Model\ScopeInterface;
use Mageplaza\SocialLogin\Helper\Data as HelperData;

/**
 * Class Social
 *
 * @package Mageplaza\SocialLogin\Helper
 */
class Social extends HelperData
{
    /**
     * @type
     */
    protected $_type;

    /**
     * @param null $type
     * @return null
     */
    public function setType($type)
    {
        $listTypes = $this->getSocialTypes();
        if (!$type || !array_key_exists($type, $listTypes)) {
            return null;
        }

        $this->_type = $type;

        return $listTypes[$type];
    }

    /**
     * @return array
     */
    public function getSocialTypes()
    {
        $socialTypes = $this->_getSocialTypes();
        uksort($socialTypes, function ($a, $b) {
            $sortA = $this->getConfigValue("sociallogin/{$a}/sort_order") ?: 0;
            $sortB = $this->getConfigValue("sociallogin/{$b}/sort_order") ?: 0;
            if ($sortA == $sortB) {
                return 0;
            }

            return ($sortA < $sortB) ? -1 : 1;
        });

        return $socialTypes;
    }

    /**
     * @param $type
     * @return array
     */
    public function getSocialConfig($type)
    {
        $apiData = [
            'Facebook'  => ["trustForwarded" => false, 'scope' => 'email, public_profile'],
            'Twitter'   => ["includeEmail" => true],
            'LinkedIn'  => ["fields" => ['id', 'first-name', 'last-name', 'email-address']],
            'Vkontakte' => ['wrapper' => ['class' => '\Mageplaza\SocialLogin\Model\Providers\Vkontakte']],
            'Instagram' => ['wrapper' => ['class' => '\Mageplaza\SocialLogin\Model\Providers\Instagram']],
            'Github'    => ['wrapper' => ['class' => '\Mageplaza\SocialLogin\Model\Providers\GitHub']],
            'Amazon'    => ['wrapper' => ['class' => '\Mageplaza\SocialLogin\Model\Providers\Amazon']],
            'Google'    => ['scope' => 'https://www.googleapis.com/auth/plus.login https://www.googleapis.com/auth/plus.me https://www.googleapis.com/auth/plus.profile.emails.read']
        ];

        if ($type && array_key_exists($type, $apiData)) {
            return $apiData[$type];
        }

        return [];
    }

    /**
     * @return array|null
     */
    public function getAuthenticateParams($type)
    {
        return null;
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function isEnabled($storeId = null)
    {
        return $this->getConfigValue("sociallogin/{$this->_type}/is_enabled", $storeId);
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function getAppId($storeId = null)
    {
        $appId = trim($this->getConfigValue("sociallogin/{$this->_type}/app_id", $storeId));

        return $appId;
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function getAppSecret($storeId = null)
    {
        $appSecret = trim($this->getConfigValue("sociallogin/{$this->_type}/app_secret", $storeId));

        return $appSecret;
    }

    /**
     * @param $type
     * @return mixed|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAuthUrl($type)
    {
        $authUrl = $this->getBaseAuthUrl();

        $type = $this->setType($type);
        switch ($type) {
            case 'Facebook':
                $param = 'hauth_done=' . $type;
                break;
            case 'Live':
                $param = null;
                break;
            case 'Yahoo':
                return $this->getDomainUrl();
                break;
            default:
                $param = 'hauth.done=' . $type;
        }

        return $authUrl . ($param ? (strpos($authUrl, '?') ? '&' : '?') . $param : '');
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getDomainUrl()
    {
        $url   = $this->getBaseAuthUrl();
        $parse = parse_url($url);

        return $parse['host'];
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getBaseAuthUrl()
    {
        $storeId = $this->getScopeUrl();
        /** @var \Magento\Store\Model\Store $store */
        $store = $this->storeManager->getStore($storeId);

        return $this->_getUrl('sociallogin/social/callback', [
            '_nosid'  => true,
            '_scope'  => $storeId,
            '_secure' => $store->isUrlSecure()
        ]);
    }

    /**
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getScopeUrl()
    {
        $scope = $this->_request->getParam(ScopeInterface::SCOPE_STORE) ?: $this->storeManager->getStore()->getId();

        if ($website = $this->_request->getParam(ScopeInterface::SCOPE_WEBSITE)) {
            $scope = $this->storeManager->getWebsite($website)->getDefaultStore()->getId();
        }

        return $scope;
    }

    /**
     * @return array
     */
    public function _getSocialTypes()
    {
        return [
            'facebook'   => 'Facebook',
            'google'     => 'Google',
            'twitter'    => 'Twitter',
            'amazon'     => 'Amazon',
            'linkedin'   => 'LinkedIn',
            'yahoo'      => 'Yahoo',
            'foursquare' => 'Foursquare',
            'vkontakte'  => 'Vkontakte',
            'instagram'  => 'Instagram',
            'github'     => 'Github'
        ];
    }
}
