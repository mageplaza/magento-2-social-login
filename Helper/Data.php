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
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\SocialLogin\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\Core\Helper\AbstractData as CoreHelper;
use Magento\Framework\Serialize\Serializer\Serialize;

/**
 * Class Data
 *
 * @package Mageplaza\SocialLogin\Helper
 */
class Data extends CoreHelper
{
    const CONFIG_MODULE_PATH = 'sociallogin';

    public $_serialize;

    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        Serialize $serialize
    ) {
        $this->_serialize = $serialize;

        parent::__construct($context, $objectManager, $storeManager);
    }

    /**
     * @param RequestInterface $request
     * @param $formId
     *
     * @return string
     */
    public function captchaResolve(RequestInterface $request, $formId)
    {
        $captchaParams = $request->getPost(\Magento\Captcha\Helper\Data::INPUT_NAME_FIELD_VALUE);

        return isset($captchaParams[$formId]) ? $captchaParams[$formId] : '';
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function canSendPassword($storeId = null)
    {
        return $this->getConfigGeneral('send_password', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getPopupEffect($storeId = null)
    {
        return $this->getConfigGeneral('popup_effect', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getStyleManagement($storeId = null)
    {
        $style = $this->getConfigGeneral('style_management', $storeId);
        if ($style === 'custom') {
            return $this->getCustomColor($storeId);
        }

        return $style;
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getCustomColor($storeId = null)
    {
        return $this->getConfigGeneral('custom_color', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getCustomCss($storeId = null)
    {
        return $this->getConfigGeneral('custom_css', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function requireRealEmail($storeId = null)
    {
        return $this->getConfigGeneral('fake_email_require', $storeId);
    }

    /**
     * @return mixed
     */
    public function isSecure()
    {
        return $this->getConfigValue('web/secure/use_in_frontend');
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function isReplaceAuthModal($storeId = null)
    {
        return $this->getConfigGeneral('authentication_popup', $storeId);
    }
}
