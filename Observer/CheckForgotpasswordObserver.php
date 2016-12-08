<?php

namespace Mageplaza\SocialLogin\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Json\Helper\Data as JsonHelper;

class CheckForgotpasswordObserver implements ObserverInterface
{
    /**
     * @var \Magento\Captcha\Helper\Data
     */
    protected $_helper;

    /**
     * @var \Magento\Framework\App\ActionFlag
     */
    protected $_actionFlag;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    protected $redirect;

    /**
     * @var CaptchaStringResolver
     */
    protected $captchaStringResolver;
    protected $customerSession;
    protected $jsonHelper;

    /**
     * @param \Magento\Captcha\Helper\Data                      $helper
     * @param \Magento\Framework\App\ActionFlag                 $actionFlag
     * @param \Magento\Framework\Message\ManagerInterface       $messageManager
     * @param \Magento\Framework\App\Response\RedirectInterface $redirect
     * @param CaptchaStringResolver                             $captchaStringResolver
     */
    public function __construct(
        \Magento\Captcha\Helper\Data $helper,
        \Magento\Framework\App\ActionFlag $actionFlag,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\Response\RedirectInterface $redirect,
        CaptchaStringResolver $captchaStringResolver,
        CustomerSession $customerSession,
        JsonHelper $jsonHelper
    ) {
        $this->_helper               = $helper;
        $this->_actionFlag           = $actionFlag;
        $this->messageManager        = $messageManager;
        $this->redirect              = $redirect;
        $this->captchaStringResolver = $captchaStringResolver;
        $this->customerSession       = $customerSession;
        $this->jsonHelper            = $jsonHelper;
    }

    /**
     * Check Captcha On Forgot Password Page
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $formId       = 'user_forgotpassword';
        $captchaModel = $this->_helper->getCaptcha($formId);
        $result       = array();
        if ($captchaModel->isRequired()) {
            /** @var \Magento\Framework\App\Action\Action $controller */
            $controller = $observer->getControllerAction();
            if (!$captchaModel->isCorrect($this->captchaStringResolver->resolve($controller->getRequest(), $formId))) {
                $result['error']   = true;
                $result['message'] = __(
                    'Incorrect CAPTCHA.');
                $this->customerSession->setResultCaptcha($result);
            }
            $captchaModel->generate();
            $result['imgSrc'] = $captchaModel->getImgSrc();
        }

        return $this;
    }
}
