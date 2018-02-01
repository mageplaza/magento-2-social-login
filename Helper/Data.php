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
 * @copyright   Copyright (c) 2016 Mageplaza (http://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */
namespace Mageplaza\SocialLogin\Helper;

use Mageplaza\Core\Helper\AbstractData as CoreHelper;
use Mageplaza\SocialLoginPro\Model\Config\Source\Captcha;

/**
 * Class Data
 *
 * @package Mageplaza\SocialLogin\Helper
 */
class Data extends CoreHelper
{
    const CONFIG_MODULE_PATH = 'sociallogin';

    const XML_PATH_GENERAL_ENABLED = 'sociallogin/general/is_enabled';
    const XML_PATH_GENERAL = 'sociallogin/general/';
    const XML_PATH_GENERAL_POPUP_LEFT = 'sociallogin/general/left';
    const XML_PATH_GENERAL_STYLE_MANAGEMENT = 'sociallogin/general/style_management';
    const XML_PATH_CAPTCHA_ENABLE = 'sociallogin/captcha/is_enabled';
    const XML_PATH_SECURE_IN_FRONTEND = 'web/secure/use_in_frontend';

    /**
     * @param null $storeId
     * @return mixed
     */
    public function isEnabled($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_GENERAL_ENABLED, $storeId);
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function isCaptchaEnabled($storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_CAPTCHA_ENABLE, $storeId);
    }

    /**
     * @param \Magento\Framework\App\RequestInterface $request
     * @param                                         $formId
     * @return string
     */
    public function captchaResolve($request, $formId)
    {
        $captchaParams = $request->getPost(\Magento\Captcha\Helper\Data::INPUT_NAME_FIELD_VALUE);

        return isset($captchaParams[$formId]) ? $captchaParams[$formId] : '';
    }

    /**
     * @param      $code
     * @param null $storeId
     * @return mixed
     */
    public function getGeneralConfig($code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_GENERAL . $code, $storeId);
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function canSendPassword($storeId = null)
    {
        return $this->getGeneralConfig('send_password', $storeId);
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function getPopupEffect($storeId = null)
    {
        return $this->getGeneralConfig('popup_effect', $storeId);
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function getStyleManagement($storeId = null)
    {
        $style = $this->getGeneralConfig('style_management', $storeId);
        if ($style == 'custom') {
            return $this->getCustomColor($storeId);
        }

        return $style;
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function getCustomColor($storeId = null)
    {
        return $this->getGeneralConfig('custom_color', $storeId);
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function getCustomCss($storeId = null)
    {
        return $this->getGeneralConfig('custom_css', $storeId);
    }

    /**
     * @return mixed
     */
    public function isSecure()
    {
        $isSecure = $this->getConfigValue(self::XML_PATH_SECURE_IN_FRONTEND);

        return $isSecure;
    }

    public function isSocialLoginProEnable()
    {
        return $this->isModuleOutputEnabled('Mageplaza_SocialLoginPro');
    }

    public function checkCaptcha(
        \Magento\Captcha\Helper\Data $captchaHelper,
        \Magento\Framework\App\RequestInterface $request,
        $formId
    ) {
        if ($this->isSocialLoginProEnable()) {
            $enabled = $this->getGeneralConfig('captcha/enabled', $storeId = null);
            if ($enabled == Captcha::TYPE_DEFAULT) {
               return $this->checkCaptchaDefault($captchaHelper, $request, $formId);
            }else{
                return true;
            }
        }
        return $this->checkCaptchaDefault($captchaHelper, $request, $formId);
    }

    public function checkCaptchaDefault(
        \Magento\Captcha\Helper\Data $captchaHelper,
        \Magento\Framework\App\RequestInterface $request,
        $formId
    ) {
        $captchaModel = $captchaHelper->getCaptcha($formId);
        if ($captchaModel->isRequired()) {
            if (!$captchaModel->isCorrect($this->captchaResolve($request, $formId))) {
                return false;
            }
            $captchaModel->generate();
            $result['imgSrc']  = $captchaModel->getImgSrc();
            return true;
        }
        return true;
    }
}