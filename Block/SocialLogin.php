<?php
namespace Mageplaza\SocialLogin\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Mageplaza\SocialLogin\Helper\Data as HelperData;
use Magento\Framework\ObjectManagerInterface;
use Magento\Customer\Model\Session as CustomerSession;


class SocialLogin extends Template
{
    protected $storeManager;
    protected $helperData;
    protected $objectFactory;
    protected $customerSession;

    public function __construct(
        Context $context,
        HelperData $helperData,
        ObjectManagerInterface $objectManager,
        CustomerSession $customerSession,
        array $data = []
    ) {
        $this->helperData      = $helperData;
        $this->objectManager   = $objectManager;
        $this->customerSession = $customerSession;
        $this->storeManager      = $context->getStoreManager();
        parent::__construct($context, $data);
    }

    /**
     * get is secure url
     *
     * @return mixed
     */
    public function isSecure()
    {
        return $this->helperData->isSecure();
    }

    /**
     * get Social Login Form Url
     *
     * @return string
     */
    public function getFormLoginUrl()
    {
        return $this->getUrl('sociallogin/popup/login', ['_secure' => $this->isSecure()]);
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
     * get Social Login Forgot Url
     */
    public function getForgotFormUrl()
    {
        return $this->getUrl('sociallogin/popup/forgot', ['_secure' => $this->isSecure()]);
    }

    public function getPopupEffect()
    {
        return $this->helperData->getPopupEffect();
    }

    public function isEnabled()
    {
        return $this->helperData->isEnabled() && !$this->customerSession->isLoggedIn();
    }

    public function getStyleColor()
    {
        return $this->helperData->getStyleManagement();
    }

    public function getCustomCss()
    {
        $storeId   = $this->storeManager->getStore()->getId(); //add
        return $this->helperData->getCustomCss($storeId);
    }
}
