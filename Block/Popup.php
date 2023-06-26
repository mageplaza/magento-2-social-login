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
 * @category  Mageplaza
 * @package   Mageplaza_SocialLogin
 * @copyright Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license   https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\SocialLogin\Block;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Mageplaza\SocialLogin\Helper\Data as HelperData;

/**
 * Class Popup
 *
 * @package Mageplaza\SocialLogin\Block
 */
class Popup extends Template
{
    /**
     * @type HelperData
     */
    protected $helperData;

    /**
     * @type CustomerSession
     */
    protected $customerSession;

    /**
     * @param Context $context
     * @param HelperData $helperData
     * @param CustomerSession $customerSession
     * @param array $data
     */
    public function __construct(
        Context $context,
        HelperData $helperData,
        CustomerSession $customerSession,
        array $data = []
    ) {
        $this->helperData      = $helperData;
        $this->customerSession = $customerSession;

        parent::__construct($context, $data);
    }

    /**
     * @return bool|mixed
     */
    public function isEnabled()
    {
        if (str_contains($this->_request->getFullActionName(), 'customer_account')) {
            return false;
        }

        if ($this->helperData->isEnabled() && !$this->customerSession->isLoggedIn() && $this->helperData->getPopupLogin()) {
            return $this->helperData->getPopupLogin();
        }

        return false;
    }

    /**
     * Js params
     *
     * @return string
     */
    public function getFormParams()
    {
        $params = [
            'headerLink'    => $this->getHeaderLink(),
            'popupEffect'   => $this->getPopupEffect(),
            'formLoginUrl'  => $this->getFormLoginUrl(),
            'forgotFormUrl' => $this->getForgotFormUrl(),
            'createFormUrl' => $this->getCreateFormUrl(),
            'fakeEmailUrl'  => $this->getFakeEmailUrl(),
            'showFields'    => $this->getFieldCanShow(),
            'popupLogin'    => $this->isEnabled(),
            'actionName'    => $this->_request->getFullActionName(),
            'checkMode'     => $this->isCheckMode()
        ];

        return json_encode($params);
    }

    /**
     * @return mixed
     */
    public function getFieldCanShow()
    {
        return $this->helperData->getFieldCanShow();
    }

    /**
     * @return string
     */
    public function getHeaderLink()
    {
        $links = $this->helperData->getConfigGeneral('link_trigger');

        return $links ?: '.header .links, .section-item-content .header.links';
    }

    /**
     * @return mixed
     */
    public function getPopupEffect()
    {
        return $this->helperData->getPopupEffect();
    }

    /**
     * get Social Login Form Url
     *
     * @return string
     */
    public function getFormLoginUrl()
    {
        return $this->getUrl('customer/ajax/login', ['_secure' => $this->isSecure()]);
    }

    /**
     * @return string
     */
    public function getFakeEmailUrl()
    {
        return $this->getUrl('sociallogin/social/email', ['_secure' => $this->isSecure()]);
    }

    /**
     * @return string
     */
    public function getForgotFormUrl()
    {
        return $this->getUrl('sociallogin/popup/forgot', ['_secure' => $this->isSecure()]);
    }

    /**
     *  get Social Login Form Create Url
     *
     * @return string
     */
    public function getCreateFormUrl()
    {
        return $this->getUrl('sociallogin/popup/create', ['_secure' => $this->isSecure()]);
    }

    /**
     * get is secure url
     *
     * @return mixed
     */
    public function isSecure()
    {
        return (bool) $this->helperData->isSecure();
    }

    /**
     * @return mixed
     */
    public function getStyleManagement()
    {
        return $this->helperData->getStyleManagement();
    }

    /**
     * @return bool
     */
    public function isRequireMoreInfo()
    {
        return ($this->helperData->requiredMoreInfo() && $this->isEnabled());
    }

    /**
     * @return mixed
     */
    public function isCheckMode()
    {
        return (bool) $this->helperData->isCheckMode();
    }
}
