<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Mageplaza\SocialLogin\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Json\Helper\Data as JsonHelper;

class CheckUserLoginObserver implements ObserverInterface
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
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $_session;

    /**
     * @var CaptchaStringResolver
     */
    protected $captchaStringResolver;

    /**
     * Customer data
     *
     * @var \Magento\Customer\Model\Url
     */
    protected $_customerUrl;
    protected $customerSession;
    protected $jsonHelper;
    protected $captchaWord;

    /**
     * @param \Magento\Captcha\Helper\Data                       $helper
     * @param \Magento\Framework\App\ActionFlag                  $actionFlag
     * @param \Magento\Framework\Message\ManagerInterface        $messageManager
     * @param \Magento\Framework\Session\SessionManagerInterface $session
     * @param CaptchaStringResolver                              $captchaStringResolver
     * @param \Magento\Customer\Model\Url                        $customerUrl
     */
    public function __construct(
        \Magento\Captcha\Helper\Data $helper,
        \Magento\Framework\App\ActionFlag $actionFlag,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Session\SessionManagerInterface $session,
        CaptchaStringResolver $captchaStringResolver,
        \Magento\Customer\Model\Url $customerUrl,
        CustomerSession $customerSession,
        JsonHelper $jsonHelper
    ) {
        $this->_helper               = $helper;
        $this->_actionFlag           = $actionFlag;
        $this->messageManager        = $messageManager;
        $this->_session              = $session;
        $this->captchaStringResolver = $captchaStringResolver;
        $this->_customerUrl          = $customerUrl;
        $this->customerSession       = $customerSession;
        $this->jsonHelper            = $jsonHelper;
    }

    /**
     * Check Captcha On User Login Page
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $event        = $observer->getEvent();
        $formId       = 'user_login';
        $captchaModel = $this->_helper->getCaptcha($formId);
        $controller   = $observer->getControllerAction();

        $loginParams = $controller->getRequest()->getPost();
        $login       = array_key_exists('username', $loginParams) ? $loginParams['username'] : null;
        $result      = array();
        if ($captchaModel->isRequired($login)) {
            $word = $this->captchaStringResolver->resolve($controller->getRequest(), $formId);
            if ($captchaWord = $captchaModel->getWord()) {
                $this->captchaWord = $captchaWord;
            }
            if ($captchaModel->isCaseSensitive()) {
                $this->captchaWord = strtolower($this->captchaWord);
                $word              = strtolower($word);
            }
            if (!($this->captchaWord === $word)) {
                $result['error']   = true;
                $result['message'] = __(
                    'Incorrect CAPTCHA.');

                $captchaModel->generate();
                $result['imgSrc'] = $captchaModel->getImgSrc();
            }
            $this->customerSession->setResultCaptcha($result);

        }
        $captchaModel->logAttempt($login);
    }
}
